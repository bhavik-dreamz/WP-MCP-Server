<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Taxonomies;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Taxonomies
 */
class ToolTaxonomiesTest extends TestCase {

    public function test_invalid_taxonomy_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_Taxonomies::call( array( 'taxonomy' => 'product_cat' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'invalid_taxonomy', $result['error'] );
    }

    public function test_missing_taxonomy_returns_error_when_not_registered(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'taxonomy_exists' )->justReturn( false );

        $result = Tool_Taxonomies::call( array( 'taxonomy' => 'category' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'taxonomy_not_found', $result['error'] );
    }

    public function test_returns_results_for_categories(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'is_taxonomy_hierarchical' )->justReturn( true );

        $term = new \stdClass();
        $term->term_id = 3;
        $term->name = 'News';
        $term->slug = 'news';
        $term->count = 8;
        $term->parent = 0;

        Functions\when( 'get_terms' )->justReturn( array( $term ) );
        Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/news/' );

        $result = Tool_Taxonomies::call( array( 'taxonomy' => 'category', 'query' => 'new' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertCount( 1, $result['results'] );
        $this->assertSame( 'category', $result['results'][0]['taxonomy'] );
        $this->assertSame( 'News', $result['results'][0]['name'] );
    }

    public function test_returns_results_for_tags(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'is_taxonomy_hierarchical' )->justReturn( false );

        $term = new \stdClass();
        $term->term_id = 9;
        $term->name = 'Featured';
        $term->slug = 'featured';
        $term->count = 4;
        $term->parent = 0;

        Functions\when( 'get_terms' )->justReturn( array( $term ) );
        Functions\when( 'get_term_link' )->justReturn( 'https://example.com/tag/featured/' );

        $result = Tool_Taxonomies::call( array( 'taxonomy' => 'post_tag' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertCount( 1, $result['results'] );
        $this->assertSame( 'post_tag', $result['results'][0]['taxonomy'] );
        $this->assertSame( 'Featured', $result['results'][0]['name'] );
    }

    public function test_wp_error_from_get_terms_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'taxonomy_exists' )->justReturn( true );
        Functions\when( 'is_taxonomy_hierarchical' )->justReturn( true );
        Functions\when( 'get_terms' )->justReturn( new \WP_Error( 'term_error', 'Failed' ) );

        $result = Tool_Taxonomies::call( array( 'taxonomy' => 'category' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'taxonomy_query_failed', $result['error'] );
    }
}
