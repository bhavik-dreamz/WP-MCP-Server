<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Products {
    public static function call( $params, $user ) {
        if ( ! class_exists( 'WC_Product_Query' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        $query = sanitize_text_field( $params['query'] ?? '' );
        $category_id = isset( $params['category_id'] ) ? intval( $params['category_id'] ) : 0;
        $min_price = isset( $params['min_price'] ) ? floatval( $params['min_price'] ) : null;
        $max_price = isset( $params['max_price'] ) ? floatval( $params['max_price'] ) : null;
        $in_stock = isset( $params['in_stock'] ) ? boolval( $params['in_stock'] ) : null;
        $per_page = isset( $params['per_page'] ) ? intval( $params['per_page'] ) : 10;
        $page = isset( $params['page'] ) ? intval( $params['page'] ) : 1;

        $args = array(
            'limit' => $per_page,
            'page' => max( 1, $page ),
            'status' => 'publish',
        );

        if ( $query !== '' ) {
            $args['search'] = $query;
        }

        if ( $category_id ) {
            $args['category'] = array( intval( $category_id ) );
        }

        if ( $in_stock !== null ) {
            $args['stock_status'] = $in_stock ? 'instock' : 'outofstock';
        }

        // Use WC_Product_Query
        $pq = new \WC_Product_Query( $args );
        $products = $pq->get_products();

        // Filter by price if needed
        $results = array();
        foreach ( $products as $p ) {
            $price = floatval( $p->get_price() );
            if ( $min_price !== null && $price < $min_price ) {
                continue;
            }
            if ( $max_price !== null && $price > $max_price ) {
                continue;
            }

            $cats = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'all' ) );
            $cat_list = array();
            foreach ( $cats as $c ) {
                $cat_list[] = array( 'id' => $c->term_id, 'name' => $c->name );
            }

            $image = wp_get_attachment_image_src( $p->get_image_id(), 'full' );

            $results[] = array(
                'id' => $p->get_id(),
                'name' => $p->get_name(),
                'price' => $p->get_price(),
                'regular_price' => $p->get_regular_price(),
                'sale_price' => $p->get_sale_price(),
                'stock_status' => $p->get_stock_status(),
                'categories' => $cat_list,
                'url' => get_permalink( $p->get_id() ),
                'image_url' => $image ? $image[0] : '',
            );
        }

        return array( 'results' => $results );
    }
}
