<?php
/*
Plugin Name: If Menu
Plugin URI: https://wordpress.org/plugins/if-menu/
Description: Show/hide menu items with conditional statements
Version: 0.7.1
Text Domain: if-menu
Author: Layered
Author URI: https://layered.studio
License: GPL2
*/

/*  Copyright 2012 Andrei Igna (email: andrei@rokm.ro)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class If_Menu {

	public static function init() {
		global $pagenow;

		load_plugin_textdomain('if-menu', false, dirname(plugin_basename(__FILE__)) . '/languages');

		if (is_admin()) {
			add_action('admin_enqueue_scripts', 'If_Menu::admin_init');
			add_action('wp_update_nav_menu_item', 'If_Menu::wp_update_nav_menu_item', 10, 2);
			add_filter('wp_edit_nav_menu_walker', 'If_Menu::customWalker'); 
			add_action('wp_nav_menu_item_custom_fields', 'If_Menu::menu_item_fields');
			add_action('wp_nav_menu_item_custom_title', 'If_Menu::menu_item_title');
			add_action('init', 'If_Menu::saveSettings');
			add_action('admin_menu', function() {
				add_submenu_page('themes.php', __('If Menu', 'if-menu'), __('If Menu', 'if-menu'), 'edit_theme_options', 'if-menu', 'If_Menu::page');
			});

			if ($pagenow !== 'nav-menus.php') {
				add_filter( 'wp_get_nav_menu_items', 'If_Menu::wp_get_nav_menu_items' );
			}
		} else {
			add_filter( 'wp_get_nav_menu_items', 'If_Menu::wp_get_nav_menu_items' );
			add_action('wp_enqueue_scripts', 'If_Menu::addAssets');
		}
	}

	public static function get_conditions( $for_testing = false ) {
		$conditions = apply_filters( 'if_menu_conditions', array() );

		if ($for_testing) {
			$c2 = array();
			foreach ($conditions as $condition) {
				$c2[$condition['id']] = $condition;
				$c2[$condition['name']] = $condition;
			}
			$conditions = $c2;
		}

		return $conditions;
	}

	public static function wp_get_nav_menu_items($items) {
		$conditions = If_Menu::get_conditions($for_testing = true);
		$hidden_items = array();

		$canPeek = get_option('if-menu-peak') && current_user_can('edit_theme_options');

		foreach ($items as $key => $item) {

			if (in_array($item->menu_item_parent, $hidden_items)) {
				if ($canPeek) {
					$item->classes[] = 'if-menu-peek';
				} else {
					unset($items[$key]);
				}
				$hidden_items[] = $item->ID;
			} else {
				$enabled = get_post_meta($item->ID, 'if_menu_enable');

				if ($enabled && $enabled[0] !== '0') {
					$if_condition_types = get_post_meta($item->ID, 'if_menu_condition_type');
					$if_conditions = get_post_meta($item->ID, 'if_menu_condition');

					$eval = array();

					foreach ($enabled as $index => $operator) {
						$singleCondition = '';

						if ($index) {
							$singleCondition .= $operator . ' ';
						}

						$bit1 = $if_condition_types[$index] === 'show' ? 1 : 0;
						$bit2 = $if_condition_types[$index] === 'show' ? 0 : 1;

						$singleCondition .= call_user_func($conditions[$if_conditions[$index]]['condition'], $item) ? $bit1 : $bit2;

						$eval[] = $singleCondition;
					}

					if ((count($eval) === 1 && $eval[0] == 0) || (count($eval) > 1 && !eval('return ' . implode(' ', $eval) . ';'))) {
						if ($canPeek) {
								$item->classes[] = 'if-menu-peek';
								$item->attr_title = __('If Menu peek - this menu item should be hidden', 'if-menu');
							} else {
								unset($items[$key]);
							}
						$hidden_items[] = $item->ID;
					}
				}
			}
		}

		return $items;
	}

	public static function admin_init() {
		global $pagenow;

		if ($pagenow == 'nav-menus.php' || $pagenow == 'themes.php') {
		  wp_enqueue_script('if-menu-js', plugins_url('assets/if-menu.js', __FILE__), array('jquery'));
		  wp_enqueue_style('if-menu-css', plugins_url('assets/if-menu.css', __FILE__));

		  wp_localize_script('if-menu-js', 'IfMenu', array(
		    'conflictErrorMessage'  =>  sprintf(
		      wp_kses(
		        __('<strong>If Menu</strong> detected a conflict with another plugin or theme and may not work as expected. <a href="%s" target="_blank">Read more about the issue here</a>', 'if-menu'),
		        array('a' => array('href' => array()), 'strong' => array())
		      ),
		      esc_url('https://wordpress.org/plugins/if-menu/faq/')
		    )
		  ));
		}
	}

	public static function saveSettings() {
		global $pagenow;

		if ($pagenow == 'themes.php' && isset($_POST['if-menu-settings'])) {
			update_option('if-menu-peak', $_POST['if-menu-peek']);
		}
	}

	public static function page() {
		$ifMenuPeek = get_option('if-menu-peak');
		?>

		<div class="wrap about-wrap if-menu-wrap">
			<h1><?php _e('If Menu', 'if-menu') ?></h1>
			<p class="about-text">
				<?php _e('Thanks for using <strong>If Menu</strong>! This plugin allows you to hide or only show menu items with visibility rules, ex: <code>Display menu item if User is logged in</code> or <code>Hide menu item if Using mobile device</code>', 'if-menu') ?>
				—
				<a href="<?php echo admin_url('nav-menus.php') ?>" class="button"><?php _e('Manage your menus', 'if-menu') ?></a></p>
			<hr class="wp-header-end">

			<div class="feature-section two-col">
				<div class="col">
					<h3><?php _e('Basic set of visibility rules', 'if-menu') ?></h3>
					<ul>
						<li><?php _e('User state: User is logged in', 'if-menu') ?></li>
						<li><?php _e('User roles: Admin, Editor, Author, etc', 'if-menu') ?></li>
						<li><?php _e('Page type: Front page, Single page, Single post', 'if-menu') ?></li>
						<li><?php _e('Device Is Mobile', 'if-menu') ?></li>
						<li><?php _e('Language Is RTL', 'if-menu') ?></li>
					</ul>
					<p><?php _e('Theme / plugin developers can extend the plugin by adding custom visibility rules', 'if-menu') ?></p>
				</div>
				<div class="col">
					<h3><?php _e('Visibility rule type', 'if-menu') ?></h3>
					<p><?php _e('Visibity rules can have 2 states:', 'if-menu') ?></p>
					<ul>
						<li><strong class="if-menu-green"><?php _e('Show', 'if-menu') ?></strong> - <?php _e('show menu item only if vsibility rule passes', 'if-menu') ?></li>
						<li><strong class="if-menu-red"><?php _e('Hide', 'if-menu') ?></strong> - <?php _e('hide menu item if rule passes', 'if-menu') ?></li>
					</ul>
				</div>
			</div>

			<hr>

			<h3 class="title"><?php _e('Settings', 'if-menu') ?></h3>

			<form method="post" action="">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php _e('If Menu peek', 'if-menu') ?></th>
							<td>
								<fieldset>
									<label><input type="checkbox" name="if-menu-peek" value="1" <?php checked($ifMenuPeek, 1) ?>> <?php _e('Enable If Menu peek', 'if-menu') ?></label><br>
								</fieldset>
								<p class="description"><?php _e('Let administrators preview hidden menu items on website (useful for testing)', 'if-menu') ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit"><input type="submit" name="if-menu-settings" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'if-menu') ?>"></p>
			</form>

		</div>

		<?php
	}

	public static function addAssets() {
		wp_enqueue_style('if-menu-site-css', plugins_url('assets/if-menu-site.css', __FILE__));
	}

  public static function menu_item_fields( $item_id ) {
    $conditions = If_Menu::get_conditions();
    $if_menu_enable = get_post_meta( $item_id, 'if_menu_enable' );
    $if_menu_condition_type = get_post_meta( $item_id, 'if_menu_condition_type' );
    $if_menu_condition = get_post_meta( $item_id, 'if_menu_condition' );

    if (!count($if_menu_enable)) {
      $if_menu_enable[] = 0;
      $if_menu_condition_type[] = '';
      $if_menu_condition[] = '';
    }

    $groupedConditions = array();
    foreach ($conditions as $condition) {
      $groupedConditions[isset($condition['group']) ? $condition['group'] : 'Custom'][] = $condition;
    }
    ?>

    <p class="if-menu-enable description description-wide">
      <label>
        <input <?php if (isset($if_menu_enable[0])) checked( $if_menu_enable[0], 1 ) ?> type="checkbox" value="1" class="menu-item-if-menu-enable" name="menu-item-if-menu-enable[<?php echo esc_attr( $item_id ); ?>][]" />
        <?php esc_html_e( 'Change menu item visibility', 'if-menu' ) ?>
      </label>
    </p>

    <div class="if-menu-conditions" style="display: <?php echo $if_menu_enable[0] ? 'block' : 'none' ?>">
      <?php for ($index = 0; $index < count($if_menu_enable); $index++) : ?>
        <p class="if-menu-condition description description-wide">
          <span class="if-menu-condition-rule">
          <select class="menu-item-if-menu-condition-type" id="edit-menu-item-if-menu-condition-type-<?php echo esc_attr( $item_id ); ?>" name="menu-item-if-menu-condition-type[<?php echo esc_html( $item_id ); ?>][]" data-val="<?php echo esc_html($if_menu_condition_type[$index]) ?>">
            <option <?php selected( 'show', $if_menu_condition_type[$index] ) ?> value="show"><?php esc_html_e( 'Show', 'if-menu' ) ?></option>
            <option <?php selected( 'hide', $if_menu_condition_type[$index] ) ?> value="hide"><?php esc_html_e( 'Hide', 'if-menu' ) ?></option>
          </select>
          <?php esc_html_e( 'if', 'if-menu' ); ?>
          <select class="menu-item-if-menu-condition" id="edit-menu-item-if-menu-condition-<?php echo esc_attr( $item_id ); ?>" name="menu-item-if-menu-condition[<?php echo esc_attr( $item_id ); ?>][]">
            <?php foreach ($groupedConditions as $group => $conditions) : ?>
              <optgroup label="<?php echo esc_attr( $group ) ?>">
                <?php foreach( $conditions as $condition ): ?>
                  <option value="<?php echo $condition['id'] ?>" <?php selected( $condition['id'], $if_menu_condition[$index] ) ?> <?php selected( $condition['name'], $if_menu_condition[$index] ) ?>><?php echo esc_html( $condition['name'] ); ?></option>
                <?php endforeach ?>
              </optgroup>
            <?php endforeach ?>
          </select>
          </span>
          <select class="menu-item-if-menu-enable-next" name="menu-item-if-menu-enable[<?php echo esc_attr( $item_id ); ?>][]">
            <option value="false">+</option>
            <option value="and" <?php if (isset($if_menu_enable[$index + 1])) selected( 'and', $if_menu_enable[$index + 1] ) ?>><?php esc_html_e( 'AND', 'if-menu' ) ?></option>
            <option value="or" <?php if (isset($if_menu_enable[$index + 1])) selected( 'or', $if_menu_enable[$index + 1] ) ?>><?php esc_html_e( 'OR', 'if-menu' ) ?></option>-->
          </select>
        </p>
      <?php endfor ?>
    </div>

    <?php
  }

  public static function menu_item_title( $item_id ) {
    $if_menu_enabled = get_post_meta( $item_id, 'if_menu_enable' );

    if ( count( $if_menu_enabled ) && $if_menu_enabled[0] !== '0' ) {
      $conditionTypes = get_post_meta( $item_id, 'if_menu_condition_type' );
      $conditions = get_post_meta( $item_id, 'if_menu_condition' );
      $rules = If_Menu::get_conditions($for_testing = true);

      if ( $conditionTypes[0] === 'show' ) {
        $conditionTypes[0] = '';
      }

      echo '<span class="is-submenu">';
      printf( __( '%s if %s', 'if-menu' ), $conditionTypes[0], $rules[$conditions[0]]['name'] );
      if ( count( $if_menu_enabled ) > 1 ) {
        printf( ' ' . _n( 'and 1 more rule', 'and %d more rules', count( $if_menu_enabled ) - 1, 'if-menu' ), count( $if_menu_enabled ) - 1 );
      }
      echo '</span>';
    }
  }

  public static function customWalker() {
    global $wp_version;

    if (version_compare( $wp_version, '4.7.0', '>=')) {
      require_once(plugin_dir_path(__FILE__) . 'if-menu-nav-menu-4.7.php');
    } elseif ( version_compare( $wp_version, '4.5.0', '>=' ) ){
      require_once(plugin_dir_path(__FILE__) . 'if-menu-nav-menu-4.5.php');
    } else {
      require_once(plugin_dir_path(__FILE__) . 'if-menu-nav-menu.php');
    }

    return 'If_Menu_Walker_Nav_Menu_Edit';
  }

	public static function wp_update_nav_menu_item( $menu_id, $menu_item_db_id ) {
    if (isset($_POST['menu-item-if-menu-enable'])) {

      delete_post_meta( $menu_item_db_id, 'if_menu_enable' );
      delete_post_meta( $menu_item_db_id, 'if_menu_condition_type' );
      delete_post_meta( $menu_item_db_id, 'if_menu_condition' );

      foreach ( $_POST['menu-item-if-menu-enable'][$menu_item_db_id] as $index => $value ) {
        if ( in_array( $value, array('1', 'and', 'or') ) ) {
          add_post_meta( $menu_item_db_id, 'if_menu_enable', $value );
          add_post_meta( $menu_item_db_id, 'if_menu_condition_type', $_POST['menu-item-if-menu-condition-type'][$menu_item_db_id][$index] );
          add_post_meta( $menu_item_db_id, 'if_menu_condition', $_POST['menu-item-if-menu-condition'][$menu_item_db_id][$index] );
        } else {
          break;
        }
      }
    }
  }

	public static function pluginActivate() {
		add_option('if-menu-peak', 1);
	}

}



/* ------------------------------------------------
	Include default conditions for menu items
------------------------------------------------ */

include 'conditions.php';



/* ------------------------------------------------
	Run the plugin
------------------------------------------------ */

register_activation_hook(__FILE__, array('If_Menu', 'pluginActivate'));
add_action('plugins_loaded', 'If_Menu::init');
