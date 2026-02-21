<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Order_Details;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Order_Details
 */
class ToolOrderDetailsTest extends TestCase {

    public function test_returns_error_when_woocommerce_missing(): void {
        $result = Tool_Order_Details::call( array( 'order_id' => 1 ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'woocommerce_missing', $result['error'] );
    }

    public function test_returns_error_when_order_id_missing(): void {
        Functions\when( 'wc_get_order' )->justReturn( null );

        $result = Tool_Order_Details::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'order_id required', $result['error'] );
    }

    public function test_returns_error_when_order_not_found(): void {
        Functions\when( 'wc_get_order' )->justReturn( false );

        $result = Tool_Order_Details::call( array( 'order_id' => 9999 ), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'order_not_found', $result['error'] );
    }

    public function test_returns_order_detail_fields(): void {
        $date = new class {
            public function date_i18n( $fmt ) { return '2024-06-01T10:00:00+00:00'; }
        };

        $item = new class {
            public function get_product_id(): int { return 7; }
            public function get_name(): string    { return 'T-Shirt'; }
            public function get_quantity(): int   { return 2; }
            public function get_total(): string   { return '19.98'; }
        };

        $order = new class( $date, $item ) {
            private $date;
            private $item;
            public function __construct( $d, $i ) { $this->date = $d; $this->item = $i; }
            public function get_id(): int                { return 55; }
            public function get_status(): string         { return 'completed'; }
            public function get_address( $type ): array  { return array( 'first_name' => 'John' ); }
            public function get_items(): array           { return array( $this->item ); }
            public function get_total(): string          { return '19.98'; }
            public function get_subtotal(): string       { return '17.99'; }
            public function get_total_tax(): string      { return '1.99'; }
            public function get_shipping_total(): string { return '0.00'; }
            public function get_payment_method(): string { return 'stripe'; }
            public function get_transaction_id(): string { return 'txn_123'; }
            public function get_date_created()           { return $this->date; }
        };

        Functions\when( 'wc_get_order' )->justReturn( $order );
        Functions\when( 'wc_get_order_notes' )->justReturn( array() );

        $result = Tool_Order_Details::call( array( 'order_id' => 55 ), new \WP_User() );

        $this->assertSame( 55, $result['id'] );
        $this->assertSame( 'completed', $result['status'] );
        $this->assertArrayHasKey( 'line_items', $result );
        $this->assertCount( 1, $result['line_items'] );
        $this->assertSame( 'T-Shirt', $result['line_items'][0]['name'] );
        $this->assertSame( 'txn_123', $result['transaction_id'] );
    }
}
