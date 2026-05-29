<?php
/**
 * Post meta registration.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Mux playback and asset ID post meta keys.
 */
class VideoMuxr_Meta {

	/**
	 * Singleton instance.
	 *
	 * @var VideoMuxr_Meta|null
	 */
	private static ?VideoMuxr_Meta $instance = null;

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
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register post meta keys for the 'post' post type.
	 */
	public function register_meta(): void {
		$auth_callback = static function (): bool {
			return current_user_can( 'edit_posts' );
		};

		register_post_meta(
			'post',
			'_videomuxr_playback_id',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => $auth_callback,
			)
		);

		register_post_meta(
			'post',
			'_videomuxr_asset_id',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => $auth_callback,
			)
		);
	}
}
