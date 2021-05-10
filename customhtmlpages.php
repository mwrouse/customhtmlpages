<?php

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Module for Custom HTML Pages
 */
class CustomHTMLPages extends Module
{
    protected $hooksList = [];

    protected $_tabs = [
        'CustomHTMLPages' => 'HTML Pages', // class => label
    ];

    public function __construct()
    {
        $this->name = 'customhtmlpages';
        $this->className = 'CustomHTMLPages';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Michael Rouse';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;
        $this->table_name = 'customhtmlpages';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        // List of hooks
        $this->hooksList = [
            'displayBackOfficeHeader',
            'moduleRoutes'
        ];

        parent::__construct();

        $this->displayName = $this->l('Custom HTML Pages');
        $this->description = $this->l('Add custom HTML Pages to your store');
    }


    /**
     * Registers all of the routes for all of the pages
     */
    public function hookModuleRoutes($params)
    {
        $pages = $this->getAllHTMLPages();

        $routes = [];

        foreach ($pages as $i => $page)
        {
            $result = $this->getRouteForPage($page, $pages);
            $routes[$this->generateRouteKey($page)] = $result;
        }

        return $routes;
    }


    /**
     * Add CSS/JS to the back office
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'css/backoffice.css', 'all');
    }



    /**
     * Returns all of the HTML pages
     */
    public function getAllHTMLPages($getInactive = false)
    {
        try
        {
            $language = $this->context->language->id;
            $shop = $this->context->shop->id;

            $qry = (new DbQuery())
                        ->select('*')
                        ->from($this->table_name)
                        ->orderBy('`id_page`')
                        ->where('`id_shop`='.$shop)
                        ->where('`id_lang`='.$language);

            if (!$getInactive)
                $qry->where('`active`=1');

            $result = Db::getInstance()->ExecuteS($qry);

            if (!is_array($result))
                return [];

            return $result;
        }
        catch (Exception $e)
        {
            Logger::addLog("ExtraProductFunctionality getExtraFunctionalityForProduct Exception: {$e->getMessage()}");
            return [];
        }
    }


    /**
     * Returns the route
     */
    public function getRouteForPage(&$page, &$allPages)
    {
        $rule = $this->getRouteRuleForPage($page, $allPages);
        $page['full_url'] = $rule;

        $route = [
            'controller' => 'page',
            'rule' => $rule.'{e:/}',
            'keywords' => [
                'e' => ['regexp' => '']
            ],
            'params' => [
                'fc' => 'module',
                'module' => $this->name,
                'page' => $page,
                'pageId' => $page['id_page'],
            ]
        ];

        return $route;
    }


    /**
     * Returns the route rule for a page (recurses through parents)
     */
    private function getRouteRuleForPage(&$page, &$allPages)
    {
        if (is_null($page))
            return '';

        $prefix = '';

        if (array_key_exists('parent', $page) && !is_null($page['parent']))
        {
            $parent = null;
            foreach ($allPages as $p)
            {
                if ($p['id_page'] == $page['parent'])
                {
                    $parent = $p;
                    break;
                }
            }

            if (!is_null($parent))
            {
                $prefix = $this->getRouteRuleForPage($parent, $allPages).'/';
            }
        }

        $url = $prefix.$page['url'];
        return $url;
    }



    private function generateRouteKey($page)
    {
        return 'customhtmlpages-page'.$page['id_page'];
    }


    /**************************
     *    Install/Uninstall   *
     **************************/
    public function install()
    {
        if ( ! parent::install()
            || ! $this->_createTabs()
            || ! $this->_createDatabases()
        ) {
            return false;
        }

        foreach ($this->hooksList as $hook) {
            if ( ! $this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }


    public function uninstall()
    {
        if ( ! parent::uninstall()
            || ! $this->_eraseDatabases()
            || ! $this->_eraseTabs()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Create tabs on the admin page
     */
    private function _createTabs()
    {
        /* This is the main tab, all others will be children of this */
        $allLangs = Language::getLanguages();
        $idTab = $this->_createSingleTab(0, 'Admin'.$this->className, $this->displayName, $allLangs);

        foreach ($this->_tabs as $class => $name) {
              $this->_createSingleTab($idTab, $class, $name, $allLangs);
        }

        return true;
    }

    /**
     * Creates a single tab
     */
    private function _createSingleTab($idParent, $class, $name, $allLangs)
    {
        $tab = new Tab();
        $tab->active = 1;

        foreach ($allLangs as $language) {
            $tab->name[$language['id_lang']] = $name;
        }

        $tab->class_name = $class;
        $tab->module = $this->name;
        $tab->id_parent = $idParent;

        if ($tab->add()) {
            return $tab->id;
        }

        return false;
    }

    /**
     * Get rid of all installed back office tabs
     */
    private function _eraseTabs()
    {
        $idTabm = (int)Tab::getIdFromClassName('Admin'.ucfirst($this->name));
        if ($idTabm) {
            $tabm = new Tab($idTabm);
            $tabm->delete();
        }

        foreach ($this->_tabs as $class => $name) {
            $idTab = (int)Tab::getIdFromClassName($class);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return true;
    }

    /**
     * Create Database Tables
     */
    private function _createDatabases()
    {
        $sql = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name.'` (
                    `id_page` INT( 12 ) AUTO_INCREMENT,
                    `id_lang` INT( 12 ) NOT NULL,
                    `id_shop` INT( 12 ) NOT NULL DEFAULT 1,
                    `name` VARCHAR( 255 ) NOT NULL,
                    `parent` INT ( 12),
                    `url` VARCHAR( 128 ) NOT NULL,
                    `meta_title` VARCHAR( 128 ) NOT NULL,
                    `meta_description` VARCHAR( 255 ) DEFAULT NULL,
                    `meta_keywords` VARCHAR( 255 ) DEFAULT NULL,
                    `content` LONGTEXT,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (  `id_page` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        if (!Db::getInstance()->Execute($sql))
        {
            return false;
        }

        return true;
    }

    /**
     * Remove Database Tables
     */
    private function _eraseDatabases()
    {
        if ( ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name.'`'))
        {
            return false;
        }

        return true;
    }
}