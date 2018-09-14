<?php

/**
 * @file plugins/generic/clamav/ClamavPlugin.inc.php
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @class ClamavPlugin
 * @ingroup plugins_generic_clamav
 *
 * @brief ClamAV plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ClamavPlugin extends GenericPlugin {
    
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {

			// Enable Clam AV's preprocessing of uploaded files
			HookRegistry::register('submissionfilesuploadform::validate', array($this, 'clamscanHandleUpload'));

		}
		return $success;
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.clamav.displayName');
	}

	/**
	 * Get a description of the plugin.
	 * @return String
	 */
	function getDescription() {
		return __('plugins.generic.clamav.description');
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('ClamavSettingsForm');
				$form = new ClamavSettingsForm($this, $context->getId());

				if (Request::getUserVar('test')) {
					$form->readInputData();
				} else if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
                } else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
            default:
                assert(false);
                return false;
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Get the clam version
	 * @param $path string Optional path to check.  If not provided, the current setting value is used.
	 * @return string
	**/
	function getClamVersion($path = '') {
		if ($path === '') {
			$path = $this->getSetting(CONTEXT_SITE, 'clamavPath');
		}
		if (!empty($path) && is_executable($path)) {
			$version = exec($path.' --version');
			if (preg_match('/^ClamAV .*/', $version)) {
				return $version;
			}
		}
		return '';
	}

   	/**
	 * Private helper method to scan a file, returning a virus message if matched
	 * @param $uploadedFileField string The field index in $_FILES[] which references the uploaded file
	 * @return string
	 */
	function _clamscanFile($uploadedFileField) {
        $temp = isset($_FILES[$uploadedFileField]['tmp_name']) == true;
		if ($this->getClamVersion() && !empty($uploadedFileField)  && isset($_FILES[$uploadedFileField]['tmp_name'])) {
			$uploadedFile = $_FILES[$uploadedFileField]['tmp_name'];
			$output = NULL;
			$exitCode = NULL;
			$scan = exec($this->getSetting(CONTEXT_SITE, 'clamavPath').' -i --no-summary '.$uploadedFile, $output, $exitCode);
			// If the scan returned anything, remove the temporary filename and report the error
			if ($exitCode === 1) {
				unlink($uploadedFile);
				unset($_FILES[$uploadedFileField]);
				$scan = str_replace($uploadedFile.': ', '', $scan);
				$user = Request::getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$message = __('plugins.generic.clamav.uploadBlocked', array('virusFound' => $scan));
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => $message));
				return $message;
			}
		}
		return '';
	}

	/**
	 * Hook callback: scan an uploaded file with ClamAV
	 * @see submissionfilesuploadform::validate()
	 */
	function clamscanHandleUpload($hookName, $args) {
        $temp1 = $args[0];
        $temp2 = $args[1];
		$uploadedFileField = $args[0];
		// If we have a valid clamscan and an uploaded file, scan the file
		$result = true;
		$message = $this->_clamscanFile($uploadedFileField);
		if (!empty($message)) {
			// set the pass-by-reference return value
			$args[4] = false;
			// returning true aborts processing
			return true;
		}
		// returning false allows processing to continue
		return false;
	}

}
?>
