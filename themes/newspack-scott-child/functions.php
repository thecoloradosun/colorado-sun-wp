<?php
/**
 * NewsroomOS Scott Child — functions.
 *
 * Enqueues parent (newspack-scott) and child styles. Child template files
 * (single.php, etc.) placed in this theme's root override the parent's.
 *
 * @package NewsroomOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue parent + child stylesheets.
 *
 * @return void
 */
function newsroomos_scott_child_enqueue_styles() {
	$parent = 'newspack-scott-style';
	wp_enqueue_style( $parent, get_template_directory_uri() . '/style.css', [], wp_get_theme( 'newspack-scott' )->get( 'Version' ) );
	wp_enqueue_style( 'newsroomos-scott-child', get_stylesheet_directory_uri() . '/style.css', [ $parent ], '0.1.0' );
}
add_action( 'wp_enqueue_scripts', 'newsroomos_scott_child_enqueue_styles' );

/**
 * EXPERIMENTATION WORKBENCH
 * ------------------------
 * Quick-layout experiments go here (or in this theme's template files) so the
 * plugin repo deploys stay untouched. Examples:
 *
 * 1. To reorder the article topper: add `templates/single-post.php`-style
 *    markup in `single.php` in THIS theme root (copy of parent's, edited).
 *
 * 2. To call plugin rendering (share bar) from theme markup instead of the
 *    plugin's the_content filter:
 *    if ( class_exists( '\NewsroomOS\Modules\Appearance\Appearance' ) ) {
 *        echo \NewsroomOS\Modules\Appearance\Appearance::render_share_bar( get_the_ID() );
 *    }
 *
 * 3. To move the share bar out of the_content: tell the plugin to stop via
 *    remove_filter( 'the_content', [ '\NewsroomOS\Modules\Appearance\Appearance', 'append_share_bar' ], 19 );
 *
 * Until the plugin's Appearance module exposes per-template topper control,
 * the child theme is the right place for placement experiments.
 */
