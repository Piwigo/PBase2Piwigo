<?php
defined('PBASE_PATH') or die('Hacking attempt!');

global $template, $page, $conf;

load_language('plugin.lang', PBASE_PATH);

if (!file_exists(PBASE_FS_CACHE))
{
  mkdir(PBASE_FS_CACHE, 0755);
}

// include page
include(PBASE_PATH . 'admin/import.php');

// template
$template->assign(array(
  'PBASE_PATH'=> PBASE_PATH,
  'PBASE_ABS_PATH'=> dirname(__FILE__).'/',
  'PBASE_ADMIN' => PBASE_ADMIN,
  ));

$template->assign_var_from_handle('ADMIN_CONTENT', 'pbase2piwigo');
