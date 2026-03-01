<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Create_Content;
use WP_MCP\Tests\TestCase;

class ToolCreateContentTest extends TestCase {

    public function test_returns_error_for_invalid_post_type(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'post_type_exists' )->justReturn( false );

        $result = Tool_Create_Content::call( array( 'post_type' => 'missing' ), new \WP_User() );

        $this->assertSame( 'invalid_post_type', $result['error'] );
    }

    public function test_returns_error_when_title_missing(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'post_type_exists' )->justReturn( true );
        Functions\when( 'get_post_type_object' )->justReturn( (object) array( 'cap' => (object) array( 'create_posts' => 'edit_posts' ) ) );

        $result = Tool_Create_Content::call( array( 'post_type' => 'post' ), new \WP_User() );

        $this->assertSame( 'title required', $result['error'] );
    }

    public function test_creates_post_successfully(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'sanitize_textarea_field' )->returnArg();
        Functions\when( 'wp_kses_post' )->returnArg();
        Functions\when( 'post_type_exists' )->justReturn( true );
        Functions\when( 'get_post_type_object' )->justReturn( (object) array( 'cap' => (object) array( 'create_posts' => 'edit_posts' ) ) );
        Functions\when( 'wp_insert_post' )->justReturn( 51 );
        Functions\when( 'get_permalink' )->justReturn( 'https://example.com/?p=51' );

        $result = Tool_Create_Content::call(
            array(
                'post_type' => 'post',
                'title' => 'My Post',
                'content' => 'Body',
            ),
            new \WP_User()
        );

        $this->assertSame( 51, $result['id'] );
        $this->assertSame( 'post', $result['post_type'] );
    }

    public function test_cpt_create_is_blocked_when_not_allowed(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'post_type_exists' )->justReturn( true );
        Functions\when( 'get_option' )->justReturn( array( 'book' ) );

        $result = Tool_Create_Content::call(
            array( 'post_type' => 'event', 'title' => 'Event A' ),
            new \WP_User()
        );

        $this->assertSame( 'post_type not allowed', $result['error'] );
    }
}
