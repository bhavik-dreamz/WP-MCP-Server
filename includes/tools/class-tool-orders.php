<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Orders {
    public static function call( $params, $user ) {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        $status = sanitize_text_field( $params['status'] ?? '' );
        $customer_id = isset( $params['customer_id'] ) ? intval( $params['customer_id'] ) : 0;
        $date_from = sanitize_text_field( $params['date_from'] ?? '' );
        $date_to = sanitize_text_field( $params['date_to'] ?? '' );
        $per_page = isset( $params['per_page'] ) ? intval( $params['per_page'] ) : 10;
        $page = isset( $params['page'] ) ? intval( $params['page'] ) : 1;

        $args = array(
            'limit' => $per_page,
            'page' => max( 1, $page ),
        );

        if ( $status ) {
            $args['status'] = $status;
        }
        if ( $customer_id ) {
            $args['customer_id'] = $customer_id;
        }
        if ( $date_from ) {
            $args['date_created'] = $args['date_created'] ?? array();
            $args['date_created']['after'] = $date_from . ' 00:00:00';
        }
        if ( $date_to ) {
            $args['date_created'] = $args['date_created'] ?? array();
            $args['date_created']['before'] = $date_to . ' 23:59:59';
        }

        $orders = wc_get_orders( $args );
        $results = array();
        foreach ( $orders as $o ) {
            $results[] = array(
                'id' => $o->get_id(),
                'status' => $o->get_status(),
                'total' => $o->get_total(),
                'currency' => $o->get_currency(),
                'customer_name' => $o->get_formatted_billing_full_name(),
                'customer_email' => $o->get_billing_email(),
                'items_count' => count( $o->get_items() ),
                'date_created' => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'c' ) : null,
                'date_modified' => $o->get_date_modified() ? $o->get_date_modified()->date_i18n( 'c' ) : null,
            );
        }

        return array( 'results' => $results );
    }
}
