<?php
/**
 * Core plugin loader.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class — wires up all subsystems.
 */
final class VideoMuxr {

	/**
	 * Singleton instance.
	 *
	 * @var VideoMuxr|null
	 */
	private static ?VideoMuxr $instance = null;

	/** Singleton accessor. */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance(). */
	private function __construct() {}

	/**
	 * Require all dependencies and register hooks.
	 */
	public function init(): void {
		$this->load_dependencies();

		VideoMuxr_Settings::get_instance()->init();
		VideoMuxr_Meta::get_instance()->init();
		VideoMuxr_REST::get_instance()->init();
		VideoMuxr_Blocks::get_instance()->init();

		add_action( 'before_delete_post', array( $this, 'handle_delete_post' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_player' ) );
	}

	/**
	 * Delete the Mux asset when a post is permanently deleted.
	 *
	 * Skips revisions, autosaves, and non-post post types.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_delete_post( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'post' !== get_post_type( $post_id ) ) {
			return;
		}

		$asset_id = get_post_meta( $post_id, '_videomuxr_asset_id', true );

		if ( is_string( $asset_id ) && '' !== $asset_id && videomuxr_is_configured() ) {
			$mux = new VideoMuxr_Mux(
				VideoMuxr_Settings::get_token_id(),
				VideoMuxr_Settings::get_token_secret()
			);
			$mux->delete_asset( $asset_id );
		}

		delete_post_meta( $post_id, '_videomuxr_playback_id' );
		delete_post_meta( $post_id, '_videomuxr_asset_id' );
	}

	/**
	 * Enqueue the Mux player script on singular post pages that have a playback ID.
	 */
	public function enqueue_player(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id     = get_the_ID();
		$playback_id = $post_id ? videomuxr_get_playback_id( $post_id ) : null;

		if ( null === $playback_id ) {
			return;
		}

		wp_enqueue_script(
			'mux-player',
			'https://cdn.jsdelivr.net/npm/@mux/mux-player@3',
			array(),
			VIDEOMUXR_VERSION,
			array( 'in_footer' => true )
		);

		// Add type="module" to the script tag — <mux-player> is an ES module web component.
		add_filter( 'script_loader_tag', 'videomuxr_add_module_type', 10, 2 );
	}

	/**
	 * Load all required class files.
	 */
	private function load_dependencies(): void {
		$dir = VIDEOMUXR_PATH . 'includes/';

		require_once $dir . 'class-videomuxr-settings.php';
		require_once $dir . 'class-videomuxr-mux.php';
		require_once $dir . 'class-videomuxr-meta.php';
		require_once $dir . 'class-videomuxr-rest.php';
		require_once $dir . 'class-videomuxr-blocks.php';
	}
}
