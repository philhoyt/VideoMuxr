<?php
declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for videomuxr_is_configured().
 */
class Test_VideoMuxr_Is_Configured extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_returns_false_when_option_missing(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$this->assertFalse( videomuxr_is_configured() );
	}

	public function test_returns_false_when_only_token_id_set(): void {
		Functions\when( 'get_option' )->justReturn( array( 'token_id' => 'abc123' ) );

		$this->assertFalse( videomuxr_is_configured() );
	}

	public function test_returns_false_when_only_token_secret_set(): void {
		Functions\when( 'get_option' )->justReturn( array( 'token_secret' => 'secret456' ) );

		$this->assertFalse( videomuxr_is_configured() );
	}

	public function test_returns_false_when_option_is_not_array(): void {
		Functions\when( 'get_option' )->justReturn( 'bad-value' );

		$this->assertFalse( videomuxr_is_configured() );
	}

	public function test_returns_true_when_both_credentials_present(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'token_id'     => 'abc123',
				'token_secret' => 'secret456',
			)
		);

		$this->assertTrue( videomuxr_is_configured() );
	}

	public function test_returns_false_when_token_id_is_empty_string(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'token_id'     => '',
				'token_secret' => 'secret456',
			)
		);

		$this->assertFalse( videomuxr_is_configured() );
	}
}
