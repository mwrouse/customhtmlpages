<?php
if (!defined('_TB_VERSION_')) {
    exit;
}

class CustomHtmlPagesPageModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        $lang = $this->context->language;

        $pageId = Tools::getValue('id_page');
        if (!isset($pageId) || is_null($pageId))
        {
            return "Custom HTML Page Not Found.";
        }


        $category = new Category(20, $this->context->language->id);
        $products = $category->getProducts($this->context->language->id, 1, 50, null, null, false, true, false, 8);

        $page = $this->module->getHTMLPageAsAClass($pageId);

        if (is_null($page))
        {
            return "Custom HTML Page Not Found";
        }

        // Add CSS
        if (!is_null($page->css)) {
            $header = $this->context->smarty->getTemplateVars('HOOK_HEADER');

            $header .= '<!-- Custom HTML Page Styling -->
            <style>' . $page->css . '</style>';

            $this->context->smarty->assign([
                'HOOK_HEADER' => $header
            ]);
        }

        // Add $product or $products
        $productId = Tools::getValue('id_product'); // From the URL
        $product = null;
        if (isset($productId) || count($page->products) == 1)
        {
            $id = (count($page->products) == 1) ? $page->products[0] : $productId;

            $product = $this->_GetProduct($id);
        }

        $products = [];
        if (count($page->products) > 1) {

            foreach ($page->products as $productId) {
                array_push($products, $this->_GetProduct($productId));
            }

            $this->context->smarty->assign([
                'products' => $products
            ]);
        }


        $this->context->smarty->assign([
            'meta_title' => $page->meta_title . ' - ' . $this->context->shop->name,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'page' => $page,
            'product' => $product,
            'products' => $products,
        ]);

        return $this->setTemplate('page.tpl');
    }


    private function _GetProduct($id)
    {
        $lang = $this->context->language;
        $product = new Product($id, true, $lang->id, $this->context->shop->id, $this->context);
        $product->attachments = $product->getAttachments($lang->id);
        $product->features = $product->getFeatures($lang->id);

        return $product;
    }
}