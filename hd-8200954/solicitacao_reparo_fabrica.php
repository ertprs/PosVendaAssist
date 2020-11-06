<?php

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
if ($areaAdmin === true) {
	include __DIR__.'/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

if ($areaAdmin === true) {
	$layout_menu = "auditoria";
} else {
	$layout_menu = "os";
}

$title = "Solicitação de Reparo";

/**
 *$arrData = array('campo tbl_comunicado'=> 'valor'  )
 *
 */
function insereComunicado($arrData){
    global $con, $login_fabrica;
    $camposTblComunicado = array_keys($arrData);

    $insertCampos = implode(',', $camposTblComunicado);
    $insertValores = array_values($arrData);
    $insertValores = implode(',', $insertValores);
    $insert = "INSERT INTO tbl_comunicado ({$insertCampos}) VALUES ({$insertValores})";

    $res = pg_query($con,$insert);

    if(!$res){
        throw new Exception('Erro ao enviar comunicado ao posto');
    }
}

function insereInteracaoOs($arrData){
    global $con, $login_fabrica;
    $camposTblOsInteracao = array_keys($arrData);

    $insertCampos = implode(',', $camposTblOsInteracao);
    $insertValores = array_values($arrData);
    $insertValores = implode(',', $insertValores);

    $insert = "INSERT INTO tbl_os_interacao ({$insertCampos}) VALUES ({$insertValores})";
    $res = pg_query($con,$insert);

    if(!$res){
        throw new Exception('Erro ao interagir na OS');
    }
}
function getPostoInterno($osInterna){
    global $con, $login_fabrica;

    $sqlPostoInterno = "SELECT posto FROM tbl_os WHERE fabrica = $login_fabrica AND os = $osInterna";
    $res = pg_query($con, $sqlPostoInterno);

    if(!$res){
        throw new Exception(utf8_encode("Erro ao encontrar posto interno"));
    }
    $postoInterno = pg_fetch_result($res, 0, 0);

    if(empty($postoInterno)){
        throw new Exception(utf8_encode("Posto Interno não encontrado"));
    }

    return $postoInterno;
}

function liberaAuditoria($osInterna){
    global $con, $login_fabrica;
    $update = "UPDATE tbl_auditoria_os set liberada = now() where os = {$osInterna}";
    $res = pg_query($con, $update);

    if(!$res){
        throw new Exception(utf8_encode("Erro ao liberar auditoria"));
    }
}



function mudaStatusOsPostoExterno($osExterna, $status, $msgStatus){
	global $con, $login_fabrica, $os, $campos;

    $insert = "INSERT INTO tbl_os_status (os, status_os, fabrica_status, observacao) VALUES ($osExterna, $status, $login_fabrica, '$msgStatus')";

    $res = pg_query($con, $insert);

    if(strlen(pg_last_error($con)) > 0){
        throw new Exception('Erro ao alterar status da OS externa');
    }
}

function getTecnicoOsInterna($osInterna){
	global $con, $login_fabrica;

    $sqlTecnicoInterno = "SELECT tecnico FROM tbl_os WHERE fabrica = $login_fabrica AND os = $osInterna";
    $res = pg_query($con, $sqlTecnicoInterno);

    if(!$res){
        throw new Exception(utf8_encode("Erro ao encontrar tecnico interno"));
    }
    $tecnicoInterno = pg_fetch_result($res, 0, 0);

    if(empty($tecnicoInterno)){
        throw new Exception(utf8_encode("Tecnico interno não encontrado"));
    }

    return $tecnicoInterno;

}


function aprovaOrcamento($osExterna, $osInterna){
    global $con, $login_fabrica, $login_posto;

    $postoInterno = getPostoInterno($osInterna);
    $tecnicoInterno = getTecnicoOsInterna($osInterna);

    // $msg =  "'Orçamento aprovado para a OS {$osInterna}'"; 
    // insereComunicado(array('destinatario'=>$tecnicoInterno, 'ativo'=>'true','fabrica' =>$login_fabrica, 'posto'  => $postoInterno, 'mensagem'=> $msg, 'obrigatorio_site' => 'true' ));

    // insereInteracaoOs(array('os' => $osExterna, 'posto'=>$login_posto, 'comentario'=>$msg, 'fabrica' => $login_fabrica));
    // insereInteracaoOs(array('os' => $osInterna, 'posto'=>$postoInterno, 'comentario'=>$msg, 'fabrica' => $login_fabrica));

    $status = 220; //Reparo em andamento
    $msgStatus = "Reparo em andamento";
    mudaStatusOsPostoExterno($osExterna,$status, $msgStatus);
}

function reprovaOrcamento($osExterna, $osInterna){
    global $con, $login_fabrica, $login_posto;

    $postoInterno = getPostoInterno($osInterna);
	$tecnicoInterno = getTecnicoOsInterna($osInterna);

    $msg =  "'Orçamento da OS $osInterna foi reprovado.'"; 
    insereComunicado(array('destinatario'=>$tecnicoInterno, 'ativo'=>'true','fabrica' =>$login_fabrica, 'posto'  => $postoInterno, 'mensagem'=> $msg, 'obrigatorio_site' => 'true' ));

    $msg =  "'Orçamento reprovado'"; 
    insereInteracaoOs(array('os' => $osExterna, 'posto'=>$login_posto, 'comentario'=>$msg, 'fabrica' => $login_fabrica));
    insereInteracaoOs(array('os' => $osInterna, 'posto'=>$postoInterno, 'comentario'=>$msg, 'fabrica' => $login_fabrica));

    $status = 241; //Orçamento Reprovado
    $msgStatus = "Orçamento Reprovado";
    mudaStatusOsPostoExterno($osExterna,$status, $msgStatus);

}



if(isset($_POST['aprovar_orcamento'])){
    if(empty($_POST['osInterna']) && empty($_POST['osExterna']) && empty($_POST['aprovar_orcamento'])){
        echo json_encode(array('msg'=> utf8_encode('Operação não realizada'))); 
        exit;
    }

    $osInterna = trim($_POST['osInterna']);
    $osExterna = trim($_POST['osExterna']);
    try{

        pg_query($con, 'BEGIN TRANSACTION');
        if($_POST['aprovar_orcamento'] == 'true' ){
            aprovaOrcamento($osExterna,$osInterna);
            $msg = "Orçamento Aprovado";
        }else{                          
            reprovaOrcamento($osExterna,$osInterna);
            $msg = "Orçamento Reprovado";
        }

        pg_query($con, 'COMMIT TRANSACTION');
        echo json_encode(array('msg'=> utf8_encode($msg)));
    }catch(Exception $ex){

    	if($_POST['aprovar_orcamento'] == 'true' ){
            $msg = "Não foi possível Aprovar Orçamento";
        }else{                          
            $msg = "Não foi possível Reprovar Orçamento";
        }
        pg_query($con, 'ROLLBACK TRANSACTION');
		echo json_encode(array('msg'=> utf8_encode($msg)));

    }
    exit;
}

if( $_POST["ReprovaOsRecolhimento"] == "true" ){
	$os = $_POST["os"];
	$motivo = $_POST["motivo"];
	$sql = "SELECT posto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
	$res = pg_query($con,$sql);
	$posto = pg_fetch_result($res,0,"posto");
	
	$sql = "UPDATE tbl_os_extra set recolhimento = false where os = {$os} ";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error())>0){
		$retorno = array("erro" => "Erro ao reprovar.", 
				"success" => "false");
	}

	$sql = "INSERT INTO tbl_comunicado
				(fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
				VALUES
				({$login_fabrica}, {$posto}, true, 'Com. Unico Posto', true, 'Solicitação de Reparo na Fábrica','A Solicitação de Reparo na Fábrica da OS {$os} foi recusada, motivo: {$motivo}')";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error())>0){
		$retorno = array("erro" => "Erro ao reprovar.", 
				"success" => "false");
	}else{
		$retorno = array("success" => "OS Reprovada com sucesso",
					"erro" => "false");
	}
	exit(json_encode($retorno));
}



if( $_POST["EnviaProdutoPosto"] == "true" ){
	$os = $_POST["os_posto"];
	
	pg_query($con,"BEGIN");
	$sql = "SELECT status_os from tbl_status_os where descricao = 'Aguardando Confirmação de Recebimento do Produto' ";
	$res = pg_query($con,$sql);
	$status_os = pg_fetch_result($res,0,"status_os");

	$sql = "INSERT INTO tbl_os_status
			(fabrica_status, os, status_os,observacao)
		VALUES
			($login_fabrica, $os, $status_os,'Produto Enviado para a Fábrica')";
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error())>0){
		pg_query($con,"ROLLBACK");
		$retorno = array("erro" => "Erro ao aprovar.", 
				"success" => "false");
	}else{
		 pg_query($con,"COMMIT");
		$retorno = array("success" => "OS Aprovada com sucesso",
					"erro" => "false");
	}

	exit(json_encode($retorno));
}

if( $_POST["confirmaRecebimentoProduto"] == "true" ){
	$os = $_POST["os_posto"];
	
	pg_query($con,"BEGIN");
	$sql = "SELECT status_os from tbl_status_os where descricao = 'Produto Recebido pelo Posto Autorizado' ";
	$res = pg_query($con,$sql);
	$status_os = pg_fetch_result($res,0,"status_os");

	$sql = "INSERT INTO tbl_os_status
			(fabrica_status, os, status_os,observacao)
		VALUES
			($login_fabrica, $os, $status_os,'Produto Recebido pelo Posto Autorizado')";
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error())>0){
		pg_query($con,"ROLLBACK");
		$retorno = array("erro" => "Erro ao aprovar.", 
				"success" => "false");
	}else{
		 pg_query($con,"COMMIT");
		$retorno = array("success" => "OS Aprovada com sucesso",
					"erro" => "false");
	}

	exit(json_encode($retorno));
}

if( $_POST["AprovaOsRecolhimento"] == "true" ){
	$os = $_POST["os_posto"];
	 pg_query($con,"BEGIN");
	$sql = "SELECT status_os from tbl_status_os where descricao = 'Aguardando Envio do Produto' ";
	$res = pg_query($con,$sql);
	$status_os = pg_fetch_result($res,0,"status_os");
	
	$sql = "SELECT posto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
	$res = pg_query($con,$sql);
	$posto = pg_fetch_result($res,0,"posto");


	$sql = "INSERT INTO tbl_os_status
			(fabrica_status, os, status_os,observacao)
		VALUES
			($login_fabrica, $os, $status_os,'OS Aprovada para reparo na fábrica')";
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error())>0){
		$retorno = array("erro" => "Erro ao aprovar.", 
				"success" => "false");
	}

	$sql = "INSERT INTO tbl_comunicado
			(fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
		VALUES
			({$login_fabrica}, {$posto}, true, 'Com. Unico Posto', true, 'Solicitação de Reparo na Fábrica','A Solicitação de Reparo na Fábrica da OS {$os} foi aprovada e a Fábrica está aguardando o envio do produto para reparo') ";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error())>0){

		pg_query($con,"ROLLBACK");

		$retorno = array("erro" => "Erro ao aprovar.", 
				"success" => "false");
	}else{

		if($reparoNaFabricaCorreios){
			// porém o gerar autorização de postagem irá gerar o código de autorização de postagem e gravar em tbl_os_extra.pac ???????
		}

		pg_query($con,"COMMIT");
		$retorno = array("success" => "OS Aprovada com sucesso",
					"erro" => "false");
	}

	exit(json_encode($retorno));
}

if( $_POST["EnviarProdutoPostoAutorizado"] == "true" ){
	$os = $_POST["os_posto"];
	pg_query($con,"BEGIN");

	$sql = "SELECT status_os from tbl_status_os where descricao = 'Produto Enviado para o Posto Autorizado' ";
	$res = pg_query($con,$sql);
	$status_os = pg_fetch_result($res,0,"status_os");

	$sql = "SELECT posto from tbl_os where os = {$os} and fabrica = {$login_fabrica}";
	$res = pg_query($con,$sql);
	$posto = pg_fetch_result($res,0,"posto");

	$sql = "INSERT INTO tbl_os_status
			(fabrica_status, os, status_os,observacao)
		VALUES
			($login_fabrica, $os, $status_os,'Produto Enviado para o Posto Autorizado')";
	$res = pg_query($con,$sql);

	if(strlen(pg_last_error())>0){
		$retorno = array("erro" => "Erro inserir status.", 
				"success" => "false");
	}

	$sql = "INSERT INTO tbl_comunicado
			(fabrica, posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
		VALUES
			({$login_fabrica}, {$posto}, true, 'Com. Unico Posto', true, 'Solicitação de Reparo na Fábrica','A OS {$os} teve o reparo concluído e o produto já foi enviado pelo fabricante') ";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error())>0){

		if($reparoNaFabricaCorreios){
			// porém o gerar autorização de postagem irá gerar o código de autorização de postagem e gravar em tbl_os_extra.pac ???????
		}

	 	pg_query($con,"ROLLBACK");

		$retorno = array("erro" => "Erro ao inserir comunicado.", 
				"success" => "false");
	}else{
		 pg_query($con,"COMMIT");
		$retorno = array("success" => "OS Aprovada com sucesso",
					"erro" => "false");
	}

	exit(json_encode($retorno));
}


if( $_POST["AbreOsInterna"] == "true" ){
	$os = $_POST["os"];
	$posto_interno = $_POST["posto"];
	pg_query($con,"BEGIN");
	
	try{

		$sql = "SELECT status_os from tbl_status_os where descricao = 'Reparo em Andamento' ";
		$res = pg_query($con,$sql);
		$status_os = pg_fetch_result($res,0,"status_os");
		

		$sql = "INSERT INTO tbl_os_status
				(fabrica_status, os, status_os,observacao)
			VALUES
				($login_fabrica, $os, $status_os,'OS em reparo')";
		$res = pg_query($con,$sql);

		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no select da os");
		}

		$sql = "SELECT
				consumidor_nome,
				consumidor_fone,
				consumidor_celular,
				consumidor_fone_comercial,
				consumidor_fone_recado,
				consumidor_endereco,
				consumidor_numero,
				consumidor_complemento,
				consumidor_bairro,
				consumidor_cep,
				consumidor_cidade,
				consumidor_estado,
				consumidor_cpf,
				consumidor_email,
				revenda_nome,
				revenda_fone,
				nota_fiscal,
				revenda,
				defeito_reclamado_descricao,
				data_nf,
				consumidor_revenda,
				observacao,
				serie,
				revenda_cnpj,
				produto
				FROM tbl_os
				WHERE tbl_os.os = {$os} and fabrica = {$login_fabrica} LIMIT 1";
		$res = pg_query($con,$sql);
		
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no select da os");
		}
			
		$arrayOS = pg_fetch_object($res);
		$sql = "INSERT INTO tbl_os(
				fabrica,
				os_numero,
				posto,
				data_abertura,
				consumidor_nome,
				consumidor_fone,
				consumidor_celular,
				consumidor_fone_comercial,
				consumidor_fone_recado,
				consumidor_endereco,
				consumidor_numero,
				consumidor_complemento,
				consumidor_bairro,
				consumidor_cep,
				consumidor_cidade,
				consumidor_estado,
				consumidor_cpf,
				consumidor_email,
				revenda_nome,
				revenda_fone,
				nota_fiscal,
				revenda,
				defeito_reclamado_descricao,
				data_nf,
				consumidor_revenda,
				observacao,
				serie,
				revenda_cnpj,
				produto ) 
			VALUES(
				{$login_fabrica},
				{$os},
				{$posto_interno},
				current_timestamp,
				'{$arrayOS->consumidor_nome}', 
				'{$arrayOS->consumidor_fone}', 
				'{$arrayOS->consumidor_celular}', 
				'{$arrayOS->consumidor_fone_comercial}', 
				'{$arrayOS->consumidor_fone_recado}', 
				'{$arrayOS->consumidor_endereco}', 
				'{$arrayOS->consumidor_numero}', 
				'{$arrayOS->consumidor_complemento}', 
				'{$arrayOS->consumidor_bairro}', 
				'{$arrayOS->consumidor_cep}', 
				'{$arrayOS->consumidor_cidade}', 
				'{$arrayOS->consumidor_estado}', 
				'{$arrayOS->consumidor_cpf}', 
				'{$arrayOS->consumidor_email}', 
				'{$arrayOS->revenda_nome}', 
				'{$arrayOS->revenda_fone}', 
				'{$arrayOS->nota_fiscal}', 
				{$arrayOS->revenda}, 
				'{$arrayOS->defeito_reclamado_descricao}', 
				'{$arrayOS->data_nf}', 
				'{$arrayOS->consumidor_revenda}', 
				'{$arrayOS->observacao}', 
				'{$arrayOS->serie}', 
				'{$arrayOS->revenda_cnpj}', 
				{$arrayOS->produto}
				)";
		
		$res = pg_query($con,$sql);
		
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no INSERT da os");
		}

		$res = pg_query ($con,"SELECT CURRVAL ('seq_os')");
		$os_interna = pg_fetch_result ($res,0,0);
		$res = pg_query($con,"INSERT INTO tbl_os_produto(os,produto) VALUES ({$os_interna},{$arrayOS->produto})");		

		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no INSERT da tbl_os_produto");
		}
		$res = pg_query($con,"INSERT INTO tbl_os_campo_extra(os,fabrica) VALUES({$os_interna},{$login_fabrica})");		
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no INSERT da tbl_os_produto");
		}
		$res = pg_query($con,"INSERT INTO tbl_os_extra(os,recolhimento)  VALUES({$os_interna},true)");		
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no INSERT da tbl_os_produto");
		}

		$sql = "UPDATE tbl_os SET os_numero = {$os_interna} where os = {$os}";
		$res = pg_query($con,$sql);
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no update da tbl_os");
		}

		$sql = "UPDATE tbl_os SET sua_os = {$os_interna} where os = {$os_interna}";
		$res = pg_query($con,$sql);
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no update da tbl_os");
		}

		$sql = "INSERT INTO tbl_os_status
				(fabrica_status, os, status_os,observacao)
			VALUES
				({$login_fabrica}, {$os_interna}, {$status_os},'OS em reparo')";
		$res = pg_query($con,$sql);
		if(strlen(pg_last_error())>0){
			throw new \Exception("Erro ao no INSERT da tbl_os_status");
		}

	}catch(\Exception $e) {
		pg_query($con,"ROLLBACK");

		$retorno = array("erro" => "Erro ao gerar os interna entrar em contato com a telecontrol", 
				"success" => "false",
				"info" => $e->getMessage());
		exit(json_encode($retorno));
	}


	$retorno = array("success" => "OS: {$os_interna} Gerada com sucesso ",
					"erro" => "false");
	 pg_query($con,"COMMIT");

	exit(json_encode($retorno));
}

if(strlen($_POST['pesquisar'])>0){

	
	if ($areaAdmin === true) {
		if(strlen($_POST["posto"]["id"])>0){
			$posto_id = $_POST["posto"]["id"];


			$cond .= "AND tbl_os.posto = $posto_id ";
		}
	}

	if(strlen($_POST["data_inicial"]) && strlen($_POST["data_final"]) ){
		$data_inicial = $_POST["data_inicial"];
		$data_final = $_POST["data_final"];


		if(!empty($data_inicial) OR !empty($data_final)){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
				$msg = "Data inicial inválida";

				list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
				$msg = "Data final inválida";

			if(strlen($msg)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";

				if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
					$msg = "Data inicial maior do que a data final";
				}
			}
		}

		$cond .= " AND tbl_os.data_abertura  BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
	}

	if(strlen($_POST["os"])){
		$os = $_POST["os"];

		$cond .= "  AND (tbl_os.os = {$os} OR tbl_os.sua_os = '{$os}') ";
	}

}


if ($areaAdmin === false) {
	$cond .= "AND tbl_os.posto = {$login_posto}";
}

$sql = "SELECT 
		tbl_os.os,
		tbl_os.sua_os,
		(tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome) AS posto_autorizado,
		tbl_os.data_abertura,
		(SELECT tbl_status_os.descricao 
			FROM tbl_os_status 
			INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os 
			WHERE tbl_os_status.os = tbl_os.os 
				AND tbl_status_os.status_os = 223 
			ORDER BY tbl_os_status.data 
			DESC LIMIT 1) AS ultimo_status,
		to_char((SELECT tbl_os_status.data 
			FROM tbl_os_status 
			INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os 
			WHERE tbl_os_status.os = tbl_os.os 
			AND tbl_status_os.status_os = 223 
			ORDER BY tbl_os_status.data 
			DESC LIMIT 1),'DD/MM/YYYY') AS data_ultimo_status,
		os_posto_interno.os AS os_interna,
		os_posto_interno.sua_os AS sua_os_interna,
		(posto_fabrica_interno.codigo_posto || ' - ' || posto_interno.nome) AS posto_interno,
		to_char(os_posto_interno.data_abertura,'DD/MM/YYYY') AS data_abertura_os_interna,
		to_char(os_posto_interno.data_fechamento,'DD/MM/YYYY') AS data_fechamento_os_interna,
		tbl_status_checkpoint.descricao AS os_interna_status
	-- INTO TEMP tmp_consulta_reparo_fabrica_{$login_admin}
	FROM tbl_os
	INNER JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os 
	INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto 
		AND tbl_posto_fabrica.fabrica = {$login_fabrica}
	INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
	LEFT JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint 
	LEFT JOIN tbl_os AS os_posto_interno ON os_posto_interno.os = tbl_os.os_numero 
		AND os_posto_interno.fabrica = {$login_fabrica}
	LEFT JOIN tbl_posto_fabrica AS posto_fabrica_interno ON posto_fabrica_interno.posto = os_posto_interno.posto 
		AND posto_fabrica_interno.fabrica = {$login_fabrica}
	LEFT JOIN tbl_posto AS posto_interno ON posto_interno.posto = posto_fabrica_interno.posto
	WHERE tbl_os.fabrica = {$login_fabrica}
	AND tbl_os_extra.recolhimento IS TRUE
	{$cond}
	AND (SELECT tbl_status_os.status_os 
			FROM tbl_os_status 
			INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os 
			WHERE tbl_os_status.os = tbl_os.os 
				AND tbl_status_os.status_os = 223 
			ORDER BY tbl_os_status.data 
			DESC LIMIT 1) = 223
	ORDER BY tbl_os.os";

$resPesquisa = pg_query($con,$sql);


if ($areaAdmin === true) {
	include __DIR__.'/cabecalho_new.php';
} else {
	include __DIR__.'/cabecalho_new.php';
}


$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "dataTable",
   "mask",
);
if($areaAdmin===true){
	include __DIR__.'/plugin_loader.php';
}
else{
	include __DIR__.'/admin/plugin_loader.php';
}


$status_auditoria = "217,218,219,220,221,222, 230,241";
//status 223 produto enviado para posto autorizado


if ($areaAdmin === false) {
	$condicaoPosto = "AND tbl_os.posto = {$login_posto}";
}

$sql = "SELECT 

		tbl_os.os,
		tbl_os.sua_os,
		(tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome) AS posto_autorizado,
	        (tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto_os, 	
		tbl_os.data_abertura,
		(SELECT tbl_status_os.descricao 
			FROM tbl_os_status 
			INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os 
			WHERE tbl_os_status.os = tbl_os.os 
				AND tbl_status_os.status_os IN ({$status_auditoria}) 
			ORDER BY tbl_os_status.data 
			DESC LIMIT 1) AS ultimo_status,
		to_char((SELECT tbl_os_status.data 
			FROM tbl_os_status 
			INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os 
			WHERE tbl_os_status.os = tbl_os.os 
			AND tbl_status_os.status_os IN ({$status_auditoria}) 
			ORDER BY tbl_os_status.data 
			DESC LIMIT 1),'DD/MM/YYYY') AS data_ultimo_status,
		os_posto_interno.os AS os_interna,
		os_posto_interno.sua_os AS sua_os_interna,
		(posto_fabrica_interno.codigo_posto || ' - ' || posto_interno.nome) AS posto_interno,
		to_char(os_posto_interno.data_abertura,'DD/MM/YYYY') AS data_abertura_os_interna,
		to_char(os_posto_interno.data_fechamento,'DD/MM/YYYY') AS data_fechamento_os_interna,
		tbl_status_checkpoint.descricao AS os_interna_status,
        tbl_tipo_atendimento.descricao AS tipo_atendimento
	INTO TEMP tmp_consulta_reparo_fabrica_{$login_admin}
	FROM tbl_os
	INNER JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os 
    INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
	INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto 
		AND tbl_posto_fabrica.fabrica = {$login_fabrica}
	INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
	INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
	LEFT JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint 
	LEFT JOIN tbl_os AS os_posto_interno ON os_posto_interno.os = tbl_os.os_numero 
		AND os_posto_interno.fabrica = {$login_fabrica}
	LEFT JOIN tbl_posto_fabrica AS posto_fabrica_interno ON posto_fabrica_interno.posto = os_posto_interno.posto 
		AND posto_fabrica_interno.fabrica = {$login_fabrica}
	LEFT JOIN tbl_posto AS posto_interno ON posto_interno.posto = posto_fabrica_interno.posto
	WHERE tbl_os.fabrica = {$login_fabrica}
	AND tbl_os_extra.recolhimento IS TRUE
	{$condicaoPosto}
	ORDER BY tbl_os.os";
//	 print_r(nl2br($sql));
$resConsulta = pg_query($con,$sql);
			
$sql = "SELECT tbl_posto.posto,
		tbl_posto.nome,
		tbl_posto_fabrica.codigo_posto
	FROM 	tbl_posto 
INNER JOIN 	tbl_posto_fabrica using (posto) 
INNER JOIN 	tbl_tipo_posto ON  tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto 
WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
AND tbl_tipo_posto.posto_interno = TRUE "; 
$res_posto_interno = pg_query($con,$sql);

if(pg_num_rows($res_posto_interno)>0){
	$options = "<option></option>";
	while ($result = pg_fetch_object($res_posto_interno)) {
		$options .= " <option value='{$result->posto}'>{$result->nome}</option> ";
	}
}else{
	$options .= "<option>SEM POSTOS INTERNOS</option>";
}

function getDadosOrcamento($osInterna){
    global $con, $login_fabrica;
    $sql = "SELECT tbl_produto.referencia, tbl_produto.descricao,
                   SUM(tbl_os_item.custo_peca) as total_valor_pecas
            FROM tbl_os_produto 
            JOIN tbl_produto on tbl_produto.produto = tbl_os_produto.produto
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            WHERE tbl_os_produto.os = {$osInterna}
            GROUP BY tbl_produto.referencia, tbl_produto.descricao";

    $res = pg_query($con, $sql);

    if(!$res){
        throw new Exception("Não foi possível obter orçamento");
    }

    $orcamento = pg_fetch_all($res);

    if(empty($orcamento)){
        throw new Exception("Orçamento do Posto Interno não preenchido");
    }
    return $orcamento[0];

}
?>


<style>

div.accordion-heading, div.accordion-inner {
	border: 1px #CCC solid;
	background-color: #FFF;
}

</style>
<script type="text/javascript">
$(function() {
	$("#data_inicial").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	// $("select").select2();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});
	$('.aprova_orcamento').click(function(){
        var osExterna = $(this).parents('tr').find('td').first().text();
        var osInterna = $(this).parents('tr').find('input[name=os_interna]').first().val();

        $.ajax({
            url:'solicitacao_reparo_fabrica.php',
            type: 'post',
            data: {
                'osExterna': osExterna,
                'osInterna': osInterna,
                'aprovar_orcamento':'true'
            }
        }).done(function(data){
            var obj = JSON.parse(data);

            if(obj.hasOwnProperty('msg')){
                alert(obj.msg);
            }
        });
    });

    $('.reprova_orcamento').click(function(){
        var osExterna = $(this).parents('tr').find('td').first().text();
        var osInterna = $(this).parents('tr').find('input[name=os_interna]').first().val();

        $.ajax({
            url:'solicitacao_reparo_fabrica.php',
            type: 'post',
            data: {
                'osExterna': osExterna,
                'osInterna': osInterna,
                'aprovar_orcamento':'false'
            }
        }).done(function(data){
            var obj = JSON.parse(data);

            if(obj.hasOwnProperty('msg')){
                alert(obj.msg);
            }
        });

    });

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();

	var table = new Object();
	table['table'] = '#table_atendimento';
	table['type'] = 'basic';
	$.dataTableLoad(table);

	$("button[id^=ReprovaOsRecolhimento]").click(function (){
		var os = $(this).val();

		Shadowbox.open({
			content: $(".div_motivo_reprova").html().replace(/__os__/g,os),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});

	});
	
	$("button[id^=AprovaOsRecolhimento]").click(function (){
		var os = $(this).val();
		aprovaOsPostagem(os);

	});
	
	$("button[id^=EnviarProdutoPostoAutorizado]").click(function (){
		var os = $(this).val();
		EnviarProdutoPostoAutorizado(os);

	});

	$("button[id^=confirmaRecebimentoProduto]").click(function (){
		var os = $(this).val();
		confirmaRecebimentoProduto(os);

	});

	$("button[id^=EnviaProdutoPosto]").click(function (){
		var os = $(this).val();
		EnviaProdutoPosto(os);

	});

	$("button[id^=ReprovaOsRecolhimento]").click(function (){
		var os = $(this).val();

		Shadowbox.open({
			content: $(".div_motivo_reprova").html().replace(/__os__/g,os),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});

	});
	$("button[id^=GerarOsInterna]").click(function (){
		var os = $(this).val();

		Shadowbox.open({
			content: $(".div_gera_os_interna").html().replace(/__os__/g,os),
			player: "html",
			width: 800,
			heigth: 600,
			options: {
				enableKeys: false
			}
		});

	});


});

function sol_reparo_count(){
	var variavel;
	variavel = parseInt($('.sol_reparo').text());
	variavel = variavel - 1;
	$('.sol_reparo').text(variavel);

}

function ag_produto_count(){
	var variavel;
	variavel = parseInt($('.ag_produto').text());
	variavel = variavel - 1;
	$('.ag_produto').text(variavel);
}

function ag_postagem_posto(){
	var variavel;
	variavel = parseInt($('.ag_postagem_posto').text());
	variavel = variavel - 1;
	$('.ag_postagem_posto').text(variavel);
}

function reparo_concluido_count(){
	var variavel;
	variavel = parseInt($('.reparo_concluido').text());
	variavel = variavel - 1;
	$('.reparo_concluido').text(variavel);

}

function ag_recebimento_count(){
	var variavel;
	variavel = parseInt($('.ag_recebimento').text());
	variavel = variavel - 1;
	$('.ag_recebimento').text(variavel);

}

function reprovarOS(){

	var loading = $("#sb-container").find("div[name=loading]").hide();
	var motivo = $("#sb-container").find("textarea[name=motivo]").val();
	var os = $("#sb-container").find("textarea[name=motivo]").data("os");
	var div = $("#sb-container").find("div[name=div_motivo]");
	
	if(typeof motivo == undefined || motivo.length == 0){
		alert('Informar motivo da recusa!');
		return;
	}
	loading.show();
	div.hide();

	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		data: { 	ReprovaOsRecolhimento:true, 
				os:os,
				motivo:motivo,
			} ,
	}).done( function(data){
		var mensagem;
		data = JSON.parse(data);

		if(data.erro != "false" ){
			alert(data.erro);
		}else{
			ag_recebimento_count();
			$('input[id=os_recolhimento_'+os+']').parent('tr').remove();
			alert("OS Recusada com sucesso!");
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});

}


function aprovaOsPostagem(os){
	// var os = $("#sb-container").find("textarea[name=motivo]").data("os");
	$('div[name=loadingsol_reparo]').show();
	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		data: { 	
			AprovaOsRecolhimento:true, 
			os_posto:os,
		} ,
	}).done( function(data){
		data = JSON.parse(data);

		if(data.erro != "false" ){
			alert(data.erro);
		}else{
			sol_reparo_count();
			$('input[id=os_recolhimento_'+os+']').parent('tr').remove();
			alert("OS Aprovada com sucesso!");
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});
	$('div[name=loadingsol_reparo]').hide();

}


function EnviaProdutoPosto(os){
	// var os = $("#sb-container").find("textarea[name=motivo]").data("os");
	$('div[name=loadingag_produto]').show();
	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		data: { 	
			EnviaProdutoPosto:true, 
			os_posto:os,
		} ,
	}).done( function(data){
		data = JSON.parse(data);

		if(data.erro != "false" ){
			alert(data.erro);
		}else{
			ag_produto_count();
			$('input[id=os_recolhimento_'+os+']').parent('tr').remove();
			alert("OS Enviada com sucesso!");
			location.reload();
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});
	$('div[name=loadingag_produto]').hide();

}
function confirmaRecebimentoProduto(os){
	// var os = $("#sb-container").find("textarea[name=motivo]").data("os");
	$('div[name=loadingRecebimentoProduto]').show();
	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		data: { 	
			confirmaRecebimentoProduto:true, 
			os_posto:os,
		} ,
	}).done( function(data){
		data = JSON.parse(data);

		if(data.erro != "false" ){
			alert(data.erro);
		}else{
			ag_postagem_posto();
			$('input[id=os_recolhimento_'+os+']').parent('tr').remove();
			alert("OS Enviada com sucesso!");
			location.reload();
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});
	$('div[name=loadingRecebimentoProduto]').hide();

}

function EnviarProdutoPostoAutorizado(os){
	$('div[name=reparo_concluido]').show();
	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		data: { 	
			EnviarProdutoPostoAutorizado:true, 
			os_posto:os,
		} ,
	}).done( function(data){
		data = JSON.parse(data);

		if(data.erro != "false" ){
			alert(data.erro);
		}else{
			reparo_concluido_count();
			$('input[id=os_recolhimento_'+os+']').parent('tr').remove();
			alert("OS Enviada p/ Posto Autorizado");
			location.reload();
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});
	$('div[name=reparo_concluido]').hide();

}



function GerarOsInterna(){

	var loading = $("#sb-container").find("div[name=loading]").hide();
	var os = $("#sb-container").find("input[name=os_posto]").data("os");
	var posto_interno = $("#sb-container").find("select[name=posto_interno]").val();
	var div = $("#sb-container").find("div[name=div_os_interna]");
	

	loading.show();
	div.hide();
	
	if(posto_interno == "" || posto_interno.length ==0 ){
		alert("Selecione um posto interno.");
		loading.hide();
		div.show();
		return;
	}

	$.ajax({
		url: "<?=$_SERVER['PHP_SELF']?>",//'relatorio_auditoria_status.php',
		type: "POST",
		data: { 	AbreOsInterna:true, 
				os:os,
				posto:posto_interno,
			} ,
	}).done( function(data){
		data = JSON.parse(data);

		if(data.erro != "false" ){
			loading.hide();
			alert("Ocorreu um erro inesperado.");
			Shadowbox.close();
		}else{
			ag_recebimento_count();
			$('input[id=os_gerar_'+os+']').parent('tr').remove();
			alert(data.success);
		}

		Shadowbox.close();

	}).fail(function(data){
		alert(data.erro);
	});

}

/**
 * Função de retorno da lupa do posto
 */
function retorna_posto(retorno) {
	/**
	 * A função define os campos código e nome como readonly e esconde o botão
	 * O posto somente pode ser alterado quando clicar no botão trocar_posto
	 * O evento do botão trocar_posto remove o readonly dos campos e dá um show nas lupas
	 */
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo).attr({ readonly: "readonly" });
	$("#posto_nome").val(retorno.nome).attr({ readonly: "readonly" });
	
}

</script>

<?
if ( pg_num_rows($resPesquisa) == 0 and strlen($_POST["pesquisar"])>0) {
	echo "<br /><div class='alert alert-error'><h4>Nenhum resultado encontrado</h4></div> ";
} 
if (count($msg_erro["msg"]) > 0) {
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<?
}
?>

<div class="div_motivo_reprova tac" style="display:none; margin: 5px; padding-right: 20px;">
	<div name="loading" class="loading tac" style="display: none;" >
		<br />
		<br />
		<br />
		<img src="imagens/loading_img.gif" />
	</div>
	<div name="div_motivo" >
		<br/>
		<label>Motivo: </label>
		<textarea name="motivo" rows="3" cols="7" style="margin: 0px 0px 10px; width: 603px; height: 200px;" data-os="__os__" ></textarea>
		<br/>
		<button type="button" style="position:rigth" class="btn btn-primary btn-sucess" data-loading-text="Salvando..." onclick="reprovarOS();">Salvar</button>
	</div>
</div>

<div class="div_gera_os_interna tac" style="display:none; margin: 5px; padding-right: 20px;">
	<div name="loading" class="loading tac" style="display:none;" >
	<br />
	<br />
		<img src="imagens/loading_img.gif" />
	</div>
	<div name="div_os_interna" >
		<br />
		<label>Selecione o posto interno para abrir a OS Interna:</label>
		<select name='posto_interno'>
		<? print_r($options); ?>
		</select>
		<input type="hidden" name="os_posto" rows="3" cols="7" data-os="__os__" ></textarea>
		<br />
		<button type="button" style="position:rigth" class="btn btn-primary btn-sucess" data-loading-text="Salvando..." onclick="GerarOsInterna();">Abrir OS Interna</button>
	</div>
</div>


<div class="tc_formulario" >
	<div class="titulo_tabela">SOLICITAÇÃO DE REPARO</div>
	<br />

	<!-- INICIO Aguardando Aprovação de Orçamento -->
    <br/>
	<div id="atendimentos_dashboard" class="accordion" >
		<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Orçam. (Aprovação)' ORDER BY data_ultimo_status ASC";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#orcamento_aprovacao">
					Orçamento em Aprovação<span class="badge badge-warning sol_reparo"><?=$count?></span>
				</a>
			</div>
			<div id="orcamento_aprovacao" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="loadingorcamento_aprovacao" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
								<th>OS</th>
								<th>Produto</th>
								<th>Valor</th>
								<? if($areaAdmin===false){ ?>
								<th>Ações</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
						    $osOrcamento = pg_fetch_all($res);
                            foreach ($osOrcamento as $orcamento) { 
                                $dadosOrcamento = getDadosOrcamento($orcamento['sua_os_interna']);
                            ?>
								<tr>
                                    <td>
                                        <a target="_blank" href="os_press.php?os=<?=$orcamento['os']?>" ><?=trim($orcamento['os'])?></a>
                                        <input type="hidden" name="os_interna" value="<?=trim($orcamento['sua_os_interna'])?>" />
                                    </td>
                                    <td><?=$dadosOrcamento['referencia'].' -  '.$dadosOrcamento['descricao']?></td>
                                    <td><?=$dadosOrcamento['total_valor_pecas']?></td>
									<? if($areaAdmin===false){ ?>
										<td>
											<button class="btn btn-small btn-success aprova_orcamento" id="aprova_orcamento" value="<?=$orcamento['os']?>">Aprovar</button>
											<button class="btn btn-small btn-danger reprova_orcamento" id="reprova_orcamento" value="<?=$orcamento['os']?>">Reprovar</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
		<!-- FIM Aguardando Aprovação / Reprovação de Orçamento -->
	<!-- INICIO Aguardando Aprovação de Reparo na Fábrica -->
    <br/>
	<div id="atendimentos_dashboard" class="accordion" >
		<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Aguardando Aprovação de Reparo na Fábrica' ORDER BY data_ultimo_status ASC";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#sol_reparo">
					Aguardando Aprovação de Reparo na Fábrica <span class="badge badge-warning sol_reparo"><?=$count?></span>
				</a>
			</div>
			<div id="sol_reparo" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="loadingsol_reparo" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
								<th>OS</th>
								<th>Posto Autorizado</th>
								<th>Data</th>
								<? if($areaAdmin===true){ ?>
								<th>Ações</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');
								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];

								?>
								<tr>
									<input type="hidden" id="os_recolhimento_<?=$os?>" value="<?=$os?>">
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$posto_autorizado?></td>
									<td><?=$data_abertura?></td>
									<? if($areaAdmin===true){ ?>
										<td>
										<? if($reparoNaFabricaCorreios){ ?>
											<button class="btn btn-small btn-success" id="AprovaOsRecolhimento" value="<?=$os?>">Gerar Autorização de Postagem</button>
										<?}else{ ?>
											<button class="btn btn-small btn-success" id="AprovaOsRecolhimento" value="<?=$os?>">Aprovar</button>
										<?}?>
											<button class="btn btn-small btn-danger" id="ReprovaOsRecolhimento" value="<?=$os?>">Recusar</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
		<!-- FIM Aguardando Aprovação de Reparo na Fábrica -->

		<!-- INICIO  Aguardando Envio do Produto -->
		<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Aguardando Envio do Produto' ORDER BY data_ultimo_status ASC";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_produto">
					Aguardando Envio do Produto<span class="badge badge-default"><?=$count?></span>
				</a>
			</div>
			<div id="ag_produto" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="loadingag_produto" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
								<th>OS</th>
								<th>Posto Autorizado</th>
								<th>Data</th>
								<th>Reparo Aprovado</th>
								<? if($areaAdmin != true){ ?>
									<th>Ação</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_abertura_os_interna = pg_fetch_result($res,$i,'data_abertura_os_interna');
                                $data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');
								$tipo_atendimento = pg_fetch_result($res,$i,'tipo_atendimento');
								
								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];
								?>
								<tr>
									<input type="hidden" id="os_recolhimento_<?=$os?>" value="<?=$os?>">
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$posto_autorizado?></td>
									<td><?=$data_abertura?></td>
									<td><?=$data_ultimo_status?></td>
									<? if($areaAdmin != true){ ?>
										<td>
                                        <?php if($tipo_atendimento != 'Fora Garantia') { ?>
                                            <button class="btn btn-small btn-success" id="GerarAutorizacaoPostagem" value="<?=$os?>">Gerar Autorização de Postagem</button>

                                        <?php } ?>
										<button class="btn btn-small btn-success" id="EnviaProdutoPosto" value="<?=$os?>">Produto Enviado</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
	<!-- FIM Aguardando Aguardando Envio do Produto -->

	<!-- INICIO  Aguardando Confirmação de Recebimento do Produto-->

	<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Aguardando Confirmação de Recebimento do Produto' ORDER BY data_ultimo_status ASC";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_recebimento">
					Aguardando Confirmação de Recebimento do Produto<span class="badge badge-success ag_recebimento"><?=$count?></span>
				</a>
			</div>
			<div id="ag_recebimento" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="loadingag_recebimento" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
								<th>OS</th>
								<th>Posto Autorizado</th>
								<th>Data</th>
								<th>Reparo Aprovado</th>
								<? if($areaAdmin===true) { ?>
								<th>Ação</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_abertura_os_interna = pg_fetch_result($res,$i,'data_abertura_os_interna');
								$data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');

								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];

								?>
								<tr>
									<input type="hidden" id="os_gerar_<?=$os?>" value="<?=$os?>">
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$posto_autorizado?></td>
									<td><?=$data_abertura?></td>
									<td><?=$data_ultimo_status?></td>
									<? if($areaAdmin===true) { ?>
										<td>
											<button class="btn btn-success" id="GerarOsInterna" value="<?=$os?>">Produto Recebido - Gerar OS Interna</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
	<!-- FIM  Aguardando Confirmação de Recebimento do Produto-->

	<!-- INICIO Reparo em Andamento-->

	<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Reparo em Andamento' AND os_interna IS NOT NULL AND data_fechamento_os_interna IS NULL ORDER BY data_ultimo_status ASC ";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_reparo">
					Reparo em Andamento <span class="badge badge-important"><?=$count?></span>
				</a>
			</div>
			<div id="ag_reparo" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<tr class="titulo_coluna" >
								<th>OS</th>
                                                                <th>Produto</th>
                                                                <th>Data Abertura</th>
                                                                <th>Status Reparo</th>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_abertura_os_interna = pg_fetch_result($res,$i,'data_abertura_os_interna');
								$os_interna_status        = pg_fetch_result($res,$i,'os_interna_status');
								$data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');
								$produto_os = pg_fetch_result($res,$i,'produto_os');

								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];


								?>
								<tr>
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$produto_os?></td>
									<td><?=$data_abertura?></td>
									<td><?=$os_interna_status?></td>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
	<!-- FIM Reparo em Andamento-->

	<!-- INICIO Reparo Concluído' -->

	<?php
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status = 'Reparo Concluído' AND os_interna IS NOT NULL AND data_fechamento_os_interna IS NOT NULL ORDER BY data_ultimo_status ASC ";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#reparo_concluido">
					Reparo Concluído <span class="badge badge-info reparo_concluido"><?=$count?></span>
				</a>
			</div>
			<div id="reparo_concluido" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="reparo_concluido" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
								<th>OS</th>
								<th>Posto Autorizado</th>
								<th>Data Abertura</th>
								<th>OS Posto Interno</th>
								<th>Posto interno</th>
								<th>Abertura OS Interna</th>
								<th>Fechamento OS Interna</th>
								<? if($areaAdmin===true){ ?>
									<th>Ação</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_abertura_os_interna = pg_fetch_result($res,$i,'data_abertura_os_interna');
								$data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');

								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];

								?>
								<tr>
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$posto_autorizado?></td>
									<td><?=$data_abertura?></td>
									<? if($areaAdmin == true) { ?>
										<td><a target="_blank" href="os_press.php?os=<?=$sua_os_interna?>" ><?=$sua_os_interna?></a></td>
									<? }else{ ?>
										<td><?=$sua_os_interna?></td>
									<? } ?>
									<td><?=$posto_interno?></td>
									<td><?=$data_abertura_os_interna?></td>
									<td><?=$data_fechamento_os_interna?></td>
									<? if($areaAdmin===true){ ?>
										<td nowrap>
											<button id="EnviarProdutoPostoAutorizado" class="btn btn-primary" value="<?=$os?>">Enviar Produto p/ P.A.</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
	<!-- FIM Reparo Concluído' -->

	<!-- INICIO | Produto Enviado para o Posto Autorizado   ' -->
	<?php 
		$sql = "SELECT * FROM  tmp_consulta_reparo_fabrica_{$login_admin} WHERE ultimo_status =  'Produto Enviado para o Posto Autorizado' AND os_interna IS NOT NULL AND data_fechamento_os_interna IS NOT NULL ORDER BY data_ultimo_status ASC";
		unset($count,$res);
		$res = pg_query($con,$sql);
		$count = pg_num_rows($res);
		if($count >0){
		?>
		<div class="accordion-group">
			<div class="accordion-heading">
				<a class="accordion-toggle" data-toggle="collapse" data-parent="#atendimentos_dashboard" href="#ag_postagem_posto">
					Produto Enviado para o Posto Autorizado<span class="badge badge-inverse ag_postagem_posto"><?=$count?></span>
				</a>
			</div>
			<div id="ag_postagem_posto" class="accordion-body collapse">
				<div class="accordion-inner">
					<table class="table table-bordered table-striped">
						<thead>
							<div name="loadingRecebimentoProduto" class="loading tac" style="display: none;" >
								<br />
								<img src="imagens/loading_img.gif" />
							</div>
							<tr class="titulo_coluna" >
							<!-- | OS | Posto Autorizado | Data de Abertura | OS Posto Interno | Posto Interno | Data Abertura | Data Fechamento | Produto Enviado | Ação               | -->
								<th>OS</th>
								<th>Posto Autorizado</th>
								<th>Data Abertura</th>
								<th>OS Posto Interno</th>
								<th>Posto Interno</th>
								<th>Data Abertura</th>
								<th>Data Fechamento</th>
								<th>Produto Enviado</th>
								<? if($areaAdmin != true){ ?>
									<th>Ação</th>
								<? } ?>
							</tr>
						</thead>
						<tbody>
							<?
							
							for ($i = 0 ; $i < $count; $i++) {
								$os         = pg_fetch_result($res,$i,'os');
								$sua_os                   = pg_fetch_result($res,$i,'sua_os');
								$posto_autorizado        = pg_fetch_result($res,$i,'posto_autorizado');
								$data_abertura                = pg_fetch_result($res,$i,'data_abertura');
								$ultimo_status         = pg_fetch_result($res,$i,'ultimo_status');
								$data_ultimo_status        = pg_fetch_result($res,$i,'data_ultimo_status');
								$os_interna             = pg_fetch_result($res,$i,'os_interna');
								$sua_os_interna            = pg_fetch_result($res,$i,'sua_os_interna');
								$posto_interno = pg_fetch_result($res,$i,'posto_interno');
								$data_abertura_os_interna = pg_fetch_result($res,$i,'data_abertura_os_interna');
								$data_fechamento_os_interna = pg_fetch_result($res,$i,'data_fechamento_os_interna');
								unset($data_format);
								$data_format = explode("-",$data_abertura);
								$data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];


								?>
								<tr>
									<input type="hidden" id="os_recolhimento_<?=$os?>" value="<?=$os?>">
									<td><a target="_blank" href="os_press.php?os=<?=$os?>" ><?=$os?></a></td>
									<td><?=$posto_autorizado?></td>
									<td><?=$data_abertura?></td>
									<? if($areaAdmin == true) { ?>
										<td><a target="_blank" href="os_press.php?os=<?=$sua_os_interna?>" ><?=$sua_os_interna?></a></td>
									<? }else{ ?>
										<td><?=$sua_os_interna?></td>
									<? } ?>
									<td><?=$posto_interno?></td>
									<td><?=$data_abertura_os_interna?></td>
									<td><?=$data_fechamento_os_interna?></td>
									<td><?=$data_ultimo_status?></td>
									<? if($areaAdmin != true){ ?>
										<td>
											<button id="confirmaRecebimentoProduto" class="btn btn-primary" value="<?=$os?>">Produto Recebido</button>
										</td>
									<? } ?>
								</tr>
							<?
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<? } ?>
	<!-- FIM Aguardando Aprovação de Reparo na Fábrica -->
	</div>
</div>

<br />


	<form method="POST" action="<?echo $PHP_SELF?>" name="frm_pesquisa_reparo" align='center' class='form-search form-inline tc_formulario'>
		<div class="titulo_tabela">Informações de Pesquisa de produtos recebidos</div>
		<br />
		<?
		if ($areaAdmin === true) {
		?>
		
			<input type="hidden" id="posto_id" name="posto[id]" value="<?=getValue('posto[id]')?>" />

			<div class="row-fluid">
				<div class="span2"></div>

				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_codigo">Código</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=getValue('posto[codigo]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>
				<div class="span4">
					<div class='control-group <?=(in_array('posto[id]', $msg_erro['campos'])) ? "error" : "" ?>' >
						<label class="control-label" for="posto_nome">Nome</label>
						<div class="controls controls-row">
							<div class="span10 input-append">
								<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=getValue('posto[nome]')?>" <?=$posto_readonly?> />
								<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
									<i class="icon-search"></i>
								</span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
							</div>
						</div>
					</div>
				</div>

				<div class="span2"></div>
			</div>
		<?
		}
		?>

		<div class='row-fluid'>
			<div class="span2"></div>

			<div class="span2">
				<div class='control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_inicial">Data Inicial</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_inicial" name="data_inicial" class="span12" type="text" value="<?=getValue('data_inicial')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array('data_final', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="data_final">Data Final</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="data_final" name="data_final" class="span12" type="text" value="<?=getValue('data_final')?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="span3">
				<div class='control-group'>
					<label class="control-label" for="os">OS</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="os" name="os" class="span12" type="text" value="<?=getValue('os')?>" />
						</div>
					</div>
				</div>
			</div>

		</div>

        <?php if ($login_fabrica == 156): ?>
		<div class='row-fluid'>
            <div class="span2"></div>

            <div class="span4">
				<div class='control-group <?=(in_array('numero_serie', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="numero_serie">Número de Série</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="numero_serie" name="numero_serie" class="span12" type="text" value="<?=getValue('numero_serie')?>" />
						</div>
					</div>
				</div>
			</div>

            <div class="span4">
				<div class='control-group <?=(in_array('autorizacao_postagem', $msg_erro['campos'])) ? "error" : "" ?>' >
					<label class="control-label" for="autorizacao_postagem">Autorização de Postagem</label>
					<div class="controls controls-row">
						<div class="span12">
							<input id="autorizacao_postagem" name="autorizacao_postagem" class="span12" type="text" value="<?=getValue('autorizacao_postagem')?>" />
						</div>
					</div>
				</div>
			</div>
        </div>
        <?php endif ?>

		<div class='row-fluid'>
			<div class='span5'></div>
			<div class="span2">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span10 input-append">
							<button type="submit" class="btn" style="webkit-border-radius: 0 0 0 0 !important; border-radius: 0 0 0 0 !important;" >Pesquisar</button>
							<input type="hidden" name="pesquisar" value="pesquisar" />
						</div>
					</div>
				</div>
			</div>
			<div class='span1'></div>
		</div>
	</form>

	

<?
if (pg_num_rows($resPesquisa) > 0) {
?>
	<table id="table_recebidos" class="table table-bordered table-striped table-fixed">
		<thead>
			<tr class="titulo_coluna" >
				<th colspan="8" >Ordens de serviços de reparo já entregues</th>
			</tr>
			<tr class="titulo_coluna">
				<th>OS</th>
				<th>Posto Autorizado</th>
				<th>Data de Abertura</th>
				<th>OS Posto Interno</th>
				<th>Posto Interno</th>
				<th>Data Abertura</th>
				<th>Data Fechamento</th>
				<th>Produto Recebido</th>
			</tr>
		</thead>
		<tbody>
			<?
			while($objeto_atendimento = pg_fetch_object($resPesquisa)){
				unset($data_format);
				$data_format = explode("-",$objeto_atendimento->data_abertura);
				$objeto_atendimento->data_abertura = $data_format[2]."/".$data_format[1]."/".$data_format[0];


				?>
				<tr>	
					<td><a target="_blank" href="os_press.php?os=<?=$objeto_atendimento->os?>" ><?=$objeto_atendimento->os?></a></td>
					<td><?=$objeto_atendimento->posto_autorizado?></td>
					<td><?=$objeto_atendimento->data_abertura?></td>
					<td><a target="_blank" href="os_press.php?os=<?=$objeto_atendimento->sua_os_interna?>" ><?=$objeto_atendimento->sua_os_interna?></a></td>
					<td><?=$objeto_atendimento->posto_interno?></td>
					<td><?=$objeto_atendimento->data_abertura_os_interna?></td>
					<td><?=$objeto_atendimento->data_fechamento_os_interna?></td>
					<td><?=$objeto_atendimento->data_ultimo_status?></td>
				</tr>
			<?
			}
			?>
		</tbody>
	</table>

<? 

}

?>
</div>
<? 
include "rodape.php";
?>

