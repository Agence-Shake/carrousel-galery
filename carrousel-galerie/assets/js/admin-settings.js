(() => {
    'use strict';

    const data = window.cgAdminData || {};
    const form = document.querySelector('.cg-form');
    const optPrefix = (form && form.dataset.cgOptPrefix)
        || data.optionKey
        || 'carrousel_galerie_settings';

    const nameSel = suffix =>
        `[name="${(optPrefix + suffix).replace(/(["\\])/g, '\\$1')}"]`;

    const dispatchChange = el => {
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    // ====== Mode d'affichage : toggle radio visuel ======
    const modeOptions = document.querySelectorAll('.cg-mode-option');
    const modeNotes   = document.querySelectorAll('[data-cg-mode-when]');
    const modeWrap    = document.querySelector('.cg-wrap');

    const syncModeUI = () => {
        let current = 'carrousel';
        modeOptions.forEach(opt => {
            const input = opt.querySelector('input[type="radio"]');
            if (input?.checked) current = input.value;
        });
        modeOptions.forEach(opt => {
            const input = opt.querySelector('input[type="radio"]');
            opt.classList.toggle('is-active', input?.value === current);
        });
        modeNotes.forEach(note => {
            note.hidden = note.dataset.cgModeWhen !== current;
        });
        if (modeWrap) modeWrap.dataset.cgDisplay = current;
    };

    modeOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            const input = opt.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
                syncModeUI();
            }
        });
    });
    syncModeUI();

    // ====== Onglets Desktop / Tablette / Mobile ======
    const tabs   = document.querySelectorAll('.cg-tab');
    const panels = document.querySelectorAll('.cg-tab-panel');
    tabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabs.forEach(b => b.classList.toggle('active', b === btn));
            panels.forEach(p => p.hidden = (p.dataset.tab !== target));
        });
    });

    // ====== Contrôles responsifs (pagination / fleches / direction) ======
    const desktopInput = key =>
        document.querySelector(nameSel(`[${key}_desktop]`));

    const refreshResp = ctrl => {
        const hidden    = ctrl.querySelector('.cg-resp-hidden');
        const display   = ctrl.querySelector('.cg-resp-display');
        const key       = ctrl.dataset.key;
        const inherited = (hidden.value === '');

        ctrl.classList.toggle('cg-inherited', inherited);

        if (inherited) {
            const dt = desktopInput(key);
            display.value = dt ? dt.value : display.options[0].value;
        } else {
            display.value = hidden.value;
        }
    };

    document.querySelectorAll('.cg-resp').forEach(ctrl => {
        const hidden  = ctrl.querySelector('.cg-resp-hidden');
        const display = ctrl.querySelector('.cg-resp-display');
        const reset   = ctrl.querySelector('.cg-reset');

        refreshResp(ctrl);

        display.addEventListener('change', () => {
            hidden.value = display.value;
            ctrl.classList.remove('cg-inherited');
            dispatchChange(hidden);
        });

        reset.addEventListener('click', () => {
            hidden.value = '';
            refreshResp(ctrl);
            dispatchChange(hidden);
        });
    });

    // Quand Desktop change → met à jour l'affichage des resp-controls en mode hérité.
    ['pagination', 'fleches', 'direction'].forEach(key => {
        const dt = desktopInput(key);
        if (!dt) return;
        dt.addEventListener('change', () => {
            document.querySelectorAll(`.cg-resp[data-key="${key}"].cg-inherited`).forEach(refreshResp);
        });
    });

    // ====== Rows conditionnels par onglet (pagination / fleches, avec héritage) ======
    const resolveOn = (key, slug) => {
        const input = document.querySelector(nameSel(`[${key}_${slug}]`));
        if (!input) return false;
        const val = input.value;
        if (val === '' || val == null) {
            // Tablette/Mobile en mode hérité → suit Desktop.
            return (slug === 'desktop') ? false : resolveOn(key, 'desktop');
        }
        return val === '1';
    };

    const syncConditionalRows = () => {
        document.querySelectorAll('.cg-tab-panel').forEach(panel => {
            const slug = panel.dataset.tab;
            ['pagination', 'fleches'].forEach(key => {
                const on = resolveOn(key, slug);
                panel.querySelectorAll(`[data-cg-conditional="${key}"]`).forEach(row => {
                    row.hidden = !on;
                });
            });
        });
    };
    
    ['pagination', 'fleches'].forEach(key => {
        ['desktop', 'tablette', 'mobile'].forEach(slug => {
            const input = document.querySelector(nameSel(`[${key}_${slug}]`));
            if (input) input.addEventListener('change', syncConditionalRows);
        });
    });
    syncConditionalRows();

    // ====== Champs conditionnels globaux (data-cg-toggle / data-cg-when) ======
    document.querySelectorAll('[data-cg-toggle]').forEach(toggle => {
        const key = toggle.dataset.cgToggle;
        const isOn = () => toggle.type === 'checkbox' ? toggle.checked : toggle.value === '1';
        const sync = () => {
            const on = isOn();
            document.querySelectorAll(`[data-cg-when="${key}"]`).forEach(row => {
                row.hidden = !on;
            });
        };
        toggle.addEventListener('change', sync);
        sync();
    });

    // ====== Boutons "Copier" ======
    const fallbackCopy = (text, onDone) => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        Object.assign(ta.style, { position: 'fixed', opacity: '0' });
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); onDone(); } catch (_) {}
        document.body.removeChild(ta);
    };

    document.querySelectorAll('.cg-copy').forEach(btn => {
        btn.addEventListener('click', () => {
            let text;
            if (btn.dataset.copy) {
                text = btn.dataset.copy;
            } else if (btn.dataset.copyFrom === 'prev') {
                const pre = btn.parentElement.querySelector('pre');
                text = pre?.textContent ?? '';
            } else if (btn.dataset.copyFrom) {
                const target = document.querySelector(btn.dataset.copyFrom);
                if (!target) return;
                text = target.value ?? target.textContent;
            } else {
                return;
            }

            const done = () => {
                btn.classList.add('cg-copied');
                const label = btn.querySelector('.cg-copy-text');
                const original = label?.textContent;
                if (label) label.textContent = 'Copié !';
                setTimeout(() => {
                    btn.classList.remove('cg-copied');
                    if (label && original) label.textContent = original;
                }, 1500);
            };

            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
            } else {
                fallbackCopy(text, done);
            }
        });
    });

    // ====== CSS textarea : charger exemple / vider ======
    {
        const textarea = document.getElementById('cg-css-textarea');
        if (textarea) {
            const exampleBtn = document.getElementById('cg-css-example');
            const clearBtn   = document.getElementById('cg-css-clear');

            exampleBtn?.addEventListener('click', () => {
                const example = exampleBtn.dataset.example || '';
                if (textarea.value.trim() !== '' && !confirm("Remplacer le CSS actuel par l'exemple ?")) return;
                textarea.value = example;
                textarea.focus();
            });

            clearBtn?.addEventListener('click', () => {
                if (textarea.value.trim() === '') return;
                if (!confirm('Vider le CSS personnalisé ?')) return;
                textarea.value = '';
                textarea.focus();
            });
        }
    }

    // ====== Picker visuel "Slides visibles" ======
    document.querySelectorAll('.cg-slides').forEach(root => {
        const hidden      = root.querySelector('.cg-slide-hidden');
        const customInput = root.querySelector('.cg-slide-custom-input');
        const customWrap  = root.querySelector('.cg-slides-custom-wrap');
        const presets     = root.querySelectorAll('.cg-slide-preset');

        const setActive = btn => presets.forEach(p => p.classList.toggle('active', p === btn));

        presets.forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.dataset.value;
                setActive(btn);
                if (val === 'custom') {
                    customWrap.hidden = false;
                    hidden.value = customInput.value || '';
                    requestAnimationFrame(() => customInput.focus());
                } else {
                    customWrap.hidden = true;
                    hidden.value = val; // '' = hérite, sinon valeur du preset
                }
            });
        });

        customInput?.addEventListener('input', () => {
            hidden.value = customInput.value;
        });
    });

    // ====== Contrôle de longueur avec unité (Elementor-like) ======
    const UNIT_REGEX = /^(-?\d+(?:\.\d+)?)(px|em|rem|%|vw|vh)?$/;

    const parseUnit = str => {
        if (!str) return { value: '', unit: 'px', custom: '' };
        const s = String(str).trim();
        const m = s.match(UNIT_REGEX);
        if (m) return { value: m[1], unit: m[2] || 'px', custom: '' };
        return { value: '', unit: 'custom', custom: s };
    };

    document.querySelectorAll('.cg-unit').forEach(ctrl => {
        const valueInput  = ctrl.querySelector('.cg-unit-value');
        const customInput = ctrl.querySelector('.cg-unit-custom');
        const currentSpan = ctrl.querySelector('.cg-unit-current');
        const list        = ctrl.querySelector('.cg-unit-list');
        const hidden      = ctrl.querySelector('.cg-unit-hidden');

        const state = parseUnit(hidden.value);

        const render = () => {
            if (state.unit === 'custom') {
                valueInput.hidden  = true;
                customInput.hidden = false;
                customInput.value  = state.custom;
                currentSpan.textContent = '⚙ ▾';
            } else {
                valueInput.hidden  = false;
                customInput.hidden = true;
                valueInput.value   = state.value;
                currentSpan.textContent = `${state.unit} ▾`;
            }
        };

        const sync = () => {
            if (state.unit === 'custom') {
                hidden.value = state.custom.trim();
            } else if (state.value === '') {
                hidden.value = '';
            } else {
                hidden.value = state.value + state.unit;
            }
        };

        render();

        valueInput.addEventListener('input', () => {
            state.value = valueInput.value;
            sync();
        });
        customInput.addEventListener('input', () => {
            state.custom = customInput.value;
            sync();
        });

        currentSpan.addEventListener('click', e => {
            e.stopPropagation();
            // Ferme les autres dropdowns ouverts.
            document.querySelectorAll('.cg-unit-list').forEach(l => { if (l !== list) l.hidden = true; });
            list.hidden = !list.hidden;
        });

        list.querySelectorAll('li').forEach(li => {
            li.addEventListener('click', () => {
                state.unit = li.dataset.unit;
                list.hidden = true;
                render();
                sync();
            });
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.cg-unit-list').forEach(l => { l.hidden = true; });
    });

    // ====== Couleurs : swatch + opacity slider + variables globales ======
    const globals = Array.isArray(data.elementorGlobals) ? data.elementorGlobals : [];

    const hexExpand = hex =>
        /^#[0-9a-f]{3}$/i.test(hex)
            ? '#' + hex.slice(1).split('').map(c => c + c).join('')
            : hex;

    const hexToRgb = hex => {
        const m = hexExpand(hex).match(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i);
        if (!m) return null;
        return { r: parseInt(m[1], 16), g: parseInt(m[2], 16), b: parseInt(m[3], 16) };
    };

    const rgbToHex = (r, g, b) =>
        '#' + [r, g, b].map(x => {
            const h = parseInt(x, 10).toString(16);
            return h.length === 1 ? '0' + h : h;
        }).join('');

    const parseColor = str => {
        const s = String(str ?? '').trim();
        if (!s) return null;
        if (/^#[0-9a-f]{6}$/i.test(s)) return { hex: s.toLowerCase(), alpha: 1 };
        if (/^#[0-9a-f]{3}$/i.test(s)) return { hex: hexExpand(s).toLowerCase(), alpha: 1 };
        const m = s.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)$/i);
        if (m) {
            return {
                hex: rgbToHex(m[1], m[2], m[3]),
                alpha: m[4] !== undefined ? Math.max(0, Math.min(1, parseFloat(m[4]))) : 1,
            };
        }
        return null;
    };

    const buildColor = (hex, alpha) => {
        if (alpha >= 1) return hex;
        const rgb = hexToRgb(hex);
        if (!rgb) return hex;
        const a = Math.round(alpha * 100) / 100;
        return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${a})`;
    };

    document.querySelectorAll('.cg-color').forEach(ctrl => {
        const text         = ctrl.querySelector('.cg-color-text');
        const swatch       = ctrl.querySelector('.cg-color-swatch');
        const opacity      = ctrl.querySelector('.cg-color-opacity');
        const opacityLabel = ctrl.querySelector('.cg-color-opacity-value');
        const dropdown     = ctrl.querySelector('.cg-color-globals');

        // État local (reflète swatch+slider, pas forcément la string brute).
        const state = { hex: '#000000', alpha: 1 };

        const updateSliderColor = () => {
            opacity.style.setProperty('--cg-current-color', state.hex);
            if (opacityLabel) {
                opacityLabel.textContent = `${Math.round(state.alpha * 100)}%`;
            }
        };

        const syncFromText = () => {
            const parsed = parseColor(text.value);
            if (parsed) {
                state.hex   = parsed.hex;
                state.alpha = parsed.alpha;
            }
            // Sinon (var(), hsl, etc.) → on conserve l'état précédent.
            swatch.value  = state.hex;
            opacity.value = Math.round(state.alpha * 100);
            updateSliderColor();
        };

        const syncFromPicker = () => {
            text.value = buildColor(state.hex, state.alpha);
            updateSliderColor();
        };

        syncFromText();

        text.addEventListener('input', syncFromText);

        swatch.addEventListener('input', () => {
            state.hex = swatch.value;
            syncFromPicker();
        });

        opacity.addEventListener('input', () => {
            state.alpha = parseInt(opacity.value, 10) / 100;
            syncFromPicker();
        });

        // Variables globales (Elementor) : insère var(--…) dans l'input texte.
        if (globals.length && dropdown) {
            const defOpt = document.createElement('option');
            defOpt.value = '';
            defOpt.textContent = '— Variables du site —';
            dropdown.appendChild(defOpt);

            globals.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.var;
                opt.textContent = `${g.title}${g.color ? ` (${g.color})` : ''}`;
                dropdown.appendChild(opt);
            });

            dropdown.hidden = false;

            dropdown.addEventListener('change', () => {
                if (dropdown.value) {
                    text.value = dropdown.value;
                    dropdown.selectedIndex = 0;
                }
            });
        }
    });
})();
