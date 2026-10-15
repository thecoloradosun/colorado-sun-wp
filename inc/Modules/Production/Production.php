<?php
/**
 * Production — streamline the staff admin experience.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Production;

defined( 'ABSPATH' ) || exit;

/**
 * Settings and hooks for the staff-facing admin experience.
 */
class Production {

	/**
	 * Hook into admin.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
	}

	/**
	 * Register the Production submenu page under NewsroomOS.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'newsroomos',
			'Production',
			'Production',
			'manage_options',
			'newsroomos-production',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Render the Production settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Production</h1>
			<p>Customize the staff admin — hide unused meta boxes, streamline the dashboard and media library.</p>

			<h2>Dashboard</h2>
			<p>Coming soon: custom dashboard widget set, hide welcome panel and unwanted widgets.</p>

			<h2>Post Edit Screen</h2>
			<p>Coming soon: hide specific meta boxes, reorder the editor layout, simplify the publish metabox.</p>

			<h2>Media Library</h2>
			<p>Coming soon: shortcuts for common media operations, default view mode.</p>
		</div>
		<?php
	}
}