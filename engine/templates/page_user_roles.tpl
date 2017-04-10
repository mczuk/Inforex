{*
 * Part of the Inforex project
 * Copyright (C) 2013 Michał Marcińczuk, Jan Kocoń, Marcin Ptak
 * Wrocław University of Technology
 * See LICENCE 
 *}
 
{include file="inc_header2.tpl"}

<div class="panel panel-primary">
	<div class="panel-heading">Your profile</div>
	<div class="panel-body">

		{include file="inc_system_messages.tpl"}

		<div class="panel panel-default">
			<div class="panel-heading">Your roles</div>
			<div class="panel-body">
				<ul>
				{foreach from=$user.role item=description key=role}
					<li><b>{$role}</b> &mdash; {$description}</li>
				{foreachelse}
					<li><i>brak</i></li>
				{/foreach}
				</ul>
			</div>
		</div>


		{if $user.role.loggedin}
		<div class="panel panel-default">
			<div class="panel-heading">Options</div>
			<div class="panel-body">
				<ul>
					<li><a class="option" id="password_change_form" href="#">Password change</a></li>
				</ul>
				<form  class="password_change_form" style="padding: 5px; background: #eee;  margin: 10px; width: 400px; display: none" action="index.php?page=user_roles" method="post">
					<input type="hidden" name="action" value="user_password_change"/>
					<table border="0" width="450">
						<tr>
							<td class="tekst"> Old password: </td>
							<td><input class="password_change" type="password" name="old_pass" size="20" /></td>
						</tr>
						<tr>
							<td class="tekst"> New password: </td>
							<td><input class="password_change" type="password" name="new_pass1" size="20" maxlength="20"/></td>
						</tr>
						<tr>
							<td class="tekst"> Repeat new password: </td>
							<td><input class="password_change" type="password" name="new_pass2" size="20" maxlength="20"/></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td align="center"><input type="submit" class="password_change" value="Change password" disabled="disabled" /></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		{/if}
	</div>
</div>

{include file="inc_footer.tpl"}
