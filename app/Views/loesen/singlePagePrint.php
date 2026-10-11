<style>
    html.print-fitting .sheet {
        display: flow-root;
        width: 196mm;
        min-height: 0;
        margin: 0;
        padding: 0;
        border: 0;
        box-shadow: none;
    }
    .sheet-content { display: flow-root; }
    html.print-fitting .sheet-content { width: 196mm; }
    @media print {
        .sheet {
            width: 196mm;
            min-height: 0;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .sheet + .sheet { break-before: page; page-break-before: always; }
    }
</style>
<script>
    (() => {
        const fitSheets = () => {
            document.documentElement.classList.add('print-fitting');
            const ruler = document.createElement('div');
            ruler.style.cssText = 'position:absolute;visibility:hidden;width:196mm;height:133mm';
            document.body.appendChild(ruler);
            const width = ruler.getBoundingClientRect().width;
            const height = ruler.getBoundingClientRect().height;
            document.querySelectorAll('.sheet-content').forEach(content => {
                content.style.zoom = '1';
                const bounds = content.getBoundingClientRect();
                const scale = Math.min(1, width / Math.max(bounds.width, content.scrollWidth), height / Math.max(bounds.height, content.scrollHeight));
                content.style.zoom = String(scale);
            });
            ruler.remove();
        };
        window.addEventListener('beforeprint', fitSheets);
        window.addEventListener('afterprint', () => {
            document.documentElement.classList.remove('print-fitting');
            document.querySelectorAll('.sheet-content').forEach(content => content.style.removeProperty('zoom'));
        });
    })();
</script>
