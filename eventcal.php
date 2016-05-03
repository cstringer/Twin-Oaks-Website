<?php
/* ===========================================
// 
// eventcal.php:
//
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

require_once ("config.php");
require_once (PPHP_DIR . "/database.php");

$Wdays = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
$Months = array ('January', 'February', 'March', 'April', 'May', 'June',
                 'July', 'August', 'September', 'October', 'November', 'December');

/* ==============================================
 
	Process calendar messages

*/
function ecal_process_msgs ($msq, $message, $get, $post)
	{
	$hout = "";
	switch ($message)
		{
      case "viewcal":
         {
         // we need year *and* month
         if (preg_match ('/^[\d]{4}$/', $get['cy']) != 1 || 
				 preg_match ('/^[\d]{1,2}$/', $get['cm']) != 1)
            {
            $today = getdate();
            $get['cy'] = $today['year'];
            $get['cm'] = $today['mon'];
            }

         // get all events for year/month
         $events = db_getevents ($msq, $get['cy'], $get['cm']);

         // append "add event" links
         $date = "";
         for ($d = 1; $d <= 31; $d++)
            {
            $date = $get['cy'] . "-" . $get['cm'] . "-" . $d;
            $events[$d] .= '<span class="caladdevt">';
            $events[$d] .= '<a href="/newevent?dt=' . $date . '">&bull; Add event</a>';
            $events[$d] .= "</span>";
            }

         // call calendar print function
         $hout .= ecal_print ($get['cy'], $get['cm'], "editcal", $events);
         break;
         }

		default:
			break;
		}

	return $hout;
	}

/* ==============================================
 
	Create an HTML calendar for a given year/month

*/
function ecal_print ($cyear, $cmon, $calstyle, $events)
   {
   $hout = "";       // holds HTML output
   global $Wdays;    // ordered array of weekday names (abbr.)
   global $Months;   // ordered array of month names (0-based)

   // get today's date
   $today = getdate();

   // default to today
   $cal = $today;

   /* Parse input year/month
      unless we have *both* we default to today */
   if ($cyear != 0 && $cmon != 0)
      {
      $cal['year'] = $cyear;
      $cal['mon'] = $cmon;
      }

   /* get first day of month
      NOTE: this is building a string
      in the form YYYY-MM-1 */
   $fdm_str = $cal['year'] . "-" . $cal['mon'] . "-1";
   $fdm_ts = strtotime ($fdm_str);
   $fdm = getdate ($fdm_ts);
   $cal = $fdm;

   // get last day of month
   $ldm_ts = mktime (0,0,0,$cal['mon']+1,0,$cal['year'],-1);
   $ldm = getdate ($ldm_ts);

   // get first day of next month
   $nm_ts = mktime (0,0,0,$cal['mon']+1,1,$cal['year'],-1);
   $nm = getdate ($nm_ts);

   // get first day of previous month
   $pm_ts = mktime (0,0,0,$cal['mon']-1,1,$cal['year'],-1);
   $pm = getdate ($pm_ts);

   // start table and add caption
   $hout .= '<table class="' . $calstyle . '">' . "\n";
   $hout .= "<caption>\n";
   $hout .= '<form method="get" action="?">' . "\n";
	//$hout .= '<input type="hidden" name="vc" value="1">';
   $hout .= '<a href="?cy=' . $pm['year'] .
            '&amp;cm=' . $pm['mon'] . '" '.
				'title="Previous Month" class="calbtn">&#9668;</a> ' . "\n";

   // print month list
   $hout .= '<select id="calmon" name="cm">' . "\n";
   $m_num = 1;
   foreach ($Months as $m)
      {
      $hout .= '<option value="' . $m_num . '"';
      if ($m_num == $cal['mon'])
         {
         $hout .= ' selected';
         }
      $hout .= '>' . $m . '</option>' . "\n";
      $m_num++;
      }
   $hout .= "</select>" . "\n";

   // print year list
   $hout .= '<select id="calyear" name="cy">' . "\n";
   for ($y = CAL_YEAR_START; $y <= CAL_YEAR_END; $y++)
      {
      $hout .= '<option';
      if ($y == $cal['year'])
         {
         $hout .= ' selected';
         }
      $hout .= '>' . $y . '</option>' . "\n";
      }
   $hout .= "</select>" . "\n";
   $hout .= '<input type="submit" value="Go"> ' . "\n";
   $hout .= '<a href="?cy=' . $nm['year'] .
            '&amp;cm=' . $nm['mon'] . '" '.
				'title="Next Month" class="calbtn">&#9658;</a> ' . "\n";
   $hout .= "</form>\n";
   $hout .= "</caption>\n";

   // create days of week header row
   $hout .= "<tr>\n";
   foreach ($Wdays as $wd)
      {
      $hout .= " <th>$wd</th>\n";
      }
   $hout .= "</tr>\n";

   // print calendar body
	$row = 0;
   $day_num = "";       // empty string (outside mo.) or day #
   $cl = "calnd";          // <td> class
   $cal_done = FALSE;   // sentinel for exiting loop
   while ($cal_done == FALSE)
      {
      // start week row
      $hout .= "<tr>\n";
      for ($col = 0; $col < 7; $col++)
         {
         /* in the 1st row, check for 1st day of mo. */
         if ($row == 0 && $col == $fdm['wday'])
            {
            $day_num = 1;
            $cl = "calday";
            }
         // check for today and highlight it
         if ($day_num == $today['mday'] &&
             $cal['mon'] == $today['mon'] &&
             $cal['year'] == $today['year'])
            {
            $cl = "caltoday";
            }
         else if ($day_num == "")
            {
            // outside of month, grey out cell
            $cl = "calnd";
            }
         else
            {
            // regular day of the month
            $cl = "calday";
            }

         // print table cell
         $hout .= ' <td class="' . $cl . '"';
			if ($cl == "calday")
				{
				$hout .= ' id="' . $cal['mon'] . "-" . $day_num . "-" . $cal['year'] . '"';
				}
			$hout .= ">\n";
         // make a little day number in the corner
         $hout .= '  <div class="calmonum">' . $day_num . "</div><br/>\n";
         // output events to cell
         if (is_numeric ($day_num) && strlen ($events[$day_num]) > 0)
            {
            $hout .= "  " . $events[$day_num] . "\n";
            }

         // close cell
         $hout .= " </td>\n";

         // check for last day of month reached
         if ($day_num == $ldm['mday'])
            {
            $day_num = "";       // set to empty string if "outside" the mo.
            $cal_done = TRUE;    // stops AFTER last column in the row
            }
         else if ($day_num != "")
            {
            $day_num++;          // advance the day counter if we're "in" the mo.
            }
         }

      // close week row
      $hout .= "</tr>\n";
      $row++;
      }

   // close table
   $hout .= "</table>\n";

   return $hout;
   }

/* ============================================
// 
// Create an HTML date/time picker
//
// ============================================*/
function ecal_printdtpicker ($datetime, $pre, $all_day = FALSE, $use_jqui = TRUE)
   {
   global $Months;
   $hout = "";
   $dt = getdate (strtotime ($datetime));
   $today = getdate();

	if ($use_jqui == TRUE)
		{
		/* use jQuery UI datepicker widget */
		$dp_id = $pre . '-datepicker';
		$odp_id = (strcmp ($pre, "s") == 0 ? "e" : "s") . '-datepicker';
		//$dp_def = $dt['year'] . '-' . $dt['mon'] . '-' . $dt['mday'];
		$dp_def = date ("M d, Y", strtotime ($datetime));
		$hout .= '<script>';
		$hout .= '$(function() {' .
					 '$("#' . $dp_id . '").datepicker({ ' .
					   'changeMonth: true, changeYear: true, ' .
					  '}); '.
					  '$("#' . $dp_id . '").datepicker( "option", "dateFormat", "M d, yy" ); ' .
					  '$("#' . $dp_id . '").datepicker( "setDate", "' . $dp_def . '" ); ' .
					 '});';
		$hout .= '</script>';
		$hout .= '<input type="text" size="15" id="' . $dp_id . '" name="' . $pre . 'date" ' .
					'value="' . $dp_def . '" class="dtinput" title="Click to select a date" />';
		}
	else
		{
		// build year list
		$hout .= '<select name="' . $pre . 'y">';
		//$syear = $today['year'];
		for ($syear = CAL_YEAR_START; $syear <= CAL_YEAR_END; $syear++)
			{
			$hout .= '<option';
			if ($dt['year'] == $syear)
				{
				$hout .= " selected";
				}
			$hout .= '>' . $syear . '</option>';
			}
		$hout .= "</select>";

		// build month list
		$hout .= '<select name="' . $pre . 'm">';
		$m = 1;
		foreach ($Months as $mo)
			{
			$hout .= '<option';
			if ($dt['mon'] == $m)
				{
				$hout .= " selected";
				}
			$hout .= ' value="' . $m . '">' . $mo . '</option>';
			$m++;
			}
		$hout .= "</select>";

		// build day list
		$hout .= '<select name="' . $pre . 'd">';
		$i = 1;
		while ($i <= 31)
			{
			$hout .= "<option";
			if ($dt['mday'] == $i)
				{
				$hout .= " selected";
				}
			$hout .= ">$i</option>";
			$i++;
			}
		$hout .= "</select>\n";
		$hout .= "&nbsp;";
		}

   // build time list
	$tclass = "dpt-shown";
   if ($all_day == TRUE)
		{
		$tclass = "dpt-hidden";
		}

	$ap = "am";
	if ($dt['hours'] == 0)
		{
		$dt['hours'] = 12;
		}
	else if ($dt['hours'] >= 12)
		{
		$ap = "pm";
		$dt['hours'] > 12 ? $dt['hours'] -= 12 : 0;
		}

	$hout .= '<span id="' . $pre . '-time" class="' . $tclass . '">';
	$hout .= '<select name="' . $pre . 'hr">';
	for ($t = 1; $t <= 12; $t++)
		{
		$hout .= '<option';
		if ($dt['hours'] == $t)
			{
			$hout .= ' selected';
			}
		$hout .= ' value="' . $t . '">';
		$hout .= $t;
		$hout .= "</option>";
		}
	$hout .= "</select>";

	$hout .= "<b>:</b>";

	$hout .= '<select name="' . $pre . 'min">';
	for ($min = 0; $min <= 59; $min++)
		{
		$hout .= '<option';
		if ($dt['minutes'] == $min)
			{
			$hout .= ' selected';
			}
		$hout .= ' value="' . $min . '">';
		if ($min < 10)
			{
			$hout .= "0";
			}
		$hout .= $min;
		$hout .= "</option>";
		}
	$hout .= "</select>";

	$hout .= '<select name="' . $pre . 'ap">';
	$hout .= '<option value="am"';
	$hout .= ($ap == "am" ? " selected" : "") . '>am</option>';
	$hout .= '<option value="pm"';
	$hout .= ($ap == "pm" ? " selected" : "") . '>pm</option>';
	$hout .= "</select>";
	$hout .= "</span>\n";

   return $hout;
   }

function ecal_print_evtlist ($evtlist)
	{
	$hout = "";

	$hout .= '<div id="ecal-list">' . "\n";
	for ($i = 0; $i < count ($evtlist); $i++)
		{
		$hout .= "<p>\n";
		$hout .= '<b class="evt-title"';
		if (strlen ($evtlist[$i]['color']) > 0)
			{
			$hout .= ' style="color:' . $evtlist[$i]['color'] . '"';
			}
		$hout .= '>' . htmlentities ($evtlist[$i]['title']) . '</b><br/>';

		$sts = strtotime ($evtlist[$i]['stime']);
		$sgd = getdate ($sts);
		$ets = strtotime ($evtlist[$i]['etime']);
		$egd = getdate ($ets);
		// subtract one from end day for all-day events
		if ($evtlist[$i]['allday'] == 1)
			{
			//$ets = mktime (0, 0, 0, $egd['mon'], $egd['mday'] - 1, $egd['year']);
			//$egd = getdate ($ets);
			//$evtlist[$i]['etime'] = uf_fmtdtmsq ($ets, TRUE);
			}

		$hout .= '<i class="evt-date">';
		if ($evtlist[$i]['allday'] == 1)
			{
			if ($sgd['year'] == $egd['year'])
				{
				$hout .= uf_fmtmonthday ($evtlist[$i]['stime']);
				}
			else
				{
				$hout .= uf_fmtdateshort ($evtlist[$i]['stime']);
				}
			}
		else
			{
			$hout .= uf_fmtdtshort ($evtlist[$i]['stime']);
			}

		if ($sts != $ets)
			{
			$hout .= " &mdash; ";
			if ($evtlist[$i]['allday'] == 1)
				{
				$hout .= uf_fmtdateshort ($evtlist[$i]['etime']);
				}
			else
				{
				$hout .= uf_fmttime ($evtlist[$i]['etime']);
				}
			}

		$hout .= "</i><br/>\n";

		if (strlen ($evtlist[$i]['description']) > 0)
			{
			$hout .= '<span class="evt-desc">';
			$hout .= htmlspecialchars ($evtlist[$i]['description']);
			$hout .= "</span>\n";
			$hout .= "<br/>\n";
			}
		
		if (strlen ($evtlist[$i]['link']) > 0)
			{
			$hout .= '<span class="evt-link">';
			$hout .= '<a href="' . $evtlist[$i]['link'] . '" target="_blank">';
			$hout .= $evtlist[$i]['link'];
			$hout .= "</a>";
			$hout .= "</span>\n";
			$hout .= "<br/>\n";
			}
		$hout .= "</p>\n";
		}
	$hout .= "</div>\n";

	if (count ($evtlist) == 0)
		{
		$hout .= "<p><i>No upcoming events.</i></p>\n";
		}

	return $hout;
	}

?>
