<?php

import('lib.pkp.classes.plugins.ImportExportPlugin');

class exml extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			$this->import('exmlDeployment');
		}
		return $success;
	}







	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		parent::display($args, $request);

		$templateMgr->assign('plugin', $this);

		switch (array_shift($args)) {
			case 'index':
			case '':
				$apiUrl = $request->getDispatcher()->url($request, ROUTE_API, $context->getPath(), 'submissions');
				$submissionsListPanel = new \APP\components\listPanels\SubmissionsListPanel(
					'submissions',
					__('common.publications'),
					[
						'apiUrl' => $apiUrl,
						'count' => 100,
						'getParams' => new stdClass(),
						'lazyLoad' => true,
					]
				);
				$submissionsConfig = $submissionsListPanel->getConfig();
				$submissionsConfig['addUrl'] = '';
				$submissionsConfig['filters'] = array_slice($submissionsConfig['filters'], 1);
				$templateMgr->setState([
					'components' => [
						'submissions' => $submissionsConfig,
					],
				]);
				$templateMgr->assign([
					'pageComponent' => 'ImportExportPage',
				]);
				$templateMgr->display($this->getTemplateResource('page.tpl'));
				break;
				case 'export':
					$exportXml = $this->exportSubmissions(
						(array) $request->getUserVar('selectedSubmissions'),
						$request->getContext(),
						$request->getUser()
					);
					import('lib.pkp.classes.file.FileManager');
					$fileManager = new FileManager();
					$exportFileName = $this->getExportFileName($this->getExportPath(), 'monographs', $context, '.xml');
					$fileManager->writeFile($exportFileName, $exportXml);
					$fileManager->downloadByPath($exportFileName);
					$fileManager->deleteByPath($exportFileName);
					break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}







	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'exml';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('exml');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('exml');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'exml';
	}



	function exportSubmissions($submissionIds, $context, $user, $opts = array()) {
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$nativeExportFilters = $filterDao->getObjectsByGroup('monograph=>native-xml');
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new NativeImportExportDeployment($context, $user));
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
		libxml_use_internal_errors(true);
		$exportFilter->setOpts($opts);
		$submissionXml = $exportFilter->execute($submissions, true);
		$xml = $submissionXml->saveXml();
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
		}
		return $xml;
	}



	
	/**
	 * @copydoc ImportExportPlugin::executeCLI
	 */
	function executeCLI($scriptName, &$args) {
		$opts = $this->parseOpts($args, ['no-embed', 'use-file-urls']);
		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$pressPath = array_shift($args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_SUBMISSION);
		$pressDao = DAORegistry::getDAO('PressDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$press = $pressDao->getByPath($pressPath);

		if (!$press) {
			if ($pressPath != '') {
				echo __('plugins.importexport.common.cliError') . "\n";
				echo __('plugins.importexport.common.error.unknownPress', array('pressPath' => $pressPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile && $this->isRelativePath($xmlFile)) {
			$xmlFile = PWD . '/' . $xmlFile;
		}

		switch ($command) {
			
			case 'export':
				$outputDir = dirname($xmlFile);
				if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
					echo __('plugins.importexport.common.cliError') . "\n";
					echo __('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $xmlFile)) . "\n\n";
					$this->usage($scriptName);
					return;
				}
		
				if ($xmlFile != '') {
					switch (array_shift($args)) {
						case 'monograph':
						case 'monographs':
							$selectedSubmissions = array_slice($args, 1);
							$xmlContent = $this->exportSubmissions($selectedSubmissions);
							file_put_contents($xmlFile, $xmlContent);
							return;
					}
				}
				break;
		}
		$this->usage($scriptName);
	}

	/**
	 * @copydoc ImportExportPlugin::usage
	 */
	function usage($scriptName) {
		fatalError('Not implemented.');
	}

}
