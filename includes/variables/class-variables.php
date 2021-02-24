<?php
/**
 * The Variables.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Variables
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Variables;

defined( 'ABSPATH' ) || exit;

/**
 * Variables.
 */
class Variables extends Post {

	/**
	 * Hold arguments.
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * From variable id.
	 *
	 * @param  string $id   Uniquer ID of variable, for example custom.
	 * @param  array  $args Context object, can be post, taxonomy or term.
	 * @return string
	 */
	public static function from( $id, $args ) {
		static $instance;

		if ( is_null( $instance ) ) {
			$instance = new Variables();
		}

		$method = "get_{$id}";
		if ( ! method_exists( $instance, $method ) ) {
			return null;
		}

		$instance->args = $args;
		$replacement    = $instance->$method();
		$instance->args = [];

		return $replacement;
	}
}
