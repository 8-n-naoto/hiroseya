"""
Laravel を起動せずに、マイグレーション / モデル / シーダーの整合性を検証する。

確認すること:
 1. モデルの #[Fillable([...])] の列が、マイグレーションに実在するか
 2. belongsTo / belongsToMany で指定した外部キー・中間テーブルが実在するか
 3. マイグレーションの外部キー参照先テーブルが実在するか（作成順も含む）
 4. DishSeeder の slug が重複していないか、カテゴリ slug が実在するか
 5. DishSeeder の価格が、元データ（xlsx）と一致するか
"""
import json
import re
import sys
from collections import OrderedDict
from pathlib import Path

# このスクリプトは docs/ に置かれている前提で、プロジェクトルートを親から求める。
APP = Path(__file__).resolve().parent.parent
errors = []
warnings = []


def err(msg):
    errors.append(msg)


def warn(msg):
    warnings.append(msg)


def pluralize(word):
    """Laravel の Str::plural に合わせた最低限の複数形。dish -> dishes"""
    if word.endswith(('s', 'x', 'z', 'ch', 'sh')):
        return word + 'es'
    if word.endswith('y') and word[-2:-1] not in 'aeiou':
        return word[:-1] + 'ies'
    return word + 's'


# ---------------------------------------------------------------- 1. schema
schema = OrderedDict()          # table -> set(columns)
table_order = []                # 作成順
foreign_refs = []               # (table, column, referenced_table, file)

COL_RE = re.compile(
    r"\$table->(id|string|text|longText|integer|unsignedInteger|unsignedBigInteger|"
    r"unsignedSmallInteger|unsignedTinyInteger|smallInteger|tinyInteger|boolean|date|"
    r"dateTime|time|timestamp|json|decimal|rememberToken|timestamps|softDeletes|"
    r"foreignId|char)\(\s*(?:'([^']+)')?"
)

for path in sorted((APP / 'database/migrations').glob('*.php')):
    src = path.read_text(encoding='utf-8')

    for m in re.finditer(r"Schema::create\('([^']+)',\s*function \(Blueprint \$table\) \{(.*?)\n        \}\);",
                         src, re.S):
        table, body = m.group(1), m.group(2)
        schema.setdefault(table, set())
        table_order.append(table)
        for c in COL_RE.finditer(body):
            kind, name = c.group(1), c.group(2)
            if kind == 'id':
                schema[table].add(name or 'id')
            elif kind == 'timestamps':
                schema[table].update({'created_at', 'updated_at'})
            elif kind == 'softDeletes':
                schema[table].add(name or 'deleted_at')
            elif kind == 'rememberToken':
                schema[table].add('remember_token')
            elif name:
                schema[table].add(name)
        # foreignId(...)->constrained('x')  /  ->constrained()
        for f in re.finditer(r"foreignId\('([^']+)'\)((?:->\w+\([^)]*\))*)", body):
            col, chain = f.group(1), f.group(2)
            con = re.search(r"constrained\((?:'([^']+)')?\)", chain)
            if con:
                ref = con.group(1) or pluralize(re.sub(r'_id$', '', col))
                foreign_refs.append((table, col, ref, path.name))

    for m in re.finditer(r"Schema::table\('([^']+)',\s*function \(Blueprint \$table\) \{(.*?)\n        \}\);",
                         src, re.S):
        table, body = m.group(1), m.group(2)
        schema.setdefault(table, set())
        for c in COL_RE.finditer(body):
            kind, name = c.group(1), c.group(2)
            if name:
                schema[table].add(name)

print('テーブル: %d' % len(schema))

# 3. 外部キーの参照先
for table, col, ref, fname in foreign_refs:
    if ref not in schema:
        err('外部キーの参照先が存在しない: %s.%s -> %s (%s)' % (table, col, ref, fname))
    else:
        # 自己参照以外は、参照先が先に作られている必要がある
        if ref != table and ref in table_order and table in table_order:
            if table_order.index(ref) > table_order.index(table):
                err('外部キーの作成順が逆: %s は %s より後に作られる (%s)' % (ref, table, fname))

# ---------------------------------------------------------------- 2. models
MODEL_TABLE = {
    'Media': 'media', 'Setting': 'settings', 'SeoMeta': 'seo_metas',
    'ActivityLog': 'activity_logs', 'StoreProfile': 'store_profile',
    'BusinessHour': 'business_hours', 'SpecialDay': 'special_days',
    'DishCategory': 'dish_categories', 'Dish': 'dishes', 'DishVariant': 'dish_variants',
    'Allergen': 'allergens', 'News': 'news', 'Event': 'events',
    'HomeSection': 'home_sections', 'HomeRecommendedDish': 'home_recommended_dishes',
    'Contact': 'contacts', 'ContactReply': 'contact_replies',
    'ReservationTimeSlot': 'reservation_time_slots',
    'ReservationSlotOverride': 'reservation_slot_overrides',
    'Reservation': 'reservations', 'SocialLink': 'social_links', 'User': 'users',
}

checked = 0
for path in sorted((APP / 'app/Models').glob('*.php')):
    name = path.stem
    if name not in MODEL_TABLE:
        err('モデルの対応テーブルが未定義: %s' % name)
        continue
    table = MODEL_TABLE[name]
    if table not in schema:
        err('モデル %s のテーブル %s がマイグレーションに存在しない' % (name, table))
        continue
    src = path.read_text(encoding='utf-8')
    checked += 1

    m = re.search(r"#\[Fillable\(\[(.*?)\]\)\]", src, re.S)
    if m:
        for col in re.findall(r"'([^']+)'", m.group(1)):
            if col not in schema[table]:
                err('%s の Fillable にある %s.%s がマイグレーションに無い' % (name, table, col))

    for cast in re.findall(r"'([a-z_]+)' => ", src.split('casts(): array')[-1][:900] if 'casts(): array' in src else ''):
        if cast not in schema[table] and cast not in ('email_verified_at',):
            warn('%s の casts にある %s.%s が見当たらない' % (name, table, cast))

    # belongsTo(X::class, 'fk')
    for rel in re.finditer(r"belongsTo\((\w+)::class,\s*'([^']+)'\)", src):
        target, fk = rel.group(1), rel.group(2)
        if fk not in schema[table]:
            err('%s の belongsTo(%s) が使う %s.%s が存在しない' % (name, target, table, fk))
        if target != 'self' and target in MODEL_TABLE and MODEL_TABLE[target] not in schema:
            err('%s の belongsTo 先 %s のテーブルが無い' % (name, target))

    # belongsToMany(X::class, 'pivot', 'a', 'b')
    for rel in re.finditer(r"belongsToMany\((\w+)::class,\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'\)", src):
        target, pivot, fk1, fk2 = rel.groups()
        if pivot not in schema:
            err('%s の中間テーブル %s が存在しない' % (name, pivot))
        else:
            for c in (fk1, fk2):
                if c not in schema[pivot]:
                    err('%s の中間テーブル %s に列 %s が無い' % (name, pivot, c))

    # hasMany(X::class) / hasMany(X::class, 'fk')
    for rel in re.finditer(r"hasMany\((\w+|self)::class(?:,\s*'([^']+)')?\)", src):
        target, fk = rel.group(1), rel.group(2)
        tgt_table = table if target == 'self' else MODEL_TABLE.get(target)
        if not tgt_table:
            continue
        col = fk or (re.sub(r'([a-z])([A-Z])', r'\1_\2', name).lower() + '_id')  # noqa: E501
        if tgt_table in schema and col not in schema[tgt_table]:
            err('%s の hasMany(%s) が使う %s.%s が存在しない' % (name, target, tgt_table, col))

    # morphOne(SeoMeta::class, 'seoable')
    for rel in re.finditer(r"morphOne\((\w+)::class,\s*'([^']+)'\)", src):
        target, morph = rel.group(1), rel.group(2)
        t = MODEL_TABLE.get(target)
        if t and t in schema:
            for suffix in ('_type', '_id'):
                if morph + suffix not in schema[t]:
                    err('%s の morphOne が使う %s.%s%s が存在しない' % (name, t, morph, suffix))

# Concerns で使う morphOne も検証
concern = (APP / 'app/Models/Concerns/HasSeoMeta.php').read_text(encoding='utf-8')
for rel in re.finditer(r"morphOne\((\w+)::class,\s*'([^']+)'\)", concern):
    for suffix in ('_type', '_id'):
        if rel.group(2) + suffix not in schema['seo_metas']:
            err('HasSeoMeta の morphOne が使う seo_metas.%s%s が無い' % (rel.group(2), suffix))

print('モデル: %d 件を検証' % checked)

# ---------------------------------------------------------------- 4/5. seeders
seed = (APP / 'database/seeders/DishSeeder.php').read_text(encoding='utf-8')
block = seed.split('private const DISHES = [', 1)[1].split("\n    ];", 1)[0] + "\n"
rows = re.findall(
    r"\['([a-z-]+)', '([^']+)', '([a-z0-9-]+)', (\[.*?\])(?:, (\[[^\[\]]*\]))?\],\n",
    block, re.S)
slugs = [r[2] for r in rows]
dup = {s for s in slugs if slugs.count(s) > 1}
if dup:
    err('DishSeeder の slug が重複: %s' % ', '.join(sorted(dup)))

cat_seed = (APP / 'database/seeders/DishCategorySeeder.php').read_text(encoding='utf-8')
cat_slugs = set(re.findall(r"'slug' => '([a-z-]+)'", cat_seed))
for r in rows:
    if r[0] not in cat_slugs:
        err('DishSeeder のカテゴリ %s が DishCategorySeeder に無い' % r[0])

# 価格の突き合わせ（元データ: 看板用写真/金額.xlsx, お持ち帰りメニュー.xlsx）
SOURCE_PRICES = {
    'あんかけうどん': 880, 'かつとじうどん': 920, 'カレーうどん': 880, 'きつねうどん': 720,
    'けんちんうどん': 880, '天とじうどん': 980, '天ぷらうどん': 920, '麺セットうどん': 920,
    'ころうどん': 850, 'ざるそば': 690, 'たぬきそば': 660, 'ヒレカツころうどん': 1170,
    '天ざるそば': 1450, 'カレー煮込み': 970, 'すき焼き煮込み': 1140, '天入りみそ煮込み': 1320,
    'みそ煮込み': 910, 'エビ丼': 1050, 'カツ丼': 880, 'みそひれかつ丼': 1130,
    '山かけ鮪丼': 1180, '鉄火丼': 1180, '天丼': 970, 'エビフライ定食': 1370,
    'かつなべ定食': 1220, 'から揚げ定食': 1220, 'コロッケ定食': 820, 'ヒレカツ定食': 1370,
    'みそかつ定食': 1220, '牛なべ定食': 1220, '広瀬屋御膳': 1700,
    'カキの玉子とじ定食': 1380, 'カキフライ定食': 1380, 'トマト煮込み': 970,
    '鳥塩生姜煮込み': 970, '豆乳煮込み': 970,
    'ミニかつ丼とうどん': 1070, 'ミニみそひれかつ丼とうどん': 1120,
    'ミニ山かけ鮪丼とうどん': 1140, 'ミニ天丼とうどん': 1140,
    'ミニカツ丼とざるそば': 1220, 'ミニひれかつ丼とざるそば': 1270,
    'ミニ山かけ鮪丼とざるそば': 1290, 'ミニ天丼とざるそば': 1290,
    '牛丼': 830, '玉子丼': 740, '親子丼': 840, '上天丼': 1200, '上かつ丼': 1150,
    'からあげ': 900, 'エビフライ': 1090, 'カキフライ': 1090, 'おろしかつ': 980,
    'みそかつ': 980, 'いか焼き': 500, '枝豆': 400, '手羽元から揚げ': 570,
    'ごぼうから揚げ': 500, 'いかから揚げ': 570, 'みそ串カツ': 270,
}
checked_prices = 0
for cat, name, slug, variants, _flags in rows:
    prices = [int(p) for p in re.findall(r",\s*(\d+)", variants)]
    if name in SOURCE_PRICES:
        if SOURCE_PRICES[name] not in prices:
            err('価格が元データと違う: %s は %d 円のはずが %s' % (name, SOURCE_PRICES[name], prices))
        checked_prices += 1

print('料理: %d 件 / 価格照合 %d 件' % (len(rows), checked_prices))

# 中間テーブルの列名（allergen_dish）
if 'allergen_dish' in schema:
    for c in ('dish_id', 'allergen_id'):
        if c not in schema['allergen_dish']:
            err('allergen_dish に %s が無い' % c)

# ---------------------------------------------------------------- 結果
print()
for w in warnings:
    print('WARN  ' + w)
for e in errors:
    print('ERROR ' + e)
print()
print('=> エラー %d 件 / 警告 %d 件' % (len(errors), len(warnings)))
sys.exit(1 if errors else 0)
