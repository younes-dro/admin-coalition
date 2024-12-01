<?php 

if ( defined( 'WP_CLI' ) ) {
    /**
     * WP-CLI command to get Discord rules by product ID.
     */
    class Get_Discord_Rules_Command {
        
        /**
         * Retrieve the Discord rules associated with a product.
         *
         * ## OPTIONS
         *
         * <product_id>
         * : The ID of the product.
         *
         * ## EXAMPLES
         *
         *     wp get-rules 123
         *
         * @when after_wp_load
         */
        public function __invoke( $args, $assoc_args ) {
            list( $product_id ) = $args;
            $product_id = intval( $product_id );

            if ( ! $product_id ) {
                WP_CLI::error( 'Please provide a valid product ID.' );
                return;
            }

            // Assuming the get_discord_rules_by_product method is in a utility class
            $rules = Woo_Discord_Steam_Integration_Utils::get_discord_rules_by_product( $product_id );

            if ( empty( $rules ) ) {
                WP_CLI::success( 'No Discord rules found for this product.' );
            } else {
                WP_CLI::success( 'Discord rules retrieved:' );
                // WP_CLI::print_value( $rules, array( 'format' => 'json' ) );
                WP_CLI::log( var_export( $rules, true ) );
            }
        }
    }

    WP_CLI::add_command( 'get-rules', 'Get_Discord_Rules_Command' );
}
