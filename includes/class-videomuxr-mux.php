<?php
/**
 * Mux API client.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Talks to the Mux Video API using Basic Auth (token_id:token_secret).
 *
 * API reference: https://docs.mux.com/api-reference
 */
class VideoMuxr_Mux {

	/** Mux API base URL. */
	private const API_BASE = 'https://api.mux.com/video/v1';

	/**
	 * Mux Token ID.
	 *
	 * @var string
	 */
	private string $token_id;

	/**
	 * Mux Token Secret.
	 *
	 * @var string
	 */
	private string $token_secret;

	/**
	 * Constructor.
	 *
	 * @param string $token_id     Mux Token ID.
	 * @param string $token_secret Mux Token Secret.
	 */
	public function __construct( string $token_id, string $token_secret ) {
		$this->token_id     = $token_id;
		$this->token_secret = $token_secret;
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Creates a Mux direct upload and returns upload_id + upload_url.
	 *
	 * POST https://api.mux.com/video/v1/uploads
	 *
	 * @param string $cors_origin Allowed CORS origin for the browser upload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_direct_upload( string $cors_origin ) {
		$body = wp_json_encode(
			array(
				'cors_origin'        => $cors_origin,
				'new_asset_settings' => array(
					'playback_policy' => array( 'public' ),
				),
				'timeout'            => 3600,
			)
		);

		if ( false === $body ) {
			return new WP_Error( 'videomuxr_encode_error', __( 'Failed to encode request body.', 'videomuxr' ) );
		}

		$response = $this->request( 'POST', self::API_BASE . '/uploads', $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response['data'] ?? null;

		if ( empty( $data['id'] ) || empty( $data['url'] ) ) {
			return new WP_Error(
				'videomuxr_bad_response',
				__( 'Unexpected response from Mux when creating upload.', 'videomuxr' )
			);
		}

		return array(
			'upload_id'  => sanitize_text_field( $data['id'] ),
			'upload_url' => esc_url_raw( $data['url'] ),
		);
	}

	/**
	 * Fetches upload status; resolves to playback_id when asset is ready.
	 *
	 * GET https://api.mux.com/video/v1/uploads/{UPLOAD_ID}
	 *
	 * @param string $upload_id The upload_id returned by create_direct_upload().
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_upload_status( string $upload_id ) {
		$upload_id = sanitize_text_field( $upload_id );

		if ( empty( $upload_id ) ) {
			return new WP_Error( 'videomuxr_invalid_upload_id', __( 'Invalid upload ID.', 'videomuxr' ) );
		}

		$response = $this->request( 'GET', self::API_BASE . '/uploads/' . rawurlencode( $upload_id ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response['data'] ?? null;

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'videomuxr_bad_response',
				__( 'Unexpected response from Mux when fetching upload status.', 'videomuxr' )
			);
		}

		$mux_status   = $data['status'] ?? 'waiting';
		$asset_id     = isset( $data['asset_id'] ) ? sanitize_text_field( (string) $data['asset_id'] ) : null;
		$playback_id  = null;
		$aspect_ratio = null;
		$status       = $this->map_upload_status( $mux_status );

		if ( $asset_id && in_array( $status, array( 'asset_created', 'ready' ), true ) ) {
			$asset = $this->get_asset( $asset_id );
			if ( ! is_wp_error( $asset ) && 'ready' === ( $asset['status'] ?? '' ) && ! empty( $asset['playback_id'] ) ) {
				$status       = 'ready';
				$playback_id  = $asset['playback_id'];
				$aspect_ratio = $asset['aspect_ratio'];
			}
		}

		return array(
			'status'       => $status,
			'asset_id'     => $asset_id,
			'playback_id'  => $playback_id,
			'aspect_ratio' => $aspect_ratio,
		);
	}

	/**
	 * Fetches asset details including status and playback ID.
	 *
	 * GET https://api.mux.com/video/v1/assets/{ASSET_ID}
	 *
	 * @param string $asset_id Mux asset ID.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_asset( string $asset_id ) {
		$asset_id = sanitize_text_field( $asset_id );

		if ( empty( $asset_id ) ) {
			return new WP_Error( 'videomuxr_invalid_asset_id', __( 'Invalid asset ID.', 'videomuxr' ) );
		}

		$response = $this->request( 'GET', self::API_BASE . '/assets/' . rawurlencode( $asset_id ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data        = $response['data'] ?? array();
		$playback_id = null;

		if ( ! empty( $data['playback_ids'] ) ) {
			foreach ( $data['playback_ids'] as $pb ) {
				if ( 'public' === ( $pb['policy'] ?? '' ) ) {
					$playback_id = sanitize_text_field( (string) ( $pb['id'] ?? '' ) );
					break;
				}
			}
		}

		return array(
			'status'       => sanitize_text_field( $data['status'] ?? '' ),
			'playback_id'  => $playback_id,
			'aspect_ratio' => sanitize_text_field( $data['aspect_ratio'] ?? '' ),
		);
	}

	/**
	 * Permanently deletes a Mux asset.
	 *
	 * DELETE https://api.mux.com/video/v1/assets/{ASSET_ID}
	 *
	 * @param string $asset_id The Mux asset identifier.
	 * @return true|WP_Error
	 */
	public function delete_asset( string $asset_id ) {
		$asset_id = sanitize_text_field( $asset_id );

		if ( empty( $asset_id ) ) {
			return new WP_Error( 'videomuxr_invalid_asset_id', __( 'Invalid asset ID.', 'videomuxr' ) );
		}

		$result = $this->request( 'DELETE', self::API_BASE . '/assets/' . rawurlencode( $asset_id ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Map a Mux upload status string to our internal vocabulary.
	 *
	 * @param string $mux_status Raw Mux upload status.
	 * @return string One of: waiting, asset_created, ready, errored.
	 */
	private function map_upload_status( string $mux_status ): string {
		switch ( $mux_status ) {
			case 'asset_created':
				return 'asset_created';
			case 'errored':
			case 'cancelled':
				return 'errored';
			default:
				return 'waiting';
		}
	}

	/**
	 * Make an authenticated HTTP request to the Mux API.
	 *
	 * @param string      $method HTTP method (GET, POST, DELETE).
	 * @param string      $url    Full URL.
	 * @param string|null $body   JSON-encoded request body for POST.
	 * @return array<string,mixed>|WP_Error Decoded JSON on success.
	 */
	private function request( string $method, string $url, ?string $body = null ) {
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->token_id . ':' . $this->token_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for Mux Basic Auth.
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		);

		if ( null !== $body ) {
			$args['body'] = $body;
		}

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_request( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		// Mux DELETE endpoints return 204 No Content on success.
		if ( 204 === $code ) {
			return array();
		}

		$json = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $json['error']['messages'][0] )
				? $json['error']['messages'][0]
				: sprintf(
					/* translators: %d: HTTP status code */
					__( 'Mux API returned HTTP %d.', 'videomuxr' ),
					$code
				);
			return new WP_Error( 'videomuxr_api_error', $msg, array( 'status' => $code ) );
		}

		if ( ! is_array( $json ) ) {
			return new WP_Error( 'videomuxr_json_error', __( 'Could not parse Mux API response.', 'videomuxr' ) );
		}

		return $json;
	}
}
