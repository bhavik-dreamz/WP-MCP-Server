<?php
/**
 * Plugin Name: WP MCP Server
 * Plugin URI:  https://example.org/wp-mcp-server
 * Description: Exposes WordPress as an MCP server for Claude Desktop and other MCP clients.
 * Version:     1.0.0
 * Author:      Generated
 * Text Domain: wp-mcp-server
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Composer autoload (vendor may be absent in this repo)
$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

// PSR-4 autoloader for plugin includes
spl_autoload_register( function ( $class ) {
    $prefix = 'WP_MCP\\';
    $base_dir = __DIR__ . '/includes/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Admin autoload
spl_autoload_register( function ( $class ) {
    $prefix = 'WP_MCP\\Admin\\';
    $base_dir = __DIR__ . '/admin/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Initialize
add_action( 'plugins_loaded', function () {
    // Load core
    if ( class_exists( 'WP_MCP\Core\REST_Controller' ) ) {
        WP_MCP\Core\REST_Controller::init();
    }

    if ( is_admin() && class_exists( 'WP_MCP\Admin\Admin_Page' ) ) {
        WP_MCP\Admin\Admin_Page::init();
    }

    // WooCommerce notice
    add_action( 'admin_notices', function () {
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'WP MCP Server: WooCommerce not active - WooCommerce tools will be unavailable.', 'wp-mcp-server' ) . '</p></div>';
        }
    } );
} );
