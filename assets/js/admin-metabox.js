(() => {
    'use strict';

    const init = () => {
        const list  = document.getElementById('cg-gallery-list');
        const input = document.getElementById('cg_gallery_ids');
        if (!list || !input) return;

        const addBtn = document.getElementById('cg-gallery-add');

        const syncInput = () => {
            const ids = [...list.querySelectorAll('.cg-item')].map(el => el.dataset.id);
            input.value = ids.join(',');
        };

        // ====== Media uploader (wp.media — global WP core) ======
        addBtn?.addEventListener('click', e => {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            const frame = wp.media({
                title: 'Images du carrousel',
                multiple: true,
                library: { type: 'image' },
                button: { text: 'Utiliser ces images' },
            });

            // Pré-sélection des images déjà choisies.
            frame.on('open', () => {
                const selection = frame.state().get('selection');
                list.querySelectorAll('.cg-item').forEach(el => {
                    const att = wp.media.attachment(el.dataset.id);
                    att.fetch();
                    selection.add(att);
                });
            });

            frame.on('select', () => {
                list.innerHTML = '';
                frame.state().get('selection').each(att => {
                    const a = att.toJSON();
                    const thumb = a.sizes?.thumbnail?.url ?? a.url;
                    const li = document.createElement('li');
                    li.className = 'cg-item';
                    li.dataset.id = a.id;
                    li.draggable = true;
                    li.innerHTML = `
                        <img src="${thumb}" alt="">
                        <button type="button" class="cg-remove" aria-label="Retirer">&times;</button>
                    `;
                    list.appendChild(li);
                });
                syncInput();
            });

            frame.open();
        });

        // ====== Suppression d'un item ======
        list.addEventListener('click', e => {
            const btn = e.target.closest('.cg-remove');
            if (!btn) return;
            btn.closest('.cg-item')?.remove();
            syncInput();
        });

        // ====== Drag-to-reorder (HTML5 DnD natif) ======
        let dragged = null;

        list.addEventListener('dragstart', e => {
            const item = e.target.closest('.cg-item');
            if (!item) return;
            dragged = item;
            item.classList.add('cg-dragging');
            e.dataTransfer.effectAllowed = 'move';
            // Firefox requiert qu'un type de data soit défini.
            try { e.dataTransfer.setData('text/plain', item.dataset.id); } catch (_) {}
        });

        list.addEventListener('dragend', () => {
            dragged?.classList.remove('cg-dragging');
            dragged = null;
        });

        list.addEventListener('dragover', e => {
            if (!dragged) return;
            const over = e.target.closest('.cg-item');
            if (!over || over === dragged) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            // Insertion avant/après selon la position du curseur dans l'item survolé.
            const rect = over.getBoundingClientRect();
            const before = (e.clientX - rect.left) < rect.width / 2;
            over.parentNode.insertBefore(dragged, before ? over : over.nextSibling);
        });

        list.addEventListener('drop', e => {
            e.preventDefault();
            syncInput();
        });

        // Active draggable sur les items déjà présents (au load).
        list.querySelectorAll('.cg-item').forEach(el => { el.draggable = true; });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
