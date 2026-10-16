<?php
/**
 * Single Post Template — NewsroomOS Appearance
 *
 * ============================================================================
 * STATUS: FUTURE-USE / NOT ACTIVE
 * ============================================================================
 * This template is ready but NOT active.  The share bar is currently handled
 * by a the_content filter (Appearance::prepend_share_bar) that works without
 * touching templates at all.  The theme renders its own template perfectly.
 *
 * TO ACTIVATE THIS TEMPLATE:
 *   1. Uncomment the template_redirect hook in Appearance::init()
 *   2. The share bar below will render instead of the the_content filter
 *   3. Now you can REORDER the topper blocks below by cutting and pasting
 *
 * HOW IT WORKS
 * ------------
 * This template delegates its renderering to the PARENT theme's template
 * parts (get_template_part), so the output matches the stock theme exactly
 * until you move blocks around inside this file.  Each topper element is a
 * labeled block — featured image + entry header, article body, share bar.
 * Move the blocks to reorder the topper.
 *
 * @package NewsroomOS
 */

use NewsroomOS\Modules\Appearance\Appearance;

get_header();
?>
	<section id="primary" class="content-area <?php echo esc_attr( newspack_get_category_tag_classes( get_the_ID() ) ); ?>">
		<main id="main" class="site-main">

			<?php
			while ( have_posts() ) :
				the_post();

				/******************************************************************
				 *  BLOCK: TOPPER  (featured image + entry header)
				 *
				 *  Delegates to the parent theme's template parts which handle
				 *  all featured-image-position settings (large, behind, beside,
				 *  above, small, default).  The output is IDENTICAL to the
				 *  stock theme.  Replace this block with custom HTML when you
				 *  want to hardcode a specific element order.
				 */
				if ( in_array( newspack_featured_image_position(), array( 'large', 'behind', 'beside', 'above' ), true ) ) :
					get_template_part( 'template-parts/post/large-featured-image' );
				else :
					?>
					<header class="entry-header">
						<?php get_template_part( 'template-parts/header/entry', 'header' ); ?>
					</header>
				<?php endif; ?>

				<div class="main-content">

					<?php
					if ( is_active_sidebar( 'article-1' ) && is_single() ) :
						dynamic_sidebar( 'article-1' );
					endif;

					if ( 'small' === newspack_featured_image_position() ) :
						newspack_post_thumbnail( 'newspack-featured-image-small' );
					endif;

					/******************************************************************
					 *  BLOCK: ARTICLE BODY
					 *
					 *  Delegates to content-single.php which renders:
					 *    <article> → entry-content (the_content) → entry-footer → author-bio
					 *
					 *  Replace this block with custom content when needed, but keep
					 *  the_content() call once inside an entry-content wrapper.
					 */
					if ( is_page() ) :
						get_template_part( 'template-parts/content/content', 'page' );
					else :
						get_template_part( 'template-parts/content/content', 'single' );
					endif;

					/******************************************************************
					 *  BLOCK: SHARE BAR  (NewsroomOS Appearance)
					 *
					 *  Stock position: inside .main-content, after the article body.
					 *  Move this block above the "ARTICLE BODY" block to place it
					 *  BETWEEN the topper and the text.
					 *
					 *  NOTE: content-single.php opens <article> and closes it.  To
					 *  place the share bar INSIDE <article> (between the topper
					 *  section's header/image and the entry-content), you must move
					 *  this block BEFORE the get_template_part() call above so it
					 *  renders before the <article> opens (then style it to position
					 *  visually inside).  For true inside-<article> placement with
					 *  proper nesting, inline a copy of content-single.php here.
					 */
					if ( class_exists( '\NewsroomOS\Modules\Appearance\Appearance' ) ) :
						echo \NewsroomOS\Modules\Appearance\Appearance::render_share_bar( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endif;

					newspack_previous_next();

					if ( comments_open() || get_comments_number() ) {
						newspack_comments_template();
					}
					?>

				</div><!-- .main-content -->

			<?php endwhile; ?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();