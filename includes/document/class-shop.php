<?php
/**
 * The Shop Document
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Shop.
 */
class Shop extends Singular {

	/**
	 * Retrieves the WooCommerce Shop SEO title.
	 *
	 * @return string
	 */
	public function title() {
		if ( ! is_object( $this->object ) ) {
			return esc_html__( 'Page not found', 'nhg-seo' );
		}

		return Strategy::get_title( 'archive', 'product', $this->object );
	}

	/**
	 * Retrieves the WooCommerce Shop SEO description.
	 *
	 * @return string
	 */
	public function description() {
		if ( ! is_object( $this->object ) ) {
			return '';
		}

		return Strategy::get_description( 'archive', 'product', $this->object );
	}

	/**
	 * Retrieves the WooCommerce Shop robots.
	 *
	 * @return string
	 */
	public function robots() {
		if ( ! is_object( $this->object ) ) {
			return [];
		}

		$robots = Strategy::get_robots( 'archive', 'product', $this->object );

		// `noindex` these conditions.
		$noindex_private            = 'private' === $this->object->post_status;
		$no_index_subpages          = is_paged();
		$noindex_password_protected = ! empty( $this->object->post_password );

		if ( $noindex_private || $noindex_password_protected || $no_index_subpages ) {
			$robots['index'] = 'noindex';
		}

		return $robots;
	}
}
