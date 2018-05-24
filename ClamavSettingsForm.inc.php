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

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 */
	function ClamavSettingsForm(&$plugin) {
		$this->plugin =& $plugin;
		
		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'clamavPath', 'required', 'plugins.generic.clamav.manager.settings.clamavPathRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$plugin =& $this->plugin;

		$this->_data = array();
		$this->_data['clamavPath'] = $plugin->getSetting(CONTEXT_SITE, 'clamavPath');
		$this->_data['clamversion'] = $this->displayVersion($this->_data['clamavPath']);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('clamavPath'));
		$this->_data['clamversion'] = $this->displayVersion($this->_data['clamavPath']);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$plugin->updateSetting(CONTEXT_SITE, 'clamavPath', $this->getData('clamavPath'), 'string');
	}

	/**
	 * Get a version for display from a clamscan path
	 * @param $path string
	 * @return string
	 */
	function displayVersion($path) {
		$plugin =& $this->plugin;
		$version = $plugin->getClamVersion($path);
		if ($version === '') {
			$version = __('plugins.generic.clamav.manager.settings.noversion');
		}
		return $version;
	}
	
}

?>
