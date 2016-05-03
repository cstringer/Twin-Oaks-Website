var oDoc, sDefTxt;

function initDoc()
	{
	oDoc = document.getElementById("textBox");
	sDefTxt = oDoc.innerHTML;
	if (document.compForm.switchMode.checked)
		{
		setDocMode(true);
		}
	document.execCommand("stylewithcss", false, true);
	}

function formatDoc(sCmd, sValue)
	{
	if (validateMode())
		{
		document.execCommand(sCmd, false, sValue);
		oDoc.focus();
		}
	}

function removeLink()
	{
	if (validateMode())
		{
		document.execCommand("unlink", false, null);
		oDoc.focus();
		}
	}

function confirmSave()
	{
	return confirm("Save info to database?\nClick 'OK' to save.");
	}

function validateMode()
	{
	if (!document.compForm.switchMode.checked)
		{
		return true;
		}
	alert("Uncheck \"Show HTML\".");
	oDoc.focus();
	return false;
	}

function setDocMode(bToSource)
	{
	var oContent;
	if (bToSource)
		{
		oContent = document.createTextNode(oDoc.innerHTML);
		oDoc.innerHTML = "";
		var oPre = document.createElement("pre");
		oDoc.contentEditable = false;
		oPre.id = "sourceText";
		oPre.contentEditable = true;
		oPre.appendChild(oContent);
		oDoc.appendChild(oPre);
		}
	else
		{
		if (document.all)
			{
			oDoc.innerHTML = oDoc.innerText;
			}
		else
			{
			oContent = document.createRange();
			oContent.selectNodeContents(oDoc.firstChild);
			oDoc.innerHTML = oContent.toString();
			}
		oDoc.contentEditable = true;
		document.execCommand("stylewithcss", false, true);
		}
	oDoc.focus();
	}

function addLink()
	{
	var sLnk = prompt('Enter link address','http://');
	if (sLnk && sLnk != '' && sLnk != 'http://')
		{
		formatDoc ('createlink', sLnk);
		}
	return true;
	}

function addImage()
	{
	var sImg = prompt('Enter image address', '');
	if (sImg && sImg != '')
		{
		formatDoc ('insertimage', sImg);
		}
	return true;
	}
