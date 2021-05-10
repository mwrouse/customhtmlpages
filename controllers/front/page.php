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


        $page = $this->module->getHTMLPageAsAClass($pageId);

        if (is_null($page))
        {
            return "Custom HTML Page Not Found";
        }

        if (!is_null($page->css)) {
            $header = $this->context->smarty->getTemplateVars('HOOK_HEADER');

            $header .= '<!-- Custom HTML Page Styling -->
            <style>' . $page->css . '</style>';

            $this->context->smarty->assign([
                'HOOK_HEADER' => $header
            ]);
        }


        $this->context->smarty->assign([
            'meta_title' => $page->meta_title . ' - ' . $this->context->shop->name,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'page' => $page,

        ]);

        return $this->setTemplate('page.tpl');
    }
}