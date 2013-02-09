<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/paylinepayment.php');
	global $cart;
	if (!$cart->id_customer)
		Tools::redirect('authentication.php?back=order.php');

        $currency_default = Currency::getCurrency(intval(Configuration::get('PS_CURRENCY_DEFAULT')));
        $paylinepayment= new paylinepayment(); // Create an object for order validation and language translations

		$order_cart = new Cart(intval($_COOKIE["OrderId"]));
		$customer = new Customer($order_cart->id_customer);

		$PurchaseAmount = $_COOKIE["PurchaseAmount"];
		$purchase_currency = $paylinepayment->GetCurrency();
		$current_currency = new Currency($cookie->id_currency);
		if($cookie->id_currency == $purchase_currency->id)
			$OrderAmount = number_format($order_cart->getOrderTotal(true, 3), 0, '', '');
		else
			$OrderAmount= number_format($paylinepayment->convertPriceFull($order_cart->getOrderTotal(true, 3), $current_currency, $purchase_currency), 0, '', '');
       
	   $result = $paylinepayment->confirmPayment();
	// We now think that the response is valid, so we can look at the result
	// if we have a valid completed order, validate it
	if ($result == 1)
	{
		if($PurchaseAmount==$OrderAmount)
			 $paylinepayment->validateOrder(intval($_COOKIE["OrderId"]), _PS_OS_PAYMENT_,$order_cart->getOrderTotal(true, 3), $paylinepayment->displayName, $paylinepayment->l('Payment Accepted. Transaction ID: ').$_POST['trans_id'], array(), $cookie->id_currency,false, $customer->secure_key);
		else
			 $paylinepayment->validateOrder(intval($_COOKIE["OrderId"]), _PS_OS_ERROR_,$PurchaseAmount, $paylinepayment->displayName, $paylinepayment->l('Payment Error. Transaction ID: ').$_POST['trans_id'], array(), $purchase_currency,false, $customer->secure_key);

        setcookie("OrderId", "", -1);
        setcookie("PurchaseAmount","", -1);

		if (!$customer->is_guest)
			Tools::redirect('history.php');
		echo $paylinepayment->l('Your Payment accepted. Order ID:').$paylinepayment->currentOrder;
	} else {
    	$paylinepayment->showMessages($result);
	}

include_once(dirname(__FILE__).'/../../footer.php');

?>
