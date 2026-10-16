<?php
/**
 * Single Post Template — NewsroomOS Appearance
 *
 * WHAT THIS IS
 * ------------
 * This file replaces the theme's single.php for any single-post page.
 * The WordPress filter `template_include` intercepts template loading and
 * redirects to this file instead. See Appearance.php → override_template().
 *
 * WHY IT EXISTS
 * -------------
 * The theme's article topper (headline, byline, featured image, share bar
 * order) is controlled HERE.  You edit this PHP file to rearrange, add,
 * or remove elements.  No settings-page UI or drag-and-drop — the source
 * IS the interface.
 *
 * HOW TO MODIFY
 * -------------
 * Each topper section below is wrapped in a large labeled comment block.
 * To reorder: cut an entire block and paste it elsewhere.
 * To remove: delete the whole block.
 * To add something: copy the pattern and insert it.
 * Save, deploy, hard-refresh the article page.
 *
 * STOCK BEHAVIOUR
 * ---------------
 * This file is written to produce output IDENTICAL to the stock
 * Newspack-Scott theme by default.  Until you move or delete a block,
 * an article page looks exactly as it did before the plugin existed.
 *
 * @package NewsroomOS
 */

use NewsroomOS\Modules\Appearance\Appearance;

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php
			/*********************************************************************
			 *  BLOCK: FEATURED IMAGE  (post-thumbnail)
			 *
			 *  Stock position: very first thing inside the <article>.
			 *  Move this whole PHP block (below) above or below any other block
			 *  to change where the featured image appears.
			 *
			 *  Delete this block entirely to hide the featured image on every
			 *  post (the theme's default is restored automatically since there
			 *  is no plugin CSS hiding anything).
			 *********************************************************************/
			if ( has_post_thumbnail() ) :
				?>
				<figure class="post-thumbnail">
					<?php the_post_thumbnail( 'full' ); ?>
				</figure>
				<?php
			endif;
			?>

			<?php
			/*********************************************************************
			 *  BLOCK: ENTRY HEADER  (category — title — subtitle — byline)
			 *
			 *  Stock order inside this block:
			 *     1. Primary Category link
			 *     2. Headline (<h1 class="entry-title">)
			 *     3. Subtitle / dek (custom meta, from Story Ingest)
			 *     4. Byline (author name + date)
			 *
			 *  Each sub-block is independently movable.  Cut one and paste it
			 *  outside this <header> wrapper to place it above/below other blocks.
			 *
			 *  Delete a sub-block to hide that element from every article.
			 *********************************************************************/
			?>
			<header class="entry-header">

				<?php
				// --- Sub-block: Primary Category ---
				$appearance_cats = get_the_category();
				if ( ! empty( $appearance_cats ) && ! is_wp_error( $appearance_cats ) ) :
					?>
					<div class="entry-category">
						<a href="<?php echo esc_url( get_category_link( $appearance_cats[0]->term_id ) ); ?>">
							<?php echo esc_html( $appearance_cats[0]->name ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php
				// --- Sub-block: Headline ---
				?>
				<h1 class="entry-title"><?php the_title(); ?></h1>

				<?php
				// --- Sub-block: Subtitle / dek ---
				// Meta key is 'article_subtitle'; change or remove as needed.
				$appearance_subtitle = get_post_meta( get_the_ID(), 'article_subtitle', true );
				if ( $appearance_subtitle ) :
					?>
					<p class="entry-subtitle"><?php echo esc_html( $appearance_subtitle ); ?></p>
				<?php endif; ?>

				<?php
				// --- Sub-block: Byline (author + date) ---
				$appearance_author = get_userdata( (int) get_post_field( 'post_author', get_the_ID() ) );
				?>
				<div class="entry-byline">
					<?php if ( $appearance_author ) : ?>
						<span class="author vcard">
							<span class="fn"><?php echo esc_html( $appearance_author->display_name ); ?></span>
						</span>
					<?php endif; ?>
					<span class="posted-on">
						<time class="entry-date published" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
					</span>
				</div>

			</header>

			<?php
			/*********************************************************************
			 *  BLOCK: SHARE BAR  (NewsroomOS Appearance)
			 *
			 *  Renders the configurable share bar from your Appearance settings
			 *  (style, channels, grouping).  The bar's visual options are set
			 *  in the WordPress admin under NewsroomOS → Appearance.
			 *
			 *  Move this block to place the share bar elsewhere
			 *  (above the featured image, inside entry-header, after the body…).
			 *
			 *  Delete this block entirely to remove the share bar while keeping
			 *  all other Appearance features intact.
			 *********************************************************************/
			if ( class_exists( '\NewsroomOS\Modules\Appearance\Appearance' ) ) :
				echo \NewsroomOS\Modules\Appearance\Appearance::render_share_bar( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endif;
			?>

			<?php
			/*********************************************************************
			 *  BLOCK: ARTICLE BODY  (the_content)
			 *
			 *  REQUIRED — keep this block once.  The body content, embedded media,
			 *  Jetpack sharing (if enabled), and the password form for protected
			 *  posts all render here.  WordPress depends on the_content() being
			 *  called in the main loop.
			 *
			 *  Do not move below the </article> closing tag.
			 *********************************************************************/
			?>
			<div class="entry-content">
				<?php
				the_content(
					sprintf(
						/* translators: %s: Post title. */
						wp_kses_post( __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'newsroomos' ) ),
						wp_kses_post( get_the_title() )
					)
				);
				wp_link_pages(
					[
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'newsroomos' ),
						'after'  => '</div>',
					]
				);
				?>
			</div>

			<?php
			/*********************************************************************
			 *  FOOTER AREA (optional)
			 *
			 *  WordPress post navigation, comments, related posts by the theme
			 *  would normally go here.  The stock theme may render these via
			 *  template-parts/ includes after the_content.  Add them here or
			 *  in a child theme when needed.
			 *
			 *  For the MVP the post nav / comments are not rendered — they will
			 *  render on the theme if desired.
			 *********************************************************************/
			?>

		</article>

	<?php endwhile; ?>
	</main>
</div>

<?php
get_footer();