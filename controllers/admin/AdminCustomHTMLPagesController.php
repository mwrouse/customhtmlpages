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

        foreach ($pages as $i => $page) {
            $pages[$i]['full_url'] = $this->module->getFullURLForPage($pages[$i], $pages);
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
        $helper->orderBy = 'full_url';
        $helper->orderWay = 'DESC';
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
        $lang = $this->context->language;

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
            'type' => 'text',
            'label' => $this->l('Breadcrumb URL Parameters'),
            'name' => 'breadcrumb_parameters',
            'required' => false,
            'hint'     => $this->l('Parameters to be applied when rendering as a breadcrumb'),
        ];

        $inputs[] = [
            'type'  => 'code',
            'mode' => 'css',
            'label' => $this->l('Style'),
            'name'  => 'style',
            'lang'  => false,
            //'autoload_rte' => true,
            'id' => 'style',
            'enableBasicAutocompletion' => true,
            'enableSnippets' => true,
            'enableLiveAutocompletion' => true,
            'maxLines' => 50,
        ];
        $inputs[] = [
            'type'  => 'code',
            'mode' => 'html',
            'label' => $this->l('Content'),
            'name'  => 'content_lang',
            'lang'  => true,
            //'autoload_rte' => true,
            'id' => 'content',
            'enableBasicAutocompletion' => true,
            'enableSnippets' => true,
            'enableLiveAutocompletion' => true,
            'maxLines' => 70,
        ];


        $allPages = $this->module->getAllHTMLPages(true);
        array_unshift($allPages, '-');


        if ($this->display == 'edit') {
            $inputs[] = [
                'type' => 'hidden',
                'name' => 'id_page'
            ];
            $title = $this->l('Edit Page');
            $action = 'submitEditCustomHTMLPage';

            $pageId = Tools::getValue('id_page');

            $this->fields_value = $this->module->getHTMLPage($pageId);

            // Remove the current page from the list of pages
            foreach ($allPages as $i => $p) {
                if ($p != '-' && $p['id_page'] == $pageId) {
                    unset($allPages[$i]);
                    break;
                }
            }
        }
        else {

        }

        // Parent select
        $inputs[] = [
            'type' => 'select',
            'label' => $this->l('Parent'),
            'name' => 'id_parent',
            'options' => [
                'query' => $allPages,
                'id' => 'id_page',
                'name' => 'name'
            ]
        ];
        //$this->fields_value['id_relatedTo'] = [];

        array_shift($allPages);

        // List of Pages this Page is related to
        $inputs[] = [
            'type' => 'swap',
            'label' => $this->l('Show On ($page->related[])'),
            'multiple' => true,
            'name' => 'id_relatedTo',
            'options' => [
                'query' => $allPages,
                'id' => 'id_page',
                'name' => 'name'
            ],
            'hint' => $this->l('Makes this page show up on other pages (not as a child page but as a related page): $page->related[]')
        ];

        $inputs[] = [
            'type' => 'html',
            'html_content' => '<hr/>',
            'name' => 'id_page',
        ];

        // List of Products
        $products = Product::getProducts($lang->id, 0, 1000, 'id_product', 'ASC');
        $inputs[] = [
            'type' => 'swap',
            'label' => $this->l('Products ($product or $products)'),
            'name' => 'id_products',
            'multiple' => true,
            'options' => [
                'query' => $products,
                'id' => 'id_product',
                'name' => 'name'
            ],
            'hint'     => $this->l('This will populate $products. If only one is selected then $product will be populated'),
        ];

        // List of Categories
        $categories = Category::getCategories($lang->id, true, false);
        $inputs[] = [
            'type' => 'swap',
            'label' => $this->l('Categories ($category or $categories)'),
            'name' => 'id_categories',
            'multiple' => true,
            'options' => [
                'query' => $categories,
                'id' => 'id_category',
                'name' => 'name'
            ],
            'hint'     => $this->l('This will populate $categories. If only one is selected then $category will be populated'),
        ];

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
            $breadcrumb_parameters = Tools::getValue('breadcrumb_parameters');
            $parent = Tools::getValue('id_parent');
            $style = Tools::getValue('style');
            $products = Tools::getValue('id_products_selected', []);
            $categories = Tools::getValue('id_categories_selected', []);

            $result = Db::getInstance()->insert(
                $this->module->table_name,
                [
                    'name' => pSQL($name),
                    'id_shop' => $shop,
                    'active' => $active,
                    'url' => $url,
                    'breadcrumb_parameters' => $breadcrumb_parameters,
                    'style' => pSQL($style, true),
                    'id_parent' => $parent,
                    'id_products' => implode(',', $products),
                    'id_categories' => implode(',', $categories),
                ]
            );

            if (!result) {
                $this->_errors[] = $this->l("Error while adding new Custom HTML Page, please try again.");
            }
            else {
                $pageId = Db::getInstance()->Insert_ID();

                // Save related pages
                $relatedPages = Tools::getValue('id_relatedTo_selected', []);
                foreach ($relatedPages as $relatedPage)
                {
                    Db::getInstance()->insert($this->module->table_related,
                    [
                        'id_parent' => $relatedPage,
                        'id_related' => $pageId
                    ]);
                }

                // Save language content
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
                            'meta_title' => pSQL($metaTitle),
                            'meta_description' => pSQL($metaDesc),
                            'meta_keywords' => pSQL($metaKeywords),
                            'content' => pSQL($content, true)
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
            $breadcrumb_parameters = Tools::getValue('breadcrumb_parameters', 'hey');
            $parent = Tools::getValue('id_parent');
            $style = Tools::getValue('style');
            $products = Tools::getValue('id_products_selected', []);
            $categories = Tools::getValue('id_categories_selected', []);

            error_log($breadcrumb_parameters);
            $result = Db::getInstance()->update($this->module->table_name,
                [
                    'name' => pSQL($name),
                    'active' => $active,
                    'url' => $url,
                    'breadcrumb_parameters' => pSQL($breadcrumb_parameters, true),
                    'style' => pSQL($style, true),
                    'id_parent' => $parent,
                    'id_products' => implode(',', $products),
                    'id_categories' => implode(',', $categories)
                ],
                'id_page ='. (int)$pageId
            );

            if (!$result) {

                $this->_errors[] = $this->l('Error while updating Custom HTML Page Name and Status');
            }
            else {
                // Save Related Pages
                $relatedPages = Tools::getValue('id_relatedTo_selected', []);
                Db::getInstance()->delete($this->module->table_related, 'id_related='.$pageId);
                if (count($relatedPages) > 0) {
                    foreach ($relatedPages as $relatedPage) {
                        Db::getInstance()->insert($this->module->table_related, [
                            'id_parent' => $relatedPage,
                            'id_related' => $pageId
                        ]);
                    }
                }

                // Save Language Content
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
                                'meta_title' => pSQL($metaTitle),
                                'meta_description' => pSQL($metaDesc),
                                'meta_keywords' => pSQL($metaKeywords),
                                'content' => pSQL($content, true)
                            ]
                        );
                    }
                    else {
                        $result = Db::getInstance()->update($this->module->table_lang,
                            [
                                'id_lang' => $lang['id_lang'],
                                'id_shop' => $shop,
                                'meta_title' => pSQL($metaTitle),
                                'meta_description' => pSQL($metaDesc),
                                'meta_keywords' => pSQL($metaKeywords),
                                'content' => pSQL($content, true)
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
        Db::getInstance()->delete($this->module->table_related, 'id_parent='.$pageId.' OR id_related='.$pageId);

        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
    }



    public function renderView()
    {
        $this->tpl_view_vars['object'] = $this->loadObject();

        return parent::renderView();
    }
}