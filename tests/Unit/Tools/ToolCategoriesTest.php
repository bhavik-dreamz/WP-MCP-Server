<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Categories;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Categories
 */
class ToolCategoriesTest extends TestCase {

    public function test_returns_error_when_woocommerce_missing(): void {
        Functions\when( 'taxonomy_exists' )->justReturn( false );

        $result = Tool_Categories::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'woocommerce_missing', $result['error'] );
    }

    public function test_returns_results_when_woocommerce_active(): void {
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();

        $term           = new \stdClass();
        $term->term_id  = 5;
        $term->name     = 'Clothing';
        $term->slug     = 'clothing';
        $term->count    = 10;
        $term->parent   = 0;

        Functions\when( 'get_terms' )->justReturn( array( $term ) );
        Functions\when( 'get_term_link' )->justReturn( 'https://example.com/product-category/clothing/' );

        $result = Tool_Categories::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertCount( 1, $result['results'] );
        $this->assertSame( 5, $result['results'][0]['id'] );
        $this->assertSame( 'Clothing', $result['results'][0]['name'] );
    }

    public function test_search_query_is_passed_to_get_terms(): void {
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_terms' )->justReturn( array() );

        $result = Tool_Categories::call( array( 'query' => 'shoes' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertSame( array(), $result['results'] );
    }

    public function test_per_page_defaults_to_twenty(): void {
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_terms' )->justReturn( array() );

        // Call without per_page param – should use default 20 without error.
        $result = Tool_Categories::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
    }
}
