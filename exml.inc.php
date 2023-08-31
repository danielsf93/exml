<?php

import('plugins.importexport.exml.lib.pkp.classes.plugins.ImportExportPlugin');

class exml extends ImportExportPlugin2
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return $success;
        }
        if ($success && $this->getEnabled()) {
            $this->addLocaleData();
            $this->import('exmlDeployment');
        }

        return $success;
    }

    public function display($args, $request)
    {
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
                $templateMgr->display($this->getTemplateResource('index.tpl'));
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
                    //'monographs' aparece no nome do arquivo .xml
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

    // forma o link de acesso a ferramenta
    public function getName()
    {
        return 'exml';
    }

    /**
     * Get the display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.exml.displayName');
    }

    /**
     * Get the display description.
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.importexport.exml.description');
    }

    //forma o prefixo do arquivo .xml
    public function getPluginSettingsPrefix()
    {
        return 'exml';
    }

    public function exportSubmissions($submissionIds, $context, $user, $request)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
        $submissions = [];
        $app = new Application();
        $request = $app->getRequest();
        $press = $request->getContext();
        foreach ($submissionIds as $submissionId) {
            $submission = $submissionDao->getById($submissionId, $context->getId());
            if ($submission) {
                $submissions[] = $submission;
            }
        }

        /********************************************		FOREACH'S	********************************************/

        $authorsInfo = [];
        $authors = $submission->getAuthors();
        foreach ($authors as $author) {
            $authorInfo = [
        'givenName' => $author->getLocalizedGivenName(),
        'surname' => $author->getLocalizedFamilyName(),
        'afiliation' => $author->getLocalizedAffiliation(),
        'orcid' => $author->getOrcid(),
    ];
            $authorsInfo[] = $authorInfo;
        }

        foreach ($submissions as $submission) {
            // Obtendo o título da submissão
            $submissionTitle = $submission->getLocalizedFullTitle();
            //obtendo o tipo de conteudo, capitulo e monografia
            $types = [1 => 'chapter', 2 => 'monograph'];
            $type = $submission->getWorkType();

            $abstract = $submission->getLocalizedAbstract();
            $doi = $submission->getStoredPubId('doi');
            $publicationUrl = $request->url($context->getPath(), 'catalog', 'book', [$submission->getId()]);
            $copyright = $submission->getLocalizedcopyrightHolder();
            // aqui retorna ano mes dia $publicationYear = $submission->getDatePublished();
            $publicationDate = $submission->getDatePublished();
            $publicationYear = date('Y', strtotime($publicationDate));
            $publicationMonth = date('m', strtotime($publicationDate));
            $publicationDay = date('d', strtotime($publicationDate));
            //timestamp
            $timestamp = date('YmdHis').substr((string) microtime(), 2, 3);

            // aqui retorna xx_XX$submissionLanguage = $submission->getLocale();
    $submissionLanguage = substr($submission->getLocale(), 0, 2); //aqui retorna xx
    $publisherName = $press->getData('publisher');
            $registrant = $press->getLocalizedName();
            //$editionNumber = $submission->getSeriesPosition();

            // Obtendo dados do autor
            $authorNames = [];
            $authors = $submission->getAuthors();
            foreach ($authors as $author) {
                $givenName = $author->getLocalizedGivenName();
                $surname = $author->getLocalizedFamilyName();
                $afiliation = $author->getLocalizedAffiliation();
                $authorNames[] = $givenName.' '.$surname;
            }
            $authorName = implode(', ', $authorNames);
            $orcid = $author->getOrcid();

            /********************************************		ESTRUTURA XML		********************************************/
            //---início estrutura xml

            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
		<doi_batch version="4.4.2" xmlns="http://www.crossref.org/schema/4.4.2" 
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
		xmlns:jats="http://www.ncbi.nlm.nih.gov/JATS1" 
		xsi:schemaLocation="http://www.crossref.org/schema/4.4.2 http://www.crossref.org/schema/deposit/crossref4.4.2.xsd">';

            //$xmlContent .='<TESTE>' . $timestamp . '</TESTE>';

            $xmlContent .= '<head>';
            //segundo documentação, doi_batch_id pode ser o proprio nome da publicação: https://www.crossref.org/documentation/register-maintain-records/verify-your-registration/submission-queue-and-log/
            //$xmlContent .= '<doi_batch_id>ba60f6118992d8a5a2-5a37</doi_batch_id>';
            $xmlContent .= '<doi_batch_id>'.htmlspecialchars($submissionTitle).'</doi_batch_id>';
            $xmlContent .= '<timestamp>'.$timestamp.'</timestamp>';
            $xmlContent .= '<depositor>';
            $xmlContent .= '<depositor_name>sibi:sibi</depositor_name> ';
            $xmlContent .= '<email_address>dgcd@abcd.usp.br</email_address>';
            $xmlContent .= '</depositor>';
            $xmlContent .= '<registrant>WEB-FORM</registrant>';
            $xmlContent .= '</head>';

            $xmlContent .= '<body>';
            $xmlContent .= '<book book_type="'.htmlspecialchars($types[$type]).'">';
            $xmlContent .= '<book_metadata>';

            $xmlContent .= '<contributors>';

            //AUTORES:
            // Primeiro autor
            $firstAuthor = reset($authorsInfo);
            if (!empty($authorInfo['afiliation'])) {
                $xmlContent .= '<organization sequence="additional" contributor_role="author">'.htmlspecialchars($authorInfo['afiliation']).'</organization>';
            }
            $xmlContent .= '<person_name sequence="first" contributor_role="author">';
            $xmlContent .= '<given_name>'.htmlspecialchars($firstAuthor['givenName']).'</given_name>';
            $xmlContent .= '<surname>'.htmlspecialchars($firstAuthor['surname']).'</surname>';
            if (!empty($authorInfo['orcid'])) {
                $xmlContent .= '<ORCID>'.htmlspecialchars($authorInfo['orcid']).'</ORCID>';
            }
            $xmlContent .= '</person_name>';
            // Autores adicionais
            foreach ($authorsInfo as $index => $authorInfo) {
                if ($index > 0) {
                    $xmlContent .= '<person_name sequence="additional" contributor_role="author">';
                    $xmlContent .= '<given_name>'.htmlspecialchars($authorInfo['givenName']).'</given_name>';
                    $xmlContent .= '<surname>'.htmlspecialchars($authorInfo['surname']).'</surname>';
                    if (!empty($authorInfo['orcid'])) {
                        $xmlContent .= '<ORCID>'.htmlspecialchars($authorInfo['orcid']).'</ORCID>';
                    }
                    $xmlContent .= '</person_name>';
                    if (!empty($authorInfo['afiliation'])) {
                        $xmlContent .= '<organization sequence="additional" contributor_role="author">'.htmlspecialchars($authorInfo['afiliation']).'</organization>';
                    }
                }
            }
            $xmlContent .= '</contributors>';

            $xmlContent .= '<titles>';
            $xmlContent .= '<title>'.htmlspecialchars($submissionTitle).'</title>';
            $xmlContent .= '</titles>';

            $xmlContent .= '<jats:abstract xml:lang="'.htmlspecialchars($submissionLanguage).'">';
            $xmlContent .= '<jats:p>'.htmlspecialchars($abstract).'</jats:p>';
            $xmlContent .= '</jats:abstract>';

            $xmlContent .= '<publication_date media_type="online">';
            $xmlContent .= '<month>'.htmlspecialchars($publicationMonth).'</month>';
            $xmlContent .= '<day>'.htmlspecialchars($publicationDay).'</day>';
            $xmlContent .= '<year>'.htmlspecialchars($publicationYear).'</year>';
            $xmlContent .= '</publication_date>';

            $xmlContent .= '<isbn>9788566404289</isbn>';

            $xmlContent .= '<publisher>';
            //copyright
            $xmlContent .= '<publisher_name>'.htmlspecialchars($copyright).'</publisher_name>';
            $xmlContent .= '</publisher>';

            $xmlContent .= '<doi_data>';
            $xmlContent .= '<doi>'.htmlspecialchars($doi).'</doi>';
            $xmlContent .= '<resource>'.htmlspecialchars($publicationUrl).'</resource>';
            $xmlContent .= '</doi_data>';
            $xmlContent .= '</book_metadata>';
            $xmlContent .= '</book>';

            $xmlContent .= '</body>';
            $xmlContent .= '</doi_batch>';
        }

        return $xmlContent;
    }

    /**
     * @copydoc ImportExportPlugin::executeCLI
     */
    public function executeCLI($scriptName, &$args)
    {
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
                echo __('plugins.importexport.common.cliError')."\n";
                echo __('plugins.importexport.common.error.unknownPress', ['pressPath' => $pressPath])."\n\n";
            }
            $this->usage($scriptName);

            return;
        }

        if ($xmlFile && $this->isRelativePath($xmlFile)) {
            $xmlFile = PWD.'/'.$xmlFile;
        }

        switch ($command) {
            case 'export':
                $outputDir = dirname($xmlFile);
                if (!is_writable($outputDir) || (file_exists($xmlFile) && !is_writable($xmlFile))) {
                    echo __('plugins.importexport.common.cliError')."\n";
                    echo __('plugins.importexport.common.export.error.outputFileNotWritable', ['param' => $xmlFile])."\n\n";
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
    public function usage($scriptName)
    {
        fatalError('Not implemented.');
    }
}
