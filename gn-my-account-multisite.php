<?php
/**
 *  GN My Account Multisite
 *
 * @package       GNMYACCOUN
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.0.0
 *
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
 * HELPER COMMENT START
 * 
 * This file contains the main information about the plugin.
 * It is used to register all components necessary to run the plugin.
 * 
 * The comment above contains all information about the plugin 
 * that are used by WordPress to differenciate the plugin and register it properly.
 * It also contains further PHPDocs parameter for a better documentation
 * 
 * The function GNMYACCOUN() is the main function that you will be able to 
 * use throughout your plugin to extend the logic. Further information
 * about that is available within the sub classes.
 * 
 * HELPER COMMENT END
 */

// Plugin name
define( 'GNMYACCOUN_NAME',			' GN My Account Multisite' );

// Plugin version
define( 'GNMYACCOUN_VERSION',		'1.0.0' );

// Plugin Root File
define( 'GNMYACCOUN_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'GNMYACCOUN_PLUGIN_BASE',	plugin_basename( GNMYACCOUN_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNMYACCOUN_PLUGIN_DIR',	plugin_dir_path( GNMYACCOUN_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNMYACCOUN_PLUGIN_URL',	plugin_dir_url( GNMYACCOUN_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNMYACCOUN_PLUGIN_DIR . 'core/class-gn-my-account-multisite.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Gn_My_Account_Multisite
 */
function GNMYACCOUN() {
	return Gn_My_Account_Multisite::instance();
}

// Check if WooCommerce is active on plugin activation
function gn_my_account_multisite_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        // WooCommerce is not installed or activated, deactivate the plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'This plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce first.' );
    }
}
register_activation_hook( __FILE__, 'gn_my_account_multisite_check_woocommerce' );

GNMYACCOUN();
