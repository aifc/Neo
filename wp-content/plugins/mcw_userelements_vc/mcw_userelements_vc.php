<?php
/*
Plugin Name: Elements for Users - Addon for Visual Composer
Author: Mehmet Celik
Version: 1.3.0
Description: Show or hide Visual Composer content elements depending on the user devices and user attributes such as user role, log in information, user names and devices.
Text Domain: mcw_userelements_vc
*/

/* Copyright 2015 Mehmet Celik */
/* Coding is an Art */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
  die( '-1' );
}

// Add mobile detect plugin
if (!class_exists('Mobile_Detect'))
{
  require_once( plugin_dir_path(__FILE__) . '/mobile_detect/Mobile_Detect.php' );
}

if (!class_exists('MCW_UserElements_VC'))
{
  // Admin notice for checking visual composer
  add_action('admin_init','mcw_ue_CheckVCVersion');
  function mcw_ue_CheckVCVersion()
  {
    // Required visual composer version is 4.7
    $required_vc = '4.7';

    // Check if visual composer is activated
    if (defined('WPB_VC_VERSION'))
    {
      // Compare visual composer version with the required one
      if (version_compare($required_vc, WPB_VC_VERSION, '>' ))
      {
        add_action( 'admin_notices', 'mcw_ue_VCNotCompatible');
      }
    }
    else
    {
      // Visual composer not activated
      add_action( 'admin_notices', 'mcw_ue_VCNotActivated');
    }
  }
  // Visual composer not compatible message
  function mcw_ue_VCNotCompatible()
  {
    echo '<div class="updated"><p><strong>Elements for Users - Addon for Visual Composer</strong> plugin requires <strong>Visual Composer '.$required_vc.' or greater</strong>.</p></div>';
  }
  // Visual composer not activated message
  function mcw_ue_VCNotActivated()
  {
    echo '<div class="updated"><p><strong>Elements for Users - Addon for Visual Composer</strong> plugin requires <strong>Visual Composer</strong> plugin installed and activated.</p></div>';
  }

  // MCW_UserElements_VC Class
  class MCW_UserElements_VC
  {
    // Shortcode name tag
    protected $tag = 'mcw_userelements_vc';
    // Plugin Name
    protected $pluginTitle = 'Elements for Users';
    protected $pluginName = 'Elements for Users - Addon for Visual Composer';
    protected $pluginSlug = 'mcw_elements_for_users';
    // Plugin settings
    private $settings = null;
    private $advanced_settings = null;
    // Device list
    protected $devices = array(
      array('id' => 'desktops', 'title' => 'Desktops'),
      array('id' => 'mobiles', 'title' => 'Mobiles'),
      array('id' => 'phones', 'title' => 'Phones'),
      array('id' => 'tablets', 'title' => 'Tablets')
    );
    // Mobile detect
    protected $detect = null;

    // Class constructor
    public function __construct()
    {
      // Create mobile detect
      $this->detect = new Mobile_Detect();

      // Check user role and remove shortcode
      add_action('wp_enqueue_scripts', array($this, 'removeElementsFromContent'), 3, 0);

      if ( defined('WPB_VC_VERSION') )
      {
        // execute VC shortcode hook
        add_filter('vc_shortcode_output', array($this, 'on_vc_shortcode_output'), 10, 3);
      }

      // ******************************************************************************************
      // Admin side

      // Initialize admin interface to add params in vc
      add_action( 'admin_init', array($this, 'on_admin_init'), 1000 );
      // Add admin menu
      add_action( 'admin_menu', array($this, 'on_admin_menu') );
      // Add admin enqueue scripts
      add_action( 'admin_enqueue_scripts', array($this, 'on_admin_enqueue_scripts') );

      // Listen for activate event
      register_activation_hook(__FILE__, array($this, 'on_register_activation_hook'));
      // Listen for deactivate event
      // register_deactivation_hook(__FILE__, array($this, 'on_register_deactivation_hook'));
    }

    // Shortcode regex
    private function getShortcodeRegex($val)
    {
      // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
      // Also, see shortcode_unautop() and shortcode.js.
      return
        '\\['                // Opening bracket
        . '(\\[?)'           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($val)"           // 2: Shortcode name
        . '(?![\\w-])'       // Not followed by word character or hyphen
        . '('                // 3: Unroll the loop: Inside the opening shortcode tag
        . '[^\\]\\/]*'       // Not a closing bracket or forward slash
        . '(?:'
        . '\\/(?!\\])'       // A forward slash not followed by a closing bracket
        . '[^\\]\\/]*'       // Not a closing bracket or forward slash
        . ')*?'
        . ')'
        . '(?:'
        . '(\\/)'            // 4: Self closing tag ...
        . '\\]'              // ... and closing bracket
        . '|'
        . '\\]'              // Closing bracket
        . '(?:'
        . '('                // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        . '[^\\[]*+'         // Not an opening bracket
        . '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        . '[^\\[]*+'         // Not an opening bracket
        . ')*+'
        . ')'
        . '\\[\\/\\2\\]'     // Closing shortcode tag
        . ')?'
        . ')'
        . '(\\]?)';          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    // Returns the user role of current user
    private function getLoggedUserRole()
    {
      // Current user data
      $current_user = wp_get_current_user();
      // Check
      if ( is_array($current_user->roles) && isset($current_user->roles[0]) )
      {
        return $current_user->roles;
      }
      else
      {
        return false;
      }
    }

    // Returns if the element is enabled (1/0)
    private function getSettings($element)
    {
      if (is_array($element))
        print_r($element);
      
      if (!isset($this->settings))
      {
        $this->settings = get_option($this->tag.'_settings');
      }

      if ( (isset($this->settings)) && (isset($this->settings[$element])) )
      {
        if ($this->settings[$element] == 'on')
        {
          return 1;
        }
        else if ($this->settings[$element] == 'disabled')
        {
          return -1;
        }
      }

      return 0;
    }

    // Returns if the element is enabled
    private function getAdvancedSettings($element)
    {
      if (!isset($this->advanced_settings))
      {
        $this->advanced_settings = get_option($this->tag.'_advanced_settings');
      }

      if ( (isset($this->advanced_settings)) && (isset($this->advanced_settings[$element])) && ($this->advanced_settings[$element] == 'on') )
      {
        return true;
      }

      return false;
    }

    // Returns an array with Visual Composer content elements names and shortcode names
    private function getVCContentElements()
    {
      // Visual Composer content elements
      $elements = array();

      // Collect visual composer content element names
      if ( class_exists( 'WPBMap' ) )
      {
        $vc_shortcodes = WPBMap::getSortedUserShortCodes();

        if ( isset($vc_shortcodes) && (is_array($vc_shortcodes)) )
        {
          foreach($vc_shortcodes as $vc_shortcode)
          {
            if ( isset($vc_shortcode['base']) && isset($vc_shortcode['name']) )
            {
              $elements[] = array('base' => $vc_shortcode['base'], 'name' => $vc_shortcode['name']);
            }
          }
        }
      }

      return $elements;
    }
    
    private function getTimeFromDate($date_input)
    {
      $date_time = explode(' ', trim($date_input));
      $month = 1;
      $day = 1;
      $year = 2000;
      $hour = 0;
      $minute = 0;
      
      if (isset($date_time[0]))
      {
        $date = explode('/', trim($date_time[0]));
        if (isset($date[0]))
        {
          $date[0] = trim($date[0]);
          if ( !empty($date[0]) && is_numeric($date[0]) )
            $year = $date[0];
        }
        
        if (isset($date[1]))
        {
          $date[1] = trim($date[1]);
          if ( !empty($date[1]) && is_numeric($date[1]) )
            $month = $date[1];
        }
        
        if (isset($date[2]))
        {
          $date[2] = trim($date[2]);
          if ( !empty($date[2]) && is_numeric($date[2]) )
            $day = $date[2];
        }
      }
      
      if (isset($date_time[1]))
      {
        $time = explode(':', trim($date_time[1]));
        if (isset($time[0]))
        {
          $time[0] = trim($time[0]);
          if ( !empty($time[0]) && is_numeric($time[0]) )
            $hour = $time[0];
        }
        
        if (isset($time[1]))
        {
          $time[1] = trim($time[1]);
          if ( !empty($time[1]) && is_numeric($time[1]) )
            $minute = $time[1];
        }
      }
      
      return mktime($hour, $minute, 0, $month, $day, $year);
    }

    // Checks if the shortcode must be filtered and returns true if the content must be filtered.
    private function isShortcodeFiltered($atts)
    {
      // Check if the variable is set
      if (isset($atts['showfor']))
      {
        if ($atts['showfor'] == 'not_logged_in')
        {
          // Remove shortcode if user is logged in
          if (is_user_logged_in())
          {
            return true;
          }
        }
        else if ($atts['showfor'] == 'logged_in')
        {
          // Remove shortcode if user is not logged in
          if (is_user_logged_in() == false)
          {
            return true;
          }
        }
        else if ($atts['showfor'] == 'selected_user_roles')
        {
          if (is_user_logged_in() == false)
          {
            // Remove shortcode if no user role is logged in
            return true;
          }
          else if (isset($atts['selected_user_roles']))
          {
            // Get user role
            $roles = $this->getLoggedUserRole();
            $selected_user_roles = array_map('trim', explode(',', $atts['selected_user_roles']));
            
            if (is_array($roles))
            {
              foreach ($roles as $role)
              {
                if (in_array($role, $selected_user_roles))
                {
                  return false;
                }
              }
              return true;
            }
          }
          else
          {
            // If no user role is selected, remove for all
            return true;
          }
        }
        else if ($atts['showfor'] == 'selected_users')
        {
          if (isset($atts['selected_users']))
          {
            $current_user_id = get_current_user_id();
            $selected_users = array_map('trim', explode(',', $atts['selected_users']));
            if (in_array($current_user_id, $selected_users) == false)
            {
              // User is not in the selected users list
              return true;
            }
          }
          else
          {
            // If no user is selected, remove for all
            return true;
          }
        }
        else if ($atts['showfor'] == 'discarded_users')
        {
          if (isset($atts['discarded_users']))
          {
            $current_user_id = get_current_user_id();
            $discarded_users = array_map('trim', explode(',', $atts['discarded_users']));
            if (in_array($current_user_id, $discarded_users))
            {
              // User is not in the selected users list
              return true;
            }
          }
        }
        else if ($atts['showfor'] == 'selected_devices')
        {
          if (isset($atts['selected_devices']))
          {
            $selected_devices = array_map('trim', explode(',', $atts['selected_devices']));

            if ( in_array('mobiles', $selected_devices, true) && ($this->detect->isMobile()) )
            {
              return false;
            }
            if ( in_array('tablets', $selected_devices, true) && ($this->detect->isTablet()) )
            {
              return false;
            }
            if ( in_array('phones', $selected_devices, true) && ($this->detect->isMobile() && !$this->detect->istablet()) )
            {
              return false;
            }
            if ( in_array('desktops', $selected_devices, true) && ( !$this->detect->isMobile() && !$this->detect->istablet() ) )
            {
              return false;
            }
          }

          return true;
        }
        else if ($atts['showfor'] == 'date_range')
        {
          $date_now = time();
          $start_date = $date_now;
          $end_date = $date_now;
          
          if ( isset($atts['start_date']) && !empty($atts['start_date']) )
          {
            $start_date = $this->getTimeFromDate($atts['start_date']);
          }
          
          if ( isset($atts['end_date']) && !empty($atts['end_date']) )
          {
            $end_date = $this->getTimeFromDate($atts['end_date']);
          }
          
          return ( ($date_now > $end_date) || ($date_now < $start_date) );
        }
        else if ($atts['showfor'] == 'time_range')
        {
          $time_now = time();
          $start_time = mktime(0, 0, 0);
          $end_time = mktime(23, 59, 59);
          
          if ( isset($atts['start_time']) && !empty($atts['start_time']) )
          {
            $time = explode(':', trim($atts['start_time']));
            $hour = 0;
            $minute = 0;
            if (isset($time[0]))
            {
              $time[0] = trim($time[0]);
              if ( !empty($time[0]) && is_numeric($time[0]) )
                $hour = $time[0];
            }
            
            if (isset($time[1]))
            {
              $time[1] = trim($time[1]);
              if ( !empty($time[1]) && is_numeric($time[1]) )
                $minute = $time[1];
            }
            
            $start_time = mktime($hour, $minute, 0);
          }
          
          if ( isset($atts['end_time']) && !empty($atts['end_time']) )
          {
            $time = explode(':', trim($atts['end_time']));
            $hour = 0;
            $minute = 0;
            if (isset($time[0]))
            {
              $time[0] = trim($time[0]);
              if ( !empty($time[0]) && is_numeric($time[0]) )
                $hour = $time[0];
            }
            
            if (isset($time[1]))
            {
              $time[1] = trim($time[1]);
              if ( !empty($time[1]) && is_numeric($time[1]) )
                $minute = $time[1];
            }
            
            $end_time = mktime($hour, $minute, 0);
            
            if ($end_time <= $start_time)
              $end_time = mktime(24 + $hour, $minute, 0);
          }
          
          return ( ($time_now > $end_time) || ($time_now < $start_time) );
        }
        else if ($atts['showfor'] == 'php_function')
        {
          if (isset($atts['php_function_name']))
          {
            if (function_exists($atts['php_function_name']))
            {
              return call_user_func($atts['php_function_name']);
            }
          }
        }
        // Else if everyone, don't do anything
      }

      return false;
    }

    // Called on register_activation_hook
    public function on_register_activation_hook()
    {
      // Initialize settings
      $this->settings = get_option($this->tag.'_settings');

      // Check if there are settings available in db
      if ( isset($this->settings) && (is_array($this->settings)) )
      {
        return;
      }

      // No settings in db so initialize settings
      $this->settings = array();
      $this->advanced_settings = array();
      // Get VC content elements
      $elements = $this->getVCContentElements();
      // Set all to on
      foreach($elements as $element)
      {
        $element = $element['base'];
        $this->settings[$element] = 'on';
        $this->advanced_settings[$element] = 'off';
      }
      // Update option in db
      update_option($this->tag.'_settings', $this->settings);
      update_option($this->tag.'_advanced_settings', $this->advanced_settings);
    }

    // Called on register_deactivation_hook
    public function on_register_deactivation_hook()
    {
      // delete_option($this->tag.'_settings');
      // delete_option($this->tag.'_advanced_settings');
    }

    // Enqueue admin scripts
    // Called on admin_enqueue_scripts action
    public function on_admin_enqueue_scripts($hook)
    {
      if ($hook == 'settings_page_'.$this->pluginSlug)
      {
        wp_enqueue_script( 'mcw_ue_cbswitch_js', plugins_url('lc_switch/lc_switch.min.js', __FILE__), array('jquery'));
        wp_enqueue_style( 'mcw_ue_cbswitch_css', plugins_url('lc_switch/lc_switch.css', __FILE__) );
      }
    }

    // Remove shortcodes from content
    // Called from wp_enqueue_scripts action
    public function removeElementsFromContent()
    {
      global $post;

      // Get advanced settings
      if (!isset($this->advanced_settings))
      {
        $this->advanced_settings = get_option($this->tag.'_advanced_settings');
      }
      
      if (empty($this->advanced_settings))
      {
        return;
      }
      
      foreach($this->advanced_settings as $element => $val)
      {
        if ($this->getAdvancedSettings($element) == true)
        {
          // Get regex of element shortcode
          $pattern = $this->getShortcodeRegex($element);
          // Find all element data
          $count = preg_match_all('/'.$pattern .'/s', $post->post_content, $found);

          // Check if there are elements found
          if ( is_array( $found ) && ! empty( $found[0] ) )
          {
            if ( isset( $found[3] ) && is_array( $found[3] ) )
            {
              foreach ( $found[3] as $key => $shortcode_atts )
              {
                // Extract shortcode parameters
                $atts = shortcode_parse_atts( $shortcode_atts );

                // Filter shortcode
                if ($this->isShortcodeFiltered($atts) == true)
                {
                  $post->post_content = str_replace($found[0][$key], '', $post->post_content);
                }
              }
            }
          }
        }
      }
    }

    // Change shortcode output if nececssary
    // Called by vc_shortcode_output filter
    public function on_vc_shortcode_output($output, $obj, $atts)
    {
      $element = '';
      // Get element name
      if ( isset($obj) )
      {
        $element = $obj->settings('base');
      }
      else
      {
        // Something wrong, do not change output
        return $output;
      }

      // Check if content check is enabled for element
      if ($this->getSettings($element) != 1)
      {
        // Content check is not enabled for this element, do not change output
        return $output;
      }

      if ($this->isShortcodeFiltered($atts) == true)
      {
        return '';
      }

      // Return output
      return $output;
    }

    // Constructs options page
    // Called by add_options_page in on_admin_menu
    public function on_add_options_page()
    {
      $this->settings = get_option($this->tag.'_settings');
      $this->advanced_settings = get_option($this->tag.'_advanced_settings');

      echo '<div id="mcw_userelements_id" class="wrap">
      <h2>'.__($this->pluginName, $this->tag).'</h2>';

      echo '<form method="post" action="options.php">';

      settings_fields($this->tag.'_group');
      do_settings_sections($this->tag.'_sections');
      submit_button();

      echo '</form></div>';
      ?>
      <script type="text/javascript">
      (function($){
        $('#mcw_userelements_id').each(function(){
          $(this).find('input:checkbox').lc_switch();
        });

        $('body').delegate('#mcw_ue_settings_checkall', 'lcs-on', function(){
          $('#mcw_userelements_id .mcw_ue_settings').find('input:checkbox').not('#mcw_ue_settings_checkall').lcs_on();
        });

        $('body').delegate('#mcw_ue_settings_checkall', 'lcs-off', function(){
          $('#mcw_userelements_id .mcw_ue_settings').find('input:checkbox').not('#mcw_ue_settings_checkall').lcs_off();
        });
      })(jQuery);
      </script>
      <?php
    }

    // Adds menu under Options
    public function on_admin_menu()
    {
      add_options_page(
        __($this->pluginName, $this->tag),
        __($this->pluginTitle, $this->tag),
        'manage_options',
        $this->pluginSlug,
        array($this, 'on_add_options_page')
      );
    }

    // Descriptive text of section in settings page
    // Called by add_settings_section callback in on_admin_init
    public function on_section_callback()
    {
      echo '<p>'.__('Select the Visual Composer content elements that will show the user attribute settings.', $this->tag).'</p>';
      echo '<p style="font-style:italic;">'.__('Enable <strong>Advanced Replace</strong> to check content on the go. This might be slower, so check only for the Visual Composer content that is necessary.', $this->tag).'</p>';
    }

    // Adds checkboxes
    // Called by add_settings_field callback in on_admin_init
    public function on_setting_field_render($element)
    {
      // Get element shortcode name
      $el = $element['base'];
      
      // Check is false by default
      $checked = ($this->getSettings($el) == 1) ? ' checked' : '';
      $checked_advanced = ($this->getAdvancedSettings($el) == true) ? ' checked' : '';

      // HTML output for the field
      echo '<div class="mcw_ue_settings" style="margin-right: 50px; display: inline;"><input type="checkbox" name="'.$this->tag.'_settings'.'['.$el.']" id="'.$el.'"value="on"'.$checked.'></div>';
      echo '<div style="margin-left: 50px; display: inline;"><input type="checkbox" name="'.$this->tag.'_advanced_settings'.'['.$el.']" id="'.$el.'"value="on"'.$checked_advanced.'></div>';
    }

    // Check all checkbox
    public function on_cb_checkall_render()
    {
      echo '<div style="margin-right: 50px; display: inline;"><input type="checkbox" name="mcw_ue_settings_checkall" id="mcw_ue_settings_checkall" value="on"></div>';
      echo '<div style="margin-left: 50px; display: inline;"><span style="font-weight: bold;">Advanced Replace</span></div>';
    }

    // Visual composer admin interface
    // Called by admin_init action
    public function on_admin_init()
    {
      // Visual Composer content elements
      $elements = $this->getVCContentElements();

      if(function_exists('vc_add_param'))
      {
        // Set error handler
        set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
          throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        
        foreach($elements as $element)
        {
          $element = $element['base'];

          if ($this->getSettings($element) == 1)
          {
            try {
              vc_add_param( $element, array(
                'type' => 'dropdown',
                'class' => '',
                'heading' => __('Show For', $this->tag),
                'param_name' => 'showfor',
                'value' => array(
                  __('Everyone', $this->tag) => '',
                  __('Not Logged In Users', $this->tag) => 'not_logged_in',
                  __('All Members', $this->tag) => 'logged_in',
                  __('Members with Selected User Roles', $this->tag) => 'selected_user_roles',
                  __('Selected Users', $this->tag) => 'selected_users',
                  __('Discarded Users', $this->tag) => 'discarded_users',
                  __('Selected Devices', $this->tag) => 'selected_devices',
                  __('Date Range', $this->tag) => 'date_range',
                  __('Time Range', $this->tag) => 'time_range',
                  __('PHP Function', $this->tag) => 'php_function',
                  ),
                  'description' => __('Select the member filter this element will be visible to.', $this->tag),
              ) );
              
              vc_add_param( $element, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __('Start Date', $this->tag),
                'param_name' => 'start_date',
                'value' => '',
                'dependency' => array('element' => 'showfor', 'value' => array('date_range')),
                'description' => __('Enter the start date (and time if necessary) that the element will be shown in the format Year/Month/Day Hour:Minute. Leave empty if there is no start date. The time should be in server date/time.<br/>Examples: 2016/01/30 or 2016/01/30 15:10<br/>Current Server Time: '.date('Y/m/d H:i'), $this->tag),
              ) );
              
              vc_add_param( $element, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __('End Date', $this->tag),
                'param_name' => 'end_date',
                'value' => '',
                'dependency' => array('element' => 'showfor', 'value' => array('date_range')),
                'description' => __('Enter the end date (and time if necessary) that the element will be shown in the format Year/Month/Day Hour:Minute. Leave empty if there is no end date. The date should be in server date/time.<br/>Examples: 2016/01/30 or 2016/01/30 15:10<br/>Current Server Time: '.date('Y/m/d H:i'), $this->tag),
              ) );
              
              vc_add_param( $element, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __('Start Time', $this->tag),
                'param_name' => 'start_time',
                'value' => '',
                'dependency' => array('element' => 'showfor', 'value' => array('time_range')),
                'description' => __('Enter the start time that the element will be shown in the format Hour:Minute. The time should be in server time. Example: '.date('H:i'), $this->tag),
              ) );
              
              vc_add_param( $element, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __('End Time', $this->tag),
                'param_name' => 'end_time',
                'value' => '',
                'dependency' => array('element' => 'showfor', 'value' => array('time_range')),
                'description' => __('Enter the end time that the element will be shown in the format Hour:Minute. The time should be in server time. Example: '.date('H:i'), $this->tag),
              ) );
              
              vc_add_param( $element, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __('Function Name', $this->tag),
                'param_name' => 'php_function_name',
                'value' => '',
                'dependency' => array('element' => 'showfor', 'value' => array('php_function')),
                'description' => __('Enter the PHP function name that suggests whether the element is shown. (Return true if the element will be hidden.)', $this->tag),
              ) );

              vc_add_param( $element, array(
                'type' => 'autocomplete',
                'class' => 'vc_not-for-custom',
                'heading' => __('Selected User Roles', $this->tag),
                'param_name' => 'selected_user_roles',
                'dependency' => array('element' => 'showfor', 'value' => array('selected_user_roles')),
                'settings' => array(
                  'multiple' => true,
                  'min_length' => 1,
                  'unique_values' => true,
                  'display_inline' => true,
                  'delay' => 100,
                  'auto_focus' => true,
                ),
                'param_holder_class' => 'vc_not-for-custom',
                'description' => __('Select user roles which this element will be visible to. Type Administrator, Editor, Author, Contributor, Subscriber or any other user role defined.', $this->tag),
              ) );

              vc_add_param( $element, array(
                'type' => 'autocomplete',
                'class' => 'vc_not-for-custom',
                'heading' => __('Selected Users', $this->tag),
                'param_name' => 'selected_users',
                'dependency' => array('element' => 'showfor', 'value' => array('selected_users')),
                'settings' => array(
                  'multiple' => true,
                  'min_length' => 1,
                  'unique_values' => true,
                  'display_inline' => true,
                  'delay' => 100,
                  'auto_focus' => true,
                ),
                'param_holder_class' => 'vc_not-for-custom',
                'description' => __('Select users which this element will be visible to. Type user name, user login name or user email.', $this->tag),
              ) );

              vc_add_param( $element, array(
                'type' => 'autocomplete',
                'class' => 'vc_not-for-custom',
                'heading' => __('Discarded Users', $this->tag),
                'param_name' => 'discarded_users',
                'dependency' => array('element' => 'showfor', 'value' => array('discarded_users')),
                'settings' => array(
                  'multiple' => true,
                  'min_length' => 1,
                  'unique_values' => true,
                  'display_inline' => true,
                  'delay' => 100,
                  'auto_focus' => true,
                ),
                'param_holder_class' => 'vc_not-for-custom',
                'description' => __('Select users which this element will NOT be visible to. Type user name, user login name or user email.', $this->tag),
              ) );

              // Selected Devices
              $all_string = array();
              foreach ($this->devices as $device)
              {
                $all_string[] = $device['title'];
              }
              $all_string = implode(', ', $all_string);
              vc_add_param( $element, array(
                'type' => 'autocomplete',
                'class' => 'vc_not-for-custom',
                'heading' => __('Selected Devices', $this->tag),
                'param_name' => 'selected_devices',
                'dependency' => array('element' => 'showfor', 'value' => array('selected_devices')),
                'settings' => array(
                  'multiple' => true,
                  'min_length' => 1,
                  'unique_values' => true,
                  'display_inline' => true,
                  'delay' => 100,
                  'auto_focus' => true,
                ),
                'param_holder_class' => 'vc_not-for-custom',
                'description' => __('Select devices which this element will be visible to. Options are '.$all_string.'.', $this->tag),
              ) );

              // Add callback and render filters fo selected user roles
              add_filter( 'vc_autocomplete_'.$element.'_selected_user_roles_callback', array($this, 'on_selected_user_roles_callback'), 10, 1 );
              add_filter( 'vc_autocomplete_'.$element.'_selected_user_roles_render', array($this, 'on_selected_user_roles_render'), 10, 1 );

              add_filter( 'vc_autocomplete_'.$element.'_selected_users_callback', array($this, 'on_selected_users_callback'), 10, 1 );
              add_filter( 'vc_autocomplete_'.$element.'_selected_users_render', array($this, 'on_selected_users_render'), 10, 1 );

              add_filter( 'vc_autocomplete_'.$element.'_discarded_users_callback', array($this, 'on_selected_users_callback'), 10, 1 );
              add_filter( 'vc_autocomplete_'.$element.'_discarded_users_render', array($this, 'on_selected_users_render'), 10, 1 );

              add_filter( 'vc_autocomplete_'.$element.'_selected_devices_callback', array($this, 'on_selected_devices_callback'), 10, 1 );
              add_filter( 'vc_autocomplete_'.$element.'_selected_devices_render', array($this, 'on_selected_devices_render'), 10, 1 ); 
            
            }
            catch(Exception $e) {
              $this->settings[$element] = 'disabled';
              $this->advanced_settings[$element] = 'off';
              
              // Update option in db
              update_option($this->tag.'_settings', $this->settings);
              update_option($this->tag.'_advanced_settings', $this->advanced_settings);
            }
          }
        }
        
        // Restore error handler
        restore_error_handler();
      }
      
      // Register options page
      register_setting(
        $this->tag.'_group',
        $this->tag.'_settings'
      );
      // Register options page
      register_setting(
        $this->tag.'_group',
        $this->tag.'_advanced_settings'
      );

      // Register settings section
      add_settings_section(
        $this->tag.'_section',
        __('Visual Composer Content Elements', $this->tag),
        array($this, 'on_section_callback'),
        $this->tag.'_sections'
      );

      // Add check all checkbox
      add_settings_field(
        'id_ue_checkall',
        __('Check All', $this->tag),
        array( $this, 'on_cb_checkall_render' ),
        $this->tag.'_sections',
        $this->tag.'_section'
      );

      // Add fields and checkboxes
      foreach($elements as $element)
      {
        if ($this->getSettings($element['base']) != -1)
        {
          add_settings_field(
            'id_'.$element['base'],
            $element['name'].'<br />['.$element['base'].']',
            array( $this, 'on_setting_field_render' ),
            $this->tag.'_sections',
            $this->tag.'_section',
            $element
          );
        }
      }
    }

    // Called by vc_autocomplete_vc_row_selected_user_roles_callback filter
    public function on_selected_user_roles_callback($search_string)
    {
      // Wordpress roles
      global $wp_roles;
      // Output array
      $data = array();

      // Check if wordpress roles are set
      if (!isset($wp_roles))
      {
        $wp_roles = new WP_Roles();
      }

      // Get role names
      $user_roles = $wp_roles->get_names();

      // Search string
      foreach($user_roles as $key=>$value)
      {
        if ( (preg_match("/$search_string/i", $value)) || (preg_match("/\b$search_string\b/i", $key)) )
        {
          $data[] = array(
            'value' => $key,
            'label' => $value,
          );
        }
      }

      // Return data array
      return $data;
    }

    // Called by vc_autocomplete_vc_row_selected_user_roles_render filter
    public function on_selected_user_roles_render($term)
    {
      // Wordpress roles
      global $wp_roles;
      // Output array
      $data = false;
      // Search string
      $search_string = '';

      // Check if wordpress roles are set
      if (!isset($wp_roles))
      {
        $wp_roles = new WP_Roles();
      }

      // Check if term is set
      if (!isset($term))
      {
        return $data;
      }

      // Check if term value is set
      if (!isset($term['value']))
      {
        return $data;
      }

      // Check if role is a valid one
      if (!($wp_roles->is_role($term['value'])))
      {
        return $data;
      }

      // Get role names
      $user_roles = $wp_roles->get_names();

      // Search string
      foreach($user_roles as $key=>$value)
      {
        if ( (preg_match('/'.$term['value'].'/i', $value)) || (preg_match('/'.$term['value'].'/i', $key)) )
        {
          $data = array(
            'value' => $key,
            'label' => $value,
          );

          // Return found term
          return $data;
        }
      }

      // Return false
      return $data;
    }

    // Called by user_search_columns filter
    public function on_search_columns($search_columns)
    {
      $search_columns[] = 'display_name';
      return $search_columns;
    }

    // Called by vc_autocomplete_vc_row__selected_users_callback filter
    public function on_selected_users_callback($search_string)
    {
      // Output array
      $data = array();

      // Add filter for display_name search column
      add_filter('user_search_columns', array($this, 'on_search_columns'));

      // Create user query
      $users = new WP_User_Query(array(
        'search' => '*'.esc_attr($search_string).'*',
        'search_columns' => array('user_login', 'user_nicename', 'user_email', 'display_name'),
      ));
      // Get user query results
      $users_found = $users->get_results();

      if ( isset($users_found) && is_array($users_found) )
      {
        foreach ($users_found as $user)
        {
          if (isset($user->data))
          {
            $data[] = array(
              'value' => $user->data->ID,
              'label' => $user->data->display_name,
            );
          }
        }
      }

      // Return data array
      return $data;
    }

    // Called by vc_autocomplete_vc_row__selected_users_render filter
    public function on_selected_users_render($term)
    {
      // Check if term is set
      if (!isset($term))
      {
        return $data;
      }

      // Check if term value is set
      if (!isset($term['value']))
      {
        return $data;
      }

      $user = get_user_by('id', $term['value']);

      if (isset($user) && $user != false)
      {
        $term['label'] = $user->display_name;
      }
      else
      {
        $term['label'] = 'Unknown User!';
      }

      // Return data array
      return $term;
    }

    // Called by vc_autocomplete_vc_row__selected_users_callback filter
    public function on_selected_devices_callback($search_string)
    {
      // Output array
      $data = array();

      foreach ($this->devices as $device)
      {
        if ( preg_match("/$search_string/i", strtolower($device['title'])) )
        {
          $data[] = array(
              'value' => $device['id'],
              'label' => __($device['title'], $this->tag),
            );
        }
      }

      // Return data array
      return $data;
    }

    // Called by vc_autocomplete_vc_row__selected_users_render filter
    public function on_selected_devices_render($term)
    {
      // Check if term is set
      if (!isset($term))
      {
        return $data;
      }

      // Check if term value is set
      if (!isset($term['value']))
      {
        return $data;
      }

      // Search term id
      foreach ($this->devices as $device)
      {
        if ( strcmp($device['id'], $term['value']) == 0 )
        {
          $term['label'] = __($device['title'], $this->tag);
          return $term;
        }
      }

      // Return unknown
      $term['label'] = 'Unknown Device!';
      // Return data array
      return $term;
    }
  }
}
require_once( plugin_dir_path(__FILE__) . '/mobile_detect/mobilecore.php' );
// Create MCW User Elements class
if(class_exists('MCW_UserElements_VC'))
{
  $MCW_UserElements_VC = new MCW_UserElements_VC;
}