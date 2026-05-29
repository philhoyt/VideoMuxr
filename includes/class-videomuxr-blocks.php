<?php
/**
 * Block registration.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers VideoMuxr blocks from compiled build output.
 */
class VideoMuxr_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var VideoMuxr_Blocks|null
	 */
	private static ?VideoMuxr_Blocks $instance = null;

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
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all plugin blocks from build output.
	 */
	public function register_blocks(): void {
		$build_dir = VIDEOMUXR_PATH . 'build/';

		if ( file_exists( $build_dir . 'videomuxr-video/' ) ) {
			register_block_type( $build_dir . 'videomuxr-video/' );
		}
	}
}
