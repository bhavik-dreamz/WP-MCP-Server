<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Create_User;
use WP_MCP\Tests\TestCase;

class ToolCreateUserTest extends TestCase {

    public function test_returns_error_without_capability(): void {
        $user = new \WP_User();
        $user->set_cap( 'create_users', false );

        $result = Tool_Create_User::call( array(), $user );

        $this->assertSame( 'insufficient_capability', $result['error'] );
    }

    public function test_requires_username_and_email(): void {
        Functions\when( 'sanitize_user' )->returnArg();
        Functions\when( 'sanitize_email' )->returnArg();
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_Create_User::call( array(), new \WP_User() );

        $this->assertSame( 'username required', $result['error'] );
    }

    public function test_returns_error_when_username_exists(): void {
        Functions\when( 'sanitize_user' )->returnArg();
        Functions\when( 'sanitize_email' )->returnArg();
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'username_exists' )->justReturn( 12 );

        $result = Tool_Create_User::call(
            array( 'username' => 'jane', 'email' => 'jane@example.com' ),
            new \WP_User()
        );

        $this->assertSame( 'username_exists', $result['error'] );
    }

    public function test_creates_user_successfully(): void {
        Functions\when( 'sanitize_user' )->returnArg();
        Functions\when( 'sanitize_email' )->returnArg();
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'username_exists' )->justReturn( false );
        Functions\when( 'email_exists' )->justReturn( false );
        Functions\when( 'wp_create_user' )->justReturn( 22 );
        Functions\when( 'wp_update_user' )->justReturn( 22 );
        Functions\when( 'get_userdata' )->justReturn(
            (object) array(
                'user_login' => 'jane',
                'user_email' => 'jane@example.com',
                'roles' => array( 'subscriber' ),
            )
        );

        $result = Tool_Create_User::call(
            array( 'username' => 'jane', 'email' => 'jane@example.com', 'password' => 'Pass#1234' ),
            new \WP_User()
        );

        $this->assertSame( 22, $result['id'] );
        $this->assertSame( 'jane', $result['username'] );
    }
}
