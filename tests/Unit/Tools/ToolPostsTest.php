<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Posts;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Posts
 */
class ToolPostsTest extends TestCase {

    // ------------------------------------------------------------------
    // Invalid post_type
    // ------------------------------------------------------------------

    public function test_invalid_post_type_returns_error(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page' ) );

        $result = Tool_Posts::call( array( 'post_type' => 'nonexistent' ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'invalid_post_type', $result['error'] );
    }

    // ------------------------------------------------------------------
    // Successful query
    // ------------------------------------------------------------------

    public function test_returns_results_and_total(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page' ) );
        Functions\when( 'get_the_title' )->justReturn( 'Sample Post' );
        Functions\when( 'wp_trim_words' )->justReturn( 'Excerpt…' );
        Functions\when( 'get_permalink' )->justReturn( 'https://example.com/sample-post/' );
        Functions\when( 'get_post_time' )->justReturn( '2024-01-01T00:00:00+00:00' );

        // WP_Query is stubbed in bootstrap.php with empty posts, so the result
        // set will be empty – the test verifies the return shape only.
        $result = Tool_Posts::call(
            array( 'query' => '', 'post_type' => 'post', 'per_page' => 10, 'page' => 1 ),
            new \WP_User()
        );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertArrayHasKey( 'total', $result );
        $this->assertIsArray( $result['results'] );
    }

    // ------------------------------------------------------------------
    // Default parameter handling
    // ------------------------------------------------------------------

    public function test_defaults_to_post_type_post(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'get_post_types' )->justReturn( array( 'post', 'page' ) );

        // WP_Query stub is already defined; just confirm no error is returned.
        $result = Tool_Posts::call( array(), new \WP_User() );

        $this->assertArrayNotHasKey( 'error', $result );
        $this->assertArrayHasKey( 'results', $result );
    }
}
