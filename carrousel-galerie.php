<?php
/**
 * Plugin Name: Carrousel Galerie
 * Description: Affiche un carrousel Swiper de la galerie propre à chaque article, page ou CPT.
 * Version: 1.1.0
 * Author: Shake
 * Requires PHP: 7.4
 * Text Domain: carrousel-galerie
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CG_VERSION', '1.1.0' );
define( 'CG_PATH', plugin_dir_path( __FILE__ ) );
define( 'CG_URL', plugin_dir_url( __FILE__ ) );
define( 'CG_OPTION_KEY', 'carrousel_galerie_settings' );

require_once CG_PATH . 'includes/settings.php';
require_once CG_PATH . 'includes/metabox.php';
require_once CG_PATH . 'includes/shortcode.php';

function cg_register_assets() {
    $swiper_handle  = 'cg-swiper';
    $swiper_version = '11.1.14';

    wp_register_script(
        $swiper_handle,
        'https://cdn.jsdelivr.net/npm/swiper@' . $swiper_version . '/swiper-bundle.min.js',
        [],
        $swiper_version,
        true
    );
    wp_add_inline_script(
        $swiper_handle,
        'window.cgSwiper = window.Swiper;',
        'after'
    );

    wp_register_style(
        'cg-swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@' . $swiper_version . '/swiper-bundle.min.css',
        [],
        $swiper_version
    );

    wp_register_style(
        'cg-carrousel',
        CG_URL . 'assets/css/carrousel.css',
        [ 'cg-swiper-css' ],
        CG_VERSION
    );

    wp_register_script(
        'cg-carrousel',
        CG_URL . 'assets/js/carrousel.js',
        [ $swiper_handle ],
        CG_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'cg_register_assets' );

function cg_admin_settings_assets( $hook ) {
    if ( strpos( $hook, 'carrousel-galerie' ) === false ) {
        return;
    }
    wp_enqueue_style( 'cg-admin-variables', CG_URL . 'assets/css/admin-variables.css', [], CG_VERSION );
    wp_enqueue_style( 'cg-admin-settings', CG_URL . 'assets/css/admin-settings.css', [ 'cg-admin-variables' ], CG_VERSION );
    wp_enqueue_script( 'cg-admin-settings', CG_URL . 'assets/js/admin-settings.js', [], CG_VERSION, true );
    wp_localize_script( 'cg-admin-settings', 'cgAdminData', [
        'optionKey'        => CG_OPTION_KEY,
        'elementorGlobals' => function_exists( 'cg_get_elementor_globals' ) ? cg_get_elementor_globals() : [],
    ] );
}
add_action( 'admin_enqueue_scripts', 'cg_admin_settings_assets' );

function cg_print_custom_css() {
    if ( is_admin() ) {
        return;
    }
    $settings = function_exists( 'cg_get_settings' ) ? cg_get_settings() : [];
    $combined_css = '';
    if ( ! empty( $settings['presets'] ) && is_array( $settings['presets'] ) ) {
        foreach ( $settings['presets'] as $p ) {
            if ( ! empty( $p['custom_css'] ) ) {
                $combined_css .= "\n" . (string) $p['custom_css'];
            }
        }
    }
    $combined_css = trim( $combined_css );
    if ( $combined_css === '' ) {
        return;
    }
    $css = preg_replace( '#</\s*style#i', '<\/style', $combined_css );
    echo "\n<style id=\"cg-custom-css\">\n" . $css . "\n</style>\n";
}
add_action( 'wp_footer', 'cg_print_custom_css', 100 );

function cg_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=carrousel-galerie' ) ) . '">Shortcodes</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cg_plugin_action_links' );
