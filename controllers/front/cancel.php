<?php

class BitbayarCancelModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();

		// Get callback data.
		$total = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);

		if (!$this->context->cart->id)
			Tools::redirect('/');

		// Display.
		$this->context->smarty->assign(array(
			'total' => $total
		));

		$this->setTemplate('cancel.tpl');
	}
}