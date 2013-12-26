{combine_script id='jquery.ajaxmanager' load='footer' path='themes/default/js/plugins/jquery.ajaxmanager.js'}
{combine_script id='jquery.jgrowl' load='footer' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_css path="themes/default/js/plugins/jquery.jGrowl.css"}

{footer_script require='jquery.ajaxmanager,jquery.jgrowl'}
(function($){
  var queue_lenght = 0;

  var queuedManager = $.manageAjax.create('queued', {
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
        }
        else {
          $.jGrowl(data['message'], {
            theme: 'error', sticky: true,
            header: '{'ERROR'|translate}'
          });
          $('.loading').toggle();
        }
        
        queue_lenght--; 
        if (data['stat'] == 'ok' && queue_lenght == 0) {
          window.location.reload();
        }
      },
      error: function () {
        $.jGrowl('{'an error happened'|translate|escape:javascript}', {
          theme: 'error', sticky: true,
          header: '{'ERROR'|translate}'
        });
        $('.loading').toggle();
      }
    });
  }

  $('#begin_analyse').click(function() {
    login = $('#pbase_login').val();
    
    if (login != '') {
      queue_lenght++;
      add_cat_to_parse_queue('http://www.pbase.com/'+ login +'/root', '/root');
      $('.loading').toggle();
    }
    return false;
  });
}(jQuery));
{/footer_script}


<form action="{$PBASE_ADMIN}-import" method="post">
  <p><label>{'PBase login'|translate} <input type="text" id="pbase_login"></label></p>
  <p class="loading"><input type="submit" id="begin_analyse" value="{'Begin analyse'|translate}"></p>
  <p class="loading" style="display:none;"><img src="{$PBASE_PATH}admin/template/loader-{$themeconf.name}.gif"></p>
</form>

<pre id="preview" style="margin-left:10px;">
</pre>