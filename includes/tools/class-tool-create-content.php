<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Create_Content {
    public static function call( $params, $user ) {
        $post_type = sanitize_text_field( $params['post_type'] ?? 'post' );

        if ( ! post_type_exists( $post_type ) ) {
            return array( 'error' => 'invalid_post_type' );
        }

        if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
            $allowed = get_option( 'wp_mcp_allowed_cpts', array() );
            if ( ! empty( $allowed ) && ! in_array( $post_type, (array) $allowed, true ) ) {
                return array( 'error' => 'post_type not allowed' );
            }
        }

        $type_obj = get_post_type_object( $post_type );
        $capability = 'edit_posts';
        if ( $type_obj && isset( $type_obj->cap->create_posts ) ) {
            $capability = $type_obj->cap->create_posts;
        } elseif ( $post_type === 'page' ) {
            $capability = 'edit_pages';
        }

        if ( ! $user->has_cap( $capability ) ) {
            return array( 'error' => 'insufficient_capability' );
        }

        $title = sanitize_text_field( $params['title'] ?? '' );
        if ( $title === '' ) {
            return array( 'error' => 'title required' );
        }

        $content = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';
        $excerpt = isset( $params['excerpt'] ) ? sanitize_textarea_field( $params['excerpt'] ) : '';

        $status = sanitize_text_field( $params['status'] ?? 'draft' );
        if ( ! in_array( $status, array( 'draft', 'publish', 'pending', 'private' ), true ) ) {
            $status = 'draft';
        }

        $postarr = array(
            'post_type' => $post_type,
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => $status,
        );

        if ( ! empty( $params['slug'] ) ) {
            $postarr['post_name'] = sanitize_title( $params['slug'] );
        }

        if ( isset( $params['author_id'] ) ) {
            $postarr['post_author'] = intval( $params['author_id'] );
        }

        $post_id = wp_insert_post( $postarr, true );
        if ( is_wp_error( $post_id ) ) {
            return array( 'error' => $post_id->get_error_message() );
        }

        return array(
            'id' => intval( $post_id ),
            'post_type' => $post_type,
            'status' => $status,
            'url' => get_permalink( $post_id ),
        );
    }
}
