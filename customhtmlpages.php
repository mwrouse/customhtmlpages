<?php

if (!defined('_TB_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/CustomHTMLPageModel.php');

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
        $this->table_lang = $this->table_name . '_lang';
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

        $classPages = $this->convertToClasses($pages);

        foreach ($classPages as $i => $page)
        {
            $result = $this->getRouteForPage($page);
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
                        ->select('t1.`id_page`, t1.`name`, t1.`id_parent`, t1.`url`, t1.`active`, t2.`meta_title`, t2.`meta_description`, t2.`meta_keywords`, t2.`content`')
                        ->from($this->table_name, 't1')
                        ->leftJoin($this->table_lang, 't2', 't2.`id_page`= t1.`id_page` AND t2.`id_shop`=t1.`id_shop` AND t2.`id_lang`='. $language)
                        ->orderBy('t1.`id_page`')
                        ->where('t1.`id_shop`='.$shop);

            if (!$getInactive)
                $qry->where('t1.`active`=1');

            $result = Db::getInstance()->ExecuteS($qry);

            if (!is_array($result))
                return [];

            return $result;
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getAllHTMLPages Exception: {$e->getMessage()}");
            return [];
        }
    }


    /**
     * Returns a single Custom HTML Page
     */
    public function getHTMLPage($pageId)
    {
        try
        {
            $language = $this->context->language->id;
            $shop = $this->context->shop->id;

            $qry = (new DbQuery())
                        ->select('t1.`id_page`, t1.`name`, t1.`id_parent`, t1.`url`, t1.`active`, t2.`meta_title`, t2.`meta_description`, t2.`meta_keywords`, t2.`content`, t2.`id_lang`')
                        ->from($this->table_name, 't1')
                        ->leftJoin($this->table_lang, 't2', 't1.`id_page` = t2.`id_page` AND t2.`id_shop`=t1.`id_shop` AND t2.`id_lang`='. $language)
                        ->where('t1.`id_shop`='.$shop)
                        ->where('t1.`id_page`='.$pageId);

            $result = Db::getInstance()->ExecuteS($qry);

            if (!$result)
                return null;

            return $this->transformResult($result[0]);
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getHTMLPage Exception: {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Returns a single page as a class (for use in the page.php file)
     */
    public function getHTMLPageAsAClass($pageId)
    {
        try
        {
            $allPages = $this->convertToClasses($this->getAllHTMLPages());
            foreach ($allPages as $p) {
                if ($p->id == $pageId)
                    return $p;
            }

            return null;
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getHTMLPage Exception: {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Returns the status of a single page
     */
    public function getHTMLPageStatus($pageId)
    {
        $page = $this->getHTMLPage($pageId);
        if (is_null($page)) {
            return 1;
        }

        return $page['active'];
    }


    /**
     * Returns the route
     */
    public function getRouteForPage($page)
    {
        $route = [
            'controller' => 'page',
            'rule' => $page->url.'{e:/}',
            'keywords' => [
                'e' => ['regexp' => '']
            ],
            'params' => [
                'fc' => 'module',
                'module' => $this->name,
                'id_page' => $page->id,
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

        if (array_key_exists('id_parent', $page) && !is_null($page['id_parent']))
        {
            $parent = null;
            foreach ($allPages as $i => $p)
            {
                if ($p['id_page'] == $page['id_parent'])
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


    /**
     * Generates a name for a route
     */
    private function generateRouteKey($page)
    {
        return 'customhtmlpages-page'.$page->id;
    }



    /**
     * Transforms the result into an object for editing on the edit page
     */
    private function transformResult($page)
    {
        $lang = $this->context->language->id;

        $tmp = [
            'id_page' => $page['id_page'],
            'name' => $page['name'],
            'url' => $page['url'],
            'id_parent' => $page['id_parent'],
            'active' => $page['active'],
            'meta_title' => $page['meta_title'],
            'meta_title_lang' => [
                $lang => $page['meta_title']
            ],
            'meta_description' => $page['meta_description'],
            'meta_description_lang' => [
                $lang => $page['meta_description']
            ],
            'meta_keywords' => $page['meta_keywords'],
            'meta_keywords_lang' => [
                $lang => $page['meta_keywords']
            ],
            'content' => $page['content'],
            'content_lang' => [
                $lang => $page['content']
            ]
        ];

        return $tmp;
    }



    /**
     * Converts everything to a class
     */
    public function convertToClasses(&$pages)
    {
        $classesById = [];
        $idsToIndex = [];

        foreach ($pages as $page)
        {
            $id = $page['id_page'];
            $classesById[$id] = new CustomHTMLPageModel($page);
        }

        // Populate children
        foreach ($pages as $page)
        {
            $pageClass = $classesById[$page['id_page']];

            if (array_key_exists('id_parent', $page) && !is_null($page['id_parent']) && $page['id_parent'] != 0)
            {
                $parent = $classesById[$page['id_parent']];
                $parent->addChild($pageClass);
            }
        }

        return $classesById;
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
                    `id_parent` INT ( 12 ) DEFAULT NULL,
                    `url` VARCHAR( 128 ) NOT NULL,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (  `id_page` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql2 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_lang.'` (
                    `id_page` INT( 12 ) AUTO_INCREMENT,
                    `id_lang` INT( 12 ) NOT NULL,
                    `id_shop` INT( 12 ) NOT NULL DEFAULT 1,
                    `meta_title` VARCHAR( 128 ) NOT NULL,
                    `meta_description` VARCHAR( 255 ) DEFAULT NULL,
                    `meta_keywords` VARCHAR( 255 ) DEFAULT NULL,
                    `content` LONGTEXT,
                    PRIMARY KEY (  `id_page` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        if (!Db::getInstance()->Execute($sql) || !Db::getInstance()->Execute($sql2))
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
        if ( ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_lang.'`'))
        {
            return false;
        }

        return true;
    }
}