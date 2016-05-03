$.fn.exists = function () {
 return this.length !== 0;
}
$(document).ready( function() {
 var sl;
 $("#mo-select li").click( function(e) {
  $("#mo-select li").removeClass("selected");
  sl = this;
  $(sl).addClass("selected");
  e.stopPropagation();
 });

 $("#mo-up").click( function(e) {
  var p = $(sl).prev("li")
  if ( $(p).exists() ) {
   $(sl).detach();
   $(p).before(sl);
  }
 });
 $("#mo-down").click( function(e) {
  var p = $(sl).next("li");
  if ( $(p).exists() ) {
   $(sl).detach();
   $(p).after(sl);
  }
 });

 $("#mo-save").click( function(e) {
  e.preventDefault();
  var o = "";
  $("#mo-select li").each( function() {
   o += $(this).find("span").attr("id") + ",";
  });
  $("#mo-order").val(o);
  //alert("Saving order:" + $("#mo-order").val() );
  $("#mo-form").submit();
 });

});

function reorderList(sbid,direction)
	{
	// get objects
	var select_box = document.getElementById(sbid);
	var opts = select_box.options;

	// get option indexes from select box and set
	// beginning new index and list limits (depending on direction)
	var sel_ndx = select_box.selectedIndex;
	var new_ndx = sel_ndx;
	var limit = 0;
	if (direction == "u")
		{
		new_ndx--;
		limit = 0;
		}
	else if (direction == "d")
		{
		new_ndx++;
		limit = select_box.length - 1;
		}
	else
		{
		return false;
		}

	// check limits
	if (new_ndx >= select_box.length || new_ndx < 0)
		{
		return false;
		}

	// find an "open" list spot
	var found_spot = false;
	var end_reached = false;
	var i = new_ndx;
	if (opts[sel_ndx].className.match(/[\d]+/))
		{
		// kids can only move to an adjascent spot
		// already occupied by a sibling
		// (note: id = parent_id)
		if (opts[new_ndx].className == opts[sel_ndx].className)
			{
			found_spot = true;
			}
		}
	else
		{
		if (opts[sel_ndx].className == "p")
			{
			if (direction == "d")
				{
				// for parent items, move down past the kids
				new_ndx += parseInt(opts[sel_ndx].id);
				}
			}

		while (found_spot == false && end_reached == false)
			{
			if (opts[i].className == "r")
				{
				// root items can have data before or after
				new_ndx = i;
				found_spot = true;
				}
			else if (opts[i].className == "p")
				{
				// for parents, the kids have to be considered
				if (direction == "d")
					{
					new_ndx += parseInt(opts[i].id);
					}
				else
					{
					new_ndx = i;
					}
				found_spot = true;
				}

			if (direction == "u")
				{
				i--;
				}
			else
				{
				i++;
				}

			if (i == limit)
				{
				end_reachd = true;
				}
			}
		}

	if (found_spot == true)
		{
		if (opts[sel_ndx].className == "p")
			{
			var ndx = sel_ndx;
			var childs = parseInt(opts[sel_ndx].id);
			for (i = 0; i <= childs; i++)
				{
				if (direction == "u")
					{
					ndx = sel_ndx + i;
					}

				// duplicate node, insert in list,
				// and remove original
				var opt = opts[ndx].cloneNode();
				if (direction == "d")
					{
					select_box.add(opt,new_ndx+1);
					select_box.remove(sel_ndx);
					}
				else
					{
					select_box.remove(ndx);
					select_box.add(opt,new_ndx+i);
					}
				}

			if (direction == "d")
				{
				new_ndx -= childs;
				}
			}
		else
			{
			var opt = opts[sel_ndx].cloneNode();
			if (direction == "d")
				{
				select_box.add(opt,new_ndx+1);
				select_box.remove(sel_ndx);
				}
			else
				{
				select_box.add(opt,new_ndx);
				select_box.remove(sel_ndx+1);
				}
			}

		select_box.selectedIndex = new_ndx;
		}
	select_box.focus();
	return true;
	}

function saveOrder(sbid)
	{
	var select_box = document.getElementById(sbid);
	var opts = select_box.options;
	var order = "";
	for (i = 0; i < select_box.length; i++)
		{
		order = order + opts[i].value;
		if (i < (select_box.length - 1))
			order = order + ",";
		}
	return order;
	}

function confDelete()
	{
	return confirm("Delete this event from the database?\nClick 'OK' to delete.");
	}

function confDelSel()
	{
	return confirm("Delete checked events from the database?\nClick 'OK' to delete.");
	}

function checkAll (chk)
	{
	var cl_name = "ec";
	var check_boxes = document.getElementsByClassName(cl_name);
	var row_cl = "nrow";
	if (chk == true)
		{
		row_cl = "chkrow";
		}
	for (var i = 0; i < check_boxes.length; ++i)
		{
		check_boxes[i].checked = chk;
		check_boxes[i].parentNode.parentNode.className = row_cl;
		}
	return true;
	}

function toggleSel (sbid)
	{
	var elem = document.getElementById(sbid);
	if (elem.className == "nrow")
		elem.className = "selrow";
	else if (elem.className == "selrow")
		elem.className = "nrow";
	return true;
	}

function toggleChk (sbid,chk)
	{
	var elem = document.getElementById(sbid);
	if (chk == true)
		elem.parentNode.parentNode.className = "chkrow";
	else
		elem.parentNode.parentNode.className = "selrow";
	return true;
	}

function toggleHelp (hid)
	{
	var sec = document.getElementById(hid);
	if (sec.className == "eh-hidden")
		{
		sec.className = "eh-shown";
		}
	else
		{
		sec.className = "eh-hidden";
		}
	return true;
	}

function toggleTime (chk)
	{
	var stime = document.getElementById ("s-time");
	var etime = document.getElementById ("e-time");
	var tclass = "";
	if (chk == true)
		{
		//alert("Check!");
		tclass = "dpt-hidden";
		}
	else
		{
		//alert("Un-check!");
		tclass = "dpt-shown";
		}
	stime.className = tclass;
	etime.className = tclass;
	return true;
	}
