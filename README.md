# WP MCP Server

Exposes a WordPress site as an MCP (Model Context Protocol) server so MCP clients like Claude Desktop can call tools over HTTP (JSON-RPC). This plugin implements REST endpoints and a set of tools for WordPress and WooCommerce data access.

## Features
- Exposes `/wp-json/wp-mcp/v1/info` (public) and `/wp-json/wp-mcp/v1/mcp` (JSON-RPC entrypoint)
- Authentication via WordPress Application Passwords (Basic auth header)
- Tools: search_posts, search_pages, search_post_categories, search_tags, create_post, create_page, create_category, search_custom_post_types, create_custom_post_type, search_products, create_product, search_product_categories, create_order, get_orders, get_order_details, create_user, recommend_products
- Admin settings page to configure site URL, enabled tools, CPT whitelist, and test credentials
- WooCommerce-aware: product/order tools only active when WooCommerce is present

## Installation

1. Copy the `wp-mcp-server` folder into your WordPress `wp-content/plugins/` directory (or use this repo as the plugin folder).
2. From the plugin folder, install Composer dependencies:

```powershell
cd e:\path\to\wp-mcp-server
composer install
```

3. Activate the plugin in WordPress (Plugins → Installed Plugins).
4. Go to Settings → WP MCP Server to configure the Site URL, enable tools, and whitelist CPTs.
5. Create an Application Password for a user (Users → Profile → Application Passwords) and use those credentials for MCP clients.

## Endpoints

- GET {site_url}/wp-json/wp-mcp/v1/info
  - Public: returns plugin version, site name, available tools.

- POST {site_url}/wp-json/wp-mcp/v1/mcp
  - Protected: requires header `Authorization: Basic base64(username:application_password)`
  - Accepts JSON-RPC 2.0 requests. Supported methods: `tools/list` and `tools/call`.

Example JSON-RPC request (tools/list):

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list",
  "params": {}
}
```

Example JSON-RPC request (call a tool):

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "search_posts",
    "arguments": { "query": "hello", "per_page": 5 }
  }
}
```

## MCP Tool Reference

Use `tools/list` to discover currently available tools for your site/user context. The list depends on:
- which tools are enabled in Settings → WP MCP Server
- whether WooCommerce is active
- current user capability (for order tools)

### Content tools (WordPress core)

These tools let MCP clients work with WordPress content data (posts, pages, and custom post types), not only WooCommerce.

1. `search_posts`
   - Purpose: Search public posts (or another valid post type).
   - Arguments:
     - `query` (string, optional)
     - `post_type` (string, optional, default `post`)
     - `per_page` (integer, optional, default `10`)
     - `page` (integer, optional, default `1`)
   - Returns: `{ "results": [...], "total": <number> }`

2. `search_pages`
   - Purpose: Search WordPress pages.
   - Arguments:
     - `query` (string, optional)
     - `per_page` (integer, optional, default `10`)
     - `page` (integer, optional, default `1`)
   - Returns: same structure as `search_posts`.

3. `search_custom_post_types`
   - Purpose: Search a custom post type that is whitelisted in plugin settings.
   - Arguments:
     - `post_type` (string, required)
     - `query` (string, optional)
     - `meta_filters` (array, optional) e.g. `[{"key":"city","value":"London"}]`
     - `per_page` (integer, optional, default `10`)
     - `page` (integer, optional, default `1`)
   - Returns: `{ "results": [...], "total": <number> }`

4. `search_post_categories`
   - Purpose: Search WordPress blog categories.
   - Arguments:
     - `query` (string, optional)
     - `parent_id` (integer, optional)
     - `per_page` (integer, optional, default `20`)
   - Returns: `{ "results": [...] }`

5. `search_tags`
   - Purpose: Search WordPress blog tags.
   - Arguments:
     - `query` (string, optional)
     - `per_page` (integer, optional, default `20`)
   - Returns: `{ "results": [...] }`

6. `create_post`
  - Purpose: Create a new WordPress post.
  - Arguments:
    - `title` (string, required)
    - `content` (string, optional)
    - `excerpt` (string, optional)
    - `status` (string, optional, default `draft`)
    - `slug` (string, optional)
    - `author_id` (integer, optional)
  - Returns: created post object with `id`, `post_type`, `status`, `url`.

7. `create_page`
  - Purpose: Create a new WordPress page.
  - Arguments: same as `create_post`.
  - Returns: created page object.

8. `create_category`
  - Purpose: Create a WordPress blog category.
  - Arguments:
    - `name` (string, required)
    - `slug` (string, optional)
    - `parent_id` (integer, optional)
    - `description` (string, optional)
  - Returns: created category object with `id`, `taxonomy`, `name`, `url`.

### Category and order tools

- WordPress category and tag tools are available as `search_post_categories` and `search_tags`.
- WooCommerce product category search is available as `search_product_categories`.
- Order details are available via `get_order_details` (WooCommerce + proper capability required).

### WooCommerce tools (only when WooCommerce is installed)

9. `search_products`
   - Purpose: Search products with optional category, stock, and price filtering.
   - Arguments:
     - `query` (string, optional)
     - `category_id` (integer, optional)
     - `min_price` (number, optional)
     - `max_price` (number, optional)
     - `in_stock` (boolean, optional)
     - `per_page` (integer, optional, default `10`)
     - `page` (integer, optional, default `1`)
   - Returns: `{ "results": [...] }`

10. `create_custom_post_type`
   - Purpose: Create a CPT item (CPT must be allowed in plugin settings).
   - Arguments:
     - `post_type` (string, required)
     - `title` (string, required)
     - `content` (string, optional)
     - `excerpt` (string, optional)
     - `status` (string, optional, default `draft`)
     - `slug` (string, optional)
     - `author_id` (integer, optional)
   - Returns: created item object with `id`, `post_type`, `status`, `url`.

11. `search_product_categories`
   - Purpose: Search/list product categories.
   - Arguments:
     - `query` (string, optional)
     - `parent_id` (integer, optional)
     - `per_page` (integer, optional, default `20`)
   - Returns: `{ "results": [...] }`

12. `create_product`
   - Purpose: Create a WooCommerce product.
   - Access: requires a user with `manage_woocommerce` capability.
   - Arguments:
     - `name` (string, required)
     - `description` (string, optional)
     - `short_description` (string, optional)
     - `status` (string, optional, default `draft`)
     - `regular_price` (number, optional)
     - `sale_price` (number, optional)
     - `sku` (string, optional)
     - `manage_stock` (boolean, optional)
     - `stock_quantity` (integer, optional)
     - `category_ids` (array, optional)
   - Returns: created product object.

13. `create_order`
   - Purpose: Create a WooCommerce order with line items.
   - Access: requires a user with `manage_woocommerce` capability.
   - Arguments:
     - `line_items` (array, required) e.g. `[{"product_id":123,"quantity":2}]`
     - `customer_id` (integer, optional)
     - `billing` (object, optional)
     - `shipping` (object, optional)
     - `status` (string, optional)
   - Returns: created order summary (`id`, `status`, `total`, `currency`).

14. `get_orders`
   - Purpose: List orders with filters.
   - Access: requires a user with `manage_woocommerce` capability.
   - Arguments:
     - `status` (string, optional)
     - `customer_id` (integer, optional)
     - `date_from` (string, optional, `YYYY-MM-DD`)
     - `date_to` (string, optional, `YYYY-MM-DD`)
     - `per_page` (integer, optional, default `10`)
     - `page` (integer, optional, default `1`)
   - Returns: `{ "results": [...] }`

15. `get_order_details`
   - Purpose: Fetch full order details (billing/shipping, line items, totals, notes).
   - Access: requires a user with `manage_woocommerce` capability.
   - Arguments:
     - `order_id` (integer, required)
   - Returns: order object (not wrapped in `results`).

16. `create_user`
   - Purpose: Create a WordPress user.
   - Access: requires a user with `create_users` capability.
   - Arguments:
     - `username` (string, required)
     - `email` (string, required)
     - `password` (string, optional)
     - `display_name` (string, optional)
     - `role` (string, optional, default `subscriber`)
   - Returns: created user object.

17. `recommend_products`
   - Purpose: Return product recommendations.
   - Arguments:
     - `strategy` (string, optional): `related`, `upsell`, `crosssell`, `bestseller`, `new_arrivals`
     - `product_id` (integer, required for `related`, `upsell`, `crosssell`)
     - `category_id` (integer, optional)
     - `limit` (integer, optional, default `5`)
   - Returns: `{ "results": [...] }`

## How to use tools (copy/paste flow)

1. Call `tools/list`

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list",
  "params": {}
}
```

2. Pick a tool name from `tools[].name`

3. Call `tools/call` with `params.name` and `params.arguments`

Example: search products in-stock under price 50

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "search_products",
    "arguments": {
      "query": "shirt",
      "in_stock": true,
      "max_price": 50,
      "per_page": 5
    }
  }
}
```

Example: get a single order detail

```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "get_order_details",
    "arguments": {
      "order_id": 1234
    }
  }
}
```

## More examples (post + page + CPT + categories/tags + order)

Example: search blog posts

```json
{
  "jsonrpc": "2.0",
  "id": 10,
  "method": "tools/call",
  "params": {
    "name": "search_posts",
    "arguments": {
      "query": "shipping policy",
      "post_type": "post",
      "per_page": 5,
      "page": 1
    }
  }
}
```

Example: search pages

```json
{
  "jsonrpc": "2.0",
  "id": 11,
  "method": "tools/call",
  "params": {
    "name": "search_pages",
    "arguments": {
      "query": "returns",
      "per_page": 5
    }
  }
}
```

Example: search custom post type (CPT)

```json
{
  "jsonrpc": "2.0",
  "id": 12,
  "method": "tools/call",
  "params": {
    "name": "search_custom_post_types",
    "arguments": {
      "post_type": "event",
      "query": "summer",
      "meta_filters": [
        { "key": "city", "value": "London" }
      ],
      "per_page": 10
    }
  }
}
```

Example: search WordPress post categories

```json
{
  "jsonrpc": "2.0",
  "id": 13,
  "method": "tools/call",
  "params": {
    "name": "search_post_categories",
    "arguments": {
      "query": "news",
      "per_page": 20
    }
  }
}
```

Example: search WordPress tags

```json
{
  "jsonrpc": "2.0",
  "id": 14,
  "method": "tools/call",
  "params": {
    "name": "search_tags",
    "arguments": {
      "query": "featured",
      "per_page": 20
    }
  }
}
```

Example: create a post

```json
{
  "jsonrpc": "2.0",
  "id": 15,
  "method": "tools/call",
  "params": {
    "name": "create_post",
    "arguments": {
      "title": "New Shipping Update",
      "content": "We now support express shipping.",
      "status": "publish"
    }
  }
}
```

Example: create a category

```json
{
  "jsonrpc": "2.0",
  "id": 16,
  "method": "tools/call",
  "params": {
    "name": "create_category",
    "arguments": {
      "name": "Announcements",
      "slug": "announcements"
    }
  }
}
```

Example: create a product

```json
{
  "jsonrpc": "2.0",
  "id": 17,
  "method": "tools/call",
  "params": {
    "name": "create_product",
    "arguments": {
      "name": "Classic Hoodie",
      "regular_price": 49.99,
      "status": "publish",
      "manage_stock": true,
      "stock_quantity": 25
    }
  }
}
```

Example: create an order

```json
{
  "jsonrpc": "2.0",
  "id": 18,
  "method": "tools/call",
  "params": {
    "name": "create_order",
    "arguments": {
      "customer_id": 12,
      "line_items": [
        { "product_id": 101, "quantity": 2 }
      ],
      "status": "processing"
    }
  }
}
```

Example: create a user

```json
{
  "jsonrpc": "2.0",
  "id": 19,
  "method": "tools/call",
  "params": {
    "name": "create_user",
    "arguments": {
      "username": "new_customer",
      "email": "new_customer@example.com",
      "role": "customer"
    }
  }
}
```

Example: search product categories

```json
{
  "jsonrpc": "2.0",
  "id": 20,
  "method": "tools/call",
  "params": {
    "name": "search_product_categories",
    "arguments": {
      "query": "hoodie",
      "per_page": 20
    }
  }
}
```

Example: get order details

```json
{
  "jsonrpc": "2.0",
  "id": 21,
  "method": "tools/call",
  "params": {
    "name": "get_order_details",
    "arguments": {
      "order_id": 1234
    }
  }
}
```

### Common errors you may see

- `woocommerce_missing`: WooCommerce is not installed/active for Woo tools.
- `insufficient_capability`: user lacks permission for order tools.
- `invalid_post_type`: invalid post type value.
- `invalid_taxonomy`: taxonomy must be `category` or `post_tag` for WP taxonomy tools.
- `post_type not allowed`: CPT not whitelisted in plugin settings.
- `name parameter required` / `Unknown tool`: wrong `tools/call` payload.

## Claude Desktop config (example)

```json
{
  "mcpServers": {
    "wordpress-site": {
      "command": "curl",
      "args": [
        "-X", "POST",
        "-H", "Content-Type: application/json",
        "-H", "Authorization: Basic {base64_credentials}",
        "-d", "@-",
        "{site_url}/wp-json/wp-mcp/v1/mcp"
      ]
    }
  }
}
```

Replace `{base64_credentials}` with base64 of `username:application_password` and `{site_url}` with your configured site URL.

## Security
- Uses WordPress Application Passwords for all `/mcp` requests.
- Admin AJAX uses nonces and capability checks.
- Inputs are sanitized using WordPress helpers.

## Notes
- If WooCommerce is not active, WooCommerce-specific tools will be hidden and unavailable.
- The plugin will use `logiscape/mcp-sdk-php` if present (installed via Composer). If the SDK is missing the plugin falls back to a minimal JSON-RPC router that still supports tools/list and tools/call.

## Contributing
- Please follow WordPress coding standards and use the built-in APIs for data access (no direct DB queries).

## License
- MIT (add or change as needed)
