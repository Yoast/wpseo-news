<?php

namespace Yoast\WP\News\Tests;

use Mockery;
use WPSEO_News_Updates_Notification;

/**
 * Tests the WPSEO_News_Updates_Notification class.
 *
 * @coversDefaultClass WPSEO_News_Updates_Notification
 */
class Updates_Notification_Test extends TestCase {

	/**
	 * Tests that Yoast_Notification_Center add_notification is called.
	 *
	 * @covers ::add_notification
	 */
	public function test_add_notification() {
		$shortlinker = Mockery::mock( 'overload:WPSEO_Shortlinker' );
		$shortlinker
			->shouldReceive( 'get' )
			->with( 'https://yoa.st/news-changes' )
			->once()
			->andReturn( 'https://yoa.st/news-changes' );

		Mockery::getConfiguration()->setConstantsMap(
			[
				'Yoast_Notification' => [
					'UPDATED' => 'updated',
				],
			]
		);
		Mockery::mock( 'overload:Yoast_Notification' );

		$notification_center = Mockery::mock( 'overload:Yoast_Notification_Center' );
		$notification_center
			->shouldReceive( 'get' )
			->withNoArgs()
			->once()
			->andReturn( $notification_center );
		$notification_center
			->shouldReceive( 'add_notification' )
			->withAnyArgs()
			->once();

		WPSEO_News_Updates_Notification::add_notification();
	}

	/**
	 * Tests that Yoast_Notification_Center remove_notification_by_id is called.
	 *
	 * @covers ::remove_notification
	 */
	public function test_get_included_post_types_with_no_set_post_types() {
		$notification_center = Mockery::mock( 'overload:Yoast_Notification_Center' );
		$notification_center
			->shouldReceive( 'get' )
			->withNoArgs()
			->once()
			->andReturn( $notification_center );
		$notification_center
			->shouldReceive( 'remove_notification_by_id' )
			->with( WPSEO_News_Updates_Notification::NOTIFICATION_ID )
			->once();

		WPSEO_News_Updates_Notification::remove_notification();
	}
}
