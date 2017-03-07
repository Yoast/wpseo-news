<?php

class WPSEO_News_Sitemap_Test extends WPSEO_News_UnitTestCase {

	private $instance;

	/**
	 * Setting up the instance of WPSEO_News_Admin_Page
	 */
	public function setUp() {
		parent::setUp();

		// Be sure eventually hook will be removed
		remove_action( 'wpseo_news_options', array( $this, 'set_default_keywords' ) );

		$this->instance = new WPSEO_News_Sitemap();
	}

	/**
	 * @covers WPSEO_News_Sitemap::add_to_index
	 */
	public function test_add_to_index_no_items() {
		$input = '';
		$output = $this->instance->add_to_index( $input );
		$this->assertEquals( $input, $output );
	}

	/**
	 * @covers WPSEO_News_Sitemap::add_to_index
	 */
	public function test_add_to_index() {

		/**
		 * We need an item to be present to get output.
		 */
		$this->factory->post->create(
			array(
				'post_title' => 'generate rss',
			)
		);

		$output = $this->instance->add_to_index( '' );

		$output_date     = new DateTime( get_lastpostdate( 'gmt' ),
			new DateTimeZone( new WPSEO_News_Sitemap_Timezone() ) );
		$expected_output = '<sitemap>' . "\n";
		$expected_output .= '<loc>' . home_url( 'news-sitemap.xml' ) . '</loc>' . "\n";
		$expected_output .= '<lastmod>' . htmlspecialchars( $output_date->format( 'c' ) ) . '</lastmod>' . "\n";
		$expected_output .= '</sitemap>' . "\n";

		$this->assertEquals( $expected_output, $output );

	}

	/**
	 * Check what happens if no posts are added
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_empty() {

		$output = $this->instance->build_sitemap();

		$expected_output = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
</urlset>';

		$this->assertEquals( $expected_output, $output );

	}

	/**
	 * Check what happens if there is one post added
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_NOT_empty() {
		$post_id = $this->factory->post->create( array(
			'post_title' => 'generate rss',
		) );

		$output = $this->instance->build_sitemap();

		// This is what we expect
		$expected_output = "<url>\n";
		$expected_output .= "\t<loc>" . get_permalink( $post_id ) . "</loc>\n";
		$expected_output .= "\t<news:news>\n";
		$expected_output .= "\t\t<news:publication>\n";
		$expected_output .= "\t\t\t<news:name>Test Blog</news:name>\n";
		$expected_output .= "\t\t\t<news:language>en</news:language>\n";
		$expected_output .= "\t\t</news:publication>\n";
		$expected_output .= "\t\t<news:publication_date>" . get_the_date( 'Y-m-d', $post_id ) . "</news:publication_date>\n";
		$expected_output .= "\t\t<news:title><![CDATA[generate rss]]></news:title>\n";
		$expected_output .= "\t\t<news:keywords><![CDATA[]]></news:keywords>\n";
		$expected_output .= "\t</news:news>\n";
		$expected_output .= "</url>";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_post_excluded() {
		// Create post
		$post_id = $this->factory->post->create();

		// Set meta value to exclude
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-exclude', 'on' );

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
</urlset>';

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}


	/**
	 * Check what happens if there is one post added with keywords
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_keywords() {
		// Create post
		$post_id = $this->factory->post->create();

		// Set meta value to exclude
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-keywords', 'keyword' );

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[keyword]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with only a single tag
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_tags() {
		// Create post
		$post_id = $this->factory->post->create();

		// Set meta value to exclude
		$term = wp_insert_term( 'tag', 'post_tag' );
		wp_set_post_terms( $post_id, array( $term['term_id'] ) );

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[tag]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added and there is are default keywords present
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_default_keywords() {

		$this->default_keywords();

		// Create post
		$post_id = $this->factory->post->create();

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[unit, test]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );

		unset( $this->instance );


		// Be sure eventually hook will be removed
		remove_action( 'wpseo_news_options', array( $this, 'set_default_keywords' ) );
	}

	/**
	 * Check what happens if there is one post added with keywords and tags
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_keywords_AND_tags() {

		// Create post
		$post_id = $this->factory->post->create();

		// Set keyword for this post
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-keywords', 'keyword' );

		// Add tag to the post
		$term = wp_insert_term( 'tag', 'post_tag' );
		wp_set_post_terms( $post_id, array( $term['term_id'] ) );

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[keyword, tag]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with keywords and tags
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_tags_AND_default_keywords() {
		// Create post
		$post_id = $this->factory->post->create();

		// Add tag to the post
		$term = wp_insert_term( 'tag', 'post_tag' );
		wp_set_post_terms( $post_id, array( $term['term_id'] ) );

		// Adding default keywords
		$this->default_keywords();

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[tag, unit, test]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with tags, keywords and default keywords
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_keywords_and_default_keywords() {

		// Create post
		$post_id = $this->factory->post->create();

		// Set keyword for this post
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-keywords', 'keyword' );

		// Adding default keywords
		$this->default_keywords();

		// Building the sitemap
		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[keyword, unit, test]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with keywords and default keywords
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_keywords_AND_tags_AND_default_keywords() {

		// Create post
		$post_id = $this->factory->post->create();

		// Set keyword for this post
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-keywords', 'keyword' );

		// Add tag to the post
		$term = wp_insert_term( 'tag', 'post_tag' );
		wp_set_post_terms( $post_id, array( $term['term_id'] ) );

		// Adding default keywords
		$this->default_keywords();

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[keyword, tag, unit, test]]></news:keywords>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if post added with some simular tags, keywords and default keywords
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_SIMULAR_keywords_AND_tags_AND_default_keywords() {

		// Create post
		$post_id = $this->factory->post->create();

		// Set keyword for this post
		update_post_meta( $post_id, '_yoast_wpseo_newssitemap-keywords', 'simular,keyword' );

		// Adding default keywords
		$this->default_keywords();

		// Add tag to the post
		$term1 = wp_insert_term( 'tag', 'post_tag' );
		$term2 = wp_insert_term( 'simular', 'post_tag' );
		wp_set_post_terms( $post_id, array( $term1['term_id'], $term2['term_id'] ) );

		$output = $this->instance->build_sitemap();

		// The expected output
		$expected_output = "\t\t<news:keywords><![CDATA[simular, keyword, tag, unit, test]]></news:keywords>\n";
		// Check if the $output contains the $expected_output

		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with a image in its content
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_image() {

		$image   = home_url( 'tests/assets/yoast.png' );
		$post_id = $this->factory->post->create( array(
			'post_title'   => 'with images',
			'post_content' => '<img src="' . $image . '" />',
		) );

		$output = $this->instance->build_sitemap();

		$expected_output = "\t<image:image>\n";
		$expected_output .= "\t\t<image:loc>" . $image . "</image:loc>\n";
		$expected_output .= "\t</image:image>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with a image in its content
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITHOUT_featured_image_restricted() {

		$image   = home_url( 'tests/assets/yoast.png' );
		$post_id = $this->factory->post->create( array(
			'post_title'   => 'featured image',
			'post_content' => '<img src="' . $image . '" />',
		) );

		$featured_image = home_url( 'tests/assets/yoast_featured.png' );
		$thumbnail_id   = $this->create_attachment( $featured_image, $post_id );

		update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );

		$output = $this->instance->build_sitemap();

		$expected_output = "\t</news:news>\n";
		$expected_output .= "\t<image:image>\n";
		$expected_output .= "\t\t<image:loc>" . $image . "</image:loc>\n";
		$expected_output .= "\t</image:image>\n";
		$expected_output .= "\t<image:image>\n";
		$expected_output .= "\t\t<image:loc>" . $featured_image . "</image:loc>\n";
		$expected_output .= "\t\t<image:title>attachment</image:title>\n";
		$expected_output .= "\t</image:image>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check what happens if there is one post added with a image in its content
	 *
	 * @covers WPSEO_News_Sitemap::build_sitemap
	 */
	public function test_sitemap_WITH_featured_image_restricted() {

		add_action( 'wpseo_news_options', array( $this, 'restrict_featured_image' ) );

		$this->instance = new WPSEO_News_Sitemap();

		$image   = home_url( 'tests/assets/yoast.png' );
		$post_id = $this->factory->post->create( array(
			'post_title'   => 'featured image',
			'post_content' => '<img src="' . $image . '" />',
		) );

		$featured_image = home_url( 'tests/assets/yoast_featured.png' );
		$thumbnail_id   = $this->create_attachment( $featured_image, $post_id );

		update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );

		$output = $this->instance->build_sitemap();

		$expected_output = "\t</news:news>\n";
		$expected_output .= "\t<image:image>\n";
		$expected_output .= "\t\t<image:loc>" . $featured_image . "</image:loc>\n";
		$expected_output .= "\t\t<image:title>attachment</image:title>\n";
		$expected_output .= "\t</image:image>\n";

		// Check if the $output contains the $expected_output
		$this->assertContains( $expected_output, $output );
	}

	/**
	 * Check that the sitemap uses the default name of news when no news post type is present
	 *
	 * @covers WPSEO_News::news_sitemap_basename
	 */
	public function test_sitemap_default_name() {
		$this->assertEquals( 'news', WPSEO_News_Sitemap::news_sitemap_basename() );
	}

	/**
	 * Check that the sitemap name uses the fallback name for the sitemap when a post type of News exists
	 *
	 * @covers WPSEO_News::news_sitemap_basename
	 */
	public function test_sitemap_name_on_post_type() {
		register_post_type( 'news' );

		$this->assertEquals( 'yoast-news', WPSEO_News_Sitemap::news_sitemap_basename() );
	}

	/**
	 * Check that the sitemap name uses the YOAST_NEWS_SITEMAP_BASENAME constant value
	 *
	 * @covers WPSEO_News::news_sitemap_basename
	 */
	public function test_sitemap_name_on_constant() {
		define( 'YOAST_NEWS_SITEMAP_BASENAME', 'unit-test-news' );

		$this->assertEquals( 'unit-test-news', WPSEO_News_Sitemap::news_sitemap_basename() );
	}


	public function restrict_featured_image( $options ) {
		$options['restrict_sitemap_featured_img'] = true;

		return $options;
	}

	public function set_default_keywords( $options ) {
		$options['default_keywords'] = 'unit, test';

		return $options;
	}

	private function default_keywords() {
		// Adding the hook to override options
		add_action( 'wpseo_news_options', array( $this, 'set_default_keywords' ) );

		// Re-instance the sitemap class
		$this->instance = new WPSEO_News_Sitemap();
	}


	private function create_attachment( $image, $post_id = 0 ) {
		return $this->factory->post->create( array(
			'post_title'     => 'attachment',
			'post_name'      => 'attachment',
			'guid'           => $image,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image/png',
			'parent_id'      => $post_id,
		) );
	}
}
