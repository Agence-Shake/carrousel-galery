<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CG_META_KEY', '_cg_gallery_ids' );

/**
 * Récupère l'ensemble des post types activés à travers tous les shortcodes (présets).
 */
function cg_get_active_post_types() {
    $settings = cg_get_settings();
    $active_pts = [];
    if ( ! empty( $settings['presets'] ) && is_array( $settings['presets'] ) ) {
        foreach ( $settings['presets'] as $preset ) {
            if ( ! empty( $preset['post_types'] ) && is_array( $preset['post_types'] ) ) {
                $active_pts = array_merge( $active_pts, $preset['post_types'] );
            }
        }
    }
    return array_unique( $active_pts );
}

/**
 * Ajoute la metabox sur les post types choisis dans les réglages.
 */
function cg_add_metabox() {
    $pts = cg_get_active_post_types();
    foreach ( $pts as $pt ) {
        add_meta_box(
            'cg_gallery_metabox',
            'Carrousel — Galerie d\'images',
            'cg_render_metabox',
            $pt,
            'normal',
            'high'
        );
    }
}
add_action( 'add_meta_boxes', 'cg_add_metabox' );

/**
 * Retourne les présets dont les post_types incluent $post_type, sous la forme
 * [ preset_id => ['name' => …, 'tag' => '[galerie_projet preset="…"]'] ].
 */
function cg_get_presets_for_post_type( $post_type ) {
    $settings = cg_get_settings();
    $out      = [];
    if ( empty( $settings['presets'] ) || ! is_array( $settings['presets'] ) ) {
        return $out;
    }
    foreach ( $settings['presets'] as $pid => $preset ) {
        $pts = ! empty( $preset['post_types'] ) && is_array( $preset['post_types'] ) ? $preset['post_types'] : [];
        if ( ! in_array( $post_type, $pts, true ) ) {
            continue;
        }
        $out[ $pid ] = [
            'name' => ! empty( $preset['name'] ) ? $preset['name'] : ucfirst( $pid ),
            'tag'  => ( $pid === 'default' )
                ? '[galerie_projet]'
                : '[galerie_projet preset="' . $pid . '"]',
        ];
    }
    return $out;
}

/**
 * Rendu de la metabox.
 */
function cg_render_metabox( $post ) {
    wp_nonce_field( 'cg_save_gallery', 'cg_gallery_nonce' );

    $ids = get_post_meta( $post->ID, CG_META_KEY, true );
    $ids = is_array( $ids ) ? array_map( 'intval', $ids ) : [];

    // Plusieurs shortcodes peuvent cibler le même post type : on les liste tous
    // pour que l'utilisateur sache lequel coller ici.
    $matching = cg_get_presets_for_post_type( $post->post_type );

    if ( count( $matching ) === 1 ) :
        $only = reset( $matching ); ?>
        <p>Sélectionne et réordonne les images du carrousel. Affichage avec <code><?php echo esc_html( $only['tag'] ); ?></code>.</p>
    <?php elseif ( count( $matching ) > 1 ) : ?>
        <p>Sélectionne et réordonne les images du carrousel. Shortcodes disponibles pour ce type de contenu&nbsp;:</p>
        <ul class="cg-shortcode-hints">
            <?php foreach ( $matching as $info ) : ?>
                <li><code><?php echo esc_html( $info['tag'] ); ?></code> <span class="cg-shortcode-hint-name">— <?php echo esc_html( $info['name'] ); ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p>Sélectionne et réordonne les images du carrousel. Affichage avec <code>[galerie_projet]</code>.</p>
    <?php endif; ?>

    <ul id="cg-gallery-list" class="cg-gallery-list">
        <?php foreach ( $ids as $id ) :
            $thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( ! $thumb ) continue;
            ?>
            <li class="cg-item" data-id="<?php echo esc_attr( $id ); ?>" draggable="true">
                <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                <button type="button" class="cg-remove" aria-label="Retirer">&times;</button>
            </li>
        <?php endforeach; ?>
    </ul>

    <p>
        <button type="button" class="button button-primary" id="cg-gallery-add">Ajouter / modifier les images</button>
    </p>

    <input type="hidden" id="cg_gallery_ids" name="cg_gallery_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
    <?php
}

/**
 * Sauvegarde.
 */
function cg_save_metabox( $post_id ) {
    if ( ! isset( $_POST['cg_gallery_nonce'] ) || ! wp_verify_nonce( $_POST['cg_gallery_nonce'], 'cg_save_gallery' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $raw = isset( $_POST['cg_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['cg_gallery_ids'] ) ) : '';
    $ids = array_values( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) );

    if ( empty( $ids ) ) {
        delete_post_meta( $post_id, CG_META_KEY );
    } else {
        update_post_meta( $post_id, CG_META_KEY, $ids );
    }
}
add_action( 'save_post', 'cg_save_metabox' );

/**
 * Enqueue media uploader + JS/CSS admin sur les écrans d'édition concernés.
 */
function cg_admin_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    $active_pts = cg_get_active_post_types();
    if ( ! in_array( $screen->post_type, $active_pts, true ) ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'cg-admin-metabox',
        CG_URL . 'assets/js/admin-metabox.js',
        [],
        CG_VERSION,
        true
    );
    wp_enqueue_style(
        'cg-admin-variables',
        CG_URL . 'assets/css/admin-variables.css',
        [],
        CG_VERSION
    );
    wp_enqueue_style(
        'cg-admin-metabox',
        CG_URL . 'assets/css/admin-metabox.css',
        [ 'cg-admin-variables' ],
        CG_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'cg_admin_assets' );
