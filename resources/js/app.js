import Alpine from 'alpinejs';

/*
 * 画像の選択。
 *
 * 料理・お知らせ・イベント・トップページのどこからでも、同じ画像ライブラリから
 * 選べるようにするための部品。フォームには media の ID だけを送る。
 *
 * 一覧はページを開いた時点では読み込まず、「画像を選ぶ」を押してから取りに行く。
 * 登録画像が数千枚あるため、全画面に埋め込むと管理画面が重くなる。
 */
Alpine.data('mediaPicker', (initial = null, endpoint = '') => ({
    open: false,
    loading: false,
    loaded: false,
    keyword: '',
    items: [],
    selected: initial,

    async show() {
        this.open = true;

        if (!this.loaded) {
            await this.fetch();
        }
    },

    async fetch() {
        this.loading = true;

        try {
            const url = new URL(endpoint, window.location.origin);
            if (this.keyword) url.searchParams.set('q', this.keyword);

            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) throw new Error(response.statusText);

            this.items = await response.json();
            this.loaded = true;
        } catch (error) {
            // 通信に失敗しても管理画面は使い続けられるようにする。
            this.items = [];
            console.error('画像一覧の取得に失敗しました', error);
        } finally {
            this.loading = false;
        }
    },

    async search() {
        this.loaded = false;
        await this.fetch();
    },

    choose(item) {
        this.selected = item;
        this.open = false;
    },

    clear() {
        this.selected = null;
    },
}));

/*
 * 並び替え。
 *
 * ドラッグ&ドロップは触る端末を選ぶうえ、店舗の方がタブレットで操作することも
 * 考えると誤操作が多い。上下ボタンで 1 つずつ動かす形にしている。
 */
Alpine.data('sortableList', () => ({
    move(index, direction) {
        const rows = Array.from(this.$root.querySelectorAll('[data-sort-row]'));
        const target = index + direction;

        if (target < 0 || target >= rows.length) return;

        if (direction < 0) {
            rows[target].before(rows[index]);
        } else {
            rows[target].after(rows[index]);
        }

        this.renumber();
    },

    renumber() {
        this.$root.querySelectorAll('[data-sort-row]').forEach((row, index) => {
            const input = row.querySelector('[data-sort-input]');
            if (input) input.value = index;

            const label = row.querySelector('[data-sort-number]');
            if (label) label.textContent = index + 1;
        });
    },
}));

/*
 * 価格バリエーションの追加・削除。
 * 同じ料理に「単品/セット」「二枚/三枚」「小/中/大」があるため、
 * 行を増やせないと実データが入力できない。
 */
Alpine.data('variantRows', (rows = []) => ({
    rows: rows.length ? rows : [{ label: '', price: '', service_type: 'dine_in', is_default: true }],

    add() {
        this.rows.push({ label: '', price: '', service_type: 'dine_in', is_default: false });
    },

    remove(index) {
        this.rows.splice(index, 1);

        if (this.rows.length === 0) this.add();
        if (!this.rows.some((row) => row.is_default)) this.rows[0].is_default = true;
    },

    // 既定の価格は提供区分ごとに 1 つ。ここで排他にしておかないと一覧の代表価格が定まらない。
    setDefault(index) {
        const type = this.rows[index].service_type;
        this.rows.forEach((row, i) => {
            if (row.service_type === type) row.is_default = i === index;
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
