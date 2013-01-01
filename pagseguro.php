<?php

##########################################
# Módulo Disponibilizado por Agência Pró #
#     http://www.agenciapro.com.br       #
#      USE MAS DEIXE OS CRÉDITOS!        #
#                                        #
#       MÓDULO CORRIDO PARA VERSÕES      #
#	    1,4 DO PRESTASHOP		 #
##########################################

/** 
 * MODULO CRIADO POR FERNANDO
 * @author Fernando Greiffo
 * @colaborador Dlani Mendes
 * @atualizado por Dlani Mendes
 * @copyright Agência Pró
 * @site http://www.agenciapro.com.br 
 * @version 3.0
 * */
class pagseguro extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();
    public $currencies;
    public $_botoes = array(
        'default',
        'btnComprarBR.jpg',
        'btnPagarBR.jpg',
        'btnPagueComBR.jpg'
    );
    public $_banners = array(
        'btnPreferenciaCartoesBR_375x75.gif',
        'btnPreferenciaCartoesBR_418x74.gif',
        'btnPreferenciaCartoesBR_505x55.gif',
        'btnPreferenciaCartoesBR_575x40.gif',
        'btnPreferenciaCartoesBR_620x40.gif',
        'btnPreferenciaCartoesBR_665x55.gif',
        'btnPreferenciaCartoesBR_735x40.gif',
        'btnPreferenciasBR_160x45.gif',
        'btnPreferenciasBR_230x40.gif',
        'btnPreferenciasBR_238x73.gif',
        'btnPreferenciasBR_260x30.gif',
        'btnPreferenciasBR_275x40.gif',
        'btnPreferenciasBR_295x45.gif',
        'btnPreferenciasBR_370x40.gif',
        'btnPreferenciasBR_415x40.gif'
    );

    public function __construct() {
        $this->name = 'pagseguro';
        $this->tab = 'payments_gateways';
        $this->version = '3.0';

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->page = basename(__file__, '.php');
        $this->displayName = $this->l('PagSeguro');
        $this->description = $this->l('Aceitar pagamentos via pagseguro');
        $this->confirmUninstall = $this->l('Tem certeza de que pretende eliminar os seus dados?');
        $this->textshowemail = $this->l('Você deve seguir coretamente os procedimentos de pagamento do pagSeguro, para que sua compra seja validada.');
    }

    public function install() {
        /* Install and register on hook */
        if (!parent::install()
                OR !$this->registerHook('payment')
                OR !$this->registerHook('paymentReturn')
                OR !$this->registerHook('shoppingCartExtra')
                OR !$this->registerHook('backBeforePayment')
                OR !$this->registerHook('paymentReturn')
                OR !$this->registerHook('rightColumn')
                OR !$this->registerHook('cancelProduct')
                OR !$this->registerHook('adminOrder')
                OR !$this->registerHook('home'))
            return false;

        $execute = Db::getInstance()->Execute("
            CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."pagseguro_order` (
              `id_pagseguro_order` int(11) NOT NULL AUTO_INCREMENT,
              `id_order` int(10) DEFAULT NULL,
              `id_transaction` char(108) DEFAULT NULL,
              PRIMARY KEY (`id_pagseguro_order`),
              UNIQUE KEY `Unique` (`id_order`,`id_transaction`)
            ) AUTO_INCREMENT=0 ;
        ");
        
        $this->create_states();

        if (!Configuration::updateValue('PAGSEGURO_BUSINESS', 'pagseguro@seudominio.com.br')
                OR !Configuration::updateValue('PAGSEGURO_TOKEN', '')
                OR !Configuration::updateValue('PAGSEGURO_BTN', 0)
                OR !Configuration::updateValue('PAGSEGURO_BANNER', 0))
            return false;

        return true;
    }

    public function create_states() {
        $this->order_state = array(
            1 => array('ccfbff', '00100', 'PagSeguro - Aguardando Pagto', ''),
            2 => array('fcffcf', '00100', 'PagSeguro - Em análise', ''),
            3 => array('ffffff', '10100', 'PagSeguro - Aprovado', ''),
            4 => array('c9fecd', '11110', 'PagSeguro - Completo', 'payment'),
            5 => array('c9fecd', '11110', 'PagSeguro - Em Disputa', 'order_canceled'),
            6 => array('d6d6d6', '00100', 'PagSeguro - Em Aberto', ''),
            7 => array('fec9c9', '11110', 'PagSeguro - Cancelado', 'order_canceled'),
        );

        /** INSTALANDO STATUS PagSeguro * */
        foreach ($this->order_state as $key => $value) {
            $orderState = new OrderState();
            $orderState->name = array();
            foreach (Language::getLanguages() AS $language) {
                $orderState->name[$language['id_lang']] = $value[2];
            }
            $orderState->send_email = (integer) $value[1][1];
            $orderState->color = '#' . $value[0];
            $orderState->hidden = false;
            $orderState->delivery = (integer) $value[1][4];
            $orderState->logable = (integer) $value[1][3];
            $orderState->invoice = (integer) $value[1][0];

            if ($orderState->add()) {
                /** COPIANDO O ICONE ATUAL * */
                $file = (dirname(__file__) . "/icons/$key.gif");
                $newfile = (dirname(dirname(dirname(__file__))) . "/img/os/{$orderState->id}.gif");
                if (!copy($file, $newfile)) {
                    return false;
                }
            }

            /** GRAVA AS CONFIGURAÇÕES  * */
            Configuration::updateValue("PAGSEGURO_STATUS_{$key}", (int) $orderState->id);
        }

        return true;
    }

    private function delete_states() {
        $keys = array(1,2,3,4,5,6,7);
        foreach ($keys as $key) {
            $id_state = (integer) Configuration::get("PAGSEGURO_STATUS_{$key}");
            $objectState = new OrderState($id_state);
            $objectState->deleted = 1;
            $objectState->update();
            Configuration::deleteByName("PAGSEGURO_STATUS_{$key}");
        }
        return true;
    }

    public function uninstall() {
        if
        (
                !Configuration::deleteByName('PAGSEGURO_BUSINESS')
                OR !Configuration::deleteByName('PAGSEGURO_TOKEN')
                OR !Configuration::deleteByName('PAGSEGURO_BTN')
                OR !Configuration::deleteByName('PAGSEGURO_BANNER')
                OR !$this->delete_states()
                OR !parent::uninstall()
        )
            return false;

        return true;
    }

    public function getContent() {
        $this->_html = '<h2>PagSeguro</h2>';
        if (isset($_POST['submitPagSeguro'])) {
            if (empty($_POST['business']))
                $this->_postErrors[] = $this->l('Digite um e-mail para a cobrança');
            elseif (!Validate::isEmail($_POST['business']))
                $this->_postErrors[] = $this->l('Digite um e-mail válido para a cobrança');

            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('PAGSEGURO_BUSINESS', $_POST['business']);
                if (!empty($_POST['pg_token'])) {
                    Configuration::updateValue('PAGSEGURO_TOKEN', $_POST['pg_token']);
                }
                $this->displayConf();
            }
            else
                $this->displayErrors();
        }
        elseif (isset($_POST['submitPagSeguro_Btn'])) {
            Configuration::updateValue('PAGSEGURO_BTN', $_POST['btn_pg']);
            $this->displayConf();
        } elseif (isset($_POST['submitPagSeguro_Bnr'])) {
            Configuration::updateValue('PAGSEGURO_BANNER', $_POST['banner_pg']);
            $this->displayConf();
        }

        $this->displayPagSeguro();
        $this->displayFormSettingsPagSeguro();
        return $this->_html;
    }

    public function displayConf() {
        $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="' . $this->l('Confirmation') . '" />
			' . $this->l('Configurações atualizadas') . '
		</div>';
    }

    public function displayErrors() {
        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
		<div class="alert error">
			<h3>' . ($nbErrors > 1 ? $this->l('There are') : $this->l('There is')) . ' ' . $nbErrors . ' ' . ($nbErrors > 1 ? $this->l('errors') : $this->l('error')) . '</h3>
			<ol>';
        foreach ($this->_postErrors AS $error)
            $this->_html .= '<li>' . $error . '</li>';
        $this->_html .= '
			</ol>
		</div>';
    }

    public function displayPagSeguro() {
        $this->_html .= '
		<img src="' . __PS_BASE_URI__ . 'modules/pagseguro/imagens/pagseguro.jpg" style="float:left; margin-right:15px;" />
		<b>' . $this->l('Este módulo permite aceitar pagamentos via PagSeguro.') . '</b><br /><br />
		' . $this->l('Se o cliente escolher o módulo de pagamento, a conta do PagSeguro sera automaticamente creditado.') . '<br />
		' . $this->l('Você precisa configurar o seu e-mail do PagSeguro, para depois usar este módulo.') . '
		<br /><br /><br />';
    }

    public function displayFormSettingsPagSeguro() {
        $conf = Configuration::getMultiple(array(
                    'PAGSEGURO_BUSINESS',
                    'PAGSEGURO_TOKEN',
                    'PAGSEGURO_BTN',
                    'PAGSEGURO_BANNER'
                ));

        $businessPag = array_key_exists('business', $_POST) ? $_POST['business'] : (array_key_exists('PAGSEGURO_BUSINESS', $conf) ? $conf['PAGSEGURO_BUSINESS'] : '');
        $token = array_key_exists('pg_token', $_POST) ? $_POST['pg_token'] : (array_key_exists('PAGSEGURO_TOKEN', $conf) ? $conf['PAGSEGURO_TOKEN'] : '');
        $btn = array_key_exists('btn_pg', $_POST) ? $_POST['btn_pg'] : (array_key_exists('PAGSEGURO_BTN', $conf) ? $conf['PAGSEGURO_BTN'] : '');
        $bnr = array_key_exists('banner_pg', $_POST) ? $_POST['banner_pg'] : (array_key_exists('PAGSEGURO_BANNER', $conf) ? $conf['PAGSEGURO_BANNER'] : '');

        /** FORMULÁRIO DE CONFIGURAÇÃO DO EMAIL E DO TOKEN * */
        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Configurações') . '</legend>
			<label>' . $this->l('E-mail para cobrança') . ':</label>
			<div class="margin-form"><input type="text" size="33" name="business" value="' . htmlentities($businessPag, ENT_COMPAT, 'UTF-8') . '" /></div>
			<br />
			
			<label>' . $this->l('Token') . ':</label>
			<div class="margin-form"><input type="text" size="33" name="pg_token" value="' . $token . '" /></div>
			<br />
			
			<center><input type="submit" name="submitPagSeguro" value="' . $this->l('Atualizar') . '" class="button" /></center>
		</fieldset>
		</form>';
        /** /FORMULÁRIO DE CONFIGURAÇÃO DO EMAIL E DO TOKEN * */
        /** FORMULÁRIO DE CONFIGURAÇÃO DO BOTÃO DE PAGAMENTO * */
        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/themes.gif" />' . $this->l('Botão') . '</legend><br/>';

        foreach ($this->_botoes as $id => $value) {
            if ($btn == $id) {
                $check = 'checked="checked"';
            } else {
                $check = '';
            }

            $this->_html .= '
			<div>
			<input type="radio" name="btn_pg" value="' . $id . '" ' . $check . ' >';

            if ($value == 'default')
                $this->_html .= '<input type="submit" value="Pague com o PagSeguro" class="exclusive_large" />';
            else
                $this->_html .= '<img src="https://pagseguro.uol.com.br/Imagens/' . $value . '" />';

            $this->_html .= '</div>
			<br />';
        }

        $this->_html .= '<br /><center><input type="submit" name="submitPagSeguro_Btn" value="' . $this->l('Salvar') . '"
			class="button" />
		</center>
		</fieldset>
		</form>';
        /** /FORMULÁRIO DE CONFIGURAÇÃO DO BOTÃO DE PAGAMENTO * */
        /** FORMULÁRIO DE CONFIGURAÇÃO DO BANNER DE EXIBIÇÃO * */
        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/themes.gif" />' . $this->l('Banner') . '</legend><br/>';

        foreach ($this->_banners as $id => $value) {
            if ($bnr == $id) {
                $check = 'checked="checked"';
            } else {
                $check = '';
            }

            $this->_html .= '
			<div>
			<input type="radio" name="banner_pg" value="' . $id . '" ' . $check . ' >';

            $this->_html .= '
			<img src="https://pagseguro.uol.com.br/Imagens/Banners/' . $value . '" />';

            $this->_html .= '
			</div>
			<br />';
        }

        $this->_html .= '<br /><center><input type="submit" name="submitPagSeguro_Bnr" value="' . $this->l('Salvar') . '"
			class="button" />
		</center>
		</fieldset>
		</form>';
        /** /FORMULÁRIO DE CONFIGURAÇÃO DO BANNER DE EXIBIÇÃO * */
    }

    public function execPayment($cart) {
        global $cookie, $smarty;
 
        try {
            // Register this payment request in PagSeguro, to obtain the payment URL for redirect your customer.
            $invoiceAddress = new Address(intval($cart->id_address_invoice));
            $customerPag = new Customer(intval($cart->id_customer));
            $currencies = Currency::getCurrencies();
            $currencies_used = array();
            $currency = $this->getCurrency();

            $currencies = Currency::getCurrencies();
            foreach ($currencies as $key => $currency)
                $smarty->assign(array(
                    'error' => false,
                    'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
                    'currencies' => $currencies_used,
                    'imgBtn' => __PS_BASE_URI__ . "modules/pagseguro/imagens/pagseguro.jpg",
                    'imgBnr' => "https://pagseguro.uol.com.br/Imagens/Banners/" .
                    $this->_banners[Configuration::get('PAGSEGURO_BANNER')],
                    'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
                    'currencies' => $currencies_used,
                    'total' => number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $currency), 2, '.', ''),
                    'pagamento' => __PS_BASE_URI__ . "modules/pagseguro/validation.php",
                    'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
                    'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));
        } catch (Exception $e) {
            $smarty->assign("error", true);
            Tools::displayError('An error ocurred!');
        }

        return $this->display(__file__, 'payment_execution.tpl');
    }

    public function hookPayment($params) {
        global $smarty;
        $smarty->assign(array(
            'imgBtn' => __PS_BASE_URI__ . "modules/pagseguro/imagens/btnfinalizaBR.jpg",
            'this_path' => $this->_path, 'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
            'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));
        return $this->display(__file__, 'payment.tpl');
    }

    public function hookPaymentReturn($params) {
        global $smarty, $cookie;
        require_once "PagSeguroLibrary/PagSeguroLibrary.php";

        $transaction_code = Tools::getValue('transaction_id');
        $id_order = Tools::getValue("id_order");

        if( $transaction_code ){
            Db::getInstance()->Execute("INSERT INTO `"._DB_PREFIX_."pagseguro_order` VALUES (NULL, {$id_order}, '{$transaction_code}');");
        }
        
        $objOrder = new Order($id_order);

        //$transaction_code = Db::getInstance()->getValue("SELECT id_transaction FROM " . _DB_PREFIX_ . "pagseguro_order WHERE id_order = {$id_order} ");

        try {
            $credentials = new PagSeguroAccountCredentials(Configuration::get("PAGSEGURO_BUSINESS"), Configuration::get("PAGSEGURO_TOKEN"));
            $transaction = PagSeguroTransactionSearchService::searchByCode($credentials, $transaction_code);
            $statusValue = $transaction->getStatus()->getValue();
            $order_state = Configuration::get("PAGSEGURO_STATUS_{$statusValue}");
            $orderState = new OrderState($order_state);
            $status = $orderState->name[$cookie->id_lang];

            if ($objOrder->getCurrentState() !== intval($order_state)) {
                $customer = new Customer(intval($params['objOrder']->id_customer));
                $mailVars = array(
                    '{email}' => Configuration::get('PS_SHOP_EMAIL'),
                    '{firstname}' => stripslashes($customer->firstname),
                    '{lastname}' => stripslashes($customer->lastname),
                    '{terceiro}' => stripslashes($this->displayName),
                    '{id_order}' => stripslashes($id_order),
                    '{status}' => stripslashes($status)
                );
                $objOrderHistory = new OrderHistory();
                $objOrderHistory->id_order = intval($id_order);
                $objOrderHistory->changeIdOrderState($order_state, $id_order);
                $objOrderHistory->addWithemail(true, $mailVars);
            }
            
        } catch (PagSeguroServiceException $e) {
            return false;
            die($e->getMessage());
        }

        $cart = new Cart($params['objOrder']->id_cart);

        $smarty->assign(array(
            "id_order" => $id_order,
            "total" => $params['total_to_pay'],
            "codigo_pagseguro" => $transaction->getCode(),
            "status" => $status,
        ));

        return $this->display(__file__, 'payment_return.tpl');
    }

    function hookHome($params) {
        include(dirname(__FILE__) . '/retorno.php');
    }

    function getStatus($param) {
        global $cookie;
        $sql_status = Db::getInstance()->Execute
                ('
			SELECT `name`
			FROM `' . _DB_PREFIX_ . 'order_state_lang`
			WHERE `id_order_state` = ' . $param . '
			AND `id_lang` = ' . $cookie->id_lang . '
			
		');
        return mysql_result($sql_status, 0);
    }

    public function enviar($mailVars, $template, $assunto, $DisplayName, $idCustomer, $idLang, $CustMail, $TplDir) {
        Mail::Send(intval($idLang), $template, $assunto, $mailVars, $CustMail, null, null, null, null, null, $TplDir);
    }

    public function getUrlByMyOrder($myOrder) {
        $module = Module::getInstanceByName($myOrder->module);
        $pagina_qstring = __PS_BASE_URI__ . "order-confirmation.php?id_cart="
                . $myOrder->id_cart . "&id_module=" . $module->id . "&id_order="
                . $myOrder->id . "&key=" . $myOrder->secure_key;
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on")
            $protocolo = "http";
        else
            $protocolo = "https";
        $retorno = $protocolo . "://" . $_SERVER['SERVER_NAME'] . $pagina_qstring;
        return $retorno;
    }

    public function inicializaPagamento($cart, $urlRetorno){
        require_once "PagSeguroLibrary/PagSeguroLibrary.php";
        $paymentRequest = new PagSeguroPaymentRequest();
        $paymentRequest->setCurrency("BRL");

        foreach ($cart->getProducts() as $product) {
            $paymentRequest->addItem($product['id_product_attribute'], $product['name'], $product['quantity'], $product['price_wt']);
        }

        $customer = new Customer($cart->id_customer);
        $address = new Address($cart->id_address_delivery);

        $telefone_full = preg_replace("/([^0-9])/", null, $address->phone);
        $telefone_full = str_pad($telefone_full, 11, "0", STR_PAD_LEFT);
        $areacode = substr($telefone_full, 1, 2);
        $phone = substr($telefone_full, 3);

        $state = new State($address->id_state);

        $number = preg_replace("/([^0-9])/", null, $address->address2);

        $paymentRequest->setShippingAddress($address->postcode, $address->address1, $number, null, null, $address->city, $state->iso_code, $address->country);

        $CODIGO_SHIPPING = PagSeguroShippingType::getCodeByType('NOT_SPECIFIED');
        $paymentRequest->setShippingType($CODIGO_SHIPPING);
        $paymentRequest->setShippingCost(number_format($cart->getOrderShippingCost(), 2, ".", ""));

        $paymentRequest->setSender($customer->firstname . " " . $customer->lastname, $customer->email, $areacode, $phone);

        $order_id = (integer) Order::getOrderByCartId($cart->id);
        $paymentRequest->setReference($order_id);

        $paymentRequest->setRedirectURL($urlRetorno);

        $credentials = new PagSeguroAccountCredentials(Configuration::get("PAGSEGURO_BUSINESS"), Configuration::get("PAGSEGURO_TOKEN"));

        $url = $paymentRequest->register($credentials);

        return $url;
    }

}