<?php
/**
 * The Strategy.
 *
 * @package    NHG
 * @subpackage NHG\SEO\Document
 * @author     Shakeeb Ahmed <me@shakeebahmed.com>
 */

namespace NHG\SEO\Document;

defined( 'ABSPATH' ) || exit;

/**
 * Strategy.
 */
class Strategy {

	/**
	 * Settings.
	 *
	 * @var array
	 */
	private static $settings = [
		'post'    => [
			'post'    => [
				'title'       => '{title} {page} {sep} {sitename}',
				'description' => '{excerpt}',
				'robots'      => [],
			],
			'default' => [
				'title'       => '{title} {page} {sep} {sitename}',
				'description' => '{excerpt}',
				'robots'      => [],
			],
		],
		'term'    => [
			'default' => [
				'title'       => '{term} {page} {sep} {sitename}',
				'description' => '{term_description}',
				'robots'      => [],
			],
		],
		'archive' => [
			'default' => [
				'title'       => '{pt_plural} Archive {page} {sep} {sitename}',
				'description' => '{pt_plural} Archive {page} {sep} {sitename}',
				'robots'      => [],
			],
		],
		'search'  => [
			'title'       => 'Searched for {searchphrase} {page} {sep} {sitename}',
			'description' => '',
			'robots'      => [],
		],
	];


	/**
	 * Get title.
	 *
	 * @param  string       $object_type    Object type.
	 * @param  string       $object_subtype Object type.
	 * @param  object|array $source         Possible object to pull variables from.
	 * @return string
	 */
	public static function get_title( $object_type, $object_subtype, $source = [] ) {
		return self::get_from_options( $object_type, $object_subtype, $source, 'title' );
	}

	/**
	 * Get description.
	 *
	 * @param  string       $object_type    Object type.
	 * @param  string       $object_subtype Object type.
	 * @param  object|array $source         Possible object to pull variables from.
	 * @return string
	 */
	public static function get_description( $object_type, $object_subtype, $source = [] ) {
		return self::get_from_options( $object_type, $object_subtype, $source, 'description' );
	}

	/**
	 * Get robots.
	 *
	 * @param  string       $object_type    Object type.
	 * @param  string       $object_subtype Object type.
	 * @param  object|array $source         Possible object to pull variables from.
	 * @return string
	 */
	public static function get_robots( $object_type, $object_subtype, $source = [] ) {
		return self::get_from_options( $object_type, $object_subtype, $source, 'robots' );
	}

	/**
	 * Get description.
	 *
	 * @param  string       $type        Object type.
	 * @param  string       $object_type Object type.
	 * @param  object|array $source      Possible object to pull variables from.
	 * @return string
	 */
	public static function get_others( $type, $object_type, $source = [] ) {
		return self::get_from_options( $object_type, false, $source, $type );
	}

	/**
	 * Simple function to use to pull data from $options.
	 *
	 * All string pulled from options will be run through replace_vars function.
	 *
	 * @param  string       $object_type    Object type.
	 * @param  string       $object_subtype Object type.
	 * @param  object|array $source         Possible object to pull variables from.
	 * @param  string       $type           Setting type.
	 * @return string
	 */
	public static function get_from_options( $object_type, $object_subtype, $source = [], $type = 'title' ) {
		$value = self::get_setting( $object_type, $object_subtype, $type );

		if ( ! empty( $value ) && in_array( $type, [ 'title', 'description' ], true ) ) {
			return \NHG\SEO\Replacer::replace( $value, $source );
		}

		return $value;
	}

	/**
	 * Simple function to use to pull data from $options.
	 *
	 * All string pulled from options will be run through replace_vars function.
	 *
	 * @param  string       $object_type Object type.
	 * @param  string       $object_id   Object type.
	 * @param  object|array $source      Possible object to pull variables from.
	 * @param  string       $type        Setting type.
	 * @return string
	 */
	public static function get_from_meta( $object_type, $object_id, $source = [], $type = 'title' ) {
		$meta_key = "_nhg_seo_{$type}";
		if ( ! metadata_exists( $object_type, $object_id, $meta_key ) ) {
			return false;
		}

		$value = get_metadata( $object_type, $object_id, $meta_key, true );
		if ( ! empty( $value ) && in_array( $type, [ 'title', 'description' ], true ) ) {
			if ( 'title' === $type ) {
				$value = $value . ' {sep} {sitename}';
			}

			return \NHG\SEO\Replacer::replace( $value, $source );
		}

		return $value;
	}

	/**
	 * Get strategy
	 *
	 * @param  string $object_type    Object type.
	 * @param  string $object_subtype Object type.
	 * @param  string $type           Setting type.
	 * @return string
	 */
	public static function get_setting( $object_type, $object_subtype, $type ) {
		if ( ! isset( self::$settings[ $object_type ] ) ) {
			return false;
		}

		$value = false;
		if ( false === $object_subtype ) {
			$value = self::$settings[ $object_type ];
		} elseif ( isset( self::$settings[ $object_type ][ $object_subtype ] ) ) {
			$value = self::$settings[ $object_type ][ $object_subtype ];
		}

		if ( ! $value ) {
			if ( ! isset( self::$settings[ $object_type ]['default'] ) ) {
				return false;
			}

			$value = self::$settings[ $object_type ]['default'];
		}

		if ( isset( $value[ $type ] ) ) {
			return $value[ $type ];
		}

		return 'robots' === $type ? [] : '';
	}

	/**
	 * Get option.
	 *
	 * @param  string $id      Option ID.
	 * @return mixed
	 */
	public static function get_option( $id ) {
		return get_field( $id, 'option' );
	}
}
