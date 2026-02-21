<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Orders;
use WP_MCP\Tests\TestCase;

/**
 * Tests for WP_MCP\Tools\Tool_Orders
 */
class ToolOrdersTest extends TestCase {

    public function test_returns_error_when_woocommerce_missing(): void {
        // wc_get_orders() does not exist in the test environment.
        $result = Tool_Orders::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'woocommerce_missing', $result['error'] );
    }

    public function test_returns_error_when_user_lacks_capability(): void {
        Functions\when( 'wc_get_orders' )->justReturn( array() );

        $user = new \WP_User();
        $user->set_cap( 'manage_woocommerce', false );

        $result = Tool_Orders::call( array(), $user );

        $this->assertArrayHasKey( 'error', $result );
        $this->assertSame( 'insufficient_capability', $result['error'] );
    }

    public function test_returns_results_when_woocommerce_present(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $date = new class {
            public function date_i18n( $fmt ) { return '2024-01-01T00:00:00+00:00'; }
        };

        $order = new class( $date ) {
            private $date;
            public function __construct( $d ) { $this->date = $d; }
            public function get_id(): int              { return 100; }
            public function get_status(): string       { return 'processing'; }
            public function get_total(): string        { return '29.99'; }
            public function get_currency(): string     { return 'USD'; }
            public function get_formatted_billing_full_name(): string { return 'Jane Doe'; }
            public function get_billing_email(): string{ return 'jane@example.com'; }
            public function get_items(): array         { return array( 'item1' ); }
            public function get_date_created()         { return $this->date; }
            public function get_date_modified()        { return $this->date; }
        };

        Functions\when( 'wc_get_orders' )->justReturn( array( $order ) );

        $result = Tool_Orders::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertCount( 1, $result['results'] );
        $this->assertSame( 100, $result['results'][0]['id'] );
        $this->assertSame( 'processing', $result['results'][0]['status'] );
    }

    public function test_status_filter_is_forwarded(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wc_get_orders' )->justReturn( array() );

        $result = Tool_Orders::call( array( 'status' => 'completed' ), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
        $this->assertSame( array(), $result['results'] );
    }

    public function test_per_page_and_page_defaults(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wc_get_orders' )->justReturn( array() );

        // Should not throw even without explicit pagination params.
        $result = Tool_Orders::call( array(), new \WP_User() );

        $this->assertArrayHasKey( 'results', $result );
    }
}
