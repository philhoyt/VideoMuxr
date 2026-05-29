<?php
/**
 * Minimal WordPress function stubs for unit tests.
 *
 * These are never mocked, so they are loaded before Patchwork.
 * Only covers functions that are called unconditionally by plugin code
 * (e.g. inside WP_Error messages) and whose return value is not
 * under test.
 */

if ( ! function_exists( '__' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
