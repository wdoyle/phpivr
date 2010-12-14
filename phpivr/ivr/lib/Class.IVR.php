<?php

/*
 *  Class специально написан поскольку аналогичный не найден в недрах Internet 
 * 	
 * 	Для запуска использовать IVR::Run(id)
 *   id - идентификатор меню из phpivr.conf 
 * 
 *  На выходе:
 *  -1		- завершение IVR
 *  <номер>	- данные указанные в команде меню transfer
 * 
 *  Grygorii Maistrenko (Григорий Майстренко)
 *  grygoriim@gmail.com
 * 
 */

require_once (dirname(__FILE__)."/phpivr.conf.php");

class IVR {
	
	// Уровень при котором выводить информацию
	private $verbose_level = 3;
	
	// Уровень при котором выводить отладочную информацию
	private $dbg = 4;
	
	// Устанавливаем количество проигрывания меню если не нажимается клавиша терминала
	private $default_loop = 1;
	
	private $MSG_RUN_CURRENT_MENU_AGAIN = 'RUN_CURRENT_MENU_AGAIN';
	private $MSG_RUN_MENU = 'RUN_MENU';
	private $MSG_TRANSFER = 'TRANSFER';
	private $MSG_INPUT = 'INPUT'; // нажата кнопка [0-9\#\*]
	private $INP_SEQ_ARR = 'inputs';
	
	private $agi;
	
	/*
	 * Конструктор
	 */
	function IVR($agi) {
		if (!$agi) {
			echo "Переданы не все параметры\n";
			exit(1);
		}
		$this->agi = $agi;
	}
	
	/*
	 * Разбираем строку вида {<var>[=<val>][,...]}[:...]
	 */	
	function parseIvrOptions($options){
		foreach (explode("|", $options) as $option) {
			$properties = array (); // Init array
			foreach (explode(",", $option) as $property) {
				$property_parsed = array (); // Init array
				$tmp_arr = array (); // Init array
				$tmp_arr = explode("=", $property);
				$property_parsed["name"] = trim($tmp_arr[0]);
				// Специально так чтобы не выдавало ошибку при обращении к элементу с индексом 1 если его нет
				$property_parsed["value"] = (count($tmp_arr)>1)?trim($tmp_arr[1]):"";
				if ($property_parsed['name']){
						$properties[] = $property_parsed;
				}
			}
			if (is_array($properties)) $parsed_options[] = $properties;
		}
		if (!$parsed_options) return false; 
		return $parsed_options;
	}

	function json_decode($json)
	{
		// Author: walidator.info 2009
		$comment = false;
		$out = '$x=';

		for ($i=0; $i<strlen($json); $i++)
			{
			if (!$comment)
			{
				if ($json[$i] == '{') $out .= ' array(';
				else if ($json[$i] == '}') $out .= ')';
				else if ($json[$i] == ':') $out .= '=>';
				else $out .= $json[$i];
			}
			else $out .= $json[$i];
			if ($json[$i] == '"')    $comment = !$comment;
		}
		eval($out . ';');
		return $x;
	}

	
	/*
	 * Получает структуру меню
	 */
	function getIvrMenu($id_ivrmenu) {
		global $ivr_config_path;
		
		if (!$id_ivrmenu) return false;
		
		$rawconfig = trim(file_get_contents($ivr_config_path));
		
		$rawconfig = $this->ConfigPreprocessor($rawconfig);
		
		$res_table = $this->json_decode($rawconfig);
		
		if (!$res_table || !count($res_table) || !count($res_table[$id_ivrmenu])) return false;
		$menu_table = $res_table[$id_ivrmenu];
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Разбираем опции меню NAME='{$menu_table['name']}' | ID='{$id_ivrmenu}'");
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - ".print_r($menu_table, true));
		
		return $menu_table;
	}
	
	
	function ConfigPreprocessor($rawconfig) {
		$rawconfig = $this->ConfigIncludeFiles($rawconfig);
		$rawconfig = $this->ConfigReplaceEscapeChars($rawconfig);
		return $rawconfig;
	}


	function ConfigReplaceEscapeChars($rawconfig){

		$rawconfig = str_replace("\\`", "`", $rawconfig);
		return $rawconfig;
	}

	function ConfigIncludeFiles($rawconfig){
		while ($len = strlen($rawconfig)){
			$matches = array();
			$inc = array();
			$inquota = false;
			$pos = 0;
			$len = strlen($rawconfig);

			while (false !== ($i = strpos($rawconfig, "`", $pos))){
				$pos = $i + 1;
				if ($i > 0 && $rawconfig[$i-1] == "\\") continue;
				if ($pos >= $len && !$inquota) break;
				if (!$inquota){
					$inquota = true;
					$inc['start'] = $pos;
				}else{
					$inquota = false;
	
					$inc['len'] = $i - $inc['start'];
					$inc['str'] = substr($rawconfig, $inc['start'], $inc['len']);//
					if ($inc['str'] != "") $matches[] = $inc;
					$inc = array();
				} //else
			} //while pos `

			/*
			 * include files
			 */

			if (!isset($matches[0])) break;

			foreach ($matches as $match){
				$incfile = trim(file_get_contents($this->ConfigReplaceEscapeChars($match['str'])));
				$rawconfig = str_replace("`".$match['str']."`", $incfile, $rawconfig);
			}
		} // while match includes
		return $rawconfig;
	}
	
	/*
	 * Проигрывание сообщения и возврат введенной последовательности
	 * Отдает либо введенную последовательность либо false
	 */
	function getAgiData($say, $prompt_count=0, $loop=1){
		if (strlen($say) > 0) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Устанавливаем счетчик проигрывания сообщения loop='$loop'");
			
			while ( $loop > 0 ) {
				--$loop;
				if ( $prompt_count > 0 ) {
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Проигрываем сообщение '$say' с ожиданием нажатия");
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Максимальное количество символов для ввода prompt_count='$prompt_count'");
					$res_dtmf = $this->agi->get_data($say, 2000, $prompt_count);
				} else {
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Проигрываем сообщение '$say'");
					$this->agi->get_data($say, 1000);
				}
				
				if (isset($res_dtmf) && strlen($res_dtmf["result"]) > 0) {
					return $res_dtmf["result"];
				} 
			}
		}
		return false;
	}

	/*
	 *
	 */
	function runAsteriskCommands($astcmd){
		if (!is_array($astcmd) || !isset($astcmd[0])) return false;
		foreach ($astcmd as $cmd) {
			$cmd = trim($cmd);
			if (($pos = strpos($cmd, " "))!==false){
				$opt = trim(substr($cmd, $pos+1));
				$cmd = trim(substr($cmd, 0, $pos));
			}
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."EXEC $cmd($opt)");
			$this->agi->exec("$cmd", "$opt");
		}
		return true;
	}
	
	/*
	 * Выполнение опций IVR либо кнопки
	 */
	function RunOptions($options){
		if (!$options ) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Опции обработчика не получены. Завершаем меню");
			return -1;
		}
		
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Опции обработчика : '$options'");
		
		$options_parsed = $this->parseIvrOptions($options);
		
		if ( !$options_parsed ) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Опции обработчика не разобраны. Завершаем меню");
			return -1;
		}
		
		foreach ($options_parsed as $properties) {
			
			$loop = $this->default_loop;
			$prompt = false;
			$transfer = false;
			$say = '';
			$astcmd = array();
			
			// Читаем все опции меню
			foreach ($properties as $property) {
				switch ($property['name']) {
					case 'menu':
						$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Переходим в меню ID='{$property['value']}'");
						return array($this->MSG_RUN_MENU => $property['value']);
					case 'transfer':
						if (isset($property['value']) && strlen($property['value']) > 0) {
							$prompt = false;
							$transfer = $property['value'];
						} else {
							if (!$prompt) $prompt = 1;
							$transfer = '';
						}
						break;
					case 'hangup':
						$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Ложим трубку");
						$this->agi->hangup();
						return -1;
					case 'exit':
						$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Завершаем IVR");
						return -1;
					case 'say':
						if (isset($property['value']) && $property['value']) {
							$say = $property['value'];
						}
						break;
					case 'prompt':
						if (isset($property['value']) && $property['value']) $prompt = $property['value'];
						else $prompt = 1;
						break;
					case 'loop':
						if (isset($property['value']) && $property['value']) $loop = $property['value'];
						break;
					case '*':
                                                if (isset($property['value']) && $property['value']) $astcmd[] = $property['value'];
                                                break;
				} //switch
			} //foreach

			$this->runAsteriskCommands($astcmd);
			
			$MSG_TEXT = $this->getAgiData($say, $prompt, $loop);

			if ($transfer!==false) {
				$MSG = $this->MSG_TRANSFER;
				if (strlen($transfer) > 0) {
					$MSG_TEXT = $transfer;
				} elseif ($MSG_TEXT) $MSG_TEXT = 'Local/'.$MSG_TEXT;
				if (isset($MSG_TEXT)) $this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Переадресовываем вызов на '{$MSG_TEXT}'");
			} else {
				$MSG = $this->MSG_INPUT;
			}
			
			// возврат кода нажатой клавиши либо трансфера в формате меню
			if (isset($MSG_TEXT) && strlen($MSG_TEXT) > 0) {
				return array($MSG => $MSG_TEXT);
			} elseif ($prompt) { // если был запрос и не нажали клавишу
				
				//TODO: если ввели неправильную последовательность, то продолжать выполнение а не
				//проигрывать меню заново все цыклы
				
				if ($transfer==='') $this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Номер переадресации не получен");
				else $this->agi->verbose(basename(__FILE__).":".__LINE__." - "."DTMF не получен");
				//return -1; //Завершить меню если не получен номер трансфера либо дтмф
			}

		} //foreach

		// возврат если все опции меню отработали и небыло запроса на нажатие клавиши
		if (!$prompt) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Все элементы отработаны - завершаем меню");
			return -1;//return array($this->MSG_RUN_CURRENT_MENU_AGAIN =>1 );
		}

		return -1;
	}
	
	
	/*
	 * Запускает меню IVR
	 * На выходе может быть:
	 * -1		- завершение меню
	 * array	- данные указанные в команде меню transfer, либо menu
	 */
	function RunMenu($id_ivrmenu) {
		if (!$id_ivrmenu) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."IVR меню ID='$id_ivrmenu' не найдено. Завершаем");
			return -1;
		}
		
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Пытаемся получить IVR меню ID='$id_ivrmenu'");
		$menu = $this->getIvrMenu($id_ivrmenu);

		if (!$menu || !is_array($menu)) {
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."IVR меню ID='$id_ivrmenu' не найдено. Завершаем");
			return -1;
		}
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."IVR меню ID='$id_ivrmenu' успешно получено");
		
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Выполняем меню ID='$id_ivrmenu'");
		$resrun = $this->RunOptions($menu['options']);
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - RETURN - ".print_r($resrun, true), 4);
		
		/*
		 * Пока возвращается "код клавиши" либо "повтор меню"
		 * Иначе выталкиваем возврат и завершаем IVR::Run
		 */
		while ( isset($resrun[$this->MSG_INPUT]) || isset($resrun[$this->MSG_RUN_CURRENT_MENU_AGAIN]) ) {
			/*
			 * Если обработчик кнопки задан то выполняем его
			 * Если RUNMENUAGAIN либо нет обработчика - заново выполняем меню
			 */
			
			if ($resrun[$this->MSG_INPUT] == -1) $resrun = -1;// чтобы не уйти в вечный цикл
			
			if (isset($resrun[$this->MSG_RUN_CURRENT_MENU_AGAIN])) {
				$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Выполняем меню ID='$id_ivrmenu' еще раз");
				$resrun = $this->RunOptions($menu['options']);
				$this->agi->verbose(basename(__FILE__).":".__LINE__." - RETURN - ".print_r($resrun, true), 4);
			} elseif (isset($resrun[$this->MSG_INPUT])) {
				$keypressed = $resrun[$this->MSG_INPUT];
				$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Была нажата кнопка '{$keypressed}'");
				
				$userfield = $this->agi->get_variable('CDR(userfield)');
				$userfield = $userfield['data'];
				$this->agi->set_variable('CDR(userfield)', $userfield ."->". $keypressed);
				$userfield = $this->agi->get_variable('CDR(userfield)');
				$userfield = $userfield['data'];
				$this->agi->verbose(basename(__FILE__).":".__LINE__." - История ввода:".print_r($userfield, true));
				
				if (isset($menu[$this->INP_SEQ_ARR][$keypressed]) && $menu[$this->INP_SEQ_ARR][$keypressed] != "") {
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Обработчик последовательности '{$keypressed}'");
					$resrun = $this->RunOptions($menu[$this->INP_SEQ_ARR][$keypressed]);
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - RETURN - ".print_r($resrun, true), 4);
				} else {
					$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Обработчик последовательности '{$keypressed}' не задан");
					if (isset($menu['invalidinput_act']) && $menu['invalidinput_act'] != "") { // если есть обработчик поумолчанию
						$this->agi->verbose(basename(__FILE__).":".__LINE__." - "."Используем обработчик неправильной последовательности");
						$resrun = $this->RunOptions($menu['invalidinput_act']);
					} else $resrun = array($this->MSG_RUN_CURRENT_MENU_AGAIN => 1); // проиграть еще раз меню
				}
				
			}
		} // while

		return $resrun;
	}
	
	/*
	 * Запускает IVR
	 * На выходе:
	 * -1		- завершение IVR
	 * <номер>	- данные указанные в команде меню transfer
	 */
	function Run($id_ivrmenu) {

		$resrun = $this->RunMenu($id_ivrmenu);
		$this->agi->verbose(basename(__FILE__).":".__LINE__." - RETURN - ".print_r($resrun, true), 4);
		/*
		 * Пока ввозвращается "код меню" будем их проигрывать
		 * Иначе выталкиваем transfer либо -1
		 */
		while (isset($resrun[$this->MSG_RUN_MENU])) {
			$resrun = $this->RunMenu($resrun[$this->MSG_RUN_MENU]);
			$this->agi->verbose(basename(__FILE__).":".__LINE__." - RETURN - ".print_r($resrun, true), 4);
		}
		
		if (($resrun[$this->MSG_TRANSFER])) return $resrun[$this->MSG_TRANSFER];
		 
		return -1;
	}
	

}
?>
