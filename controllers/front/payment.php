<?php

include_once(dirname(__FILE__).'/../../alipay.php');

class TinlikePaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;
		if (!$this->module->checkCurrency($cart))
			Tools::redirect('index.php?controller=order');

        $address = new Address((int)($cart->id_address_delivery));
		$customer = new Customer((int)($cart->id_customer));
		$currency = new Currency((int)($cart->id_currency));
		
		$alipay_config = array(
			'service' => $this->module->service,
			'partner' => $this->module->partner,
			'key' => $this->module->key,
			'notify_url' => $this->module->notify_url,
			'return_url' => $this->module->return_url,
			'seller_email' => $this->module->seller_email,
		);
		
		$productsinfo = $cart->getProducts(true);
		
		$alipay_params = array(
		    'out_trade_no' => (int)($cart->id).'_'.date('YmdHis').'_'.$cart->secure_key,
			'subject' => date('Ymdhms'),
			'logistics_type' => 'EXPRESS',
			'logistics_fee' => '0.00',
			'logistics_payment' => 'SELLER_PAY',
			'price' => $cart->getOrderTotal(true, Cart::BOTH),
			'quantity' => '1',
			'body' => $productsinfo[0]['description_short'],
			'receive_name' => $address->firstname . $address->lastname,
			'receive_address' => $address->address1 . $address->address2,
			'receive_zip' => $address->postcode,
			'receive_phone' => $address->phone,
			'receive_mobile' => $address->phone_mobile,
			//'show_url' => $this->module->show_url,
		);
		
		$alipay = new Alipay($alipay_config);
		$para = $alipay->buildRequestPara($alipay_params);
		
		$this->context->smarty->assign(array(
		    'aliparams' => $para,
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
			'this_path_ssl' => $alipay->alipay_gateway_new."_input_charset=".trim(strtolower($alipay->input_charset))
		));

		$this->setTemplate('payment_execution.tpl');
	}
}