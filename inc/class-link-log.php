<?php

/**
 * The Link Log core plugin class
 */

 
// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * The core plugin class
 */
if ( !class_exists( 'PP_Linklog' ) ) { 

  class PP_Linklog {
    
    /**
     * Instance
     *
     * @since  5
     * @var    singleton
     * @access protected
     */
    protected static $_instance = null;
    
    
    /**
     * Plugin Main File Path and Name
     * was $_file before
     * removed in v5
     */
     
    
    /**
     * Plugin Name
     *
     * @since  1
     * @var    string
     * @access private
     */
    private $plugin_name;
    
    
    /**
     * Plugin Slug
     *
     * @since  1
     * @var    string
     * @access private
     */
    private $plugin_slug;
    
    
    /**
     * Plugin Version
     *
     * @since  5
     * @var    int
     * @access private
     * was $version before
     */
    private $plugin_version;
    
    public $settings;
    private $admin_handle;
    private $analysis_handle;
    private $gdprpage_handle;
    protected $nonce_action;
      
    
     /**
     * Init the Class 
     *
     * @since 1
     * @see getInstance
     */
    protected function __construct( $settings ) {
     
      $this->plugin_file    = $settings['file'];
      $this->plugin_slug    = $settings['slug'];
      $this->plugin_name    = $settings['name'];
      $this->plugin_version = $settings['version'];
      
      $this->get_settings();
      $this->init();
      
    }
    
    
    /**
     * Prevent Cloning
     *
     * @since 5
     */
    protected function __clone() {}
    
    
    /**
	   * Get the Instance
     *
     * @since 5
     * @param array $settings {
     *   @type string $file    Plugin Main File Path and Name
     *   @type string $slug    Plugin Slug
     *   @type string $name    Plugin Name
     *   @type int    $version Plugin Verion
     * }
     * @return singleton
     */
    public static function getInstance( $settings ) {
     
      if ( null === self::$_instance ) {

        self::$_instance = new self( $settings );
        
      }
      
      return self::$_instance;
      
    }
    
    
    /**
	   * get plugin file
     *
     * @since 5
     * @access public
     */
    public function get_plugin_file() {
      
      return $this->plugin_file;
      
    }
    
    
    /**
	   * get plugin slug
     *
     * @since 5
     * @access public
     */
    public function get_plugin_slug() {
      
      return $this->plugin_slug;
      
    }
    
    
    /**
	   * get plugin name
     *
     * @since 5
     * @access public
     */
    public function get_plugin_name() {
      
      return $this->plugin_name;
      
    }
    
    
    /**
	   * get plugin version
     *
     * @since 5
     * @access public
     */
    public function get_plugin_version() {
      
      return $this->plugin_version;
      
    }
    
    
    /**
     * get the settings
     */
    private function get_settings() {
      
      $this->settings = array();
      $this->settings['urlparam'] = get_option( 'swcc_linklog_urlparam', 'goto' );
      $this->settings['iplockparam'] = get_option( 'swcc_linklog_iplockparam', '0' );
      $this->settings['omitbotsparam'] = ( ( get_option( 'swcc_linklog_omitbotsparam', '0' ) == '1' ) ? true : false );
      $this->settings['nofollowparam'] = ( ( get_option( 'swcc_linklog_nofollowparam', '0' ) == '1' ) ? true : false );
      $this->settings['tracktelparam'] = ( ( get_option( 'swcc_linklog_tracktelparam', '0' ) == '1' ) ? true : false );
      $this->settings['installed_version'] = get_option( 'swcc_linklog_version', 'NONE' );
      $this->settings['automationparam'] = get_option( 'swcc_linklog_automationparam', 'AUTO' );
      $this->settings['menutitle'] = get_option( 'swcc_linklog_menutitle', $this->get_plugin_name() );
      $this->settings['pagetitle'] = get_option( 'swcc_linklog_pagetitle', 'Link Click Analysis' );
      $this->settings['gdpr_compliant'] = ( ( get_option( 'swcc_linklog_gdpr_compliant', '0' ) == '1' ) ? true : false );
      
    }
    
    
    /**
     * do plugin init 
     */
    private function init() {
      
      $this->nonce_action = 'linklog-make-gdpr-compliant';
      
      register_activation_hook( $this->get_plugin_file(), array( $this, 'install' ) );
      add_action( 'plugins_loaded', array( $this, 'update' ) ) ;
      add_action( 'wpmu_new_blog', array( $this, 'new_blog' ), 10, 6 );
      add_filter( 'query_vars', array( $this, 'add_queryvar' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_css' ) );
      add_action( 'init', array( $this, 'redirect' ), 1 );
      add_action( 'init', array( $this, 'add_text_domain' ) );
      add_action( 'admin_init', array( $this, 'register_settings' ) );
      add_action( 'admin_menu', array( $this, 'adminmenu' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_js' ) );
      add_action( 'wp_ajax_linklog_list_descriptions', array( &$this, 'ajax_list_descriptions' ) );
      add_action( 'wp_ajax_pp_linklog_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );
      
      if ( $this->settings['automationparam'] != 'NEVER' ) {
        
        // If Automation is set to "AUTO" or "CUSTOM" we have to process all posts
        // IF Automation is set to "NEVER" we do not add the filter so does not run needless
        add_filter( 'the_content', array( $this, 'parse_content' ), 99 );
        
      }
      
      
      if ( $this->settings['automationparam'] == 'CUSTOM' ) {
        
        // If Automation is set to "CUSTOM" we have to add a meta box to post and page Writing Screen 
        add_action( 'add_meta_boxes', array( $this, 'add_customization_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_customization_meta_data' ) );
        
      }
      
      
      // @since 3
      add_action( 'wp_ajax_linklog_make_gdpr_compliant_now', array( &$this, 'make_gdpr_compliant_now' ) );
      
      // @since 3
      add_action( 'admin_notices', array( $this, 'admin_notices' ) );
      
    }
     
     
    /**
     * add text domain
     */
    function add_text_domain() {  
    
      load_plugin_textdomain( 'link-log' );
      
    }
    
    
    /**
     * parse content and change all external urls
     */
    function parse_content( $content ) {
      
      global $post;
      $process = true;
      
      if ( $this->settings['automationparam'] == 'CUSTOM' ) {
        
        if ( get_post_meta( $post->ID, '_linklog_custom_process_this', true ) != '1' ) {
          
          $process = false;
        
        }
        
      }
      
      if ( $process ) {
      
        $content = preg_replace_callback( "/<a(\s[^>]*)href=[\"\']??([^\" >]*?)[\"\']??([^>]*)>(.*)<\/a>/siU", array( $this, 'change_link' ), $content );
      
      }
      
      return $content;
    
    }
    
    
    /**
     * callback function to change the link
     */
    function change_link( $linkparts ) {
      
      $add = '';
      
      if ( $this->settings['nofollowparam'] ) {
      
        if ( strpos( str_replace( "'", '"', strtolower( $linkparts[1] . $linkparts[3] ) ), 'rel="nofollow"' ) === false ) {
        
          $add = ' rel="nofollow"';
        
        }
      
      }
      
      return '<a' . $linkparts[1].' href="' . $this->make_url( $linkparts[2] ) . '"' . $linkparts[3] . $add . '>' . $linkparts[4] . '</a>'; 
    
    }
    
    
    /**
     * make the url
     */
    function make_url ( $url ) {
      
      if ( ( ( substr( strtolower( $url ), 0, 7 ) == 'http://' || substr( strtolower( $url ), 0, 8 ) == 'https://' ) &&  substr( strtolower( $url ), 0, strlen( home_url() ) ) != strtolower( home_url() ) && substr( strtolower( $url ), 0, strlen( admin_url() ) ) != strtolower( admin_url() ) && substr( strtolower( $url ), 0, strlen( content_url() ) ) != strtolower( content_url() ) && substr( strtolower( $url ), 0, strlen( plugins_url() ) ) != strtolower( plugins_url() ) ) || ( true == $this->settings['tracktelparam'] && 'tel:' == substr( strtolower( $url ), 0, 4 ) ) ) {
        
        // Replace bad characters...
        $url = str_replace( '&#038;', '&', str_replace( '&amp;', '&', str_replace( array( "\n", "\r") , '', $url ) ) );
        $url = home_url() . '?' . $this->settings['urlparam'] . '=' . rtrim( strtr( base64_encode ( $this->xor_this( $url ) ), '+/', '-_' ), '=' );
        
      }
      
      return $url;
      
    }
    
    
    /**
     * very simple encryption / decryption
     */
    function xor_this( $string ) {
      
      if ( defined( 'NONCE_SALT' ) && NONCE_SALT != '' ) {
        
        // use NONCE_SALT 
        $key = NONCE_SALT;
        
      } else {
        
        $key = 'XRaXwx-6o-sJO~llAXj>}-Eto,yrW&qdj+]TE_wN7?bl|+`0JZ/d^u evtiRNQEQ';
        
      }
      
      for($i = 0; $i < strlen( $string ); $i++)  {
        
          $string[$i] = ( $string[$i] ^ $key[$i % strlen( $key )] );
          
      }  
      
      return $string;
      
    }

    
    /**
     * add given url parameter to query vars
     */
    function add_queryvar ($qvars) {
      
      $qvars[] =  $this->settings['urlparam'];
      return $qvars;
      
    }
    
    
    /**
     * get ip address
     */
    private function get_client_ip() {
      
      $ipaddress = '';
      
      if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        
      } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        
      } elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
        
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        
      } elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
        
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        
      } elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
        
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
        
      } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        
        $ipaddress = $_SERVER['REMOTE_ADDR'];
        
      }
      
      $ipaddress = trim( $ipaddress );
      
      if ( false === filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ) {
      
        $ipaddress = '';
      
      }
        
      $ipaddress = esc_attr( $ipaddress );
      
      return $ipaddress;
    
    }
    
    
    /**
     * hash ip address for gdpr compliance
     * @access private
     * @since  3
     * @param  string $ipaddress the ip address to encrypt
     * @return string
     */
    function hash_ip ( $ipaddress = '' ) {
    
      if ( '' != $ipaddress ) {
        $ipaddress = '-' . hash( 'sha256', $ipaddress, false );
      }
      
      return $ipaddress;
      
    }
    
    
    /**
     * is visitor a bot?
     */
    function is_bot() {
      
      $bots = array( 
        'googlebot', 
        'msnbot', 
        'baiduspider', 
        'bingbot', 
        'slurp', 
        'yahoo', 
        'askjeeves', 
        'fastcrawler', 
        'infoseek', 
        'lycos', 
        'yandex', 
        'teoma', 
        'ia_archiver', 
        'webmon', 
        'webcrawler', 
        'findlink',
        'exabot',
        'gigabot',
        'msrbot',
        'seekbot',
        'yacybot',
        'mj12bot',
        'yanga',
        'domaincrawler',
        'facebookexternalhit',
        'openindexspider',
        'backlinkcrawler',
        'alexa',
        'froogle',
        'inktomi',
        'looksmart',
        'firefly',
        'ask jeeves',
        'webfindbot',
        'zyborg',
        'feedfetcher-google',
        'twitturls',
        'r6_feedfetcher',
        'netcraftsurveyagent',
        'printfulbot',
        'twitterbot',
        'butterfly',
        'tweetmemebot',
        'yandexbot',
        'searchmetricsbot',
        'pingdom',
        'pinterest',
        'bitlybot',
        'sitecheck'
      );
      
      return ( ( preg_match( '/' . implode( '|', $bots ) . '/', strtolower( $_SERVER['HTTP_USER_AGENT'] ) ) > 0) ? true : false );
    }
    
    
    /**
     * log and redirect
     */
    function redirect() {
      
      if ( !is_admin() and isset( $_GET ) ) {
        
        $urlparam =  $this->settings['urlparam'];
        
        if ( isset( $_GET[$urlparam] ) ) {
          
          // goto key exitst
          $url = str_replace ( ' ', '+', $this->xor_this ( base64_decode( str_pad( strtr( $_GET[$urlparam], '-_,', '+/=' ), strlen( $_GET[$urlparam] ) % 4, '=', STR_PAD_RIGHT ) ) ) );
          $ip = $this->get_client_ip();
          $is_bot = $this->is_bot();
          
          // redirect immediately
          ignore_user_abort( true );
          set_time_limit( 0 );
          header( 'HTTP/1.1 303 See Other' ); // @since 3 - use 303 redirect to avoid browser caching
          header('Cache-Control: no-store, no-cache, must-revalidate'); // @since 3 - avoid browser caching
          header('Expires: Thu, 01 Jan 1970 00:00:00 GMT'); // @since 3 - avoid browser caching
          header( 'Location: ' . $url, true );
          header( 'Connection: close', true );
          header( "Content-Encoding: none\r\n" );
          header( 'Content-Length: 0', true );
          flush();
          ob_flush();
          session_write_close();
          
          // do DB stuff after client was redirected
          $iplock = $this->settings['iplockparam'];
          $url = rtrim( esc_sql( $url ), '/' );
          $insert = true;
          global $wpdb;
          
          if ( $this->settings['omitbotsparam'] && $is_bot ) {
          
            $insert = false;
          
          }
          
          
          // @since 3
          // hash ip address
          $ip = $this->hash_ip( $ip );
         
            
          if ( $insert && $iplock != 0 && $ip != '' ) {
            
            $test = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'linklog WHERE linklog_url = "' . $url . '" AND linklog_ip = "' . $ip . '" AND linklog_clicked >=  DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -' . $iplock . ' SECOND)' );
            
            if ( ! is_null($wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'linklog WHERE linklog_url = "' . $url . '" AND linklog_ip = "' . $ip . '" AND linklog_clicked >=  DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -' . $iplock . ' SECOND)' ) ) ) {
              
              $insert = false;
           
            }
          
          }
          
          if ( $insert ) {
            
            $wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'linklog ( linklog_url, linklog_ip ) VALUES ( "' .  $url . '", "' . $ip . '" )' ) ;
            
          }
          
          exit;
          
        }
        
      }
      
    }
    
    
    /**
     * add admin menu entry
     */
    function adminmenu() {
      
      // settings page
      $this->admin_handle = add_options_page( 'link-log', 'link-log', 'manage_options', 'link-log-settings', array( $this, 'admin_settings' ) );
      
      // log page
      $this->analysis_handle = add_menu_page( $this->settings['menutitle'], $this->settings['menutitle'], 'publish_pages', 'link-log-log', array( $this, 'admin_log' ), 'dashicons-admin-links', '25.00001' );
      
      // @since 3
      // gdpr compliance page
      $this->gdprpage_handle = add_submenu_page( null, 'link-log', '', 'activate_plugins', 'link-log-gdpr', array( $this, 'make_gdpr_compliant' ) );
      
    }
    
    
    /**
     * show settings in admin / settings / link-log
     */
    function admin_settings() {
      
      global $wpdb;
      
      if ( !current_user_can( 'manage_options' ) )  {
        
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        
      }
     
      if ( isset( $_POST ) && isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], 'page=link-log-settings' ) && strpos( $_POST['_wp_http_referer'], 'tab=titles' ) ) {
        
        foreach ( $_POST['linkdescription'] as $key => $value ) {
          
          $url = base64_decode( $key );
          $desc = $value;
          
          if ( empty( $desc) || '' == $desc ) {
            
            $desc = $url;
            
          }
          
          $wpdb->query( 'UPDATE ' . $wpdb->prefix . 'linklog_descriptions SET linklog_description = "' . $desc . '" WHERE linklog_url = "' . $url . '" AND linklog_description != "' . $desc . '";' );
        } 
        
        echo '<div class="updated"><p>' . __( 'Link Descriptions updated', 'link-log' ) .'</p></div>';
        
      }
      
      $url = admin_url( 'options-general.php?page=' . $_GET['page'] . '&tab=' );
      $current_tab = 'general';
      
      if ( isset( $_GET['tab'] ) ) {
        
        $current_tab = $_GET['tab'];
        
      }
      
      if ( ! in_array( $current_tab, array( 'general', 'advanced', 'auto', 'strings', 'titles' ) ) ) {
        
        $current_tab = 'general';
        
      }
      
      if ( 'titles' != $current_tab ) {
        
        $action = 'options.php';
        
      } else {
        
        $action = '';
        
      }
      
      ?>
      <div class="wrap pp-admin-page-wrapper link-log" id="pp-link-log-settings">
       	<div class="pp-admin-notice-area"><div class="wp-header-end"></div></div>
		<div class="pp-admin-page-header">
			<div class="pp-admin-page-title"><h1><?php echo $this->get_plugin_name(); ?></h1></div>
		</div>
        <h2 class="nav-tab-wrapper">
          <a href="<?php echo $url . 'general'; ?>" class="nav-tab<?php if ( 'general' == $current_tab ) { echo ' nav-tab-active'; } ?>"><span class="dashicons dashicons-admin-generic"></span><span class="text"><?php _e( 'General', 'link-log' ); ?></span></a>
          <a href="<?php echo $url . 'advanced'; ?>" class="nav-tab<?php if ( 'advanced' == $current_tab ) { echo ' nav-tab-active'; } ?>"><span class="dashicons dashicons-admin-tools"></span><span class="text"><?php _e( 'Advanced', 'link-log' );?></span></a>
          <a href="<?php echo $url . 'auto'; ?>" class="nav-tab<?php if ( 'auto' == $current_tab ) { echo ' nav-tab-active'; } ?>"><span class="dashicons dashicons-lightbulb"></span><span class="text"><?php _e( 'Automation', 'link-log' ); ?></span></a>
          <a href="<?php echo $url . 'strings'; ?>" class="nav-tab<?php if ( 'strings' == $current_tab ) { echo ' nav-tab-active'; } ?>"><span class="dashicons dashicons-analytics"></span><span class="text"><?php _e( 'Analysis Page', 'link-log' ); ?></span></a>
          <a href="<?php echo $url . 'titles'; ?>" class="nav-tab<?php if ( 'titles' == $current_tab ) { echo ' nav-tab-active'; } ?>"><span class="dashicons dashicons-edit"></span><span class="text"><?php _e( 'Link Descriptions', 'link-log' ); ?></span></a>
        </h2>
        <div class="postbox">
          <div class="inside">
            <form method="post" action="<?php echo $action; ?>" class="linklog_settings_form">
              <?php
                settings_fields( 'linklog_settings_' . $current_tab );   
                do_settings_sections( 'linklog_settings_section_' . $current_tab );

                if ( 'titles' != $current_tab ) {
                  
                  submit_button(); 
                  
                }
              ?>
            </form>     
          </div>
        </div>
		<div class="postbox">
			<div class="inside">
				<h2>PLEASE NOTE</h2>
				<p>Development, maintenance and support of this plugin has been retired. You can use this plugin as long as is works for you. Thanks for your understanding.<br />Regards, Peter</p>
			</div>
		</div>
      </div>  
      <?php
    }
    
    /**
     * show the nav icons on admin page
     * @since 5
     */
    function show_nav_icons( $icons ) {
       
      foreach ( $icons as $icon ) {
         
        echo '<a href="' . $icon['link'] . '" title="' . $icon['title'] . '"><span class="dashicons ' . $icon['icon'] . '"></span><span class="text">' . $icon['title'] . '</span></a>';
         
      }
      
    }
    
    
    /**
     * show admin page log
     */
    function admin_log() {
      
      if ( !current_user_can( 'publish_pages' ) )  {
        
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        
      }
      
      $available_views = array(   
        '7d' => array( 
          'title' => __( 'Last 7 Days', 'link-log' ),
          'current_first' => strtotime( '-8 days' ),
          'current_last' => strtotime( '-1 day' ),
          'previous_first' => strtotime( '-16 days' ),
          'previous_last' => strtotime( '-9 days' )
        ),    
        '14d' => array( 
          'title' => __( 'Last 14 Days', 'link-log' ),
          'current_first' => strtotime( '-15 days' ),
          'current_last' => strtotime( '-1 day' ),
          'previous_first' => strtotime( '-30 days' ),
          'previous_last' => strtotime( '-16 days' )
        ),
        '30d' => array( 
          'title' => __( 'Last 30 Days', 'link-log' ),
          'current_first' => strtotime( '-31 days' ),
          'current_last' => strtotime( '-1 day' ),
          'previous_first' => strtotime( '-62 days' ),
          'previous_last' => strtotime( '-32 days' )
        ),
        'cm' => array( 
          'title' => __( 'Current Month', 'link-log' ),
          'current_first' => mktime( 0, 0, 0, date( 'm' ), 1, date( 'Y' ) ),
          'current_last' => time(),
          'previous_first' => mktime( 0, 0, 0, date( 'm' ) - 1, 1, date( 'Y' ) ),
          'previous_last' => strtotime( '-1 month' )
        ),
        'lm' => array( 
          'title' => __( 'Last Month', 'link-log' ),
          'current_first' => mktime( 0, 0, 0, date( 'm' ) - 1, 1, date( 'Y' ) ),
          'current_last' => mktime( 0, 0, 0, date( 'm' ), 0, date( 'Y' ) ),
          'previous_first' => mktime( 0, 0, 0, date( 'm' ) - 2, 1, date( 'Y' ) ),
          'previous_last' => mktime( 0, 0, 0, date( 'm' ) - 1, 0, date( 'Y' ) ),
        ),
        'cy' => array( 
          'title' => __( 'Current Year', 'link-log' ),
          'current_first' => mktime( 0, 0, 0, 1, 1, date( 'Y' ) ),
          'current_last' => time(),
          'previous_first' => mktime( 0, 0, 0, 1, 1, date( 'Y' ) - 1 ),
          'previous_last' => strtotime( '-1 year' )
        ),
        'ly' => array( 
          'title' => __( 'Last Year', 'link-log' ),
          'current_first' => mktime( 0, 0, 0, 1, 1, date( 'Y' ) - 1 ),
          'current_last' => mktime( 0, 0, 0, 12, 31, date( 'Y' ) - 1 ),
          'previous_first' => mktime( 0, 0, 0, 1, 1, date( 'Y' ) - 2 ),
          'previous_last' => mktime( 0, 0, 0, 12, 31, date( 'Y' ) - 2 )
        )
      );
      
      $pp_linklog_stats_table = new PP_Linklog_Stats_Table( $available_views );
      $pp_linklog_stats_table->prepare_items();
      
      ?>
      <div class="wrap pp-admin-page-wrapper">
        <h1><span><?php echo $this->settings['pagetitle']; ?></span></h1>
        <form id="linklogstats" method="get">
          <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
          <?php $pp_linklog_stats_table->views(); ?>
          <?php $pp_linklog_stats_table->display() ?>
        </form>
      </div>
      <?php
      
    }
    
    
    /**
     * show admin page to make plugin gdpr compliant
     * @since  3
     * @return void
     */
    function make_gdpr_compliant() {
      
      if ( !current_user_can( 'activate_plugins' ) )  {
        
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        
      }
      
      if ( $this->is_gdpr_compliant() ) {
        ?>
        <div class="wrap">
          <h1><?php _e( 'The Link Log plugin is already GDPR compliant.', 'link-log' ); ?></h1>
        </div>
        <?php
        return;
      }
      
      $nonce = wp_create_nonce( $this->nonce_action );
      
      ?>
      <div class="wrap">
        <h1 style="line-height: 48px; padding-top: 0; padding-bottom: 0; background-image: url(<?php echo plugins_url( 'assets/img/gdpr.png', $this->get_plugin_file() ); ?>); background-repeat: no-repeat; padding-left: 110px;"><?php _e( 'Make Link Log GDPR compliant', 'link-log' ); ?></h1>
        <div style="margin: 32px; border: 2px solid #4B6F87; background-color: #fff; padding: 12px;">
          <p><?php _e( 'As of version 3 the Link Log plugin encrypts the visitors IP-address for data protection reasons before storing it into the database. The IP-addresses are used exclusively to identify multiple clicks from the same IP-address and exclude them from counting. Encrypting the IP-addresses does not change the functionality. The encrypted IP-addresses can not be decrypted. This enables you to use this plugin according to the GDPR.', 'link-log' ); ?></p>
          <p><?php _e( '<strong>The previously existing data are not encrypted!</strong> To make the Link Log plugin GDPR compliant all IP-addresses currently stored in the database will be encrypted.', 'link-log' ); ?></p>
        </div>
        <div style="margin: 32px; background-color: #4B6F87; padding: 12px; color: #fff">
          <p style="line-height: 36px"><span class="dashicons dashicons-warning" style="color: #fff; font-size: 36px; width: 36px; height: 36px;"></span> <?php _e( 'These changes cannot be reversed!', 'link-log' ); ?></p>
        </div>
        <div style="margin: 32px; background-color: #4B6F87; padding: 12px; color: #fff">
          <p style="line-height: 36px"><span class="dashicons dashicons-warning" style="color: #fff; font-size: 36px; width: 36px; height: 36px;"></span> <?php _e( 'It is strongly recommended to back up the database before proceeding because changes are made to the database!', 'link-log' ); ?></p>
        </div>
        <div id="linklog_gdpr_ajax_area">
          <input style="height: auto; padding: 16px 32px; display: block; margin-left: auto; margin-right: auto;" type="button" name="make_gdpr_compliant_now" id="make_gdpr_compliant_now" class="button button-primary" value="<?php _e( 'Encrypt existing database entries now', 'link-log' ); ?>" />
        </div>
      </div>
      <script type='text/javascript'>
        var rowcounter = 0;
        jQuery( '#make_gdpr_compliant_now' ).click( function() {
          jQuery( '#linklog_gdpr_ajax_area' ).html( '<?php _e( 'Processing started...', 'link-log' ); ?>' );
          jQuery( '#linklog_gdpr_ajax_area' ).css( { 'margin': '32px', 'background-color': '#FFCB3D', 'padding': '12px' } );
          make_gdpr_compliant();
        });
        function make_gdpr_compliant() {
          jQuery.ajax( { 
            type: 'POST', 
            url: ajaxurl, 
            data: { 'action': 'linklog_make_gdpr_compliant_now', 'nonce': '<?php echo $nonce; ?>' },
            success: function( response ) {
              rows = parseInt( response );
              if ( rows == -999999 ) {
                jQuery( '#linklog_gdpr_ajax_area' ).css( { 'background-color' : '#FF0000', 'color' : '#fff', 'text-align' : 'center' } );
                jQuery( '#linklog_gdpr_ajax_area' ).html( '<?php _e( 'Security Check failed! Please reload page and try again.', 'link-log' ); ?>' );
              } else if ( rows > 0 ) {
                rowcounter += rows;
                jQuery( '#linklog_gdpr_ajax_area' ).html( '<?php _e( 'Rows processed', 'link-log' ); ?>: ' + rowcounter );
                make_gdpr_compliant();
              } else {
                jQuery( '#linklog_gdpr_ajax_area' ).css( { 'background-color' : '#2A8E00', 'color' : '#fff', 'text-align' : 'center' } );
                jQuery( '#linklog_gdpr_ajax_area' ).html( '<?php _e( 'Done! All stored IP-addresses are encrypted now.', 'link-log' ); ?>' );
              }
            }
          });
        }
      </script>
      <?php

    }
    
    /**
     * show admin notices
     * @since  3
     * @return void
     */
    function admin_notices() {
      
      // show dgpr message
      if ( ! $this->is_gdpr_compliant() && current_user_can( 'activate_plugins' ) && get_current_screen()->id != $this->gdprpage_handle ) {
        ?>
        <div class="notice notice-error">
          <p style="line-height: 48px; background-image: url(<?php echo plugins_url( 'assets/img/gdpr.png', $this->get_plugin_file() ); ?>); background-repeat: no-repeat; padding-left: 110px;"><strong><a href="<?php echo admin_url( 'admin.php?page=link-log-gdpr' ); ?>"><?php _e( 'Make Link Log plugin GDPR compliant', 'link-log' ); ?></a></p>
        </div>
        <?php
      }
      
      
      // invite to follow me
      // in 10 days earliest after gdpr compliance
      if ( $this->is_gdpr_compliant() && ! get_option( 'pp-link-log-admin-notice-1-start' ) ) {
        update_option( 'pp-link-log-admin-notice-1-start', time() + 10 * 24 * 60 * 60 );
      }
      if ( get_option( 'pp-link-log-admin-notice-1-start' ) <= time() ) {
        if ( current_user_can( 'manage_options' ) && get_user_meta( get_current_user_id(), 'pp-link-log-admin-notice-1', true ) != 'dismissed' ) {
          ?>
          <div class="notice is-dismissible pp-link-log-admin-notice" id="pp-link-log-admin-notice-1">
            <p><img src="<?php echo plugins_url( 'assets/img/pluginicon.png', $this->get_plugin_file() ); ?>" style="width: 48px; height: 48px; float: left; margin-right: 20px" /><strong><?php _e( 'Do you like the Link Log plugin?', 'link-log' ); ?></strong><br /><?php _e( 'Follow me:', 'link-log' ); ?> <a class="dashicons dashicons-facebook-alt" href="https://www.facebook.com/petersplugins" title="<?php _e( 'Authors facebook Page', 'link-log' ); ?>"></a><div class="clear"></div></p>
          </div>
          <?php
        }
      }
      
      // ask for rating
      // in 30 days at the earliest after gdpr compliance
      if ( $this->is_gdpr_compliant() && ! get_option( 'pp-link-log-admin-notice-2-start' ) ) {
        update_option( 'pp-link-log-admin-notice-2-start', time() + 30 * 24 * 60 * 60 );
      }
      if ( get_option( 'pp-link-log-admin-notice-2-start' ) <= time() ) {
        if ( current_user_can( 'manage_options' ) && get_user_meta( get_current_user_id(), 'pp-link-log-admin-notice-2', true ) != 'dismissed' ) {
          ?>
          <div class="notice is-dismissible pp-link-log-admin-notice" id="pp-link-log-admin-notice-2">
            <p><img src="<?php echo plugins_url( 'assets/img/pluginicon.png', $this->get_plugin_file() ); ?>" style="width: 48px; height: 48px; float: left; margin-right: 20px" /><?php _e( 'If you like the Link Log plugin please support my work with giving it a good rating so that other users know it is helpful for you. Thanks.', 'link-log' ); ?><br /><a href="https://wordpress.org/support/plugin/<?php echo $this->plugin_slug; ?>/reviews/#new-post" title="<?php _e( 'Please rate plugin', 'link-log' ); ?>"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a><div class="clear"></div></p>
          </div>
          <?php
        }
      }
    }
    
    
    /**
     * register settings
     */
    function register_settings() {
      
      add_settings_section( 'linklog-settings-general', '', null, 'linklog_settings_section_general' );
      register_setting( 'linklog_settings_general', 'swcc_linklog_urlparam', array( $this, 'admin_urlparam_validate' ) );
      register_setting( 'linklog_settings_general', 'swcc_linklog_iplockparam' );
      register_setting( 'linklog_settings_general', 'swcc_linklog_omitbotsparam' );
      add_settings_field( 'swcc_linklog_settings_urlparam', '', array( $this, 'admin_urlparam' ), 'linklog_settings_section_general', 'linklog-settings-general', array( 'label_for' => 'swcc_linklog_urlparam' ) );
      add_settings_field( 'swcc_linklog_settings_iplockparam', '', array( $this, 'admin_iplockparam' ), 'linklog_settings_section_general', 'linklog-settings-general', array( 'label_for' => 'swcc_linklog_iplockparam' ) );
      add_settings_field( 'swcc_linklog_settings_omitbotsparam', '', array( $this, 'admin_omitbotsparam' ), 'linklog_settings_section_general', 'linklog-settings-general', array( 'label_for' => 'swcc_linklog_omitbotsparam' ) );
      
      add_settings_section( 'linklog-settings-advanced', '', null, 'linklog_settings_section_advanced' );
      register_setting( 'linklog_settings_advanced', 'swcc_linklog_nofollowparam' );
      register_setting( 'linklog_settings_advanced', 'swcc_linklog_tracktelparam' );
      add_settings_field( 'swcc_linklog_settings_nofollow', '', array( $this, 'admin_nofollowparam' ), 'linklog_settings_section_advanced', 'linklog-settings-advanced', array( 'label_for' => 'swcc_linklog_nofollowparam' ) );
      add_settings_field( 'swcc_linklog_settings_tracktel', '', array( $this, 'admin_tracktel' ), 'linklog_settings_section_advanced', 'linklog-settings-advanced', array( 'label_for' => 'swcc_linklog_tracktelparam' ) );
      
      add_settings_section( 'linklog-settings-auto', '', null, 'linklog_settings_section_auto' );
      register_setting( 'linklog_settings_auto', 'swcc_linklog_automationparam' );
      add_settings_field( 'swcc_linklog_settings_automationparam', '', array( $this, 'admin_automationparam' ), 'linklog_settings_section_auto', 'linklog-settings-auto', array( 'label_for' => 'swcc_linklog_automationparam' ) );
      
      add_settings_section( 'linklog-settings-strings', '', null, 'linklog_settings_section_strings' );
      register_setting( 'linklog_settings_strings', 'swcc_linklog_menutitle', array( $this, 'admin_menutitle_validate' ) );
      register_setting( 'linklog_settings_strings', 'swcc_linklog_pagetitle', array( $this, 'admin_pagetitle_validate' ) );
      add_settings_field( 'swcc_linklog_settings_menutitle', '', array( $this, 'admin_menutitle' ), 'linklog_settings_section_strings', 'linklog-settings-strings', array( 'label_for' => 'swcc_linklog_menutitle' ) );
      add_settings_field( 'swcc_linklog_settings_pagetitle', '', array( $this, 'admin_pagetitle' ), 'linklog_settings_section_strings', 'linklog-settings-strings', array( 'label_for' => 'swcc_linklog_pagetitle' ) );
      
      add_settings_section( 'linklog-settings-titles', '', array( $this, 'admin_section_titles_fullcontent' ), 'linklog_settings_section_titles' );
    
    }
   
    
    /**
     * handle the settings field : url
     */
    function admin_urlparam() {
      echo '<p>';
      echo __( 'Parameter Name to use in URL' , 'link-log' );
      echo '<input class="regular-text" type="text" name="swcc_linklog_urlparam" id="swcc_linklog_urlparam" value="' .  $this->settings['urlparam'] . '" /><p class="description">e.g. <code>' . get_home_url() . '?<strong style="text-decoration: underline;">' . $this->settings['urlparam'] . '</strong>=ENCRYPTEDURL</code></p>';
      echo '</p>';
    }
    
    
    /**
     * check input : url
     */
    function admin_urlparam_validate( $input ) {
      
      if ( empty( $input ) ) {
        
        $new = $this->settings['urlparam'];
        
      }  elseif ( ctype_alnum( $input ) ) {
        
        $new = $input;
        
      } else {
        
        $new =  $this->settings['urlparam'];
        add_settings_error( 'link-log-settings-url-err', 'link-log-settings-url-error', __( 'The parameter name must only contain letters and/or digits.', 'link-log' ), 'error' );	
      
      }
      
      return $new;
    
    }
    
    
    /**
     * handle the settings field : iplock
     */
    function admin_iplockparam() {
    
      $curvalue = $this->settings['iplockparam'];
      echo '<p>';
      echo __( 'IP Lock Setting', 'link-log' );
      echo '<select name="swcc_linklog_iplockparam" id="swcc_linklog_iplockparam">';
      echo '<option value="0"' . ( ( $curvalue == 0 ) ? ' selected="selected"' : '' ) . '>' . __(' Count all clicks', 'link-log' ) . '</option>';
      echo '<option value="30"' . ( ( $curvalue == 30 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 30 seconds', 'link-log' ) .'</option>';
      echo '<option value="60"' . ( ( $curvalue == 60 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 1 minute', 'link-log' ) . '</option>';
      echo '<option value="300"' . ( ( $curvalue == 300 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 5 minutes', 'link-log' ) . '</option>';
      echo '<option value="900"' . ( ( $curvalue == 900 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 15 minutes', 'link-log' ) . '</option>';
      echo '<option value="1800"' . ( ( $curvalue == 1800 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 30 minutes', 'link-log' ) . '</option>';
      echo '<option value="3600"' . ( ( $curvalue == 3600 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 1 hour', 'link-log' ) . '</option>';
      echo '<option value="10800"' . ( ( $curvalue == 10800 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 3 hours', 'link-log' ) . '</option>';
      echo '<option value="21600"' . ( ( $curvalue == 21600 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 6 hours', 'link-log' ) . '</option>';
      echo '<option value="43200"' . ( ( $curvalue == 43200 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 12 hours', 'link-log' ) . '</option>';
      echo '<option value="86400"' . ( ( $curvalue == 86400 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 24 hours', 'link-log' ) . '</option>';
      echo '<option value="129600"' . ( ( $curvalue == 129600 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 36 hours', 'link-log' ) . '</option>';
      echo '<option value="172800"' . ( ( $curvalue == 172800 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 48 hours', 'link-log' ) . '</option>';
      echo '<option value="259200"' . ( ( $curvalue == 259200 ) ? ' selected="selected"' : '' ) . '>' . __( 'Do not count multiple clicks from the same IP within 72 hours', 'link-log' ) . '</option>';
      echo '</select>';
      echo '</p>';
      
    }
      
    
    /**
     * handle the settings field : omitbots
     */
    function admin_omitbotsparam() {
      
      echo '<p class="toggle">';
      echo '<input type="checkbox" name="swcc_linklog_omitbotsparam" id="swcc_linklog_omitbotsparam" value="1"' . ( ( $this->settings['omitbotsparam'] ) ? ' checked="checked"' : '' ) . ' /><label for="swcc_linklog_omitbotsparam" class="check"></label>';
      echo __( 'Exclude Search Engines and other Robots', 'link-log' );
      echo '</p>';  
    
    }
    
    
    /**
     * handle the settings field : nofollow
     */
    function admin_nofollowparam() {
      
      echo '<p class="toggle">';
      echo '<input type="checkbox" name="swcc_linklog_nofollowparam" id="swcc_linklog_nofollowparam" value="1"' . ( ( $this->settings['nofollowparam'] ) ? ' checked="checked"' : '' ) . ' /><label for="swcc_linklog_nofollowparam" class="check"></label>';
      echo __( 'Add <code>rel="nofollow"</code> to processed links', 'link-log' );
      echo '</p>';  
    
    }
    
    
    /**
     * handle the settings field : track telephone links
     */
    function admin_tracktel() {
      
      echo '<p class="toggle">';
      echo '<input type="checkbox" name="swcc_linklog_tracktelparam" id="swcc_linklog_tracktelparam" value="1"' . ( ( $this->settings['tracktelparam'] ) ? ' checked="checked"' : '' ) . ' /><label for="swcc_linklog_tracktelparam" class="check"></label>';
      echo __( 'Track telephone links', 'link-log' );
      echo '</p>';  
    
    }
    
    
    /**
     * handle the settings field : automation
     */
    function admin_automationparam() {
      
      $curvalue = $this->settings['automationparam'];
      echo '<p>';
      echo __( 'Process links', 'link-log' );
      echo '<select name="swcc_linklog_automationparam" id="swcc_linklog_automationparam">';
      echo '<option value="AUTO"' . ( ( $curvalue == 'AUTO' ) ? ' selected="selected"' : '' ) . '>' . __( 'Process all links fully automated (recommended)', 'link-log' ) . '</option>';
      echo '<option value="CUSTOM"' . ( ( $curvalue == 'CUSTOM' ) ? ' selected="selected"' : '' ) . '>' . __( 'Only process links on specified posts or pages (shows option)', 'link-log' ) . '</option>';
      echo '<option value="NEVER"' . ( ( $curvalue == 'NEVER' ) ? ' selected="selected"' : '' ) . '>' . __( 'Never process links automatically (template functions are used)', 'link-log' ) . '</option>';
      echo '</select>';
      echo '</p>';
    
    }
    
    
    /**
     * handle the settings field : menutitle
     */
    function admin_menutitle() {
     
      echo '<p>';
      echo __( 'Title for Link Click Analysis page to show in menu', 'link-log' );
      echo '<input class="regular-text" type="text" name="swcc_linklog_menutitle" id="swcc_linklog_menutitle" value="' .  $this->settings['menutitle'] . '" />';
      echo '</p>';
    
    }
    
    
    /**
     * check input : menutitle
     */
    function admin_menutitle_validate( $input ) {
    
      if ( empty( $input ) ) {
      
        $input = $this->get_plugin_name();
      
      }
      
      return $input;
    
    }
    
    
    /**
     * handle the settings field : pagetitle
     */
    function admin_pagetitle() {
      
      echo '<p>';
      echo __( 'Page Title for Link Click Analysis page', 'link-log' );
      echo '<input class="regular-text" type="text" name="swcc_linklog_pagetitle" id="swcc_linklog_pagetitle" value="' .  $this->settings['pagetitle'] . '" />';
      echo '</p>';
    
    }
    
    
    /**
     * check input : pagetitle
     */
    function admin_pagetitle_validate( $input ) {
      
      if ( empty( $input ) ) {
        
        $input = __( 'Link Click Analysis', 'link-log' );
        
      }
      
      return $input;
      
    }
    
   
    /**
     * settings section : link descriptions
     */
    function admin_section_titles_fullcontent() {
      
      // this is different from all other sections because it does not use setting fields but creates the content itself
      echo '<h2 class="title">' . __( 'Edit Link Descriptions', 'link-log' ). '</h2><div class="form-table" id="linklog-custom-link-descriptions-table"><img src="'. plugins_url( 'assets/img/loader.gif', $this->get_plugin_file() ) . '" style="width: 54px; height: 55px; margin: 0 25px 25px 0; float: left" /><p style="height: 55px; line-height: 55px; white-space: nowrap">' . __( 'Please wait while all links are collected...', 'link-log' ) . '</div>';
      // print out JS function in footer to generate list via AJAX
      add_action( 'admin_print_footer_scripts', array( $this, 'add_list_descriptions_js' ) );
      
    }
    
    
    
    /**
     * add admin css file
     */
    function admin_css() {
      
      if ( get_current_screen()->id == $this->admin_handle || get_current_screen()->id == $this->analysis_handle ) { 
      
        wp_enqueue_style( 'pp-admin-page', plugins_url( 'assets/css/pp-admin-page-v2.css', $this->get_plugin_file() ) );
        wp_enqueue_style( 'link-log-ui', plugins_url( 'assets/css/link-log-ui.css', $this->get_plugin_file() ) );
        wp_enqueue_style( 'link-log-analysis-ui', plugins_url( 'assets/css/link-log-analysis-ui.css', $this->get_plugin_file() ) );
        
      }
      
    }
    
    
    /**
     * add meta box to specify if external links should be processed or not - only if Automation is set to "CUSTOM"
     */
    function add_customization_meta_box() {
      
      $screens = array( 'post', 'page' );
      
      foreach ( $screens as $screen ) {
        
        add_meta_box( 'linklog_automation_process', __( 'Track external links', 'link-log' ), array( $this, 'show_customization_meta_box' ), $screen, 'side' );
        
      }
      
    }
    
    
    /**
     * content of customization meta box
     */
    function show_customization_meta_box( $post ) {
      
      wp_nonce_field( 'linklog_customization', 'linklog_customization_nonce' );
      $value = ( get_post_meta( $post->ID, '_linklog_custom_process_this', true ) == '1' );
      echo '<div class="toggle"><input type="checkbox" name="linklog_custom_process_this" id="linklog_custom_process_this" value="1"' . ( ( $value ) ? ' checked="checked"' : '' ) . ' /><label for="linklog_custom_process_this">' . __( 'Count clicks on external links', 'link-log' ) . '</label></div>';
    
    }
    
    
    /**
     * save meta data of customization meta box
     */
    function save_customization_meta_data( $post_id ) {
      
      if ( ! isset( $_POST['linklog_customization_nonce'] ) ) {
        
        return;
        
      }
      
      if ( ! wp_verify_nonce( $_POST['linklog_customization_nonce'], 'linklog_customization' ) ) {
        
        return;
        
      }
      
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        
        return;
        
      }
      
      $value = '0';
      
      if ( isset( $_POST['linklog_custom_process_this'] ) ) {
        
        $value = '1';
        
      }
      
      update_post_meta( $post_id, '_linklog_custom_process_this', $value );
      
    }
    
    
    /**
     * check for gdpr compliance
     * @access private
     * @since  3
     * @return boolean
     */
    private function is_gdpr_compliant() {
       
       return $this->settings['gdpr_compliant'];
       
    }
    
    // ***
    // *** AJAX
    // ***
    
    // print out the script for list generation
    function add_list_descriptions_js() {
      $ajax_nonce = wp_create_nonce( "linklog_ajax_list_descriptions" );
      ?>
      <script type='text/javascript'>
        jQuery( document ).ready(function() {
          jQuery.ajax( { 
            type: 'POST', 
            url: ajaxurl, 
            data: { 'action': 'linklog_list_descriptions', 'security' : '<?php echo $ajax_nonce; ?>' }, 
            success: function( response ) {  
              jQuery( '#linklog-custom-link-descriptions-table' ).html( response );
            }
          });
        });
      </script>
      <?php  
    }
    
    // create the link description list for customizing
    function ajax_list_descriptions() {
      global $wpdb;
      if ( ! check_ajax_referer( 'linklog_ajax_list_descriptions', 'security', false ) ) {
        echo '<p>' . __( 'Security Check failed.', 'link-log' ) . '</p>';
        echo '<p>' . __( 'Maybe a timeout?', 'link-log' ) . '</p>';
        echo '<p>' . __( 'Please reload page or logoff/logon if message repeats.', 'link-log' ) . '</p>';
      } else {
        $wpdb->query( 'INSERT INTO ' . $wpdb->prefix . 'linklog_descriptions ( linklog_url, linklog_description ) SELECT DISTINCT linklog_url, linklog_url FROM ' . $wpdb->prefix . 'linklog WHERE linklog_url NOT IN ( SELECT linklog_url FROM ' . $wpdb->prefix . 'linklog_descriptions);' );
        $data = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'linklog_descriptions ORDER BY linklog_description', ARRAY_A );
        foreach ( $data as $ll ) {
          echo '<p><input type="text" style="width: 100%" name="linkdescription[' . base64_encode( $ll['linklog_url'] ) . ']" value="' . $ll['linklog_description'] . '" /><br />URL: <code>' . $ll['linklog_url'] . '</code></p>';
        }
        submit_button(); 
      }
      die();
    }
    
    /**
     * handle AJAX request to make plugin gdpr compliant
     * @since  3
     * @return void
     */
    function make_gdpr_compliant_now() {
      
      // this function encrypts 10 IPs per call
      // it is called recursively as long as there are IPs to encrypt
      // this ensures that this also works if the process was aborted before
      global $wpdb;
      $updated_rows = 0;
      
      // first check nonce
      if ( ! wp_verify_nonce( $_POST['nonce'], $this->nonce_action ) ) {
        
        echo -999999;
        die();
        
      }
      
      // as of version 4 we also check for NOT NULL and not empty
      $rows = $wpdb->get_results( 'SELECT DISTINCT linklog_ip AS ip FROM ' . $wpdb->prefix . 'linklog WHERE linklog_ip IS NOT NULL AND linklog_ip != "" AND linklog_ip NOT LIKE "-%" LIMIT 25' );
      foreach ( $rows as $row ) {
        $updated_rows += $wpdb->query( 'UPDATE ' . $wpdb->prefix . 'linklog SET linklog_ip = "' . $this->hash_ip( $row->ip ) . '" WHERE linklog_ip = "' . $row->ip . '";' );
      }
      echo $updated_rows;
      if ( empty( $rows ) ) {
        // no more rows with not encrypted IPs
        update_option( 'swcc_linklog_gdpr_compliant', 1 );
      }
      die();
    }
    
    
    /**
     * add admin js file
     * @since 3
     */
    function admin_js() {
    
      wp_enqueue_script( 'link-log', plugins_url( 'assets/js/link-log.js', $this->get_plugin_file() ), 'jquery', $this->get_plugin_version(), true );
      
    }
    
    
    /**
     * dismiss an admin notice via AJAX
     * @since 3
     */
    function dismiss_admin_notice() {
      
      if ( isset( $_POST['pp_linklog_dismiss_admin_notice'] ) ) {
        
        update_user_meta( get_current_user_id(), $_POST['pp_linklog_dismiss_admin_notice'], 'dismissed' );
        
      }
      
      wp_die();
      
    }
       
    
    // ***
    // *** install / activate / new multisite blog
    // ***
    

    // on plugin installation
    function install( $network_wide ) {
      if( $network_wide ) {
        $this->install_network();
      } else {
        $this->install_single();
      }
    }
    
    // for installation on wp single site or for a single blog within a multi site installation
    function install_single() {
      $this->create_table();
    }

    
    // for network wide installation on wp multi site
    function install_network() {
      global $wpdb;
      $activeblog = $wpdb->blogid;
      $blogids = $wpdb->get_col( esc_sql( 'SELECT blog_id FROM ' . $wpdb->blogs ) );
      foreach ($blogids as $blogid) {
        switch_to_blog($blogid);
        $this->create_table();
      }
      switch_to_blog( $activeblog );
    }

    
    // when a new blog is added on wp multi site 
    function new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
      
      global $wpdb;
      
      if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
      
      }
      
      if ( is_plugin_active_for_network( 'link-log/link-log.php' ) ) {
        
        $current = $wpdb->blogid;
        switch_to_blog( $blog_id );
        $this->create_table();
        switch_to_blog( $current );
        
      }
    }

    
    // create tables for single blog
    function create_table() {
      
      global $wpdb;
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      
      // @since 3
      // just to be sure...
      update_option( 'swcc_linklog_gdpr_compliant', 0 );
      
      dbDelta( 'CREATE TABLE ' . $wpdb->prefix . 'linklog (
        linklog_url VARCHAR(500) NOT NULL, 
        linklog_clicked TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
        linklog_ip VARCHAR(50) 
        );'
      );
      dbDelta( 'CREATE TABLE ' . $wpdb->prefix . 'linklog_descriptions (
        linklog_url VARCHAR(500) NOT NULL, 
        linklog_description VARCHAR(100) NOT NULL
        );'
      );
      
      // @since 3
      // if there are no unencrypted IPs in the table the plugin is already gdpr compliant now
      // as of version 4 we also check for NOT NULL and not empty
      if ( 0 == $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'linklog WHERE linklog_ip IS NOT NULL AND linklog_ip != "" AND linklog_ip NOT LIKE "-%"' ) ) {
        
        update_option( 'swcc_linklog_gdpr_compliant', 1 );
        
      }
      
      update_option( 'swcc_linklog_version', $this->get_plugin_version() );
      
      // Reload Settings
      $this->get_settings();
      
    }
    

    // update
    function update() {
      
      if ( $this->settings['installed_version'] != $this->get_plugin_version() )  {
        $this->create_table();
      }
      
      if ( $this->settings['installed_version'] < '1.3' )  {
        // as of version 3 we can do this only if data is not encrypted yet
        global $wpdb;
        $wpdb->query( 'UPDATE ' . $wpdb->prefix . 'linklog SET linklog_url = TRIM( TRAILING "/" FROM linklog_url ) WHERE linklog_ip NOT LIKE "-%"' ) ;
      }
      
    }
    
    
    // ***
    // *** uninstall
    // ***

    
    // uninstall main function
    function uninstall( ) {
      if( is_multisite() ) {
        $this->uninstall_network();
      } else {
        $this->uninstall_single();
      }
    }

    // for uninstall on wp single site
    function uninstall_single() {
      $this->delete_table();
      $this->delete_settings();
    }

    function uninstall_network() {
      // for network wide uninstall on wp multi site
      global $wpdb;
      $activeblog = $wpdb->blogid;
      $blogids = $wpdb->get_col( esc_sql( 'SELECT blog_id FROM ' . $wpdb->blogs ) );
      foreach ($blogids as $blogid) {
        switch_to_blog($blogid);
        $this->delete_table();
        $this->delete_settings();
      }
      switch_to_blog( $activeblog );
    }

    function delete_table() {
      // delete tables
      global $wpdb;
      $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'linklog' );
      $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'linklog_descriptions' );
    }
    
    // delete settings from database
    function delete_settings() {
      foreach ( $this->settings as $key => $value) {
        delete_option( 'swcc_linklog_' . $key );
      }
    }
   
  }
  
}

?>