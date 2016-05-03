<?php
/* ===========================================
// 
// evtimpexp.php
//
//    Event importer/exporter
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

require_once ("database.php");
require_once ("utilfuncs.php");
require_once ("eventedit.php");


/* ===========================================

	Process event import/export messages
*/
function eix_process_msgs ($msq, $message, $get, $post)
	{
	$hout = "";
	switch ($message)
		{
      case "impexpevents":
         {
         $exp_retv = FALSE;
         if (strlen ($post['expcsv']) > 0)
            {
            $exp_retv = eix_exporteventfile ($msq, "csv");
				db_close ($msq);
				exit;
            }
         else if (strlen ($post['expical']) > 0)
            {
            $exp_retv = eix_exporteventfile ($msq, "ical");
				db_close ($msq);
				exit;
            }
         else if (strlen ($post['impevents']) > 0)
            {
            $hout .= eix_importeventfile ($msq);
				header ("Location: /viewevents");
            }
         else
            {
            $hout .= eix_printnav ($msq);
            }
         break;
         }

      default:
         break;
		}
	return $hout;
	}

/* ===========================================

	Process content editor messages
*/
function eix_printnav ($msq)
	{
	$hout = "";
	$hout .= "<h3>Import/Export Events</h3>\n";
	$hout .= '<form class="edithome" method="post" action="/impexpevents" ' .
				'enctype="multipart/form-data">';
	$hout .= "<p>";
	$hout .= "<b>Import</b><br/>\n";
	$hout .= "Add events created by other calendar software " .
				"that can export to CSV or iCal format. " .
				"Click 'Browse/Choose File' to find the file on your computer, " .
				"then 'Import' to add the events to the database.\n";
	$hout .= "<br/><br/>\n";
	$hout .= '<input type="file" accept="text/csv,text/calendar" name="impfile">';
	$hout .= "<br/><br/>\n";
	$hout .= '<input type="submit" name="impevents" value="Import events"> ';
	$hout .= "<br/><br/>\n";
	$hout .= "<b>Export</b><br/>\n";
	$hout .= "This saves the events in the database " .
				"to a file on your local computer, " . 
				"compatible with calendar software " .
				"that uses CSV or iCal format.\n";
	$hout .= "<br/><br/>\n";
	$hout .= '<input type="submit" name="expcsv" value="Export to CSV"> ';
	$hout .= '<input type="submit" name="expical" value="Export to iCal"><br/>';
	$hout .= "</p>";
	$hout .= "</form>\n";
	return $hout;
	}

/* ===========================================

	Export events to specified file type
*/
function eix_exporteventfile ($msq, $filetype)
	{
	global $DbName;

	// get events from database
	$res = $msq->query("SELECT * FROM events ORDER BY stime");
	if ($res == FALSE)
		{
		return FALSE;
		}
	$res->data_seek(0);

	// determine file type, default to iCal
	$fext = ".ics";
	$fmimetype = "text/calendar";
	if ($filetype == "csv")
		{
		$fext = ".csv";
		$fmimetype = "text/csv";
		}

	// build file name
	$fname = "";
	//$fname .= $_SERVER['HTTP_HOST'];
	$fname .= $DbName;
	$fname .= "_events_";
	$fname .= date("Y-m-d");
	$fname .= $fext;

	// create temp file, always
	$tempfile = fopen ($fname, 'w');

	if ($filetype == "csv")
		{
		// write header row
		$fields = array("Subject", "Start Date", "Start Time", "End Date", 
							 "End Time", "All Day Event", "Description");
		fputcsv ($tempfile, $fields);
		
		// write row for each event
		while ($row = $res->fetch_assoc())
			{
			$fields[0] = $row['title'];
			$fields[1] = uf_fmtdatecsv ($row['stime']);
			$fields[2] = uf_fmttimecsv ($row['stime']);
			$fields[3] = uf_fmtdatecsv ($row['etime']);
			$fields[4] = uf_fmttimecsv ($row['etime']);
			$fields[5] = ($row['allday'] == 1) ? "TRUE" : "FALSE";
			$fields[6] = strip_tags ($row['description']);
			$fields[6] = str_replace ('"', '\"', $fields[6]);
			$fields[6] = str_replace (array("\n","\r"),array(' ',""), $fields[6]);
			fputcsv ($tempfile, $fields);
			}
		}
	else
		{
		// write iCal header
		fwrite ($tempfile, "BEGIN:VCALENDAR\n");
		fwrite ($tempfile, "PRODID:-//StrCal v1.0//StringCal iCal Exporter//EN\n");
		fwrite ($tempfile, "VERSION:2.0\n");
		fwrite ($tempfile, "CALSCALE:GREGORIAN\n");
		fwrite ($tempfile, "X-WR-CALNAME:" . $_SERVER['HTTP_HOST'] . "\n");
		fwrite ($tempfile, "X-WR-TIMEZONE:" . DEF_TIMEZONE . "\n");

		//TODO: write timezone info
		fwrite ($tempfile, "BEGIN:VTIMEZONE\n");
		fwrite ($tempfile, "TZID:America/Chicago\n");
		fwrite ($tempfile, "BEGIN:DAYLIGHT\n");
		fwrite ($tempfile, "TZOFFSETFROM:-0600\n");
		fwrite ($tempfile, "TZOFFSETTO:-0500\n");
		fwrite ($tempfile, "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU\n");
		fwrite ($tempfile, "DTSTART:20070311T020000\n");
		fwrite ($tempfile, "TZNAME:CDT\n");
		fwrite ($tempfile, "END:DAYLIGHT\n");
		fwrite ($tempfile, "BEGIN:STANDARD\n");
		fwrite ($tempfile, "TZOFFSETFROM:-0500\n");
		fwrite ($tempfile, "TZOFFSETTO:-0600\n");
		fwrite ($tempfile, "RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU\n");
		fwrite ($tempfile, "DTSTART:20071104T020000\n");
		fwrite ($tempfile, "TZNAME:CST\n");
		fwrite ($tempfile, "END:STANDARD\n");
		fwrite ($tempfile, "END:VTIMEZONE\n");

		// write VEVENT section for each event
		while ($row = $res->fetch_assoc())
			{
			fwrite ($tempfile, "BEGIN:VEVENT\n");
			if ($row['allday'] == "1")
				{
				fwrite ($tempfile, "DTSTART;VALUE=DATE:" . uf_fmtdateical ($row['stime']) . "\n");
				fwrite ($tempfile, "DTEND;VALUE=DATE:"   . uf_fmtdateical ($row['etime']) . "\n");
				}
			else
				{
				fwrite ($tempfile, "DTSTART;TZID=" . DEF_TIMEZONE . ":" . uf_fmtdtical ($row['stime']) . "\n");
				fwrite ($tempfile, "DTEND;TZID="   . DEF_TIMEZONE . ":" . uf_fmtdtical ($row['etime']) . "\n");
				}
			fwrite ($tempfile, "SUMMARY:" . $row['title'] . "\n");
			fwrite ($tempfile, "DESCRIPTION:" . 
				str_replace (array ("\n", "\r"), array("<br/>",""), $row['description']) . "\n");
			fwrite ($tempfile, "END:VEVENT\n");
			}

		// end iCal
		fwrite ($tempfile, "END:VCALENDAR\n");
		fwrite ($tempfile, "\n");
		}

	// close temp file
	fclose ($tempfile);

	// set headers
	header ("Content-type: $fmimetype");
	header ("Content-Disposition: attachment; filename=\"$fname\"");
	header ("Expires: " . gmdate('D, d M Y H:i:s', gmmktime() - 3600) . ' GMT');
	header ("Content-Length: " . filesize ($fname));
	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header ('Pragma: public');

	// output file
	$fp = fopen($fname, "r");
	fpassthru($fp);
	fclose($fp);

	// delete temp file
	unlink ($fname);

	return TRUE;
	}

/* ===========================================

	Import event file
*/
function eix_importeventfile ($msq)
	{
	$hout = "";
	$evt_count = 0;

	// check for uploaded file
	if ((is_uploaded_file ($_FILES['impfile']['tmp_name']) == FALSE) ||
		 ($_FILES['impfile']['size'] == 0))
		{
		$hout .= uf_print_bar ("error", "ERROR: no data to import!");
		return $hout;
		}

	/*DEBUG:*/
	if (DEBUG_MODE == 1)
		{
		$hout .= '<b>File:</b> ' . $_FILES['impfile']['name'] . "<br/>\n";
		$hout .= '<b>Type:</b> ' . $_FILES['impfile']['type'] . "<br/>\n";
		$hout .= '<b>Size:</b> ' . $_FILES['impfile']['size'] . " Bytes<br/>\n";
		$hout .= '<b>Temp:</b> ' . $_FILES['impfile']['tmp_name'] . "<br/>\n";
		}

	// parse valid file types
	if (strcmp ($_FILES['impfile']['type'], "text/calendar") == 0)
		{
		$evt_count = eix_importical ($msq, $hout);
		}
	else if (strcmp ($_FILES['impfile']['type'], "text/csv") == 0)
		{
		$evt_count = eix_importcsv ($msq, $hout);
		}
	else
		{
		$hout .= uf_print_bar ("error", "ERROR: file type not CSV or iCal.");
		}

	if ($evt_count > 0)
		{
		$hout .= uf_print_bar ("info", "Added $evt_count events to database.");
		}

	// delete temp file!
	unlink ($_FILES['impfile']['tmp_name']);

	return $hout;
	}

/* ===========================================

	Import events from iCal file
*/
function eix_importical ($msq, $hout)
	{
	$evt_count = 0;		// returns # events added to db successfully
	$fbuf = "";				// string buffer holds entire iCal file
	$fbuf_ndx = 0;			// starting location when looking for events
	$fbuf_end = 0;			// char pos of end calendar tag (not EOF!)
	$evt_begin = 0;		// 1st char after a begin event tag
	$evt_end = 0;			// 1st char before end event tag
	$evt_str = "";			// all event data, from begin to end tags (not incl. tags)
	$end_loop = FALSE;	// while() sentinel

	// read entire tmp file to string buffer
	$fbuf = file_get_contents ($_FILES['impfile']['tmp_name']);

	// get ending "tag" position
	$fbuf_end = strpos ($fbuf, "END:VCALENDAR");

	/* parse string until the end "tag",
		or until we stop finding events */
	while ($fbuf_ndx < $fbuf_end && $end_loop == FALSE)
		{
		// find begin/end event "tags"
		$evt_begin = strpos ($fbuf, "BEGIN:VEVENT", $fbuf_ndx) + strlen ("BEGIN:VEVENT\n");
		$evt_end = strpos ($fbuf, "END:VEVENT", $fbuf_ndx);

		// advance starting char to *past* end tag for next pass
		$fbuf_ndx = $evt_end + strlen ("END:VEVENT\n");

		// if we can't find any more event tags, end this thing
		if ($evt_begin === FALSE || $evt_end === FALSE)
			{
			$end_loop = TRUE;
			}
		else
			{
			// for convenience, get string of data between event begin/end tags
			$evt_str = substr ($fbuf, $evt_begin, $evt_end - $evt_begin);

			// clear a new temp data struct
			$data_tmp = array();

			/* Parse data lines */

			// get SUMMARY ('title')
			if (preg_match ("/SUMMARY:(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['title'] = addslashes (rtrim ($matches[1]));
				}
			
			// get start date and/or time
			if (preg_match ("/DTSTART;VALUE=DATE:(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['allday'] = 1;
				$data_tmp['stime'] = uf_fmtdtmsq (rtrim ($matches[1]) . "T000000");
				}
			else if (preg_match ("/DTSTART;TZID=(.*):(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['stime'] = uf_fmtdtmsq (rtrim ($matches[2]));
				}
			else if (preg_match ("/DTSTART:(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['stime'] = uf_fmtdtmsq (rtrim ($matches[1]));
				}

			// we can't do much without a start date!
			if (strlen ($data_tmp['stime']) == 0)
				{
				continue;
				}

			// get end date and/or time
			if (preg_match ("/DTEND;VALUE=DATE:(.*)/", $evt_str, $matches) != 0)
				{
				/* iCal stores all-day events' end dates as the day *after* the event ends...
					Feed the end date into mktime(), and subtract 1 second for the end date. */
				$dstr = rtrim ($matches[1]);
				$gd = getdate (strtotime ($dstr));
				$dts = mktime ($gd['hours'], $gd['minutes'], $gd['seconds'] - 1, $gd['mon'], $gd['mday'], $gd['year']);
				$data_tmp['etime'] = uf_fmtdtmsq ($dts, TRUE);
				}
			else if (preg_match ("/DTEND;TZID=(.*):(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['etime'] = uf_fmtdtmsq (rtrim ($matches[2]));
				}
			else if (preg_match ("/DTEND:(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['etime'] = uf_fmtdtmsq (rtrim ($matches[1]));
				}

			// use start time if we don't find end time
			if (strlen ($data_tmp['etime']) == 0)
				{
				$data_tmp['etime'] = $data_tmp['stime'];
				}

			if (preg_match ("/DESCRIPTION:(.*)/", $evt_str, $matches) != 0)
				{
				$data_tmp['description'] = addslashes ($matches[1]);
				}

			// run sql query
			if (db_insertdata ($msq, 'events', $data_tmp) == FALSE)
				{
				$hout .= uf_print_bar ("error", "ERROR: couldn't add event " . 
										 stripslashes ($data_tmp['title']) . 
										 " (SQL Error: " . $msq->error . ")");
				}
			else
				{
				$evt_count++;
				}
			}
		}
	return $evt_count;
	}

/* ===========================================

	Import events from CSV file
*/
function eix_importcsv ($msq, $hout)
	{
	$evt_count = 0;	// returns # events successfully added
	$sql_str = "";		// temp holder for sql statment

	// open temp file
	$fhandle = fopen ($_FILES['impfile']['tmp_name'], 'r');

	/* We don't know what order the fields will be in.
		This reads the order of the header (1st) line,
		and builds an ordered array of the names of 
		local database equivalents. */
	$csvfields = fgetcsv ($fhandle, 1024, ',', '"');
	$i = 0;
	$csvorder = array();
	foreach ($csvfields as $cf)
		{
		switch ($cf)
			{
			case "Subject":
				$csvorder[$i] = 'title';
				break;
			case "Start Date":
				$csvorder[$i] = 'sdate';
				break;
			case "Start Time":
				$csvorder[$i] = 'stime';
				break;
			case "End Date":
				$csvorder[$i] = 'edate';
				break;
			case "End Time":
				$csvorder[$i] = 'etime';
				break;
			case "All Day Event":
				$csvorder[$i] = 'allday';
				break;
			case "Location":
				$csvorder[$i] = 'location';
				break;
			case "Description":
				$csvorder[$i] = 'description';
				break;
			default:
				break;
			}
		$i++;
		}
	$num_fields = $i;

	/*DEBUG:*/
	if (DEBUG_MODE == 1)
		{
		$hout .= "<b>Fields found ($num_fields):</b><br/>\n";
		foreach ($csvorder as $f)
			{
			$hout .= " - $f<br/>\n";
			}
		}

	// parse remainder of file
	while (($data = fgetcsv ($fhandle, 1000, ',', '"')) !== FALSE)
		{
		// clear temp data array
		$data_tmp = array();
		for ($ndx = 0; $ndx < $num_fields; $ndx++)
			{
			// "translate" the data into a friendly assoc array
			$data_tmp[$csvorder[$ndx]] = $data[$ndx];
			}

		// set generic title if there isn't one
		if (strlen ($data_tmp['title']) == 0)
			{
			$data_tmp['title'] = "Event " . ($evt_count + 1);
			}
		$data_tmp['title'] = addslashes ($data_tmp['title']);

		// we need a start date!
		if (strlen ($data_tmp['sdate']) == 0)
			{
			continue;
			}

		// if no end date, use start date
		if (strlen ($data_tmp['edate']) == 0)
			{
			$data_tmp['edate'] = $data_tmp['sdate'];
			}

		// if no start time, treat as all day event
		if (strlen ($data_tmp['stime']) == 0)
			{
			$data_tmp['allday'] = "TRUE";
			}

		// if no end time, set to start time
		if (strlen ($data_tmp['etime']) == 0)
			{
			$data_tmp['etime'] = $data_tmp['stime'];
			}

		// set times for all day events to, well, all day!
		if ($data_tmp['allday'] == "TRUE")
			{
			$data_tmp['stime'] = "00:00:00";
			$data_tmp['etime'] = "00:00:00";
			$data_tmp['allday'] = 1;
			}
		else
			{
			$data_tmp['allday'] = 0;
			}

		// make start/end datetimes, unset *date vars
		$data_tmp['stime'] = uf_fmtdtmsq ($data_tmp['sdate'] . " " . $data_tmp['stime']);
		unset ($data_tmp['sdate']);
		$data_tmp['etime'] = uf_fmtdtmsq ($data_tmp['edate'] . " " . $data_tmp['etime']);
		unset ($data_tmp['edate']);

		// add location to description, then unset
		if (strlen ($data_tmp['location']) > 0)
			{
			$data_tmp['description'] .= " (" . $data_tmp['location'] . ")";
			}
		unset ($data_tmp['location']);
		$data_tmp['description'] = strip_tags ($data_tmp['description']);
		$data_tmp['description'] = addslashes ($data_tmp['description']);

		/*DEBUG:*/
		//$hout .= "<pre>SQL: $sql_str </pre>\n";
		if (db_insertdata ($msq, 'events', $data_tmp) == FALSE)
			{
			$hout .= uf_print_bar ("error", "ERROR: couldn't add event " .  $data_tmp['title'] . 
									 ", SQL Error: " . $msq->error);
			}
		else
			{
			$evt_count++;
			}
		}

	// close file
	fclose ($fhandle);

	return $evt_count;
	}

/* END php */

?>
