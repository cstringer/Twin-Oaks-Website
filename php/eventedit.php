<?php
/* ===========================================
// 
// eventedit.php
//
//    Event database editor
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

define ("DOC_ROOT", "/var/chroot/home/content/16/9298216/html/twinoaks");

require_once ("database.php");
require_once ("utilfuncs.php");
require_once (DOC_ROOT . "/eventcal.php");

/* ===========================================

	Handle event editor messages
*/
function evt_process_msgs ($msq, $message, $get, $post)
	{
	$hout = "";
   switch ($message)
      {
      case "viewevents":
         {
         // process checkmarked items
         if (strlen ($post['evtdelsel']) > 0)
            {
            $hout .= evt_maint ($msq, "del", $post['evtchk']);
            }
         else if (strlen ($post['evtarch']) > 0)
            {
            $hout .= evt_maint ($msq, "arch", $post['evtchk']);
            }
         else if (strlen ($post['evtunarch']) > 0)
            {
            $hout .= evt_maint ($msq, "unarch", $post['evtchk']);
            }

         // reset the search field
         if (strlen ($post['srchreset']) > 0)
            {
            $post['srch'] = "";
            }

			// set archive mode
         if (strlen ($post['viewarch']) > 0)
            {
            $post['archives'] = 1;
            }
         else if (strlen ($post['exitarch']) > 0)
            {
            $post['archives'] = 0;
            }

         $hout .= evt_print_home();
         $hout .= evt_printlist ($msq, $get['s'], $post['srch'], $post['archives']);
         break;
         }

      case "editevent":
      case "viewevent":
         $hout .= evt_modify ($msq, "edit", $get['id'], 0);
         break;

      case "copyevent":
         $hout .= evt_modify ($msq, "copy", $get['id'], $get['dt']);
         break;

      case "newevent":
         $hout .= evt_modify ($msq, "new", 0, $get['dt']);
         break;

      case "saveevent":
			{
         if (strlen ($post['cancel']) == 0)
            {
				if (strlen ($post['delete']) > 0)
					{
					$hout .= evt_delete ($msq, $post['id']);
					}
				else if (strlen ($post['save']) > 0)
					{
					$hout .= evt_savedata ($msq, $post);
					}
				}

			if (strlen ($post['prevpg']) == 0)
				{
				$post['prevpg'] = "/viewevents";
				}
			header ('Location: ' . $post['prevpg']);

         break;
			}

      case "delevent":
         $hout .= evt_delete ($msq, $get['id']);
			if (strlen ($post['prevpg']) == 0)
				{
				$post['prevpg'] = "/viewevents";
				}
			header ('Location: ' . $post['prevpg']);
         break;

		default:
			break;
		}
	return $hout;
	}

/* ==============================================
 
	Event maintainence

*/
function evt_maint ($msq, $action, $idlist)
   {
   $hout = "";
   $sql_str = "";
   $evt_count = 0;
   $a_txt = "";

   if (!is_array ($idlist))
      {
      $hout .= uf_print_bar ("error", "No events selected.");
      return $hout;
      }

   foreach ($idlist as $id)
      {
      if ($action == "del")
         {
         $sql_str = "DELETE FROM events WHERE id = $id LIMIT 1";
         $a_txt = "Deleted";
         }
      else if ($action == "arch")
         {
         $sql_str = "UPDATE events SET archive = '1' WHERE id = $id LIMIT 1";
         $a_txt = "Archived";
         }
      else if ($action == "unarch")
         {
         $sql_str = "UPDATE events SET archive = '0' WHERE id = $id LIMIT 1";
         $a_txt = "Unarchived";
         }

      // run sql query
      $res = $msq->query($sql_str);
      if ($res == FALSE)
         {
         $hout .= uf_print_bar("error", "ERROR processing event: " . $msq->error);
         }
      else
         {
         $evt_count++;
         }
      }

   // print status message
   $hout .= uf_print_bar ("info", "<b>$a_txt $evt_count events.</b>");

   return $hout;
   }

/* ==============================================
 
	Print event navigation 

*/
function evt_print_home()
	{
   $hout = "";

   $hout .= "<h3>Event Editor</h3>\n";
   $hout .= '<div class="edithome">';

   $hout .= "<p><b>Event List View</b><br/>\n";
   $hout .= "View the complete list of events.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/viewevents">View the Event List</a>' . "\n";
   $hout .= "</p>\n";

   $hout .= "<p><b>Calendar View</b><br/>\n";
   $hout .= "See all of the events on a calendar by month.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/viewcal">View the Calendar</a>' . "\n";
   $hout .= "</p>\n";

   $hout .= "<p><b>Add a New Event</b><br/>\n";
   $hout .= "Create a new event to add to the database.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/newevent">Add a New Event</a>' . "\n";
   $hout .= "</p>\n";

   $hout .= "<p><b>Import/Export Events</b><br/>\n";
   $hout .= "Add or save events with other calendar software.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/impexpevents">Import/Export Events</a>' . "\n";
   $hout .= "</p>\n";

   $hout .= "</div>\n";
   $hout .= "<br/>\n";

   return $hout;
	}

/* ==============================================
 
	Print events list

*/
function evt_printlist ($msq, $sort, $srch, $archives = 0)
   {
   $hout = "";

   // get sort order, default to id
   if (strlen ($sort) == 0)
      {
      $sort = "id";
      }

   $sql_str = "SELECT * FROM events WHERE ";
   $sql_str .= "archive = '$archives' ";
   if (strlen ($srch) > 0)
      {
      $sql_str .= "AND (title LIKE '%$srch%' OR " .
                  "description LIKE '%$srch%') ";
      }
   $sql_str .= "ORDER BY $sort";

   $res = $msq->query($sql_str);
   if ($res == FALSE)
      {
      $hout .= uf_print_bar ("error", "ERROR reading event database: " . $msq->error);
      return $hout;
      }
   $res->data_seek(0);

	// print help section
	$hout .= evt_printhelp();

   // begin event list
   $hout .= '<form method="post" action="/viewevents">';
   $hout .= '<input type="hidden" name="archives" value="' . $archives . '">';
   $hout .= '<table class="eventlist">' . "\n";
   $cap_str = "Event " . ($archives == 1 ? "Archives" : "Database");
   $hout .= "<caption>$cap_str</caption>\n";

   $hout .= '<tr><td colspan="10">';
   $hout .= '<div id="evtmaint">';
   $hout .= 'Checked Events: ';
   $hout .= '<input type="submit" name="evtdelsel" value="Delete" onclick="return confDelSel();">';
   if ($archives == 1)
      {
      $hout .= '<input type="submit" name="evtunarch" value="Unarchive">';
      //$hout .= '<input type="submit" name="exportarch" value="Export to iCal">';
      $hout .= "<br/>\n";
      $hout .= 'Event Archive: <input type="submit" name="exitarch" value="Exit Archives">';
      }
   else
      {
      $hout .= '<input type="submit" name="evtarch" value="Archive">';
      $hout .= "<br/>\n";
      $hout .= 'Event Archive: <input type="submit" name="viewarch" value="View Archives">';
      }
   $hout .= '</div>';
   $hout .= '<div id="evtsearch">';
   $hout .= 'Search: <input type="search" name="srch" size="30" value="' . $srch . '">';
   $hout .= '<input type="submit" name="srchsubmit" value="Go">';
   $hout .= '<input type="submit" name="srchreset" value="Reset">';
   $hout .= '</div>';
   $hout .= "</td></tr>\n";

   $hout .= '<tr>';
   $hout .= '<th><input type="checkbox" id="chkall" onclick="checkAll(this.checked);"></th>';
   $hout .= '<th><a href="/viewevents?s=color">Color</a></th>';
   $hout .= '<th><a href="/viewevents?s=title">Event</a></th>';
   $hout .= '<th><a href="/viewevents?s=stime">Start</a></th>';
   $hout .= '<th>All-Day</th>';
   $hout .= '<th><a href="/viewevents?s=etime">End</a></th>';
   $hout .= '<th>Link</th>';
   $hout .= '<th><a href="/viewevents?s=description">Description</a></th>';
   $hout .= '<th>Copy</th><th>Edit</th>';
   $hout .= "</tr>\n";

   $desc = "";
   $row_num = 1;
   while ($row = $res->fetch_assoc())
      {
      // start table row
      $hout .= '<tr class="nrow" onclick="toggleSel(this.id);" id="row' . $row['id'] . '">';

      // row checkbox for multiple select
      $hout .= '<td><input onclick="toggleChk(this.id,this.checked);" id="chk'. $row['id'] . '" ' .
               'type="checkbox" class="ec" name="evtchk[]" value="' . $row['id'] . '"></td>';

      // event color ('color'.png for image)
      $hout .= '<td>';
      if (strlen ($row['color']) > 0)
         {
         $hout .= '<img class="ecimg" alt="' . $row['color'] . ' " src="img/' . $row['color'] . '.png">';
         }
      $hout .= '</td>';

      // event title
      $hout .= "<td>" . htmlspecialchars ($row['title']) . "</td>";

      if ($row['allday'] == 1)
         {
			// subtract a day from end time
			//$ets = strtotime ($row['etime']);
			//$egd = getdate ($ets);
			//$row['etime'] = mktime (0, 0, 0, $egd['mon'], $egd['mday'] - 1, $egd['year']);

         // show short date, no time for all day events
         $hout .= "<td>" . uf_fmtdateshort ($row['stime'], FALSE) . "</td>";
         $hout .= '<td><img alt="All-Day" src="img/icon-check.png"></td>';
         $hout .= "<td>" . uf_fmtdateshort ($row['etime'], FALSE) . "</td>";
         //$hout .= "<td>" . $row['etime'] . "</td>";
         }
      else
         {
         // show long date/time
         $hout .= "<td>" . uf_fmtdtlong ($row['stime']) . "</td>";
         $hout .= "<td>&nbsp;</td>";
         $hout .= "<td>" . uf_fmtdtlong ($row['etime']) . "</td>";
         }

      $hout .= "<td>";
      if (strlen ($row['link']) > 0)
         {
         $hout .= '<a href="' . $row['link'] . '" target="_blank">';
         $hout .= '<img alt="Link" src="img/icon-link.png">';
         $hout .= '</a>';
         }
      $hout .= "</td>";

      $desc = htmlspecialchars ($row['description']);
      if (strlen ($desc) > MAX_EVENT_DESC)
         {
         // just show a snippet of description
         $desc = substr ($desc, 0, MAX_EVENT_DESC);
         $desc .= "[...]";
         }
      $hout .= "<td>$desc</td>";

      $hout .= '<td><a href="/copyevent?id=' . $row['id'] . '">' .
               '<img src="img/icon-copy.png" alt="Copy"/></a></td>';
      $hout .= '<td><a href="/editevent?id=' . $row['id'] . '">' .
               '<img src="img/icon-edit.gif" alt="Edit"/></a></td>';
      /* hide delete icon
      $hout .= '<td><a onclick="return confDelete();" href="/delevent&amp;id=' . $row['id'] . '">' .
               '<img src="img/icon-delete.gif" alt="Delete"/></a></td>';
      */
      $hout .= "</tr>\n";
      $row_num++;
      }
   $hout .= "</table>\n";
   $hout .= '</form>';
   $hout .= "<br/>\n";

   return $hout;
   }

function evt_printhelp()
	{
   // print help info
	$hout = "";
   $hout .= '<div id="evthelp" class="evthelp">';
	$hout .= '<img alt="?" src="img/icon-help.png" class="helpicon" title="Toggle help" ' .
				'onclick="toggleHelp(\'evthelptxt\')"/>';
   $hout .= '<b onclick="toggleHelp(\'evthelptxt\')">Event Editor Help</b><br/>' . "\n";
	$hout .= '<p>';
	$hout .= '<span id="evthelptxt" class="eh-hidden">';
   $hout .= "In list view, events may be edited, copied, deleted, or archived.<br/>\n";
	$hout .= "<br/>\n";
   $hout .= "&bull; Click ";
	$hout .= '<img alt="Edit" src="img/icon-edit.gif" class="icon-sm"/> ';
	$hout .= "to edit, or ";
	$hout .= '<img alt="Copy" src="img/icon-copy.png" class="icon-sm"/> ';
	$hout .= "to copy an event.<br/>\n";
	$hout .= "<br/>\n";
   $hout .= "&bull; To delete or archive events, select the checkbox next to one or more ";
	$hout .= "events, or the checkbox in the header to select all, then click ";
	$hout .= "the 'Delete' or 'Archive' button. <i>(Archived items aren't displayed on the site, ";
	$hout .= "but remain in the database.)</i><br/>\n";
	$hout .= "Click 'View Archives' to see archived events.<br/>\n";
	$hout .= "<br/>\n";
   $hout .= "&bull; Click on the column header to sort the event list in that order.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= "&bull; Click ";
	$hout .= '<img alt="link" src="img/icon-link.png" class="icon-sm"/> ';
	$hout .= " to open the event link in a new window.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= "&bull; Click anywhere in the row to highlight it.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= "&bull; Use the 'Search' field to find all events with the search terms in the ";
	$hout .= "title or description <i>(it matches upper OR lower case.)</i> ";
	$hout .= "Click 'Reset' to list all events. Searches non-archived or archived events, ";
	$hout .= "depending on the mode in which the list is viewed.<br/>\n";
	$hout .= "</span>\n";
   $hout .= "</p>\n";
   $hout .= "</div>\n";
	return $hout;
	}

/* ==============================================
 
	Modify events

*/
function evt_modify ($msq, $action, $id, $datetime)
   {
   $hout = "";
   if (strcmp ($action, "copy") == 0)
      {
      $hout .= "<h3>Copy an Event</h3>\n";
      $hout .= evt_printform ($msq, "copy", $id, $datetime);
      }
   else if (strcmp ($action, "new") == 0)
      {
      // print new event form
      $hout .= "<h3>Add a New Event</h3>\n";
      $hout .= evt_printform ($msq, "new", "new", $datetime);
      }
   else if (strcmp ($action, "edit") == 0)
      {
      // print edit event form
      $hout .= "<h3>Edit Event</h3>\n";
      $hout .= evt_printform ($msq, "edit", $id, 0);
      }
   return $hout;
   }

/* ==============================================
 
	Print event modify form

*/
function evt_printform ($msq, $action, $id, $datetime)
   {
   $hout = "";
   $row = array();

   // edit and copy pull existing data from db
   if (strcmp ($action, "edit") == 0 || strcmp ($action, "copy") == 0)
      {
      $row = db_geteventdata ($msq, $id);
      if ($row == NULL)
         {
         $hout .= uf_print_bar ("error", "ERROR reading events database: " . $msq->error);
         return $hout;
         }
		
		// for all-day events, show end day as day before
		if ($row['allday'] == 1)
			{
         //$ets = strtotime ($row['etime']);
         //$egd = getdate ($ets);
         //$ets = mktime (0, 0, 0, $egd['mon'], $egd['mday'] - 1, $egd['year']);
			//$row['etime'] = uf_fmtdtmsq ($ets, TRUE);
			}
      }

   // set date for new events
   if (strcmp ($action, "new") == 0)
      {
      // default to today's date
      $evt_ts = time();
      if (strlen ($datetime) > 0)
         {
         // convert input to timestamp
         $dt_ts = strtotime ($datetime);
         if ($dt_ts != FALSE)
            {
            $evt_ts = $dt_ts;
            }
         }
      $row['stime'] = date ("Y-m-d H:i:00", $evt_ts);
      $row['etime'] = $row['stime'];
      }

   // set defaults for new events
   if (strcmp ($action, "new") == 0)
      {
      // set defaults
      $row['title'] = "";
      $row['description'] = "";
      $row['link'] = "";
      $row['color'] = "";
      $row['allday'] = 0;
      }

   // start form, include id and action in hidden fields
   $hout .= "<br/>\n";
   $hout .= '<form action="/saveevent" method="post">' . "\n";
   $hout .= '<input type="hidden" name="id" value="' . $id . '" />' . "\n";
   $hout .= '<input type="hidden" name="action" value="' . $action . '" />' . "\n";
   $hout .= '<input type="hidden" name="prevpg" value="' .
            htmlspecialchars ($_SERVER['HTTP_REFERER']) . '" />' . "\n";

   // start table
   $hout .= '<table id="secedit">' . "\n";

   // title
   $hout .= '<tr><th>Event Title:</th>';
   $hout .= '<td><input type="text" name="title" size="60"';
	$hout .= ' placeholder="(required)"';
	$hout .= ' value="' . $row['title'] . '" />';
   $hout .= "<br/>\n";
   $hout .= '<input disabled type="checkbox" id="arch" name="archive" value="1"';
   $hout .= $row['archive'] == 1 ? " checked" : "";
   $hout .= ' /><label for="arch"> Archived</label>';
   $hout .= "</td></tr>\n";

   // date pickers
	$all_day = $row['allday'] == 1 ? TRUE : FALSE;
   $hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";
   $hout .= '<tr><th>Start:</th>';
   $hout .= '<td>';
	$hout .= ecal_printdtpicker ($row['stime'], "s", $all_day);
   $hout .= ' <input type="checkbox" id="ad" name="allday" value="1" onclick="toggleTime(this.checked);"';
   $hout .= $all_day == 1 ? " checked" : "";
   $hout .= ' /><label for="ad">All-day</label></td></tr>' . "\n";
   $hout .= '<tr><th>End:</th>';
   $hout .= '<td>';
	$hout .= ecal_printdtpicker ($row['etime'], "e", $all_day);
	$hout .= '</td></tr>' . "\n";
   $hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

   // color select
   $hout .= '<tr><th>Color:</th>';
   $hout .= '<td>';
   $hout .= '<input type="radio" id="cn" name="color" value=""' .
            ($row['color'] == "" ? " checked" : "") . ' />' .
            '<label for="cn"> None</label> ';
   $hout .= '<input type="radio" id="cb" name="color" value="darkblue"' .
            ($row['color'] == "darkblue" ? " checked" : "") . ' />' .
            '<label for="cb"><img class="ecimg" alt="" src="img/darkblue.png" /></label> ';
   $hout .= '<input type="radio" id="co" name="color" value="darkorange"' .
            ($row['color'] == "darkorange" ? " checked" : "") . ' />' .
            '<label for="co"><img class="ecimg" alt="" src="img/darkorange.png" /></label> ';
   $hout .= '<input type="radio" id="cr" name="color" value="darkred"' .
            ($row['color'] == "darkred" ? " checked" : "") . ' />' .
            '<label for="cr"><img class="ecimg" alt="" src="img/darkred.png" /></label> ';
   $hout .= '<input type="radio" id="cp" name="color" value="purple"' .
            ($row['color'] == "purple" ? " checked" : "") . ' />' .
            '<label for="cp"><img class="ecimg" alt="" src="img/purple.png" /></label> ';
   $hout .= '<input type="radio" id="ck" name="color" value="hotpink"' .
            ($row['color'] == "hotpink" ? " checked" : "") . ' />' .
            '<label for="ck"><img class="ecimg" alt="" src="img/hotpink.png" /></label> ';
   $hout .= '<input type="radio" id="cg" name="color" value="darkgreen"' .
            ($row['color'] == "darkgreen" ? " checked" : "") . ' />' .
            '<label for="cg"><img class="ecimg" alt="" src="img/darkgreen.png" /></label> ';
   $hout .= '<input type="radio" id="cl" name="color" value="goldenrod"' .
            ($row['color'] == "goldenrod" ? " checked" : "") . ' />' .
            '<label for="cl"><img class="ecimg" alt="" src="img/goldenrod.png" /></label> ';
   $hout .= "</td></tr>\n";
   $hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

   // link
   $hout .= '<tr><th>Link:</th>';
   $hout .= '<td><input type="text" name="link" size="60"';
	$hout .= ' placeholder="http://"';
	$hout .= ' value="' . $row['link'];
   $hout .= '" /></td></tr>' . "\n";
   $hout .= '<tr><td colspan="2">&nbsp;</td></tr>' . "\n";

   // description text area
   $hout .= '<tr><th>Description:</th>';
	$hout .= '<td><textarea name="description" placeholder="(optional)" cols="63" rows="10">';
   $hout .= $row['description'] . "</textarea></td></tr>\n";

   // save and cancel buttons
   $hout .= '<tr><td></td><td>' .
            '<input id="saveEvt" type="submit" name="save" value="Save Event" onclick="return confirmSave();" /> ' .
            '<input id="cancelEvt" type="submit" name="cancel" value="Cancel" /> ';
   if (strcmp ($action, "edit") == 0)
      {
      $hout .= '<input id="delEvt" type="submit" name="delete" value="Delete" onclick="return confDelete();" /> ';
      }
   $hout .= "</td></tr>\n";

   // close table/form
   $hout .= "</table>\n";
   $hout .= "</form>\n";

   return $hout;
   }

/* ==============================================
 
	Save event data

*/
function evt_savedata ($msq, $post)
   {
   $hout = "";

   $evtdata = array();

	$date_tmp = array();
	if (strlen ($post['sdate']) > 0 && strlen ($post['edate']) > 0)
		{
		$date_tmp = getdate (strtotime ($post['sdate']));
		$post['sy'] = $date_tmp['year'];
		$post['sm'] = $date_tmp['mon'];
		$post['sd'] = $date_tmp['mday'];
		$date_tmp = getdate (strtotime ($post['edate']));
		$post['ey'] = $date_tmp['year'];
		$post['em'] = $date_tmp['mon'];
		$post['ed'] = $date_tmp['mday'];
		}

   // check for invalid date/time
   if (checkdate ($post['sm'], $post['sd'], $post['sy']) == FALSE ||
		 checkdate ($post['em'], $post['ed'], $post['ey']) == FALSE)
      {
      $hout .= uf_print_bar("error", "ERROR: invalid date. Go back and select a new date.");
      return $hout;
      }

   // check for all day event
	$evtdata['allday'] = 0;
   if (strlen ($post['allday']) > 0)
		{
      // set data accordlingly
      $evtdata['allday'] = 1;
      $post['shr'] = 12;
      $post['smin'] = 0;
      $post['ssec'] = 0;
      $post['sap'] = "am";
      $post['ehr'] = 11;
      $post['emin'] = 59;
      $post['esec'] = 59;
      $post['eap'] = "pm";
      }

   // convert 12-hr to 24-hr
   if (strcmp ($post['sap'], "am") == 0)
      {
      if ($post['shr'] == 12)
          $post['shr'] = 0;
      }
   else
      {
      if ($post['shr'] != 12)
          $post['shr'] += 12;
      }
   // for end time too!
   if (strcmp ($post['eap'], "am") == 0)
      {
      if ($post['ehr'] == 12)
          $post['ehr'] = 0;
      }
   else
      {
      if ($post['ehr'] != 12)
          $post['ehr'] += 12;
      }

	$sts = mktime ($post['shr'], $post['smin'], $post['ssec'], $post['sm'], $post['sd'], $post['sy']);
	$sgd = getdate ($sts);
	$ets = mktime ($post['ehr'], $post['emin'], $post['esec'], $post['em'], $post['ed'], $post['ey']);
	$egd = getdate ($ets);

	if ($sts > $ets)
		{
		$hout .= uf_print_bar("error", "ERROR: dates out of range. Go back and select new dates.");
		return $hout;
		}

   // build datetime strings (YYYY-MM-DD hh:mm:ss)
   $evtdata['stime'] = $sgd['year']  . "-" . $sgd['mon'] . "-" . $sgd['mday'] . " " .
                       $sgd['hours'] . ":" . $sgd['minutes'] . ":" . $sgd['seconds'];
	$evtdata['stime'] = uf_fmtdtmsq ($evtdata['stime']);
   $evtdata['etime'] = $egd['year']  . "-" . $egd['mon'] . "-" . $egd['mday'] . " " .
                       $egd['hours'] . ":" . $egd['minutes'] . ":" . $egd['seconds'];
	$evtdata['etime'] = uf_fmtdtmsq ($evtdata['etime']);

	$evtdata['title'] = $post['title'];
   if (strlen ($evtdata['title']) == 0)
      {
      $evtdata['title'] = "New Event";
      }
   $evtdata['title'] = stripslashes (str_replace ("\'", "''", $evtdata['title']));

   $evtdata['description'] = stripslashes (str_replace ("\'", "''", $post['description']));
   $evtdata['description'] = strip_tags ($evtdata['description'], '<b><i><u><a><img>');

   $evtdata['color'] = $post['color'];
   $evtdata['link'] = $post['link'];

	/*DEBUG:
	$hout .= "<pre>";
	foreach ($evtdata as $k => $v)
		{
		$hout .= "<b>$k</b> = '$v'<br/>\n";
		}
	$hout .= "</pre>\n";
	return $hout;
	*/

   $res = FALSE;
   $table = 'events';
   if (strcmp ($post['action'], "new") == 0 || strcmp ($post['action'], "copy") == 0)
      {
      // run insert
      $res = db_insertdata ($msq, $table, $evtdata);
      }
   else
      {
      // run update
      $res = db_updatedata ($msq, $table, $evtdata, array('id', $post['id']));
      }

   if ($res == FALSE)
      {
      $hout .= uf_print_bar ("error", "ERROR adding event to database: " . $msq->error);
      }
   else
      {
      $hout .= uf_print_bar ("info", "Event saved.");
      }
   $hout .= evt_print_home();
   $hout .= evt_printlist ($msq, "", "", 0);

   return $hout;
   }

/* ==============================================
 
	Delete event from database

*/
function evt_delete ($msq, $id)
   {
   $hout = "";

   $res = db_deletedata ($msq, 'events', array('id',$id));
   if ($res == FALSE)
      {
      $hout .= uf_print_bar ("error", "ERROR deleting event: " . $msq->error);
      }
   else
      {
      $hout .= uf_print_bar ("info", "Event deleted.");
      }
   $hout .= evt_print_home();
   $hout .= evt_printlist ($msq, "", "", 0);

   return $hout;
   }
   


/* END PHP */

?>
