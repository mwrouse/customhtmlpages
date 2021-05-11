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
        $product = null;
        $products = [];

        $productId = Tools::getValue('id_product', null); // From the URL
        if (isset($productId) && !is_null($productId))
        {
            $product = $this->_GetProduct($productId);
        }

        if (count($page->_products) > 0)
        {
            foreach ($page->_products as $productId) {
                array_push($products, $this->_GetProduct($productId));
            }

            if (is_null($product) && count($products) == 1)
                $product = array_shift($products);
        }

        // Add $category or $categories
        $category = null;
        $categories = [];

        $categoryId = Tools::getValue('id_category', null); // From the URL
        if (isset($categoryId) && !is_null($categoryId))
        {
            $category = $this->_GetCategory($categoryId);
        }

        if (count($page->_categories) > 0)
        {
            foreach ($page->_categories as $categoryId) {
                array_push($categories, $this->_GetCategory($categoryId));
            }

            if (is_null($category) && count($categories) == 1) {
                $category = array_shift($categories);
            }
        }

        $this->context->smarty->assign([
            'page' => $page,
            'product' => $product,
            'products' => $products,
            'category' => $category,
            'categories' => $categories,
        ]);

        $page->meta_title = $this->eval($page->meta_title);
        $page->meta_description = $this->eval($page->meta_description);
        $page->meta_keywords = $this->eval($page->meta_keywords);

        $this->context->smarty->assign([
            'meta_title' => $page->meta_title . ' - ' . $this->context->shop->name,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
        ]);

        $page->content = $this->eval($page->content);

        return $this->setTemplate('page.tpl');
    }


    private function _GetProduct($id, $loadExtra = true)
    {
        $lang = $this->context->language;
        $product = new Product($id, true, $lang->id, $this->context->shop->id, $this->context);

        $product->id_image = $product->getCoverWs();

        if ($loadExtra)
        {
            $product->attachments = $product->getAttachments($lang->id);

            $product->features = $product->getFrontFeatures($lang->id);

        }

        return $product;
    }

    private function _GetCategory($id)
    {
        $lang = $this->context->language;
        $shop = $this->context->shop;

        $category = new Category($id, $lang->id, $shop->id);

        $category->subcategories = $category->getSubCategories($lang->id);
        $category->products = $category->getProductsWs();

        for($i = 0; $i < count($category->products); $i++)
        {
            $category->products[$i] = $this->_GetProduct($category->products[$i]['id'], false);
        }

        return $category;
    }


    private function eval($str)
    {
        return $this->context->smarty->fetch('eval:'.$str);
    }
}