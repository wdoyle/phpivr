#!/usr/bin/php
<?

class ivr{
	function json_decode($json)
	{ 
		// Author: walidator.info 2009
		$comment = false;
		$out = '$x=';
  	 
		for ($i=0; $i<strlen($json); $i++)
		{
			if (!$comment)
			{
				if ($json[$i] == '{')        $out .= ' array(';
				else if ($json[$i] == '}')    $out .= ')';
				else if ($json[$i] == ':')    $out .= '=>';
				else                         $out .= $json[$i];           
			}
			else $out .= $json[$i];
			if ($json[$i] == '"')    $comment = !$comment;
		}
		eval($out . ';');
		return $x;
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


}
$ivr = new ivr();
$config = file_get_contents("/etc/asterisk/phpivr.conf");
echo $ivr->ConfigPreprocessor($config);
var_dump($ivr->json_decode($ivr->ConfigPreprocessor($config), true));


?>
