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
class ClamScanFailureException extends Exception {};

class ClamavPlugin extends GenericPlugin {
	const TYPE_SOCKET = 'socket';
	const TYPE_EXECUTABLE = 'executable';
	const TIMEOUT_DEFAULT = 30;
	const UNSCANNED_DEFAULT = 'allow';
	const UNSCANNED_ALLOW = 'allow';
	const UNSCANNED_BLOCK = 'block';

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE'))
			return true;
		if ($success && $this->getEnabled()) {
			// Enable Clam AV's preprocessing of uploaded files
			HookRegistry::register('SubmissionFile::validate', array($this, 'clamscanHandleUpload'));
			// Create handler for AJAX call
			HookRegistry::register('LoadHandler', array($this, 'setPageHandler'));
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
	 * Site-wide plugins should override this function to return true.
	 *
	 * @return boolean
	 */
	function isSitePlugin() {
		return true;
	}
	
	/**
	 * Backwards-compatible method to retrieve the current context across
	 * multiple versions of PKP applicatiosn
	 * @return 
	 */
	function _getBackwardsCompatibleContext() {
		if(method_exists('Application', 'get')) {
			// OJS 3.2 and later
			$request = Application::get()->getRequest();
			$context = $request->getContext();
		} else {
			// OJS 3.1.2 and earlier
			$context = Request::getContext();
		}
		return $context;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
				$this->getEnabled() ? array(
			new LinkAction(
					'settings', new AjaxModal(
					$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')), $this->getDisplayName()
					), __('manager.plugins.settings'), null
			),
				) : array(), parent::getActions($request, $verb)
		);
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$contextID = (!is_null($this->_getBackwardsCompatibleContext()) ? $this->_getBackwardsCompatibleContext()->getId() : CONTEXT_SITE);

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('ClamavSettingsForm');
				$form = new ClamavSettingsForm($this, $contextID);
				
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData($request);
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
		if(method_exists($this, 'getTemplateResource')) {
			// OJS 3.1.2 and later
			return parent::getTemplatePath($inCore);
		} else {
			// OJS 3.1.1 and earlier 3.x releases
			return parent::getTemplatePath($inCore) . 'templates' . DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Get the clam version
	 * @param $path string Optional path to check. If not provided, the current setting value is used.
	 * @param $type string Type of connection to be used: executable or socket.
	 * @return string
	 * */
	function getClamVersion($path = '', $type=self::TYPE_EXECUTABLE) {
		if ($path === '') {
			$path = $this->getSetting(CONTEXT_SITE, 'clamavPath');
		}
		if ($type === self::TYPE_EXECUTABLE && !empty($path) && is_executable($path)) {
			$version = exec($path . ' --version');
			if (preg_match('/^ClamAV .*/', $version)) {
				return $version;
			}
		} else if ($type === self::TYPE_SOCKET && !empty($path)) {
			$output = '';
			$errno = 0;
			$errorMessage = "";
			
			$clamDaemon = stream_socket_client("$path", $errno, $errorMessage);

			if ($clamDaemon) {
				// clamd wants non-blocking socket connections
				stream_set_blocking($clamDaemon, false);

				// get version of clamd
				// \0 is null character.
				stream_socket_sendto($clamDaemon, "zVERSION\0");
				$output = $this->_clamDaemonShortPolling($clamDaemon);
				return $output;
			}

		}
		return '';
	}

	/**
	 * Private helper method to scan a file using the clamscan executable, returning a virus message if matched
	 * @param $props array Information about the uploaded file from the OJS database
	 * @param $uploadedFile string Path to the uploaded file in OJS's files directory
	 * @return string
	 */
	function _clamscanFile($uploadedFile) {
		if ($this->getClamVersion() && !empty($uploadedFile)) {
			$uploadedFile="jimmy.txt";
			$output = "";
			$exitCode = "";
			$clamAVPath = $this->getSetting(CONTEXT_SITE, 'clamavPath');
			$scan = exec($clamAVPath . ' --no-summary -i ' . $uploadedFile, $output, $exitCode);
			//process the result
			preg_match('/:(.*)/',$output[0],$matches);
			$virusID=trim($matches[1]);
			switch ($exitCode) {
				//clean
				case 0:
					return false;
				//virus found!
				case 1: 
					return $virusID;
				//failed to scan
				case 2:
					throw new ClamScanFailureException("ClamAV failed to scan the file");
			}
		}
	}

	/**
	 * Private helper method to scan a file using the clamav daemon
	 * @param $uploadedFileField string array key in $_FILES for the file in question
	 * @return string
	 */
	function _clamDaemonFile($uploadedFile) {
		$clamavSocketPath = $this->getSetting(CONTEXT_SITE, 'clamavSocketPath');
		$maxInstreamSize = 1024; // TODO: need to universalize this based on a plugin setting
		$output = "";
		$errno = 0;
		$errorMessage = "";

		// stream_socket_client() seems to be preferred over older socket
		// connections nowadays
		$clamDaemon = stream_socket_client("$clamavSocketPath", $errno, $errorMessage);
		// open file for reading in binary mode
		$tmpFile = fopen($uploadedFile, 'rb');

		if ($clamDaemon && $tmpFile) {
			// clamd wants non-blocking socket connections
			stream_set_blocking($clamDaemon, false);

			// begin IDSESSION with clamd. z prefix indicates null delimiter.
			// \0 is null character.
			stream_socket_sendto($clamDaemon, "zIDSESSION\0");

			/*
			 * INSTREAM begins data stream for virus scanning. Data is sent
			 * in chunks, with format <length><data> where <length> is the size
			 * of the following data in bytes expressed as a 4-byte unsigned
			 * integer in network byte order.
			 * 
			 * We're using pack() to generate the big-endian-formatted numbers.
			 * 
			 * As per the clamd man page, clamd requires a non-blocking
			 * connection which means we need to roll our own polling solution--
			 * this is managed by _clamDaemonShortPolling().
			 */
			stream_socket_sendto($clamDaemon, "zINSTREAM\0");

			while (!feof($tmpFile)) {
				$data = fread($tmpFile, $maxInstreamSize);
				$package = pack("N", strlen($data)).$data;
				stream_socket_sendto($clamDaemon, $package);
			}
			// terminating INSTREAM with zero-length chunk
			$data = pack("N", "");
			stream_socket_sendto($clamDaemon, $data);

			// wait for output
			$output = $this->_clamDaemonShortPolling($clamDaemon);

			// closing IDSESSION
			stream_socket_sendto($clamDaemon, "nEND\0");

			fclose($tmpFile);
			fclose($clamDaemon);
			if($output['safe'] === false) {
				if($output['message'] == 'timeout') {
					return $output['message'];
				} else {
					// Virus detected
					return $output['message'];
				}
				//errors?
			}
		}
		// TODO: how do we clear old notifications?
		return ''; // no virus detected
	}

	/**
	 * Private helper method to manage short polling a socket and return output
	 * @param $socket stream resource The streaming socket resource.
	 * @param $delay int Time between short polls, measured in nanoseconds
	 * @param $intervals int Number of intervals to check; after $intervals checks, return.
	 * @return string
	 */
	function _clamDaemonShortPolling($socket, $delay = 100000000, $intervals = 10) {
		// author's note: cludge cludge cludge cludge. TODO: look into long polling for unix sockets?
		$output = "";
		$intervals = $this->getSetting(CONTEXT_SITE, 'clamavSocketTimeout')*10;
		$failover = $this->getSetting(CONTEXT_SITE, 'allowUnscannedFiles');
		
		for ($i = 0; $i < $intervals; $i++) {
			// sleep() does not accept fractional seconds and usleep uses a surprising amount of CPU, leaving us with time_nanosleep().
			time_nanosleep(0, $delay);
			$output = stream_get_contents($socket);
			if ($output !== "") {
				if($output == "1: stream: OK\0") {
					return Array('safe' => true, 'message' => $output);
				} else {
					return Array('safe' => false, 'message' => substr($output, 11));
				}
			}
		}
		if($failover === "block") {
			return Array('safe' => false, 'message' => 'timeout');
		} else {
			return Array('safe' => true, '');
		}
	}

	/**
	 * Hook callback: scan an uploaded file with ClamAV
	 * @see submissionfilesuploadform::validate()
	 */
	function clamscanHandleUpload($hookName, $args) {
		$fileId = $args[2]['fileId'];
		$props = $args[2];
		$allowedLocales = $args[3];
		$file = Services::get('file')->get($fileId);
		$path = $file->path;
		$uploadedFile = Config::getVar('files', 'files_dir') . '/' . $path;
		//scan for viruses if file exists
		if (null !== $file) {
			$useSocket = $this->getSetting(CONTEXT_SITE, 'clamavUseSocket');
			
			try {
				if ($useSocket === true) {
					$message = $this->_clamDaemonFile($uploadedFile);
				}
				else {
					$message = $this->_clamscanFile($uploadedFile);
				}
					//No viruses found! Continue with submission
					if ($message === false) {
						return false;
					}
					//ClamAV reported a virus or failed to complete the scan
					else {
						//Prepare to notify the user and halt the upload process
						$rejectionMessage = ["threatname"=>$message];
						import('lib.pkp.classes.validation.ValidatorFactory');
						$schemaService = Services::get('schema');
						$validator = \ValidatorFactory::make(
							$props,
							$schemaService->getValidationRules(SCHEMA_SUBMISSION_FILE, $allowedLocales), 
							$rejectionMessage
						);
						//If Clam found a virus, it will return the signature name as a string
						if ($message == true) {
							//create a user notification
							$validator->errors()->add('clamAV::virusDetected', __('plugins.generic.clamav.uploadBlocked',$rejectionMessage));
						}
					}
					
			}
			//Scanning errors will generate a custom exception
			catch (ClamScanFailureException $e){
					//Couldn't scan, but ClamAV plugin settings are permissive, continue with submission anyway
					if ( $this->getSetting(CONTEXT_SITE, 'allowUnscannedFiles')===UNSCANNED_ALLOW) {
						return false;
					}
					//Otherwise notify the user that there was an error
					else {
						//the user will see the general error message from the locale file
						$validator->errors()->add('clamAV::failedToScan', __('plugins.generic.clamav.error'));
					}
			}

			//The file is considered unsafe. Interrupt submission process and clean up the working copy/metadata
			$errors = $schemaService->formatValidationErrors($validator->errors(), $schemaService->get(SCHEMA_SUBMISSION_FILE), $allowedLocales);
	
			if ($args[0]===null){
					$args[0]=$errors;
			}
			elseif (is_array($args[0])) {
				array_push($args[0],$errors);
			}
			//Clean up rejected file
			Services::get('file')->delete($fileId);
			// returning true aborts processing
			return true;
			
		}
	}

	/**
	 * Route requests for the clam version to custom page handler
	 *
	 * @see PKPPageRouter::route()
	 * @param $hookName string
	 * @param $params array
	 */
	public function setPageHandler($hookName, $params) {
		$page = $params[0];
		if ($this->getEnabled() && $page === 'clamavVersion') {
			$this->import('ClamavVersionHandler');
			define('HANDLER_CLASS', 'ClamavVersionHandler');
			return true;
		}
		return false;
	}
}

?>
