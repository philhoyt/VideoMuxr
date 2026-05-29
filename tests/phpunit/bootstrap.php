<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap file.
 *
 * Loads the WordPress test suite environment when running integration tests.
 * For unit tests (tests/phpunit/unit/), Brain Monkey mocks are used instead.
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// Unit test suite — no WordPress bootstrap needed.
	return;
}

// Required by the WP Core test bootstrap.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );

require_once $wp_tests_dir . '/includes/functions.php';

function _manually_load_plugin(): void {
	$plugin_file = glob( dirname( __DIR__, 2 ) . '/*.php' )[0] ?? '';
	if ( $plugin_file ) {
		require $plugin_file;
	}
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $wp_tests_dir . '/includes/bootstrap.php';
