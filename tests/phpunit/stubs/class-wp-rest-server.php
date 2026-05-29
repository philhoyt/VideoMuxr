<?php
/**
 * Minimal WP_REST_Server stub for unit tests.
 */

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Stub WP_REST_Server constants for use outside a full WordPress environment.
	 */
	class WP_REST_Server {
		const CREATABLE  = 'POST';
		const READABLE   = 'GET';
		const DELETABLE  = 'DELETE';
		const EDITABLE   = 'POST, PUT, PATCH';
		const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}
