<?php

// TESTE
// http://novaomega.localhost/?notificationCode=8002645A-B7C1-4058-B0F8-161230F2490C&notificationType=transaction

$code = (isset($_POST['notificationCode']) && trim($_POST['notificationCode']) !== "" ? trim($_POST['notificationCode']) : null);
$type = (isset($_POST['notificationType']) && trim($_POST['notificationType']) !== "" ? trim($_POST['notificationType']) : null);

//$code = (isset($_GET['notificationCode']) && trim($_GET['notificationCode']) !== "" ? trim($_GET['notificationCode']) : null);
//$type = (isset($_GET['notificationType']) && trim($_GET['notificationType']) !== "" ? trim($_GET['notificationType']) : null);

require_once "PagSeguroLibrary/PagSeguroLibrary.php";

function TransactionNotification($notificationCode) {
    ob_clean();
    global $cookie;

    $credentials = new PagSeguroAccountCredentials(Configuration::get("PAGSEGURO_BUSINESS"), Configuration::get("PAGSEGURO_TOKEN"));

    try {
        $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);

        $id_transaction = $transaction->getCode();
        $id_status = $transaction->getStatus()->getValue();

        $order_state = Configuration::get("PAGSEGURO_STATUS_{$id_status}");
        $orderState = new OrderState($order_state);
        $status = $orderState->name[$cookie->id_lang];

        $id_order = Db::getInstance()->getValue("SELECT id_order FROM " . _DB_PREFIX_ . "pagseguro_order WHERE id_transaction = '{$id_transaction}'");

        $order = new Order(intval($id_order));

        /** ENVIO DO EMAIL * */
        $pagseguro = new pagseguro();
        $idCustomer = $order->id_customer;
        $idLang = $order->id_lang;
        $customer = new Customer(intval($idCustomer));

        $mailVars = array
            (
            '{email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{firstname}' => stripslashes($customer->firstname),
            '{lastname}' => stripslashes($customer->lastname),
            '{terceiro}' => stripslashes($pagseguro->displayName),
            '{id_order}' => stripslashes($pagseguro->currentOrder),
            '{status}' => stripslashes($status)
        );

        $pagseguro->enviar($mailVars, 'pagseguro', $status, $pagseguro->displayName, $idCustomer, $idLang, $customer->email, 'modules/pagseguro/mails/');
        /** /ENVIO DO EMAIL * */
        $extraVars = array();
        $history = new OrderHistory();
        $history->id_order = intval($id_order);
        $history->changeIdOrderState(intval($order_state), intval($id_order));
        $history->addWithemail(true, $extraVars);
        die("Sucesso!");
    } catch (PagSeguroServiceException $e) {
        var_dump($e);
        die("Error!");
    }
}

if ($code && $type) {

    $notificationType = new PagSeguroNotificationType($type);
    $strType = $notificationType->getTypeFromValue();

    switch ($strType) {

        case 'TRANSACTION':
            TransactionNotification($code);
            break;

        default:
            var_dump($strType);
            break;
    }

    //self::printLog($strType);
}
