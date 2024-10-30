<?php 
 
/**
 * The link-log Plugin Uninstall
 */
  
  
// If this file is accessed withot plugin uninstall is requested, abort  
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ||	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ ) ) ) {
  status_header( 404 );
  exit;
}


// Load plugin and start uninstall
require_once( plugin_dir_path( __FILE__ ) . '/loader.php' );
pp_linklog()->uninstall();

?>