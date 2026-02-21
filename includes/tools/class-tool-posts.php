<?php
namespace WP_MCP\Tools;

use WP_MCP\Core\Auth;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Posts {
    /**
     * Search posts or pages
     * Params: query, post_type (default post), per_page, page
     */
    public static function call( $params, $user ) {
        $query = sanitize_text_field( $params['query'] ?? '' );
        $post_type = sanitize_text_field( $params['post_type'] ?? 'post' );
        $per_page = isset( $params['per_page'] ) ? intval( $params['per_page'] ) : 10;
        $page = isset( $params['page'] ) ? intval( $params['page'] ) : 1;

        // Validate post_type
        $available = get_post_types( array( 'public' => true ) );
        if ( ! in_array( $post_type, $available, true ) ) {
            return array( 'error' => 'invalid_post_type' );
        }

        $args = array(
            's' => $query,
            'post_type' => $post_type,
            'posts_per_page' => $per_page,
            'paged' => max( 1, $page ),
            'post_status' => 'publish',
        );

        $wpq = new \WP_Query( $args );
        $results = array();
        foreach ( $wpq->posts as $p ) {
            $results[] = array(
                'id' => $p->ID,
                'title' => get_the_title( $p ),
                'excerpt' => wp_trim_words( $p->post_excerpt ?: $p->post_content, 55 ),
                'url' => get_permalink( $p ),
                'date' => get_post_time( 'c', true, $p ),
                'status' => $p->post_status,
            );
        }

        return array( 'results' => $results, 'total' => (int) $wpq->found_posts );
    }
}
