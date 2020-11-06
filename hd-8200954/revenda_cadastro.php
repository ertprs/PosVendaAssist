<?php
session_start();

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

//SÛ para testes
include "helpdesk/mlg_funciones.php";

/*  ValidaÁ„o...    */
if (!function_exists('validaCNPJ')) {
	function validaCNPJ($TaxID, $return_str = true) {
		global $con;    // Para conectar com o banco...
	// 	echo "Validando $TaxID...<br>";
		$cnpj = preg_replace("/\D/","",$TaxID);   // Limpa o cnpj / CNPJ
	// 	echo "Validando $cnpj...<br>";
		if (strlen($cnpj) != 11 and strlen($cnpj) != 14) return false;

		if(strlen($cnpj) > 0){
			$res_cnpj = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cnpj')");
			if ($res_cnpj === false) {
				return ($return_str) ? pg_last_error($con) : false;
			}
		}
		return ($return_str) ? $cnpj : true;
	}
}

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if (!function_exists('is_email')) {
	function is_email($email=""){   // False se n„o bate...
		return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
	}
}

//  Para testes da tela de pesquisa
if (preg_match('/revenda_cadastro(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (file_exists("pesquisa_revenda$suffix.php"))		 $pr_suffix = $suffix;
}

$estados_BR	= array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO",
					"MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR",
					"RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO");

/*  FunÁıes v·rias...   */
if (!function_exists('getPost')) {
	function getPost($param,$get_first = false) {
		if ($get_first) {
			if (isset($_GET[$param]))  return anti_injection($_GET[$param]);
			if (isset($_POST[$param])) return anti_injection($_POST[$param]);
		} else {
			if (isset($_POST[$param])) return anti_injection($_POST[$param]);
			if (isset($_GET[$param]))  return anti_injection($_GET[$param]);
		}
		return null;
	}
}

if (!function_exists('pg_quote')) {
	function pg_quote($str, $type_numeric = false) {
	    if (is_bool($str))   return ($str===true) ? 'TRUE':'FALSE';
		if (is_null($str))	 return 'NULL';
		if (is_numeric($str) and $type_numeric) return $str;
		if (in_array($str,array('null','true','false'))) return strtoupper($str);
		return "'".pg_escape_string($str)."'";
	}
}

if (!function_exists('tira_acentos')) {
	function tira_acentos ($texto) {
		$acentos      = array("com" => "·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«",
							  "sem"	=> "aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC");
		return strtr($texto,$acentos['com'], $acentos['sem']);
	}
}
//  Fim funÁıes
//  HD 234135 - MLG - Para fazer com que uma f·brica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3));

//  Autocomplete
if ($_GET['ajax']=='rv_nome') {
    $razao_social = mb_strtoupper(anti_injection($_GET['q']));  // mb_ para mudar tambÈm os acentos e Á
    $limite = anti_injection($_GET['limit']);
    if (is_numeric($limite)) $limite = "LIMIT $limite";

    $sql_ac="SELECT nome FROM tbl_revenda_fabrica WHERE nome ~ '^$razao_social' $limite";
    if ($usa_rev_fabrica) $sql_ac="SELECT contato_razao_social AS nome
                                     FROM tbl_revenda_fabrica
                                    WHERE fabrica = $login_fabrica
                                      AND contato_razao_social ~* '^$razao_social' $limite";

    $res_ac = pg_query($con, $sql_ac);
    if (is_resource($res_ac)) {
        if (pg_num_rows($res_ac)) {
            $revendas = pg_fetch_all($res_ac);

            foreach ($revendas as $revenda_nome) {
            	echo $revenda_nome[nome]."\n";
            }
        }
    }
    exit;
}

if ($_GET['ajax']=='rv_cidade' and isset($_GET['q'])) {
	$q = utf8_decode(anti_injection($_GET["q"]));
	$q = tira_acentos($q);
	$cidade = preg_replace('/\W/', '.', $q);
	$limite = anti_injection($_GET['limit']);
	$estado = anti_injection($_GET['estado']);
    if (is_numeric($limite)) $limite = "LIMIT $limite";

	if (in_array($estado, $estados_BR)) $w_estado = "estado = '$estado' AND";

	$sql_c = "SELECT cidade, estado, cod_ibge AS cod_cidade FROM tbl_ibge ";
	if ($usa_rev_fabrica) {
		$sql_c = "SELECT cidade, estado, cod_ibge AS cod_cidade FROM tbl_ibge ";
		$sql_ac= "SELECT contato_razao_social AS nome
									   FROM tbl_revenda_fabrica
									  WHERE fabrica = $login_fabrica
										AND contato_razao_social ~* '^$razao_social' $limite";
	} else {
		//$sql_c = "SELECT DISTINCT ON (nome, estado) nome AS cidade, estado, cidade AS cod_cidade FROM tbl_cidade ";
		$sql_ac="SELECT nome FROM tbl_revenda WHERE nome ~ '^$razao_social' $limite";
	}

	//HD 682849 - Erro ao recuperar o nome da cidade.
	$sql_c.= "WHERE $w_estado TRANSLATE(TRIM(cidade),
										'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
										'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC'
							   ) ~* '$cidade' ORDER BY estado, cidade $limite";

	$res_c = @pg_query($con, $sql_c);
	if (!is_resource($res_c) or @pg_num_rows($res_c) == 0) exit(" | | ");
	$cidades = pg_fetch_all($res_c);

	foreach ($cidades as $info_cidade) {
		extract($info_cidade);
		echo "$cidade|$estado|$cod_cidade\n";
    }
	exit;
}

//  Procura a raz„o social da Matriz, baseado no radical do CNPJ.
if ($_GET['ajax']=='rv_cnpj_nome') {
	$cnpj = preg_replace('/\D/', '', $_GET['cnpj']);

	if (strlen($cnpj) < 14) {
		$radical_cnpj = substr($cnpj, 0, 8);

		$sql = "
		SELECT
		tbl_revenda_fabrica.contato_razao_social ,
		count(contato_razao_social)              ,
		tbl_revenda_fabrica.revenda_fabrica

		FROM
		tbl_revenda_fabrica
		JOIN tbl_cidade ON tbl_revenda_fabrica.cidade = tbl_cidade.cidade

		WHERE
		cnpj LIKE '$radical_cnpj%'
		AND fabrica = {$login_fabrica}

		GROUP BY contato_razao_social, tbl_revenda_fabrica.revenda_fabrica ORDER BY count(contato_razao_social) DESC LIMIT 1";
	}
	elseif (strlen($cnpj) == 14) {
		$sql = "
		SELECT
		tbl_revenda_fabrica.contato_razao_social ,
		count(contato_razao_social)              ,
		tbl_revenda_fabrica.revenda_fabrica      ,
		tbl_revenda_fabrica.contato_endereco     ,
		tbl_revenda_fabrica.contato_numero       ,
		tbl_revenda_fabrica.contato_complemento  ,
		tbl_revenda_fabrica.contato_bairro       ,
		tbl_revenda_fabrica.contato_cep          ,
		tbl_cidade.estado                        ,
		tbl_cidade.nome AS cidade_nome           ,
		tbl_revenda_fabrica.contato_fone         ,
		tbl_revenda_fabrica.ie                   ,
		tbl_revenda_fabrica.contato_nome         ,
		tbl_revenda_fabrica.contato_email        ,
		tbl_revenda_fabrica.contato_fax

		FROM
		tbl_revenda_fabrica
		JOIN tbl_cidade ON tbl_revenda_fabrica.cidade = tbl_cidade.cidade

		WHERE
		cnpj = '$cnpj'
		AND fabrica = {$login_fabrica}

		GROUP BY contato_razao_social,contato_endereco,contato_numero,contato_complemento,contato_bairro,contato_cep,tbl_cidade.estado,tbl_cidade.nome,contato_fone,ie,contato_nome,contato_email,contato_fax,tbl_revenda_fabrica.revenda_fabrica ORDER BY count(contato_razao_social) DESC LIMIT 1";
	}
    $res = pg_query($con, $sql);
    $total = pg_num_rows($res);
    $i = 0;
    if ($total) {
	    $razao       =  pg_fetch_result($res, 0, contato_razao_social);
	    $revenda_fabrica = pg_fetch_result($res, 0, revenda_fabrica);
		if(strlen($cnpj) == 14){
		$edereco     =  pg_fetch_result($res, 0, contato_endereco);
		$numero      = pg_fetch_result($res, 0, contato_numero);
		$complemento = pg_fetch_result($res, 0, contato_complemento);
		$bairro      = pg_fetch_result($res, 0, contato_bairro);
		$cep         = pg_fetch_result($res, 0, contato_cep);
		$estado      = pg_fetch_result($res, 0, estado);
		$cidade      = pg_fetch_result($res, 0, cidade_nome);
		$fone        = pg_fetch_result($res, 0, contato_fone);
		$ie          = pg_fetch_result($res, 0, ie);;
		$contato     = pg_fetch_result($res, 0, contato_nome);
		$email       = pg_fetch_result($res, 0, contato_email);
		$fax         = pg_fetch_result($res, 0, contato_fax);

		echo "$razao|$edereco|$numero|$complemento|$bairro|$cep|$estado|$cidade|$fone|$ie|$contato|$email|$fax|$revenda_fabrica";
		}
		else{

			echo "$razao|$revenda_fabrica";
		}
        exit;
    }
	if($total == 0 and strlen($cnpj) > 8){
		$cnpj = substr(preg_replace('/\D/', '', $_GET['cnpj']),0,8);

		$sql = "SELECT contato_razao_social,
	               count(contato_razao_social),
		       tbl_revenda_fabrica.revenda_fabrica
				   FROM tbl_revenda_fabrica
				   WHERE cnpj = '$cnpj'
				   GROUP BY contato_razao_social, revenda_fabrica ORDER BY count(contato_razao_social) DESC LIMIT 1";
		$res = pg_query($con, $sql);
		$total1 = pg_num_rows($res);
		if ($total1) {
			$razao       =  pg_fetch_result($res, 0, contato_razao_social);
			$revenda_fabrica = pg_fetch_result($res, 0, revenda_fabrica);

			echo "$razao|$revenda_fabrica";
		}
	}

	if(!$otal and !$total1)
	{
		exit("Sem resultados");
	}

    exit;
}

$btn_acao   = strtolower ($_POST['btn_acao']);
$revenda    = preg_replace('/\D/', '', getPost("revenda"));

if ($btn_acao == 'reset') {
	header("location:" . $PHP_SELF);
	die;
}

if (is_null(getPost("pais")) or getPost("pais") == '') $pais = $login_pais;
if ($pais == '') $pais = 'BR';

if (is_null($revenda) or $revenda == '') unset($revenda);

// pre_echo($_REQUEST, 'Dados');
#-------------------- GRAVAR -----------------
if ($btn_acao == "gravar") {
    $cnpj = getPost("cnpj");
    $cnpj = preg_replace( '/\D/', '', $cnpj);
    $nome = substr(getPost("nome"), 0, 50); //HD 682849 - Max. 50

    if ($cnpj != '') {
		$valida_cpf_cnpj = verificaCpfCnpj($cnpj);

		if(empty($valida_cpf_cnpj)){
			$xcnpj = validaCNPJ($cnpj);
			if (!is_numeric($xcnpj)) $msg_erro[] = $xcnpj;
		}else{
			$msg_erro[] = $valida_cpf_cnpj;
		}
    } else {
		$msg_erro[] = traduz("digite.o.cnpj",$con,$cook_idioma);
    }

	if (!isset($revenda) and count($msg_erro) == 0 and $nome == '' and is_numeric($xcnpj)) {
		// verifica se revenda est· cadastrada, o usu·rio passa apenas o CNPJ
		$sql = "SELECT revenda FROM tbl_revenda WHERE  cnpj = '$xcnpj'";
        if ($usa_rev_fabrica)
            $sql = "SELECT cnpj FROM tbl_revenda_fabrica
                                  WHERE cnpj = '$xcnpj' AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			$revenda = pg_fetch_result($res,0,0);
			header ("Location: $PHP_SELF?revenda=$revenda");
			exit;
		}else{
			$msg_erro[] = traduz("revenda.nao.cadastrado.favor.completar.os.dados.do.cadastro",$con,$cook_idioma);
			$nova_rev   = true;
		}
	}

	if (!count($msg_erro)) {
        $ie         = getPost("ie");
        $endereco   = getPost("endereco");
        $numero     = getPost("numero");
        $complemento= getPost("complemento");
        $bairro     = getPost("bairro");
        $cep        = preg_replace('/\D/', '', getPost("cep"));
        $cidade     = getPost("cidade");
        $estado     = getPost("estado");
        $pais       = getPost("pais");
        $fone       = getPost("fone");
        $fax        = getPost("fax");
        $contato    = getPost("contato");
        $email      = getPost("email");
        $codigo_cidade = getPost("codigo_cidade");

        if ($cidade == '') {
    		$msg_erro[] = traduz("favor.informar.a.cidade",$con,$cook_idioma);
    	}
        if ($estado == '') {
    		$msg_erro[] = traduz("favor.informar.o.estado",$con,$cook_idioma);
    	}

        if (is_null($endereco) or is_null($numero) or is_null($cidade) or is_null($estado) or is_null($cep)) {
            $msg_erro[] = "O endereÁo da revenda est· incompleto";
        }

    	if (is_null($fone) && ( in_array($login_fabrica, array(11,172)) && $login_posto==6359)) { // HD 51964
    		$msg_erro = "Digite o telefone da revenda";
    	}

        $mail   = (is_email($email)) ? $email : null;
        $cep    = ((strlen($cep) == 8 and $pais == 'BR') or $pais != 'BR') ? $cep : null;
        $estado = (in_array($estado, $estados_BR)) ? $estado : null;

    	if(strlen($login_pais) == 0) $login_pais = 'BR';

		if (strlen ($msg_erro) == 0) {
			if ($login_pais == "BR") {
				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cod_cidade = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
						$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade_ibge}', '{$cidade_estado_ibge}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						$cod_cidade = pg_fetch_result($res, 0, "cidade");
					} else {
						$msg_erro .= "Cidade n„o encontrada";
					}
				}
			} else {
				$sql = "SELECT	cidade
						FROM	tbl_cidade
						WHERE	nome   = UPPER(fn_retira_especiais('$cidade'))
						AND	estado = UPPER('$estado')";
				$res = pg_exec ($con,$sql);

				if(@pg_numrows($res) > 0){
					$cod_cidade = pg_result($res,0,0);
				}else{
					$cidade = strtoupper($cidade);
					$estado = strtoupper($estado);

					$sql = "INSERT INTO tbl_cidade
							(
								nome ,
								estado
							)VALUES(
								'$cidade',
								'$estado'
							)";
					$res = @pg_exec ($con,$sql);

					$res		= @pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
					$cod_cidade	= pg_result ($res,0,0);
				}
			}
		}
    }
    	#----------------------------- Dados ---------------------
    if (!count($msg_erro)) {
        /*
		$nome       = mb_strtoupper(pg_quote($nome));  // Raz„o Social
        $cnpj       = pg_quote($xcnpj);
        $ie         = pg_quote($ie);
        $endereco   = pg_quote($endereco);
        $numero     = pg_quote($numero);
        $complemento= pg_quote($complemento);
        $bairro     = pg_quote($bairro);
        $cep        = pg_quote($cep);
        $xcidade    = pg_quote($xcidade); // Se È IBGE È text, se È tbl_cidade, È numÈrico
        $xcidadeibge= pg_quote($xibge, true); // Se È IBGE È text, se È tbl_cidade, È numÈrico
        $estado     = pg_quote($estado);
        $pais       = pg_quote($pais);
        $fone       = pg_quote($fone);
        $fax        = pg_quote($fax);
        $contato    = pg_quote($contato);
        $email      = pg_quote($email);
		*/

        if ($revenda == '') {
    		$sql_r = "SELECT revenda FROM tbl_revenda WHERE  cnpj = '$xcnpj';";
    		$res_r = pg_query($con,$sql_r);

    		if (pg_num_rows($res_r) > 0) {
    			$revenda = pg_fetch_result($res_r, 0, 0);
            }
        }

		if ($usa_rev_fabrica) {
            $sql_u = "SELECT revenda_fabrica FROM tbl_revenda_fabrica WHERE cnpj = '$cnpj' AND fabrica = $login_fabrica";
            $res_u = pg_query($con, $sql_u);
            $update = (pg_num_rows($res_u) == 1) ? true : false;

	        if ($update) {

	            $sql = "UPDATE tbl_revenda_fabrica SET
	                        contato_razao_social= '$nome'         ,
	                        contato_endereco    = '$endereco'     ,
	                        contato_numero      = '$numero'       ,
	                        contato_complemento = '$complemento'  ,
	                        contato_bairro      = '$bairro'       ,
	                        contato_cep         = '$cep'          ,
	                        contato_fone        = '$fone'         ,
	                        contato_fax         = '$fax'          ,
	                        contato_email       = '$email'        ,
	                        contato_nome        = '$contato'      ,
							ie                  = '$ie'            ,
							cidade				= $cod_cidade
	                    WHERE tbl_revenda_fabrica.cnpj = '$cnpj' AND fabrica = $login_fabrica";
	        } else {
	        	if(intval($revenda) == 0){

					$sql_r = "
						INSERT INTO tbl_revenda (
							nome       ,
							cnpj       ,
							ie         ,
							endereco   ,
							numero     ,
							complemento,
							bairro     ,
							cep        ,
							cidade     ,
							contato    ,
							email      ,
							fone       ,
							fax        ,
							pais
						) VALUES (
							'$nome'		,
							'$cnpj'		,
							'$ie'		,
							'$endereco'	,
							'$numero'	,
							'$complemento',
							'$bairro'	,
							'$cep'		,
							$cod_cidade,
							'$contato'	,
							'$email'	,
							'$fone'		,
							'$fax'		,
							'$login_pais'
						)
	                    RETURNING revenda";
                   	$res_r = pg_query($con, $sql_r);
                   	$revenda = pg_fetch_result($res_r, 0, 'revenda');
	        	}

				$sql = "INSERT INTO tbl_revenda_fabrica (
									fabrica,
									contato_razao_social,
									contato_endereco,
									contato_numero,
									contato_complemento,
									contato_bairro,
									contato_cep,
									contato_fone,
									contato_fax,
									contato_email,
									contato_nome,
									cnpj,
									ie,
									cidade,
									revenda
							) VALUES (
									$login_fabrica,
									'$nome',
									'$endereco',
									'$numero',
									'$complemento',
									'$bairro',
									'$cep',
									'$fone',
									'$fax',
									'$email',
									'$contato',
									'$cnpj',
									'$ie',
									$cod_cidade,
									$revenda
							) RETURNING revenda_fabrica";
	        }
		} else {
			if (strlen ($revenda) > 0) {  // update. O CNPJ n„o deve mudar!
				$sql = "UPDATE tbl_revenda SET
							nome		= '$nome'       ,
							/*cnpj		= '$cnpj'       ,*/
							ie		    = '$ie'         ,
							endereco	= '$endereco'   ,
							numero		= '$numero'     ,
							complemento	= '$complemento',
							bairro		= '$bairro'     ,
							cep		    = '$cep'        ,
							cidade		= $cod_cidade,
							contato		= '$contato'    ,
							email		= '$email'      ,
							fone		= '$fone'       ,
							fax		    = '$fax'        ,
							pais		= '$login_pais'
						WHERE tbl_revenda.revenda = $revenda";
			}else{
				#-------------- INSERT ---------------
				$sql = "INSERT INTO tbl_revenda (
							nome       ,
							cnpj       ,
							ie         ,
							endereco   ,
							numero     ,
							complemento,
							bairro     ,
							cep        ,
							cidade     ,
							contato    ,
							email      ,
							fone       ,
							fax        ,
							pais
						) VALUES (
							'$nome'		,
							'$cnpj'		,
							'$ie'		,
							'$endereco'	,
							'$numero'	,
							'$complemento',
							'$bairro'	,
							'$cep'		,
							$cod_cidade,
							'$contato'	,
							'$email'	,
							'$fone'		,
							'$fax'		,
							'$login_pais'
						)
	                    RETURNING revenda";
			}
		}

		if(!count($msg_erro)){
			$res = pg_query($con, $sql);

			if (pg_last_error($con)) {
				$msg_erro[] = pg_last_error($con);
			}else{
				$_SESSION['msg_sucesso'] = "Gravado com Sucesso!";
				header ("Location: $PHP_SELF");
				exit;
			}
		}else{
			$msg_erro[] = "Erro ao gravar dados!";
		}

	}
}

#-------------------- Pesquisa Revenda -----------------
if (strlen($revenda) > 0 and !count($msg_erro)) {
	if ($usa_rev_fabrica) {
	    $sql = "
	       SELECT   cnpj         	,		ie           ,
                    contato_razao_social AS nome         ,
                    contato_endereco     AS endereco     ,
                    contato_bairro       AS bairro       ,
                    contato_complemento  AS complemento  ,
                    contato_numero       AS numero       ,
                    contato_cep          AS cep          ,
                    contato_fone         AS fone         ,
                    contato_fax          AS fax          ,
                    contato_nome         AS contato      ,
                    contato_email        AS email        ,
                    tbl_cidade.nome      AS cidade       ,
                    tbl_cidade.estado    AS estado
            FROM    tbl_revenda_fabrica
            JOIN    tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			WHERE	tbl_revenda_fabrica.cnpj = '$revenda'
                AND tbl_revenda_fabrica.fabrica = $login_fabrica";
	} else {
		$sql = "SELECT	tbl_revenda.revenda      ,
						tbl_revenda.cnpj         ,
						tbl_revenda.ie           ,
						tbl_revenda.nome         ,
						tbl_revenda.endereco     ,
						tbl_revenda.bairro       ,
						tbl_revenda.complemento  ,
						tbl_revenda.numero       ,
						tbl_revenda.cep          ,
						tbl_revenda.fone         ,
						tbl_revenda.fax          ,
						tbl_revenda.contato      ,
						tbl_revenda.email        ,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado        ,
	                    tbl_revenda.cidade AS codigo_cidade
				FROM	tbl_revenda
				JOIN	tbl_cidade USING(cidade)
				WHERE	tbl_revenda.revenda = $revenda ";
	}
// if ($_COOKIE['debug'] == 'sim') pre_echo($_sql, 'Dados revenda');
	$res = @pg_query($con, $sql);
	if (is_resource($res)) {
        if (pg_num_rows($res) == 1) {
            $info_revenda = pg_fetch_assoc($res, 0);
            extract(array_map(trim, $info_revenda));    // Cria uma vari·vel com cada key e o valor do registro
        } else {
            $msg_erro[] = (pg_num_rows($res)==0) ? 'Revenda n„o encontrada!' : 'Existe mais de uma revenda cadastrada com o mesmo CNPJ!';
        }
    } else {
		pre_echo($sql, pg_last_error($con));
        $msg_erro[] = 'Erro ao ler as informaÁıes da Revenda!';
    }
}

$visual_black = "manutencao-admin";

$title     = traduz("cadastro.de.revendas",$con,$cook_idioma);

$layout_menu = "cadastro";

include 'cabecalho.php';	
?>

<?php
	include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>







<script type="text/javascript">
    $().ready(function(){
        $("input[name='cep']").blur(function() {
            $("input[name='numero']").focus();
        });
	<?php
	if ($usa_rev_fabrica) {
		if (is_string($msg_erro) && strlen($msg_erro) || is_array($msg_erro) && count($msg_erro)) {
			$revenda_fabrica = $_POST['revenda_fabrica'];

			if (strlen($revenda_fabrica) > 0) {
				$sql = "SELECT contato_razao_social FROM tbl_revenda_fabrica WHERE revenda_fabrica=$revenda_fabrica";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					echo "$('#nome').attr('readonly', 'readonly');";
					echo "$('#nome').css('color', '#ACACAC');";
				}
			}
		}
		else {
			echo "buscaRevenda();";
		}

	}
	?>
	$('.num').keypress(function(e) {
		if (e.altKey || e.ctrlKey) return true;
		var k = e.which;
		var c = String.fromCharCode(k);
		k = e.keyCode;
		var allowed = '1234567890';
		if (allowed.indexOf(c) >= 0) return true;
		ignore=(k < 16 || (k > 16 && k < 32) || (k > 32 && k < 41));
		if (ignore || allowed.indexOf(c) < 0 ) return false;
	}).keyup(function(e) {
		k = e.keyCode;
		if (k == 86 && e.ctrlKey) $(this).val($(this).val().replace(/\D/g, ''));
	});

	$('#displayArea').html('&nbsp;');

	$('#cep').keyup(function(){
		$('endereco').attr('readonly', '');
		$('#bairro').attr('readonly', '');
		$('#cidade').attr('readonly', '');
		$('#estado').attr('readonly', '');
	});

	$("#cnpj").mask("99.999.999/9999-99"); $("#cep").mask("99.999-999");

	<?php
	if ($pais != "BR") {
	?>
		$('#cidade').autocomplete(location.pathname, {
			minChars: 3,
			delay: 250,
			width: 350,
			max: 20,
			matchContains: true,
			extraParams: {
				ajax: 'rv_cidade',
				estado: function() {return $('#estado').val();}
			},
			formatItem: function(row) {return row[0] + " - " + row[1];},
			formatResult: function(row) {return row[0];}
				}).result(function(event, data, formatted) {
			$("#estado").val(data[1]);
		});
	<?php
	} else {
	?>
		// var extraParamEstado = {
		// estado: function () {
		// 		return $("#estado").val()
		// 	}
		// };

		// $("#cidade").autocomplete("admin/autocomplete_cidade_new.php", {
		// 	minChars: 3,
		// 	delay: 150,
		// 	width: 350,
		// 	matchContains: true,
		// 	extraParams: extraParamEstado,
		// 	formatItem: function (row) { return row[0]; },
		// 	formatResult: function (row) { return row[0]; }
		// });

		// $("#cidade").result(function(event, data, formatted) {
		// 	$("#cidade").val(data[0]);
		// });
	<?php
	}
	?>


/*		$('#cidade').autocomplete(location.pathname, {
			minChars: 3,
			delay: 250,
			width: 350,
			extraParams: {
				ajax: 'rv_cidade',
				estado: function() { return $('#estado').val();}
			},
			matchContains: true,
			formatItem: function(row) {return row[0] + " - " + row[1];},
			formatResult: function(row) {return row[0];}
				}).result(function(event, data, formatted) {
			$("#estado").val(data[1]);
		});*/
	});
<? if ($usa_rev_fabrica){ ?>
function buscaRevenda(){

		 $('#btn_gravar').css('display', 'inline');
		 $('input[name=revenda_fabrica]').val('');

		cnpj_pesquisa = $('input[name=cnpj]').val();
		if (cnpj_pesquisa.length == 0){
			 //$('input[name=nome]').css('color','#ACACAC');
			 $('input[name=nome]').val('');
			 //$('input[name=nome]').attr("readonly",true);
			 //$('input[name=ie]').attr("readonly",true);
			 //HD 350218
			 /*
			 $('input[name=endereco]').attr("readonly",true);
			 $('input[name=numero]').attr("readonly",true);
			 $('input[name=complemento]').attr("readonly",true);
			 $('input[name=bairro]').attr("readonly",true);
			 $('input[name=cep]').attr("readonly",true);
			 $('input[name=estado]').attr("readonly",true);
			 $('input[name=cidade]').attr("readonly",true);
			 $('input[name=fone]').attr("readonly",true);
			 $('input[name=contato]').attr("readonly",true);
			 $('input[name=email]').attr("readonly",true);
			 $('input[name=fax]').attr("readonly",true);
			 */
			 $('#btn_gravar').css('display', 'none');
			 return true;
		}
		
		cnpj_pesquisa = cnpj_pesquisa.replace(".", "");
		cnpj_pesquisa = cnpj_pesquisa.replace(".", "");
		cnpj_pesquisa = cnpj_pesquisa.replace("/", "");
		cnpj_pesquisa = cnpj_pesquisa.replace("-", "");

		if (cnpj_pesquisa.length != 14) {
			alert('Informe o CNPJ com 14 digitos');
			return false;
		}
		nome_rev = $('input[name=nome]');
		//nome_rev.val('Aguarde enquanto È localizada a raz„o social');
		$.get(location.pathname,
			  'ajax=rv_cnpj_nome&cnpj=' + cnpj_pesquisa,
			  function(data) {
				if(data != 'Sem resultados'){
					resposta = data.split("|");

					//Carrega os Campos
					$('input[name=nome]').val(resposta[0]);

					if(resposta.length > 2){
					 $('input[name=endereco]').val(resposta[1]);
					 $('input[name=numero]').val(resposta[2]);
					 $('input[name=complemento]').val(resposta[3]);
					 $('input[name=bairro]').val(resposta[4]);
					 $('input[name=cep]').val(resposta[5]);
					 $('input[name=estado]').val(resposta[6]);
					 $('input[name=cidade]').val(resposta[7]);
					 $('input[name=fone]').val(resposta[8]);
					 $('input[name=ie]').val(resposta[9]);
					 $('input[name=contato]').val(resposta[10]);
					 $('input[name=email]').val(resposta[11]);
					 $('input[name=fax]').val(resposta[12]);
					 $('input[name=revenda_fabrica]').val(resposta[13]);

					//Bloqueia os Campos
					$('input[name=nome]').attr("readonly",true);
					$('input[name=ie]').attr("readonly",true);
					//HD 350218
					/*
					$('input[name=endereco]').attr("readonly",true);
					$('input[name=numero]').attr("readonly",true);
					$('input[name=complemento]').attr("readonly",true);
					$('input[name=bairro]').attr("readonly",true);
					$('input[name=cep]').attr("readonly",true);
					$('input[name=estado]').attr("readonly",true);
					$('input[name=cidade]').attr("readonly",true);
					$('input[name=fone]').attr("readonly",true);
					$('input[name=contato]').attr("readonly",true);
					$('input[name=email]').attr("readonly",true);
					$('input[name=fax]').attr("readonly",true);
					*/

					//Muda cor da fonte dos campos
					 $('input[name=nome]').css('color','#ACACAC');
					 $('input[name=ie]').css('color','#ACACAC');
					//HD 350218
					/*
					$('input[name=endereco]').css('color','#ACACAC');
					$('input[name=numero]').css('color','#ACACAC');
					$('input[name=complemento]').css('color','#ACACAC');
					$('input[name=bairro]').css('color','#ACACAC');
					$('input[name=cep]').css('color','#ACACAC');
					$('input[name=estado]').css('color','#ACACAC');
					$('input[name=cidade]').css('color','#ACACAC');
					$('input[name=fone]').css('color','#ACACAC');
					$('input[name=contato]').css('color','#ACACAC');
					$('input[name=email]').css('color','#ACACAC');
					$('input[name=fax]').css('color','#ACACAC');
					*/

					 document.getElementById("mensagem").style.display = "block";
					 document.getElementById("mensagem2").style.display = "none";

					 //#HD 350218 - Mudado para Display inline
					 $('#btn_gravar').css('display', 'inline');
					}
					else{
						 $('input[name=endereco]').css('color','#000');
						 $('input[name=numero]').css('color','#000');
						 $('input[name=complemento]').css('color','#000');
						 $('input[name=bairro]').css('color','#000');
						 $('input[name=cep]').css('color','#000');
						 $('input[name=estado]').css('color','#000');
						 $('input[name=cidade]').css('color','#000');
						 $('input[name=fone]').css('color','#000');
						 $('input[name=ie]').css('color','#000');
						 $('input[name=contato]').css('color','#000');
						 $('input[name=email]').css('color','#000');
						 $('input[name=fax]').css('color','#000');

						 $('input[name=endereco]').val('');
						 $('input[name=numero]').val('');
						 $('input[name=complemento]').val('');
						 $('input[name=bairro]').val('');
						 $('input[name=cep]').val('');
						 $('input[name=estado]').val('');
						 $('input[name=cidade]').val('');
						 $('input[name=fone]').val('');
						 $('input[name=ie]').val('');
						 $('input[name=contato]').val('');
						 $('input[name=email]').val('');
						 $('input[name=fax]').val('');
						 $('input[name=revenda_fabrica]').val(resposta[1]);

						 $('input[name=nome]').attr("readonly",false);
						 $('input[name=endereco]').attr("readonly",false);
						 $('input[name=numero]').attr("readonly",false);
						 $('input[name=complemento]').attr("readonly",false);
						 $('input[name=bairro]').attr("readonly",false);
						 $('input[name=cep]').attr("readonly",false);
						 $('input[name=estado]').attr("readonly",false);
						 $('input[name=cidade]').attr("readonly",false);
						 $('input[name=fone]').attr("readonly",false);
						 $('input[name=ie]').attr("readonly",false);
						 $('input[name=contato]').attr("readonly",false);
						 $('input[name=email]').attr("readonly",false);
						 $('input[name=fax]').attr("readonly",false);

						 document.getElementById("mensagem2").style.display = "block";
						 document.getElementById("mensagem").style.display = "none";

						}

			}
				else{

					$('input[name=nome]').val('').focus();
					$('input[name=nome]').css('color','black');
					$('input[name=nome]').css('color','#000');
					$('input[name=endereco]').css('color','#000');
					$('input[name=numero]').css('color','#000');
					$('input[name=complemento]').css('color','#000');
					$('input[name=bairro]').css('color','#000');
					$('input[name=cep]').css('color','#000');
					$('input[name=estado]').css('color','#000');
					$('input[name=cidade]').css('color','#000');
					$('input[name=fone]').css('color','#000');
					$('input[name=ie]').css('color','#000');
					$('input[name=contato]').css('color','#000');
					$('input[name=email]').css('color','#000');
					$('input[name=fax]').css('color','#000');

					$('input[name=endereco]').val('');
					$('input[name=numero]').val('');
					$('input[name=complemento]').val('');
					$('input[name=bairro]').val('');
					$('input[name=cep]').val('');
					$('input[name=estado]').val('');
					$('input[name=cidade]').val('');
					$('input[name=fone]').val('');
					$('input[name=ie]').val('');
					$('input[name=contato]').val('');
					$('input[name=email]').val('');
					$('input[name=fax]').val('');

					$('input[name=nome]').attr("readonly",false);
					$('input[name=endereco]').attr("readonly",false);
					$('input[name=numero]').attr("readonly",false);
					$('input[name=complemento]').attr("readonly",false);
					$('input[name=bairro]').attr("readonly",false);
					$('input[name=cep]').attr("readonly",false);
					$('input[name=estado]').attr("readonly",false);
					$('input[name=cidade]').attr("readonly",false);
					$('input[name=fone]').attr("readonly",false);
					$('input[name=ie]').attr("readonly",false);
					$('input[name=contato]').attr("readonly",false);
					$('input[name=email]').attr("readonly",false);
					$('input[name=fax]').attr("readonly",false);

				}

		});
	}
<? } ?>
function formataCNPJ(campo) {
	var cnpj = campo.value.length;
	if (cnpj ==  2 || cnpj == 6) campo.value += '.';
	if (cnpj == 10) campo.value += '/';
	if (cnpj == 15) campo.value += '-';
}

function fnc_pesquisa_revenda(campo, tipo) {
	var url = "";
	var campox = campo.value;
    if (campox == undefined) return false;
	if((tipo=='nome' && campox.length < 4) || (tipo=='cnpj' && campox.length <8)) {
		if (tipo=='nome') alert("Digite ao menos 4 letras para pesquisar por nome.");
		if (tipo=='cnpj') alert("Digite ao menos os 8 primeiros dÌgitos do CNPJ.");
		return false;
	}
	url = "pesquisa_revenda<?=$pr_suffix?>.php?forma=reload&"+tipo+"=" + campox + "&tipo="+tipo;

	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=560,height=400,top=18,left=0");
	janela.retorno		= "<? echo $PHP_SELF ?>";
	janela.focus();
}
//HD 8236 Paulo, deixar cnpj sÛ digitar o numero

/*function char(cnpj){
	try{var element = cnpj.which	}catch(er){};
	try{var element = event.keyCode	}catch(er){};
	if (String.fromCharCode(element).search(/[0-9]|[.]|[/]|[-]/gi) == -1)
	return false
}*/

function bloqueiaNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58)) return false;
    else {
        if (tecla != 8) return true;
        else return false;
    }
}

function ValidaEmail() {
  var reg_email = /^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i;
  var end_email = $('#email').val();
  if (end_email == '') return true;
  if (reg_email.test(end_email)) return true;
    alert('Email incorreto');
	$('#email').focus();
}

<?php
if ($pais == "BR") {
?>
	$(function () {
		$("#estado").change(function () {
			if ($(this).val().length > 0) {
				$("#cidade").removeAttr("readonly");
			} else {
				$("#cidade").attr({"readonly": "readonly"});
			}
		});
	});
<?php
}
?>
</script>

<style type="text/css">
/*  Autocomplete    */
@import url(/assist/js/jquery.autocomplete.css);
/*  Form e table    */
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef;
    text-transform: uppercase;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
    text-transform: uppercase;
}

img, input[type=image] {border: 0 solid transparent}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}
.line_lst td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    width: 700px;
    padding: 2px;
}
</style>
<?php
	if (strlen ($msg_erro) > 0 OR count($msg_erro)) {
		if (count($msg_erro)){
			$msg_erro = implode('<br>', $msg_erro);
		}?>
			<div class='alerts'>
				<div class='alert danger margin-top'><?=$msg_erro;?></div>
			</div>
		<?php
		//echo "<div class='error' style='padding: 2px 0; width: 700px;'>{$msg_erro}</div>";
	}

	if(@$_SESSION['msg_sucesso']){
		echo "<div class='sucesso'>{$_SESSION['msg_sucesso']}</div>";
		unset($_SESSION['msg_sucesso']);
	}
?>
<p>
<? if($login_fabrica == 3){ ?>
<table width='600' align='center' border='0' bgcolor='#efd9e2'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#9b596d' size='-1'>
		<b><?= traduz('ATEN«√O: MUDAN«A DE PROCEDIMENTO') ?></b><br>
		<?= traduz('Para incluir uma nova revenda, preencha seu CNPJ e clique <b>na lupa</b>') ?>
		</font>
	</td>
</tr>
</table>

	<center>
	<div style='display:none; font:14px Arial; background-color:#7092BE; width:700px;' id='mensagem2'>
		<b><?= traduz('CNPJ n„o cadastrado, insira os dados da Nova Revenda</b>') ?>
	</div>
	</center>
<? }
else {?>
<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		<? fecho ("para.incluir.uma.nova.revenda.preencha.somente.seu.cnpj.e.clique.em.gravar",$con,$cook_idioma);?>
		<br>
		<? fecho ("faremos.uma.pesquisa.para.verificar.se.a.revenda.ja.esta.cadastrada.em.nosso.banco.de.dados",$con,$cook_idioma);?>
		</font>
	</td>
</tr>
</table>
<? } ?>

<form name="frm_revenda" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="revenda" value="<? echo $revenda ?>">
<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td><b><?echo $erro;?></b></td>
	</tr>
</table>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="4"class="menu_top">
			<font color='#36425C'><? echo "INFORMA«’ES CADASTRAIS";?>
		</td>
	</tr>
	<tr class="menu_top">
		<td><? echo traduz("cnpj.revenda",$con,$cook_idioma); if ($cook_idioma=='ES') echo " 1";?></td>
		<td><? if ($cook_idioma=='ES') echo "ID DISTRIBUIDOR 2"; else echo traduz("ie",$con,$cook_idioma);?></td>
		<td><? echo traduz("fone",$con,$cook_idioma);?></td>
		<td><? echo traduz("fax",$con,$cook_idioma);?></td>
	</tr>
	<tr class="table_line">
		<td>
            <input  type="text" id="cnpj" name="cnpj" size="20" maxlength='18'
                   value="<? echo $cnpj ?>" onblur="buscaRevenda();"
                 onfocus="displayText('&nbsp;Insira o n˙mero no Cadastro Nacional de Pessoa JurÌdica.');" tabindex='-1'>
		 <input type='hidden' name='revenda_fabrica' id='revenda_fabrica'>
            &nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='center'
                   onclick="javascript: fnc_pesquisa_revenda (document.frm_revenda.cnpj,'cnpj')">
        </td>
		<!--//hd 40364 - incluÌdo lupa para o cnpj -->

		<td><input type="text" name="ie" id='ie' size="18" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" id='fone' class="telefone" size="15" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" id='fax' class="telefone" size="15" maxlength="20" value="<? echo $fax ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="4"><? echo traduz("RAZ√O",$con,$cook_idioma);?></td>
	</tr>
	<tr class="table_line">
		<td colspan="3">
            <input type="text" id='nome' name="nome" size="70" maxlength="150" value="<? echo $nome ?>" style="width:400px">&nbsp;

       </td>
	</tr>
</table>

<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
    <tr class="menu_top">
        <td><? echo traduz("cep",$con,$cook_idioma);?></td>
        <td><? echo traduz("estado",$con,$cook_idioma);?></td>
        <td><? echo traduz("cidade",$con,$cook_idioma);?></td>
        <td colspan="2"><? echo traduz("bairro",$con,$cook_idioma);?></td>
    </tr>
    <tr class="table_line">
        <td><input type="text" name="cep" class="addressZip" id='cep' size="10" maxlength="10" value="<? echo $cep ?>"></td>
        <?php
        if ($pais == "BR") {
        ?>
            <td>
                <select id="estado" name="estado" size="1" class="addressState" style="width:100px">
                    <option value="" >Selecione</option>
                    <?php
                    #O $array_estados est· no arquivo funcoes.php
                    foreach ($array_estados() as $sigla => $nome_estado) {
                        $selected = ($sigla == $estado) ? "selected" : "";

                        echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                    }
                    ?>
                </select>
            </td>
        <?php
        } else {
        ?>
            <td>
                <input type="text" name="estado" id='estado' size="2"  maxlength="2"  value="<? echo $estado ?>" onkeypress='return bloqueiaNumero(event)'>
            </td>
        <?php
        }
        ?>
        <td>
            <input type="hidden" name="codigo_cidade" value='<?=$codigo_cidade?>' />
            <?php
            if ($pais == "BR") {?>
                <select id="cidade" name="cidade" class="addressCity" style="width:100px">
                    <option value="" >Selecione</option>
                    <?php
                        if (strlen($estado) > 0) {
                            $sql = "SELECT DISTINCT * FROM (
                                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
                                        UNION (
                                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
                                        )
                                    ) AS cidade
                                    ORDER BY cidade ASC";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                                while ($result = pg_fetch_object($res)) {
                                    $selected  = (trim($result->cidade) == $cidade) ? "SELECTED" : "";

                                    echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                }
                            }
                        }
                    ?>
                </select>
            <?php
            }else{?>
                <input type="text" id="cidade" name="cidade"  maxlength="30" <?=($pais == "BR" && !strlen($estado)) ? "readonly" :""?> value="<? echo $cidade; ?>" style='position:relative'>
            <?php
            }
            ?>
        </td>
        <td colspan="2"><input type="text" class="addressDistrict" name="bairro" id='bairro' size="35" maxlength="20" value="<? echo $bairro ?>"></td>
    </tr>
	<tr class="menu_top">
		<td colspan="2"><? echo traduz("ENDERE«O",$con,$cook_idioma);?></td>
		<td><? echo traduz("N⁄MERO",$con,$cook_idioma);?></td>
		<td colspan="2"><? echo traduz("complemento",$con,$cook_idioma);?></td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="endereco" class="address" id='endereco' size="42" maxlength="50" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" id='numero' size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" id='complemento' size="35" maxlength="30" value="<? echo $complemento ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td><? echo traduz("contato",$con,$cook_idioma);?></td>
		<td><? echo traduz("email",$con,$cook_idioma);?></td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="contato" id='contato' size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
		<td align="center">
			<input type="text" name="email" id="email" size="40" maxlength="50" value="<? echo $email ?>" onblur="ValidaEmail();">
		</td>
 	</tr>
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<input id='btn_gravar' name='btn_gravar' type='image' src="<?if($sistema_lingua =='ES')echo "admin_es/"?>imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='gravar' ; document.frm_revenda.submit() } else { alert ('Aguarde') }" ALT="<?fecho ("gravar.formulario",$con,$cook_idioma);?>" border='0'>
<!--
<input type='hidden' name='btn_descredenciar' value=''>
<img src='imagens_admin/btn_apagar.gif' style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_descredenciar.value == '' ) { if(confirm('Deseja realmente EXCLUIR esta REVENDA?') == true) { document.frm_revenda.btn_descredenciar.value='descredenciar'; document.frm_revenda.submit(); }else{ return; }; } else { alert ('Aguarde submiss„o') }" ALT="Apagar a Ordem de ServiÁo" border='0'>
 -->
<input id='btn_limpar' name='btn_limpar' type='image' src="<?if($sistema_lingua =='ES')echo "admin_es/";?>imagens/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='reset' ; document.frm_revenda.reset(); window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde') }" ALT="<?fecho ("limpar.campos",$con,$cook_idioma);?>" border='0'>

</center>
</form>

<p>

<?
if ($_GET ['listar'] == 'todos') {
    $cond_pais = ($login_pais != '') ? "              AND   tbl_revenda.pais            = '$login_pais'" : '';
	$sql = "SELECT	tbl_revenda.revenda,
					tbl_revenda.nome           ,
					tbl_revenda.cnpj           ,
					tbl_cidade.nome AS cidade  ,
					tbl_cidade.estado
			FROM    tbl_revenda
			JOIN    tbl_cidade USING(cidade)
			JOIN    tbl_estado using(estado)
			WHERE   ativo IS TRUE
            $cond_pais
			ORDER BY estado, tbl_revenda.nome, cnpj ASC";

    if ($usa_rev_fabrica) $sql = "
	       SELECT  tbl_revenda.revenda  AS revenda       ,
                    tbl_revenda.cnpj     AS cnpj         ,
                    contato_razao_social AS nome         ,
                    tbl_cidade.nome      AS cidade       ,
                    tbl_cidade.estado    AS estado
            FROM    tbl_revenda
            JOIN    tbl_revenda_fabrica USING(revenda)
            JOIN    tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			WHERE	tbl_revenda_fabrica.fabrica = $login_fabrica
            $cond_pais
			ORDER BY estado, tbl_revenda.nome, cnpj";

	$res = pg_query($con,$sql);
    if (is_resource($res)) {
        $tot_revendas = pg_num_rows($res);
	   echo "<table width='650' align='center' border='0' style='table-layout:fixed'>";
    }
	for ($i = 0; $i < $tot_revendas; $i++) {
		if ($i % 20 == 0) {
// 			if ($i > 0) echo "</table>";
			flush();

// 			echo "<table width='650' align='center' border='0'>";
			echo "<tr class='top_lst'>";

			echo "<td align='center' style='width: 160px;'>";
			echo traduz("cidade",$con,$cook_idioma);
			echo "</td>";

			echo "<td align='center'>";
			echo traduz("estado",$con,$cook_idioma);
			echo "</td>";

			echo "<td align='center' width='300' style='width:300px'>";
			echo traduz("revenda",$con,$cook_idioma);
			echo "</td>";

			echo "<td align='center' style='width:12em'>";
			echo traduz("cnpj",$con,$cook_idioma);
			echo "</td>";

			echo "</tr>";
		}
        $row = pg_fetch_assoc($res, $i);
        extract($row);
		if($sistema_lingua<>'ES') $cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        $tooltip = (strlen($nome) > 42) ? " title='$nome'" : '';
//          substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
?>
      <tr class="line_lst">
        	<td><?=$cidade?></td>
        	<td align='center'><?=$estado?></td>
        	<td<?=$tooltip?>>
                <a href='<?=$PHP_SELF."?revenda=".$revenda ?>'><?=$nome?></a>
            </td>
        	<td align='right'><?=$cnpj?></td>
        </tr>
<? 	}
	echo "</table>";
}
?>
<p>
<script language='javascript' src='admin/address_components.js'></script>
<? include "rodape.php"; ?>
