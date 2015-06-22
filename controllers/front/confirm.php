<?php

class BitbayarConfirmModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	public function initContent()
	{
		global $smarty;
		parent::initContent();

		$total = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);
		$customer_currency = $this->context->currency->iso_code;
		$button_pay = Configuration::get('BITBAYAR_BUTTON');
		
		// Display.
		$this->context->smarty->assign(array(
			'total' => $total,
			'button_pay' => Configuration::get('BITBAYAR_BUTTON'),
			'this_path' => $this->module->getPathUri(),//keep for retro compat
			'this_path_cod' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('confirm.tpl');
	}
}