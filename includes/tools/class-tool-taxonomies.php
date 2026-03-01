<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Taxonomies {
    public static function call( $params, $user ) {
        $taxonomy = sanitize_text_field( $params['taxonomy'] ?? 'category' );
        $query = sanitize_text_field( $params['query'] ?? '' );
        $parent_id = isset( $params['parent_id'] ) ? intval( $params['parent_id'] ) : 0;
        $per_page = isset( $params['per_page'] ) ? intval( $params['per_page'] ) : 20;

        if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
            return array( 'error' => 'invalid_taxonomy' );
        }

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array( 'error' => 'taxonomy_not_found' );
        }

        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => max( 1, $per_page ),
        );

        if ( $query !== '' ) {
            $args['search'] = $query;
        }

        if ( $parent_id && is_taxonomy_hierarchical( $taxonomy ) ) {
            $args['parent'] = $parent_id;
        }

        $terms = get_terms( $args );
        if ( is_wp_error( $terms ) ) {
            return array( 'error' => 'taxonomy_query_failed' );
        }

        $results = array();
        foreach ( $terms as $t ) {
            $results[] = array(
                'id' => $t->term_id,
                'taxonomy' => $taxonomy,
                'name' => $t->name,
                'slug' => $t->slug,
                'count' => $t->count,
                'url' => get_term_link( $t ),
                'parent_id' => $t->parent,
            );
        }

        return array( 'results' => $results );
    }
}
