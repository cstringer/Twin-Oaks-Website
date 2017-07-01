<?php
/**
 * Constants and definitions configuration
 **/

if ($_SERVER['HTTP_HOST'] != 'twinoaks.minidoc.local')
    {
    define("PATH_ROOT",      "/var/chroot/home/content/16/9298216/html/twinoaks");
    }
else
    {
    define("PATH_ROOT",      "/var/www/twinoaks");
    }
define("PPHP_DIR",       PATH_ROOT . "/php");
define("DOC_ROOT",       PATH_ROOT);
define("DEBUG_MODE",     false);
define("FILE_HEADERS",   "_headers.hts");
define("FILE_FOOTER",    "_footer.hts");
define("FILE_CSS",       "twinoaks.css");
define("DEF_SECTION",    "Home");

define("CAL_YEAR_START", 2012);
define("CAL_YEAR_END",   2020);

define("DEF_TIMEZONE",   'America/Chicago');
define("MAX_EVENT_DESC", 100);

?>
