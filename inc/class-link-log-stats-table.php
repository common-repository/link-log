<?php

/**
 * The link-log Stats Table plugin class
 */

 
// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


// use WP_List_Table to display stats
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
  
  
/**
 * The Stats Table plugin class
 */
if ( !class_exists( 'PP_Linklog_Stats_Table' ) ) { 
      
  class PP_Linklog_Stats_Table extends WP_List_Table {
    
    private  $admin_available_views;
    
    /**
	   * here we go
     */
    function __construct( $available_views ){
      global $status, $page;
      
      $this->admin_available_views = $available_views;
      
      parent::__construct( array(
        'singular'  => 'click', 
        'plural'    => 'clicks',
        'ajax'      => false
      ) );
        
    }
    
    
    
    /**
	   * this method is used to render a column if no specific method exists for that column
     */
    function column_default( $item, $column_name ){
      switch ( $column_name ) {
        case 'current':
        case 'previous':
          return '<div class="textright">' . number_format( $item[$column_name] ) . '</div>';
        default:
          return print_r( $item, true );
      }
    }
    
    
    /**
	   * this method is used to render the title column
     */
    function column_title( $item ) {
      return '<strong><span class="row-title">' . $item['title'] . '</span></strong>';
    }
    
    
    /**
	   * this method returns an array of the columns to display
     */
    function get_columns() {
      $current_view = $this->get_current_view_array();
      
      return array(
        'title' => 'Link',
        'current' => '<div class="textright">' . __( 'Clicks', 'link-log' ) . '<br /> ' . date( 'Y-m-d', $current_view['current_first'] ) . '<br /> -&nbsp;' . date( 'Y-m-d', $current_view['current_last'] ) . '</div>',
        'previous' => '<div class="textright">' . __( 'Clicks', 'link-log' ) . '<br /> ' . date( 'Y-m-d', $current_view['previous_first'] ) . '<br /> -&nbsp;' . date( 'Y-m-d', $current_view['previous_last'] ) . '</div>'
      );
    }

    
    /**
	   * this method returns an array of the columns that should be sortable
     */
    function get_sortable_columns() {
      return array(
        'title' => array( 'title', false ),
        'current' => array( 'current', false ),
        'previous' => array('previous', false )
      );
    } 

    
    /**
	   * this method queries the data to display
     */
    function prepare_items() {
      global $wpdb;
      $per_page = 50;
      $columns = $this->get_columns();
      $hidden = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );
      $current_view = $this->get_current_view_array();
     
      $current_first = date( 'Y-m-d', $current_view['current_first'] ) . ' 00:00:00';
      $current_last = date( 'Y-m-d', $current_view['current_last'] )  . ' 23:59:59';
      $previous_first = date( 'Y-m-d', $current_view['previous_first'] )  . ' 00:00:00';
      $previous_last = date( 'Y-m-d', $current_view['previous_last'] ) . ' 23:59:59';
      
      $data = $wpdb->get_results( "SELECT COALESCE( description.linklog_description, log.title ) AS title, log.current, log.previous FROM ( SELECT linklog_url AS title, SUM( IF( linklog_clicked BETWEEN '" . $current_first . "' AND '" . $current_last . "', 1, 0 ) ) AS current, SUM( IF( linklog_clicked BETWEEN '" . $previous_first . "' AND '" . $previous_last . "', 1, 0 ) ) AS previous FROM " . $wpdb->prefix . "linklog WHERE ( linklog_clicked BETWEEN '" . $current_first . "' AND '" . $current_last . "' ) OR ( linklog_clicked BETWEEN '" . $previous_first . "' AND '" . $previous_last . "' ) GROUP BY linklog_url ) AS log LEFT OUTER JOIN " . $wpdb->prefix . "linklog_descriptions as description ON log.title = description.linklog_url ORDER BY title" , ARRAY_A );
      
      function usort_reorder( $a, $b ){
        $orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title';
        $order = ( !empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
        if ( $orderby == 'title' ) {
          $result = strcasecmp( $a[$orderby], $b[$orderby] );
        } else {
          $result = $a[$orderby] - $b[$orderby];
        }
        return ( $order === 'asc' ) ? $result : -$result;
      }
      usort( $data, 'usort_reorder' );
      
      $current_page = $this->get_pagenum();
      $total_items = count($data);
      $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
      
      $this->items = $data;
      
      $this->set_pagination_args( array(
        'total_items' => $total_items,
        'per_page'    => $per_page,
        'total_pages' => ceil($total_items/$per_page)
      ) );
      
    }    
    
    
    /**
	   * this method returns an associative array listing all the views that can be used with this table
     */
    function get_views() {
      $views = array();
      $current = $this->get_current_view();

      foreach ( $this->admin_available_views as $key => $value) {
        if ( $key == $current ) {
          $class =' class="current"';
        } else {
          $class = '';
        }
        if ( $key == '7d' ) {
          $url = remove_query_arg( 'view' );
        } else {
          $url = add_query_arg( 'view' ,$key );
        }
        $views[$key] = '<a href="' . $url .'"' . $class . '>' . $value['title'] . '</a>';
      }
      return $views;
    }
    
    
    /**
	   * this method shows the message to be displayed when there are no items
     */
    function no_items() {
      echo 'No clicks during selected period';
    }
    
    
    /**
	   * custom function to detect the current view
     */
    function get_current_view() {
      $current_view = ( ! empty($_REQUEST['view']) ? $_REQUEST['view'] : '7d');
      if ( ! array_key_exists( $current_view, $this->admin_available_views ) ) {
        $current_view = '7d';
      }
      return $current_view;
    }
    
    
    /**
	   * custom function to return the current view from the link-log core class
     */
    function get_current_view_array() {
      return $this->admin_available_views[$this->get_current_view()];
    }
    
  }
}

?>