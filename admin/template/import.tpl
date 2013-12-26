<div class="titrePage">
	<h2>PBase2Piwigo</h2>
</div>


{if $STEP == 'analyze'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.analyze.tpl'}

{else if $STEP == 'config'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.config.tpl'}

{else if $STEP == 'init_import'}
{include file=$PBASE_ABS_PATH|@cat:'admin/template/import.init_import.tpl'}
 
{/if}