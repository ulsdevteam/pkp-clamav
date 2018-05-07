<?php

/**
 * @defgroup plugins_generic_clamav
 */
 
/**
 * @file plugins/generic/clamav/index.php
 *
 * Copyright (c) 2018 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @ingroup plugins_generic_clamav
 * @brief Wrapper for ClamAV plugin.
 *
 */

require_once('ClamavPlugin.inc.php');

return new ClamavPlugin();

?>
