<?php
/**
 * Plugin Name:       VideoMuxr
 * Plugin URI:        https://github.com/philhoyt/VideoMuxr/
 * Description:       Routes WordPress video uploads through Mux for transcoding and playback.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Tested up to:      6.9
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

define( 'VIDEOMUXR_VERSION', '1.0.0' );
define( 'VIDEOMUXR_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIDEOMUXR_URL', plugin_dir_url( __FILE__ ) );

// Wire Plugin Update Checker.
require_once VIDEOMUXR_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$videomuxr_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/philhoyt/VideoMuxr/',
	__FILE__,
	'videomuxr'
);
$videomuxr_update_checker->getVcsApi()->enableReleaseAssets();

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
 * Returns true when both Mux API credentials are saved.
 */
function videomuxr_is_configured(): bool {
	$settings = get_option( 'videomuxr_settings', array() );
	if ( ! is_array( $settings ) ) {
		return false;
	}
	return ! empty( $settings['token_id'] ) && ! empty( $settings['token_secret'] );
}

/**
 * Returns the Mux playback ID stored for a post, or null if not set.
 *
 * @param int $post_id Post ID.
 * @return string|null
 */
function videomuxr_get_playback_id( int $post_id ): ?string {
	$value = get_post_meta( $post_id, '_videomuxr_playback_id', true );
	return ( is_string( $value ) && '' !== $value ) ? $value : null;
}

/**
 * Renders a <mux-player> element for the given playback ID.
 *
 * @param string               $playback_id Mux playback ID.
 * @param array<string,string> $attrs       Additional HTML attributes.
 * @return string
 */
function videomuxr_get_player_html( string $playback_id, array $attrs = array() ): string {
	$default_attrs = array(
		'playback-id' => $playback_id,
		'controls'    => '',
		'playsinline' => '',
	);

	/* @var array<string,string> $attrs */
	$attrs = apply_filters( 'videomuxr_player_attrs', array_merge( $default_attrs, $attrs ), $playback_id );

	$attr_string = '';
	foreach ( $attrs as $key => $value ) {
		$key = esc_attr( $key );
		if ( '' === $value ) {
			$attr_string .= ' ' . $key;
		} else {
			$attr_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}
	}

	return '<mux-player' . $attr_string . '></mux-player>';
}

/**
 * Add type="module" to the mux-player script tag.
 *
 * @param string $tag    HTML script tag.
 * @param string $handle Script handle.
 * @return string
 */
function videomuxr_add_module_type( string $tag, string $handle ): string {
	if ( 'mux-player' === $handle ) {
		return str_replace( ' src=', ' type="module" src=', $tag );
	}
	return $tag;
}

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
