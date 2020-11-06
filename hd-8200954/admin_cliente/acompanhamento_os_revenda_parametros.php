<?

$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include "../monitora.php";
}

if (!empty($_POST)){

	/*if($_REQUEST['chk_opt1'])  $chk1  = $_REQUEST['chk_opt1'];
	if($_REQUEST['chk_opt2'])  $chk2  = $_REQUEST['chk_opt2'];
	if($_REQUEST['chk_opt3'])  $chk3  = $_REQUEST['chk_opt3'];
	if($_REQUEST['chk_opt4'])  $chk4  = $_REQUEST['chk_opt4'];
	if($_REQUEST['chk_opt9'])  $chk9  = $_REQUEST['chk_opt9'];*/
	
	$radio_option = $_REQUEST['radio_option'];

	switch($radio_option){
		case "chk_opt1":
			$chk1  = $radio_option;
			break;

		case "chk_opt2":
			$chk2  = $radio_option;
			break;

		case "chk_opt3":
			$chk3  = $radio_option;
			break;

		case "chk_opt4":
			$chk4  = $radio_option;
			break;

		case "chk_opt9":
			$chk9  = $radio_option;
			break;
	}

	$descricao_posto = trim($_REQUEST["descricao_posto"]);
	$revenda_revenda = trim($_REQUEST["revenda_revenda"]);
	$data_inicial_01    = trim($_REQUEST["data_inicial_01"]);
	$data_final_01      = trim($_REQUEST["data_final_01"]);
	$codigo_posto       = trim($_REQUEST['codigo_posto']);
	$produto_referencia = trim($_REQUEST["produto_referencia"]);
	$produto_nome       = trim($_REQUEST["produto_nome"]);
	$numero_os          = trim($_REQUEST["numero_os"]);
	$numero_nf          = trim($_REQUEST["numero_nf"]);
	$nome_revenda       = trim($_REQUEST["revenda_nome"]);
	$cnpj_revenda       = trim($_REQUEST["revenda_cnpj"]);

	if (in_array($login_fabrica, [167, 203])) {
		$data_corte = "01/04/2019";
		
		if (!empty($numero_os)) {
			$sql_data_os = "SELECT to_char(data_digitacao, 'dd/mm/yyyy') AS data_digitacao_inicial FROM tbl_os WHERE sua_os = '$numero_os'";
			$res_data_os = pg_query($con, $sql_data_os);
			if (pg_num_rows($res_data_os) > 0) {
				$data_digitacao_inicial = pg_fetch_result($res_data_os, 0, 'data_digitacao_inicial');
				if (!verifica_data_corte($data_corte, $data_digitacao_inicial)) {
					$msg_erro["msg"][]    = "Data informada inferior a data limite para pesquisa";
				}	
			}
		} else {
			if (!verifica_data_corte($data_corte, $data_inicial_01)) {
				$msg_erro["msg"][]    = "Data informada inferior a data limite para pesquisa";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (count($msg_erro) == 0) {

		if(!strlen($data_inicial_01) > 0 && !strlen($data_final_01) > 0 && !strlen($radio_option) > 0){
			$msg_erro["msg"][]    = "Selecione ao menos um par�metro para a pesquisa.";
			$msg_erro["campos"][] = "data";
			$msg_erro["campos"][] = "checkbox";
		} else {
		
			// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
			$sql = "SELECT  tbl_os.os                                                   ,
							tbl_os.sua_os                                               ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
							to_char(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada       ,
							tbl_posto_fabrica.codigo_posto AS codigo_posto,
							tbl_posto_fabrica.nome_fantasia,
							tbl_posto.nome as descricao_posto,
							tbl_produto.referencia                                      ,
							tbl_produto.descricao                                       ,
							tbl_produto.mao_de_obra                                     ,
							tbl_os.nota_fiscal                                          ,
							tbl_os.serie                                                ,
							tbl_os.revenda_nome                     AS revenda_nome,
							tbl_os.revenda_cnpj                     AS revenda_cnpj
					FROM    tbl_os
					JOIN    tbl_revenda          ON tbl_revenda.revenda            = tbl_os.revenda
					JOIN    tbl_produto          ON tbl_produto.produto            = tbl_os.produto
					JOIN    tbl_posto            ON tbl_posto.posto                = tbl_os.posto
					JOIN    tbl_posto_fabrica    ON tbl_posto.posto                = tbl_posto_fabrica.posto
												AND tbl_posto_fabrica.fabrica      = $login_fabrica
					WHERE   (tbl_os.sua_os ILIKE '%-%' OR tbl_os.consumidor_revenda = 'R')
					AND     tbl_os.fabrica = $login_fabrica
					AND     (1=2 ";

			$msg = "";
			$monta_sql = '';

			if(strlen($chk1) > 0){
				//dia atual
				$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$dia_hoje = pg_fetch_result($resX, 0, 0);
				$dia_hoje_inicio = $dia_hoje . ' 00:00:00';
				$dia_hoje_final  = $dia_hoje . ' 23:59:59';

				/*$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
				$resX = pg_exec ($con,$sqlX);*/
				#  $dia_hoje_final = pg_result ($resX,0,0);

				$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
				$dt = 1;

				$msg .= " e OS Revenda lan�adas hoje";

			}

			if(strlen($chk2) > 0){
				// dia anterior
				$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$dia_ontem = pg_fetch_result($resX, 0, 0);
				$dia_ontem_inicial = $dia_ontem . ' 00:00:00';
				$dia_ontem_final   = $dia_ontem . ' 23:59:59';

				$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";

				if (!empty($chk1)) {
					$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$dia_ontem_inicial' AND '$dia_hoje_final' ) ";
				}

				$dt = 1;

				$msg .= " e OS Revenda lan�adas ontem";
			}

			if(strlen($chk3) > 0){
				// �ltima semana
				$sqlX = "SELECT to_char (current_date , 'D')";
				$resX = pg_exec ($con,$sqlX);
				$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

				$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$dia_semana_inicial = pg_result ($resX,0,0);
				$data_semana_inicial = $dia_semana_inicial . ' 00:00:00';

				$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$dia_semana_final = pg_result ($resX,0,0);
			   	$data_semana_final = $dia_semana_final . ' 23:59:59';

				$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$data_semana_inicial' AND '$data_semana_final') ";
				$dt = 1;

				$msg .= " e OS Revenda lan�adas nesta semana";

			}

			if(strlen($chk4) > 0){
				// do m�s
				$mes_inicial = trim(date("Y")."-".date("m")."-01");
				$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

				$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
				$dt = 1;

				$msg .= " e OS Revenda lan�adas neste m�s";
			}

				// entre datas
			if((strlen($data_inicial_01) > 0) && (strlen($data_final_01) > 0)){
				if(strlen($data_inicial_01) > 0 && strlen($data_final_01) > 0){
					$data_inicial = $data_inicial_01;
					$data_final   = $data_final_01;

					list($di, $mi, $yi) = explode("/", $data_inicial);
					list($df, $mf, $yf) = explode("/", $data_final);

					if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
						$msg_erro["msg"][]    = "Data Inv�lida";
						$msg_erro["campos"][] = "data";
					} else {
						$aux_data_inicial = "{$yi}-{$mi}-{$di}";
						$aux_data_final   = "{$yf}-{$mf}-{$df}";

						if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
							$msg_erro["msg"][]    = "Data Final n�o pode ser menor que a Data Inicial";
							$msg_erro["campos"][] = "data";
						}else {
							if (!empty($chk_opt1)) {
								$data_compara1 = new DateTime($dia_hoje);
								$data_compara2 = new DateTime($nova_data_inicial);

								if ($data_compara1 < $data_compara2) {
									$nova_data_inicial = $dia_hoje;
								}

								$data_compara2 = new DateTime($nova_data_final);

								if ($data_compara1 > $data_compara2) {
									$nova_data_final = $dia_hoje;
								}
							}

							if (!empty($chk_opt2)) {
								$data_compara1 = new DateTime($dia_ontem);
								$data_compara2 = new DateTime($nova_data_inicial);

								if ($data_compara1 < $data_compara2) {
									$nova_data_inicial = $dia_ontem;
								}

								$data_compara2 = new DateTime($nova_data_final);

								if ($data_compara1 > $data_compara2) {
									$nova_data_final = $dia_ontem;
								}
							}

							if (!empty($chk_opt3)) {
								$data_compara1 = new DateTime($dia_semana_inicial);
								$data_compara2 = new DateTime($nova_data_inicial);

								if ($data_compara1 < $data_compara2) {
									$nova_data_inicial = $dia_semana_inicial;
								}

								$data_compara1 = new DateTime($dia_semana_final);
								$data_compara2 = new DateTime($nova_data_final);

								if ($data_compara1 > $data_compara2) {
									$nova_data_final = $dia_semana_final;
								}

							}

							if (!empty($chk_opt4)) {
								$data_compara1 = new DateTime($mes_inicial);
								$data_compara2 = new DateTime($nova_data_inicial);

								if ($data_compara1 < $data_compara2) {
									$nova_data_inicial = $mes_inicial;
								}

								$data_compara1 = new DateTime($mes_final);
								$data_compara2 = new DateTime($nova_data_final);

								if ($data_compara1 > $data_compara2) {
									$nova_data_final = $mes_final;
								}

							}

							$aux_data_inicial = $nova_data_inicial . ' 00:00:00';
							$aux_data_final = $nova_data_final . ' 23:59:59';

							$monta_sql = " OR (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final') ";
							$dt = 1;

						 	$msg .= " e OS Revenda lan�adas entre os dias $data_inicial e $data_final ";
						}
					}
				}else{
					$msg_erro["msg"][]    = "Data Inv�lida";
					$msg_erro["campos"][] = "data";
				}
			}

			if(strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0){
				// codigo do posto
				$sqlBuscaPosto = "SELECT tbl_posto_fabrica.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND (
						(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
						OR
						(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
					)";
				$resBuscaPosto = pg_query($con ,$sqlBuscaPosto);

				if (!pg_num_rows($resBuscaPosto)) {
					$msg_erro["msg"][]    = "Posto n�o encontrado";
					$msg_erro["campos"][] = "posto";
				} else {
					if ($dt == 1) $xsql = "AND ";
					else          $xsql = "OR ";
					$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto = '". $codigo_posto ."' ";
					$dt = 1;
					$msg .= " e OS Revenda lan�adas pelo posto $codigo_posto ";
				}
			}

			if(strlen($produto_referencia) > 0 || strlen($produto_nome) > 0){
				// referencia do produto

				$sqlBuscaProduto = "SELECT produto
					FROM tbl_produto
					WHERE fabrica_i = {$login_fabrica}
					AND (
	                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
	                    OR
	                    (UPPER(descricao) = UPPER('{$produto_nome}'))
	                )
				";

				$resBuscaProduto = pg_query($con ,$sqlBuscaProduto);

				if (!pg_num_rows($resBuscaProduto)) {
					$msg_erro["msg"][]    = "Produto n�o encontrado";
					$msg_erro["campos"][] = "produto";
				} else {
					if ($dt == 1) $xsql = "AND ";
					else          $xsql = "OR ";

					$monta_sql .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
					$dt = 1;

					$msg .= " e OS Revenda lan�adas com produto $produto_referencia ";
				}
			}

			if(strlen($revenda_nome) > 0 && strlen($revenda_cnpj) >0){

				$sqlBuscaRevenda = "SELECT revenda
					FROM tbl_revenda
					WHERE revenda = {$revenda_revenda}
				";
				$resBuscaRevenda = pg_query($con ,$sqlBuscaRevenda);

				if (!pg_num_rows($resBuscaRevenda)) {
					$msg_erro["msg"][]    = "Revenda n�o encontrada.";
					$msg_erro["campos"][] = "produto";
				} else {
					if ($dt == 1) $xsql = "AND ";
					else          $xsql = "OR ";
					$monta_sql .= "$xsql tbl_revenda.cnpj = '". $cnpj_revenda ."' ";
					$dt = 1;
					$msg .= " e OS Revenda lan�adas pela revenda $cnpj_revenda - $nome_revenda ";
				}
			}

			if(strlen($chk9) > 0){
				// numero de serie do produto
				if ($dt == 1) $xsql = "AND ";
				else          $xsql = "OR ";

				$monta_sql .= "$xsql tbl_os.finalizada ISNULL ";
				$dt = 1;
			}

			if(strlen($numero_os) > 0){
				// numero_os

				$sqlBuscaOS = "SELECT os
					FROM tbl_os
					WHERE fabrica = {$login_fabrica}
					AND sua_os = '{$numero_os}'
				";
				$resBuscaOS = pg_query($con ,$sqlBuscaOS);

				if (!pg_num_rows($resBuscaOS)) {
					$msg_erro["msg"][]    = "OS Revenda n�o encontrada";
					$msg_erro["campos"][] = "revenda_os";
				} else {
					if ($dt == 1) $xsql = "AND ";
					else          $xsql = "OR ";

					$monta_sql .= "$xsql tbl_os.sua_os ilike '". $numero_os ."%' ";
					$dt = 1;

					$msg .= " e OS Revenda lan�adas com n�mero $numero_os ";
				}
			}

			// ordena sql padrao
			$sql .= $monta_sql;
			$sql .= ")
					ORDER BY lpad(tbl_os.sua_os,20,'0') ASC";
			$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";

			//echo "<br>".nl2br($sql); exit;


			//$res_xls   = pg_query($con, $sql);
			$resMASTER = pg_query($con,$sql);

			$rows = pg_num_rows($resMASTER);

			if ($_POST["gerar_excel"]) {
				if(pg_num_rows($resMASTER) > 0){
					$data = date ("dmY");

					$fileName = "relatorio_os_revenda-{$data}.xls";

					$colspan = 6;

					if(in_array($login_fabrica, array(11, 15, 81, 114, 172))){
						$colspan = 7;
					}

					$file = fopen("/tmp/{$fileName}", "w");
					$thead = "<table border='1'>
								<thead>
									<tr>
										<th colspan='" . $colspan . "' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
											RELAT�RIO DE OS REVENDA
										</th>
									</tr>
									<tr>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS Revenda</th>
					";

					if(in_array($login_fabrica, array(81,114))){
						$thead .= "
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
						";
					}

					$thead .="
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Produto</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota Fiscal</th>";

					if(in_array($login_fabrica, array(11,15,172))){
						$thead .= "
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>MO Produto</th>
						";
					}

					$thead .= "
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Revenda</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
										<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Finalizada</th>
								</tr>
							</thead>
					";

					fwrite($file, $thead);
					$body = "<tbody>";

					for ($x = 0 ; $x < pg_numrows ($resMASTER) ; $x++){
						$os              = trim(pg_result($resMASTER,$x,os));
						$sua_os          = trim(pg_result($resMASTER,$x,sua_os));
						$data_abertura   = trim(pg_result($resMASTER,$x,data_abertura));
						$finalizada      = trim(pg_result($resMASTER,$x,finalizada));
						$referencia      = trim(pg_result($resMASTER,$x,referencia));
						$descricao       = trim(pg_result($resMASTER,$x,descricao));
						$nota_fiscal     = trim(pg_result($resMASTER,$x,nota_fiscal));
						$serie           = trim(pg_result($resMASTER,$x,serie));
						$revenda         = trim(strtoupper(pg_result($resMASTER,$x,revenda_nome)));
						//$mao_de_obra     = number_nameat(pg_result($resMASTER,$x,mao_de_obra), 2, ",", ".");
						$mao_de_obra     = trim(pg_result($resMASTER,$x,mao_de_obra));

						if (in_array($login_fabrica, array(81, 114))) {

							$codigo_posto  = trim(pg_fetch_result($resMASTER, $x, 'codigo_posto'));
							
							$nome_fantasia_exibe = " - ".substr(trim(pg_fetch_result($resMASTER, $x, 'descricao_posto')),0,30);
							$nome_fantasia = " - ".trim(pg_fetch_result($resMASTER, $x, 'descricao_posto'));
							

							$nome_do_posto_exibe = $codigo_posto.$nome_fantasia_exibe;
							$nome_do_posto = $codigo_posto.$nome_fantasia;

						}

						$body .= "
								<tr>
									<td nowrap align='center' valign='top'>$sua_os</td>
						";

						if(in_array($login_fabrica, array(81,114))){
							$body .= "
									<td nowrap align='center' valign='top'>$nome_do_posto_exibe</td>
							";
						}

						$body .= "
									<td nowrap align='center' valign='top'>$referencia - $descricao</td>
									<td nowrap align='center' valign='top'>$nota_fiscal</td>
						";

						if(in_array($login_fabrica, array(11,15,172))){

							$mao_de_obra = number_format($mao_de_obra,2,",",".");

							$body .= "
									<td nowrap align='right' valign='top'>R$ $mao_de_obra</td>
							";
						}

						$body .= "
									<td nowrap align='center' valign='top'>$revenda</td>
									<td nowrap align='center' valign='top'>$data_abertura</td>
									<td nowrap align='center' valign='top'>$finalizada</td>
								</tr>
						";
					}

					fwrite($file, $body);

					fwrite($file, "
								<tr>
									<th colspan='" . $colspan . "' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resMASTER)." registros</th>
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
		}
	}
}

$layout_menu = "gerencia";
$title = "ACOMPANHAMENTO DE OS DE REVENDA";

include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "mask",
    "alphanumeric",
    "dataTable",
    "shadowbox",
);

include("../admin/plugin_loader.php");

?>
<script type="text/javascript">
	
	$(function() {
		$.datepickerLoad(Array("data_final_01", "data_inicial_01"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		var table = new Object();
        table['table'] = '#resultado_os_revenda';
        table['type'] = 'full';
        $.dataTableLoad(table);

        $(".radio").on('click',function(){
        	$('#data_inicial_01').prop("disabled", true);
        	$('#data_final_01').prop("disabled", true);
        	$("#data_inicial_01").prev(".asteristico").hide();
        	$("#data_final_01").prev(".asteristico").hide();
        });

        $("#div_data_inicial").on('click',function(){
        	if($('#data_inicial_01').prop("disabled") == true){
        		$('#data_inicial_01').prop("disabled", false);
        		$('#data_final_01').prop("disabled", false);
        		$("#data_inicial_01").prev(".asteristico").show();
        		$("#data_final_01").prev(".asteristico").show();
        		$(".radio_input").prop("disabled",true);
				$(".radio_input").prop("checked",false);
        	}
        });

        $("#div_data_final").on('click',function(){
        	if($('#data_final_01').prop("disabled") == true){
        		$('#data_final_01').prop("disabled", false);
        		$('#data_inicial_01').prop("disabled", false);
        		$("#data_inicial_01").prev(".asteristico").show();
        		$("#data_final_01").prev(".asteristico").show();
        		$(".radio_input").prop("disabled",true);
				$(".radio_input").prop("checked",false);
        	}
        });

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function retorna_produto (retorno) {
	    $("#produto").val(retorno.produto);
	    $("#produto_referencia").val(retorno.referencia);
	    $("#produto_descricao").val(retorno.descricao);
	}

	function retorna_revenda(retorno) {
	    $("#revenda_nome").val(retorno.razao);
	    $("#revenda_cnpj").val(retorno.cnpj);
	    $("#revenda_revenda").val(retorno.revenda_fabrica);
	}
</script>

<?

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?
} 

/*SETANDO CHECKED PARA OS CHECKBOXES QUANDO RETORNAR O POST*/
	if($_REQUEST['chk_opt1'])  $checked1  = "CHECKED";
	if($_REQUEST['chk_opt2'])  $checked2  = "CHECKED";
	if($_REQUEST['chk_opt3'])  $checked3  = "CHECKED";
	if($_REQUEST['chk_opt4'])  $checked4  = "CHECKED";
	if($_REQUEST['chk_opt9'])  $checked9  = "CHECKED";

?>

<div class="alert alert-warning">
	<h4>Data m�nima para pesquisa 01/04/2019</h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigat�rios </b>
</div>

<form name="frm_pesquisa" METHOD="POST" ACTION="<?=$PHP_SELF?>" align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Par�metros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("revenda_os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class="control-label" for="revenda_os">N�mero da OS Revenda</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="numero_os" value="<?=$numero_os?>" class="span12">&nbsp;
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<div id="div_data_inicial">
								<h5 class='asteristico'>*</h5>
								<input size="12" maxlength="10" type="text" name="data_inicial_01" id="data_inicial_01" value="<?=$data_inicial_01?>" class="span12 inputs_datas" >
							</div>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<div id="div_data_final">
							<h5 class='asteristico'>*</h5>
							<input size="12" maxlength="10" type="text" name="data_final_01" id="data_final_01" value='<?=$data_final_01?>' class="span12 inputs_datas">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>C�digo Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span8 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_referencia'>Ref. Produto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_descricao'>Descri��o Produto</label>
				<div class='controls controls-row'>
					<div class='span8 input-append'>
						<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("revenda", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class="control-label" for="revenda_cnpj">CNPJ</label>
                <div class="controls controls-row">
                    <div class="span7 input-append">
                        <input id="revenda_cnpj" name="revenda_cnpj" class='span12' maxlength="20" type="text" value="<?=$revenda_cnpj?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
 					</div>
				</div>
			</div>
		</div>
        <div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class="control-label" for="revenda_nome">Nome Revenda</label>
                <div class="controls controls-row">
                    <div class="span8 input-append">
                        <input id="revenda_nome" name="revenda_nome" class="span12" type="text" maxlength="50" value="<?=$revenda_nome?>" />
                        <span class="add-on" rel="lupa" >
                            <i class="icon-search"></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("checkbox", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
							<input class='radio_input' type="radio" name="radio_option" <?=$checked9?> value="chk_opt9">&nbsp;
							N�o Finalizadas&nbsp;
							</label>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>
    <div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("checkbox", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
							<input class='radio_input' type="radio" name="radio_option" <?=$checked1?> value="chk_opt1">&nbsp;
							OS Lan�adas Hoje&nbsp;
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("checkbox", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
							<input class='radio_input' type="radio" name="radio_option" <?=$checked2?> value="chk_opt2">&nbsp;
							OS Lan�adas Ontem&nbsp;
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("checkbox", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
							<input class='radio_input' type="radio" name="radio_option" <?=$checked3?> value="chk_opt3">&nbsp;
							OS Lan�adas Nesta Semana&nbsp;
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("checkbox", $msg_erro["campos"])) ? "error" : ""?>'>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
							<input class='radio_input' type="radio" name="radio_option" <?=$checked4?> value="chk_opt4">&nbsp;
							OS Lan�adas Neste M�s&nbsp;
						</label>
					</div>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="revenda_fone" value="">
	<input type="hidden" name="revenda_cidade" value="">
	<input type="hidden" name="revenda_estado" value="">
	<input type="hidden" name="revenda_endereco" value="">
	<input type="hidden" name="revenda_cep" value="">
	<input type="hidden" name="revenda_numero" value="">
	<input type="hidden" name="revenda_complemento" value="">
	<input type="hidden" name="revenda_bairro" value="">
	<input type='hidden' name = 'revenda_email'>
	<input type='hidden' name = 'revenda_revenda' id="revenda_revenda" value="<?=$revenda_revenda;?>">

	<p>
		<button class='btn' onClick="document.frm_pesquisa.submit();" alt="Preencha as op��es e clique aqui para pesquisar">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br>

</form>
<br>

</div>
<div style="margin-left: 0.3cm; margin-right: 0.3cm;">

<? 
if ($rows == 0 and !empty($_POST) && !count($msg_erro["msg"]) > 0) {
	echo "  <div class='container'>
        		<div class='alert'>
            		<h4>Nenhum resultado encontrado</h4>
        		</div>  
    		</div>";
}elseif ($rows >0 and !empty($_POST)){

	?>
	<input type="hidden" name="rows" id="rows" value="<?=$rows?>" />
	<?

	echo "
		<table id='resultado_os_revenda' class='table table-striped table-bordered table-hover table-fixed'>
			<thead>
				<tr class='titulo_coluna'>
					<th>OS Revenda</th>
	";

	if (in_array($login_fabrica, array(81, 114))) {
		echo "		<th>Posto</th>";
	}

	echo "
					<th>Produto</th>
					<th>Nota Fiscal</th>
	";

	if(in_array($login_fabrica, array(11,172))) echo "<th>MO Produto</th>";

	echo "
					<th>Revenda</th>
	";

	if (in_array($login_fabrica, array(11, 81, 114, 172))) echo "<th>Abertura</th>";

	echo "
					<th>Finalizada</th>
				</tr>
			</thead>
			<tbody>
	";

	for ($i = 0 ; $i < $rows ; $i++){
		$os              = trim(pg_result($resMASTER,$i,'os'));
		$sua_os          = trim(pg_result($resMASTER,$i,'sua_os'));
		$data_abertura   = trim(pg_result($resMASTER,$i,'data_abertura'));
		$finalizada      = trim(pg_result($resMASTER,$i,'finalizada'));
		$referencia      = trim(pg_result($resMASTER,$i,'referencia'));
		$descricao       = trim(pg_result($resMASTER,$i,'descricao'));
		$nota_fiscal     = trim(pg_result($resMASTER,$i,'nota_fiscal'));
		$serie           = trim(pg_result($resMASTER,$i,'serie'));
		$revenda         = trim(strtoupper(pg_result($resMASTER,$i,'revenda_nome')));
		$revenda_cnpj         = trim(strtoupper(pg_result($resMASTER,$i,'revenda_cnpj')));
		//$mao_de_obra     = number_nameat(pg_result($resMASTER,$i,'mao_de_obra'), 2, ",", ".");
		$mao_de_obra     = trim(pg_result($resMASTER,$i,'mao_de_obra'));

		$descricao_exibe = substr($descricao, 0,30);
		$revenda_exibe   = $revenda_cnpj." - ".substr($revenda, 0,40);
		if (in_array($login_fabrica, array(81, 114))) {

			$codigo_posto  = trim(pg_fetch_result($resMASTER, $i, 'codigo_posto'));
			
			$nome_fantasia_exibe = " - ".substr(trim(pg_fetch_result($resMASTER, $i, 'descricao_posto')),0,30);
			$nome_fantasia = " - ".trim(pg_fetch_result($resMASTER, $i, 'descricao_posto'));
			

			$nome_do_posto_exibe = $codigo_posto.$nome_fantasia_exibe;
			$nome_do_posto = $codigo_posto.$nome_fantasia;

		}

			echo "	<tr>";
				echo "	<td class='tac' nowrap>
							$sua_os
						</td>";

			if (in_array($login_fabrica, array(81, 114))) {

				echo "	<td nowrap align='left'>
							<label title='$nome_do_posto' > $nome_do_posto_exibe </label>
						</td>";

			}

				echo "	<td class='tal' nowrap>
							<label title='$referencia - $descricao'> $referencia - $descricao_exibe </label>
						</td>";

				echo "	<td class='tac'>$nota_fiscal</td>";

			if(in_array($login_fabrica, array(11,172))) {

				$mao_de_obra = number_format($mao_de_obra,2,",",".");
				echo "<td class='tar'>R$ $mao_de_obra</td>";

			}

			echo "<td class='tal' nowrap><label title='$revenda_exibe	'>$revenda_exibe</label></td>";

			if(in_array($login_fabrica, array(11, 81, 114, 172))) {

				echo "<td class='tac'>$data_abertura</td>";

			}

				echo "<td class='tac'>$finalizada</td>";
			echo "</tr>";

		flush();

	}

	echo "</tbody>";
	echo "</table>";



	$jsonPOST = excelPostToJson($_POST); ?>

	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div> <?
}
?>
<br>

<?

include "../admin/rodape.php";
