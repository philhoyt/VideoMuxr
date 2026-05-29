<?php
/**
 * Minimal WP_Error stub for unit tests.
 */

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Stub WP_Error for use outside a full WordPress environment.
	 */
	class WP_Error {

		/** @var string */
		private string $code;

		/** @var string */
		private string $message;

		/** @var mixed */
		private mixed $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Optional data.
		 */
		public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/** Return the error code. */
		public function get_error_code(): string {
			return $this->code;
		}

		/** Return the error message. */
		public function get_error_message(): string {
			return $this->message;
		}

		/** Return the error data. */
		public function get_error_data(): mixed {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Check if a value is a WP_Error instance.
	 *
	 * @param mixed $thing Value to check.
	 */
	function is_wp_error( mixed $thing ): bool {
		return $thing instanceof WP_Error;
	}
}
