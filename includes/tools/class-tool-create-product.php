<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Create_Product {
    public static function call( $params, $user ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        if ( ! $user->has_cap( 'manage_woocommerce' ) ) {
            return array( 'error' => 'insufficient_capability' );
        }

        $name = sanitize_text_field( $params['name'] ?? '' );
        if ( $name === '' ) {
            return array( 'error' => 'name required' );
        }

        $status = sanitize_text_field( $params['status'] ?? 'draft' );
        if ( ! in_array( $status, array( 'draft', 'publish', 'pending', 'private' ), true ) ) {
            $status = 'draft';
        }

        $product_id = wp_insert_post(
            array(
                'post_type' => 'product',
                'post_title' => $name,
                'post_content' => wp_kses_post( $params['description'] ?? '' ),
                'post_excerpt' => sanitize_textarea_field( $params['short_description'] ?? '' ),
                'post_status' => $status,
            ),
            true
        );

        if ( is_wp_error( $product_id ) ) {
            return array( 'error' => $product_id->get_error_message() );
        }

        if ( isset( $params['regular_price'] ) ) {
            $regular = wc_format_decimal( $params['regular_price'] );
            update_post_meta( $product_id, '_regular_price', $regular );
            update_post_meta( $product_id, '_price', $regular );
        }

        if ( isset( $params['sale_price'] ) ) {
            $sale = wc_format_decimal( $params['sale_price'] );
            update_post_meta( $product_id, '_sale_price', $sale );
            update_post_meta( $product_id, '_price', $sale );
        }

        if ( ! empty( $params['sku'] ) ) {
            update_post_meta( $product_id, '_sku', sanitize_text_field( $params['sku'] ) );
        }

        $manage_stock = isset( $params['manage_stock'] ) ? (bool) $params['manage_stock'] : false;
        update_post_meta( $product_id, '_manage_stock', $manage_stock ? 'yes' : 'no' );
        if ( $manage_stock ) {
            $qty = isset( $params['stock_quantity'] ) ? max( 0, intval( $params['stock_quantity'] ) ) : 0;
            update_post_meta( $product_id, '_stock', $qty );
            update_post_meta( $product_id, '_stock_status', $qty > 0 ? 'instock' : 'outofstock' );
        }

        if ( ! empty( $params['category_ids'] ) && is_array( $params['category_ids'] ) ) {
            $cat_ids = array_map( 'intval', $params['category_ids'] );
            wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );
        }

        return array(
            'id' => intval( $product_id ),
            'post_type' => 'product',
            'status' => $status,
            'url' => get_permalink( $product_id ),
        );
    }
}
