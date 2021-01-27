<?php

namespace Yoast\WP\News\Tests;

use Brain\Monkey;
use Mockery;
use Yoast\WPTestUtils\BrainMonkey\YoastTestCase;

/**
 * TestCase base class.
 */
abstract class TestCase extends YoastTestCase {

	/**
	 * Test setup.
	 */
	protected function set_up() {
		parent::set_up();

		Monkey\Functions\stubs(
			[
				'is_admin' => false,
				'__'       => null,
			]
		);

		Monkey\Functions\expect( 'get_option' )
			->zeroOrMoreTimes()
			->with( Mockery::anyOf( 'wpseo', 'wpseo_titles', 'wpseo_taxonomy_meta', 'wpseo_social', 'wpseo_ms' ) )
			->andReturn( [] );

		Monkey\Functions\expect( 'get_site_option' )
			->zeroOrMoreTimes()
			->with( Mockery::anyOf( 'wpseo', 'wpseo_titles', 'wpseo_taxonomy_meta', 'wpseo_social', 'wpseo_ms' ) )
			->andReturn( [] );
	}
}
