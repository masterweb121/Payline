<!-- Payline Payment Module -->
<p class="payment_module">
    <a href="javascript:$('#paylinepayment_form').submit();" title="{l s='Pay by Payline' mod='paylinepayment'}">
        <img src="modules/paylinepayment/payline.gif" alt="{l s='Pay by Payline' mod='payment'}" />
		{l s='پرداخت با دروازه پرداخت Payline.' mod='paylinepayment'}
	</a>
</p>

<form action="modules/paylinepayment/payment.php" method="post" id="paylinepayment_form" class="hidden">
    <input type="hidden" name="orderId" value="{$orderId}" />
</form>
<br><br>
<!-- End of Payline Payment Module-->
