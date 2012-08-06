<?php
if (!defined('PBASE_PATH')) die('Hacking attempt!');

/**
 * get the content of web-page, with cache management
 * @param: string url
 * @return: string
 */
function get_file_cached($url)
{
  // html files are cached 10000 seconds
  $cache_id_prefix = str_replace('http://www.pbase.com/', null, $url);
  $cache_id_prefix = preg_replace('#([^a-z0-9-_]+)#i', null, $cache_id_prefix);
  $cache_id = $cache_id_prefix.'-'.substr(time(), 0, -4);
  
  if (!file_exists(PBASE_FS_CACHE.$cache_id))
  {
    $files = glob(PBASE_FS_CACHE.$cache_id_prefix.'-*');
    foreach ($files as $file) unlink($file);
    
    if (($result = download_remote_file($url, PBASE_FS_CACHE.$cache_id)) !== true)
    {
      return $result;
    }
  }
  
  $html = file_get_contents(PBASE_FS_CACHE.$cache_id);
  
  return utf8_encode($html);
}

/**
 * helper to navigate throught the tree
 * @param: &array tree
 * @param: string path
 * @return: reference to a branch of the tree
 */
function &get_current_cat(&$tree, $path)
{
  if (empty($path)) return $tree;
  
  $path = ltrim($path, '/');
  $path = explode('/', $path);
  $current = &$tree[ $path[0] ];
  array_shift($path);
  
  foreach ($path as $folder)
  {
    $current = &$current['categories'][$folder];
  }
  
  return $current;
}

/**
 * check if give account (http://pbase.com/login/root) exists
 * @param: string url
 * @return: bool
 */
function check_account($url)
{
  $html = get_file_cached($url);
  return !(bool)preg_match('#<h2>Unknown Account</h2>#i', $html);
}

/**
 * parse a category page
 * @param: string category url
 * @param: bool parse only sub-categories
 * @param: bool parse only one level
 * @return: array category
 */
function parse_category($url, $path, $cats_only=false, $one_level=false)
{
  $url = str_replace('&page=all', null, $url);
  
  $current = array(
    'url' => $url,
    'path' => null,
    'id' => null,
    );
    
  // id
  $temp = parse_url($url);
  $temp = explode('/', $temp['path']);
  $current['id'] = $temp[ count($temp)-1 ];
  $current['path'] = $path.'/'.$current['id'];
  
  if ($one_level === 'stop')
  {
    return $current;
  }
    
  $current = array_merge($current, array(
    'title' => null,
    'description' => null,
    'pictures' => array(),
    'categories' => array(),
    'nb_pictures' => 0,
    'nb_categories' => 0, 
    ));
    
  
  $url.= '&page=all'; // seriously... (should be ? not &)
  
  
  $html = get_file_cached($url);
  if ($html == 'file_error')
  {
    return $current;
  }
  
  preg_match('#<body>(.*)</body>#is', $html, $matches);
  $body = $matches[1];
  
  // content
  // if (preg_match('#<CENTER>(.*)</CENTER>#is', $body, $matches) === 0) return 'null1';
  if (preg_match_all('#<A HREF="([^">]*)" class="thumbnail">#i', $body, $matches) === 0) return $current;
  $links = $matches[1];
  
  // title
  if (preg_match('#<h2>(.*?)</h2>#i', $body, $matches))
  {
    $current['title'] = trim($matches[1]);
  }
  else
  {
    $current['title'] = $current['id'];
  }
  
  // description
  if (preg_match('#<!-- BEGIN user desc -->(.*?)<!-- END user desc -->#s', $body, $matches))
  {
    $current['description'] = trim($matches[1]);
  }
  
  // sub-cats and pictures
  foreach ($links as $link)
  {
    if (strpos($link, '/image/') !== false)
    {
      if (($image = parse_image($link, $cats_only)) !== null)
      {
        $current['pictures'][ $image['id'] ] = $image;
      }
      $current['nb_pictures']++;
    }
    else
    {
      $next_level = ($one_level === true) ? 'stop' : false;
      if (($category = parse_category($link, $current['path'], $cats_only, $next_level)) !== null)
      {
        $current['categories'][ $category['id'] ] = $category;
      }
      $current['nb_categories']++;
    }
  }
  
  return $current;
}

/**
 * parse a picture page
 * @param: string picture url
 * @param: bool return only url and id
 * @return: array picture
 */
function parse_image($url, $light=false)
{
  $url = preg_replace('#/(small|medium|large|original)$#', null, $url);
  
  $current = array(
    'url' => $url,
    'id' => null,
    );
    
  // id
  $temp = parse_url($url);
  $temp = explode('/', $temp['path']);
  $current['id'] = $temp[ count($temp)-1 ];
  
  if ($light)
  {
    return $current;
  }
  
  $current = array_merge($current, array(
    'title' => null,
    'path' => null,
    'description' => null,
    'date' => null,
    'author' => null,
    'keywords' => array(),
    ));
    
  $url.= '/original';
  
  
  $html = get_file_cached($url);
  if ($html == 'file_error')
  {
    return $current;
  }
  
  preg_match('#<body>(.*)</body>#is', $html, $matches);
  $body = $matches[1];
  
  // path
  if (preg_match('#<IMG ([^>]*)class="display" src="([^">]*)"#i', $body, $matches) ===0) return null;
  $current['path'] = $matches[2];
  
  // title
  preg_match('#<span class="title">(.*?)</span>#i', $body, $matches);
  if (!empty($matches[1]))
  {
    $current['title'] = trim($matches[1]);
    $current['title'] = get_filename_wo_extension($current['title']);
  }
  else
  {
    $current['title'] = $current['id'];
  }
  
  // description
  if (preg_match('#<p class="caption">(.*?)</p>#is', $body, $matches))
  {
    $current['description'] = trim($matches[1]);
  }
  
  // date
  if (preg_match('#<span class=date>(.*?)</span>#i', $body, $matches))
  {
    $current['date'] = trim($matches[1]);
  }
  
  // author
  if (preg_match('#<span class=artist>(.*?)</span>#i', $body, $matches))
  {
    $current['author'] = trim($matches[1]);
  }
  
  
  preg_match('#<head>(.*)</head>#is', $html, $matches);
  $head = $matches[1];
  
  // keywords
  if (preg_match('#<meta name="keywords" content="([^">]*)">#i', $head, $matches))
  {
    $words = explode(',', $matches[1]);
    foreach ($words as $word)
    {
      if (!empty($word)) $current['keywords'][] = trim($word);
    }
  }
  
  return $current;
}

/**
 * print categories tree (list or select)
 * @param: array tree
 * @param: int level
 * @param: string tree type (list|select)
 * @return: string
 */
function print_tree(&$tree, $level=0, $type='list')
{
  $out = '';
  
  if ($type == 'list')
  {
    $out.= '<ul>';
    foreach ($tree as $item)
    {
      $out.= '<li>';
      $out.= '<a href="'.$item['url'].'">'.$item['title'].'</a> ['.$item['nb_pictures'].']';
      if (!empty($item['categories']))
        $out.= print_tree($item['categories'], $level+1, 'list');
      $out.= '</li>';
    }
    $out.= '</ul>';
  }
  else if ($type == 'select')
  {
    $i=0;
    foreach ($tree as $item)
    {
      $out.= '<option value="'.$item['path'].'"';
      if ($level==0 and $i==0) $out.= ' selected="selected"';
      $out.= '>';
      
      $out.= str_repeat('&nbsp;', $level*4);
      $out.= '- '.$item['title'].' ['.$item['nb_pictures'].']';
      
      $out.= '</option>';
      
      if (!empty($item['categories']))
        $out.= print_tree($item['categories'], $level+1, 'select');
        
      $i++;
    }
  }
  
  return $out;
}

/**
 * test if a download method is available
 * @return: bool
 */
if (!function_exists('test_remote_download'))
{
  function test_remote_download()
  {
    return function_exists('curl_init') || ini_get('allow_url_fopen');
  }
}

/**
 * download a remote file
 *  - needs cURL or allow_url_fopen
 *  - take care of SSL urls
 *
 * @param: string source url
 * @param: mixed destination file (if true, file content is returned)
 */
if (!function_exists('download_remote_file'))
{
  function download_remote_file($src, $dest)
  {
    if (empty($src))
    {
      return false;
    }
    
    $return = ($dest === true) ? true : false;
    
    /* curl */
    if (function_exists('curl_init'))
    {
      if (!$return)
      {
        $newf = fopen($dest, "wb");
      }
      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, $src);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-language: en"));
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)');
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
      if (strpos($src, 'https://') !== false)
      {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      }
      if (!$return)
      {
        curl_setopt($ch, CURLOPT_FILE, $newf);
      }
      else
      {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      }
      
      if (($out = curl_exec($ch)) === false)
      {
        return 'file_error';
      }
      
      curl_close($ch);
      
      if (!$return)
      {
        fclose($newf);
        return true;
      }
      else
      {
        return $out;
      }
    }
    /* file get content */
    else if (ini_get('allow_url_fopen'))
    {
      if (strpos($src, 'https://') !== false and !extension_loaded('openssl'))
      {
        return false;
      }
      
      $opts = array(
        'http' => array(
          'method' => "GET",
          'user_agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
          'header' => "Accept-language: en",
        )
      );

      $context = stream_context_create($opts);
      
      if (($file = file_get_contents($src, false, $context)) === false)
      {
        return 'file_error';
      }
      
      if (!$return)
      {
        file_put_contents($dest, $file);
        return true;
      }
      else
      {
        return $file;
      }
    }
    
    return false;
  }
}

/**
 * count pictures and cats in the selected cat
 * @param: &array tree
 * @param: string $path
 * @param: &int nb pictures
 * @param: &int nb categories
 * @param: bool recursive
 * @return: void
 */
function count_pictures_cats(&$tree, $path, &$nb_pictures, &$nb_categories, $recursive=true)
{
  $current = &get_current_cat($tree, $path);
  $nb_pictures+= $current['nb_pictures'];
  $nb_categories++;
  
  if ( $recursive and !empty($current['categories']) )
  {
    foreach ($current['categories'] as $cat)
    {
      count_pictures_cats($tree, $cat['path'], $nb_pictures, $nb_categories, $recursive);
    }
  }
}

/**
 * extract unique values of the specified key in a two dimensional array
 * @param: array
 * @param: mixed key name
 * @return: array
 */
if (!function_exists('array_unique_deep'))
{
  function array_unique_deep(&$array, $key)
  {
    $values = array();
    foreach ($array as $k1 => $row)
    {
      foreach ($row as $k2 => $v)
      {
        if ($k2 == $key)
        {
          $values[ $k1 ] = $v;
          continue;
        }
      }
    }
    return array_unique($values);
  }
}

/**
 * search a string in array values
 * // http://www.strangeplanet.fr/blog/dev/php-une-fonction-pour-rechercher-dans-un-tableau
 * @param string needle
 * @param array haystack
 * @param bool return all instances
 * @param bool search in PCRE mode
 * @return key or array of keys
 */
if (!function_exists('array_pos'))
{
  function array_pos($needle, &$haystack, $match_all=false, $preg_mode=false)
  {
    if ($match_all) $matches = array();
    
    foreach ($haystack as $i => $row)
    {
      if (!is_array($row))
      {
        if (!$preg_mode)
        {
          if (strpos($row, $needle) !== false)
          {
            if (!$match_all) return $i;
            else array_push($matches, $i);
          }
        }
        else
        {
          if (preg_match($needle, $row) === 1)
          {
            if (!$match_all) return $i;
            else array_push($matches, $i);
          }
        }
      }
    }
    
    if ( !$match_all or !count($matches) ) return false;
    return $matches;
  }
}

?>