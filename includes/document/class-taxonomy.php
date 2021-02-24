<?php
/**
 * The Term Document
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomy class.
 */
class Taxonomy implements IDocument {

	/**
	 * Post object.
	 *
	 * @var Term
	 */
	protected $object;

	/**
	 * Set term object.
	 *
	 * @param Term $object Current term object.
	 */
	public function set_object( $object ) {
		$this->object = $object;
	}

	/**
	 * Retrieves the SEO title for a taxonomy.
	 *
	 * @return string The SEO title for the taxonomy.
	 */
	public function title() {
		if ( ! is_object( $this->object ) ) {
			return esc_html__( 'Page not found', 'nhg-seo' );
		}

		$title = Strategy::get_from_meta( 'term', $this->object->term_id, $this->object, 'title' );
		if ( \is_string( $title ) && '' !== $title ) {
			return $title;
		}

		return Strategy::get_title( 'term', $this->object->taxonomy, $this->object );
	}

	/**
	 * Retrieves the SEO description for a taxonomy.
	 *
	 * @return string The SEO description for the taxonomy.
	 */
	public function description() {
		if ( ! is_object( $this->object ) ) {
			return '';
		}

		$description = Strategy::get_from_meta( 'term', $this->object->term_id, $this->object, 'description' );
		if ( \is_string( $description ) && '' !== $description ) {
			return $description;
		}

		return Strategy::get_description( 'term', $this->object->taxonomy, $this->object );
	}

	/**
	 * Retrieves the robots for a taxonomy.
	 *
	 * @return string The robots for the taxonomy
	 */
	public function robots() {
		if ( ! is_object( $this->object ) ) {
			return [];
		}

		return Strategy::get_robots( 'term', $this->object->taxonomy, $this->object );
	}

	/**
	 * Retrieves the canonical URL.
	 *
	 * @return array
	 */
	public function canonical() {
		if ( ! is_object( $this->object ) ) {
			return [];
		}

		$term_link = get_term_link( $this->object, $this->object->taxonomy );

		return [
			'canonical'          => is_wp_error( $term_link ) ? '' : $term_link,
			'canonical_override' => '',
		];
	}
}
