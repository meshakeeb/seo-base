<?php
/**
 * The Open Graph Base.
 *
 * @package    NHG
 * @subpackage NHG\SEO\OpenGraph
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\OpenGraph;

use NHG\SEO\Document\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Open Graph.
 */
class Base {

	/**
	 * Holds network slug.
	 *
	 * @var string
	 */
	public $network = '';

	/**
	 * Get title
	 *
	 * @return bool|string
	 */
	public function get_title() {
		return Document::get()->get_title();
	}

	/**
	 * Get description.
	 *
	 * @return bool|string
	 */
	public function get_description() {
		return Document::get()->get_description();
	}

	/**
	 * Get canonical.
	 *
	 * @return bool|string
	 */
	public function get_canonical() {
		return Document::get()->get_canonical();
	}

	/**
	 * Internal function to output social meta tags.
	 *
	 * @param  string $property Property attribute value.
	 * @param  string $content  Content attribute value.
	 * @return bool
	 */
	public function tag( $property, $content ) {
		if ( empty( $content ) || ! is_scalar( $content ) ) {
			return false;
		}

		$tag = 'facebook' === $this->network ? 'property' : 'name';
		printf(
			'<meta %1$s="%2$s" content="%3$s">' . "\n",
			esc_attr( $tag ),
			esc_attr( $property ),
			esc_attr( $content )
		);
	}
}
