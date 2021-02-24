<?php
/**
 * The Replacer.
 *
 * @package    NHG
 * @subpackage NHG\SEO
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO;

use NHG\SEO\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Replacer.
 */
class Replacer {

	/**
	 * Default post data.
	 *
	 * @var array
	 */
	private $defaults = [
		'ID'            => '',
		'name'          => '',
		'post_author'   => '',
		'post_content'  => '',
		'post_date'     => '',
		'post_excerpt'  => '',
		'post_modified' => '',
		'post_title'    => '',
		'taxonomy'      => '',
		'term_id'       => '',
		'term404'       => '',
		'filename'      => '',
	];

	/**
	 * Replace `{variables}` with context-dependent value.
	 *
	 * @param  string $string The string containing the {variables}.
	 * @param  array  $args   Context object, can be post, taxonomy or term.
	 * @return string
	 */
	public static function replace( $string, $args = [] ) {
		// Bail early.
		if ( ! Helper::str_contains( '{', $string ) ) {
			return $string;
		}

		$replacer = new Replacer();

		return $replacer->do( $string, $args );
	}

	/**
	 *  Replace `{variables}` with context-dependent value.
	 *
	 * @param  string $string  The string containing the {variables}.
	 * @param  array  $args    Context object, can be post, taxonomy or term.
	 * @return string
	 */
	public function do( $string, $args = [] ) {
		$this->pre_replace( $args );
		$replacements = $this->set_up_replacements( $string );

		// Do the replacements.
		if ( is_array( $replacements ) && [] !== $replacements ) {
			$string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}

		if ( isset( $replacements['{sep}'] ) && '' !== $replacements['{sep}'] ) {
			$q_sep  = preg_quote( $replacements['{sep}'], '`' );
			$string = preg_replace( '`' . $q_sep . '(?:\s*' . $q_sep . ')*`u', $replacements['{sep}'], $string );
		}

		return $string;
	}

	/**
	 * Run prior to replacement.
	 *
	 * @param array $args    Context object, can be post, taxonomy or term.
	 */
	private function pre_replace( $args ) {
		// Setup arguments.
		$this->args = (object) wp_parse_args( $args, $this->defaults );
		if ( ! empty( $this->args->post_content ) ) {
			$this->args->post_content = $this->strip_shortcodes( $this->args->post_content );
		}
		if ( ! empty( $this->args->post_excerpt ) ) {
			$this->args->post_excerpt = $this->strip_shortcodes( $this->args->post_excerpt );
		}
	}

	/**
	 * Get the replacements for the variables.
	 *
	 * @param  string $string String to parse for variables.
	 * @return array Retrieved replacements.
	 */
	private function set_up_replacements( $string ) {
		$replacements = [];
		if ( ! preg_match_all( '/{(.*?)}/iu', $string, $matches ) ) {
			return $replacements;
		}

		foreach ( $matches[1] as $index => $variable ) {
			$value = Variables\Variables::from( $variable, $this->args );
			if ( false !== $value ) {
				$replacements[ $matches[0][ $index ] ] = $value;
			}
		}

		return $replacements;
	}

	/**
	 * Strip all shortcodes active or orphan.
	 *
	 * @param  string $content Content to remove shortcodes from.
	 * @return string
	 */
	private function strip_shortcodes( $content ) {
		if ( ! Helper::str_contains( '[', $content ) ) {
			return $content;
		}

		// Remove Caption shortcode.
		$content = \preg_replace( '#\s*\[caption[^]]*\].*?\[/caption\]\s*#is', '', $content );
		return preg_replace( '~\[\/?.*?\]~s', '', $content );
	}
}
