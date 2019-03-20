<?php
/**
 * Yoast SEO: News plugin file.
 *
 * @package WPSEO_News\XML_Sitemaps
 */

/**
 * Handle images used in News.
 */
class WPSEO_News_Sitemap_Images {

	/**
	 * The current item.
	 *
	 * @var object
	 */
	private $item;

	/**
	 * The output that will be returned.
	 *
	 * @var string
	 */
	private $output = '';

	/**
	 * The options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Storage for the images.
	 *
	 * @var array
	 */
	private $images;

	/**
	 * Setting properties and build the item.
	 *
	 * @param object $item    News post object.
	 * @param array  $options The options.
	 */
	public function __construct( $item, $options ) {
		$this->item    = $item;
		$this->options = $options;

		$this->parse_item_images();
	}

	/**
	 * Return the output, because the object is converted to a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->output;
	}

	/**
	 * Parsing the images from the item.
	 */
	private function parse_item_images() {
		$this->get_item_images();

		if ( ! empty( $this->images ) ) {
			foreach ( $this->images as $src => $img ) {
				$this->parse_item_image( $src, $img );
			}
		}
	}

	/**
	 * Getting the images for the given $item.
	 */
	private function get_item_images() {
		$restrict_sitemap_featured_img = isset( $this->options['restrict_sitemap_featured_img'] ) ? $this->options['restrict_sitemap_featured_img'] : false;
		if ( ! $restrict_sitemap_featured_img && preg_match_all( '/<img [^>]+>/', $this->item->post_content, $matches ) ) {
			$this->get_images_from_content( $matches );
		}

		// Also check if the featured image value is set.
		$post_thumbnail_id = get_post_thumbnail_id( $this->item->ID );
		if ( '' !== $post_thumbnail_id ) {
			$this->get_item_featured_image( $post_thumbnail_id );
		}
	}

	/**
	 * Getting the images from the content.
	 *
	 * @param array $matches Images found in the content.
	 */
	private function get_images_from_content( $matches ) {
		foreach ( $matches[0] as $img ) {
			if ( ! preg_match( '/src=("|\')([^"|\']+)("|\')/', $img, $match ) ) {
				continue;
			}

			$src = $this->parse_image_source( $match[2] );
			if ( ! empty( $src ) && ! isset( $this->images[ $src ] ) ) {
				$this->images[ $src ] = $this->parse_image( $img );
			}
		}
	}

	/**
	 * Parsing the image source.
	 *
	 * @param string $src Image Source.
	 *
	 * @return string|void
	 */
	private function parse_image_source( $src ) {

		static $home_url;

		if ( is_null( $home_url ) ) {
			$home_url = home_url();
		}

		if ( strpos( $src, 'http' ) !== 0 && strpos( $src, '//' ) !== 0 ) {
			if ( $src[0] !== '/' ) {
				return null;
			}

			$src = $home_url . $src;
		}

		if ( $src !== esc_url( $src, null, 'sanitize' ) ) {
			return null;
		}

		return $src;
	}

	/**
	 * Setting title and alt for image and returns them in an array.
	 *
	 * @param string $img Image HTML.
	 *
	 * @return array
	 */
	private function parse_image( $img ) {
		$image = array();
		if ( preg_match( '/title=("|\')([^"\']+)("|\')/', $img, $match ) ) {
			$image['title'] = str_replace( array( '-', '_' ), ' ', $match[2] );
		}

		if ( preg_match( '/alt=("|\')([^"\']+)("|\')/', $img, $match ) ) {
			$image['alt'] = str_replace( array( '-', '_' ), ' ', $match[2] );
		}

		return $image;
	}

	/**
	 * Parse the XML for given image.
	 *
	 * @param string $src Image source.
	 * @param array  $img Image array.
	 *
	 * @return void
	 */
	private function parse_item_image( $src, $img ) {
		/**
		 * Filter: 'wpseo_xml_sitemap_img_src' - Allow changing of sitemap image src.
		 *
		 * @api string $src The image source.
		 *
		 * @param object $item The post item.
		 */
		$src = apply_filters( 'wpseo_xml_sitemap_img_src', $src, $this->item );

		$this->output .= "\t<image:image>\n";
		$this->output .= "\t\t<image:loc>" . htmlspecialchars( $src ) . "</image:loc>\n";

		if ( ! empty( $img['title'] ) ) {
			$this->output .= "\t\t<image:title>" . htmlspecialchars( $img['title'] ) . "</image:title>\n";
		}

		if ( ! empty( $img['alt'] ) ) {
			$this->output .= "\t\t<image:caption>" . htmlspecialchars( $img['alt'] ) . "</image:caption>\n";
		}

		$this->output .= "\t</image:image>\n";
	}

	/**
	 * Getting the featured image.
	 *
	 * @param integer $post_thumbnail_id Thumbnail ID.
	 *
	 * @return void
	 */
	private function get_item_featured_image( $post_thumbnail_id ) {

		$attachment = $this->get_attachment( $post_thumbnail_id );

		if ( empty( $attachment ) ) {
			return;
		}

		$image = array();

		if ( ! empty( $attachment['title'] ) ) {
			$image['title'] = $attachment['title'];
		}

		if ( ! empty( $attachment['alt'] ) ) {
			$image['alt'] = $attachment['alt'];
		}

		if ( ! empty( $attachment['src'] ) ) {
			$this->images[ $attachment['src'] ] = $image;
		}
		elseif ( ! empty( $attachment['href'] ) ) {
			$this->images[ $attachment['href'] ] = $image;
		}
	}

	/**
	 * Get attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	private function get_attachment( $attachment_id ) {
		// Get attachment.
		$attachment = get_post( $attachment_id );

		// Check if we've found an attachment.
		if ( is_null( $attachment ) ) {
			return array();
		}

		// Return properties.
		return array(
			'title'       => $attachment->post_title,
			'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
			'href'        => get_permalink( $attachment->ID ),
			'src'         => $attachment->guid,
		);
	}
}
