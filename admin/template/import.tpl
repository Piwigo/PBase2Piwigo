{combine_css path=$PBASE_PATH|@cat:"admin/template/style.css"}


<div class="titrePage">
	<h2>PBase2Piwigo</h2>
</div>


{if $STEP == 'analyze'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.analyze.tpl'}

{elseif $STEP == 'config'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.config.tpl'}

{elseif $STEP == 'init_import'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.init_import.tpl'}
 
{/if}