<?php

	function isFileTexto($file = false){ 
		if(!$file)
			return false;

		// Valida se o arquvo é um TXT
		if(in_array(pathinfo($file,PATHINFO_EXTENSION), array('txt')))
			return true;

		return false;
	}



	function msgError(Array $error = Array()){
		if(count($error) == 0)
			return ; 

		$str = Array();

		$str[] = "<div class='alert alert-error'>";
			$str[] = "<strong>Error!</strong>";
			$str[] = '<button type="button" class="close" data-dismiss="alert">&times;</button>';
			$str[] = "<ul>";
				foreach ($error as $key => $erro) {
					$str[] = "<li>
									<strong>{$key}</strong><br />
									".implode("<br />", $erro)."
					</li>";
				}
			
			$str[] = "</ul>";
		$str[] = "</div>";

		echo implode("\n", $str);
	}