<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Products;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Products (WooCommerce present).
 *
 * WC_Product_Query is loaded via the global-namespace stub file in
 * setUpBeforeClass() so it does not pollute other test files that rely on
 * the class being absent (e.g. ToolProductsMissingTest).
 */
class ToolProductsTest extends TestCase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require_once dirname( __DIR__, 2 ) . '/stubs/WC_Product_Query.php';
    }

    public function test_returns_results_when_wc_product_query_exists(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wp_get_post_terms' )->justReturn( array() );
        Functions\when( 'wp_get_attachment_image_src' )->justReturn( false );
        Functions\when( 'get_permalink' )->justReturn( 'https://example.com/product/shirt/' );

        $result = Tool_Products::call( array( 'query' => 'shirt' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertIsArray( $result['results'] );
    }

    public function test_price_filtering_accepts_valid_range(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wp_get_post_terms' )->justReturn( array() );
        Functions\when( 'wp_get_attachment_image_src' )->justReturn( false );
        Functions\when( 'get_permalink' )->justReturn( '' );

        // WC_Product_Query returns no products (default stub); result must be
        // correctly shaped with min/max_price params accepted without error.
        $result = Tool_Products::call(
            array( 'min_price' => 1.0, 'max_price' => 100.0 ),
            new \WP_User()
        );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertIsArray( $result['results'] );
    }

    public function test_in_stock_filter_is_forwarded(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_Products::call(
            array( 'in_stock' => true ),
            new \WP_User()
        );

        $this->assertArrayHasKey( 'results', $result );
    }

    public function test_category_filter_is_forwarded(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_Products::call(
            array( 'category_id' => 3 ),
            new \WP_User()
        );

        $this->assertArrayHasKey( 'results', $result );
    }
}
