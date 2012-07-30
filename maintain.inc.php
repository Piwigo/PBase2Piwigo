<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

define(
  'pbase2piwigo_default_config', 
  serialize(array(
    ))
  );


function plugin_install() 
{
  global $conf;
  
  // conf_update_param('pbase2piwigo', pbase2piwigo_default_config);
  
  mkdir($conf['data_location'].'pbase_cache/', 0755);
}

function plugin_activate()
{
  global $conf;

  // if (empty($conf['pbase2piwigo']))
  // {
    // conf_update_param('pbase2piwigo', pbase2piwigo_default_config);
  // }
  
  if (!file_exists($conf['data_location'].'pbase_cache/'))
  {
    mkdir($conf['data_location'].'pbase_cache/', 0755);
  }
}

function plugin_uninstall() 
{
  // pwg_query('DELETE FROM `'. CONFIG_TABLE .'` WHERE param = "pbase2piwigo" LIMIT 1;');
  
  rrmdir($conf['data_location'].'pbase_cache/');
}

function rrmdir($dir)
{
  if (!is_dir($dir))
  {
    return false;
  }
  $dir = rtrim($dir, '/');
  $objects = scandir($dir);
  $return = true;
  
  foreach ($objects as $object)
  {
    if ($object !== '.' && $object !== '..')
    {
      $path = $dir.'/'.$object;
      if (filetype($path) == 'dir') 
      {
        $return = $return && rrmdir($path); 
      }
      else 
      {
        $return = $return && @unlink($path);
      }
    }
  }
  
  return $return && @rmdir($dir);
} 

?>