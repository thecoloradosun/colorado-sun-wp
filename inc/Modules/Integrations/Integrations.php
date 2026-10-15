<?php
/**
 * Integrations — API connections for Google Docs, webhooks, and external services.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Manages external API connections (Google Docs, webhook endpoints, credentials).
 */
class Integrations {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
	}

	/**
	 * Register the Integrations submenu page under NewsroomOS.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'newsroomos',
			'Integrations',
			'Integrations',
			'manage_options',
			'newsroomos-integrations',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Render the Integrations settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1>Integrations</h1>
			<p>Configure external API connections: Google Docs → WP draft creation, webhook endpoints, and credential management.</p>

			<h2>Google Docs Connector</h2>
			<p>Coming soon: OAuth setup, webhook endpoint URL, and document→post mapping rules.</p>

			<h2>Webhook Endpoints</h2>
			<p>Coming soon: custom REST API routes for NewsroomOS story planning integration.</p>

			<h2>Credentials</h2>
			<p>Coming soon: store and manage API keys for external services.</p>
		</div>
		<?php
	}
}