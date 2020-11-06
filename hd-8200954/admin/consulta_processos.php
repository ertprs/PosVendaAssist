<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';
  // echo "<pre>";
  // print_r($_POST);
  // echo "</pre>";

$title = "Consulta de Processos Jurídicos";
$layout_menu = "gerencia";
$admin_privilegios="gerencia";

function verificaDataValida($data){
    if(!empty($data)){
        list($di, $mi, $yi) = explode("/", $data);

        return checkdate($mi,$di,$yi) ? true : false;
    }

    return false;
}
function subtraiData($data, $dias = 0, $meses = 0, $ano = 0){
    $data = explode("/", $data);
    $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] - $meses,
    $data[0] - $dias, $data[2] - $ano) );

    return $newData;
}

function somaData($data, $dias = 0, $meses = 0, $ano = 0){
    $data = explode("/", $data);
    $newData = date("d/m/Y", mktime(0, 0, 0, $data[1] + $meses,
    $data[0] + $dias, $data[2] + $ano) );

    return $newData;
}
function mascara($val, $mascara){
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mascara)-1; $i++){
		if($mascara[$i] == '#'){
			if(isset($val[$k]))
				$maskared .= $val[$k++];
		}else{
			if(isset($mascara[$i]))
				$maskared .= $mascara[$i];
		}
	}
	return $maskared;
}

if ($_POST["pesquisar"] == "Pesquisar") {

	if(in_array($login_fabrica, array(42,183))){
		$nome_consumidor = $_POST["nome_consumidor"];	

		if(strlen(trim($nome_consumidor))> 0 ){
			$condNome = " AND tbl_processo.consumidor_nome ILIKE '$nome_consumidor%' ";
		}  
	}

	if (strlen($_POST["n_processo"])) {

		$n_processo = $_POST["n_processo"];
		$sql_n_processo = "AND tbl_processo.numero_processo = '$n_processo'";

	}else if ($login_fabrica == 183 AND strlen(trim($_POST['numero_processo_telecontrol'])) > 0){
		$numero_processo_telecontrol = $_POST["numero_processo_telecontrol"];
		$sql_n_processo = "AND tbl_processo.processo = $numero_processo_telecontrol";
	}else{

		$sql_n_processo = "";

		if(strlen(trim($nome_consumidor))==0){

			if (strlen($_POST["data_inicio"])) {
				$data_inicio = $_POST["data_inicio"];
				$data_inicial = $data_inicio;
				if(empty($data_inicial) OR !verificaDataValida($data_inicial)){
			        $data_inicial       = subtraiData(Date('d/m/Y'),0,0,1);
			        $data_final         = Date('d/m/Y');
			    }
			    $aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));
			}else{
				if($login_fabrica == 42){
					if(strlen(trim($consumidor_cpf))==0){
						$msg_erro["campos"][] = "data_inicio";
					}
				}else{
					$msg_erro["campos"][] = "data_inicio";
				}
			}

			if (strlen($_POST["data_fim"])) {
				$data_fim = $_POST["data_fim"];
				$data_final = $data_fim;
				if(empty($data_final) OR !verificaDataValida($data_final)){
			        $data_final = somaData($data_inicial,0,0,1);
			    }
			    $aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));
			}else{
				if($login_fabrica == 42 ){
					if(strlen(trim($consumidor_cpf))==0){
						$msg_erro["campos"][] = "data_fim";
					}
				}else{
					$msg_erro["campos"][] = "data_fim";
				}
				
			}

	}

		if (count($msg_erro["campos"])>0) {
			$msg_erro["msg"][] = "Preencher os Campos Obrigatórios.";
		}

		if (count($msg_erro["msg"])==0) {

			if($aux_data_inicial > $aux_data_final){

		    	$msg_erro["msg"][] = "Intervalo de Datas Incorreto.";

		    }else{

		    	$sqlX = "SELECT '$aux_data_inicial'::date + interval '12 months' > '$aux_data_final'";
				$resX = pg_query($con,$sqlX);
				$periodo_meses = pg_fetch_result($resX,0,0);

				if($periodo_meses == 'f'){

					$msg_erro["msg"][] = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES";
					$msg_erro["campos"][] = "data_inicio";
					$msg_erro["campos"][] = "data_fim";

				}
		    }
		}

		$aux_data_inicial   = implode("-", array_reverse(explode("/", $data_inicial)));
    	$aux_data_final     = implode("-", array_reverse(explode("/", $data_final)));
	}



	if (strlen($_POST["data_fim"]) AND strlen($_POST["data_inicio"]) AND !strlen($_POST["n_processo"])) {
		if (strlen($_POST["tipo_data"])) {
			$tipo_data = $_POST["tipo_data"];

			if ($tipo_data === "data_processo") {
				$sql_data = "AND (tbl_processo.data_input BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')";
			}elseif ($tipo_data === "data_audiencia") {

				if($login_fabrica == 42){
					$sql_data = "AND ((tbl_processo_item.data_audiencia1 BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')
							 OR (tbl_processo_item.data_audiencia2 BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'))";
				}else{
					$sql_data = "AND ((tbl_processo.data_audiencia1 BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')
							 OR (tbl_processo.data_audiencia2 BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'))";	
				}
				

			}elseif($tipo_data === "data_notificacao"){
				$sql_data = "AND (tbl_processo_item.data_notificacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')";
			}
		}else{
			$sql_data = "AND (tbl_processo.data_input BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59')";
		}
		
	}else{
		$sql_data = "";
	}

	if (strlen($_POST["orgao"])) {

		$orgao = $_POST["orgao"];

		if ($orgao === "orgao_procon") {

			$sql_orgao = "AND tbl_processo.orgao = 'Procon'";

		}elseif ($orgao === "orgao_juizado") {

			$sql_orgao = "AND tbl_processo.orgao = 'Juizado'";

		}else{

			$sql_orgao = "";

		}
	}

	if (strlen($_POST["status_p"])) {

		$status_processo = $_POST["status_p"];

		if ($status_processo === "status_aberto") {

			$sql_status_processo = "AND tbl_processo.data_solucao is null";

		}elseif ($status_processo === "status_fechado") {

			$sql_status_processo = "AND tbl_processo.data_solucao is not null";

		}else{

			$sql_status_processo = "";

		}
	}

	if (strlen($_POST["estado"])) {

		$estado = $_POST["estado"];
		$sql_estado = "AND tbl_cidade.estado = '$estado'";

	}else{

		$join_cidade = "";
		$sql_estado = "";

	}

	if (strlen($_POST["consumidor_cpf"])) {

		$consumidor_cpf = $_POST["consumidor_cpf"];
		$vowels = array(".", "-", "/");
		$consumidor_cpf = str_replace($vowels, "", $consumidor_cpf);

		$sql_cpf_cnpj = "AND tbl_processo.consumidor_cpf_cnpj = '$consumidor_cpf'";

	}else{


		$sql_cpf_cnpj = "";

	}

	if (count($msg_erro["msg"]) == 0) {

		if($login_fabrica == 42){
			/*$data_audiencias = " to_char(tbl_processo_item.data_audiencia1, 'DD/MM/YYYY') AS 					data_audiencia1,
							to_char(tbl_processo_item.data_audiencia2, 'DD/MM/YYYY') AS data_audiencia2, 
							to_char(tbl_processo_item.data_notificacao, 'DD/MM/YYYY') AS data_notificacao,";*/
			$join_processo_item = " left join tbl_processo_item on tbl_processo_item.processo = tbl_processo.processo  ";
		}else{
			$data_audiencias = " to_char(data_audiencia1, 'DD/MM/YYYY') AS data_audiencia1,
							to_char(data_audiencia2, 'DD/MM/YYYY') AS data_audiencia2, 
							to_char(data_notificacao, 'DD/MM/YYYY') AS data_notificacao,";
			$join_processo_item = " ";
		}

		$sql_pesq = "SELECT distinct tbl_processo.numero_processo as processo,
							tbl_processo.processo as processo_id,
							tbl_processo.fabrica as fabrica,
							tbl_os.os as os,
							tbl_os.serie as serie,
							tbl_hd_chamado.hd_chamado as hd_chamado,
							tbl_admin.nome_completo as atendente,
							tbl_hd_chamado.status as status,
							tbl_posto.nome_fantasia as posto,
							orgao,
							tbl_processo.consumidor_nome as consumidor_nome,
							tbl_processo.consumidor_cpf_cnpj as consumidor_cpf,
							tbl_processo.consumidor_fone1 as consumidor_fone1,
							tbl_processo.consumidor_fone2 as consumidor_fone2,
							tbl_processo.consumidor_email as consumidor_email,
							tbl_processo.consumidor_endereco as consumidor_endereco,
							tbl_processo.consumidor_bairro as consumidor_bairro,
							tbl_processo.consumidor_numero as consumidor_numero,
							tbl_processo.consumidor_complemento as consumidor_complemento,
							tbl_status_processo.descricao as status_processo_descricao,
							tbl_cidade.nome as cidade,
							tbl_cidade.estado as estado,
							tbl_processo.consumidor_cep as consumidor_cep,
							$data_audiencias
							to_char(data_solucao, 'DD/MM/YYYY') AS data_solucao,
							advogado_nome,
							advogado_celular,
							advogado_email,
							solucao,
							valor_cliente,
							custo_advogado,
							historico,
							tbl_motivo_processo.descricao as motivo_processo,
							tbl_produto.referencia as produto,
							tbl_produto.descricao as descricao,
							tbl_processo.data_input,
							tbl_processo.observacao AS observacao_audiencia
						FROM tbl_processo
							LEFT JOIN tbl_produto on (tbl_processo.produto = tbl_produto.produto)
							LEFT JOIN tbl_status_processo using(status_processo)
							LEFT JOIN tbl_hd_chamado on (tbl_processo.hd_chamado = tbl_hd_chamado.hd_chamado)
							LEFT JOIN tbl_os on (tbl_processo.os = tbl_os.os)
							LEFT JOIN tbl_posto on (tbl_os.posto = tbl_posto.posto)
							LEFT JOIN tbl_posto_fabrica on (tbl_posto.posto = tbl_posto_fabrica.posto) AND tbl_posto_fabrica.fabrica = $login_fabrica
							LEFT JOIN tbl_admin on (tbl_hd_chamado.admin = tbl_admin.admin )
							LEFT JOIN tbl_cidade on (tbl_processo.cidade = tbl_cidade.cidade)
							LEFT JOIN tbl_motivo_processo on (tbl_processo.motivo_processo = tbl_motivo_processo.motivo_processo)
							$join_processo_item

						WHERE tbl_processo.fabrica = $login_fabrica
						$sql_orgao
						$sql_estado
						$sql_data
						$sql_status_processo
						$sql_cpf_cnpj
						$sql_n_processo
						$condNome
						;";
						
		$res_pesq = pg_query($con,$sql_pesq);

		if ($_POST["gerar_excel"]) {

			if (pg_num_rows($res_pesq)>0) {
				$data = date("d-m-Y-H-i");
				$fileName = "relatorio_processos_{$data}.csv";
				$file = fopen("/tmp/{$fileName}", "w");

				if($login_fabrica == 81){
					$head = "N. Processo;Orgão;Nome Cliente;CPF;Telefone 1;Telefone 2;E-mail;CEP;Estado;Cidade;Bairro;Endereço;Número;Complemento;Atendimento;Status Atendimento;Atendente;Posto;Ordem de Serviço;Produto;Nº Série;Motivo Principal;Data Notificação;Data Audiência 1;Data Audiência 2;Solução;Data Solução;Nome Advogado;Telefone Advogado;E-mail;Custo Advogado;Valor Cliente;\r\n";
				}else if ($login_fabrica == 183){
					$head = "Data Cadastro;N. Processo;Status do Processo;Orgão;Nome Cliente;CPF;Telefone 1;Telefone 2;E-mail;CEP;Estado;Cidade;Bairro;Endereço;Número;Complemento;Atendimento;Status Atendimento;Atendente;Posto;Ordem de Serviço;Produto;Nº Série;Motivo Principal;Data Notificação;Data Audiência 1;Data Audiência 2;Data Solução;Periodo em Aberto;Nome Advogado;Telefone Advogado;E-mail;Custo Advogado;Valor Cliente;\r\n";
				}else{
					$head = "N. Processo;Orgão;Nome Cliente;CPF;Telefone 1;Telefone 2;E-mail;CEP;Estado;Cidade;Bairro;Endereço;Número;Complemento;Atendimento;Status Atendimento;Atendente;Posto;Ordem de Serviço;Produto;Nº Série;Motivo Principal;Data Notificação;Data Audiência 1;Data Audiência 2;Solução;Data Solução;Nome Advogado;Telefone Advogado;E-mail;Custo Advogado;Valor Cliente;Observação\r\n";
				}

				fwrite($file, $head);
				$body = '';

				for ($x=0; $x<pg_num_rows($res_pesq);$x++){
					$x_processo_id 			= pg_fetch_result($res_pesq, $x,'processo_id');
					$x_processo				= pg_fetch_result($res_pesq, $x,'processo');
					$x_orgao				= pg_fetch_result($res_pesq, $x,'orgao');
					$x_cons_nome			= pg_fetch_result($res_pesq, $x,'consumidor_nome');
					$x_cons_cpf				= pg_fetch_result($res_pesq, $x,'consumidor_cpf');
					$x_cons_fone1			= pg_fetch_result($res_pesq, $x,'consumidor_fone1');
					$x_cons_fone2			= pg_fetch_result($res_pesq, $x,'consumidor_fone2');
					$x_cons_email			= pg_fetch_result($res_pesq, $x,'consumidor_email');
					$x_cons_cep				= pg_fetch_result($res_pesq, $x,'consumidor_cep');
					$x_cons_uf 				= pg_fetch_result($res_pesq, $x,'estado');
					$x_cidade				= pg_fetch_result($res_pesq, $x,'cidade');
					$x_cons_bairro			= pg_fetch_result($res_pesq, $x,'consumidor_bairro');
					$x_cons_endereco		= pg_fetch_result($res_pesq, $x,'consumidor_endereco');
					$x_cons_numero			= pg_fetch_result($res_pesq, $x,'consumidor_numero');
					$x_cons_complemento		= pg_fetch_result($res_pesq, $x,'consumidor_complemento');
					$x_atendimento			= pg_fetch_result($res_pesq, $x,'hd_chamado');
					$x_status_atendimento   = pg_fetch_result($res_pesq, $x,'status');
					$x_status_processo_descricao   = pg_fetch_result($res_pesq, $x,'status_processo_descricao');
					$x_atendente			= pg_fetch_result($res_pesq, $x,'atendente');
					$x_posto				= pg_fetch_result($res_pesq, $x,'posto');
					$x_os 					= pg_fetch_result($res_pesq, $x,'os');
					$x_prod_ref 			= pg_fetch_result($res_pesq, $x,'produto');
					$x_prod_desc			= pg_fetch_result($res_pesq, $x,'descricao');
					$x_ns 					= pg_fetch_result($res_pesq, $x,'serie');
					$x_motivo_processo		= pg_fetch_result($res_pesq, $x,'motivo_processo');
					$x_data_not				= pg_fetch_result($res_pesq, $x,'data_notificacao');
					$x_data_audiencia1 		= pg_fetch_result($res_pesq, $x,'data_audiencia1');
					$x_data_audiencia2 		= pg_fetch_result($res_pesq, $x,'data_audiencia2');
					$x_solucao 				= pg_fetch_result($res_pesq, $x,'solucao');
					$x_data_solucao 		= pg_fetch_result($res_pesq, $x,'data_solucao');
					$x_adv_nome 			= pg_fetch_result($res_pesq, $x,'advogado_nome');
					$x_adv_celular 			= pg_fetch_result($res_pesq, $x,'advogado_celular');
					$x_adv_email 			= pg_fetch_result($res_pesq, $x,'advogado_email');
					$x_adv_custo 			= pg_fetch_result($res_pesq, $x,'custo_advogado');
					$x_valor_cli 			= pg_fetch_result($res_pesq, $x,'valor_cliente');
					$x_historico 			= pg_fetch_result($res_pesq, $x,'historico');
					$x_dt_processo_t 		= mostra_data(substr(pg_fetch_result($res_pesq, $x, 'data_input'),0 ,10));
					
					if (strlen($x_cons_cpf)>11) {
						$x_cons_cpf = mascara($x_cons_cpf,'##.###.###/####-##');
					}else{
						$x_cons_cpf = mascara($x_cons_cpf,'###.###.###-##');
					}

					if ($login_fabrica == 183){
						$sql_processo_item = "
							SELECT 
								(
									SELECT data_notificacao 
									FROM tbl_processo_item 
									WHERE processo = $x_processo_id 
									ORDER BY tbl_processo_item.data_input ASC LIMIT 1
								) AS data_inicio_processo,
								(SELECT COUNT(*) FROM tbl_processo_item WHERE processo = $x_processo_id) AS qtde_estapas,
								TO_CHAR(data_notificacao, 'DD/MM/YYYY') AS data_notificacao,
								TO_CHAR(data_audiencia1, 'DD/MM/YYYY') AS data_audiencia1,
								TO_CHAR(data_audiencia2, 'DD/MM/YYYY') AS data_audiencia2
							FROM tbl_processo_item
							WHERE tbl_processo_item.processo = $x_processo_id
							ORDER BY tbl_processo_item.data_input DESC LIMIT 1";
						$res_processo_item = pg_query($con, $sql_processo_item);

						if (pg_num_rows($res_processo_item) > 0){
							$data_inicio_processo = pg_fetch_result($res_processo_item, 0, "data_inicio_processo");
							$qtde_estapas     	  = pg_fetch_result($res_processo_item, 0, "qtde_estapas");
							$data_notificacao 	  = pg_fetch_result($res_processo_item, 0, "data_notificacao");
							$x_data_audiencia1    = pg_fetch_result($res_processo_item, 0, "data_audiencia1");
							$x_data_audiencia2    = pg_fetch_result($res_processo_item, 0, "data_audiencia2");
							
							$data1 = new DateTime(formata_data($x_data_solucao));
							$data2 = new DateTime($data_inicio_processo );
							$intervalo = $data1->diff( $data2 );
							$msg_intervalo = "{$intervalo->y} anos, {$intervalo->m} meses e {$intervalo->d} dias"; 
						}
					}
					//$body .= $x_processo.";".$x_orgao.";".$x_corrocco95ns_nome.";".$x_cons_cpf.";".$x_cons_fone." ;".$x_cons_fone." ;".$x_cons_email.";".$x_cons_cep.";".$x_cons_uf.";".$x_cidade.";".$x_cons_bairro.";".$x_cons_endereco.";".$x_cons_numero.";".$x_cons_complemento.";".$x_atendimento.";".$x_status_atendimento.";".$x_atendente.";".$x_posto.";".$x_os.";".$x_produto.                  ";".$x_ns.";".$x_motivo_processo.";".$x_data_not.";".$x_data_audiencia1.";".$x_data_audiencia2.";".$x_solucao.";".$x_data_solucao.";".$x_adv_nome.";".$x_adv_celular.";".$x_adv_email.";".$x_adv_custo.";".$x_valor_cli.";".$x_historico;

					if($login_fabrica == 81){
						$body .= $x_processo.";".$x_orgao.";".$x_cons_nome.";".$x_cons_cpf.";".$x_cons_fone1.";".$x_cons_fone2.";".$x_cons_email.";".$x_cons_cep.";".$x_cons_uf.";".$x_cidade.";".$x_cons_bairro.";".$x_cons_endereco.";".$x_cons_numero.";".$x_cons_complemento.";".$x_atendimento.";".$x_status_atendimento.";".$x_atendente.";".$x_posto.";".$x_os.";".$x_prod_ref." - ".$x_prod_desc.";".$x_ns.";".$x_motivo_processo.";".$x_data_not.";".$x_data_audiencia1.";".$x_data_audiencia2.";".$x_solucao.";".$x_data_solucao.";".$x_adv_nome.";".$x_adv_celular.";".$x_adv_email.";".$x_adv_custo.";".$x_valor_cli;
					}else if ($login_fabrica == 183){
						$body .= $x_dt_processo_t.";".$x_processo.";".$x_status_processo_descricao.";".$x_orgao.";".$x_cons_nome.";".$x_cons_cpf.";".$x_cons_fone1.";".$x_cons_fone2.";".$x_cons_email.";".$x_cons_cep.";".$x_cons_uf.";".$x_cidade.";".$x_cons_bairro.";".$x_cons_endereco.";".$x_cons_numero.";".$x_cons_complemento.";".$x_atendimento.";".$x_status_atendimento.";".$x_atendente.";".$x_posto.";".$x_os.";".$x_prod_ref." - ".$x_prod_desc.";".$x_ns.";".$x_motivo_processo.";".$x_data_not.";".$x_data_audiencia1.";".$x_data_audiencia2.";".$x_data_solucao.";".$msg_intervalo.";".$x_adv_nome.";".$x_adv_celular.";".$x_adv_email.";".$x_adv_custo.";".$x_valor_cli;
					}else{
						$body .= $x_processo.";".$x_orgao.";".$x_cons_nome.";".$x_cons_cpf.";".$x_cons_fone1.";".$x_cons_fone2.";".$x_cons_email.";".$x_cons_cep.";".$x_cons_uf.";".$x_cidade.";".$x_cons_bairro.";".$x_cons_endereco.";".$x_cons_numero.";".$x_cons_complemento.";".$x_atendimento.";".$x_status_atendimento.";".$x_atendente.";".$x_posto.";".$x_os.";".$x_prod_ref." - ".$x_prod_desc.";".$x_ns.";".$x_motivo_processo.";".$x_data_not.";".$x_data_audiencia1.";".$x_data_audiencia2.";".$x_solucao.";".$x_data_solucao.";".$x_adv_nome.";".$x_adv_celular.";".$x_adv_email.";".$x_adv_custo.";".$x_valor_cli.";".$x_historico;
					}
					$body .= "\r\n";
					// $body .= $x_processo.";".$x_orgao.";".$x_cons_nome.";".$x_cons_cpf.";".$x_cons_fone1.";".$x_cons_fone2.";".$x_cons_email.";".$x_cons_cep.";".$x_cons_uf.";".$x_cidade.";".$x_cons_bairro.";".$x_cons_endereco.";".$x_cons_numero.";".$x_cons_complemento.";".$x_atendimento.";".$x_status_atendimento.";".$x_atendente.";".$x_posto.";".$x_os.";".$x_prod_ref."-"$x_prod_desc.";".$x_ns.";".$x_motivo_processo.";".$x_data_not.";".$x_data_audiencia1.";".$x_data_audiencia2.";".$x_solucao.";".$x_data_solucao.";".$x_adv_nome.";".$x_adv_celular.";".$x_adv_email.";".$x_adv_custo.";".$x_valor_cli.";".$x_historico;
					// $body .= "\r\n";
				}
				$body = $body;
			    fwrite($file, $body);
			    fclose($file);
			    if (file_exists("/tmp/{$fileName}")) {

	                system("mv /tmp/{$fileName} xls/{$fileName}");

	                echo "xls/{$fileName}";
				}
			}
			exit;
		}
	}
}





include 'cabecalho_new.php';

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "dataTable"
);

include 'plugin_loader.php';
?>

<script type="text/javascript">

$(function() {
	/**
	 * Carrega o datepicker já com as mascara para os campos
	 */
	//$("#data_nascimento").datepicker({ maxDate: 0, minDate: "-6d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_inicio").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	$("#data_fim").datepicker({  dateFormat: "dd/mm/yy" }).mask("99/99/9999");
	//$("#consumidor_cpf").mask("999.999.999-99");

	/**
	 * Inicia o shadowbox, obrigatório para a lupa funcionar
	 */
	Shadowbox.init();


	/**
	 * Evento que chama a função de lupa para a lupa clicada
	 */
	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});


	<?php
		if(strlen(getValue('consumidor_cpf')) > 0){
			if(strlen(getValue('consumidor_cpf')) > 14){
	?>
				$("#consumidor_cpf").mask("99.999.999/9999-99");
				$("label[for=consumidor_cpf]").html("CNPJ");
	<?php
			}else{
	?>
				$("#consumidor_cpf").mask("999.999.999-99");
				$("label[for=consumidor_cpf]").html("CPF");
	<?php
			}
	?>
	<?php
		}
	?>
	/**
	 * Evento de keypress do campo consumidor_cpf
	 * Irá verificar o tamanho do campo, se o tamanho já for 14(CPF) irá alterar a máscara para CNPJ e alterar o Label
	 */
	$("#consumidor_cpf").blur(function(){
		var tamanho = $(this).val().replace(/\D/g, '');

		if(tamanho.length > 11){
			$("#consumidor_cpf").mask("99.999.999/9999-99");
			$("label[for=consumidor_cpf]").html("CNPJ");
		}else{
			$("#consumidor_cpf").mask("999.999.999-99");
			$("label[for=consumidor_cpf]").html("CPF");
		}
	});

	$("#consumidor_cpf").focus(function(){
		$(this).unmask();
	});

});

<?php if($login_fabrica == 81){//HD-3251974 ?>
function verObservacoes(processo){
	var tr = $('#'+processo);
	if(tr.is(":visible") == false){
		tr.show();
	}else{
		tr.hide();
	}
}
<?php } ?>
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<FORM name='frm_pesquisa_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline tc_formulario">
<div class="titulo_tabela">Consulta de Processos</div>
<br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span2">
			<div class='control-group' >
				<label class="control-label" for="n_processo">Nº Processo</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="n_processo" name="n_processo" class="span12" type="text" value="<?=getValue('n_processo')?>" />
					</div>
				</div>
			</div>
		</div>
		<?php if ($login_fabrica == 183){ ?>
		<div class="span2">
			<div class='control-group' >
				<label class="control-label" for="numero_processo_telecontrol">Nº Proc. Telecontrol</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="numero_processo_telecontrol" name="numero_processo_telecontrol" class="span12" type="text" value="<?=getValue('numero_processo_telecontrol')?>" />
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class="span2">
			<div class='control-group' >
				<label class="control-label" for="consumidor_cpf">CPF / CNPJ</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="consumidor_cpf" name="consumidor_cpf" class="span12" type="text" value="<?=getValue('consumidor_cpf')?>" />
					</div>
				</div>
			</div>
		</div>
		<?php if(in_array($login_fabrica, array(42,183))){?>
   		<div class="span3">
   			<div class='control-group' >
				<label class="control-label" for="nome_consumidor">Nome Consumidor</label>
				<div class="controls controls-row">
					<div class="span12">
						<input id="nome_consumidor" name="nome_consumidor" class="span12" type="text" value="<?=getValue('nome_consumidor')?>" />
					</div>
				</div>
			</div>   			
   		</div>
   		<?php } ?>
   	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span2">
			<div class='control-group <?=(in_array("data_inicio", $msg_erro["campos"])) ? "error" : ""?>' >
				<label class="control-label" for="data_inicio">Data Inicio</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?=getValue('data_inicio')?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class='control-group <?=(in_array("data_fim", $msg_erro["campos"])) ? "error" : ""?>' >
				<label class="control-label" for="data_fim">Data Fim</label>
				<div class="controls controls-row">
					<div class="span12">
						<h5 class='asteristico'>*</h5>
						<input id="data_fim" name="data_fim" class="span12" type="text" value="<?=getValue('data_fim')?>" />
					</div>
				</div>
			</div>
		</div>
		<?php if($login_fabrica == 42){ ?>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="tipo_data" id="optionsRadios1" value="data_notificacao" <?if($tipo_data=="data_notificacao") echo "checked";?>>
				         Data Notificação
				    </label>
				</div>
			</div>
		</div>
		<?php }else{  ?>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="tipo_data" id="optionsRadios1" value="data_processo" <?if($tipo_data=="data_processo") echo "checked";?>>
				         Data Processo
				    </label>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="tipo_data" id="optionsRadios2" value="data_audiencia" <?if($tipo_data=="data_audiencia") echo "checked";?>>
				        Data Audiência
				    </label>
				</div>
			</div>
		</div>
   		<div class="span2"></div>
   	</div>
   	<div class='row-fluid'>
   		<div class='span2'></div>
   		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<strong>Tipo do Orgão:</strong>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="orgao" id="optionsOrgao2" value="orgao_consumidor" <?if($orgao=="consumidor") echo "checked";?>>
				        Consumidor
				    </label>
				</div>
			</div>
		</div>
   		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="orgao" id="optionsOrgao1" value="orgao_juizado" <?if($orgao=="orgao_juizado") echo "checked";?>>
				         Juizado
				    </label>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="orgao" id="optionsOrgao2" value="orgao_procon" <?if($orgao=="orgao_procon") echo "checked";?>>
				        Procon
				    </label>
				</div>
			</div>
		</div>
   		<div class="span2"></div>
   	</div>
   	<div class='row-fluid'>
   		<div class='span2'></div>
   		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<strong>Status do Processo:</strong>
				</div>
			</div>
		</div>
   		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="status_p" id="staus_p1" value="status_aberto" <?if($status_processo=="status_aberto") echo "checked";?>>
				         <?php echo ($login_fabrica == 42)? "Em andamento" : "Aberto"  ?>
				    </label>
				</div>
			</div>
		</div>
		<div class='span2'>
			<div class='control-group' ><br>
				<div class="controls controls-row">
					<label class="radio">
				        <input type="radio" name="status_p" id="staus_p1" value="status_fechado" <?if($status_processo=="status_fechado") echo "checked";?>>
				        <?php echo ($login_fabrica == 42)? "Concluído" : "Finalizado"  ?>
				    </label>
				</div>
			</div>
		</div>
		<div class="span2">
			<div class="control-group">
				<label class="control-label" for="estado">Estado</label>
				<div class="controls controls-row">
					<div class="span12">
							<select id="estado" name="estado" class="span12">
								<option value="" >Selecione</option>
								<?php
								#O $array_estados está no arquivo funcoes.php
								foreach ($array_estados as $sigla => $nome_estado) {
									$selected = ($sigla == getValue('estado')) ? "selected" : "";

									echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
								}
								?>
							</select>
					</div>
				</div>
			</div>
		</div>
   		<div class="span2"></div>
   	</div>
	<br />
		<p class="tac">
			<input type="submit" class="btn" name="pesquisar" value="Pesquisar" />
		</p>
		<br />
</FORM>
<br />

<!-- Tabela -->
<?
//Lista a Consulta dos Processos
if (isset($res_pesq)) {
	if(pg_num_rows($res_pesq) > 0){
		if($login_fabrica <> 81){
			$class='table-hover';
		}
?>
<?php if ($login_fabrica == 183){ echo "</div><div class='container-fluid'>";} ?>
	<form name="frm_tab" method="GET" class="form-search form-inline <?=$class?> " enctype="multipart/form-data" >
		<table id="frm_tab" class='table table-striped table-bordered table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<td>Nº do Processo</td>
					<td>Status do Processo</td>
					<td><?=($login_fabrica == 183) ? "Data Cadastro" : "Data Processo"?></td>
					<td>Consumidor</td>
					<td>CPF / CNPJ</td>
					<?php if ($login_fabrica == 183){ ?>
					<td>Data Notificação</td>
					<?php } ?>
					<?php if($login_fabrica != 42){?>
						<td>Data Audiência 1</td>
						<td>Data Audiência 2</td>
					<?php } ?>
					<?php if ($login_fabrica == 183){ ?>
						<td>Data Solução</td>
						<td>Periodo em Aberto</td>
					<?php } ?>
					<td>Atendimentos</td>
					<td>Ordens de Serviços</td>
					<?php if($login_fabrica == 81){ ?>
					<td>Observações Audiência</td>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?
				for ($i = 0 ; $i < pg_num_rows($res_pesq) ; $i++) {

					$n_processo_t		= pg_fetch_result($res_pesq, $i, 'processo');
					$processo_id		= pg_fetch_result($res_pesq, $i, 'processo_id');
					$dt_processo_t 		= mostra_data(substr(pg_fetch_result($res_pesq, $i, 'data_input'),0 ,10));
					$consumidor_t		= pg_fetch_result($res_pesq, $i, 'consumidor_nome');
					$dt_audiencia1_t	= pg_fetch_result($res_pesq, $i, 'data_audiencia1');
					$dt_audiencia2_t	= pg_fetch_result($res_pesq, $i, 'data_audiencia2');
					$atendimentos_t		= pg_fetch_result($res_pesq, $i, 'hd_chamado');
					$os_t		    	= pg_fetch_result($res_pesq, $i, 'os');
					$cons_cpf_cnpj		= pg_fetch_result($res_pesq, $i,'consumidor_cpf');
					$status_processo_descricao		= pg_fetch_result($res_pesq, $i,'status_processo_descricao');
					$observacao_audiencia = pg_fetch_result($res_pesq, $i, 'observacao_audiencia'); //HD-3251974
					$data_solucao       = pg_fetch_result($res_pesq, $i, 'data_solucao');
					if (strlen($cons_cpf_cnpj)>11) {
						$cons_cpf_cnpj = mascara($cons_cpf_cnpj,'##.###.###/####-##');
					}else{
						$cons_cpf_cnpj = mascara($cons_cpf_cnpj,'###.###.###-##');
					}

					if ($login_fabrica == 183){
						$sql_processo_item = "
							SELECT 
								(
									SELECT data_notificacao 
									FROM tbl_processo_item 
									WHERE processo = $processo_id 
									ORDER BY tbl_processo_item.data_input ASC LIMIT 1
								) AS data_inicio_processo,
								(SELECT COUNT(*) FROM tbl_processo_item WHERE processo = $processo_id) AS qtde_estapas,
								TO_CHAR(data_notificacao, 'DD/MM/YYYY') AS data_notificacao,
								TO_CHAR(data_audiencia1, 'DD/MM/YYYY') AS data_audiencia1,
								TO_CHAR(data_audiencia2, 'DD/MM/YYYY') AS data_audiencia2
							FROM tbl_processo_item
							WHERE tbl_processo_item.processo = $processo_id
							ORDER BY tbl_processo_item.data_input DESC LIMIT 1";
						$res_processo_item = pg_query($con, $sql_processo_item);

						if (pg_num_rows($res_processo_item) > 0){
							$data_inicio_processo = pg_fetch_result($res_processo_item, 0, "data_inicio_processo");
							$qtde_estapas     	  = pg_fetch_result($res_processo_item, 0, "qtde_estapas");
							$data_notificacao 	  = pg_fetch_result($res_processo_item, 0, "data_notificacao");
							$dt_audiencia1_t  	  = pg_fetch_result($res_processo_item, 0, "data_audiencia1");
							$dt_audiencia2_t  	  = pg_fetch_result($res_processo_item, 0, "data_audiencia2");
							
							
							$data1 = new DateTime(formata_data($data_solucao));
							$data2 = new DateTime($data_inicio_processo );
							$intervalo = $data1->diff( $data2 );
							$msg_intervalo = "{$intervalo->y} anos, {$intervalo->m} meses e {$intervalo->d} dias"; 
						}
					}

					?>
					<tr>
						<td class='tac'>
							<a href='cadastro_processos.php?num_processo=<?=$processo_id?>' target='_blank'>
								<?echo $n_processo_t?>
							</a>
						</td>
						<td class='tac'><?echo $status_processo_descricao?></td>
						<td class='tac'><?echo $dt_processo_t?></td>
						<td><?echo $consumidor_t?></td>
						<td class='tac'><?echo $cons_cpf_cnpj?></td>
						<?php if ($login_fabrica == 183){ ?>
						<td><?=$data_notificacao?></td>
						<?php } ?>
						<?php if($login_fabrica != 42){?>
							<td class='tac'><?echo $dt_audiencia1_t?></td>
							<td class='tac'><?echo $dt_audiencia2_t?></td>
						<?php } ?>

						<?php if ($login_fabrica == 183){ ?>
							<td class='tac'><?=$data_solucao?></td>
							<td><?=$msg_intervalo?></td>
						<?php } ?>

						<td class='tac'>
							<a href='callcenter_interativo_new.php?callcenter=<?=$atendimentos_t?>' target='_blank'>
							<?=$atendimentos_t?>
							</a>
						</td>
						<td class='tac'>
							<a href='os_press.php?os=<?=$os_t?>' target='_blank'>
								<?echo $os_t?>
							</a>
						</td>
						<?php if($login_fabrica == 81){ //HD-3251974 ?>
						<td><button type='button' class='btn btn-small' onclick="verObservacoes(<?=$n_processo_t?>);" >Observações</button></td>
						<?php } ?>
					</tr>
					<?php if($login_fabrica == 81){ ?>
						<tr id='<?=$n_processo_t?>' style='display: none;' >
							<td colspan='9'>
								<table class='table table-striped table-bordered table-fixed'>
									<thead>
										<tr class='titulo_coluna'>
											<th>Observações Audiência</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>
												<textarea readonly  class="span12" style="height: 100px; width: 800px;"><?=$observacao_audiencia?></textarea>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
				<?
					}
				}
				?>
			</tbody>
		</table>
	</form>
<?php if ($login_fabrica == 183){ echo "</div>";} ?>
	<br />
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Excel</span>
		</div>
	<?
	}else{?>
	<div class="container">
		<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
	<?
	}
}
?>
<script>
    $(function() {
        var table = new Object();
        table['table'] = '#frm_tab';
        table['type'] = 'full';
        $.dataTableLoad(table);
    });
</script>

<?php
include "rodape.php";
?>
