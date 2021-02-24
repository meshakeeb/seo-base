<?php
/**
 * The Term.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Variables
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Variables;

defined( 'ABSPATH' ) || exit;

/**
 * Term.
 */
class Term extends Misc {

	/**
	 * Get the term name to use as a replacement.
	 *
	 * @return string|null
	 */
	public function get_term() {
		return ! empty( $this->args->taxonomy ) && ! empty( $this->args->name ) ? $this->args->name : null;
	}

	/**
	 * Get the term description to use as a replacement.
	 *
	 * @return string|null
	 */
	public function get_term_description() {
		if ( ! isset( $this->args->term_id ) || empty( $this->args->taxonomy ) ) {
			return null;
		}

		$term_desc = get_term_field( 'description', $this->args->term_id, $this->args->taxonomy );
		return '' !== $term_desc ? $term_desc : null;
	}
}
