<?php 
/*
Plugin Name: PBase2Piwigo
Version: auto
Description: Import photos from PBase
Plugin URI: auto
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

if (basename(dirname(__FILE__)) != 'pbase2piwigo')
{
  add_event_handler('init', 'pbase_error');
  function pbase_error()
  {
    global $page;
    $page['errors'][] = 'PBase2Piwigo folder name is incorrect, uninstall the plugin and rename it to "pbase2piwigo"';
  }
  return;
}

global $conf;

define('PBASE_PATH',     PHPWG_PLUGINS_PATH . 'pbase2piwigo/');
define('PBASE_ADMIN',    get_root_url() . 'admin.php?page=plugin-pbase2piwigo');
define('PBASE_FS_CACHE', PHPWG_ROOT_PATH . $conf['data_location'] . 'pbase_cache/');


include_once(PBASE_PATH . 'include/ws_functions.inc.php');


add_event_handler('ws_add_methods', 'pbase_add_ws_method');

if (defined('IN_ADMIN'))
{
  add_event_handler('get_admin_plugin_menu_links', 'pbase_admin_menu');

  add_event_handler('get_batch_manager_prefilters', 'pbase_add_batch_manager_prefilters');
  add_event_handler('perform_batch_manager_prefilters', 'pbase_perform_batch_manager_prefilters', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);

  function pbase_admin_menu($menu) 
  {
    $menu[] = array(
      'NAME' => 'PBase2Piwigo',
      'URL' => PBASE_ADMIN,
      );
    return $menu;
  }

  function pbase_add_batch_manager_prefilters($prefilters)
  {
    $prefilters[] = array(
      'ID' => 'pbase',
      'NAME' => l10n('Imported from PBase'),
      );
    return $prefilters;
  }

  function pbase_perform_batch_manager_prefilters($filter_sets, $prefilter)
  {
    if ($prefilter == 'pbase')
    {
      $query = '
  SELECT id
    FROM '.IMAGES_TABLE.'
    WHERE file LIKE "pbase-%"
  ;';
      $filter_sets[] = array_from_query($query, 'id');
    }

    return $filter_sets;
  }
}
