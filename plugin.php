<?php
/**
 * Plugin Name: Colorado Sun custom plugin
 * Plugin URI: https://github.com/thecoloradosun/colorado-sun-wp
 * Description: One plugin to rule them all.
 * Version: 1.0.0
 * Author: Colorado Sun
 * Author URI: https://coloradosun.com
 * Text Domain: colorado-sun
 *
 * For more information on WordPress plugin headers, see the following page:
 * https://developer.wordpress.org/plugins/plugin-basics/header-requirements/
 *
 * @package ColoradoSun
 */

// Ensure that everything is namespaced.
namespace ColoradoSun;

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
				<p><?php esc_html_e( 'Colorado Sun plugin was not properly built.', 'colorado-sun' ); ?></p>
			</div>
			<?php
		}
	);
	return;
}

// Initialize the plugin.

Module_Loader::init();
