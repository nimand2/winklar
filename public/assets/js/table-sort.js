(() => {
    const collator = new Intl.Collator('de-CH', { numeric: true, sensitivity: 'base' });
    const valueOf = (cell, type) => {
        const text = (cell.dataset.sortValue ?? cell.textContent).trim();
        if (type === 'number') {
            const number = Number(text.replace(/CHF|[\s'’#]/g, '').replace(',', '.'));
            return Number.isFinite(number) ? number : 0;
        }
        return text;
    };

    document.querySelectorAll('[data-sortable-table]').forEach(table => {
        const body = table.tBodies[0];
        if (!body || !table.tHead) return;
        const headers = Array.from(table.tHead.rows[0].cells);
        headers.forEach((header, column) => {
            const label = header.textContent.trim();
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'table-sort-button';
            button.append(document.createTextNode(label));
            const arrow = document.createElement('span');
            arrow.className = 'table-sort-arrow';
            arrow.setAttribute('aria-hidden', 'true');
            arrow.textContent = '↕';
            button.append(arrow);
            button.setAttribute('aria-label', `${label} aufsteigend sortieren`);
            header.setAttribute('scope', 'col');
            header.setAttribute('aria-sort', 'none');
            header.replaceChildren(button);

            button.addEventListener('click', () => {
                const descending = header.getAttribute('aria-sort') === 'ascending';
                const type = header.dataset.sortType;
                const rows = Array.from(body.rows).map((row, index) => ({ row, index }));
                rows.sort((left, right) => {
                    const a = valueOf(left.row.cells[column], type);
                    const b = valueOf(right.row.cells[column], type);
                    const comparison = type === 'number' ? a - b : collator.compare(a, b);
                    return (descending ? -comparison : comparison) || left.index - right.index;
                });
                body.append(...rows.map(entry => entry.row));
                headers.forEach(other => {
                    other.setAttribute('aria-sort', 'none');
                    other.querySelector('.table-sort-arrow').textContent = '↕';
                    const otherButton = other.querySelector('button');
                    otherButton.setAttribute('aria-label', `${otherButton.firstChild.textContent} aufsteigend sortieren`);
                });
                header.setAttribute('aria-sort', descending ? 'descending' : 'ascending');
                arrow.textContent = descending ? '▼' : '▲';
                button.setAttribute('aria-label', `${label} ${descending ? 'aufsteigend' : 'absteigend'} sortieren`);
            });
        });
    });
})();
