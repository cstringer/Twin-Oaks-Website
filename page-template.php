<!doctype html>
<html>
 <head>
  <title><?php echo $Section['title']; ?></title>
  <meta name="robots" content="index,follow"/>
  <meta name="description" content="A unique golf facility featuring a driving range, par-3 pitch and putt course, and friendly, professional instruction."/>
  <link rel="stylesheet" type="text/css" href="/twinoaks.css"/>
  <link rel="stylesheet" type="text/css" href="/to-internal.css"/>
  <link rel="stylesheet" type="text/css" href="/colorbox.css"/>
  <script src="/jquery-1.8.3.js"></script>
  <script src="/jquery.colorbox-min.js"></script>
  <script src="/to-jscript.js"></script>
<?php echo file_get_contents (FILE_HEADERS); ?>
 </head>

 <body>

  <div id="titlebar">
	 <div id="menu-btn"><img src="/img/icon-menu.png"></div>
   <a href="/"><img id="logo" alt="" src="/img/TO-logo.png"></a>
	<span class="info">
	 <h1>Twin Oaks Golf Complex</h1>
	 <address>
	  1326&nbsp;East&nbsp;1900&nbsp;Road &bull;
	  Eudora,&nbsp;KS&nbsp;66025 &bull;
     <span class="tel">
	   (785)&nbsp;542-2844
     </span>
	 </address>
   </span>
  </div>

  <div id="nav"> 
<?php echo idx_build_menu ($Msq, $_GET['sec']); ?>
  </div>

  <div id="content">
<?php echo $ContentHtml; ?>
  </div>

  <div id="footer">
<?php echo file_get_contents (FILE_FOOTER); ?>
  </div>

<?php if (DEBUG_MODE == TRUE) { ?>
  <pre id="dbginfo">
<?php echo idx_build_debug_info(); ?>
  </pre>
<?php } ?>

 </body>

</html>
