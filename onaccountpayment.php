<?php
/*
* 2007-2017 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Onaccountpayment extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'onaccountpayment';
        $this->author = 'Fresh Wave';
        $this->version = '1.0.0';
        $this->need_instance = 1;

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;
        $this->currencies = false;

        parent::__construct();

        $this->displayName = $this->trans('On account', array(), 'Modules.Onaccountpayment.Admin');
        $this->description = $this->trans('Accept on account payments', array(), 'Modules.Onaccountpayment.Admin');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions')) {
            return false;
        }
        return true;
    }

    public function hasProductDownload($cart)
    {
        $products = $cart->getProducts();

        if (!empty($products)) {
            foreach ($products as $product) {
                $pd = ProductDownload::getIdFromIdProduct((int)($product['id_product']));
                if ($pd and Validate::isUnsignedInt($pd)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        // Check if cart has product download
        if ($this->hasProductDownload($params['cart'])) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('On account', array(), 'Modules.Onaccountpayment.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:onaccountpayment/views/templates/hook/onaccountpayment_intro.tpl'));

        $payment_options = [
            $newOption,
        ];
        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['order']->getCurrentState();

        if ($state) {
            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'status' => 'ok',
                'reference' => $params['order']->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
        } else {
            $this->smarty->assign(array(
                'status' => 'failed',
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
        }
        return $this->fetch('module:onaccountpayment/views/templates/hook/payment_return.tpl');
    }
}
