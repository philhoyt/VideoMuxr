<?php
declare(strict_types=1);

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VideoMuxr_Blocks class and block source file integrity.
 */
class Test_VideoMuxr_Blocks extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Source file integrity
	// -------------------------------------------------------------------------

	public function test_block_json_exists(): void {
		$path = dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json';
		$this->assertFileExists( $path );
	}

	public function test_render_php_exists(): void {
		$path = dirname( __DIR__, 3 ) . '/src/videomuxr-video/render.php';
		$this->assertFileExists( $path );
	}

	public function test_build_block_json_exists(): void {
		$path = dirname( __DIR__, 3 ) . '/build/videomuxr-video/block.json';
		$this->assertFileExists( $path );
	}

	public function test_build_index_js_exists(): void {
		$path = dirname( __DIR__, 3 ) . '/build/videomuxr-video/index.js';
		$this->assertFileExists( $path );
	}

	public function test_build_render_php_exists(): void {
		$path = dirname( __DIR__, 3 ) . '/build/videomuxr-video/render.php';
		$this->assertFileExists( $path );
	}

	// -------------------------------------------------------------------------
	// block.json contract
	// -------------------------------------------------------------------------

	public function test_block_json_has_correct_name(): void {
		$json = json_decode(
			file_get_contents( dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json' ),
			true
		);
		$this->assertSame( 'videomuxr/video', $json['name'] );
	}

	public function test_block_json_api_version_is_3(): void {
		$json = json_decode(
			file_get_contents( dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json' ),
			true
		);
		$this->assertSame( 3, $json['apiVersion'] );
	}

	public function test_block_json_has_playback_id_attribute(): void {
		$json = json_decode(
			file_get_contents( dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json' ),
			true
		);
		$this->assertArrayHasKey( 'playbackId', $json['attributes'] );
		$this->assertSame( 'string', $json['attributes']['playbackId']['type'] );
	}

	public function test_block_json_has_asset_id_attribute(): void {
		$json = json_decode(
			file_get_contents( dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json' ),
			true
		);
		$this->assertArrayHasKey( 'assetId', $json['attributes'] );
	}

	public function test_block_json_html_support_is_disabled(): void {
		$json = json_decode(
			file_get_contents( dirname( __DIR__, 3 ) . '/src/videomuxr-video/block.json' ),
			true
		);
		$this->assertFalse( $json['supports']['html'] );
	}

	// -------------------------------------------------------------------------
	// Class instantiation
	// -------------------------------------------------------------------------

	public function test_blocks_class_is_singleton(): void {
		$a = VideoMuxr_Blocks::get_instance();
		$b = VideoMuxr_Blocks::get_instance();
		$this->assertSame( $a, $b );
	}
}
