<?php

class TapResponseModuleFrontController {

	public function postProcess() {
		$order_id      = $_REQUEST['trackid'];
		$res_desc      = $_REQUEST['result'];
		$checksum_recv = $_REQUEST['hash'];
		$secret_key	   = Configuration::get('Tap_SECRET_KEY');
		$merchant_id   = Configuration::get('Tap_MERCHANT_ID');
		$order_amount  = $_REQUEST['amt'];
				
		$reference_id = $_REQUEST['ref'];
		$payment_id = $_REQUEST['payid'];
		$cart = $this->context->cart;
		$amount = $cart->getOrderTotal(true,Cart::BOTH);
		
		$str = 'x_account_id'.$merchant_id.'x_ref'.$reference_id.'x_resultSUCCESSx_referenceid'.(int)$cart->id.'';
		$checksum = hash_hmac('sha256', $str, $secret_key);
		
		$history_message='Payment ';
		if($res_desc == 'SUCCESS' and $order_id == (int)$cart->id and $checksum == $checksum_recv)
		{	
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
		
		$history_message = $responseMsg.'. Tap Reference ID: '.$reference_id;		
		$customer = new Customer((int)$this->context->cart->id_customer);
		$secure_key = Context::getContext()->customer->secure_key;
		$obj = new Tap();
		
		$obj->validateOrder(intval($cart->id), $PAYMENT_status, $amount, $obj->displayName, $history_message, array(), '', false, $cart->secure_key);					
		
		$this->context->smarty->assign(array(
			'status' => $PAYMENT_status,
			'responseMsg' => $res_desc,
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));
		$cart->delete();
		if ($order_id && ($secure_key == $customer->secure_key) && $res_desc=='SUCCESS') {
            $module_id = $this->module->id;
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$amount.'&key='.$customer->secure_key.'&responseMsg='.$res_desc);
        } else {
            $this->setTemplate('module:tap/views/templates/hook/payment_response.tpl');
        }
}
}
