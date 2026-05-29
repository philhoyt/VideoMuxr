<?php
/**
 * Plugin Name:       VideoMuxr
 * Plugin URI:        https://github.com/philhoyt/VideoMuxr/
 * Description:       Routes WordPress video uploads through Mux for transcoding and playback.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Tested up to:      7.0
 * Requires PHP:      8.1
 * Author:            philhoyt
 * Author URI:        https://philhoyt.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       videomuxr
 * Domain Path:       /languages
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'VIDEOMUXR_VERSION', '0.1.0' );
define( 'VIDEOMUXR_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIDEOMUXR_URL', plugin_dir_url( __FILE__ ) );

// Wire Plugin Update Checker.
require_once VIDEOMUXR_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

( static function (): void {
	$checker = PucFactory::buildUpdateChecker(
		'https://github.com/philhoyt/VideoMuxr/',
		__FILE__,
		'videomuxr'
	);
	$checker->getVcsApi()->enableReleaseAssets();
} )();

// Load public helper functions.
require_once VIDEOMUXR_PATH . 'includes/functions.php';

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
function videomuxr_init(): void {
	load_plugin_textdomain( 'videomuxr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	require_once VIDEOMUXR_PATH . 'includes/class-videomuxr.php';
	VideoMuxr::get_instance()->init();
}
add_action( 'plugins_loaded', 'videomuxr_init' );

/**
 * Add a Settings link on the Plugins list page.
 *
 * @param array<string,string> $links Existing action links.
 * @return array<string,string>
 */
function videomuxr_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=videomuxr' ) ),
		esc_html__( 'Settings', 'videomuxr' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'videomuxr_plugin_action_links' );

/**
 * Activation hook.
 */
function videomuxr_activate(): void {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'videomuxr_activate' );

/**
 * Deactivation hook.
 */
function videomuxr_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'videomuxr_deactivate' );
