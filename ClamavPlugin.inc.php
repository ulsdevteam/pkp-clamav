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
import('plugins.generic.clamav.Clamav');

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
			HookRegistry::register('ArticleFileManager::handleUpload', array(&$this, 'clamscanHandleUpload'));
		}
		return $success;
	}

	/**
	* @see PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		// This is a site-wide plugin.
		return true;
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
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $isSubclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) {
			$pageCrumbs[] = array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins'
			);
			$pageCrumbs[] = array(
				Request::url(null, 'manager', 'plugins', 'generic'),
				'plugins.categories.generic'
			);
		}

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}


	/**
	 * Display verbs for the management interface.
	 * @return array of verb => description pairs
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('manager.plugins.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
	 * @return boolean
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) {
			// If enabling this plugin, go directly to the settings
			if ($verb == 'enable') {
				$verb = 'settings';
			} else {
				return false;
			}
		}

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('ClamavSettingsForm');
				$form = new ClamavSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$user =& Request::getUser();
						import('classes.notification.NotificationManager');
						$notificationManager = new NotificationManager();
						$notificationManager->createTrivialNotification($user->getId());
						Request::redirect(null, 'manager', 'plugins', 'generic');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					if (Request::getUserVar('test')) {
						$form->readInputData();
					} else {
						$form->initData();
					}
					$this->setBreadCrumbs(true);
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
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
		} else {
			$clam = new Clamav(array('clamd_sock' => $path));
			$version = $clam->send('VERSION');
			if ($version) {
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
		if ($this->getClamVersion() && !empty($uploadedFileField)  && isset($_FILES[$uploadedFileField]['tmp_name'])) {
			$uploadedFile = $_FILES[$uploadedFileField]['tmp_name'];
			$output = NULL;
			$exitCode = NULL;
			$path = $this->getSetting(CONTEXT_SITE, 'clamavPath');
			if (is_executable($path)) {
				$scan = exec($this->getSetting(CONTEXT_SITE, 'clamavPath').' -i --no-summary '.$uploadedFile, $output, $exitCode);
			} else {
				$clam = new Clamav(array('clamd_sock' => $path));
				$exitCode = $clam->scan($uploadedFile);
				if ($exitCode) {
					$output = $clam->getMessage();
				}
			}
			// If the scan returned anything, remove the temporary filename and report the error
			if ($exitCode === 1) {
				unlink($uploadedFile);
				unset($_FILES[$uploadedFileField]);
				$scan = str_replace($uploadedFile.': ', '', $scan);
				$user =& Request::getUser();
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
	 * @see ArticleFileManager::handleUpload()
	 */
	function clamscanHandleUpload($hookName, $args) {
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
