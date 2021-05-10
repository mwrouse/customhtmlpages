<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminCustomHTMLPagesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id_page';
        $this->table = 'customhtmlpages';
        $this->className = 'customhtmlpages';

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_flag'] = [
                'href' => static::$currentIndex.'&configure=&id_flag=new&updat'.$this->className.'&token='.$this->token,
                'desc' => $this->l('Add New Page', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

     /**
     * List of all the Custom HTML Pages
     */
    public function renderList()
    {
        $pages = $this->module->getAllHTMLPages(true /* get inactive as well */);
        $content = '';

        if (!$pages)
            return $content;

        foreach ($pages as $i => $page)
        {
            $this->module->getRouteForPage($pages[$i], $pages);
        }

        $fieldsList = [
            'id_page'  => [
                'title'   => 'ID',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'name'      => [
                'title'   => $this->l('Name'),
            ],
            'full_url' => [
                'title' => $this->l('Full URL'),
            ],
            'active'    => [
                'title'   => $this->l('Status'),
                'active'  => 'status',
                'type'    => 'bool',
            ]
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit", "delete"];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->listTotal = count($pages);
        $helper->identifier = 'id_page';
        $helper->position_identifier = 'id_page';
        $helper->title = "Custom Pages";
        $helper->orderBy = 'id_page';
        $helper->orderWay = 'ASC';
        $helper->table = $this->table;
        $helper->token = Tools::getAdminTokenLite('AdminCustomHTMLPages');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $content .= $helper->generateList($pages, $fieldsList);

        return $content;
    }
}