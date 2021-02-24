<?php
/**
 * The Singular Document
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Singular class.
 */
class Singular implements IDocument {

	/**
	 * Post object.
	 *
	 * @var WP_Post
	 */
	protected $object;

	/**
	 * Set post object.
	 *
	 * @param WP_Post $object Current post object.
	 */
	public function set_object( $object ) {
		$this->object = $object;
	}

	/**
	 * Retrieves the SEO title.
	 *
	 * @return string
	 */
	public function title() {
		if ( ! is_object( $this->object ) ) {
			return esc_html__( 'Page not found', 'nhg-seo' );
		}

		$title = Strategy::get_from_meta( 'post', $this->object->ID, $this->object, 'title' );
		if ( \is_string( $title ) && '' !== $title ) {
			return $title;
		}

		return Strategy::get_title( 'post', $this->object->post_type, $this->object );
	}

	/**
	 * Retrieves the SEO description.
	 *
	 * @return string
	 */
	public function description() {
		if ( ! is_object( $this->object ) ) {
			return '';
		}

		$description = Strategy::get_from_meta( 'post', $this->object->ID, $this->object, 'description' );
		if ( \is_string( $description ) && '' !== $description ) {
			return $description;
		}

		$description = Strategy::get_description( 'post', $this->object->post_type, $this->object );
		if ( \is_string( $description ) && '' !== $description ) {
			return $description;
		}

		if ( is_singular( 'product' ) ) {
			return 'Finn alt du trenger av treningstøy, kosttilskudd, lavkarbo, smartmat og treningsutstyr. Stort utvalg av alt innen trening. Rask levering til hele Norge.';
		}

		return '';
	}

	/**
	 * Retrieves the robots.
	 *
	 * @return string
	 */
	public function robots() {
		if ( ! is_object( $this->object ) ) {
			return [];
		}

		$robots = Strategy::get_robots( 'post', $this->object->post_type, $this->object );

		// `noindex` these conditions.
		$noindex_private            = 'private' === $this->object->post_status;
		$no_index_subpages          = is_paged();
		$noindex_password_protected = ! empty( $this->object->post_password );

		if ( $noindex_private || $noindex_password_protected || $no_index_subpages ) {
			$robots['index'] = 'noindex';
		}

		return $robots;
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		$canonical = get_permalink( $this->object->ID );

		// Fix paginated pages canonical, but only if the page is truly paginated.
		$current_page = get_query_var( 'page' );
		if ( $current_page > 1 ) {
			$number_of_pages = ( substr_count( get_queried_object()->post_content, '<!--nextpage-->' ) + 1 );
			if ( $number_of_pages && $current_page <= $number_of_pages ) {
				$canonical = Document::get()->get_canonical_paged( $canonical, $current_page );
			}
		}

		return [
			'canonical'         => $canonical,
			'canonical_unpaged' => $canonical,
		];
	}
}
