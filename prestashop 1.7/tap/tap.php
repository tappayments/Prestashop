<?php
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_CAN_LOAD_FILES_'))
	exit;
	
class Tap extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();
	private $_responseReasonText = null;

	public function __construct(){
		$this->name = 'tap';
		$this->tab = 'payments_gateways';
		$this->version = '1.1';
		$this->author = 'Tap Development Team';
		$this->controllers = array('payment', 'response');
        parent::__construct();
		$this->page = basename(__FILE__, '.php');
		
        $this->displayName = $this->l('Tap');
        $this->description = $this->l('Module for accepting payments by Tap');
	}
	
	public function install(){
		if(parent::install()){
			Configuration::updateValue('Tap_MERCHANT_ID', '');
			Configuration::updateValue('Tap_USERNAME', '');
            Configuration::updateValue('Tap_SECRET_KEY', '');
            Configuration::updateValue('Tap_MODE', '');            
           			
			
			//$this->registerHook('payment');
			$this->registerHook('PaymentReturn');
			$this->registerHook('ShoppingCartExtra');
			$this->registerHook('paymentOptions');
			if(!Configuration::get('Tap_ORDER_STATE')){
				$this->setTapOrderState('Tap_ID_ORDER_SUCCESS','Payment Received','#b5eaaa');
				$this->setTapOrderState('Tap_ID_ORDER_FAILED','Payment Failed','#E77471');
				$this->setTapOrderState('Tap_ID_ORDER_PENDING','Payment Pending','#F4E6C9');
				Configuration::updateValue('Tap_ORDER_STATE', '1');
			}		
			return true;
		}
		else {
			return false;
		}
	}
	
	public function uninstall(){
		if (!Configuration::deleteByName('Tap_MERCHANT_ID') OR
			!Configuration::deleteByName('Tap_USERNAME') OR
			!Configuration::deleteByName('Tap_SECRET_KEY') OR
			!Configuration::deleteByName('Tap_MODE') 	OR	
			!parent::uninstall()){
				return false;
		}	
		return true;
	}
	
	public function setTapOrderState($var_name,$status,$color){
		$orderState = new OrderState();
		$orderState->name = array();
		foreach(Language::getLanguages() AS $language){
			$orderState->name[$language['id_lang']] = $status;
		}
		$orderState->send_email = false;
		$orderState->color = $color;
		$orderState->hidden = false;
		$orderState->delivery = false;
		$orderState->logable = true;
		$orderState->invoice = true;
		if ($orderState->add())
			Configuration::updateValue($var_name, (int)$orderState->id);
		return true;
	}
	
	public function getContent() {
        $this->_html = '<h2>' . $this->displayName . '</h2>';
        if (isset($_POST['submitTap'])) {
            if (empty($_POST['merchant_id']))
                $this->_postErrors[] = $this->l('Please Enter your Merchant ID.');
			if (empty($_POST['username']))
				$this->_postErrors[] = $this->l('Please Enter your Username.');
            if (empty($_POST['secret_key']))
                $this->_postErrors[] = $this->l('Please Enter your API Key.');
            
            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('Tap_MERCHANT_ID', $_POST['merchant_id']);
				Configuration::updateValue('Tap_USERNAME', $_POST['username']);
                Configuration::updateValue('Tap_SECRET_KEY', $_POST['secret_key']);
                Configuration::updateValue('Tap_MODE', $_POST['mode']);
                $this->displayConf();
            } else {
                $this->displayErrors();
            }
        }
        $this->_displayTap();
        $this->_displayFormSettings();
        return $this->_html;
    }
	
	public function displayConf(){
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Settings updated').'
		</div>';
	}
	
	public function displayErrors(){
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}
	
	public function _displayTap(){
		$this->_html .= '
		<img src="../modules/tap/logo.png" style="float:left; padding: 0px; margin-right:15px;height:40px;width:255px" />
		<b>'.$this->l('This module allows you to accept payments by Tap.').'</b><br /><br />
		'.$this->l('You need to configure your Tap account first before using this module.').'
		<br /><br /><br />';
	}
	
	 public function _displayFormSettings() {

        $test = '';
        $live = '';
        $on = '';
        $off = '';
        $mode = Configuration::get('Tap_MODE');
        $id = Configuration::get('Tap_MERCHANT_ID');
        $key = Configuration::get('Tap_SECRET_KEY');
        $apiusername = Configuration::get('Tap_USERNAME');

        if (!empty($id)) {
            $merchant_id = $id;
        } else {
            $merchant_id = '';
        }

        if (!empty($key)) {
            $secret_key = $key;
        } else {
            $secret_key = '';
        }
        
        
        if (!empty($apiusername)) {
            $username = $apiusername;
        } else {
            $username = '';
        }

        if (!empty($mode)) {
            if ($mode == 'TEST') {
                $test = "selected='selected'";
                $live = '';
            }
            if ($mode == 'LIVE') {
                $live = "selected='selected'";
                $test = '';
            }
        } else {
            $live = '';
            $test = "selected='selected'";
        }

        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Configuration Settings') . '</legend>
				<table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">' . $this->l('Please specify the Merchant ID, Username and API Key provided by Tap.') . '<br /><br /></td></tr>
					<tr>
                                            <td width="130" style="height: 25px;">' . $this->l('Tap Merchant ID') . '</td>
                                            <td><input type="text" name="merchant_id" value="' . $merchant_id . '" style="width: 170px;" /></td>
                                        </tr>
										<tr>
											<td width="130" style="height: 25px;">' . $this->l('Tap Username') . '</td>
											<td><input type="text" name="username" value="' . $username . '" style="width: 170px;" /></td>
										</tr>
										<tr>
											<td width="130" style="height: 25px;">' . $this->l('Tap Secret Key') . '</td>
											<td><input type="text" name="secret_key" value="' . $secret_key . '" style="width: 170px;" /></td>
										</tr>
										<tr>
											<td width="130" style="height: 25px;">' . $this->l('Tap Mode') . '</td>
											<td>
												<select name="mode" style="width: 110px;">
													<option value="TEST" ' . $test . '>Sandbox(Test)</option>
													<option value="LIVE" ' . $live . '>Live</option>
												</select>
											</td>
										</tr>
										<tr><td colspan="2"><p class="hint clear" style="display: block; width: 350px;">' . $this->l('Select the Mode you want to work on.') . '</p></td></tr>
										<tr> </tr><br /><br />
					<tr><td colspan="2" align="center"><br /><input class="button" name="submitTap" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>
		';
    }

		public function hookPaymentOptions($params)
		{

			$newOption = new PaymentOption();
			$this->context->smarty->assign(array(
                'path' => $this->_path,
            ));
			
			$newOption->setCallToActionText($this->trans('Pay by Tap', array(), 'Modules.Tap.Shop'))
				//->setLogo(_MODULE_DIR_.'tap/views/img/logo-mymodule.png')		
				->setAdditionalInformation('You will be redirected to the Tap payment page.')				
				->setAction($this->context->link->getModuleLink($this->name, 'payment'));
			return [$newOption];
		}
	

	public function execPayment($cart){
		global $smarty;  


		if(Configuration::get('Tap_MODE')){ 
			$send_url='http://live.gotapnow.com/webpay.aspx';//Development
			$collaborator='Tap';
		}else{ 
			$send_url='https://www.gotapnow.com/webpay.aspx';//Production
			$collaborator='Tap';
		}	

		$MERCHANTID=Configuration::get('Tap_MERCHANT_ID');
		$USERNAME=Configuration::get('Tap_USERNAME');
		$APIKEY=Configuration::get('Tap_SECRET_KEY');
        
		$bill_address = new Address(intval($cart->id_address_invoice));
		$ship_address = new Address(intval($cart->id_address_delivery));
		$bc = new Country($bill_address->id_country);
		$sc = new Country($ship_address->id_country);				
		$customer = new Customer(intval($cart->id_customer));
		
		$id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));		
		$currency = new Currency(intval($id_currency));		
		$ref = 'Order ID : '.intval($cart->id);
		
		$first_name = $bill_address->firstname;
		$last_name = $bill_address->lastname;
		$name = $first_name." ".$last_name;
		$phone = $bill_address->phone;
		$phone_mobile = $bill_address->phone_mobile;
		$email = $customer->email;			
		
		if (!empty($phone))
			$CstMobile = $phone;
			
		if (!empty($phone_mobile))
			$CstMobile = $phone_mobile;
		
		$amount = $cart->getOrderTotal(true,Cart::BOTH);
		
		$CurrencyCode = $currency->iso_code;
		
		$protocol='http://';
		$host='';
		if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
			$protocol='https://';
		}
		if (isset($_SERVER["HTTP_HOST"]) && ! empty($_SERVER["HTTP_HOST"])) {
			$host=$_SERVER["HTTP_HOST"];
		}
		
		
		$order_id = intval($cart->id);
        $cust_id = intval($cart->id_customer);
		
		$protocol='http://';
		if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
			$protocol='https://';
		}
		if (isset($_SERVER["HTTP_HOST"]) && ! empty($_SERVER["HTTP_HOST"])) {
			$host=$_SERVER["HTTP_HOST"];
		}
		
		$smarty->assign(array(
            'send_url' => $send_url,
			'MEID'=> $MERCHANTID,
			'UName'=> $USERNAME,			
			'Amount'=> $amount,
			'CurrencyCode'=> $CurrencyCode,
			'OrdID'=> $order_id,
			'CstEmail'=> $email,
			'CstFName'=> $first_name,
			'CstLName'=> $last_name,
			'CstMobile'=> $CstMobile,		
			'ReturnURL'=> $protocol.$_SERVER['HTTP_HOST'].$this->_path.'validation.php',	
			'PrdName'=>$ref,
        ));
		//return $this->display(__FILE__, 'payment_execution.tpl');
    }
	
	public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }		
		
        $state = $params['order']->getCurrentState();
		
		//if($state == '15' || $state == '2'){
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice(
					$params['order']->getOrdersTotalPaid(),
					new Currency($params['order']->id_currency),
					false
				),
				'shop_name' => $this->context->shop->name,
				//'checkName' => $this->context->checkName,
				//'checkAddress' => Tools::nl2br($this->context->address),
				'status' => 'Ok',
				'responseMsg' => $_GET['responseMsg'],
				'id_order' => $params['order']->id
			));
		//}
		/* else{
			$this->smarty->assign(array(
				'status' => 'failed',
				'responseMsg' => $_GET['responseMsg'],
				));
		} */
        //return $this->fetch('module:paytm/views/templates/hook/payment_response.tpl');
		return $this->display(__FILE__, 'views/templates/hook/payment_response.tpl');
    }
	
}
?>
