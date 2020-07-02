<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once(_PS_MODULE_DIR_ . 'spottercommtxmlgenerator/XMLGenerator.php');

class Spottercommtxmlgenerator extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'spottercommtxmlgenerator';
        $this->tab = 'export';
        $this->version = '0.0.1';
        $this->author = 'spotter.com.mt';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;


        $this->displayName = 'spotter.com.mt - XML generator';
        $this->description = 'Automatically generates the needed XML to connect your shop to spotter.com.mt';
        $this->confirmUninstall = 'You will not be able to connect your shop to 
        spotter.com.mt. Are you sure you want uninstall this module?';
        parent::__construct();
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SPOTTERCOMMTXMLGENERATOR_LIVE_MODE', true);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('actionProductSave') &&
            $this->generateXML('install');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SPOTTERCOMMTXMLGENERATOR_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }


    public function hookActionProductDelete()
    {
        $this->generateXML('hookActionProductDelete');
    }

    public function hookActionProductSave()
    {
        $this->generateXML('hookActionProductSave');
    }

    public function generateXML($from = null)
    {
//        var_dump($from);
        $languages = Language::getLanguages(true, $this->context->shop->id);
        $englishId = 1;
        foreach ($languages as $language) {
            if ($language['iso_code'] === 'en') {
                $englishId = (int)$language['id_lang'];
            }
        }


        $xml = new XMLGenerator('<?xml version="1.0" encoding="utf-8"?><webstore/>');
//        $now = date('Y-n-j G:i');
//        $xml->addChild('created_at', "$now");
//        $products = $xml->addChild('products');

        $all_products = Product::getProducts($englishId, 0, 3000, 'id_product', 'ASC', false, true);
////        var_dump($all_products);
//        $file = fopen(_PS_ROOT_DIR_ . "/demo.json", "w");
//        fwrite($file, json_encode($all_products));
//        fclose($file);
        foreach ($all_products as $product) {
            var_dump($product);
//                            var_dump(Context::getContext()->link->getProductLink(1));
            $id_product = $product['id_product'];
            $name = $product['name'];
            $manufacturer_name = $product['manufacturer_name'];
            $description = $product['description'];
//            $available_for_order = $product['available_for_order'];
            $isbn = $product['isbn'];
            $ean13 = $product['ean13'];
            $available_for_order = $product['available_for_order'];
            $additional_shipping_cost = $product['additional_shipping_cost'];
            $category = $this->generateProductCategories($product['id_category_default']);
            $link = Context::getContext()->link->getProductLink($product['id_product']);
            $image = Image::getCover($product['id_product']);
            $imagePath = Context::getContext()->link->getImageLink($product['link_rewrite'], $image['id_image']);
            $realPrice = Product::getPriceStatic($id_product);
//            $realPrice = Product::getPriceStatic($id_product);

            if ($realPrice !== 0 && $available_for_order == 1) {
                $product = $xml->addChild('product');
                $product->sku = null;
                $product->sku->addCData($id_product);
                $product->name = null;
                $product->name->addCData($name);
                $product->manufacturer = null;
                $product->manufacturer->addCData($manufacturer_name);
                $product->category = null;
                $product->category->addCData($category);
                $product->price_with_vat = null;
                $product->price_with_vat->addCData($realPrice);
                $product->shipping_cost = null;
                $product->shipping_cost->addCData($additional_shipping_cost);
                $product->instock = null;
                $product->instock->addCData("Y");
                $product->isbn = null;
                $product->isbn->addCData($isbn);
                $product->ean = null;
                $product->ean->addCData($ean13);
//                $product->weight = null;
//                $product->weight->addCData(($row['variants'][$variantKey]['weight_unit'] === 'kg') ?
//                    $row['variants'][$variantKey]['weight'] * 1000 : $row['variants'][$variantKey]['weight_unit']);
                $product->url = null;
                $product->url->addCData($link);
                $product->image = null;
                $product->image->addCData($imagePath);
                $product->description = null;
                $product->description->addCData($description);
//                foreach ($row['images'] as $image) {
//                    if ($image['id'] !== $row['image']['id']) {
//                        $product->addChild('additional_image', $image['src']);
//                    }
//                }
//                foreach ($row['options'] as $option) {
//                    switch (strtolower($option['name'])) {
//                        case 'size':
//                            $product->size = null;
//                            $product->size->addCData(implode(',', $option['values']));
//                            break;
//                        case 'color':
//                            $product->color = null;
//                            $product->color->addCData(implode(',', $option['values']));
//                            break;
//                    }
//                }
            }
        }
        $xml->saveXML(_PS_ROOT_DIR_ . '/spotter.xml');
//        $xml->saveXML();
//        echo Product::getPriceStatic(1);
//        echo json_encode(Product::getProductCategoriesFull(1));
//        echo Context::getContext()->link->getProductLink(1);
//        echo Product::getSimpleProducts(1);
        return true;
    }

    private function generateProductCategories($productCategoryId)
    {
        return strip_tags(Tools::getPath('/', $productCategoryId, true));
    }
}
