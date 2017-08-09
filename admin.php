<?php

// Backend only

class DFDebug_Admin {
	// Reference back to $dfdebug global.
	var $dfdebug = null;

	function __construct( $parent = null ) {
		if ( $parent )
			$this->dfdebug = $parent;

		add_action( 'admin_init',      array( &$this, 'admin_init' ) );
		add_action( 'admin_menu',      array( &$this, 'admin_menu' ) );

	}

	function admin_init() {
		global $dfdebug;
		$this->settings_fields();

		$load_all_available_modules = is_admin() && is_super_admin();
		if ( apply_filters( 'dfdebug_load_all_available_modules', $load_all_available_modules ) ) {
			$dfdebug->load_all_modules();
		}

	}

	function admin_menu() {
		$parent_id = 'dfdebug-menu';
		add_utility_page( 'Debug Tools', 'Debug Tools', '', $parent_id );
		add_submenu_page( $parent_id, 'Settings',     'Settings',     'manage_options', 'dfdebug-admin',        array( &$this, 'admin_settings_page' ) );
		add_submenu_page( $parent_id, 'User Options', 'User Options', 'read',           'dfdebug-user-options', array( &$this, 'admin_user_settings_page' ) );

		do_action( 'dfdebug_admin_menu', $parent_id );
	}

	function settings_fields() {
		global $dfdebug;

		register_setting( 'dfdebug-admin', 'dfdebug_enabled' );
		register_setting( 'dfdebug-admin', 'dfdebug_modules_global_active',      array( &$this, 'sanitize_active_modules' ) );
		register_setting( 'dfdebug-admin', 'dfdebug_modules_available_to_users', array( &$this, 'sanitize_active_modules' ) );

		$text_callback = array( &$this, 'input_text_field' );
		$checkbox_callback = array( &$this, 'input_checkbox_field' );

//		add_settings_section( 'global', 'Global Settings', '__return_false', 'dfdebug-admin' );
//		add_settings_field( 'dfdebug_enabled', 'Enabled', $checkbox_callback, 'dfdebug-admin', 'global', array( 'label_for' => 'dfdebug_enabled', 'name' => 'dfdebug_enabled', 'value' => 1 ) );

		add_settings_section( 'modules', 'Modules', '__return_false', 'dfdebug-admin' );
		add_settings_field( 'dfdebug_modules_global_active', 'Active for All', array( $this, 'module_selector' ), 'dfdebug-admin', 'modules',
			array(
				'label_for' => 'dfdebug_modules_global_active',
				'options_cb' => array( $this, 'all_modules' ),
				'selected_cb' => array( $this, 'modules_active_for_all_users' )
			)
		);

		add_settings_field( 'dfdebug_modules_available_to_users', 'Available to Users', array( $this, 'module_selector' ), 'dfdebug-admin', 'modules',
			array(
				'label_for' => 'dfdebug_modules_available_to_users',
				'options_cb' => array( $this, 'all_modules' ),
				'selected_cb' => array( $this, 'modules_available_to_all_users' )
			)
		);

		add_settings_section( 'user_settings', 'User settings', '__return_false', 'dfdebug-admin' );
		add_settings_field( '_current_users', 'Current Users', array( &$this, 'current_user_selector' ), 'dfdebug-admin', 'user_settings', array( 'label_for' => '_current_users' ) );

		// User Options page
		add_settings_section( 'modules', 'Modules', '__return_false', 'dfdebug-user-options' );
		add_settings_field( 'dfdebug_active_modules', 'Your active modules', array( $this, 'module_selector' ), 'dfdebug-user-options', 'modules',
			array(
				'label_for' => 'dfdebug_active_modules',
				'options_cb' => array( $this, 'modules_available_to_user' ),
				'selected_cb' => array( $this, 'modules_active_for_user' )
			)
		);
	}

	function input_text_field( $args ) {
		extract( $args );
		echo sprintf( '<input type="text" id="%s" name="%s" value="%s" />', $label_for, $name, get_option( $name ) );
		echo self::settings_description( $args );
	}

	function input_checkbox_field( $args ) {
		extract( $args );
		$opt = get_option( str_replace( '[]', '', $name ) );
		$is_chk = is_array( $opt ) ? in_array( $value, $opt ) : $value == $opt;
		echo sprintf( '<input type="checkbox" id="%s" name="%s" value="%s" %s />', $label_for, $name, $value, checked( $is_chk, true, false ) );
		echo ' ' . self::settings_description( $args );
	}

	function input_select_field( $args ) {

	}

	function module_selector( $args ) {
		global $dfdebug;
		$module_keys = is_callable( $args['options_cb'] ) ? call_user_func( $args['options_cb'] ) : array();
		$selected = is_callable( $args['selected_cb'] ) ? call_user_func( $args['selected_cb'] ) : array();

		foreach ( $module_keys as $slug ) {
			if ( !isset( $dfdebug->modules[$slug] ) )
				continue;

			$module = $dfdebug->modules[$slug];
			$id = $args['label_for'] . '_' . $slug;
			$checked = checked( in_array( $slug, (array) $selected ), true, false );
			echo sprintf( '<label id="%s"><input type="checkbox" id="%s" name="%s[]" value="%s" %s /> %s</label>',
				$id, $id, $args['label_for'], $slug, $checked, $module->name
			);
			if ( !empty( $module->description ) ) {
				echo sprintf( '<span style="padding-left: 2em; display: block;" class="description">%s</span><br />', $module->description );
			}
		}
	}

	function all_modules() {
		global $dfdebug;
		return array_keys( $dfdebug->modules );
	}

	function modules_available_to_all_users() {
		return get_option( 'dfdebug_modules_available_to_users', array() );
	}

	function modules_active_for_all_users() {
		return get_option( 'dfdebug_modules_global_active', array() );
	}

	function modules_available_to_user( $user_id = false ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}

		$all_available = $this->modules_available_to_all_users();

		if ( $user_id ) {
			$modules = get_user_meta( $user_id, 'dfdebug_available_modules', true );
		}

		if ( empty( $modules ) ) {
			$modules = array();
		}

		$modules = array_merge( $all_available, $modules );

		return apply_filters( 'dfdebug_modules_available_to_user', $modules );
	}

	function modules_active_for_user( $user_id = false ) {

		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			$modules = get_user_meta( $user_id, 'dfdebug_active_modules', true );
		}

		if ( empty( $modules ) ) {
			$modules = array();
		}

		return $modules;
	}

	function current_user_selector( $args ) {
		global $wpdb;
		$users = $wpdb->get_results(
			"SELECT u.ID, u.display_name, u.user_login " .
			"FROM $wpdb->usermeta um INNER JOIN $wpdb->users u ON um.user_id = u.ID " .
			"WHERE um.meta_key='dfdebug_active_modules' AND um.meta_value != ''"
		);

		if ( empty( $users ) ) {
			echo '<p>No users are currently using Debug Tools.</p>';
		} else {
			$size = count( $users ) > 10 ? 10 : count( $users );
			echo sprintf( '<select name="%s" size="%d">', $args['label_for'], $size );
			foreach ( $users as $user ) {
				echo sprintf( '<option value="%d">%s (%s)</option>', $user->ID, esc_html( $user->display_name ), esc_html( $user->user_login ) );
			}
		}

		echo '<br /><p><a href="#" class="button">Enable a User</a></p>';

	}

	function settings_description( $args ) {
		$html = '';
		if ( isset( $args['description'] ) ) {
			$html = sprintf( '<span class="description">%s</span>', $args['description'] );
		}
		return $html;
	}

	function admin_user_settings_page( $args = array() ) {
		global $plugin_page, $pagenow;
		$this->process_user_settings_page();

		$url_args = array(
			'page' => $plugin_page,
			'noheader' => 1
		);
		$url = add_query_arg( $url_args, admin_url( $pagenow ) );
		$this->admin_settings_page( $args, $url );
	}

	function admin_settings_page( $args = array(), $page = 'options.php' ) {
		global $plugin_page, $title;

		settings_errors();

		echo '<div class="wrap">';
		echo '<h2>' . $title . '</h2>';
		echo '<form method="post" action="' . $page . '">';
		settings_fields( $plugin_page );
		do_settings_sections( $plugin_page );
		echo '<p><input type="submit" value="Save" class="button button-primary" /></p>';
		echo '</form></div>';
	}

	function sanitize_active_modules( $modules ) {
		global $dfdebug;

		if ( empty( $modules ) || !is_array( $modules ) ) {
			$modules = array();
		}

		$available = $dfdebug->get_available_modules();
		$modules = array_intersect( $modules, array_keys( $available ) );

		return $modules;
	}

	function process_user_settings_page() {
		global $pagenow, $plugin_page;

		if ( ! $user_id = get_current_user_id() )
			return;

		$options = array(
			'dfdebug_active_modules'
		);

		$action = isset( $_POST['action'] ) ? $_POST['action'] : '';

		if ( 'update' == $action ) {
			check_admin_referer( $plugin_page . '-options' );

			if ( $options ) {
				foreach ( $options as $option ) {
					$option = trim( $option );
					$value = null;
					if ( isset( $_POST[ $option ] ) ) {
						$value = $_POST[ $option ];
						if ( ! is_array( $value ) )
							$value = trim( $value );
						$value = wp_unslash( $value );
					}
					update_user_meta( $user_id, $option, $value );
				}
			}

			if ( !count( get_settings_errors() ) ) {
				add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
			}

			set_transient('settings_errors', get_settings_errors(), 30);

			/**
			 * Redirect back to the settings page that was submitted
			 */
			$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
			wp_redirect( $goback );
			exit;
		}
	}
}