<?php
namespace WP_MCP\Tests\Unit;

use Brain\Monkey\Functions;
use WP_MCP\Admin\Admin_Page;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Admin\Admin_Page helper methods.
 */
class AdminPageTest extends TestCase {

    // ------------------------------------------------------------------
    // sanitize_tools
    // ------------------------------------------------------------------

    public function test_sanitize_tools_returns_empty_array_for_non_array(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        $result = Admin_Page::sanitize_tools( 'not-an-array' );
        $this->assertSame( array(), $result );
    }

    public function test_sanitize_tools_sanitizes_each_element(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        $result = Admin_Page::sanitize_tools( array( 'search_posts', 'get_orders' ) );
        $this->assertSame( array( 'search_posts', 'get_orders' ), $result );
    }

    // ------------------------------------------------------------------
    // sanitize_allowed_cpts
    // ------------------------------------------------------------------

    public function test_sanitize_allowed_cpts_accepts_array(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        $result = Admin_Page::sanitize_allowed_cpts( array( 'book', 'event' ) );
        $this->assertSame( array( 'book', 'event' ), $result );
    }

    public function test_sanitize_allowed_cpts_splits_csv_string(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        $result = Admin_Page::sanitize_allowed_cpts( 'book, event , movie' );
        $this->assertSame( array( 'book', 'event', 'movie' ), array_values( $result ) );
    }

    public function test_sanitize_allowed_cpts_returns_empty_array_for_other_types(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        $result = Admin_Page::sanitize_allowed_cpts( 42 );
        $this->assertSame( array(), $result );
    }
}
