<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/paylinepayment.php');

global $cart;
if (!$cart->id_customer)
    Tools::redirect('authentication.php?back=order.php');
	
$paylinepayment= new paylinepayment();
$message = $paylinepayment->l('لطفاً صبر کنید...').'<br />';
$message .= $paylinepayment->execPayment($cart);
echo $message;

include_once(dirname(__FILE__).'/../../footer.php');

?>
