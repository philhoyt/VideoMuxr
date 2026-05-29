<?php
/**
 * REST API endpoints.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles REST routes under the "videomuxr/v1" namespace.
 */
class VideoMuxr_REST {

	/**
	 * Singleton instance.
	 *
	 * @var VideoMuxr_REST|null
	 */
	private static ?VideoMuxr_REST $instance = null;

	/** REST namespace. */
	public const NAMESPACE = 'videomuxr/v1';

	/** Singleton accessor. */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance(). */
	private function __construct() {}

	/** Register WordPress hooks. */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST routes for this plugin.
	 */
	public function register_routes(): void {
		// POST /videomuxr/v1/direct-upload.
		register_rest_route(
			self::NAMESPACE,
			'/direct-upload',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_direct_upload' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// GET /videomuxr/v1/upload-status.
		register_rest_route(
			self::NAMESPACE,
			'/upload-status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'handle_upload_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'upload_id' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// DELETE /videomuxr/v1/asset.
		register_rest_route(
			self::NAMESPACE,
			'/asset',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'handle_delete_asset' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'post_id'  => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'asset_id' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Permission callback
	// -------------------------------------------------------------------------

	/**
	 * Shared permission check: must be able to edit posts.
	 *
	 * @return true|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'videomuxr_forbidden',
				__( 'You do not have permission to use this endpoint.', 'videomuxr' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	/**
	 * POST /videomuxr/v1/direct-upload — request a Mux direct upload URL.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_direct_upload() {
		if ( ! videomuxr_is_configured() ) {
			return new WP_Error(
				'videomuxr_not_configured',
				__( 'Mux API credentials are not configured.', 'videomuxr' ),
				array( 'status' => 503 )
			);
		}

		$mux    = $this->make_mux_client();
		$result = $mux->create_direct_upload( home_url() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * GET /videomuxr/v1/upload-status — poll a Mux upload for its current status.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_upload_status( WP_REST_Request $request ) {
		if ( ! videomuxr_is_configured() ) {
			return new WP_Error(
				'videomuxr_not_configured',
				__( 'Mux API credentials are not configured.', 'videomuxr' ),
				array( 'status' => 503 )
			);
		}

		$upload_id = (string) $request->get_param( 'upload_id' );
		$mux       = $this->make_mux_client();
		$result    = $mux->get_upload_status( $upload_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Only surface playback_id when the asset is truly ready.
		if ( 'ready' !== $result['status'] ) {
			unset( $result['playback_id'] );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * DELETE /videomuxr/v1/asset — delete a Mux asset.
	 *
	 * Accepts either:
	 *   - post_id  — looks up _videomuxr_asset_id from post meta and clears both meta keys
	 *   - asset_id — deletes the Mux asset directly (used by the block editor)
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_delete_asset( WP_REST_Request $request ) {
		if ( ! videomuxr_is_configured() ) {
			return new WP_Error(
				'videomuxr_not_configured',
				__( 'Mux API credentials are not configured.', 'videomuxr' ),
				array( 'status' => 503 )
			);
		}

		$post_id  = (int) $request->get_param( 'post_id' );
		$asset_id = (string) $request->get_param( 'asset_id' );

		if ( $post_id > 0 ) {
			// Post-meta path: verify the caller can edit this specific post.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new WP_Error(
					'videomuxr_forbidden',
					__( 'You cannot edit this post.', 'videomuxr' ),
					array( 'status' => 403 )
				);
			}

			$asset_id = (string) get_post_meta( $post_id, '_videomuxr_asset_id', true );

			if ( '' === $asset_id ) {
				return new WP_Error(
					'videomuxr_no_asset',
					__( 'No Mux asset found for this post.', 'videomuxr' ),
					array( 'status' => 404 )
				);
			}
		} elseif ( '' === $asset_id ) {
			return new WP_Error(
				'videomuxr_missing_param',
				__( 'Provide either post_id or asset_id.', 'videomuxr' ),
				array( 'status' => 400 )
			);
		}

		$mux    = $this->make_mux_client();
		$result = $mux->delete_asset( $asset_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $post_id > 0 ) {
			delete_post_meta( $post_id, '_videomuxr_playback_id' );
			delete_post_meta( $post_id, '_videomuxr_asset_id' );
		}

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a Mux client using current credentials.
	 */
	private function make_mux_client(): VideoMuxr_Mux {
		return new VideoMuxr_Mux(
			VideoMuxr_Settings::get_token_id(),
			VideoMuxr_Settings::get_token_secret()
		);
	}
}
