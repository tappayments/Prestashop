<script language="javascript" type="text/javascript">
	function encodeTxnRequest()
	{
		document.tap.submit();
	}
</script>

<p style='text-align:center'  class="payment_module">
	<a style='text-decoration: none' href="javascript:void(0);" onclick="encodeTxnRequest()">
	<img src="https://www.gotapnow.com/web/tap.png" alt="{l s='Pay with Tap '}" /><br /><br />
	<span>Click here if you are not processed within 30 seconds</span>
	</a>
	<form  onload="encodeTxnRequest()" action="{$send_url}" name="tap" method="post" id="tap">
		<input type="hidden" name="MEID" id="MEID" value="{$MEID}" />
		<input type="hidden" name="UName" value="{$UName}" /> 
		<input type="hidden" name="PWD" value="{$PWD}" />
		<input type="hidden" name="ItemName1" value="{$PrdName}" />
		<input type="hidden" name="ItemQty1" value="1" />
		<input type="hidden" name="ItemPrice1" value="{$Amount}" />
		<input type="hidden" name="CurrencyCode" value="{$CurrencyCode}" />
		<input type="hidden" name="OrdID" value="{$OrdID}" />
		<input type="hidden" name="CstEmail" value="{$CstEmail}" />
		<input type="hidden" name="CstFName" value="{$CstFName}" />
		<input type="hidden" name="CstLName" value="{$CstLName}" />
		<input type="hidden" name="CstMobile" value="{$CstMobile}" />
		<input type="hidden" name="ReturnURL" value="{$ReturnURL}" />
	</form>
</p>

<script language="javascript" type="text/javascript">
	document.tap.submit();
</script>


