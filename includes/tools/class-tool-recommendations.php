<?php
namespace WP_MCP\Tools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tool_Recommendations {
    public static function call( $params, $user ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return array( 'error' => 'woocommerce_missing' );
        }

        $product_id = isset( $params['product_id'] ) ? intval( $params['product_id'] ) : 0;
        $category_id = isset( $params['category_id'] ) ? intval( $params['category_id'] ) : 0;
        $limit = isset( $params['limit'] ) ? intval( $params['limit'] ) : 5;
        $strategy = sanitize_text_field( $params['strategy'] ?? 'related' );

        $results = array();

        switch ( $strategy ) {
            case 'related':
                if ( $product_id ) {
                    $related = wc_get_related_products( $product_id, $limit );
                    foreach ( $related as $rid ) {
                        $p = wc_get_product( $rid );
                        if ( $p ) {
                            $results[] = self::product_summary( $p );
                        }
                    }
                }
                break;
            case 'upsell':
                if ( $product_id ) {
                    $p = wc_get_product( $product_id );
                    if ( $p ) {
                        $ids = $p->get_upsell_ids();
                        $ids = array_slice( $ids, 0, $limit );
                        foreach ( $ids as $rid ) {
                            $pp = wc_get_product( $rid );
                            if ( $pp ) {
                                $results[] = self::product_summary( $pp );
                            }
                        }
                    }
                }
                break;
            case 'crosssell':
                if ( $product_id ) {
                    $p = wc_get_product( $product_id );
                    if ( $p ) {
                        $ids = $p->get_cross_sell_ids();
                        $ids = array_slice( $ids, 0, $limit );
                        foreach ( $ids as $rid ) {
                            $pp = wc_get_product( $rid );
                            if ( $pp ) {
                                $results[] = self::product_summary( $pp );
                            }
                        }
                    }
                }
                break;
            case 'bestseller':
                $q = new \WP_Query( array(
                    'post_type' => 'product',
                    'posts_per_page' => $limit,
                    'meta_key' => 'total_sales',
                    'orderby' => 'meta_value_num',
                ) );
                foreach ( $q->posts as $pp ) {
                    $p = wc_get_product( $pp->ID );
                    if ( $p ) {
                        $results[] = self::product_summary( $p );
                    }
                }
                break;
            case 'new_arrivals':
                $q = new \WP_Query( array(
                    'post_type' => 'product',
                    'posts_per_page' => $limit,
                    'orderby' => 'date',
                    'order' => 'DESC',
                ) );
                foreach ( $q->posts as $pp ) {
                    $p = wc_get_product( $pp->ID );
                    if ( $p ) {
                        $results[] = self::product_summary( $p );
                    }
                }
                break;
            default:
                return array( 'error' => 'unknown_strategy' );
        }

        return array( 'results' => array_slice( $results, 0, $limit ) );
    }

    private static function product_summary( $p ) {
        $image = wp_get_attachment_image_src( $p->get_image_id(), 'full' );
        return array(
            'id' => $p->get_id(),
            'name' => $p->get_name(),
            'price' => $p->get_price(),
            'url' => get_permalink( $p->get_id() ),
            'image_url' => $image ? $image[0] : '',
            'rating' => floatval( $p->get_average_rating() ),
            'sales_count' => intval( get_post_meta( $p->get_id(), 'total_sales', true ) ),
        );
    }
}
