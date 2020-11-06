<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include "autentica_admin.php";
include "funcoes.php";

$erro = "";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$vazio = true;

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if ((strlen(trim($_POST["data_inicial"])) <= 0) && (strlen(trim($_POST["data_final"])) <= 0) && (strlen(trim($_POST["codigo_posto"])) <= 0) && (strlen(trim($_POST["descricao_posto"])) <= 0)){
		$msg_erro["msg"][]    = traduz("Preencha todos os campos obrigatórios.");
		$msg_erro["campos"][] = "data";
		$msg_erro["campos"][] = "posto";
	}else{
		if (strlen(trim($_POST["data_inicial"])) > 0) $x_data_inicial = trim($_POST["data_inicial"]);
		if (strlen(trim($_GET["data_inicial"])) > 0)  $x_data_inicial = trim($_GET["data_inicial"]);

		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);

		if (strlen(trim($_POST["data_final"])) > 0) $x_data_final   = trim($_POST["data_final"]);
		if (strlen(trim($_GET["data_final"])) > 0) $x_data_final = trim($_GET["data_final"]);

		$x_data_final   = fnc_formata_data_pg($x_data_final);

		if (strlen($x_data_inicial) > 0 && $x_data_inicial != "null" && ($x_data_inicial < $x_data_final)) {
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial = substr($x_data_inicial, 8, 2);
			$mes_inicial = substr($x_data_inicial, 5, 2);
			$ano_inicial = substr($x_data_inicial, 0, 4);
			$data_inicial = $dia_inicial . "/" . $mes_inicial . "/" . $ano_inicial;
			list($d, $m, $y) = explode("/", $data_inicial);
	        if(!checkdate($m,$d,$y)){
	            //$erro = "Data Inválida";
	            $msg_erro["msg"][]    = traduz("Data Inválida");
				$msg_erro["campos"][] = "data";
			}
		}else{
			//$erro = " Data inválida ";
		}

		if (strlen($x_data_final) > 0 && $x_data_final != "null") {
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final = substr($x_data_final, 8, 2);
			$mes_final = substr($x_data_final, 5, 2);
			$ano_final = substr($x_data_final, 0, 4);
			$data_final = $dia_final . "/" . $mes_final . "/" . $ano_final;
			list($d, $m, $y) = explode("/", $data_final);
	        if(!checkdate($m,$d,$y)){
	            //$erro = "Data Inválida";
	            $msg_erro["msg"][]    = traduz("Data Inválida");
				$msg_erro["campos"][] = "data";
			}
		}else{
			//$erro = " Data Inválida ";
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		}

		if (strlen(trim($_POST["status"])) > 0) $status = trim($_POST["status"]);
		if (strlen(trim($_GET["status"])) > 0)  $status = trim($_GET["status"]);

		if (strlen(trim($_POST["posto"])) > 0) $posto = trim($_POST["posto"]);
		if (strlen(trim($_GET["posto"])) > 0)  $posto = trim($_GET["posto"]);

		if (strlen($codigo_posto)==0) {
			//$erro = "Informe o posto";
			$msg_erro["msg"][]    = traduz("Posto não encontrado");
			$msg_erro["campos"][] = "posto";
		} else {
			$sqlp = "SELECT posto
					 FROM tbl_posto_fabrica
					 WHERE fabrica = $login_fabrica
					 AND codigo_posto = '$codigo_posto'";
			$resp = pg_exec($con, $sqlp);

			if (pg_numrows($resp) > 0) {
				$posto = pg_result($resp, 0, 0);
			} else {
				//$erro = "Posto informado não encontrado.";
				$msg_erro["msg"][]    = traduz("Posto não encontrado");
				$msg_erro["campos"][] = "posto";
			}
		}


		$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
		setcookie("LinkStatus", $link_status);
		
		$vazio = false;
	}
}

$layout_menu = "auditoria";
$title = traduz("RELAÇÃO DE STATUS DA  ORDEM DE SERVIÇO");

include "cabecalho_new.php";

$plugins = array(
	"datepicker",
	"shadowbox",
	"alphanumeric",
	"autocomplete",
	"mask",
	"dataTable"
);

include "plugin_loader.php";
?>
<script>
	$(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		var table_os_nao_excluidas = new Object();
        table_os_nao_excluidas['table'] = '#resultado_os_nao_excluidas';
        table_os_nao_excluidas['type'] = 'full';
        $.dataTableLoad(table_os_nao_excluidas);

        var table_os_excluidas = new Object();
        table_os_excluidas['table'] = '#resultado_os_excluidas';
        table_os_excluidas['type'] = 'full';
        $.dataTableLoad(table_os_excluidas);
	});

	function retorna_posto(retorno){
	$("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
	}
</script>

<script type="text/javascript">

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}
});

</script>

<input type="hidden" name="acao">

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right"> <?=traduz("* Campos obrigatórios")?> </b>
</div>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>" class='form-search form-inline tc_formulario'>
<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div>
<br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input class="span12" type="text" name="codigo_posto" id="codigo_posto" value="<? echo $codigo_posto ?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'><?=traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input class="span12" type="text" name="descricao_posto" id="descricao_posto" value="<? echo $descricao_posto ?>">
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
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; ?>" class="span12">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final"  size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" class="span12">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?
	if ( !in_array($login_fabrica, array(11,172)) ) {
	?>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Status")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
						<select name="status">
							<option <?if ($status == "00") echo " selected ";?> value='00'><?=traduz("Todas")?></option>
							<option <?if ($status == "01") echo " selected ";?> value='01'><?=traduz("Aprovadas")?></option>
							<option <?if ($status == "13") echo " selected ";?> value='13'><?=traduz("Recusadas")?></option>
							<option <?if ($status == "18") echo " selected ";?> value='18'><?=traduz("Finalizadas")?></option>
							<option <?if ($status == "14") echo " selected ";?> value='14'><?=traduz("Acumuladas")?></option>
							<option <?if ($status == "15") echo " selected ";?> value='15'><?=traduz("Excluídas")?></option>
						</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'></div>
	</div>
	<?
	}else{ // testar com o posto 01048
	?>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Status")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
						<select name="status">
							<option <?if ($status == "00") echo " selected ";?> value='00'><?=traduz("Todas")?></option>
							<option <?if ($status == "01") echo " selected ";?> value='01'><?=traduz("Aprovadas")?></option>
							<option <?if ($status == "16") echo " selected ";?> value='16'><?=traduz("Finalizadas")?></option>
							<option <?if ($status == "17") echo " selected ";?> value='17'><?=traduz("Não Finalizadas")?></option>
							<option <?if ($status == "15") echo " selected ";?> value='15'><?=traduz("Excluídas")?></option>
						</select>
						</div>
					</div>
				</div>
			</div>
		<div class='span4'></div>
	</div>
	<?
	}
	?>
	<p><br/>
	<button class='btn' id="btn_acao" type="submit"><?=traduz("Pesquisar")?></button>
	<input type='hidden' id="acao" name='acao' value='PESQUISAR' />
	</p><br/>
</form>
</div>
<div style="margin-right: 1.5cm; margin-left: 1.5cm;">
<br>

<?

flush();

if (strlen($acao) > 0 && strlen($erro) == 0) {
	//SOMENTE OSs QUE NÃO ESTÃO EXCLUIDAS
	if ($status <> "15" AND $status <> "17" ) {

		$sql = "SELECT *FROM (
					SELECT  DISTINCT
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                                 AS nome_posto      ,
						tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao  ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura   ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY')   AS data_fechamento ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')        AS finalizada      ,
						tbl_os.pecas                                                      ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.mao_de_obra                                                ,
						tbl_extrato.extrato                                               ,
						tbl_extrato_extra.exportado                                       ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado        ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao    ,
						tbl_os.nota_fiscal                                                ,
						tbl_os.serie                                                      ,
						tbl_os.os_reincidente                                             ,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os,
						(
							select tbl_os_status.status_os
							from tbl_os_status
							where tbl_os_status.os = tbl_os.os
							and   extrato notnull
							order by data desc limit 1
						)                                              AS status_os       ,
						(
							select tbl_os_status.observacao
							from tbl_os_status
							where tbl_os_status.os = tbl_os.os
							and   extrato notnull
							order by data desc limit 1
						)                                              AS observacao
					FROM  tbl_os
					LEFT JOIN  tbl_os_extra          ON tbl_os_extra.os           = tbl_os.os
					LEFT JOIN  tbl_extrato           ON tbl_extrato.extrato       = tbl_os_extra.extrato
					LEFT JOIN  tbl_extrato_extra     ON tbl_extrato_extra.extrato = tbl_os_extra.extrato
					JOIN  tbl_posto_fabrica          ON tbl_posto_fabrica.posto   = tbl_os.posto
													AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
					JOIN  tbl_posto                  ON tbl_posto.posto           = tbl_os.posto
					WHERE tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.posto   = $posto
					GROUP BY tbl_posto_fabrica.codigo_posto,
						 tbl_posto.nome                    ,
						 tbl_os.os                         ,
						 tbl_os.sua_os                     ,
						 tbl_os.data_digitacao             ,
						 tbl_os.data_abertura              ,
						 tbl_os.data_fechamento            ,
						 tbl_os.finalizada                 ,
						 tbl_os.pecas                      ,
						 tbl_os.tipo_atendimento           ,
						 tbl_os.mao_de_obra                ,
						 tbl_extrato.extrato               ,
						 tbl_extrato_extra.exportado       ,
						 tbl_extrato.aprovado              ,
						 tbl_extrato.data_geracao          ,
						 tbl_os.nota_fiscal                ,
						 tbl_os.serie                      ,
						 tbl_os.os_reincidente             ,
						 tbl_os.consumidor_revenda         ,
						 tbl_os.cortesia                   ,
						 tbl_os.tipo_atendimento                        ) x";

		//TODAS
		if ($status == "00") {
			$sql.= " WHERE data_fechamento NOTNULL";
		}

		//APROVADA
		if ($status == "01") {
			if ($login_fabrica == 19) {
				$sql.= " WHERE status_os <> 13
						 AND aprovado NOTNULL
						 AND data_fechamento NOTNULL ";
			}else{
				$sql.= " WHERE aprovado NOTNULL
						 AND extrato NOTNULL
						 AND data_fechamento NOTNULL ";
			}
		}

		//PESQUISA POR RECUSADAS
		if ($status == "13") {
			$sql.= " WHERE status_os = 13";
		}

		//ACUMULADA
		if ($status == "14") {
			$sql.= " WHERE status_os = 14
					 AND aprovado IS NULL
					 AND extrato IS NULL
					 AND data_fechamento NOTNULL";
		}

		//EXCLUIDA
		if ($status == "15") {
			$sql.= " WHERE status_os = 15
					 AND extrato IS NULL
					 AND data_fechamento NOTNULL";
		}

		// OS Finalizadas
		if ($status == "16") {
			$sql.= " WHERE data_fechamento NOTNULL
						AND aprovado is NULL";
		}

		// OS Finalizadas
		if ($status == "18") {
			$sql.= " WHERE data_fechamento <> ''";
		}



		$sql .= " ORDER BY codigo_posto, sua_os";
		#echo "SQL1:" . $sql;

		$res = pg_exec($con,$sql);


		if (pg_numrows($res) > 0) {
			echo "<table>";
			echo "<tbody>";
			echo "<tr>";
			if($login_fabrica == 20){
				echo "<td class='tal'><font size=1>&nbsp; <b>".traduz("OS excluída pelo fabricante")."</b> <br>&nbsp; <B>".traduz("Para alterar, acesse a")." <a href='os_parametros.php'>".traduz("Consulta de OS</a> clique em Alterar OS e faça as alterações necessárias.")." </B></font></td>";
			}else{
				echo "<td><div style='background-color: #FFE1E1; width: 30px; height: 20px;'></div></td>";
				echo "<td align='left'>&nbsp; <b>".traduz("OS excluída pelo fabricante")."</b> <br>&nbsp;<B>".traduz("Para alterar, acesse a")." <a href='os_parametros.php'>".traduz("Consulta de OS</a> clique em Reabrir OS e faça as alterações necessárias")."</B></td>";
			}
			echo "</tr>";
			if($login_fabrica == 30) { // HD 50477
				echo "<tr>";
				echo "<td><div style='background-color: #D7FFE1; width: 30px; height: 20px;'></div></td>";
				echo "<td align='left'><b>&nbsp;".traduz("Reincidências")."</b></td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
			echo "<br>";

			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";

			echo "<table id='resultado_os_nao_excluidas'class='table table-striped table-bordered table-hover table-fixed'>";
			echo "<thead>";
			echo "<tr class='titulo_tabela'>";
			if ($status == 00) {
				echo "<th colspan='10'>".traduz("Relação de OS")."</th>";
			}elseif ($status == 01) {
				echo "<th colspan='10'>".traduz("Relação de OS Aprovada")."</th>";
			}elseif ($status == 13) {
				echo "<th colspan='10'>".traduz("Relação de OS Recusada")."</th>";
			}elseif ($status == 14) {
				echo "<th colspan='10'>".traduz("Relação de OS Acumulada")."</th>";
			}elseif ($status == 16 || $status == 18) {
				echo "<th colspan='10'>".traduz("Relação de OS Finalizada")."</th>";
			}

			echo "</tr>";


			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				/*echo "<tr class='subtitulo'>";
				echo "<td colspan='100%'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";*/
			}


				echo "<tr class='titulo_coluna'>";
				echo "<td class='tac span1'>".traduz("OS")."</td>";
				echo "<td class='tac span2'>".traduz("Digitação")."</td>";
				echo "<td class='tac span2'>".traduz("Abertura")."</td>";
				echo "<td class='tac span2'>".traduz("Fechamento")."</td>";
				echo "<td class='tac span2'>".traduz("Finalizada")."</td>";
				echo "<td class='tac'>".traduz("Total")."</td>";
				echo "<td class='tac span2'>".traduz("Protocolo")."</td>";
				if($login_fabrica==30) { // HD 50477
					echo "<td class='tac span2'>".traduz("Nota Fiscal")."</td>";
					echo "<td class='tac span2'>".traduz("Série")."</td>";
				}
				echo "<td class='tac span2'>".traduz("Status")."</td>";
				if($login_fabrica==1) {
					echo "<td class='tac span2'>".traduz("Tipo OS")."</td>";
				}
				echo "</tr>";
				echo "</thead>";
				echo "<tbody>";

				$cor = "";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto     = trim(pg_result($res,$i,codigo_posto));
				$nome_posto       = trim(pg_result($res,$i,nome_posto));
				$os               = trim(pg_result($res,$i,os));
				$sua_os           = trim(pg_result($res,$i,sua_os));
				$data_digitacao   = trim(pg_result($res,$i,data_digitacao));
				$data_abertura    = trim(pg_result($res,$i,data_abertura));
				$data_fechamento  = trim(pg_result($res,$i,data_fechamento));
				$finalizada       = trim(pg_result($res,$i,finalizada));
				$pecas            = trim(pg_result($res,$i,pecas));
				$mao_de_obra      = trim(pg_result($res,$i,mao_de_obra));
				$total            = $custo_pecas + $mao_de_obra;
				$extrato          = trim(pg_result($res,$i,extrato));
				$exportado        = trim(pg_result($res,$i,exportado));
				$aprovado         = trim(pg_result($res,$i,aprovado));
				$data_geracao     = trim(pg_result($res,$i,data_geracao));
				$status_os        = trim(pg_result($res,$i,status_os));
				$observacao       = trim(pg_result($res,$i,observacao));
				$tipo_atendimento = trim(pg_result($res,$i,tipo_atendimento));
				$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
				$serie            = trim(pg_result($res,$i,serie));
				$os_reincidente   = trim(pg_result($res,$i,os_reincidente));
				$tipo_os = trim(pg_result($res,$i,tipo_os));

				#$cor = ($i % 2 == 0) ? " style='background-color: #F1F4FA;' " : " style='background-color: #F7F5F0;' " ;

				if ( ($login_fabrica == 19 AND $status_os == 13) OR
					 ($login_fabrica <> 19 AND $status_os == 13 AND strlen(trim($data_fechamento)) == 0) ) {
					$cor = " style='background-color: #FFE1E1;' ";
					$rowspan = "2";
				}else{
					$rowspan = "1";
				}

				if ($status_os == 14 AND strlen($extrato) == 0) {
					$cor = " style='background-color: #D7FFE1;' ";
				}
				if($login_fabrica ==30 AND $os_reincidente =='t') { // HD 50477
					$cor = " style='background-color: #D7FFE1;' ";
				}
				
				echo "<tr>";				
				echo "<td class='tac' $cor><a href='os_press.php?os=$os' target='_blank'>". $sua_os ."</a></td>";					
				echo "<td class='tac span2' $cor>" . $data_digitacao . "</td>";
				echo "<td class='tac span2' $cor>" . $data_abertura . "</td>";
				echo "<td class='tac span2' $cor>" . $data_fechamento . "</td>";
				echo "<td class='tac span2' $cor>" . $finalizada . "</td>";
				/*
				echo "<td nowrap align='center'><acronym title='Data de fechamento digitada: $data_fechamento' style='cursor: help;'>" . $finalizada . "</acronym></td>";
				*/
				echo "<td class='tar' $cor>" . number_format($total,2,",",".") . "</td>";
				echo "<td class='tac span2' $cor>" . $os . "</td>";
				if($login_fabrica == 30) { // HD 50477
					echo "<td class='tac span2' $cor>" . $nota_fiscal . "</td>";
					echo "<td class='tac span2' $cor>" . $serie . "</td>";
				}
				echo "<td class='tac  span2' $cor>";
				//echo "\n\n<!-- DATA GERACAO: ".strlen($data_geracao)."<BR> APROVADO: ".strlen($aprovado)."<BR> STATUS: ".strlen($status_os)." -->\n\n";


				if ($status == "00") {
					if  (strlen($data_geracao) >  0  AND strlen($aprovado) == 0)               
						echo traduz("Em aprovação");
					elseif ($status_os == 92) {
						echo traduz("Aguardando Aprovação");
					}elseif ($status_os == 93 and $tipo_atendimento==13) {
						echo traduz("Troca Aprovada");
					}elseif ($status_os == 94 and $tipo_atendimento==13) {
						echo traduz("Troca Recusada");
					}
					elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0) echo traduz("Finalizada");
					elseif ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0) echo traduz("Aprovada");					
					elseif ($login_fabrica == 20 AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen($exportado)>0)
					 echo traduz("Pagamento efetuado");
					elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0) echo traduz("Aprovada");
					elseif ($login_fabrica == 20 AND $status_os == 13)                     echo traduz("Recusada");
					elseif ($login_fabrica == 20 AND $status_os == 14)                       echo traduz("Acumulada");
					elseif ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)echo traduz("Recusada");
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0)                
						echo traduz("Recusada");
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0)                
					    echo traduz("Finalizada");
					elseif ($status_os == 14 AND strlen($extrato) == 0)                      echo traduz("Acumulada");
					elseif ($status_os == 15 AND strlen($extrato) == 0)                     echo traduz("Excluída");
					elseif ($login_fabrica == 20 AND strlen(trim($data_fechamento))>0 and strlen($extrato)==0)                                          
						echo trauz("Finalizada");
				}

				if ($status == "01") {
					if ($login_fabrica == 19 AND $status_os <> 13 AND strlen($aprovado) > 0)    echo traduz("Aprovada");
					elseif ($login_fabrica <> 19 AND strlen($aprovado) > 0 AND strlen($extrato) > 0) echo traduz("Aprovada");
				}
				elseif ($status == "13") {
					if ($login_fabrica == 19 AND $status_os == 13 AND strlen($extrato) > 0)echo traduz("Recusada");
					elseif ($login_fabrica <> 19 AND $status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0) 
						echo traduz("Recusada");
				}
				elseif ($status == "14") {
					if ($status_os == 14 AND strlen($extrato) == 0) 
						echo traduz("Acumulada");
				}
				elseif ($status == "15") {
					if ($status_os == 15 AND strlen($extrato) == 0)
					    echo traduz("Excluída");
				}

				if ( $status == 16) {
					if     (strlen($data_geracao) >  0  AND strlen($aprovado) == 0)
					    echo traduz("Em aprovação");
					elseif ($status_os == 92) {
						echo traduz("Aguardando Aprovação");
					}elseif ($status_os == 93 and $tipo_atendimento==13) {
						echo traduz("Troca Aprovada");
					}elseif ($status_os == 94 and $tipo_atendimento==13) {
						echo traduz("Troca Recusada");
					}
					elseif (strlen($data_geracao) == 0  AND strlen($aprovado) == 0 AND strlen($status_os) == 0 AND strlen(trim($data_fechamento)) <> 0) 
						echo traduz("Finalizada");
					elseif ($status_os == NULL AND strlen($aprovado) > 0 AND strlen($extrato) > 0 AND strlen(trim($data_fechamento)) > 0)
					    echo traduz("Finalizada");
					elseif ($status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) == 0)                							
						echo traduz("Recusada");
					elseif ($status_os == 13 AND strlen($extrato) == 0 AND strlen(trim($data_fechamento)) > 0)                 							
						echo traduz("Finalizada");
					elseif ($status_os == 14 AND strlen($extrato) == 0)                       echo traduz("Acumulada");
					elseif ($status_os == 15 AND strlen($extrato) == 0)                 
						echo traduz("Excluída");


				}

				if($status == 18)
				{
					echo traduz("Finalizada");
				}



				echo "</td>";

				if($login_fabrica == 1){
					echo "<td class='tac' $cor>$tipo_os</td>";
				}
				echo "</tr>";

				if (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tr>";
					echo "<td colspan='10' $cor><b>".traduz("Obs. Fábrica:")."</b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo "</table>";
			flush();
			echo "<br>";
			/*	echo "<img border='0' src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_relatorio.acao.value == '') { document.frm_relatorio.acao.value='GRAVAR'; document.frm_relatorio.submit(); }else{ alert('Aguarde submissão...'); } \" style='cursor: hand;'>";
			    echo "<br><br>";*/

			$achou = "sim";
		}else{
			$achou = "nao";
		}
	}

	//PESQUISA POR TODAS E/OU EXCLUÍDAS
	if ($status == "00" OR $status == "15") {
		$sql = "SELECT  tbl_os_excluida.codigo_posto                                        ,
						tbl_posto.nome                                      AS nome_posto   ,
						tbl_os_excluida.admin                                               ,
						tbl_os_excluida.sua_os                                              ,
						tbl_os_excluida.referencia_produto                                  ,
						tbl_os_excluida.serie                                               ,
						tbl_os_excluida.nota_fiscal                                         ,
						to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')       AS data_nf      ,
						to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY') AS data_exclusao,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os,
						(
							select tbl_os_status.observacao
							from tbl_os_status
							where tbl_os_status.os = tbl_os_excluida.os
							order by data desc limit 1
						)                                              AS observacao
				FROM    tbl_os_excluida
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.codigo_posto = tbl_os_excluida.codigo_posto
											AND tbl_posto_fabrica.fabrica      = $login_fabrica
				JOIN    tbl_posto            ON tbl_posto.posto                = tbl_os_excluida.posto
				JOIN    tbl_os ON tbl_os_excluida.os = tbl_os.os
				WHERE   tbl_os_excluida.fabrica = $login_fabrica
				AND     tbl_os_excluida.posto   = $posto
				AND tbl_os.data_digitacao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'
				AND tbl_os.excluida IS TRUE";




		$sql .= " ORDER BY tbl_os_excluida.data_exclusao;";
		#echo "SQL2:" . nl2br($sql);
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<br>";

			echo "<input type='hidden' name='qtde_os' value='" . pg_numrows($res) . "'>";

			if($login_fabrica==1){
			echo "<table '>";
					echo "<TR>";
						echo "<TD width='20' bgcolor='#FFE1E1'>&nbsp;</TD>";
						echo "<td> </td>";
						echo "<TD>".traduz("OSs excluidas pelo posto.")."</TD>";
					echo "</TR>";
					echo "</TABLE>";
				echo "<BR>";
			}

			echo "<table id='resultado_os_excluidas' class='table table-striped table-bordered table-hover table-fixed'>";
			echo "<thead>";
			echo "<tr class='titulo_tabela'>";
			echo "<th colspan='100'>".traduz("Relação de OS Excluídas")."</th>";
			echo "</tr>";


			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				/*echo "<tr class='subtitulo'>";
				echo "<td colspan='8'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";*/
			}

			echo "<tr class='titulo_tabela'>";
			echo "<td class='tac span1'>".traduz("OS")."</td>";
			echo "<td class='tac span2'>".traduz("Produto")."</td>";
			echo "<td class='tac span2'>".traduz("Série")."</td>";
			echo "<td class='tac span2'>".traduz("Nota Fiscal")."</td>";
			echo "<td class='tac span2'>".traduz("Data NF")."</td>";
			echo "<td class='tac span2'>".traduz("Data Exclusão")."</td>";
			echo "<td class='tac span2'>".traduz("Status")."</td>";
			if($login_fabrica==1) {
				echo "<td class='tac span2'>".traduz("Tipo OS")."</td>";
			}
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$produto        = trim(pg_result($res,$i,referencia_produto));
				$serie          = trim(pg_result($res,$i,serie));
				$nota_fiscal    = trim(pg_result($res,$i,nota_fiscal));
				$data_nf        = trim(pg_result($res,$i,data_nf));
				$data_exclusao  = trim(pg_result($res,$i,data_exclusao));
				$observacao     = trim(pg_result($res,$i,observacao));
				$admin          = trim(pg_result($res,$i,admin));
				$tipo_os          = trim(pg_result($res,$i,tipo_os));

				#$cor = ($i % 2 == 0) ? " style='background-color: #F1F4FA;' " : " style='background-color: #F7F5F0;' " ;

				if($login_fabrica==1){
					if (strlen($admin)==0) {
						$cor = " style='background-color: #FFE1E1;' ";
					}
				}else{
					if ($status == "00" OR $status == "15") {
						$cor = " style='background-color: #FFE1E1;' ";
					}
				}

				echo "<tr>";
				echo "<td class='tac' $cor>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
				//echo "<a href='os_consulta_excluida.php?os=$sua_os' target='_blank'>" . $sua_os . "</a>";
				echo "<a href='os_parametros_excluida.php' target='_blank'>" . $sua_os . "</a>";
				echo "</td>";
				echo "<td class='tal' $cor>" . $produto . "</td>";
				echo "<td class='tac' $cor>" . $serie . "</td>";
				echo "<td class='tac' $cor>" . $nota_fiscal . "</td>";
				echo "<td class='tac' $cor>" . $data_nf . "</td>";
				echo "<td class='tac' $cor>" . $data_exclusao . "</td>";
				echo "<td class='tac' $cor>".traduz("Excluída")."</td>";
				if($login_fabrica == 1){
					echo "<td class='tac' $cor>$tipo_os</td>";
				}
				echo "</tr>";
				if ($login_fabrica== 1 AND strlen($observacao) > 0) {
					echo "<tr>";
					echo "<td colspan='10' $cor><b>".traduz("Obs. Fábrica:")."</b>" . $observacao . "</td>";
					echo "</tr>";
				}
			}
			echo"</tbody>";
			echo "</table>";
			$achou2 = "sim";
		}else{
			$achou2 = "nao";
		}
	}


	##### OS NÃO FINALIZADAS (SOMENTE PESQUISA POR TODAS) #####
	if ($status == "00" OR $status == "17" ) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto                                   ,
						tbl_posto.nome                                 AS nome_posto     ,
						tbl_os.os                                                        ,
						tbl_os.sua_os                                                    ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')    AS data_digitacao ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')     AS data_abertura  ,
						tbl_os.pecas                                                     ,
						tbl_os.mao_de_obra                                               ,
						TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS aprovado       ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao   ,
						tbl_os.nota_fiscal                                               ,
						tbl_os.serie                                                     ,
						tbl_os.os_reincidente                                            ,
						CASE
							WHEN tbl_os.cortesia IS TRUE THEN
								'Cortesia'
							WHEN tbl_os.tipo_atendimento = 35 THEN
								'Troca cortesia'
							WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca consumidor'
							WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
								'Troca de revenda'
							WHEN tbl_os.consumidor_revenda = 'R' THEN
								'Revenda'
							ELSE
								'Consumidor'
						END AS tipo_os,
						(
							SELECT tbl_os_status.status_os
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS status_os      ,
						(
							SELECT tbl_os_status.observacao
							FROM tbl_os_status
							WHERE tbl_os_status.os = tbl_os.os
							ORDER BY data DESC LIMIT 1
						)                                              AS observacao
				FROM tbl_os
				JOIN tbl_os_extra USING (os)
				JOIN tbl_posto    USING (posto)
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato
				WHERE tbl_os.data_digitacao::date BETWEEN '$x_data_inicial' AND '$x_data_final'
				AND tbl_os.finalizada      ISNULL
				AND tbl_os.data_fechamento ISNULL
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_os.posto   = $posto";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<br />";
			echo "<br />";
			echo "<table id='resultado_os_nao_finalizadas' class='table table-striped table-bordered table-hover table-fixed'>";
			echo  "<thead>";
			echo "<tr class='titulo_tabela'>";
			echo "<th colspan='100'>".traduz("Relação de OS não Finalizadas")."</th>";
			echo "</tr>";

			if (strlen($posto) > 0) {
				$codigo_posto    = trim(pg_result($res,0,codigo_posto));
				$nome_posto      = trim(pg_result($res,0,nome_posto));

				/*echo "<tr class='subtitulo'>";
				echo "<td colspan='100%'>$codigo_posto - $nome_posto</td>";
				echo "</tr>";*/
			}


			echo "<tr class='titulo_coluna'>";
			echo "<th class='tac span1'>".traduz("OS")."</th>";
			echo "<th class='tac span2'>".traduz("Digitação")."</th>";
			echo "<th class='tac span2'>".traduz("Abertura")."</th>";
			echo "<th class='tac span2'>".traduz("Total")."</th>";
			echo "<th class='tac span2'>".traduz("Protocolo")."</th>";
			if($login_fabrica==30) { // HD 50477
				echo "<th class='tac span2'>".traduz("Nota Fiscal")."</th>";
				echo "<th class='tac span2'>".traduz("Série")."</th>";
			}
			echo "<th class='tac span2'>".traduz("Status")."</th>";
			if($login_fabrica==1) {
				echo "<th class='tac span2'>".traduz("Tipo OS")."</th>";
			}
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));
				$data_digitacao = trim(pg_result($res,$i,data_digitacao));
				$data_abertura  = trim(pg_result($res,$i,data_abertura));
				$pecas          = trim(pg_result($res,$i,pecas));
				$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
				$total          = $custo_pecas + $mao_de_obra;
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				$status_os       = trim(pg_result($res,$i,status_os));
				$observacao      = trim(pg_result($res,$i,observacao));
				$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
				$serie            = trim(pg_result($res,$i,serie));
				$os_reincidente   = trim(pg_result($res,$i,os_reincidente));
				$tipo_os   = trim(pg_result($res,$i,tipo_os));

				$cor = ($i % 2 == 0) ? " style='background-color: #F1F4FA;' " : " style='background-color: #F7F5F0;' " ;

				if ($status_os == 13) {
					$cor = " style='background-color: #FFE1E1;' ";
					$rowspan = "2";
				}else{
					$rowspan = "1";
				}

				if($login_fabrica == 30 AND $os_reincidente =='t') {
					$cor = " style='background-color: #D7FFE1;' ";
				}

				echo "<tr>";
				echo "<td class='tac' rowspan='$rowspan' $cor>";
				if ($login_fabrica == 1) {
					echo $codigo_posto;
				}
				if(in_array($login_fabrica, array(94))){
					echo "<a href='os_press.php?os=$os' target='_blank'>" . $sua_os . "</a>";	
				}else{
					echo "<a href='os_press.php?os=$sua_os' target='_blank'>" . $sua_os . "</a>";
				}
				echo "</td>";
				echo "<td class='tac' $cor>" . $data_digitacao . "</td>";
				echo "<td class='tac' $cor>" . $data_abertura . "</td>";
				echo "<td class='tar' $cor>" . number_format($total,2,",",".") . "</td>";
				echo "<td class='tac' $cor>" . $os . "</td>";
				if($login_fabrica == 30) { // HD 50477
					echo "<td class='tac' $cor>" . $nota_fiscal . "</td>";
					echo "<td class='tac' $cor>" . $serie . "</td>";
				}
				echo "<td class='tac' $cor>";
				if     (strlen($data_geracao) > 0  AND strlen($aprovado) == 0) echo traduz("Em aprovação");
				elseif (strlen($data_geracao) == 0 AND strlen($aprovado) == 0 AND strlen($status_os) == 0) echo traduz("Não finalizada");
			#	elseif (strlen($aprovado) > 0)                                                             echo "Aprovada";
				elseif ($status_os == 13 and  strlen($extrato) == 0)                                       echo traduz("Recusada");
				elseif ($status_os == 14 and  strlen($extrato) == 0)                                       echo traduz("Acumulada");
				elseif ($status_os == 15 and  strlen($extrato) == 0)                                       echo traduz("Excluída");
				else echo traduz("Aguardando Análise");
				echo "</td>";
				if($login_fabrica == 1){
					echo "<td class='tac' $cor>$tipo_os</td>";
				}
				echo "</tr>";

				if (strlen($aprovado) == 0 AND strlen($observacao) > 0 AND $status_os <> 14) {
					echo "<tfoot>";
					echo "<tr>";
					echo "<td colspan='100' $cor><b>".traduz("Obs. Fábrica:") ."</b>" . $observacao . "</td>";
					echo "</tr>";
					echo "</tfoot>";
				}
			}
			echo "</tbody>";
			echo "</table>";
			echo "<br>";
			echo "<br>";

			$achou3 = "sim";
		}else {
			$achou3 = "nao";
		}
	}
}

if ($achou == "nao" && $achou2 == "nao" && $achou3 == "nao" && $vazio == false && (!count($msg_erro["msg"]) > 0)) {
	echo "<div class='container'>";
	echo "<div class='alert'>";
	echo "<h4>".traduz("Nenhum resultado encontrado")."</h4>";
	echo "</div>";
	echo "</div>";
	
}

?> </div> <?

include "rodape.php";
?>