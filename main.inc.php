<?php 
/*
Plugin Name: PBase2Piwigo
Version: auto
Description: Extension for importing pictures from PBase
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf;

define('PBASE_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
define('PBASE_ADMIN', get_root_url() . 'admin.php?page=plugin-' . basename(dirname(__FILE__)));
define('PBASE_FS_CACHE', $conf['data_location'].'pbase_cache/');

if (defined('IN_ADMIN'))
{
  add_event_handler('get_admin_plugin_menu_links', 'pbase_admin_menu');

  function pbase_admin_menu($menu) 
  {
    array_push($menu, array(
      'NAME' => 'PBase2Piwigo',
      'URL' => PBASE_ADMIN,
    ));
    return $menu;
  }
}

include_once(PBASE_PATH . 'include/ws_functions.inc.php');

add_event_handler('ws_add_methods', 'pbase_add_ws_method');

?>