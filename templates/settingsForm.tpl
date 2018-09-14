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
		{fbvElement type="text" id="clamavPath" name="clamavPath" value="$clamavPath" label="plugins.generic.clamav.manager.settings.clamavPath"}
	{/fbvFormArea}
    
    <div id=clamVersion">
        <label for="clamavVersion">{translate key="plugins.generic.clamav.manager.settings.version"}</label>
        <input disabled="disabled" type="text" id="clamavVersion" value="{$clamavVersion}" />
        <input type="submit" name="test" value="{translate key="plugins.generic.clamav.manager.settings.test"}" />
    </div>

	{fbvFormButtons}

    
</form>
