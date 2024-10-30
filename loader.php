<?php

/**
 * The Link Log Plugin Loader
 *
 * @since 5
 *
 **/
 
// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Load files
 */
require_once( plugin_dir_path( __FILE__ ) . '/inc/class-link-log.php' );

if ( is_admin() ) {
  
  require_once( plugin_dir_path( __FILE__ ) . '/inc/class-link-log-stats-table.php' );
  
}


/**
 * Main Function
 */
function pp_linklog() {

  return PP_LinkLog::getInstance( array(
    'file'    => dirname( __FILE__ ) . '/link-log.php',
    'slug'    => basename( pathinfo( __FILE__, PATHINFO_DIRNAME ) ),
    'name'    => 'Link Log',
    'version' => '5.0.2'
  ) );
    
}


/**
 * Run the plugin
 */
pp_linklog();


?>