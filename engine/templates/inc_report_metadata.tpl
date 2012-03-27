<h1>General metadata</h1>

<table class="tablesorter" cellspacing="1">
    <tr>
        <th style="width: 100px">Title</th>
        <td>{$row.title}</td>
    </tr>
</table>

<form method="POST">

<h1>Custom metadata</h1>
<table class="tablesorter" cellspacing="1">
    {foreach from=$features item=f}
    <tr>
        <th style="width: 100px">{$f.title}</th>
        <td><input type="text" name="ext_{$f.name}" style="width: 99%" value="{$f.value}"/></td>
    </tr>
    {/foreach}
</table>

<input type="submit" value="Save" style="margin: 5px; padding: 5px 15px"/>
<input type="hidden" name="action" value="metadata_save"/>
<input id="report_id" type="hidden" name="report_id" value="{$row.id}">
</form>
