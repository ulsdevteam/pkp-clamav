<?php

/**
 * @file plugins/generic/clamav/ClamavVersionHandler.inc.php
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @class ClamavVersionHandler
 * @ingroup plugins_generic_clamav
 *
 * @brief ClamAV version handler class.
 *
 * @class ClamavVersionHandler
 * @ingroup plugins_generic_clamavplugin
 *
 * @brief Handle router requests for the clam AV version for the clam AV plugin.
 */


namespace APP\plugins\generic\clamav;

use PKP\core\JSONMessage;
use PKP\security\Validation;
use PKP\plugins\PluginRegistry;
use APP\template\TemplateManager;
use APP\handler\Handler;

class ClamavVersionHandler extends Handler {
	/**
	 * Return the Clam AV version
	 *
	 * @param $args array
	 * @param $request PKPRequest
	 * @return null|JSONMessage
	 */
	public function clamavVersion($args, $request) {
		if(Validation::isSiteAdmin()) {
			$plugin = PluginRegistry::getPlugin('generic', 'clamavplugin');
			$userVars = $request->getUserVars();
			$path = $userVars['path'];
			$type = $userVars['type'];
			if ($path && $type) {
				$clamVersion = $plugin->getClamVersion($path, $type);
			} else {
				$clamVersion = '';
			}
			return new JSONMessage(true, $clamVersion);
		}
	}
}
