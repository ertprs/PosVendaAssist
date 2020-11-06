<?php
session_start();

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include '../helpdesk/mlg_funciones.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';

include 'funcoes.php';
unset($email);
$msg_erro = array();

/*  Validação...    */
if (!function_exists('validaCNPJ')) {
	function validaCNPJ($TaxID, $return_str = true) {
		global $con;    // Para conectar com o banco...
		$cnpj = preg_replace("/\D/","",$TaxID);   // Limpa o cnpj / CNPJ
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

function soNums($var) {
	return preg_replace('/\D/', '', $var);
}

//  HD 234135 - MLG - Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica   = in_array($login_fabrica, array(3,15,24,117,138,161,184,191,200));
$usa_atacadista    = in_array($login_fabrica,array(50));

$revenda = $_REQUEST['revenda'];
#-------------------- Descredenciar -----------------

if($_GET['monta_cidade'] == "sim"){ //hd_chamado=2909049

    $estado = $_GET['estado'];
    $id_revenda = $_GET['id_revenda'];
    $sql = "SELECT tbl_cidade.nome,
                    tbl_cidade.cidade
                    FROM tbl_cidade
                    JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
                    WHERE tbl_revenda.revenda = $id_revenda";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $cidade_nome = pg_fetch_result($res, 0, 'nome');
        $option = "<option value='$cidade_nome'>$cidade_nome</option>";
    }
    echo "$option";
    exit;
}

if ($_POST["btn_acao"] == "descredenciar" AND !empty($_POST['revenda'])) {

	if(!empty($revenda)){
		$sql = "DELETE FROM tbl_revenda WHERE revenda = $revenda;";
	}

    if ($usa_rev_fabrica) {
    	$cnpj = $_POST['cnpj'];
    	$sql = "DELETE FROM tbl_revenda_fabrica WHERE cnpj = '$cnpj' AND fabrica = $login_fabrica";
    }
	$res = pg_query($con,$sql);
	if (is_resource($res)) {
		if (pg_affected_rows($res) == 1) {
			header ("Location: $PHP_SELF?excluido=true");
			exit;
		} else {
			$msg_erro['msg'][] = tradiz('Revenda usada em algum cadastro, não é possível excluí-la.');
		}
	} else {
		$msg_erro['msg'][] = pg_last_error($con).'Não foi possível excluir a revenda. Por favor, tente novamente.';
		if(strpos(pg_last_error(),'violates')) {
			unset($msg_erro);
			$msg_erro['msg'][] = traduz('Revenda já utilizada no cadastro de OS, não pode ser excluída');
		}
	}
}

#-------------------- GRAVAR -----------------
if ($_POST["btn_acao"] == "gravar") {
	$cnpj		= substr(soNums($_REQUEST['cnpj']), 0, 14);
	$cidade		= $_REQUEST['cidade'];
	$estado		= $_REQUEST['estado'];
	$observacao	= $_REQUEST['observacao'];
	$nome		= substr($_REQUEST['razao_social'],0,150);

    if ($login_fabrica == 11 or $login_fabrica == 172) {
        $bloqueio = $_REQUEST["bloqueio"][0];
    }

	if (empty($cnpj)) {
		$msg_erro["msg"]["obg"]= traduz('Preencha os campos obrigatórios');
	        $msg_erro["campos"][]= 'cnpj';
	        $msg_erro["campos"][]= 'razao_social';
	} else{
		if ($usa_rev_fabrica AND count($msg_erro["msg"]) == 0 and empty($_REQUEST['revenda'])) {
			$sql_u = "SELECT revenda_fabrica, cidade FROM tbl_revenda_fabrica WHERE cnpj = '$cnpj' AND fabrica = $login_fabrica LIMIT 1";
			$res_u = pg_query($con, $sql_u);
			if(pg_num_rows($res_u) > 0) {
				$aux_revenda_fabrica = pg_fetch_result($res_u, 0, 'revenda_fabrica');
				$aux_cidade = pg_fetch_result($res_u, 0, 'cidade');
				if (strlen($aux_cidade) > 0) {
					$msg_erro["msg"][]= "$aux_revenda_fabrica CNPJ JÁ CADASTRADA, CLIQUE A LUPA PARA PESQUISAR";
				}
			}
		}
	}



    if(count($msg_erro["msg"]) == 0) {
	if (empty($cidade)) {
		$msg_erro["msg"]["obg"]= traduz('Preencha os campos obrigatórios');
        $msg_erro["campos"][]= 'cidade';
	}

	if (empty($estado)) {
		$msg_erro["msg"]["obg"]= traduz('Preencha os campos obrigatórios');
        $msg_erro["campos"][]= 'estado';
	}
        $pais="BR";
        if($login_fabrica ==20){
        	$sql = "SELECT pais FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
        	$res = pg_query($con,$sql);
        	if(pg_num_rows($res) >0) $pais = pg_fetch_result($res, 0, 'pais');
        }
        if($pais == "BR"){
			$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cnpj));
			if(empty($valida_cpf_cnpj)){
				$valida_cnpj = validaCNPJ($cnpj, false);

				if ($valida_cnpj) {
					$xcnpj = pg_quote(preg_replace('/\D/', '', $cnpj));
				} else {
					$msg_erro["msg"][]    = traduz("CNPJ da revenda inválida");
					$msg_erro["campos"][] = "cnpj";
				}
			}else{
				$msg_erro["msg"] = $valida_cpf_cnpj;
				$msg_erro["campos"][] = "cnpj";
			}
        }

        if ($login_fabrica == 117) {
	        $aux_sql = "SELECT DISTINCT(descricao) FROM tbl_macro_linha WHERE ativo IS TRUE GROUP BY descricao ORDER BY descricao";
			$aux_res = pg_query($con, $aux_sql);
			$aux_row = pg_num_rows($aux_res);

			if ($aux_row > 0) {
				$linhas = array();

				for ($z = 0; $z < $aux_row; $z++) { 
					$modalidade_linha = $_POST["modalidade_$z"];
					if (strlen($modalidade_linha) == 0) {
						$msg_erro["msg"]["obg"] .= traduz('<br>Preencha todas as linhas com suas respectivas modalidades');
		        		$msg_erro["campos"][]= 'modalidade';

		        		break;
					} else {
						$linhas[] = $modalidade_linha;
					}
				}
			}
		}
    }

    if(count($msg_erro["msg"]) == 0) {
    	if ((strlen($nome) == 0 AND !$usa_rev_fabrica)or ($usa_rev_fabrica and $nome !='' and count(array_filter($_POST))<4)) {
    		// verifica se revenda já está cadastrada
    		$sql = "SELECT	tbl_revenda.*                     ,
    						tbl_cidade.nome   AS cidade_nome  ,
    						tbl_cidade.estado AS cidade_estado
    				FROM	tbl_revenda
    				JOIN	tbl_cidade USING (cidade)
    				WHERE	tbl_revenda.cnpj = '$xcnpj' ";

			if ($usa_rev_fabrica) $sql = "SELECT  revenda_fabrica AS revenda							,
							cnpj            , ie					,
							contato_razao_social	AS nome			,
							contato_fone            AS fone			,
							contato_fax             AS fax			,
							contato_nome            AS contato		,
							contato_endereco        AS endereco		,
							contato_numero          AS numero		,
							contato_complemento     AS complemento	,
							contato_bairro          AS bairro       ,
							contato_cep             AS cep          ,
							contato_email           AS email        ,
							tbl_cidade.nome			AS cidade_nome	,
							tbl_cidade.estado		AS cidade_estado
					  FROM  tbl_revenda_fabrica
					  LEFT JOIN  tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
					 WHERE  cnpj = '$xcnpj' AND fabrica = $fabrica";

    		$res = pg_query($con,$sql);
    		if (pg_num_rows($res) > 0) {

    			$msg_erro["msg"] = "Revenda já está cadastrada.";

    			$revenda     = pg_fetch_result($res, 0, 'revenda');
    			$ie          = pg_fetch_result($res, 0, 'ie');
    			$nome        = pg_fetch_result($res, 0, 'nome');
    			$fone        = pg_fetch_result($res, 0, 'fone');
    			$fax         = pg_fetch_result($res, 0, 'fax');
    			$contato     = pg_fetch_result($res, 0, 'contato');
    			$endereco    = pg_fetch_result($res, 0, 'endereco');
    			$numero      = pg_fetch_result($res, 0, 'numero');
    			$complemento = pg_fetch_result($res, 0, 'complemento');
    			$bairro      = pg_fetch_result($res, 0, 'bairro');
    			$cep         = pg_fetch_result($res, 0, 'cep');
    			$cidade      = pg_fetch_result($res, 0, 'cidade_nome');
    			$estado      = pg_fetch_result($res, 0, 'cidade_estado');
    			$email       = pg_fetch_result($res, 0, 'email');
    			if ($usa_atacadista) {
    				$atacadista = pg_fetch_result($res, 0, 'atacadista');
    			}

    		}else{
    			$msg_erro["msg"][] = traduz("Revenda não cadastrada, favor completar os dados de cadastro");
    		}
    	}

#----------------------------- Dados ---------------------
/*  O campo 'revenda' não pode ser NULL...
    	if (strlen($revenda) > 0)
    		$xrevenda = "'".$revenda."'";
    	else
    		$xrevenda = 'null';
*/
	}

	if (count($msg_erro["msg"]) == 0) {
//		o getPost(campo, true) pega o POST (ou o GET se não tiver POST), o pg_quote coloca as aspas se não for boolean ou NULL
		// todo cortar strings de acordo com o tamanho no bd
		// sql : SELECT column_name, data_type,character_maximum_length FROM information_schema.columns WHERE table_name = 'tbl_revenda_fabrica';

        $nome		= substr($_REQUEST['razao_social'],0,150);
        $nome_fantasia = substr($_REQUEST['nome_fantasia'],0,150);
	$nome_aux	= $_REQUEST['nome_aux'];
        $endereco	= substr($_REQUEST['endereco'],0,60);
        $numero		= substr($_REQUEST['numero'],0,20);
        $complemento= substr($_REQUEST['complemento'],0,30);
        $bairro		= substr($_REQUEST['bairro'],0,30);
        $cep		= substr(soNums($_REQUEST['cep']),0,8);
        $email		= substr($_REQUEST['email'],0,50);
        $fone		= substr($_REQUEST['fone'],0,20);
        $fax		= substr($_REQUEST['fax'],0,20);
        $contato	= substr($_REQUEST['contato'],0,30);

	$nome_fantasia = (!empty($nome_fantasia)) ? $nome_fantasia : null;

        if ($usa_atacadista) {
		$atacadista = $_REQUEST['atacadista'];
        }

    	if (!is_null($cep) and $pais=='BR' and strlen($cep) != 8) {
                $cep = null;
                $msg_erro["msg"][]= traduz('CEP inválido!');
                $msg_erro["campos"][]= 'cep';
        }

    	if (strlen($email) > 0 && !is_email($email)) {
			$email = null;
			$msg_erro["msg"][]= traduz('E-mail Inválido');
            $msg_erro["campos"][]= 'email';
		}

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
				$msg_erro["msg"][]= traduz('Cidade não encontrada');
           		$msg_erro["campos"][]= 'cidade';
			}
		}
    }

	if (count($msg_erro["msg"]) == 0) {


		if ($revenda == '') {
    		$sql_r = "SELECT revenda FROM tbl_revenda WHERE  cnpj = '$xcnpj'";
    		$res_r = pg_query($con,$sql_r);
			$nome = substr($nome,0,50);
    		if (pg_num_rows($res_r) > 0) {
    			$revenda = pg_fetch_result($res_r, 0, 0);
            }
        }

        if ($usa_atacadista) {
        	if (!empty($atacadista)) {
        		$field_atacadista = ',atacadista';
        		$value_atacadista = ",true";
        		$update_atacadista = ', atacadista = true';
        	}else{
        		$field_atacadista = ',atacadista';
        		$value_atacadista = ",false";
        		$update_atacadista = ', atacadista = false';
        	}
        }

		if ($usa_rev_fabrica AND count($msg_erro["msg"]) == 0) {
            pg_query($con, "BEGIN");

            $sql_u = "SELECT revenda_fabrica FROM tbl_revenda_fabrica WHERE cnpj = '$cnpj' AND fabrica = $login_fabrica";
            $res_u = pg_query($con, $sql_u);
            $update = (pg_num_rows($res_u) == 1) ? true : false;

	        if ($update) {
				if($login_fabrica == 15 or $login_fabrica == 24){
					$campo_nome_fantasia = ", contato_nome_fantasia = '$nome_fantasia'";
				}
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
							ie                  = '$ie'           ,
							cidade				= $cod_cidade
							$campo_nome_fantasia
	                    WHERE tbl_revenda_fabrica.cnpj = '$cnpj' AND fabrica = $login_fabrica";
	        } else {
	        	if(intval($revenda) == 0 AND count($msg_erro["msg"]) == 0){

					 $sql_r = "INSERT INTO tbl_revenda (
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
							$cod_cidade	,
							'$contato'	,
							'$email'	,
							'$fone'		,
							'$fax'		,
							'$login_pais'
						)
	                    RETURNING revenda;";
	                $res_r = pg_query($con, $sql_r);
                   	$revenda = pg_fetch_result($res_r, 0);

	        	}

				if($login_fabrica == 15 or $login_fabrica == 24){
					$campo_nome_fantasia = ", contato_nome_fantasia ";
					$conteudo_nome_fantasia = ", '$nome_fantasia' ";
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
									$campo_nome_fantasia
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
									$conteudo_nome_fantasia
							) RETURNING revenda_fabrica;";
	        }

		} else {
            pg_query($con, "BEGIN");

			if (strlen ($revenda) > 0 AND count($msg_erro["msg"]) == 0) {  // update. O CNPJ não deve mudar!
				$sql = "UPDATE tbl_revenda SET
							nome		= '$nome'       ,
							/*cnpj		= '$cnpj'       ,*/
							ie		    = '$ie'         ,
							endereco	= '$endereco'   ,
							numero		= '$numero'     ,
							complemento	= '$complemento',
							bairro		= '$bairro'     ,
							cep		    = '$cep'        ,
							cidade		= $cod_cidade ,
							contato		= '$contato'    ,
							email		= '$email'      ,
							fone		= '$fone'       ,
							fax		    = '$fax'        ,
							pais		= '$login_pais'
							$update_atacadista
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
							$field_atacadista
						) VALUES (
							'$nome'		,
							'$cnpj'		,
							'$ie'		,
							'$endereco'	,
							'$numero'	,
							'$complemento',
							'$bairro'	,
							'$cep'		,
							$cod_cidade	,
							'$contato'	,
							'$email'	,
							'$fone'		,
							'$fax'		,
							'$login_pais'
							$value_atacadista
						)
	                    RETURNING revenda";
			}
		}

		$res = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$msg_erro["msg"][] = traduz("Erro ao gravar revenda");
            pg_query($con, "ROLLBACK");
		} else {
            if (empty($revenda)) {
               $revenda = pg_fetch_result($res, 0, "revenda");
            }

            if ($login_fabrica == 11 or $login_fabrica == 172) {
                $sqlRevFab = "SELECT revenda_fabrica FROM tbl_revenda_fabrica WHERE fabrica = {$login_fabrica} AND revenda = {$revenda}";
                $resRevFab = pg_query($con, $sqlRevFab);

                if ($bloqueio == "t" && !pg_num_rows($resRevFab)) {
                    $sqlRevFab = "
                        INSERT INTO tbl_revenda_fabrica 
                            (fabrica, revenda, data_bloqueio, admin_bloqueio, contato_razao_social, cnpj, contato_nome, cidade)
                        VALUES 
                            ({$login_fabrica}, {$revenda}, current_timestamp, {$login_admin}, '{$nome}', '{$cnpj}', '{$contato}', {$cod_cidade})
                    ";
                    $resRevFab = pg_query($con, $sqlRevFab);
                } else if ($bloqueio != t && pg_num_rows($resRevFab) > 0) {
                    $sqlRevFab = "
                        DELETE FROM tbl_revenda_fabrica WHERE fabrica = {$login_fabrica} AND revenda = {$revenda}
                    ";
                    $resRevFab = pg_query($con, $sqlRevFab);
                }

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = traduz("Erro ao gravar revenda");
                    pg_query($con, "ROLLBACK");
                }
            }

            if($login_fabrica == 191){
            	$sql = "SELECT cliente_admin FROM tbl_cliente_admin WHERE fabrica = {$login_fabrica} AND cnpj = '{$cnpj}'";
            	$res = pg_query($con,$sql);

            	if(pg_num_rows($res) == 0){

            		$sql = "INSERT INTO tbl_cliente_admin (
		                        nome,
		                        cnpj,
		                        ie,
		                        endereco,
		                        numero,
		                        complemento,
		                        bairro,
		                        cep,
		                        cidade,
		                        estado,
		                        contato,
		                        email,
		                        fone,
		                        celular,
		                        codigo,
		                        codigo_representante,
		                        fabrica
		                    ) VALUES (
		                        '$nome',
		                        '$cnpj',
		                        '$ie',
		                        '$endereco',
		                        '$numero',
		                        '$complemento',
		                        '$bairro',
		                        '$cep',
		                        '$cidade',
		                        '$estado',
		                        '$contato',
		                        '$email',
		                        '$fone',
		                        '$celular',
		                        '$cnpj',
		                        '$cnpj',
		                        $login_fabrica
		            ) RETURNING cliente_admin";
		            $res = pg_query($con,$sql);

		            if (strlen(pg_last_error()) > 0) {
	                    $msg_erro["msg"][] = "Erro ao gravar revenda";
	                    pg_query($con, "ROLLBACK");
	                }else{

	                	$cliente_admin = pg_fetch_result($res, 0, 'cliente_admin');

	                	$senha = $_POST['senha'];

	                	if(empty($senha)){
	                		$caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
							$senha = substr(str_shuffle($caracteres),0,6);
	                	}

	                	$sql = "INSERT INTO tbl_admin(fabrica,login,senha,email,fone,nome_completo,cliente_admin,cliente_admin_master,privilegios) VALUES($login_fabrica,'$cnpj','$senha','$email','$fone','$nome',$cliente_admin,true,'*')";

	                }

            	}else{

            		$senha = $_POST['senha'];
            		$cliente_admin = pg_fetch_result($res, 0, 'cliente_admin');

                	if(empty($senha)){
                		$caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
						$senha = substr(str_shuffle($caracteres),0,6);
                	}

            		$sql = "SELECT admin FROM tbl_admin WHERE fabrica = $login_fabrica AND login = '{$cnpj}'";
            		$res = pg_query($con,$sql);

            		if(pg_num_rows($res) > 0){
            			$admin = pg_fetch_result($res, 0, 'admin');

            			$sql = "UPDATE tbl_admin SET senha = '{$senha}' WHERE admin = {$admin}";
            		}else{
            			$sql = "INSERT INTO tbl_admin(fabrica,login,senha,email,fone,nome_completo,cliente_admin,cliente_admin_master,privilegios) VALUES($login_fabrica,'$cnpj','$senha','$email','$fone','$nome',$cliente_admin,true,'*')";
            		}
            		$res = pg_query($con,$sql);

            		if (strlen(pg_last_error()) > 0) {
	                    $msg_rro["msg"][] = "Erro ao gravar login";
	                    pg_query($con, "ROLLBACK");
	                }

            	}

            }

            if (empty($msg_erro["msg"])) {
                pg_query($con, "COMMIT");
            }
        }
    }

	if ($usa_rev_fabrica and count($msg_erro["msg"]) == 0) {
		$cnpj_aux = substr(preg_replace('/\D/', '', $cnpj),0,8);
		if($nome_aux != $nome){
			$sql = "UPDATE tbl_revenda_fabrica
			        set contato_razao_social = '$nome'
					WHERE SUBSTR(cnpj,1,8) = '$cnpj_aux'
					AND tbl_revenda_fabrica.fabrica = $login_fabrica
			        ";
			$res = pg_query($con,$sql);
			if (pg_last_error($con))
				$msg_erro["msg"] = pg_last_error($con);
		}
	}

	if(count($msg_erro["msg"]) ==0 and strlen($revenda) >0 && !$usa_rev_fabrica){
		$sql = "SELECT revenda
				FROM  tbl_revenda_compra
				WHERE revenda = $revenda
				AND   fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if (pg_last_error($con))
				$msg_erro["msg"] = pg_last_error($con);

		if(pg_num_rows($res)==0){
			$sql = "INSERT INTO tbl_revenda_compra (
						revenda,
						fabrica
					) VALUES (
						$revenda,
						$login_fabrica
					)";
			$res = pg_query($con,$sql);

			if (pg_last_error($con))
				$msg_erro["msg"] = pg_last_error($con);
		}
	}

	/*HD - 4365905*/
	if($login_fabrica == 117 && strlen($msg_erro["msg"]) == 0 && count($msg_erro["msg"]) == 0) {
		pg_query($con, "BEGIN");

		if (count($linhas) > 0 && strlen($revenda) > 0) {
			$aux_sql = "SELECT modalidade_revenda FROM tbl_modalidade_revenda WHERE fabrica = $login_fabrica AND revenda = $revenda";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$aux_sql = "DELETE FROM tbl_modalidade_revenda WHERE fabrica = $login_fabrica AND revenda = $revenda";
				$aux_res = pg_query($con, $aux_sql);

				if (pg_last_error()) {
					$msg_erro["msg"] = traduz("Erro ao salvar ao atualizar a(s) modalidade(s) da revenda");
				}
			}

			if(strlen($msg_erro["msg"]) == 0 && count($msg_erro["msg"]) == 0) {
				foreach ($linhas as $modalidade) {
					$aux_sql = "INSERT INTO tbl_modalidade_revenda (revenda, modalidade, fabrica)
								VALUES ($revenda, $modalidade, $login_fabrica)";
					$aux_res = pg_query($con, $aux_sql);
					
					if (pg_last_error()) {
						$msg_erro["msg"] = traduz("Erro ao salvar a(s) modalidade(s) da revenda");
						break;
					}
				}
			}


			$aux_sql    = "UPDATE tbl_revenda_fabrica SET observacao = '{$observacao}' WHERE revenda = $revenda AND fabrica = $login_fabrica";
			$aux_res    = pg_query($con, $aux_sql);

			if (pg_last_error()) {
				$msg_erro["msg"] = traduz("Erro ao salvar a observação da revenda");
			}

			if (strlen($msg_erro["msg"]) == 0 && count($msg_erro["msg"]) == 0) {
				pg_query($con, "COMMIT");
			} else {
				pg_query($con, "ROLLBACK");
			}
		}
	}

	if(strlen($msg_erro["msg"]) == 0 AND count($msg_erro["msg"]) == 0){
		header ("Location: $PHP_SELF?sucesso=true");
		exit;
	}
}

#-------------------- Pesquisa Revenda -----------------
if ((strlen($revenda) > 0 and count($msg_erro["msg"]) == 0) OR $_GET['listar'] OR $_POST["gerar_excel"]) {

	if($_GET['listar'] OR $_POST["gerar_excel"]){
        if ($login_fabrica == 11 or $login_fabrica == 172) {
            $usa_rev_fabrica = true;
        }

		$cond2 = " WHERE tbl_revenda_fabrica.fabrica = $login_fabrica";
	}else{
		$cond = " WHERE tbl_revenda.revenda = $revenda ";
		$cond2 = " WHERE tbl_revenda_fabrica.revenda = '$revenda'
                	AND tbl_revenda_fabrica.fabrica = $login_fabrica";
	}

    if ($login_fabrica == 11 or $login_fabrica == 172) {
        $columnRevendaBloqueada = "
            , CASE WHEN tbl_revenda_fabrica.data_bloqueio IS NOT NULL THEN 't' ELSE NULL END AS bloqueio 
        ";
        $leftJoinRevendaFabrica = "
            LEFT JOIN tbl_revenda_fabrica ON tbl_revenda_fabrica.revenda = tbl_revenda.revenda AND tbl_revenda_fabrica.fabrica = {$login_fabrica}
        ";
    }

    if($login_fabrica == 191){
    	$leftJoinAdmin = " LEFT JOIN tbl_admin AB ON tbl_revenda_fabrica.cnpj = AB.login AND AB.fabrica = {$login_fabrica} ";
    	$campoSenha = ", AB.senha";

    }

	$sql = "SELECT	tbl_revenda.revenda      ,
					tbl_revenda.nome         ,
					tbl_revenda.endereco     ,
					tbl_revenda.bairro       ,
					tbl_revenda.complemento  ,
					tbl_revenda.numero       ,
					tbl_revenda.cep          ,
					tbl_revenda.cnpj         ,
					tbl_revenda.fone         ,
					tbl_revenda.fax          ,
					tbl_revenda.contato      ,
					tbl_revenda.fax          ,
					tbl_revenda.email        ,
					tbl_revenda.ie           ,
					tbl_cidade.nome AS cidade,
					null AS nome_fantasia,
					tbl_cidade.estado,
					tbl_revenda.atacadista
                    {$columnRevendaBloqueada}
            FROM tbl_revenda
            {$leftJoinRevendaFabrica}
			LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade

			 $cond";
    if ($usa_rev_fabrica) $sql_rf = "
	       SELECT	revenda,
	   				cnpj         		,	ie           ,
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
                    contato_cidade       AS codigo_cidade,
                    tbl_cidade.nome      AS cidade       ,
                    tbl_cidade.estado    AS estado       ,
					contato_nome_fantasia AS nome_fantasia,
                    TO_CHAR(data_bloqueio, 'DD/MM/YYYY HH24:MI') AS data_bloqueio,
                    tbl_admin.nome_completo AS admin_bloqueio
                    $campoSenha
            FROM    tbl_revenda_fabrica
            LEFT JOIN    tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_revenda_fabrica.admin_bloqueio AND tbl_admin.fabrica = {$login_fabrica}
            $leftJoinAdmin
			$cond2";

    $_sql = ($usa_rev_fabrica && $login_fabrica != 15) ? $sql_rf : $sql; /*HD-3992758*/

    if (in_array($login_fabrica, array(117))) {
    	$aux_res = pg_query($con, $_sql);
    	$aux_rev = pg_fetch_result($aux_res, 0, 'revenda');

    	if (strlen($aux_rev) == 0) {
    		$aux_sql = $sql;
    		$aux_res = pg_query($con, $aux_sql);
    		$aux_rev = pg_fetch_result($aux_res, 0, 'revenda');

    		if (strlen($aux_rev) > 0) {
    			$_sql = $sql;
    		}    		
    	}
    }
    
	$resSubmit = pg_query($con,$_sql);
	$count_rev = pg_num_rows($resSubmit);

	if ($count_rev > 0) {
		if($_POST['gerar_excel']){

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_revendas-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			fwrite($file, "<table border='1'>
								<thead>
									<tr bgcolor='#FAFF73'>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Razão Social</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Fantasia</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>IE</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>FAX</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Contato</th>
									</tr>
								</thead>
								<tbody>");
			for($i = 0; $i < $count_rev; $i++){
				$cnpj 					= pg_fetch_result($resSubmit,$i,'cnpj');
				$ie 					= pg_fetch_result($resSubmit,$i,'ie');
				$contato_razao_social 	= pg_fetch_result($resSubmit,$i,'nome');
				$contato_nome_fantasia  = pg_fetch_result($resSubmit,$i,'nome_fantasia');
				$estado 				= pg_fetch_result($resSubmit,$i,'estado');
				$cidade 				= pg_fetch_result($resSubmit,$i,'cidade');
				$contato_endereco 		= pg_fetch_result($resSubmit,$i,'endereco');
				$contato_numero 		= pg_fetch_result($resSubmit,$i,'cnumero');
				$contato_complemento 	= pg_fetch_result($resSubmit,$i,'complemento');
				$contato_bairro 		= pg_fetch_result($resSubmit,$i,'bairro');
				$contato_cep 			= pg_fetch_result($resSubmit,$i,'cep');
				$contato_fone 			= pg_fetch_result($resSubmit,$i,'fone');
				$contato_fax 			= pg_fetch_result($resSubmit,$i,'fax');
				$contato_email 			= pg_fetch_result($resSubmit,$i,'email');
				$contato_nome 			= pg_fetch_result($resSubmit,$i,'nome');

				fwrite($file, "<tr>
									<td>$cnpj &nbsp;</td>
									<td>$contato_razao_social</td>
									<td>$contato_nome_fantasia</td>
									<td>$ie</td>
									<td>$contato_endereco, $contato_numero</td>
									<td>$contato_complemento</td>
									<td>$contato_bairro</td>
									<td>$cidade</td>
									<td>$estado</td>
									<td>$contato_cep</td>
									<td>$contato_fone</td>
									<td>$contato_fax</td>
									<td>$contato_email</td>
									<td>$contato_nome</td>
								</tr>");
			}
			fwrite($file, "<tr>
								<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
							</tr>
						</tbody>
					</table>");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}

			exit;
		}else{
			if(!$_GET['listar']){
				$_RESULT["revenda"]          = trim(pg_fetch_result($resSubmit, 0, 'revenda'));
				$_RESULT["razao_social"]             = trim(pg_fetch_result($resSubmit, 0, 'nome'));
				$_RESULT["cnpj"]             = trim(pg_fetch_result($resSubmit, 0, 'cnpj'));
				$_RESULT["endereco"]         = trim(pg_fetch_result($resSubmit, 0, 'endereco'));
				$_RESULT["numero"]           = trim(pg_fetch_result($resSubmit, 0, 'numero'));
				$_RESULT["complemento"]      = trim(pg_fetch_result($resSubmit, 0, 'complemento'));
				$_RESULT["bairro"]          = trim(pg_fetch_result($resSubmit, 0, 'bairro'));
				$_RESULT["cep"]              = trim(pg_fetch_result($resSubmit, 0, 'cep'));
				$_RESULT["cidade"]           = trim(pg_fetch_result($resSubmit, 0, 'cidade'));
				$_RESULT["estado"]           = trim(pg_fetch_result($resSubmit, 0, 'estado'));
				$_RESULT["email"]            = trim(pg_fetch_result($resSubmit, 0, 'email'));
				$_RESULT["fone"]             = trim(pg_fetch_result($resSubmit, 0, 'fone'));
				$_RESULT["fax"]              = trim(pg_fetch_result($resSubmit, 0, 'fax'));
				$_RESULT["contato"]          = trim(pg_fetch_result($resSubmit, 0, 'contato'));
				$_RESULT["ie"]               = trim(pg_fetch_result($resSubmit, 0, 'ie'));
				$_RESULT["atacadista"]       = trim(pg_fetch_result($resSubmit, 0, 'atacadista'));
				$_RESULT["nome_fantasia"]    = trim(pg_fetch_result($resSubmit, 0, 'nome_fantasia'));

                if ($login_fabrica == 11 or $login_fabrica == 172) {
                    $_RESULT["bloqueio"] = pg_fetch_result($resSubmit, 0, "bloqueio");
                }

                if ($login_fabrica == 191) {
                    $_RESULT["senha"] = pg_fetch_result($resSubmit, 0, "senha");
                }
			}
		}
	}
}

$visual_black = "manutencao-admin";
$title        = traduz("CADASTRO DE REVENDAS");
$layout_menu  = "cadastro";

include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"maskedinput",
	"dataTable",
	"alphanumeric"
);

include("plugin_loader.php");
?>

<?php

	$inputs = array(
	"cnpj" => array(
		"span"      => 4,
		"label"     => traduz("CNPJ"),
		"type"      => "input/text",
		"width"     => 8,
		"lupa" => array(
            "name" => "lupa",
            "tipo" => "revenda",
            "parametro" => "cnpj",
            "extra" => array(
                "revenda" => "true"
            )
        ),
		"required"  => true,
		"maxlength" => 18
	),
	"razao_social" => array(
		"span"      => 4,
		"label"     => traduz("Razão Social"),
		"type"      => "input/text",
		"lupa" => array(
            "name" => "lupa",
            "tipo" => "revenda",
            "parametro" => "razao_social",
            "extra" => array(
                "revenda" => "true"
            )
        ),
		"required"  => true,
		"maxlength" => 150
	)
);

if($login_fabrica == 15 or $login_fabrica == 24){
	$inputs["nome_fantasia"] = array(
		"span"      => 8,
		"label"     => traduz("Nome Fantasia"),
		"type"      => "input/text",
		"maxlength" => 50
	);
}

$addressState = "addressState";

$addressZip = "addressZip";

$cidadeRequired = true;

$cidadeLabel = traduz("Cidade");

if (in_array($login_fabrica, [180,181,182])) {
	
	$addressState = "";

	$addressZip = "";
	
	$cidadeRequired = false;

	$cidadeLabel = "";

}

$campos = array(
	"ie" => array(
		"span"      => 4,
		"label"     => traduz("I.E."),
		"type"      => "input/text",
		"width"     => 10
	),
	"fone" => array(
		"span"      => 4,
		"label"     => traduz("Fone"),
		"type"      => "input/text",
		"width"     => 6,
		"class" => "telefone"
	),
	"fax" => array(
		"span"      => 4,
		"label"     => traduz("Fax"),
        "type"      => "input/text",
		"width"     => 6,
		"class" => "telefone"
	),
	"contato" => array(
		"span"      => 4,
		"label"     => traduz("Contato"),
		"type"      => "input/text",
		"maxlength" => 30
	), 
	"cep" => array(
		"span"      => 4,
        "class"     => $addressZip,
		"label"     => traduz("CEP"),
		"type"      => "input/text",
		//"width"   => 5,
		"maxlength" => 10,
		//"extra"   => array("onblur" => "buscaCEP(this.value)")
        //"extra"   => array("onblur" => "busca_cep(this.value)")
	),
    "estado" => array(
        "span"      => 2,
        "class"     => $addressState,
        "label"     => traduz("Estado"),
        "type"      => "select",
        //"width"     => 5,
        "required"  => true,
        "option" 	=> array()
    ),
    "cidade" => array(
        "span"      => 2,
        "class"     => "addressCity cidade_info",
        "label"     => $cidadeLabel,
        "type"      => "select",
        //"maxlength" => 30,
        "required"  => $cidadeRequired
    ),
    "bairro" => array(
        "span"      => 4,
        "class"     => "addressDistrict",
        "label"     => traduz("Bairro"),
        "type"      => "input/text",
        "maxlength" => 20
    ),
	"endereco" => array(
		"span"      => 4,
        "class"     => "address",
		"label"     => traduz("Endereço"),
		"type"      => "input/text",
		"width"     => 12,
		"maxlength" => 50
	),
	"numero" => array(
		"span"      => 4,
		"label"     => traduz("Número"),
		"type"      => "input/text",
		"width"     => 5,
		"maxlength" => 10
	),
	"complemento" => array(
		"span"      => 4,
		"label"     => traduz("Complemento"),
		"type"      => "input/text",
		"width"     => 12,
		"maxlength" => 40
	),
	"email" => array(
		"span"      => 4,
		"label"     => "E-mail",
		"type"      => "input/text",
		"maxlength" => 50
	),
);

if ($login_fabrica == 11 or $login_fabrica == 172) {
    $campos["bloqueio"] = array(
        "span"   => 2,
        "label"  => traduz("Bloquear Revenda?"),
        "type"   => "checkbox",
        "checks" => array(
            "t" => ""
        )
    );
}

if ($login_fabrica == 191) {
    $campos["senha"] = array(
        "span"   => 4,
        "label"  => "Senha de Acesso",
        "type"   => "input/text",
        "maxlength" => 50
    );
}

if (!strlen($_REQUEST["estado"])) {
	$campos["cidade"]["readonly"] = true;
}

/*foreach ($estados_BR as $value) {
	$campos['estado']['options'][$value] = $value;
}*/

$inputs = array_merge($inputs,$campos);

if($atacadista == "t"){
	$inputs["atacadista"] = array(
			"span"      => 4,
			"label"     => traduz("Atacadista"),
			"type"      => "input/checkbox"
		);
}

$hiddens = array(
	"revenda"
);
?>

<script type="text/javascript">

    $( window ).load(function() { //hd_chamado=2909049
        var id_revenda = $("#revenda").val();
        if(id_revenda.length > 0){
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>?monta_cidade=sim&id_revenda="+id_revenda,
                cache: false,
                success: function(data) {
                    retorno = data;
                    $("#cidade").html(retorno);
                }
            });
        }
    });


	$(function(){
		Shadowbox.init();
		//$.telefoneMask();
		$.autocompleteLoad(Array("revenda"),null,{
			revenda: {
				retorno : function(){
					ajax_revenda();
				}
			}
		});

        $("input[name='cep']").blur(function() {
            $("input[name='numero']").focus();
        });

		

        <?php if (!in_array($login_fabrica,[180, 181, 182])) { ?>
          $("#cep").mask("99.999-999");
          $("#cnpj").mask("99.999.999/9999-99");
        <? } ?>
		
		<?php if ($login_fabrica != 3) { ?>
			$("#numero").numeric();
		<?php } ?>
        $(".telefone").numeric();
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#estado").change(function () {
			if ($(this).val().length > 0) {
				$("#cidade").removeAttr("readonly");
			} else {
				$("#cidade").attr({"readonly": "readonly"});
			}
		});

	});

	function ajax_revenda(){
		$.ajax({
			cache : false,
			url : "autocomplete_ajax_revenda.php",
			type : "GET",
			data : {term : $("#cnpj").val(),search : "cod"},
			complete : function(retorno){
				retorno = $.parseJSON(retorno.responseText);
				retorna_revenda(retorno[0]);
			}
		});
	}

	function retorna_revenda(retorno){
		window.location = 'revenda_cadastro.php?revenda='+retorno.revenda_fabrica;
	}

	function geraExcel(){
		$.ajax({
			url : "<? echo $PHP_SELF; ?>?gera_excel=1",
			cache: false,
			success:function(data){
				$("#excel").html(data);
			}
		});
	}

    function escape(cep){

        cep = cep.replace(".", "");
        cep = cep.replace("-", "");

        return cep;

    }

    	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

    /** select de provincias/estados */
    $(function() {

        var post = "<?php echo $_POST['estado']; ?>";

	    <?php if (in_array($login_fabrica,[181])) { ?> 
	    	
	    	$(".cidade_info").hide()

            var colombia = ["Distrito Capital","Amazonas","Antioquia","Arauca","Atlántico", 
	            	"Bolívar","Boyacá","Caldas","Caquetá","Casanare",
	                "Cauca","Cesar","Chocó", "Córdoba","Cundinamarca","Guainía",
	                "Guaviare","Huila","La Guajira","Magdalena","Meta","Nariño",
	                "Norte de Santander","Putumayo","Quindío","Risaralda",
	                "San Andrés e Providencia","Santander","Sucre","Tolima",
	                "Valle del Cauca","Vaupés","Vichada"];

            $("#estado").append('<optgroup label="Provincias">');
            
			var select = "";
			
			$.each(colombia, function( index, value ) {
			    
			    var semAcento = removerAcentos(value);

                if (post == semAcento) {
                	select = "selected";
                }

				var option = "<option value='" + semAcento + "' "+ select + ">" + value + "</option>";

			    $("#estado").append(option);

			    select = "";
			}); 

            $("#estado").append('</optgroup>');

	  	<?php } ?>

	  	<?php if (in_array($login_fabrica,[182])) { ?>
			
			$(".cidade_info").hide()
			
			var peru = ["Amazonas","Ancash","Apurímac","Arequipa","Ayacucho",
			        "Cajamarca","Callao","Cusco","Huancavelica","Huánuco",
			        "Ica","Junin","La Libertad","Lambayeque","Lima","Loreto",
			        "Madre de Dios","Moquegua","Pasco","Piura","Puno","San Martín",
			        "Tacna","Tumbes","Ucayali"];

			$("#estado").append('<optgroup label="Provincias">');

			var select = "";
			
			$.each(peru, function( index, value ) {
			    
			    var semAcento = removerAcentos(value);

                if (post == semAcento) {
                	select = "selected";
                }

				var option = "<option value='" + semAcento + "' "+ select + ">" + value + "</option>";

			    $("#estado").append(option);

			    select = "";
			}); 

			$("#estado").append('</optgroup>');

		<?php } ?>

		<?php if (in_array($login_fabrica,[180])) {  ?>

			$(".cidade_info").hide()
			
			var argentina = ['Buenos Aires','Catamarca','Chaco','Chubut','Córdoba',             'Corrientes','Entre Ríos','Formosa','Jujuy','La Pampa',             'La Rioja','Mendoza','Misiones','Neuquén','Río Negro',             'Salta','San Juan','San Luis','Santa Cruz','Santa Fe',             'Santiago del Estero','Terra do Fogo','Tucumán'];

			$("#estado").append('<optgroup label="Provincias">');
			
			var select = "";
			
			$.each(argentina, function( index, value ) {
			    
			    var semAcento = removerAcentos(value);
			    
                if (post == semAcento) {
                	select = "selected";
                }

				var option = "<option value='" + semAcento + "' "+ select + ">" + value + "</option>";

			    $("#estado").append(option);

			    select = "";
			}); 

	        $("#estado").append('</optgroup>');

		<?php } ?>  
            
        <?php if (!in_array($login_fabrica, [180,181,182])) { ?>
            
            brasil = [  "AC - Acre",
                        "AL - Alagoas",
                        "AM - Amazonas",
                        "AP - Amapá"  ,
                        "BA - Bahia",
                        "CE - Ceará"  ,
                        "DF - Distrito Federal",
                        "ES - Espírito Santo" ,
                        "GO - Goiás",
                        "MA - Maranhão", 
                        "MG - Minas Gerais",
                        "MS - Mato Grosso do Sul",
                        "MT - Mato Grosso",
                        "PA - Pará",
                        "PB - Paraíba",
                        "PE - Pernambuco",
                        "PI - Piauí",
                        "PR - Paraná",
                        "RJ - Rio de Janeiro",
                        "RN - Rio Grande do Norte",
                        "RO - Rondônia", 
                        "RR - Roraima",
                        "RS - Rio Grande do Sul", 
                        "SC - Santa Catarina",
                        "SE - Sergipe", 
                        "SP - São Paulo",
                        "TO - Tocantins" ];

            $("#estado").append('<optgroup label="Estados">');
         
			var select = "";
			
			$.each(brasil, function( index, value ) {
			    
		     	var sigla = value.split(" - ");

                if (post == sigla[0]) {
                	select = "selected";
                }

				var option = "<option value=" + sigla[0] + " "+ select + ">" + value + "</option>";

			    $("#estado").append(option);

			    select = "";
			}); 
            $("#estado").append('</optgroup>');


        <?php } ?>
        
    });


</script>

<?php
if (count($_GET["sucesso"]) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=traduz('Gravado com sucesso!')?></h4>
    </div>
<?php
}

if (count($_GET["excluido"]) > 0) {
?>
    <div class="alert alert-success">
        <h4><?=traduz('Revenda excluída com sucesso!')?></h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<? if($login_fabrica <> 15){ ?>
<div class="alert">
<? if (strlen ($msg) > 0) { ?>
	<p>
		<? echo $msg; ?>
	</p>
<? } ?>
	<p>
		<?=traduz('Para incluir uma nova revenda, preencha somente seu CNPJ e clique em gravar.')?>
		<br>
		<?=traduz('Faremos uma pesquisa para verificar se a revenda já está cadastrada em nosso banco de dados.')?>
	</p>
</div>
<? } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name="frm_revenda" method="POST" class="form-search form-inline tc_formulario" >
	<?php
		$titulo_form = ($_GET['revenda'] || !empty($revenda)) ? traduz("Alteração de Cadastro") : traduz("Cadastro");
	?>
	<div class='titulo_tabela '><?=$titulo_form?></div>
	<br/>

	<?php
		echo montaForm($inputs, $hiddens);
	
	if ($login_fabrica == 117) {
		if (strlen($revenda) > 0) {
			$aux_sql = "SELECT observacao FROM tbl_revenda_fabrica WHERE revenda = $revenda AND fabrica=$login_fabrica";
			$aux_res = pg_query($con, $aux_sql);

			if (pg_num_rows($aux_res) > 0) {
				$observacao = pg_fetch_result($aux_res, 0, 'observacao');
			}

			$aux_sql = "SELECT modalidade FROM tbl_modalidade_revenda WHERE fabrica = $login_fabrica AND revenda = $revenda ORDER BY modalidade_revenda";
			$aux_res = pg_query($con, $aux_sql);
			$aux_row = pg_num_rows($aux_res);

			if ($aux_row > 0) {
				$modalidades_cadastradas = array();

				for ($w = 0; $w < $aux_row; $w++) {
					$modalidades_cadastradas[$w] = pg_fetch_result($aux_res, $w, 'modalidade');
				}
			}
		} ?>
		<div class='titulo_tabela '><?=traduz('Política de Pós-Venda para o Revendedor')?></div>
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><b><?=traduz('Linha')?></b></label>
						<div class='controls controls-row'>
							<div class='span4'>
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><b><?=traduz('Modalidade')?></b></label>
					<div class='controls controls-row'>
						<div class='span4'>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php
		$aux_sql = "SELECT modalidade, nome FROM tbl_modalidade WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY nome";
		$aux_res = pg_query($con, $aux_sql);
		$aux_row = pg_num_rows($aux_res);

		if ($aux_row > 0) {
			$modalidades = array();

			for ($z = 0; $z < $aux_row; $z++) { 
				$modalidades[$z]["modalidade"] = pg_fetch_result($aux_res, $z, 'modalidade');
				$modalidades[$z]["nome"]       = pg_fetch_result($aux_res, $z, 'nome');
			}
		}


		$aux_sql = "SELECT DISTINCT(descricao) FROM tbl_macro_linha WHERE ativo IS TRUE GROUP BY descricao ORDER BY descricao";
		$aux_res = pg_query($con, $aux_sql);
		$aux_row = pg_num_rows($aux_res);

		if ($aux_row > 0) {
			for ($z=0; $z < $aux_row; $z++) { 
				$descricao   = pg_fetch_result($aux_res, $z, 'descricao'); ?>
				<div class='row-fluid'>
					<div class='span2'></div>
						<div class='span4'>
							<div class='control-group <?=(in_array("modalidade", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='modalidade_<?=$z;?>'><?=$descricao;?></label>
								<div class='controls controls-row'>
									<div class='span4'>
									</div>
								</div>
							</div>
						</div>
					<div class='span4'>
						<div class='control-group <?=(in_array("modalidade", $msg_erro["campos"])) ? "error" : ""?>'>
							<div class='controls controls-row'>
								<div class='span4'>
									<h5 class='asteristico'>*</h5>
									<select name="modalidade_<?=$z;?>" id="modalidade_<?=$z;?>">
										<option value=""><?=traduz('Selecione')?></option>
										<?php
											foreach ($modalidades as $key => $modalidade_atual) {
												$modalidade = $modalidade_atual["modalidade"];
												$nome       = $modalidade_atual["nome"]; 

												if (!empty($modalidades_cadastradas))  {
													if ($modalidades_cadastradas[$z] == $modalidade) {
														$selected = " selected ";
													}
												}?>
												
												?> <option value="<?=$modalidade;?>" <?=$selected;?>><?=$nome;?></option> <?		
												
												unset($selected);
											}
										?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class='span2'></div>
				</div>
			<?php }
		}

		if (strlen($revenda) > 0) {
			 ?> <label class='control-label' for='anexar'><?=traduz('Clique para anexar contrato ou acordo')?></label> <?
			
			$boxUploader = array(
		      "div_id" => "div_anexos",
		      "prepend" => $anexo_prepend,
		      "context" => "revenda",
		      "label_botao" => traduz("Anexar ou Excluir Arquivo"),
		      "unique_id" => $revenda,
		      "hash_temp" => $anexoNoHash
			);

			include '../box_uploader.php';
		} ?>
	    <br>
	    <div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for='observacao'><?=traduz('Observação')?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<textarea name="observacao" id="observacao" style="width: 500px; height: 85px;"><?=$observacao;?></textarea>
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
			</div>
			<div class='span2'></div>
		</div>
	    <br />
<?php } ?>
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'gravar');"><?=traduz('Gravar')?></button>
		<?php
			if($_GET['revenda'] || !empty($revenda)){
		?>
			<button class='btn btn-warning' id="btn_acao" type="button"  onclick="window.location='<?php echo $PHP_SELF ?>';"><?=traduz('Limpar')?></button>
			<button class='btn btn-danger' id="btn_acao" type="button"  title="Excluir registro do sistema" onclick="if(confirm('Deseja realmente EXCLUIR esta REVENDA?') == true) {submitForm($(this).parents('form'),'descredenciar');}"><?=traduz('Excluir')?></button>
		<?php
			}
		?>

		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<p>
<center>
	<? if(in_array($login_fabrica, array(15,24,138,184,191,200))){ ?>
		<button class='btn' type="button" onclick="javascript: window.location='<? echo $PHP_SELF ?>?listar=todos'; return false;" ALT="Listar todas as Revendas" border='0'><?=traduz('Listar Todas as Revendas')?></button>
	<? } else if ($login_fabrica == 11 or $login_fabrica == 172) { ?>
        <button class='btn btn-danger' type="button" onclick="javascript: window.location='<? echo $PHP_SELF ?>?listar=todos'; return false;" ALT="Listar todas as Revendas Bloqueadas" border='0'><?=traduz('Listar Todas as Revendas Bloqueadas')?></button>
    <? } ?>
</center>
</p>
</div>
<?

if ($_GET ['listar'] == 'todos') {


	if($count_rev > 0){
	?>
		<table id="resultado_revendas" class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' >
					<th><?=traduz('CNPJ')?></th>
					<th><?=traduz('Razão Social')?></th>
					<?php
						if ($login_fabrica == 15 or $login_fabrica == 24) {
							echo "<th>".traduz("Nome Fantasia")."</th>";
						}
					?>
					<th><?=traduz('Cidade')?></th>
					<th><?=traduz('Estado')?></th>
                    <?php
                    if ($login_fabrica == 11 or $login_fabrica == 172) {
                    ?>
                        <th><?=traduz('Data de Bloqueio')?></th>
                        <th><?=traduz('Admin')?></th>
                    <?php
                    }
                    ?>
				</tr>
			</thead>
			<tbody>
	<?php

		for ($i = 0; $i < $count_rev; $i++) {
			$revenda          = trim(pg_fetch_result($resSubmit, $i, 'revenda'));
			$nome             = trim(pg_fetch_result($resSubmit, $i, 'nome'));
			$cnpj             = trim(pg_fetch_result($resSubmit, $i, 'cnpj'));
			$cidade           = trim(pg_fetch_result($resSubmit, $i, 'cidade'));
			$estado           = trim(pg_fetch_result($resSubmit, $i, 'estado'));
			$nome_fantasia    = trim(pg_fetch_result($resSubmit, $i, 'nome_fantasia'));

            if ($login_fabrica == 11 or $login_fabrica == 172) {
                $data_bloqueio  = pg_fetch_result($resSubmit, $i, "data_bloqueio");
                $admin_bloqueio = pg_fetch_result($resSubmit, $i, "admin_bloqueio");
            }

			$cnpj = $cnpj;
			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);

			echo "	<tr>
						<td class='tar' nowrap><a href='$PHP_SELF?revenda={$revenda}' >{$cnpj}</a></td>
						<td class='tal' nowrap><a href='$PHP_SELF?revenda={$revenda}' >{$nome}</a></td>";
			if($login_fabrica == 15 or $login_fabrica == 24){
				echo "	<td class='tal' nowrap><a href='$PHP_SELF?revenda={$revenda}' >{$nome_fantasia}</a></td>";
			}
				echo "
                    <td class='tal' nowrap>{$cidade}</td>
					<td class='tac'>{$estado}</td>
                ";

                if ($login_fabrica == 11 or $login_fabrica == 172) {
                    echo "
                        <td class='tac' nowrap>{$data_bloqueio}</td>
                        <td>{$admin_bloqueio}</td>
                    ";                    
                }

				echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";

		if ($count_rev > 50) {
		?>
			<script>
				$.dataTableLoad({ table: "#resultado_revendas" });
			</script>
		<?php
			}
		?>

		<br />

		<?php
        if ($login_fabrica != 11 and $login_fabrica <> 172) {
			$jsonPOST = excelPostToJson($_POST);
		      ?>

    		<div id='gerar_excel' class="btn_excel">
    			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    			<span><img src='imagens/excel.png' /></span>
    			<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
    		</div>
	   <?php
        }
	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>'.traduz("Nenhum resultado encontrado").'</h4>
			</div>
			</div>';
	}

}

?>
<script language='javascript' src='address_components.js'></script>
<? include "rodape.php"; ?>
