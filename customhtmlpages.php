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
        $this->table_related = $this->table_name .'_relatedPages';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        // List of hooks
        $this->hooksList = [
            'displayBackOfficeHeader',
            'moduleRoutes',
            'displaySitemapPages', /* You must had {hook h='displaySitemapPages'} to your sitemap.tpl file if you want this to work */
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
        $this->context->controller->addCSS($this->_path . 'css/customhtmlpages.css', 'all');
        $this->context->controller->addJS($this->_path . 'js/customhtmlpages.js');

        $allPages = $this->getAllHTMLPages(true);

        Media::addJsDef([
            'allCustomHTMLPages' => $allPages
        ]);
    }


    /**
     * Return Pages for the sitemap
     */
    public function hookDisplaySitemapPages()
    {
        $allPages = $this->getAllHTMLPages();
        $tree = $this->convertToTree($allPages);

        $this->context->smarty->assign([
            'tree' => $tree,
            'templatePath' => _PS_MODULE_DIR_.'/'.$this->name.'/views/templates/front/sitemap.tpl',
        ]);

        return $this->display(__FILE__, 'views/templates/front/sitemap.tpl');
    }



    /**********************************
     *       Database Interface       *
     **********************************/

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
                        ->select('t1.`id_page`, t1.`name`, t1.`id_parent`, t1.`url`, t1.`style`, t1.`id_products`, t1.`id_categories`, t1.`active`, t2.`meta_title`, t2.`meta_description`, t2.`meta_keywords`, t2.`content`')
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
            if ($pageId == 'new')
                return $this->getDefaultValues();

            $language = $this->context->language->id;
            $shop = $this->context->shop->id;

            $qry = (new DbQuery())
                        ->select('t1.`id_page`, t1.`name`, t1.`id_parent`, t1.`url`, t1.`style`, t1.`id_products`, t1.`id_categories`, t1.`active`, t2.`meta_title`, t2.`meta_description`, t2.`meta_keywords`, t2.`content`, t2.`id_lang`')
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
     * Returns all of the pages that are related to a certain page
     */
    private function getRelatedPagesForPage($pageId)
    {
        try
        {
            $language = $this->context->language->id;
            $shop = $this->context->shop->id;

            $qry = (new DbQuery())
                        ->select('id_related')
                        ->from($this->table_related)
                        ->where('`id_parent`='.$pageId);

            $result = Db::getInstance()->ExecuteS($qry);

            if (!$result)
                return [];

            $final = [];
            foreach ($result as $res)
            {
                array_push($final, $res['id_related']);
            }
            return $final;
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getRelatedPagesForPage Exception: {$e->getMessage()}");
            return null;
        }
    }


    /**
     * Opposite of getRelatedPages, returns pages that this page is marked to be related to
     */
    private function getPagesThisPageIsRelatedTo($pageId)
    {
        try
        {
            $language = $this->context->language->id;
            $shop = $this->context->shop->id;

            $qry = (new DbQuery())
                        ->select('id_parent')
                        ->from($this->table_related)
                        ->where('`id_related`='.$pageId);

            $result = Db::getInstance()->ExecuteS($qry);

            if (!$result)
                return [];

            $final = [];
            foreach ($result as $res)
            {
                array_push($final, $res['id_parent']);
            }
            return $final;
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getPagesThisPageIsRelatedTo Exception: {$e->getMessage()}");
            return null;
        }
    }




    /****************************************
     *       Helper/Utility Functions       *
     ****************************************/

    /**
     * Returns a single page as a class (for use in the page.php file)
     */
    public function getHTMLPageAsAClass($pageId)
    {
        try
        {
            $pages = $this->getAllHTMLPages();
            $allPages = $this->convertToClasses($pages);

            $shopURL = $this->getShopURL($this->context);

            $found = null;
            foreach ($allPages as $p) {
                $p->link = $shopURL.$p->url;
                if ($p->id == $pageId)
                    $found = $p;
            }


            return $found;
        }
        catch (Exception $e)
        {
            Logger::addLog("CustomHTMLPages getHTMLPage Exception: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Returns the route
     */
    public function getRouteForPage($page)
    {
        $rule = $page->url;
        $route = [
            'controller' => 'page',
            'rule' => $rule.'{e:/}',
            'keywords' => [
                'e' => ['regexp' => '']
            ],
            'params' => [
                'fc' => 'module',
                'module' => $this->name,
                'id_page' => $page->id,
                'url' => $rule,
            ]
        ];

        return $route;
    }


    /**
     * Returns the route rule for a page (recurses through parents)
     * For displaying on the table page
     */
    public function getFullURLForPage(&$page, &$allPages)
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
                $prefix = $this->getFullURLForPage($parent, $allPages).'/';
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
            'id' => $page['id_page'],
            'id_page' => $page['id_page'],
            'name' => $page['name'],
            'url' => $page['url'],
            'id_parent' => $page['id_parent'],
            'style' => $page['style'],
            'active' => $page['active'],
            'id_products' => $this->explode($page['id_products']),
            'id_categories' => $this->explode($page['id_categories']),
            'id_relatedTo' => $this->getPagesThisPageIsRelatedTo($page['id_page']),
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
     * Default values for a new page
     */
    private function getDefaultValues()
    {
        return [
            'id_page' => 'new',
            'id' => 'new',
            'name' => '',
            'url' => '',
            'style' => '',
            'active' => 1,
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'id_products' => [],
            'id_categories' => [],
            'id_relatedTo' => [],
        ];
    }


    private function explode($data)
    {
        return (is_null($data) || empty($data)) ? [] : explode(',', $data);
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
            $classesById[$id] = new CustomHTMLPageModel($page, $this->context);
        }

        // Populate children
        foreach ($pages as $page)
        {
            $pageClass = $classesById[$page['id_page']];

            // Add this page as a child
            if (array_key_exists('id_parent', $page) && !is_null($page['id_parent']) && $page['id_parent'] != 0)
            {
                $parent = $classesById[$page['id_parent']];
                $parent->addChild($pageClass);
            }

            // Mark related pages
            $relatedPages = $this->getRelatedPagesForPage($page['id_page']);
            foreach ($relatedPages as $relatedPage)
            {
                $relatedPageClass = $classesById[$relatedPage];
                $pageClass->addRelated($relatedPageClass);
            }
        }

        return $classesById;
    }


    /**
     * Converts the pages to a tree for the sitemap
     */
    public function convertToTree($pages)
    {
        if (!is_array($pages) || count($pages) == 0)
            return;

        $classes = (gettype($pages[0]) == 'array') ? $this->convertToClasses($pages) : $pages;

        $roots = [];

        foreach ($classes as $page) {
            $page->computeFullURL();
            if ($page->parent == null) {
                array_push($roots, $page);
            }
        }

        $url = $this->getShopURL($this->context);

        // Clear all parent links
        foreach ($classes as $page) {
            $page->url = $url.'/'.$page->url;
            $page->parent = null;
        }

        return $roots;
    }


    /**
     * Gets the shop URL
     */
    private function getShopURL($context)
    {
        $url = 'http://'.$context->shop->domain;

        if (isset($context->shop->domain_ssl) && !empty($context->shop->domain_ssl))
            $url = 'https://'.$context->shop->domain_ssl;

        return $url.'/';
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
                    `style` LONGTEXT DEFAULT NULL,
                    `id_products` LONGTEXT DEFAULT NULL,
                    `id_categories` LONGTEXT DEFAULT NULL,
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
                    PRIMARY KEY (  `id_page`, `id_lang` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        $sql3 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_related.'` (
                    `id_parent` INT( 12 ) NOT NULL,
                    `id_related` INT( 12 ) NOT NULL,
                    PRIMARY KEY (  `id_parent`, `id_related` )
                ) ENGINE =' ._MYSQL_ENGINE_;

        if (!Db::getInstance()->Execute($sql) || !Db::getInstance()->Execute($sql2) || !Db::getInstance()->Execute($sql3))
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
            ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_lang.'`') ||
            ! Db::getInstance()->Execute('DROP TABLE`'._DB_PREFIX_.$this->table_related.'`'))
        {
            return false;
        }

        return true;
    }
}