<?php
/**
 * Appearance — share bar configuration for article pages.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Appearance;

defined( 'ABSPATH' ) || exit;

/**
 * Share bar configuration for article pages.
 *
 * The share bar renders at the top of the article body via a the_content filter
 * (priority 10, before Jetpack).  Article layout and theming stay 100 % in the
 * Newspack-Scott theme — no template override, no drift.  The settings page
 * controls share-bar channel/style options only.
 *
 * When you need to restructure the article topper (reorder headline, byline,
 * featured image), THAT is when we add the template override with
 * get_template_part() calls matching the parent theme's template parts.
 * Until then, the theme's own template is authoritative.
 */
class Appearance {

	const SETTINGS_KEY = 'newsroomos_appearance_settings';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return [
			'share_bar' => [
				'enabled'     => true,
				'appearance'  => [
					'style'    => 'monochrome',
					'shape'    => 'pill',
					'grouping' => 'grouped',
				],
				'channels'    => [
					'email'            => true,
					'facebook'         => true,
					'bluesky'          => false,
					'x'                => true,
					'reddit'           => false,
					'threads'          => false,
					'linkedin'         => true,
					'whatsapp'         => false,
					'print'            => true,
					'google_preferred' => true,
				],
				'link_format' => "{headline}\n\n{story_url}",
			],
		];
	}

	/**
	 * Get saved settings merged with defaults.
	 *
	 * Migrates the old per-profile settings shape (1.x experimental builds)
	 * into the flat share_bar shape.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = self::default_settings();
		$saved    = get_option( self::SETTINGS_KEY, [] );
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return $defaults;
		}
		// Handle the old per-profile settings shape from 1.x experimental builds.
		if ( ! empty( $saved['profiles'] ) && is_array( $saved['profiles'] ) ) {
			$key     = ! empty( $saved['active_profile'] ) ? $saved['active_profile'] : 'default';
			$profile = isset( $saved['profiles'][ $key ] ) ? $saved['profiles'][ $key ] : reset( $saved['profiles'] );
			if ( is_array( $profile ) && isset( $profile['share_bar'] ) ) {
				return array_replace_recursive( $defaults, [ 'share_bar' => $profile['share_bar'] ] );
			}
		}
		return array_replace_recursive( $defaults, $saved );
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_filter( 'the_content', [ __CLASS__, 'prepend_share_bar' ], 10 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_front_assets' ] );
	}

	/**
	 * Prepend the share bar to the article content.
	 *
	 * Priority 10 fires before Jetpack sharing (19).  Only affects singular
	 * views in the main query.  The share bar renders at the top of the
	 * .entry-content area, inside the <article> tag.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function prepend_share_bar( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$settings = self::get_settings();
		if ( empty( $settings['share_bar']['enabled'] ) ) {
			return $content;
		}
		return self::render_share_bar( get_the_ID() ) . $content;
	}

	/**
	 * Add Appearance submenu under NewsroomOS.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'newsroomos',
			'Appearance',
			'Appearance',
			'manage_options',
			'newsroomos-appearance',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings with the Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'newsroomos-appearance',
			self::SETTINGS_KEY,
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
				'default'           => self::default_settings(),
			]
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $input Raw POST data.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::default_settings();
		$clean    = $defaults;

		if ( isset( $input['share_bar'] ) && is_array( $input['share_bar'] ) ) {
			$sb       = $input['share_bar'];
			$clean_sb = &$clean['share_bar'];
			if ( isset( $sb['appearance'] ) ) {
				$clean_sb['appearance']['style']    = in_array( $sb['appearance']['style'] ?? '', [ 'monochrome', 'color' ], true ) ? $sb['appearance']['style'] : 'monochrome';
				$clean_sb['appearance']['shape']    = in_array( $sb['appearance']['shape'] ?? '', [ 'pill', 'rounded', 'circle' ], true ) ? $sb['appearance']['shape'] : 'pill';
				$clean_sb['appearance']['grouping'] = in_array( $sb['appearance']['grouping'] ?? '', [ 'separate', 'grouped' ], true ) ? $sb['appearance']['grouping'] : 'grouped';
			}
			if ( isset( $sb['channels'] ) && is_array( $sb['channels'] ) ) {
				foreach ( $clean_sb['channels'] as $ch => $_ ) {
					$clean_sb['channels'][ $ch ] = ! empty( $sb['channels'][ $ch ] );
				}
			}
			$clean_sb['link_format'] = isset( $sb['link_format'] )
				? sanitize_textarea_field( stripslashes( $sb['link_format'] ) )
				: $defaults['share_bar']['link_format'];
		}

		return $clean;
	}

	/**
	 * Render the Appearance settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$settings = self::get_settings();
		$sb       = $settings['share_bar'];
		?>
		<div class="wrap">
			<h1>Appearance</h1>
			Share bar configuration for article pages. The bar renders at the top of the article body via a <code>the_content</code> filter — no template override is needed for share-bar-only changes. Article layout and theming stay 100 % in the Newspack-Scott theme. When you need to restructure topper element order (headline, byline, featured image), that's when we add a template override that delegates to the theme's template parts.
			<form method="post" action="options.php">
				<?php settings_fields( 'newsroomos-appearance' ); ?>

				<h2 class="title">Share Bar — Style &amp; Channels</h2>
				<table class="form-table">
					<tr><th scope="row">Button Style</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][style]">
							<option value="monochrome" <?php selected( $sb['appearance']['style'], 'monochrome' ); ?>>Monochrome</option>
							<option value="color" <?php selected( $sb['appearance']['style'], 'color' ); ?>>Brand Colors</option>
						</select>
					</td></tr>
					<tr><th scope="row">Button Shape</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][shape]">
							<option value="pill" <?php selected( $sb['appearance']['shape'], 'pill' ); ?>>Pill (Axios style)</option>
							<option value="rounded" <?php selected( $sb['appearance']['shape'], 'rounded' ); ?>>Rounded Square</option>
							<option value="circle" <?php selected( $sb['appearance']['shape'], 'circle' ); ?>>Circle</option>
						</select>
					</td></tr>
					<tr><th scope="row">Layout / Grouping</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][grouping]">
							<option value="grouped" <?php selected( $sb['appearance']['grouping'], 'grouped' ); ?>>Group social channels (Axios style)</option>
							<option value="separate" <?php selected( $sb['appearance']['grouping'], 'separate' ); ?>>All buttons separate</option>
						</select>
						<p class="description">"Group social channels" puts Facebook, X, Bluesky, Reddit, Threads, LinkedIn, and WhatsApp inside one pill; Email, Print, and Google Preferred each get their own button.</p>
					</td></tr>
					<tr><th scope="row">Channels</th><td>
						<fieldset><legend class="screen-reader-text">Share Channels</legend>
						<?php foreach ( $sb['channels'] as $ch => $ch_val ) : ?>
							<?php if ( 'google_preferred' === $ch ) : ?>
								<div class="newsroomos-share-channel">
									<label><input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred]" value="1" <?php checked( is_bool( $ch_val ) ? $ch_val : false ); ?>> Google Preferred Source</label>
									<p class="description" style="margin-left:24px">Shows as a styled Deeplink button (<code>coloradosun.com</code>). Links to <code>https://www.google.com/preferences/source?q=coloradosun.com</code> and matches the share bar button style.</p>
								</div>
							<?php else : ?>
								<label class="newsroomos-share-channel"><input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][<?php echo esc_attr( $ch ); ?>]" value="1" <?php checked( is_bool( $ch_val ) ? $ch_val : false ); ?>> <?php echo esc_html( ucfirst( $ch ) ); ?></label>
							<?php endif; ?>
						<?php endforeach; ?>
						</fieldset>
					</td></tr>
					<tr><th scope="row"><label for="newsroomos-link-format">Link Format</label></th><td>
						<textarea id="newsroomos-link-format" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][link_format]" rows="3" class="large-text"><?php echo esc_textarea( $sb['link_format'] ); ?></textarea>
						<p class="description">Placeholders: <code>{headline}</code> <code>{subtitle}</code> <code>{featured_image_url}</code> <code>{story_url}</code>.</p>
					</td></tr>
				</table>

				<?php submit_button( 'Save Appearance Settings' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin styles for the Appearance settings page.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'newsroomos_page_newsroomos-appearance' !== $hook ) {
			return;
		}
		wp_add_inline_style(
			'wp-admin',
			implode(
				'',
				[
					'.newsroomos-share-channel{display:block;margin-bottom:6px}',
				]
			)
		);
	}

	/**
	 * Enqueue share-bar companion CSS on the front end.
	 *
	 * Fires during <head> rendering, so the inline style is output in the
	 * proper order relative to the theme stylesheet (newspack-theme-style).
	 *
	 * @return void
	 */
	public static function enqueue_front_assets() {
		if ( ! is_singular() ) {
			return;
		}
		$settings = self::get_settings();
		if ( empty( $settings['share_bar']['enabled'] ) ) {
			return;
		}
		$app      = $settings['share_bar']['appearance'];
		$border_r = 'pill' === $app['shape'] ? '999px' : ( 'circle' === $app['shape'] ? '50%' : '4px' );
		$btn_sel  = '.newsroomos-share-bar.newsroomos-shape-' . $app['shape'] . ' .newsroomos-share-btn';
		$css      = $btn_sel . '{display:inline-flex;align-items:center;padding:6px 14px;margin:2px;border:1px solid #d0d0d0;border-radius:' . $border_r . ';background:#fff;color:#111;text-decoration:none;font-size:13px;line-height:1.4;transition:background .15s,border-color .15s}';
		$css     .= $btn_sel . ':hover{background:#f5f5f5;border-color:#bbb;color:#111}';
		$css     .= '.newsroomos-share-cluster{' . ( 'circle' === $app['shape'] ? '' : 'border:1px solid #d0d0d0;border-radius:' . $border_r . ';padding:2px 4px;' ) . 'display:inline-flex;align-items:center}';
		$css     .= '.newsroomos-share-cluster .newsroomos-share-btn{border:none;background:transparent;margin:0 1px;padding:4px 8px}';
		$css     .= '.newsroomos-share-cluster .newsroomos-share-btn:hover{background:#f0f0f0;border:none}';

		if ( 'color' === $app['style'] ) {
			$brands = [
				'facebook' => '#1877F2',
				'x'        => '#000',
				'bluesky'  => '#0085FF',
				'threads'  => '#000',
				'linkedin' => '#0A66C2',
				'reddit'   => '#FF4500',
				'whatsapp' => '#25D366',
				'email'    => '#666',
				'print'    => '#666',
			];
			foreach ( $brands as $ch => $color ) {
				$css .= '.newsroomos-share-btn.newsroomos-' . $ch . '{color:' . $color . ';border-color:' . $color . '}';
				$css .= '.newsroomos-share-btn.newsroomos-' . $ch . ':hover{background:' . $color . ';color:#fff;border-color:' . $color . '}';
			}
		}

		wp_add_inline_style( 'newspack-theme-style', $css );
	}

	/**
	 * Render the share bar with Axios-style appearance options.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function render_share_bar( $post_id ) {
		$settings = self::get_settings();
		$channels = $settings['share_bar']['channels'];
		$app      = $settings['share_bar']['appearance'];
		$url      = rawurlencode( get_permalink( $post_id ) );
		$title    = rawurlencode( get_the_title( $post_id ) );
		$sub_meta = get_post_meta( $post_id, 'article_subtitle', true );
		$img_url  = has_post_thumbnail( $post_id ) ? wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ) : '';
		$link_fmt = str_replace(
			[ '{headline}', '{subtitle}', '{featured_image_url}', '{story_url}' ],
			[ get_the_title( $post_id ), ! empty( $sub_meta ) ? $sub_meta : '', ! empty( $img_url ) ? $img_url : '', get_permalink( $post_id ) ],
			$settings['share_bar']['link_format']
		);

		$shape_cls = 'newsroomos-shape-' . $app['shape'];

		$social_channels = [
			'facebook' => [ 'https://www.facebook.com/sharer.php?u=' . $url, 'Facebook' ],
			'x'        => [ 'https://twitter.com/intent/tweet?text=' . rawurlencode( $link_fmt ), 'X' ],
			'bluesky'  => [ 'https://bsky.app/intent/compose?text=' . rawurlencode( $link_fmt ), 'Bluesky' ],
			'threads'  => [ 'https://threads.net/intent/post?text=' . rawurlencode( $link_fmt ), 'Threads' ],
			'linkedin' => [ 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url, 'LinkedIn' ],
			'reddit'   => [ 'https://reddit.com/submit?url=' . $url . '&title=' . $title, 'Reddit' ],
			'whatsapp' => [ 'https://api.whatsapp.com/send?text=' . rawurlencode( $link_fmt ), 'WhatsApp' ],
		];
		$standalone_channels = [
			'email' => [ 'mailto:?body=' . rawurlencode( $link_fmt ), 'Email' ],
			'print' => [ 'javascript:window.print()', 'Print' ],
		];

		$html = '<div class="newsroomos-share-bar ' . esc_attr( $shape_cls ) . '">';

		foreach ( $standalone_channels as $ch => $cfg ) {
			if ( empty( $channels[ $ch ] ) ) {
				continue;
			}
			$html .= sprintf(
				'<a href="%s" class="newsroomos-share-btn newsroomos-standalone">%s</a> ',
				esc_url( $cfg[0] ),
				esc_html( $cfg[1] )
			);
		}

		if ( ! empty( $channels['google_preferred'] ) ) {
			$gp_url = 'https://www.google.com/preferences/source?q=coloradosun.com';
			$html  .= sprintf(
				'<a href="%s" target="_blank" rel="noopener" class="newsroomos-share-btn newsroomos-standalone">%s</a> ',
				esc_url( $gp_url ),
				'Add Colorado Sun on Google'
			);
		}

		$has_social  = false;
		$social_html = '';
		foreach ( $social_channels as $ch => $cfg ) {
			if ( empty( $channels[ $ch ] ) ) {
				continue;
			}
			$has_social   = true;
			$social_html .= sprintf(
				'<a href="%s" target="_blank" rel="noopener" class="newsroomos-share-btn newsroomos-social">%s</a> ',
				esc_url( $cfg[0] ),
				esc_html( $cfg[1] )
			);
		}
		if ( $has_social ) {
			if ( 'grouped' === $app['grouping'] ) {
				$html .= '<span class="newsroomos-share-cluster">' . $social_html . '</span> ';
			} else {
				$html .= $social_html;
			}
		}

		$html .= '</div>';

		return $html;
	}
}