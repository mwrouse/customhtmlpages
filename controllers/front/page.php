<?php
if (!defined('_TB_VERSION_')) {
    exit;
}

class CustomHtmlPagesPageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $page = Tools::getValue('page');
        if (!isset($page) || is_null($page))
        {
            return "Custom HTML Page Not Found.";
        }

        $this->context->smarty->assign([
            'meta_title' => $page['meta_title'] . ' - ' . $this->context->shop->name,
            'meta_description' => $page['meta_description'],
            'meta_keywords' => $page['meta_keywords'],
            'page' => $page
        ]);

        return $this->setTemplate('page.tpl');
    }
}