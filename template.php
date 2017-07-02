<?php
/**
 * Site page template
 */
?>
<!doctype html>
<html>

    <head>
    <?php Head();?>

    </head>

    <body>
        <div id="titlebar">
        <?php Titlebar();?>
        </div>

        <div id="nav">
        <?php Nav();?>
        </div>

        <div id="content">
        <?php Content();?>
        </div>

        <div id="footer">
        <?php Footer();?>
        </div>
    </body>

</html>
<?php

/*=== Functions ===*/

function Head() {
    global $CurrentPage, $SiteConfig;
?>
    <title><?php echo $SiteConfig['head']['titlePrefix'];?> <?php echo $CurrentPage['title'];?></title>

    <meta charset="<?php echo $SiteConfig['head']['charset'];?>">
    <meta name="description" content="<?php echo $CurrentPage['description'];?>">
    <meta name="google-site-verification" content="<?php echo $SiteConfig['head']['googleSiteVer'];?>">
    <?php if ($SiteConfig['head']['responsive']) { ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php } ?>

    <link rel="shortcut icon" href="<?php echo $SiteConfig['head']['favicon'];?>">

    <?php foreach ($SiteConfig['head']['stylesheets'] as $css) { ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $css;?>">
    <?php }?>

    <?php foreach ($SiteConfig['head']['scripts'] as $js) { ?>
    <script src="<?php echo $js;?>"></script>
    <?php }?>
<?php
}

/**
 * Print the title bar
 */
function Titlebar() {
    global $SiteConfig;
    if (isset($SiteConfig['titlebar']) && isset($SiteConfig['titlebar']['file']) &&
        file_exists($SiteConfig['titlebar']['file'])) {
        include_once($SiteConfig['titlebar']['file']);
    }
}

/**
 * Print the navigation menu
 */
function Nav() {
    global $Navigation;
?>
<div id="menu-btn"><img src="/img/icon-menu.png"></div>
<ul id="menubar">
<?php foreach ($Navigation as $nav) { NavItem($nav); } ?>
</ul>
<?php
}

/**
 * Print a single navigation item (<li>) and any submenus
 */
function NavItem($nav) {
    $hasChildren = (is_array($nav['children']) && count($nav['children']) > 0);
?>
<li<?php if ($nav['inPath']) echo ' class="cursec"';?>>
    <?php NavLink($nav);?>
    <?php if ($hasChildren) { ?>
    <ul class="submenu">
    <?php foreach ($nav['children'] as $child) { NavItem($child); } ?>
    </ul>
    <?php } ?>
</li>
<?php
}

/**
 * Print the link for a navigation item
 */
function NavLink($nav) {
    $hasChildren = (is_array($nav['children']) && count($nav['children']) > 0);
?>
<a href="<?php echo $nav['href'];?>">
<?php echo $nav['title'];?>
    <?php if ($hasChildren) { ?>
    <img class="down-arrow" src="/img/smenu-icon.png" alt="">
    <?php } ?>
</a>
<?php
}

/**
 * Include the current page content file, if set & exists
 */
function Content() {
    global $CurrentPage;
    if (isset($CurrentPage['file']) && file_exists($CurrentPage['file'])) {
        include_once($CurrentPage['file']);
    }
}

/**
 * Include the site footer content file, if set & exists
 */
function Footer() {
    global $SiteConfig;
    if (isset($SiteConfig['footer']) && isset($SiteConfig['footer']['file']) &&
        file_exists($SiteConfig['footer']['file'])) {
        include_once($SiteConfig['footer']['file']);
    }
}
?>
