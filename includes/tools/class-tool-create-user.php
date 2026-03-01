<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Create_User {
    public static function call( $params, $user ) {
        if ( ! $user->has_cap( 'create_users' ) ) {
            return array( 'error' => 'insufficient_capability' );
        }

        $username = sanitize_user( $params['username'] ?? '' );
        $email = sanitize_email( $params['email'] ?? '' );
        $password = isset( $params['password'] ) ? (string) $params['password'] : wp_generate_password( 20, true, true );
        $role = sanitize_text_field( $params['role'] ?? 'subscriber' );

        if ( $username === '' ) {
            return array( 'error' => 'username required' );
        }
        if ( $email === '' ) {
            return array( 'error' => 'email required' );
        }

        if ( username_exists( $username ) ) {
            return array( 'error' => 'username_exists' );
        }
        if ( email_exists( $email ) ) {
            return array( 'error' => 'email_exists' );
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            return array( 'error' => $user_id->get_error_message() );
        }

        wp_update_user( array(
            'ID' => intval( $user_id ),
            'role' => $role,
            'display_name' => sanitize_text_field( $params['display_name'] ?? $username ),
        ) );

        $created = get_userdata( $user_id );

        return array(
            'id' => intval( $user_id ),
            'username' => $created ? $created->user_login : $username,
            'email' => $created ? $created->user_email : $email,
            'roles' => $created ? $created->roles : array( $role ),
        );
    }
}
