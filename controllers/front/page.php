<?php
if (!defined('_TB_VERSION_')) {
    exit;
}

class CustomHtmlPagesPageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $pageId = Tools::getValue('id_page');
        if (!isset($pageId) || is_null($pageId))
        {
            return "Custom HTML Page Not Found.";
        }

        $allPages = $this->module->convertToClasses($this->module->getAllHTMLPages());

        $page = null;

        foreach ($allPages as $p) {
            if ($p->id == $pageId) {
                $page = $p;
                break;
            }
        }

        if (is_null($page))
        {
            return "Custom HTML Page Not Found";
        }

        $this->context->smarty->assign([
            'meta_title' => $page->meta_title . ' - ' . $this->context->shop->name,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'page' => $page
        ]);

        return $this->setTemplate('page.tpl');
    }
}