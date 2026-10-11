<script>
    window.addEventListener('afterprint', () => {
        const referrer = document.referrer ? new URL(document.referrer) : null;
        const returnUrl = referrer?.origin === window.location.origin
            ? referrer.href
            : document.querySelector('.screen-actions a')?.href;

        if (window.history.length <= 1) {
            window.close();
            // If the browser keeps the tab open, return within this tab.
            window.setTimeout(() => {
                if (returnUrl) window.location.replace(returnUrl);
            }, 150);
        } else if (referrer?.origin === window.location.origin) {
            window.history.back();
        } else if (returnUrl) {
            window.location.replace(returnUrl);
        }
    }, { once: true });

    window.addEventListener('load', async () => {
        if (document.fonts) await document.fonts.ready;
        window.requestAnimationFrame(() => window.print());
    }, { once: true });
</script>
