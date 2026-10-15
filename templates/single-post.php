<?php
/**
 * Single Post Template — NewsroomOS Appearance
 *
 * Replaces the theme's single.php when the Appearance module is active.
 * Renders header elements in the order configured at NewsroomOS → Appearance.
 * Preserves the theme's header and footer via get_header() / get_footer().
 *
 * @package NewsroomOS
 */

use NewsroomOS\Modules\Appearance\Appearance;

get_header();

$settings    = Appearance::get_settings();
$current_ct  = get_post_type();
$is_page     = 'page' === $current_ct;
$tpl_key     = $is_page ? 'page' : ( 'post' === $current_ct ? 'single' : 'single-post' );
$layout      = $settings['layout']['templates'][ $tpl_key ] ?? $settings['layout']['templates']['single'];
$feat_pos    = $layout['featured_image_position'];
?>

<main id="main" class="site-main" role="main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php
			// Determine featured image and shared bar positions from element order.
			$before_parts  = [];
			$after_parts   = [];
			$feat_in_order = false;
			$found_feat    = false;

			foreach ( $settings['header_elements'] as $el ) {
				if ( empty( $el['enabled'] ) ) {
					continue;
				}
				if ( 'featured_image' === $el['id'] ) {
					if ( 'hidden' !== $feat_pos && has_post_thumbnail() ) {
						$feat_in_order = true;
					}
					continue;
				}
				$html = Appearance::render_single_element( $el, get_the_ID() );
				if ( 'between_headline_body' === $feat_pos ) {
					if ( $found_feat ) {
						$after_parts[] = $html;
					} else {
						$before_parts[] = $html;
					}
				} else {
					$before_parts[] = $html;
				}
			}

			$feat_html = '';
			if ( 'hidden' !== $feat_pos && has_post_thumbnail() ) {
				$feat_html = '<div class="newsroomos-element newsroomos-featured-image post-thumbnail">' . get_the_post_thumbnail( get_the_ID(), 'full' ) . '</div>';
			}

			if ( 'between_headline_body' === $feat_pos ) {
				echo implode( '', $before_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $feat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( '', $after_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo $feat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( '', $before_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<?php
			// Share bar at end of article if it's not placed in the header.
			$share_in_header = false;
			foreach ( $settings['header_elements'] as $el ) {
				if ( 'share_bar' === $el['id'] && ! empty( $el['enabled'] ) ) {
					$share_in_header = true;
					break;
				}
			}
			if ( ! $share_in_header ) {
				echo Appearance::render_share_bar( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();