<?php

class BitbayarCallbackModuleFrontController extends ModuleFrontController
{

	public function display()
	{
		$response = new stdClass();
		$response->success = true;
		$response->errors = array();
		$bitbayar_url_check = 'https://bitbayar.com/api/check_invoice';
		$db = Db::getInstance();

		// Get callback data
		$id_cart = (int)$_POST['invoice_id'];
		$id_bitbayar = $_POST['id'];
		$total_paid = $_POST['rp'];

		$cart = new Cart($id_cart);
		$customer = new Customer($cart->id_customer);
		$cart_amount = (float)$cart->getOrderTotal(true, Cart::BOTH);

		//~ Get Status 
		$data_check = array(
			'token' => Configuration::get('BITBAYAR_APITOKEN'),
			'id' => $id_bitbayar
		);
		$check_status = $this->module->curlPost($bitbayar_url_check, $data_check);
		$result = json_decode($check_status);

		$status = $result->status;

		$pay_bitbayar = array(
			'id_order' => (int)$_POST['invoice_id'],
			'cart_id' => 0,
			'bitbayar_id' => $_POST['id'],
			'txid' => $_POST['txid'],
			'status' => $status
		);
		$db->insert('order_bitbayar', $pay_bitbayar);


		if ($status == 'paid')
		{
			
			$pay_bitbayar = array(
				'id_order' => $id_cart,
				'cart_id' => 0,
				'bitbayar_id' => $id_bitbayar,
				'txid' => $_POST['txid'],
				'status' => $status
			);
			$db->insert('order_bitbayar', $pay_bitbayar);
			
			// Check if there is an order for this cart.
			$id_order = (int)Order::getOrderByCartId($id_cart);
			$order = new Order($id_order);
			if ($order->id)
			{
				// Update order state
				$order_state = $order->getCurrentOrderState();
				if ($order_state->id == Configuration::get('PS_OS_BITBYAR'))
					$order->setCurrentState(_PS_OS_PAYMENT_);
				else
				{
					$response->success = false;
					$response->errors[] = 'Order is not waiting for validation.';
				}
			}
			else
			{
				//~ Validate order
				//~ validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), $currency_special = NULL);
				$this->module->validateOrder($id_cart, _PS_OS_PAYMENT_, $total_paid, $this->module->displayName, null, array(), (int)$cart->id_currency, false, $customer->secure_key);
			}
		}
		else
		{
			$response->success = false;
			$response->errors[] = 'Invalid status.';
		}

	echo Tools::jsonEncode($response);
	}
}