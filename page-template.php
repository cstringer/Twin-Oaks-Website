<!doctype html>
<html>
<head>
  <title><?php echo $Section['title']; ?></title>
  <meta name="robots" content="index,follow"/>
  <meta name="description" content="A unique golf facility featuring a driving range, par-3 pitch and putt course, and friendly, professional instruction."/>
  <link rel="stylesheet" type="text/css" href="/css/twinoaks.css"/>
  <link rel="stylesheet" type="text/css" href="/css/to-internal.css"/>
  <link rel="stylesheet" type="text/css" href="/css/colorbox.css"/>
  <script src="/js/jquery-1.8.3.js"></script>
  <script src="/js/jquery.colorbox-min.js"></script>
  <script src="/js/to-jscript.js"></script>
<?php echo file_get_contents (FILE_HEADERS); ?>
 </head>

 <body>

  <div id="titlebar">
    <div class="container">
      <a href="/"><img id="logo" alt="" src="/img/TO-logo.png"></a>
      <span class="info">
        <h1>Twin Oaks Golf Complex</h1>
        <address>
         1326&nbsp;East&nbsp;1900&nbsp;Road&nbsp;&middot;
         Eudora,&nbsp;KS&nbsp;66025&nbsp;&middot;
        <span class="tel">
         (785)&nbsp;542-2844
        </span>
        </address>
      </span>
    </div>
    <div id="menu-btn"><img src="/img/icon-menu.png"></div>
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
