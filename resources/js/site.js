/*
 * 公開サイトの JavaScript。
 *
 * 公開ページはフレームワークを載せない。ページの中身はサーバー側で
 * 出し切っているため、JS が動かなくても閲覧・お問い合わせは成立する。
 * ここにあるのは「あると心地よい」動きだけ。
 */

/* ---------------------------------------------------------------------
 * スマートフォンのメニュー開閉
 * ------------------------------------------------------------------ */
function setupDrawer() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');

    if (!toggle || !drawer) return;

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        drawer.dataset.open = String(open);
        toggle.setAttribute('aria-label', open ? 'メニューを閉じる' : 'メニューを開く');
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    // 画面を広げたときに開いたままにしない。
    window.matchMedia('(min-width: 900px)').addEventListener('change', (event) => {
        if (event.matches) setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
}

/* ---------------------------------------------------------------------
 * 見出しと写真のふわりとした出現
 *
 * 動きを減らす設定の人には一切動かさない。
 * また JS が失敗したときに中身が消えたままにならないよう、
 * 初期状態の非表示は JS 側で付ける（CSS には書かない）。
 * ------------------------------------------------------------------ */
function setupReveal() {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const targets = document.querySelectorAll('[data-reveal]');

    if (reduced || !('IntersectionObserver' in window) || targets.length === 0) return;

    targets.forEach((element) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(14px)';
        element.style.transition = 'opacity .8s cubic-bezier(.22,1,.36,1), transform .8s cubic-bezier(.22,1,.36,1)';
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.style.opacity = '1';
            entry.target.style.transform = 'none';
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    targets.forEach((element) => observer.observe(element));
}

/* ---------------------------------------------------------------------
 * 二重送信の防止
 *
 * 問い合わせフォームは送信に数秒かかることがあり、
 * 待てずに二度押しされると同じ内容が 2 件届く。
 * ------------------------------------------------------------------ */
function setupSubmitGuard() {
    document.querySelectorAll('form[data-guard]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            if (!button) return;

            // 送信自体は止めない。値を送るため disabled ではなく見た目だけ変える。
            button.dataset.label = button.textContent;
            button.textContent = '送信しています…';
            button.style.pointerEvents = 'none';
            button.style.opacity = '0.6';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupDrawer();
    setupReveal();
    setupSubmitGuard();
});
