<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Create_Category {
    public static function call( $params, $user ) {
        if ( ! $user->has_cap( 'manage_categories' ) ) {
            return array( 'error' => 'insufficient_capability' );
        }

        $name = sanitize_text_field( $params['name'] ?? '' );
        if ( $name === '' ) {
            return array( 'error' => 'name required' );
        }

        $args = array();
        if ( ! empty( $params['slug'] ) ) {
            $args['slug'] = sanitize_title( $params['slug'] );
        }
        if ( isset( $params['parent_id'] ) ) {
            $args['parent'] = intval( $params['parent_id'] );
        }
        if ( ! empty( $params['description'] ) ) {
            $args['description'] = sanitize_textarea_field( $params['description'] );
        }

        $term = wp_insert_term( $name, 'category', $args );
        if ( is_wp_error( $term ) ) {
            return array( 'error' => $term->get_error_message() );
        }

        $term_id = intval( $term['term_id'] ?? 0 );

        return array(
            'id' => $term_id,
            'taxonomy' => 'category',
            'name' => $name,
            'url' => get_term_link( $term_id, 'category' ),
        );
    }
}
