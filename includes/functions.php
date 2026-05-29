<?php
/**
 * Public helper functions.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

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
