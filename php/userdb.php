<?php
/* ===========================================
// 
// userdb.php
//
//    User login and administration
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

/* Includes */
require_once ("database.php");
require_once ("utilfuncs.php");
require_once ("pbkdf2.php");

/* Definitions */
define ("UDB_TABLE",			"editusers");
define ("UDB_ADMIN_UID",	1);
define ("UDB_TIMEOUT",		3600);
define ("UDB_MIN_PASSLEN", 8);

define ("UDB_CK_HASH_ALGO", "sha1");
define ("UDB_CK_SECURE", FALSE);
define ("UDB_CK_AUTH_NAME", "c09t329a");
define ("UDB_CK_AUTH_SALT", "8y377dhc56u2cc9a");
define ("UDB_CK_TRANS_NAME", "9df7a88q");
define ("UDB_CK_TRANS_SALT", "32iijkji38vjb55a");

define ("UDB_PERM_NONE", 	 0);
define ("UDB_PERM_CONTENT", 1);
define ("UDB_PERM_EVENTS",  2);
define ("UDB_PERM_USERS",   4);
define ("UDB_PERM_ADMIN",   7);

define ("UDB_IV_USERNAME", 	1);
define ("UDB_IV_PASSMATCH",	2);
define ("UDB_IV_PASSSHORT",	4);
define ("UDB_IV_PASSCHARS",	8);
define ("UDB_IV_PASSCURRENT", 16);

/* ==============================================
 
	Process messages

*/
function udb_process_msgs ($msq, $message, $get, $post)
	{
	$hout = "";

	// get logged-in user info
	$atoken = $_COOKIE[UDB_CK_AUTH_NAME];
	$cuid = db_get_uid_by_utoken ($msq, UDB_TABLE, $atoken);
	$uperm = db_get_uperm_by_utoken ($msq, UDB_TABLE, $atoken);

	switch ($get['e'])
		{
		case "logout":
			{
			db_set_user_login ($msq, UDB_TABLE, $cuid, NULL);
			//TODO: unset cookie? how does "remember me" fit into this?
			setcookie (UDB_CK_AUTH_NAME, '', time() - 42000,
						  "/", $_SERVER['HTTP_HOST'], UDB_CK_SECURE, TRUE);
			header ("Location: /");
			break;
			}

		case "userdb":
			{
			if ($uperm & UDB_PERM_USERS)
				{
				$hout .= udb_print_home();
				$hout .= udb_print_userdb ($msq, $cuid);
				}
			break;
			}

		case "useradd":
		case "useredit":
			{
			if ($uperm & UDB_PERM_USERS)
				{
				$hout .= udb_print_edit_form ($msq, $get['uid'], $cuid);
				}
			break;
			}

		case "usersave":
			{
			if ($uperm & UDB_PERM_USERS)
				{
				if (strlen ($post['save']) == 0)
					{
					// go back to user db 
					header ("Location: /userdb");
					}
				else
					{
					// validate form input
					$ret = udb_validate_user_data ($msq, $post);
					if ($ret != 0)
						{
						$hout .= uf_print_bar ("error", "Error $ret saving user.");
						$hout .= udb_print_edit_form ($msq, $post['uid'], $cuid);
						}
					else
						{
						// save user data
						udb_save_user_data ($msq, $post);
						header ("Location: /userdb");
						}
					}
				}
			break;
			}

		default:
			break;
		}
	return $hout;
	}

/* ==============================================
 
	Parse GET variables from URI

*/
function udb_print_home()
	{
	$hout = "";

	$hout .= "<h3>Site User Editor</h3>\n";

	$hout .= '<div class="edithome">' . "\n";

	$hout .= "<p>";
	$hout .= "<b>Add a User</b><br/>\n";
	$hout .= "Create a new user who can access the site editor.<br/>\n";
	$hout .= "<br/>\n";
	$hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/useradd">Add a User</a>' . "\n";
	$hout .= "</p>";

	$hout .= "</div>\n";
	
	return $hout;
	}

/* ==============================================
 
	Print list of site users

*/
function udb_print_userdb ($msq, $cuid)
	{
	$hout = "";

	$users = db_getusers ($msq, UDB_TABLE);
	if ($users == NULL)
		{
		return $hout;
		}

	$hout .= '<table class="udb-list">' . "\n";
	
	$hout .= "<tr>";
	$hout .= "<th>User Name</th>";
	$hout .= "<th>Last Login</th>";
	$hout .= "<th>Logged In</th>";
	$hout .= "<th>Allowed to Edit</th>";
	$hout .= "<th>Change</th>";
	$hout .= "<th>Remove</th>";
	$hout .= "</tr>\n";

	for ($i = 0; $i < count($users); $i++)
		{
		$hout .= "<tr>";
		// username
		$uname = $users[$i]['uname'];
		if ($users[$i]['uid'] == $cuid)
			{
			$uname = "<b>$uname</b>";
			}
		$hout .= "<td>$uname</td>";
		// last login time
		$ltime = "Inactive";
		if (strlen ($users[$i]['ulogintime']) > 0)
			{
			$ltime = uf_fmtdtshort ($users[$i]['ulogintime']);
			}
		$hout .= "<td>$ltime</td>";
		// currently logged in?
		$active = "no";
		if (strlen ($users[$i]['utoken']) > 0)
			{
			$active = "yes";
			}
		$hout .= "<td>$active</td>";
		// site permissions
		$hout .= "<td>";
		if ($users[$i]['uid'] == UDB_ADMIN_UID)
			{
			$hout .= "All <i>(Site Admin)</i>";
			}
		else
			{
			$hout .= ($users[$i]['uperm'] & UDB_PERM_CONTENT) ? " Content," : "";
			$hout .= ($users[$i]['uperm'] & UDB_PERM_EVENTS) ? " Events," : "";
			$hout .= ($users[$i]['uperm'] & UDB_PERM_USERS) ? " Users," : "";
			$hout = rtrim ($hout, ",");
			}
		$hout .= "</td>";
		// edit user link
		$hout .= '<td>';
		if ((strcmp ($active, "no") == 0 || $users[$i]['uid'] == $cuid) &&
			 ($users[$i]['uid'] != UDB_ADMIN_UID || $cuid == UDB_ADMIN_UID))
			{
			$hout .= '<a href="/useredit?uid=' . $users[$i]['uid'] . '">';
			$hout .= '<img alt="Edit" src="img/icon-edit.gif"/>';
			$hout .= '</a>';
			}
		else
			{
			$hout .= '<img alt="X" src="img/icon-lock.png"/>';
			}
		$hout .= '</td>';
		// delete user link
		$hout .= '<td>';
		if ($users[$i]['uid'] != UDB_ADMIN_UID && strcmp ($active, "no") == 0)
			{
			$hout .= '<a href="/userdelete?uid=' . $users[$i]['uid'] . '">';
			$hout .= '<img alt="X" src="img/icon-delete.gif"/>';
			$hout .= '</a>';
			}
		else
			{
			$hout .= '<img alt="X" src="img/icon-lock.png"/>';
			}
		$hout .= '</td>';
		$hout .= "</tr>\n";
		}

	$hout .= "</table>\n";

	return $hout;
	}

/* ==============================================
 
	Print user edit form

*/
function udb_print_edit_form ($msq, $uid = 0, $cuid)
	{
	$hout = "";

	$udata = array();
	if ($uid != 0)
		{
		$udata = db_get_udata_uid ($msq, UDB_TABLE, $uid);
		if ($udata == NULL)
			{
			//TODO: log error
			return $hout;
			}
		}
	else
		{
		$uid = "new";
		$udata['uname'] = $_POST['uname'];
		}

	$hout .= "<h3>User Editor</h3>\n";

	$hout .= '<form method="POST" action="/usersave">';
	$hout .= '<input type="hidden" name="uid" value="' . $uid . '"/>' . "\n";
	$hout .= '<table class="udb-edit">' . "\n";

	$hout .= "<tr><th>User Name:</th>";
	$hout .= "<td>";
	$hout .= '<input type="text" name="uname" size="30" ' .
				'value="' . $udata['uname'] . '" ' .
				'/>';
	$hout .= "</td></tr>\n";

	$hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

	if (strcmp ($uid, "new") != 0 || $cuid != UDB_ADMIN_UID || $uid == UDB_ADMIN_UID)
		{
		$hout .= "<tr><th>Current Password:</th>";
		$hout .= "<td>";
		$hout .= '<input type="password" name="curpw" size="30"/>';
		$hout .= "</td></tr>\n";
		}

	$hout .= "<tr><th>New Password:</th>";
	$hout .= "<td>";
	$hout .= '<input type="password" name="upw[]" size="30"/>';
	$hout .= "</td></tr>\n";
	$hout .= "<tr><th>Re-type<br/>Password:</th>";
	$hout .= "<td>";
	$hout .= '<input type="password" name="upw[]" size="30"/>';
	$hout .= "</td></tr>\n";

	$hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

	if ($uid != UDB_ADMIN_UID)
		{
		$hout .= "<tr>";
		$hout .= '<th>Allowed to Edit:</th>';
		$hout .= '<td>';
		$hout .= '<input id="upc" type="checkbox" name="uperm[]" value="1"/';
		$hout .= ($udata['uperm'] & UDB_PERM_CONTENT) ? " checked" : "";
		$hout .= '> ';
		$hout .= '<label for="upc">Content</label><br/>' . "\n";
		$hout .= '<input id="upe" type="checkbox" name="uperm[]" value="2"/';
		$hout .= ($udata['uperm'] & UDB_PERM_EVENTS) ? " checked" : "";
		$hout .= '> ';
		$hout .= '<label for="upe">Events</label><br/>' . "\n";
		$hout .= '<input id="upu" type="checkbox" name="uperm[]" value="4"/';
		$hout .= ($udata['uperm'] & UDB_PERM_USERS) ? " checked" : "";
		$hout .= '> ';
		$hout .= '<label for="upu">Users</label><br/>' . "\n";
		$hout .= '</td>';
		$hout .= "</tr>\n";
		}

	$hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

	$hout .= '<tr><td colspan="2"><span class="ehf-buttons">';
	$hout .= '<input type="submit" class="saveBtn" name="save" value="Save User"/> ';
	$hout .= '<input type="submit" class="cancelBtn" name="cancel" value="Cancel"/> ';
	$hout .= '</span></td></tr>' . "\n";

	$hout .= "</table>\n";
	$hout .= "</form>\n\n";

	return $hout;
	}

/* ===========================================

	Validate user input
*/
function udb_validate_user_data ($msq, $post)
	{
	$retval = 0;
	
	/* Username must be 4 to 32 alpha-numeric characters */
	if (udb_validate_username ($post['uname']) == FALSE)
		{
		// usrname not valid
		$retval += UDB_IV_USERNAME;
		}

	if (strcmp ($post['upw'][0], $post['upw'][1]) != 0)
		{
		// passwords don't match
		$retval += UDB_IV_PASSMATCH;
		}

	if (strlen ($post['upw'][0]) < 8)
		{
		// password too short
		$retval += UDB_IV_PASSSHORT;
		}

	/* Password must contain at least one digit, one capital and one special char */
	if (preg_match ('/[\d]{1,}/', $post['upw'][0]) != 1 ||
		 preg_match ('/[A-Z]{1,}/', $post['upw'][0]) != 1 ||
		 preg_match ('/[\+\?\!\@\#\$\%\^\=\*]{1,}/', $post['upw'][0]) != 1)
		{
		// password not strong enough
		$retval += UDB_IV_PASSCHARS;
		}

	/* Check current password */
	if (strcmp ($post['uid'], "new") != 0)
		{
		// check current password
		$udata = db_get_udata_uid ($msq, UDB_TABLE, $post['uid']);
		if (validate_password ($post['curpw'], $udata['upw']) == FALSE)
			{
			// not the right password
			$retval += UDB_IV_PASSCURRENT;
			}
		}

	return $retval;
	}

/* ===========================================

	Validate username
*/
function udb_validate_username ($uname)
	{
	$retval = FALSE;
   /* Username must be 4 to 32 alpha-numeric characters */
   if (preg_match ('/^[0-9a-zA-Z]{4,32}$/', $uname) == 1)
      {
      // usrname valid
      $retval = TRUE;
      }

	return $retval;
	}

/* ===========================================

	Save validated user data
*/
function udb_save_user_data ($msq, $post)
	{
	$retval = FALSE;

	// get user id
	$uid = $post['uid'];

	// init user data
	$udata = array();

	// get user name
	$udata['uname'] = $post['uname'];

	// accumulate permissions
	$udata['uperm'] = 0;
	for ($i = 0; $i < count($post['uperm']); $i++)
		{
		$udata['uperm'] += $post['uperm'][$i];
		}
	if ($uid == UDB_ADMIN_UID)
		{
		$udata['uperm'] = UDB_PERM_ADMIN;
		}

	// hash password
	$udata['upw'] = create_hash ($post['upw'][0]);

	if (strcmp ($uid, "new") == 0)
		{
		// insert new user data
		$res = db_insertdata ($msq, UDB_TABLE, $udata);
		}
	else
		{
		// update existing user 
		$res = db_updatedata ($msq, UDB_TABLE, $udata, array('uid', $uid));
		}

	return $retval;
	}

/* ===========================================

	Print user login form
*/
function udb_print_login()
	{
	$hout = "";

	$hout .= "<html>\n\n";
	$hout .= ' <form id="login-form" method="POST" action="/">' . "\n";

	$hout .= "  <table>\n";
	$hout .= "   <tr>";
	$hout .= '<th><label for="login-un">Username:</label></th>';
	$hout .= '<td><input type="text" id="login-un" name="un" size="24"/></td>';
	$hout .= "</tr>\n";
	$hout .= "   <tr>";
	$hout .= '<th><label for="login-pw">Password: </label></th>';
	$hout .= '<td><input type="password" id="login-pw" name="pw" size="24"/></td>';
	$hout .= '<td rowspan="2"><input type="submit" id="login-sub" name="login" value="Login"/></td>';
	$hout .= "</tr>\n";
	/*
	$hout .= "   <tr>";
	$hout .= '<td colspan="2">&nbsp;</td>';
	$hout .= "</tr>\n";
	$hout .= "   <tr>";
	$hout .= '<td></td>';
	$hout .= '<td><input type="checkbox" id="login-rem" name="rem"/>';
	$hout .= '<label for="login-rem"> Remember me</login></td>';
	$hout .= "</tr>\n";
	*/
	$hout .= "  </table>\n";

	$hout .= " </form>\n\n";

	return $hout;
	}

/* ===========================================

	Validate a user's login credentials
*/
function udb_check_login ($msq, $post)
	{
	$retval = FALSE;
	$uid = NULL;
	//global $Err;

	// Only authenticate requests from our own server
	if (strlen ($_SERVER['HTTP_REFERER']) > 0 && 
		 strstr ($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) == FALSE)
		{
		return FALSE;
		}

	// check for auth token
	if (isset ($_COOKIE[UDB_CK_AUTH_NAME]) == TRUE)
		{
		// the auth token is set, find it in the DB
		$cval = $_COOKIE[UDB_CK_AUTH_NAME];
		$uid = db_get_uid_by_utoken ($msq, UDB_TABLE, $cval);
		if ($uid != NULL)
			{
			// validate auth token
			$ltime = db_get_ltime_by_uid ($msq, UDB_TABLE, $uid);
			$atoken = udb_create_auth_token ($uid, $ltime);
			//$Err = "ltime = $ltime\n uid = $uid\n atoken = $atoken\n cval = $cval";
			if (strcmp ($atoken, $cval) == 0)
				{
				// auth token is valid
				$retval = TRUE;
				}
			}
		}

	/* There's no valid auth token, check username/password */
	if ($retval == FALSE)
		{
		$upw = "";
		if (udb_validate_username ($post['un']) == TRUE)
			{
			$uid = db_get_uname_uid ($msq, UDB_TABLE, $post['un']);
			$upw = db_get_uname_upw ($msq, UDB_TABLE, $post['un']);
			}

		$logged_in = FALSE;
		/*TODO: deal with logged-in users?
		if ($uid != NULL)
			{
			//$logged_in = db_get_user_login_status ($msq, UDB_TABLE, $uid);
			}
		*/

		if (strlen ($upw) > 0 && $uid != NULL && $logged_in == FALSE && 
			 validate_password ($post['pw'], $upw) == TRUE)
			{
			$ltime = uf_fmtdtmsq (time(), TRUE);
			$atoken = udb_create_auth_token ($uid, $ltime);
			db_set_user_login ($msq, UDB_TABLE, $uid, $atoken, $ltime);
			setcookie (UDB_CK_AUTH_NAME, $atoken, time() + UDB_TIMEOUT,
						  "/", $_SERVER['HTTP_HOST'], UDB_CK_SECURE, TRUE);
			$_COOKIE[UDB_CK_AUTH_NAME] = $atoken;
			$retval = TRUE;
			}
		}
	return $retval;
	}

function udb_create_auth_token ($uid, $ltime)
	{
	$hstr = $uid . $_SERVER['HTTP_USER_AGENT'] . UDB_CK_AUTH_SALT . $ltime;
	return hash (UDB_CK_HASH_ALGO, $hstr);
	}

/* END PHP */

?>
