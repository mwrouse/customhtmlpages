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
            $this->page_header_toolbar_btn['new_page'] = [
                'href' => static::$currentIndex.'&configure=&id_page=new&update'.$this->className.'&token='.$this->token,
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

        $fieldsList = [
            'id_page'  => [
                'title'   => 'ID',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
            ],
            'name'      => [
                'title'   => $this->l('Name'),
            ],
            'url' => [
                'title' => $this->l('URL'),
            ],
            'active'    => [
                'title'   => $this->l('Active'),
                'active'  => 'status',
                'type'    => 'bool',
            ]
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ["edit", "delete", ];
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


    /**
     * Form for editing a page
     */
    public function renderForm()
    {
        $inputs[] = [
            'type'   => 'switch',
            'label'  => $this->l("Active"),
            'name'   => 'active',
            'values' => [
                [
                    'id'    => 'active_on',
                    'value' => 1,
                ],
                [
                    'id'    => 'active_off',
                    'value' => 0,
                ],
            ]
        ];
        $inputs[] = [
            'type'  => 'text',
            'label' => $this->l('Page Name'),
            'name'  => 'name',
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l('Meta Title'),
            'name' => 'meta_title_lang',
            'required' => true,
            'id' => 'name',
            'lang' => true,
            'class' => 'copyMeta2friendlyURL',
            'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l('Meta Description'),
            'name' => 'meta_description_lang',
            'lang' => true,
            'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
        ];
        $inputs[] = [
            'type' => 'tags',
            'label' => $this->l('Meta Keywords'),
            'name' => 'meta_keywords_lang',
            'lang' => true,
            'hint'  => [
                $this->l('To add "tags" click in the field, write something, and then press "Enter."'),
                $this->l('Invalid characters:').' &lt;&gt;;=#{}',
            ],
        ];
        $inputs[] = [
            'type' => 'text',
            'label' => $this->l('Friendly URL'),
            'name' => 'url',
            'required' => true,
            'hint'     => $this->l('Only letters and the hyphen (-) character are allowed.'),
        ];
        $inputs[] = [
            'type'  => 'textarea',
            'label' => $this->l('Content'),
            'name'  => 'content_lang',
            'lang'  => true,
            'autoload_rte' => true,
        ];

        if ($this->display == 'edit') {
            $inputs[] = [
                'type' => 'hidden',
                'name' => 'id_page'
            ];
            $title = $this->l('Edit Page');
            $action = 'submitEditCustomHTMLPage';
            $this->fields_value = $this->module->getHTMLPage(Tools::getValue('id_page'));

        }
        else {
            $title = $this->l('New Page');
            $action = 'submitAddCustomHTMLPage';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $title,
                'icon'  => 'icon-cogs',
            ],
            'input' => $inputs,
            'buttons' => [
                'save-and-stay' => [
                    'title' => $this->l('Save and Stay'),
                    'class' => 'btn btn-default pull-right',
                    'name' => $action.'AndStay',
                    'icon' => 'process-icon-save',
                    'type' => 'submit'
                ]

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name'  => $action,
            ],

        ];

        return parent::renderForm();
    }



    /**
     * When the add/edit form is submitted
     */
    public function postProcess()
    {
        $pageId = Tools::getValue('id_page');
        if (Tools::isSubmit('submitEditCustomHTMLPage') || Tools::isSubmit('submitEditCustomHTMLPageAndStay')) {
            if ($pageId == 'new')
                $this->processAdd();
            else
                $this->processUpdate();
        }
        else if (Tools::isSubmit('status'.$this->table)) {
            $this->toggleStatus();
        }
        else if (Tools::isSubmit('delete'.$this->table)) {
            $this->processDelete();
        }
    }


    /**
     * Adding a new page
     */
    public function processAdd()
    {
        $name = Tools::getValue('name');
        $saveAndStay = Tools::isSubmit('submitEditCustomHTMLPageAndStay');

        $shop = $this->context->shop->id;

        $pageId = null;

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $active = Tools::getValue('active');
            $url = Tools::getValue('url');

            $result = Db::getInstance()->insert(
                $this->module->table_name,
                [
                    'name' => $name,
                    'id_shop' => $shop,
                    'active' => $active,
                    'url' => $url,
                ]
            );

            if (!result) {
                $this->_errors[] = $this->l("Error while adding new Custom HTML Page, please try again.");
            }
            else {
                $pageId = Db::getInstance()->Insert_ID();

                foreach ($this->getLanguages() as $lang) {
                    $content = Tools::getValue('content_lang_' . $lang['id_lang']);
                    $metaTitle = Tools::getValue('meta_title_lang_'.$lang['id_lang']);
                    $metaDesc = Tools::getValue('meta_description_lang_'.$lang['id_lang']);
                    $metaKeywords = Tools::getValue('meta_keywords_lang_'.$lang['id_lang']);

                    $result =Db::getInstance()->insert(
                        $this->module->table_lang,
                        [
                            'id_page' => (int)$pageId,
                            'id_lang' => $lang['id_lang'],
                            'id_shop' => $shop,
                            'meta_title' => $metaTitle,
                            'meta_description' => $metaDesc,
                            'meta_keywords' => $metaKeywords,
                            'content' => $content
                        ]
                    );

                    if (!$result) {
                        $this->_errors[] = $this->l('Error when adding new Custom HTML Page content');
                    }
                }
            }
        }


        if (empty($this->_errors)) {
            if (!$saveAndStay && $pageId != null) {
                $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
            }
            else {
                // Have to go to the edit page now
                $this->redirect_after = static::$currentIndex . '&configure=&id_page='. $pageId . '&update'.$this->className.'&token=' . $this->token;
            }
        }
    }


    /**
     * Updating a page
     */
    public function processUpdate()
    {
        $pageId = Tools::getValue('id_page');
        $saveAndStay = Tools::isSubmit('submitEditCustomHTMLPageAndStay');

        $shop = $this->context->shop->id;

        $name = Tools::getValue('name');

        if (!$name || !Validate::isGenericName($name)) {
            $this->_errors[] = $this->l('Invalid Name');
        }
        else {
            $active = Tools::getValue('active');
            $url = Tools::getValue('url');

            $result = Db::getInstance()->update($this->module->table_name,
                [
                    'name' => $name,
                    'active' => $active,
                    'url' => $url,
                ],
                'id_page ='. (int)$pageId
            );

            if (!$result) {
                $this->_errors[] = $this->l('Error while updating Custom HTML Page Name and Status');
            }
            else {
                foreach ($this->getLanguages() as $lang) {
                    $content = Tools::getValue('content_lang_' . $lang['id_lang']);
                    $metaTitle = Tools::getValue('meta_title_lang_'.$lang['id_lang']);
                    $metaDesc = Tools::getValue('meta_description_lang_'.$lang['id_lang']);
                    $metaKeywords = Tools::getValue('meta_keywords_lang_'.$lang['id_lang']);

                    $isLangAdded = Db::getInstance()->getValue('SELECT id_page FROM '._DB_PREFIX_.$this->module->table_lang.' WHERE (id_page='.(int)$pageId.' AND id_lang='.$lang['id_lang'].' AND id_shop='.$shop.')');
                    if (!$isLangAdded) {
                        Db::getInstance()->insert(
                            $this->module->table_lang,
                            [
                                'id_page' => (int)$pageId,
                                'id_lang' => $lang['id_lang'],
                                'id_shop' => $shop,
                                'meta_title' => $metaTitle,
                                'meta_description' => $metaDesc,
                                'meta_keywords' => $metaKeywords,
                                'content' => $content
                            ]
                        );
                    }
                    else {
                        $result = Db::getInstance()->update($this->module->table_lang,
                            [
                                'id_lang' => $lang['id_lang'],
                                'id_shop' => $shop,
                                'meta_title' => $metaTitle,
                                'meta_description' => $metaDesc,
                                'meta_keywords' => $metaKeywords,
                                'content' => $content
                            ],
                            'id_page ='. (int)$pageId
                        );

                        if (!$result) {
                            $this->_errors[] = $this->l('Error while updating Custom HTML Page Content');
                        }
                    }
                }
            }

            if (empty($this->_errors)) {
                if (!$saveAndStay) {
                    $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
                }
                else {
                    $this->redirect_after = static::$currentIndex . '&configure=&id_page='. $pageId . '&update'.$this->className.'&token=' . $this->token;
                }
            }
        }
    }


    /**
     * Turning a page on or off from the main table
     */
    public function toggleStatus()
    {
        $pageId = Tools::getValue('id_page');

        Db::getInstance()->update(
            $this->module->table_name,
            ['active' => !$this->module->getHTMLPageStatus($pageId)],
            'id_page = ' . $pageId
        );
    }


    /**
     * Deleting a page from the main table
     */
    public function processDelete()
    {
        $pageId = Tools::getValue('id_page');

        Db::getInstance()->delete($this->module->table_name, 'id_page='.$pageId);
        Db::getInstance()->delete($this->module->table_lang, 'id_page='.$pageId);

        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
    }



    public function renderView()
    {
        $this->tpl_view_vars['object'] = $this->loadObject();

        return parent::renderView();
    }
}