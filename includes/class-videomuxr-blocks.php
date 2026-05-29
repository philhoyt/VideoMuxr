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
	 * Register every compiled block found under build/blocks/.
	 *
	 * Each block compiles to its own directory containing a block.json;
	 * new blocks are picked up automatically without changing this method.
	 */
	public function register_blocks(): void {
		$blocks_dir = VIDEOMUXR_PATH . 'build/blocks/';

		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		$block_folders = glob( $blocks_dir . '*', GLOB_ONLYDIR );

		if ( empty( $block_folders ) ) {
			return;
		}

		foreach ( $block_folders as $block_folder ) {
			register_block_type( $block_folder );
		}
	}
}
