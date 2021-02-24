<?php
/**
 * The OpenGraph Image parser.
 *
 * @package    NHG
 * @subpackage NHG\SEO\OpenGraph
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\OpenGraph;

use NHG\SEO\Helper;
use NHG\SEO\Document\Strategy;

defined( 'ABSPATH' ) || exit;

/**
 * Image.
 */
class Image {

	/**
	 * Holds the images that have been put out as OG image.
	 *
	 * @var array
	 */
	private $images = [];

	/**
	 * The parameters we have for Facebook images.
	 *
	 * @var array
	 */
	private $usable_dimensions = [
		'min_width'  => 200,
		'max_width'  => 2000,
		'min_height' => 200,
		'max_height' => 2000,
	];

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Image
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new Image();
			$instance->set_images();
		}

		return $instance;
	}

	/**
	 * Check whether we have images or not.
	 *
	 * @return bool
	 */
	public function has_images() {
		return ! empty( $this->images );
	}

	/**
	 * Return the images array.
	 *
	 * @return array
	 */
	public function get_images() {
		return $this->images;
	}

	/**
	 * Check if page is front page or singular and call the corresponding functions.
	 */
	public function set_images() {
		if ( post_password_required() ) {
			return;
		}

		switch ( true ) {
			case is_front_page():
				$this->set_user_defined_image();
				break;
			case is_home():
				$this->set_posts_page_image();
				break;
			case is_singular():
				$this->set_singular_image();
				break;
		}

		$this->set_woocommerce_images();

		// If not, get default image.
		$image = Strategy::get_option( 'default_og_image' );
		if ( ! $this->has_images() && $image ) {
			$this->add_image_by_url( $image );
		}
	}

	/**
	 * Adds an image based on a given URL, and attempts to be smart about it.
	 *
	 * @param string $url The given URL.
	 */
	public function add_image_by_url( $url ) {
		if ( empty( $url ) ) {
			return;
		}

		$attachment_id = Helper::get_attachment_by_url( $url );

		if ( $attachment_id > 0 ) {
			$this->add_image_by_id( $attachment_id );
			return;
		}

		$this->add_image( [ 'url' => $url ] );
	}

	/**
	 * Adds an image to the list by attachment ID.
	 *
	 * @param int $attachment_id The attachment ID to add.
	 */
	public function add_image_by_id( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$variations = $this->get_variations( $attachment_id );

		// If we are left without variations, there is no valid variation for this attachment.
		if ( empty( $variations ) ) {
			return;
		}

		// The variations are ordered so the first variations is by definition the best one.
		$attachment = $variations[0];
		if ( $attachment ) {
			$this->add_image( $attachment );
		}
	}

	/**
	 * Adds an image to the list by attachment ID.
	 *
	 * @param int $attachment_id The attachment ID to add.
	 */
	public function add_additional_by_id( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$attachment = $this->get_attachment_image( $attachment_id, 'full' );

		// If we are left without variations, there is no valid variation for this attachment.
		if ( empty( $attachment ) ) {
			return;
		}

		// The variations are ordered so the first variations is by definition the best one.
		if ( $attachment ) {
			$this->add_image( $attachment );
		}
	}

	/**
	 * Display an OpenGraph image tag.
	 *
	 * @param string $attachment Source URL to the image.
	 */
	public function add_image( $attachment ) {
		if ( ! is_array( $attachment ) || empty( $attachment['url'] ) ) {
			return;
		}

		$attachment_url = explode( '?', $attachment['url'] );
		if ( ! empty( $attachment_url ) ) {
			$attachment['url'] = $attachment_url[0];
		}

		if ( array_key_exists( $attachment['url'], $this->images ) ) {
			return;
		}

		$this->images[ $attachment['url'] ] = $attachment;
	}

	/**
	 * Returns the different image variations for consideration.
	 *
	 * @param  int $attachment_id The attachment to return the variations for.
	 * @return array The different variations possible for this attachment ID.
	 */
	private function get_variations( $attachment_id ) {
		$variations = [];
		$sizes      = [ 'full', 'large', 'medium_large' ];

		foreach ( $sizes as $size ) {
			if ( $variation = $this->get_attachment_image( $attachment_id, $size ) ) { // phpcs:ignore
				if ( $this->has_usable_dimensions( $variation ) ) {
					$variations[] = $variation;
				}
			}
		}

		return $variations;
	}

	/**
	 * Retrieve an image to represent an attachment.
	 *
	 * @param  int          $attachment_id Image attachment ID.
	 * @param  string|array $size          Optional. Image size. Accepts any valid image size, or an array of width
	 *                                    and height values in pixels (in that order). Default 'thumbnail'.
	 * @return false|array
	 */
	private function get_attachment_image( $attachment_id, $size = 'thumbnail' ) {
		$image = wp_get_attachment_image_src( $attachment_id, $size );

		// Early Bail!
		if ( ! $image ) {
			return false;
		}

		list( $src, $width, $height ) = $image;

		return [
			'id'     => $attachment_id,
			'url'    => $src,
			'width'  => $width,
			'height' => $height,
		];
	}

	/**
	 * Checks whether an img sizes up to the parameters.
	 *
	 * @param  array $dimensions The image values.
	 * @return bool True if the image has usable measurements, false if not.
	 */
	private function has_usable_dimensions( $dimensions ) {
		foreach ( [ 'width', 'height' ] as $param ) {
			$minimum = $this->usable_dimensions[ 'min_' . $param ];
			$maximum = $this->usable_dimensions[ 'max_' . $param ];

			$current = $dimensions[ $param ];
			if ( ( $current < $minimum ) || ( $current > $maximum ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets the user-defined image of the post.
	 *
	 * @param null|int $post_id The post ID to get the images for.
	 */
	private function set_user_defined_image( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_queried_object_id();
		}

		if ( $this->has_images() ) {
			return;
		}

		$this->set_featured_image( $post_id );
	}

	/**
	 * Retrieve the featured image.
	 *
	 * @param int $post_id The post ID.
	 */
	private function set_featured_image( $post_id = null ) {
		if ( $post_id && has_post_thumbnail( $post_id ) ) {
			$attachment_id = get_post_thumbnail_id( $post_id );
			$this->add_image_by_id( $attachment_id );
		}
	}

	/**
	 * Get the images of the posts page.
	 */
	private function set_posts_page_image() {
		if ( $this->has_images() ) {
			return;
		}

		$post_id = get_option( 'page_for_posts' );
		$this->set_featured_image( $post_id );
	}

	/**
	 * Get the images of the singular post.
	 *
	 * @param null|int $post_id The post ID to get the images for.
	 */
	private function set_singular_image( $post_id = null ) {
		$post_id = is_null( $post_id ) ? get_queried_object_id() : $post_id;

		$this->set_user_defined_image( $post_id );

		if ( $this->has_images() ) {
			return;
		}

		$this->set_content_image( get_post( $post_id ) );
	}

	/**
	 * Adds the first usable attachment image from the post content.
	 *
	 * @param object $post The post object.
	 */
	private function set_content_image( $post ) {
		$content = sanitize_post_field( 'post_content', $post->post_content, $post->ID );

		// Early bail!
		if ( '' === $content || false === Helper::str_contains( '<img', $content ) ) {
			return;
		}

		$images = [];
		if ( preg_match_all( '`<img [^>]+>`', $content, $matches ) ) {
			foreach ( $matches[0] as $img ) {
				if ( preg_match( '`src=(["\'])(.*?)\1`', $img, $match ) ) {
					if ( isset( $match[2] ) ) {
						$images[] = $match[2];
					}
				}
			}
		}

		$images = array_unique( $images );
		if ( empty( $images ) ) {
			return;
		}

		foreach ( $images as $image ) {
			$attachment_id = Helper::get_attachment_by_url( $image );
			if ( 0 === $attachment_id ) {
				$this->add_image( $image );
			} else {
				$this->add_image_by_id( $attachment_id );
			}

			// If an image has been added, we're done.
			if ( $this->has_images() ) {
				return;
			}
		}
	}

	/**
	 * Set woocommerce related images.
	 */
	private function set_woocommerce_images() {
		global $wp_query;

		if ( ! function_exists( 'is_product_category' ) || is_product_category() ) {
			$this->add_image_by_id(
				get_term_meta(
					$wp_query->get_queried_object()->term_id,
					'thumbnail_id',
					true
				)
			);
		}

		if ( is_singular( 'product' ) ) {
			$product     = wc_get_product();
			$attachments = $product->get_gallery_image_ids();

			if ( ! is_array( $attachments ) || empty( $attachments ) ) {
				return;
			}

			foreach ( $attachments as $attachment_id ) {
				$this->add_additional_by_id( $attachment_id );
			}
		}
	}
}
