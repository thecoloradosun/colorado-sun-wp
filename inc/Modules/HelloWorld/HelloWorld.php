<?php
/**
 * Hello World — a placeholder module to confirm the module system is working.
 *
 * Adds a dashboard widget and an admin bar notice so you can visually verify
 * the module is loaded and active.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\HelloWorld;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder module for developer onboarding verification.
 */
class HelloWorld {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_bar_menu', [ __CLASS__, 'add_admin_notice' ], 999 );
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_dashboard_widget' ] );
	}

	/**
	 * Adds a notice to the admin bar confirming the module is active.
	 *
	 * @param object $wp_admin_bar The Admin bar object.
	 * @return void
	 */
	public static function add_admin_notice( $wp_admin_bar ) {
		$args = array(
			'id'    => 'hello_world_info',
			'title' => 'Hello World module is enabled',
		);
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Adds a dashboard widget with placeholder content.
	 *
	 * @return void
	 */
	public static function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'hello_world_dashboard',
			'Hello World',
			[ __CLASS__, 'render_dashboard_widget' ]
		);
	}

	/**
	 * Render the dashboard widget content.
	 *
	 * @return void
	 */
	public static function render_dashboard_widget() {
		?>
		<p>Hello World — this custom module is working.</p>
		<p>Use this as a starting point for building your own modules.</p>
		<?php
	}
}