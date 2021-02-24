<?php
/**
 * Plugin Name:       NHG SEO
 * Version:           1.0.0
 * Plugin URI:        https://netthandelsgruppen.no/
 * Description:       Custom seo plugin.
 * Author:            Shakeeb Ahmed
 * Author URI:        https://shakeebahmed.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.0
 * Tested up to:      5.5
 *
 * @package      NHG
 * @copyright    Copyright (C) 2020, NHG.
 * @link         https://netthandelsgruppen.no/
 */

defined( 'ABSPATH' ) || exit;

require_once 'vendor/autoload.php';

\NHG\SEO\Plugin::get()
	->set_paths( __FILE__ )
	->setup();
