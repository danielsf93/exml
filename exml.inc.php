<?php

import('plugins.importexport.exml.lib.pkp.classes.plugins.ImportExportPlugin');

class exml extends ImportExportPlugin2 {
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

	function exportSubmissions($submissions) {
		$request = Application::getRequest();
		$xmlContent = '<doi_batch xmlns="http://www.crossref.org/schema/4.3.7" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="4.3.7" xsi:schemaLocation="http://www.crossref.org/schema/4.3.7 http://www.crossref.org/schemas/crossref4.3.7.xsd">';
	
		// Head section
		$xmlContent .= '<head>';
		$xmlContent .= '<doi_batch_id>20080305081200</doi_batch_id>';
		$xmlContent .= '<timestamp>200815071200</timestamp>';
		$xmlContent .= '<depositor>';
		$xmlContent .= '<depositor_name>Sample Master</depositor_name>';
		$xmlContent .= '<email_address>support@crossref.org</email_address>';
		$xmlContent .= '</depositor>';
		$xmlContent .= '<registrant>Sample Data</registrant>';
		$xmlContent .= '</head>';
	
		// Body section
		$xmlContent .= '<body>';
		$xmlContent .= '<book book_type="edited_book">';
		$xmlContent .= '<book_set_metadata language="en">';
		$xmlContent .= '<set_metadata>';
		$xmlContent .= '<titles>';
		$xmlContent .= '<title>Sample Set Title</title>';
		$xmlContent .= '</titles>';
		$xmlContent .= '<isbn media_type="print">0 571 08989 5</isbn>';
		$xmlContent .= '</set_metadata>';
		$xmlContent .= '<titles>';
		$xmlContent .= '<title>Sample Volume Title</title>';
		$xmlContent .= '</titles>';
		$xmlContent .= '<volume>1</volume>';
		$xmlContent .= '<publication_date media_type="print">';
		$xmlContent .= '<year>2007</year>';
		$xmlContent .= '</publication_date>';
		$xmlContent .= '<isbn media_type="print">0064410145</isbn>';
		$xmlContent .= '<publisher>';
		$xmlContent .= '<publisher_name>Sample Publisher</publisher_name>';
		$xmlContent .= '</publisher>';
		$xmlContent .= '</book_set_metadata>';
		$xmlContent .= '</book>';
	
		// Continue adding other elements here
	
		$xmlContent .= '</body>';
		$xmlContent .= '</doi_batch>';
	
		return $xmlContent;
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
