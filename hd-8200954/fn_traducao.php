<?php
/* 26-07-2012 - MLG - Movi o include e as funções de tradução para um arquivo, assim fica mais fácil alterar
 * mantendo as atualizações num único lugar */

/* Criado por Fabio - 04/09/2008 */
include_once 'traducao.php';
include_once '_traducao_erro.php';
/**
 * Alterado por MLG 06-2012
 *   Admite array como primeiro parâmetro, gerando uma string de forma recursiva
 *   se não precisar de parâmetros, o de idioma é opcional, pega o idioma que estiver 'ativo'
 *   o 4º parâmetro não precisa mais ser obrigatóriamente um array, pode ser uma STRING CSV (desde que os valores não contenham ',')
 *       ex.: traduz(array(idx1, idx2), $con, $idioma, "nome,6")
 *       ex.: traduz(array(idx1, idx2), null, null, "nome,6")
 **/



if (!function_exists('traduz')) {
	function traduz($inputText, $con=null, $lang = null, $x_parametros = null){

		global $msg_traducao, $cook_idioma, $sistema_lingua , $moduloTraducao, $login_fabrica, $frases_traduzidas_tela, $con;

		if (!is_resource($con))
			$con = $GLOBALS['con'];

		$lang = strtolower($lang) ? : strtolower($cook_idioma);
		$lang = strtolower($lang) ? : strtolower($sistema_lingua);

		if (strlen($lang)==0) {
			$lang = 'pt-br';
		}

		if (substr($lang, 0, 2) == 'en')
			$lang = 'en-US';


		/*
			O modulo de tradução é definido no parametros adicionais da fabrica
		*/

		if (isset($moduloTraducao)) {

			if (!isset($moduloTraducao[$lang]) || $moduloTraducao[$lang] !== true) {
				$lang = 'pt-br';
			}

		} else {
			$lang = 'pt-br';
		}

		//MLG 01-06-2012 - Novidade: passando um array como 1º parâmetro, concatena todas as traduções usando os mesmos parâmetros
		if (is_array($inputText)) {
			$trad = array();
			$sep = ' ';

			if (isset($inputText['sep'])) {
				$sep = $inputText['sep'];
				unset($inputText['sep']);
			}

			foreach($inputText as $msgID) {
				if (!is_array($msgID)) // Evita fazer loooooop...
					$trad[] = traduz($msgID, $con, $lang, $x_parametros);
			}
			//if (count($trad))
			return implode($sep, $trad);
		}

		$inputID = $inputText;

		if (preg_match('/[A-Z ]/', $inputText))
			$inputText = preg_replace(
				'/\.*$/', '',
				preg_replace(
					'/[^a-z0-9\/%]+/', '.',
					mb_strtolower(
						strtr(
							$inputText,
							"áâàãäéêèëíîìïóôòõúùüçñÁÂÀÃÄÉÊÈËÍÎÌÏÓÔÒÕÚÙÜÇÑ",
							"aaaaaeeeeiiiioooouuucnAAAAAEEEEIIIIOOOOUUUCN"
						)
					)
				)
			);

		$mensagem = $msg_traducao[$lang][$inputText];

		$texto = $inputText;

		if (!isset($msg_traducao[$lang][$inputText])) {
    }

		/**
		 * log erros ao tentar localizar um texto. No DEVEL.
		 */
		if (strlen($mensagem)==0 and is_resource($con) and $GLOBALS['_serverEnvironment'] == 'development') {
			$logged = pg_fetch_result(
				pg_query(
					$con,
					"SELECT msg_id FROM tmp_msg_sem_traduzir
					  WHERE msg_id = '$inputText'
					    AND idioma = '$lang'"
				), 0, 0
			);

			if (!$logged) {
				$sql = "INSERT INTO tmp_msg_sem_traduzir
						       (msg_id,idioma,programa)
						VALUES ('$inputText', '$lang','{$_SERVER['PHP_SELF']}')";
				$x_res = @pg_query($con,$sql);
			}
		}

		if (strlen($mensagem)==0)
			$mensagem = $msg_traducao['pt-br'][$inputText];

		if (strlen($mensagem)==0)
			$mensagem = (strpos($inputID, ' ') !== false) ?
				$inputID : ucwords(str_replace('.',' ',$inputID));


		if ($x_parametros) {
			if (!is_array($x_parametros)) {
				$x_parametros = explode(",",$x_parametros);
			}
			while ( list($x_variavel,$x_valor) = each($x_parametros)) {
				$mensagem = preg_replace('/%/',$x_valor,$mensagem,1);
			}
		}
		return $mensagem;
	}
}


/* Criado por Paulo e alterado por Fabio (abaixo) - 03-09-2008 */
if (!function_exists("fecho")) {
	function fecho($inputText,$con=null,$cook_idioma =  null,$x_parametros = null){
		echo traduz($inputText, null, $cook_idioma, $x_parametros);
	};
}

