<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Breakpoints supportés et leurs labels.
 */
function cg_breakpoints() {
    return [
        'desktop'  => [ 'label' => 'Desktop',  'width' => 1024 ],
        'tablette' => [ 'label' => 'Tablette', 'width' => 768 ],
        'mobile'   => [ 'label' => 'Mobile',   'width' => 0 ],
    ];
}

/**
 * Valeurs par défaut globales.
 * Pour tablette / mobile : valeur vide = "hériter du desktop".
 */
function cg_default_settings() {
    return [
        // Slides visibles par breakpoint — tablette/mobile vides = hérite de Desktop.
        'slides_desktop'  => 4,
        'slides_tablette' => '',
        'slides_mobile'   => '',

        // Mode Galerie : nombre de lignes affichées par breakpoint.
        // Desktop 0 = illimité ; tablette/mobile '' = hérite de Desktop, '0' = illimité.
        'rows_desktop'  => 0,
        'rows_tablette' => '',
        'rows_mobile'   => '',

        // Espace entre slides (string avec unité : "20px", "1em", "5%", var custom…)
        'space_desktop'  => '20px',
        'space_tablette' => '',
        'space_mobile'   => '',

        // Padding latéral du wrapper (preview slides précédente/suivante)
        'padding_desktop'  => '20px',
        'padding_tablette' => '',
        'padding_mobile'   => '',

        // Pagination — desktop: 0/1 ; tablette/mobile: ''|'0'|'1'
        'pagination_desktop'  => 0,
        'pagination_tablette' => '',
        'pagination_mobile'   => '',

        // Flèches
        'fleches_desktop'  => 0,
        'fleches_tablette' => '',
        'fleches_mobile'   => '',

        // Direction — desktop: horizontal|vertical ; tablette/mobile: ''|horizontal|vertical
        'direction_desktop'  => 'horizontal',
        'direction_tablette' => '',
        'direction_mobile'   => '',

        // Emplacement des flèches — desktop: inside|outside ; tablette/mobile: ''|inside|outside
        'arrows_position_desktop'  => 'inside',
        'arrows_position_tablette' => '',
        'arrows_position_mobile'   => '',

        // Hauteur max des images (sert aussi de hauteur du swiper quand direction = vertical)
        'image_max_height_desktop'  => '400px',
        'image_max_height_tablette' => '',
        'image_max_height_mobile'   => '',

        // Style des contrôles (responsive : par breakpoint, vide = hérite Desktop)
        'bullet_size_desktop'  => '9px',
        'bullet_size_tablette' => '',
        'bullet_size_mobile'   => '',
        'bullet_gap_desktop'   => '20px',
        'bullet_gap_tablette'  => '',
        'bullet_gap_mobile'    => '',
        'arrow_size_desktop'   => '44px',
        'arrow_size_tablette'  => '',
        'arrow_size_mobile'    => '',

        // Mode d'affichage : carrousel (slider Swiper) ou galerie (grille CSS)
        'display_mode'   => 'carrousel',

        'speed'          => 800,
        'loop'           => 0,
        'autoplay'       => 0,
        'autoplay_delay' => 3000,
        'pause_hover'    => 1,
        'start_slide'    => 0,

        'effet'          => 'slide',
        'centered'       => 0,

        'color_bullet'        => '#000000',
        'color_bullet_active' => '#000000',
        'color_arrow'         => '#000000',
        'color_arrow_hover'   => '#000000',
        'color_bg'            => '',

        // Post types
        'post_types' => [ 'post' ],

        // CSS personnalisé injecté sur les pages affichant le carrousel.
        'custom_css' => '',
    ];
}

function cg_get_settings() {
    $saved = get_option( CG_OPTION_KEY, null );

    $defaults_preset = cg_default_settings();
    $presets = [];

    if ( is_array( $saved ) && isset( $saved['presets'] ) && is_array( $saved['presets'] ) ) {
        foreach ( $saved['presets'] as $id => $pdata ) {
            if ( ! is_array( $pdata ) ) continue;
            $presets[ $id ] = array_merge( $defaults_preset, $pdata );
            if ( empty( $presets[ $id ]['name'] ) ) {
                $presets[ $id ]['name'] = ucfirst( $id );
            }
        }
    }

    return [
        'presets' => $presets,
    ];
}

/**
 * Récupère les réglages d'un préset spécifique avec fallback sur default
 */
function cg_get_preset_settings( $preset_id = 'default' ) {
    $all = cg_get_settings();
    if ( isset( $all['presets'][ $preset_id ] ) ) {
        return $all['presets'][ $preset_id ];
    }
    if ( isset( $all['presets']['default'] ) ) {
        return $all['presets']['default'];
    }
    return cg_default_settings();
}

/**
 * Menu principal d'administration et sous-menus.
 */
function cg_register_settings_menu() {
    // Menu principal "Carrousel Galerie"
    add_menu_page(
        'Carrousel Galerie',
        'Carrousel Galerie',
        'manage_options',
        'carrousel-galerie',
        'cg_render_list_page',
        'dashicons-images-alt2',
        20
    );

    // Sous-menu "Tous les shortcodes" (même slug pour remplacer la première entrée du sous-menu)
    add_submenu_page(
        'carrousel-galerie',
        'Tous les shortcodes',
        'Tous les shortcodes',
        'manage_options',
        'carrousel-galerie',
        'cg_render_list_page'
    );

    // Sous-menu "Ajouter un shortcode"
    add_submenu_page(
        'carrousel-galerie',
        'Ajouter un shortcode',
        'Ajouter un shortcode',
        'manage_options',
        'carrousel-galerie-edit',
        'cg_render_edit_page'
    );
}
add_action( 'admin_menu', 'cg_register_settings_menu' );

function cg_register_settings() {
    register_setting( 'cg_settings_group', CG_OPTION_KEY, [
        'sanitize_callback' => 'cg_sanitize_settings',
        'default'           => [
            'presets' => [],
        ],
    ] );
}
add_action( 'admin_init', 'cg_register_settings' );

function cg_sanitize_preset( $input, $preset_id = 'default' ) {
    $defaults = cg_default_settings();
    $out      = [];

    $out['name'] = ! empty( $input['name'] ) ? sanitize_text_field( $input['name'] ) : ( $preset_id === 'default' ? 'Par défaut' : ucfirst( $preset_id ) );

    // Slides par breakpoint
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k   = 'slides_' . $bp;
        $val = isset( $input[ $k ] ) ? trim( (string) $input[ $k ] ) : '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $val === '' ) ? $defaults[ $k ] : floatval( $val );
        } else {
            $out[ $k ] = ( $val === '' ) ? '' : floatval( $val );
        }
    }

    // Lignes (mode Galerie) par breakpoint : entier >= 0 ; '' = hériter (sauf desktop).
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k   = 'rows_' . $bp;
        $val = isset( $input[ $k ] ) ? trim( (string) $input[ $k ] ) : '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $val === '' ) ? 0 : max( 0, intval( $val ) );
        } else {
            $out[ $k ] = ( $val === '' ) ? '' : max( 0, intval( $val ) );
        }
    }

    // Longueurs CSS par breakpoint
    foreach ( [ 'space', 'padding', 'image_max_height', 'bullet_size', 'bullet_gap', 'arrow_size' ] as $key ) {
        foreach ( array_keys( cg_breakpoints() ) as $bp ) {
            $k   = $key . '_' . $bp;
            $val = isset( $input[ $k ] ) ? trim( (string) $input[ $k ] ) : '';
            $out[ $k ] = cg_sanitize_length( $val );
            if ( $out[ $k ] === '' && $bp === 'desktop' ) {
                $out[ $k ] = $defaults[ $k ];
            }
        }
    }

    // Pagination / flèches
    foreach ( [ 'pagination', 'fleches' ] as $key ) {
        foreach ( array_keys( cg_breakpoints() ) as $bp ) {
            $k = $key . '_' . $bp;
            $v = $input[ $k ] ?? '';
            if ( $bp === 'desktop' ) {
                $out[ $k ] = ! empty( $v ) ? 1 : 0;
            } else {
                $out[ $k ] = in_array( (string) $v, [ '0', '1' ], true ) ? (string) $v : '';
            }
        }
    }

    // Direction
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k = 'direction_' . $bp;
        $v = $input[ $k ] ?? '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $v === 'vertical' ) ? 'vertical' : 'horizontal';
        } else {
            $out[ $k ] = in_array( $v, [ 'horizontal', 'vertical' ], true ) ? $v : '';
        }
    }

    // Emplacement des flèches
    foreach ( array_keys( cg_breakpoints() ) as $bp ) {
        $k = 'arrows_position_' . $bp;
        $v = $input[ $k ] ?? '';
        if ( $bp === 'desktop' ) {
            $out[ $k ] = ( $v === 'outside' ) ? 'outside' : 'inside';
        } else {
            $out[ $k ] = in_array( $v, [ 'inside', 'outside' ], true ) ? $v : '';
        }
    }

    // Non-responsifs
    $out['speed']          = isset( $input['speed'] ) ? intval( $input['speed'] ) : $defaults['speed'];
    $out['autoplay_delay'] = isset( $input['autoplay_delay'] ) ? intval( $input['autoplay_delay'] ) : $defaults['autoplay_delay'];
    $out['start_slide']    = isset( $input['start_slide'] ) ? max( 0, intval( $input['start_slide'] ) ) : 0;
    foreach ( [ 'loop', 'autoplay', 'pause_hover', 'centered' ] as $b ) {
        $out[ $b ] = ! empty( $input[ $b ] ) ? 1 : 0;
    }
    $out['effet'] = in_array( $input['effet'] ?? '', [ 'slide', 'fade', 'coverflow', 'cube', 'flip' ], true )
        ? $input['effet'] : 'slide';

    $out['display_mode'] = in_array( $input['display_mode'] ?? '', [ 'carrousel', 'galerie' ], true )
        ? $input['display_mode'] : 'carrousel';

    // Couleurs
    foreach ( [ 'color_bullet', 'color_bullet_active', 'color_arrow', 'color_arrow_hover', 'color_bg' ] as $c ) {
        $val       = $input[ $c ] ?? '';
        $clean     = cg_sanitize_color( $val );
        $out[ $c ] = ( $clean === '' && $c !== 'color_bg' ) ? $defaults[ $c ] : $clean;
    }

    // CSS personnalisé
    $css = isset( $input['custom_css'] ) ? (string) $input['custom_css'] : '';
    $css = mb_substr( $css, 0, 50000 );
    $out['custom_css'] = trim( $css );

    // Post types par preset
    $allowed = array_keys( cg_get_available_post_types() );
    $pts     = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? $input['post_types'] : [];
    $out['post_types'] = array_values( array_intersect( $allowed, $pts ) );
    if ( empty( $out['post_types'] ) ) {
        $out['post_types'] = [ 'post' ];
    }

    return $out;
}

function cg_sanitize_settings( $input ) {
    $existing = cg_get_settings();
    $out = [
        'presets' => [],
    ];

    if ( isset( $input['presets'] ) && is_array( $input['presets'] ) ) {
        foreach ( $input['presets'] as $pid => $pdata ) {
            $pid_clean = sanitize_key( $pid );
            if ( empty( $pid_clean ) ) continue;
            $out['presets'][ $pid_clean ] = cg_sanitize_preset( $pdata, $pid_clean );
        }
    }

    return $out;
}

/**
 * Sanitize une valeur de longueur CSS.
 */
function cg_sanitize_length( $val ) {
    $val = trim( (string) $val );
    if ( $val === '' ) {
        return '';
    }
    $clean = preg_replace( '/[^a-zA-Z0-9.\-+*\/() %,]/', '', $val );
    return is_string( $clean ) ? $clean : '';
}

/**
 * Sanitize une couleur CSS : hex, rgb(a), hsl(a), var(--…), nom.
 */
function cg_sanitize_color( $val ) {
    $val = trim( (string) $val );
    if ( $val === '' ) {
        return '';
    }
    if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\s*\)$/i', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^var\(\s*--[\w-]+\s*(,\s*[^)]+)?\)$/i', $val ) ) {
        return $val;
    }
    if ( preg_match( '/^[a-zA-Z]+$/', $val ) ) {
        return $val;
    }
    return '';
}

/**
 * Liste des couleurs globales Elementor.
 */
function cg_get_elementor_globals() {
    if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
        return [];
    }
    $kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
    if ( ! $kit ) {
        return [];
    }
    $out = [];
    foreach ( [ 'system_colors', 'custom_colors' ] as $section ) {
        $items = $kit->get_settings_for_display( $section );
        if ( ! is_array( $items ) ) {
            continue;
        }
        foreach ( $items as $item ) {
            if ( empty( $item['_id'] ) ) {
                continue;
            }
            $out[] = [
                'id'    => $item['_id'],
                'title' => $item['title'] ?? $item['_id'],
                'color' => $item['color'] ?? '',
                'var'   => 'var(--e-global-color-' . $item['_id'] . ')',
            ];
        }
    }
    return $out;
}

function cg_get_available_post_types() {
    $types = get_post_types( [ 'public' => true ], 'objects' );
    unset( $types['attachment'] );
    $out = [];
    foreach ( $types as $slug => $obj ) {
        $out[ $slug ] = $obj->labels->singular_name ?: $slug;
    }
    return $out;
}

function cg_render_desktop_bool( $name, $current, $extra_attrs = '' ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>" <?php echo $extra_attrs; ?>>
        <option value="0" <?php selected( (int) $current, 0 ); ?>>Désactiver</option>
        <option value="1" <?php selected( (int) $current, 1 ); ?>>Activer</option>
    </select>
    <?php
}

function cg_render_desktop_direction( $name, $current ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>">
        <option value="horizontal" <?php selected( $current, 'horizontal' ); ?>>Horizontal</option>
        <option value="vertical"   <?php selected( $current, 'vertical' ); ?>>Vertical</option>
    </select>
    <?php
}

function cg_render_desktop_select( $name, $current, $options ) {
    ?>
    <select name="<?php echo esc_attr( $name ); ?>">
        <?php foreach ( $options as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

function cg_render_icon_copy() {
    ?>
    <svg class="cg-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
    </svg>
    <?php
}

function cg_render_slides_mockup( $slides_per_view ) {
    $total      = (int) ceil( $slides_per_view );
    $block_pct  = round( 100 / $slides_per_view, 4 );
    $has_peek   = fmod( $slides_per_view, 1 ) > 0.001;
    ?>
    <span class="cg-slide-mockup" aria-hidden="true">
        <?php for ( $i = 0; $i < $total; $i++ ) :
            $is_peek = $has_peek && ( $i === $total - 1 );
            ?>
            <span class="cg-slide-block<?php echo $is_peek ? ' cg-slide-peek' : ''; ?>" style="flex: 0 0 <?php echo $block_pct; ?>%"></span>
        <?php endfor; ?>
    </span>
    <?php
}

function cg_render_slides_picker( $name, $value, $allow_inherit ) {
    $presets = [ 1, 1.2, 2, 2.2, 3, 3.5, 4, 5 ];

    $matches_preset = false;
    foreach ( $presets as $p ) {
        if ( $value !== '' && floatval( $p ) === floatval( $value ) ) {
            $matches_preset = true;
            break;
        }
    }
    $is_empty  = ( $value === '' || $value === null );
    $is_custom = ! $is_empty && ! $matches_preset;
    ?>
    <div class="cg-slides">
        <div class="cg-slides-grid">
            <?php if ( $allow_inherit ) : ?>
                <button type="button" class="cg-slide-preset cg-slide-inherit<?php echo $is_empty ? ' active' : ''; ?>" data-value="" aria-label="Hériter de Desktop">
                    <span class="cg-slide-mockup cg-slide-mockup-special" aria-hidden="true">↺</span>
                    <span class="cg-slide-label">Hérite</span>
                </button>
            <?php endif; ?>

            <?php foreach ( $presets as $preset ) :
                $is_active     = ! $is_empty && ! $is_custom && floatval( $preset ) === floatval( $value );
                $is_fractional = ( floor( $preset ) != $preset ); // peek décimaux : pas pertinent en mode Galerie.
                ?>
                <button type="button" class="cg-slide-preset<?php echo $is_active ? ' active' : ''; ?><?php echo $is_fractional ? ' cg-slide-fractional' : ''; ?>"<?php echo $is_fractional ? ' data-cg-mode-only="carrousel"' : ''; ?> data-value="<?php echo esc_attr( $preset ); ?>" aria-label="<?php echo esc_attr( $preset ); ?> slides visibles">
                    <?php cg_render_slides_mockup( $preset ); ?>
                    <span class="cg-slide-label"><?php echo esc_html( $preset ); ?></span>
                </button>
            <?php endforeach; ?>

            <button type="button" class="cg-slide-preset cg-slide-custom-btn<?php echo $is_custom ? ' active' : ''; ?>" data-value="custom" aria-label="Valeur personnalisée">
                <span class="cg-slide-mockup cg-slide-mockup-special" aria-hidden="true">⚙</span>
                <span class="cg-slide-label">Custom</span>
            </button>
        </div>

        <div class="cg-slides-custom-wrap"<?php echo $is_custom ? '' : ' hidden'; ?>>
            <label class="cg-slides-custom-label">
                <span>Valeur personnalisée :</span>
                <input type="number" step="0.1" min="0.1" class="cg-slide-custom-input"
                    value="<?php echo $is_custom ? esc_attr( $value ) : ''; ?>" placeholder="Ex: 1.7">
            </label>
        </div>

        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" class="cg-slide-hidden" value="<?php echo esc_attr( $value ); ?>">
    </div>
    <?php
}

function cg_render_unit( $name, $value, $placeholder = '' ) {
    ?>
    <span class="cg-unit">
        <input type="number" class="cg-unit-value" step="0.1" placeholder="<?php echo esc_attr( $placeholder ); ?>">
        <input type="text"   class="cg-unit-custom" placeholder="ex: calc(50% - 10px)" hidden>
        <span class="cg-unit-current" tabindex="0" role="button">px ▾</span>
        <ul class="cg-unit-list" hidden>
            <li data-unit="px">px</li>
            <li data-unit="em">em</li>
            <li data-unit="rem">rem</li>
            <li data-unit="%">%</li>
            <li data-unit="vw">vw</li>
            <li data-unit="vh">vh</li>
            <li data-unit="custom">⚙ Custom</li>
        </ul>
        <input type="hidden" class="cg-unit-hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
    </span>
    <?php
}

function cg_render_color( $name, $value ) {
    ?>
    <span class="cg-color">
        <span class="cg-color-picker">
            <input type="color" class="cg-color-swatch" value="#000000" title="Choisir une couleur">
            <span class="cg-color-opacity-wrap">
                <input type="range" min="0" max="100" step="1" value="100" class="cg-color-opacity" title="Opacité" aria-label="Opacité">
                <span class="cg-color-opacity-value" aria-hidden="true">100%</span>
            </span>
        </span>
        <input type="text" class="cg-color-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="#000000 / rgba(…) / var(--…)" spellcheck="false">
        <select class="cg-color-globals" hidden></select>
    </span>
    <?php
}

function cg_render_resp_control( $key, $bp, $current, $options, $hidden_name ) {
    ?>
    <span class="cg-resp" data-key="<?php echo esc_attr( $key ); ?>" data-bp="<?php echo esc_attr( $bp ); ?>">
        <select class="cg-resp-display">
            <?php foreach ( $options as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="<?php echo esc_attr( $hidden_name ); ?>" value="<?php echo esc_attr( $current ); ?>" class="cg-resp-hidden">
        <button type="button" class="cg-reset button-link" title="Hériter de Desktop" aria-label="Hériter de Desktop">↺</button>
    </span>
    <?php
}

/**
 * Page 1 : LISTE DES SHORTCODES (Style Page WordPress)
 */
function cg_render_list_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $all_settings = cg_get_settings();
    $presets      = $all_settings['presets'];
    $opt          = CG_OPTION_KEY;

    // Suppression d'un shortcode
    if ( isset( $_POST['cg_delete_preset_id'] ) && check_admin_referer( 'cg_delete_preset_action', 'cg_delete_preset_nonce' ) ) {
        $del_id = sanitize_key( $_POST['cg_delete_preset_id'] );
        if ( isset( $presets[ $del_id ] ) ) {
            unset( $all_settings['presets'][ $del_id ] );
            update_option( CG_OPTION_KEY, $all_settings );
            echo '<div class="notice notice-success is-dismissible"><p>Shortcode supprimé avec succès.</p></div>';
            $all_settings = cg_get_settings();
            $presets      = $all_settings['presets'];
        }
    }

    // Duplication d'un shortcode
    if ( isset( $_POST['cg_duplicate_preset_id'] ) && check_admin_referer( 'cg_duplicate_preset_action', 'cg_duplicate_preset_nonce' ) ) {
        $dup_id = sanitize_key( $_POST['cg_duplicate_preset_id'] );
        if ( isset( $presets[ $dup_id ] ) ) {
            $new_name = $presets[ $dup_id ]['name'] . ' (Copie)';
            $new_id   = sanitize_key( $new_name );
            if ( empty( $new_id ) ) {
                $new_id = 'preset_' . time();
            }
            $base_id = $new_id;
            $i = 1;
            while ( isset( $presets[ $new_id ] ) ) {
                $new_id = $base_id . '_' . $i;
                $i++;
            }
            $all_settings['presets'][ $new_id ] = array_merge( $presets[ $dup_id ], [ 'name' => $new_name ] );
            update_option( CG_OPTION_KEY, $all_settings );
            echo '<div class="notice notice-success is-dismissible"><p>Shortcode dupliqué avec succès.</p></div>';
            $presets = $all_settings['presets'];
        }
    }
    ?>
    <div class="wrap cg-wrap">
        <h1 class="wp-heading-inline">Shortcodes Carrousel Galerie</h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=carrousel-galerie-edit' ) ); ?>" class="page-title-action">Ajouter un shortcode</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:20px;">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary" style="font-weight:600;">Nom du shortcode</th>
                    <th scope="col" class="manage-column" style="font-weight:600;">Code d'intégration (Shortcode)</th>
                    <th scope="col" class="manage-column" style="font-weight:600;">Contenus associés</th>
                    <th scope="col" class="manage-column" style="width: 150px; font-weight:600; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $presets ) ) : ?>
                    <tr>
                        <td colspan="4" style="padding:20px; text-align:center; color:#646970;">
                            Aucun shortcode créé pour le moment. <a href="<?php echo esc_url( admin_url( 'admin.php?page=carrousel-galerie-edit' ) ); ?>">Créer un premier shortcode</a>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php
                    $available_pts = cg_get_available_post_types();
                    foreach ( $presets as $pid => $pdata ) :
                        $edit_url = admin_url( 'admin.php?page=carrousel-galerie-edit&preset=' . $pid );
                        $shortcode_code = ( $pid === 'default' ) ? '[galerie_projet]' : '[galerie_projet preset="' . esc_attr( $pid ) . '"]';
                        $assigned_pts = ! empty( $pdata['post_types'] ) && is_array( $pdata['post_types'] ) ? $pdata['post_types'] : [ 'post' ];
                        $pt_labels = [];
                        foreach ( $assigned_pts as $pt_slug ) {
                            if ( isset( $available_pts[ $pt_slug ] ) ) {
                                $pt_labels[] = $available_pts[ $pt_slug ];
                            } else {
                                $pt_labels[] = $pt_slug;
                            }
                        }
                        ?>
                        <tr>
                            <td class="column-title has-row-actions column-primary">
                                <strong><a class="row-title" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $pdata['name'] ); ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>">Modifier</a> | </span>
                                    <span class="inline">
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field( 'cg_duplicate_preset_action', 'cg_duplicate_preset_nonce' ); ?>
                                            <input type="hidden" name="cg_duplicate_preset_id" value="<?php echo esc_attr( $pid ); ?>">
                                            <button type="submit" class="button-link" style="color:#2271b1; cursor:pointer;">Dupliquer</button>
                                        </form>
                                    </span>
                                    | <span class="trash">
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce shortcode ?');">
                                            <?php wp_nonce_field( 'cg_delete_preset_action', 'cg_delete_preset_nonce' ); ?>
                                            <input type="hidden" name="cg_delete_preset_id" value="<?php echo esc_attr( $pid ); ?>">
                                            <button type="submit" class="button-link" style="color:#b32d2e; cursor:pointer;">Supprimer</button>
                                        </form>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="cg-code-wrap" style="display:inline-flex; align-items:center; gap:8px; background:#f0f0f1; padding:4px 10px; border-radius:6px; border:1px solid #c3c4c7;">
                                    <code style="background:none; padding:0; font-size:13px; color:#1d2327; font-weight:600;"><?php echo esc_html( $shortcode_code ); ?></code>
                                    <button type="button" class="cg-copy cg-copy-badge" data-copy="<?php echo esc_attr( $shortcode_code ); ?>" title="Copier le shortcode" aria-label="Copier le shortcode">
                                        <?php cg_render_icon_copy(); ?>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:13px; color:#50575e;"><?php echo esc_html( implode( ', ', $pt_labels ) ); ?></span>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-secondary">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Page 2 : AJOUTER / MODIFIER UN SHORTCODE
 */
function cg_render_edit_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $all_settings = cg_get_settings();
    $presets      = $all_settings['presets'];
    $opt          = CG_OPTION_KEY;

    $is_new = ! isset( $_GET['preset'] ) || ! isset( $presets[ sanitize_key( $_GET['preset'] ) ] );
    $active_preset = $is_new ? 'preset_' . time() : sanitize_key( $_GET['preset'] );

    $s = $is_new
        ? array_merge( cg_default_settings(), [ 'name' => 'Nouveau shortcode' ] )
        : $presets[ $active_preset ];

    $preset_opt = $opt . "[presets][$active_preset]";
    $shortcode_tag = ( $active_preset === 'default' ) ? '[galerie_projet]' : '[galerie_projet preset="' . esc_attr( $active_preset ) . '"]';
    $current_pts = ! empty( $s['post_types'] ) && is_array( $s['post_types'] ) ? $s['post_types'] : [ 'post' ];
    ?>
    <div class="wrap cg-wrap" data-cg-display="<?php echo esc_attr( $s['display_mode'] ); ?>">
        <h1 class="wp-heading-inline">
            <?php echo $is_new ? 'Ajouter un shortcode' : 'Modifier le shortcode : ' . esc_html( $s['name'] ); ?>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=carrousel-galerie' ) ); ?>" class="page-title-action">← Retour à la liste</a>
        <hr class="wp-header-end">

        <form method="post" action="options.php" class="cg-form" style="margin-top:20px;" data-cg-opt-prefix="<?php echo esc_attr( $preset_opt ); ?>">
            <?php settings_fields( 'cg_settings_group' ); ?>

            <!-- Conserver les autres présets intacts lors de la sauvegarde -->
            <?php foreach ( $presets as $pid => $pdata ) :
                if ( $pid === $active_preset ) continue;
                foreach ( $pdata as $k => $v ) :
                    if ( is_array( $v ) ) {
                        foreach ( $v as $subk => $subv ) {
                            echo '<input type="hidden" name="' . esc_attr( $opt . "[presets][$pid][$k][$subk]" ) . '" value="' . esc_attr( $subv ) . '">';
                        }
                    } else {
                        echo '<input type="hidden" name="' . esc_attr( $opt . "[presets][$pid][$k]" ) . '" value="' . esc_attr( $v ) . '">';
                    }
                endforeach;
            endforeach; ?>

            <section class="cg-card cg-card-mode">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Mode d'affichage</h2>
                    <p class="cg-card-sub">Le choix structurant : slider Swiper ou grille statique.</p>
                </header>
                <div class="cg-card-body">
                    <div class="cg-mode-switch">
                        <label class="cg-mode-option<?php echo $s['display_mode'] === 'carrousel' ? ' is-active' : ''; ?>">
                            <input type="radio" name="<?php echo esc_attr( $preset_opt ); ?>[display_mode]" value="carrousel" <?php checked( $s['display_mode'], 'carrousel' ); ?>>
                            <span class="cg-mode-icon" aria-hidden="true">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="7" width="14" height="18" rx="1.5"/>
                                    <rect x="2" y="9" width="5" height="14" rx="1"/>
                                    <rect x="25" y="9" width="5" height="14" rx="1"/>
                                </svg>
                            </span>
                            <span class="cg-mode-title">Carrousel</span>
                            <span class="cg-mode-desc">Slider Swiper avec pagination, flèches, autoplay, loop…</span>
                        </label>
                        <label class="cg-mode-option<?php echo $s['display_mode'] === 'galerie' ? ' is-active' : ''; ?>">
                            <input type="radio" name="<?php echo esc_attr( $preset_opt ); ?>[display_mode]" value="galerie" <?php checked( $s['display_mode'], 'galerie' ); ?>>
                            <span class="cg-mode-icon" aria-hidden="true">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="11" height="11" rx="1.5"/>
                                    <rect x="18" y="3" width="11" height="11" rx="1.5"/>
                                    <rect x="3" y="18" width="11" height="11" rx="1.5"/>
                                    <rect x="18" y="18" width="11" height="11" rx="1.5"/>
                                </svg>
                            </span>
                            <span class="cg-mode-title">Galerie</span>
                            <span class="cg-mode-desc">Grille CSS responsive, sans Swiper. Plus léger.</span>
                        </label>
                    </div>
                    <p class="cg-mode-note" data-cg-mode-when="galerie">
                        <strong>Mode Galerie actif&nbsp;:</strong> choisis le nombre de colonnes et de lignes par écran (Desktop / Tablette / Mobile) ; les images au-delà de colonnes × lignes sont masquées. Pagination, flèches, autoplay, loop sont ignorés.
                    </p>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Informations du shortcode</h2>
                </header>
                <div class="cg-card-body">
                    <table class="form-table">
                        <tr>
                            <th>Nom du shortcode</th>
                            <td>
                                <input type="text" name="<?php echo esc_attr( $preset_opt ); ?>[name]" value="<?php echo esc_attr( $s['name'] ); ?>" class="large-text" required placeholder="Ex: Carrousel Page Accueil">
                            </td>
                        </tr>
                        <?php if ( ! $is_new ) : ?>
                            <tr>
                                <th>Code du shortcode</th>
                                <td>
                                    <div class="cg-code-wrap" style="display:inline-flex; align-items:center; gap:10px; background:#f0f0f1; padding:6px 12px; border-radius:6px; border:1px solid #c3c4c7;">
                                        <code style="font-size:14px; font-weight:700; color:#1d2327;"><?php echo esc_html( $shortcode_tag ); ?></code>
                                        <button type="button" class="cg-copy cg-copy-badge" data-copy="<?php echo esc_attr( $shortcode_tag ); ?>" title="Copier le shortcode" aria-label="Copier le shortcode">
                                            <?php cg_render_icon_copy(); ?>
                                        </button>
                                    </div>
                                    <p class="description" style="margin-top:6px;">Copie et colle ce code dans l'éditeur de tes pages ou articles pour afficher ce carrousel.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Réglages d'affichage de la metabox</h2>
                    <p class="cg-card-sub">Sélectionne les types de contenu sur lesquels afficher le gestionnaire d'images pour ce shortcode.</p>
                </header>
                <div class="cg-card-body">
                    <?php
                    $native_pt = [];
                    $custom_pt = [];
                    foreach ( cg_get_available_post_types() as $slug => $label ) {
                        $obj = get_post_type_object( $slug );
                        if ( $obj && ! empty( $obj->_builtin ) ) {
                            $native_pt[ $slug ] = $label;
                        } else {
                            $custom_pt[ $slug ] = $label;
                        }
                    }
                    $render_pt = function ( $items ) use ( $preset_opt, $current_pts ) {
                        foreach ( $items as $slug => $label ) : ?>
                            <label class="cg-pt">
                                <input type="checkbox" name="<?php echo esc_attr( $preset_opt ); ?>[post_types][]" value="<?php echo esc_attr( $slug ); ?>"
                                    <?php checked( in_array( $slug, $current_pts, true ) ); ?>>
                                <span class="cg-pt-label"><?php echo esc_html( $label ); ?></span>
                                <code class="cg-pt-slug"><?php echo esc_html( $slug ); ?></code>
                            </label>
                        <?php endforeach;
                    };
                    ?>

                    <?php if ( ! empty( $native_pt ) ) : ?>
                        <div class="cg-pt-group">
                            <div class="cg-pt-grid">
                                <?php $render_pt( $native_pt ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $custom_pt ) ) : ?>
                        <div class="cg-pt-group" style="margin-top:15px;">
                            <h4 class="cg-pt-group-title">
                                <span>CPT UI / Custom Post Types</span>
                            </h4>
                            <div class="cg-pt-grid">
                                <?php $render_pt( $custom_pt ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Layout &amp; comportement</h2>
                    <p class="cg-card-sub">Réglages par taille d'écran. Tablette et Mobile héritent de Desktop si laissés vides.</p>
                </header>
                <div class="cg-card-body">

                    <div class="cg-tabs" role="tablist">
                        <?php foreach ( cg_breakpoints() as $slug => $bp ) : ?>
                            <button type="button" class="cg-tab<?php echo $slug === 'desktop' ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $slug ); ?>" role="tab">
                                <?php echo esc_html( $bp['label'] ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ( cg_breakpoints() as $slug => $bp ) :
                        $is_desktop = ( $slug === 'desktop' );
                        ?>
                        <div class="cg-tab-panel" data-tab="<?php echo esc_attr( $slug ); ?>"<?php echo $is_desktop ? '' : ' hidden'; ?>>
                    <table class="form-table">
                        <tr>
                            <th>
                                <span data-cg-mode-only="carrousel">Slides visibles</span>
                                <span data-cg-mode-only="galerie">Colonnes</span>
                            </th>
                            <td>
                                <?php cg_render_slides_picker( $preset_opt . "[slides_$slug]", $s[ 'slides_' . $slug ], ! $is_desktop ); ?>
                            </td>
                        </tr>
                        <tr data-cg-mode-only="galerie">
                            <th>Lignes</th>
                            <td>
                                <input type="number" min="0" step="1" style="width:90px"
                                    name="<?php echo esc_attr( $preset_opt . "[rows_$slug]" ); ?>"
                                    value="<?php echo esc_attr( $s[ 'rows_' . $slug ] ); ?>"
                                    placeholder="<?php echo $is_desktop ? '0' : 'Hérite'; ?>">
                                <span class="cg-hint"><?php echo $is_desktop ? '0 = illimité (toutes les images)' : 'Vide = hérite de Desktop · 0 = illimité'; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <span data-cg-mode-only="carrousel">Espace entre slides</span>
                                <span data-cg-mode-only="galerie">Gap entre images</span>
                            </th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[space_$slug]", $s[ 'space_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Padding latéral</th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[padding_$slug]", $s[ 'padding_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                                <?php if ( $is_desktop ) : ?><span class="cg-hint" data-cg-mode-only="carrousel">preview slides ←/→</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php
                        $bool_options = [ '0' => 'Désactiver', '1' => 'Activer' ];
                        $dir_options  = [ 'horizontal' => 'Horizontal', 'vertical' => 'Vertical' ];
                        ?>
                        <tr data-cg-mode-only="carrousel">
                            <th>Pagination</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_bool( $preset_opt . '[pagination_desktop]', $s['pagination_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'pagination', $slug, $s[ 'pagination_' . $slug ], $bool_options, $preset_opt . "[pagination_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="pagination" data-cg-mode-only="carrousel">
                            <th>Taille des bullets</th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[bullet_size_$slug]", $s[ 'bullet_size_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="pagination" data-cg-mode-only="carrousel">
                            <th>Espace entre bullets</th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[bullet_gap_$slug]", $s[ 'bullet_gap_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr data-cg-mode-only="carrousel">
                            <th>Flèches de navigation</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_bool( $preset_opt . '[fleches_desktop]', $s['fleches_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'fleches', $slug, $s[ 'fleches_' . $slug ], $bool_options, $preset_opt . "[fleches_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $arrows_pos_options = [ 'inside' => 'Intérieur', 'outside' => 'Extérieur' ]; ?>
                        <tr data-cg-conditional="fleches" data-cg-mode-only="carrousel">
                            <th>Emplacement des flèches</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_select( $preset_opt . '[arrows_position_desktop]', $s['arrows_position_desktop'], $arrows_pos_options ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'arrows_position', $slug, $s[ 'arrows_position_' . $slug ], $arrows_pos_options, $preset_opt . "[arrows_position_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr data-cg-conditional="fleches" data-cg-mode-only="carrousel">
                            <th>Taille des flèches</th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[arrow_size_$slug]", $s[ 'arrow_size_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                            </td>
                        </tr>
                        <tr data-cg-mode-only="carrousel">
                            <th>Direction</th>
                            <td>
                                <?php if ( $is_desktop ) : ?>
                                    <?php cg_render_desktop_direction( $preset_opt . '[direction_desktop]', $s['direction_desktop'] ); ?>
                                <?php else : ?>
                                    <?php cg_render_resp_control( 'direction', $slug, $s[ 'direction_' . $slug ], $dir_options, $preset_opt . "[direction_$slug]" ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <span data-cg-mode-only="carrousel">Hauteur du carrousel</span>
                                <span data-cg-mode-only="galerie">Hauteur max image</span>
                            </th>
                            <td>
                                <?php cg_render_unit( $preset_opt . "[image_max_height_$slug]", $s[ 'image_max_height_' . $slug ], $is_desktop ? '' : 'Hérite de Desktop' ); ?>
                                <span class="cg-hint" data-cg-mode-only="galerie">en % = proportion de la largeur (50% → image 2× plus large que haute)</span>
                            </td>
                        </tr>
                        </table>
                    </div>
                <?php endforeach; ?>

                </div>
            </section>

            <section class="cg-card" data-cg-mode-only="carrousel">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Comportement global</h2>
                    <p class="cg-card-sub">Réglages d'animation et de lecture du carrousel.</p>
                </header>
                <div class="cg-card-body">
                    <div class="cg-global-grid">
                        <table class="form-table">
                            <tr><th>Vitesse (ms)</th><td><input type="number" name="<?php echo $preset_opt; ?>[speed]" value="<?php echo esc_attr( $s['speed'] ); ?>"></td></tr>
                            <tr><th>Autoplay</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $preset_opt; ?>[autoplay]" value="1" <?php checked( $s['autoplay'], 1 ); ?> data-cg-toggle="autoplay"><span>Activer</span></label></td></tr>
                        </table>
                        <table class="form-table">
                            <tr><th>Slide de démarrage</th><td><input type="number" min="0" name="<?php echo $preset_opt; ?>[start_slide]" value="<?php echo esc_attr( $s['start_slide'] ); ?>" style="width:80px"> <span class="cg-hint">0 = première</span></td></tr>
                            <tr><th>Boucle infinie</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $preset_opt; ?>[loop]" value="1" <?php checked( $s['loop'], 1 ); ?>><span>Activer</span></label></td></tr>
                        </table>

                        <table class="form-table cg-autoplay-sub" data-cg-when="autoplay">
                            <tr><th>Délai autoplay (ms)</th><td><input type="number" name="<?php echo $preset_opt; ?>[autoplay_delay]" value="<?php echo esc_attr( $s['autoplay_delay'] ); ?>"></td></tr>
                        </table>
                        <table class="form-table cg-autoplay-sub" data-cg-when="autoplay">
                            <tr><th>Pause au survol</th><td><label class="cg-toggle"><input type="checkbox" name="<?php echo $preset_opt; ?>[pause_hover]" value="1" <?php checked( $s['pause_hover'], 1 ); ?>><span>Activer</span></label></td></tr>
                        </table>
                    </div>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">Couleurs du shortcode</h2>
                </header>
                <div class="cg-card-body">
                    <table class="form-table cg-colors">
                        <tr data-cg-mode-only="carrousel"><th>Bullet inactif</th><td><?php cg_render_color( $preset_opt . '[color_bullet]', $s['color_bullet'] ); ?></td></tr>
                        <tr data-cg-mode-only="carrousel"><th>Bullet actif</th><td><?php cg_render_color( $preset_opt . '[color_bullet_active]', $s['color_bullet_active'] ); ?></td></tr>
                        <tr data-cg-mode-only="carrousel"><th>Flèche</th><td><?php cg_render_color( $preset_opt . '[color_arrow]', $s['color_arrow'] ); ?></td></tr>
                        <tr data-cg-mode-only="carrousel"><th>Flèche au survol</th><td><?php cg_render_color( $preset_opt . '[color_arrow_hover]', $s['color_arrow_hover'] ); ?></td></tr>
                        <tr>
                            <th>
                                <span data-cg-mode-only="carrousel">Fond du carrousel</span>
                                <span data-cg-mode-only="galerie">Fond de la galerie</span>
                            </th>
                            <td><?php cg_render_color( $preset_opt . '[color_bg]', $s['color_bg'] ); ?></td>
                        </tr>
                    </table>
                </div>
            </section>

            <section class="cg-card">
                <header class="cg-card-head">
                    <h2 class="cg-card-title">CSS personnalisé (Spécifique à ce shortcode)</h2>
                </header>
                <div class="cg-card-body">
                    <textarea
                        id="cg-css-textarea"
                        name="<?php echo $preset_opt; ?>[custom_css]"
                        class="cg-css-textarea"
                        rows="8"
                        spellcheck="false"
                        placeholder="/* CSS spécifique à ce shortcode... */"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
                </div>
            </section>

            <div class="cg-actions">
                <?php submit_button( $is_new ? 'Créer le shortcode' : 'Enregistrer les modifications', 'primary cg-submit', 'submit', false ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=carrousel-galerie' ) ); ?>" class="button button-secondary cg-cancel">Annuler</a>
            </div>
        </form>
    </div>
    <?php
}
