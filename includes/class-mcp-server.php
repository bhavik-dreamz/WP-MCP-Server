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
                'description' => 'Search WordPress posts by keyword with optional pagination.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'query'     => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'post_type' => array( 'type' => 'string',  'description' => 'Post type (default: post)' ),
                        'per_page'  => array( 'type' => 'integer', 'description' => 'Results per page (default: 10)' ),
                        'page'      => array( 'type' => 'integer', 'description' => 'Page number (default: 1)' ),
                    ),
                ),
            ),
            'search_pages' => array(
                'name' => 'search_pages',
                'description' => 'Search WordPress pages by keyword.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'query'    => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'per_page' => array( 'type' => 'integer', 'description' => 'Results per page (default: 10)' ),
                        'page'     => array( 'type' => 'integer', 'description' => 'Page number (default: 1)' ),
                    ),
                ),
            ),
            'search_custom_post_types' => array(
                'name' => 'search_custom_post_types',
                'description' => 'Search allowed custom post types with optional meta filters.',
                'inputSchema' => array(
                    'type' => 'object',
                    'required' => array( 'post_type' ),
                    'properties' => array(
                        'post_type'    => array( 'type' => 'string',  'description' => 'Custom post type slug (required)' ),
                        'query'        => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'meta_filters' => array( 'type' => 'array',   'description' => 'Array of {key, value} meta filter objects' ),
                        'per_page'     => array( 'type' => 'integer', 'description' => 'Results per page (default: 10)' ),
                        'page'         => array( 'type' => 'integer', 'description' => 'Page number (default: 1)' ),
                    ),
                ),
            ),
            'search_products' => array(
                'name' => 'search_products',
                'description' => 'Search WooCommerce products with optional price and stock filters.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'query'       => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'category_id' => array( 'type' => 'integer', 'description' => 'Filter by product category ID' ),
                        'min_price'   => array( 'type' => 'number',  'description' => 'Minimum price filter' ),
                        'max_price'   => array( 'type' => 'number',  'description' => 'Maximum price filter' ),
                        'in_stock'    => array( 'type' => 'boolean', 'description' => 'Only return in-stock products' ),
                        'per_page'    => array( 'type' => 'integer', 'description' => 'Results per page (default: 10)' ),
                        'page'        => array( 'type' => 'integer', 'description' => 'Page number (default: 1)' ),
                    ),
                ),
            ),
            'search_product_categories' => array(
                'name' => 'search_product_categories',
                'description' => 'List or search WooCommerce product categories.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'query'     => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'parent_id' => array( 'type' => 'integer', 'description' => 'Filter by parent category ID' ),
                        'per_page'  => array( 'type' => 'integer', 'description' => 'Results per page (default: 20)' ),
                    ),
                ),
            ),
            'get_orders' => array(
                'name' => 'get_orders',
                'description' => 'List WooCommerce orders with optional status, customer and date filters.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'status'      => array( 'type' => 'string',  'description' => 'Order status (e.g. processing, completed)' ),
                        'customer_id' => array( 'type' => 'integer', 'description' => 'Filter by customer user ID' ),
                        'date_from'   => array( 'type' => 'string',  'description' => 'Start date (YYYY-MM-DD)' ),
                        'date_to'     => array( 'type' => 'string',  'description' => 'End date (YYYY-MM-DD)' ),
                        'per_page'    => array( 'type' => 'integer', 'description' => 'Results per page (default: 10)' ),
                        'page'        => array( 'type' => 'integer', 'description' => 'Page number (default: 1)' ),
                    ),
                ),
            ),
            'get_order_details' => array(
                'name' => 'get_order_details',
                'description' => 'Get full details (line items, billing, shipping, notes) of a WooCommerce order.',
                'inputSchema' => array(
                    'type' => 'object',
                    'required' => array( 'order_id' ),
                    'properties' => array(
                        'order_id' => array( 'type' => 'integer', 'description' => 'WooCommerce order ID (required)' ),
                    ),
                ),
            ),
            'recommend_products' => array(
                'name' => 'recommend_products',
                'description' => 'Recommend WooCommerce products using related, upsell, crosssell, bestseller, or new_arrivals strategies.',
                'inputSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'strategy'    => array( 'type' => 'string',  'description' => 'Recommendation strategy: related, upsell, crosssell, bestseller, new_arrivals (default: related)' ),
                        'product_id'  => array( 'type' => 'integer', 'description' => 'Source product ID (required for related, upsell, crosssell)' ),
                        'category_id' => array( 'type' => 'integer', 'description' => 'Filter by category ID' ),
                        'limit'       => array( 'type' => 'integer', 'description' => 'Maximum number of results (default: 5)' ),
                    ),
                ),
            ),
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
        if ( empty( $params['name'] ) ) {
            throw new \Exception( 'name parameter required' );
        }
        $tool = sanitize_text_field( $params['name'] );
        $args = $params['arguments'] ?? array();

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
