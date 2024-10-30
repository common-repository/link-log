<?php

/**
 * The Link Log Plugin
 *
 * Link Log allows you to track which external links your visitors click on
 *
 * @wordpress-plugin
 * Plugin Name: Smart External Link Click Monitor [Link Log]
 * Plugin URI: https://wordpress.org/plugins/link-log/
 * Description: Log external link clicks
 * Version: 5.0.2
 * Author: Peter Raschendorfer
 * Author URI: https://profiles.wordpress.org/petersplugins/
 * Text Domain: link-log
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Loader
 */
require_once( plugin_dir_path( __FILE__ ) . '/loader.php' );


/**
 * Theme Function to return a link log URL
 */
function get_linklog_url( $url ) {
  
  return pp_linklog()->make_url( $url );
  
}


/**
 * Theme Function to print a link log URL
 */
function the_linklog_url( $url ) {
  
  echo pp_linklog()->make_url( $url );
  
}

?>