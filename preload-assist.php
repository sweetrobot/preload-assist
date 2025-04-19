<?php
/**
 * Preload Assist
 *
 * @package           PreloadAssist
 * @author            Preload Assist
 * @copyright         2025 Preload Assist
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Preload Assist
 * Plugin URI:        https://example.com/preload-assist
 * Description:       Generates URL permutations based on WooCommerce categories, FacetWP facets, and custom parameters for FlyingPress cache preloading.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Preload Assist
 * Author URI:        https://example.com
 * Text Domain:       preload-assist
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://example.com/preload-assist/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'PRELOAD_ASSIST_VERSION', '1.0.0' );
define( 'PRELOAD_ASSIST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRELOAD_ASSIST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PRELOAD_ASSIST_PLUGIN_FILE', __FILE__ );
define( 'PRELOAD_ASSIST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load dependencies.
require_once PRELOAD_ASSIST_PLUGIN_DIR . 'includes/class-preload-assist.php';

// Activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'PreloadAssist\\Preload_Assist', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PreloadAssist\\Preload_Assist', 'deactivate' ) );

// Initialize the plugin.
function preload_assist_init() {
    $preload_assist = new PreloadAssist\Preload_Assist();
    $preload_assist->init();
}
add_action( 'plugins_loaded', 'preload_assist_init' );