{**
* plugins/generic/clamav/settingsForm.tpl
*
* Copyright (c) 2018 University of Pittsburgh
* Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
*
* ClamAV plugin settings
*
*}
<script>
	$(function () {ldelim}
		// Attach the plugin's js
		$.getScript("{$pluginJavascriptURL}clamavVersion.js");
		
		// Attach the form handler.
		$('#clamavSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{* not ideal, but this is way better than trying to load css dynamically into the HEAD tag and then remove it on modal close. *}
<link rel="stylesheet" href="{$pluginStylesheetURL}clamav.css" />

<form class="pkp_form" id="clamavSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}" data-json-href="{$baseUrl}" data-loading-href="{$pluginLoadingImageURL}" data-not-found="{translate key="plugins.generic.clamav.manager.settings.noversion"}" data-network-problem="{translate key="plugins.generic.clamav.manager.settings.networkProblem"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="clamavSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.clamav.manager.settings.description"}</div>

	{fbvFormArea id="clamscanSettingsFormArea" description="plugins.generic.clamav.manager.settings.description"}
		{fbvFormSection id="clamavExecutableArea"}
			{fbvElement type="text" id="clamavPath" name="clamavPath" value="$clamavPath" label="plugins.generic.clamav.manager.settings.clamavPath"}
			<dl>
				<dt>{translate key="plugins.generic.clamav.manager.settings.clamExecutableVersion"}</dt>
				<dd id="clamExecutableVersion"></dd>
			</dl>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="clamdSettingsFormArea" title="plugins.generic.clamav.manager.settings.daemon"}
		{fbvFormSection description="plugins.generic.clamav.manager.settings.daemon.description" list=true}
			{fbvElement type="checkbox" id="clamavUseSocket" name="clamavUseSocket" value="1" checked="$clamavUseSocket" label="plugins.generic.clamav.manager.settings.clamavUseSocket"}
		{/fbvFormSection}
		{fbvFormSection id="clamdSocketArea"}
			{fbvElement type="text" id="clamavSocketPath" name="clamavSocketPath" value="$clamavSocketPath" label="plugins.generic.clamav.manager.settings.clamavSocketPath"}
			<dl>
				<dt>{translate key="plugins.generic.clamav.manager.settings.clamSocketVersion"}</dt>
				<dd id="clamSocketVersion"></dd>
			</dl>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}

</form>
