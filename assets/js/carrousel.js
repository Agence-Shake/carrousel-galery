(() => {
    'use strict';

    const MAX_SWIPER_WAIT_ATTEMPTS = 50; // 50 × 100ms = 5s max
    let swiperWaitAttempts = 0;

    const initAll = () => {
        const Swiper = window.cgSwiper || window.Swiper;
        if (typeof Swiper === 'undefined') {
            if (++swiperWaitAttempts >= MAX_SWIPER_WAIT_ATTEMPTS) {
                console.error('[Carrousel Galerie] Swiper introuvable après 5s — script non chargé ?');
                return;
            }
            setTimeout(initAll, 100);
            return;
        }

        document.querySelectorAll('.custom-swiper-galerie').forEach(slider => {
            if (slider.swiper) return;

            let config = {};
            try {
                config = JSON.parse(slider.getAttribute('data-cg-config') || '{}');
            } catch (e) {
                console.warn('[Carrousel Galerie] data-cg-config invalide', e);
                config = {};
            }

            // Résout les sélecteurs DOM des modules par breakpoint.
            // On itère par clé (pas Object.values) pour préserver les références.
            const breakpoints = config.breakpoints || {};
            Object.keys(breakpoints).forEach(bp => {
                const b = breakpoints[bp];
                if (!b) return;
                if (b.pagination && b.pagination.el) {
                    b.pagination = Object.assign({}, b.pagination, {
                        el: slider.querySelector(b.pagination.el),
                    });
                }
                if (b.navigation && b.navigation.nextEl) {
                    b.navigation = Object.assign({}, b.navigation, {
                        nextEl: slider.querySelector(b.navigation.nextEl),
                        prevEl: slider.querySelector(b.navigation.prevEl),
                    });
                }
            });

            // Base = config du breakpoint avec la clé numérique la plus petite (mobile).
            // JS coerce automatiquement la clé numérique en string pour l'accès objet.
            const sortedKeys = Object.keys(breakpoints)
                .map(k => Number(k))
                .filter(k => Number.isFinite(k))
                .sort((a, b) => a - b);
            const base = sortedKeys.length ? (breakpoints[sortedKeys[0]] || {}) : {};

            // Conversion explicite + garde-fou contre 0/NaN/undefined.
            const sv = Number(base.slidesPerView);
            const slidesPerView = (Number.isFinite(sv) && sv > 0) ? sv : 1;

            const options = {
                speed: Number(config.speed) || 800,
                loop: !!config.loop,
                effect: config.effect || 'slide',
                centeredSlides: !!config.centered,
                slidesPerView: slidesPerView,
                spaceBetween: (base.spaceBetween != null) ? base.spaceBetween : 0,
                direction: base.direction || 'horizontal',
                pagination: base.pagination || false,
                navigation: base.navigation || false,
                breakpoints: breakpoints,
            };

            if (config.autoplay) {
                options.autoplay = config.autoplay;
            }

            const startSlide = parseInt(config.startSlide, 10) || 0;

            // afterInit fire après init + setup interne complet, plus fiable que init.
            // On ne hook pas la pagination ici : Swiper gère lui-même les clicks bullets
            // via `clickable: true` (avec slideToLoop() en loop mode).
            options.on = {
                afterInit() {
                    // Loop mode : Swiper 11 ne repositionne les slides pour le peek-gauche
                    // qu'au premier slideTo/slideNext/slidePrev. À l'init, la slide précédente
                    // (du loop) reste en queue de DOM → peek-gauche vide.
                    //
                    // ⚠️ INTERNAL API : loopFix() n'est pas dans la doc publique Swiper. Peut être
                    // renommé/changé dans une 11.x mineure → vérifier au prochain bump Swiper.
                    // Le typeof check protège contre une suppression silencieuse.
                    requestAnimationFrame(() => {
                        if (this.params.loop && typeof this.loopFix === 'function') {
                            this.loopFix({ direction: 'prev', activeSlideIndex: this.activeIndex });
                        }
                        if (startSlide > 0) {
                            if (this.params.loop) {
                                this.slideToLoop(startSlide, 0, false);
                            } else {
                                this.slideTo(startSlide, 0);
                            }
                        }
                    });
                },
            };

            new Swiper(slider, options);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
