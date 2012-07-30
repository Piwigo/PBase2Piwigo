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
  <legend>{'Choose albums to import'|@translate}</legend>
  
  <select name="categories[]" class="categoryList" multiple="multiple" size="20">
    {$TREE}
  </select>
  
  <br>
  <a href="{$RESET_LINK}" onClick="return confirm('{'Are you sure?'|@translate}');">Reset</a>
</fieldset>

<fieldset>
  <legend>{'Configuration'|@translate}</legend>
  
  <p><label><input type="checkbox" name="recursive" value="1" checked="checked"> <b>Recursive</b></label></p>
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
    <input type="submit" value="Begin Transfer">    
  </p>
</form>