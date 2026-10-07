<?php
/**
 * Story Ingestor — sends story metadata to NewsroomOS on save_post.
 *
 * Hooks save_post to capture post ID, display headline, Yoast SEO title,
 * slug, URL, and post type, then POSTs them to the NewsroomOS webhook
 * endpoint for headline-version tracking and analytics.
 *
 * @package ColoradoSun
 */

namespace ColoradoSun\Modules\StoryIngest;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for post saves and forwards metadata to NewsroomOS.
 *
 * Configuration constants (define in wp-config.php or similar):
 *   COLORADO_SUN_STORY_WEBHOOK_URL  — full URL of the upsert endpoint
 *   COLORADO_SUN_STORY_WEBHOOK_KEY  — shared secret for X-WP-Webhook-Key
 */
class StoryIngestor {

	/**
	 * Hook into save_post.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 10, 3 );
	}

	/**
	 * Callback for save_post — sends story data to the NewsroomOS webhook.
	 *
	 * Skips auto-saves, revisions, and non-public post types. Uses
	 * wp_remote_post for the HTTP call; failures are logged but never
	 * block the save operation.
	 *
	 * @param int      $post_id Post ID being saved.
	 * @param \WP_Post $post    The post object.
	 * @param bool     $update  Whether this is an update to an existing post.
	 * @return void
	 */
	public static function on_save_post( int $post_id, \WP_Post $post, bool $update ): void {

		// Only process public post types (post, page, etc.).
		$post_type = $post->post_type;
		if ( empty( $post_type ) ) {
			return;
		}

		// Skip auto-saves and revisions.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Skip non-public post types (nav_menu_item, revision, etc.).
		$post_type_obj = get_post_type_object( $post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		// Gather the payload.
		$payload = self::build_payload( $post_id, $post );

		// Send to NewsroomOS.
		self::send_webhook( $payload );
	}

	/**
	 * Build the JSON payload for the NewsroomOS upsert endpoint.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post   The post object.
	 * @return array<string, mixed> Payload ready for JSON encoding.
	 */
	private static function build_payload( int $post_id, \WP_Post $post ): array {

		$display_headline = $post->post_title;
		$seo_headline     = get_post_meta( $post_id, '_yoast_wpseo_title', true );

		// If Yoast title is empty, send null (the endpoint handles it).
		if ( empty( $seo_headline ) ) {
			$seo_headline = null;
		}

		$published_at = null;
		if ( 'publish' === $post->post_status && ! empty( $post->post_date_gmt ) ) {
			// Format as ISO-8601 for the webhook endpoint.
			$published_at = gmdate( 'c', strtotime( $post->post_date_gmt . ' GMT' ) );
		} elseif ( 'publish' === $post->post_status && ! empty( $post->post_date ) ) {
			// Fall back to local date if GMT not set.
			$gmt_time     = get_gmt_from_date( $post->post_date );
			$published_at = gmdate( 'c', strtotime( $gmt_time . ' GMT' ) );
		}

		return array(
			'post_id'          => $post_id,
			'post_type'        => $post->post_type,
			'title'            => $display_headline,
			'url'              => get_permalink( $post_id ),
			'slug'             => $post->post_name,
			'status'           => $post->post_status,
			'published_at'     => $published_at,
			'display_headline' => $display_headline,
			'seo_headline'     => $seo_headline,
		);
	}

	/**
	 * POST the payload to the NewsroomOS webhook endpoint.
	 *
	 * Logs errors via WordPress error logging but never interrupts the
	 * save operation.
	 *
	 * @param array<string, mixed> $payload The story data.
	 * @return void
	 */
	private static function send_webhook( array $payload ): void {

		$webhook_url = defined( 'COLORADO_SUN_STORY_WEBHOOK_URL' )
			? COLORADO_SUN_STORY_WEBHOOK_URL
			: '';

		$webhook_key = defined( 'COLORADO_SUN_STORY_WEBHOOK_KEY' )
			? COLORADO_SUN_STORY_WEBHOOK_KEY
			: '';

		if ( empty( $webhook_url ) || empty( $webhook_key ) ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'[Colorado Sun Story Ingest] Webhook URL or key not configured. '
				. 'Define COLORADO_SUN_STORY_WEBHOOK_URL and COLORADO_SUN_STORY_WEBHOOK_KEY.'
			);
			// phpcs:enable
			return;
		}

		// phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout -- Deliberately high: the webhook is fire-and-forget from save_post and must succeed even when the target endpoint is momentarily slow.
		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => array(
					'Content-Type'     => 'application/json',
					'X-WP-Webhook-Key' => $webhook_key,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			)
		);
		// phpcs:enable WordPressVIPMinimum.Performance.RemoteRequestTimeout

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'[Colorado Sun Story Ingest] Webhook request failed: '
				. $response->get_error_message()
				. ' | Post ID: ' . $payload['post_id']
			);
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$response_body = wp_remote_retrieve_body( $response );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'[Colorado Sun Story Ingest] Webhook returned '
				. $response_code . ': ' . $response_body
				. ' | Post ID: ' . $payload['post_id']
			);
		}
	}
}
