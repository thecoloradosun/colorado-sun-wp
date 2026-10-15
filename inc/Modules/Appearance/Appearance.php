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
					'id'           => 'featured_image',
					'label'        => 'Featured Image',
					'css_selector' => '.post-thumbnail',
					'enabled'      => true,
					'custom_css'   => '',
				],
			],
			'share_bar'       => [
				'enabled'     => false,
				'placement'   => 'below_headline',
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
	 * Get saved settings, merged with defaults.
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
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
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
	 * Sanitize the submitted settings array.
	 *
	 * @param array $input Raw POST data.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::default_settings();
		$clean    = $defaults;

		// Header elements: rebuild from ordered IDs.
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

		// Share bar.
		if ( isset( $input['share_bar'] ) && is_array( $input['share_bar'] ) ) {
			$sb       = $input['share_bar'];
			$clean_sb = &$clean['share_bar'];
			$clean_sb['enabled']   = ! empty( $sb['enabled'] );
			$clean_sb['placement'] = in_array( $sb['placement'] ?? '', [ 'below_headline', 'end_of_article' ], true )
				? $sb['placement'] : 'below_headline';

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

		// Layout per template.
		if ( isset( $input['layout']['templates'] ) && is_array( $input['layout']['templates'] ) ) {
			foreach ( $clean['layout']['templates'] as $tpl_key => &$tpl ) {
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
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1>Appearance</h1>
			<p>Configure the article layout, header element order and styling, and the share bar.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'newsroomos-appearance' ); ?>

				<!-- =========== ARTICLE HEADER SECTION =========== -->
				<h2 class="title">Article Header</h2>
				<p class="description">Drag to reorder. Each element has its own visibility toggle and custom CSS box. The CSS selector shown next to each name is what you use in the custom CSS box.</p>

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
											<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_enabled][<?php echo esc_attr( $el['id'] ); ?>]" value="1" <?php checked( $el['enabled'] ); ?>>
											Visible
										</label>
										<div class="newsroomos-el-css">
											<textarea name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[header_css][<?php echo esc_attr( $el['id'] ); ?>]" rows="2" placeholder="Custom CSS rules for this element…"><?php echo esc_textarea( $el['custom_css'] ); ?></textarea>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						</td>
					</tr>
				</table>

				<!-- =========== SHARE BAR SECTION =========== -->
				<h2 class="title">Share Bar</h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Enable Share Bar</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][enabled]" value="1" <?php checked( $settings['share_bar']['enabled'] ); ?>>
								Show share buttons on article pages
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Placement</th>
						<td>
							<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][placement]">
								<option value="below_headline" <?php selected( $settings['share_bar']['placement'], 'below_headline' ); ?>>Below Headline</option>
								<option value="end_of_article" <?php selected( $settings['share_bar']['placement'], 'end_of_article' ); ?>>End of Article</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Channels</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">Share Channels</legend>
								<?php foreach ( $settings['share_bar']['channels'] as $ch => $ch_val ) : ?>
									<?php if ( 'google_preferred' === $ch ) : ?>
										<div class="newsroomos-share-channel">
											<label>
												<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][enabled]" value="1" <?php checked( is_array( $ch_val ) && ! empty( $ch_val['enabled'] ) ); ?>>
												Google Preferred Source
											</label>
											<div class="newsroomos-subsettings" style="margin-left:24px;margin-top:4px;">
												<label>Theme:
													<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][theme]">
														<option value="light" <?php selected( $ch_val['theme'] ?? 'light', 'light' ); ?>>Light</option>
														<option value="dark" <?php selected( $ch_val['theme'] ?? '', 'dark' ); ?>>Dark</option>
													</select>
												</label>
												<label style="margin-left:12px;">Language code (e.g. <code>en</code>, <code>es</code>):
													<input type="text" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][google_preferred][lang]" value="<?php echo esc_attr( $ch_val['lang'] ?? '' ); ?>" size="6">
												</label>
												<p class="description">Uses the standard JavaScript embed from <code>https://news.google.com/swg/js/v1/publisher.js</code>. Leave language blank to auto-detect from browser.</p>
											</div>
										</div>
									<?php else : ?>
										<label class="newsroomos-share-channel">
											<input type="checkbox" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][channels][<?php echo esc_attr( $ch ); ?>]" value="1" <?php checked( is_bool( $ch_val ) ? $ch_val : false ); ?>>
											<?php echo esc_html( ucfirst( $ch ) ); ?>
										</label>
									<?php endif; ?>
								<?php endforeach; ?>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="newsroomos-link-format">Link Format</label></th>
						<td>
							<textarea id="newsroomos-link-format" name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[share_bar][link_format]" rows="3" class="large-text"><?php echo esc_textarea( $settings['share_bar']['link_format'] ); ?></textarea>
							<p class="description">Use placeholders: <code>{headline}</code>, <code>{subtitle}</code>, <code>{featured_image_url}</code>, <code>{story_url}</code>. This template is used when composing the share text for each channel.</p>
						</td>
					</tr>
				</table>

				<!-- =========== LAYOUT SECTION =========== -->
				<h2 class="title">Layout</h2>
				<p class="description">Per-template settings for featured image position and element overrides. Defaults apply to all templates not listed here.</p>

				<table class="form-table" role="presentation">
					<?php foreach ( $settings['layout']['templates'] as $tpl_key => $tpl ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $tpl['label'] ); ?></th>
							<td>
								<label>Featured image position:
									<select name="<?php echo esc_attr( self::SETTINGS_KEY ); ?>[layout][templates][<?php echo esc_attr( $tpl_key ); ?>][featured_image_position]">
										<option value="above_headline" <?php selected( $tpl['featured_image_position'], 'above_headline' ); ?>>Above Headline</option>
										<option value="between_headline_body" <?php selected( $tpl['featured_image_position'], 'between_headline_body' ); ?>>Between Headline and Body</option>
										<option value="hidden" <?php selected( $tpl['featured_image_position'], 'hidden' ); ?>>Hidden</option>
									</select>
								</label>
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
		wp_add_inline_style(
			'wp-admin',
			'
			.newsroomos-sortable {
				max-width: 640px;
				list-style: none;
				margin: 0;
				padding: 0;
			}
			.newsroomos-sortable li {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 12px 12px 8px;
				margin-bottom: 8px;
				cursor: move;
				display: flex;
				flex-wrap: wrap;
				align-items: flex-start;
				gap: 8px;
			}
			.newsroomos-sortable li.ui-sortable-helper {
				box-shadow: 0 2px 8px rgba(0,0,0,0.12);
			}
			.newsroomos-sortable li.ui-sortable-placeholder {
				visibility: visible !important;
				background: #f0f0f1;
				border: 2px dashed #c3c4c7;
				border-radius: 4px;
			}
			.newsroomos-drag-handle {
				cursor: grab;
				color: #8c8f94;
				padding-top: 2px;
			}
			.newsroomos-el-label {
				flex: 1;
				min-width: 200px;
			}
			.newsroomos-el-label strong {
				display: inline-block;
				min-width: 120px;
			}
			.newsroomos-el-checkbox {
				white-space: nowrap;
				margin-top: 2px;
			}
			.newsroomos-el-css {
				width: 100%;
			}
			.newsroomos-el-css textarea {
				width: 100%;
				font-family: Consolas, Monaco, monospace;
				font-size: 12px;
			}
			.newsroomos-share-channel {
				display: block;
				margin-bottom: 6px;
			}
		' 
		);
	}
}