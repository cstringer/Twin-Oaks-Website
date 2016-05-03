<?php 

/* Includes */
require_once ("../config.php");
require_once (PPHP_DIR . "/utilfuncs.php");
require_once (PPHP_DIR . "/database.php");
require_once (PPHP_DIR . "/contentedit.php");
require_once (PPHP_DIR . "/eventedit.php");
require_once (PPHP_DIR . "/evtimpexp.php");
require_once (PPHP_DIR . "/userdb.php");

/* Global variables */
$MySqli = NULL;
$Err = "";
$PrintNav  = TRUE;
$PrintHf   = TRUE;
$PrintHout = TRUE;
$DbgMode   = FALSE;
$Hout = "";

// set error report level
error_reporting (E_ERROR);

// set the default timezone 
date_default_timezone_set(DEF_TIMEZONE);

// connect to SQL database
$MySqli = db_connect ($Err);
if ($MySqli != NULL)
	{
	if (udb_check_login ($MySqli, $_POST) == FALSE)
		{
		$Hout = udb_print_login();
		}
	else
		{
		// parse URI
		idx_parse_request_uri ($_SERVER['REQUEST_URI']);

		// get logged-in user permissions
		$atoken = $_COOKIE[UDB_CK_AUTH_NAME];
		$uid = db_get_uid_by_utoken ($MySqli, UDB_TABLE, $atoken);
		$uperm = db_get_uperm_by_utoken ($MySqli, UDB_TABLE, $atoken);

		// catch calendar output
		if (strlen ($_GET['cy']) > 0 && strlen ($_GET['cm']) > 0)
			{
			$_GET['e'] = "viewcal";
			}

		// print page as popup?
		if (strcmp ($_GET['pu'], "1") == 0)
			{
			$PrintHf = FALSE;
			$PrintNav = FALSE;
			}

		// print navigation elements?
		if (strcmp ($_GET['nav'], "0") == 0)
			{
			$PrintNav = FALSE;
			}

		if ($PrintNav == TRUE)
			{
			// add navigation menu
			$Hout .= idx_print_menu ($uperm);
			}

		// handle message processing
		$Hout .= idx_process_msgs ($MySqli, $_GET, $_POST, $uperm);
		}

	// close SQL
	db_close ($MySqli);
	}

// turn OFF cache, avoids stale data records
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($PrintHf == TRUE)
	{
	readfile ("_es-header.html");
	}

// output HTML
if ($PrintHout == TRUE)
	{
	echo $Hout;
	}

if ($PrintHf == TRUE)
	{
	echo idx_print_footinfo();
	echo idx_print_debug_info();
	readfile ("_es-footer.html"); 
	}

return;

/* ==============================================
 
	Parse GET variables from URI

*/
function idx_parse_request_uri ($uri)
	{
	// parse request URI
	if (strlen ($uri) > 0)
		{
		$g_pos = strpos ($uri, '?') + 1;
		$req_vars = explode ('&', substr ($uri, $g_pos));
		foreach ($req_vars as $rv)
			{
			if (preg_match ('/^(.*)=(.*)/', $rv, $match))
				{
				$_GET[$match[1]] = $match[2];
				}
			}
		}
	return;
	}

/* ==============================================
 
	Process script messages

*/
function idx_process_msgs ($msq, $get, $post, $uperm)
	{
	$hout = "";
	$message = $get['e'];

	// handle content/section editing
	if ($uperm & UDB_PERM_CONTENT)
		{
		$hout .= ce_process_msgs ($msq, $message, $get, $post);
		}

	if ($uperm & UDB_PERM_EVENTS)
		{
		// handle event editing
		$hout .= evt_process_msgs ($msq, $message, $get, $post);

		// handle event import/export
		$hout .= eix_process_msgs ($msq, $message, $get, $post);

		// handle calendar
		if (strcmp ($message, "viewcal") == 0)
			{
			$hout .= evt_print_home();
			}
		$hout .= ecal_process_msgs ($msq, $message, $get, $post);
		}

	// handle user database 
	$hout .= udb_process_msgs ($msq, $message, $get, $post);

	// default to home
	if (strlen ($hout) == 0)
		{
		$hout = idx_print_edithome ($msq, $uperm);
		}

	return $hout;
	}

/* ==============================================
 
	Print menu

*/
function idx_print_menu ($uperm)
	{
	$hout = "";
	$hout .= '<table id="menubar">' . "\n";
  	$hout .= "<tr>\n";
	$hout .= '<td><a href="/">Editor Home</a></td>';
	if ($uperm & UDB_PERM_CONTENT)
		{
		$hout .= '<td><a href="/editcontent">Edit Content</a></td>';
		}
	if ($uperm & UDB_PERM_EVENTS)
		{
		$hout .= '<td><a href="/viewevents">Edit Events</a></td>';
		}
	if ($uperm & UDB_PERM_USERS)
		{
		$hout .= '<td><a href="/userdb">Edit Users</a></td>';
		}
	$hout .= '<td class="mi-logout"><a href="/logout">Logout</a></td>';
	$hout .= "</tr>\n";
	$hout .= "</table>\n";

	return $hout;
	}
/* ==============================================
 
	Print editor home page

*/
function idx_print_edithome ($msq, $uperm)
	{
	$hout = "";
	$hout .= "<h3>Site Editor Home</h3>\n";

	$hout .= '<div class="edithome">';

	if ($uperm & UDB_PERM_CONTENT)
		{
		$hout .= '<p>';
		$hout .= "<b>Edit Content</b><br/>\n";
		$hout .= "Manage site sections, content, and navigation menu order, ";
		$hout .= "and edit the header, footer, and stylesheet files.<br/>\n";
		$hout .= "<br/>\n";
		$hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"> ';
		$hout .= '<a href="/editcontent">Go to Content Editor</a><br/>' . "\n";
		$hout .= "</p>\n";
		}

	if ($uperm & UDB_PERM_EVENTS)
		{
		$hout .= '<p>';
		$hout .= "<b>Edit Events</b><br/>\n";
		$hout .= "Add, edit, archive or delete listings from the event database.<br/>\n";
		$hout .= "<br/>\n";
		$hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"> ';
		$hout .= '<a href="/viewevents">Go to Event Editor</a><br/>' . "\n";
		$hout .= "</p>\n";
		}

	if ($uperm & UDB_PERM_USERS)
		{
		$hout .= '<p>';
		$hout .= "<b>Edit Users</b><br/>\n";
		$hout .= "Control access to the site editor.<br/>";
		$hout .= "<br/>\n";
		$hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"> ';
		$hout .= '<a href="/userdb">Go to Site User Editor</a><br/>' . "\n";
		$hout .= "</p>\n";
		}

	$hout .= "</div>\n";
	return $hout;
	}

/* ==============================================
 
	Print footer info area

*/
function idx_print_footinfo()
	{
	$hout = "";
	$hout .= ' <div id="footer">' . "\n";
	$hout .= "  <pre>\n";
	$hout .= "   <b>Date/Time:</b> " . date (DATE_COOKIE, $_SERVER['REQUEST_TIME']) . "\n";
	$hout .= "   <b>Remote IP:</b> " . $_SERVER['REMOTE_ADDR'] . "\n";
	$hout .= "  </pre>\n";
	$hout .= " </div>\n";
	return $hout;
	}

/* ==============================================
 
	Print debugging information

*/
function idx_print_debug_info ($print_dbg = FALSE)
	{
	$hout = "";
	global $DbgMode, $Err;
	if ($DbgMode == TRUE || $print_dbg == TRUE)
		{
		$hout .= ' <div id="dbg-info">' . "\n";
		$hout .= "  <pre>";
		$hout .= "   <b>Last Error:</b> '$Err'\n";
		$hout .= "   <b>SERVER:</b> " . print_r ($_SERVER, TRUE) . "\n";
		$hout .= "  </pre>\n";
		$hout .= " </div>\n";
		}
	return $hout;
	}


/* END PHP */

?>
