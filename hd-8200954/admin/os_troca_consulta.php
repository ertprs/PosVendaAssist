<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,auditoria";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = trim(strtolower($_POST['btn_acao']));
if (strlen($_GET['btn_acao']) > 0) $btn_acao = trim(strtolower($_GET['btn_acao']));

if ($btn_acao == 'excluir') {

	if (strlen($_GET['os']) > 0) $os = trim($_GET['os']);

	if (strlen($os) > 0) {
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		//echo $sql;
	}

	header("Location: os_troca_parametros.php");
	exit;
}

$msg_erro           = '';
$vet_periodo_um_ano = array(1);

// recebe as variaveis
if ($_REQUEST['chk_opt1']) $chk1 = $_REQUEST['chk_opt1'];
if ($_REQUEST['chk_opt2']) $chk2 = $_REQUEST['chk_opt2'];
if ($_REQUEST['chk_opt3']) $chk3 = $_REQUEST['chk_opt3'];
if ($_REQUEST['chk_opt4']) $chk4 = $_REQUEST['chk_opt4'];
if ($_REQUEST['chk_opt5']) $chk5 = $_REQUEST['chk_opt5'];
if ($_REQUEST['chk_opt6']) $chk6 = $_REQUEST['chk_opt6'];


$data_inicial_01  = trim($_REQUEST["data_inicial"]);
$data_final_01    = trim($_REQUEST["data_final"]);
$codigo_posto     = trim($_REQUEST['codigo_posto']);
$admin_autoriza   = trim($_REQUEST['admin_autoriza']);
$admin            = trim($_REQUEST["admin"]);
$causa_troca      = trim($_REQUEST["causa_troca"]);
$tipo_atendimento = trim($_REQUEST["tipo_atendimento"]);
$produto 		  = $_REQUEST['produto'];

if (strlen($tipo_atendimento) == 0 AND strlen($causa_troca) == 0 AND strlen($admin) == 0 and strlen($admin_autoriza) == 0 AND empty($data_inicial_01) AND empty($data_final_01) ){
	$msg_erro = "Especifique os parametros para pesquisa";
}

if (empty($data_inicial_01)) $msg_erro = "Data inválida";
if (empty($data_final_01))   $msg_erro = "Data inválida";

if (!empty($data_inicial_01) AND !empty($data_final_01) and empty($msg_erro)) {

	if ($data_inicial_01 == 'dd/mm/aaaa') $msg_erro = "Data inválida";
	if ($data_final_01 == 'dd/mm/aaaa')   $msg_erro = "Data inválida";

    if (strlen($msg_erro) == 0) {

        list($di, $mi, $yi) = explode("/", $data_inicial_01);
        if (!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";

    }

    if (strlen($msg_erro) == 0) {

        list($df, $mf, $yf) = explode("/", $data_final_01);
        if (!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";

    }

    if (strlen($msg_erro) == 0) {

        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final	  = "$yf-$mf-$df";

        if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
            $msg_erro = "Data Inválida.";
        }

		//25/09/2010 MLG - HD 303907 - Limitar o relatório para um intervalo máximo de 1 mês
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month') && !in_array($login_fabrica, $vet_periodo_um_ano)) {//HD 704407
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
		}

		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 year') && in_array($login_fabrica, $vet_periodo_um_ano)) {//HD 704407
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 ano.';
		}

		unset($di, $mi, $yi, $df, $mf, $yf); // Limpando...

	}
}

$layout_menu = "auditoria";
$title = "RELAÇAO DE ORDENS DE SERVIÇO TROCA";

if(empty($msg_erro)){
	// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
	$sql = "SELECT  DISTINCT
				tbl_os_revenda.os_revenda,
				tbl_os_revenda.nota_fiscal, 
				tbl_os_revenda.sua_os,
				SPLIT_PART(tbl_os_revenda.sua_os,'-',1) AS sua_os_ordenacao,
				tbl_os_revenda.tipo_os,
				tbl_tipo_atendimento.descricao	AS tipo_atendimento,
				tbl_os_revenda.explodida,
				TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY')	AS abertura,
				TO_CHAR(tbl_os_revenda.digitacao,'DD/MM/YYYY')		AS digitacao,
				CURRENT_DATE						AS data_fechamento,
				FALSE							AS excluida,
				NULL							AS serie,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome						AS nome_posto,
				NULL							AS produto_referencia, ";
				if($login_fabrica==1){
					$sql.= " NULL		AS referencia_interna, ";
					$sql.= " tbl_os_revenda_item.rg_produto as status_peca_os,  ";
					$sql.= " tbl_os_revenda_item.obs_causa , ";
					$sql.= " tbl_os_revenda_item.causa_troca as causa_troca_id,  ";
				}
				$sql.= " NULL							AS produto_descricao,
				tbl_os_revenda.tipo_os_cortesia	,
				tbl_admin.login						AS admin_nome,
				NULL							AS consumidor_nome,
				NULL							AS consumidor_revenda,
				autoriza.login						AS autoriza_nome,
				tbl_causa_troca.descricao				AS causa_troca,
				'R'                      	                        AS origem_os
			FROM tbl_os_revenda
			JOIN tbl_os_revenda_item	ON tbl_os_revenda_item.os_revenda	= tbl_os_revenda.os_revenda
			JOIN tbl_causa_troca		ON tbl_causa_troca.causa_troca		= tbl_os_revenda.causa_troca
			JOIN tbl_posto			ON tbl_posto.posto			= tbl_os_revenda.posto
			JOIN tbl_posto_fabrica		ON tbl_posto_fabrica.posto		= tbl_posto.posto
									   AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_revenda.tipo_atendimento
			JOIN tbl_admin			ON tbl_os_revenda.admin			= tbl_admin.admin
			LEFT JOIN tbl_admin autoriza ON tbl_os_revenda.admin_autoriza = autoriza.admin
			WHERE tbl_os_revenda.fabrica = $login_fabrica
			AND	  tbl_os_revenda.tipo_atendimento in (17,18,35) 
			AND   tbl_os_revenda.posto <> 6359";//HD 704407 - INTERACAO 23

	$monta_sql = "";
	$msg = 'OS lançadas';

	if (!empty($data_inicial_01) AND !empty($data_final_01) and empty($msg_erro)) {

		//MLG 25/09/2010 - Já faz a conferência de datas acima!
		$monta_sql .= "\nAND tbl_os_revenda.digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'::date + INTERVAL '1 day - 1 sec' ";
		$dt = 1;
		$msg .= " entre os dias $data_inicial_01 e $data_final_01";

	}

	if (strlen($codigo_posto) > 0) {// codigo do posto
		$monta_sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$dt = 1;
		$msg .= " pelo posto $nome_posto";
	}
	
	if (!empty($produto)) {
		$monta_sql .= "\nAND tbl_os_revenda_item.produto = $produto ";
		$dt = 1;
	}

	if (strlen($tipo_atendimento) > 0) {
		$monta_sql .= "\nAND tbl_os_revenda.tipo_atendimento= $tipo_atendimento ";
		$dt = 1;
	}

	if (strlen($admin_autoriza) > 0) {		
		$monta_sql .= "\nAND tbl_os_revenda.admin_autoriza = $admin_autoriza ";
		$dt = 1;
	}

	if (strlen($causa_troca) > 0) {
		$monta_sql .= "\nAND tbl_os_revenda.causa_troca = $causa_troca ";
		$dt = 1;
	}

	if(strlen($admin) > 0){
		$monta_sql .= "\nAND tbl_os_revenda.admin = $admin ";
		$dt = 1;
	}

	$sql .= $monta_sql;

					$sql .= "
					UNION
						SELECT  tbl_os.os					AS os_revenda,
							tbl_os.nota_fiscal, 
							tbl_os.sua_os,
							SPLIT_PART(tbl_os.sua_os,'-',1) AS sua_os_ordenacao,
							tbl_os.tipo_os,
							tbl_tipo_atendimento.descricao	 		AS tipo_atendimento,
							NULL						AS explodida,
							TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')	AS abertura,
							TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')	AS digitacao,
							tbl_os.data_fechamento,
							tbl_os.excluida,
							tbl_os.serie,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome as posto,
							tbl_produto.referencia				AS produto_referencia, ";

							if($login_fabrica==1){
								$sql.= " tbl_produto.referencia_fabrica		AS referencia_interna, ";
								$sql.= " tbl_os.prateleira_box as status_peca_os,  ";
								$sql.= " tbl_os_troca.obs_causa,   ";
								$sql.= " tbl_os_troca.causa_troca as causa_troca_id,  ";
							}

							$sql .= " tbl_produto.descricao				AS produto_descricao,
							tbl_os.tipo_os_cortesia,
							tbl_admin.login					AS admin_nome,
							tbl_os.consumidor_nome,
							tbl_os.consumidor_revenda,
							autoriza.login					AS autoriza_nome,
							tbl_causa_troca.descricao			AS causa_troca,
							'O'						AS origem_os
						FROM tbl_os
						JOIN tbl_os_troca		ON tbl_os_troca.os      = tbl_os.os
						JOIN tbl_causa_troca	ON tbl_causa_troca.causa_troca  = tbl_os_troca.causa_troca
						JOIN tbl_produto		ON tbl_produto.produto	= tbl_os.produto
						JOIN tbl_posto			ON tbl_posto.posto	= tbl_os.posto
						JOIN tbl_tipo_atendimento USING(tipo_atendimento)
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto.posto AND tbl_posto_fabrica.fabrica=$login_fabrica
						JOIN tbl_admin			ON tbl_os.admin		= tbl_admin.admin
						LEFT JOIN tbl_admin autoriza ON tbl_os_troca.admin_autoriza = autoriza.admin
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.tipo_atendimento in (17,18,35)
						AND   tbl_os.posto <> 6359";//HD 704407 - INTERACAO 23

	if (!empty($data_inicial_01) AND !empty($data_final_01) and empty($msg_erro)) {
		$monta_sql = "\nAND tbl_os.data_digitacao BETWEEN '$aux_data_inicial' AND '$aux_data_final'::date + INTERVAL '1 day - 1 sec' ";
	}

	if (strlen($codigo_posto) > 0) {// codigo do posto
		$monta_sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		$dt = 1;
	}

	if (!empty($produto)) {
		$monta_sql .= " AND tbl_os.produto = $produto ";
		$dt = 1;
	}

	if (strlen($tipo_atendimento) > 0) {
		$monta_sql .= " AND tbl_os.tipo_atendimento = $tipo_atendimento ";
		$dt = 1;
	}

	if (strlen($admin_autoriza) > 0) {
		$monta_sql .= " AND tbl_os_troca.admin_autoriza = $admin_autoriza ";
		$dt = 1;
	}

	if (strlen($causa_troca) > 0) {
		$monta_sql .= " AND tbl_os_troca.causa_troca = $causa_troca ";
		$dt = 1;
	}

	if (strlen($admin) > 0) {
		$monta_sql .= " AND tbl_os.admin = $admin ";
		$dt = 1;
	}

	$sql .= "$monta_sql ORDER BY causa_troca,sua_os_ordenacao,os_revenda ASC";

	if(!isset($_POST['gerar_excel'])){
		$sql .= " limit 500 ";
	}

	$sql_principal = $sql ;
	$res_principal = pg_query($con, $sql_principal);

	$qtde_principal = pg_num_rows($res_principal);
}

if (strlen($msg_erro) == 0 and $_POST['gerar_excel']) {
	if (is_resource($res_principal)) $tot = pg_num_rows($res_principal);
	if ($tot > 0) {	
		$filename = "xls/relatorio_os_troca_consulta_$login_fabrica-$login_admin.xls";
		$arquivo = fopen($filename,"w");
		$url	  = 'http://posvenda.telecontrol.com.br/assist/admin';
		$os_width = ($login_fabrica == 1) ? '110' : '90';
		
		$thead	  = "
		<table style='width:100%;border:0;border-spacing:1px;border-collapse:separate;margin:auto;table-layout:fixed;' cellpadding='1' class='tabela'>
			<caption class='titulo_coluna' style='font-size:14px;margin:auto 1px'>$msg</caption>
			<thead>
				<tr class='titulo_coluna'>
					<th width='$os_width'>OS</th>
					<th width='075'>Nota Fiscal</th>
					<th width='075'>Abertura</th>
					<th width='090'>Fechamento</th>
					<th>Admin</th>
					<th>Admin Autorizada</th>
					<th>Consumidor</th>
					<th>Posto</th>
					<th>Produto Origem telecontrol</th>";
					if($login_fabrica==1){
						$thead .= "<th>Produto Origem Interna</th>";
						$thead .= "<th>Produto Origem Rev.Produto</th>";
					}
						
					$thead .= "<th width='120' nowrap>Tipo Atendimento</th>
								<th width='150' nowrap>Motivo</th>

								<th width='150' nowrap>Pedido</th>
								<th width='150' nowrap>Nº Processo</th>
								<th width='150' nowrap>Justificativa</th>
								<th width='150' nowrap>Nº B.O</th>
								<th width='150' nowrap>Status Peça na OS</th>
								<th width='150' nowrap>Peça</th>
								<th width='150' nowrap>Estado-Cidade</th>
								<th width='150' nowrap>Mídias</th>
								<th width='150' nowrap>Valor das Peças</th>
								<th width='150' nowrap>O.Ss</th>
								

					";

		// O arquivo não vai ter coluna 'ações'. $tabela vai pro arquivo
	

	$tabela = $thead . "
		</tr>
	</thead>
	<tbody>\n";

		$thead.= "
		</tr>
	</thead>";

		$tbody = "
	<tbody>\n";

		for ($i = 0; $i < $qtde_principal; $i++) {

			$os                 = trim(pg_fetch_result($res_principal, $i, 'os_revenda'));
			$nota_fiscal        = trim(pg_fetch_result($res_principal, $i, 'nota_fiscal'));
			$sua_os_revenda     = trim(pg_fetch_result($res_principal, $i, 'sua_os'));
			$data               = trim(pg_fetch_result($res_principal, $i, 'digitacao'));
			$abertura           = trim(pg_fetch_result($res_principal, $i, 'abertura'));
			$sua_os             = trim(pg_fetch_result($res_principal, $i, 'sua_os'));
			$serie              = trim(pg_fetch_result($res_principal, $i, 'serie'));
			$consumidor_nome    = trim(pg_fetch_result($res_principal, $i, 'consumidor_nome'));
			$posto_nome         = trim(pg_fetch_result($res_principal, $i, 'nome_posto'));
			$codigo_posto       = trim(pg_fetch_result($res_principal, $i, 'codigo_posto'));
			$produto_nome       = trim(pg_fetch_result($res_principal, $i, 'produto_descricao'));
			$produto_referencia = trim(pg_fetch_result($res_principal, $i, 'produto_referencia'));
			$data_fechamento    = trim(pg_fetch_result($res_principal, $i, 'data_fechamento'));
			$excluida           = trim(pg_fetch_result($res_principal, $i, 'excluida'));
			$tipo_os_cortesia   = trim(pg_fetch_result($res_principal, $i, 'tipo_os_cortesia'));
			$tipo_os            = trim(pg_fetch_result($res_principal, $i, 'tipo_os'));
			$tipo_atendimento   = trim(pg_fetch_result($res_principal, $i, 'tipo_atendimento'));
			$admin_nome         = trim(pg_fetch_result($res_principal, $i, 'admin_nome'));
			$autoriza_nome      = trim(pg_fetch_result($res_principal, $i, 'autoriza_nome'));
			$causa_troca        = trim(pg_fetch_result($res_principal, $i, 'causa_troca'));
			$explodida          = trim(pg_fetch_result($res_principal, $i, 'explodida'));
			$consumidor_revenda = trim(pg_fetch_result($res_principal, $i, 'consumidor_revenda'));
			$origem_os			= trim(pg_fetch_result($res_principal, $i, 'origem_os'));
			$status_peca_os 	= trim(pg_fetch_result($res_principal, $i, 'status_peca_os'));
			$obs_causa 			= trim(pg_fetch_result($res_principal, $i, 'obs_causa'));
			$obs_causa 			= str_replace("<br/>", "\n", $obs_causa);
			$causa_troca_id 	= pg_fetch_result($res_principal, $i, 'causa_troca_id');

			$justificativa = "";
			$n_processo = "";
			$nbo = "";
			$numos_vicio = "";
			$estado_cidade="";
			$midia = "";

			$sqlCE = "SELECT campos_adicionais FROM tbl_os_campo_extra where os = $os";
			$resCE = pg_query($con, $sqlCE);
			if(pg_num_rows($resCE)>0){
				$campos_adicionais = json_decode(pg_fetch_result($resCE, 0, 'campos_adicionais'),true);
			}else{
				$campos_adicionais = null;
			}

			if($causa_troca_id == 130){
				$estado_cidade = $obs_causa;
				$obs_causa = "";
			}

			if($causa_troca_id == 274 OR $causa_troca_id == 128){
				$midia = $status_peca_os;
				$justificativa = $obs_causa;
				$status_peca_os = "";
				$obs_causa = "";
			}

			if($causa_troca_id == 127){
				$obs_causa = explode(":", $obs_causa);
				$n_processo = $obs_causa[1];

				$obs_causa = "";
			}

			if($causa_troca_id == 126){
				$numos_vicio = $obs_causa;
				$obs_causa = "";
			}

			if($causa_troca_id == 125){
				$obs_causa_arr  = explode("nº", $obs_causa);
				$pedido =  substr(trim($obs_causa_arr[1]),0,5);

				$findme   = 'Motivo:';
				$pos = strpos($obs_causa, $findme);

				$justificativa = substr($obs_causa, $pos);
				$nbo = substr($obs_causa, 0, $pos);

				$findBO   = 'B.O.';
				$posBO = strpos($nbo, $findBO);
				$nbo = substr($nbo, $posBO);

				$obs_causa = "";

			}

			if($causa_troca_id == 124 OR $causa_troca_id == 380){
				$obs_causa = str_replace("Posição", " | <b>Posição</b> ", $obs_causa);
			}

			if($login_fabrica==1){
				$referenciaInterna = trim(pg_fetch_result($res_principal, $i, 'referencia_interna'));;
			}			
			$data_fecham = "";

			if ($data_fechamento != "") {
				$data_fecham = date('d/m/Y', strtotime($data_fechamento));
			}

			$btn = ($i % 2 == 0) ? 'amarelo' : 'azul';
			$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

			if ($excluida == "t") $cor = "#FFE1E1";

			if (strlen(trim($sua_os)) == 0) $sua_os = $os;

			if ($login_fabrica == 1) {
				$sua_os = $codigo_posto . $sua_os;
				$sua_os_revenda = $codigo_posto . $sua_os_revenda;

			}

			if ($origem_os == 'O') {

				$link_sua_os = "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>";
				$xls_sua_os = "<a href='os_press.php?os=$os' target='_blank'>";
				$xls_sua_os.= (is_numeric($sua_os)) ? "=TEXTO($sua_os;\"00000\")" : $sua_os;
				$xls_sua_os.= '</a>';

			}  else {

				$link_sua_os = "<a href='$url/os_revenda_finalizada.php?os_revenda=$os' target='_blank'>$sua_os_revenda</a>";
				$xls_sua_os = "<a href='$url/os_revenda_finalizada.php?os_revenda=$os' target='_blank'>";
				$xls_sua_os.= (is_numeric($sua_os_revenda)) ? "=TEXTO($sua_os_revenda;\"00000\")" : $sua_os_revenda;
				$xls_sua_os.= '</a>';

				$data_fecham = '';//HD 704407 - INTERACAO 58

			}

			$tablerow = "
			<tr style='background-color: $cor;'>
				<td nowrap align='right'>$xls_sua_os</td>
				<td align='center'>$nota_fiscal</td>
				<td align='center'>$abertura</td>
				<td align='center'>$data_fecham</td>
				<td nowrap>$admin_nome</td>
				<td nowrap>$autoriza_nome</td>
				<td nowrap>$consumidor_nome</td>
				<td nowrap>$codigo_posto - $posto_nome</td>
				<td nowrap>$produto_referencia - $produto_nome</td>";
				if($login_fabrica == 1){
					$tablerow .="<td nowrap>$referenciaInterna</td>";
					$tablerow .="<td nowrap>". $campos_adicionais['produto_origem'] ."</td>";
				}
			$tablerow .= "<td align='center' nowrap>$tipo_atendimento</td>
				<td align='center' nowrap>$causa_troca</td>";

			$tablerow .= "<td>$pedido</td>";
			$tablerow .= "<td>$n_processo</td>";
			$tablerow .= "<td>$justificativa</td>";
			$tablerow .= "<td>$nbo</td>";
			$tablerow .= "<td>$status_peca_os</td>";
			$tablerow .= "<td>$obs_causa</td>";
			$tablerow .= "<td>$estado_cidade</td>";
			$tablerow .= "<td>$midia</td>";
			$tablerow .= "<td>$valor_peca</td>";
			$tablerow .= "<td>".$numos_vicio."</td>";

			$tablerow .= "</tr>";

			$row = "
			<tr style='background-color: $cor;'>
				<td nowrap>$link_sua_os</td>
				<td align='center'>$abertura</td>
				<td align='center'>$data_fecham</td>
				<td nowrap title='$admin_nome'>".substr($admin_nome,0,17)."</td>
				<td nowrap title='$autoriza_nome'>".substr($autoriza_nome,0,17)."</td>
				<td nowrap class='nowraptext' title='$consumidor_nome'>".$consumidor_nome."</td>
				<td nowrap class='nowraptext' title='$codigo_posto - $posto_nome'>$posto_nome</td>
				<td nowrap class='nowraptext' title='$produto_referencia - $produto_nome'>".substr($produto_referencia,0,17)."</td>";
				if($login_fabrica == 1){
					$row .="<td nowrap>$referenciaInterna</td>";
				}
				$row .="<td nowrap class='nowraptext' align='center'>$tipo_atendimento</td>
				<td nowrap class='nowraptext' align='center' nowrap colspan='2'>$causa_troca</td>";
		
			$row.= ' </tr>';
			$tabela .= $tablerow;	

			//$tabela .= $row; // Para mostrar na tela as ações
		}
		
		

		$tabela .= "</tbody></table></body></html>";

		fwrite($arquivo,$tabela);
		fclose($arquivo);

		echo $filename;
		exit;
		?>

		

<?php } ?>

	<br />
	<br />
	<?php

}else{ 

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");


?>
<SCRIPT LANGUAGE="JavaScript">

		function Excluir(os) {

			if (confirm('Deseja realmente excluir esse registro?') == true) {
				window.location = '<? echo $PHP_SELF; ?>?btn_acao=excluir&os='+os;
			}

		}

		function bindEvent(element, evt, listener) {

			if (element.addEventListener) {
				element.addEventListener(evt, listener, false);
				return true;
			} else {

				if (element.attachEvent) {
					return element.attachEvent('on' + evt, listener);
				} else {
					element['on' + evt] = false;
					return false;
				}

			}

		}

	</SCRIPT>

	<style type="text/css">

		.menu_top {
			text-align: center;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: x-small;
			font-weight: bold;
			border: 1px solid;
			color:#ffffff;
			background-color: #596D9B
		}

		.table_line {
			text-align: left;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: normal;
			border: 0px solid;
			background-color: #D9E2EF
		}

		.table_line2 {
			text-align: left;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 10px;
			font-weight: normal;
		}

		a.linkTitulo {
			font-family: Arial;
			font-size: 11px;
			font-weight: bold;
			border: 0px solid;
			color: #ffffff
		}
		a.botao {
			background-color:ButtonFace;
			color:ButtonText;
			font-size: 11px;
			padding: 2px 5px;
			border-width: 1px;
			border-radius: 3px;
			-o-border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			border-style: outset;
			border-bottom-color: ButtonHighlight;
			border-right-color: ButtonHighlight;
			border-top-color: ButtonShadow;
			border-left-color: ButtonShadow;
		}
		a.botao:hover {
			background-color:ButtonFace;
			color:ButtonText;
			border-style: inset;
			border-bottom-color:ButtonShadow;
			border-right-color:	ButtonShadow;
			border-top-color:	ButtonHighlight;
			border-left-color:	ButtonHighlight;
		}
		table.tabela caption {
			height: 24px;
			vertical-align: middle;
			padding-top: 4px;
			-moz-border-radius: 4px 4px 0 0;
			border-top-left-radius: 4px;
			border-top-right-radius: 4px;
			-webkit-border-top-left-radius: 4px;
			-webkit-border-top-right-radius: 4px;
		}
		table.tabela tr:last-child td {
			padding-left: 1em;
			-moz-border-radius: 0 4px;
			border-bottom-left-radius: 4px;
			border-bottom-right-radius: 4px;
			-webkit-border-bottom-left-radius: 4px;
			-webkit-border-bottom-right-radius: 4px;
		}
		table.tabela tr td.nowraptext {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			_zoom: 1;
			*zoom: 1;
		}
		table.tabela tr td:nth-child(6),table.tabela tr td:nth-child(7) {
			white-space: nowrap;
			overflow-x: hidden;
			text-overflow: ellipsis;
			-o-text-overflow: ellipsis;
		}

		table.tabela tr td {
			font-family: verdana;
			font-size: 10px;
			border:1px solid #596d9b;
		}

		.titulo_coluna {
			background-color:#596d9b;
			font: bold 11px "Arial";
			color:#FFFFFF;
			text-align:center;
		}

		.msg_erro {
			background-color:#FF0000;
			font: bold 16px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
	</style>
</div>

<?php if(strlen($msg_erro)>0){ ?>
<div class="container">
	<div class="alert alert-error">
        <h4><?=$msg_erro?></h4>
    </div>
    <div style="text-align: center">
    	<a href="os_troca_parametros.php" class="btn ">Pesquisar</a>
    </div>
</div>
<?php } 

	

if($qtde_principal>0 and empty($msg_erro)){

?>

<div class="container-fluid">
	<table class='table table-striped table-bordered table-hover table-fixed' > 
		<thead>
		<tr class='titulo_coluna'>
			<th width='$os_width'>OS</th>
			<th width='075'>Nota Fiscal</th>
			<th width='075'>Abertura</th>
			<th width='090'>Fechamento</th>
			<th>Admin</th>
			<th>Admin Autorizada</th>
			<th>Consumidor</th>
			<th>Posto</th>
			<th>Produto Origem Telecontrol</th>					
			<th>Produto Origem Interna</th>						
			<th>Produto Origem Rev.Produto</th>		
			<th width='120' nowrap>Tipo Atendimento</th>
			<th width='150' nowrap>Motivo</th>
		</tr>
		</thead>	
		<tbody>
			<?php
			$os_width = ($login_fabrica == 1) ? '110' : '90';
			

			for ($i = 0; $i < $qtde_principal; $i++) {
				$os                 = trim(pg_fetch_result($res_principal, $i, 'os_revenda'));
				$nota_fiscal        = trim(pg_fetch_result($res_principal, $i, 'nota_fiscal'));
				$sua_os_revenda     = trim(pg_fetch_result($res_principal, $i, 'sua_os'));
				$data               = trim(pg_fetch_result($res_principal, $i, 'digitacao'));
				$abertura           = trim(pg_fetch_result($res_principal, $i, 'abertura'));
				$sua_os             = trim(pg_fetch_result($res_principal, $i, 'sua_os'));
				$serie              = trim(pg_fetch_result($res_principal, $i, 'serie'));
				$consumidor_nome    = trim(pg_fetch_result($res_principal, $i, 'consumidor_nome'));
				$posto_nome         = trim(pg_fetch_result($res_principal, $i, 'nome_posto'));
				$codigo_posto       = trim(pg_fetch_result($res_principal, $i, 'codigo_posto'));
				$produto_nome       = trim(pg_fetch_result($res_principal, $i, 'produto_descricao'));
				$produto_referencia = trim(pg_fetch_result($res_principal, $i, 'produto_referencia'));
				$data_fechamento    = trim(pg_fetch_result($res_principal, $i, 'data_fechamento'));
				$excluida           = trim(pg_fetch_result($res_principal, $i, 'excluida'));
				$tipo_os_cortesia   = trim(pg_fetch_result($res_principal, $i, 'tipo_os_cortesia'));
				$tipo_os            = trim(pg_fetch_result($res_principal, $i, 'tipo_os'));
				$tipo_atendimento   = trim(pg_fetch_result($res_principal, $i, 'tipo_atendimento'));
				$admin_nome         = trim(pg_fetch_result($res_principal, $i, 'admin_nome'));
				$autoriza_nome      = trim(pg_fetch_result($res_principal, $i, 'autoriza_nome'));
				$causa_troca        = trim(pg_fetch_result($res_principal, $i, 'causa_troca'));
				$explodida          = trim(pg_fetch_result($res_principal, $i, 'explodida'));
				$consumidor_revenda = trim(pg_fetch_result($res_principal, $i, 'consumidor_revenda'));
				$origem_os			= trim(pg_fetch_result($res_principal, $i, 'origem_os'));
				if($login_fabrica==1){
					$referenciaInterna = trim(pg_fetch_result($res_principal, $i, 'referencia_interna'));;
				}			
				$data_fecham = "";

				$sqlCE = "SELECT campos_adicionais FROM tbl_os_campo_extra where os = $os";
				$resCE = pg_query($con, $sqlCE);
				if(pg_num_rows($resCE)>0){
					$campos_adicionais = json_decode(pg_fetch_result($resCE, 0, 'campos_adicionais'),true);
				}else{
					$campos_adicionais = null;
				}

				if ($data_fechamento != "") {
					$data_fecham = date('d/m/Y', strtotime($data_fechamento));
				}

				$btn = ($i % 2 == 0) ? 'amarelo' : 'azul';
				$cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';

				if ($excluida == "t") $cor = "#FFE1E1";

				if (strlen(trim($sua_os)) == 0) $sua_os = $os;

				if ($login_fabrica == 1) {
					$sua_os = $codigo_posto . $sua_os;
					$sua_os_revenda = $codigo_posto . $sua_os_revenda;

				}

				if ($origem_os == 'O') {

					$link_sua_os = "<a href='os_press.php?os=$os' target='_blank'>$sua_os</a>";
					$xls_sua_os = "<a href='os_press.php?os=$os' target='_blank'>";
					$xls_sua_os.= (is_numeric($sua_os)) ? "=TEXTO($sua_os;\"00000\")" : $sua_os;
					$xls_sua_os.= '</a>';

				}  else {

					$link_sua_os = "<a href='os_revenda_finalizada.php?os_revenda=$os' target='_blank'>$sua_os_revenda</a>";
					$xls_sua_os = "<a href='os_revenda_finalizada.php?os_revenda=$os' target='_blank'>";
					$xls_sua_os.= (is_numeric($sua_os_revenda)) ? "=TEXTO($sua_os_revenda;\"00000\")" : $sua_os_revenda;
					$xls_sua_os.= '</a>';

					$data_fecham = '';//HD 704407 - INTERACAO 58

				}

				$row = "
				<tr style='background-color: $cor;'>
					<td nowrap>$link_sua_os</td>
					<td align='center'>$nota_fiscal</td>
					<td align='center'>$abertura</td>
					<td align='center'>$data_fecham</td>
					<td nowrap title='$admin_nome'>".substr($admin_nome,0,17)."</td>
					<td nowrap title='$autoriza_nome'>".substr($autoriza_nome,0,17)."</td>
					<td nowrap class='nowraptext' title='$consumidor_nome'>".$consumidor_nome."</td>
					<td nowrap class='nowraptext' title='$codigo_posto - $posto_nome'>$posto_nome</td>
					<td nowrap class='nowraptext' title='$produto_referencia - $produto_nome'>".substr($produto_referencia,0,17)."</td>";
					if($login_fabrica == 1){
						$row .="<td nowrap>$referenciaInterna</td>";
						$row .="<td nowrap>".$campos_adicionais['produto_origem']."</td>";
					}
					$row .="<td nowrap class='nowraptext' align='center'>$tipo_atendimento</td>
					<td nowrap class='nowraptext' align='center' nowrap>$causa_troca</td>";
				
				$row.= ' </tr>';
				

				echo $row; // Para mostrar na tela as ações
			}

			?>
		</tbody>



	</table>
	
	<br>
	<br>
	<div class="excel">
		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>
		
		 <div id='gerar_excel' class="btn_excel">
	        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
	        <span><img src='imagens/excel.png' /></span>
	        <span class="txt">Gerar Arquivo Excel</span>
	    </div>
	</div>

<?php 
}else{
	if(strlen($msg_erro)==0){ ?>
		<div class="container">
			<div class="alert alert-warning">
		        <h4>Nenhum registro encontrado</h4>
		    </div>
		</div>
	<?php }
}

include "rodape.php";

}

?>
