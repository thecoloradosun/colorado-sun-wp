<?php
/**
 * Blocks & Patterns — custom Gutenberg blocks and reusable patterns.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\BlocksPatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Registers custom Gutenberg blocks and reusable patterns.
 */
class BlocksPatterns {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
	}

	/**
	 * Register the Blocks & Patterns submenu page.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'newsroomos',
			'Blocks &amp; Patterns',
			'Blocks &amp; Patterns',
			'manage_options',
			'newsroomos-blocks',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Render the Blocks & Patterns settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Blocks &amp; Patterns</h1>
			<p>Enable and configure custom Gutenberg block types and reusable patterns.</p>
		</div>
		<?php
	}
}