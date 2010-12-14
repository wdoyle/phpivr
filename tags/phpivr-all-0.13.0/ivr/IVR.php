#!/usr/bin/php -q
<?php

/*
 *  IVR.php
 *  
 *  IVR AGI приложение для Asterisk PBX
 *  
 *  Григорий Майстренко (Grygorii Maistrenko)
 *  grygoriim@gmail.com
 */

include (dirname(__FILE__)."/lib/Class.IVR.php");
include (dirname(__FILE__)."/lib/phpagi-2.14/phpagi.php");

$agi = new AGI();

//$agi->verbose(basename(__FILE__).":".__LINE__." - Входящие параметры ".print_r($argv, true));

if (isset($argv[1]) && $argv[1]!=""){
	$MENUID = $argv[1];
	//$agi->verbose(basename(__FILE__).":".__LINE__." - Передан параметр".print_r($resrun, true));
} else {
	$MENUID = "common";
}

$ivr = new IVR($agi);

$resrun = $ivr->Run($MENUID);

if ($resrun != '-1'){
	// делаем трансфер
	$resrun = $agi->exec("TRANSFER $resrun");
	$agi->verbose(basename(__FILE__).":".__LINE__." - ".print_r($resrun, true));
}

?>
