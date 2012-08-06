{include file='include/colorbox.inc.tpl'}
{include file='include/add_album.inc.tpl'}

{footer_script require='jquery.ui.resizable'}{literal}
jQuery(".categoryList").resizable({
  handles: "s,e,se",
  animate: true,
  autoHide: true,
  ghost: true
});
{/literal}{/footer_script}

<form action="{$F_ACTION}" method="post">
<fieldset>
  <legend>{'Select albums to import'|@translate}</legend>
  
  <select name="categories[]" class="categoryList" multiple="multiple" size="20">
    {$TREE}
  </select>
  
  <b>{'Nb albums'|@translate}</b>: {$nb_categories}<br>
  <b>{'Nb photos'|@translate}</b>: {$nb_pictures}<br>
  
  <br>
  <a href="{$RESET_LINK}" onClick="return confirm('{'Are you sure?'|@translate}');">{'Reset'|@translate}</a>
</fieldset>

<fieldset>
  <legend>{'Configuration'|@translate}</legend>
  
  <p><label><input type="checkbox" name="recursive" value="1" checked="checked"> <b>{'Recursive'|@translate}</b></label></p>
  
  <p id="albumSelectWrapper">
    <b>{'Import in this album'|@translate}:</b>
    <select style="width:400px" name="parent_category" id="albumSelect" size="1">
      <option value="0">------------</option>
      {html_options options=$category_parent_options}
    </select>
    {'... or '|@translate}<a href="#" class="addAlbumOpen" title="{'create a new album'|@translate}">{'create a new album'|@translate}</a>
  </p>
    
  <p>
    <b>{'Fill these fields from pBase datas'|@translate}:</b>
    <label><input type="checkbox" name="fills[]" value="fill_name" checked="checked"> {'Photo name'|@translate}</label>
    <label><input type="checkbox" name="fills[]" value="fill_author" checked="checked"> {'Author'|@translate}</label>
    <label><input type="checkbox" name="fills[]" value="fill_tags" checked="checked"> {'Tags'|@translate}</label>
    <label><input type="checkbox" name="fills[]" value="fill_taken" checked="checked"> {'Creation date'|@translate}</label>
    <label><input type="checkbox" name="fills[]" value="fill_comment" checked="checked"> {'Description'|@translate}</label>
  </p>
</fieldset>

  <p class="bottomButtons">
    <input type="submit" value="{'Begin transfer'|@translate}">    
  </p>
</form>