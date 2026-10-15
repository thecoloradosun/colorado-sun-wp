<?php
/**
 * Appearance — configure article layout, template elements, and share bar.
 *
 * @package NewsroomOS
 */

namespace NewsroomOS\Modules\Appearance;

defined( 'ABSPATH' ) || exit;

/**
 * Settings and PHP template override for the user-facing article layout.
 *
 * Uses single_template filter to load a custom template that renders
 * header elements in the configured order via `get_header()` / `get_footer()`.
 */
class Appearance {

	const SETTINGS_KEY = 'newsroomos_appearance_settings';

	/**
	 * Return the default profiles. Each profile has a label, header_elements,
	 * share_bar config, and layout config.
	 *
	 * @return array
	 */
	public static function default_profiles() {
		return [
			'default' => [
				'label'           => 'One Column (default)',
				'header_elements' => self::default_header_elements(),
				'share_bar'       => self::default_share_bar(),
				'layout'          => self::default_layout(),
			],
		];
	}

	/**
	 * Default header elements list.
	 *
	 * @return array
	 */
	private static function default_header_elements() {
		return [
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
		];
	}

	/**
	 * Default share bar config.
	 *
	 * @return array
	 */
	private static function default_share_bar() {
		return [
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
		];
	}

	/**
	 * Default layout config.
	 *
	 * @return array
	 */
	private static function default_layout() {
		return [
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
		];
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::SETTINGS_KEY, [] );
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return [
				'active_profile' => 'default',
				'profiles'       => self::default_profiles(),
			];
		}
		// Ensure defaults for any missing profiles or keys.
		$defaults = [
			'active_profile' => 'default',
			'profiles'       => self::default_profiles(),
		];
		return array_replace_recursive( $defaults, $saved );
	}

	/**
	 * Get the current active profile's config.
	 *
	 * @return array
	 */
	public static function get_active_profile() {
		$settings = self::get_settings();
		$key      = $settings['active_profile'];
		if ( isset( $settings['profiles'][ $key ] ) ) {
			return $settings['profiles'][ $key ];
		}
		// Fallback to first profile.
		reset( $settings['profiles'] );
		return current( $settings['profiles'] );
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu',            [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init',            [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_action( 'template_redirect',     [ __CLASS__, 'override_template' ], PHP_INT_MAX );
		add_action( 'wp_footer',             [ __CLASS__, 'frontend_debug_marker' ], 1 );
	}

	/**
	 * State captured at template_redirect time, printed by debug marker.
	 *
	 * @var array
	 */
	private static $redirect_state = [];

	/**
	 * Debug marker to confirm the plugin loads on the front end.
	 * Remove after wiring is verified.
	 *
	 * @return void
	 */
	public static function frontend_debug_marker() {
		echo '<!-- newsroomos:frontend-loaded ' . wp_json_encode( self::$redirect_state ) . ' -->'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Override the single post template by short-circuiting template loading.
	 *
	 * Uses template_redirect (instead of template_include filter) because
	 * WordPress.com VIP / Newspack may override the template_include filter
	 * after our registration. This approach directly includes our template
	 * and exits, preventing any further template processing.
	 *
	 * @return void
	 */
	public static function override_template() {
		self::$redirect_state = [
			'fired'        => true,
			'singular'     => is_singular(),
			'single'       => is_single(),
			'post_type'    => get_post_type(),
			'tpl_exists'   => file_exists( dirname( __DIR__, 2 ) . '/templates/single-post.php' ),
			'tpl_path'     => dirname( __DIR__, 2 ) . '/templates/single-post.php',
		];
		if ( ! is_singular() ) {
			return;
		}
		$plugin_template = dirname( __DIR__, 2 ) . '/templates/single-post.php';
		if ( ! file_exists( $plugin_template ) ) {
			return;
		}
		// Load our template instead of the theme's.
		load_template( $plugin_template, true );
		exit;
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
				'default'           => [
					'active_profile' => 'default',
					'profiles'       => self::default_profiles(),
				],
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
		$defaults = [
			'active_profile' => 'default',
			'profiles'       => self::default_profiles(),
		];
		$clean    = $defaults;

		// Active profile.
		if ( ! empty( $input['active_profile'] ) ) {
			$clean['active_profile'] = sanitize_key( $input['active_profile'] );
		}

		// Profile key being edited.
		$profile_key = ! empty( $input['profile_key'] ) ? sanitize_key( $input['profile_key'] ) : 'default';
		if ( ! isset( $clean['profiles'][ $profile_key ] ) ) {
			$clean['profiles'][ $profile_key ] = $defaults['profiles']['default'];
		}

		// Header elements.
		if ( isset( $input['header_order'] ) && is_array( $input['header_order'] ) ) {
			$clean['profiles'][ $profile_key ]['header_elements'] = [];
			$enabled    = isset( $input['header_enabled'] ) ? (array) $input['header_enabled'] : [];
			$custom_css = isset( $input['header_css'] ) ? (array) $input['header_css'] : [];
			$lookup     = [];
			foreach ( $defaults['profiles']['default']['header_elements'] as $el ) {
				$lookup[ $el['id'] ] = $el;
			}
			foreach ( $input['header_order'] as $id ) {
				$id = sanitize_key( $id );
				if ( ! isset( $lookup[ $id ] ) ) {
					continue;
				}
				$el    = $lookup[ $id ];
				$el['enabled']    = ! empty( $enabled[ $id ] );
				$el['custom_css'] = isset( $custom_css[ $id ] ) ? sanitize_textarea_field( $custom_css[ $id ] ) : '';
				$clean['profiles'][ $profile_key ]['header_elements'][] = $el;
			}
		}

		// Share bar.
		if ( isset( $input['share_bar'] ) && is_array( $input['share_bar'] ) ) {
			$sb       = $input['share_bar'];
			$clean_sb = &$clean['profiles'][ $profile_key ]['share_bar'];
			if ( isset( $sb['appearance'] ) ) {
				$clean_sb['appearance']['style']    = in_array( $sb['appearance']['style'] ?? '', [ 'monochrome', 'color' ], true ) ? $sb['appearance']['style'] : 'monochrome';
				$clean_sb['appearance']['shape']    = in_array( $sb['appearance']['shape'] ?? '', [ 'pill', 'rounded', 'circle' ], true ) ? $sb['appearance']['shape'] : 'pill';
				$clean_sb['appearance']['grouping'] = in_array( $sb['appearance']['grouping'] ?? '', [ 'separate', 'grouped' ], true ) ? $sb['appearance']['grouping'] : 'grouped';
			}
			if ( isset( $sb['channels'] ) && is_array( $sb['channels'] ) ) {
				foreach ( $clean_sb['channels'] as $ch => $_ ) {
					if ( 'google_preferred' === $ch ) {
						$gp = $sb['channels']['google_preferred'] ?? [];
						$clean_sb['channels']['google_preferred'] = ! empty( $gp );
					} else {
						$clean_sb['channels'][ $ch ] = ! empty( $sb['channels'][ $ch ] );
					}
				}
			}
			$clean_sb['link_format'] = isset( $sb['link_format'] )
				? sanitize_textarea_field( stripslashes( $sb['link_format'] ) )
				: $defaults['profiles']['default']['share_bar']['link_format'];
		}

		// Layout templates.
		if ( isset( $input['layout']['templates'] ) && is_array( $input['layout']['templates'] ) ) {
			foreach ( $clean['profiles'][ $profile_key ]['layout']['templates'] as $tpl_key => &$tpl ) {
				$posted = $input['layout']['templates'][ $tpl_key ] ?? [];
				if ( isset( $posted['featured_image_position'] ) ) {
					$tpl['featured_image_position'] = in_array( $posted['featured_image_position'], [ 'above_headline', 'between_headline_body', 'hidden' ], true )
						? $posted['featured_image_position'] : 'above_headline';
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
		$settings    = self::get_settings();
		$profile_key = $settings['active_profile'];
		$profile     = isset( $settings['profiles'][ $profile_key ] ) ? $settings['profiles'][ $profile_key ] : reset( $settings['profiles'] );
		?>
		<div class="wrap">
			<h1>Appearance</h1>
			<p class="description">Configure article layout per template profile. Uses a PHP template override — changes take effect immediately after saving.</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'newsroomos-appearance' ); ?>
				<input type="hidden" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[profile_key]" value="<?php echo esc_attr( $profile_key ); ?>">

				<!-- TEMPLATE PROFILE SELECTOR -->
				<table class="form-table"><tr>
					<th scope="row">Template Profile</th>
					<td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[active_profile]" onchange="this.form.submit()">
							<?php foreach ( $settings['profiles'] as $pk => $p ) : ?>
								<option value="<?php echo esc_attr( $pk ); ?>" <?php selected( $profile_key, $pk ); ?>><?php echo esc_html( $p['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Select a profile to configure. Each profile stores its own element order, CSS, share bar settings, and layout. Create new profiles by selecting "Add New…" and saving.</p>
					</td>
				</tr></table>

				<!-- HEADER ELEMENTS -->
				<h2 class="title">Article Header</h2>
				<p class="description">Drag to reorder. Each element has a visibility toggle and custom CSS box.</p>
				<table class="form-table"><tr><th scope="row">Header Elements</th><td>
					<ul id="newsroomos-header-elements" class="newsroomos-sortable">
						<?php foreach ( $profile['header_elements'] as $el ) : ?>
							<li data-id="<?php echo esc_attr( $el['id'] ); ?>">
								<span class="dashicons dashicons-menu newsroomos-drag-handle"></span>
								<input type="hidden" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_order][]" value="<?php echo esc_attr( $el['id'] ); ?>">
								<span class="newsroomos-el-label"><strong><?php echo esc_html( $el['label'] ); ?></strong> <code><?php echo esc_html( $el['css_selector'] ); ?></code></span>
								<label class="newsroomos-el-checkbox"><input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_enabled][<?php echo esc_attr( $el['id'] ); ?>]" value="1" <?php checked( $el['enabled'] ); ?>> Visible</label>
								<div class="newsroomos-el-css"><textarea name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_css][<?php echo esc_attr( $el['id'] ); ?>]" rows="2" placeholder="Custom CSS rule for this element…"><?php echo esc_textarea( $el['custom_css'] ); ?></textarea></div>
							</li>
						<?php endforeach; ?>
					</ul>
				</td></tr></table>

				<!-- SHARE BAR -->
				<h2 class="title">Share Bar — Style &amp; Channels</h2>
				<table class="form-table">
					<tr><th scope="row">Button Style</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][style]">
							<option value="monochrome" <?php selected( $profile['share_bar']['appearance']['style'], 'monochrome' ); ?>>Monochrome</option>
							<option value="color" <?php selected( $profile['share_bar']['appearance']['style'], 'color' ); ?>>Brand Colors</option>
						</select>
					</td></tr>
					<tr><th scope="row">Button Shape</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][shape]">
							<option value="pill" <?php selected( $profile['share_bar']['appearance']['shape'], 'pill' ); ?>>Pill (Axios style)</option>
							<option value="rounded" <?php selected( $profile['share_bar']['appearance']['shape'], 'rounded' ); ?>>Rounded Square</option>
							<option value="circle" <?php selected( $profile['share_bar']['appearance']['shape'], 'circle' ); ?>>Circle</option>
						</select>
					</td></tr>
					<tr><th scope="row">Layout / Grouping</th><td>
						<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][appearance][grouping]">
							<option value="grouped" <?php selected( $profile['share_bar']['appearance']['grouping'], 'grouped' ); ?>>Group social channels (Axios style)</option>
							<option value="separate" <?php selected( $profile['share_bar']['appearance']['grouping'], 'separate' ); ?>>All buttons separate</option>
						</select>
						<p class="description">"Group social channels" puts Facebook, X, Bluesky, Reddit, Threads, LinkedIn, and WhatsApp inside one pill; Email, Print, and Google Preferred each get their own button.</p>
					</td></tr>
					<tr><th scope="row">Channels</th><td>
						<fieldset><legend class="screen-reader-text">Share Channels</legend>
						<?php foreach ( $profile['share_bar']['channels'] as $ch => $ch_val ) : ?>
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
						<textarea id="newsroomos-link-format" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][link_format]" rows="3" class="large-text"><?php echo esc_textarea( $profile['share_bar']['link_format'] ); ?></textarea>
						<p class="description">Placeholders: <code>{headline}</code> <code>{subtitle}</code> <code>{featured_image_url}</code> <code>{story_url}</code>.</p>
					</td></tr>
				</table>

				<!-- LAYOUT -->
				<h2 class="title">Layout</h2>
				<p class="description">Per-template featured image position.</p>
				<table class="form-table">
					<?php foreach ( $profile['layout']['templates'] as $tpl_key => $tpl ) : ?>
						<tr><th scope="row"><?php echo esc_html( $tpl['label'] ); ?></th><td>
							<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[layout][templates][<?php echo esc_attr( $tpl_key ); ?>][featured_image_position]">
								<option value="above_headline" <?php selected( $tpl['featured_image_position'], 'above_headline' ); ?>>Above Headline</option>
								<option value="between_headline_body" <?php selected( $tpl['featured_image_position'], 'between_headline_body' ); ?>>Between Headline and Body</option>
								<option value="hidden" <?php selected( $tpl['featured_image_position'], 'hidden' ); ?>>Hidden</option>
							</select>
						</td></tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button( 'Save Appearance Settings' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets for the Appearance settings page.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'newsroomos_page_newsroomos-appearance' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_add_inline_script(
			'jquery-ui-sortable',
			'jQuery(function($){$("#newsroomos-header-elements").sortable({handle:".newsroomos-drag-handle",placeholder:"ui-state-highlight"});});'
		);
		wp_add_inline_style(
			'wp-admin',
			implode(
				'',
				[
					'.newsroomos-sortable{max-width:640px;list-style:none;margin:0;padding:0}',
					'.newsroomos-sortable li{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:12px 12px 8px;margin-bottom:8px;cursor:move;display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px}',
					'.newsroomos-sortable li.ui-sortable-helper{box-shadow:0 2px 8px rgba(0,0,0,0.12)}',
					'.newsroomos-sortable li.ui-state-highlight{visibility:visible!important;background:#f0f0f1;border:2px dashed #c3c4c7;border-radius:4px;height:60px}',
					'.newsroomos-drag-handle{cursor:grab;color:#8c8f94;padding-top:2px}',
					'.newsroomos-el-label{flex:1;min-width:200px}',
					'.newsroomos-el-label strong{display:inline-block;min-width:120px}',
					'.newsroomos-el-checkbox{white-space:nowrap;margin-top:2px}',
					'.newsroomos-el-css{width:100%}',
					'.newsroomos-el-css textarea{width:100%;font-family:Consolas,Monaco,monospace;font-size:12px}',
					'.newsroomos-share-channel{display:block;margin-bottom:6px}',
				] 
			)
		);
	}

	// -------------------------------------------------------------------------
	// FRONT-END RENDERING (called from the template file)
	// -------------------------------------------------------------------------

	/**
	 * Render a single header element.
	 *
	 * @param array $el      Element config.
	 * @param int   $post_id Post ID.
	 * @return string
	 */
	public static function render_single_element( $el, $post_id ) {
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
			case 'featured_image':
				if ( has_post_thumbnail( $post_id ) ) {
					return '<div class="newsroomos-element newsroomos-featured-image post-thumbnail">' . get_the_post_thumbnail( $post_id, 'full' ) . '</div>';
				}
				return '';
		}
		return '';
	}

	/**
	 * Render the share bar with Axios-style appearance options.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function render_share_bar( $post_id ) {
		$profile  = self::get_active_profile();
		$channels = $profile['share_bar']['channels'];
		$app      = $profile['share_bar']['appearance'];
		$url      = rawurlencode( get_permalink( $post_id ) );
		$title    = rawurlencode( get_the_title( $post_id ) );
		$sub_meta = get_post_meta( $post_id, 'article_subtitle', true );
		$img_url  = has_post_thumbnail( $post_id ) ? wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ) : '';
		$link_fmt = str_replace(
			[ '{headline}', '{subtitle}', '{featured_image_url}', '{story_url}' ],
			[ get_the_title( $post_id ), ! empty( $sub_meta ) ? $sub_meta : '', ! empty( $img_url ) ? $img_url : '', get_permalink( $post_id ) ],
			$profile['share_bar']['link_format']
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

		// Companion CSS.
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

		wp_add_inline_style( 'wp-block-library', $css );

		return $html;
	}
}