<?php
declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for videomuxr_get_player_html().
 */
class Test_VideoMuxr_Player_Html extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Pass-through stubs for escaping and filter functions.
		Functions\stubs(
			array(
				'esc_attr'      => static fn( string $v ): string => $v,
				'apply_filters' => static fn( string $tag, mixed $value ): mixed => $value,
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_output_contains_playback_id_attribute(): void {
		$html = videomuxr_get_player_html( 'abc123' );

		$this->assertStringContainsString( 'playback-id="abc123"', $html );
	}

	public function test_output_contains_controls_boolean_attribute(): void {
		$html = videomuxr_get_player_html( 'abc123' );

		$this->assertStringContainsString( ' controls', $html );
	}

	public function test_output_contains_playsinline_boolean_attribute(): void {
		$html = videomuxr_get_player_html( 'abc123' );

		$this->assertStringContainsString( ' playsinline', $html );
	}

	public function test_output_is_mux_player_element(): void {
		$html = videomuxr_get_player_html( 'abc123' );

		$this->assertStringStartsWith( '<mux-player', $html );
		$this->assertStringEndsWith( '</mux-player>', $html );
	}

	public function test_custom_attrs_are_merged(): void {
		$html = videomuxr_get_player_html( 'abc123', array( 'muted' => '' ) );

		$this->assertStringContainsString( ' muted', $html );
	}

	public function test_custom_attr_with_value_is_quoted(): void {
		$html = videomuxr_get_player_html( 'abc123', array( 'style' => 'width:100%' ) );

		$this->assertStringContainsString( 'style="width:100%"', $html );
	}

	public function test_custom_attr_overrides_default(): void {
		$html = videomuxr_get_player_html( 'abc123', array( 'playback-id' => 'override' ) );

		$this->assertStringContainsString( 'playback-id="override"', $html );
		$this->assertStringNotContainsString( 'playback-id="abc123"', $html );
	}
}
