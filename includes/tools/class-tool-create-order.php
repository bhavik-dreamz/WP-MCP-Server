<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Create_Order {
    public static function call( $params, $user ) {
        if ( ! function_exists( 'wc_create_order' ) || ! function_exists( 'wc_get_product' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        if ( ! $user->has_cap( 'manage_woocommerce' ) ) {
            return array( 'error' => 'insufficient_capability' );
        }

        $line_items = $params['line_items'] ?? array();
        if ( ! is_array( $line_items ) || empty( $line_items ) ) {
            return array( 'error' => 'line_items required' );
        }

        $customer_id = isset( $params['customer_id'] ) ? intval( $params['customer_id'] ) : 0;
        $order = wc_create_order( array( 'customer_id' => $customer_id ) );

        if ( is_wp_error( $order ) || ! $order ) {
            return array( 'error' => 'order_create_failed' );
        }

        foreach ( $line_items as $item ) {
            $product_id = isset( $item['product_id'] ) ? intval( $item['product_id'] ) : 0;
            $quantity = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;

            if ( ! $product_id ) {
                continue;
            }

            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $order->add_product( $product, $quantity );
        }

        if ( ! empty( $params['billing'] ) && is_array( $params['billing'] ) ) {
            $order->set_address( $params['billing'], 'billing' );
        }

        if ( ! empty( $params['shipping'] ) && is_array( $params['shipping'] ) ) {
            $order->set_address( $params['shipping'], 'shipping' );
        }

        if ( ! empty( $params['status'] ) ) {
            $order->update_status( sanitize_text_field( $params['status'] ) );
        }

        $order->calculate_totals();
        $order->save();

        return array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
        );
    }
}
