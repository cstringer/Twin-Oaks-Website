<?php
/* ===========================================
// 
// contentedit.php
//
//    Content database editor
// 
//    Written by: Chris Stringer
//                chris@chrisstringer.us
//
// =========================================*/

//TODO: read these from config file!!!
define ("DOC_ROOT", "/home/content/16/9298216/html/twinoaks");
define ("IMG_URL_BASE", "http://twinoaksgolf.us");
define ("IMG_UL_DIR", "/img-upload");
define ("FILE_HEADER", "/_headers.hts");
define ("FILE_FOOTER", "/_footer.hts");
define ("FILE_CSS",    "/twinoaks.css");

require_once ("database.php");
require_once ("utilfuncs.php");

/* ===========================================

   Process content editor messages
*/
function ce_process_msgs ($msq, $message, $get, $post)
	{
	$hout = "";
	switch ($message)
		{
      case "editcontent":
         $hout .= ce_print_home ($msq);
         break;

      case "editappearance":
         $hout .= ce_print_appedit ($msq);
         break;

      case "saveappearance":
			if (strlen ($post['cancel']) == 0)
				{
				$hout .= ce_save_appearance ($msq, $post);
				}
			header ('Location: /editcontent');
         break;

      case "modsection":
         $hout .= ce_section_modify ($msq, $post);
         break;

      case "newsection":
         $post['newsection'] = 1;
         $hout .= ce_section_modify ($msq, $post);
         break;

      case "reordermenu":
         $post['reordermenu'] = 1;
         $hout .= ce_section_modify ($msq, $post);
         break;

      case "savesection":
         if (strlen ($post['cancel']) == 0)
            {
            $hout .= ce_savesection ($msq, $post);
				}
			header ('Location: /editcontent');
         break;

      case "delconfirm":
         $hout .= ce_deletesection ($msq, $post['id'], $post['no']);
			header ('Location: /editcontent');
         break;

      case "setmenuorder":
			if (strlen ($post['cancel']) == 0)
				{
				$hout .= ce_savemenuorder ($msq, $post['mo']);
				}
			header ('Location: /editcontent');
         break;

		case "manageimgs":
			$hout .= ce_manage_images ($msq, $post);
			break;

		default:
			break;
		}
	return $hout;
	}

/* ===========================================

   Print content editor home
*/
function ce_print_home ($msq)
	{
   $hout = "";

   // get sections from db
   $sections = db_getparent_secnames ($msq);
   if ($sections == NULL)
      {
      $hout .= uf_print_bar ("error", "ERROR reading content database: " . $msq->error);
      return $hout;
      }

   $hout .= "<h3>Content Editor</h3>\n";
   $hout .= '<form class="edithome" method="post" action="/modsection">';
   $hout .= "<p><b>Edit Section Content</b><br/>\n";
   $hout .= "Each section has a section name, title, page description, " .
            "and content area &mdash; all editable with this utility. " .
            "Additionally sections may be deleted from the database.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<select name="id">';
   $hout .= '<optgroup label="Section">';
   foreach ($sections as $s)
      {
		$sid = db_getsectionid ($msq, $s);
      $hout .= '<option value="' . $sid . '">' . $s . '</option>';
		$c_secs = db_getchild_secnames ($msq, $sid);
		if ($c_secs != NULL)
			{
			foreach ($c_secs as $c)
				{
				$cid = db_getsectionid ($msq, $c);
				$hout .= '<option value="' . $cid . '">&nbsp;&#9642; ' . $c . '</option>';
				}
			}
      }
	$hout .= '</optgroup>';
   $hout .= "</select>";
   $hout .= '<input type="submit" name="editsection" value="Edit">';
   $hout .= '<input type="submit" name="delsection" value="Delete"> ';
   $hout .= "</p>\n";
   $hout .= "</form>\n";

   $hout .= '<form class="edithome" method="post" action="/newsection">';
   $hout .= "<p><b>Create a New Section</b><br/>\n";
   $hout .= "Use this to add a new section to the site. ";
   $hout .= "It creates a menu item and a page with a header, navigation menu, footer, ";
   $hout .= "and an empty content area, all of which can be edited.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<input type="submit" name="newsection" value="Create a New Section"> ';
   $hout .= "</p>\n";
   $hout .= "</form>\n";

   $hout .= '<form class="edithome" method="post" action="/reordermenu">';
   $hout .= "<p><b>Change Menu Order</b><br/>\n";
   $hout .= "Re-arrange the order of sections in the navigation menu.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a href="/reordermenu">Change menu order</a>' . "\n";
   $hout .= "</p>\n";
   $hout .= "</form>\n";

	// header/footer edit
   $hout .= '<form class="edithome" method="post" action="/editappearance">';
   $hout .= "<p><b>Edit Site Appearance</b><br/>\n";
   $hout .= "Alter the appearance of all site pages by changing the header, ";
   $hout .= "footer, and CSS files. <i>For experienced web developers only.</i><br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<input type="submit" name="edithf" value="Edit Appearance"> ';
   $hout .= "</p>\n";
   $hout .= "</form>\n";

   $hout .= '<div class="edithome">';
   $hout .= "<p><b>View the Site</b><br/>\n";
   $hout .= "Open the public web site in a new brower window.<br/>\n";
   $hout .= "<br/>\n";
   $hout .= '<img alt=">" src="http://twinoaksgolf.us/img/yellow-triangle.png"/> ' .
            '<a target="_blank" href="http://twinoaksgolf.us">Visit the Site</a>' . "\n";
   $hout .= "</p>\n";
   $hout .= "</div>\n";

   return $hout;
	}

/* ===========================================

	Edit section data
*/
function ce_section_modify ($msq, $post)
   {
   $hout = "";
   $id = $post['id'];
   foreach ($post as $p => $v)
      {
      switch ($p)
         {
         case "reordermenu":
            $hout .= ec_reordermenu ($msq);
            break;

         case "delsection":
            $hout .= "<h3>Delete Section</h3>\n";
            $hout .= '<form class="edithome" method="post" action="/delconfirm">';
            $hout .= "<p>";
            $hout .= "<b>Delete the section '" . db_getsectionname ($msq, $id) . "'?</b><br/>\n";
            $hout .= "This will permanently remove the section and all its data, " .
                     "but any images or videos referenced by the page will be saved.<br/>\n";
            $hout .= "<br/>\n";
            $hout .= '<input type="hidden" name="id" value="' . $id . '">';
            $hout .= '<input type="submit" name="yes" value="Delete Section">';
            $hout .= '<input type="submit" name="no" value="Cancel">';
            $hout .= "</p>\n";
            $hout .= "</form>\n";
            break;

         case "editsection":
            $hout .= "<h3>Edit Section</h3>\n";
            $hout .= ce_printsectionedit ($msq, $id);
            break;

         case "newsection":
            $hout .= "<h3>Create a New Section</h3>\n";
            $hout .= ce_printsectionedit ($msq, "new");
            break;

         default:
            break;
         }
      }

   return $hout;
   }

/* ===========================================

   Set order of sections in menu
*/
function ec_reordermenu ($msq)
   {
   $hout = "";

   $sections = db_getparent_secnames ($msq);
   if ($sections == NULL)
      {
      $hout .= uf_print_bar ("error", "ERROR reading database: " . $msq->error);
      return $hout;
      }

	$childs = array(0 => array(0,NULL));
   foreach ($sections as $s)
      {
		$sid = db_getsectionid ($msq, $s);
		$c_secs = db_getchild_secnames ($msq, $sid);
		if ($c_secs != NULL)
			{
			foreach ($c_secs as $c)
				{
				$cid = db_getsectionid ($msq, $c);
				$childs[$sid][$cid] = $c;
				}
			}
      }

   $hout .= "<h3>Change Menu Order</h3>\n";
   $hout .= "<p>";
   $hout .= "Click a section name, then 'Up' or 'Down' to rearrange the list. ";
   $hout .= "When you're finished, click the 'Save Order' button, or 'Cancel' ";
	$hout .= "to keep the order unchanged.\n";
   $hout .= "</p>\n";

	$hout .= '<p>NOTE: Sections with "child" sections move as a single unit below. ';
	$hout .= 'Use the <a href="/editcontent">Section Content Editor</a> to ';
	$hout .= 'change the "parent" of a section.</p>';
	$hout .= "<br />\n";

	$num_secs = count ($sections) + count ($childs);
   $hout .= '<ul id="mo-select">' . "\n";
	foreach ($sections as $s)
		{
		$sid = db_getsectionid ($msq, $s);
		$cc = count ($childs[$sid]);
		if ($cc != 0)
			{
			/* value = section id
				class = r (root) | p (parent) | # (parent id of child)
				id = num_childs (parents)
				text = section name */
			$hout .= ' <li><span id="' . $sid . '">' . $s . "</span>\n";
			$hout .= "  <ul>\n";
			foreach ($childs[$sid] as $cid => $c)
				{
				$hout .= '   <li><span id="' . $cid . '">' . $c . '</span></li>' . "\n";
				}
			$hout .= "  </ul>\n";
			$hout .= " </li>\n";
			}
		else
			{
			$hout .= ' <li><span id="' . $sid . '">' . $s . '</span></li>' . "\n";
			}
		}
   $hout .= "</ul>\n";
   $hout .= "<br/>\n";

	$hout .= '<div class="ehf-buttons">';
   $hout .= '<button id="mo-up">UP</button> ';
   $hout .= '<button id="mo-down">DOWN</button> ';
   $hout .= "</div>\n";
   $hout .= "<br/>\n";

	$hout .= '<form id="mo-form" class="ehf-buttons" method="post" action="/setmenuorder">' . "\n";
	$hout .= '<input id="mo-order" type="hidden" name="mo">' . "\n";
   $hout .= '<input id="mo-save" class="saveBtn" type="submit" value="Save Order">' . "\n";
   $hout .= '<input class="cancelBtn" type="submit" name="cancel" value="Cancel">' . "\n";
   $hout .= "</form>\n";

   return $hout;
   }

/* ===========================================

   save menu order in database
*/
function ce_savemenuorder ($msq, $mo)
   {
   $hout = "";

   // menu order is comma-separated string of sec. ids
   $ord = explode (",", $mo);

   $i = 0;
   $data = array();
   foreach ($ord as $id)
      {
      $data['menuorder'] = $i;
      $res = db_updatedata ($msq, 'content', $data, array('id',$id));
      if ($res == FALSE)
         {
         $hout .= uf_print_bar ("error", "ERROR updating menu order: " . $msq->error);
         }
      $i++;
      }

   $hout .= uf_print_bar ("info", "Saved menu order.");

   return $hout;
   }

/* ===========================================

	Print section editing form
*/
function ce_printsectionedit ($msq, $id)
   {
   $hout = "";

   $sdata = array();
   $sqlaction = "upd";
   if (strcmp ($id, "new") != 0)
      {
      // get section from DB
      $sdata = db_getsectiondata ($msq, $id);
      if ($sdata == NULL)
         {
         $hout .= uf_print_bar ("error", "ERROR reading section from DB: " . $msq->error);
         return $hout;
         }
      }
   else
      {
      // this is a new section
      $sqlaction = "ins";
      // use # sections as default menu order for new sections
      $sdata['menuorder'] = db_getsectioncount ($msq);
      $sdata['section'] = "New Section";
      $sdata['title'] = "Twin Oaks Golf Complex - Eudora, KS - NEW_SECTION";
      $sdata['description'] = "This text is displayed by search engines as the page description.";
      $sdata['chunk'] = "<h3>Heading</h3>\n<p>Paragraph</p>\n";
      }

	$sections = db_getparent_secnames ($msq);
	if ($sections == NULL)
		{
		$hout .= uf_print_bar ("error", "ERROR getting parent section names.");
		return $hout;
		}

   $hout .= "<br/>\n";
   $hout .= '<form name="compForm" action="/savesection" method="post" ' .
            'onsubmit="if(validateMode()){this.chunk.value=oDoc.innerHTML;return true;}return false;">' . "\n";
   $hout .= '<input type="hidden" name="chunk" />' . "\n";
   $hout .= '<input type="hidden" name="sqlaction" value="' . $sqlaction . '"/>' . "\n";
   $hout .= '<input type="hidden" name="id" value="' . $id . '"/>' . "\n";

   $hout .= '<table id="secedit">' . "\n";
   $hout .= '<tr><th>Section Name:</th>' .
            '<td><input type="text" name="secname" size="20" value="' . $sdata['section'] . '"></td>';
   $hout .= "<th>Menu Order:</th>" .
            '<td><input readonly type="text" name="menuorder" size="3" value="' . $sdata['menuorder'] . '"></td>';
   $hout .= "</tr>\n";

	$hout .= '<tr><th>Parent Section:</th>';
	$hout .= '<td>';
	$hout .= '<select name="parent_id">';
	$hout .= '<option value="">-none-</option>';
	foreach ($sections as $s)
		{
		$pid = db_getsectionid ($msq, $s);
		$hout .= '<option value="' . $pid . '"';
		if ($sdata['parent_id'] == $pid)
			{
			$hout .= ' selected';
			}
		if ($id == $pid)
			{
			$hout .= ' disabled';
			}
		$hout .= '>' . $s . '</option>';
		}
	$hout .= '</select>';
	$hout .= "</td></tr>\n";

   $hout .= '<tr><th>Page Title:</th><td colspan="3">' .
            '<input type="text" name="title" size="40" value="' . $sdata['title'] . '">' . "</td></tr>\n";

   $hout .= '<tr><th>Description:</th><td colspan="3">' .
            '<input type="text" name="description" size="70" value="' . $sdata['description'] . '">' .
            "</td></tr>\n";

   $hout .= '<tr><td colspan="4">';
   $hout .= '<div id="toolBar2">';
   $hout .= '<select onchange="formatDoc(\'formatblock\',this[this.selectedIndex].value);this.selectedIndex=0;">';
   $hout .= '<option class="heading" selected>- Text Style -</option>' .
            '<option value="p">Normal Text</option>' .
            '<option value="h2">Gradient Title (Big)</option>' .
            '<option value="h3">Gradient Title (Small)</option>' .
            '<option value="pre">Preformatted Text</option>';
   $hout .= "</select>\n";

   $hout .= '<select onchange="formatDoc(\'fontname\',this[this.selectedIndex].value);this.selectedIndex=0;">';
   $hout .= '<option class="heading" selected>- Font -</option>';
   $hout .= '<option style="font-family:Arial">Arial</option>';
   $hout .= '<option style="font-family:Arial Black">Arial Black</option>';
   $hout .= '<option style="font-family:Comic Sans MS">Comic Sans MS</option>';
   $hout .= '<option style="font-family:Courier New">Courier New</option>';
   $hout .= '<option style="font-family:Georgia">Georgia</option>';
   $hout .= '<option style="font-family:Helvetica">Helvetica</option>';
   $hout .= '<option style="font-family:Impact">Impact</option>';
   $hout .= '<option style="font-family:Lucida Console,Monaco">Lucida Console</option>';
   $hout .= '<option style="font-family:Lucida Sans Unicode,Lucida Grande">Lucida Sans Unicode</option>';
   $hout .= '<option style="font-family:Palatino">Palatino</option>';
   $hout .= '<option style="font-family:Tahoma">Tahoma</option>';
   $hout .= '<option style="font-family:Times New Roman">Times New Roman</option>';
   $hout .= '<option style="font-family:Trebuchet MS">Trebuchet MS</option>';
   $hout .= '<option style="font-family:Verdana,Geneva">Verdana</option>';
   $hout .= "</select>\n";

   $hout .= '<select onchange="formatDoc(\'fontsize\',this[this.selectedIndex].value);this.selectedIndex=0;">';
   $hout .= '<option class="heading" selected>- Font Size -</option>';
   $hout .= '<option value="1">Smallest</option><option value="2">Small</option>';
   $hout .= '<option value="3">Normal</option>';
   $hout .= '<option value="4">Large</option>';
   $hout .= '<option value="5">Largest</option>';
   $hout .= '</select>';

   $hout .= '<img class="intLink" alt="Undo" onclick="formatDoc(\'undo\');" src="img/undo.png" />';
   $hout .= '<img class="intLink" alt="Redo" onclick="formatDoc(\'redo\');" src="img/redo.png" />';
   $hout .= '<img class="intLink" alt="Remove formatting" onclick="formatDoc(\'removeFormat\')" src="img/normal.png">';
   $hout .= '<img class="intLink" alt="Bold" onclick="formatDoc(\'bold\');" src="img/bold.png" />';
   $hout .= '<img class="intLink" alt="Italic" onclick="formatDoc(\'italic\');" src="img/italic.png" />';
   $hout .= '<img class="intLink" alt="Underline" onclick="formatDoc(\'underline\');" src="img/underline.png" />';
   $hout .= '<img class="intLink" alt="Add Link" onclick="addLink();" src="img/link.png" />';
   $hout .= '<img class="intLink" alt="Remove Link" onclick="removeLink();" src="img/unlink.png" />';
   $hout .= '<img class="intLink" alt="Insert Image" onclick="addImage();" src="img/image.png" />';
   $hout .= '<span id="editMode"> &nbsp; ';
   $hout .= '<input type="checkbox" name="switchMode" id="switchBox" onchange="setDocMode(this.checked);" />';
   $hout .= '<label for="switchBox"> Show HTML</label></span>';
   $hout .= "</div></td></tr>\n";

   $hout .= '<tr><td colspan="4"><div id="textBox" contenteditable="true">' .
            $sdata['chunk'] . '</div></td></tr>' . "\n";

   $hout .= '<tr><th colspan="2">';
   $hout .= '<input id="saveSec" type="submit" onclick="return confirmSave();" value="Save Section">';
   $hout .= '<input id="cancelSec" type="submit" name="cancel" value="Cancel">';
   $hout .= "</th></tr>\n";

   $hout .= "</table>\n";
   $hout .= "</form>\n";
   $hout .= '<script type="text/javascript">initDoc();</script>';

   return $hout;
   }

/* ===========================================

	Save section data to database
*/
function ce_savesection ($msq, $post)
   {
   $hout = "";

   if (strlen ($post['secname']) == 0)
      {
      $hout .= uf_print_bar ("error", "ERROR: No section name specified. Click your " .
                              "browser's 'Back' button to re-enter a section name, then save again.");
      return $hout;
      }  

   // convert strings
   $sdata = array();
   $sdata['section'] = stripslashes (str_replace ("\'", "''", $post['secname']));
   $sdata['title'] = stripslashes (str_replace ("\'", "''", $post['title']));
   $sdata['description'] = stripslashes (str_replace ("\'", "''", $post['description']));
   $sdata['chunk'] = stripslashes (str_replace ("\'", "''", $post['chunk']));
   $sdata['menuorder'] = $post['menuorder'];
	$sdata['parent_id'] = $post['parent_id'];

   $res = FALSE;
   $table = 'content';
   if (strcmp ("ins", $post['sqlaction']) == 0)
      {
      $res = db_insertdata ($msq, $table, $sdata);
      }
   else if (strcmp ("upd", $post['sqlaction']) == 0)
      {
      $res = db_updatedata ($msq, $table, $sdata, array('id',$post['id']));
      }
   
   if ($res == FALSE)
      {
		if (strpos ($msq->error, "Duplicate entry") !== FALSE)
			{
			$hout .= uf_print_bar ("error", "ERROR: section name already in use.");
			}
		else
			{
			$hout .= uf_print_bar ("error", "ERROR running SQL query: " . $msq->error);
			}
      return $hout;
      }
            
   $hout .= uf_print_bar ("info", "Section successfully saved.");

   return $hout;
   }
            
/* ===========================================

	Delete a section from the db
*/
function ce_deletesection ($msq, $id, $no)
   {
   $hout = "";

   if (strlen ($no) > 0)
      {
      // delete cancelled
      $hout .= uf_print_bar ("info", "Delete cancelled.");
      return $hout;
      }

	// check section id
   if (strlen ($id) == 0)
      {
      $hout .= uf_print_bar ("error", "ERROR: no section id!");
      return $hout;
      }

	// get section name for display purposes
   $section = db_getsectionname ($msq, $id);

	// delete section from db
   $res = db_deletedata ($msq, 'content', array('id',$id));
   if ($res == FALSE)
      {
      $hout .= uf_print_bar ("error", "ERROR deleting section '$section': " . $msq->error);
      }
   else
      {
      $hout .= uf_print_bar ("info", "Section '$section' deleted.");
      }
   return $hout;
   }

/* ===========================================

	Print header/footer editing page
*/
function ce_print_appedit()
	{
	$hout ="";

	$hout .= "<h3>Edit Appearance</h3>\n";
	$hout .= "<br/>\n";

	$hout .= '<form id="edithf" method="post" action="/saveappearance">';
	$hout .= '<p>';
	$hout .= "<b>Headers</b><br/>\n";
	$hout .= 'These tags appear in the &lt;head&gt; section.';
	$hout .= '</p>';
	$hout .= '<textarea name="header">';
	$hout .= htmlentities (file_get_contents (DOC_ROOT . "/" . FILE_HEADER));
	$hout .= "</textarea>\n";
	$hout .= "<br/>\n";

	$hout .= '<p>';
	$hout .= "<b>Footer</b><br/>\n";
	$hout .= 'This information appears at the bottom of each page.';
	$hout .= '</p>';
	$hout .= '<textarea name="footer">';
	$hout .= htmlentities (file_get_contents (DOC_ROOT . "/" . FILE_FOOTER));
	$hout .= "</textarea>\n";
	$hout .= "<br/>\n";

	$hout .= '<p>';
	$hout .= "<b>CSS</b><br/>\n";
	$hout .= 'Sets the style of HTML elements for each page.';
	$hout .= '</p>';
	$hout .= '<textarea name="stylesheet">';
	$hout .= htmlentities (file_get_contents (DOC_ROOT . "/" . FILE_CSS));
	$hout .= "</textarea>\n";
	$hout .= "<br/>\n";

	$hout .= '<div class="ehf-buttons">';
	$hout .= '<input type="submit" class="saveBtn" name="edithfSave" value="Save Files"/> ';
	$hout .= '<input type="submit" class="cancelBtn" name="cancel" value="Cancel"/>';
	$hout .= "</div>\n";
	$hout .= "</form>\n";

	return $hout;
	}

function ce_save_appearance ($msq, $post)
	{
	$hout ="";

	$pf_fname = DOC_ROOT . "/" . FILE_HEADER;
	$prev_file = file_get_contents ($pf_fname);
	$post_str = str_replace ('\"', '"', $post['header']);
	$post_str = str_replace ("\'", "'", $post_str);
	$post_str = str_replace ("\r", '', $post_str);
	if (strcmp ($prev_file, $post_str) != 0)
		{
		if (ce_saveandbackup ($pf_fname, $post_str) == FALSE)
			{
			$hout .= uf_print_bar ("error", "Can't save header file.");
			}
		else
			{
			$hout .= uf_print_bar ("info", "Saved header file.");
			}
		}

	$pf_fname = DOC_ROOT . "/" . FILE_FOOTER;
	$prev_file = file_get_contents ($pf_fname);
	$post_str = str_replace ('\"', '"', $post['footer']);
	$post_str = str_replace ("\'", "'", $post_str);
	$post_str = str_replace ("\r", '', $post_str);
	if (strcmp ($prev_file, $post_str) != 0)
		{
		if (ce_saveandbackup ($pf_fname, $post_str) == FALSE)
			{
			$hout .= uf_print_bar ("error", "Can't save footer file.");
			}
		else
			{
			$hout .= uf_print_bar ("info", "Saved footer file.");
			}
		}

	$pf_fname = DOC_ROOT . "/" . FILE_CSS;
	$prev_file = file_get_contents ($pf_fname);
	$post_str = str_replace ('\"', '"', $post['stylesheet']);
	$post_str = str_replace ("\'", "'", $post_str);
	$post_str = str_replace ("\r", '', $post_str);
	if (strcmp ($prev_file, $post_str) != 0)
		{
		if (ce_saveandbackup ($pf_fname, $post_str) == FALSE)
			{
			$hout .= uf_print_bar ("error", "Can't open save file.");
			}
		else
			{
			$hout .= uf_print_bar ("info", "Saved stylesheet.");
			}
		}

	return $hout;
	}

function ce_saveandbackup ($fname, $in_str)
	{
	// check for input filename, in_str *can* be empty
	if (strlen ($fname) == 0)
		{
		return FALSE;
		}

	// get previous file as string
	$prev_file = file_get_contents ($fname);

	// make backup file
	$bkup_fname = $fname . ".bak";
	if (ce_writefile ($bkup_fname, $prev_file) == FALSE)
		{
		return FALSE;
		}
	
	// save file
	return ce_writefile ($fname, $in_str);
	}

function ce_writefile ($fname, $in_str)
	{
	// check input
	if (strlen ($fname) == 0)
		{
		return FALSE;
		}
	
	// write file
	$hfile = fopen ($fname, 'w');
	if ($hfile != FALSE)
		{
		fwrite ($hfile, $in_str, strlen ($in_str));
		fclose ($hfile);
		}
	return TRUE;
	}

function ce_manage_images ($msq, $post)
	{
	$hout = "";
	$hout .= "<h3>Manage Images</h3>\n";

   $dir = DOC_ROOT . IMG_UL_DIR;
	$pat = $dir . "/*.{gif,jpg,png}";
	
	$hout .= '<table>' . "\n";
	$hout .= '<tr><th>Address</th><th>Preview</th></tr>' . "\n";
	foreach (glob ($pat, GLOB_BRACE) as $file)
		{
		$fname = basename ($file);
		$url = IMG_URL_BASE . IMG_UL_DIR . "/" . $fname;

		$hout .= '<tr>';
		$hout .= '<td><a href="' . $url . '">' . $url . '</a></td>';
		$hout .= '<td><img src="' . IMG_UL_DIR . "/" . $fname . '" style="max-width:80px;max-height:80px" /></td>';
		$hout .= '</tr>';
		}
	$hout .= '</table>' . "\n";

	return $hout;
	}

/* END PHP */

?>
