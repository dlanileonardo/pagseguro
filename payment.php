<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__) . '/pagseguro.php');

if (!$cookie->isLogged(true))
	Tools::redirect('authentication.php?back=order.php');
elseif (!Customer::getAddressesTotalById((int)($cookie->id_customer)))
	Tools::redirect('address.php?back=order.php?step=1');

$pagseguro = new pagseguro();
echo $pagseguro->execPayment($cart);

include_once(dirname(__FILE__) . '/../../footer.php');
