{combine_script id='jquery.ajaxmanager' load='footer' path='themes/default/js/plugins/jquery.ajaxmanager.js'}
{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_css path="admin/themes/default/uploadify.jGrowl.css"}

{footer_script require='jquery.ajaxmanager,jquery.jgrowl'}
var errorHead   = '{'ERROR'|@translate|@escape:'javascript'}';
var errorMsg    = '{'an error happened'|@translate|@escape:'javascript'}';
var successHead = '{'Success'|@translate|@escape:'javascript'}';

{literal}
var queue_lenght = 0;

var queuedManager = jQuery.manageAjax.create('queued', {
  queue: true,  
  maxRequests: 1
});

function add_cat_to_parse_queue(url, path) {
  queuedManager.add({
    type: 'POST',
    dataType: 'json',
    url: 'ws.php?format=json',
    data: { method: 'pwg.pBase.parse', url: url, path: path },
    success: function(data) {
      if (data['stat'] == 'ok') {
        cats = data['result']['categories'];
        for (i in cats) {
          queue_lenght++;
          add_cat_to_parse_queue(cats[i]['url'], cats[i]['path']);
        }
        $('#preview').html($('#preview').html()+data['result']['path']+"\n");
      } else {
        jQuery.jGrowl(data['message'], { theme: 'error', header: errorHead, sticky: true });
        jQuery('.loading').toggle();
      }
      
      queue_lenght--; 
      if (data['stat'] == 'ok' && queue_lenght == 0) {
        window.location.reload();
      }
    },
    error: function () {
      jQuery.jGrowl(errorMsg, { theme: 'error', header: errorHead, sticky: true });
      jQuery('.loading').toggle();
    }
  });
}
{/literal}

jQuery('#begin_analyse').click(function() {ldelim}
  login = $('#pbase_login').val();
  
  if (login != '') {ldelim}
    queue_lenght++;
    add_cat_to_parse_queue('http://www.pbase.com/'+ login +'/root', '/root');
    jQuery('.loading').toggle();
  }
  return false;
});
{/footer_script}


<form action="{$PBASE_ADMIN}-import" method="post">
<p><label>{'PBase login'|@translate} <input type="text" id="pbase_login"></label></p>
<p class="loading"><input type="submit" id="begin_analyse" value="{'Begin analyse'|@translate}"></p>
<p class="loading" style="display:none;"><img src="{$PBASE_PATH}admin/template/loader-{$themeconf.name}.gif"></p>
</form>

<pre id="preview">
</pre>