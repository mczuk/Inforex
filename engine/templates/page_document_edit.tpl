{include file="inc_header.tpl"}

{if $subcorpora|@count == 0}
    {capture assign=message}
    <em>New document cannot be added because the corpus does not have any subcorpora.</em> 
    Please create a subcorpus first. <br/>
    Subcorpora can be created by corpus owner or admin on the page <a href="index.php?page=corpus&amp;corpus={$corpus.id}&amp;subpage=subcorpora">Settings &raquo; Subcorpora</a>.
    {/capture}
    {include file="common_message.tpl"}
{else}
	{assign var="action" value="document_add"}
	{assign var="button_text" value="Create new document"}
	{assign var="add_content" value="report_content"}
	{include file="inc_document_metadata_form.tpl"}
{/if}

{include file="inc_footer.tpl"}
