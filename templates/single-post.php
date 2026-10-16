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

// Use fully-qualified namespace to avoid autoloader timing issues.
use NewsroomOS\Modules\Appearance\Appearance;

printf( '<!-- NewsroomOS Appearance template active -->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
get_header();

$profile    = Appearance::get_active_profile();
$current_ct = get_post_type();
$is_page    = 'page' === $current_ct;
$tpl_key    = $is_page ? 'page' : ( 'post' === $current_ct ? 'single' : 'single-post' );
$layout     = $profile['layout']['templates'][ $tpl_key ] ?? $profile['layout']['templates']['single'];
$feat_pos   = $layout['featured_image_position'];
?>

<main id="main" class="site-main" role="main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php
			$feat_html = '';
			if ( 'hidden' !== $feat_pos && has_post_thumbnail() ) {
				$feat_html = '<div class="newsroomos-element newsroomos-featured-image post-thumbnail">' . get_the_post_thumbnail( get_the_ID(), 'full' ) . '</div>';
			}

			if ( 'between_headline_body' === $feat_pos ) {
				// Split before/after at the featured image position in the element list.
				$before_parts = [];
				$after_parts  = [];
				$past_feat    = false;
				foreach ( $profile['header_elements'] as $el ) {
					if ( empty( $el['enabled'] ) ) {
						continue;
					}
					if ( 'featured_image' === $el['id'] ) {
						$past_feat = true;
						continue;
					}
					$html = Appearance::render_single_element( $el, get_the_ID() );
					if ( $past_feat ) {
						$after_parts[] = $html;
					} else {
						$before_parts[] = $html;
					}
				}
				echo implode( '', $before_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $feat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo implode( '', $after_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				$all_parts = [];
				foreach ( $profile['header_elements'] as $el ) {
					if ( empty( $el['enabled'] ) || 'featured_image' === $el['id'] ) {
						continue;
					}
					$all_parts[] = Appearance::render_single_element( $el, get_the_ID() );
				}
				if ( 'above_headline' === $feat_pos ) {
					echo $feat_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo implode( '', $all_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<?php
			// If share_bar is not in the header elements, append it after the body.
			$share_in_header = false;
			foreach ( $profile['header_elements'] as $el ) {
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