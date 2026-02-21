<?php
namespace WP_MCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Logiscape\MCP\Server as MCPServer;
use Logiscape\MCP\Handlers\CallToolResult;
use Logiscape\MCP\Types\TextContent;

class MCP_Server {
    protected $server;
    protected $user;

    public function __construct( $wp_user ) {
        $this->user = $wp_user;
        // Instantiate SDK server if available, otherwise provide minimal fallback
        if ( class_exists( '\\Logiscape\\MCP\\Server' ) ) {
            $this->server = new MCPServer();
        } else {
            $this->server = null;
        }

        $this->register_handlers();
    }

    protected function register_handlers() {
        // If SDK available, use registerHandler calls; if not, we'll implement simple routing
        if ( $this->server ) {
            $this->server->registerHandler( 'tools/list', array( $this, 'handle_tools_list' ) );
            $this->server->registerHandler( 'tools/call', array( $this, 'handle_tools_call' ) );
        }
    }

    /**
     * Handle raw JSON-RPC payload and return JSON response. Uses SDK if present.
     */
    public function handle_raw( $raw ) {
        if ( $this->server ) {
            return $this->server->handle( $raw );
        }

        // Fallback: parse as JSON-RPC with method and params
        $data = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['method'] ) ) {
            throw new \Exception( 'Invalid JSON-RPC payload' );
        }

        switch ( $data['method'] ) {
            case 'tools/list':
                return $this->handle_tools_list( $data['params'] ?? array() );
            case 'tools/call':
                return $this->handle_tools_call( $data['params'] ?? array() );
            default:
                throw new \Exception( 'Method not found' );
        }
    }

    public function handle_tools_list( $params = array() ) {
        // Build tools list from enabled options
        $enabled = get_option( 'wp_mcp_enabled_tools', array() );
        $allowed_cpts = get_option( 'wp_mcp_allowed_cpts', array() );

        $all_tools = array(
            'search_posts' => array(
                'name' => 'search_posts',
                'inputSchema' => array(
                    'query' => 'string',
                    'post_type' => 'string',
                    'per_page' => 'integer',
                    'page' => 'integer',
                ),
            ),
            'search_pages' => array('name' => 'search_pages'),
            'search_custom_post_types' => array('name' => 'search_custom_post_types'),
            'search_products' => array('name' => 'search_products'),
            'search_product_categories' => array('name' => 'search_product_categories'),
            'get_orders' => array('name' => 'get_orders'),
            'get_order_details' => array('name' => 'get_order_details'),
            'recommend_products' => array('name' => 'recommend_products'),
        );

        $tools = array();
        foreach ( $all_tools as $key => $info ) {
            if ( empty( $enabled ) || in_array( $key, (array) $enabled, true ) ) {
                // WooCommerce gating
                if ( in_array( $key, array( 'search_products', 'search_product_categories', 'get_orders', 'get_order_details', 'recommend_products' ), true ) ) {
                    if ( ! class_exists( 'WooCommerce' ) ) {
                        continue;
                    }
                }
                $tools[] = $info;
            }
        }

        return array( 'tools' => $tools );
    }

    public function handle_tools_call( $params = array() ) {
        if ( empty( $params['tool'] ) ) {
            throw new \Exception( 'tool parameter required' );
        }
        $tool = sanitize_text_field( $params['tool'] );
        $args = $params['args'] ?? array();

        $mapping = array(
            'search_posts' => '\\WP_MCP\\Tools\\Tool_Posts',
            'search_pages' => '\\WP_MCP\\Tools\\Tool_Posts',
            'search_custom_post_types' => '\\WP_MCP\\Tools\\Tool_CPT',
            'search_products' => '\\WP_MCP\\Tools\\Tool_Products',
            'search_product_categories' => '\\WP_MCP\\Tools\\Tool_Categories',
            'get_orders' => '\\WP_MCP\\Tools\\Tool_Orders',
            'get_order_details' => '\\WP_MCP\\Tools\\Tool_Order_Details',
            'recommend_products' => '\\WP_MCP\\Tools\\Tool_Recommendations',
        );

        if ( ! isset( $mapping[ $tool ] ) ) {
            throw new \Exception( 'Unknown tool' );
        }

        $class = $mapping[ $tool ];
        if ( ! class_exists( $class ) ) {
            throw new \Exception( 'Tool class not found: ' . $class );
        }

        if ( ! is_callable( array( $class, 'call' ) ) ) {
            throw new \Exception( 'Tool does not implement call()' );
        }

        // Call the tool with sanitized args and current user
        return call_user_func( array( $class, 'call' ), $args, $this->user );
    }
}
