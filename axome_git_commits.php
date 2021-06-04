<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Axome_git_commits extends Module
{
    protected $config_form = false;

    private $templateFile;
    private $cacheId;

    public function __construct()
    {
        $this->name = 'axome_git_commits';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Maxence DOURNEAU';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Axome - Affichage de commits depuis GitHub');

        $this->templateFile = 'module:axome_git_commits/views/templates/front.tpl';
        $this->cacheId = 'axome_git_commits';

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function install(): ?bool
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayFooterBefore') &&
            $this->setConfigDefaultValues();
    }

    public function uninstall()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = null;
        if (((bool)Tools::isSubmit('submitAxome_git_commitsModule')) == true) {
            $output = $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAxome_git_commitsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the form structure.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Enter a valid GitHub username'),
                        'name' => 'AXOME_GIT_COMMITS_USERNAME',
                        'label' => $this->l('GitHub username'),
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-folder"></i>',
                        'desc' => $this->l('Enter a valid GitHub repository'),
                        'name' => 'AXOME_GIT_COMMITS_REPOSITORY',
                        'label' => $this->l('GitHub repository'),
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-tag"></i>',
                        'desc' => $this->l('Enter a valid GitHub branch'),
                        'name' => 'AXOME_GIT_COMMITS_BRANCH',
                        'label' => $this->l('GitHub branch'),
                    ],
                    [
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-list"></i>',
                        'desc' => $this->l('Enter a valid number'),
                        'name' => 'AXOME_GIT_COMMITS_NB_COMMITS',
                        'label' => $this->l('Number of commits'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'AXOME_GIT_COMMITS_USERNAME' => Configuration::get('AXOME_GIT_COMMITS_USERNAME'),
            'AXOME_GIT_COMMITS_REPOSITORY' => Configuration::get('AXOME_GIT_COMMITS_REPOSITORY'),
            'AXOME_GIT_COMMITS_BRANCH' => Configuration::get('AXOME_GIT_COMMITS_BRANCH'),
            'AXOME_GIT_COMMITS_NB_COMMITS' => Configuration::get('AXOME_GIT_COMMITS_NB_COMMITS'),
        ];
    }

    /**
     * Set default configuration values
     */
    protected function setConfigDefaultValues()
    {
        Configuration::updateValue("AXOME_GIT_COMMITS_USERNAME", "PrestaShop");
        Configuration::updateValue("AXOME_GIT_COMMITS_REPOSITORY", "PrestaShop");
        Configuration::updateValue("AXOME_GIT_COMMITS_BRANCH", "develop");
        Configuration::updateValue("AXOME_GIT_COMMITS_NB_COMMITS", "10");
        return true;
    }

    /**
     * Check and save the configuration form
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        \Tools::clearCache(Context::getContext()->smarty);

        return $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Display lists of commits
     */
    public function hookDisplayFooterBefore()
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId($this->cacheId))) {
            $gitHub = new \AxomeGitCommits\GitHub(
                Configuration::get('AXOME_GIT_COMMITS_USERNAME'),
                Configuration::get('AXOME_GIT_COMMITS_REPOSITORY'),
                Configuration::get('AXOME_GIT_COMMITS_BRANCH')
            );

            $this->context->smarty->assign([
                'username' => Configuration::get('AXOME_GIT_COMMITS_USERNAME'),
                'repository' => Configuration::get('AXOME_GIT_COMMITS_REPOSITORY'),
                'commits' => array_slice($gitHub->getAllCommits(), 0, Configuration::get('AXOME_GIT_COMMITS_NB_COMMITS'))
            ]);
        }

        return $this->fetch($this->templateFile, $this->getCacheId($this->cacheId));
    }
}
