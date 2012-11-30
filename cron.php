<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require_once "PagSeguroLibrary/PagSeguroLibrary.php";

class searchTransactionsByDateInterval {

	public static function main() {
		
		$mktimeontem = mktime (0, 0, 0, date("m")  , date("d")-2, date("Y"));
		$initialDate = date("Y-m-d", $mktimeontem)  . "T" . '00:00';
		$finalDate = date("Y-m-d") . "T" . '00:00';
		
		$pageNumber = 1;
		$maxPageResults = 40;
		
		try {
			$credentials = new PagSeguroAccountCredentials(Configuration::get("PAGSEGURO_BUSINESS"), Configuration::get("PAGSEGURO_TOKEN"));			
			$result = PagSeguroTransactionSearchService::searchByDate($credentials, $pageNumber, $maxPageResults, $initialDate, $finalDate);
			
			self::printResult($result, $initialDate, $finalDate);
		} catch (PagSeguroServiceException $e) {
			die($e->getMessage());
		}
		
	}
	
	
	public static function printResult(PagSeguroTransactionSearchResult $result, $initialDate, $finalDate) {
		$finalDate = $finalDate ? $finalDate : 'now';
		echo "<h2>Search transactions by date</h2>";
		echo "<h3>$initialDate to $finalDate</h3>";
		$transactions = $result->getTransactions();
		$inseridas = 0;

		if (is_array($transactions) && count($transactions) > 0) {
			foreach($transactions as $key => $transactionSummary) {
				if( Order::existsInDatabase($transactionSummary->getCode(), "orders") ){
					$id_order = $transactionSummary->getReference();
					$id_transaction = $transactionSummary->getCode();
					if( !Db::getInstance()->getValue("SELECT `id_pagseguro_order` FROM `" . _DB_PREFIX_ . "pagseguro_order` WHERE
					`id_order` = '{$id_order}' AND `id_transaction` = '{$id_transaction}' ") ){
						Db::getInstance()->Execute("INSERT INTO `" . _DB_PREFIX_ . "pagseguro_order` 
						(`id_pagseguro_order`, `id_order`, `id_transaction` )
							VALUES
						(NULL, '{$id_order}', '{$id_transaction}');");
						$inseridas++;
					}

				}
			}
		}
		echo count($transactions) . " Transação(ões) encontradas. <br />";
		echo $inseridas . " Transação(ões) atualizadas. <br />";
	}
	
}

searchTransactionsByDateInterval::main();