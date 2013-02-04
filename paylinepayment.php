<?php

if (!defined('_PS_VERSION_'))
	exit;

class paylinepayment extends PaymentModule{

	private $_html = '';
	private $_postErrors = array();
	const _PAYLINE_TEST_API_ = 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567';
	const _PAYLINE_TEST_URL_ = 'http://payline.ir/payment-test/';
	const _PAYLINE_ACTION_URL_ = 'http://payline.ir/payment/';


	public function __construct(){

		$this->name = 'paylinepayment';
		$this->tab = 'payments_gateways';
		$this->version = '1.0';
		$this->author = 'Presta-Shop.ir';

		$this->currencies = true;
  		$this->currencies_mode = 'radio';

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Payline Payment');
		$this->description = $this->l('A free module to pay online for Payline.');
		$this->confirmUninstall = $this->l('Are you sure, you want to delete your details?');

		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module');

		$config = Configuration::getMultiple(array('PAYLINE_PIN', ''));
		if (!isset($config['PAYLINE_PIN']))
			$this->warning = $this->l('Your Payline Pin Code must be configured in order to use this module');


		if ($_SERVER['SERVER_NAME'] == 'localhost')
			$this->warning = $this->l('Your are in localhost, Payline Payment can\'t validate order');


	}
	public function install(){
		if (!parent::install()
	    	OR !Configuration::updateValue('PAYLINE_PIN', '')
			OR !Configuration::updateValue('PAYLINE_TEST_MODE', FALSE)
	      	OR !$this->registerHook('payment')
	      	OR !$this->registerHook('paymentReturn')){
			    return false;
		}
		return true;
	}
	public function uninstall(){
		if (!Configuration::deleteByName('PAYLINE_PIN')
			OR !Configuration::deleteByName('PAYLINE_TEST_MODE')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function displayFormSettings()
	{
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />'.$this->l('Settings').'</legend>
				<label>'.$this->l('Payline API').'</label>
				<div class="margin-form"><input type="text" size="30" name="PaylinePin" id="paylinepin" value="'.Configuration::get('PAYLINE_PIN').'" '.(Configuration::get('PAYLINE_TEST_MODE') ? 'disabled' : '').'/>
				<p class="hint clear" style="display: hidden; width: 501px;">'.$this->l('You must be a member of payline.ir and get API code.').'</p></div>
				<div class="clear">
				<label>'.$this->l('Active test mode?').'</label>
				<div class="margin-form">
				<input type="radio" name="activeTest" value="1" '.(Configuration::get('PAYLINE_TEST_MODE') ? 'checked' : '').'/><span> '.$this->l('بله').'</span> 
				<input type="radio" name="activeTest" value="0" '.(Configuration::get('PAYLINE_TEST_MODE') ? '' : 'checked').' /><span> '.$this->l('خیر').'</span>
				<p class="hint clear" style="display: hidden; width: 501px;">'.$this->l('Test Payline paymentt method without API key.').'</p></div></div>
				<center><input type="submit" name="submitPayline" value="'.$this->l('Update Settings').'" class="button" /></center>
			</fieldset>
		</form>';
	}

	public function displayConf()
	{
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Settings updated').'
		</div>';
	}

	public function displayErrors()
	{
		foreach ($this->_postErrors AS $err)
		$this->_html .= '<div class="alert error">'. $err .'</div>';
	}

    public function getContent()
	{
		$this->displayPaylinePayment;
		$this->postprocess ();
		$this->displayFormSettings();
		return $this->_html;
	}
	
	private function postprocess ()
	{
		if (isset($_POST['submitPayline']))
		{
			Configuration::updateValue('PAYLINE_TEST_MODE', $_POST['activeTest']);
			if ($_POST['activeTest'] != '1' AND empty($_POST['PaylinePin']))
				$this->_postErrors[] = $this->l('Payline API is required.');
			if (!sizeof($this->_postErrors))
			{
				if (!Configuration::get('PAYLINE_TEST_MODE'))
					Configuration::updateValue('PAYLINE_PIN', $_POST['PaylinePin']);
				return $this->displayConf();
			}
			return $this->displayErrors();
		}
	}

	private function displayPaylinePayment()
	{
		$this->_html .= '<h2>'.$this->l('Payline Payment').'</h2>';
		$this->_html .= '<img src="../modules/paylinepayment/payline.gif" style="float:left; margin-right:15px;"><b>'.$this->l('This module allows you to accept payments by Payline.').'</b><br /><br />
		'.$this->l('Any cart from Shetab Banks are accepted.').'<br /><br /><br />';

	}

	public function execPayment($cart)
	{
		global $cookie, $smarty;

        include_once("sender.php");
		$activeTestMode = Configuration::get('PAYLINE_TEST_MODE');
        $url = ($activeTestMode ? self::_PAYLINE_TEST_URL_ : self::_PAYLINE_ACTION_URL_).'gateway-send';
        $api = ($activeTestMode ? self::_PAYLINE_TEST_API_ : Configuration::get('PAYLINE_PIN'));
        $purchase_currency = $this->GetCurrency();
		$current_currency = new Currency($cookie->id_currency);

		if($cookie->id_currency == $purchase_currency->id)
			$amount = number_format($cart->getOrderTotal(true, 3), 0, '', '');
		else
			$amount= number_format($this->convertPriceFull($cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');
        
		$OrderId = $cart->id;
        $redirect = (Configuration::get('PS_SSL_ENABLED') ?'https://' :'http://').$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/paylinepayment/validation.php';

        $result = send($url,$api,$amount,$redirect);
        if($result > 0 && is_numeric($result)){
            setcookie("OrderId", $cart->id, time()+1800);
            setcookie("PurchaseAmount", $amount, time()+1800);
            $go = ($activeTestMode ? self::_PAYLINE_TEST_URL_ : self::_PAYLINE_ACTION_URL_).'gateway-'.$result;
			$message = $this->l('در حال اتصال به بانک');
			Tools::redirectLink($go);
            return $message;
        }
		switch($result)
		{
		   case -1:  $this->_postErrors[] = $this->l('API ارسالی نامعتبر است'); break;
		   case -2:  $this->_postErrors[] = $this->l('مبلغ تراکنش کمتر از 1000 ریال است'); break;
		   case -3:  $this->_postErrors[] = $this->l('آدرس بازگشت نامعتبر است'); break;
		   case -4:  $this->_postErrors[] = $this->l('درگاه وجود ندارد یا نامعتبر است'); break;
		}
		$this->displayErrors();
		return $this->_html;
	}
	public function confirmPayment(){
        
		include_once('sender.php');

        $activeTestMode = Configuration::get('PAYLINE_TEST_MODE');
		$url = ($activeTestMode ? self::_PAYLINE_TEST_URL_ : self::_PAYLINE_ACTION_URL_).'gateway-result-second';
        $api = ($activeTestMode ? self::_PAYLINE_TEST_API_ : Configuration::get('PAYLINE_PIN'));
        $trans_id = $_POST['trans_id'];
        $id_get = $_POST['id_get'];
        $status = get($url,$api,$trans_id,$id_get);
		return $status;
	}

	public function showMessages($result)
	{
		switch($result)
		{
		   case -4: $this->_postErrors[]=$this->l('تراکنش موفقيت آميز نبود<br />'.'شماره تراکنش Payline:'.$_POST['trans_id']); break;
		}
		$this->displayErrors();
		echo $this->_html;
		return $result;
	}

	public function hookPayment($params){
		if (!$this->active)
			return ;
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'confirmation.tpl');
	}
	
	/**
	 *
	 * Convert amount from a currency to an other currency automatically
	 * @param float $amount
	 * @param Currency $currency_from if null we used the default currency
	 * @param Currency $currency_to if null we used the default currency
	 */
	public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null)
	{
		if ($currency_from === $currency_to)
			return $amount;

		if ($currency_from === null)
			$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

		if ($currency_to === null)
			$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

		if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
			$amount *= $currency_to->conversion_rate;
		else
		{
            $conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
			// Convert amount to default currency (using the old currency rate)
			$amount = Tools::ps_round($amount / $conversion_rate, 2);
			// Convert to new currency
			$amount *= $currency_to->conversion_rate;
		}
		return Tools::ps_round($amount, 2);
	}

}
