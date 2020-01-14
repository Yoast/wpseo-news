<?php

namespace Yoast\WP\News\Tests;

/**
 * Test the News Option class.
 */
class Option_Test extends TestCase {

	/**
	 * The instance.
	 *
	 * @var Doubles\Option_Double
	 */
	protected $instance;

	/**
	 * Sets the instance.
	 */
	public function setUp() {
		parent::setUp();

		$external_mock              = \Mockery::mock( 'overload:\WPSEO_Option' );
		$external_mock->option_name = 'wpseo_news';
		$external_mock->defaults    = [];

		$this->instance = new Doubles\Option_Double();
	}

	/**
	 * Tests the validation of the option.
	 *
	 * @param string $option_name The option name.
	 * @param array  $clean       The clean data.
	 * @param array  $dirty       The data to validate.
	 * @param array  $expected    The expected value.
	 * @param string $message     Message to show when test fails.
	 *
	 * @dataProvider validate_option_provider
	 *
	 * @covers WPSEO_News_Option::validate_option
	 */
	public function test_validate_option( $option_name, $clean, $dirty, $expected, $message ) {
		$clean    = [ $option_name => $clean ];
		$dirty    = [ $option_name => $dirty ];
		$expected = [ $option_name => $expected ];
		$actual   = $this->instance->validate_option( $dirty, $clean, [] );

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * Data provider for test_validate_option.
	 *
	 * @return array The options.
	 */
	public function validate_option_provider() {
		return [
			[
				'option_name' => 'news_sitemap_include_post_types',
				'clean'       => [
					'page' => 'on',
				],
				'dirty'       => [
					'post' => 'on',
				],
				'expected'    => [
					'post' => 'on',
				],
				'message'     => 'Tests the include post type options with valid data.',
			],
			[
				'option_name' => 'news_sitemap_include_post_types',
				'clean'       => [
					'page' => 'on',
				],
				'dirty'       => [
					false => 'on',
				],
				'expected'    => [],
				'message'     => 'Tests the include post type options with valid invalid data.',
			],
			[
				'option_name' => 'news_sitemap_exclude_terms',
				'clean'       => [
					'term' => 'on',
				],
				'dirty'       => [
					'term' => 'on',
				],
				'expected'    => [
					'term' => 'on',
				],
				'message'     => 'Tests the excluded terms options with valid data.',
			],
			[
				'option_name' => 'news_sitemap_exclude_terms',
				'clean'       => [
					'term' => 'on',
				],
				'dirty'       => [
					false => 'on',
				],
				'expected'    => [],
				'message'     => 'Tests the excluded terms options with valid invalid data.',
			],
		];
	}
}