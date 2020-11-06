<?php
//hd-2604602 - analise 29
$usa_campo_atendimento = true;


$regras["consumidor|cpf"] = array(
	"obrigatorio" => true
);

$regras["consumidor|email"] = array(
	"obrigatorio" => true
);

$regras["produto|codigo_lacre"] = array(
	"function" => array("valida_codigo_lacre_positron")
);

$regras["os|tipo_atendimento"] = array(
	"obrigatorio" => true
);

$regras["produto|serie"] = array(
	"function" => array("valida_numero_de_serie_positron")
);

$regras["produto|referencia"] = array( //hd_chamado=2717074
	"function" => array("valida_reparo_na_fabrica", "valida_garantia_positron")
);

$auditorias = array(
	"auditoria_os_reincidente_positron",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_os_revenda_positron"
);
//verifica_pre_os
$pre_funcoes_fabrica = array("valida_numero_serie", "retira_pecas_laudo_zero_hora","verifica_qtde_anexos");

$funcoes_fabrica = array("grava_anexo_sem_ns", "verifica_estoque_peca", "verifica_recall",
							"verifica_laudo_zero_hora", "pega_acessorios", "verifica_mau_uso","valida_lanca_peca");
$garantia_total = 0;


function retira_pecas_laudo_zero_hora(){

	global $login_fabrica, $campos, $os, $con, $login_admin;

	if($campos['os']['tipo_atendimento'] == 243){
		$campos['produto_pecas'] = array();
	}

}

function verifica_qtde_anexos(){
	global $campos;

	$count_anexo = array();

	if ($campos["posto"]["id"] == 420183){

		foreach ($campos["anexo"] as $key => $value) {
			if (strlen($value) > 0) {
				$count_anexo[] = "ok";
			}
		}

		if(count($count_anexo) < 2){
			throw new Exception("Para a abertura da Ordem de serviço é necessário dois anexos");
		}
	}
}


function valida_codigo_lacre_positron(){

	global $login_fabrica, $campos, $os, $con, $login_admin;

	$tipo_atendimento 	= $campos['os']['tipo_atendimento'];
	$codigo_lacre 		= $campos['produto']['codigo_lacre'];

	if(strlen(trim($tipo_atendimento))>0){

		$sql = "SELECT descricao FROM tbl_tipo_atendimento
				WHERE fabrica = $login_fabrica
				AND tipo_atendimento = $tipo_atendimento ";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)> 0 ){
			$descricao = pg_fetch_result($res, 0, 'descricao');
			if($descricao == "Laudo Zero Hora" and strlen(trim($codigo_lacre)) == 0 ){
				throw new Exception("Código do lacre inválido");
			}
		}
	}

}

function valida_reparo_na_fabrica() { //hd_chamado=2717074
	global $con, $campos, $login_fabrica, $msg_erro;

	if($campos["produto"]["id"] > 0){
		$produto_id = $campos["produto"]["id"];
		$sql = "select parametros_adicionais from tbl_produto where fabrica_i = $login_fabrica and produto = $produto_id";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$reparo_na_fabrica = trim(pg_fetch_result($res, 0, 'parametros_adicionais'));
			$param_adicionais = json_decode($reparo_na_fabrica,true);
            $reparo_na_fabrica = $param_adicionais['reparo_na_fabrica'];

            if($reparo_na_fabrica == 't'){
            	$msg_erro["msg"]["campo_obrigatorio"] = "Posto de assistência não tem autorização para realizar reparo ou descaracterizar <br/>
					(trocar peça, ressoldar e outros) este produto. Cliente deverá ser orientado a ligar para 0800-775-1400.";
				$msg_erro["campos"][] = "produto[referencia]";
            }
		}
	}
}

function valida_numero_de_serie_positron() {
	global $con, $campos, $login_fabrica,$msg_erro;

	if($campos['produto']['sem_ns'] != 't'){

		$produto_id = $campos["produto"]["id"];
		$produto_serie = $campos["produto"]["serie"];

		if (strlen($produto_id) > 0) {
			$sql = "select produto from tbl_produto where fabrica_i = $login_fabrica and produto = $produto_id and numero_serie_obrigatorio is true";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0 && empty($produto_serie)){
				$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
				$msg_erro["campos"][] = "produto[serie]";
			}
		}
	}else{
		$campos["produto"]["serie"] = "S/N";
	}
}


function valida_garantia_positron(){

	global $con, $login_fabrica, $campos, $garantia_mes, $valida_garantia, $garantia_total, $usou_garantia_estendida;

	$valida_garantia = "";

	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];

	$sql_garantia_produto = "SELECT garantia FROM tbl_produto WHERE produto = ".$campos['produto']['id'];
	$res_garantia_produto = pg_query($con, $sql_garantia_produto);
	if(pg_num_rows($res_garantia_produto)>0){
		$garantia 			= pg_fetch_result($res_garantia_produto, 0, 'garantia');
		$garantia_total 	= $garantia;
	}

	if (strtotime(formata_data($data_compra)." +{$garantia_total} months") < strtotime(formata_data($data_abertura))) {

			$sql = " select garantia_mes from tbl_cliente_garantia_estendida
					 where produto = ".$campos['produto']['id']."
					 and numero_serie = '".$campos['produto']['serie']."'
					 and cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."' ";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				$garantia_mes 				= pg_fetch_result($res, 0, 'garantia_mes');
				$garantia_total 			+= $garantia_mes;
				$usou_garantia_estendida 	= true;
			}
		}
	if (strtotime(formata_data($data_compra)." +{$garantia_total} months") < strtotime(formata_data($data_abertura))) {
		throw new Exception("Produto fora de garantia");		
	} else {
		return true;
	}
}

/*
function verifica_garantia_estendida(){
	global $con, $login_fabrica, $campos, $garantia_mes;

	$sql_garantia_produto = "SELECT garantia FROM tbl_produto WHERE produto = ".$campos['produto']['id'];
	$res_garantia_produto = pg_query($con, $sql_garantia_produto);
	if(pg_num_rows($res_garantia_produto)>0){
		$garantia = pg_fetch_result($res_garantia_produto, 0, 'garantia');
	}
	$sql = " select garantia_mes from tbl_cliente_garantia_estendida
			 where produto = ".$campos['produto']['id']."
			 and cpf = '".preg_replace("/[\.\-\/]/", "", $campos['consumidor']['cpf'])."' ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$garantia_mes = pg_fetch_result($res, 0, 'garantia_mes');
		$garantia_mes += $garantia;
	}
	
}
*/
function gravar_os_garantia(){
	global $con, $login_fabrica, $campos, $garantia_total, $os, $usou_garantia_estendida;

	if($usou_garantia_estendida == true){
		$garantia_estendida = "garantia_estendida";
	}else{
		$garantia_estendida = "";
	}

	$sql = "UPDATE tbl_os SET garantia_produto = '$garantia_total', certificado_garantia = '$garantia_estendida' WHERE os = $os";
	$res = pg_query($con, $sql);

}

//a pedido do Waldir isso foi comentado.
/*
function valida_anexo_sem_ns() {
	global $campos, $msg_erro;

	$campos["anexo_sem_ns"] 	= $_POST["anexo_sem_ns"];
	$campos["anexo_sem_ns_s3"] 	= $_POST["anexo_sem_ns_s3"];

	if(strlen($campos["anexo_sem_ns"] ) > 0){
		if (strlen($campos["anexo_sem_ns"]) > 0) {
			$count_anexo[] = "ok";
		}
	}

	if(!count($count_anexo)){
		$msg_erro["msg"][] = "Os anexos são obrigatórios";
		return false;
	}else{
		return true;
	}
}

function grava_anexo_sem_ns() {
	global $campos,  $os, $login_fabrica;

	if(valida_anexo_sem_ns() == true){
		$s3 = new AmazonTC('os_produto_serie', $login_fabrica);

		$arquivos = array();

			if ($campos["anexo_sem_ns_s3"][$key] != "t" && strlen($campos["anexo_sem_ns"]) > 0) {
				$ext = preg_replace("/.+\./", "", $campos["anexo_sem_ns"]);

				$arquivos[] = array(
					"file_temp" => $campos["anexo_sem_ns"],
					"file_new"  => "{$os}.{$ext}"
				);
			}
		if (count($arquivos) > 0) {

			$s3->moveTempToBucket($arquivos, null, null, false);
		}
	}
}
*/

/*
function verifica_anexo_sem_ns(){

	global $campos,  $os, $login_fabrica;
	$sem_ns 		= $_POST['produto']["sem_ns"];
	$anexo_sem_ns 	= $_POST["anexo_sem_ns"];

	if($sem_ns == 't' and strlen(trim($anexo_sem_ns))==0){
		throw new Exception("O campo anexo de produto sem N/S é obrigatório");
	}

}
*/

function auditoria_os_reincidente_positron(){
	global $campos, $con, $os, $login_fabrica;

	if(strlen(trim($campos['produto']['defeito_constatado']))>0){

		$sql = "select os from tbl_os
				where posto = ".$campos['posto']['id']."
				and serie = '".$campos['produto']['serie']."'
				and serie <> ''
				and serie <> 'S/N'
				and defeito_constatado = ".$campos['produto']['defeito_constatado']."
				and fabrica = $login_fabrica
				and os < $os
				and data_abertura >= (data_abertura - INTERVAL '90 days') limit 1";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$os_reincidente_positron = pg_fetch_result($res, 0, 'os');

			$sql_reincidencia = "UPDATE tbl_os SET os_reincidente = TRUE where fabrica = $login_fabrica AND os = $os";
			$res_reincidencia = pg_query($con, $sql_reincidencia);

			$sql_reincidencia_extra = "UPDATE tbl_os_extra SET os_reincidente = $os_reincidente_positron
										WHERE os = $os";
			$res_reincidencia_extra = pg_query($con, $sql_reincidencia_extra);
		}
	}
}

function auditoria_os_revenda_positron(){
	global $campos, $con, $os, $login_posto;

	$consumidor_revenda = $campos['os']['consumidor_revenda'];

	if ($consumidor_revenda == "R" AND $login_posto == 20564) {
		$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                          ({$os}, 6, 'OS em Auditoria de Reincidência')";
        $res = pg_query($con, $sql);
		
	}

}


function valida_lanca_peca(){

    global $login_fabrica, $campos, $os, $con;


    $pecas_pedido		= $campos["produto_pecas"];
	$defeito_constatado = $campos["produto"]["defeito_constatado"];

	if($campos['os']['tipo_atendimento'] != 243 and !empty($defeito_constatado)) {
		$sql = "SELECT defeito_constatado
				FROM tbl_defeito_constatado
				WHERE fabrica = $login_fabrica
				AND defeito_constatado = $defeito_constatado
				AND lancar_peca";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0) {
	
			$sql = "SELECT os
				FROM tbl_os_item 
				join tbl_os_produto using(os_produto)
				WHERE os = $os";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) == 0 and verifica_peca_lancada() == false) {
				throw new Exception("É obrigado lançar peça para este defeito constatado");
			}
		}
	}
}

function verifica_recall(){
	global $login_fabrica, $campos, $os, $con, $login_admin;

	$produto 	= $campos['produto']['id'];
	$serie 		= $campos['produto']['serie'];
	$posto 		= $campos['posto']['id'];
	$defeito_constatado = $campos['produto']['defeito_constatado'];

	if(strlen(trim($defeito_constatado))>0){

		$descricao_mau_uso 	= $campos['produto']['desc_mau_uso'];

		$sql = "SELECT descricao from tbl_defeito_constatado where fabrica = $login_fabrica and defeito_constatado = $defeito_constatado";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, descricao);
		}

		if($descricao != "Mau Uso"){

			$sql = "SELECT peca FROM tbl_produto_recall
					WHERE produto = $produto
					AND  '$serie' BETWEEN serie_inicial AND  serie_final
					AND fabrica = $login_fabrica";
			$res = pg_query($con, $sql);
			$dados_pecas = array();
			if(pg_num_rows($res)>0){
				for($i=0; $i<pg_num_rows($res); $i++){
					$peca = pg_fetch_result($res, $i, peca);
					$dados_pecas["$peca"] = $peca;
				}
			}

			$sql_os_produto = "SELECT os_produto FROM tbl_os_produto WHERE os = $os ";
			$res_os_produto = pg_query($con, $sql_os_produto);
			if(pg_num_rows($res_os_produto)>0){
				$os_produto = pg_fetch_result($res_os_produto, 0, os_produto);
			}


			foreach($dados_pecas as $peca){
				$sql = "SELECT tbl_os_item.peca FROM tbl_os_produto
						INNER JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
						WHERE tbl_os_produto.os = $os
						AND tbl_os_item.peca = $peca ";

				$res = pg_query($con, $sql);

				if(pg_num_rows($res) == 0){

					$sql_lista_basica = "SELECT qtde FROM tbl_lista_basica
										 WHERE produto = $produto
										 AND peca = $peca
										 ANd fabrica = $login_fabrica";
					$res_lista_basica = pg_query($con, $sql_lista_basica);
					if(pg_num_rows($res_lista_basica)>0){
						$qtde = pg_fetch_result($res_lista_basica, 0, qtde);
					}

					$campos_adicionais = array('recall' => true );
					$campos_adicionais = json_encode($campos_adicionais);

					$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin, fabrica_i, posto_i, produto_i, parametros_adicionais)
							VALUES ($os_produto, $peca, $qtde, 11194, $login_admin, $login_fabrica, $posto, $produto, '".$campos_adicionais."' ) ";
					$res = pg_query($con, $sql);

				}
			}
		}



	}


}

function verifica_pre_os(){
	global $login_fabrica, $campos, $os, $con, $login_admin;

	$posto 				= $campos['posto']['id'];
	$cpf_consumidor		= $campos['consumidor']['cpf'];

	if($campos['os']['hd_chamado'] == ''){

		$retirar = array(".", '-');

		$cpf_consumidor = str_replace($retirar, "", $cpf_consumidor);

		$sql = "SELECT tbl_hd_chamado.hd_chamado
				FROM tbl_hd_chamado_extra
				INNER JOIN tbl_hd_chamado using(hd_chamado)
				WHERE tbl_hd_chamado_extra.os is null and abre_os is true
				AND tbl_hd_chamado_extra.posto = $posto
				AND cpf = '$cpf_consumidor'
				AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			throw new Exception("Já existe uma pré-os aberta para esse CPF ");
		}
	}
}

function valida_numero_serie(){

	global $login_fabrica, $campos, $os, $con, $login_admin;

	if($campos['produto']['sem_ns'] != 't'){

		$serie 		= $campos['produto']['serie'];
		$produto 	= $campos['produto']['id'];

		$sql = "SELECT numero_serie_obrigatorio FROM tbl_produto
				WHERE produto = $produto
				AND fabrica_i = $login_fabrica
				AND numero_serie_obrigatorio = 't'";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){

			$primeiro_caracter = substr($serie, 0,1);
			$ano = substr($serie, 1,2);
			$mes = substr($serie, 3,2);
			$finais = substr($serie, 5,5);

			$ano_limite = date("y") - 5;

			$array_primeiro = array(7,8,3);

			if(!in_array($primeiro_caracter, $array_primeiro)){
				$erro = TRUE;
			}

			if(strlen(trim($serie)) != 10){
				$erro = TRUE;
			}

			if($ano < $ano_limite or $ano > date("y")){
				$erro = true;
			}

			if($mes > 12){
				$erro = true;
			}

			if($erro == 1 and $serie <> 'S/N'){
				throw new Exception("Número de série inválido");
			}
		}
	}
}

function pega_acessorios(){

	global $login_fabrica, $campos, $os, $con, $login_admin;

	$tipo_atendimento 	= $campos['os']['tipo_atendimento'];
	$codigo_lacre 		= $campos['produto']['codigo_lacre'];

	if(strlen(trim($tipo_atendimento))>0){

		$sql = "SELECT descricao FROM tbl_tipo_atendimento
				WHERE fabrica = $login_fabrica
				AND tipo_atendimento = $tipo_atendimento ";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)> 0 ){
			$descricao = pg_fetch_result($res, 0, 'descricao');
			if($descricao != "Laudo Zero Hora"){
				$i=0;
				foreach($campos['produto']['acessorio'] as $acessorio){
					if($i>0){
						$nome_acessorio .= ", ";
					}
					$nome_acessorio .= $acessorio;
					$i++;
				}

				$sql = "UPDATE tbl_os SET acessorios = '$nome_acessorio' where os = $os";
				$res = pg_query($con, $sql);
			}
		}
	}
}

function verifica_laudo_zero_hora(){

	global $login_fabrica, $campos, $os, $con, $login_admin, $erro_acessorio, $erro_aparencia;

	$tipo_atendimento 	= $campos['os']['tipo_atendimento'];
	$produto_pecas 		= $campos['produto_pecas'][0]['id'];
	$defeito_constatado = $campos['produto']['defeito_constatado'];
	$acessorio 			= $campos['os']['acessorios'];
	$aparencia 			= $campos['os']['aparencia_produto'];
	$codigo_lacre 		= $campos['produto']['codigo_lacre'];

	if(strlen(trim($login_admin)) == 0){
		$login_admin = 'null';
	}

	if(strlen(trim($tipo_atendimento))>0){

		$sql = "SELECT descricao FROM tbl_tipo_atendimento
				WHERE fabrica = $login_fabrica
				AND tipo_atendimento = $tipo_atendimento ";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)> 0 ){
			$descricao = pg_fetch_result($res, 0, 'descricao');
			if($descricao == "Laudo Zero Hora" and ($acessorio == "incompleto" or $aparencia == "usado" or $acessorio == "" or $aparencia == "")) {
				if($acessorio == "incompleto" or $acessorio == ""){
					$erro_acessorio = true;
					throw new Exception("O acessórios não pode ser incompleto ou vazio");
				}
				if($aparencia == "usado" or $aparencia == ""){
					$erro_aparencia = true;
					throw new Exception("A aparência do produto não pode ser usado ou vazio");
				}
			}elseif($descricao == "Laudo Zero Hora" and ($acessorio == "completo" or $aparencia == "novo")){
				$erro_aparencia = false;
				$erro_acessorio = false;
				$sql = "UPDATE tbl_os SET codigo_fabricacao = '$codigo_lacre', data_fechamento = now(), finalizada = now(), os_fechada = 't' WHERE os = $os ";
				$res = pg_query($con, $sql);

			}
		}
	}
}

function verifica_mau_uso(){

	global $login_fabrica, $campos, $os, $con, $login_admin, $erro_campo_mau_uso;

	$defeito_constatado = $campos['produto']['defeito_constatado'];
	$descricao_mau_uso 	= $campos['produto']['desc_mau_uso'];

	if(strlen(trim($defeito_constatado))>0){
		$sql = "SELECT descricao from tbl_defeito_constatado where fabrica = $login_fabrica and defeito_constatado = $defeito_constatado";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res, 0, descricao);

			if($descricao == "Mau Uso"){

				if(strlen(trim($descricao_mau_uso))==0){
					$erro_campo_mau_uso = true;
					throw new Exception("O campo descrição do mau uso deve ser preenchido");
				}else{
					$sql_upd = "UPDATE tbl_os_extra SET obs_adicionais = '$descricao_mau_uso' where os = $os";
					$res_upd = pg_query($con, $sql_upd);

					$sql_upd_os = "UPDATE tbl_os SET excluida = 't' WHERE os = $os and fabrica = $login_fabrica";
					$res_upd_os = pg_query($con, $sql_upd_os);
				}
			}
		}
	}
}

?>
