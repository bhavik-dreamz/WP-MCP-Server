<?php
namespace WP_MCP\Tests\Unit\Tools;

use WP_MCP\Tools\Tool_Products;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Products when WooCommerce is absent.
 *
 * This file intentionally does NOT define WC_Product_Query so that the
 * tool's guard clause (`class_exists('WC_Product_Query')`) returns false.
 * It must be discovered by PHPUnit before ToolProductsTest.php, which
 * defines the class.  Alphabetically "Missing" < "Test", so this is
 * guaranteed when PHPUnit scans the directory in sorted order.
 */
class ToolProductsMissingTest extends TestCase {

    public function test_returns_error_when_woocommerce_missing(): void {
        // WC_Product_Query is not yet defined when this test file is loaded.
        $result = Tool_Products::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'woocommerce_missing', $result['error'] );
    }
}
