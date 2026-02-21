<?php
/**
 * WooCommerce stub for tests – must be in the global namespace.
 *
 * This file is required by ToolProductsTest.php via a setUpBeforeClass call
 * so that the class is only defined for tests that expect WooCommerce to be
 * present.  ToolProductsMissingTest.php does NOT include this file, which
 * is why the "WooCommerce missing" test there naturally triggers the guard.
 */
if ( ! class_exists( 'WC_Product_Query' ) ) {
    class WC_Product_Query {
        protected array $args;

        public function __construct( array $args = array() ) {
            $this->args = $args;
        }

        public function get_products(): array {
            return array();
        }
    }
}
