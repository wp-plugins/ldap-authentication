<?php
/*
Plugin Name: LDAP Authentication
Plugin URI: http://www.andrew-bellamy.co.uk/index.php/2009/08/ldap-authentication/
Description:  Authenticates against an LDAP server.
Version: 1.0
Author: Andrew Bellamy
Author URI: http://www.andrew-bellamy.co.uk
*/

/*
Copyright 2009 Andrew Bellamy  (email : bellamy.aj@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


require_once (ABSPATH.WPINC.'/registration.php');


define('LDAP_AUTHENTICATION_VERSION', '1.0');

/*   Overrides wp_authenticate   */
if(!function_exists('wp_authenticate') ) :
	add_action('admin_menu', 'ldap_add_page');
	add_action('login_form', 'ldap_loginFormExtra');


	/*   Setup Admin Menus   */
	function ldap_add_page() {
		add_menu_page('LDAP Authentication', 'LDAP Authentication '.LDAP_AUTHENTICATION_VERSION, 10, __FILE__, 'admin_page');
		add_submenu_page(__FILE__, __('Overview', 'LDAP Authentication'), __('Overview', 'LDAP Authentication'), 10, __FILE__, 'admin_page');
		add_submenu_page(__FILE__, __('Directory Settings', 'LDAP Authentication'), __('Directory Settings', 'LDAP Authentication'), 10, __FILE__ . '&ldap_action=ds', 'admin_page');
		add_submenu_page(__FILE__, __('Logon Setting', 'LDAP Authentication'), __('Logon Setting', 'LDAP Authentication'), 10, __FILE__ . '&ldap_action=logon', 'admin_page');
	}
   

	/*   Direct page actions to functions   */
	function admin_page() {
		if ($_GET['ldap_action'] == 'ds') {
			ldapDS();
		} elseif ($_GET['ldap_action'] == 'logon') {
			ldapLogon();
		} elseif(1) {
			ldapMain();
		}
	}


	/*   Main Page   */
	function ldapMain() {
		if($_POST['action'] == 'update') {
			$enable = intval(htmlspecialchars($_POST['ldapEnable'])) == 1 ? 1 : 0;
			update_option('ldapEnable', $enable);
			echo '<div id="message" class="updated fade" style="background-color: rgb(255, 251, 204);"><p><strong>Settings saved.</strong></p></div>';
		} else {
			$enable = intval(get_option('ldapEnable')) == 1 ? 1 : 0;
		}

		if($enable) { $enableT = "checked"; } else { $enableF = "checked"; }
?>
		<div class="wrap">
			<h2>Activate</h2>
			<form method="post">
				<fieldset>
					<?php wp_nonce_field('update-options'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Enable Directory Authentication</th>
							<td>
								<input type="radio" name="ldapEnable" value="1" <?php echo $enableT; ?> /> Yes &ensp;
								<input type="radio" name="ldapEnable" value="0" <?php echo $enableF; ?> /> No</label>
								<p>You will still be able to login with standard WP users if the LDAP server(s) go offline.</p>
							</td>
						</tr>
					</table>
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="ldapEnable" />
					<p class="submit"><input type="submit" class="button-primary" value="<?php echo _e('Save Changes'); ?>" /></p>
				</fieldset>
			</form>
		</div>
<?php
	}


	/*   Access Directory Page   */
	function ldapDS() {
		if($_POST['action'] == 'update') {
			update_option('ldapServer', htmlspecialchars($_POST['ldapServer']));
			update_option('ldapDomain', strtoupper(htmlspecialchars($_POST['ldapDomain'])));
			if(stripos(strtoupper(htmlspecialchars($_POST['ldapDC'])), ", DC=") > 0) {
				update_option('ldapDC', strtoupper(htmlspecialchars($_POST['ldapDC'])));
			} else {
				update_option('ldapDC', "DC=".strtoupper(htmlspecialchars(str_replace(",", ", DC=",str_replace(" ", "", $_POST['ldapDC'])))));
			}
			update_option('ldapStudentOu', htmlspecialchars($_POST['ldapStudentOu']));

			echo '<div id="message" class="updated fade" style="background-color: rgb(255, 251, 204);"><p><strong>Settings saved.</strong></p></div>';
		}

?>
		<div class="wrap">
			<h2>Directory Settings</h2>
			<form method="post">
				<fieldset>
					<?php wp_nonce_field('update-options'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Servers</th>
							<td>
								<input type="text" name="ldapServer" value="<?php echo get_option('ldapServer'); ?>" size="50" />
								<p>The name or IP address of the directory server(s) (eg: zeus1, t-sm1).  Separate multiple entries by a comma and/or alternate ports with a colon (eg: my.server1.org, my.server2.edu:387)</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Domain</th>
							<td>
								<input type="text" name="ldapDomain" value="<?php echo get_option('ldapDomain'); ?>" size="50" />
								<p>Like: zeus, home, etc</p>
							</td>
						</tr>

						<?php if(stripos(get_option('ldapDC'), "DC=") > 1) { ?>
						<tr valign="top">
							<th scope="row">Domain Controller</th>
							<td>
								<input type="text" name="ldapDC" value="<?php echo get_option('ldapDC'); ?>" size="50" />
								<p>If you need to add more, do it in the same format as above</p>
							</td>
						</tr>
						<?php } else { ?>
						<tr valign="top">
							<th scope="row">Domain Controller</th>
							<td>
								<input type="text" name="ldapDC" value="<?php echo get_option('ldapDC'); ?>" size="50" />
								<p>Comma seperated list</p>
							</td>
						</tr>
						<?php } ?>

						<tr valign="top">
							<th scope="row">Domain</th>
							<td>
								<input type="text" name="ldapStudentOu" value="<?php echo get_option('ldapStudentOu'); ?>" size="50" />
								<p>The OU name for students</p>
							</td>
						</tr>
					</table>
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="ldapServer, ldapDomain, ldapDC, ldapStudentOu" />
					<p class="submit"><input type="submit" class="button-primary" value="<?php echo _e('Save Changes'); ?>"/></p>
				</fieldset>
			</form>
		</div>
<?php
	}


	/*   Logon Message Page   */
	function ldapLogon() {
		if($_POST['action'] == 'update') {
			update_option('ldapLSM', htmlspecialchars($_POST['ldapLSM']));
			echo '<div id="message" class="updated fade" style="background-color: rgb(255, 251, 204);"><p><strong>Settings saved.</strong></p></div>';
		}
?>
		<div class="wrap">
			<h2>Logon Setting</h2>
			<form method="post">
				<fieldset>
					<?php wp_nonce_field('update-options'); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Login Screen Message</th>
							<td>
								<textarea name="ldapLSM" cols="60" rows="3"><?php echo get_option('ldapLSM'); ?></textarea>
								<p>Displayed on the login screen, underneath the username/password fields</p>
							</td>
						</tr>
					</table>
					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="ldapLSM" />
					<p class="submit"><input type="submit" class="button-primary" value="<?php echo _e('Save Changes'); ?>"/></p>
				</fieldset>
			</form>
		</div>
<?php
	}


	/*   Main LDAP Processing Function   */
	function wp_authenticate($username, $password) {
		if ($username == '') return new WP_Error('empty_username', __('<b>Error</b>: Username is empty.'));
		if ($password == '') return new WP_Error('empty_password', __('<b>Error</b>: Password is empty.'));

		//  If LDAP is enabled continue
		if(get_option('ldapEnable') == 1) {
			$username = sanitize_user($username);
			$user = get_userdatabylogin($username);

			if (!$user || ($user->user_login != $username)) {
				//  Connect to server
				$connection = ldap_connect(get_option('ldapServer'));

				//  Set server options
				ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

				//  If theres a bind with credentials continue.
				if(@ldap_bind($connection, get_option('ldapDomain')."\\".$username, $password)) {
					//  Get user details from server
					$result = @ldap_get_entries($connection, @ldap_search($connection, get_option('ldapDC'), 'samAccountName='.$username));

					//  Get OU
					list($name, $equal, $value) = split(",", $result[0]["dn"]);
					list($key, $ou) = split("=", $equal);

					//  If OU equals student OU error
					if($ou === get_option('ldapStudentOu')) {
						return new WP_Error('invalid_username', __('<b>Error</b>: Students Not Allowed.'));
					} else {
						//  Put user details in array and create new WP user and login
						$userData = array(
											'user_pass'	 => $password,
											'user_login'	=> $result[0]['samaccountname'][0],
											'user_nicename' => $result[0]['givenname'][0].' '.$result[0]['sn'][0],
											'user_email'	=> $result[0]['mail'][0],
											'display_name'  => $result[0]['displayname'][0],
											'first_name'	=> $result[0]['givenname'][0],
											'last_name'	 => $result[0]['sn'][0]
										);
						wp_insert_user($userData);
						$user = apply_filters('wp_authenticate_user', $user, $password);
						$user = get_userdatabylogin($username);
						return new WP_User($user->ID);
					}
				} else {  //  No bind
					do_action('wp_login_failed', $username);
					return new WP_Error('invalid_username', __('<b>Error</b>: Invalid username.'));
				}
			} else {  //  No user
				if(is_wp_error($user)){
					do_action('wp_login_failed', $username);
					return $user;
				}

				if(!wp_check_password($password, $user->user_pass, $user->ID)){
					do_action('wp_login_failed', $username);
					return new WP_Error('incorrect_password', __('<b>Error</b>: Invalid password.'));
				}
			}
		}

		//  If LDAP is not enabled use standard WP login
		$user = apply_filters('wp_authenticate_user', get_userdatabylogin($username), $password);
		return new WP_User($user->ID);
	}
	

	/*   Add Message to Logon Page   */
	function ldap_loginFormExtra() {
		if(get_option('ldapEnable')) {
			echo "<p>".stripslashes(__(sprintf(get_option('ldapLSM'))))."</p>";
		}
	}

endif;
?>