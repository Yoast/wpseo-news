<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News
 */

/**
 * Represents the news updates notification for Yoast SEO.
 */
class WPSEO_News_Updates_Notification {

	const NOTIFICATION_ID = 'wpseo-updates-news';

	/**
	 * Adds the updates notification to the Notification Center.
	 *
	 * @return void
	 */
	public static function add_notification() {
		$notification_msg_a = sprintf(
			/* translators: %s expands to Yoast SEO News */
			__( 'Changed settings in %s:', 'wordpress-seo-news' ),
			'Yoast SEO News'
		);
		$notification_msg_b = sprintf(
			/* translators: %s expands to Yoast SEO News */
			__( 'You\'ve just updated %s, but have you already noticed that we\'ve changed some settings?', 'wordpress-seo-news' ),
			'Yoast SEO News'
		);

		$notification_info_url      = WPSEO_Shortlinker::get( 'https://yoa.st/news-changes' );
		$notification_info_linktext = __( 'Read everything about it here', 'wordpress-seo-news' );

		$notification_message = sprintf(
			'<b>%s</b> %s <a href="%s" target="_blank">%s</a>.',
			$notification_msg_a,
			$notification_msg_b,
			$notification_info_url,
			$notification_info_linktext
		);

		$notification = new Yoast_Notification(
			$notification_message,
			[
				'id'   => self::NOTIFICATION_ID,
				'type' => Yoast_Notification::UPDATED,
			]
		);

		$notification_center = Yoast_Notification_Center::get();
		$notification_center->add_notification( $notification );
	}

	/**
	 * Removes the updates notification from the Notification Center.
	 *
	 * @return void
	 */
	public static function remove_notification() {
		$notification_center = Yoast_Notification_Center::get();
		$notification_center->remove_notification_by_id( self::NOTIFICATION_ID );
	}
}
