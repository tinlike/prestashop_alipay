<?php

if (!defined('_PS_VERSION_'))
	exit;

class Tinlike extends PaymentModule
{
    private $_html = '';
	private $_postErrors = array();
	
	public $service = "trade_create_by_buyer";
	public $partner;
	public $key;
	public $seller_email;
	
	public $notify_url;
	public $return_url;

	
	public function __construct()
	{
		$this->name = 'tinlike';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		
		$config = Configuration::getMultiple(array('ALIPAY_SERVICE', 'ALIPAY_PARTNER', 'ALIPAY_KEY','ALIPAY_SELLER_EMAIL'));
		if (!empty($config['ALIPAY_SERVICE']))
			$this->service = $config['ALIPAY_SERVICE'];
		if (!empty($config['ALIPAY_PARTNER']))
			$this->partner = $config['ALIPAY_PARTNER'];
		if (!empty($config['ALIPAY_KEY']))
			$this->key = $config['ALIPAY_KEY'];
		if (!empty($config['ALIPAY_SELLER_EMAIL']))
			$this->seller_email = $config['ALIPAY_SELLER_EMAIL'];
		
		$this->notify_url = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'module/'.$this->name.'/validation';
		$this->return_url = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'module/'.$this->name.'/validation';
		

		$this->controllers = array('payment', 'validation');
		$this->bootstrap = true;
		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Aipay Module');
		$this->description = $this->l('Accepts payments by Alipay.');
		$this->author = 'www.tinlike.com';
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
		if (!isset($this->partner) OR !isset($this->key) OR !isset($this->seller_email) OR !isset($this->service))
			$this->warning = $this->l('Account owner and details must be configured in order to use this module correctly');
	}

	public function install()
	{
		if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
			return false;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
			
        Configuration::deleteByName('ALIPAY_SERVICE');
		Configuration::deleteByName('ALIPAY_PARTNER');
		Configuration::deleteByName('ALIPAY_KEY');
		Configuration::deleteByName('ALIPAY_SELLER_EMAIL');

		return true;
	}
	
	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{		
            Configuration::updateValue('ALIPAY_SERVICE', Tools::getValue('ALIPAY_SERVICE'));		
			Configuration::updateValue('ALIPAY_PARTNER', Tools::getValue('ALIPAY_PARTNER'));
			Configuration::updateValue('ALIPAY_KEY', Tools::getValue('ALIPAY_KEY'));
			Configuration::updateValue('ALIPAY_SELLER_EMAIL', Tools::getValue('ALIPAY_SELLER_EMAIL'));
			
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	private function _displayBankWire()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}
	
	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('ALIPAY_SELLER_EMAIL'))
				$this->_postErrors[] = $this->l('Seller email is required.');
			if (!Tools::getValue('ALIPAY_PARTNER'))
				$this->_postErrors[] = $this->l('Partner ID is required.');
		    if (!Tools::getValue('ALIPAY_KEY'))
				$this->_postErrors[] = $this->l('Security code is required.');
		}
	}
	
	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';
		
		$this->_html .= $this->_displayBankWire();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Account details'),
					'icon' => 'icon-envelope'
				),
				'input' => array(
				
				    array(
                        'type' => 'select',
                        'label' => $this->l('Service Type'),
                        'desc' => $this->l('Choose a service type'),
                        'name' => 'ALIPAY_SERVICE',
                        'options' => array(
                            'query' => array(
							     array(
							         'id_option' => 'trade_create_by_buyer',
								     'name' => 'trade create by buyer',
								),
								array(
								      'id_option' => 'create_partner_trade_by_buyer',
									  'name' => 'create partner trade by buyer',
							    ),
							),
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),

					array(
						'type' => 'text',
						'label' => $this->l('Seller Email'),
						'name' => 'ALIPAY_SELLER_EMAIL',
						'desc' => $this->l('Your Alipay account.')
					),
					array(
						'type' => 'text',
						'label' => $this->l('Partner ID'),
						'name' => 'ALIPAY_PARTNER',
						'maxlength' => '16',
					),
					array(
						'type' => 'text',
						'label' => $this->l('Security Code'),
						'name' => 'ALIPAY_KEY',
						'maxlength' => '32',
						'desc' => $this->l('Your security code.')
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
		    'ALIPAY_SERVICE' => Tools::getValue('ALIPAY_SERVICE', Configuration::get('ALIPAY_SERVICE')),
			'ALIPAY_PARTNER' => Tools::getValue('ALIPAY_PARTNER', Configuration::get('ALIPAY_PARTNER')),
			'ALIPAY_KEY' => Tools::getValue('ALIPAY_KEY', Configuration::get('ALIPAY_KEY')),
			'ALIPAY_SELLER_EMAIL' => Tools::getValue('ALIPAY_SELLER_EMAIL', Configuration::get('ALIPAY_SELLER_EMAIL')),
		);
	}
	
	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_tinlike' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		global $smarty;
		
		switch($params['objOrder']->getCurrentState())
		{
			case Configuration::get('PS_OS_PAYMENT'):
			case Configuration::get('PS_OS_OUTOFSTOCK'):
				$smarty->assign('status', 'ok');
				break;

			case Configuration::get('PS_OS_BANKWIRE'):
				$smarty->assign('status', 'pending');
				break;

			case Configuration::get('PS_OS_ERROR'):
			default:
				$smarty->assign('status', 'failed');
				break;
		}

		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
}