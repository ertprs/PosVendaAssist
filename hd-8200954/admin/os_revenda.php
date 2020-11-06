<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';

if($login_fabrica == 164){
    $sql = "select segmento_atuacao, descricao from tbl_segmento_atuacao  where fabrica = $login_fabrica and ativo is true";
    $resDestinacao = pg_query($con, $sql);  
}

if($_POST["busca_defeito_constatado"]){

    $produto = $_POST["produto"];

    $sql = "SELECT DISTINCT
                tbl_defeito_constatado.descricao,
                tbl_defeito_constatado.defeito_constatado                
                FROM tbl_diagnostico
                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                AND tbl_produto.produto = {$produto}
                AND tbl_diagnostico.ativo IS TRUE
                ORDER BY tbl_defeito_constatado.descricao ASC ";
    $res = pg_query($con, $sql);
    for($i=0; $i<pg_num_rows($res); $i++){
        $descricao              = pg_fetch_result($res, $i, descricao);
        $defeito_constatado     = pg_fetch_result($res, $i, defeito_constatado);

        $options .= "<option value='$defeito_constatado'>$descricao</option>";
    }
    echo $options;
    exit;
}


if($_POST["busca_defeito_reclamado"]){

	$produto = $_POST["produto"];

	$option = "<option value=''></option>";

	$sql = "SELECT 
				tbl_diagnostico.defeito_reclamado, 
				tbl_defeito_reclamado.descricao 
			FROM tbl_diagnostico 
			INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia 
			INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado 
			WHERE 
				tbl_produto.produto = {$produto} 
				AND tbl_produto.fabrica_i = {$login_fabrica} 
				AND tbl_diagnostico.fabrica = {$login_fabrica} 
				AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
                AND tbl_defeito_reclamado.ativo IS TRUE 
                AND tbl_diagnostico.ativo IS TRUE 
            ORDER BY tbl_defeito_reclamado.descricao ASC";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$rows = pg_num_rows($res);

		for ($i = 0; $i < $rows; $i++) { 
			
			$defeito_reclamado = pg_fetch_result($res, $i, "defeito_reclamado");
			$descricao         = pg_fetch_result($res, $i, "descricao");

			$option .= "<option value='{$defeito_reclamado}' > {$descricao} </option>";

		}

	}

	exit($option);

}

if ($login_fabrica == 1) {
    include("os_revenda_blackedecker.php");
    exit;
}

include_once 'funcoes.php';

/*  MLG 25/01/2011 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
    Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
    Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
    Para saber se tem anexo:temNF($os, 'bool');
    Para saber se 2º anexo: temNF($os, 'bool', 2);
    Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
                            echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
                            echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/                          
include_once '../anexaNF_inc.php';

if ($fabricaFileUploadOS) {
    if (!empty($os)) {
        $tempUniqueId = $os;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$pedir_sua_os = pg_result($res,0,pedir_sua_os);

$msg_erro["msg"]  = array();
$qtde_item = 20;

if (strlen($_POST['qtde_item']) > 0) $qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if ($btn_acao == "gravar"){

    if (strlen($_POST['sua_os']) > 0){
            $xsua_os = $_POST['sua_os'] ;
        if ($login_fabrica <> 11 and $login_fabrica <> 172 and $login_fabrica <> 5 and $login_fabrica<>3) {
            $xsua_os = "000000" . trim($xsua_os);
            $xsua_os = substr($xsua_os, strlen($xsua_os) - 7 , 7) ;
        }
            $xsua_os = "'". $xsua_os ."'";
    } else {
        $xsua_os = "null";
    }

    //die(pre_echo($_POST));
    if($_POST['data_abertura']){
        $xdata_abertura = dateFormat($_POST['data_abertura'], 'dmy', "'y-m-d'");
        $xdata_abertura_comp = dateFormat($_POST['data_abertura'], 'dmy', "y-m-d");
    }
    if($_POST['data_nf']){
        $xdata_nf = dateFormat($_POST['data_nf'], 'dmy', "'y-m-d'");
        $xdata_nf_comp = dateFormat($_POST['data_nf'], 'dmy', "y-m-d");
    }

    /* if (!$xdata_abertura or !$xdata_nf)
        $msg_erro = "Data Inválida - Obrigatória"; */

    if(!$xdata_abertura){
        $msg_erro["msg"][] = traduz("A Data Abertura é Obrigatória");
        $msg_erro["campos"][] = "data";
    }

    if(strlen($nota_fiscal) == 0){
    	$msg_erro["msg"][] = traduz("A Nota Fical é obrigatória");
    	$msg_erro["campos"][] = "nota_fiscal";
    }

    if(strlen($nota_fiscal) > 0 && !$xdata_nf){
        $msg_erro["msg"][] = traduz("A Data da Nota é Obrigatória");
        $msg_erro["campos"][] = "data_nota";
    }

    if(strlen($xdata_abertura_comp) > 0 && strlen($xdata_nf_comp) > 0 && strtotime($xdata_abertura_comp) < strtotime($xdata_nf_comp)){
    	$msg_erro["msg"][] = traduz("A Data da Nota não pode ser maior que a Data de Abertura");
        $msg_erro["campos"][] = "data_nota";
    }

    $campo_extra              = "";
    $valor_campo_extra        = "";
    $valor_update_campo_extra = "";

    if($login_fabrica== 164){
        $data7dias = date("Y-m-d",strtotime("-7 day"));
        if (strtotime(str_replace("'", "", $xdata_abertura)) < strtotime($data7dias)) {
            $msg_erro['msg'][] = traduz("Data da Abertura não pode ser anterior a 7 dias.");
            $msg_erro["campos"][] = "data";
        }

        $revenda_estado = $_POST['revenda_estado'];

        if (strlen(trim($_POST['revenda_fantasia'])) > 0) {
            $revenda_fantasia = pg_escape_string($_POST['revenda_fantasia']);
            $campo_extra['revenda_fantasia'] = $revenda_fantasia;
            $campo_extra = ", '".json_encode($campo_extra)."'";
            $valor_campo_extra = ", campos_extra";
            $valor_update_campo_extra = ", campos_extra  = $campo_extra";
        }
    }

    $nota_fiscal = $_POST["nota_fiscal"];

    if (strlen($nota_fiscal) == 0) {
        $xnota_fiscal = 'null';
    } else {
        $nota_fiscal = trim($nota_fiscal);
        $nota_fiscal = str_replace(".","",$nota_fiscal);
        $nota_fiscal = str_replace(" ","",$nota_fiscal);
        $nota_fiscal = str_replace("-","",$nota_fiscal);
        $nota_fiscal = str_replace("'","",$nota_fiscal);
        $nota_fiscal = "000000" . $nota_fiscal;
        $nota_fiscal = substr($nota_fiscal,strlen($nota_fiscal)-6,6);
        $xnota_fiscal = "'" . $nota_fiscal . "'" ;
    }

    if (strlen($_POST['revenda_cnpj']) > 0) {
        $revenda_cnpj  = $_POST['revenda_cnpj'];
        $revenda_cnpj  = str_replace(".","",$revenda_cnpj);
        $revenda_cnpj  = str_replace("-","",$revenda_cnpj);
        $revenda_cnpj  = str_replace("/","",$revenda_cnpj);
        $revenda_cnpj  = str_replace(" ","",$revenda_cnpj);
        $xrevenda_cnpj = "'". $revenda_cnpj ."'";

        if($login_fabrica == 35){
            $resultado = VerificaBloqueioRevenda($revenda_cnpj, $login_fabrica);
            if(strlen(trim($resultado))>0){
                $msg_erro["msg"][] = $resultado;
                $msg_erro["campos"][] = "revenda";
            }            
        }

    } else {
        $msg_erro["msg"][] = traduz("Insira as informações de Revenda");
        $msg_erro["campos"][] = "revenda";
        // $xrevenda_cnpj = "null";
    }

    if (strlen($_POST['posto_codigo']) > 0) {
        $posto_codigo = trim($_POST['posto_codigo']);
        $posto_codigo = str_replace("-","",$posto_codigo);
        $posto_codigo = str_replace(".","",$posto_codigo);
        $posto_codigo = str_replace("/","",$posto_codigo);
        $posto_codigo = substr($posto_codigo,0,14);

        $res = pg_exec($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
        $posto = pg_result($res,0,0);

    } else {
    	$msg_erro["msg"][] = traduz("Insira as informações do Posto");
        $msg_erro["campos"][] = "posto";
        // $posto = "null";
    }

    if(count($msg_erro["msg"]) == 0){

        if ($xrevenda_cnpj <> "null") {
            $sql = "SELECT *
                    FROM   tbl_revenda
                    WHERE  cnpj = $xrevenda_cnpj";
            $res = pg_exec($con,$sql);

            if (pg_numrows ($res) == 0){
                $msg_erro["msg"][] = traduz("CNPJ da revenda não cadastrado");
            } else {
                $revenda        = trim(pg_result($res,0,revenda));
                $nome           = trim(pg_result($res,0,nome));
                $endereco       = trim(pg_result($res,0,endereco));
                $numero         = trim(pg_result($res,0,numero));
                $complemento    = trim(pg_result($res,0,complemento));
                $bairro         = trim(pg_result($res,0,bairro));
                $cep            = trim(pg_result($res,0,cep));
                $cidade         = trim(pg_result($res,0,cidade));
                $fone           = trim(pg_result($res,0,fone));
                $cnpj           = trim(pg_result($res,0,cnpj));

                if (strlen($revenda) > 0)
                    $xrevenda = "'". $revenda ."'";
                else
                    $xrevenda = "null";

                if (strlen($nome) > 0)
                    $xnome = "'". $nome ."'";
                else
                    $xnome = "null";

                if (strlen($endereco) > 0)
                    $xendereco = "'". $endereco ."'";
                else
                    $xendereco = "null";

                if (strlen($numero) > 0)
                    $xnumero = "'". $numero ."'";
                else
                    $xnumero = "null";

                if (strlen($complemento) > 0)
                    $xcomplemento = "'". $complemento ."'";
                else
                    $xcomplemento = "null";

                if (strlen($bairro) > 0)
                    $xbairro = "'". $bairro ."'";
                else
                    $xbairro = "null";

                if (strlen($cidade) > 0)
                    $xcidade = "'". str_replace("'","''",$cidade) ."'";
                else
                    $xcidade = "null";

                if (strlen($cep) > 0)
                    $xcep = "'". $cep ."'";
                else
                    $xcep = "null";

                if (strlen($fone) > 0)
                    $xfone = "'". $fone ."'";
                else
                    $xfone = "null";

                if (strlen($cnpj) > 0)
                    $xcnpj = "'". $cnpj ."'";
                else
                    $xcnpj = "null";

                $sql = "SELECT cliente
                        FROM   tbl_cliente
                        WHERE  cpf = $xrevenda_cnpj";
                $res = pg_exec($con,$sql);

                if (pg_numrows($res) == 0){
                    // insere dados
                    $sql = "INSERT INTO tbl_cliente (
                                nome       ,
                                endereco   ,
                                numero     ,
                                complemento,
                                bairro     ,
                                cep        ,
                                cidade     ,
                                fone       ,
                                cpf
                            )VALUES(
                                $xnome       ,
                                $xendereco   ,
                                $xnumero     ,
                                $xcomplemento,
                                $xbairro     ,
                                $xcep        ,
                                $xcidade     ,
                                $xfone       ,
                                $xcnpj
                            )";
                    // pega valor de cliente

                    $res      = pg_exec($con,$sql);
                    $msg_erro["msg"][] = pg_errormessage($con);

                    if (count($msg_erro["msg"]) == 0 and strlen($cliente) == 0) {
                        $res     = pg_exec($con,"SELECT CURRVAL ('seq_cliente')");
                        $msg_erro["msg"][] = pg_errormessage($con);
                        if (count($msg_erro["msg"]) == 0) $cliente = pg_result($res,0,0);
                    }

                } else {
                    // pega valor de cliente
                    $cliente = pg_result($res,0,cliente);
                }
            }
        } else {
            $msg_erro["msg"][] = traduz("CNPJ não informado");
        }

        if (strlen($_POST['revenda_fone']) > 0) {
            $xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
        } else {
			if($login_fabrica == 19) {
				$msg_erro["msg"][] = "Favor informar o telefone da revenda";
			}else{
				$xrevenda_fone = "null";
			}
        }

        if (strlen($_POST['revenda_email']) > 0) {
            $xrevenda_email = "'". $_POST['revenda_email'] ."'";
        } else {
            $xrevenda_email = "null";
        }

        if (strlen($_POST['obs']) > 0) {
            $xobs = "'". str_replace("'","''",$_POST['obs']) ."'";
        } else {
            $xobs = "null";
        }

        if (strlen($_POST['contrato']) > 0) {
            $xcontrato = "'". $_POST['contrato'] ."'";
        } else {
            $xcontrato = "'f'";
        }

        // Localizar a última OS para cadastro da Black & Decker
        //LIBERAR WELLINGTON 03/01/2007
        if ((in_array($login_fabrica, [1])) and strlen($os_revenda) == 0) {
            if ($posto == "null") {
                $msg_erro["msg"][] = traduz(" Digite o Código do Posto.");
            } else {
                if (strlen($posto) == 0) {
                    $msg_erro["msg"][] = traduz(" Posto digitado não foi encontrado.");
                } else {

                    $sql = "SELECT MAX(sua_os) FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto;";
                    $res = pg_exec($con,$sql);

                    if (pg_numrows($res) == 1) {

                        $max_os = pg_result($res, 0, 0);

                        if(strstr($max_os, "-") == true){
                            list($max_os, $rest) = explode("-", $max_os);
                        }

                        $xsua_os = $max_os + 1;
                        $xsua_os = "00000".$xsua_os;
                        if ($login_fabrica==1)  $xsua_os = substr($xsua_os, strlen($xsua_os)-5 , 5) ;
                        $xsua_os = "'".$xsua_os."'";

                    }
                }
            }
        }
    }

    if (!in_array($login_fabrica, [11,172])) {

        if (count($msg_erro["msg"]) == 0) {

            $res = pg_exec($con,"BEGIN TRANSACTION");

            if (strlen($os_revenda) == 0) {

                #-------------- insere pedido ------------
                $sql = "INSERT INTO tbl_os_revenda (
                            fabrica      ,
                            sua_os       ,
                            data_abertura,
                            cliente      ,
                            revenda      ,
                            obs          ,
                            digitacao    ,
                            posto        ,
                            contrato     
                            $valor_campo_extra
                        ) VALUES (
                            $login_fabrica   ,
                            $xsua_os         ,
                            $xdata_abertura  ,
                            $cliente         ,
                            $revenda         ,
                            $xobs            ,
                            current_timestamp,
                            $posto           ,
                            $xcontrato       
                            $campo_extra
                        )";
    			$cadastro_os = true;
            } else {
                //digitacao     = current_timestamp                ,
                $sql = "UPDATE tbl_os_revenda SET
                            fabrica       = $login_fabrica ,
                            sua_os        = $xsua_os       ,
                            data_abertura = $xdata_abertura,
                            cliente       = $cliente       ,
                            revenda       = $revenda       ,
                            obs           = $xobs          ,
                            posto         = $posto         ,
                            contrato      = $xcontrato     
                            $valor_update_campo_extra
                        WHERE os_revenda = $os_revenda
                        AND   fabrica    = $login_fabrica";
            }
            $res = pg_query($con,$sql);
            if(strlen(pg_last_error($con)) > 0){
            	$msg_erro["msg"][] = pg_last_error($con);
            }

    /*
            if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
                $res        = pg_exec($codefeito_constatado_descricaon,"SELECT CURRVAL ('seq_os_revenda')");
                $os_revenda = pg_result($res,0,0);
                $msg_erro   = pg_errormessage($con);

                $sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
                $res = @pg_exec($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
    */

            if (count($msg_erro["msg"]) == 0 and strlen($os_revenda) == 0) {
                
                $res        = pg_query($con,"SELECT CURRVAL ('seq_os_revenda')");
                $os_revenda = pg_fetch_result($res,0,0);

                if(strlen(pg_last_error($con)) > 0){
    	        	$msg_erro["msg"][] = pg_last_error($con);
    	        }

                // se nao foi cadastrado número da OS Fabricante (Sua_OS)
                if ($xsua_os == 'null' AND count($msg_erro["msg"]) == 0 and strlen($os_revenda) <> 0) {
                    if (!in_array($login_fabrica, array(1,3,11,172))) {
                        $sql = "UPDATE tbl_os_revenda SET
                                    sua_os        = '$os_revenda'
                                WHERE os_revenda  = $os_revenda
                                AND  posto        = $posto
                                AND  fabrica      = $login_fabrica ";
                        $res = pg_query($con,$sql);

                        if(strlen(pg_last_error($con)) > 0){
    			        	$msg_erro["msg"][] = pg_last_error($con);
    			        }

                    }
                }

                if (count($msg_erro["msg"]) == 0) {
                    $sql = "UPDATE tbl_cliente SET
                                contrato = $xcontrato
                            WHERE cliente  = $revenda";
                    $res = pg_query($con,$sql);

                    if(strlen(pg_last_error($con)) > 0){
    		        	$msg_erro["msg"][] = pg_last_error($con);
    		        }

                }

                //HD-3207600
                // if (strlen($msg_erro) > 0) {
                //  break ;
                // }
            }


            if (count($msg_erro["msg"]) == 0) {

                if (!empty($os_revenda)) {
                    if (strlen(trim($_POST['anexo_chave'])) > 0) {
                        $anexo_chave_tdocs = $_POST['anexo_chave'];

                        if (!empty($anexo_chave_tdocs)) {
                            $sql_update_tdocs = "UPDATE tbl_tdocs SET referencia_id = '$os_revenda' WHERE hash_temp = '$anexo_chave_tdocs' AND fabrica = $login_fabrica";
                            $res_update_tdocs = pg_query($con, $sql_update_tdocs);
                        }
                    }
                }

                //$qtde_item = $_POST['qtde_item'];

                $tem_produto = false;

                for ($i = 0 ; $i < $qtde_item ; $i++)
                {

    				$novo               = $_POST["novo_".$i];
    				$item               = $_POST["item_".$i];
    				$referencia         = $_POST["produto_referencia_".$i];
    				$serie              = $_POST["produto_serie_".$i];
    				$serie = str_replace("'","",$serie);
    				$type               = $_POST["versao_produto_".$i];
    				$capacidade         = $_POST["produto_capacidade_".$i];
    				$embalagem_original = $_POST["embalagem_original_".$i];
    				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
    				$qtde               = $_POST["qtde_".$i];

    				if(strlen($referencia) > 0){
    					$tem_produto = true;
    				}else{
    					continue;
    				}

                    if($login_fabrica != 160 and !$replica_einhell){
                        $type = $_POST["type_".$i];
                    }

                    if($login_fabrica == 162){
                        $imei = $_POST["imei_".$i];
                    }

                    if (!strlen($qtde)) {
                        $qtde = 1;
                    }

                    $tipo_atendimento    = $_POST["tipo_atendimento_".$i];
                    if (strlen(trim($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

                    if($login_fabrica == 74){
                        $data_fabricacao    = $_POST["data_fabricacao_".$i];

                        $data_fabricacao_modif = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_fabricacao);

                        /* ==============================*/

                        if(count($msg_erro["msg"])==0){
                            list($df, $mf, $yf) = explode("/", $data_fabricacao);
                            if(!checkdate($mf,$df,$yf))
                                $msg_erro["msg"][] = "Data Inválida";
                        }

                        if(count($msg_erro["msg"])==0){
                            $sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
                            $resX = pg_query ($con,$sqlX);
                            $aux_atual = pg_fetch_result ($resX,0,0);
                        }

                        if(empty($aux_atual)){
                            $msg_erro["msg"][] = "Data Inválida";
                        }

                        if(count($msg_erro["msg"])==0){
                            $sqlX = "SELECT '$aux_atual'::date  > '$data_fabricacao_modif'";
                            $resX = pg_query($con,$sqlX);
                            $periodo_data = pg_fetch_result($resX,0,0);
                        }
                        if($periodo_data == f){
                            $msg_erro["msg"][] = "Data Inválida";
                        }

                        if(count($msg_erro["msg"])==0){
                            $condicao_data_fabricao = ",data_fabricacao";
                        }

                        if(strlen($data_fabricacao) > 0){
                            $xdata_fabricacao = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_fabricacao);
                            $condicao_data_fabricao_value = ",'$xdata_fabricacao'";

                            $condicao_data_fabricao_update = " ,data_fabricacao    = '$xdata_fabricacao'";
                        }

                        

                        if(empty($xdata_fabricacao)){
                            $xdata_fabricacao = "null";

                        }
                    }else{

                        if(strlen($xdata_fabricacao) == 0){
                            $xdata_fabricacao = "null";

                        }

                        if($login_fabrica == 164){
                            $defeito_constatado = $_POST["defeito_constatado_$i"];
                            $destinacao         = $_POST["destinacao_$i"];

                            $condicao_def_constatado_destinacao = ", defeito_constatado_descricao ";

                            $value_def_constatado_destinacao = ", '$defeito_constatado' ";

                            $update_def_constatado = ", defeito_constatado_descricao = '$defeito_constatado' ";

                            $rg_produto = $destinacao; 

                        }

                    }

    				if (strlen($serie) == 0) {
    					$serie = "null";
    					$xserie = "null";
    				} else {
    					$xserie = $serie;
    					$serie = "'". $serie ."'";
    				}

                    if (strlen($type) == 0) $type = "null";
                    else                    $type = "'".$type."'";

                    if (strlen($embalagem_original) == 0) $embalagem_original = "null";
                    else                                  $embalagem_original = "'".$embalagem_original."'";

                    if (strlen($sinal_de_uso) == 0) $sinal_de_uso = "null";
                    else                            $sinal_de_uso = "'".$sinal_de_uso."'";

                    if(in_array($login_fabrica, array(50,94))){ //hd_chamado=2705567
                        $defeito_reclamado  = $_POST["defeito_reclamado_".$i];

                        if(strlen(trim($referencia)) > 0 AND strlen(trim($defeito_reclamado)) == 0){
                            $msg_erro["msg"][] = "Preencher o defeito reclamado";
                        }else{
                            $defeito_reclamado = ($login_fabrica == 50) ? $defeito_reclamado : "'".$defeito_reclamado."'";

                            $condicao_defeito_reclamado = ($login_fabrica == 50) ? ", defeito_reclamado" : ", defeito_constatado_descricao";
                            $condicao_defeito_reclamado_value = ", $defeito_reclamado";
                            $condicao_defeito_reclamado_update = ($login_fabrica == 50)  ? ", defeito_reclamado = {$defeito_reclamado}" : " ,defeito_constatado_descricao    = $defeito_reclamado";
                        }
                    }

                    if (strlen($item) > 0 AND $novo == 'f') {
                        $sql = "DELETE FROM tbl_os_revenda_item
                                WHERE  os_revenda = $os_revenda
                                AND    os_revenda_item = $item";
                        //$res = @pg_exec($con,$sql);

                        if(strlen(pg_last_error()) > 0){
                        	$msg_erro["msg"][] = pg_last_error($con);
                        }

                    }

                    if (count($msg_erro["msg"]) == 0) {

                        if (strlen($referencia) > 0) {
                            $referencia_original = $referencia;
                            $referencia = str_replace("-","",$referencia);
                            $referencia = str_replace(".","",$referencia);
                            $referencia = str_replace("/","",$referencia);
                            $referencia = str_replace(" ","",$referencia);
                            $referencia = "'". $referencia ."'";

                            $sql = "SELECT 
                            			produto,
                            			numero_serie_obrigatorio 
                                    FROM tbl_produto
                                    INNER JOIN tbl_linha USING (linha)
                                    WHERE 
                                    	UPPER(referencia_pesquisa) = UPPER($referencia)
                                    	AND fabrica = $login_fabrica";
                            $res = pg_exec($con,$sql);

                            if (pg_num_rows($res) == 0) {

                                $msg_erro["msg"][] = "Produto não encontrado";

                            } else {

    							$produto                  = pg_fetch_result($res, 0, "produto");
    							$numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");

    							if($numero_serie_obrigatorio == "t" && (strlen(trim($xserie)) == 0) || $xserie == "null"){
    								$msg_erro["msg"][] = "O Produto {$referencia_original} exige Número de Série";
    							}else{

                                    if(in_array($login_fabrica, array(20))){

                                        if(strlen($xserie) != 3 && strlen($xserie) != 9){
                                            $msg_erro["msg"][] = "O Número de Serie do produto {$referencia_original} Deve Conter 3 ou 9 Dígitos.";
                                        }

                                        if(!is_numeric($xserie)){
                                            $msg_erro["msg"][] = "O Número de Série do produto {$referencia_original} deve ser apenas números.";
                                        }

                                    }


                                }

                            	if (count($msg_erro["msg"]) == 0) { // HD 321132 - Inicio
                            		/* ===========INÍCIO DO PROCESSO DA IMAGEM =============== */
                            		//zambaa

                            		if ($anexaNotaFiscal) {
                            			$qt_anexo = 0;
                            			foreach($_FILES['foto_nf'] as $files){                                                                                                                                                  
                            				if(strlen($_FILES['foto_nf']['name'][$qt_anexo])==0){
                            					if ($login_fabrica == 81){
                            						$msg_erro = 'Imagem da Nota Fiscal obrigatória.';
                            					}
                            					 continue;
                            				}
                            				$dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
                            				$dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
                            				$dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
                            				$dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
                            				$dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];

                            				$anexou = anexaNF("r_$os_revenda", $dados_anexo);

                            				if ($anexou !== 0) {
                            					 $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
                            				}

                            				$qt_anexo++;
                            			}
                            		}
                            	}
                            }

                            if (strlen($capacidade) == 0) {
                                $xcapacidade = 'null';
                            } else {
                                $xcapacidade = "'".$capacidade."'";
                            }

                            if (count($msg_erro["msg"]) == 0) {
                                if ($login_fabrica == 50 ){

                                    if ((strlen($os_revenda) == 0) OR ($novo == 't')){
                                        $sql =  "INSERT INTO tbl_os_revenda_item (
                                                    os_revenda         ,
                                                    produto            ,
                                                    nota_fiscal        ,
                                                    data_nf            ,
                                                    serie              ,
                                                    type               ,
                                                    embalagem_original ,
                                                    sinal_de_uso
                                                    $condicao_data_fabricao
                                                    {$condicao_defeito_reclamado}
                                                ) VALUES (
                                                    $os_revenda           ,
                                                    $produto              ,
                                                    $xnota_fiscal         ,
                                                    $xdata_nf             ,
                                                    $serie                ,
                                                    $type                 ,
                                                    $embalagem_original   ,
                                                    $sinal_de_uso
                                                    $condicao_data_fabricao_value
                                                    {$condicao_defeito_reclamado_value}
                                                )";
                                    } else {
                                        $sql =  "UPDATE tbl_os_revenda_item SET
                                                    produto            = '$produto'            ,
                                                    nota_fiscal        = $xnota_fiscal         ,
                                                    data_nf            = $xdata_nf             ,
                                                    serie              = $serie                ,
                                                    type               = $type                 ,
                                                    embalagem_original = $embalagem_original   ,
                                                    sinal_de_uso       = $sinal_de_uso         ,
                                                    data_fabricacao    = $xdata_fabricacao
                                                    $condicao_data_fabricao_update 
                                                    {$condicao_defeito_reclamado_update} 
                                                WHERE  os_revenda      = $os_revenda
                                                AND    os_revenda_item = $item";
                                    }
                                }else{
                                    if ((strlen($os_revenda) == 0) OR ($novo == 't')){

                                        if($login_fabrica == 162){
                                            $rg_produto = $imei;
                                        }

                                        $sql =  "INSERT INTO tbl_os_revenda_item (
                                                    os_revenda         ,
                                                    produto            ,
                                                    nota_fiscal        ,
                                                    data_nf            ,
                                                    serie              ,
                                                    type               ,
                                                    rg_produto         ,
                                                    qtde               ,
                                                    embalagem_original ,
                                                    sinal_de_uso
                                                    $condicao_def_constatado_destinacao
                                                    $condicao_data_fabricao
                                                    $condicao_defeito_reclamado
                                                ) VALUES (
                                                    $os_revenda           ,
                                                    $produto              ,
                                                    $xnota_fiscal         ,
                                                    $xdata_nf             ,
                                                    $serie                ,
                                                    $type                 ,
                                                    '$rg_produto'         ,
                                                    $qtde                 ,
                                                    $embalagem_original   ,
                                                    $sinal_de_uso
                                                    $value_def_constatado_destinacao 
                                                    $condicao_data_fabricao_value
                                                    $condicao_defeito_reclamado_value
                                                )";
                                    } else {
                                        $sql =  "UPDATE tbl_os_revenda_item SET
                                                    produto            = '$produto'            ,
                                                    nota_fiscal        = $xnota_fiscal         ,
                                                    data_nf            = $xdata_nf             ,
                                                    serie              = $serie                ,
                                                    type               = $type                 ,
                                                    rg_produto         = '$rg_produto',
                                                    qtde               = $qtde                 ,
                                                    embalagem_original = $embalagem_original   ,
                                                    sinal_de_uso       = $sinal_de_uso         ,
                                                    data_fabricacao    = $xdata_fabricacao
                                                    $update_def_constatado 
                                                    $condicao_data_fabricao_update
                                                    $condicao_defeito_reclamado_update
                                                WHERE  os_revenda      = $os_revenda
                                                AND    os_revenda_item = $item";

                                    }
                                }

                                $res = pg_query($con,$sql);

                                if(strlen(pg_last_error($con)) > 0){
    		                    	$msg_erro["msg"][] = pg_last_error($con);
    		                    }

                                if (count($msg_erro["msg"]) > 0) {
                                    break ;
                                } else {
                    //                              $sql = "SELECT fn_valida_os_item_revenda($os_revenda,$login_fabrica,$produto)";
                    //                              $res = @pg_exec($con,$sql);
                    //                              $msg_erro = pg_errormessage($con);

                                    if (count($msg_erro["msg"]) > 0) {
                                        $linha_erro = $i;
                                        break ;
                                    }
                                }
                            }
                        }
                    }
                }

                if($tem_produto === false){
                	$msg_erro["msg"][] = traduz("Por favor, insira pelo menos um produto para a abertura de OS Revenda");
                }

                if(count($msg_erro["msg"]) == 0) {
    				$sql = "UPDATE tbl_revenda set fone = $xrevenda_fone, email = $xrevenda_email  where revenda = $revenda "; 
    				$res = pg_query($con,$sql);
    				
    				if(strlen(pg_last_error($con)) > 0){
                    	$msg_erro["msg"][] = traduz("Erro ao gravar a OS de Revenda");
                    }


                    $sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
                    $res = pg_query($con,$sql);

                    if(strlen(pg_last_error($con)) > 0){
                    	$msg_erro["msg"][] = traduz("Erro ao gravar a OS de Revenda");
                    }

                }
            }
        }

        /* ===========FIM DO PROCESSO DA IMAGEM ================ */

        if (count($msg_erro["msg"]) == 0) {
            $res = pg_exec($con, "COMMIT TRANSACTION");
            header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
            exit;
        } else {
            /* if (strpos (implode("|", $msg_erro["msg"]),"tbl_os_revenda_unico") > 0) $msg_erro["msg"][] = " O Número da Ordem de Serviço do Fabricante já Esta Cadastrado.";
            if (strpos (implode("|", $msg_erro["msg"]),"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro["msg"][] = "Data da abertura deve ser informada."; */
    		$res = pg_exec($con, "ROLLBACK TRANSACTION");
    		if($cadastro_os) unset($os_revenda); 
    	}

    } else {

        if (count($msg_erro["msg"]) == 0) {

            $fabricas_arr = [11, 172];

            $res = pg_exec($con,"BEGIN TRANSACTION");

            foreach ($fabricas_arr as $fabrica) {

                $sqlPostoSequencial = "UPDATE tbl_posto_fabrica
                                       SET sua_os = sua_os + 1
                                       WHERE posto = {$posto}
                                       AND fabrica = {$fabrica}";
                $resPostoSequencial = pg_query($con, $sqlPostoSequencial);

                #-------------- insere pedido ------------
                $sql = "INSERT INTO tbl_os_revenda (
                            fabrica      ,
                            sua_os       ,
                            data_abertura,
                            cliente      ,
                            revenda      ,
                            obs          ,
                            digitacao    ,
                            posto        ,
                            contrato     
                            $valor_campo_extra
                        ) VALUES (
                            $fabrica   ,
                            $xsua_os         ,
                            $xdata_abertura  ,
                            $cliente         ,
                            $revenda         ,
                            $xobs            ,
                            current_timestamp,
                            $posto           ,
                            $xcontrato       
                            $campo_extra
                        ) RETURNING os_revenda";
                $res = pg_query($con,$sql);

                $os_revenda_fab = pg_fetch_result($res, 0, 'os_revenda');

                $cadastro_os = true;

                $arrFabricaOs[$fabrica] = $os_revenda_fab;

            }

            if(strlen(pg_last_error($con)) > 0){
                $msg_erro["msg"][] = pg_last_error($con);
            }

            if (count($msg_erro["msg"]) == 0) {

                if (!empty($os_revenda)) {
                    if (strlen(trim($_POST['anexo_chave'])) > 0) {
                        $anexo_chave_tdocs = $_POST['anexo_chave'];

                        if (!empty($anexo_chave_tdocs)) {
                            $sql_update_tdocs = "UPDATE tbl_tdocs SET referencia_id = '$os_revenda' WHERE hash_temp = '$anexo_chave_tdocs' AND fabrica = $login_fabrica";
                            $res_update_tdocs = pg_query($con, $sql_update_tdocs);
                        }
                    }
                }

                //$qtde_item = $_POST['qtde_item'];

                $tem_produto = false;

                for ($i = 0 ; $i < $qtde_item ; $i++)
                {

                    $novo               = $_POST["novo_".$i];
                    $item               = $_POST["item_".$i];
                    $referencia         = $_POST["produto_referencia_".$i];
                    $serie              = $_POST["produto_serie_".$i];
                    $serie = str_replace("'","",$serie);
                    $type               = $_POST["versao_produto_".$i];
                    $capacidade         = $_POST["produto_capacidade_".$i];
                    $embalagem_original = $_POST["embalagem_original_".$i];
                    $sinal_de_uso       = $_POST["sinal_de_uso_".$i];
                    $qtde               = $_POST["qtde_".$i];
                    $fabrica_codigo_interno = $_POST["fabrica_codigo_interno_".$i];
                    $codigo_interno         = trim($_POST["codigo_interno_".$i]);
                    $possui_codigo_interno       = $_POST['possui_codigo_interno_'.$i];

                    if(strlen($referencia) > 0){
                        $tem_produto = true;
                    }else{
                        continue;
                    }

                    if($login_fabrica != 160 and !$replica_einhell){
                        $type = $_POST["type_".$i];
                    }

                    if (!strlen($qtde)) {
                        $qtde = 1;
                    }

                    $tipo_atendimento    = $_POST["tipo_atendimento_".$i];
                    if (strlen(trim($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

                    /* ==============================*/

                    if(empty($xdata_fabricacao)){
                        $xdata_fabricacao = "null";

                    }

                    if(strlen($xdata_fabricacao) == 0){
                        $xdata_fabricacao = "null";

                    }

                    if (strlen($serie) == 0) {
                        $serie = "null";
                        $xserie = "null";
                    } else {
                        $xserie = $serie;
                        $serie = "'". $serie ."'";
                    }

                    if (strlen($type) == 0) $type = "null";
                    else                    $type = "'".$type."'";

                    if (strlen($embalagem_original) == 0) $embalagem_original = "null";
                    else                                  $embalagem_original = "'".$embalagem_original."'";

                    if (strlen($sinal_de_uso) == 0) $sinal_de_uso = "null";
                    else                            $sinal_de_uso = "'".$sinal_de_uso."'";

                    if (count($msg_erro["msg"]) == 0) {

                        if (strlen($referencia) > 0) {
                            $referencia_original = $referencia;
                            $referencia = str_replace("-","",$referencia);
                            $referencia = str_replace(".","",$referencia);
                            $referencia = str_replace("/","",$referencia);
                            $referencia = str_replace(" ","",$referencia);

                            $referencia = strtoupper ($referencia);

                            $arrDadosProduto = valida_produto_pacific_lennox($referencia);

                            $produto = "";
                            $os_revenda = "";

                            if (count($arrDadosProduto["fabrica"]) > 1) {

                                if (empty($possui_codigo_interno)) {
                                    
                                    $msg_erro["msg"][] = "Informe se o produto {$referencia} possui código interno ou não <br />";

                                } else {

                                    if ($possui_codigo_interno == "nao") {

                                        $produto    = $arrDadosProduto["fabrica"][11]["produto"];
                                        $os_revenda = $arrFabricaOs["11"];
                                        $fabricaValida = 11;

                                    } else {

                                        $codigoInternoValido = false;
                                        foreach ($arrDadosProduto["fabrica"] as $fabricaId => $fabricaArr) {

                                            if ($fabricaArr["codigo_interno"] == $codigo_interno) {

                                                $os_revenda          = $arrFabricaOs[$fabricaId];
                                                $produto             = $fabricaArr["produto"];
                                                $codigoInternoValido = true;
                                                $fabricaValida       = $fabricaId;

                                            }

                                        }

                                        if (empty($codigo_interno)) {
                                            $msg_erro["msg"][] = traduz("Informe o código interno do produto %", null, null, [$referencia])."<br />";
                                        } else if (!$codigoInternoValido) {
                                            $msg_erro["msg"][] = traduz("Código interno informado no produto % inválido", null, null, [$referencia])."<br />";
                                        }

                                    }

                                }

                            } else if (count($arrDadosProduto["fabrica"]) == 1) {

                                if (!empty($codigo_interno)) {

                                    $codigoInternoValido = false;
                                    foreach ($arrDadosProduto["fabrica"] as $fabricaId => $fabricaArr) {

                                        if ($fabricaArr["codigo_interno"] == $codigo_interno) {

                                            $os_revenda          = $arrFabricaOs[$fabricaId];
                                            $produto             = $fabricaArr["produto"];
                                            $codigoInternoValido = true;
                                            $fabricaValida       = $fabricaId;

                                        }

                                    }

                                    if (!$codigoInternoValido) {
                                        $msg_erro["msg"][] = traduz("Código interno informado no produto % inválido", null, null, [$referencia])."<br />";
                                    }

                                } else {

                                    $fabArr = array_keys($arrDadosProduto["fabrica"]);

                                    $fabricaValida = $fabArr[0];
                                    $produto       = $arrDadosProduto["fabrica"][$fabArr[0]]["produto"];
                                    $os_revenda    = $arrFabricaOs[$fabArr[0]];

                                }

                            }

                            if (!empty($arrDadosProduto["msg_erro"])) {

                                $msg_erro["msg"][] = traduz("Produto não encontrado");

                            } else if (!empty($produto)) {

                                $sqlSerieObrigatorio = "SELECT numero_serie_obrigatorio
                                                        FROM tbl_produto
                                                        WHERE produto = {$produto}";
                                $resSerieObrigatorio = pg_query($con, $sqlSerieObrigatorio);

                                $numero_serie_obrigatorio = pg_fetch_result($resSerieObrigatorio, 0, "numero_serie_obrigatorio");

                                if($numero_serie_obrigatorio == "t" && ((strlen(trim($xserie)) == 0) || $xserie == "null")) {
                                    $msg_erro["msg"][] = traduz("O Produto % exige Número de Série", null, null, [$referencia_original]);
                                }

                                if (count($msg_erro["msg"]) == 0) { // HD 321132 - Inicio
                                    /* ===========INÍCIO DO PROCESSO DA IMAGEM =============== */
                                    //zambaa

                                    if ($anexaNotaFiscal) {
                                        $qt_anexo = 0;
                                        foreach($_FILES['foto_nf'] as $files){                                                                                                                                                  
                                            if(strlen($_FILES['foto_nf']['name'][$qt_anexo])==0){
                                                if ($login_fabrica == 81){
                                                    $msg_erro = 'Imagem da Nota Fiscal obrigatória.';
                                                }
                                                 continue;
                                            }
                                            $dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
                                            $dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
                                            $dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
                                            $dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
                                            $dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];

                                            $anexou = anexaNF("r_$os_revenda", $dados_anexo);

                                            if ($anexou !== 0) {
                                                 $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
                                            }

                                            $qt_anexo++;
                                        }
                                    }
                                }
                            }

                            if (strlen($capacidade) == 0) {
                                $xcapacidade = 'null';
                            } else {
                                $xcapacidade = "'".$capacidade."'";
                            }

                            if (count($msg_erro["msg"]) == 0) {
                                
                                $sql =  "INSERT INTO tbl_os_revenda_item (
                                            os_revenda         ,
                                            produto            ,
                                            nota_fiscal        ,
                                            data_nf            ,
                                            serie              ,
                                            type               ,
                                            rg_produto         ,
                                            qtde               ,
                                            embalagem_original ,
                                            sinal_de_uso
                                            $condicao_def_constatado_destinacao
                                            $condicao_data_fabricao
                                            $condicao_defeito_reclamado
                                        ) VALUES (
                                            $os_revenda           ,
                                            $produto              ,
                                            $xnota_fiscal         ,
                                            $xdata_nf             ,
                                            $serie                ,
                                            $type                 ,
                                            '$rg_produto'         ,
                                            $qtde                 ,
                                            $embalagem_original   ,
                                            $sinal_de_uso
                                            $value_def_constatado_destinacao 
                                            $condicao_data_fabricao_value
                                            $condicao_defeito_reclamado_value
                                        )";

                                $res = pg_query($con,$sql);

                                $fabricasExplode[$fabricaValida] = true;

                                $sql = "SELECT fn_valida_os_revenda($os_revenda, $posto, $fabricaValida)";
                                $res = @pg_exec ($con,$sql);

                                if (!empty(pg_errormessage($con))) {
                                    $msg_erro["msg"][] = pg_errormessage($con);
                                }

                                if(strlen(pg_last_error($con)) > 0){
                                    $msg_erro["msg"][] = pg_last_error($con);
                                }
                            }
                        }
                    }
                }

            if($tem_produto === false){
            	$msg_erro["msg"][] = traduz("Por favor, insira pelo menos um produto para a abertura de OS Revenda");
            }

                if(count($msg_erro["msg"]) == 0) {
                    $sql = "UPDATE tbl_revenda set fone = $xrevenda_fone, email = $xrevenda_email  where revenda = $revenda "; 
                    $res = pg_query($con,$sql);
                    
                    if(strlen(pg_last_error($con)) > 0){
                        $msg_erro["msg"][] = traduz("Erro ao gravar a OS de Revenda");
                    }

                    if(strlen(pg_last_error($con)) > 0){
                        $msg_erro["msg"][] = traduz("Erro ao gravar a OS de Revenda");
                    }
                }
            }
        }

        /* ===========FIM DO PROCESSO DA IMAGEM ================ */
        
        if (count($msg_erro["msg"]) == 0) {

            foreach ($arrFabricaOs as $fabricaId => $osRevendaId) {

                $sqlVerificaProduto = "SELECT os_revenda_item
                                       FROM tbl_os_revenda_item
                                       WHERE os_revenda = {$osRevendaId}";
                $resVerificaProduto = pg_query($con, $sqlVerificaProduto);

                if (pg_num_rows($resVerificaProduto) == 0) {

                    pg_query($con, "DELETE FROM tbl_os_revenda WHERE os_revenda = {$osRevendaId}");
                    unset($arrFabricaOs[$fabricaId]);

                }

            }

            foreach ($fabricasExplode as $idFabrica => $val) {

                $osRevenda = $arrFabricaOs[$idFabrica];

                $sql = "SELECT fn_explode_os_revenda($osRevenda, $idFabrica)";
                $res = pg_query ($con,$sql);
                if (!empty(pg_errormessage($con))) {
                    $msg_erro["msg"][] = pg_errormessage($con);
                }
            }
                    
            if (count($msg_erro["msg"]) == 0) {

                $osRevendaRedireciona = $arrFabricaOs[$login_fabrica];

                if (empty($osRevendaRedireciona)) {
                    $osRevendaRedireciona = $osRevenda;
                }

                $res = pg_exec($con, "COMMIT TRANSACTION");
                header ("Location: os_revenda_finalizada.php?os_revenda={$osRevendaRedireciona}");
                exit;
            } else {
                $res = pg_exec($con, "ROLLBACK TRANSACTION");
                if($cadastro_os) unset($os_revenda); 
            }

        } else {
            /* if (strpos (implode("|", $msg_erro["msg"]),"tbl_os_revenda_unico") > 0) $msg_erro["msg"][] = " O Número da Ordem de Serviço do Fabricante já Esta Cadastrado.";
            if (strpos (implode("|", $msg_erro["msg"]),"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro["msg"][] = "Data da abertura deve ser informada."; */
            $res = pg_exec($con, "ROLLBACK TRANSACTION");
            if($cadastro_os) unset($os_revenda); 
        }

    }
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
    if(strlen($os_revenda) > 0){
        $sql_delete_item = "DELETE FROM tbl_os_revenda_item
		USING tbl_os_revenda
		WHERE tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
		AND tbl_os_revenda_item.os_revenda = $os_revenda
		AND tbl_os_revenda.fabrica = $login_fabrica";
        $res_delete_item = pg_query($con, $sql_delete_item);

        $sql = "DELETE FROM tbl_os_revenda
                WHERE  tbl_os_revenda.os_revenda = $os_revenda
                AND    tbl_os_revenda.fabrica    = $login_fabrica";
        $res = pg_exec($con,$sql);

        $msg_erro = pg_errormessage($con);
        $msg_erro = substr($msg_erro,6);

        if (strlen($msg_erro) == 0) {
            header("Location: $PHP_SELF");
            exit;
        }
    }
}

if((count($msg_erro["msg"]) == 0) && (strlen($os_revenda) > 0)){
    
    if ($login_fabrica == 164) {
        $campo_estado = ", tbl_estado.estado AS revenda_estado";
        $join_estado  = " LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                          LEFT JOIN tbl_estado ON tbl_cidade.estado = tbl_estado.estado";   
    }

    // seleciona do banco de dados
    $sql = "SELECT  OS.sua_os                                                ,
                    OS.obs                                                   ,
                    OS.contrato                                              ,
                    to_char(OS.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
                    to_char(OS.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
                    RE.nome                                AS revenda_nome   ,
                    RE.cnpj                                AS revenda_cnpj   ,
                    RE.fone                                AS revenda_fone   ,
                    RE.email                               AS revenda_email  ,
                    PF.codigo_posto                        AS posto_codigo   ,
                    PO.nome                                AS posto_nome     ,
                    OS.campos_extra                        AS campo_extra
                    $campo_estado
            FROM       tbl_os_revenda   OS
            JOIN       tbl_revenda      RE ON OS.revenda = RE.revenda
            JOIN       tbl_fabrica      FA ON FA.fabrica = OS.fabrica
            LEFT JOIN tbl_posto         PO ON PO.posto   = OS.posto
            LEFT JOIN tbl_posto_fabrica PF ON PF.posto   = PO.posto AND   PF.fabrica = FA.fabrica
            $join_estado
            WHERE OS.os_revenda = $os_revenda
            AND   OS.fabrica    = $login_fabrica";
    $res = pg_exec($con, $sql);

    if (pg_numrows($res) > 0){
        $sua_os         = pg_result($res,0,sua_os);
        $data_abertura  = pg_result($res,0,data_abertura);
        $data_digitacao = pg_result($res,0,data_digitacao);
        $revenda_nome   = pg_result($res,0,revenda_nome);
        $revenda_cnpj   = pg_result($res,0,revenda_cnpj);
        $revenda_fone   = pg_result($res,0,revenda_fone);
        $revenda_email  = pg_result($res,0,revenda_email);
        $obs            = pg_result($res,0,obs);
        $posto_codigo   = pg_result($res,0,posto_codigo);
        $posto_nome     = pg_result($res,0,posto_nome);
        $contrato       = pg_result($res,0,contrato);
        $campo_extra      = pg_result($res,0,'campos_extra');
        if (!empty($campo_extra)) {
            $campo_extra = json_decode($campo_extra, true);
            $revenda_fantasia = $campo_extra['revenda_fantasia'];
        }

        if ($login_fabrica == 164) {
            $revenda_estado   = pg_result($res,0,'revenda_estado');
        }

        $sql = "SELECT *
                FROM   tbl_os
                WHERE  fabrica = $login_fabrica
                AND (
                       tbl_os.sua_os = '$sua_os'         OR tbl_os.sua_os = '0$sua_os'
                    OR tbl_os.sua_os = '00$sua_os'       OR tbl_os.sua_os = '000$sua_os'
                    OR tbl_os.sua_os = '0000$sua_os'     OR tbl_os.sua_os = '00000$sua_os'
                    OR tbl_os.sua_os = '000000$sua_os'   OR tbl_os.sua_os = '0000000$sua_os'
                    OR tbl_os.sua_os = '00000000$sua_os'
                    OR tbl_os.sua_os = '$sua_os-01'      OR tbl_os.sua_os = '$sua_os-02'
                    OR tbl_os.sua_os = '$sua_os-03'      OR tbl_os.sua_os = '$sua_os-04'
                    OR tbl_os.sua_os = '$sua_os-05'      OR tbl_os.sua_os = '$sua_os-06'
                    OR tbl_os.sua_os = '$sua_os-07'      OR tbl_os.sua_os = '$sua_os-08'
                    OR tbl_os.sua_os = '$sua_os-09'      OR ";

        $suas_oss = "";
        for ($x=1;$x<=300;$x++) {
            $suas_oss .= "tbl_os.sua_os = '$sua_os-$x' OR ";
        }
        $sql .= $suas_oss;


        $sql .= "tbl_os.sua_os = '0$sua_os-01' OR
                 tbl_os.sua_os = '0$sua_os-02' OR
                 tbl_os.sua_os = '0$sua_os-03' OR
                 tbl_os.sua_os = '0$sua_os-04' OR
                 tbl_os.sua_os = '0$sua_os-05' OR
                 tbl_os.sua_os = '0$sua_os-06' OR
                 tbl_os.sua_os = '0$sua_os-07' OR
                 tbl_os.sua_os = '0$sua_os-08' OR
                 tbl_os.sua_os = '0$sua_os-09' OR ";

        $suas_oss = "";
        for ($x=1;$x<=40;$x++) {
            $suas_oss .= " tbl_os.sua_os = '0$sua_os-$x' OR ";
        }
        $sql .= $suas_oss;


        $sql .= "tbl_os.sua_os = '00$sua_os-01' OR
                 tbl_os.sua_os = '00$sua_os-02' OR
                 tbl_os.sua_os = '00$sua_os-03' OR
                 tbl_os.sua_os = '00$sua_os-04' OR
                 tbl_os.sua_os = '00$sua_os-05' OR
                 tbl_os.sua_os = '00$sua_os-06' OR
                 tbl_os.sua_os = '00$sua_os-07' OR
                 tbl_os.sua_os = '00$sua_os-08' OR
                 tbl_os.sua_os = '00$sua_os-09' OR ";

        $suas_oss = "";
        for ($x=1;$x<=40;$x++) {
            $suas_oss .= "tbl_os.sua_os = '00$sua_os-$x' OR ";
        }
        $sql .= $suas_oss;

        $sql .= "tbl_os.sua_os = '000$sua_os-01' OR
                 tbl_os.sua_os = '000$sua_os-02' OR
                 tbl_os.sua_os = '000$sua_os-03' OR
                 tbl_os.sua_os = '000$sua_os-04' OR
                 tbl_os.sua_os = '000$sua_os-05' OR
                 tbl_os.sua_os = '000$sua_os-06' OR
                 tbl_os.sua_os = '000$sua_os-07' OR
                 tbl_os.sua_os = '000$sua_os-08' OR
                 tbl_os.sua_os = '000$sua_os-09' OR ";

        $suas_oss = "";
        for ($x=1;$x<=40;$x++) {
            $suas_oss .= "tbl_os.sua_os = '000$sua_os-$x' OR ";
        }
        $sql .= $suas_oss;

        //apenas para terminar o OR
        $sql .= "tbl_os.sua_os = '000$sua_os-40'";


            $sql .= ")
                    ";

        $resX = @pg_exec($con, $sql);

        if (@pg_numrows($resX) == 0) $exclui = 1;

        $sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
                        to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
                FROM    tbl_os_revenda_item
                JOIN    tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
                WHERE   tbl_os_revenda.os_revenda = $os_revenda
                AND     tbl_os_revenda.fabrica    = $login_fabrica
                AND     tbl_os_revenda_item.nota_fiscal NOTNULL
                AND     tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
        $res = pg_exec($con, $sql);

        if (pg_numrows($res) > 0){
            $nota_fiscal = pg_result($res,0,nota_fiscal);
            $data_nf     = pg_result($res,0,data_nf);
        }
    } else {
        header('Location: os_revenda.php?msg=Gravado com Sucesso!');
        exit;
    }
}
$msg = $_GET['msg'];

$title          = traduz("CADASTRO DE ORDEM DE SERVIÇO - REVENDA");

if($login_fabrica <> 108 and $login_fabrica <> 111){
    $layout_menu = "callcenter";
} else {
    $layout_menu = "gerencia";
}

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

if($login_fabrica == 86) {
?>
    <script type="text/javascript">
        $(function(){
            $('#nota_fiscal').numeric();
        });
    </script>
<?php
}
?>

<script type="text/javascript">

$(document).ready(function() {
    Shadowbox.init();
    $("input[type=file]").change(function(){
        var tamanho = $(this).prop('files')[0]['size'];

        if (parseInt(tamanho) > 2097152) {
            alert('<?=traduz("Anexo não será aceito pois é maior que 2MB")?>');
            $(this).val("");
        }
    });
});

function fnc_pesquisa_revenda (campo, tipo) {
    var url = "";
    if (tipo == "nome") {
        url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
    }
    if (tipo == "cnpj") {
        url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
    }
    if(campo.value!=""){
        janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
        janela.nome         = document.frm_os.revenda_nome;
        janela.cnpj         = document.frm_os.revenda_cnpj;
        janela.fone         = document.frm_os.revenda_fone;
        janela.cidade       = document.frm_os.revenda_cidade;
        janela.estado       = document.frm_os.revenda_estado;
        janela.endereco     = document.frm_os.revenda_endereco;
        janela.numero       = document.frm_os.revenda_numero;
        janela.complemento  = document.frm_os.revenda_complemento;
        janela.bairro       = document.frm_os.revenda_bairro;
        janela.cep          = document.frm_os.revenda_cep;
        janela.email        = document.frm_os.revenda_email;
        janela.focus();
    }
    else{
        alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
        Abre janela com resultado da pesquisa de Produtos pela
        referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, tipo, posicao) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&posicao=" + posicao ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia   = campo;
        janela.descricao    = campo2;

        janela.focus();

    }
    else{
        alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }
}


function fnc_pesquisa_produto_serie (campo,campo2,campo3) {
    if (campo3.value != "") {
        var url = "";
        url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.referencia   = campo;
        janela.descricao    = campo2;
        janela.serie    = campo3;
        janela.focus();
    }
    else{
        alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }
}


/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
        Formata o Campo de CNPJ a medida que ocorre a digitação
        Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
    var mycnpj = '';
        mycnpj = mycnpj + cnpj;
        myrecord = "revenda_cnpj";
        myform = form;

        if (mycnpj.length == 2){
            mycnpj = mycnpj + '.';
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 6){
            mycnpj = mycnpj + '.';
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 10){
            mycnpj = mycnpj + '/';
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
        if (mycnpj.length == 15){
            mycnpj = mycnpj + '-';
            window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
        }
}

//INICIO DA FUNCAO DATA
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

//Coloca NF
var ok = false;
function TodosNF() {
    f = document.frm_os;
    if (!ok) {
        for (i=0; i<<?echo $qtde_item?>; i++){
            myREF = "produto_referencia_" + i;
            myNF  = "produto_nf_0";
            myNFF = "produto_nf_" + i;
            if ((f.elements[myREF].type == "text") && (f.elements[myREF].value != "")){
                f.elements[myNFF].value = f.elements[myNF].value;
                //alert(i);
            }
            ok = true;
        }
    } else {
        for (i=1; i<<?echo $qtde_item?>; i++){
            myNFF = "produto_nf_" + i;
            f.elements[myNFF].value = "";
        }
        ok = false;
    }

}

<? if($login_fabrica == 14 OR $login_fabrica == 30) {?>
    function char(nota_fiscal){
        try{var element = nota_fiscal.which }catch(er){};
        try{var element = event.keyCode }catch(er){};
        if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
        return false
    }
    window.onload = function(){
        document.getElementById('nota_fiscal').onkeypress = char;
    }
<? }?>

<?php
if (in_array($login_fabrica, [11, 172])) {
?>
    $(function(){

        $("input[name^=possui_codigo_interno]").click(function(){

            let posicao = $(this).data("posicao");

            if ($(this).val() == 'sim') {
                $("input[name=codigo_interno_"+posicao+"]").show();
            } else {
                $("input[name=codigo_interno_"+posicao+"]").hide().text("");
            }

        });

    });
<?php
}
?>

$(function() {
        $.datepickerLoad(Array("data_abertura", "data_nf"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        for (var i = 0; i < document.getElementById('qtde_item').value; i++) {
            var nome = '#data_nf_' + i;
            $(nome).datepicker({startdate:'01/01/2000'});
            $(nome).mask("99/99/9999");
        }
    });

    function VerificaBloqueioRevenda(cnpj, fabrica){
      $.ajax({
          type: "POST",
          datatype: 'json',
          url: "./ajax_verifica_bloquei_revenda.php",
          data: {VerificaBloqueioRevenda: true, cnpj:cnpj, fabrica:fabrica},
          cache: false,
          success: function(retorno){
              var dados = $.parseJSON(retorno);
              if(dados.retorno.length > 0){
                alert(dados.retorno);
              }
          }
      });
    }

    function retorna_revenda(retorno) {
        $("#revenda_nome").val(retorno.razao);
	$("#revenda_cnpj").val(retorno.cnpj);
	$("#revenda_fone").val(retorno.fone);
	$("#revenda_email").val(retorno.email);
    
    <?php if ($login_fabrica == 164) { ?>
        $("#revenda_estado").val(retorno.estado);
    <?php } ?>
    
    <?php if($login_fabrica == 35){ ?>
            VerificaBloqueioRevenda(retorno.cnpj, <?=$login_fabrica?>);
        <?php } ?>
    }

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function pesquisaNumeroSerie(serie, produto, posicao){
        var serie = jQuery.trim(serie.value);

        if (serie.length > 2){
            Shadowbox.open({
                content:    "produto_serie_pesquisa_nv.php?serie="+serie+"&posicao="+posicao,
                player: "iframe",
                title:      '<?=traduz("Pesquisa Número de Serie")?>',
                width:  800,
                height: 500
            });
        }else
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }

    function retorna_numero_serie(produto,referencia,descricao, posicao,cnpj,nome,fone,email,serie,data_fabricacao){
        var data = data_fabricacao.split('-');
        data_fabricacao = data[2]+'/'+data[1]+'/'+data[0];
        gravaDados("produto_referencia_"+posicao,referencia);
        gravaDados("produto_descricao_"+posicao,descricao);
        gravaDados("produto_descricao_"+posicao,descricao);
        <?php if($login_fabrica <> '3') { ?>
            gravaDados("produto_serie_"+posicao,serie);
        <?php } ?>
        gravaDados("data_fabricacao_"+posicao,data_fabricacao);
        <?php if($login_fabrica == '50') { ?>
            gravaDados("revenda_cnpj",cnpj);
            gravaDados("revenda_nome",nome);
            gravaDados("revenda_fone",fone);
            gravaDados("revenda_email",email);
        <?php } ?>

    }

    function gravaDados(name, valor){
        try {
            $("input[name="+name+"]").val(valor);
        } catch(err){
            return false;
        }
    }

    function busca_defeito_reclamado(produto, posicao){

    	$("input[name='produto_hidden_"+posicao+"']").val(produto);

    	$.ajax({
    		url: "os_revenda.php",
    		type: "post",
    		data: {
    			busca_defeito_reclamado: true,
  				produto: produto
    		},
    		complete: function(data){

    			var options = data.responseText;

    			$("select[name='defeito_reclamado_"+posicao+"']").html(options);

    		}
    	});

    }

    function busca_defeito_constatado(produto, posicao){

        $.ajax({
            url: "os_revenda.php",
            type: "post",
            data: {
                busca_defeito_constatado: true,
                produto: produto
            },
            complete: function(data){

                var options = data.responseText;

                $("select[name='defeito_constatado_"+posicao+"']").html(options);

            }
        });
    }

</script>


<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->
<? if ($ip <> "189.47.44.88" AND 1==2) { ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
    <tr>
        <td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz('ATENÇÃO')?>: <br><br> <?=traduz('A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO')?>.</font></td>
    </tr>
</table>

<? exit; ?>

<? } ?>

<br>
    <? if(count($msg_erro["msg"]) > 0){

        ?>
            <div class='alert alert-danger'>
                <h4>
                    <?php 
                    echo implode("<br />", $msg_erro["msg"]);
                    ?>
                </h4>
            </div>
    <? } ?>
        <? if(strlen($msg)>0){ ?>
            <div class='alert alert-success'>
                <h4>
                    <? echo $msg ?>
                </h4>
            </div>
    <? } ?>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form class='form-search form-inline tc_formulario' name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">

    <div class='titulo_tabela'><?=traduz('Cadastrar OS Revenda')?></div>
            <!-- Formulário -->
            <input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
            <input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>

            <? if ($pedir_sua_os == 't') { ?>
            <div class='row-fluid'>
            <div class='span1'></div>
                <div class='span5'>
                    <div class='control-group <?=(in_array("os_fabricante", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('OS Fabricante')?></label>
                        <div class='controls controls-row'>         
                            <input  name="sua_os" class="frm" type="text" <?if ($login_fabrica==5) { echo " maxlength='6' ";} else { echo " maxlength='10' ";}?> value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
                        </div>
                    </div>      
                </div>
            <div class='span6'></div>   
            </div>  
                    <? } ?>
        <br />          
        <div class='row-fluid'>
            <div class='span1'></div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Data Abertura')?></label>
                        <div class='controls controls-row'>
                            <div class='span2'>                 
                    <?
                        if($login_fabrica == 104){
                            if(empty($data_abertura)){
                                $data_abertura = date("d/m/Y");
                            }
                    ?>
                            <h5 class="asteristico">*</h5>
                            <input class="inptc7" name="data_abertura" id="data_abertura" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" readonly >
                    <?
                        }else{
                    ?>
                            <h5 class="asteristico">*</h5>
                            <input class="inptc7" name="data_abertura" rel="data" id="data_abertura" maxlength="10" value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0">
                    <?
                        }
                    ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Nota Fiscal')?></label>
                        <div class='controls controls-row'>
                            <div class='span2'>
                                <h5 class="asteristico">*</h5>
                                <input name="nota_fiscal" id="nota_fiscal" maxlength="20" value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span3'>
                    <div class='control-group <?=(in_array("data_nota", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Data Nota')?></label>
                        <div class='controls controls-row'>
                            <div class='span2'> 
                                <h5 class="asteristico">*</h5>          
                                <input class="inptc7" name="data_nf" id="data_nf" maxlength="10" value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" >
                            </div>
                        </div>
                    </div>
                </div>      
            <div class='span1'></div>
        </div>
        <div class='row-fluid'>
            <div class='span1'></div>
                <div class='span5'>
                <div class='control-group input-append <?=(in_array("revenda", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('CNPJ Revenda')?></label>
                        <div class='controls controls-row'>
                            <h5 class="asteristico">*</h5> 
                            <?php 

                            $mascara_cnpj = "formata_cnpj(this.value, 'frm_os')";

                            if (in_array($login_fabrica,[180, 181, 182])) {
                                $mascara_cnpj = "";
                            } 

                            ?> 
                            <input class="frm" type="text" id="revenda_cnpj" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onKeyUp="<?php echo $mascara_cnpj ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')" <? } ?>><span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
                        </div>
                    </div>      
                </div>
                <div class='span5'>
                    <div class='control-group input-append <?=(in_array("revenda", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Nome Revenda')?></label>
                        <div class='controls controls-row'>
                            <h5 class="asteristico">*</h5>  
                            <input class="frm" type="text" id="revenda_nome" name="revenda_nome" size="50" maxlength="50" value="<? echo $revenda_nome ?>"  <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')" <? } ?>><span class="add-on" rel="lupa" ><i class="icon-search"></i></span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
                        </div>
                    </div>
                </div>
            <div class='span1'></div>   
        </div>
         <?php if ($login_fabrica == 164) { ?>
                    <div class='row-fluid'>
                        <div class='span1'></div>
                            <div class='span1'>
                                <div class='control-group input-append'>
                                    <label class='control-label' for=''>UF Revenda</label>
                                    <div class='controls controls-row'>
                                        <input class="frm" readonly style="width: 50%;" type="text" id="revenda_estado" name="revenda_estado" size="2" value="<? echo $revenda_estado ?>">
                                    </div>
                                </div>      
                            </div>
                            <div class='span4'></div>
                            <div class='span5'>
                                <div class='control-group input-append'>
                                    <label class='control-label' for=''>Nome Fantasia / Transferência</label>
                                    <div class='controls controls-row'> 
                                        <input class="frm" type="text" id="revenda_fantasia" name="revenda_fantasia" size="50" value="<? echo $revenda_fantasia ?>">
                                    </div>
                                </div>      
                            </div>
                        <div class='span1'></div>                       
                    </div>   
        <?php } ?>
        <div class='row-fluid'>
            <div class='span1'></div>
                <div class='span5'>
                    <div class='control-group <?=(in_array("fone_revenda", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Fone Revenda')?></label>
                        <div class='controls controls-row'>         
                            <input class="frm" type="text" name="revenda_fone" id="revenda_fone" size="15"  maxlength="20"  value="<? echo $revenda_fone ?>" >
                        </div>
                    </div>      
                </div>
                <div class='span5'>
                    <div class='control-group <?=(in_array("email_revenda", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('E-mail Revenda')?></label>
                        <div class='controls controls-row'>         
                            <input class="frm" type="text" name="revenda_email" size="50" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
                        </div>
                    </div>      
                </div>

            <input type="hidden" name="revenda_cidade" value="">
            <input type="hidden" name="revenda_estado" value="">
            <input type="hidden" name="revenda_endereco" value="">
            <input type="hidden" name="revenda_cep" value="">
            <input type="hidden" name="revenda_numero" value="">
            <input type="hidden" name="revenda_complemento" value="">
            <input type="hidden" name="revenda_bairro" value="">        

            <div class='span1'></div>           
        </div>              
        <div class='row-fluid'>
            <div class='span1'></div>
                <div class='span5'>
                    <div class='control-group input-append <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Código do Posto')?></label>
                        <div class='controls controls-row'>
                            <h5 class="asteristico">*</h5>  
                            <input class="frm" type="text" id="codigo_posto" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" <? } ?>><span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        </div>
                    </div>      
                </div>
                <div class='span5'>
                    <div class='control-group input-append <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for=''><?=traduz('Nome do Posto')?></label>
                        <div class='controls controls-row'> 
                            <h5 class="asteristico">*</h5>      
                            <input class="frm" type="text" id="descricao_posto" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" <? } ?>><span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>      
                </div>
            <div class='span1'></div>                       
        </div>                  

<?
    if($login_fabrica == 7){
?>  <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span5'>
                    <div class='control-group'>
                        <label class='control-label' for=''><?=traduz('Contrato')?></label>
                        <div class='controls controls-row'>         
                            <input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
                        </div>
                    </div>      
                </div>
                <div class='span5'></div>
                <div class='span1'></div>
    </div>          
                        
<?
    }
?>
<?
    if ($login_fabrica == 3) {
        $sql = "SELECT COUNT(*) FROM tbl_os_revenda_item WHERE os_revenda = $os_revenda";
        $res = pg_query($con, $sql);

        if (empty($qtde_linhas))
            $qtde_linhas = pg_result($res,0,0);

        if (!empty($os_revenda) && !empty($qtde_linhas))
            $qtde_item = $qtde_linhas + 3;
        if ($qtde_item > 40)
            $qtde_item = 40;
    }
?>
            <div class='row-fluid'>
                <div class='span1'></div>
                <div class='span5'>
                    <div class='control-group'>
                        <label class='control-label' for=''><?=traduz('Observações')?></label>
                        <div class='controls controls-row'>         
                            <input class="frm" type="text" name="obs" size="84" value="<? echo $obs ?>">
                        </div>
                    </div>      
                </div>
                <div class='span5'>     
                    <div class='control-group'>
                        <label class='control-label' for=''><?=traduz('Qtde. Linhas')?></label>
                        <div class='controls controls-row'>         
                        <select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit();">
                            <option value='20' <? if ($qtde_linhas <= 20) echo 'selected'; ?>>20</option>
                            <option value='30' <? if ($qtde_linhas > 20 && $qtde_linhas <= 30) echo 'selected'; ?>>30</option>
                            <option value='40' <? if ($qtde_linhas > 30 && $qtde_linhas <= 40) echo 'selected'; ?>>40</option>
                        </select>
                        </div>
                    </div>      
                </div>
                <div class='span1'></div>       
            </div>              

    <?php 
    if ($fabricaFileUploadOS) {
        $boxUploader = array(
            "div_id" => "div_anexos",
            "prepend" => $anexo_prepend,
            "context" => "os",
            "unique_id" => $tempUniqueId,
            "hash_temp" => $anexoNoHash,
            "reference_id" => $tempUniqueId
        );

        include "box_uploader.php";

    } else if ($anexaNotaFiscal) {
        $temImg = temNF("r_$os_revenda", 'count');
        
        if (($anexa_duas_fotos and $temImg < LIMITE_ANEXOS) or $temImg == 0) { 
        ?>
            
        <div class='row-fluid'>
            <div class='span1'></div>

            <div class='span5'>
                <div class='control-group <?=(in_array("foto_nf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for=''><?=traduz('Imagem da Nota Fiscal')?></label>
                    <div class='controls controls-row'>         
                        <?=$inputNotaFiscal?>
                    </div>
                </div>      
            </div>

        </div>

    <?php } } ?>

    <br />

    </div>  

<br>

<?
if (strlen($os_revenda) > 0) {
    $sql = "SELECT      tbl_produto.produto
            FROM        tbl_os_revenda_item
            JOIN        tbl_produto   USING (produto)
            JOIN        tbl_os_revenda USING (os_revenda)
            WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
            ORDER BY    tbl_os_revenda_item.os_revenda_item";
    $res_os = pg_exec($con,$sql);
}

if($login_fabrica == 50){

    $sql_dr = "SELECT defeito_reclamado, descricao FROM tbl_defeito_reclamado WHERE fabrica = {$login_fabrica} AND ativo";
    $res_dr = pg_query($con, $sql_dr);

}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

for ($i=0; $i<$qtde_item; $i++) {
    if ($i % 20 == 0) {
        #if ($i > 0) {
        #   echo "<tr>";
        #   echo "<td colspan='5'>";
        #   echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

        #   if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
        #       echo "<img src='imagens_admin/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
        #   }

        #   echo "</td>";
        #   echo "</tr>";
        #   echo "</table>";
        #}

        echo "<table class='table table-striped table-bordered table-large'>";
        echo "<tr class='titulo_coluna'>";
        if($login_fabrica != 151 and $login_fabrica != 162){
            echo "<th align='center'>";
                if($login_fabrica==35){
                    echo "PO#";
                } else {

                    if($login_fabrica == 160 or $replica_einhell){
                        echo "Nº Lote";
                    }else{
                        echo traduz("Número Série");
                    }
                }
            echo "</th>";
        }
        if($login_fabrica  == 160 or $replica_einhell){
            echo "<th align='center'>".traduz("Versão Produto")."</th>";
        }

        if (in_array($login_fabrica, [11,172])) {
            echo "<th align='center'>".traduz("Possui código interno?")."</th>";
            echo "<th align='center'>".traduz('Código Interno')."</th>";
        }

        echo "<th align='center'>".traduz("Produto")."</th>";
        echo "<th align='center'>".traduz("Descrição do Produto")."</th>";

        if($login_fabrica == 162){
            echo "<th align='center'>".traduz("Número de Série")."</th>";
            echo "<th align='center'>".traduz("IMEI")."</th>";
        }

        if($login_fabrica == 164){
            echo "<th>".traduz("Defeito Constatado")."</th>";
            echo "<th>".traduz("Destinação")."</th>";
        }

        #       echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da NF</font></td>";
        #       echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número da NF</font> <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosNF()\" ALT='Selecionar todas' style='cursor:pointer;'></td>";
        if ($login_fabrica == 1) {
            echo "<th align='center'>".traduz("Type")."</th>";
            echo "<th align='center'>".traduz("Embalagem Original")."</th>";
            echo "<th align='center'>".traduz("Sinal de Uso")."</th>";
        }
        //MUDAR PARA DYNACOM
        if ($login_fabrica == 10) {
            echo "<th align='center'>".traduz("Data NF")."</th>";
            echo "<th align='center'>".traduz("Número NF ")."</th>";
        }
        if ($login_fabrica == 74) {
            echo "<th align='center'>".traduz("Data Fabricação")."</th>";
        }

        if (in_array($login_fabrica, array(121,151))) {
            echo "<th align='center'>".traduz("QTDE")."</th>";
        }

        ##hd_chamado=2705567##
        if(in_array($login_fabrica, array(50,94))){
            echo "<th align='center'>".traduz("Defeito Reclamado")."</th>";
        }
        ## FIM hd_chamado=2705567##
        echo "</tr>";
    }

    $qtde = "";

    if (strlen($os_revenda) > 0){
        if (@pg_numrows($res_os) > 0) {
            $produto = trim(@pg_result($res_os,$i,produto));
        }

        if(strlen($produto) > 0){
            // seleciona do banco de dados
            $sql =  "SELECT tbl_os_revenda_item.os_revenda_item                          ,
                            tbl_os_revenda_item.produto                                  ,
                            tbl_os_revenda_item.serie                                    ,
                            tbl_os_revenda_item.nota_fiscal                              ,
                            to_char(tbl_os_revenda_item.data_nf,'DD/MM/YYYY') AS data_nf ,
                            tbl_os_revenda_item.capacidade                               ,
                            tbl_os_revenda_item.defeito_reclamado                        ,
                            tbl_os_revenda_item.type                                     ,
                            tbl_os_revenda_item.embalagem_original                       ,
                            tbl_os_revenda_item.sinal_de_uso                             ,
                            tbl_os_revenda_item.rg_produto,
                            tbl_os_revenda_item.qtde                                     ,
                            tbl_produto.referencia                                       ,
                            tbl_produto.descricao                                        ,
                            tbl_os_revenda_item.defeito_constatado_descricao
                    FROM    tbl_os_revenda
                    JOIN    tbl_os_revenda_item
                    ON      tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
                    JOIN    tbl_produto
                    ON      tbl_produto.produto = tbl_os_revenda_item.produto
                    WHERE   tbl_os_revenda_item.os_revenda = $os_revenda";
            $res = pg_exec($con, $sql);

            if (@pg_numrows($res) == 0) {
                $novo               = 't';
                $os_revenda_item    = $_POST["item_".$i];
                $referencia_produto = $_POST["produto_referencia_".$i];
                $serie              = $_POST["produto_serie_".$i];
                if($login_fabrica == 160 or $replica_einhell){
                    $versao_produto = $_POST["versao_produto_".$i];
                }
                if($login_fabrica == 162){
                    $imei = $_POST["imei_".$i];
                }
				$produto_descricao           = $_POST["produto_descricao_".$i];
				#               $nota_fiscal = $_POST["produto_nf_".$i];
				#               $data_nf     = $_POST["data_nf_".$i];
				$capacidade                  = $_POST["produto_capacidade_".$i];
				$type                        = $_POST["type_".$i];
				$embalagem_original          = $_POST["embalagem_original_".$i];
				$sinal_de_uso                = $_POST["sinal_de_uso_".$i];
				$data_fabricacao             = $_POST['data_fabricacao_'.$i];
				$defeito_reclamado           = $_POST['defeito_reclamado_'.$i];
				$defeito_reclamado_item      = $_POST['defeito_reclamado_'.$i];
            } else {

                $novo               = 'f';
                $os_revenda_item    = pg_result($res,$i,os_revenda_item);
                $produto_item       = pg_result($res,$i,produto);

                $referencia_produto = pg_result($res,$i,referencia);
                $produto_descricao  = pg_result($res,$i,descricao);
                $serie              = pg_result($res,$i,serie);
                if($login_fabrica == 160 or $replica_einhell){
                    $versao_produto = pg_result($res,$i,type);
                }

                if($login_fabrica == 162){
                    $imei = pg_result($res,$i,rg_produto);
                }

				$nota_fiscal             = pg_result($res,$i,nota_fiscal);
				#               $data_nf = pg_result($res,$i,data_nf);
				$capacidade              = pg_result($res,$i,capacidade);
				$type                    = pg_result($res,$i,type);
				$embalagem_original      = pg_result($res,$i,embalagem_original);
				$sinal_de_uso            = pg_result($res,$i,sinal_de_uso);
				$qtde                    = pg_result($res,$i,qtde);
				$defeito_reclamado       = pg_fetch_result($res, $i, 'defeito_constatado_descricao');
				$defeito_reclamado_item  = pg_fetch_result($res, $i, "defeito_reclamado");

                if($login_fabrica == 164){
                    $defeito_constatado_descricao  = pg_result($res,$i,defeito_constatado_descricao);
                    $destinacao =  pg_result($res,$i,rg_produto);
                }
            }
        } else {

            $produto_item = "";
            $novo = 't';
            $os_revenda_item    = $_POST["item_".$i];
            $referencia_produto = $_POST["produto_referencia_".$i];

            $serie              = $_POST["produto_serie_".$i];
            if($login_fabrica == 160 or $replica_einhell){
                $versao_produto = pg_result($res,$i,type);
            }
            if($login_fabrica == 162){
                $imei =  $_POST["imei_".$i];
            }
            if($login_fabrica == 164){
                $destinacao = $_POST["destinacao_".$i];
            }
			$produto_descricao       = $_POST["produto_descricao_".$i];
			#           $nota_fiscal = $_POST["produto_nf_".$i];
			#           $data_nf     = $_POST["data_nf_".$i];
			$capacidade              = $_POST["produto_capacidade_".$i];
			$type                    = $_POST["type_".$i];
			$embalagem_original      = $_POST["embalagem_original_".$i];
			$sinal_de_uso            = $_POST["sinal_de_uso_".$i];
			$data_fabricacao         = $_POST['data_fabricacao_'.$i];
			$defeito_reclamado       = $_POST['defeito_reclamado_'.$i];
			$defeito_reclamado_item  = $_POST["defeito_reclamado_".$i];
        }
    } else {
        $novo               = 't';
        $os_revenda_item    = $_POST["item_".$i];
        $referencia_produto = $_POST["produto_referencia_".$i];
        $serie              = $_POST["produto_serie_".$i];
        if($login_fabrica == 164){
            $defeito_constatado_descricao = $_POST["defeito_constatado_".$i];
            $destinacao = $_POST["destinacao_".$i];
        }
        
        if($login_fabrica == 160 or $replica_einhell){
            $versao_produto = pg_result($res,$i,type);
        }
        if($login_fabrica == 162){
            $imei =  $_POST["imei_".$i];
        }
		$produto_descricao      = $_POST["produto_descricao_".$i];
		#       $nota_fiscal    = $_POST["produto_nf_".$i];
		#       $data_nf        = $_POST["data_nf_".$i];
		$capacidade             = $_POST["produto_capacidade_".$i];
		$type                   = $_POST["type_".$i];
		$embalagem_original     = $_POST["embalagem_original_".$i];
		$sinal_de_uso           = $_POST["sinal_de_uso_".$i];
		$data_fabricacao        = $_POST['data_fabricacao_'.$i];
		$defeito_reclamado      = $_POST['defeito_reclamado_'.$i];
		$defeito_reclamado_item = $_POST["defeito_reclamado_".$i];


    }

    echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
    echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

    echo "<tr "; if ($linha_erro == $i AND strlen($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo "bgcolor='#D9E2EF'>\n";
    if($login_fabrica != 151 and $login_fabrica != 162){
        echo "<td align='center' nowrap><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20' value='$serie'"; if ($login_fabrica == 5) echo " onblur=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\""; echo ">&nbsp;";
    }
    if($login_fabrica == 74){
        echo "<span class='add-on' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\" style='cursor:pointer;'><i class='icon-search'></i></span>";
    } else if($login_fabrica != 151 and $login_fabrica != 162){
        echo "<span class='add-on' src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\" style='cursor:pointer;'><i class='icon-search'></i></span>";
    }
    echo "</td>\n";

    if($login_fabrica == 160 or $replica_einhell){
        echo "<td align='center'>
                <input class='frm' type='text' name='versao_produto_$i' size='15' maxlength='10' value='$versao_produto'>
            </td>\n";
    }

    if (in_array($login_fabrica, [11,172])) { 

        $exibeCheckboxInterno = "f";

        $displayPergunta = "none";

        if (!empty($referencia_produto)) {

            $arrDadosProduto = valida_produto_pacific_lennox($referencia_produto);

            if (count($arrDadosProduto["fabrica"]) > 1) {

                $displayPergunta = "block";

            }

        }

        $displayCodigo = "";

        if ($_POST['possui_codigo_interno_'.$i] == "nao") {
            $displayCodigo = "hidden";
        }

        ?>
        <td align="center">
            <div id='botoes_sim_nao_<?= $i ?>' style='display: <?= $displayPergunta ?>'>
                <label style='font-weight: bolder;color: darkgreen;cursor: pointer;'>
                    <input type='radio' data-posicao='<?= $i ?>' name='possui_codigo_interno_<?= $i ?>' value='sim' <?= ($_POST['possui_codigo_interno_'.$i] == 'sim') ? "checked" : "" ?> /> Sim
                </label>
                <label style='font-weight: bolder;color: darkred;cursor: pointer;'>
                    <input type='radio' data-posicao='<?= $i ?>' name='possui_codigo_interno_<?= $i ?>' value='nao' <?= ($_POST['possui_codigo_interno_'.$i] == 'nao') ? "checked" : "" ?> /> Não
                </label>
            </div>
        </td>
        <td align="center">
            <input type="text" name="codigo_interno_<?= $i ?>" value="<?= $_POST['codigo_interno_'.$i] ?>" class="frm" <?= $displayCodigo ?> />
        </td>
    <?php
    }

    echo "<td align='center' nowrap><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'";  if ($login_fabrica == 5) echo " onblur='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\")'"; 
    echo ">&nbsp;<span class='add-on' src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, \"referencia\", $i)' style='cursor:pointer;'><i class='icon-search'></span</td>\n";
    echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_$i' size='35' maxlength='50' value='$produto_descricao'";  if ($login_fabrica == 5) echo " onblur='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\")'"; 
    echo ">&nbsp;<span class='add-on' src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, \"descricao\", $i)' style='cursor:pointer;'><i class='icon-search'></span></td>\n";
#   echo "<td align='center'><input class='frm' type='text' name='data_nf_$i'  size='12'  maxlength='10'  value='$data_nf'></td>";
#   echo "<td align='center'><input class='frm' type='text' name='produto_nf_$i' size='9' maxlength='20' value='$nota_fiscal'>";

    if($login_fabrica == 162){
        echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20' value='$serie'"; if ($login_fabrica == 5) echo ">&nbsp;";
        echo "</td>";
        echo "<td align='center'><input class='frm' type='text' name='imei_$i' id='imei_$i'  size='15'  maxlength='18' value='$imei' $prop_readonly >";
        echo "</td>";
    }

    if($login_fabrica == 164){
        echo "<td>";
            echo "<select name='defeito_constatado_$i' id='defeito_constatado_$i' data-posicao='$i'>";
                echo "<option value=''></option>";

                if(isset($_POST) or strlen($produto_item)>0){

                    if(strlen($produto_item)>0 ){
                        $cond_produto = " AND tbl_produto.produto = {$produto_item} ";
                    }else{
                        $cond_produto = " AND tbl_produto.referencia = '$referencia_produto' ";
                    }
                    $sql = "SELECT DISTINCT
                                tbl_defeito_constatado.descricao,
                                tbl_defeito_constatado.defeito_constatado                
                                FROM tbl_diagnostico
                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                                JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                                $cond_produto
                                AND tbl_diagnostico.ativo IS TRUE
                                ORDER BY tbl_defeito_constatado.descricao ASC ";
                    $res = pg_query($con, $sql);
                    for($z=0; $z<pg_num_rows($res); $z++){
                        $descricao              = pg_fetch_result($res, $z, descricao);
                        $defeito_constatado     = pg_fetch_result($res, $z, defeito_constatado);

                        if($defeito_constatado_descricao == $defeito_constatado){
                            $selected = " selected ";
                        }else{
                            $selected = "";;
                        }
                        echo "<option value='$defeito_constatado' $selected >$descricao</option>";                    
                    }                    
                }
             echo "</select>";
        echo "</td>";
        echo "<td>"; 
            echo "<select name='destinacao_$i' id='destinacao_$i'>";
                
                echo "<option value=''>".traduz("Destinação")."</option>";
                for($a=0; $a<pg_num_rows($resDestinacao); $a++){
                    $segmento_atuacao   = pg_fetch_result($resDestinacao, $a, "segmento_atuacao");
                    $descricao          = pg_fetch_result($resDestinacao, $a, "descricao");

                    if($destinacao == $segmento_atuacao){
                        $selected = " selected ";
                    }else{
                        $selected = " ";
                    }

                    echo "<option value='$segmento_atuacao' $selected >$descricao</option>";
                }
                

             echo "</select>";
        echo "</td>";
    }

    if ($login_fabrica == 1) {
        echo "<td align='center' nowrap>\n";
        echo " &nbsp; <select name='type_$i' class='frm'>";
        if(strlen($type) == 0) { echo "<option value='' selected></option>"; }
        echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo ">".traduz("Tipo 1")."</option>";
        echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo ">".traduz("Tipo 2")."</option>";
        echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo ">".traduz("Tipo 3")."</option>";
        echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo ">".traduz("Tipo 4")."</option>";
        echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo ">".traduz("Tipo 5")."</option>";
        echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo ">".traduz("Tipo 6")."</option>";
        echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo ">".traduz("Tipo 7")."</option>";
        echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo ">".traduz("Tipo 8")."</option>";
        echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo ">".traduz("Tipo 9")."</option>";
        echo "<option value='Tipo 10'"; if($type == 'Tipo 10') echo " selected"; echo ">".traduz("Tipo 10")."</option>";
        echo "</select> &nbsp; ";
        echo "</td>\n";
        echo "<td align='center' nowrap>\n";
        echo " &nbsp; <input class='frm' type='radio' name='embalagem_original_$i' value='t'"; if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo " checked"; echo ">";
        echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>".traduz("Sim")."</b></font> ";
        echo "<input class='frm' type='radio' name='embalagem_original_$i' value='f'"; if ($embalagem_original == 'f') echo " checked"; echo ">";
        echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>".traduz("Não")."</b></font> &nbsp; ";
        echo "</td>\n";
        echo "<td align='center' nowrap>\n";
        echo " &nbsp; <input class='frm' type='radio' name='sinal_de_uso_$i' value='t'"; if ($sinal_de_uso == 't') echo " checked"; echo ">";
        echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>".traduz("Sim")."</font> ";
        echo "<input class='frm' type='radio' name='sinal_de_uso_$i' value='f'"; if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo " checked"; echo ">";
        echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>".traduz("Não")."</font> &nbsp; ";
        echo "</td>\n";
    }
    if($login_fabrica==10){
        echo "<td nowrap align='center'><input name='data_nf_$i' id='data_nf_$i' size='12' maxlength='10' value='$data_nf_$i' type='text' class='frm' tabindex='0' > <font face='arial' size='1'></font></td>";
        echo "<td nowrap align='center'>";
        echo "<input name='nota_fiscal' size='8' maxlength='6'value='$nota_fiscal ' type='text' class='frm' tabindex='0' ></td>";

    }
    if($login_fabrica==74){
        echo "<td nowrap align='center'><input name='data_fabricacao_{$i}' id='data_fabricacao_{$i}' size='12' maxlength='10' value='$data_fabricacao' type='text' class='frm' tabindex='0' > <font face='arial' size='1'></font></td>";
    }

    if (in_array($login_fabrica, array(121,151))) {
        echo "<td nowrap align='center'><input name='qtde_{$i}' id='qtde_{$i}' size='5' maxlength='10' value='$qtde' type='text' class='frm' tabindex='0' > <font face='arial' size='1'></font></td>";
    }

    if($login_fabrica == 50){

        echo "<td nowrap align='center'>";

        if(pg_num_rows($res_dr) > 0){

        	echo "<input type='hidden' name='produto_hidden_{$i}' value='".$_POST["produto_hidden_".$i]."'>";

            echo "<select name='defeito_reclamado_{$i}' class='frm'>";

            if(strlen($_POST["produto_hidden_".$i]) > 0 || strlen($produto_item) > 0){

            	$option = "<option value=''></option>";

            	if (strlen($produto_item) > 0) {
            		$produto = $produto_item;
            	} else if (strlen($_POST["produto_hidden_".$i])){
            		$produto = $_POST["produto_hidden_".$i];
            	}

            	$sql_dr = "SELECT 
							tbl_diagnostico.defeito_reclamado, 
							tbl_defeito_reclamado.descricao 
						FROM tbl_diagnostico 
						INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia 
						INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado 
						WHERE 
							tbl_produto.produto = {$produto} 
							AND tbl_produto.fabrica_i = {$login_fabrica} 
							AND tbl_diagnostico.fabrica = {$login_fabrica} 
							AND tbl_defeito_reclamado.fabrica = {$login_fabrica} 
                            AND tbl_defeito_reclamado.ativo IS TRUE";
				$res_dr = pg_query($con, $sql_dr);

				if(pg_num_rows($res_dr) > 0){

					$rows = pg_num_rows($res_dr);

					for ($k = 0; $k < $rows; $k++) { 
						
						$defeito_reclamado = pg_fetch_result($res_dr, $k, "defeito_reclamado");
						$descricao         = pg_fetch_result($res_dr, $k, "descricao");

						$selected = ($defeito_reclamado == $defeito_reclamado_item) ? "selected" : "";

						$option .= "<option value='{$defeito_reclamado}' {$selected} > {$descricao} </option>";

					}

				}

				echo $option;

            }

            echo "</select>";

        }

        echo "</td>";

    }

    ##hd_chamado=2705567##
    if($login_fabrica == 94){
        echo "
            <td nowrap align='center'>
                <input name='defeito_reclamado_{$i}' id='defeito_reclamado_{$i}' size='25' maxlength='50' value='$defeito_reclamado' type='text' class='frm' tabindex='0' >
                <font face='arial' size='1'></font>
            </td>
        ";
    }
    ## fim - hd_chamado=2705567##
    echo "</tr>\n";
}
?>

</table>

<br />

<p class="tac">
    <input type="button" class='btn btn-primary' value="<?=traduz("Gravar")?>" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('<?=traduz('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.')?>') }" ALT='<?=traduz("Gravar")?>' border='0' > 
    <? if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) { ?>
        &nbsp; &nbsp; &nbsp; <input type="button" class="btn btn-danger" style="width:75px; cursor:pointer;" value="Apagar" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('<?=traduz("Deseja realmente apagar esta OS?")?>') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('<?=traduz("Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.")?>') }" ALT='<?=traduz("Apagar a Ordem de Serviço")?>' border='0'>
    <? } ?>
</p>

</form>

<br>

<? include 'rodape.php'; ?>
