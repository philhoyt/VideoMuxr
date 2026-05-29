<?php
/**
 * Minimal WP_REST_Request stub for unit tests.
 */

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Stub WP_REST_Request for use outside a full WordPress environment.
	 */
	class WP_REST_Request {

		/** @var array<string,mixed> */
		private array $params = array();

		/** @var string */
		private string $method;

		/**
		 * @param string $method HTTP method.
		 * @param string $route  REST route.
		 */
		public function __construct( string $method = 'GET', string $route = '' ) {
			$this->method = $method;
		}

		/**
		 * Get a request parameter.
		 *
		 * @param string $key Parameter name.
		 * @return mixed
		 */
		public function get_param( string $key ): mixed {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * Set a request parameter.
		 *
		 * @param string $key   Parameter name.
		 * @param mixed  $value Parameter value.
		 */
		public function set_param( string $key, mixed $value ): void {
			$this->params[ $key ] = $value;
		}

		/** Return the HTTP method. */
		public function get_method(): string {
			return $this->method;
		}
	}
}
