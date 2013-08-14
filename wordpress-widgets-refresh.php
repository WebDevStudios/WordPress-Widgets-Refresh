<?php
/**
 * WordPress Widgets Refresh
 *
 * @package   WordPressWidgetsRefresh
 * @author    Contributors to https://github.com/WebDevStudios/WordPress-Widgets-Refresh
 * @license   GPL-2.0+
 * @link      https://github.com/WebDevStudios/WordPress-Widgets-Refresh
 *
 * @wordpress-plugin
 * Plugin Name: WordPress Widgets Refresh
 * Plugin URI:  https://github.com/WebDevStudios/WordPress-Widgets-Refresh
 * Description: Rethink WordPress Widgets
 * Version:     0.1.0
 * Author:      Contributors to https://github.com/WebDevStudios/WordPress-Widgets-Refresh
 * Text Domain: wordpress-widgets-refresh
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'class-wordpress-widgets-refresh.php';

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'WordPress_Widgets_Refresh', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WordPress_Widgets_Refresh', 'deactivate' ) );

WordPress_Widgets_Refresh::get_instance();
