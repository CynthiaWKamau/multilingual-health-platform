// Offline support
(function () {
    const base    = new URL('.', document.currentScript.src);
    const swahili = document.documentElement.lang === 'sw';

    const text = swahili ? {
        offline: 'Huna mtandao. Unaona kurasa zilizohifadhiwa. Ushauri wa AI na ukaguzi wa dhana potofu vinahitaji mtandao.',
        disabled: 'Inahitaji mtandao'
    } : {
        offline: "You're offline. Showing saved pages. AI symptom guidance and the myth checker need an internet connection.",
        disabled: 'Needs internet'
    };

    // Service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(new URL('sw.js', base), { scope: base.pathname })
            .then(() => navigator.serviceWorker.ready)
            .then(reg => {
                let last = 0;
                try { last = Number(localStorage.getItem('sh_warm')) || 0; } catch (e) {}
                if (navigator.onLine && reg.active && Date.now() - last > 3600000) {
                    reg.active.postMessage({ type: 'warm' });
                    try { localStorage.setItem('sh_warm', String(Date.now())); } catch (e) {}
                }
            })
            .catch(() => {});
    }

    // Offline banner
    const bar = document.createElement('div');
    bar.setAttribute('role', 'status');
    bar.style.cssText = 'position:relative;z-index:300;background:#6b7c6d;color:#fff;'
        + 'padding:9px max(16px,5vw);font:500 0.83rem/1.5 Poppins,system-ui,sans-serif;';
    bar.textContent = text.offline;
    document.body.prepend(bar);

    // Online-only forms
    const buttons = Array.from(document.querySelectorAll('form[method="POST"] button, form[method="post"] button'));
    buttons.forEach(b => { b.dataset.label = b.textContent; });

    function update() {
        const offline = !navigator.onLine;
        bar.hidden = !offline;
        buttons.forEach(b => {
            b.disabled = offline;
            b.style.opacity = offline ? '0.55' : '';
            b.textContent = offline ? text.disabled : b.dataset.label;
        });
    }

    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    update();
})();
