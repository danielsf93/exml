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
						$request->getUser(),
						$request
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
		return __('plugins.importexport.exml.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.exml.description');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'exml';
	}

		

	
	function exportSubmissions($submissionIds, $context, $user, $request) {
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submissions = array();
		foreach ($submissionIds as $submissionId) {
			$submission = $submissionDao->getById($submissionId, $context->getId());
			if ($submission) $submissions[] = $submission;
		}
	
		
/********************************************		FOREACH'S	********************************************/



foreach ($submissions as $submission) {
	// Obtendo o título da submissão
	$submissionTitle = $submission->getLocalizedFullTitle();
	
	$abstract = $submission->getLocalizedAbstract();
	$doi = $submission->getStoredPubId('doi'); 
	$publicationUrl = $request->url($context->getPath(), 'catalog', 'book', array($submission->getId()));	
	
	// Obtendo dados do autor
	$authorNames = array();
	$authors = $submission->getAuthors();
	foreach ($authors as $author) {
		$givenName = $author->getLocalizedGivenName();
		$surname = $author->getLocalizedFamilyName();
		$afiliation = $author->getLocalizedAffiliation();
		$authorNames[] = $givenName . ' ' . $surname;
	}
	$authorName = implode(', ', $authorNames);

	

		
		
/********************************************		ESTRUTURA XML		********************************************/		
		//---início estrutura xml

		$xmlContent = '<?xml version="1.0" encoding="UTF-8"?>';
		$xmlContent .= '<doi_batch xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
		$xmlContent .= ' xsi:schemaLocation="http://www.crossref.org/schema/5.3.0 https://www.crossref.org/schemas/crossref5.3.0.xsd"';
		$xmlContent .= ' xmlns="http://www.crossref.org/schema/5.3.0" xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1"';
		$xmlContent .= ' xmlns:fr="http://www.crossref.org/fundref.xsd" xmlns:mml="http://www.w3.org/1998/Math/MathML"';
		$xmlContent .= ' version="5.3.0">';
		
		//---head
		$xmlContent .= '<head>';
			$xmlContent .= '<doi_batch_id>test.x</doi_batch_id>';
			$xmlContent .= '<timestamp>2021010100000000</timestamp>';
			$xmlContent .= '<depositor>';
				$xmlContent .= '<depositor_name>Crossref</depositor_name>';
				$xmlContent .= '<email_address>pfeeney@crossref.org</email_address>';
			$xmlContent .= '</depositor>';
			$xmlContent .= '<registrant>Society of Metadata Idealists</registrant>';
		$xmlContent .= '</head>';
		
		//---body
		$xmlContent .= '<body>';
			$xmlContent .= '<book book_type="monograph">';
				$xmlContent .= '<book_metadata language="en">';
				//---contributors
			$xmlContent .= '<contributors>';
				$xmlContent .= '<person_name sequence="first" contributor_role="author">';
					$xmlContent .= '<given_name>' . htmlspecialchars($givenName) . '</given_name>';
					$xmlContent .= '<surname>' . htmlspecialchars($surname) . '</surname>';
					$xmlContent .= '<affiliations>';
						$xmlContent .= '<institution>';
							$xmlContent .= '<institution_name>' . htmlspecialchars($afiliation) . '</institution_name>';
						$xmlContent .= '</institution>';
						$xmlContent .= '<institution>';
							$xmlContent .= '<institution_id type="ror">https://ror.org/036rp1748</institution_id>';
						$xmlContent .= '</institution>';
					$xmlContent .= '</affiliations>';
				$xmlContent .= '</person_name>';
			$xmlContent .= '</contributors>';
					
			
					
					//---titles
					$xmlContent .= '<titles>';
						$xmlContent .= '<title>' . htmlspecialchars($submissionTitle) . '</title>';
					$xmlContent .= '</titles>';
					//----abstract 
					$xmlContent .= '<jats:abstract> <jats:p>' . htmlspecialchars($abstract) . '</jats:p> </jats:abstract>';

					$xmlContent .= '<edition_number>2</edition_number>';
					$xmlContent .= '<publication_date media_type="print">';
						$xmlContent .= '<year>2009</year>';
					$xmlContent .= '</publication_date>';
					$xmlContent .= '<isbn media_type="electronic">1596680547</isbn>';
					$xmlContent .= '<isbn media_type="print">9789000002191</isbn>';
						//tentando pegar o nome da editora:
						//$publisherName = htmlspecialchars($press->getName($press->getPrimaryLocale()));
						$xmlContent .= '<publisher>';
							$xmlContent .= '<publisher_name>' . 'xablau' . '</publisher_name>';
						$xmlContent .= '</publisher>';
					$xmlContent .= '<doi_data>';
						$xmlContent .= '<doi>' . htmlspecialchars($doi) . '</doi>';
						$xmlContent .= '<resource>' . htmlspecialchars($publicationUrl) . '</resource>';
					$xmlContent .= '</doi_data>';
				$xmlContent .= '</book_metadata>';
			$xmlContent .= '</book>';
		$xmlContent .= '</body>';

/////inicio testes

		$xmlContent .= '<teste>';
		$xmlContent .= 'aaa'. 'testeee' . 'bbb';
		$xmlContent .= '</teste>';
		





		
////fInal testes
		}

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
