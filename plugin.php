<?php
/**
 * Plugin Name: NewsroomOS
 * Plugin URI: https://github.com/thecoloradosun/colorado-sun-wp
 * Description: The NewsroomOS platform plugin for The Colorado Sun's WordPress site. Modules add newsroom tooling on top of the site.
 * Version: 1.0.0
 * Author: Colorado Sun
 * Author URI: https://coloradosun.com
 * Text Domain: newsroomos
 *
 * For more information on WordPress plugin headers, see the following page:
 * https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
 *
 * @package NewsroomOS
 */

// Ensure that everything is namespaced.
namespace NewsroomOS;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// This will load composer's autoload.
// Keep in mind that you need to run `composer dump-autoload` after adding new classes.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Add admin notice in case the plugin has not been built.
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'NewsroomOS plugin was not properly built.', 'newsroomos' ); ?></p>
			</div>
			<?php
		}
	);
	return;
}

// Initialize the plugin.

Module_Loader::init();