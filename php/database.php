<?php
/* ===========================================
//	
//	database.php
//
//		Functions for interacting with MySQL
// 
// 	Written by: Chris Stringer
//						chris@chrisstringer.us
//
//	=========================================*/

/* Includes */
require_once ("utilfuncs.php");

/* Load database config */
//$CfgDir = "/var/chroot/home/content/16/9298216/cfg";
//$CfgFile = $CfgDir . "/twinoaks.cfg";
$DbName = "twinoaks";
$DbSrv = "127.0.0.1";
$DbUname = "chris";
$DbPswd =  "pocket42";
/*
if (file_exists ($CfgFile) != FALSE)
	{
	$fh = fopen ($CfgFile, 'r');
	$cfg = fgetcsv ($fh, 512);
	if ($cfg != NULL && $cfg != FALSE)
		{
		$DbName =  $cfg[0];
		$DbSrv =   $cfg[1];
		$DbUname = $cfg[2];
		$DbPswd =  $cfg[3];
		}
	fclose ($fh);
	}
*/

/* ===========================================

	Connect to the database
*/
function db_connect (&$err)
	{
	$msq = NULL;

	global $DbName, $DbSrv, $DbUname, $DbPswd;
	if (strlen ($DbName) == 0 ||
		 strlen ($DbSrv) == 0 ||
		 strlen ($DbUname) == 0 ||
		 strlen ($DbPswd) == 0)
		 {
		 return NULL;
		 }

	// connect to SQL database
	$msq = new mysqli ($DbSrv, $DbUname, $DbPswd, $DbName, 3306);
	if ($msq->connect_errno)
		{
		$err = "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
	return $msq;
	}

/* ===========================================
	
	Close the database connection
*/
function db_close ($msq)
	{
	// check database object
	if ($msq == NULL)
		{
		return FALSE;
		}

	// kill connection
	if (($msq->kill ($msq->thread_id)) == FALSE)
		{
		return FALSE;
		}

	// close handle
	if (($msq->close()) == FALSE)
		{
		return FALSE;
		}

	// not sure if this is neccessary
	$msq = NULL;

	// success!
	return TRUE;
	}

/* ===========================================
	
	Find all upcoming non-archived events 

*/
function db_get_upcoming_events ($msq, $num)
	{
	$evtlist = array();

	$sql_str = "";
	$sql_str .= "SELECT * FROM events WHERE archive = '0' AND ";
	$sql_str .= "(stime >= NOW() OR etime >= NOW()) ";
	$sql_str .= "ORDER BY stime LIMIT $num";
	$res = $msq->query($sql_str);
	$i = 0;
	if ($res != FALSE)
		{
		$res->data_seek(0);
		while ($row = $res->fetch_assoc())
			{
			$evtlist[$i] = $row;
			$i++;
			}
		}
	return $evtlist;
	}

/* ===========================================
	
	Find all non-archived events 

		Returns an array of events with HTML
		markup, 1-indexed by day of month
*/
function db_getevents ($msq, $cyear, $cmon)
	{
	$events = array(0 => "");

	// add opening div tags
	for ($d = 1; $d <= 31; $d++)
      {
      $events[$d] = '<div class="calevt">';
      }

   // default to today's date
   if ($cyear == 0 || $cmon == 0)
      {
      $today = getdate();
      $cyear = $today['year'];
      $cmon = $today['mon'];
      }

   // zero-pad single-digit months
   $cm_str = "$cmon";
   if (strlen ($cm_str) == 1)
      {
      $cm_str = "0$cmon";
      }

   // get last day of month
   $ldm_ts = mktime (0,0,0,$cmon+1,0,$cyear,-1);
   $ldm = getdate ($ldm_ts);

   /* Get all events for year/month
      $events[] will hold HTML chunk for each day
      of the month, 1-indexed by day (0 is invalid day)
      This also accounts for multi-day events */
   $stime;
   $etime;
   $sql_str = "";
   $sql_str .= "SELECT * FROM events WHERE archive = '0' AND " .
               "(((MONTH(stime) = '$cmon' AND YEAR(stime) = '$cyear') OR " .
               "(MONTH(etime) = '$cmon' AND YEAR(etime) = '$cyear'))" .
               " OR '$cyear-$cmon-01' BETWEEN stime AND etime) ORDER BY stime";
   $res = $msq->query($sql_str);
   if ($res != FALSE)
      {
      $res->data_seek(0);
      while ($row = $res->fetch_assoc())
         {
			// for all-day events, subtract 1 from end day
			if ($row['allday'] == 1)
				{
				//$egd = getdate (strtotime ($row['etime']));
				//$row['etime'] = uf_fmtdtmsq (mktime (0, 0, 0, $egd['mon'], $egd['mday'] - 1, $egd['year']), TRUE);
				}

         // if start year/month don't match, start day is 1st of mo.
         $stime = getdate (strtotime ($row['stime']));
         $sday = $stime['mday'];
         if ($stime['year'] != $cyear || $stime['mon'] != $cmon)
            {
            $sday = 1;
            }

         // if end year/month don't match, ending day is last of mo.
         $etime = getdate (strtotime ($row['etime']));
         $eday = $etime['mday'];
         if ($etime['year'] != $cyear || $etime['mon'] != $cmon)
            {
            $eday = $ldm['mday'];
            }

         // add event listing for each day between start/end
         for ($day = $sday; $day <= $eday; $day++)
            {
				// build event link
            $events[$day] .= '<a class="cevt" style="color:' . $row['color'] . ';" ' .
                             'href="viewevent?popup=1&amp;id=' . $row['id'] . '">';
            $events[$day] .= $row['title'];
            $events[$day] .= "</a>";

				// print a small date
            $events[$day] .= '<div class="calsmalldate">';

            // check for multi-day events
            if ($sday != $eday)
               {
               // print a date range
               $events[$day] .= $stime['mon'] . "/" . $stime['mday'];
               if ($row['allday'] == 0)
                  {
						// print the time
                  $events[$day] .= " (" . uf_fmttime ($row['stime']) . ")";
                  }
               $events[$day] .= " - ";
               $events[$day] .= $etime['mon'] . "/" . $etime['mday'];
               if ($row['allday'] == 0)
                  {
						// print the time
                  $events[$day] .= " (" . uf_fmttime ($row['etime']) . ")";
                  }
               }
            else if ($row['allday'] == 0)
               {
               // print time range if event is not all day
               $events[$day] .= uf_fmttime ($row['stime']);
					if ($row['stime'] != $row['etime'])
						{
						$events[$day] .= "-";
						$events[$day] .= uf_fmttime ($row['etime']);
						}
               }

            $events[$day] .= '</div>';
            }
         }
      }
	
	// add closing div tags
   for ($d = 1; $d <= 31; $d++)
      {
      $events[$d] .= '</div>';
      }

	return $events;
	}

/* ============================================
	
	Return array of event data by id
*/
function db_geteventdata ($msq, $id)
	{
	$row = array();
	$res = $msq->query("SELECT * FROM events WHERE id = $id");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	if ($row == FALSE)
		{
		return NULL;
		}
	return $row;
	}

/* ============================================
	
	Return array of all child sections for parent_id 
	ordered by menu order
*/
function db_getchild_secnames ($msq, $id)
	{
	$sections = array();

	// run query
	$sql_str = "SELECT section FROM content WHERE parent_id = '$id' ORDER BY menuorder";
   $res = $msq->query($sql_str);
   if ($res == FALSE)
      {
		return NULL;
		}
	$res->data_seek(0);

	// build array
	$i = 0;
	while ($row = $res->fetch_assoc())
		{
		$sections[$i] = $row['section'];
		$i++;
		}

	return $sections;
	}


/* ============================================
	
	Return array of all parent sections ordered
	by menu order
*/
function db_getparent_secnames ($msq)
	{
	$sections = array();

	// run query
	$sql_str = "SELECT section FROM content WHERE parent_id = '0' ORDER BY menuorder";
   $res = $msq->query($sql_str);
   if ($res == FALSE)
      {
		return NULL;
		}
	$res->data_seek(0);

	// build array
	$i = 0;
	while ($row = $res->fetch_assoc())
		{
		$sections[$i] = $row['section'];
		$i++;
		}

	return $sections;
	}

/* ============================================
	
	Return array of section data
*/
function db_getsectiondata ($msq, $id)
	{
	$sdata = array();
	$res = $msq->query("SELECT * FROM content WHERE id = '$id'");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$sdata = $res->fetch_assoc();
	if ($sdata == FALSE)
		{
		return NULL;
		}
	return $sdata;
	}

/* ============================================
	
	Return section name by id
*/
function db_getsectionname ($msq, $id)
	{
	$section = "";
	if (strlen ($id) > 0)
		{
		$res = $msq->query ("SELECT section FROM content WHERE id = '$id' LIMIT 1");
		if ($res == FALSE)
			{
			return "";
			}
		$res->data_seek(0);
		$row = $res->fetch_assoc();
		$section = $row['section'];
		}
	return $section;
	}

/* ============================================
	
	Return section id by name
*/
function db_getsectionid ($msq, $section)
	{
	$id = NULL;
	if (strlen ($section) > 0)
		{
		$res = $msq->query ("SELECT id FROM content WHERE section = '$section' LIMIT 1");
		if ($res == FALSE)
			{
			return NULL;
			}
		$res->data_seek(0);
		$row = $res->fetch_assoc();
		$id = $row['id'];
		}
	return $id;
	}

/* ============================================
	
	Return number of sub-sections
*/
function db_getchild_count ($msq, $pid)
	{
	$res = $msq->query("SELECT id FROM content WHERE parent_id = $pid");
	if ($res == FALSE)
		{
		return NULL;
		}
	return $res->num_rows;
	}

/* ============================================
	
	Return number of sections
*/
function db_getsectioncount ($msq)
	{
	$res = $msq->query("SELECT menuorder FROM content");
	if ($res == FALSE)
		{
		return NULL;
		}
	return $res->num_rows;
	}

/* ============================================
 
	Insert data into specified table
*/
function db_insertdata ($msq, $table, $data)
	{
	// check params
	if (strlen ($table) == 0 || count ($data) == 0)
		{
		return FALSE;
		}

   // build insert query
   $sql_str = "INSERT INTO $table (";
   $sql_str .= implode (", ", array_keys($data));
   $sql_str .= ") VALUES (";
   foreach ($data as $k => $val)
      {
      $sql_str .= "'$val', ";
      }
   $sql_str = rtrim ($sql_str, ", ");
   $sql_str .= ")";

	// run query
   return $msq->query($sql_str);
	}

/* ============================================
 
	Update data in specified table with key = val
*/
function db_updatedata ($msq, $table, $data, $key)
	{
   // check params
   if (strlen ($table) == 0 || count ($data) == 0 || !is_array($key))
      {
      return FALSE;
      }

	// build update
	$sql_str = "";
	$sql_str .= "UPDATE $table SET ";
	$upd_items = array();
	$i = 0;
	foreach	($data as $k => $v)
		{
		$upd_items[$i] = "$k = '$v'";
		$i++;
		}
	$sql_str .= implode (", ", $upd_items);
	$sql_str .= " WHERE " . $key[0] . " = '" . $key[1] . "' LIMIT 1";

	// run query
	return $msq->query($sql_str);
	}

/* ============================================
 
	Delete record from database
*/
function db_deletedata ($msq, $table, $key)
	{
   $sql_str = "";
   $sql_str .= "DELETE FROM $table WHERE " . $key[0] ." = ";
   $sql_str .= "'" . $key[1] . "' LIMIT 1";

   return $msq->query($sql_str);
	}

/* ============================================
 
	Get user data
*/
function db_getusers ($msq, $table)
	{
	$udb = array(array());
	if (strlen ($table) == 0)
		{
		return NULL;
		}
	$res = $msq->query("SELECT * FROM $table ORDER BY uid");
	if ($res != FALSE)
		{
		$res->data_seek(0);
		$i = 0;
		while ($row = $res->fetch_assoc())
			{
			$udb[$i] = $row;
			$i++;
			}
		}
	return $udb;
	}

/* ============================================
 
	Get a user's data
*/
function db_get_udata_uid ($msq, $table, $uid)
	{
	if (strlen ($table) == 0)
		{
		return NULL;
		}
	
	$res = $msq->query("SELECT * FROM $table WHERE uid = $uid LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	return $res->fetch_assoc();
	}

/* ============================================
 
	Get a user password hash by username
*/
function db_get_uname_upw ($msq, $table, $uname)
	{
	$upw = "";
	if (strlen ($table) == 0)
		{
		return "";
		}
	$res = $msq->query("SELECT upw FROM $table WHERE uname = '$uname' LIMIT 1");
	if ($res == FALSE)
		{
		return "";
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['upw'];
	}

/* ============================================
 
	Get a user id by username
*/
function db_get_uname_uid ($msq, $table, $uname)
	{
	$uid = NULL;
	if (strlen ($table) == 0)
		{
		return NULL;
		}
	$res = $msq->query("SELECT uid FROM $table WHERE uname = '$uname' LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['uid'];
	}

/* ============================================
 
	Get user login time by uid
*/
function db_get_ltime_by_uid ($msq, $table, $uid)
	{
	$ltime = 0;
	if (strlen ($table) == 0)
		{
		return 0;
		}
	$res = $msq->query("SELECT ulogintime FROM $table WHERE uid = $uid LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['ulogintime'];
	}

/* ============================================
 
	Get user permissions by uid
*/
function db_get_uperm_uid ($msq, $table, $uid)
	{
	$uperm = 0;
	if (strlen ($table) == 0)
		{
		return 0;
		}
	$res = $msq->query("SELECT uperm FROM $table WHERE uid = $uid LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['uperm'];
	}

/* ============================================
 
	Set user login info
*/
function db_set_user_login ($msq, $table, $uid, $token = NULL, $ltime = NULL)
	{
	$retval = FALSE;
	if (strlen ($table) == 0)
		{
		return FALSE;
		}

	$sql_str = "";
	$sql_str .= "UPDATE $table SET ";
	if ($token != NULL)
		{
		$sql_str .= "ulogintime = '$ltime', utoken = '$token'";
		}
	else
		{
		$sql_str .= "utoken = NULL";
		}
	$sql_str .= " WHERE uid = $uid LIMIT 1";

	$res = $msq->query($sql_str);
	if ($res != FALSE)
		{
		$retval = TRUE;
		}
	return $retval;
	}

/* ============================================
 
	Get user permissions
*/
function db_get_uperm_by_utoken ($msq, $table, $token)
	{
	$uid = NULL;
	if (strlen ($table) == 0)
		{
		return NULL;
		}
	$res = $msq->query("SELECT uperm FROM $table WHERE utoken = '$token' LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['uperm'];
	}

/* ============================================
 
	Check if user is currently logged in
*/
function db_get_uid_by_utoken ($msq, $table, $token)
	{
	$uid = NULL;
	if (strlen ($table) == 0)
		{
		return NULL;
		}
	$res = $msq->query("SELECT uid FROM $table WHERE utoken = '$token' LIMIT 1");
	if ($res == FALSE)
		{
		return NULL;
		}
	$res->data_seek(0);
	$row = $res->fetch_assoc();
	return $row['uid'];
	}

/* END PHP */

?>
