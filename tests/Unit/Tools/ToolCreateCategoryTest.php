<?php
namespace WP_MCP\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use WP_MCP\Tools\Tool_Create_Category;
use WP_MCP\Tests\TestCase;

class ToolCreateCategoryTest extends TestCase {

    public function test_returns_error_without_capability(): void {
        $user = new \WP_User();
        $user->set_cap( 'manage_categories', false );

        $result = Tool_Create_Category::call( array( 'name' => 'News' ), $user );

        $this->assertSame( 'insufficient_capability', $result['error'] );
    }

    public function test_returns_error_when_name_missing(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();

        $result = Tool_Create_Category::call( array(), new \WP_User() );

        $this->assertSame( 'name required', $result['error'] );
    }

    public function test_creates_category_successfully(): void {
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'sanitize_title' )->returnArg();
        Functions\when( 'sanitize_textarea_field' )->returnArg();
        Functions\when( 'wp_insert_term' )->justReturn( array( 'term_id' => 7, 'term_taxonomy_id' => 9 ) );
        Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/news/' );

        $result = Tool_Create_Category::call( array( 'name' => 'News' ), new \WP_User() );

        $this->assertSame( 7, $result['id'] );
        $this->assertSame( 'category', $result['taxonomy'] );
    }
}
