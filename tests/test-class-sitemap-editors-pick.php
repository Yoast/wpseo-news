<?php

class WPSEO_News_Sitemap_Editors_Pick_Test extends WPSEO_News_UnitTestCase {

	/**
	 * @var WPSEO_News_Sitemap_Editors_Pick
	 */
	private $instance;

	private $post_id;

	/**
	 * Setting up the instance of WPSEO_News_Admin_Page
	 */
	public function setUp() {
		parent::setUp();

		$this->post_id = $this->factory->post->create(
			array(
				'post_title'   => 'generate rss',
				'post_excerpt' => 'Post excerpt 1'
			)
		);
		// Set post as editors pick
		add_post_meta($this->post_id, '_yoast_wpseo_newssitemap-editors-pick', 'on');

		$this->instance = new WPSEO_News_Sitemap_Editors_Pick();
	}

	/**
	 * @covers WPSEO_News_Sitemap_Editors_Pick::generate_rss
	 */
	public function test_generate_rss() {
		// The date in XML format
		$date_in_rss = get_the_date( DATE_RFC822, $this->post_id );

		// Start buffering to get the output of display method
		ob_start();

		$this->instance->generate_rss( false );

		$output = ob_get_contents();
		ob_end_clean();


		// We expect this part in the generated HTML
		$expected_output  = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
		$expected_output .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
		$expected_output .= '<channel>' . PHP_EOL;
		$expected_output .= '<atom:link href="http://example.org/editors-pick.rss" rel="self" type="application/rss+xml" />' . PHP_EOL;
		$expected_output .= '<link>http://example.org</link>' . PHP_EOL;
		$expected_output .= '<description>Just another WordPress site</description>' . PHP_EOL;
		$expected_output .= '<title>Test Blog</title>' . PHP_EOL;
		$expected_output .= '<item>' . PHP_EOL;
		$expected_output .= '<title><![CDATA[generate rss]]></title>' . PHP_EOL;
		$expected_output .= '<guid isPermaLink="true">' . get_permalink( $this->post_id ) . '</guid>' . PHP_EOL;
		$expected_output .= '<link>' . get_permalink( $this->post_id ) . '</link>' . PHP_EOL;
		$expected_output .= '<description><![CDATA[Post excerpt 1]]></description>' . PHP_EOL;
		$expected_output .= '<dc:creator><![CDATA[]]></dc:creator>' . PHP_EOL;
		$expected_output .= '<pubDate>' . $date_in_rss . '</pubDate>' . PHP_EOL;
		$expected_output .= '</item>' . PHP_EOL;
		$expected_output .= '</channel>' . PHP_EOL;
		$expected_output .= '</rss>' . PHP_EOL;

		// Check if the $output contains the $expected_output
		$this->assertEquals( $expected_output, $output );
//		$this->expectOutputString( $expected_output );
	}

}
