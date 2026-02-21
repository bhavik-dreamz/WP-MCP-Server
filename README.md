# WP MCP Server

Exposes a WordPress site as an MCP (Model Context Protocol) server so MCP clients like Claude Desktop can call tools over HTTP (JSON-RPC). This plugin implements REST endpoints and a set of tools for WordPress and WooCommerce data access.

## Features
- Exposes `/wp-json/wp-mcp/v1/info` (public) and `/wp-json/wp-mcp/v1/mcp` (JSON-RPC entrypoint)
- Authentication via WordPress Application Passwords (Basic auth header)
- Tools: search_posts, search_pages, search_custom_post_types, search_products, search_product_categories, get_orders, get_order_details, recommend_products
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
    "tool": "search_posts",
    "args": { "query": "hello", "per_page": 5 }
  }
}
```

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
