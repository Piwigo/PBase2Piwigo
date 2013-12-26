<?php
defined('PBASE_PATH') or die('Hacking attempt!');

include_once(PBASE_PATH.'include/functions.inc.php');

$step = null;

if (!test_remote_download())
{
  $page['errors'][] = l10n('No download method available. PBase2piwigo needs cURL extension or allow_url_fopen=true');
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
    if (isset($_SESSION['pbase_empty_error']))
    {
      $page['errors'][] = l10n('Import queue is empty');
      unset($_SESSION['pbase_empty_error']);
    }
    
    // counters
    $nb_categories = $nb_pictures = 0;
    count_pictures_cats($tree, '/root', $nb_pictures, $nb_categories, true);
    
    // get piwigo categories
    $query = '
SELECT id, name, uppercats, global_rank
  FROM '.CATEGORIES_TABLE.'
;';
    display_select_cat_wrapper($query, array(), 'category_parent_options');
    
    $template->assign(array(
      'nb_categories' => $nb_categories-1, // don't count root
      'nb_pictures' => $nb_pictures,
      'F_ACTION' => PBASE_ADMIN.'-import&amp;action=init_import',
      'RESET_LINK' => PBASE_ADMIN.'-import&amp;action=reset_tree',
      'TREE' => print_tree($tree, 0, 'select'),
      ));
    break;
  }
  
  case 'init_import':
  {
    $categories = $_POST['categories'];
    $nb_categories = $nb_pictures = 0;
    
    if (isset($_POST['recursive']))
    {
      // we don't add "root", only it's children
      if (@$categories[0] == '/root')
      {
        $temp = &get_current_cat($tree, '/root');
        $categories = array_merge($categories, array_values(array_unique_deep($temp['categories'], 'path')));
        $categories = array_unique($categories);
        unset($categories[0]);
      }
      
      // remove duplicate categories (in case of recursive mode)
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
    foreach ($temp_cats as $path)
    {
      count_pictures_cats($tree, $path, $nb_pictures, $nb_categories, isset($_POST['recursive']));
    }
    
    if ($nb_pictures == 0)
    {
      $_SESSION['pbase_empty_error'] = true;
      redirect(PBASE_ADMIN.'-import');
    }
    
    $template->assign(array(
      'nb_pictures' => $nb_pictures,
      'nb_categories' => $nb_categories,
      'categories' => $categories,
      'PARENT_CATEGORY' => $_POST['parent_category'],
      'RECURSIVE' => boolean_to_string(isset($_POST['recursive'])),
      'FILLS' => implode(',', @$_POST['fills']),
      'MANAGE_LINK' => get_root_url().'admin.php?page=batch_manager&amp;cat=recent',
      ));
    break;
  }
}

$template->assign('STEP', $_GET['action']);

$template->set_filename('pbase2piwigo', realpath(PBASE_PATH . 'admin/template/import.tpl'));
