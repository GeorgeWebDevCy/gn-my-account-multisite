<?php
/**
 *  GN My Account Multisite
 *
 * @package       GNMYACCOUN
 * @wordpress-plugin
 * Plugin Name:    GN My Account Multisite
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-my-account-multisite
 * Description:   Display all orders from all sites in the multisite network for the current user
 * Version:       1.0.0
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   gn-my-account-multisite
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with  GN My Account Multisite. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GN My Account Multisite
 *
 * @package       GNMYACCOUN
 * @wordpress-plugin
 * Plugin Name:    GN My Account Multisite
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-my-account-multisite
 * Description:   Display all orders from all sites in the multisite network for the current user
 * Version:       1.0.0
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   gn-my-account-multisite
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with  GN My Account Multisite. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Plugin name
define( 'GNMYACCOUN_NAME', 'GN My Account Multisite' );

// Plugin version
define( 'GNMYACCOUN_VERSION', '1.0.0' );

// Plugin Root File
define( 'GNMYACCOUN_PLUGIN_FILE', __FILE__ );

// Plugin base
define( 'GNMYACCOUN_PLUGIN_BASE', plugin_basename( GNMYACCOUN_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNMYACCOUN_PLUGIN_DIR', plugin_dir_path( GNMYACCOUN_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNMYACCOUN_PLUGIN_URL', plugin_dir_url( GNMYACCOUN_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNMYACCOUN_PLUGIN_DIR . 'core/class-gn-my-account-multisite.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @since   1.0.0
 * @return  object|Gn_My_Account_Multisite
 */
function GNMYACCOUN() {
    return Gn_My_Account_Multisite::instance();
}

/**
 * Enqueue the custom CSS and inline styles.
 */
function gn_my_account_multisite_enqueue_styles() {
    // Get the URL of the plugin folder
    $plugin_url = GNMYACCOUN_PLUGIN_URL;

    // Enqueue the custom CSS file
    wp_enqueue_style( 'gn-my-account-multisite-css', $plugin_url . 'gn-my-account-multisite.css' );

    // Inline CSS
    $inline_css = '
        /* Custom styles for tables with class "george" */
        table.george.woocommerce-orders-table.woocommerce-MyAccount-orders.shop_table.shop_table_responsive.my_account_orders.account-orders-table {
            width: 100%;
            display: table;
        }

        /* Hide tables without the class "george" */
        table.woocommerce-orders-table.woocommerce-MyAccount-orders.shop_table.shop_table_responsive.my_account_orders.account-orders-table {
            display: none;
        }
        .woocommerce-account .woocommerce .woocommerce-MyAccount-content .woocommerce-info
        {
            display:none;
        }
    ';

    // Add the inline CSS to the enqueued style
    wp_add_inline_style( 'gn-my-account-multisite-css', $inline_css );
}
add_action( 'wp_enqueue_scripts', 'gn_my_account_multisite_enqueue_styles' );


// Check if WooCommerce is active on plugin activation
function gn_my_account_multisite_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        // WooCommerce is not installed or activated, deactivate the plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'This plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce first.' );
    }
}
register_activation_hook( __FILE__, 'gn_my_account_multisite_check_woocommerce' );

// Shortcode to display the default WooCommerce My Account dashboard with the custom orders table
function gn_woocommerce_my_account_shortcode( $atts ) {
    if ( class_exists( 'WooCommerce' ) ) {
        if ( is_user_logged_in() ) {
            // Output the default WooCommerce My Account dashboard
            ob_start();
            echo do_shortcode( '[woocommerce_my_account]' );

            // Display the custom orders table if the tab "orders" is active
            if ( isset( $_GET['tab'] ) && 'orders' === $_GET['tab'] ) {
                echo do_shortcode( '[gn_woocommerce_custom_orders_table]' );
            }

            return ob_get_clean();
        } else {
            // Show login form if the user is not logged in
            return do_shortcode( '[woocommerce_my_account]' );
        }
    } else {
        return 'WooCommerce is not installed or activated.';
    }
}
add_shortcode( 'gn_woocommerce_my_account', 'gn_woocommerce_my_account_shortcode' );


// Shortcode to display custom table with orders from all sites
function gn_woocommerce_custom_orders_table() {
    if ( class_exists( 'WooCommerce' ) ) {
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $user_email   = $current_user->user_email;

            // Get all sites in the multisite network
            $sites = get_sites();

            // Array to store orders for all sites
            $all_orders = array();

            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );

                // Custom query to get orders for the current user based on their email
                global $wpdb;
                $order_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->prefix}posts
                        WHERE post_type = 'shop_order'
                        AND post_status IN ( 'wc-completed', 'wc-processing', 'wc-on-hold' )
                        AND post_author = %d
                        ORDER BY post_date DESC",
                        $current_user->ID
                    )
                );

                if ( $order_ids ) {
                    foreach ( $order_ids as $order_id ) {
                        $order = wc_get_order( $order_id );
                        if ( $order && ! in_array( $order, $all_orders, true ) ) {
                            // Store the site ID where the order was created as custom order metadata
                            $order->update_meta_data( '_order_blog_id', get_current_blog_id() );
                            $order->save();
                            $all_orders[] = $order;
                        }
                    }
                }

                restore_current_blog();
            }

            if ( ! empty( $all_orders ) ) {
                echo '<table class="george woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
                echo '<thead><tr>';
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">Order</span></th>';
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">Date</span></th>';
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-product-name"><span class="nobr">Product Name</span></th>'; // Add the Product Name column
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr">Status</span></th>';
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">Total</span></th>';
                echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr">Actions</span></th>';
                echo '</tr></thead>';
                echo '<tbody>';

                foreach ( $all_orders as $order ) {
                    // Get the site ID where the order was created
                    $site_id = $order->get_meta( '_order_blog_id', true );

                    // Switch to the site where the order was created
                    switch_to_blog( $site_id );

                    // Get the site URL of the order
                    $site_url = get_home_url( $site_id );

                    echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-' . esc_attr( $order->get_status() ) . ' order">';
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order"><a href="' . esc_url( $site_url . '/my-account/view-order/' . $order->get_id() ) . '">' . $order->get_order_number() . '</a></td>';
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">' . wc_format_datetime( $order->get_date_created() ) . '</td>';
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-product-name" data-title="Product Name">'; // Start Product Name cell
                    foreach ( $order->get_items() as $item_id => $item ) {
                        $product_id = $item->get_product_id();
                        $product_name = $item->get_name();
                        $product_permalink = get_permalink( $product_id );
                        // Display the product name with a link to the product page
                        echo '<a href="' . esc_url( $product_permalink ) . '">' . $product_name . '</a><br>';
                    }
                    echo '</td>'; // End Product Name cell
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="Status">' . wc_get_order_status_name( $order->get_status() ) . '</td>';
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">' . wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) . '</td>';
                    echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions"><a href="' . esc_url( $site_url . '/my-account/view-order/' . $order->get_id() ) . '" class="woocommerce-button button view">View</a></td>';
                    echo '</tr>';

                    // Restore the current site before moving to the next order
                    restore_current_blog();
                }

                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>' . __( 'No orders found.', 'gn-my-account-multisite' ) . '</p>';
            }
        } else {
            // Show login form if the user is not logged in
            echo do_shortcode( '[woocommerce_my_account]' );
        }
    } else {
        echo 'WooCommerce is not installed or activated.';
    }
}
add_shortcode( 'gn_woocommerce_custom_orders_table', 'gn_woocommerce_custom_orders_table' );

// Function to override the default "My Orders" endpoint template
function gn_my_account_override_orders_template( $template ) {
    if ( is_wc_endpoint_url( 'orders' ) ) {
        // Display the custom orders table using shortcode
        $custom_orders_table = do_shortcode( '[gn_woocommerce_custom_orders_table]' );

        // Return the custom content as the new template
        return '<div class="woocommerce-MyAccount-content">' . $custom_orders_table . '</div>';
    }

    return $template;
}
add_filter( 'woocommerce_account_content', 'gn_my_account_override_orders_template' );

// Helper function to get formatted order subtotal
function gn_my_account_multisite_get_formatted_order_subtotal( $order ) {
    return wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) );
}

// Helper function to get formatted order tax
function gn_my_account_multisite_get_formatted_order_tax( $order ) {
    return wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) );
}

// Initialize the plugin
GNMYACCOUN();
