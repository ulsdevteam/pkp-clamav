{**
 * plugins/generic/clamav/settingsForm.tpl
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * ClamAV plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.clamav.manager.clamavSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="clamavSettings">
<div id="description">{translate key="plugins.generic.clamav.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}
<table width="100%" class="data">
	<tr valign="top" id="clamavPath">
		<td width="20%" class="label">{fieldLabel name="clamavPath" key="plugins.generic.clamav.manager.settings.clamavPath" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="clamavPath" id="clamavPath" value="{$clamavPath|escape}" size="25" class="textField" /></td>
	</tr>
	<tr valign="top" id="clamavVersion">
		<td width="20%" class="label">{translate key="plugins.generic.clamav.manager.settings.version"}</td>
		<td width="80%" class="value">{$clamversion|escape}</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
<input type="submit" name="test" class="button" value="{translate key="plugins.generic.clamav.manager.settings.test"}"/>
<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
