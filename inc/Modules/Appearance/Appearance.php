<?php
/**
 * Appearance — configure article layout, template elements, and share bar.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Appearance;

defined( 'ABSPATH' ) || exit;

/**
 * Settings and template hooks for the user-facing article layout.
 */
class Appearance {

	/**
	 * Hook into admin and front-end.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
	}

	/**
	 * Register the Appearance submenu page under NewsroomOS.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'newsroomos',
			'Appearance',
			'Appearance',
			'manage_options',
			'newsroomos-appearance',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Render the Appearance settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Appearance</h1>
			<p>Configure the article layout and user-facing template elements.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'newsroomos-appearance' ); ?>
				<?php do_settings_sections( 'newsroomos-appearance' ); ?>
				<?php submit_button(); ?>
			</form>

			<hr>

			<h2>Article Header</h2>
			<p>Coming soon: toggle visibility of headline, byline, subtitle, and featured image; reorder elements; configure date format and byline style.</p>

			<h2>Share Bar</h2>
			<p>Coming soon: enable automatic share bar with configurable channels (Facebook, Twitter/X, Google Preferred source button) and placement.</p>

			<h2>Layout</h2>
			<p>Coming soon: featured image position, content width, sidebar options, and block-anchored sections.</p>
		</div>
		<?php
	}
}