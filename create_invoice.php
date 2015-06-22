<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/bitbayar.php');

$context  = Context::getContext();
$cart     = $context->cart;

$bitbayar   = new bitbayar();

if ($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR !$bitbayar->active)
	Tools::redirect('index.php?controller=order&step=1');

$authorized = false;

foreach (Module::getPaymentModules() as $module)
	if ($module['name'] == 'bitbayar') {
			$authorized = true;
			break;
	}

if (!$authorized)
	die($bitbayar->l('This payment method is not available.', 'validation'));
echo $bitbayar->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');