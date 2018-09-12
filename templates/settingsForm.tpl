{**
 * plugins/generic/allowedUploads/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Allowed Uploads plugin settings
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#clamavSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="clamavSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="clamavSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.clamav.manager.settings.description"}</div>

	{fbvFormArea id="clamavSettingsFormArea"}
		{fbvElement type="text" id="clamavPath" name="clamavPath" value="$clamav" label="plugins.generic.clamav.manager.settings.clamavPath"}
	{/fbvFormArea}

	{fbvFormButtons}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
