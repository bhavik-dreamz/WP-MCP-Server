<?php
namespace WP_MCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Auth {
    /**
     * Authenticate a REST request using Application Passwords.
     * Returns WP_User on success, WP_Error on failure.
     *
     * @param \WP_REST_Request $request
     * @return \WP_User|\WP_Error
     */
    public static function authenticate_request( \WP_REST_Request $request ) {
        $auth = $request->get_header( 'authorization' );
        if ( empty( $auth ) ) {
            return new \WP_Error( 'no_auth', __( 'Authorization header missing', 'wp-mcp-server' ), array( 'status' => 401 ) );
        }

        if ( stripos( $auth, 'basic ' ) === 0 ) {
            $b64 = substr( $auth, 6 );
            $decoded = base64_decode( $b64 );
            if ( empty( $decoded ) ) {
                return new \WP_Error( 'invalid_auth', __( 'Invalid Authorization header', 'wp-mcp-server' ), array( 'status' => 401 ) );
            }
            list( $username, $password ) = array_pad( explode( ':', $decoded, 2 ), 2, '' );
            if ( $username === '' || $password === '' ) {
                return new \WP_Error( 'invalid_auth', __( 'Invalid credentials', 'wp-mcp-server' ), array( 'status' => 401 ) );
            }

            if ( ! function_exists( 'wp_authenticate_application_password' ) ) {
                return new \WP_Error( 'no_app_passwords', __( 'Application Passwords not available on this site', 'wp-mcp-server' ), array( 'status' => 500 ) );
            }

            $user = wp_authenticate_application_password( null, $username, $password );
            if ( is_wp_error( $user ) ) {
                return $user;
            }

            if ( ! $user || ! $user->has_cap( 'read' ) ) {
                return new \WP_Error( 'forbidden', __( 'Insufficient capability', 'wp-mcp-server' ), array( 'status' => 403 ) );
            }

            return $user;
        }

        return new \WP_Error( 'invalid_auth_scheme', __( 'Unsupported authorization scheme', 'wp-mcp-server' ), array( 'status' => 401 ) );
    }
}
