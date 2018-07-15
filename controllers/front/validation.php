<?php
 
include_once(dirname(__FILE__).'/../../alipay.php');

class TinlikeValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'tinlike')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));
			
		$customer = new Customer($cart->id_customer);
		
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');
			
		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		
		$status = Configuration::get('PS_OS_ERROR');
		
		$alipay_config = array(
			'service' => $this->module->service,
			'partner' => $this->module->partner,
			'key' => $this->module->key,
		);
		
		$alipay = new Alipay($alipay_config);
		$verify_result = $alipay->verifyReturn();

		if($verify_result)
		{
            $status = Configuration::get('PS_OS_PAYMENT');
		}

		$this->module->validateOrder($cart->id, $status, $total, $this->module->displayName, NULL, array(), (int)$currency->id, false, $customer->secure_key);
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
