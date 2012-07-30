<?php
if (!defined('PBASE_PATH')) die('Hacking attempt!');

function pbase_add_ws_method($arr)
{
  $service = &$arr[0];
  
  $service->addMethod(
    'pwg.pBase.addImage',
    'ws_pBase_add_image',
    array(
      'url' => array(),
      'category' => array('default' => null),
      'fills' => array('default' => 'fill_name,fill_taken,fill_author,fill_comment,fill_tags'),
      ),
    'Used by PBase2Piwigo'
    );
    
  $service->addMethod(
    'pwg.pBase.addCat',
    'ws_pBase_add_cat',
    array(
      'path' => array(),   
      'parent_id' => array('default' => null),
      'recursive' => array('default' => true),
      ),
    'Used by PBase2Piwigo'
    );
    
  $service->addMethod(
    'pwg.pBase.parse',
    'ws_pBase_parse',
    array(
      'url' => array(),
      'path' => array(),
      ),
    'Used by PBase2Piwigo'
    );
}

/**
 * pBase parse category
 */
function ws_pBase_parse($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Forbidden');
  }
  
  if (empty($params['path']) or empty($params['url']))
  {
    return new PwgError(403, l10n('Missing parameters'));
  }
  
  include_once(PBASE_PATH.'include/functions.inc.php');
  
  if (test_remote_download() === false)
  {
    return new PwgError(null, l10n('No download method available'));
  }
  if ($params['path'] == '/root' and check_account($params['url']) == false)
  {
    return new PwgError(null, l10n('Invalid user id'));
  }
  
  // get current tree
  $tree_file = PBASE_FS_CACHE.'tree.json';
  if (file_exists($tree_file))
  {
    $tree = json_decode(file_get_contents($tree_file), true);
  }
  else
  {
    $tree = array(
      'root' => array(),
      );
  }
  // parse sub-tree
  $current = &get_current_cat($tree, $params['path']);
  $path = substr($params['path'], 0, strrpos($params['path'], '/'));
  $current = parse_category($params['url'], $path, true, true);
  
  // save tree
  file_put_contents($tree_file, json_encode($tree));
  
  // return datas for AJAX queue
  return $current;
}

/**
 * pBase add category
 */
function ws_pBase_add_cat($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Forbidden');
  }
  
  if (empty($params['path']))
  {
    return new PwgError(null, l10n('Missing parameters'));
  }
  
  include_once(PBASE_PATH.'include/functions.inc.php');
  
  // get current tree
  $tree_file = PBASE_FS_CACHE.'tree.json';
  $tree = json_decode(file_get_contents($tree_file), true);
  $category = &get_current_cat($tree, $params['path']);
  
  if ($category['path'] == '/root')
  {
    $category['title'] = 'Import PBase';
  }
  
  // add category
  $query = '
SELECT id, name
  FROM '.CATEGORIES_TABLE.'
  WHERE name = "'.$category['title'].' <!--pbase-->"
;';
  $result = pwg_query($query);
  
  if (pwg_db_num_rows($result))
  {
    list($category_id) = pwg_db_fetch_row($result);
  }
  else
  {
    $category_id = ws_categories_add(array(
      'name' => $category['title'].' <!--pbase-->',
      'parent' => $params['parent_id'],
      'comment' => $category['description'],
      ), $service);
    $category_id = $category_id['id'];
  }
  
  // return datas for AJAX queue
  $output = array(
    'category_id' => $category_id,
    'pictures' => array(),
    'categories' => array(),
    'message' => sprintf(l10n('Album "%s" added'), $category['title']),
    );
    
  foreach ($category['pictures'] as &$pict)
  {
    array_push($output['pictures'], $pict['url']);
  }
  if ($params['recursive'])
  {
    foreach ($category['categories'] as &$cat)
    {
      array_push($output['categories'], $cat['path']);
    }
  }
  
  return $output;
}

/**
 * pBase add picture
 */
function ws_pBase_add_image($params, &$service)
{
  if (!is_admin())
  {
    return new PwgError(401, 'Forbidden');
  }
  
  if (empty($params['url']))
  {
    return new PwgError(403, l10n('Missing parameters'));
  }
  
  include_once(PBASE_PATH.'include/functions.inc.php');
  include_once(PHPWG_ROOT_PATH . 'admin/include/functions.php');
  include_once(PHPWG_ROOT_PATH . 'admin/include/functions_upload.inc.php');
    
  // photo infos
  $photo = parse_image($params['url'], false);
  if (empty($photo))
  {
    return new PwgError(null, l10n('Invalid picture'));
  }
  
  $photo['local_path'] = PBASE_FS_CACHE . 'pbase-'.$photo['id'].'.'.get_extension($photo['path']);
  
  $query = '
SELECT id
  FROM '.IMAGES_TABLE.'
  WHERE file = "'.basename($photo['local_path']).'"
;';
  $result = pwg_query($query);
  
  if (pwg_db_num_rows($result))
  {
    list($photo['image_id']) = pwg_db_fetch_row($result);
    associate_images_to_categories(array($photo['image_id']), array($params['category']));
  }
  else
  {
    // copy file
    if (download_remote_file($photo['path'], $photo['local_path']) === false)
    {
      return new PwgError(500, l10n('No download method available'));
    }
    
    // add photo
    $photo['image_id'] = add_uploaded_file($photo['local_path'], basename($photo['local_path']), array($params['category']));
  
    // do some updates
    if (!empty($params['fills']))
    {
      $params['fills'] = rtrim($params['fills'], ',');
      $params['fills'] = explode(',', $params['fills']);
    
      $updates = array();
      if (in_array('fill_name', $params['fills']))    $updates['name'] = pwg_db_real_escape_string($photo['title']); 
      if (in_array('fill_posted', $params['fills']))  $updates['date_available'] = date('Y-d-m H:i:s', strtotime($photo['date']));
      if (in_array('fill_taken', $params['fills']))   $updates['date_creation'] = date('Y-d-m H:i:s', strtotime($photo['date']));
      if (in_array('fill_author', $params['fills']))  $updates['author'] = pwg_db_real_escape_string($photo['author']);
      if (in_array('fill_comment', $params['fills'])) $updates['comment'] = pwg_db_real_escape_string($photo['description']);
      
      if (count($updates))
      {
        single_update(
          IMAGES_TABLE,
          $updates,
          array('id' => $photo['image_id'])
          );
      }
      
      if ( !empty($photo['keywords']) and in_array('fill_tags', $params['fills']) )
      {
        $raw_tags = implode(',', $photo['keywords']);
        set_tags(get_tag_ids($raw_tags), $photo['image_id']);
      }
    }
  }
  
  return sprintf(l10n('Photo "%s" imported'), $photo['title']);
}

?>