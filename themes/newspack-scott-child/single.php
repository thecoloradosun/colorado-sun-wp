<?php
/**
 * Single Post Template — NewsroomOS Scott Child
 *
 * Starting point for article-topper experiments. Blocks are in the stock
 * order (matching what the site renders today). To reorder the topper,
 * MOVE the labeled blocks below ($newsroomos_featured_image through
 * $newsroomos_share_bar) — each is one self-contained feature block.
 *
 * @package NewsroomOS
 */

defined( 'ABSPATH' ) || exit;

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
			// =============================================================
			// BLOCK: Featured image (post-thumbnail)
			// Move this block to place the image relative to other topper
			// elements. Stock position: above the entry header.
			// =============================================================
			if ( has_post_thumbnail() ) :
				?>
				<figure class="post-thumbnail">
					<?php the_post_thumbnail( 'full' ); ?>
				</figure>
				<?php
			endif;

			// =============================================================
			// BLOCK: Entry header — category, headline, subtitle, byline.
			// Each element inside is independently movable.
			// =============================================================
			?>
			<header class="entry-header">

				<?php
				// -- Category -------------------------------------------------
				$newsroomos_cats = get_the_category();
				if ( ! empty( $newsroomos_cats ) && ! is_wp_error( $newsroomos_cats ) ) :
					?>
					<div class="entry-category">
						<a href="<?php echo esc_url( get_category_link( $newsroomos_cats[0]->term_id ) ); ?>">
							<?php echo esc_html( $newsroomos_cats[0]->name ); ?>
						</a>
					</div>
				<?php endif; ?>

				<?php
				// -- Headline -------------------------------------------------
				// the_title() outputs escaped by default when $echo = true.
				?>
				<h1 class="entry-title"><?php the_title(); ?></h1>

				<?php
				// -- Subtitle / dek (custom meta, populated by Story Ingest) --
				$newsroomos_subtitle = get_post_meta( get_the_ID(), 'article_subtitle', true );
				if ( $newsroomos_subtitle ) :
					?>
					<p class="entry-subtitle"><?php echo esc_html( $newsroomos_subtitle ); ?></p>
				<?php endif; ?>

				<?php
				// -- Byline ---------------------------------------------------
				$newsroomos_author = get_userdata( (int) get_post_field( 'post_author', get_the_ID() ) );
				?>
				<div class="entry-byline">
					<?php if ( $newsroomos_author ) : ?>
						<span class="author vcard">
							<span class="fn"><?php echo esc_html( $newsroomos_author->display_name ); ?></span>
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
			// =============================================================
			// BLOCK: Share bar (NewsroomOS Appearance module)
			// Renders the plugin's configurable share bar (channels, style,
			// grouping). Remove this block to relocate the bar elsewhere,
			// e.g. above the headline or below the content.
			// =============================================================
			if ( class_exists( '\NewsroomOS\Modules\Appearance\Appearance' ) ) :
				echo \NewsroomOS\Modules\Appearance\Appearance::render_share_bar( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endif;

			// =============================================================
			// BLOCK: Article body. Keep the_content() once — the theme's
			// content renderer and page builders depend on it.
			// =============================================================
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

		</article>

	<?php endwhile; ?>

	</main>
</div>

<?php
get_footer();