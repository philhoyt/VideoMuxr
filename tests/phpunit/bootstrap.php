<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap file.
 *
 * Loads the WordPress test suite environment when running integration tests.
 * For unit tests (tests/phpunit/unit/), Brain Monkey mocks WordPress functions.
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	// Unit test suite — load stubs and plugin classes directly.
	$root = dirname( __DIR__, 2 );

	require_once $root . '/vendor/autoload.php';

	// Minimal constants so plugin files pass the ABSPATH guard.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/' );
	}

	// WordPress class stubs (loaded before Patchwork — never mocked).
	require_once __DIR__ . '/stubs/class-wp-error.php';
	require_once __DIR__ . '/stubs/class-wp-rest-server.php';
	require_once __DIR__ . '/stubs/class-wp-rest-request.php';

	// WordPress function stubs (loaded before Patchwork — never mocked).
	require_once __DIR__ . '/stubs/functions-wp.php';

	// Initialize Patchwork before loading plugin files so Brain\Monkey can
	// mock plugin-defined functions (e.g. videomuxr_is_configured).
	require_once $root . '/vendor/antecedent/patchwork/Patchwork.php';

	// Plugin files (loaded after Patchwork so they are interceptable).
	require_once $root . '/includes/functions.php';
	require_once $root . '/includes/class-videomuxr-settings.php';
	require_once $root . '/includes/class-videomuxr-mux.php';
	require_once $root . '/includes/class-videomuxr-meta.php';
	require_once $root . '/includes/class-videomuxr-rest.php';

	return;
}

// Integration test suite — bootstrap the full WordPress test environment.
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
