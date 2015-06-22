<?php
if (!defined('_PS_VERSION_'))
exit;

class bitbayar extends PaymentModule
{
	private $html = '';
	private $post_errors = array();

	public function __construct()
	{
		$this->name = 'bitbayar';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'Teddy Fresnel';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_); 
		$this->bootstrap = true;
		$this->bitbayar_url = 'https://bitbayar.com/api/create_invoice';
		$this->bitbayar_currency = 'IDR';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		$this->displayName = $this->l('BitBayar Payment');
		$this->description = $this->l('Accept bitcoin payment with BitBayar.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('BITBAYAR_MERCHANT'))
			$this->warning = $this->l('Please setup merchant e-mail. ');

		if (!Configuration::get('BITBAYAR_APITOKEN'))
			$this->warning .= $this->l('Please setup your Api Token. ');
	}

	public function install()
	{
		if (!parent::install()
			|| !Configuration::updateValue('BITBAYAR_MERCHANT', '')
			|| !Configuration::updateValue('BITBAYAR_APITOKEN', '')
			|| !$this->createOrderState()
			|| !$this->registerHook('payment')
			|| !$this->registerHook('paymentReturn'))
			return false;
		
		$db = Db::getInstance();
		$query = "CREATE TABLE `"._DB_PREFIX_."order_bitbayar` (
				`id_payment` int(11) NOT NULL AUTO_INCREMENT,
				`id_order` int(11) NOT NULL,
				`cart_id` int(11) NOT NULL,
				`bitbayar_id` varchar(50) NOT NULL,
				`txid` varchar(255) NOT NULL,
				`status` varchar(255) NOT NULL,
				PRIMARY KEY (`id_payment`),
				UNIQUE KEY `bitbayar_id` (`bitbayar_id`)
				) ENGINE="._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

		$db->Execute($query);
		Configuration::updateValue('BITBAYAR_MERCHANT', '');
		Configuration::updateValue('BITBAYAR_APITOKEN', '');
		Configuration::updateValue('BITBAYAR_BUTTON', 'medium');
		return true;
	}

	public function createOrderState()
	{
		if (!Configuration::get('PS_OS_BITBAYAR'))
		{
			$db = Db::getInstance();

			//~ Create order state
			$dataOs = array(
				'invoice' => 0,
				'send_email' => 0,
				'module_name' => 'bitbayar',
				'color' => '#4169E1',
				'unremovable' => 1,
				'hidden' => 0,
				'logable' => 0,
				'delivery' => 0,
				'shipped' => 0,
				'paid' => 0,
				'deleted' => 0
			);
			$db->insert('order_state', $dataOs);

			//~ Create order state lang
			$id_order_state = $db->Insert_ID();
			$dataOsl = array(
				'id_order_state' => $id_order_state,
				'id_lang' => 1,
				'name' => 'Awaiting BitBayar payment',
				'template' => 'bitbayar',
			);
			$db->insert('order_state_lang', $dataOsl);

			 //~ Set configuration.
			Configuration::updateValue('PS_OS_BITBAYAR', $id_order_state);
		}
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall()
			|| !Configuration::deleteByName('BITBAYAR_MERCHANT')
			|| !Configuration::deleteByName('BITBAYAR_APITOKEN')
			|| !Configuration::deleteByName('BITBAYAR_BUTTON'))
			return false;
		return true;
	}

	public function getContent()
	{
		$this->_html .= '<h2>'.$this->l('BitBayar Payment').'</h2>';

		$this->_submitConfiguration();
		$this->_setBitBayarHeading();
		$this->_setConfigurationForm();

		return $this->_html;
	}

	private function _setBitBayarHeading()
	{
		$this->_html .= '
		<div style="float: right; width: 440px; height: 150px; border: dashed 1px #666; padding: 8px; margin-left: 12px;">
			<h2>'.$this->l('Opening your BitBayar account').'</h2>
			<div style="clear: both;"></div>
			<p>'.$this->l('When opening your BitBayar account by clicking on the following image, you are helping us significantly to improve the BitBayar solution:').'</p>
			<p style="text-align: center;"><a href="https://bitbayar.com/" target="blank"><img src="'.$this->_path.'views/img/prestashop_bitbayar.png" alt="PrestaShop & BitBayar" style="margin-top: 12px;" /></a></p>
			<div style="clear: right;"></div>
		</div>
		
		<img src="'.$this->_path.'views/img/logo-bitbayar.png" style="float:left; margin-right:15px;" />
		<b>'.$this->l('This module allows you to accept payments by BitBayar.').'</b><br /><br />
		'.$this->l('If the client chooses this payment mode, your BitBayar account will be automatically credited.').'
		'.$this->l('You need to configure your BitBayar account before using this module.').'
		<div style="clear:both;">&nbsp;</div>';
	}

	private function _setConfigurationForm()
	{
		$this->_html .=	'<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
						<script type="text/javascript">
							var pos_select = '.(($tab = (int)Tools::getValue('tabs')) ? $tab : '0').';
						</script>';

		if (_PS_VERSION_ <= '1.5')
		{
			$this->_html .=
			'<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'tabpane.js"></script>
			<link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_CSS_DIR_.'tabpane.css" />';
		}
		else 
		{
			$this->_html .= 
			'<script type="text/javascript" src="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.js"></script>
			<link type="text/css" rel="stylesheet" href="'._PS_BASE_URL_._PS_JS_DIR_.'jquery/plugins/tabpane/jquery.tabpane.css" />';
		}

		$this->_html .=
			'<input type="hidden" name="tabs" id="tabs" value="0" />
			<div class="tab-pane" id="tab-pane-1" style="width:100%;">
				<div class="tab-page" id="step1">
					<h4 class="tab">'.$this->l('Settings').'</h2>
					'.$this->_getSettingsTabHtml().'
				</div>
			</div>
			<div class="clear"></div>
			<script type="text/javascript">
				function loadTab(id){}
				setupAllTabs();
			</script>
		</form>';
	}

	private function _getSettingsTabHtml()
	{
		global $cookie;
		$buttonType = (string)(Tools::getValue('bitbayar_button', Configuration::get('BITBAYAR_BUTTON')));

		$html = '
		<fieldset>
			<legend>'.$this->l('Settings').'</legend>
			<form>
				<div class="form-group">
					<label for="bitbayar_merchant">'.$this->l('BitBayar account e-mail').'</label>
					<input type="email" class="form-control" id="bitbayar_merchant" placeholder="Email Merchant" name="bitbayar_merchant" value="'.htmlentities(Tools::getValue('newmerchant_bitbayar', Configuration::get('BITBAYAR_MERCHANT')), ENT_COMPAT, 'UTF-8').'">
				</div>
				
				<div class="form-group">
					<label for="bitbayar_apitoken">'.$this->l('Token').'</label>
					<input type="text" class="form-control" id="bitbayar_apitoken" placeholder="API Token" name="bitbayar_apitoken" value="'.htmlentities(Tools::getValue('token_bitbayar', Configuration::get('BITBAYAR_APITOKEN')), ENT_COMPAT, 'UTF-8').'">
					<p class="help-block">API Token from your merchant account, under <i>Setting & API</i> menu</p>
				</div>
				
				<div class="form-group">
					<label for="bitbayar_button">'.$this->l('Payment Button').'</label>
					<div class="radio" style="height: 40px;">
						<label>
						<input type="radio" name="bitbayar_button" value="large" '.($buttonType == 'large' ? 'checked="checked" ' : '').'/>
						<img src="'.$this->_path.'views/img/bitbayar-pay-large.png" style="padding-right: 10px;margin-bottom: -10px;">
						</label>
					</div>
				
					<div class="radio" style="height: 35px;">
						<label>
						<input type="radio" name="bitbayar_button" value="medium" '.($buttonType == 'medium' ? 'checked="checked" ' : '').'/>
						<img src="'.$this->_path.'views/img/bitbayar-pay-medium.png" style="padding-right: 10px;margin-bottom: -10px;">
						</label>
					</div>
					
					<div class="radio" style="height: 35px;">
						<label>
						<input type="radio" name="bitbayar_button" value="small" '.($buttonType == 'small' ? 'checked="checked" ' : '').'/>
						<img src="'.$this->_path.'views/img/bitbayar-pay-small.png" style="padding-right: 10px;margin-bottom: -10px;">
						</label>
					</div>
					
					<div class="radio">
						<label>
						<input type="radio" name="bitbayar_button" value="text" '.($buttonType == 'text' ? 'checked="checked" ' : '').'/><strong>'.$this->l(' Text Only').'</strong>
						</label>
					</div>
				</div>

				<button type="submit" name="submitbitbayar" class="btn btn-default">'.$this->l('Save settings').'</button>

			</form>
		</fieldset>';

		return $html;
	}


	private function _submitConfiguration()
	{
		global $currentIndex, $cookie;

		if (Tools::isSubmit('submitbitbayar'))
		{
			$template_available = array('A', 'B', 'C');
			$apiToken = Tools::getValue('bitbayar_apitoken');

			$this->_errors = array();

			if (Tools::getValue('bitbayar_merchant') == NULL)
			{
				$this->_errors[] = $this->l('Missing BitBayar account e-mail');
			}

			if ($apiToken == NULL)
			{
				$this->_errors[] = $this->l('Missing BitBayar Token');
			}

			if (strlen($apiToken) != 33 || $apiToken[0] != 'S')
			{
				$this->_errors[] = $this->l('API Token is not valid!');
			}

			if (Tools::getValue('bitbayar_button') == NULL)
			{
				$this->_errors[] = $this->l('Please select BitBayar button');
			}

			if (count($this->_errors) > 0)
			{
				$error_msg = '';
				foreach ($this->_errors AS $error)
					$error_msg .= $error.'<br />';
				$this->_html = $this->displayError($error_msg);
			}
			else
			{
				Configuration::updateValue('BITBAYAR_MERCHANT', trim(Tools::getValue('bitbayar_merchant')));
				Configuration::updateValue('BITBAYAR_APITOKEN', trim(Tools::getValue('bitbayar_apitoken')));
				Configuration::updateValue('BITBAYAR_BUTTON', trim(Tools::getValue('bitbayar_button')));

				$this->_html = $this->displayConfirmation($this->l('Settings updated'));
			}
		}
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_name' => $this->name,
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}


	public function execPayment($cart)
	{
		global $cookie, $smarty, $currency;

		if (!$this->active)
			return ;

		$customer_currency = $currency->iso_code;
		$default_currency = Currency::getDefaultCurrency()->iso_code;
		$currency_array = Currency::getCurrencies($object = false, $active = 1);
		$amount = $cart->getOrderTotal(true);

		$idr_rate = $this->checkIdrCurrency($cart);

		if($customer_currency == $this->bitbayar_currency)
			$idr_rate=1;
		if($customer_currency != $this->bitbayar_currency && $default_currency == $this->bitbayar_currency){
			exit("BitBayar Error, This ".$customer_currency. " currency not support!");}
		if($idr_rate) {
			$dataPost = array(
				'token'=>Configuration::get('BITBAYAR_APITOKEN'),
				'invoice_id'=>$cart->id,
				'rupiah'=>round($amount*$idr_rate),
				'memo'=>'Invoice #'.$cart->id.' '.Configuration::get('PS_SHOP_NAME'),
				'callback_url'=>Context::getContext()->link->getModuleLink($this->name, 'callback'),
				'url_success'=>Context::getContext()->link->getModuleLink($this->name, 'success'),
				'url_failed'=>Context::getContext()->link->getModuleLink($this->name, 'cancel')
			);
			$bb_pay = $this->curlPost($this->bitbayar_url, $dataPost);
			$result = json_decode($bb_pay);
			
			if($result->success){
				header('Location: '.$result->payment_url);
				exit;
			}
			else{
				exit('BitBayar API Error: '.$result->error_message);
			}
		}else {
			exit("BitBayar Error, This ".$customer_currency. " currency not support!");
		}
	}

	public function checkIdrCurrency($cart)
	{
		$currencies = Currency::getCurrencies($object = false, $active = 1);
		
		foreach($currencies as $arr)
			if ((string)$arr['iso_code'] == $this->bitbayar_currency )
				$idr_rate = round($arr['conversion_rate']);
					return $idr_rate;
			return false;
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

	public function curlPost($url, $data) 
	{
		if(empty($url) OR empty($data))
		{
			return 'Error: invalid Url or Data';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST,count($data));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);  # Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"); # Some server may refuse your request if you dont pass user agent

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//execute post
		$result = curl_exec($ch);

		//close connection
		curl_close($ch);
		return $result;
	}
}