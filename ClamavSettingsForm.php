<?php

/**
 * @file plugins/generic/clamav/ClamavSettingsForm.inc.php
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @class ClamavSettingsForm
 * @ingroup plugins_generic_clamav
 *
 * @brief Form for the site admin to modify Clam AV plugin settings
 */

namespace APP\plugins\generic\clamav;

use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;
use PKP\core\PKPApplication;
use APP\core\Application;
use APP\template\TemplateManager;

class ClamavSettingsForm extends Form {

	/** @var int */
	var $_contextId;

	/** @var object */
	var $_plugin;
	
	/**
	 * Constructor
	 * @param $plugin ClamavSettingsForm
	 * @param $contextId int
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;
		
		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
		
		$this->addCheck(new FormValidator($this, 'clamavPath', FormValidator::FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.clamav.manager.settings.clamavPathRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$plugin = $this->_plugin;
		$request = Application::get()->getRequest();
		$basePluginUrl = $request->getBaseUrl() . DIRECTORY_SEPARATOR . $plugin->getPluginPath() . DIRECTORY_SEPARATOR;

		$this->setData('clamavPath', $plugin->getSetting(PKPApplication::CONTEXT_SITE, 'clamavPath'));
		$this->setData('clamavUseSocket', $plugin->getSetting(PKPApplication::CONTEXT_SITE, 'clamavUseSocket'));
		$this->setData('clamavSocketPath', $plugin->getSetting(PKPApplication::CONTEXT_SITE, 'clamavSocketPath'));
		$this->setData('unscannedFileOption', $plugin->getSetting(PKPApplication::CONTEXT_SITE, 'allowUnscannedFiles'));
		$this->setData('clamavSocketTimeout', $plugin->getSetting(PKPApplication::CONTEXT_SITE, 'clamavSocketTimeout'));


		$this->setData('pluginJavascriptURL', $basePluginUrl . 'js' . DIRECTORY_SEPARATOR);
		$this->setData('pluginStylesheetURL', $basePluginUrl . 'css' . DIRECTORY_SEPARATOR);
		$this->setData('pluginLoadingImageURL', $basePluginUrl . 'images' . DIRECTORY_SEPARATOR . "spinner.gif");
		$this->setData('pluginLoadingImageURL', $basePluginUrl . 'images' . DIRECTORY_SEPARATOR . "spinner.gif");
		$this->setData('pluginAjaxUrl', $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'clamav', 'clamavVersion'));

		$this->setData('baseUrl', $request->getBaseUrl());
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('clamavPath', 'clamavUseSocket', 'clamavSocketPath', 'clamavSocketTimeout', 'allowUnscannedFiles',));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = NULL, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());

		$unscannedFileOptions = array(
			'allow' => __('plugins.generic.clamav.manager.settings.allowUnscannedFiles.allow'),
			'block' => __('plugins.generic.clamav.manager.settings.allowUnscannedFiles.block')
		);
		$templateMgr->assign('unscannedFileOptions', $unscannedFileOptions);

		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute(...$functionArgs) {
		// set defaults if we have them. Not using built-in validation because
		// these fields aren't mandatory.
		$clamavSocketTimeout = $this->getData('clamavSocketTimeout');
		if((int) $clamavSocketTimeout < 1) {
			$clamavSocketTimeout = $this->_plugin::TIMEOUT_DEFAULT;
		}
		$allowUnscannedFiles = $this->getData('allowUnscannedFiles');
		if((string) $allowUnscannedFiles !== $this->_plugin::UNSCANNED_ALLOW && (string) $allowUnscannedFiles !== $this->_plugin::UNSCANNED_BLOCK) {
			$allowUnscannedFiles = $this->_plugin::UNSCANNED_DEFAULT;
		}

		$this->_plugin->updateSetting(PKPApplication::CONTEXT_SITE, 'clamavPath', $this->getData('clamavPath'), 'string');
		$this->_plugin->updateSetting(PKPApplication::CONTEXT_SITE, 'clamavUseSocket', $this->getData('clamavUseSocket'), 'bool');
		$this->_plugin->updateSetting(PKPApplication::CONTEXT_SITE, 'clamavSocketPath', $this->getData('clamavSocketPath'), 'string');
		$this->_plugin->updateSetting(PKPApplication::CONTEXT_SITE, 'clamavSocketTimeout', $clamavSocketTimeout, 'int');
		$this->_plugin->updateSetting(PKPApplication::CONTEXT_SITE, 'allowUnscannedFiles', $allowUnscannedFiles, 'string');
	}

	/**
	 * Get a version for display from a clamscan path
	 * @param $path string
	 * @return string
	 */
	function displayVersion($path) {
		$plugin = $this->_plugin;
		$version = $plugin->getClamVersion($path);
		if ($version === '') {
			$version = __('plugins.generic.clamav.manager.settings.noversion');
		}
		return $version;
	}

}

?>
