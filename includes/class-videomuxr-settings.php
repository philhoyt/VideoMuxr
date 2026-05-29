<?php
/**
 * Admin settings page and option helpers.
 *
 * @package VideoMuxr
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the Settings → VideoMuxr admin page.
 */
class VideoMuxr_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var VideoMuxr_Settings|null
	 */
	private static ?VideoMuxr_Settings $instance = null;

	/** Options key. */
	public const OPTION_KEY = 'videomuxr_settings';

	/** Singleton accessor. */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor — use get_instance(). */
	private function __construct() {}

	/** Register admin hooks. */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	// -------------------------------------------------------------------------
	// Option accessors
	// -------------------------------------------------------------------------

	/**
	 * Return saved options array.
	 *
	 * @return array<string,string>
	 */
	public static function get_options(): array {
		$saved = get_option( self::OPTION_KEY, array() );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * Retrieve the Mux Token ID.
	 */
	public static function get_token_id(): string {
		return (string) ( self::get_options()['token_id'] ?? '' );
	}

	/**
	 * Retrieve the Mux Token Secret.
	 */
	public static function get_token_secret(): string {
		return (string) ( self::get_options()['token_secret'] ?? '' );
	}

	// -------------------------------------------------------------------------
	// Admin UI
	// -------------------------------------------------------------------------

	/**
	 * Register the plugin settings page under Settings menu.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'VideoMuxr', 'videomuxr' ),
			__( 'VideoMuxr', 'videomuxr' ),
			'manage_options',
			'videomuxr',
			array( $this, 'render_settings_page' )
		);
	}

	/** Register settings, section, and fields. */
	public function register_settings(): void {
		register_setting(
			'videomuxr_group',
			self::OPTION_KEY,
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'videomuxr_mux',
			__( 'Mux API Credentials', 'videomuxr' ),
			array( $this, 'render_section_description' ),
			'videomuxr'
		);

		add_settings_field(
			'videomuxr_token_id',
			__( 'Token ID', 'videomuxr' ),
			array( $this, 'render_text_field' ),
			'videomuxr',
			'videomuxr_mux',
			array(
				'name' => 'token_id',
				'type' => 'text',
			)
		);

		add_settings_field(
			'videomuxr_token_secret',
			__( 'Token Secret', 'videomuxr' ),
			array( $this, 'render_text_field' ),
			'videomuxr',
			'videomuxr_mux',
			array(
				'name' => 'token_secret',
				'type' => 'password',
			)
		);
	}

	/**
	 * Sanitize raw option array submitted from the form.
	 *
	 * @param mixed $input Raw POST data.
	 * @return array<string,string>
	 */
	public function sanitize_options( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		return array(
			'token_id'     => sanitize_text_field( $input['token_id'] ?? '' ),
			'token_secret' => sanitize_text_field( $input['token_secret'] ?? '' ),
		);
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the section description.
	 */
	public function render_section_description(): void {
		echo '<p>' . esc_html__( 'Enter your Mux API credentials. The token needs Mux Video: Full Access.', 'videomuxr' ) . '</p>';
	}

	/**
	 * Render a text or password settings field.
	 *
	 * @param array<string,string> $args Field arguments (name, type).
	 */
	public function render_text_field( array $args ): void {
		$opts  = self::get_options();
		$name  = esc_attr( $args['name'] );
		$type  = esc_attr( $args['type'] ?? 'text' );
		$value = esc_attr( $opts[ $args['name'] ] ?? '' );

		printf(
			'<input type="%1$s" name="%2$s[%3$s]" value="%4$s" class="regular-text" autocomplete="off" />',
			$type,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped via esc_attr() above.
			esc_attr( self::OPTION_KEY ),
			$name,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped via esc_attr() above.
			$value  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped via esc_attr() above.
		);
	}

	/**
	 * Render the plugin settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'VideoMuxr Settings', 'videomuxr' ); ?></h1>

			<?php if ( ! videomuxr_is_configured() ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Mux credentials are not configured. REST endpoints will return errors until credentials are saved.', 'videomuxr' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'videomuxr_group' );
				do_settings_sections( 'videomuxr' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
