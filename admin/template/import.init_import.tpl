{combine_script id='jquery.ajaxmanager' load='footer' path='themes/default/js/plugins/jquery.ajaxmanager.js'}
{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_script id='MultiGetSet' load='header' path=$PBASE_PATH|cat:'admin/template/MultiGetSet.js'}
{combine_css path="admin/themes/default/uploadify.jGrowl.css"}

{footer_script require='jquery.ajaxmanager,jquery.jgrowl'}
var errorHead   = '{'ERROR'|@translate|@escape:'javascript'}';
var errorMsg    = '{'an error happened'|@translate|@escape:'javascript'}';
var successHead = '{'Success'|@translate|@escape:'javascript'}';
var errors_final_msg = '{'%1$d errors occured. %2$d albums and %3$d photos added.'|@translate}';

{literal}
// custom classe for counters listening
var MyClass = function(){
  var public = this;
  var private = {};
  
  private.lenght = 0;
  private.errors = 0;
  private.categories = 0;
  private.pictures = 0;
  
  MultiGetSet({
    public: public,
    private: private,
    handler: Observable
  });
};

var queue = new MyClass();

queue.listen("lenght", function(opt){
  if (opt.newValue == 0) {
    $(".loading").css('display', 'none');
    if (queue.get("errors") == 0) {
      $(".infos").css('display', 'block');
    } else {
      errors_final_msg = errors_final_msg.replace('%1$d', queue.get("errors"));
      errors_final_msg = errors_final_msg.replace('%2$d', queue.get("categories"));
      errors_final_msg = errors_final_msg.replace('%3$d', queue.get("pictures"));
      
      $(".warnings").css('display', '').html('<ul><li>'+ errors_final_msg +'</li></ul>');
    }
  }
});
queue.listen("categories", function(opt) {
  $(".nb_categories").html(opt.newValue);
});
queue.listen("pictures", function(opt) {
  $(".nb_pictures").html(opt.newValue);
});
queue.listen("errors", function(opt) {
  $(".nb_errors").html(opt.newValue);
});


var queuedManager = jQuery.manageAjax.create('queued', {
  queue: true,  
  maxRequests: 1
});

function add_cat_to_add_queue(path, parent_id, recursive, fills) {
  queuedManager.add({
    type: 'POST',
    dataType: 'json',
    url: 'ws.php?format=json',
    data: { method: 'pwg.pBase.addCat', path: path, parent_id: parent_id, recursive: recursive },
    success: function(data) {
      if (data['stat'] == 'ok') {
        data = data['result'];
        
        for (i in data['pictures']) {
          queue.increment("lenght");
          add_picture_to_add_queue(data['pictures'][i], data['category_id'], fills);
        }
        if (recursive) {
          for (i in data['categories']) {
            queue.increment("lenght");
            add_cat_to_add_queue(data['categories'][i], data['category_id'], recursive, fills);
          }
        }
        
        jQuery.jGrowl(data['message'], { theme: 'success', header: successHead, life: 2000, sticky: false });
        queue.increment("categories");
      } else {
        jQuery.jGrowl(data['result'], { theme: 'error', header: errorHead, sticky: true });
        queue.increment("errors");
      }
      
      queue.decrement("lenght");
    },
    error: function () {
      jQuery.jGrowl(errorMsg, { theme: 'error', header: errorHead, sticky: true });
      queue.increment("errors");
    }
  });
}

function add_picture_to_add_queue(url, cat_id, fills) {
  queuedManager.add({
    type: 'POST',
    dataType: 'json',
    url: 'ws.php?format=json',
    data: { method: 'pwg.pBase.addImage', url: url, category: cat_id, fills: fills },
    success: function(data) {
      if (data['stat'] == 'ok') {
        jQuery.jGrowl(data['result'], { theme: 'success', header: successHead, life: 2000, sticky: false });
        queue.increment("pictures");
      } else {
        jQuery.jGrowl(data['result'], { theme: 'error', header: errorHead, sticky: true });
        queue.increment("errors");
      }
      
      queue.decrement("lenght");
    },
    error: function () {
      jQuery.jGrowl(errorMsg, { theme: 'error', header: errorHead, sticky: true });
      queue.increment("errors");
    }
  });
}
{/literal}

{foreach from=$categories item=cat}
add_cat_to_add_queue('{$cat}', 0, {$RECURSIVE}, '{$FILLS}');
{/foreach}

queue.set("lenght", {$categories|@count});
{/footer_script}


<div class="infos" style="display:none;">
  <ul>
    <li>{'Completed. %1$d albums and %2$d photos added.'|@translate|sprintf:$nb_categories:$nb_pictures}</li>
  </ul>
</div>
<div class="warnings" style="display:none;">
  <ul>
    <li>hohiho</li>
  </ul>
</div>

<p>
  <b>Nb albums</b>: <span class="nb_categories">0</span>/{$nb_categories}<br>
  <b>Nb pictures</b>: <span class="nb_pictures">0</span>/{$nb_pictures}<br>
  <b>Errors</b>: <span class="nb_errors">0</span><br>
  <br>
  <img class="loading" src="{$PBASE_PATH}admin/template/loader-{$themeconf.name}.gif">
</p>

{* <a href="{$MANAGE_LINK}">{'Manage this set of %d photos'|@translate|sprintf:$nb_pictures}</a> *}