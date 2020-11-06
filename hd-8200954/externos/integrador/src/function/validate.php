<?php

	function isFileTexto($file = false) { 
		if(!$file)
			return false;

		// Valida se o arquvo é um TXT
		if(in_array(strtolower(pathinfo($file,PATHINFO_EXTENSION)), array('txt')))
			return true;

		return false;
	}



	function msgError($error = Array()){
		if(count($error) == 0)
			return true; 

		$str = Array();

		$str[] = "<div class='alert alert-error'><strong>Error!</strong>".
				 '<button type="button" class="close" data-dismiss="alert">&times;</button>'.
				 "<ul>";
		foreach ($error as $key => $erro) {
			$str[] = "<li>
						<strong>{$key}</strong><br />
						".implode("<br />", $erro)."
			</li>";
		}
	
		$str[] = "</ul></div>";

		echo implode("\n", $str);
	}
