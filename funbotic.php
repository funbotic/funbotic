<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.funbotic.com
 * @since             1.0.0
 * @package           Funbotic
 *
 * @wordpress-plugin
 * Plugin Name:       Funbotic
 * Plugin URI:        https://www.funbotic.com
 * Description:       Logged in/logged out menu.  Conditional shortcodes.  Custom fields.
 * Version:           1.0.0
 * Author:            Alexander LaBrie
 * Author URI:        https://www.funbotic.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       funbotic
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-funbotic-activator.php
 */
function activate_funbotic() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-funbotic-activator.php';
	Funbotic_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-funbotic-deactivator.php
 */
function deactivate_funbotic() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-funbotic-deactivator.php';
	Funbotic_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_funbotic' );
register_deactivation_hook( __FILE__, 'deactivate_funbotic' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-funbotic.php';

/**
 * Registers logic for displaying menus conditionally.
 */
require_once dirname( __FILE__ ) . '/includes/funbotic-conditional-menus.php';

/**
 * Registers shortcodes and logic to display content conditionally.
 */
require_once dirname( __FILE__ ) . '/includes/funbotic-conditional-shortcodes.php';

/**
 * Provides custom fields to be used when uploading media, focused on making a per-user gallery possible.
 */
require_once dirname( __FILE__ ) . '/includes/funbotic-media-fields.php';

/**
 * Generates shortcode for gallery populated dynamically with images tagged with the current Subscriber.
 */
require_once dirname( __FILE__ ) . '/includes/funbotic-dynamic-user-gallery.php';

/**
 * Redirects the user to the home page when logging out.
 */
require_once dirname( __FILE__ ) . '/includes/funbotic-redirect.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_funbotic() {

	$plugin = new Funbotic();
	$plugin->run();

}

run_funbotic();
