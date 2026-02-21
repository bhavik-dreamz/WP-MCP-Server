<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_CPT;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_CPT
 */
class ToolCPTTest extends TestCase {

    public function test_missing_post_type_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_CPT::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'post_type required', $result['error'] );
    }

    public function test_disallowed_cpt_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_option' )->justReturn( array( 'book' ) );

        $result = Tool_CPT::call( array( 'post_type' => 'event' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'post_type not allowed', $result['error'] );
    }

    public function test_invalid_post_type_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_option' )->justReturn( array() ); // no restriction
        Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page' ) );

        $result = Tool_CPT::call( array( 'post_type' => 'nonexistent' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'invalid_post_type', $result['error'] );
    }

    public function test_valid_cpt_returns_results_array(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_option' )->justReturn( array() );
        Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page', 'book' ) );
        Functions\when( 'get_the_title' )->justReturn( 'My Book' );
        Functions\when( 'wp_trim_words' )->justReturn( '…' );
        Functions\when( 'get_permalink' )->justReturn( 'https://example.com/book/my-book/' );
        Functions\when( 'get_post_meta' )->justReturn( array() );

        // WP_Query is already stubbed by a previous test suite run; no extra setup needed.
        $result = Tool_CPT::call( array( 'post_type' => 'book', 'query' => '' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertArrayHasKey( 'total', $result );
    }

    public function test_meta_filters_are_applied(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_option' )->justReturn( array() );
        Functions\when( 'get_post_types' )->justReturn( array( 'book' ) );
        Functions\when( 'get_the_title' )->justReturn( 'Test' );
        Functions\when( 'wp_trim_words' )->justReturn( '' );
        Functions\when( 'get_permalink' )->justReturn( '' );
        Functions\when( 'get_post_meta' )->justReturn( array() );

        $meta_filters = array(
            array( 'key' => 'author', 'value' => 'Tolkien' ),
        );

        // Should not throw or return an error.
        $result = Tool_CPT::call(
            array( 'post_type' => 'book', 'meta_filters' => $meta_filters ),
            new \WP_User()
        );

        $this->assertArrayHasKey( 'results', $result );
    }
}
