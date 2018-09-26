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
import('lib.pkp.classes.form.Form');

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

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'clamavPath', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.clamav.manager.settings.clamavPathRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$plugin = $this->_plugin;

		$this->setData('clamavPath', $plugin->getSetting(CONTEXT_SITE, 'clamavPath'));
		$this->setData('clamavVersion', $this->displayVersion($this->getData('clamavPath')));
		$this->setData('clamavUseSocket', $plugin->getSetting(CONTEXT_SITE, 'clamavUseSocket'));
		$this->setData('clamavSocketPath', $plugin->getSetting(CONTEXT_SITE, 'clamavSocketPath'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('clamavPath', 'clamavUseSocket', 'clamavSocketPath'));
		$this->setData('clamavVersion', $this->displayVersion($this->_data['clamavPath']));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$this->_plugin->updateSetting(CONTEXT_SITE, 'clamavPath', $this->getData('clamavPath'), 'string');
		$this->_plugin->updateSetting(CONTEXT_SITE, 'clamavUseSocket', $this->getData('clamavUseSocket'), 'bool');
		$this->_plugin->updateSetting(CONTEXT_SITE, 'clamavSocketPath', $this->getData('clamavSocketPath'), 'string');
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
