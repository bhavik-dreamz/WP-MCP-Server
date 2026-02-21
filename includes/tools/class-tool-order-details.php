<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Order_Details {
    public static function call( $params, $user ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        $order_id = isset( $params['order_id'] ) ? intval( $params['order_id'] ) : 0;
        if ( ! $order_id ) {
            return array( 'error' => 'order_id required' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return array( 'error' => 'order_not_found' );
        }

        $items = array();
        foreach ( $order->get_items() as $item ) {
            $items[] = array(
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
            );
        }

        $notes = array();
        $raw_notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
        foreach ( $raw_notes as $n ) {
            $notes[] = array( 'date' => $n->date_created ? $n->date_created->date_i18n( 'c' ) : '', 'note' => $n->content );
        }

        $data = array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'billing' => $order->get_address( 'billing' ),
            'shipping' => $order->get_address( 'shipping' ),
            'line_items' => $items,
            'total' => $order->get_total(),
            'subtotal' => $order->get_subtotal(),
            'tax' => $order->get_total_tax(),
            'shipping_total' => $order->get_shipping_total(),
            'payment_method' => $order->get_payment_method(),
            'transaction_id' => $order->get_transaction_id(),
            'notes' => $notes,
            'date_created' => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'c' ) : null,
        );

        return $data;
    }
}
