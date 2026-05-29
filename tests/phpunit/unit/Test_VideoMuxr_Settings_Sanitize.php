<?php
declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VideoMuxr_Settings::sanitize_options().
 */
class Test_VideoMuxr_Settings_Sanitize extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// sanitize_text_field is provided by tests/phpunit/stubs/functions-wp.php
		// (loaded before Patchwork) so it cannot be re-mocked here.
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_returns_empty_array_for_non_array_input(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options( 'not-an-array' );

		$this->assertSame( array(), $result );
	}

	public function test_returns_empty_array_for_null_input(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options( null );

		$this->assertSame( array(), $result );
	}

	public function test_sanitizes_token_id(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options(
			array(
				'token_id'     => '  my-token  ',
				'token_secret' => 'secret',
			)
		);

		$this->assertSame( 'my-token', $result['token_id'] );
	}

	public function test_sanitizes_token_secret(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options(
			array(
				'token_id'     => 'token',
				'token_secret' => '<script>bad</script>',
			)
		);

		$this->assertSame( 'bad', $result['token_secret'] );
	}

	public function test_result_always_contains_both_keys(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options( array() );

		$this->assertArrayHasKey( 'token_id', $result );
		$this->assertArrayHasKey( 'token_secret', $result );
	}

	public function test_missing_keys_default_to_empty_string(): void {
		$result = VideoMuxr_Settings::get_instance()->sanitize_options( array() );

		$this->assertSame( '', $result['token_id'] );
		$this->assertSame( '', $result['token_secret'] );
	}
}
