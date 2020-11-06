<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'');
define('ASSCLI_BACK', '../');

if ($areaAdminCliente == true) {
    include_once "../dbconfig.php";
    include_once "../includes/dbconnect-inc.php";
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="gerencia,call_center";
    include 'autentica_admin.php';
    include 'funcoes.php';
}


if(isset($_POST["verificaChamadoAnterior"])){

    $callcenter = $_POST['callcenter'];
    $cpf  = $_POST['cpf'];
    $nota_fiscal = $_POST['nf'];
    $produto = $_POST['produto'];
    if($_POST['data_nf'] == 'null'){
        $data_nf  = $_POST['data_nf'];
    }else{
        $data_nf  = fnc_formata_data_pg($_POST['data_nf']);
    }
    if(strlen($nota_fiscal)>0 or $nota_fiscal != 'null'){
        $condNf  = " AND tbl_hd_chamado_extra.nota_fiscal = '$nota_fiscal' ";
    }

    if(strlen($data_nf)>0  or $data_nf != 'null'){
        $condDataNf = "  AND tbl_hd_chamado_extra.data_nf = $data_nf  ";
    }

    if(strlen($produto)>0  or $produto != 'null'){
        $condProduto = " AND  tbl_hd_chamado_extra.produto = $produto ";
    }

    if(strlen($cpf)>0  or $cpf != 'null'){
        $condCPF = " and tbl_hd_chamado_extra.cpf = '$cpf' ";
    }

    $sql = " SELECT
                tbl_hd_chamado.hd_chamado
            FROM tbl_hd_chamado_extra
            INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
            WHERE tbl_hd_chamado.hd_chamado_anterior is not null
            $condCPF
            $condProduto
            AND tbl_hd_chamado.hd_chamado > $callcenter
            ORDER BY hd_chamado desc limit 1 ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $hd_chamado = pg_fetch_result($res, 0, hd_chamado);

        if($callcenter == $hd_chamado){
            echo json_encode(array('retorno'=>'naoencontrou'));
        }else{
            echo json_encode(array('retorno'=>'encontrou', 'hd_chamado' => $hd_chamado));
        }
    }else{
        echo json_encode(array('retorno'=>'naoencontrou'));
    }

    exit;
}

function uper_acentos($texto){ //HD-3282875
	$array1 = array('á', 'à', 'â', 'ã', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'ô', 'õ', 'ú', 'ù', 'û', 'ç');
	$array2 = array('Á', 'À', 'Â', 'Ã', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Ô', 'Õ', 'Ú', 'Ù', 'Û', 'Ç');
	return str_replace( $array1, $array2, $texto );
}

$msg_erro = "";

// recebe as variaveis
$chk1   = $_REQUEST['chk_opt1'];
$chk2   = $_REQUEST['chk_opt2'];
$chk3   = $_REQUEST['chk_opt3'];
$chk4   = $_REQUEST['chk_opt4'];
$chk5   = $_REQUEST['chk_opt5'];
$chk6   = $_REQUEST['chk_opt6']; //Posto
$chk7   = $_REQUEST['chk_opt7'];
$chk8   = $_REQUEST['chk_opt8'];
$chk9   = $_REQUEST['chk_opt9'];
$chk10  = $_REQUEST['chk_opt10'];
$chk11  = $_REQUEST['chk_opt11'];
$chk12  = $_REQUEST['chk_opt12'];
$chk13  = $_REQUEST['chk_opt13'];
$chk14  = $_REQUEST['chk_opt14'];
$chk15  = $_REQUEST['chk_opt15'];
$chk16  = $_REQUEST['chk_opt16'];
$chk17  = $_REQUEST['chk_opt17'];
$chk18  = $_REQUEST['chk_opt18'];
$chk19  = $_REQUEST['chk_opt19'];
$chk20  = $_REQUEST['chk_opt20'];
$chk21  = $_REQUEST['chk_opt21'];
$chk22  = $_REQUEST['chk_opt22'];
$chk24  = $_REQUEST['chk_opt24'];
$chk25  = $_REQUEST['chk_opt25'];
$chk27  = $_REQUEST['chk_opt27'];
$chk40  = $_REQUEST['chk_opt40'];
$chk85  = $_REQUEST['chk_opt85'];
$chk66  = $_REQUEST['chk_opt66'];
$chk67  = $_REQUEST['chk_opt67'];
$chk86  = $_REQUEST['chk_opt86'];
$chk32  = $_REQUEST['chk_opt32'];
$chk33  = $_REQUEST['chk_opt33'];
$chk91  = $_REQUEST['chk_opt91'];
$chk92  = $_REQUEST['chk_opt92'];

$chk_marca = $_REQUEST['chk_marca'];
//O nome do próximo chd tem 90 referindo-se ao $login_admin
$chk90 = $_REQUEST['chk_opt90'];
$chk99 = $_REQUEST['chk_opt99'];
$chk_posto_estado = $_REQUEST['chk_posto_estado'];
$chk_cnpj_revenda = $_REQUEST['chk_cnpj_revenda'];
$chk_familia = $_REQUEST['chk_familia'];

$chk_opt162 = $_REQUEST['chk_opt162']; //HD-3352176
$motivo_transferencia = $_REQUEST['motivo_transferencia'];

$codigo_posto         	= trim($_REQUEST['codigo_posto']);
$produto_referencia   	= trim($_REQUEST["produto_referencia"]);
$produto_nome         	= trim($_REQUEST["produto_nome"]);
$numero_serie         	= trim($_REQUEST["numero_serie"]);
$lote                   = trim($_REQUEST["lote"]);
$numero_processo        = trim($_REQUEST["numero_processo"]);
$versao                 = trim($_REQUEST["versao"]);
$nome_consumidor      	= trim($_REQUEST["nome_consumidor"]);
$cpf_consumidor       	= trim($_REQUEST["cpf_consumidor"]);
$cidade               	= trim($_REQUEST["cidade"]);
$uf                   	= trim($_REQUEST["uf"]);
$numero_os            	= trim($_REQUEST["numero_os"]);
$nota_fiscal          	= trim($_REQUEST["nota_fiscal"]);
$callcenter           	= trim($_REQUEST["callcenter"]);
$situacao             	= trim($_REQUEST["situacao"]);
$cep                  	= trim($_REQUEST["cep"]);
$consumidor_estado    	= trim($_REQUEST["consumidor_estado"]);
$pedido			    	= trim($_REQUEST["pedido"]);
$fone                 	= trim($_REQUEST["fone"]);
$codigo_cliente_admin 	= trim($_REQUEST["codigo_cliente_admin"]);
$atendimento_callcenter = trim($_REQUEST["atendimento_callcenter"]);
$geral                  = trim($_REQUEST["geral"]);
$nome_fantasia          = trim($_REQUEST["nome_fantasia"]);
$numero_postagem          = trim($_REQUEST["numero_postagem"]);
$posto_estado 			= $_REQUEST['posto_estado'];
$familia 				= $_REQUEST['familia'];
$hd_motivo_ligacao 		= $_REQUEST['hd_motivo_ligacao'];
$data_inicial 			= $_REQUEST[ 'data_inicial' ];
$data_final   			= $_REQUEST[ 'data_final' ];
$numero_ibbl  			= $_REQUEST[ 'numero_ibbl' ];
$hd_classificacao 		= $_REQUEST['hd_classificacao'];
$providencia 			= $_REQUEST['providencia'];
$abertura_fechamento = $_REQUEST['data_abertura_fechamento'];
$codigo_postagem = $_REQUEST['chk_opt_codigo_postagem'];
$chk_opt_postagem = $_REQUEST['chk_opt_postagem'];

$check_por_origem   = trim($_REQUEST['por_origem']);
$origem             = trim($_REQUEST['origem']);

$providencia3 	= trim($_REQUEST['providencia_nivel_3']);
$motivo_contato = trim($_REQUEST['motivo_contato']);
$pedido_venda = trim($_REQUEST['pedido_venda']);
$nf_venda     = trim($_REQUEST['nf_venda']);

$chk29  = $_REQUEST['chk_opt29'];

$chk30 = $_REQUEST['chk_opt30'];

$check_tec_esporadico = $_REQUEST['chk_opt852']; /*HD - 4258409*/
if (!empty($check_tec_esporadico)) {
	$tecnico_esporadico_id     = trim($_REQUEST["tecnico_esporadico_id"]);
}

$check_consumidor_pais = $_REQUEST['chk_opt87']; /*HD - 4304128*/
if (!empty($check_consumidor_pais)) {
	$cond_pais = "";
	$consumidor_pais_filtro = trim($_REQUEST["consumidor_pais"]);
	if ($login_fabrica == 52) {
		$cond_pais =  " AND JSON_FIELD('pais', array_campos_adicionais) = '$consumidor_pais_filtro' ";
	}
}

$check_por_atendente = $_REQUEST['por_atendente'];

if($login_fabrica == 30){
	$chk_opt32  = $_REQUEST['chk_opt32'];
	if($chk_opt32 == 1){
		$revenda_cnpj = $_POST["revenda_cnpj"];
		$revenda_nome = $_POST["revenda_nome"];

		$cond_revenda = " and (tbl_hd_chamado_extra.revenda_nome = '$revenda_nome' and tbl_hd_chamado_extra.revenda_cnpj = '$revenda_cnpj') "; 
	}	
}

if($login_fabrica == 35){
    if (isset($_POST["status_interacao"]) && isset($_POST["json_chamados"])){
        if(count($_POST["json_chamados"]) == 0){
            echo '{"erro":"true","msg":"Selecione um Registro para alterar o status."}';
            exit;
        }
        $arrChamados = $_POST["json_chamados"];
        $status = $_POST["status_interacao"];
        $erro = false;
        pg_query($con, "BEGIN TRANSACTION");
        foreach($arrChamados as $chamado){
            $hd_chamado = $chamado["chamado"];
            $updateStatus = "UPDATE tbl_hd_chamado set status = '{$status}' WHERE hd_chamado = {$hd_chamado}";
            $res = pg_query($con, $updateStatus);

            if(strlen(pg_last_error($con)) == 0){
                $insertItem = "INSERT INTO tbl_hd_chamado_item (hd_chamado,
                                                                comentario,
                                                                admin,
                                                                interno,
                                                                status_item
                                                               )
                                                        VALUES ({$hd_chamado},
                                                                'Status alterado para: {$status}',
                                                                {$login_admin},
                                                                 true,
                                                                 '{$status}'
                                                               )";
                $res = pg_query($con, $insertItem);

                if(strlen(pg_last_error($con)) > 0 ){
                    $erro = true;
                    break;
                }
            }else{
                $erro = true;
                break;
            }
        }

        if($erro){
            pg_query($con, "ROLLBACK TRANSACTION");
            echo '{"erro":"true","msg":"Erro ao gravar o Status"}';
        }else{
            pg_query($con, "COMMIT TRANSACTION");
            echo '{"erro":"false","msg":"Status Alterado"}';
        }
        exit;
    }
}
if($login_fabrica == 74){
	$situacao = array();
	if($_REQUEST['situacao']){
		$situacao = $_REQUEST['situacao'];
		$status_atlas = implode(',', $situacao);
	}
}
$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);

if(empty($chk1) AND empty($chk2) AND empty($chk3) AND empty($chk4) AND empty($chk5) AND empty($chk6) AND empty($chk7) AND empty($chk8) AND empty($chk9) AND empty($chk10) AND empty($chk11) AND empty($chk12) AND empty($chk13) AND empty($chk14) AND empty($chk15) AND empty($chk16) AND empty($chk17) AND empty($chk18) AND empty($chk19) AND empty($chk20) AND empty($chk24) AND empty($chk25) AND empty($chk27) AND empty($chk40) AND empty($chk90) AND empty($chk85) AND empty($chk66) AND empty($chk67) AND empty($chk86) AND empty($data_inicial) AND empty($atendente) AND empty($chk_opt31) AND empty($chk_opt32)AND empty($chk_marca)  AND empty($chk_opt_postagem) AND strpos("nao_reolvidos", $status_atlas) === false AND empty($chk91) AND empty($chk92) AND empty($_REQUEST['opt_email'])){
	$msg_erro = traduz("Informe mais parâmetros para realizar a pesquisa");
	if(isset($_GET['fale_conosco']) && $_GET['fale_conosco'] == "true"){
		$msg_erro = "";
	}
}

$sqlCondAt = " AND 7 = 7 ";
$cond_hd_chamado_status = " 8 = 8 ";

# HD 58801
$por_atendente = $_REQUEST["por_atendente"];
if ($por_atendente == 1){
	$atendente = $_REQUEST["atendente"];
	if (strlen($atendente)>0){
        if($login_fabrica == 24){
            $sqlCondAt = "AND (tbl_hd_chamado.atendente = $atendente OR tbl_hd_chamado.sequencia_atendimento = $atendente)";
        }else
		    $sqlCondAt = "AND tbl_hd_chamado.atendente = $atendente";
	}
}

if($login_fabrica == 24){
	$por_interventor = $_REQUEST["por_intervensor"];
	if ($por_interventor == 1){
		$interventor = $_REQUEST["intervensor"];

		$sql_interventor = "SELECT nome_completo FROM tbl_admin WHERE admin = $intervensor;";
		$res_intervensor = pg_query($con,$sql_interventor);

		if (pg_num_rows($res_intervensor)>0) {
			$intervensor_admin = strtoupper(trim(pg_fetch_result($res_intervensor, 0, 'nome_completo')));

		}


		$sqlCondAt .= " AND (tbl_hd_chamado.atendente = $intervensor OR tbl_hd_chamado_item.admin = $intervensor OR tbl_hd_chamado.sequencia_atendimento = $intervensor)";

		$intervensor_sql = " JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado ";
		$distinc = 'DISTINCT';

	}
}

if( strlen ( $chk90 ) >0 && strlen( $numero_ibbl ) == 0  )
{
	$msg_erro = 'Favor digitar o número IBBL';

}

if( strlen( $geral ) == 0 && strlen ( $chk1 ) == 0 && strlen( $chk2 ) == 0 && strlen ( $chk3 ) == 0 && strlen( $chk4 ) == 0 && strlen( $chk90 ) == 0 && strlen ( $data_inicial ) > 0 || strlen ( $data_final ) > 0 ){

	if( strlen($msg_erro)==0 ){

	//Início Validação de Datas
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data inválida");

			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = traduz("Data inválida");

		if(strlen($erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_final < $nova_data_inicial){
				$msg_erro = traduz("Data inválida.");
			}

			//Fim Validação de Datas
		}
	}
}
if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($callcenter) == 0 && strlen ( $data_inicial ) > 0 || strlen( $data_final ) > 0 && strlen( $chk90 ) != 0){
	$data_inicial = $_REQUEST["data_inicial"];
	$data_final   = $_REQUEST["data_final"];

	if (strlen($data_final) > 0 AND strlen($data_inicial) > 0 and $data_final <> "dd/mm/aaaa" and  $data_inicial <> "dd/mm/aaaa") {
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
		$xdata_inicial = "$xdata_inicial 00:00:00";

		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
		$xdata_final   =  "$xdata_final 23:59:59";


		$sqlX = "SELECT '$xdata_inicial'::date + interval '6 months' > '$xdata_final'";
		$resX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro = traduz("AS DATAS DEVEM SER NO MÁXIMO 6 MESES");
		}
	}else $msg_erro = traduz("Data inválida");
}


	$cond1 = " 1 = 1 ";
	$cond2 = " 1 = 1 ";
	$cond3 = " 1 = 1 ";
	$cond4 = " 1 = 1 ";
	$cond5 = " 1 = 1 ";
	$cond6 = " 1 = 1 ";
	$cond7 = " 1 = 1 ";
	$cond8 = " 1 = 1 ";
	$cond9 = " 1 = 1 ";
	$cond10 = " 1 = 1 ";
	$cond11 = " 1 = 1 ";
	$cond12 = " 1 = 1 ";
	$cond13 = " 1 = 1 ";
	$cond14 = " 1 = 1 ";
	$cond15 = " 1 = 1 ";
	$cond16 = " 1 = 1 ";
	$cond17 = " 1 = 1 ";
	$cond18 = " 2 = 2 "; // providencia
	$cond19 = " 3 = 3 "; // data providencia
	$cond20 = " 4 = 4 "; // estado (Região)
	$cond21 = " 5 = 5 "; // pré -os
	$cond22 = " 6 = 6 "; // pré -os
	$cond23 = " 1 = 1 "; // pré -os
    $cond24 = " 1 = 1 "; // consumidor_estado (por estado e não por região)
    $cond27 = " 1 = 1 "; // consumidor_estado (por estado e não por região)
    $cond28 = " 1 = 1 "; // Classificação do atendimento
	$cond85 = " 1 = 1 "; // Nome Fantasia
	$cond86 = " 1 = 1 "; // Numero Postagem

	//HD 244202: Seleção de status dos chamados
    if($login_fabrica == 24 AND !empty($situacao) AND empty($status))
        $status = $situacao;

	if ($login_fabrica == 24) {
		if (!empty($status) AND $situacao!="TODOS") {
			if($situacao == "com_intervencao"){
				$cond_hd_chamado_intervencao = " JOIN (SELECT DISTINCT(tbl_hd_chamado_item.hd_chamado)
												FROM tbl_hd_chamado_item
												JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado AND tbl_hd_chamado.fabrica = $login_fabrica
												JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin OR tbl_admin.admin = tbl_hd_chamado.sequencia_atendimento
												WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_admin.intervensor IS TRUE) x ON x.hd_chamado = tbl_hd_chamado.hd_chamado  ";
				$cond_hd_chamado_status = " 1 = 1";
			}
			else if($situacao == "nescessita_intervencao"){
				$cond_hd_chamado_intervencao = " JOIN (SELECT DISTINCT(tbl_hd_chamado.hd_chamado)
												FROM tbl_hd_chamado
												JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.sequencia_atendimento AND tbl_admin.fabrica = $login_fabrica
												WHERE tbl_hd_chamado.fabrica = $login_fabrica AND tbl_hd_chamado.sequencia_atendimento IS NOT NULL AND tbl_admin.intervensor IS TRUE AND (tbl_hd_chamado.status = 'Aberto' OR tbl_hd_chamado.status = 'Pendente')) x ON x.hd_chamado = tbl_hd_chamado.hd_chamado";
				$cond_hd_chamado_status = " 1 = 1";
			}
			else{
				$cond_hd_chamado_status = " tbl_hd_chamado.status = '$status'";
			}
		}else {
			$cond_hd_chamado_status = " 1 = 1";
		}
	}else if($login_fabrica == 74){

		if(count($situacao) > 0){
			$status_atlas = implode(',', $situacao);
			if(count($situacao) == 0){
				$cond_hd_chamado_status = " tbl_hd_chamado.status is not null ";
			}else{
				if(strpos("TODOS", $status_atlas) !== false){
					$cond_hd_chamado_status = " tbl_hd_chamado.status is not null";
				}elseif(strpos("nao_reolvidos", $status_atlas) !== false){
					$cond_hd_chamado_status = " lower(tbl_hd_chamado.status) <> 'resolvido' AND upper(tbl_hd_chamado.status) <> 'PROTOCOLO DE INFORMACAO' ";
				}else{
					$status_atlas = "";
					foreach ($situacao as $status) {
						$status_atlas .= "'$status'";
					}
					$status_atlas = str_replace("''", "','", $status_atlas);
					$cond_hd_chamado_status = " tbl_hd_chamado.status in($status_atlas)";
				}
			}
		}else{
			$cond_hd_chamado_status = " 1 = 1";
		}
	}else {
		if ($situacao=="TODOS"){
			$cond_hd_chamado_status = " tbl_hd_chamado.status is not null";
		}else	if ($situacao=="PENDENTES"){
			$cond1 = "UPPER(tbl_hd_chamado.status) <> 'RESOLVIDO'";
		}else if ($situacao=="SOLUCIONADOS"){
			$cond1 = " UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'";
		}else{
			if (!empty($situacao)){
				$cond_hd_chamado_status = " TO_ASCII(tbl_hd_chamado.status, 'LATIN9') = TO_ASCII('".((in_array($login_fabrica, array(136))) ? $situacao : retira_acentos($situacao))."', 'LATIN9')";
			}else{
				$cond_hd_chamado_status = " tbl_hd_chamado.status is not null ";
			}
		}
	}

	if(strlen($chk1) > 0){
		//dia atual
		$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
		$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

		$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
		$resX = pg_exec ($con,$sqlX);
		#  $dia_hoje_final = pg_result ($resX,0,0);

		$cond1 = " tbl_hd_chamado.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final' ";
	}

	if(strlen($chk2) > 0) {
		// dia anterior
		$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

		$cond2 =" tbl_hd_chamado.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final' ";
	}

	if(strlen($chk3) > 0){
		// última semana
		$sqlX = "SELECT to_char (current_date , 'D')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

		$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

		$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

		$cond3 =" tbl_hd_chamado.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final' ";

	}

	if(strlen($chk4) > 0){
		// do mês
		$mes_inicial = trim(date("Y")."-".date("m")."-01");
		$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

		$cond4 = " tbl_hd_chamado.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59' ";

	}

	if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($callcenter) == 0 && $xdata_inicial && $xdata_final ){
		$cond5 = "  tbl_hd_chamado.data BETWEEN '$xdata_inicial' AND '$xdata_final'  ";
	}
	if(strlen($chk1) == 0 AND strlen($chk2) == 0 AND strlen($chk3) == 0 AND strlen($chk4) == 0 AND strlen($callcenter) == 0 && strlen($xdata_inicial) ==0  && strlen($xdata_final) ==0 and empty($por_atendente) ){
	}

	if($abertura_fechamento == "fechamento"){
		$cond5 = " 1 =  1 ";
		$condFechamento = " AND tbl_hd_chamado.hd_chamado IN (SELECT DISTINCT tbl_hd_chamado_item.hd_chamado
			FROM tbl_hd_chamado_item
			JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
			WHERE tbl_hd_chamado_item.data BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND UPPER(tbl_hd_chamado_item.status_item) = 'RESOLVIDO')";
	}

	if(strlen($chk6) > 0){
		// codigo do posto
		if (strlen($codigo_posto) > 0){
			$cond6 = " tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
		}else{
			$msg_erro = traduz("Código de posto inválido!");
		}
	}

	if(strlen($chk7) > 0){
		// referencia do produto
		if ($produto_referencia) {
			$sql = "Select produto from tbl_produto where fabrica_i=$login_fabrica AND referencia_pesquisa = '$produto_referencia' ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,0);
				$cond7 = (in_array($login_fabrica, [151,178,189])) ? " tbl_hd_chamado_item.produto = $produto " :	" tbl_hd_chamado_extra.produto = $produto ";
			}
		}else{
			$msg_erro = traduz("Referência inválida!");
		}
	}

	if(strlen($chk8) > 0){
		// numero de serie do produto
		if ($numero_serie) {
			$cond8 = ($login_fabrica == 52 || $login_fabrica == 151) ? " tbl_hd_chamado_item.serie = '$numero_serie'" : " tbl_hd_chamado_extra.serie = '$numero_serie' ";
		}else{
			$msg_erro = traduz("Número de série inválido!");
		}
	}

    if(strlen($chk_opt31)){
        if (strlen($lote)>0) {
            $lote   = preg_replace('/\D/', '', $lote);
            $cond_lote  =  " AND tbl_hd_chamado_extra.serie like '%$lote%' ";
        }else{
            $msg_erro = traduz("Informe um número de lote");
        }
    }

    if(strlen(trim($chk22))>0){
        $cond22 = " tbl_hd_chamado_extra.numero_processo = '$numero_processo' ";
    }

	if(strlen($chk9) > 0){
		// nome_consumidor
		if ($nome_consumidor){
			//$monta_sql .= "$xsql tbl_cliente.nome ilike '%".$cliente."%' ";
			$cond9 = "  tbl_hd_chamado_extra.nome ~* '".$nome_consumidor."' ";
		}else{
			$msg_erro = traduz("Nome do consumidor inválido!");
		}
	}

	if(strlen($chk10) > 0){
		// cpf_consumidor
		if ($cpf_consumidor){
			$cpf_consumidor = str_replace('.', '', $cpf_consumidor);
			$cpf_consumidor = str_replace('-', '', $cpf_consumidor);
			$cpf_consumidor = str_replace('/', '', $cpf_consumidor);
			$cond10 = " tbl_hd_chamado_extra.cpf = '". $cpf_consumidor."' ";
		}else{
			$msg_erro = traduz("CPF/CNPJ inválido!");
		}
	}


	if(strlen($chk13) > 0){
		$numero_os = preg_replace('([^0-9-])','',$numero_os); //retorna numero e '-'

		// numero_os
		if ($numero_os AND strlen($numero_os) > 0){
			if (in_array($login_fabrica,array(151))) {
				$join_tbl_os = " JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_item.os AND tbl_os.excluida IS NOT TRUE AND tbl_os.fabrica = {$login_fabrica} ";
				$cond13 = " tbl_os.sua_os LIKE '".$numero_os."%' ";
			} else {
				if (in_array($login_fabrica, array(169,170))) {
					$join_jornada = "
						LEFT JOIN tbl_hd_jornada ON tbl_hd_jornada.fabrica = {$login_fabrica}
					";
				}

				$join_tbl_os = " JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.excluida IS NOT TRUE AND tbl_os.fabrica = {$login_fabrica} ";
				$cond13 = " tbl_os.sua_os LIKE '".$numero_os."%' ";
			}

		}else{
			$msg_erro = traduz("Número de OS inválido!");
		}

	}

	if(strlen($chk14) > 0){
		// nota fiscal
		if ($nota_fiscal){
			$cond14 = (in_array($login_fabrica, [151,189])) ? " tbl_hd_chamado_item.nota_fiscal ='".$nota_fiscal."' " : " tbl_hd_chamado_extra.nota_fiscal ='".$nota_fiscal."' ";
		}else{
			$msg_erro = traduz("Nota fiscal inválida!");
		}
	}

	if(strlen($chk15) > 0){
		// nota fiscal
		if ($callcenter){
			$callcenter = preg_replace('([^0-9])','',$callcenter);
			$cond15 = " tbl_hd_chamado.hd_chamado = $callcenter";
		}else{
			$msg_erro = traduz("Número do atendimento inválido!");
		}
	}

	if(strlen($chk16) > 0){
		$fone = str_replace("(", "", $fone);
		$fone = str_replace(")", "", $fone);
		$fone = str_replace(" ", "", $fone);
		$fone = str_replace("-", "", $fone);

		// nota fiscal
		if ($fone){
			$cond16 = " tbl_hd_chamado_extra.fone LIKE '".$fone."%' ";
		}else{
			$msg_erro = traduz("Telefone do consumidor inválido!");
		}
	}

	if(strlen($chk17) > 0){
		$cep = preg_replace('([^0-9])','',$cep);
		// nota fiscal
		if ($cep){
			$cep = str_replace(".", "", $cep);
			$cep = str_replace("-", "", $cep);
			$cond17 = " tbl_hd_chamado_extra.cep = '".$cep."' ";
		}else{
			$msg_erro = traduz("CEP do consumidor inválido!");
		}
	}

	if(strlen($chk18) > 0){
	// pré-os
		$cond21 = " tbl_hd_chamado_extra.abre_os is true ";
	}


	if(strlen($chk91) > 0){

		if (!empty($pedido_venda)) {

			$cond_pedido_venda = "AND JSON_FIELD('pedido_venda',tbl_hd_chamado_extra.array_campos_adicionais) IS NOT NULL
								  AND JSON_FIELD('pedido_venda',tbl_hd_chamado_extra.array_campos_adicionais) = '{$pedido_venda}'";

		} else {
			$msg_erro = "Informe um número de pedido de venda";
		}

	}

	if(strlen($chk92) > 0){

		if (!empty($nf_venda)) {

			$cond_nf_venda = "AND JSON_FIELD('nf_venda',tbl_hd_chamado_extra.array_campos_adicionais) IS NOT NULL
							  AND JSON_FIELD('nf_venda',tbl_hd_chamado_extra.array_campos_adicionais) = '{$nf_venda}'";

		} else {
			$msg_erro = "Informe uma NF de venda";
		}

	}

	if(strlen($chk19) > 0){
		// CLIENTE_ADMIN
		if (strlen($codigo_cliente_admin) > 0){
			$cond22 = " tbl_cliente_admin.codigo = '". $codigo_cliente_admin."' ";
		}
	}


	if(strlen($chk20) > 0){
		// Atendimento Cadence(Solutiva)
		if (strlen($atendimento_callcenter) > 0){
			$cond22 = " tbl_hd_chamado_extra.atendimento_callcenter ILIKE '". $atendimento_callcenter."' ";
		}
	}

    if(strlen($chk21) > 0){
        // Atendimento Cadence(Solutiva)
        if (!empty($hd_motivo_ligacao) > 0){
        	if ($login_fabrica == 50) {
        		$hd_motivo_ligacao = implode(",", $hd_motivo_ligacao);

        		$cond25 = " AND tbl_hd_chamado_extra.hd_motivo_ligacao IN ($hd_motivo_ligacao) ";
        	} else {
            	$cond25 = " AND tbl_hd_chamado_extra.hd_motivo_ligacao = $hd_motivo_ligacao ";
        	}
        }
    }

    if($login_fabrica == 85){
        if(strlen($chk85) > 0){
            // Nome Fantasia (GELOPAR)
            if (strlen($nome_fantasia) > 0){
                $cond85 = " JSON_FIELD('nome_fantasia',tbl_hd_chamado_extra.array_campos_adicionais) = '$nome_fantasia'";
            }
        }
    }

    if($login_fabrica == 162 || $login_fabrica == 164){
        if(strlen($chk86) > 0){
            // Número Postagem
            if (strlen($numero_postagem) > 0){
                $cond86 = " tbl_hd_chamado_postagem.numero_postagem = '$numero_postagem'";
            }
        }
    }

	if(strlen($chk24) > 0){
		if ($consumidor_estado){
			$cond24 = " tbl_cidade.estado='".$consumidor_estado."' ";
		}else{
			$msg_erro = traduz("Estado do consumidor inválido!");
		}
	}

	if (in_array($login_fabrica, [186]) && !empty($_REQUEST['opt_email'])) {

		$email_callcenter = $_REQUEST["email_callcenter"];

		$condEmail = "AND tbl_hd_chamado_extra.email = '{$email_callcenter}'";
	}

	if(strlen($chk25) > 0){
		if (strlen($pedido) > 0){
			$cond27 = " tbl_hd_chamado_extra.pedido ='".$pedido."' ";
		}else{
			$msg_erro = traduz("Informe o número do pedido!");
		}
	}
	if(strlen($chk_opt_postagem) > 0){
		if(strlen($codigo_postagem)>0){
			$sql_codigo_postagem = " AND tbl_hd_chamado_extra.codigo_postagem = '$codigo_postagem' ";
		}
	}

	if(strlen($chk27) > 0){
		if (strlen($hd_classificacao) > 0){
			$cond28 = " tbl_hd_chamado.hd_classificacao =".$hd_classificacao;
		}else{
			$msg_erro = traduz("Informe a classificação do atendimento!");
		}
	}

	if ($login_fabrica == 183){
		$pesquisa_classificacao = false;

		if (strlen(trim($hd_classificacao)) > 0){
			$sql_classificacao = "SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = $login_fabrica AND hd_classificacao = $hd_classificacao AND UPPER(descricao) ILIKE '%DEVOLU%' ";
			$res_classificacao = pg_query($con, $sql_classificacao);

			if (pg_num_rows($res_classificacao) > 0){
				$pesquisa_classificacao = true;
			}
		}

		if (empty($numero_os)){
			$coluna_revenda = "tbl_hd_chamado_extra.revenda_cnpj, tbl_hd_chamado_extra.revenda_nome,";
			$join_tbl_os = " LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.excluida IS NOT TRUE AND tbl_os.fabrica = {$login_fabrica} ";
		}
	}

	if(strlen($chk_posto_estado) > 0){
		if ($posto_estado){
			$posto_estado = implode('\',\'',$posto_estado);
			$cond_posto_estado = " AND tbl_posto_fabrica.contato_estado in ('".$posto_estado."') ";
		}else{
			$msg_erro = traduz("Estado do posto inválido!");
		}
	}

	if (strlen($chk_cnpj_revenda) > 0) {
		if (strlen($_POST["cnpj_revenda"])) {
			$cnpj_revenda = $_POST["cnpj_revenda"];
			$cond_cnpj_revenda = " AND tbl_hd_chamado_extra.revenda_cnpj = '$cnpj_revenda' ";
		} else {
			$msg_erro = traduz("Digite o CNPJ da Revenda");
		}
	}

	if(strlen($check_por_origem) > 0){ //hd_chamado=2902269
	        $cond_origem = " AND tbl_hd_chamado_extra.origem = '$origem' ";
	}

	if (in_array($login_fabrica, array(169,170)) || $usaOrigemCadastro){
		if(strlen(trim($chk29)) > 0 AND strlen(trim($origem)) > 0){
			$cond_origem = " AND tbl_hd_chamado_extra.origem = '$origem'";
		}
    }

    if (in_array($login_fabrica, array(169,170))) {

		if ($chk30 == "true"){
			$cond_jornada = "
				AND (
					(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
					OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
					OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
				)
			";
		}

		$join_jornada = "
			LEFT JOIN tbl_hd_jornada ON tbl_hd_jornada.fabrica = {$login_fabrica}
			LEFT JOIN tbl_os ON (tbl_os.os = tbl_hd_chamado_extra.os OR tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado) AND tbl_os.fabrica = {$login_fabrica}
		";
		$campo_jornada = "
			, CASE WHEN tbl_os.os IS NOT NULL AND (
				(tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.estado IS NULL AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto = tbl_os.produto)
				OR (tbl_hd_jornada.cidade = tbl_cidade.cidade AND tbl_hd_jornada.produto IS NULL)
				OR (tbl_hd_jornada.estado = tbl_cidade.estado AND tbl_hd_jornada.cidade IS NULL AND tbl_hd_jornada.produto IS NULL)
			) THEN
				TRUE
			ELSE
				FALSE
			END AS jornada
		";
	}

	if(strlen($chk_familia) > 0){
		if ($familia){
			$familia = implode(',',$familia);
			$cond_familia = " AND tbl_produto.familia in ( ".$familia." )  ";
		}else{
			$msg_erro = traduz("Família inválida!");
		}
	}
//chamadoFricon
	if(strlen($chk_marca) > 0) {
		if (strlen($_POST["marca"])) {
			$marca_fricon = $_REQUEST['marca'];
			$cond_marca_logo = " AND tbl_hd_chamado_extra.marca = '$marca_fricon' ";
		} else {
			$msg_erro = traduz("Digite a Marca Logo");
		}
	}
//chamado Arge
	if(strlen($chk66) > 0) {
		if (strlen($_POST["linha_prod"])) {
			$linha_prod = $_REQUEST['linha_prod'];
			$cond_linha_prod = " AND tbl_produto.linha in ( ".$linha_prod." )  ";
		} else {
			$msg_erro = traduz("Digite a Linha do Produto");
		}
	}

	if(strlen($chk67) > 0) {
		if (strlen($_POST["familia_prod"])) {
			$familia_prod = $_REQUEST['familia_prod'];
			$cond_familia_prod = " AND tbl_produto.familia in ( ".$familia_prod." )  ";
		} else {
			$msg_erro = traduz("Digite a Família do Produto");
		}
	}

	if($login_fabrica == 162){ //HD-3352176
		if(strlen(trim($chk_opt162)) > 0){
			$cond_hd_situacao = "AND tbl_hd_chamado_extra.hd_situacao = $motivo_transferencia";
		}
	}

	if($login_fabrica == 160 or $replica_einhell){
        if(strlen(trim($chk40))>0){
            $cond40 = " AND array_campos_adicionais ~ 'versao_produto' and array_campos_adicionais ~ '$versao' ";
        }
    }


if(empty($msg_erro)){

	if ( $login_fabrica == 5 ) {
		 // providencia --------------
		 $providencia_chk = ( isset($_REQUEST['providencia_chk']) ) ? $_REQUEST['providencia_chk'] : $_REQUEST['providencia_chk'];
		 if ( isset($providencia_chk) && ! empty($providencia_chk) ) {
		 	$providencia = ( isset($_REQUEST['providencia']) ) ? $_REQUEST['providencia'] : $_REQUEST['providencia'];
		 	$providencia = ( ! empty($providencia) ) ? pg_escape_string($providencia) : null ;
		 	$cond18      = ( ! empty($providencia) ) ? ' tbl_hd_chamado_extra.data_providencia = '.$providencia : $cond18 ;
		 }
		 unset($providencia_chk,$providencia);
		 // data providencia ---------
		 $providencia_data_chk = ( isset($_REQUEST['providencia_data_chk']) ) ? $_REQUEST['providencia_data_chk'] : $_REQUEST['providencia_data_chk'];
		 if ( isset($providencia_data_chk) && ! empty($providencia_data_chk) ) {
		 	$providencia_data = ( isset($_REQUEST['providencia_data']) ) ? $_REQUEST['providencia_data'] : $_REQUEST['providencia_data'];
		 	$providencia_data = ( ! empty($providencia_data) ) ? pg_escape_string(fnc_formata_data_pg($providencia_data)) : null ;
		 	$cond19		      = ( ! empty($providencia_data) ) ? ' tbl_hd_chamado.previsao_termino = '.$providencia_data : $cond19 ;
		 }
		 // estado -------------------
		 $estado_chk = ( isset($_REQUEST['regiao_chk']) ) ? $_REQUEST['regiao_chk'] : $_REQUEST['regiao_chk'];
		 if ( isset($estado_chk) && ! empty($estado_chk) ) {
		 	$estado  = ( isset($_REQUEST['regiao']) ) ? $_REQUEST['regiao'] : $_REQUEST['regiao'];
		 	$estados = array();
		 	switch ( strtoupper($estado) ) {
		 		case 'SUL':
		 			$aTmp    = array('PR','SC','RS');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		case 'SP': case 'SP-CAPITAL': case 'SP-INTERIOR':
		 			$estados[] = 'SP';
		 			break;
		 		case 'RJ': case 'PE': case 'BA': case 'MG':
		 			$estados[] = pg_escape_string($estado);
		 		case 'BR-NEES':
		 			$aTmp    = array('AL','BA','CE','MA','PB','PE','PI','RN','SE','ES');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		case 'BR-NCO':
		 			$aTmp    = array('AC','AP','AM','PA','RR','RO','TO','GO','MT','MS','DF');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		default:
		 			$cond20 = ' 1=1 ';
		 			break;
		 	}
		 	if ( count($estados) > 0 ) {
		 		$estados_string = implode("','",$estados);
		 		$cond20         = " tbl_cidade.estado IN ('{$estados_string}') ";
		 	}
		 }
	}

	if($login_fabrica == 30){
		$sql_resumo = "SELECT
			tbl_admin.nome_completo,
			tbl_hd_chamado.status,
			tbl_hd_chamado.atendente,
			COUNT(*)
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao and tbl_hd_situacao.fabrica = $login_fabrica
			JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin  and tbl_admin.fabrica = $login_fabrica
			LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin and tbl_cliente_admin.fabrica = $login_fabrica
			{$join_tbl_os}
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND UPPER(tbl_hd_chamado.status) in ('ABERTO','RESOLVIDO')
			AND $cond1
			AND $cond2
			AND $cond3
			AND $cond4
			AND $cond5
			AND $cond6
			AND $cond7
			AND $cond8
			AND $cond9
			AND $cond10
			AND $cond11
			AND $cond12
			AND $cond13
			AND $cond14
			AND $cond15
			AND $cond16
			AND $cond17
			AND $cond18
			AND $cond19
			AND $cond20
			AND $cond21
			AND $cond22
			AND $cond24
			AND $cond27
			AND $cond28
			$cond_origem
			$condFechamento
			$sqlCondAt
			$sql_codigo_postagem";
			$sql_resumo .= " GROUP BY tbl_admin.nome_completo,tbl_hd_chamado.status,tbl_hd_chamado.atendente
			ORDER BY tbl_admin.nome_completo,tbl_hd_chamado.status";

		$res_resumo = pg_query($con,$sql_resumo);
    }

    if (!in_array($login_fabrica, [189])) {
    	$campos_produto = "tbl_produto.descricao as produto_nome,
					   	   tbl_produto.referencia as produto_referencia,";
    }

	$campo_serie = " tbl_hd_chamado_extra.serie , ";
    if (in_array($login_fabrica, [52,151,189])) {
	    	if($login_fabrica != 189){
			$condEx    = "and (tbl_hd_chamado_item.produto is not null or tbl_hd_chamado_item.os notnull) and interno";
			$campo_serie = " tbl_hd_chamado_item.serie , ";
		}
		$JOIN_ITEM = "LEFT JOIN tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado  $condEx
					  LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_item.produto AND tbl_produto.fabrica_i = $login_fabrica ";
		$campo_os  = " case when tbl_hd_chamado_item.os notnull then tbl_hd_chamado_item.os else tbl_hd_chamado_extra.os end as os_item,";
	} else if ($login_fabrica == 162 || $login_fabrica == 164) {
        $JOIN_ITEM = "LEFT JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_postagem.fabrica = $login_fabrica
                      LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
        ";
	} else {
		if ($login_fabrica == 183){
			$campos_linha_familia = ",
				tbl_linha.codigo_linha AS codigo_linha,
				tbl_linha.nome AS nome_linha,
				tbl_familia.descricao AS descricao_familia
			";
			$JOIN_ITEM = "
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica
				LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
				LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = $login_fabrica
			";
		
		} else if (!in_array($login_fabrica, [189])) {
			$JOIN_ITEM = " LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto AND tbl_produto.fabrica_i = $login_fabrica";
		}
	}

	if(in_array($login_fabrica, [178])){
		$campo_os = " ( SELECT os FROM tbl_os JOIN tbl_hd_chamado_item USING(os) WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado ORDER BY os LIMIT 1) AS os_item, ";
	}

	if (!$_REQUEST["gerar_excel"]) {
		$limit = "LIMIT 501";
	}

	if ($login_fabrica == 86) {
		$sql_dias_abertos = "DATE_PART('DAYS', (tbl_hd_chamado.data - current_date)) AS dias_aberto,";
	} else {
		$sql_dias_abertos = "tbl_hd_chamado_extra.dias_aberto,";
	}

	if(isset($_GET['fale_conosco']) && $_GET['fale_conosco'] == "true"){

		$tipo = "producao"; // teste - producao

		$admin_fale_conosco = ($tipo == "teste") ? 6409 : 6437;

		$cond_fale_conosco = " AND tbl_hd_chamado.status IS NULL AND tbl_hd_chamado.admin = $admin_fale_conosco";

	}

	if(in_array($login_fabrica, array(30,151,189))){
		$providencia = $_POST["providencia"];

		if(!empty($providencia)){
			$cond_providencia = " AND tbl_hd_motivo_ligacao.hd_motivo_ligacao = {$providencia}";
		}
	}

	$cond_duvida = "";
	$cond_tipo_atendimento_consumidor = "";

	if(in_array($login_fabrica, [30])){
		$tipo_atendimento_consumidor = $_POST["tipo_atendimento_consumidor"];

		if(!empty($tipo_atendimento_consumidor)){
			$cond_tipo_atendimento_consumidor = " AND tbl_hd_chamado_extra.consumidor_revenda = '$tipo_atendimento_consumidor'";
		}
	}

	if (in_array($login_fabrica, array(152,180,181,182))) {
		$duvida_consumidor = $_POST["duvida_consumidor"];

		if(!empty($duvida_consumidor)){

			if ($duvida_consumidor == "TODOS") {
				$cond_duvida = " AND tbl_hd_chamado_extra.tipo_registro IN('Reclamação', 'Comercial', 'Técnica')";
			} else {
				$cond_duvida = " AND tbl_hd_chamado_extra.tipo_registro = '{$duvida_consumidor}'";
			}
		}

		$tipo_atendimento_consumidor = $_POST["tipo_atendimento_consumidor"];

		if (!empty($tipo_atendimento_consumidor)) {

			if ($tipo_atendimento_consumidor == "TODOS") {
	            $cond_tipo_atendimento_consumidor = " AND JSON_FIELD('tipo_atendimento_consumidor', tbl_hd_chamado_extra.array_campos_adicionais) IN ('C', 'R', 'S')";
			} else {
	            $cond_tipo_atendimento_consumidor = " AND JSON_FIELD('tipo_atendimento_consumidor', tbl_hd_chamado_extra.array_campos_adicionais) = '{$tipo_atendimento_consumidor}'";
			}
		}
	}
	if (in_array($login_fabrica, array(169,170,189))){
		$distinc = "DISTINCT ON (tbl_hd_chamado.hd_chamado) ";
	}
    if($login_fabrica == 24){
        $campo_suggar = " tbl_hd_chamado_extra.produto, tbl_hd_chamado_extra.nota_fiscal, ";
    }

    if (in_array($login_fabrica, [169, 170])) {
    	if (!empty($chk32) && !empty($providencia3)) {
    		$condProv3 = "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($chk33) && !empty($motivo_contato)) {
    		$condMotivoContato = "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}

	$leftProtocoloVia = " LEFT JOIN tbl_hd_tipo_chamado ON tbl_hd_chamado.hd_tipo_chamado = tbl_hd_tipo_chamado.hd_tipo_chamado AND tbl_hd_tipo_chamado.fabrica = {$login_fabrica} ";
	$campoProtocoloVia = ", tbl_hd_tipo_chamado.descricao AS protocolo_via";
    }


    if($login_fabrica == 42){
    	$campo_makita = " tbl_hd_chamado_extra.tipo_registro,  "; 
    }
    if($login_fabrica == 30){
    	$campo_esmaltec = " tbl_hd_chamado_extra.revenda_nome,  ";
    }
	if($login_fabrica == 80){
		$distinc = 'DISTINCT';
		
		$campos_precision = "tbl_status_checkpoint.descricao AS status_descricao,
							CASE
							WHEN tbl_hd_chamado.status = 'Resolvido' AND  tbl_hd_chamado.resolvido Is NOT NULL THEN
								to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY')
							WHEN tbl_hd_chamado.status = 'Resolvido' AND  tbl_hd_chamado.resolvido IS NULL THEN
								(SELECT MAX(to_char(data,'DD/MM/YYYY')) 
											FROM  (SELECT data FROM tbl_hd_chamado_item 
								where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
								AND tbl_hd_chamado_item.admin_transferencia IS NOT NULL AND 
								tbl_hd_chamado_item.status_item = 'Resolvido'
								ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC limit 1) data) 
							ELSE 
								''
							END AS data_protocolo,";
		$join_precision   = "LEFT JOIN tbl_hd_chamado_item on (tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado) 
							LEFT JOIN tbl_os on (tbl_os.os = tbl_hd_chamado_extra.os) 
							LEFT JOIN tbl_status_checkpoint on (tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint)";

		$status_os = $_REQUEST['status_os'];
		$consumidor_estado = $_REQUEST['consumidor_estado'];
		$consumidor_cidade = $_REQUEST['consumidor_cidade'];
		if(strlen($status_os) > 0 ){
			if($status_os == 'todos'){
				$where_precision .= "AND tbl_os.status_checkpoint in (0,1,2,3,4,9)";
			}else{
				$where_precision .= "AND tbl_os.status_checkpoint = '$status_os'";			
			}
		}
		if(strlen($consumidor_estado) > 0){
			$where_precision .= "AND tbl_hd_chamado_extra.estado = '$consumidor_estado'";
		}
		if(strlen($consumidor_cidade) > 0){
			$where_precision .= "AND tbl_hd_chamado_extra.cidade = '$consumidor_cidade'";
		}
	}
	$sql = "SELECT $distinc tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.titulo,
					(SELECT MAX(to_char(data,'DD/MM/YYYY HH24:MI')) 
					 FROM  (SELECT data FROM tbl_hd_chamado_item 
							where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
							AND tbl_hd_chamado_item.admin_transferencia IS NOT NULL
							ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC limit 1) data) 
					AS ultima_transferencia,
					tbl_hd_chamado.protocolo_cliente,
					tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao,
					tbl_hd_chamado.status,
					tbl_hd_chamado.hd_chamado_anterior,
					$campo_serie
					$campos_precision
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_hd_chamado_extra.data_nf,
					tbl_hd_chamado_extra.marca,
					tbl_hd_chamado_extra.defeito_reclamado,
					tbl_hd_chamado_extra.defeito_reclamado_descricao,
					tbl_hd_chamado.data AS data_hd_chamado,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS providencia_data,
					to_char(tbl_hd_chamado.data + INTERVAL '5 DAYS','DD/MM/YYYY') AS data_maxima,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.endereco,
					tbl_hd_chamado_extra.numero,
					tbl_hd_chamado_extra.complemento,
					tbl_hd_chamado_extra.cep,
                    tbl_hd_chamado_extra.email AS email_consumidor,
					tbl_hd_chamado_extra.bairro,
					tbl_hd_providencia.descricao AS descricao_providencia,
					tbl_motivo_contato.descricao AS descricao_motivo_contato,
                    $campo_suggar
                    $campo_makita
                    $campo_esmaltec
					tbl_hd_chamado_extra.array_campos_adicionais,
					$campo_os
					$campos_produto
					{$coluna_revenda}
					tbl_hd_chamado_extra.nome as consumidor_nome,
                    tbl_hd_chamado_extra.cpf,
                    CASE WHEN tbl_hd_chamado_extra.consumidor_revenda = 'C'
                         THEN 'CONSUMIDOR'
                         WHEN tbl_hd_chamado_extra.consumidor_revenda = 'S'
                         THEN 'CONSTRUTORA'
                         WHEN tbl_hd_chamado_extra.consumidor_revenda = 'R'
                         THEN 'REVENDA'
                    END  AS hd_consumidor_revenda,
					CASE
						WHEN tbl_hd_chamado_extra.fone IS NOT NULL AND tbl_hd_chamado_extra.fone <> '' THEN
							tbl_hd_chamado_extra.fone
						WHEN tbl_hd_chamado_extra.fone2 IS NOT NULL AND tbl_hd_chamado_extra.fone2 <> '' THEN
							tbl_hd_chamado_extra.fone2
						WHEN tbl_hd_chamado_extra.celular IS NOT NULL AND tbl_hd_chamado_extra.celular <> '' THEN
							tbl_hd_chamado_extra.celular
					END
					AS consumidor_telefone,
					tbl_hd_chamado_extra.fone2 AS telefone_comercial,
					tbl_hd_chamado_extra.celular AS telefone_celular,
					tbl_hd_chamado_extra.origem,
					$sql_dias_abertos
					tbl_posto_fabrica.codigo_posto as codigo_posto,
					tbl_posto.nome as posto_nome,
					tbl_posto_fabrica.contato_cidade as posto_cidade,
					tbl_posto_fabrica.contato_estado as posto_estado,
                    tbl_posto_fabrica.contato_fone_comercial AS fone_posto,
					tbl_hd_chamado.atendente,
					tbl_hd_chamado.sequencia_atendimento as intervensor,
					tbl_hd_chamado.categoria,
					tbl_hd_chamado.admin,
					tbl_hd_chamado.cliente_admin,
					tbl_cliente_admin.nome,
					tbl_cliente_admin.cidade,
					tbl_cidade.nome as nome_cidade,
					tbl_cidade.estado AS consumidor_estado,
					tbl_cliente_admin.estado,
					CASE
						WHEN $login_fabrica = 30 AND tbl_hd_chamado.status <> 'Aberto' THEN
							''
						ELSE
							tbl_hd_situacao.descricao
					END AS providencia,
					tbl_hd_classificacao.descricao AS classificacao_atendimento
					{$campo_jornada}
					{$campos_linha_familia}
					{$campoProtocoloVia}
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			$JOIN_ITEM
			$intervensor_sql
			$join_jornada
			$join_precision
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao AND tbl_hd_situacao.fabrica = {$login_fabrica}
			LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
			LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao
			AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			$cond_hd_chamado_intervencao
			LEFT JOIN tbl_hd_classificacao ON tbl_hd_chamado.hd_classificacao = tbl_hd_classificacao.hd_classificacao AND tbl_hd_classificacao.fabrica = {$login_fabrica}
			{$join_tbl_os}
			LEFT JOIN tbl_hd_providencia ON tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia
			AND tbl_hd_providencia.fabrica = {$login_fabrica}
			LEFT JOIN tbl_motivo_contato ON tbl_hd_chamado_extra.motivo_contato = tbl_motivo_contato.motivo_contato
			AND tbl_motivo_contato.fabrica = {$login_fabrica}
			{$leftProtocoloVia}
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.titulo !~* 'HELP-DESK' AND tbl_hd_chamado.titulo !~* 'Atendimento Revenda'
			AND $cond1
			AND $cond2
			AND $cond3
			AND $cond4
			AND $cond5
			AND $cond6
			AND $cond7
			AND $cond8
			AND $cond9
			AND $cond10
			AND $cond11
			AND $cond12
			AND $cond13
			AND $cond14
			AND $cond15
			AND $cond16
			AND $cond17
			AND $cond18
			AND $cond19
			AND $cond20
			AND $cond21
			AND $cond22
			AND $cond23
         AND $cond24
         AND $cond27
         AND $cond28
			AND $cond85
			AND $cond86
			AND $cond_hd_chamado_status
			$cond25
            $cond_origem
			$cond_posto_estado
			$cond_cnpj_revenda
			$cond_familia
			$cond_familia_prod
			$cond_linha_prod
			$cond_marca_logo
			$cond26
            $cond40
            $cond_lote
			$sql_codigo_postagem
			$condFechamento
			$cond_fale_conosco
			$cond_jornada
			$cond_hd_situacao
			$cond_providencia
			$cond_duvida
			$cond_pais
			$cond_tipo_atendimento_consumidor
			{$condMotivoContato}
			{$condProv3}
			{$cond_revenda}
			{$cond_pedido_venda}
			{$cond_nf_venda}
			{$condEmail}
			{$where_precision}";

			if( $login_fabrica == 90 && $chk90 == '1' && strlen( $numero_ibbl ) > 0  )
			{

				$sql .= " AND tbl_hd_chamado.protocolo_cliente = '$numero_ibbl' ";
			}
			if($chk99 == '1') {
				$sql .= " AND tbl_hd_chamado.categoria ~* 'reclamacao' ";
			}

			if($areaAdminCliente == true){
				$sql .= " AND tbl_hd_chamado.cliente_admin = $login_cliente_admin ";
			}

			$sql .= "$sqlCondAt";
	if (in_array($login_fabrica, array(169,170))) {
		$sql .= " ORDER BY tbl_hd_chamado.hd_chamado DESC, jornada DESC $limit";
	} else {
		$sql .= " ORDER BY tbl_hd_chamado.hd_chamado DESC $limit";
	}
	$sql  = str_replace('__STATUS__',$cond_status,$sql);
	//chamadoFricon
	if(stripos($sql, 'between') === false and empty($callcenter) and empty($numero_serie)) {
		$sql = str_ireplace("ORDER BY", " AND tbl_hd_chamado.data > current_timestamp - interval '24 months' ORDER BY  " , $sql);
	}
// echo "<pre>".print_r($sql,1)."</pre>";exit;
	$resHD = pg_query($con, $sql);
    if($login_fabrica == 24){
        $dadosCompleto = pg_fetch_all($resHD);
    }

	$num_chamados = pg_num_rows ($resHD);

	if ($_REQUEST["gerar_excel"]) {

		if ($num_chamados > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_atendimento-{$login_fabrica}-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");

			fwrite($file,"<table border='1'");
			fwrite($file,"<thead>");
			fwrite($file,"<tr class='titulo_coluna'>");

			switch($login_fabrica) {
				case 5:fwrite($file,"
					 <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Chamado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Providência</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Providência</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</TH>");
				break;

				case 52: fwrite($file,"
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente Abertura</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Série</T     H>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Marca</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Chamado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>E-mail</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade <br /> (Consumidor)</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado <br /> (Consumidor)</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>País <br /> (Consumidor)</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade<br/>(Posto)</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado<br/>(Posto)</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status OS</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status Call-Center</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Classificação</TH>");
				break;

				case 24: fwrite($file,"
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem do chamado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente Responsável</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Interventor</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Recebimento/Abertura</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Máxima para Solução</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ligação Agendada</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Chamado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Ordem de Serviço</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Categoria</TH>");
				break;

				case 90: fwrite($file,"
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Atendente</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência do Produto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Chamado</TH>
                            <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Status</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número IBBL</th>");
				break;
				
				case 80: 
					fwrite($file,"
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Aberto Por')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Atendente Atual')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Data')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Nº Chamado')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Status')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Data de Finalização do Protocolo')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Classificação')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Produto')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Data de Compra')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Telefone do Cliente')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('E-mail do Cliente')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Cliente')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Cidade')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Estado')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('OS')."</TH>
					<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Status da OS')."</TH>");
				break;


				default:
					if(in_array($login_fabrica,[169,170])){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Protocolo Via')."</TH>");
					}

					fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Aberto Por')."</TH>");
					fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Atendente Atual')."</TH>");

					if (in_array($login_fabrica, [186])) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto Indicado</TH>");
					}

					if ($login_fabrica == 35) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Última Transferência</TH>");
					}

					if ($login_fabrica == 11) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Admin Responsável</TH>");
					}

					if(in_array($login_fabrica, [50,178])) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Atendimento</TH>");
					}

					fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Data')."</TH>");

					if($login_fabrica == 30){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de Conclusão</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tempo de Conclusão</TH>
							<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Motivo</TH>");
					}

					if($login_fabrica == 74){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Visita Técnico</TH>");
					}

					if ($login_fabrica == 85) {
						if (!empty($check_tec_esporadico)) {
							fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Encerramento</TH>");
						}

						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Referência')."</TH>");
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Produto')."</TH>");
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Série')."</TH>");
					} else {
						if($login_fabrica == 52){
							fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Série')."</TH>");	
						}
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Produto')."</TH>");
					}

					

					if($login_fabrica == 137){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº Lote</TH>");
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</TH>");
					}

					if ($login_fabrica == 183){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Familia Produto</TH>");
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Linha Produto</TH>");
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamado</TH>");
					}

					if(in_array($login_fabrica,array(15,30,45,140))){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito</TH>");
					}else if(in_array($login_fabrica, array(120,201,50,178))){//HD-3282875 adicionada fabrica 50
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamado</TH>");
                    }

                    /*HD - 4382764*/
                    if ($login_fabrica == 174) {
                    	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome da Loja</TH>");
                    }

					fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Nº Chamado')."</TH>");

					if ($login_fabrica == 183){
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido SAP</TH>");
					}

                    if($login_fabrica == 115){
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo de Atendimento</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>At. Relacionado</TH>");
                    }

					if($login_fabrica == 7){
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DR</TH>");
                    }else{

                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Cliente')."</TH>");
                    }
                    if($login_fabrica == 115){
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email Cliente</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone Consumidor</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone Comercial</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone Celular</TH>");
                    }

                    if($login_fabrica == 30){ //hd_chamado=2902269
                    	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo </TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone </TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Telefone Posto</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Revenda</TH>");
                    }

                    if($login_fabrica == 45){
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CPF</TH>");
                    }
                    if ($login_fabrica == 178) {
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</TH>");

                    }

                    if ($login_fabrica != 115) {
                        fwrite($file,"
                            <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</TH>
                            <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Estado</TH>"
                        );
                    } else {

                        fwrite($file,"
                            <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Estado')."</TH>
                            <TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Cidade')."</TH>"
                        );
                    }
					if($login_fabrica == 11){
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Motivo Ligação</TH>");
                    }

                    if ($login_fabrica == 115) {
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</TH>");

                    }

                    if($moduloProvidencia || $classificacaoHD){
                    	$label_prov = traduz("Providência");
                    	$label_class = traduz("Classificação");
                    	if ($login_fabrica == 189) {
                        	$label_prov = traduz("Ação");
                        	$label_class = traduz("Registro Ref. a");
                    	} 
                    	if ($login_fabrica != 80) {
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>$label_prov</TH>");
                        }
                    	if ($login_fabrica == 189) {
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Ação</TH>");
                        }
                        if (in_array($login_fabrica, [169,170])) {
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Providência nv. 3</TH>");
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Motivo Contato</TH>");
                        }
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>$label_class</TH>");

                        if ($login_fabrica == 30) {
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</TH>");

                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº da SR</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nº do Processo</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Recebimento</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fatal</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Notificado</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota do Consumidor</TH>");
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Id da Reclamação</TH>");
                        }

                        if (in_array($login_fabrica, array(169,170,178))) {
                            fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</TH>");
                        }

                    } else {
                        if ((in_array($login_fabrica, array(85)) && !empty($check_tec_esporadico)) || $login_fabrica != 85) {
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Técnico Esporádico</TH>");
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor do Serviço Combinado</TH>");
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Última Resposta");
                        } else {
                        	fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</TH>");
                        }
                    }

                    if ($login_fabrica == 85) {
                    	 fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data da Previsão</TH>");
                    	 fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data 1ª Visita</TH>");
                    }

                    if (in_array($login_fabrica,array(175,177))){
                	    fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</TH>");
                    }
                    if ($login_fabrica == 74) {
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Providência</TH>");
                    }

                    if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || !in_array($login_fabrica, [85,189]))
                    fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</TH>");

                	if ($login_fabrica == 183 AND $pesquisa_classificacao == true){
                		fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ Revenda</TH>");
                		fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nome Revenda</TH>");
                	}

					fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Status')."</TH>");
                    if (in_array($login_fabrica, array(178))) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Resposta do Consumidor</TH>");
                    }
					if (in_array($login_fabrica, array(169,170))) {
						fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</TH>");
					}

                   	if ($login_fabrica == 86) {
                   		fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Dias em Aberto</TH>");
                   	}

                    if($login_fabrica == 136){

                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Endereço</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Complemento</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Origem</TH>");
                        fwrite($file,"<TH bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido</TH>");

                    }

				break;
			}
			fwrite($file,"</tr>");
			fwrite($file,"</thead>");
			fwrite($file,"<tbody>");

			$aux_contador = 0;
			for ($i = 0; $i < $num_chamados; $i++){
				$callcenter         = trim(pg_result ($resHD,$i,'hd_chamado'));
				$data_hd_chamado    = trim(pg_result ($resHD,$i,'data_hd_chamado'));
				$data               = trim(pg_result ($resHD,$i,'data'));
				$data_nf            = trim(pg_result ($resHD,$i,'data_nf'));
				$data_maxima        = pg_result($resHD,$i,'data_maxima');
				$sua_os             = trim(pg_result ($resHD,$i,'sua_os'));
				$origem             = trim(pg_result ($resHD,$i,'origem'));				
				$endereco           = trim(pg_result ($resHD,$i,'endereco'));
				$numero             = trim(pg_result ($resHD,$i,'numero'));
				$complemento        = trim(pg_result ($resHD,$i,'complemento'));
				$cep                = trim(pg_result ($resHD,$i,'cep'));
				$bairro             = trim(pg_result ($resHD,$i,'bairro'));
				$os                 = trim(pg_result ($resHD,$i,'os'));
				$dias_aberto        = trim(pg_result ($resHD,$i,'dias_aberto'));
				if (strlen($campo_os)>0) {
					$os_item                 = trim(pg_result ($resHD,$i,'os_item'));
				}
				$serie              = trim(pg_result ($resHD,$i,'serie'));
				$consumidor_nome    = trim(pg_result ($resHD,$i,'consumidor_nome')); //hd_chamado=2683927
				$consumidor_telefone= trim(pg_result ($resHD,$i,'consumidor_telefone'));
				$consumidor_cpf     = trim(pg_result ($resHD,$i,'cpf'));
				$posto_nome         = trim(pg_result ($resHD,$i,'posto_nome'));
				$codigo_posto       = trim(pg_result ($resHD,$i,'codigo_posto'));
				$posto_cidade       = trim(pg_result ($resHD,$i,'posto_cidade'));
				$posto_estado       = trim(pg_result ($resHD,$i,'posto_estado'));
				$produto_nome       = trim(pg_result ($resHD,$i,'produto_nome'));
				$produto_referencia = trim(pg_result ($resHD,$i,'produto_referencia'));
				$defeito_reclamado  = trim(pg_result ($resHD,$i,'defeito_reclamado_descricao'));
				$status             = trim(pg_result ($resHD,$i,'status'));
				$providencia        = trim(pg_result ($resHD,$i,'providencia'));
				$providencia_data	= trim(pg_result ($resHD,$i,'providencia_data'));
				$atendente          = trim(pg_result ($resHD,$i,'atendente'));
				$categoria          = trim(pg_result ($resHD,$i,'categoria'));
                $admin              = trim(pg_result ($resHD,$i,'admin'));
				$cliente_admin      = trim(pg_result ($resHD,$i,'cliente_admin'));
				$intervensor        = trim(pg_result ($resHD,$i,'intervensor'));
				$nome_cliente       = trim(pg_result ($resHD,$i,'nome'));
				$cidade				= trim(pg_result ($resHD,$i,'cidade'));
				$estado             = trim(pg_result($resHD,$i,'estado'));
				$nome_cidade		= trim(pg_result ($resHD,$i,'nome_cidade'));
				$consumidor_estado	= trim(pg_result ($resHD,$i,'consumidor_estado'));
				$protocolo_cliente			= trim(pg_result ($resHD,$i,'protocolo_cliente'));
				$hd_motivo_ligacao			= trim(pg_result ($resHD,$i,'hd_motivo_ligacao'));
				$array_campos_adicionais 	= pg_fetch_result($resHD, $i, 'array_campos_adicionais');
				$marca_logo			= trim(pg_result ($resHD,$i,'marca'));
				$defeito_reclamado_arge  = trim(pg_fetch_result ($resHD,$i,'defeito_reclamado'));
				$classificacao_atendimento  = trim(pg_fetch_result ($resHD,$i,'classificacao_atendimento'));
                $fone_posto = trim(pg_fetch_result($resHD, $i, 'fone_posto'));
                $hd_consumidor_revenda  = pg_fetch_result($resHD, $i, 'hd_consumidor_revenda');
                $descricao_providencia3  = pg_fetch_result($resHD, $i, 'descricao_providencia');
                $motivo_contato_callcenter  = pg_fetch_result($resHD, $i, 'descricao_motivo_contato');
                
                if ($login_fabrica == 183){
					$codigo_linha 		= pg_fetch_result($resHD, $i, "codigo_linha");
					$nome_linha  		= pg_fetch_result($resHD, $i, "nome_linha");
					$descricao_familia 	= pg_fetch_result($resHD, $i, "descricao_familia");

					$revenda_nome 		= pg_fetch_result($resHD, $i, "revenda_nome");
					$revenda_cnpj 		= pg_fetch_result($resHD, $i, "revenda_cnpj");
				}

                if ($login_fabrica == 35) {
                	$ultima_transferencia = pg_fetch_result($resHD, $i, 'ultima_transferencia');
                }

                if($login_fabrica == 30){
                	$revenda_nome = pg_fetch_result($resHD, $i, revenda_nome);
                }

		if(in_array($login_fabrica,[169,170])){
			$protocolo_via = pg_fetch_result($resHD, $i, 'protocolo_via');
		}

                if ($login_fabrica == 52) { /*HD - 4304128*/
	            	$array_campos_adicionais = json_decode($array_campos_adicionais, true);

	            	if (!empty($array_campos_adicionais["pais"])) {
	            		$consumidor_pais = $array_campos_adicionais["pais"];
	            	} else {
	            		$consumidor_pais = "";
	            	}

	            	if (!empty($consumidor_pais_filtro) && !empty($check_consumidor_pais)) {
	            		if (strlen($consumidor_pais) == 0) {
	            			continue;
	            		} else if ($consumidor_pais_filtro != $consumidor_pais) {
	            			continue;
	            		}
	            	}
	            }

				if ($login_fabrica == 178) { 
	            	$array_campos_adicionais = json_decode($array_campos_adicionais, true);

	            	if (!empty($array_campos_adicionais["resposta_consumidor"])) {
	            		$resposta_consumidor = $array_campos_adicionais["resposta_consumidor"];
	            	} else {
	            		$resposta_consumidor = "";
	            	}

	            }
              
                /*HD- 4258409*/
				if ($login_fabrica == 85 && !empty($check_tec_esporadico)) {
					$json_campos_add   = json_decode($array_campos_adicionais, true);
					$hd_tec_esporadico = $json_campos_add["tecnico_esporadico_id"];
					$pesquisar_tecnico_esporadio = false;

					if (!empty($tecnico_esporadico_id)) {
						if ($tecnico_esporadico_id != $hd_tec_esporadico) {
							continue;
						} else {
							$pesquisar_tecnico_esporadio = true;
						}
					} else if (empty($hd_tec_esporadico)) {
						continue;
					} else {
						$pesquisar_tecnico_esporadio = true;
					}

					if ($pesquisar_tecnico_esporadio == true) {
						/*Data de Encerramento*/
						$aux_sql   = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data_encerramento FROM tbl_hd_chamado_item WHERE hd_chamado = $callcenter AND status_item = 'Resolvido' LIMIT 1";
						$aux_res   = pg_query($con, $aux_sql);
						$rows      = pg_num_rows($aux_res);
						$encerrado = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'data_encerramento') : "";

						/*Técnico Esporádico*/
						$aux_sql = "SELECT codigo_externo || ' - ' || nome AS tecnico_esporadico FROM tbl_tecnico WHERE tecnico = $hd_tec_esporadico";
						$aux_res = pg_query($con, $aux_sql);
						$rows    = pg_num_rows($aux_res);
						$tecnico = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'tecnico_esporadico') : "";

						/*Valor do Serviço Combinado*/
						$vl_ser  = "R$ " . str_replace(".", ",", $json_campos_add["valor_servico_combinado"]);

						/*Última interação do atendente para o cliente*/
						$aux_sql = "SELECT comentario FROM tbl_hd_chamado_item WHERE hd_chamado = $callcenter AND comentario IS NOT NULL AND interno IS NOT TRUE ORDER BY tbl_hd_chamado_item DESC LIMIT 1";
						$aux_res   = pg_query($con, $aux_sql);
						$rows      = pg_num_rows($aux_res);
						$ult_inte  = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'comentario') : "";

						if (empty($ult_inte)) {
							$quebra   = array(";", "\n", "&nbsp;");
							$ult_inte = strip_tags($ult_inte);
							$ult_inte = str_replace($quebra, "", $ult_inte);
						}
						
						$aux_contador++;
					}
				}

				if ($login_fabrica == 85) {

					$array_campos_adicionais = json_decode($array_campos_adicionais, true);
					$data_previsao_ambev     = $array_campos_adicionais['data_previsao_ambev'];
					$data_primeira_visita = $array_campos_adicionais['data_primeira_visita'];
				}

                if($login_fabrica == 30){ //hd_chamado=2902269
                    $arr_campos_adicionais = $array_campos_adicionais;
                }

                if(in_array($login_fabrica, array(169,170,175,177,178))){
                	$origem = pg_fetch_result($resHD, $i, 'origem');
                }

                if($login_fabrica == 30 OR $login_fabrica == 115){
                	$hd_consumidor_revenda  = pg_fetch_result($resHD, $i, 'hd_consumidor_revenda');
                }

                if($login_fabrica == 115){ //hd_chamado=2710901
                    $hd_chamado_anterior    = pg_fetch_result($resHD, $i, 'hd_chamado_anterior');
                    $telefone_comercial     = pg_fetch_result($resHD, $i, 'telefone_comercial');
                    $telefone_celular       = pg_fetch_result($resHD, $i, 'telefone_celular');
                }

                if(in_array($login_fabrica,array(52,80,115))){
                    $email_consumidor = trim(pg_fetch_result($resHD, $i, 'email_consumidor'));
                }
				if ($login_fabrica == 86) {
					$dias_aberto = str_replace("-", "", $dias_aberto);
				}

				if ($login_fabrica == 86 && in_array($status, array("Cancelado", "Resolvido","RESOLVIDO"))) {
					$dias_aberto = "";
				}

				if ($login_fabrica != 24) {
					if (in_array($login_fabrica, array(74,81,183))) {
						$array_campos_adicionais = json_decode($array_campos_adicionais, true);
					} else {
						$array_campos_adicionais  = (explode("||",$array_campos_adicionais));

						foreach($array_campos_adicionais as $valores){
							list($key,$valor) = explode("=>",$valores);
							$$key = $valor;
						}
					}
				} else {
					$array_campos_adicionais = json_decode($array_campos_adicionais, true);

					if (strlen($array_campos_adicionais["ligacao_agendada"]) > 0) {
						list($laa, $lam, $lad) = explode("-", $array_campos_adicionais["ligacao_agendada"]);
						$ligacao_agendada = "{$lad}/{$lam}/{$laa}";
					} else {
						$ligacao_agendada = "";
					}
				}
				
				$status_checkpoint = "";
				if (strlen($os_item) > 0) {
					$sql_sc = "SELECT tbl_status_checkpoint.descricao,excluida, sua_os
						   FROM tbl_os
						   JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
						   WHERE os = $os_item";
					$res_sc = pg_query($con, $sql_sc);

					$status_checkpoint = pg_result($res_sc, 0, "descricao");
					$excluida = pg_result($res_sc, 0, "excluida");
					$sua_os   = pg_result($res_sc, 0, "sua_os");
					if($excluida == 't') $status_checkpoint = 'OS Excluída';
				}

				if (strlen($status_checkpoint) == 0) {
					$status_checkpoint = "Aberta Call-Center";
				}

				if(strlen($atendente) >0){
					$sqlx="SELECT login from tbl_admin where admin=$atendente";
					$resx=pg_exec($con,$sqlx);
					$atendente          = strtoupper(trim(pg_result ($resx,0,'login')));
				}

				if(strlen($intervensor) >0){
					$sqlx="SELECT login from tbl_admin where admin=$intervensor";
					$resx=pg_exec($con,$sqlx);
					$intervensor          = strtoupper(trim(pg_result ($resx,0,'login')));
				}

				if($login_fabrica == 5){
					# HD 58801
					$sqlx = "SELECT login from tbl_admin where admin=$admin";
					$resx = pg_exec($con,$sqlx);
					$atendente = strtoupper(trim(pg_result ($resx,0,'login')));
				}

				if(strlen($admin) >0){
					$sqlx="SELECT login from tbl_admin where admin=$admin";
					$resx=pg_exec($con,$sqlx);
					$admin          = strtoupper(trim(pg_result ($resx,0,'login')));
				}

				if (strlen (trim ($sua_os)) == 0) $sua_os = $os;
				if(strlen($os)> 0){
					$sqlx="SELECT sua_os
							FROM tbl_os
							WHERE os = $os";
					$resx=pg_exec($con,$sqlx);
					$sua_os = trim(pg_result ($resx,0,'sua_os'));
				}
				$mais_30 = "";
				if($login_fabrica == 74 AND $status != "Resolvido" AND $status != "RESOLVIDO" AND (strtotime($data_hd_chamado.'+ 30 days') < strtotime(date('Y-m-d')))){
					$mais_30 = "'mais_30'";
				}

				$data_conclusao = "";
				if($login_fabrica == 30 and $status=='RESOLVIDO'){
					$sql_conclusao = "SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data_conclusao
										FROM tbl_hd_chamado
										JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
										WHERE tbl_hd_chamado.hd_chamado = $callcenter
										AND UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
										AND UPPER(status_item) = 'RESOLVIDO'
										ORDER BY hd_chamado_item LIMIT 1";
					$res_conclusao = pg_query($con,$sql_conclusao);
					if(pg_num_rows($res_conclusao) > 0){
						$data_conclusao = pg_fetch_result($res_conclusao, 0, 'data_conclusao');
					}
				}
				if ($login_fabrica == 52) {
					if (strlen($marca_logo)>0) {
						$sqlx="SELECT nome FROM tbl_marca WHERE marca = $marca_logo;";
						$resx=pg_exec($con,$sqlx);
						$marca_logo_nome = pg_fetch_result($resx, 0, 'nome');
					}else{
						$marca_logo_nome = "";
					}
				}


				if (in_array($login_fabrica,array(15,30,50,90,120,201,137,140,183))) {//HD-3282875 adicionada fabrica 50

					if(strlen($defeito_reclamado_arge) > 0){
			                    $sqlx="SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = '$defeito_reclamado_arge';";
			                    $resx=pg_exec($con,$sqlx);
			                    $defeito_reclamado_arg = strtoupper(trim(pg_fetch_result($resx, 0, 'descricao')));

			                    if($login_fabrica == 50){//HD-3282875 adicionada fabrica 50
									$defeito_reclamado_arg = uper_acentos($defeito_reclamado_arg);
								}

					}else{
						$defeito_reclamado_arg = "";
					}
				}
				if ($login_fabrica == 80) {
					$status_os = trim(pg_fetch_result ($resHD,$i,'status_descricao'));
					$data_protocolo = trim(pg_fetch_result ($resHD,$i,'data_protocolo'));
				}

				fwrite($file,"<TR>");
				switch($login_fabrica) {
					case 5:
					   fwrite($file,"<TD align='center' nowrap>$callcenter</TD>
								<TD align='center' nowrap>$data</TD>
								<TD nowrap>".substr($consumidor_nome,0,18)."</TD>
								<TD nowrap>".substr($produto_nome,0,16)."</TD>
								<TD align='center' nowrap>$status</TD>
								<TD align='center' nowrap>".substr($providencia,0,17)."</TD>
								<TD align='center' nowrap>$providencia_data</TD>
								<TD align='center' nowrap>$atendente</TD>");
					break;
		//chamadoFricon
					case 52:
						    fwrite($file,"<TD align='center' nowrap>$atendente</TD>
									<TD align='center' nowrap>$admin</TD>
									<TD align='center' nowrap>$data</TD>
									<TD nowrap>".$produto_nome."</TD>
									<TD nowrap>".$serie."</TD>
									<TD nowrap>".$marca_logo_nome."</TD>
									<TD align='center' nowrap>$callcenter</TD>
									<TD>".$consumidor_nome."</TD>
                                    <td>".$email_consumidor."</td>
									<TD align='center' nowrap>$nome_cidade</TD>
									<TD align='center' nowrap>$consumidor_estado</TD>
									<TD align='center' nowrap>$consumidor_pais</TD>
									<TD align='center'>$nome_cliente</TD>
									<TD align='center' nowrap>$cidade</TD>
									<TD align='center' nowrap>$estado</TD>
									<TD align='center'>$posto_nome</TD>
									<TD align='center'>$posto_cidade</TD>
									<TD align='center'>$posto_estado</TD>
									<TD align='center' nowrap>$os_item</TD>
									<TD align='center' nowrap>$status_checkpoint</TD>
									<TD align='center' nowrap>$status</TD>
									<TD align='center' nowrap>$classificacao_atendimento</TD>");
					break;

					case 24:
						if ($por_interventor == 1){
							fwrite($file,"<TD align='center' nowrap>$admin</TD>
								<TD align='center' nowrap>$atendente</TD>
								<TD align='center' nowrap>$intervensor_admin</TD>
								<TD nowrap>".substr($produto_nome,0,17)."</TD>
								<TD align='center' nowrap>$status</TD>
								<TD align='center' nowrap>$data</TD>
								<TD align='center' nowrap>$data_maxima</TD>
								<TD align='center' nowrap>$ligacao_agendada</TD>
								<TD align='center' nowrap>$callcenter</TD>
								<TD align='center' nowrap>$os</TD>
								<TD>".$consumidor_nome."</TD>
								<TD align='center' nowrap>$nome_cidade</TD>
								<TD align='center' nowrap>$consumidor_estado</TD>
								<TD align='center'>$categoria</TD>");
						} else {
							fwrite($file,"<TD align='center' nowrap>$admin</TD>
								<TD align='center' nowrap>$atendente</TD>
								<TD align='center' nowrap>$intervensor</TD>
								<TD nowrap>".substr($produto_nome,0,17)."</TD>
								<TD align='center' nowrap>$status</TD>
								<TD align='center' nowrap>$data</TD>
								<TD align='center' nowrap>$data_maxima</TD>
								<TD align='center' nowrap>$ligacao_agendada</TD>
								<TD align='center' nowrap>$callcenter</TD>
								<TD align='center' nowrap>$os</TD>
								<TD>".$consumidor_nome."</TD>
								<TD align='center' nowrap>$nome_cidade</TD>
								<TD align='center' nowrap>$consumidor_estado</TD>
								<TD align='center'>$categoria</TD>");
						}

					break;

					case 90:
                        $defeito_reclamado_ibbl = (empty($defeito_reclamado_arg))? $defeito_reclamado : $defeito_reclamado_arg;
					    fwrite($file,"<TD align='center' nowrap>$atendente</TD>
								<TD align='center' nowrap>$data</TD>
								<TD align='center' >".$produto_referencia."</TD>
								<TD align='center' nowrap>$callcenter</TD>
                                <TD align='center' nowrap>$defeito_reclamado_ibbl</TD>
								<TD>".substr($consumidor_nome,0,17)."</TD>
								<TD>$consumidor_telefone</TD>
								<TD align='center' nowrap>$nome_cidade</TD>
								<TD align='center' nowrap>$consumidor_estado</TD>
								<TD align='center'>$posto_nome</TD>
								<TD align='center' nowrap>$sua_os</TD>
								<TD align='center' nowrap>$status</TD>\n
								<TD align='center' nowrap>$protocolo_cliente</TD>");
					break;
					case 80: 
						fwrite($file,"
						<TD align='center' nowrap>$admin</td>
						<TD align='center' nowrap>$atendente</td>
						<TD align='center' nowrap>$data</td>
						<TD align='center' nowrap>$callcenter</td>
						<TD align='center' nowrap>$status</td>
						<TD align='center' nowrap>$data_protocolo</td>
						<TD align='center' nowrap>$classificacao_atendimento</td>
						<TD align='center' nowrap>$produto_referencia - $produto_nome</td>
						<TD align='center' nowrap>$data_nf</TD>
						<TD align='center' nowrap>$consumidor_telefone</TD>
						<TD align='center' nowrap>$email_consumidor</TD>
						<TD align='center' nowrap>$consumidor_nome</td>
						<TD align='center' nowrap>$nome_cidade</td>
						<TD align='center' nowrap>$consumidor_estado</td>
						<TD align='center' nowrap>$sua_os</TD>
						<TD align='center' nowrap>$status_os</TD>");
					break;

					default:
					    if(in_array($login_fabrica,[169,170])){
						fwrite($file,"<TD align='center' $cor_linha nowrap>$protocolo_via</TD>");
					    }

					    fwrite($file,"<TD align='center' nowrap>$admin</TD><TD align='center' $cor_linha nowrap>$atendente</TD>");
						if ($login_fabrica == 11) {
					    	fwrite($file,"<TD align='center' nowrap>{$admin}</TD>");
					    }

					    if(in_array($login_fabrica, [50])){
					    	fwrite($file,"<TD align='center' nowrap>{$hd_motivo_ligacao}</TD>");
					    }

					    if (in_array($login_fabrica, [178])) {
					    	fwrite($file,"<TD align='center' nowrap>{$hd_consumidor_revenda}</TD>");
					    }

					    if (in_array($login_fabrica, [186])) {
							fwrite($file,"<TD align='center' $cor_linha nowrap>$codigo_posto - $posto_nome</TD>");	
						}

						if ($login_fabrica == 35) {
							fwrite($file,"<TD align='center' $cor_linha nowrap>$ultima_transferencia</TD>");
						}

						fwrite($file,"<TD align='center' $cor_linha nowrap>$data</TD>");
						if($login_fabrica == 30){
							fwrite($file,"	<TD align='center' nowrap>$data_conclusao</TD>
									<TD align='center' nowrap>$dias_aberto</TD>
									<TD align='center' nowrap>$providencia</TD>");
						}
						if($login_fabrica == 74){
							fwrite($file,"	<TD align='center' nowrap>".$array_campos_adicionais["data_visita_tecnico"]."</TD>");
						}

						if ($login_fabrica == 85) {
							if (!empty($check_tec_esporadico))	fwrite($file,"<TD $cor_linha nowrap>".$encerrado."</TD>");

							fwrite($file,"<TD $cor_linha nowrap>".$produto_referencia."</TD>");
							fwrite($file,"<TD $cor_linha nowrap>".$produto_nome."</TD>");
							fwrite($file,"<TD $cor_linha nowrap>".$serie."</TD>");
						} else {
							if($login_fabrica == 52){
								fwrite($file,"<TD $cor_linha nowrap>".$serie."</TD>");
							}
							fwrite($file,"<TD $cor_linha nowrap>". $produto_referencia . "  -  ".$produto_nome."</TD>");
						}

						
						if($login_fabrica == 137){
							fwrite($file,"<TD align='center'>$serie</TD>");
							fwrite($file,"<TD align='center'>$defeito_reclamado_arg</TD>");
						}

						if ($login_fabrica == 183){
							fwrite($file,"<TD align='center'>$descricao_familia</TD>");
							fwrite($file,"<TD align='center'>$codigo_linha-$nome_linha</TD>");
							fwrite($file,"<TD align='center'>$defeito_reclamado_arg</TD>");
						}

						if(in_array($login_fabrica,array(15,30,50,120,201,140))){ //HD-3282875 adicionada fabrica 50
							fwrite($file,"<TD align='center'>$defeito_reclamado_arg</TD>");
						}

						if(in_array($login_fabrica,array(45,178))){
							fwrite($file,"<TD align='center'>$defeito_reclamado</TD>");
						}

						/*HD - 4382764*/
						if ($login_fabrica == 174) {
							$aux_array = json_decode(pg_fetch_result($resHD, $i, 'array_campos_adicionais'), true);

							if (!empty($aux_array["nome_loja"])) {
								$nome_loja = $aux_array["nome_loja"];
							} else {
								$nome_loja = "";
							}

							fwrite($file,"<TD align='center' $cor_linha nowrap>$nome_loja</TD>");
						}

                       	fwrite($file,"<TD align='center' $cor_linha nowrap>$callcenter</TD>");

                       	if ($login_fabrica == 183){
                       		$pedido_sap = $array_campos_adicionais["pedido_sap"];
                       		fwrite($file,"<TD align='center' $cor_linha nowrap>$pedido_sap</TD>");
                       	}

                        if($login_fabrica == 115){
					    	fwrite($file,"<TD align='center' nowrap>$hd_consumidor_revenda</TD>");
                            fwrite($file,"<TD align='center' $cor_linha nowrap>$hd_chamado_anterior</TD>");
                        }

						fwrite($file, "	<TD $cor_linha nowrap>$consumidor_nome</TD>");

                        if($login_fabrica == 115){
                            fwrite($file,"<TD align='center' $cor_linha nowrap>$email_consumidor</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$consumidor_telefone</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$telefone_comercial</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$telefone_celular</TD>");
                        }
                        if($login_fabrica == 45){
                            fwrite($file, "<TD nowrap>".$consumidor_cpf."</TD>");
                        }

                        if($login_fabrica == 30){ //hd_chamado=2902269
                        	fwrite($file, " <TD align='center' $cor_linha nowrap>$hd_consumidor_revenda</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$consumidor_telefone</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$fone_posto</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$revenda_nome </TD>");
                        }

					   if ($login_fabrica == 178) {
                            fwrite($file,"
                                <td>{$cep}</td>
                                <td>".uper_acentos($endereco)."</td>
                                <td>".uper_acentos($bairro)."</td>
                                <td>".uper_acentos($complemento)."</td>
                            ");
                        }



                        if ($login_fabrica != 115) {
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$nome_cidade</TD>");
                            fwrite($file, "<TD align='center' $cor_linha nowrap>$consumidor_estado</TD>");
                        } else {
                            fwrite($file, "<TD align='center' $cor_linha nowrap>$consumidor_estado</TD>");
                            fwrite($file, " <TD align='center' $cor_linha nowrap>$nome_cidade</TD>");
                        }

						if(in_array($login_fabrica,array(11))){
							fwrite($file,"<TD align='center' $cor_linha>$hd_motivo_ligacao</TD>");
                        }

                        if ($login_fabrica == 115) {
                            fwrite($file,"
                                <td>{$cep}</td>
                                <td>".uper_acentos($endereco)."</td>
                                <td>".uper_acentos($bairro)."</td>
                                <td>".uper_acentos($complemento)."</td>
                            ");
                        }

						if($moduloProvidencia || $classificacaoHD){
							if ($login_fabrica != 80) {
	                            fwrite($file,"<TD align='center' $cor_linha>$hd_motivo_ligacao </TD>");
	                        }
							if ($login_fabrica == 189) {
	                            fwrite($file,"<TD align='center' $cor_linha>$providencia_data</TD>");
	                        }

	                        if(in_array($login_fabrica, array(169,170))){
								fwrite($file,"<TD align='center' $cor_linha>$descricao_providencia3</TD>");
								fwrite($file,"<TD align='center' $cor_linha>$motivo_contato_callcenter</TD>");
							}

							fwrite($file,"<TD align='center' $cor_linha>$classificacao_atendimento </TD>");

                        } else {
                        	if ((in_array($login_fabrica, array(85)) && !empty($check_tec_esporadico)) || $login_fabrica != 85) {
                        		fwrite($file,"<TD align='center' nowrap>".$tecnico."</TD>");
                        		fwrite($file,"<TD align='center' nowrap>".$vl_ser."</TD>");
                        		fwrite($file,"<TD align='center' nowrap>".$ult_inte."</TD>");
                        	} else {
                            	fwrite($file,"<TD align='center' nowrap>");
								if($login_fabrica == 85 and strlen($codigo_posto) > 1) 
	                            	fwrite($file,"$codigo_posto -");
                            	fwrite($file," $posto_nome</TD>");
                        	}
                        }

						if(in_array($login_fabrica,array(74))){
							fwrite($file,"<TD align='center' $cor_linha>$providencia_data</TD>");
						}
                        if($login_fabrica == 30){ //hd_chamado=2902269
                        	if($origem == 'fale'){
		                		$origem = "Site Esmaltec";
		                	}
                            fwrite($file,"<TD align='center' $cor_linha>$origem</TD>");

                            $arr_campos_adicionais = json_decode($arr_campos_adicionais, true);

                            $numero_sr          = $arr_campos_adicionais['numero_sr'];
                            $numero_process     = $arr_campos_adicionais['numero_process'];
                            $data_recebimento   = $arr_campos_adicionais['data_recebimento'];
                            $data_fatal         = $arr_campos_adicionais['data_fatal'];
                            $info_notificado    = $arr_campos_adicionais['info_notificado'];
                            $nota_consumidor    = $arr_campos_adicionais['nota_consumidor'];
                            $id_reclamacao      = $arr_campos_adicionais['id_reclamacao'];

                            fwrite($file,"<TD align='center' $cor_linha>$numero_sr</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$numero_process</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$data_recebimento</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$data_fatal</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$info_notificado</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$nota_consumidor</TD>");
                            fwrite($file,"<TD align='center' $cor_linha>$id_reclamacao</TD>");
                        }

                        if(in_array($login_fabrica, array(85))){
                        	
							fwrite($file,"<TD align='center' $cor_linha>".stripcslashes($data_previsao_ambev)."</TD>");
							fwrite($file,"<TD align='center' $cor_linha>$data_primeira_visita</TD>");
						}

                        if(in_array($login_fabrica, array(169,170,175,177,178))){
							fwrite($file,"<TD align='center' $cor_linha>$origem</TD>");
						}

                        if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || !in_array($login_fabrica, [85,189]))
                        fwrite($file,"<TD align='center' $cor_linha nowrap>$sua_os</TD>");

                    	if ($login_fabrica == 183 AND $pesquisa_classificacao == true){
                    		fwrite($file,"<TD align='center' $cor_linha nowrap>$revenda_nome</TD>");
                    		fwrite($file,"<TD align='center' $cor_linha nowrap>$revenda_cnpj</TD>");
                    	}

                        fwrite($file,"<TD align='center' $cor_linha nowrap>$status</TD>");
	                    if (in_array($login_fabrica, array(178))) {
                        	fwrite($file,"<TD align='center' $cor_linha nowrap>$resposta_consumidor</TD>");
	                    }

                        if (in_array($login_fabrica, array(169,170))) {
                            fwrite($file,"<TD align='center' nowrap>$posto_nome</TD>");
                        }

						if ($login_fabrica == 86) {
							fwrite($file,"<TD align='center' nowrap>$dias_aberto</TD>");
						}
						if($login_fabrica == 136){

							$sql_pedido = "SELECT pedido FROM tbl_hd_chamado_extra WHERE hd_chamado = {$callcenter}";
						    $res_pedido = pg_query($con, $sql_pedido);

						    $pedido = (pg_num_rows($res_pedido) > 0) ? pg_fetch_result($res_pedido, 0, "pedido") : "";

							fwrite($file, "
								<td>{$endereco}</td>
								<td>{$numero}</td>
								<td>{$complemento}</td>
								<td>{$bairro}</td>
								<td>{$cep}</td>
								<td>{$origem}</td>
								<td>{$pedido}</td>
							");
						}


					break;
				}
				fwrite($file,"</TR>");
			}
			if ($login_fabrica == 11) {
				$colspan_t = 13;
			} else if($login_fabrica == 30) {
				$colspan_t = 16;
			} else if($login_fabrica == 50) {
				$colspan_t = 12;
			} else if ($login_fabrica == 115){
                $colspan_t = 21;
            } else if ($login_fabrica == 85) {
                $colspan_t = 15;
                $num_chamados = ($aux_contador > 0) ? $aux_contador : $num_chamados;
			} else if ($login_fabrica == 174) {
				$colspan_t = 13;
			} else if ($login_fabrica == 52) {
				$colspan_t = 20;
			}else if (in_array($login_fabrica,array(175))){
				$colspan_t = 14;
			}else if ($login_fabrica == 177){
				$colspan_t = 21;
			}else if ($login_fabrica == 178){
				$colspan_t = 20;
			} else if ($login_fabrica == 186) {
				$colspan_t = 13;
			}else if ($login_fabrica == 183){
				$colspan_t = 16;
				if ($pesquisa_classificacao == true){
					$colspan_t = 18;
				}
			} else {
				$colspan_t = 11;
			}
			fwrite($file, "
						<tr>
							<th colspan='".$colspan_t."' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".$num_chamados." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}

	if($_REQUEST['gerar_resumo']){
		
		$sql = "SELECT  count(tbl_hd_chamado.hd_chamado) AS qtde,
				tbl_hd_chamado_origem.descricao AS origem,
				tbl_hd_classificacao.descricao AS classificacao
			INTO TEMP tmp_resumo
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			$JOIN_ITEM
			LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
			LEFT JOIN tbl_cidade on tbl_cidade.cidade = tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_posto_fabrica on tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
			and tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
			LEFT JOIN tbl_cliente_admin on tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao
			AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			$cond_hd_chamado_intervencao
			LEFT JOIN tbl_hd_classificacao ON tbl_hd_chamado.hd_classificacao = tbl_hd_classificacao.hd_classificacao
			LEFT JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem
			{$join_tbl_os}
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.titulo !~* 'HELP-DESK'
			AND $cond1
			AND $cond2
			AND $cond3
			AND $cond4
			AND $cond5
			AND $cond6
			AND $cond7
			AND $cond8
			AND $cond9
			AND $cond10
			AND $cond11
			AND $cond12
			AND $cond13
			AND $cond14
			AND $cond15
			AND $cond16
			AND $cond17
			AND $cond18
			AND $cond19
			AND $cond20
			AND $cond21
			AND $cond22
			AND $cond23
			AND $cond24
			AND $cond27
			AND $cond28
			AND $cond85
			AND $cond86
			AND $cond_hd_chamado_status
			$cond25
			$cond_origem
			$cond_posto_estado
			$cond_cnpj_revenda
			$cond_familia
			$cond_familia_prod
			$cond_linha_prod
			$cond_marca_logo
			$cond26
			$cond40
			$cond_lote
			$sql_codigo_postagem
			$condFechamento
			$cond_fale_conosco
			GROUP BY tbl_hd_chamado_origem.descricao,
			tbl_hd_classificacao.descricao;
			SELECT * FROM tmp_resumo ORDER BY origem,classificacao;";
		$resSubmit = pg_query($con,$sql);
		$dadosResumo = pg_fetch_all($resSubmit);

		foreach($dadosResumo AS $key => $value){

			$array_classificacao[] = $value['classificacao'];

		}
		$array_classificacao = array_unique($array_classificacao);

		$cabecalho = "Origem;Total de Contatos;".implode(";",$array_classificacao)."\n";

		foreach($dadosResumo AS $key => $value){
			$array_origem[] = $value['origem'];
		}

		$array_origem = array_unique($array_origem);

		foreach($array_origem AS $key => $value){

			$sql = "SELECT SUM(coalesce(qtde,0)) AS total_origem FROM tmp_resumo WHERE origem = '{$value}'";
			$res = pg_query($con,$sql);

			$totalOrigem = pg_fetch_result($res,0,'total_origem');
			
			$linha .= $value.";$totalOrigem;";
			$qtde_origem_classificacao = array();

			foreach($array_classificacao AS $k => $v){
				if(strlen($v) == 0) continue;

				$sql = "SELECT coalesce(qtde,0) AS qtde FROM tmp_resumo WHERE origem = '{$value}' AND classificacao = '{$v}'";
				$res = pg_query($con,$sql);

				$qtde_origem_classificacao[] = (pg_fetch_result($res,0,'qtde') > 0) ?pg_fetch_result($res,0,'qtde') : 0;

			}

			$linha .= implode(";",$qtde_origem_classificacao) ."\n";
		}

		$data = date('Y-m-d-H-i');

		$fileName = "resumo_atendimento-{$login_fabrica}-{$data}.csv";
		$file = fopen("/tmp/{$fileName}", "w");
		fwrite($file,$cabecalho);
		fwrite($file,$linha);
		fclose($file);
		if (file_exists("/tmp/{$fileName}")) {
			system("mv /tmp/{$fileName} xls/{$fileName}");
			echo "xls/{$fileName}";
		}

		exit;
	}

	$arrayLegenda = array(
		array(
			"cor" => "#FF0000",
			"titulo" => traduz("Atendimentos abertos a mais de 30 dias"),
		),
	   array(
			"cor" => "#FFFF00",
			"titulo" => traduz("Data de providência que se encerra hoje."),
		)
	);
}

$layout_menu = "callcenter";
$title = traduz("RELAÇÃO DE ATENDIMENTOS LANÇADOS ");

include "cabecalho_new.php";

$plugins = array("dataTable");

include("plugin_loader.php");
?>
<style type="text/css">
	#content{
		margin: 0 auto;
	}

	.cor_legenda{
		width: 10px !important;
		height: 10px !important;
		padding: 5px !important;
	}

	.table tbody tr.warning > td {
	  background-color: #ffff00 !important;
	}
	.mais_30 {
	  background-color: #ff0000 !important;
	}
</style>
<script language="javascript">
         var checksJson = new Array();
    function addJsonAcao(el,chamado){

        if( $(el).is(":checked")){
            var adiciona = true;
            checksJson.forEach(function(el,i){
                if(el.chamado == chamado){
                    adiciona=false;

                }
            } );
            if(adiciona){
                 checksJson.push({"chamado":chamado});
            }

        }else{
            var remove =false;
            var indice;
            checksJson.forEach(function(el,i){
                if(el.chamado == chamado){
                    remove = true
                    indice = i;
                }
            });
            if(remove){
                console.log(indice);
                checksJson.splice(indice,1);
            }
        }
        console.log(checksJson);
    }

    <?php if($login_fabrica == 24){ ?>

        function verificaChamadoAnterior(callcenter, cpf, produto, data_nf, nf, categoria){
            $.ajax({
                type:"post",
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{
                verificaChamadoAnterior: true,
                    cpf: cpf,
                    produto : produto,
                    data_nf:data_nf,
                    nf:nf,
                    callcenter:callcenter
                },
                complete: function(data){
                    var respJson = $.parseJSON(data.responseText);

                    if(respJson.retorno == "encontrou"){
                        var r = confirm("Existe um atendimento de nº "+respJson.hd_chamado+"  mais recente, deseja abri-lo?");
                        if (r == true) {
                            window.open("callcenter_interativo_new.php?callcenter="+respJson.hd_chamado+"#"+categoria);
                        } else {
                            window.open("callcenter_interativo_new.php?callcenter="+callcenter+"#"+categoria);
                        }
                    }else{
                        window.open("callcenter_interativo_new.php?callcenter="+callcenter+"#"+categoria);
                    }
                }
            });
        }
    <?php } ?>

  function gravaStatus(){
     if(checksJson.length > 0){
          if(confirm("Deseja realmente alterar o Status?")){
               var  status_interacao = $("#status_interacao").val();

           	 $.ajax({
                 type:"post",
           	   url: "<?php echo $_SERVER['PHP_SELF']; ?>",
           	   data:{
           			json_chamados: checksJson,
           			 status_interacao: status_interacao
                 },
           		complete: function(data){
                   var respJson = $.parseJSON(data.responseText);
                   if(respJson.erro == "erro"){
                       alert(respJson.msg);
                   }else{
                       checksJson.forEach(function(el,i){
                           $("#status_"+el.chamado).html(status_interacao);
                       });
                       alert(respJson.msg);
                   }

           	   }
           	 });
          }
     }

  }

$(function () {
	$("#gerar_resumo").click(function () {
		if (ajaxAction()) {
			var json = $.parseJSON($("#jsonPOSTResumo").val());
			json["gerar_resumo"] = true;
			json["gerar_excel"] = "";

			$.ajax({
				url: "/assist/admin/callcenter_consulta_lite_interativo.php",
					type: "POST",
					data: json,
					beforeSend: function () {
					loading("show");
				},
					complete: function (data) {
					window.open(data.responseText, "_blank");
					loading("hide");
				}
			});
		}
	});

	$(".submit_pesquisa_os").click(function(){

		$(this).closest("form[name=form_pesquisa]").submit();

	});

});
</script>
<?php

if(strlen($msg_erro) == 0){

	if ($login_fabrica == 30) {

	if (pg_num_rows($res_resumo)>0) { ?>
		<table class="table table-bordered" style="width: 100%;">
			<thead>
				<tr class="titulo_tabela">
					<td colspan="3" class="tac"> <?=traduz('Resumo dos chamados por atendente')?></td>
				</tr>
				<tr class="titulo_coluna">
					<td> <?=traduz('Atendente')?></td>
					<td width="20%" calss="tac"> <?=traduz('Chamados Abertos')?></td>
					<td width="20%" calss="tac"> <?=traduz('Chamados Resolvidos')?></td>
				</tr>
		</thead>
			<?

			while($row = pg_fetch_row($res_resumo)) {
				$a_tabela[$row[0]][$row[1]] = $row[2].','.$row[3];
			}
			$params = $_SERVER['QUERY_STRING'];
			foreach ($a_tabela as $nome  => $a_info) { ?>
				<?
				$aberto = explode(',',$a_info['Aberto']);
				$resolvido = explode(',',$a_info['Resolvido']);
				?>
				<tr>
					<td><?=$nome?></td>
					<td class="tac"><a href='<?=$PHP_SELF?>?<?=$params?>&por_atendente=1&situacao=PENDENTES&atendente=<?=$aberto[0]?>'><?=floatval($aberto[1])?></a></td>
					<td class="tac"><a href='<?=$PHP_SELF?>?<?=$params?>&por_atendente=1&situacao=SOLUCIONADOS&atendente=<?=$resolvido[0]?>'><?=floatval($resolvido[1])?></td>
				</tr>
<?			}?>
		</table>
<br><br>
<?
	}
	}
		// BTN_NOVA BUSCA



if($num_chamados > 0){
	if ($num_chamados > 500) {
		$count = 500;
?>
		<div id='registro_max'>
			<h6><?=traduz('Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.')?></h6>
		</div>
<?php
	} else {
		$count = $num_chamados;
	}

	if ($login_fabrica == 74) {
?>
		<div class="container">
			<table>

				<?php
				foreach ($arrayLegenda as $key => $legenda) {
				?>
					<tr>
					<td class="cor_legenda" style="background-color:<?=$legenda['cor']?>"></td>
					<td class="titulo_legenda"><?=$legenda['titulo']?></td>
					</tr>
				<?php
				}
				?>

			</table>
		</div>
<?php
    }
	if ($login_fabrica == 85) {
?>
		<div class="container">
			<table>
				<tr>
					<td class="cor_legenda" style="background-color:#00FF00"></td>
					<td class="titulo_legenda"><?=traduz('Atendimento não resolvidos com OS resolvidas')?></td>
				</tr>
			</table>
		</div>
		<div class="container">
            <table>
                <tr>
                    <td class="cor_legenda" style="background-color:#B0C4DE"></td>
                    <td class="titulo_legenda"><?=traduz('Atendimento com OS Vinculadas e fechadas em 36hrs')?></td>
                </tr>
            </table>
        </div>

<?php
    }
    if (in_array($login_fabrica, array(169,170))) {
?>
        <div class="container">
            <table>
                <tr>
                    <td class="cor_legenda" style="background-color:#ED8F66"></td>
                    <td class="titulo_legenda"><?=traduz('Atendimento com acompanhamento de Jornada do Callcenter')?></td>
                </tr>
            </table>
        </div>
<?php
    }

	if($login_fabrica == 85 && $areaAdminCliente == true){
		$programaphp = "pre_os_cadastro_sac.php";
	}else{
		$programaphp = "callcenter_interativo_new.php";
	}
	echo "<input type='hidden' id='rows' value='{$num_chamados}' />"; ?>
	</div>
		<table id='content' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna'>
				<?php

				switch($login_fabrica) {
					case 5:echo "
						 <TH>Nº Chamado</TH>
								<TH>Data</TH>
								<TH>Consumidor</TH>
								<TH>Produto</TH>
								<TH>Status</TH>
								<TH>Providência</TH>
								<TH>Data Providência</TH>
								<TH>Atendente</TH>";
					break;

					case 52: echo "
						<TH>Atendente</TH>
								<TH>Atendente Abertura</TH>
								<TH>Data</TH>
								<TH>Produto</TH>
								<TH>Série</TH>
								<TH>Marca</TH>
								<TH>Nº Chamado</TH>
								<TH>Consumidor</TH>
								<TH>E-mail</TH>
								<TH>Cidade <br /> (Consumidor)</TH>
								<TH>Estado <br /> (Consumidor)</TH>
								<TH>País <br /> (Consumidor)</TH>
								<TH>Cliente</TH>
								<TH>Cidade</TH>
								<TH>Estado</TH>
								<TH>Posto</TH>
								<TH>Cidade<br/>(Posto)</TH>
								<TH>Estado<br/>(Posto)</TH>
								<TH>OS</TH>
								<TH>Status OS</TH>
								<TH>Status Call-Center</TH>
								<TH>Classificação do Atendimento</TH>";
					break;

					case 24: echo "
						<TH>Origem do chamado</TH>
								<TH>Atendente Responsável</TH>
								<TH>Interventor</TH>
								<TH>Produto</TH>
								<TH>Status</TH>
								<TH>Data Recebimento/Abertura</TH>
								<TH>Data Máxima para Solução</TH>
								<TH>Ligação Agendada</TH>
								<TH>Nº Chamado</TH>
								<TH>Ordem de Serviço</TH>
								<TH>Cliente</TH>
								<TH>Cidade</TH>
								<TH>Estado</TH>
								<TH>Categoria</TH>";
					break;

					case 90: echo "
						<TH>Atendente</TH>
								<TH>Data</TH>
								<TH>Referência do Produto</TH>
								<TH>Nº Chamado</TH>
                                <TH>Defeito Reclamado</TH>
								<TH>Cliente</TH>
								<TH>Telefone</TH>
								<TH>Cidade</TH>
								<TH>Estado</TH>
								<TH>Posto</TH>
								<TH>OS</TH>
								<TH>Status</TH>
								<th>Número IBBL</th>";
					break;

					default:
                        if($login_fabrica == 35){
                            echo "<th>&nbsp</th>";
						}
						if($login_fabrica == 81){
							$gravacao  = array(
                                'http://callcenter.telecontrol.com.br/records/2015/10/1126720015-20150210-131021-P0C01-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/10/1128890572-20150210-101953-P0C02-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/10/1132592309-20150210-092309-P0C02-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/10/1132592309-20150210-094026-P0C00-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/10/1132592309-20150210-094137-P0C00-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-102319-P0C01-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-113656-P0C01-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-125749-P0C00-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-143942-P0C01-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-144007-P0C01-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1121385028-20150213-163842-P0C02-Station.wav',
                                'http://callcenter.telecontrol.com.br/records/2015/13/1123446200-20150213-114802-P0C26-Station.wav'
                            );
                            echo "<th>".traduz('Gravação')."</th>";
                        }

			if(in_array($login_fabrica,[169,170])){
				echo "<th>".traduz('Protocolo Via')."</th>";
			}

						echo "<TH>".traduz('Aberto por')."</TH>";
						echo "<TH>".traduz('Atendente Atual')."</TH>";

						if (in_array($login_fabrica, [186])) {
							echo "<TH >Posto Indicado</TH>";
						}

						if ($login_fabrica == 35) {
							echo "<TH >Última Transferência</TH>";
						}

						if($login_fabrica == 50){
							echo "<th>Tipo de Atendimento</th>";
						}

						if ($login_fabrica == 11) {
							echo "<TH>Admin Responsável</TH>";
						}

						if($telecontrol_distrib or $interno_telecontrol) {
							echo "<TH>Origem</TH>";
						}

                        echo "<TH>".traduz('Data')."</TH>";
						if($login_fabrica == 80){
							echo "<TH>".traduz('Data de Finalização do Protocolo')."</TH>";
						}
						if($login_fabrica == 30){
                            echo "
                                <TH>Data de Conclusão</TH>
								<TH>Tempo de Conclusão</TH>
                                <TH>Motivo</TH>
                            ";
                        }

						if($login_fabrica == 74){
							echo "<TH>Visita Técnico</TH>";
                        }

                        if ($login_fabrica == 85) {


                        	if (!empty($check_tec_esporadico)) echo "<TH>Data Encerramento</TH>";	

                        	echo "<TH>Referência</TH>";
                        }

                        if (!in_array($login_fabrica, [189])) {
						echo "<TH>".traduz('Produto')."</TH>";
                        }

						if($login_fabrica == 42){
							echo "<TH>Referência do Atendimento</TH>";
						}

						if (in_array($login_fabrica,array(52,85))) {
                        				echo "<TH>Série</TH>";
                        			}

						if ($login_fabrica == 80) {
							echo "<TH>Data de Compra</TH>";
						}

						if($login_fabrica == 137){
							echo "<TH>Nº Lote</TH>";
							echo "<TH>Defeito</TH>";
						}

						if ($login_fabrica == 183){
							echo "<TH nowrap>Familia do Produto</TH>";
							echo "<TH nowrap>Linha do Produto</TH>";
							echo "<TH>Defeito Reclamado</TH>";
						}

						if(in_array($login_fabrica,array(15,30,45,140))){
							echo "<TH>Defeito</TH>";
						}else if(in_array($login_fabrica, array(120,201,50))){ //HD-3282875 adicionada fabrica 50
                            echo "<TH>Defeito Reclamado</TH>";
                        }

                        /*HD - 4382764*/
                        if ($login_fabrica == 174) {
                        	echo "<TH>Nome da Loja</TH>";
                        }

                        echo "<TH>".traduz('Nº Chamado')."</TH>";

                        if ($login_fabrica == 183){
                        	echo "<th>Pedido SAP</th>";
                        }

                        if($login_fabrica == 115){
                            echo "<TH>At. Relacionado</TH>";
                        }

						if($login_fabrica == 7){
                            echo "<TH>DR</TH>";
                        }else{
                            echo "<TH>".traduz('Cliente')."</TH>";
                        }
                        if($login_fabrica == 115){
                            echo "<TH>Email Cliente</TH>";
                        }

                        if($login_fabrica == 30){ //hd_chamado=2902269
                        	echo "<TH> Tipo </TH>";
                            echo "<TH>Telefone </TH>";
                            echo "<TH>Telefone Posto</TH>";
                        }

                        if($login_fabrica == 45 || $login_fabrica == 140){
                        	$cnpj = ($login_fabrica == 140) ? "/CNPJ" : "";
                            echo "<TH>CPF{$cnpj}</TH>";
                        }

                        if($login_fabrica == 85 && empty($check_tec_esporadico)){
                        	echo "<TH>Cliente Admin</TH>";
                        }

                        echo "
                            <TH>".traduz('Cidade')."</TH>
                            <TH>".traduz('Estado')."</TH>
						";
						
						if($login_fabrica == 80){
							echo "
								<TH>".traduz('Telefone do Cliente')."</TH>
								<TH>".traduz('E-mail do Cliente')."</TH>";
						}
                        if($login_fabrica == 30){
                        	echo "<TH>Nome Revenda</TH>";
                        }

                        if($login_fabrica == 85 && !empty($check_tec_esporadico)){
                        	echo "<TH>Técnico Esporádico</TH>";
                        	echo "<TH>Valor do Serviço Combinado</TH>";
                        	echo "<TH>Última Resposta</TH>";
                        }

					    if($login_fabrica == 11){
                            echo "<TH>Motivo Ligação</TH>";
                        }

                        if($moduloProvidencia  || $classificacaoHD){
                        	$label_prov = "Providência";
                        	$label_class = "Classificação";
                        	if ($login_fabrica == 189) {
	                        	$label_prov = "Ação";
	                        	$label_class = "Registro Ref. a";
                        	} 

                        	if ($login_fabrica != 80) {
                            	echo "<TH>$label_prov</TH>";
                        	}
                        	if ($login_fabrica == 189) {
                            	echo "<TH>Data Ação</TH>";
                        	}
                            echo "<TH>$label_class</TH>";

                            if(in_array($login_fabrica, array(30,169,170))){
                                echo "<TH>Origem</TH>";
                            }

                            if (in_array($login_fabrica, [169, 170])) {
                            	echo "<TH>Providência nv. 3</TH>
                            		  <TH>Motivo Contato</TH>";
                            }

                        }else{
                        	if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || $login_fabrica != 85)
                            	echo "<TH>Posto</TH>";
                        }

                        if ($login_fabrica == 85) {
                        	echo "<th>Data de Previsão</th>";
                            echo "<th>Data 1ª Visita</th>";
                        }

                        if (in_array($login_fabrica,array(175,177,178))){
                        	echo "<TH>Origem</TH>";
                        }

                        if($login_fabrica == 136){
						    echo "<TH>Origem</TH>";
						    echo "<TH>Pedido</TH>";
                        }

                        if($login_fabrica == 74){
                  	        echo "<th>Data Providência</th>";
                        }

                        if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || !in_array($login_fabrica, [85,189]))
                        	echo "<TH>OS</TH>";

						if($login_fabrica == 80){
							echo "<TH>".traduz('Status da OS')."</TH>";
						}
                        if ($login_fabrica == 183 AND $pesquisa_classificacao == true){
                        	echo "<TH>".traduz('CNPJ Revenda')."</TH>";
                        	echo "<TH>".traduz('Nome Revenda')."</TH>";
                        }

                        echo "<TH>".traduz('Status')."</TH>";

                        if (in_array($login_fabrica, array(169,170))) {
                            echo "<TH>Posto</TH>";
                        }


                        if ($login_fabrica == 86) {
                            echo "<th>Dias em Aberto</th>";
                        }
					break;
                }

                if ($login_fabrica == 125) $aux_colspan = " colspan=4 ";

				if($login_fabrica != 74){
					echo "<TH width='85' $aux_colspan;>Ação</TH>";
                }

				echo "</tr>";
				echo "</thead>";
				echo "<tbody>";

		$aux_contador = 0;
		for ($j = 0; $j < $count; $j++){

            if($login_fabrica == 24){
                $produto_id = trim(pg_fetch_result($resHD, $j, 'produto'));
                $nota_fiscal = trim(pg_fetch_result($resHD, $j, 'nota_fiscal'));
            }

			$callcenter         = trim(pg_fetch_result ($resHD,$j,'hd_chamado'));
			$data_hd_chamado    = trim(pg_fetch_result ($resHD,$j,'data_hd_chamado'));
			$data               = trim(pg_fetch_result ($resHD,$j,'data'));
			$data_nf            = trim(pg_fetch_result ($resHD,$j,'data_nf'));
			$data_maxima        = pg_fetch_result($resHD,$j,'data_maxima');
			$sua_os             = trim(pg_fetch_result ($resHD,$j,'sua_os'));
			$origem             = trim(pg_fetch_result ($resHD,$j,'origem'));
			$os                 = trim(pg_fetch_result ($resHD,$j,'os'));
			$dias_aberto        = trim(pg_fetch_result ($resHD,$j,'dias_aberto'));
			if (strlen($campo_os)>0) {
				$os_item        = trim(pg_fetch_result ($resHD,$j,'os_item'));
			}

			if($login_fabrica == 42){
				$tipo_registro = pg_fetch_result($resHD, $j, 'tipo_registro');
			}
			$serie              = trim(pg_fetch_result ($resHD,$j,'serie'));
			$consumidor_nome    = trim(pg_fetch_result ($resHD,$j,'consumidor_nome')); //hd_chamado=2683927
            $email_consumidor   = trim(pg_fetch_result($resHD, $j, 'email_consumidor'));
			$consumidor_telefone= trim(pg_fetch_result ($resHD,$j,'consumidor_telefone'));
			$consumidor_cpf     = trim(pg_result ($resHD,$j,'cpf'));
			$posto_nome         = trim(pg_fetch_result ($resHD,$j,'posto_nome'));
			$codigo_posto       = trim(pg_fetch_result ($resHD,$j,'codigo_posto'));
			$posto_cidade       = trim(pg_fetch_result ($resHD,$j,'posto_cidade'));
			$posto_estado       = trim(pg_fetch_result ($resHD,$j,'posto_estado'));
			$produto_nome       = trim(pg_fetch_result ($resHD,$j,'produto_nome'));
			$produto_referencia = trim(pg_fetch_result ($resHD,$j,'produto_referencia'));
			$defeito_reclamado  = trim(pg_fetch_result ($resHD,$j,'defeito_reclamado_descricao'));
			$status             = trim(pg_fetch_result ($resHD,$j,'status'));
			$providencia        = trim(pg_fetch_result ($resHD,$j,'providencia'));
			$providencia_data	= trim(pg_fetch_result ($resHD,$j,'providencia_data'));
			$atendente          = trim(pg_fetch_result ($resHD,$j,'atendente'));
			$categoria          = trim(pg_fetch_result ($resHD,$j,'categoria'));
			$admin              = trim(pg_fetch_result ($resHD,$j,'admin'));
			$intervensor        = trim(pg_fetch_result ($resHD,$j,'intervensor'));
			$nome_cliente       = trim(pg_fetch_result ($resHD,$j,'nome'));
			$cidade				= trim(pg_fetch_result ($resHD,$j,'cidade'));
			$estado             = trim(pg_fetch_result($resHD,$j,'estado'));
			$nome_cidade		= trim(pg_fetch_result ($resHD,$j,'nome_cidade'));
			$consumidor_estado	= trim(pg_fetch_result ($resHD,$j,'consumidor_estado'));
			$protocolo_cliente	= trim(pg_fetch_result ($resHD,$j,'protocolo_cliente'));
			$hd_motivo_ligacao	= trim(pg_fetch_result ($resHD,$j,'hd_motivo_ligacao'));
			$array_campos_adicionais = pg_fetch_result($resHD, $j, 'array_campos_adicionais');
			$marca_logo				= trim(pg_fetch_result ($resHD,$j,'marca'));
			$defeito_reclamado_arge  = trim(pg_fetch_result ($resHD,$j,'defeito_reclamado'));
			$classificacao_atendimento  = trim(pg_fetch_result ($resHD,$j,'classificacao_atendimento'));
            $fone_posto = trim(pg_fetch_result($resHD, $j, 'fone_posto'));
            $hd_consumidor_revenda  = pg_fetch_result($resHD, $j, 'hd_consumidor_revenda');
            $motivo_contato_callcenter = pg_fetch_result($resHD, $j, 'descricao_motivo_contato');
            $providencia_nivel3 = pg_fetch_result($resHD, $j, 'descricao_providencia');

            if($login_fabrica == 30){
            	$hd_consumidor_revenda  = pg_fetch_result($resHD, $j, 'hd_consumidor_revenda');
            	$revenda_nome = pg_fetch_result($resHD, $j, revenda_nome);
            }     

            if ($login_fabrica == 35) {
            	$ultima_transferencia = pg_fetch_result($resHD, $j, "ultima_transferencia");
            	#$ultima_transferencia = "teste";
            }       

            if ($login_fabrica == 183){
				$codigo_linha 		= pg_fetch_result($resHD, $j, "codigo_linha");
				$nome_linha  		= pg_fetch_result($resHD, $j, "nome_linha");
				$descricao_familia 	= pg_fetch_result($resHD, $j, "descricao_familia");

				$revenda_nome 		= pg_fetch_result($resHD, $j, "revenda_nome");
				$revenda_cnpj 		= pg_fetch_result($resHD, $j, "revenda_cnpj");
			}

		if(in_array($login_fabrica,[169,170])){
			$protocolo_via = pg_fetch_result($resHD, $j, "protocolo_via");
		}

            if ($login_fabrica == 52) {
            	$array_campos_adicionais = json_decode($array_campos_adicionais, true);

            	if (!empty($array_campos_adicionais["pais"])) {
            		$consumidor_pais = $array_campos_adicionais["pais"];
            	} else {
            		$consumidor_pais = "";
            	}

            	if (!empty($consumidor_pais_filtro) && !empty($check_consumidor_pais)) {
            		if (strlen($consumidor_pais) == 0) {
            			continue;
            		} else if ($consumidor_pais_filtro != $consumidor_pais) {
            			continue;
            		}
            	}
            }

            /*HD- 4258409*/
			if ($login_fabrica == 85 && !empty($check_tec_esporadico)) {
				$json_campos_add   = json_decode($array_campos_adicionais, true);
				$hd_tec_esporadico = $json_campos_add["tecnico_esporadico_id"];
				$pesquisar_tecnico_esporadio = false;

				if (!empty($tecnico_esporadico_id)) {
					if ($tecnico_esporadico_id != $hd_tec_esporadico) {
						continue;
					} else {
						$pesquisar_tecnico_esporadio = true;
					}
				} else if (empty($hd_tec_esporadico)) {
					continue;
				} else {
					$pesquisar_tecnico_esporadio = true;
				}

				if ($pesquisar_tecnico_esporadio == true) {
					/*Data de Encerramento*/
					$aux_sql   = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data_encerramento FROM tbl_hd_chamado_item WHERE hd_chamado = $callcenter AND status_item = 'Resolvido' LIMIT 1";
					$aux_res   = pg_query($con, $aux_sql);
					$rows      = pg_num_rows($aux_res);
					$encerrado = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'data_encerramento') : "";

					/*Técnico Esporádico*/
					$aux_sql = "SELECT codigo_externo || ' - ' || nome AS tecnico_esporadico FROM tbl_tecnico WHERE tecnico = $hd_tec_esporadico";
					$aux_res = pg_query($con, $aux_sql);
					$rows    = pg_num_rows($aux_res);
					$tecnico = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'tecnico_esporadico') : "";

					/*Valor do Serviço Combinado*/
					$vl_ser  = "R$ " . str_replace(".", ",", $json_campos_add["valor_servico_combinado"]);

					/*Última interação do atendente para o cliente*/
					$aux_sql = "SELECT comentario FROM tbl_hd_chamado_item WHERE hd_chamado = $callcenter AND comentario IS NOT NULL AND interno IS NOT TRUE ORDER BY tbl_hd_chamado_item DESC LIMIT 1";
					$aux_res   = pg_query($con, $aux_sql);
					$rows      = pg_num_rows($aux_res);
					$ult_inte  = ($rows > 0) ? pg_fetch_result($aux_res, 0, 'comentario') : "";

					if (empty($ult_inte)) {
						$quebra   = array(";", "\n", "&nbsp;");
						$ult_inte = strip_tags($ult_inte);
						$ult_inte = str_replace($quebra, "", $ult_inte);
					}
				}
			}

            if($login_fabrica == 115){ //hd_chamado=2710901
                $hd_chamado_anterior = pg_fetch_result($resHD, $j, 'hd_chamado_anterior');
			}

            if(in_array($login_fabrica, array(169,170,175,177,178))){
				$origem  = pg_fetch_result($resHD, $j, 'origem');
				$jornada = pg_fetch_result($resHD, $j, 'jornada');
            }

            if($login_fabrica == 24){
                $verificaChamadoAnterior = array_search($callcenter, array_column($dadosCompleto, 'hd_chamado_anterior'));
                $temChamado = ($verificaChamadoAnterior !== false) ? true : false;
            }

			if ($login_fabrica == 52) {
				if (strlen($marca_logo)>0) {
					$sqlx="select nome from  tbl_marca where marca = $marca_logo;";
					$resx=pg_exec($con,$sqlx);
					$marca_logo         = pg_fetch_result($resx, 0, 'nome');
				}
			}
			if (in_array($login_fabrica,array(15,30,50,90,120,201,137,140,183))) { //HD-3282875 adicionada fabrica 50
				unset($defeito_reclamado_arg);
				if(strlen($defeito_reclamado_arge) > 0){
					$sqlx="select descricao from  tbl_defeito_reclamado where defeito_reclamado = '$defeito_reclamado_arge';";
					$resx=pg_exec($con,$sqlx);
					$defeito_reclamado_arg         = (in_array($login_fabrica, array(50))) ? trim(pg_fetch_result($resx, 0, 'descricao')) : strtoupper(trim(pg_fetch_result($resx, 0, 'descricao')));

					if($login_fabrica == 50){//HD-3282875 adicionada fabrica 50
						$defeito_reclamado_arg = uper_acentos($defeito_reclamado_arg);
					}
				}
			}

			if ($login_fabrica == 86) {
				$dias_aberto = str_replace("-", "", $dias_aberto);
			}

			if ($login_fabrica == 86 && in_array($status, array("Cancelado", "Resolvido","RESOLVIDO"))) {
				$dias_aberto = "";
			}

			if ($login_fabrica != 24) {
				if (in_array($login_fabrica, array(74,81,183))) {
					$array_campos_adicionais = json_decode($array_campos_adicionais, true);
				} else {
					$array_campos_adicionais  = (explode("||",$array_campos_adicionais));

					foreach($array_campos_adicionais as $valores){
						list($key,$valor) = explode("=>",$valores);
						$$key = $valor;
					}
				}
			} else {
				$array_campos_adicionais = json_decode($array_campos_adicionais, true);

					if (array_key_exists("ligacao_agendada", $array_campos_adicionais) && (strlen($array_campos_adicionais["ligacao_agendada"]) > 0) ){
					list($laa, $lam, $lad) = explode("-", $array_campos_adicionais["ligacao_agendada"]);
					if(checkdate($lam, $lad, $laa)){
						$ligacao_agendada = "{$lad}/{$lam}/{$laa}";
					}else{
						$ligacao_agendada = "";
					}
				} else {
					$ligacao_agendada = "";
				}
			}

			if ($login_fabrica == 85) {

				$array_campos_adicionais = json_decode(current($array_campos_adicionais), true);
				$data_previsao_ambev     = $array_campos_adicionais['data_previsao_ambev'];
				$data_primeira_visita    = $array_campos_adicionais['data_primeira_visita'];
			}
			
			$status_checkpoint = "";
			if (strlen($os_item) > 0) {
				$sql_sc = "SELECT tbl_status_checkpoint.descricao,excluida, sua_os
					   FROM tbl_os
					   JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
					   WHERE os = $os_item";
				$res_sc = pg_query($con, $sql_sc);

				$status_checkpoint = pg_fetch_result($res_sc, 0, "descricao");
				$excluida = pg_fetch_result($res_sc, 0, "excluida");
				$sua_os   = pg_fetch_result($res_sc,0,"sua_os");
				if($excluida =='t') $status_checkpoint = "OS Excluída";
			}
			if (strlen($status_checkpoint) == 0) {
				$status_checkpoint = "Aberta Call-Center";
			}

			if(strlen($atendente) >0){
				$sqlx="SELECT login from tbl_admin where admin=$atendente";
				$resx=pg_exec($con,$sqlx);
				$atendente          = strtoupper(trim(pg_result ($resx,0,login)));
			}

			if(strlen($intervensor) >0){
				$sqlx="SELECT login from tbl_admin where admin=$intervensor";
				$resx=pg_exec($con,$sqlx);
				$intervensor          = strtoupper(trim(pg_result ($resx,0,login)));
			}

			if($login_fabrica == 5){
				# HD 58801
				$sqlx = "SELECT login from tbl_admin where admin=$admin";
				$resx = pg_exec($con,$sqlx);
				$atendente = strtoupper(trim(pg_result ($resx,0,login)));
			}

			if(strlen($admin) >0){
				$sqlx="SELECT login from tbl_admin where admin=$admin";
				$resx=pg_exec($con,$sqlx);
				$admin          = strtoupper(trim(pg_result ($resx,0,login)));
			}

			if (strlen (trim ($sua_os)) == 0) $sua_os = $os;
			if(strlen($os)> 0){
				$sqlx="SELECT sua_os
						FROM tbl_os
						WHERE os = $os";
				$resx=pg_exec($con,$sqlx);
				$sua_os = trim(pg_result ($resx,0,sua_os));
			}

			$cor = ($i % 2 == 0)? '#F1F4FA'	:"#F7F5F0";
			$btn = ($i % 2 == 0)? 'azul'	: 'amarelo';
			$mais_30 = "";
			if($login_fabrica == 74 AND $status != "Resolvido" AND $status != "RESOLVIDO" AND (strtotime($data_hd_chamado.'+ 30 days') < strtotime(date('Y-m-d')))){
				$mais_30 = "mais_30";
			}

			$data_conclusao = "";
			if($login_fabrica == 30 and $status=='RESOLVIDO'){
				$sql_conclusao = "SELECT to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') as data_conclusao
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_item ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
									WHERE tbl_hd_chamado.hd_chamado = $callcenter
									AND UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
									AND UPPER(status_item) = 'RESOLVIDO'
									ORDER BY hd_chamado_item LIMIT 1";
				$res_conclusao = pg_query($con,$sql_conclusao);
				if(pg_num_rows($res_conclusao) > 0){
					$data_conclusao = pg_fetch_result($res_conclusao, 0, 'data_conclusao');
				}
			}

			if($login_fabrica == 74){
				$date = date('d/m/Y');
				if($providencia_data === $date){
					$class = "class='warning'";
				}else{
					$class="";
				}
			}


			 if ($login_fabrica == 85 ){
                $tr = "";
                if (strlen($callcenter) > 0 and strlen($os) > 0) {
                    $sql_atendimento="
                        SELECT  tbl_os.data_digitacao_fechamento ,
                                tbl_os.data_fechamento,
                                tbl_hd_chamado.data AS abertura_atendimento
                        FROM    tbl_os
                        JOIN    tbl_hd_chamado_extra    USING(os)
                        JOIN    tbl_hd_chamado          ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
                        WHERE   tbl_hd_chamado.fabrica      = $login_fabrica
                        AND     tbl_hd_chamado.hd_chamado   = {$callcenter}
                        AND     tbl_os.os                   = {$os} ";
                    $res_atendimento = pg_query($con,$sql_atendimento);

                    if (pg_num_rows($res_atendimento) > 0) {
                        $os_finalizada          = pg_fetch_result($res_atendimento, 0, 'data_digitacao_fechamento');
                        $data_fechamento        = pg_fetch_result($res_atendimento, 0, 'data_fechamento');
                        $abertura_atendimento   = pg_fetch_result($res_atendimento, 0, 'abertura_atendimento');
                        if (strlen($os_finalizada) > 0 && $status != "Resolvido" && $status != "RESOLVIDO" and strlen($data_fechamento) > 0 ) {
                                $tr = " style='background-color:#00FF00' ";
                        }
                        if (strlen($os_finalizada) > 0 && strtotime($os_finalizada) < strtotime($abertura_atendimento.'+ 36 hours')) {
                            $tr = " style='background-color:#B0C4DE' ";
                        }
                    }
                }
            }


            if (in_array($login_fabrica, array(169,170))) {
				$style= "";

                if ($jornada == "t") {
                    $style = "style='background: #ED8F66 !important;'";
                }
            }
            echo "<TR $class $style>";

			switch($login_fabrica) {
				case 5:
				   echo "<TD class='tac' nowrap>$callcenter</TD>
							<TD class='tac' nowrap>$data</TD>
							<TD nowrap><ACRONYM TITLE='".$consumidor_nome."'>".substr($consumidor_nome,0,18)."</ACRONYM></TD>
							<TD nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>".substr($produto_nome,0,16)."</ACRONYM></TD>
							<TD class='tac' nowrap>$status</TD>
							<TD class='tac' nowrap>".substr($providencia,0,17)."</TD>
							<TD class='tac' nowrap>$providencia_data</TD>
							<TD class='tac' nowrap>$atendente</TD>";
				break;

				case 52:
					    echo "<TD class='tac' nowrap>$atendente</TD>
								<TD class='tac' nowrap>$admin</TD>
								<TD class='tac' nowrap>$data</TD>
								<TD nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>".$produto_nome."</ACRONYM></TD>
								<TD class='tac' nowrap>$serie</TD>
								<TD class='tac' nowrap>$marca_logo</TD>
								<TD class='tac' nowrap>$callcenter</TD>
								<TD nowrap>".$consumidor_nome."</TD>
                                <td>".$email_consumidor."</td>
								<TD class='tac' nowrap>$nome_cidade</TD>
								<TD class='tac' nowrap>$consumidor_estado</TD>
								<TD class='tac' nowrap>$consumidor_pais</TD>
								<TD class='tac'>$nome_cliente</TD>
								<TD class='tac' nowrap>$cidade</TD>
								<TD class='tac' nowrap>$estado</TD>
								<TD class='tac'>$posto_nome</TD>
								<TD class='tac'>$posto_cidade</TD>
								<TD class='tac'>$posto_estado</TD>
								<TD class='tac' nowrap><a href='os_press.php?os=$os_item' target='_blank'>$os_item</a></TD>
								<TD class='tac' nowrap>$status_checkpoint</TD>
								<TD class='tac' nowrap>$status</TD>
								<TD class='tac' nowrap>$classificacao_atendimento</TD>";
				break;

				case 24:
					if ($por_interventor == 1){
						echo "<TD class='tac' nowrap>$admin</TD>
							<TD class='tac' nowrap>$atendente</TD>
							<TD class='tac' nowrap>$intervensor_admin</TD>
							<TD nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>".substr($produto_nome,0,17)."</ACRONYM></TD>
							<TD class='tac' nowrap>$status</TD>
							<TD class='tac' nowrap>$data</TD>
							<TD class='tac' nowrap>$data_maxima</TD>
							<TD class='tac' nowrap>$ligacao_agendada</TD>
							<TD class='tac' nowrap>$callcenter</TD>
							<TD class='tac' nowrap><a href='os_press.php?os=$os' target='_blank'>$os</a></TD>
							<TD nowrap>".$consumidor_nome."</TD>
							<TD class='tac' nowrap>$nome_cidade</TD>
							<TD class='tac' nowrap>$consumidor_estado</TD>
							<TD class='tac'>$categoria</TD>";
					}else{
						echo "<TD class='tac' nowrap>$admin</TD>
							<TD class='tac' nowrap>$atendente</TD>
							<TD class='tac' nowrap>$intervensor</TD>
							<TD nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>".substr($produto_nome,0,17)."</ACRONYM></TD>
							<TD class='tac' nowrap>$status</TD>
							<TD class='tac' nowrap>$data</TD>
							<TD class='tac' nowrap>$data_maxima</TD>
							<TD class='tac' nowrap>$ligacao_agendada</TD>
							<TD class='tac' nowrap>$callcenter</TD>
							<TD class='tac' nowrap><a href='os_press.php?os=$os' target='_blank'>$os</a></TD>
							<TD nowrap>".$consumidor_nome."</TD>
							<TD class='tac' nowrap>$nome_cidade</TD>
							<TD class='tac' nowrap>$consumidor_estado</TD>
							<TD class='tac'>$categoria</TD>";
					}

				break;

				case 90:

                    $defeito_reclamado_ibbl = (empty($defeito_reclamado_arg)) ? $defeito_reclamado : $defeito_reclamado_arg; 
				    echo "<TD class='tac' nowrap>$atendente</TD>
							<TD class='tac' nowrap>$data</TD>
							<TD nowrap class='tac'><ACRONYM TITLE='$produto_referencia - $produto_nome'>".$produto_referencia."</ACRONYM></TD>
							<TD class='tac' nowrap>$callcenter</TD>
                            <TD class='tac' nowrap>$defeito_reclamado_ibbl</TD>
							<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,17)."</ACRONYM></TD>
							<TD class='tac' nowrap>$consumidor_telefone</TD>
							<TD class='tac' nowrap>$nome_cidade</TD>
							<TD class='tac' nowrap>$consumidor_estado</TD>
							<TD class='tac'>$posto_nome</TD>
							<TD class='tac' nowrap><a href='os_press.php?os=$sua_os' target='_blank'>$sua_os</a></TD>
							<TD class='tac' nowrap>$status</TD>\n
							<TD class='tac' nowrap>$protocolo_cliente</TD>";
				break;

				default:

                    if($login_fabrica == 35){
                        echo "<td><input type='checkbox' onchange='addJsonAcao($(this),$callcenter)' name='check_acao' value='$callcenter'/></td>";
					}

					if($login_fabrica == 81){
							$nu = rand(0,11);
							$link = $gravacao[$nu] ;
								echo "<td align='center'><a href='$link'><img src='imagens/nota.jpg' width='40'> </a></td>";
                    }

		    if(in_array($login_fabrica,[169,170])){
				 echo "<TD class='tac' $tr nowrap>{$protocolo_via}</TD>";
			}

                    if ($login_fabrica == 156 and empty($admin)) {
                        $qry_cliente_admin = pg_query(
                            $con,
                            "SELECT cliente_admin FROM tbl_hd_chamado WHERE hd_chamado = $callcenter"
                        );

                        $cliente_admin_hd_chamado = pg_fetch_result($qry_cliente_admin, 0, 'cliente_admin');

                        if ($cliente_admin_hd_chamado) {
                            $qry_admin = pg_query(
                                $con,
                                "SELECT UPPER(login) AS admin
                                 FROM tbl_admin
                                 WHERE cliente_admin = $cliente_admin_hd_chamado
                                 AND admin = (
                                    SELECT admin from tbl_hd_chamado_item
                                    WHERE hd_chamado = $callcenter
                                    ORDER BY data LIMIT 1
                                )"
                            );

                            if (pg_num_rows($qry_admin)) {
                                echo "<TD class='tac' $tr nowrap>" . pg_fetch_result($qry_admin, 0, 'admin') . "</TD>";
                            } else {
                                echo "<TD class='tac' $tr nowrap>&nbsp;</TD>";
                            }
                        } else {
                            echo "<TD class='tac' $tr nowrap>&nbsp;</TD>";
                        }
                    } else {
                        echo "<TD class='tac' $tr nowrap>$admin</TD>";
                    }

				    echo "<TD class='tac' $tr nowrap>$atendente</TD>";

				    if (in_array($login_fabrica, [186])) {
						echo "<TD class='tac' $tr nowrap>$codigo_posto - $posto_nome</TD>";	
					}

				    if ($login_fabrica == 35) {
                   		
                   		echo "<TD class='tac' $tr nowrap>$ultima_transferencia</TD>";
                   	}

				    if($login_fabrica == 50){
				    	echo "<td class='tac' $tr nowrap>$hd_motivo_ligacao</td>";
				    }
				    if ($login_fabrica == 11) {
				    	echo "<TD class='tac' nowrap>{$admin}</TD>";
				    }

					if($telecontrol_distrib or $interno_telecontrol) {
						echo "<TD class='tac' $tr nowrap>$origem</TD>";
					}
					echo "<TD class='tac' $tr nowrap>$data</TD>";
					if ($login_fabrica == 80) {
						$data_protocolo = trim(pg_fetch_result ($resHD,$j,'data_protocolo'));
						echo "<TD class='tac' nowrap>$data_protocolo</td>";
					}
					if($login_fabrica == 30){
						echo "	<TD class='tac' nowrap>$data_conclusao</TD>
								<TD class='tac' nowrap>$dias_aberto</TD>
								<TD class='tac' nowrap>$providencia</TD>";
					}
					if($login_fabrica == 74){
						echo "	<TD class='tac' nowrap>".$array_campos_adicionais["data_visita_tecnico"]."</TD>";
					}
                    if ($login_fabrica == 136) {
                        echo "<TD $tr nowrap nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome' >".$produto_referencia." - ".substr($produto_nome, 0, 17)."</ACRONYM></TD>";
                    }else{
                    	if ($login_fabrica == 85) {
                    		if (!empty($check_tec_esporadico))
                    			echo "<TD class='tac' $tr>$encerrado</TD>";
                    		echo "<TD $tr>$produto_referencia</TD>";
                    	}

                    	if (!in_array($login_fabrica, [189])) {
                    		echo "<TD $tr nowrap><ACRONYM TITLE='$produto_referencia - $produto_nome'>".substr($produto_nome,0,17)."</ACRONYM></TD>";	
                    	}

                        if($login_fabrica == 42){
                        	echo "<TD class='tac' nowrap>$tipo_registro</TD>";
                        }

                        if (in_array($login_fabrica,array(52,85))) {
                    		echo "<TD $tr>$serie</TD>";
                    	}
                    }

					if ($login_fabrica == 80) {
						echo "<TD class='tac' $tr nowrap>$data_nf</TD>";
					}
					if ($login_fabrica == 45) {
						echo "<TD class='tac'>$defeito_reclamado</TD>";
					}
					if ($login_fabrica == 183){
						echo "<TD nowrap class='tac'>$descricao_familia</TD>";
						echo "<TD nowrap class='tac'>$codigo_linha-$nome_linha</TD>";
						echo "<TD nowrap class='tac'>$defeito_reclamado_arg</TD>";
					}
					if(in_array($login_fabrica,array(15,30,50,120,201,140))){ //HD-3282875 adicionada fabrica 50
						echo "<TD class='tac'>$defeito_reclamado_arg</TD>";
					}

					if($login_fabrica == 137){
						echo "<TD class='tac'>$serie</TD>";
						echo "<TD class='tac'>$defeito_reclamado_arg</TD>";
					}

					/*HD - 4382764*/
					if ($login_fabrica == 174) {
						$aux_array = json_decode(pg_fetch_result($resHD, $j, 'array_campos_adicionais'), true);

						if (!empty($aux_array["nome_loja"])) {
							$nome_loja = $aux_array["nome_loja"];
						} else {
							$nome_loja = "";
						}

						echo "<TD class='tal'>$nome_loja</TD>";
					}

					echo "<TD class='tac $mais_30' $tr  nowrap><A href='$programaphp?callcenter=$callcenter#$categoria' target='blank'>$callcenter</A></TD>";

					if ($login_fabrica == 183){
						$pedido_sap = $array_campos_adicionais["pedido_sap"];
						echo "<TD class='tac' nowrap>$pedido_sap</td>";
					}


                    if ($login_fabrica == 115) {
                        echo "<TD class='tac $mais_30' $tr  nowrap><A href='$programaphp?callcenter=$hd_chamado_anterior#$categoria' target='blank'>$hd_chamado_anterior</A></TD>";
                    }

                    echo ($login_fabrica != 1 ) ? "<TD $tr ><ACRONYM TITLE='$consumidor_nome' >".substr($consumidor_nome,0,17)."</ACRONYM></TD>" : "<TD class='tac' nowrap>".strtoupper($nome_cliente)."</TD>";

                    if ($login_fabrica == 115) {
                        echo "<TD class='tac' nowrap>$email_consumidor</td>";
                    }

                    if($login_fabrica == 30){//hd_chamado=2902269
                    	echo "<td class='tac' nowrap> $hd_consumidor_revenda </td>";
                        echo "<td class='tac' nowrap> $consumidor_telefone </td>";
                        echo "<td class='tac' nowrap> $fone_posto </td>";
                    }

                    if($login_fabrica == 45 || $login_fabrica == 140){
                        echo "<td class='tac' $tr nowrap> $consumidor_cpf </td>"; //CPF NKS
                    }
                    if($login_fabrica == 85 && empty($check_tec_esporadico)){
                        echo "<td class='tac' $tr nowrap> $cliente_admin </td>";
                    }
					echo "<TD class='tac' $tr >$nome_cidade</TD>
					      <TD class='tac' $tr nowrap>$consumidor_estado</TD>";

					if ($login_fabrica == 80) {
						$status_os = trim(pg_fetch_result ($resHD,$j,'status_descricao'));
						echo "<TD class='tac' nowrap>$consumidor_telefone</td>";
						echo "<TD class='tac' nowrap>$email_consumidor</td>";
					}
					if($login_fabrica == 30){
						echo "<TD class='tac' $tr nowrap>$revenda_nome</TD>";
					}

					if ((in_array($login_fabrica, array(85)) && !empty($check_tec_esporadico))) {
						echo "<TD class='tal'>$tecnico</TD>";
						echo "<TD class='tac'>$vl_ser</TD>";
						echo "<TD class='tal'>$ult_inte</TD>";
					}

					if(in_array($login_fabrica,array(11))){
						echo "<TD class='tac'>$hd_motivo_ligacao</TD>";
                    }

					if($moduloProvidencia  || $classificacaoHD){
						if ($login_fabrica != 80) {
                        	echo "<TD class='tac'>$hd_motivo_ligacao</TD>";
                    	}
						if ($login_fabrica == 189) {
                        	echo "<TD class='tac'>$providencia_data</TD>";
                    	}
						echo "<TD class='tac'>$classificacao_atendimento</TD>";


					} else {
                        if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || $login_fabrica != 85)
							echo "<TD class='tac' $tr >";
							echo ($login_fabrica == 85 and strlen($codigo_posto) > 1) ? "$codigo_posto - " : "";
							echo "<ACRONYM TITLE='$posto_nome' >".substr($posto_nome, 0, 15)."</ACRONYM></TD>";
                    }

					if(in_array($login_fabrica,array(74))){
						echo "<TD class='tac'>$providencia_data</TD>";
					}

					if(in_array($login_fabrica,array(136))){
						echo "<TD class='tac'>$origem</TD>";

						$sql_pedido = "SELECT pedido FROM tbl_hd_chamado_extra WHERE hd_chamado = {$callcenter}";
					    $res_pedido = pg_query($con, $sql_pedido);

				        $pedido = (pg_num_rows($res_pedido) > 0) ? pg_fetch_result($res_pedido, 0, "pedido") : "";

				        if(strlen($pedido) == 0){
				        	echo "<td></td>";
				        }else{
				        	echo "<td>";
								echo "<a href='pedido_admin_consulta.php?pedido={$pedido}' target='_blank'>{$pedido}</a>";
							echo "</td>";
				        }

					}
                    if($login_fabrica == 30){
                    	if($origem == 'fale'){
                    		$origem = "Site Esmaltec";
                    	}
                        echo "<TD class='tac'  nowrap>$origem</TD>";
                    }
                    if(in_array($login_fabrica, array(169,170,175,177,178))){
                    	echo "<TD class='tac'  nowrap>$origem</TD>";
                    }

                	if (in_array($login_fabrica, [169,170])) {
                    	echo "<TD class='tac'>$providencia_nivel3</TD>
                    		  <TD class='tac'>$motivo_contato_callcenter</TD>";
                    }

                    if ($login_fabrica == 85) {
                    	echo "<TD class='tac' nowrap>". stripcslashes($data_previsao_ambev)."</td>";
                    	echo "<TD class='tac' nowrap>$data_primeira_visita</td>";
                    }

		    if(in_array($login_fabrica,array(151,178))){
				echo "<TD class='tac' $tr nowrap><a href='os_press.php?os=$os_item' target='_blank'>$sua_os</a></TD>";
                    } else {

                    	if ($login_fabrica == 141 && $hd_consumidor_revenda == 'REVENDA') { 
 
                    		$sqlOsNumero = "SELECT os_revenda FROM tbl_os_revenda WHERE hd_chamado = {$callcenter}";
                    		
                    		$resOsNumero = pg_query($con, $sqlOsNumero);
			      				
                    		$os_numero_revenda = pg_fetch_result($resOsNumero, 0, 'os_revenda');

                    		?>
                    		<td> 
	                    		<form target="_blank" action="os_consulta_lite.php" method="POST" name="form_pesquisa">
	                    			<input type="hidden" value="<?= $os_numero_revenda ?>" name="sua_os" />
	                    			<input type="hidden" value="Pesquisar" name="btn_acao" />
	                    			<a href="#" class="submit_pesquisa_os"><?= $os_numero_revenda ?></a>
	                    		</form>
                    		</td>
                    	<?php
                    	} else {

	                    	if ((in_array($login_fabrica, array(85)) && empty($check_tec_esporadico)) || !in_array($login_fabrica, [85,189])) {
								echo "<TD class='tac' $tr nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
	                    	}
							if($login_fabrica == 80){
								echo "<TD class='tac' nowrap>$status_os</td>";
							}
                    	}
                    }

                    if ($login_fabrica == 183 AND $pesquisa_classificacao == true){
                    	echo "<TD>$revenda_cnpj</TD>";
                    	echo "<TD>$revenda_nome</TD>";
                    }

                    if($login_fabrica == 35){
                        echo "<TD id='status_{$callcenter}' class='tac' nowrap>$status</TD>";
                    }else{
       					echo "<TD class='tac' $tr nowrap>$status</TD>";
                    }

                    if (in_array($login_fabrica, array(169,170))) {
                        echo "<TD class='tac' $tr ><ACRONYM TITLE='$posto_nome' >".substr($posto_nome, 0, 15)."</ACRONYM></TD>";
                    }

					if ($login_fabrica == 86) {
						echo "<TD class='tac' nowrap>$dias_aberto</TD>";
					}
				break;
			}

			if($login_fabrica != 74){

				echo "<TD align='center' $tr > ";

				if($areaAdminCliente != true){
						  echo "<A href='callcenter_interativo_print.php?callcenter=$callcenter' target='blank'  >
						  	<button type='button' class='btn btn-small tac' style='width: 72px; margin-bottom: 5px;'>Imprimir</button>
						  </A>";
				}
                if($login_fabrica == 24){
                    if($temChamado == false){
                        $produto_id = (strlen($produto_id))? $produto_id : 'null';
                        $data_nf = (strlen($data_nf))? $data_nf : 'null';
                        $nota_fiscal = (strlen($nota_fiscal))? $nota_fiscal : 'null';
                        $consumidor_cpf = (strlen($consumidor_cpf))? $consumidor_cpf : 'null';

                        ?>
                        <button type='button' class='btn btn-small btn-primary'  onclick='verificaChamadoAnterior(<?=$callcenter?>,"<?=$consumidor_cpf?>", "<?=$produto_id?>", "<?=$data_nf?>", "<?=$nota_fiscal?>", "<?=$categoria?>")'>Consultar</button>

                    <?php
                        //echo " <A href='$programaphp?callcenter=$callcenter#$categoria' target='blank'></a>";
                    }

                }else{
                    echo " <A href='$programaphp?callcenter=$callcenter#$categoria' target='blank'>
                            <button type='button' class='btn btn-small btn-primary'>".traduz('Consultar')."</button>
                          </A>";
                }

                if($login_fabrica == 45){
                    echo "<a href='callcenter_interativo_print_pdf.php?callcenter=$callcenter' target='_blank' > <img alt='Gerar PDF' src='imagens/img_pdf.jpg'/></a>";
                }
					echo  "</TD>";
			}

			echo "</TR>";

		}
		echo "</tbody>";
		echo "</table>";
		if($num_chamados < 50){
		?>
			<script>
                <?php if($login_fabrica == 24){ ?>

                    $.dataTableLoad({
                        table: "#content",
                        aoColumns:[null,null,null,null,null,{"sType":"date"},{"sType":"date"},{"sType":"date"},null,null,null,null,null,null,null]
                    });

                <?php }else{ ?>
                	var colunas = [];
                	$('#content').find(".titulo_coluna").find("th").each(function(index, el){
						($(el).attr("class") == "date_column") ? colunas.push({"sType":"date"}) : colunas.push(null);
					});

					$.dataTableLoad({
						table: "#content",
						type: "custom",
						config: [ "info" ],
						aoColumns: colunas
					});
                <?php } ?>
			</script>
		<?php
		}else{
		?>
			<script>
                <?php if($login_fabrica == 24){ ?>

                    $.dataTableLoad({
                        table: "#content",
                        aoColumns:[null,null,null,null,null,{"sType":"date"},{"sType":"date"},{"sType":"date"},null,null,null,null,null,null,null]
                    });

                <?php }else{ ?>
                	var colunas = [];
                	$('#content').find(".titulo_coluna").find("th").each(function(index, el){
						($(el).attr("class") == "date_column") ? colunas.push({"sType":"date"}) : colunas.push(null);
					});

					$.dataTableLoad({ table: "#content", aoColumns: colunas });

                <?php } ?>
			</script>
		<?php
		} ?>
        <br/>
        <?php if($login_fabrica == 35){ ?>
        <div class="container">
        	<div class='titulo_tabela '><?=traduz('Com marcados, alterar status para')?>:</div>

            <form name='frm_status_chamado' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
			<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
			   <div class='tar control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
			        	<label class='control-label' for='linha' style="padding-right: 42px">Status</label>
			        	<div class='controls controls-row tar'>

                                <select name="status_interacao" id="status_interacao" style="width:80px; font-size:9px" class="input" <?php echo ($login_fabrica == 74) ? "onchange=\"verificaStatus(this.value)\"" : ""; ?> >

		                            <?php
		                                $sql = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica order by status ";
		                                $res = pg_query($con,$sql);

		                                for ($i = 0; $i < pg_num_rows($res);$i++){

		                                	$status_hd = pg_result($res,$i,0);

		                                	$status_hd_desc = ($status_hd == 'Ag. Consumidor') ? 'Aguardando Consumidor' : $status_hd;

		                                	$selected_status = (utf8_decode($status_hd) == $status_interacao) ? "SELECTED" : null;
		                            ?>
		                                	<option value="<?=utf8_decode($status_hd)?>" <?echo $selected_status?> ><?echo utf8_decode($status_hd_desc); ?></option>
		                            <?
		                                }
		                            ?>
                                </select>

			        	</div>
			       </div>
                </div>
                <div class='span4'>
			        <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='linha'></label>
		                <div class='controls controls-row tal'>
                            <button class='btn' id="btn_acao" type="button"  onclick="gravaStatus()"><?=traduz('Alterar')?></button>
			            </div>
		            </div>
                </div>
                <div class="span2"></div>
			</div>

            </form>
         </div>
		<?
		}

		$_REQUEST['situacao'] = retira_acentos($_REQUEST['situacao']);
		$jsonPOST = excelPostToJson($_REQUEST);
		$jsonPOST = utf8_decode($jsonPOST);
		?>
			<br />
			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
			</div>
<?php
		if($telecontrol_distrib || $login_fabrica == 174){
?>
			<br />
			<div id='gerar_resumo' class="btn_excel">
				<input type="hidden" id="jsonPOSTResumo" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz('Gerar Resumo')?></span>
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
}else{
?>
    <div class="alert alert-error">
		<h4><?=$msg_erro?></h4>
    </div>
<?php
}

?>
	<br />
	<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>
		<TR class='table_line'>
			<TD align='center' background='#D9E2EF'>
				<?php
					$link = ($areaAdminCliente == true) ? "consulta_atendimento_cliente_admin.php" : "callcenter_parametros_new.php";
				?>
				<button class='btn' id="btn_acao" type="button"  onclick="window.location='callcenter_parametros_new.php';"><?=traduz('Nova Pesquisa')?></button>
			</TD>
		</TR>
	</TABLE>
<?
if(in_array($login_fabrica, array(169,170))){
?>
<style type="text/css">
	.table-striped tbody tr:first-child td, .table-striped tbody tr:first-child th {
		background: none !important;
	}
	.table-striped tbody tr:nth-child(1n+2) td, .table-striped tbody tr:nth-child(1n+2) th {
	    /*background-color: #f9f9f9;*/
	    background: none !important;
	}
</style>
<?php
}

echo "<div class='container'>";
include "rodape.php";

?>
