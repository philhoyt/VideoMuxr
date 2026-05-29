<?php
declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VideoMuxr_REST permission callbacks and authorization checks.
 */
class Test_VideoMuxr_REST_Permission extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// check_permission()
	// -------------------------------------------------------------------------

	public function test_check_permission_returns_wp_error_when_user_lacks_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = VideoMuxr_REST::get_instance()->check_permission();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_forbidden', $result->get_error_code() );
	}

	public function test_check_permission_returns_true_when_user_has_edit_posts(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$result = VideoMuxr_REST::get_instance()->check_permission();

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// handle_delete_asset() — per-post capability check (SEC-01 fix)
	// -------------------------------------------------------------------------

	public function test_delete_asset_rejects_when_user_cannot_edit_specific_post(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( true );
		// current_user_can( 'edit_post', $id ) returns false.
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = new WP_REST_Request( 'DELETE' );
		$request->set_param( 'post_id', 42 );

		$result = VideoMuxr_REST::get_instance()->handle_delete_asset( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_forbidden', $result->get_error_code() );
	}

	public function test_delete_asset_rejects_when_not_configured(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( false );

		$request = new WP_REST_Request( 'DELETE' );
		$request->set_param( 'post_id', 42 );

		$result = VideoMuxr_REST::get_instance()->handle_delete_asset( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_not_configured', $result->get_error_code() );
	}

	public function test_delete_asset_returns_404_when_post_has_no_asset(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$request = new WP_REST_Request( 'DELETE' );
		$request->set_param( 'post_id', 99 );

		$result = VideoMuxr_REST::get_instance()->handle_delete_asset( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_no_asset', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// handle_direct_upload()
	// -------------------------------------------------------------------------

	public function test_direct_upload_rejects_when_not_configured(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( false );

		$result = VideoMuxr_REST::get_instance()->handle_direct_upload();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_not_configured', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// handle_upload_status()
	// -------------------------------------------------------------------------

	public function test_upload_status_rejects_when_not_configured(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( false );

		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'upload_id', 'test-upload-id' );

		$result = VideoMuxr_REST::get_instance()->handle_upload_status( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_not_configured', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// handle_delete_asset() — direct asset_id path (block editor use case)
	// -------------------------------------------------------------------------

	public function test_delete_asset_rejects_when_neither_param_provided(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( true );

		$request = new WP_REST_Request( 'DELETE' );

		$result = VideoMuxr_REST::get_instance()->handle_delete_asset( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'videomuxr_missing_param', $result->get_error_code() );
	}

	public function test_delete_asset_with_direct_asset_id_skips_per_post_check(): void {
		Functions\when( 'videomuxr_is_configured' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( array() );
		// wp_remote_request returns a WP_Error so the Mux HTTP call fails gracefully.
		Functions\when( 'wp_remote_request' )->justReturn(
			new WP_Error( 'http_request_failed', 'test' )
		);

		$request = new WP_REST_Request( 'DELETE' );
		$request->set_param( 'asset_id', 'mux-asset-abc123' );

		$result = VideoMuxr_REST::get_instance()->handle_delete_asset( $request );

		// Handler reached the Mux delete call — result is an API error, NOT a
		// permission or missing_param error. This confirms the per-post check
		// is skipped when asset_id is provided directly.
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNotSame( 'videomuxr_forbidden', $result->get_error_code() );
		$this->assertNotSame( 'videomuxr_missing_param', $result->get_error_code() );
		$this->assertNotSame( 'videomuxr_not_configured', $result->get_error_code() );
	}
}
