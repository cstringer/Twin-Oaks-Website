<?php 

/*=== Includes ===*/
include_once ("config.php");
include_once (PPHP_DIR . "/database.php");
include_once ("eventcal.php");

/*=== Globals ===*/
$Err = "";
$ContentHtml = "";
$Msq = NULL;
$Section = array();
$PrintPopup = FALSE;
$Menu = array();

/*=== Main ===*/

date_default_timezone_set("America/Chicago");

// set error reporting level
error_reporting (E_ALL);

// connect to MySQL database
$Msq = db_connect ($Err);
if ($Msq == NULL)
	{
	exit ("Error connecting to database: $Err");
	}

// set default section
if (!isset ($_GET['sec']))
	{
	$_GET['sec'] = "Home";
	}

// parse request URI
idx_parse_request_uri ($_SERVER['REQUEST_URI']);

// get section data
$Section = idx_get_section ($Msq, $_GET['sec']);

// handle section-specific needs
switch ($_GET['sec'])
	{
	case "Events":
		{
		$ContentHtml .= $Section['chunk'];
		$ContentHtml .= idx_handle_events ($Msq, $_GET);
		break;
		}

	case "Viewevent":
		{
		$ContentHtml .= idx_viewevent ($Msq, $_GET['id']);
		break;
		}

	default:
		$ContentHtml .= $Section['chunk'];
		break;
	}

$ContentHtml .= "\n\n";

// render page template
require_once ("page-template.php");

// close the db connection
db_close ($Msq);
exit;

/*=== Functions ===*/

function idx_parse_request_uri ($uri)
	{
   // parse request URI
   if (strlen ($uri) > 0) 
      {  
      $g_pos = strpos ($_SERVER['REQUEST_URI'], "?") + 1; 
      $req_vars = explode ("&", substr ($_SERVER['REQUEST_URI'], $g_pos));
      foreach ($req_vars as $rv)
         {  
         if (preg_match ('/^(.*)=(.*)/', $rv, $match))
            {  
            // store key/value pairs in $_GET
            $_GET[$match[1]] = $match[2];
            }  
         }  
		// convert section name
		$_GET['sec'] = str_replace ("_", " ", $_GET['sec']);
		//$_GET['sec'] = ucwords ($_GET['sec']);
      }
	return;
	}

function idx_get_section ($msq, $sec_url)
	{
	$secs = idx_get_url_sections ($sec_url);
	$s = DEF_SECTION;
	if (count ($secs) == 1)
		{
		$s = $secs[0];
		}
	else if (count ($secs) == 2)
		{
		//TODO: check $secs[0] validity!?!
		$s = $secs[1];
		}
	$_GET['sec'] = $s;

	// return array of section data
	$sid = db_getsectionid ($msq, $s);
	if ($sid == NULL)
		{
		$sid = db_getsectionid ($msq, DEF_SECTION);
		}
	return db_getsectiondata ($msq, $sid);
	}

function idx_get_url_sections ($url)
	{
	$secs = array();

	// get rid of trailing slash
	$url = rtrim ($url, '/');
	
	// check for empty string
	if (strlen ($url) > 0)
		{
		// split URL into array by /
		$secs = explode ('/', $url);
		for ($i = 0; $i < count($secs); $i++)
			{
			$secs[$i] = ucwords ($secs[$i]);
			}
		}
	return $secs;
	}

function idx_build_debug_info()
	{
	global $Err;
	$hout = "";
	if (DEBUG_MODE == TRUE)
		{
		ksort ($_GET);
		ksort ($_POST);
		ksort ($_SERVER);
		$hout .= "ERROR: '$Err'\n";
		$hout .= "GET: " . print_r ($_GET, TRUE);
		$hout .= "POST: " . print_r ($_POST, TRUE);
		$hout .= "SERVER: " . print_r ($_SERVER, TRUE);
		}
	return $hout;
	}

function idx_build_menu ($msq, $cur_sec)
	{
	$hout = "";

	$sections = db_getparent_secnames ($msq);

	//$hout .= ' <div id="menu-btn"><img src="/img/icon-menu.png"></div>' . "\n";
	$hout .= ' <ul id="menubar">' . "\n";
	foreach ($sections as $sec)
		{
		// get child sections for sub-menu
		$cid = db_getsectionid ($msq, $sec);
		$child_secs = db_getchild_secnames ($msq, $cid);

		// build "url" - lowercase and replace space with dash
		$sec_url = str_replace (" ", "_", strtolower ($sec));

		if ($child_secs != NULL)
			{
			// start list item
			$hout .= '   <li>';
			$hout .= '<a href="/' . $sec_url . '"';
			if (strcasecmp ($sec, $cur_sec) == 0)
				{
				$hout .= ' class="cursec"';
				}
			$hout .= '>';
			$hout .= $sec;
			$hout .= '<img class="down-arrow" src="/img/smenu-icon.png" alt="v"/>';
			$hout .= '</a>';
			$hout .= '<ul id="sub' . $cid . '" class="submenu">';
			foreach ($child_secs as $c)
				{
				$c_url = str_replace (" ", "_", strtolower ($c));
				$hout .= '<li><a href="/' . $sec_url . "/" . $c_url . '"';
				if (strcasecmp ($c, $cur_sec) == 0)
					{
					$hout .= ' class="cursec"';
					}
				$hout .= '>' . $c . '</a></li>';
				}
			$hout .= '</ul>';
			}
		else
			{
			// start table cell
			$hout .= '   <li>';
			$hout .= '<a href="/' . $sec_url . '"';
			if (strcmp ($sec, $cur_sec) == 0)
				{
				$hout .= ' class="cursec"';
				}
			$hout .= '>' . $sec . '</a>';
			}

		// close list item
		$hout .= "</li>\n"; 
		}
	$hout .= " </ul>\n\n";
	return $hout;
	}

function idx_handle_events ($msq, $get)
	{
	$hout = "";

	// we need year *and* month
	if (!isset ($get['cy']) || preg_match ('/^[\d]{4}$/', $get['cy']) != 1 || 
		 !isset ($get['cm']) || preg_match ('/^[\d]{1,2}$/', $get['cm']) != 1)
		{
		$today = getdate();
		$get['cy'] = $today['year'];
		$get['cm'] = $today['mon'];
		}
	else
		{
		// we're in calendar view mode
		$get['v'] = "cal";
		}

	// print list or calendar view
	if (isset ($get['v']) && strcmp ($get['v'], "cal") == 0)
		{
		// get events for selected year/month
		$events = db_getevents ($msq, $get['cy'], $get['cm']);

		// print calendar
		$hout .= '<script>$(document).ready(function(){ $(".cevt").colorbox(); });</script>' . "\n";
		$hout .= '<p><img alt=">" src="/img/yellow-triangle.png"/> ';
		$hout .= '<a href="/events"><b>View the Events List</b></a></p>';
		$hout .= "<h3>Calendar</h3>\n\n";
		$hout .= ecal_print ($get['cy'], $get['cm'], "viewcal", $events);
		}
	else
		{
		//TODO: set number of events to show
		$num_evts = 5;
		$evtlist = db_get_upcoming_events ($msq, $num_evts);

		// print event list
		$hout .= '<p><img alt=">" src="/img/yellow-triangle.png"/> ';
		$hout .= '<a href="/events?v=cal"><b>View the Calendar</b></a></p>';
		$hout .= "<h3>Upcoming</h3>\n\n";
		$hout .= ecal_print_evtlist ($evtlist);
		}

	return $hout;
	}

function idx_viewevent ($msq, $id)
	{
	$hout = "";

	if (preg_match ('/^[0-9]+$/', $id) != 1)
		{
		return $hout;
		}
	
	// get event data from DB
	$event = db_geteventdata ($msq, $id);

	$hout .= '<p id="viewevent">';
	$hout .= '<b class="evt-title" style="color:' . $event['color'] . '">' . $event['title'] . "</b><br/>\n";
	if ($event['allday'] == 1)
		{
		$event['stime'] = uf_fmtdateshort ($event['stime']);
		$event['etime'] = uf_fmtdateshort ($event['etime']);
		if (strcmp ($event['stime'], $event['etime']) != 0)
			{
			$hout .=  $event['stime'] . " &mdash; ";
			$hout .=  $event['etime'] . "<br/>\n";
			}
		else
			{
			$hout .= $event['stime'] . "<br/>\n";
			}
		}
	else
		{
		$event['stime'] = uf_fmtdtlong ($event['stime']);
		$event['etime'] = uf_fmtdtlong ($event['etime']);
		if ($event['stime'] != $event['etime'])
			{
			$hout .= "Starts: " . uf_fmtdtlong ($event['stime']) . "<br/>\n";
			$hout .= "Ends: " . uf_fmtdtlong ($event['etime']) . "<br/>\n";
			}
		else
			{
			$hout .= uf_fmtdtlong ($event['stime']) . "<br/>\n";
			}
		}
	$hout .= "<br/>\n";
	if (strlen ($event['description']) > 0)
		{
		$hout .= '<span class="evt-desc">' . $event['description'] . "</span><br/>\n";
		}
	$hout .= "<br/>\n";
	$hout .= '<a href="' . $event['link'] . '" target="_blank">' . $event['link'] . "</a><br/>\n";
	$hout .= "</p>\n";

	//$prev_pg = $_SERVER['HTTP_REFERER'];
	//$hout .= '<p><img alt=">" src="/img/yellow-triangle.jpg"> ';
	//$hout .= '<a href="' . $prev_pg . '">Back to Events</a></p>' . "\n";

	return $hout;
	}

?>
