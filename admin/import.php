<?php
if (!defined('PBASE_PATH')) die('Hacking attempt!');

include_once(PBASE_PATH.'include/functions.inc.php');

$step = null;

if (test_remote_download() === false)
{
  array_push($page['errors'], l10n('No download method available. PBase2piwigo needs cURL extension or allow_url_fopen=true'));
}
else
{
  if (isset($_GET['action']))
  {
    switch ($_GET['action'])
    {
      case 'reset_tree':
        unlink(PBASE_FS_CACHE.'tree.json');
        redirect(PBASE_ADMIN.'-import');
        break;
    }
  }
  else
  {
    if (!file_exists(PBASE_FS_CACHE.'tree.json'))
    {
      $_GET['action'] = 'analyze';
    }
    else
    {
      $_GET['action'] = 'config';
    }
  }
  
  if (file_exists(PBASE_FS_CACHE.'tree.json'))
  {
    $tree = json_decode(file_get_contents(PBASE_FS_CACHE.'tree.json'), true);
  }
}


switch ($_GET['action'])
{
  case 'analyze':
  {
    break;
  }
  
  case 'config':
  {
    $template->assign(array(
      'F_ACTION' => PBASE_ADMIN.'-import&amp;action=init_import',
      'RESET_LINK' => PBASE_ADMIN.'-import&amp;action=reset_tree',
      'TREE' => print_tree($tree, 0, 'select'),
      ));
    break;
  }
  
  case 'init_import':
  {
    $categories = $_POST['categories'];
    
    // remove duplicate categories (in case of recursive mode)
    if (isset($_POST['recursive']))
    {
      foreach ($categories as &$path)
      {
        if ( ($matches = array_pos('#'.$path.'/([\w/]+)#', $categories, true, true)) !== false)
        {
          foreach ($matches as $i) unset($categories[$i]);
        }
      }
      unset($path);
    }
    
    // count pictures and cats
    $temp_cats = $categories;
    $nb_categories = $nb_pictures = 0;
    foreach ($temp_cats as $path)
    {
      count_pictures_cats($tree, $path, $nb_pictures, $nb_categories, isset($_POST['recursive']));
    }
    
    $template->assign(array(
      'nb_pictures' => $nb_pictures,
      'nb_categories' => $nb_categories,
      'categories' => $categories,
      'RECURSIVE' => boolean_to_string(isset($_POST['recursive'])),
      'FILLS' => implode(',', @$_POST['fills']),
      'MANAGE_LINK' => get_root_url().'admin.php?page=batch_manager&amp;cat=recent',
      ));
    break;
  }
}

$template->assign('STEP', $_GET['action']);

$template->set_filename('pbase2piwigo', dirname(__FILE__) . '/template/import.tpl');

?>