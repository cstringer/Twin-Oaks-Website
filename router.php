<?php

/*=== Definitions ===*/
define("SITE_CONFIG",   "site.json");
define("PAGE_TEMPLATE", "template.php");


/*=== Globals ===*/
$CurrentPage = null;
$Navigation = array();
$SiteConfig = null;


/*=== Main ===*/

// load site config file
if (!file_exists(SITE_CONFIG)) {
    exit("Missing site config file");
}

// load, parse, validate site config
$SiteConfig = json_decode(file_get_contents(SITE_CONFIG), true);
if (!is_array($SiteConfig) || count ($SiteConfig['pages']) == 0) {
    exit("Error loading site config");
}
validate_site_config();

// parse site pages
foreach ($SiteConfig['pages'] as $page) {
    validate_page_props($page);
    parse_page($page);
}

// do 404 if not found
if ($CurrentPage == null) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    $CurrentPage = $SiteConfig['404'];
}

// interpolate page template
include_once(PAGE_TEMPLATE);

// bow out gracefully
exit(0);


/*=== Functions ===*/

/**
 * Extract navigation from site config, determine current page
 */
function parse_page($page, &$nav_parent = null) {
    global $CurrentPage, $Navigation;

    // check required params
    if (!isset($page['urlAlias']) || !isset($page['title'])) {
        return;
    }

    // true = page is in navigation path (it or a child is current page)
    $in_nav_path = false;

    // determine page, current path
    if (stristr($_SERVER['REQUEST_URI'], $page['urlAlias']) !== false) {
        // only match '/' if current page is home
        if (($page['urlAlias'] == '/' && $_SERVER['REQUEST_URI'] == '/') ||
            ($page['urlAlias'] != '/')) { 
            $in_nav_path = true;
        }
        // if the URI and alias exactly match, it's the current page
        if (strcasecmp($_SERVER['REQUEST_URI'], $page['urlAlias']) == 0) {
            $CurrentPage = $page;
        }
    }

    // build navigation entry
    $nav_data = array(
        'title' => $page['title'],
        'href' => $page['urlAlias'],
        'inPath' => $in_nav_path
    );

    // if there are child pages, recurse
    if (is_array($page['children']) && count($page['children'] > 0)) {
        $nav_data['children'] = array();
        foreach ($page['children'] as $p) {
            parse_page($p, $nav_data);
        }
    }

    // add to correct navigation parent
    if ($nav_parent) {
        array_push($nav_parent['children'], $nav_data);
    } else {
        array_push($Navigation, $nav_data);
    }
}

/**
 * Set site config values to defaults if missing 
 */
function validate_site_config() {
    global $SiteConfig;
    if (!is_array($SiteConfig)) {
        return;
    }

    // head
    if (!is_array($SiteConfig['head'])) {
        $SiteConfig['head'] = array();
    }
    if (!isset($SiteConfig['head']['charset'])) {
        $SiteConfig['head']['charset'] = 'utf-8';
    }
    if (!isset($SiteConfig['head']['favicon'])) {
        $SiteConfig['head']['favicon'] = '';
    }
    if (!isset($SiteConfig['head']['googleSiteVer'])) {
        $SiteConfig['head']['googleSiteVer'] = '';
    }
    if (!is_array($SiteConfig['head']['scripts'])) {
        $SiteConfig['head']['scripts'] = array();
    }
    if (!is_array($SiteConfig['head']['stylesheets'])) {
        $SiteConfig['head']['stylesheets'] = array();
    }
    if (!isset($SiteConfig['head']['titlePrefix'])) {
        $SiteConfig['head']['titlePrefix'] = '';
    }

    // footer
    if (!is_array($SiteConfig['footer'])) {
        $SiteConfig['footer'] = array();
    }
    if (!isset($SiteConfig['footer']['file'])) {
        $SiteConfig['footer']['file'] = '';
    }
}

/**
 * Set page properties to defaults if missing 
 */
function validate_page_props(&$page) {
    if (!isset($page['title'])) {
        $page['title'] = '';
    }
    if (!isset($page['description'])) {
        $page['description'] = '';
    }
    if (!isset($page['file'])) {
        $page['file'] = '';
    }
    if (!isset($page['urlAlias'])) {
        $page['urlAlias'] = '';
    }
}

?>
