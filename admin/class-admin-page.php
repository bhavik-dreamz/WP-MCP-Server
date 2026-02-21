<?php
namespace WP_MCP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Page {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_action( 'wp_ajax_wp_mcp_test_connection', array( __CLASS__, 'ajax_test_connection' ) );
    }

    public static function add_menu() {
        add_options_page( __( 'WP MCP Server', 'wp-mcp-server' ), __( 'WP MCP Server', 'wp-mcp-server' ), 'manage_options', 'wp-mcp-server', array( __CLASS__, 'render_page' ) );
    }

    public static function register_settings() {
        register_setting( 'wp_mcp_settings', 'wp_mcp_site_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
        register_setting( 'wp_mcp_settings', 'wp_mcp_enabled_tools', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_tools' ) ) );
        register_setting( 'wp_mcp_settings', 'wp_mcp_allowed_cpts', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_allowed_cpts' ) ) );
    }

    public static function sanitize_tools( $val ) {
        if ( ! is_array( $val ) ) {
            return array();
        }
        return array_map( 'sanitize_text_field', $val );
    }

    public static function sanitize_allowed_cpts( $val ) {
        if ( is_array( $val ) ) {
            return array_map( 'sanitize_text_field', $val );
        }
        // accept comma-separated
        if ( is_string( $val ) ) {
            $parts = array_map( 'trim', explode( ',', $val ) );
            return array_filter( array_map( 'sanitize_text_field', $parts ) );
        }
        return array();
    }

    public static function enqueue( $hook ) {
        if ( $hook !== 'settings_page_wp-mcp-server' ) {
            return;
        }
        wp_enqueue_style( 'wp-mcp-admin', plugin_dir_url( __DIR__ ) . 'assets/admin.css' );
        wp_enqueue_script( 'wp-mcp-admin-js', plugin_dir_url( __DIR__ ) . 'assets/admin.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'wp-mcp-admin-js', 'wp_mcp', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wp_mcp_nonce' ) ) );
    }

    public static function render_page() {
        include __DIR__ . '/views/settings-page.php';
    }

    public static function ajax_test_connection() {
        check_ajax_referer( 'wp_mcp_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'forbidden' );
        }

        $username = sanitize_text_field( $_POST['username'] ?? '' );
        $app_pass = sanitize_text_field( $_POST['app_password'] ?? '' );

        if ( empty( $username ) || empty( $app_pass ) ) {
            wp_send_json_error( 'missing' );
        }

        $site_url = get_option( 'wp_mcp_site_url', home_url() );
        $endpoint = rtrim( $site_url, '/' ) . '/wp-json/wp-mcp/v1/info';

        $credentials = base64_encode( $username . ':' . $app_pass );
        $response = wp_remote_get( $endpoint, array( 'headers' => array( 'Authorization' => 'Basic ' . $credentials ), 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        wp_send_json_success( array( 'code' => $code, 'body' => $body ) );
    }
}
