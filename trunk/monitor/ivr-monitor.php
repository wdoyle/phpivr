<?php

/*
 *  ivr-monitor.php
 *
 *  Григорий Майстренко (Grygorii Maistrenko)
 *  grygoriim@gmail.com
 */

include (dirname(__FILE__)."/lib/ivr-monitor.conf.php");
include (dirname(__FILE__)."/lib/phpagi-2.14/phpagi-asmanager.php");

//$_ivr_config_path

$asm = new AGI_AsteriskManager();
/*
 * Try to connect to server
 */
if($asm->connect($_asm_host, $_asm_user, $_asm_passwd)) {
	/*
	 * Parse IVR config
	 */
	$menu_table = json_decode(file_get_contents($_ivr_config_path), TRUE);
	if (!$menu_table || !count($menu_table)) return false;

	
	/*
	 * Get active channels with AGI lounched
	 */ 
	$info = $asm->command("core show channels");
	$data = array();
	
	/*
	 * Filter lines with AGI
	 */
	foreach( preg_grep("/.*@.*:.*AGI/", explode("\n", $info["data"])) as $line) {
		$chan = preg_split("/ +/", $line);
		if ( preg_match("/.*AGI\((.*).*\)/", $chan[3], $matches) ) {
			$agi_params = preg_split("/,/", $matches[1]);
			
			/*
			 * if ivr menu with name of second AGI param exist
			 */
			if ($menu_table[$agi_params[1]]) {
				$call = array();
				$call['channel'] = $chan[0];
				$call['location'] = $chan[1];
				$call['state'] = $chan[2];
				$call['application'] = $chan[3];
				
				$cid = $asm->GetVar($chan[0], 'CALLERID(all)');
				$call['callerid'] = $cid['Value'];
				
				$data['calls'][] = $call;
			}
		}
	}
	if ($data && count($data)) {
		print_r(json_encode($data));
	}
	$asm->disconnect();
}
?>
