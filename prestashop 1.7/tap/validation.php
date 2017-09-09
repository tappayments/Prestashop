<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/tap.php');

	$context = Context::getContext();
	$cart = $context->cart;

	$order_id      = $_REQUEST['trackid'];
	$res_desc      = $_REQUEST['result'];
	$checksum_recv = $_REQUEST['hash'];
	$secret_key	   = Configuration::get('Tap_SECRET_KEY');
	$merchant_id   = Configuration::get('Tap_MERCHANT_ID');
	$order_amount  = $_REQUEST['amt'];
	$reference_id  = $_REQUEST['ref'];
	$payment_id	   = $_REQUEST['payid'];
	
	$amount = $cart->getOrderTotal(true,Cart::BOTH);
	
	$customer = new Customer($cart->id_customer);
	
	if (!Validate::isLoadedObject($customer))
		Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
	
	if ($amount == 0)
	{
		Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key);
	}
		
	$str = 'x_account_id'.$merchant_id.'x_ref'.$reference_id.'x_resultSUCCESSx_referenceid'.(int)$cart->id.'';
	$checksum = hash_hmac('sha256', $str, $secret_key);
	
	$history_message='Payment ';
	
	$extras = array();
	$extras['transaction_id'] = $payment_id;
	$extras['reference_id'] = $reference_id;
	$extras['payment_type'] = $_REQUEST['crdtype'];
	$status='';
		
	if($res_desc == 'SUCCESS' and $order_id == (int)$cart->id and $checksum == $checksum_recv)
	{	
		$status='Ok';
		$history_message='Payment Success - Payment ID '.$payment_id;
		$PAYMENT_status= Configuration::get('Tap_ID_ORDER_SUCCESS');
	}
	else if($res_desc=='CANCELLED')
	{
		$history_message='Payment Cancelled';	
		$PAYMENT_status= Configuration::get('Tap_ID_ORDER_FAILED');
	}
	else
	{
		$history_message='Security Error !!';
		$PAYMENT_status= Configuration::get('Tap_ID_ORDER_FAILED');
	}
	
	$history_message = $history_message.'. Tap Reference ID: '.$reference_id;		

	$tap = new Tap();
	
	$tap->validateOrder(intval($cart->id), $PAYMENT_status, $amount, $tap->displayName, $history_message, $extras, '', false, $customer->secure_key);					
	
	if($status=='Ok')
	{
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$tap->id.'&id_order='.(int)$tap->currentOrder.'&key='.$customer->secure_key);
	}
	else 
	{
		Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
	}
?>