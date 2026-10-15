<?php
/**
 * Appearance — configure article layout, template elements, share bar, and per-element CSS.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Appearance;

defined( 'ABSPATH' ) || exit;

/**
 * Settings and template hooks for the user-facing article layout.
 */
class Appearance {

	const SETTINGS_KEY = 'newsroomos_appearance_settings';

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return [
			'header_elements' => [
				[
					'id'           => 'category',
					'label'        => 'Primary Category',
					'css_selector' => '.entry-category',
					'enabled'      => true,
					'custom_css'   => '',
				],
				[
					'id'           => 'headline',
					'label'        => 'Headline',
					'css_selector' => '.entry-title',
					'enabled'      => true,
					'custom_css'   => '',
				],
				[
					'id'           => 'subtitle',
					'label'        => 'Subtitle / Dek',
					'css_selector' => '.entry-subtitle',
					'enabled'      => true,
					'custom_css'   => '',
				],
				[
					'id'           => 'byline',
					'label'        => 'Byline',
					'css_selector' => '.entry-byline',
					'enabled'      => true,
					'custom_css'   => '',
				],
				[
					'id'           => 'share_bar',
					'label'        => 'Share Bar',
					'css_selector' => '.newsroomos-share-bar',
					'enabled'      => true,
					'custom_css'   => '',
				],
				[
					'id'           => 'featured_image',
					'label'        => 'Featured Image',
					'css_selector' => '.post-thumbnail',
					'enabled'      => true,
					'custom_css'   => '',
				],
			],
			'share_bar'       => [
				'enabled'     => true,
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
					'google_preferred' => [
						'enabled' => false,
						'theme'   => 'light',
						'lang'    => '',
					],
				],
				'link_format' => "{headline}\n\n{story_url}",
			],
			'layout'          => [
				'templates' => [
					'single'      => [
						'label'                   => 'Default Post',
						'featured_image_position' => 'above_headline',
					],
					'single-post' => [
						'label'                   => 'Post (CPT)',
						'featured_image_position' => 'above_headline',
					],
					'page'        => [
						'label'                   => 'Page',
						'featured_image_position' => 'above_headline',
					],
				],
			],
		];
	}

	/**
	 * Get saved settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::SETTINGS_KEY, [] );
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return self::default_settings();
		}
		return array_replace_recursive( self::default_settings(), $saved );
	}

	/**
	 * Hook into WordPress — admin menus, settings, assets, and front-end filters.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_filter( 'the_content', [ __CLASS__, 'filter_the_content' ], 1 );
		add_filter( 'post_thumbnail_html', [ __CLASS__, 'filter_post_thumbnail' ], 10, 2 );
		add_action( 'wp_head', [ __CLASS__, 'output_custom_css' ], 99 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_front_assets' ], 99 );
	}

	/**
	 * Register the Appearance submenu page under NewsroomOS.
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
	 * Register settings with the WordPress Settings API.
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
		add_settings_section(
			'newsroomos-appearance-header',
			'Article Header',
			'__return_empty_string',
			'newsroomos-appearance'
		);
		add_settings_section(
			'newsroomos-appearance-share',
			'Share Bar',
			'__return_empty_string',
			'newsroomos-appearance'
		);
		add_settings_section(
			'newsroomos-appearance-layout',
			'Layout',
			'__return_empty_string',
			'newsroomos-appearance'
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

		if ( isset( $input['header_order'] ) && is_array( $input['header_order'] ) ) {
			$clean['header_elements'] = [];
			$enabled    = isset( $input['header_enabled'] ) ? (array) $input['header_enabled'] : [];
			$custom_css = isset( $input['header_css'] ) ? (array) $input['header_css'] : [];
			$lookup     = [];
			foreach ( $defaults['header_elements'] as $el ) {
				$lookup[ $el['id'] ] = $el;
			}
			foreach ( $input['header_order'] as $id ) {
				$id = sanitize_key( $id );
				if ( ! isset( $lookup[ $id ] ) ) {
					continue;
				}
				$el               = $lookup[ $id ];
				$el['enabled']    = ! empty( $enabled[ $id ] );
				$el['custom_css'] = isset( $custom_css[ $id ] ) ? sanitize_textarea_field( $custom_css[ $id ] ) : '';
				$clean['header_elements'][] = $el;
			}
		}

		if ( isset( $input['share_bar'] ) && is_array( $input['share_bar'] ) ) {
			$sb       = $input['share_bar'];
			$clean_sb = &$clean['share_bar'];
			if ( isset( $sb['channels'] ) && is_array( $sb['channels'] ) ) {
				foreach ( $clean_sb['channels'] as $ch => $default_val ) {
					if ( 'google_preferred' === $ch ) {
						$gp = $sb['channels']['google_preferred'] ?? [];
						$clean_sb['channels']['google_preferred']['enabled'] = ! empty( $gp['enabled'] );
						$clean_sb['channels']['google_preferred']['theme']   = in_array( $gp['theme'] ?? '', [ 'light', 'dark' ], true )
							? $gp['theme'] : 'light';
						$clean_sb['channels']['google_preferred']['lang']    = sanitize_text_field( $gp['lang'] ?? '' );
					} else {
						$clean_sb['channels'][ $ch ] = ! empty( $sb['channels'][ $ch ] );
					}
				}
			}
			$clean_sb['link_format'] = isset( $sb['link_format'] )
				? sanitize_textarea_field( stripslashes( $sb['link_format'] ) )
				: $defaults['share_bar']['link_format'];
		}

		if ( isset( $input['layout']['templates'] ) && is_array( $input['layout']['templates'] ) ) {
			foreach ( $clean['layout']['templates'] as $tpl_key => &$tpl ) {
				$posted = $input['layout']['templates'][ $tpl_key ] ?? [];
				if ( isset( $posted['featured_image_position'] ) ) {
					$tpl['featured_image_position'] = in_array( $posted['featured_image_position'], [ 'above_headline', 'between_headline_body', 'hidden' ], true ) ? $posted['featured_image_position'] : 'above_headline';
				}
			}
			unset( $tpl );
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
		?>
		<div class="wrap">
			<h1>Appearance</h1>
			<p class="description">Configure the article layout and user-facing template elements. <strong>Live preview:</strong> changes take immediate effect after saving — the plugin hides theme defaults and renders elements in this exact order.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'newsroomos-appearance' ); ?>

				<!-- ====== ARTICLE HEADER ====== -->
				<h2 class="title">Article Header</h2>
				<p class="description">Drag to reorder. The CSS selector is what you use in the custom CSS box below each element.</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Header Elements</th>
						<td>
							<ul id="newsroomos-header-elements" class="newsroomos-sortable">
								<?php foreach ( $settings['header_elements'] as $el ) : ?>
									<li data-id="<?php echo esc_attr( $el['id'] ); ?>">
										<span class="dashicons dashicons-menu newsroomos-drag-handle"></span>
										<input type="hidden" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_order][]" value="<?php echo esc_attr( $el['id'] ); ?>">
										<span class="newsroomos-el-label">
											<strong><?php echo esc_html( $el['label'] ); ?></strong>
											<code><?php echo esc_html( $el['css_selector'] ); ?></code>
										</span>
										<label class="newsroomos-el-checkbox">
											<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_enabled][<?php echo esc_attr( $el['id'] ); ?>]" value="1" <?php checked( $el['enabled'] ); ?>> Visible
										</label>
										<div class="newsroomos-el-css">
											<textarea name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_css][<?php echo esc_attr( $el['id'] ); ?>]" rows="2" placeholder="Custom CSS rules for this element…"><?php echo esc_textarea( $el['custom_css'] ); ?></textarea>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="description">Share Bar position is set by its order in the list above. Configure which buttons appear in the Share Bar section below.</p>
						</td>
					</tr>
				</table>

				<!-- ====== SHARE BAR CHANNEL CONFIG ====== -->
				<h2 class="title">Share Bar — Channel Settings</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Channels</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">Share Channels</legend>
								<?php foreach ( $settings['share_bar']['channels'] as $ch => $ch_val ) : ?>
									<?php if ( 'google_preferred' === $ch ) : ?>
										<div class="newsroomos-share-channel">
											<label><input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][enabled]" value="1" <?php checked( is_array( $ch_val ) && ! empty( $ch_val['enabled'] ) ); ?>> Google Preferred Source <em>(coloradosun.com — auto-detected)</em></label>
											<div style="margin-left:24px;margin-top:4px;">
												<label>Theme: <select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][theme]"><option value="light" <?php selected( $ch_val['theme'] ?? 'light', 'light' ); ?>>Light</option><option value="dark" <?php selected( $ch_val['theme'] ?? '', 'dark' ); ?>>Dark</option></select></label>
												<label style="margin-left:12px;">Language: <input type="text" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][lang]" value="<?php echo esc_attr( $ch_val['lang'] ?? '' ); ?>" size="6"></label>
												<p class="description">Standard JS embed from <code>https://news.google.com/swg/js/v1/publisher.js</code>. Domain auto-detected — no extra config. Language blank = auto-detect.</p>
											</div>
										</div>
									<?php else : ?>
										<label class="newsroomos-share-channel"><input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][<?php echo esc_attr( $ch ); ?>]" value="1" <?php checked( is_bool( $ch_val ) ? $ch_val : false ); ?>> <?php echo esc_html( ucfirst( $ch ) ); ?></label>
									<?php endif; ?>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="newsroomos-link-format">Link Format</label></th>
						<td>
							<textarea id="newsroomos-link-format" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][link_format]" rows="3" class="large-text"><?php echo esc_textarea( $settings['share_bar']['link_format'] ); ?></textarea>
							<p class="description">Placeholders: <code>{headline}</code> <code>{subtitle}</code> <code>{featured_image_url}</code> <code>{story_url}</code>.</p>
						</td>
					</tr>
				</table>

				<!-- ====== LAYOUT ====== -->
				<h2 class="title">Layout</h2>
				<p class="description">Per-template featured image position.</p>
				<table class="form-table" role="presentation">
					<?php foreach ( $settings['layout']['templates'] as $tpl_key => $tpl ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $tpl['label'] ); ?></th>
							<td>
								<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[layout][templates][<?php echo esc_attr( $tpl_key ); ?>][featured_image_position]">
									<option value="above_headline" <?php selected( $tpl['featured_image_position'], 'above_headline' ); ?>>Above Headline</option>
									<option value="between_headline_body" <?php selected( $tpl['featured_image_position'], 'between_headline_body' ); ?>>Between Headline and Body</option>
									<option value="hidden" <?php selected( $tpl['featured_image_position'], 'hidden' ); ?>>Hidden</option>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button( 'Save Appearance Settings' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin styles and scripts for the settings page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'newsroomos_page_newsroomos-appearance' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_add_inline_script(
			'jquery-ui-sortable',
			'
			jQuery(function($){
				$("#newsroomos-header-elements").sortable({
					handle: ".newsroomos-drag-handle",
					placeholder: "ui-state-highlight"
				});
			});
		' 
		);
		wp_add_inline_style(
			'wp-admin',
			'
			.newsroomos-sortable{max-width:640px;list-style:none;margin:0;padding:0}
			.newsroomos-sortable li{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:12px 12px 8px;margin-bottom:8px;cursor:move;display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px}
			.newsroomos-sortable li.ui-sortable-helper{box-shadow:0 2px 8px rgba(0,0,0,0.12)}
			.newsroomos-sortable li.ui-state-highlight{visibility:visible!important;background:#f0f0f1;border:2px dashed #c3c4c7;border-radius:4px;height:60px}
			.newsroomos-drag-handle{cursor:grab;color:#8c8f94;padding-top:2px}
			.newsroomos-el-label{flex:1;min-width:200px}
			.newsroomos-el-label strong{display:inline-block;min-width:120px}
			.newsroomos-el-checkbox{white-space:nowrap;margin-top:2px}
			.newsroomos-el-css{width:100%}
			.newsroomos-el-css textarea{width:100%;font-family:Consolas,Monaco,monospace;font-size:12px}
			.newsroomos-share-channel{display:block;margin-bottom:6px}
		' 
		);
	}

	/**
	 * Enqueue front-end assets (Google Preferred Source script).
	 *
	 * @return void
	 */
	public static function enqueue_front_assets() {
		$settings = self::get_settings();
		if ( empty( $settings['share_bar']['channels']['google_preferred']['enabled'] ) ) {
			return;
		}
		wp_enqueue_script(
			'google-preferred-source',
			'https://news.google.com/swg/js/v1/publisher.js',
			[],
			null,
			true
		);
	}

	/**
	 * Filter the_content — render header elements in configured order before the article body.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_the_content( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$settings  = self::get_settings();
		$post_type = get_post_type();
		$is_page   = 'page' === $post_type;
		$tpl_key   = $is_page ? 'page' : ( 'post' === $post_type ? 'single' : 'single-post' );
		$layout    = $settings['layout']['templates'][ $tpl_key ] ?? $settings['layout']['templates']['single'];

		$header_html  = '';
		$feat_enabled = false;
		foreach ( $settings['header_elements'] as $el ) {
			if ( empty( $el['enabled'] ) ) {
				continue;
			}
			if ( 'featured_image' === $el['id'] ) {
				$feat_enabled = true;
				continue;
			}
			$header_html .= self::render_single_element( $el, get_the_ID() );
		}

		$featured_html = '';
		if ( $feat_enabled && 'hidden' !== $layout['featured_image_position'] && has_post_thumbnail() ) {
			$featured_html = '<div class="newsroomos-element newsroomos-featured-image post-thumbnail">' . get_the_post_thumbnail( get_the_ID(), 'full' ) . '</div>';
		}

		if ( 'between_headline_body' === $layout['featured_image_position'] ) {
			return $featured_html . $header_html . $content;
		}
		return $header_html . $featured_html . $content;
	}

	/**
	 * Render a single header element.
	 *
	 * @param array $el      Element config.
	 * @param int   $post_id Post ID.
	 * @return string
	 */
	private static function render_single_element( $el, $post_id ) {
		switch ( $el['id'] ) {
			case 'category':
				$cats = get_the_category( $post_id );
				if ( empty( $cats ) ) {
					return '';
				}
				return '<div class="newsroomos-element newsroomos-category entry-category"><a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '">' . esc_html( $cats[0]->name ) . '</a></div>';
			case 'headline':
				return '<div class="newsroomos-element newsroomos-headline entry-title">' . get_the_title( $post_id ) . '</div>';
			case 'subtitle':
				$sub = get_post_meta( $post_id, 'article_subtitle', true );
				return $sub ? '<div class="newsroomos-element newsroomos-subtitle entry-subtitle">' . esc_html( $sub ) . '</div>' : '';
			case 'byline':
				$author = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
				$html   = '<div class="newsroomos-element newsroomos-byline entry-byline">';
				if ( $author ) {
					$html .= esc_html( $author->display_name );
				}
				$html .= ' &middot; ' . get_the_date( '', $post_id ) . '</div>';
				return $html;
			case 'share_bar':
				return self::render_share_bar( $post_id );
		}
		return '';
	}

	/**
	 * Render the share bar HTML.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function render_share_bar( $post_id ) {
		$settings = self::get_settings();
		$channels = $settings['share_bar']['channels'];
		$url      = rawurlencode( get_permalink( $post_id ) );
		$title    = rawurlencode( get_the_title( $post_id ) );
		$sub_meta = get_post_meta( $post_id, 'article_subtitle', true );
		$img_url  = has_post_thumbnail( $post_id ) ? wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ) : '';
		$link_fmt = str_replace(
			[ '{headline}', '{subtitle}', '{featured_image_url}', '{story_url}' ],
			[ get_the_title( $post_id ), ! empty( $sub_meta ) ? $sub_meta : '', ! empty( $img_url ) ? $img_url : '', get_permalink( $post_id ) ],
			$settings['share_bar']['link_format']
		);
		$ch_html  = '';

		$channel_configs = [
			'email'    => [
				'url'   => 'mailto:?body=' . rawurlencode( $link_fmt ),
				'label' => 'Email',
			],
			'facebook' => [
				'url'   => 'https://www.facebook.com/sharer.php?u=' . $url,
				'label' => 'Facebook',
			],
			'bluesky'  => [
				'url'   => 'https://bsky.app/intent/compose?text=' . rawurlencode( $link_fmt ),
				'label' => 'Bluesky',
			],
			'x'        => [
				'url'   => 'https://twitter.com/intent/tweet?text=' . rawurlencode( $link_fmt ),
				'label' => 'X',
			],
			'reddit'   => [
				'url'   => 'https://reddit.com/submit?url=' . $url . '&title=' . $title,
				'label' => 'Reddit',
			],
			'threads'  => [
				'url'   => 'https://threads.net/intent/post?text=' . rawurlencode( $link_fmt ),
				'label' => 'Threads',
			],
			'linkedin' => [
				'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
				'label' => 'LinkedIn',
			],
			'whatsapp' => [
				'url'   => 'https://api.whatsapp.com/send?text=' . rawurlencode( $link_fmt ),
				'label' => 'WhatsApp',
			],
			'print'    => [
				'url'   => 'javascript:window.print()',
				'label' => 'Print',
			],
		];

		foreach ( $channel_configs as $ch_key => $cfg ) {
			if ( empty( $channels[ $ch_key ] ) ) {
				continue;
			}
			$target  = in_array( $ch_key, [ 'email', 'print' ], true ) ? '' : ' target="_blank" rel="noopener"';
			$ch_html .= sprintf( '<a href="%s" class="newsroomos-share-btn newsroomos-share-%s"%s>%s</a> ', esc_url( $cfg['url'] ), esc_attr( $ch_key ), $target, esc_html( $cfg['label'] ) );
		}

		if ( ! empty( $channels['google_preferred']['enabled'] ) ) {
			$gp_theme = $channels['google_preferred']['theme'] ?? 'light';
			$gp_lang  = $channels['google_preferred']['lang'] ?? '';
			$gp_attrs = 'google-add-preferred-source-btn data-theme="' . esc_attr( $gp_theme ) . '"';
			if ( $gp_lang ) {
				$gp_attrs .= ' data-lang="' . esc_attr( $gp_lang ) . '"';
			}
			$ch_html .= '<div class="newsroomos-share-btn newsroomos-share-google-preferred" ' . $gp_attrs . '></div> ';
		}

		if ( empty( $ch_html ) ) {
			return '';
		}
		return '<div class="newsroomos-element newsroomos-share-bar">' . $ch_html . '</div>';
	}

	/**
	 * Suppress the theme's default post thumbnail output — Appearance re-renders it.
	 *
	 * @param string $html    Thumbnail HTML.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public static function filter_post_thumbnail( $html, $post_id ) {
		if ( ! is_singular() || ! in_the_loop() ) {
			return $html;
		}
		return '';
	}

	/**
	 * Output custom CSS in <head>: hide theme defaults + per-element custom CSS.
	 *
	 * @return void
	 */
	public static function output_custom_css() {
		if ( ! is_singular() ) {
			return;
		}
		$settings = self::get_settings();
		$css      = '.entry-header{display:none!important}.post-thumbnail,.wp-post-image{display:none!important}';

		foreach ( $settings['header_elements'] as $el ) {
			$css .= $el['css_selector'] . '{display:none!important}';
		}

		foreach ( $settings['header_elements'] as $el ) {
			if ( ! empty( $el['custom_css'] ) ) {
				$css .= $el['css_selector'] . '{' . $el['custom_css'] . '}';
			}
		}

		wp_register_style( 'newsroomos-appearance-css', false, [], '1.1.0' );
		wp_enqueue_style( 'newsroomos-appearance-css' );
		wp_add_inline_style( 'newsroomos-appearance-css', $css );
	}
}