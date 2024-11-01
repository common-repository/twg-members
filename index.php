<?php
/*
Plugin Name: TheWebGears Members
Description: This plugin gives you the possibility to have members on your WordPress-based site - separate from the WP users.
Author: beatmasta [Alex Vanyan] (The Web Gears http://thewebgears.com)
Version: 1.1
Author URI: http://cs16.us
*/


if(!function_exists('twg_members_install')):
function twg_members_install()
{
    global $wpdb;
    
    $table = $wpdb->prefix . "twg_members";
    $structure = "CREATE TABLE $table (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL,
		password VARCHAR(255) NOT NULL,
	UNIQUE KEY id (id)
    );";
    $wpdb->query($structure);
	
    $table = $wpdb->prefix . "twg_member_fields";
    $structure = "CREATE TABLE $table (
        id INT(11) NOT NULL AUTO_INCREMENT,
		type ENUM('text', 'textarea', 'dropdown') DEFAULT 'text',
        name VARCHAR(50) NOT NULL,
		title VARCHAR(50),
		value TEXT,
		regex VARCHAR(255),
	UNIQUE KEY id (id)
    );";
    $wpdb->query($structure);
    
    $table = $wpdb->prefix . "twg_memberfield_values";
    $structure = "CREATE TABLE $table (
        id INT(11) NOT NULL AUTO_INCREMENT,
		uid INT(11) NOT NULL,
		fname VARCHAR(50) NOT NULL,
        value LONGTEXT,
        private TINYINT(1) NOT NULL DEFAULT '0',
	UNIQUE KEY id (id)
    );";
    $wpdb->query($structure);
	
	add_option('twg_memberopts', array());
}
register_activation_hook(__FILE__, 'twg_members_install');
endif;

if(!function_exists('twg_members_uninstall')):
function twg_members_uninstall()
{
    global $wpdb;
    
    $table = $wpdb->prefix . "twg_members";
    $unstructure = "DROP TABLE $table;";
    $wpdb->query($unstructure);
	
    $table = $wpdb->prefix . "twg_member_fields";
    $unstructure = "DROP TABLE $table;";
    $wpdb->query($unstructure);
	
    $table = $wpdb->prefix . "twg_memberfield_values";
    $unstructure = "DROP TABLE $table;";
    $wpdb->query($unstructure);
	
	delete_option('twg_memberopts');
}
register_deactivation_hook(__FILE__, 'twg_members_uninstall');
endif;

class TWGMembers {
	
	public $user_label = "";
	public $pass_label = "";
	public $submit_value = "";
	public $login_error = "";
	public $reg_error = "";
	public $reg_success = "";
	public $registration_errors = "";
	private $regx = array();
	private $session = array(
							 'auth' => 0,
							 'user' => 'Guest'
							);
	
	function __construct() {
		session_name('twg_membersession');
		session_start();
		require dirname(__FILE__) . "/regx.php";
		$this->regx = $defaultRegexes;
		if($_SESSION['auth'] && $_SESSION['user']) {
			$this->session = $_SESSION;
		}
		if(!get_option('twg_memberopts', false)) {
			add_option('twg_memberopts', array());
		}
		add_action('admin_init', array($this, 'initialize'));
		wp_register_style('twg-member-print-styles', WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/styles/style.css');
		add_action('wp_print_styles', array($this, 'simple_page_styles'));
	}
	
	function initialize() {
		wp_register_style('twg-member-settings-styles', WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/styles/admin.css');
		add_action('admin_print_styles', array($this, 'settings_page_styles'));
		wp_register_script('twg-member-settings-scripts', WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/scripts.js');
		add_action('admin_print_scripts', array($this, 'settings_page_scripts'));
	}
	
	function is_logged_in() {
		if($this->sess('auth') == 1) {
			return true;
		} else {
			return false;
		}
	}
	
	function get_username($uid = null) {
        if(!is_null($uid)) {
            global $wpdb;
            $query = $wpdb->get_results("SELECT `username` FROM `" . $wpdb->prefix . "twg_members` WHERE `id` = " . (int) $uid);
            return empty($query[0]->username) ? 'Guest' : $query[0]->username;
        } else {
            return $this->sess('user');
        }
	}
	
	function logout($force = false) {
		if((isset($_GET['logout']) && !isset($_POST['ulogin_submit'])) || true === $force) {
			$this->sess('auth', 0);
			$this->sess('user', 'Guest');
			unset($_SESSION);
			return true;
		} else {
			return false;
		}
	}
	
	function login_form($filters) {
		global $wpdb;
		
		parse_str($filters, $filter_arr);
		
		$logged_in = false;
		$this->logout();
		
		$this->user_label = $this->opt('label_user') ? $this->opt('label_user') : 'Username:';
		$this->pass_label = $this->opt('label_pass') ? $this->opt('label_pass') : 'Password:';
		
		foreach((array) $filter_arr as $fkey => $fval) {
			$this->{$fkey} = $fval;
		}
		
		if($this->sess('auth') == 1) {
			$logged_in = true;
		}
		
		if($_POST['ulogin_submit'] && !$logged_in) {
			if(($usr = $_POST['login_field']) && ($pwd = $_POST['password_field'])) {
				$table = $wpdb->prefix . "twg_members";
				$qstring = "SELECT * FROM `$table` WHERE `username` = '$usr'";
				$userselect = $wpdb->get_results($qstring);
				if(count($userselect)) {
					if(md5($pwd) == $userselect[0]->password) {
						$logged_in = true;
						$this->sess('auth', 1);
						$this->sess('uid', $userselect[0]->id);
						$this->sess('user', $_POST['login_field']);
					} else {
						$this->login_error = $this->opt('error_wrongpass') ? $this->opt('error_wrongpass') : "Password is incorrect";
					}
				} else {
					$this->login_error = $this->opt('error_wronguser') ? $this->opt('error_wronguser') : "Username is incorrect";
				}
			} else {
				$this->login_error = $this->opt('error_nouserpass') ? $this->opt('error_nouserpass') : "Username and/or password is empty";
			}
		}
		
		if(!$logged_in) {
			if($this->opt('custom_loginform')) {
				echo $this->opt('custom_loginform');
			} else {
				$this->draw_login_form();
			}
		} else {
			if($this->opt('uloggedin_msg')) {
				echo $this->opt('uloggedin_msg');
			} else {
				$this->draw_loggedin_menu();
			}
		}
	}
	
	function registration_form() {
		$this->user_label = $this->opt('label_user_reg');
		$this->pass_label = $this->opt('label_pass_reg');
		$this->draw_registration_form();
	}
	
	function login_errors() {
		return $this->login_error;
	}
	
	function registration_errors() {
		return $this->registration_errors;
	}
	
	function set_settings_page() {
		add_options_page('TWG Members', 'TWG Members', 'administrator', 'twg-members', array($this, 'draw_settings_page'));
	}
	
	function simple_page_styles() {
		wp_enqueue_style('twg-member-print-styles');
	}
	
	function settings_page_styles() {
		wp_enqueue_style('twg-member-settings-styles');
	}
	
	function settings_page_scripts() {
		wp_enqueue_script('twg-member-settings-scripts');
	}
	
	function draw_settings_page() {
	
		global $wpdb;
		
		if(isset($_POST) && array_key_exists('regfields', $_POST)) {
		
			$table = $wpdb->prefix . "twg_member_fields";
			$qstring = "TRUNCATE TABLE `$table`";
			$wpdb->query($qstring);
			
			$qstring = "INSERT INTO `$table` VALUES ";
			
			foreach((array) $_POST['regfields'] as $field) {
				$qstring .= "(NULL, '" . $field['type'] . "', '" . $field['name'] . "', '" . $field['title'] . "', '" . $field['value'] . "', '" . $field['regex'] . "')" . (end($_POST['regfields']) != $field ? ", " : " ");
			}
		
			$wpdb->query($qstring);
		}
		
		if(!$_POST['preserve_regvalues']) {
			$_POST['preserve_regvalues'] = 'off';
		}
		
		if(isset($_POST['update_twg_membersettings'])) {
			$post_excludes = array('update_twg_membersettings', 'regfields', 'newregfield_radio', 'page_on_tab');
			foreach($_POST as $postkey => $postval) {
				if(!in_array($postkey, $post_excludes)) {
					$this->opt($postkey, $postval);
				}
			}
		}
		
		echo '<div class="twg_settings_wrapper">
				
				<div class="twg_settings_updated_bar"' . (isset($_POST['update_twg_membersettings']) ? ' style="display: block;"' : '') . '>Settings updated!</div>
		
				<h1>TheWebGears Members :: Settings</h1>
				
				<ul class="twg_settings_tab_bar no_select">
					<li class="twg_settings_tab twg_active_tab">
						Login Settings
					</li>
					<li class="twg_settings_tab">
						Registration Settings
					</li>
				</ul>
				
				<h2 class="twg_settings_title">Login Settings</h2>
				<form method="POST" action="">
					<div class="twg_settings_content twg_settings_login twg_visible">
						<div class="twg_settings_content_block">
							<label for="label_user">Title for \'username\' field:</label>
							<input class="twg_settings_textfield" id="label_user" name="label_user" value="' . $this->opt('label_user') . '" />
						</div>
						<div class="twg_settings_content_block">
							<label for="label_pass">Title for \'password\' field:</label>
							<input class="twg_settings_textfield" id="label_pass" name="label_pass" value="' . $this->opt('label_pass') . '" />
						</div>
						<div class="h_separator"></div>
						<div class="twg_settings_content_block">
							<label for="custom_loginform">Custom login form (HTML, leave blank to use default form):</label>
							<textarea class="twg_settings_textarea" id="custom_loginform" name="custom_loginform">' . $this->opt('custom_loginform') . '</textarea>
						</div>
						<div class="twg_settings_content_block">
							<label for="uloggedin_msg">User logged in message (HTML allowed):</label>
							<textarea class="twg_settings_textarea" id="uloggedin_msg" name="uloggedin_msg">' . $this->opt('uloggedin_msg') . '</textarea>
						</div>
						<div class="twg_settings_errors_block">
							<h3>Errors configuration</h3>
							<br /><br />
							<div class="twg_settings_errors_content_block">
								<div class="twg_settings_content_block">
									<label for="error_nouserpass">Username and/or password is empty:</label>
									<input class="twg_settings_textfield" id="error_nouserpass" name="error_nouserpass" value="' . $this->opt('error_nouserpass') . '" />
								</div>
								<div class="twg_settings_content_block">
									<label for="error_wronguser">Username is incorrect:</label>
									<input class="twg_settings_textfield" id="error_wronguser" name="error_wronguser" value="' . $this->opt('error_wronguser') . '" />
								</div>
								<div class="twg_settings_content_block">
									<label for="error_wrongpass">Password is incorrect:</label>
									<input class="twg_settings_textfield" id="error_wrongpass" name="error_wrongpass" value="' . $this->opt('error_wrongpass') . '" />
								</div>
							</div>
						</div>
						<div class="h_separator"></div>
					</div>
					<div class="twg_settings_content twg_settings_register">
						
						<div class="twg_register_settings_general">
							<div class="twg_settings_content_block">
								<label for="label_user_reg">Title for \'username\' field:</label>
								<input class="twg_settings_textfield" id="label_user_reg" name="label_user_reg" value="' . $this->opt('label_user_reg') . '" />
							</div>
							<div class="twg_settings_content_block">
								<label for="label_pass_reg">Title for \'password\' field:</label>
								<input class="twg_settings_textfield" id="label_pass_reg" name="label_pass_reg" value="' . $this->opt('label_pass_reg') . '" />
							</div>
							<div class="twg_settings_content_block">
								<label for="regex_for_uname">Validation rule for username:</label>
								<select class="twg_settings_dropdown" id="regex_for_uname" name="regex_for_uname">';
									foreach($this->regx as $kreg => $regx) {
										echo '<option value="' . $kreg . '"';
										if((!$this->opt('regex_for_uname') && $kreg == 'usr') || $this->opt('regex_for_uname') == $kreg) {
											echo ' selected="selected"';
										}
										echo '>' . $regx['name'] . '</option>';
									}
echo '
								</select>
							</div>
							<div class="twg_settings_content_block">
								<label for="regex_for_passwd">Validation rule for password:</label>
								<select class="twg_settings_dropdown" id="regex_for_passwd" name="regex_for_passwd">';
									foreach($this->regx as $kreg => $regx) {
										echo '<option value="' . $kreg . '"';
										if((!$this->opt('regex_for_passwd') && $kreg == 'pwd') || $this->opt('regex_for_passwd') == $kreg) {
											echo ' selected="selected"';
										}
										echo '>' . $regx['name'] . '</option>';
									}
echo '
								</select>
							</div>
							<div class="twg_settings_content_block">
								<label for="preserve_regvalues">Preserve field values on form submit?</label>
								<input type="checkbox" id="preserve_regvalues" name="preserve_regvalues"' . ($this->opt('preserve_regvalues') == 'on' ? ' checked="checked"' : '') . ' />
							</div>
							<div class="twg_settings_content_block">
								<label for="error_useralreadyexists">Username already exists message:</label>
								<input class="twg_settings_textfield" id="error_useralreadyexists" name="error_useralreadyexists" value="' . $this->opt('error_useralreadyexists') . '" />
							</div>
							<div class="twg_settings_content_block">
								<label for="success_registered">Registration success message:</label>
								<input class="twg_settings_textfield" id="success_registered" name="success_registered" value="' . $this->opt('success_registered') . '" />
							</div>
						</div>
						
						<br style="float: left; clear: both;" />
							
						<h4>Your registration form looks like this</h4>
							
						<div class="twg_set_registration_fields">
							<div class="twg_registration_fields">
								<div class="twg_registration_field_row">
									<div class="twg_regfield_proto_label">' . ($this->opt('label_user_reg') ? $this->opt('label_user_reg') : 'Username:') . '</div>
									<div class="twg_delete_icon disabled"></div>
									<div class="twg_edit_icon disabled"></div>
									<div class="twg_registration_field twg_pseudofield_text"></div>
								</div>
								<div class="twg_registration_field_row">
									<div class="twg_regfield_proto_label">' . ($this->opt('label_pass_reg') ? $this->opt('label_pass_reg') : 'Password:') . '</div>
									<div class="twg_delete_icon disabled"></div>
									<div class="twg_edit_icon disabled"></div>
									<div class="twg_registration_field twg_pseudofield_text"></div>
								</div>';
									
									$fi = 0;
									$reg_fields = (array)$this->get_registration_fields();
									
									foreach($reg_fields as $field) {
										echo '
												<div class="twg_registration_field_row">
													' . ($field->title ? '<div class="twg_regfield_proto_label">' . $field->title . '</div>' : '') . '
													<div class="twg_delete_icon"></div>
													<div class="twg_edit_icon"></div>
													<div class="twg_registration_field twg_pseudofield_' . $field->type . '">';
													
													if($field->type == 'dropdown') {
														$val = explode('::', $field->value);
														echo $val[0] ? $val[0] : $val;
													} else {
														echo $field->value;
													}
													
										echo '		</div>
													<input type="hidden" name="regfields[' . $fi . '][name]" value="' . $field->name . '" class="twg_reghidden_name" />
													<input type="hidden" name="regfields[' . $fi . '][type]" value="' . $field->type . '" class="twg_reghidden_type" />
													<input type="hidden" name="regfields[' . $fi . '][title]" value="' . $field->title . '" class="twg_reghidden_title" />
													<input type="hidden" name="regfields[' . $fi . '][value]" value="' . $field->value . '" class="twg_reghidden_value" />
													<input type="hidden" name="regfields[' . $fi . '][regex]" value="' . $field->regex . '" class="twg_reghidden_regex" />
												</div>
										';
										$fi++;
									}
						
echo '						
						</div>
						<script type="text/javascript">
							var fi = ' . $fi . ';
						</script>
					</div>
					<div class="twg_new_registration_fields">
						<h3>Add new field:</h3>
						<div class="twg_new_regfields_radiogroup">
							<label for="newregfield_radio_text">text </label><input type="radio" id="newregfield_radio_text" name="newregfield_ftype" value="text" checked="checked" />
							<label for="newregfield_radio_textarea">textarea </label><input type="radio" id="newregfield_radio_textarea" name="newregfield_ftype" value="textarea" />
							<label for="newregfield_radio_dropdown">dropdown </label><input type="radio" id="newregfield_radio_dropdown" name="newregfield_ftype" value="dropdown" />
						</div>
						<ul class="twg_new_regfields_add">
							<li class="twg_new_regfields_add_text twg_visible">
								<div class="twg_newfield_row">Field name: <input type="text" class="newregfield_fname" /></div>
								<div class="twg_newfield_row">Field title: <input type="text" class="newregfield_ftitle" /></div>
								<div class="twg_newfield_row">Defaut value: <input type="text" class="newregfield_fvalue" /></div>
								<div class="twg_newfield_row twg_newfield_lastrow">Validation: 
									<select class="newregfield_fregex">
										<option value="" selected="selected">select rule</option>';
										foreach($this->regx as $kreg => $regx) {
											echo '<option value="' . $kreg . '">' . $regx['name'] . '</option>';
										}
echo '								</select>
								</div>
							</li>
							<li class="twg_new_regfields_add_textarea">
								<div class="twg_newfield_row">Field name: <input type="text" class="newregfield_fname" /></div>
								<div class="twg_newfield_row">Field title: <input type="text" class="newregfield_ftitle" /></div>
								<div class="twg_newfield_row">Defaut value: <input type="text" class="newregfield_fvalue" /></div>
								<div class="twg_newfield_row twg_newfield_lastrow">Validation: 
									<select class="newregfield_fregex">
										<option value="" selected="selected">select rule</option>';
										foreach($this->regx as $kreg => $regx) {
											echo '<option value="' . $kreg . '">' . $regx['name'] . '</option>';
										}
echo '								</select>
								</div>
							</li>
							<li class="twg_new_regfields_add_dropdown">
								<div class="twg_newfield_row">Field name: <input type="text" class="newregfield_fname" /></div>
								<div class="twg_newfield_row">Field title: <input type="text" class="newregfield_ftitle" /></div>
								<div class="twg_newfield_row">Values: <input type="text" class="newregfield_fvalue" /><div class="twg_new_fieldval_adder"></div><div class="twg_new_fieldvals"></div></div>
								<div class="twg_newfield_row twg_newfield_lastrow">Validation: 
									<select class="newregfield_fregex">
										<option value="" selected="selected">select rule</option>';
										foreach($this->regx as $kreg => $regx) {
											echo '<option value="' . $kreg . '">' . $regx['name'] . '</option>';
										}
echo '								</select>
								</div>
							</li>
						</ul>
						<input type="button" id="twg_new_regfields_adder" value="Add Field" />
					</div>
				</div>
				<div class="twg_settings_submit_block">
					<input type="hidden" name="page_on_tab" value="' . ($_POST['page_on_tab'] ? (int) $_POST['page_on_tab'] : 0) . '" />
					<input type="submit" name="update_twg_membersettings" value="Update Options" />
				</div>
			</form>
		  </div>
			  
			  ';
	}
	
	private function sess($key, $val = null) {
		if(is_null($val)) {
			if(!array_key_exists($key, $this->session)) {
				return false;
			} else {
				return $this->session[$key];
			}
		} else {
			$_SESSION[$key] = $val;
			$this->session[$key] = $val;
		}
	}
	
	private function opt($option, $value = null) {
		$options = get_option('twg_memberopts');
		if(is_null($value)) {
			return $options[$option] ? stripslashes($options[$option]) : false;
		} else {
			$options[$option] = $value;
			return update_option('twg_memberopts', $options);
		}
	}
	
	private function draw_loggedin_menu() {
		echo '
		<div class="twg_logged_in">
			Hello, <span class="twg_loggedin_username">' . $this->sess('user') . '</span> (<a class="twg_logout_link" href="?logout">logout</a>)
		</div>
		';
	}
	
	private function draw_login_form() {
		echo '
		<div class="twg_loginform_wrapper">
			<form name="twg_loginform" method="POST" action="">
				' . ($this->user_label ? '<label for="login_loginfield_label" class="login_loginfield_label">' . $this->user_label . '</label>' : '') . '
				<input type="text" id="login_loginfield_label" name="login_field" />
				' . ($this->pass_label ? '<label for="password_loginfield_label" class="password_loginfield_label">' . $this->pass_label . '</label>' : '') . '
				<input type="password" id="password_loginfield_label" name="password_field" />
				<input type="submit" id="ulogin_submit" name="ulogin_submit" value="' . ($this->submit_value ? $this->submit_value : 'Login') . '" />
			</form>
		</div>
		';
	}
	
	private function draw_registration_form() {
		global $wpdb;
		
		$field_regexes = array(
								'login_field' => $this->opt('regex_for_uname'),
								'password_field' => $this->opt('regex_for_passwd')
							  );
		$ignore_fields = array('twg_register_btn' => '');
		$submit_errors = array();
		
		$this->user_label = $this->opt('label_user') ? $this->opt('label_user') : 'Username:';
		$this->pass_label = $this->opt('label_pass') ? $this->opt('label_pass') : 'Password:';
		
		$table = $wpdb->prefix . "twg_member_fields";
		$qstring = "SELECT * FROM `$table`";
		$fields = $wpdb->get_results($qstring);
		
		foreach($fields as $field) {
			$field_regexes[$field->name] = $field->regex;
		}
		
		$post_arr = array_diff_key($_POST, $ignore_fields);
		
		if(isset($_POST['twg_register_btn'])) {
			$user = $_POST['login_field'];
			$pass = $_POST['password_field'];
			
			$table = $wpdb->prefix . "twg_members";
			$qstring = "SELECT * FROM `$table` WHERE `username` = '$user'";
			$userselect = $wpdb->get_results($qstring);
			if(!count($userselect)) {
				foreach($post_arr as $key => $val) {
					$key = addslashes($key);
					$val = addslashes($val);
					if($this->regx[$field_regexes[$key]]) {
						if(!preg_match($this->regx[$field_regexes[$key]]['regx'], $val)) {
							$submit_errors[] = $key;
						}
					}
                }
				if(!count($submit_errors)) {
                    $wpdb->query("INSERT INTO `$table` SET `username` = '$user', `password` = '" . md5($pass) . "'");
                    $table = $wpdb->prefix . "twg_members";
                    $res_id = $wpdb->get_results("SELECT last_insert_id() AS lid FROM `$table`");
                    $uid = $res_id[0]->lid;
                    $table = $wpdb->prefix . "twg_memberfield_values";
                    $qstring = "INSERT INTO `$table` VALUES ";
                    foreach($post_arr as $key => $val) {
                        if($key != 'login_field' && $key != 'password_field' && !preg_match('/^[a-z0-9\.,_-]+_private_check$/i', $key)) {
                            $qstring .= "(NULL, $uid, '$key', '$val', '0')" . (end($post_arr) != $val ? ", " : "");
                        }
                    }
					$wpdb->query($qstring);
					$this->reg_success = $this->opt('success_registered') ? $this->opt('success_registered') : "You were successfully registered";
				}
			} else {
				$this->reg_error = $this->opt('error_useralreadyexists') ? $this->opt('error_useralreadyexists') : "User with that username already exists";
			}
		}
		
		echo '
		<div class="twg_regform_wrapper">
			<form name="twg_regform" method="POST" action="">
				<div class="twg_regform_field_row">
					' . ($this->user_label ? '<label for="login_field" class="login_field_label">' . $this->user_label . '</label>' : '') . '
					<input type="text" id="login_field" name="login_field" value="' . ($this->opt('preserve_regvalues') == 'on' ? $_POST["login_field"] : '') . '" />
				</div>
				<div class="twg_regform_field_row">
					' . ($this->pass_label ? '<label for="password_field" class="password_field_label">' . $this->pass_label . '</label>' : '') . '
					<input type="password" id="password_field" name="password_field" value="' . ($this->opt('preserve_regvalues') == 'on' ? $_POST["password_field"] : '') . '" />
				</div>
		';
				
				$this->show_registration_fields();
		
		if(!empty($submit_errors)) {
			echo '<script type="text/javascript">
					jQuery(".twg_reg_error").live("mouseover", function() {
						jQuery(this).fadeOut(500, function() {
							jQuery(this).remove();
						});
					});
			';
				foreach($submit_errors as $subm_field) {
					echo '
						obj = jQuery(".twg_regform_wrapper [name=\"' . $subm_field . '\"]");
						obj.after(\'<div class="twg_reg_error">this field is invalid</div>\').next().css({
							"position": "absolute",
							"left": obj.offset().left, 
							"top": obj.offset().top, 
							"width": obj.get(0).clientWidth, 
							"height": obj.get(0).clientHeight + 1,
							"text-align": "center"
						});
						';
				}
			echo '</script>';
		}
				
		echo '
				<input type="submit" id="twg_register_btn" name="twg_register_btn" value="' . ($this->opt('regbutton_value') ? $this->opt('regbutton_value') : 'Register') . '" />
			</form>
			<span class="twg_regform_error_box">
				' . $this->reg_error . '
			</span>
			<span class="twg_regform_success_box">
				' . $this->reg_success . '
			</span>
		</div>
		';
	}
	
	function my_profile() {
		global $wpdb;
		
		$ignore_fields = array('update_profile_button_value' => '', 'login_field' => '', 'password_field' => '');
		$field_regexes = array(
								'login_field' => $this->opt('regex_for_uname'),
								'password_field' => $this->opt('regex_for_passwd')
							  );
		$submit_errors = array();
		$uid = (int) $this->sess('uid');
		$this->user_label = $this->opt('label_user') ? $this->opt('label_user') : 'Username:';
		$this->pass_label = $this->opt('label_pass') ? $this->opt('label_pass') : 'Password:';
		
		if(!$uid) {
			@header('location: ' . get_bloginfo('url'));
            echo '<script type="text/javascript">window.location.href = "' . get_bloginfo('url') . '";</script>';
            return false;
		}
		
		$table = $wpdb->prefix . "twg_member_fields";
		$qstring = "SELECT * FROM `$table`";
		$fields = $wpdb->get_results($qstring);
		
		foreach($fields as $field) {
			$field_regexes[$field->name] = $field->regex;
		}
		
		if(isset($_POST['update_profile_button_value'])) {

            if($_POST['password_field']) {
                if($this->regx[$field_regexes['password_field']]) {
					if(!preg_match($this->regx[$field_regexes['password_field']]['regx'], $_POST['password_field'])) {
                        $submit_errors[] = 'password_field';
                    }
                }
                if(!in_array('password_field', $submit_errors)) {
                    $table = $wpdb->prefix . "twg_members";
                    $wpdb->query("UPDATE `$table` SET `password` = '" . md5($_POST['password_field']) . "' WHERE `id` = $uid");
                }
            }

			$post_arr = array_diff_key($_POST, $ignore_fields);

            foreach($post_arr as $key => $val) {
                if(preg_match('/^[a-z0-9\.,_-]+_private_check$/i', $key)) {
                    unset($post_arr[$key]);
                    continue;
                }
				$key = addslashes($key);
				$val = addslashes($val);
				if($this->regx[$field_regexes[$key]]) {
					if(!preg_match($this->regx[$field_regexes[$key]]['regx'], $val)) {
						$submit_errors[] = $key;
					}
				}
            }

			if(!count($submit_errors)) {
                $table = $wpdb->prefix . "twg_memberfield_values";
                $wpdb->get_results("DELETE FROM `$table` WHERE `uid` = $uid AND `fname` <> 'login_field' AND `fname` <> 'password_field'");

                $qstring = "INSERT INTO `$table` VALUES ";
                foreach($post_arr as $key => $val) {
                    $qstring .= "(NULL, $uid, '$key', '$val', '" . ($_POST[$key . '_private_check'] ? '1' : '0') . "')" . (end($post_arr) != $val ? ", " : "");
                }
				$wpdb->query($qstring);
			}
		}
        
        $user_fields = $wpdb->get_results("
        SELECT *, memberfields.`value` AS vals FROM `" . $wpdb->prefix . "twg_member_fields` AS memberfields
        LEFT JOIN `" . $wpdb->prefix . "twg_memberfield_values` AS memberfield_values
        ON memberfields.`name` = memberfield_values.`fname` AND memberfield_values.`uid` = $uid
        ");

		echo '<div class="twg_my_profile_wrapper">
                  <form method="POST" action="">
                    <div class="twg_my_profile_row twg_my_profile_row_static">
                        <label class="twg_myprofile_label username_field_label">' . $this->user_label . '</label>
                        <div class="twg_my_profile_valuebox">
                            <span class="twg_username">' . $this->sess('user') . '</span>
                        </div>
                    </div>
                    <div class="twg_my_profile_row">
                        <label for="password_field" class="twg_myprofile_label password_field_label">' . $this->pass_label . '</label>
                        <div class="twg_my_profile_valuebox">
                            <input type="password" id="password_field" name="password_field" value="" />
                        </div>
                    </div>';

		            $this->show_registration_fields($user_fields);
        
		echo '
                    <input type="submit" id="twg_update_profile_btn" name="update_profile_button_value" value="' . ($this->opt('update_profile_button_value') ? $this->opt('update_profile_button_value') : 'Update Profile') . '" />
                </form>
			</div>
			 ';
		
		if(!empty($submit_errors)) {
			echo '<script type="text/javascript">
					jQuery(".twg_reg_error").live("mouseover", function() {
						jQuery(this).fadeOut(500, function() {
							jQuery(this).remove();
						});
					});
			';
				foreach($submit_errors as $subm_field) {
					echo '
						obj = jQuery(".twg_my_profile_row [name=\"' . $subm_field . '\"]");
						obj.after(\'<div class="twg_reg_error">this field is invalid</div>\').next().css({
							"position": "absolute",
							"left": obj.offset().left, 
							"top": obj.offset().top, 
							"width": obj.get(0).clientWidth, 
							"height": obj.get(0).clientHeight + 1,
							"text-align": "center"
						});
						';
				}
			echo '</script>';
		}
	}

    function profile_page() {

        $uid = (int) $_GET['uid'];

        if(!$uid) return false;

        $username = $this->get_username($uid);
        $user_fields = $this->get_member_fields($uid);

        echo '<div class="twg_public_profile_wrapper">';

        echo '
            <div class="twg_public_profile_row twg_pp_row_username">
                <div class="twg_public_profile_title">Username: </div>
                <div class="twg_public_profile_value">' . $username . '</div>
            </div>
        ';

        echo '<div class="twg_public_profile_wrapper">';

        foreach((array) $user_fields as $field) {
            echo '
                <div class="twg_public_profile_row twg_pp_row_' . $field->name . '">
                    <div class="twg_public_profile_title">' . ($field->title ? $field->title : '') . '</div>
                    <div class="twg_public_profile_value">' . $field->value . '</div>
                </div>
            ';
        }

        echo '</div>';
    }

    function member_list($atts) {

        $members_list = $this->get_members_list();

        if(!is_array($atts)) {
            parse_str($atts, $atts);
        }

        echo '<div class="twg_memberlist_wrapper">';

        foreach((array) $members_list as $member) {

            echo '<div class="twg_memberlist_member_wrapper">';

                echo '
                    <div class="twg_memberlist_row">
                        <div class="twg_memberlist_title">Username: </div>
                        <div class="twg_memberlist_value">' . $member->username . '</div>
                    </div>
                ';

                $memberfields = array_slice((array) $member->fields, 0, 3);

                foreach($memberfields as $field) {
                    echo '
                        <div class="twg_memberlist_row">
                            <div class="twg_memberlist_title">' . $field->title . '</div>
                            <div class="twg_memberlist_value">' . $field->value . '</div>
                        </div>
                    ';
                }

                if ( count($atts) && $atts['profile_page'] ) {
                    echo '
                        <div class="twg_memberlist_view_link">
                            <a href="' . get_bloginfo("url") . '/' . $atts['profile_page'] . '/?uid=' . $member->id . '">view profile</a>
                        </div>
                    ';
                }

            echo '</div>';

        }

        echo '</div>';
    }

    function get_members_list() {
        
        global $wpdb;

        $members = $wpdb->get_results("SELECT `id`, `username` FROM `" . $wpdb->prefix . "twg_members`");

        if(!$members || empty($members)) {
            return false;
        }

        foreach($members as &$member) {
            $member->fields = $this->get_member_fields($member->id);
        }

        return $members;
    }

    private function get_member_fields($uid) {
        global $wpdb;
        return $wpdb->get_results("
        SELECT *, memberfields.`value` AS vals FROM `" . $wpdb->prefix . "twg_member_fields` AS memberfields
        LEFT JOIN `" . $wpdb->prefix . "twg_memberfield_values` AS memberfield_values
        ON memberfields.`name` = memberfield_values.`fname` AND memberfield_values.`uid` = $uid
        WHERE memberfield_values.`private` <> 1
        ");
    }
	
	private function get_registration_fields() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "twg_member_fields`");
	}
	
	private function show_registration_fields($regfields = null) {
		
		$reg_fields = is_null($regfields) ? $this->get_registration_fields() : $regfields;
		
		foreach((array) $reg_fields as $field) {
			
			$def_value = ($regfields ? $field->value : $_POST[$field->name]);
			
			echo '<div class="' . ($regfields ? 'twg_my_profile_row' : 'twg_regform_field_row') . '">';
			
			echo ($field->title ? '<label for="' . $field->name . '" class="' . ($regfields ? 'twg_myprofile_label ' : '') . $field->name . '_label">' . $field->title . '</label>' : '');

            if($regfields) {
                echo '<div class="twg_my_profile_valuebox">';
            }

			switch($field->type) {
				case 'text':
					 echo '<input type="text" id="' . $field->name . '" name="' . $field->name . '" value="' . ($this->opt('preserve_regvalues') == 'on' ? $def_value : '') . '" />';
					break;
				case 'textarea':
					echo '<textarea id="' . $field->name . '" name="' . $field->name . '">' . ($this->opt('preserve_regvalues') == 'on' ? $def_value : '') . '</textarea>';
					break;
				case 'dropdown':
					echo '<select id="' . $field->name . '" name="' . $field->name . '">';
						foreach((array) explode('::', ($regfields ? $field->vals : $field->value)) as $opt) {
							echo '<option value="' . $opt . '"' . (($regfields ? true : $this->opt('preserve_regvalues') == 'on') && $opt == $def_value ? ' selected="selected"' : '') . '>' . $opt . '</option>';
						}
					echo '</select>';
					break;
			}

            if($regfields) {
                echo '<span class="twg_my_profile_checkbox"><input type="checkbox" id="' . $field->name . '_private_check" name="' . $field->name . '_private_check"' . ($field->private ? ' checked="checked"' : '') . ' /></span> <label for="' . $field->name . '_private_check" class="twg_my_profile_private_txt">private</label>';
                echo '</div>';
            }
			
			echo '</div>';
		}
	}
	
}

$twg_members = new TWGMembers();

if(!function_exists('twg_login_form')) {
	function twg_login_form($filters = '') {
		global $twg_members;
		$twg_members->login_form($filters);
	}
    add_shortcode('twg-login-form', 'twg_login_form');
}

if(!function_exists('twg_register_form')) {
	function twg_register_form() {
		global $twg_members;
		$twg_members->registration_form();
	}
    add_shortcode('twg-register-form', 'twg_register_form');
}

if(!function_exists('twg_login_errors')) {
	function twg_login_errors() {
		global $twg_members;
		echo $twg_members->login_errors();
	}
    add_shortcode('twg-login-errors', 'twg_login_errors');
}

if(!function_exists('twg_logged_in')) {
	function twg_logged_in() {
		global $twg_members;
		return $twg_members->is_logged_in();
	}
}

if(!function_exists('twg_get_username')) {
	function twg_get_username() {
		global $twg_members;
		return $twg_members->get_username();
	}
    add_shortcode('twg-get-username', 'twg_get_username');
}

if(!function_exists('twg_my_id')) {
	function twg_my_id() {
		global $twg_members;
		return $twg_members->get_uid();
	}
    add_shortcode('twg-my-id', 'twg_my_id');
}

if(!function_exists('twg_my_profile')) {
	function twg_my_profile() {
		global $twg_members;
		return $twg_members->my_profile();
	}
    add_shortcode('twg-my-profile', 'twg_my_profile');
}

if(!function_exists('twg_profile_page')) {
	function twg_profile_page() {
		global $twg_members;
		return $twg_members->profile_page();
	}
    add_shortcode('twg-profile-page', 'twg_profile_page');
}

if(!function_exists('twg_member_list')) {
	function twg_member_list($atts = '') {
		global $twg_members;
		$twg_members->member_list($atts);
	}
    add_shortcode('twg-member-list', 'twg_member_list');
}

if(!function_exists('twg_get_memberlist')) {
	function twg_get_memberlist() {
		global $twg_members;
		return $twg_members->get_members_list();
	}
}

if(!function_exists('twg_logout')) {
	function twg_logout() {
		global $twg_members;
		$twg_members->logout(true);
	}
    add_shortcode('twg-logout', 'twg_logout');
}

if(!function_exists('twg_settings_page')) {
	function twg_settings_page() {
		global $twg_members;
		$twg_members->set_settings_page();
	}
	add_action('admin_menu', 'twg_settings_page');
}

?>
