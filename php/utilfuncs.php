<?php
/* ===========================================
// 
// utilfuncs.php
//
//    Utility functions
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

function uf_print_bar ($type, $err)
	{
	switch ($type)
		{
		case "error":
			$type = "errorbar";
			break;

		case "info":
		default:
			$type = "infobar";
			break;
		}
	$hout = '<div class="' . $type . '">';
	$hout .= "<b>$err</b>";
	$hout .= "</div><br/>\n";
	return $hout;
	}

/* ===========================================

   Return a date/time string in desired format
*/
function uf_fmtdtday ($datetime)
   {
   return date ("j", strtotime ($datetime));
   }

function uf_fmtdateical ($datetime)
   {
   return date ('Ymd', strtotime ($datetime));
   }

function uf_fmtdtical ($datetime)
   {
   return date ('Ymd\THis', strtotime ($datetime));
   }

function uf_fmttimecsv ($datetime)
   {
   return date ("G:i:s", strtotime ($datetime));
   }

function uf_fmtdatecsv ($datetime)
   {
   return date ("m/d/y", strtotime ($datetime));
   }

function uf_fmtdateshort ($date_str, $is_ts = FALSE)
   {
	if ($is_ts == FALSE)
		{
		$date_str = strtotime ($date_str);
		}
   return date ("D, M j, Y", $date_str);
   }

function uf_fmtdate ($datetime)
   {
   return date ("l, F j, Y", strtotime ($datetime));
   }

function uf_fmttimeshort ($datetime)
   {
   return date ("ga", strtotime ($datetime));
   }

function uf_fmttime ($datetime)
   {
   return date ("g:ia", strtotime ($datetime));
   }

function uf_fmtmonthday ($datetime)
	{
   return date ("D, M j", strtotime ($datetime));
	}

function uf_fmtdtlong ($datetime)
   {
   return uf_fmtdate ($datetime) . " " . uf_fmttime ($datetime);
   }

function uf_fmtdtshort ($datetime)
   {
   return uf_fmtdateshort ($datetime) . " " . uf_fmttime ($datetime);
   }

function uf_fmtdtmsq ($date_str, $is_ts = FALSE)
   {
	if ($is_ts == FALSE)
		{
		$date_str = strtotime ($date_str);
		}
   return date ("Y-m-d G:i:s", $date_str);
   }

/* END PHP */
?>
