<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$data_inicial = trim($_REQUEST["data_inicial"]);
$data_final   = trim($_REQUEST["data_final"]);
$fabrica      = trim($_REQUEST["fabrica"]);
$tipo         = trim($_REQUEST["tipo"]);
$referencia   = trim ($_REQUEST['referencia']);
$descricao    = trim ($_REQUEST['descricao']);
$nota         = trim ($_REQUEST['nota']);
$serie        = trim ($_REQUEST['serie']);
$nota_acerto  = trim ($_REQUEST['nota_acerto']);
$serie_acerto = trim ($_REQUEST['serie_acerto']);
$busca        = trim ($_REQUEST['busca']);

$msg_erro = array();

if ($_REQUEST["consulta_faturamento_item"]) {
	echo "
	<html>
	<head>
	<title>Itens Divergentes das NFs</title>
	<link type='text/css' rel='stylesheet' href='css/css.css'>
	<style>
	.numero {
		text-align: right;
		padding-right: 5px;
	}
	</style>
	</head>

	<body>
	<table align='center' border='0' cellspacing='1' cellpaddin='1'>
	<table>
	";

	$faturamento_item = $_REQUEST["consulta_faturamento_item"];
	$sql = "
	SELECT
	TO_CHAR(data, 'DD/MM/YYYY HH24:MI') AS data,
	qtde_acerto,
	nota_fiscal,
	serie

	FROM
	tbl_faturamento_baixa_divergencia

	WHERE
	faturamento_item=$faturamento_item
	";
	$res = pg_query($con, $sql);

	$n = pg_num_rows($res);

	if ($n) {
			$sql = "
			SELECT
			tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_faturamento.nota_fiscal,
			tbl_faturamento.serie,
			tbl_faturamento_item.qtde_quebrada
			
			FROM
			tbl_peca
			JOIN tbl_faturamento_item ON tbl_faturamento_item.peca=tbl_peca.peca
			JOIN tbl_faturamento ON tbl_faturamento_item.faturamento=tbl_faturamento.faturamento
			
			WHERE
			tbl_faturamento_item.faturamento_item=$faturamento_item
			";
			$res_peca = pg_query($con, $sql);

			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res_peca, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);
			
			if (strlen($serie)) {
				$nota_fiscal .= "-" . $serie;
			}

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:15px' align='center'>";
			echo "<td colspan='4'>";
			echo "Detalhamento da Baixa de Itens<br>";
			echo "</td>";
			echo "</tr>";
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:12px' align='center'>";
			echo "<td colspan='4'>";
			echo "$referencia - $descricao<br>";
			echo "NF: $nota_fiscal - Qtde Divergente: $qtde_quebrada";
			echo "</td>";
			echo "</tr>";

			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";

			echo "<td>";
			echo "Data";
			echo "</td>";

			echo "<td>";
			echo "Qtde";
			echo "</td>";

			echo "<td>";
			echo "Nota";
			echo "</td>";

			echo "<td>";
			echo "Série";
			echo "</td>";

			echo "</tr>";

		for($i = 0; $i < $n; $i++) {
			$cor = "cccccc";
			if ($i % 2 == 0) $cor = '#eeeeee';

			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);

			echo "<tr bgcolor='$cor' style='font-size:11px'>";

			echo "<td>";
			echo $data;
			echo "</td>";

			echo "<td>";
			echo $qtde_acerto;
			echo "</td>";

			echo "<td>";
			echo $nota_fiscal."/".$emissao;
			echo "</td>";

			echo "<td>";
			echo $serie;
			echo "</td>";

			echo "</tr>";
		}
	}
	else {
	}

	echo "
	</table>
	</body>
	</html>
	";
	die;
}

if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
	$data_inicial = implode("-", array_reverse(explode("/", $data_inicial)));
	$data_final = implode("-", array_reverse(explode("/", $data_final)));

	$sql = "SELECT '$data_inicial'::date, '$data_final'::date";
	@$res = pg_query($con, $sql);
	if (pg_errormessage($con)) {
		$msg_erro[] = "Data informada inválida";
	}
	else {
		$sql = "SELECT 1 WHERE '$data_inicial'::date <= '$data_final'::date";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
		}
		else {
			$msg_erro[] = "A data final deve ser menor ou igual a data inicial";
		}
	}
}

if (strlen($fabrica) > 0) {
	$fabrica = intval($fabrica);
	$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica=$fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$fabrica_nome = pg_result($res, 0, nome);

		if (strlen($referencia) > 0) {
			$sql = "SELECT peca, descricao FROM tbl_peca WHERE referencia='$referencia' AND fabrica=$fabrica";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$peca = pg_result($res, 0, peca);
				$descricao = pg_result($res, 0, descricao);
			}
			else {
				$msg_erro[] = "Referência da peça informada inválida";
			}
		}
		else {
			$descricao = "";
		}
	}
}elseif (isset($_GET["fabrica"])) {
	$msg_erro[] = "Informe a fábrica para gerar o relatório";
}

if (strlen($tipo) > 0) {
	$tipos_possiveis = array("todas", "aberto", "parcial", "total");
	if (in_array($tipo, $tipos_possiveis)) {
	}
	else {
		$tipo = "todas";
	}
}else{
	$tipo = "aberto";
}


if (strlen($nota) > 0 && count($msg_erro) == 0) {
	if (strlen($serie) > 0) {
		$sqlSerie = "AND tbl_faturamento.serie='$serie'";
	}else {
		$sqlSerie = "";
	}

	$sql = "
	SELECT
	faturamento

	FROM
	tbl_faturamento

	WHERE
	fabrica=$fabrica or fabrica = 10
	AND nota_fiscal='$nota'
	$sqlSerie
	";
	$res = pg_query($con, $sql);
	
	if (pg_num_rows($res) == 0) {
		$msg_erro[] = "Nota Fiscal/Série não encontrada";
	}
}else {
	$serie = "";
}

if (strlen($serie_acerto) && strlen($nota_acerto) == 0) {
	$serie_acerto = "";
}

/* REGRAS DE FILTRAGEM PARA NÃO SOBRECARREGAR O BANCO DE DADOS */
if (isset($_REQUEST["btn_acao"])) {
	if (strlen($peca) == 0 && strlen($nota) == 0) {
		if ($tipo == "aberto" || $tipo == "parcial") {
		}
		else {
			if (strlen($data_inicial) == 0 || strlen($data_final) == 0) {
				//$msg_erro[] = "Para busca com status <b>Todas</b> ou <b>Baixado Total</b> é obrigatório informar Data Inicial e Data Final";
			}
		}
	}
}



if(trim($_POST['btnacao']) == 'Gravar'){
	$qtde_itens = $_POST['qtde_itens'];
	pg_query ($con,"BEGIN TRANSACTION");

	for($i = 0; $i < $qtde_itens; $i++){
		$quebrada 			= intval($_POST["quebrada{$i}"]);
		$serie 				= $_POST["serie_{$i}"];
		$nota_fiscal 		= $_POST["nota_fiscal_{$i}"];
		$faturamento_item 	= intval($_POST["faturamento_item_{$i}"]);
		$baixa_manual 		= intval($_POST["baixa_manual_{$i}"]);
		$x_referencia 		= $_POST["referencia_{$i}"];

		if($baixa_manual > 0){
			$erro = false;
			if($baixa_manual > $quebrada){
				$msg_error[] = "Peça '{$x_referencia}' baixa manual é maior que a quantidade divergente!";
				$erro = true;
			}

			if(!$erro){
				$sql = "INSERT INTO tbl_faturamento_baixa_divergencia 
						(faturamento_item,qtde_acerto,nota_fiscal,serie,data)
					VALUES 
						($faturamento_item,$baixa_manual,$nota_fiscal,$serie,now());";

				if(!pg_query($con, $sql)){
					$msg_error[] = "Erro ao gravar dados!<erro>{pg_last_error($con)}</erro>";
				}else{
					unset($_POST["baixa_manual_{$i}"]);
					$sucesso = "Dados baixados com sucesso!";
				}
			}
		}
	}

	if(count($msg_error) == 0 ){
		//pg_query ($con,"ROLLBACK TRANSACTION");
		pg_query ($con,"COMMIT TRANSACTION");
	}else{
		
		pg_query ($con,"ROLLBACK TRANSACTION");
		$sucesso = null;
	}
}


$msg_erro = implode("<br>", $msg_erro);

if ($_REQUEST["formato"] == "xls" && strlen($msg_erro) == 0)  {
	ob_start();
}else {
	?>

	<html>
	<head>
	<title>Itens Divergentes das NFs</title>
	<link type="text/css" rel="stylesheet" href="css/css.css">
	<style>
	.numero {
		text-align: right;
		padding-right: 5px;
	}
	</style>
	</head>

	<body>

	<? include 'menu.php' ?>

	<? include "javascript_calendario_new.php"; ?>
	<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
	<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
	<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
	<script type='text/javascript' src='js/dimensions.js'></script>
	<script type="text/javascript" src="js/thickbox.js"></script>
	<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
	<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

	<script language="JavaScript">

	function verificaNumero(e) {
	    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
	        return false;
	    }
	}

	$(document).ready(function() {
		 $(".baixa_manual").keypress(verificaNumero);
	});

	$(function() {
		function formatItem(row) {
			return row[0] + " - " + row[1] + " - " + row[2];
		}

		function formatResult(row) {
			return row[0];
		}

		$("#descricao").autocomplete("<?echo 'peca_consulta_ajax.php?busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#descricao").result(function(event, data, formatted) {
			$("#referencia").val(data[1]) ;
			$("#descricao").val(data[2]) ;
		});

		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});

	function baixar_divergencia(faturamento_item, linha) {
		var qtde = $("#qtde" + linha).val();
		var nota = $("#nota" + linha).val();
		var serie = $("#serie" + linha).val();
		var qtde_quebrada = parseInt($("#quebrada" + linha).val());
		var qtde_baixado = parseInt($("#baixado" + linha).val());

		if (qtde == "") {
			alert("Digite a quantidade para ser baixada");
			$("#qtde" + linha).focus();
			return false;
		}
		else {
			qtde = parseInt(qtde);

			if (qtde <= 0) {
				alert("A quantidade deve ser um número positivo");
				$("#qtde" + linha).focus();
				return false;
			}
			else if (qtde > qtde_quebrada - qtde_baixado) {
				alert("A quantidade restante a ser baixada para este item é de no máximo " + (qtde_quebrada - qtde_baixado));
				$("#qtde" + linha).val(qtde_quebrada - qtde_baixado);
				$("#qtde" + linha).focus();
				return false;
			}
			else if (nota == "") {
				if (confirm("Efetuar a baixa sem o número da nota fiscal?")) {
				}
				else {
					$("#nota" + linha).focus();
					return false;
				}
			}

			$("#qtde"+linha).attr("disabled", true);
			$("#nota"+linha).attr("disabled", true);
			$("#serie"+linha).attr("disabled", true);
			$("#btn_ok"+linha).attr("disabled", true);

			var url = "nf_divergente_ajax.php?acao=baixar&faturamento_item=" + faturamento_item + "&qtde=" + qtde + "&nota=" + nota + "&serie=" + serie;
			requisicao = requisicaoHTTP("GET", url, true, "baixar_divergencia_retorno", linha);

			if (requisicao) {
			}
			else {
				$("#qtde"+linha).attr("disabled", false);
				$("#nota"+linha).attr("disabled", false);
				$("#serie"+linha).attr("disabled", false);
				$("#btn_ok"+linha).attr("disabled", false);
			}
		}
	}

	function baixar_divergencia_retorno(retorno, linha) {
		retorno = retorno.split('|');
		acao = retorno[0];
		status_pos = retorno[1];
		mensagem = retorno[2];
		total = retorno[3];

		switch(status_pos) {
			case "sucesso":
				$("#div_baixado" + linha).html(mensagem);
				$("#baixado" + linha).val(mensagem);
				$("#qtde"+linha).val("");
				$("#nota"+linha).val("");
				$("#serie"+linha).val("");

				if (total == "total") {
					$("#tdqtde"+linha).html("");
					$("#tdnota"+linha).html("");
					$("#tdserie"+linha).html("");
					$("#tdbtn_ok"+linha).html("");
				}

				alert("Divergência baixada com sucesso!");
			break;

			case "erro":
				alert(mensagem);
			break;

			default:
				alert("Ocorreu um erro no sistema, contate o HelpDesk");
		}

		$("#qtde"+linha).attr("disabled", false);
		$("#nota"+linha).attr("disabled", false);
		$("#serie"+linha).attr("disabled", false);
		$("#btn_ok"+linha).attr("disabled", false);
	}

	</script>

	<center><h1>Itens Divergentes das NFs</h1></center>

	<p>

	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='get'>
	<table>
		<?

		if (strlen($msg_erro) > 0) {
			echo "<div style='color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div>";
		}

		if(count($msg_error) > 0){
			$msg_error = implode('<br />', $msg_error);
			echo "<div style='color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_error</div>";
		}

		if(!empty($sucesso)){
			echo "<div style='color: #618F26; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>{$sucesso}</div>";
		}

		?>
		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<? echo $_REQUEST["data_inicial"]; ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<? echo $_REQUEST["data_final"]; ?>"></td>
		</tr>
		<tr>
			<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
			$res = pg_exec($con,$sql);
			echo "<select style='width:150px;' name='fabrica' id='fabrica' class='frm'>";
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_result($res,$x,fabrica);
						$aux_nome    = pg_result($res,$x,nome);
						echo "$fabrica.$aux_fabrica";
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica || (strlen($fabrica)==0 && $aux_fabrica==51)) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
			<td align='right'>Status</td>
			<td>
			<select name="tipo" id="tipo" class='frm'>
				<option <? if ($tipo == "todas") echo "selected"; ?> value="todas">Todas</option>
				<option <? if ($tipo == "aberto" || $tipo == "") echo "selected"; ?> value="aberto">Em Aberto</option>
				<option <? if ($tipo == "parcial") echo "selected"; ?> value="parcial">Baixado Parcial</option>
				<option <? if ($tipo == "total") echo "selected"; ?> value="total">Baixado Total</option>
			</select>
			</td>
		</tr>
		<tr>
			<td align='right'>Referência da Peça</td>
			<td><input type='text' size='10' name='referencia' id='referencia' class="frm" value="<? echo $referencia; ?>"></td>
			<td align='right'>Descrição da Peça</td>
			<td><input type='text' size='20' name='descricao'   id='descricao' class="frm" value="<? echo $descricao; ?>"></td>
		</tr>
		<tr>
			<td align='right'>Número da NF de Entrada</td>
			<td><input type='text' size='20' maxlength='20' name='nota' id='nota' class="frm" value="<? echo $nota; ?>"></td>
			<td align='right'>Série da NF de Entrada</td>
			<td><input type='text' size='4' maxlength='3' name='serie' id='serie' class="frm" value="<? echo $serie; ?>"></td>
		</tr>
		<tr>
			<td align='right'>Número da NF de Acerto</td>
			<td><input type='text' size='20' maxlength='20' name='nota_acerto' id='nota_acerto' class="frm" value="<? echo $nota_acerto; ?>"></td>
			<td align='right'>Série da NF de Acerto</td>
			<td><input type='text' size='4' maxlength='3' name='serie_acerto' id='serie_acerto' class="frm" value="<? echo $serie; ?>"></td>
		</tr>
		<tr>
			<td align='center' colspan='4'><input type='checkbox' name='formato' id='formato' value='xls' <? if ($_REQUEST["formato"]) echo "checked"; ?>> Gerar relatório para Excel (XLS)</td>
		</tr>
		<tr>
			<td align='center' colspan='4'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br>
	</form>

	<?
}	//ELSE de if ($_GET["formato"] == "xls")

if(strlen($_REQUEST["btn_acao"])==0){
	//$_REQUEST["btn_acao"] = "Pesquisar";
	$tipo = "aberto";
	$fabrica = 51;
}
if ($_REQUEST["btn_acao"] == "Pesquisar" && strlen($msg_erro) == 0) {

	if (strlen($data_inicial)) {
		$sql_data_inicial = "AND tbl_faturamento.emissao >= '$data_inicial'::date";
	}

	if (strlen($data_final)) {
		$sql_data_final = "AND tbl_faturamento.emissao <= '$data_final'::date";
	}

	switch($tipo) {
		case "todas":
			$sql_status = "";
		break;

		case "aberto":
			$sql_status = "HAVING SUM(tbl_faturamento_baixa_divergencia.qtde_acerto) IS NULL";
		break;
		
		case "parcial":
			$sql_status = "HAVING SUM(tbl_faturamento_baixa_divergencia.qtde_acerto) > tbl_faturamento_item.qtde_quebrada";
		break;
		
		case "total":
			$sql_status = "HAVING SUM(tbl_faturamento_baixa_divergencia.qtde_acerto) <= tbl_faturamento_item.qtde_quebrada";
		break;
		
		default:
			$sql_status = "";
	}

	if (strlen($peca)) {
		$sql_peca = "AND tbl_faturamento_item.peca=$peca";
	}

	if (strlen($nota)) {
		$sql_nota = "AND tbl_faturamento.nota_fiscal='$nota'";
	}

	if (strlen($serie)) {
		$sql_serie = "AND tbl_faturamento.serie='$serie'";
	}

	if (strlen($nota_acerto)) {
		$sql_nota_acerto = "AND tbl_faturamento_baixa_divergencia.nota_fiscal='$nota_acerto'";
	}

	if (strlen($serie_acerto)) {
		$sql_serie_acerto = "AND tbl_faturamento_baixa_divergencia.serie='$serie_acerto'";
	}

	$sql = "
	SELECT
	tbl_faturamento_item.faturamento_item,
	SUM(tbl_faturamento_baixa_divergencia.qtde_acerto) AS qtde_baixado
	
	FROM
	tbl_faturamento
	JOIN tbl_faturamento_item ON tbl_faturamento.faturamento=tbl_faturamento_item.faturamento
	JOIN tbl_peca ON tbl_faturamento_item.peca=tbl_peca.peca
	LEFT JOIN tbl_faturamento_baixa_divergencia ON tbl_faturamento_item.faturamento_item=tbl_faturamento_baixa_divergencia.faturamento_item
	
	WHERE
	tbl_peca.fabrica=$fabrica
	AND tbl_faturamento.fabrica IN (10, $fabrica)
	AND tbl_faturamento.posto = 4311
	AND tbl_faturamento_item.qtde_quebrada > 0
	$sql_data_inicial
	$sql_data_final
	$sql_peca
	$sql_nota
	$sql_serie
	$sql_nota_acerto
	$sql_serie_acerto

	GROUP BY
	tbl_faturamento_item.faturamento_item,
	tbl_peca.referencia,
	tbl_faturamento.emissao,
	tbl_faturamento_item.qtde_quebrada

	$sql_status

	ORDER BY
	tbl_faturamento.emissao ASC,
	tbl_peca.referencia ASC
	";
	//echo nl2br($sql);
	$res_consulta = pg_query($con, $sql);

	echo "<form action='{$_SERVER["PHP_SELF"]}' name='baixa_manual' method='POST'>";
		echo "<table align='center' border='0' cellspacing='1' cellpaddin='1'>";
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:13px' align='center'>";
				echo "<td nowrap>Referência</td>";
				echo "<td nowrap>Descrição</td>";
				echo "<td style='width:70px;'>Nota Fiscal</td>";
				echo "<td style='width:70px;'>Qtde Divergente</td>";
				echo "<td style='width:70px;'>Qtde Baixado</td>";
				echo "<td style='width:70px;'>Baixar Manual</td>";
				//echo "<td style='width:70px;'>Qtde Acerto</td>";
				//echo "<td style='width:70px;'>NF Acerto</td>";
				//echo "<td style='width:70px;'>Série NF Acerto</td>";
				//echo "<td></td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_numrows($res_consulta) ; $i++) {
				//Recupera os valores do resultado da consulta
				$valores_linha = pg_fetch_array($res_consulta, $i);

				//Transforma os resultados recuperados de array para variáveis
				extract($valores_linha);
				
				$sql = "
				SELECT
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_faturamento.faturamento,
				TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
				tbl_faturamento.nota_fiscal,
				TO_CHAR(emissao, 'DD/MM/YYYY') AS emissao,
				tbl_faturamento.serie,
				tbl_faturamento_item.qtde,
				tbl_faturamento_item.qtde_estoque,
				tbl_faturamento_item.qtde_quebrada
				
				FROM
				tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento.faturamento=tbl_faturamento_item.faturamento
				JOIN tbl_peca ON tbl_faturamento_item.peca=tbl_peca.peca
				LEFT JOIN tbl_faturamento_baixa_divergencia ON tbl_faturamento_item.faturamento_item=tbl_faturamento_baixa_divergencia.faturamento_item
				
				WHERE
				tbl_faturamento_item.faturamento_item=$faturamento_item
				";
				$res_dados = pg_query($con, $sql);

				//Recupera os valores do resultado da consulta
				$valores_linha = pg_fetch_array($res_dados, 0);

				//Transforma os resultados recuperados de array para variáveis
				extract($valores_linha);

				$cor = "cccccc";
				if ($i % 2 == 0) $cor = '#eeeeee';

				echo "<tr bgcolor='$cor' style='font-size:11px'>";

				echo "<td align='left'>";
				echo "<input type='hidden' name='referencia_$i' value='$referencia'>";
				echo $referencia;
				echo "</td>";

				echo "<td align='left'>";
				echo $descricao;
				echo "</td>";
				
				echo "<td align='right' nowrap>";
				echo $nota_fiscal."-".$emissao;
				echo "</td>";
				
				echo "<td align='left' class='numero'>";
				echo $qtde_quebrada;
				echo "<input type='hidden' id='quebrada$i' name='quebrada$i' value='$qtde_quebrada'>";
				echo "</td>";
				
				if (strlen($qtde_baixado) == 0) {
					$qtde_baixado = 0;
				}

				if ($qtde_baixado > 0) {
					$link_abre = "<a href=\"javascript:function abre_consulta_item_$i(){ window.open('$PHP_SELF?consulta_faturamento_item=$faturamento_item', 'detalhe_item_$i', 'status=0, toolbar=0, location=0, menubar=0, directories=0, resizable=0, scrollbars=0, height=300, width=300'); } abre_consulta_item_$i()\">";
					$link_fecha = "</a>";
				}
				else {
					$link_abre = "";
					$link_fecha = "";
				}

				echo "<td align='left' class='numero'>";
					echo "$link_abre<div id='div_baixado$i' name='div_baixado$i' style='display:inline;'>$qtde_baixado</div>$link_fecha";
					echo "<input type='hidden' id='baixado$i' name='baixado$i' value='$qtde_baixado'>";
				echo "</td>";
				/*
				echo "<td align='center' id='tdqtde$i'>";
				if ($qtde_baixado < $qtde_quebrada) {
					echo "<input type='text' size='7' id='qtde$i' name='qtde$i' class='frm'>";
				}
				echo "</td>";
				
				echo "<td align='center' id='tdnota$i'>";
				if ($qtde_baixado < $qtde_quebrada) {
					echo "<input type='text' size='7' id='nota$i' name='nota$i' class='frm'>";
				}
				echo "</td>";

				echo "<td align='center' id='tdserie$i'>";
				if ($qtde_baixado < $qtde_quebrada) {
					echo "<input type='text' size='7' id='serie$i' name='serie$i' class='frm'>";
				}
				echo "</td>";

				echo "<td align='center' id='tdbtn_ok$i'>";
				if ($qtde_baixado < $qtde_quebrada) {
					echo "<input type='button' id='btn_ok$i' name='btn_ok$i' value='OK' class='frm' onclick='baixar_divergencia($faturamento_item, $i)'>";
				}
				echo "</td>";
				  */
					echo "<td align='center'>";
						echo "<input type='hidden' name='serie_{$i}' value='{$serie}' />";
						echo "<input type='hidden' name='nota_fiscal_{$i}' value='{$nota_fiscal}' />";
						echo "<input type='hidden' name='faturamento_item_{$i}' value='{$faturamento_item}' />";
						$baixa_manual = intval($_POST["baixa_manual_{$i}"]);
						echo "<input type='text' name='baixa_manual_{$i}' value='{$baixa_manual}' class='frm baixa_manual' style='width: 50px; font-weight normal;' />";
					echo "</td>";
				echo "</tr>";
			}

			echo "<tr>";
				echo "<td colspan='6' align='center' style='padding: 20px'>";
					echo "<input type='hidden' name='data_inicial' value='{$_REQUEST["data_inicial"]}' />";
					echo "<input type='hidden' name='data_final' value='{$_REQUEST["data_final"]}' />";
					echo "<input type='hidden' name='fabrica' value='{$_REQUEST["fabrica"]}' />";
					echo "<input type='hidden' name='tipo' value='{$_REQUEST["tipo"]}' />";
					echo "<input type='hidden' name='referencia' value='{$_REQUEST["referencia"]}' />";
					echo "<input type='hidden' name='descricao' value='{$_REQUEST["descricao"]}' />";
					echo "<input type='hidden' name='nota' value='{$_REQUEST["nota"]}' />";
					echo "<input type='hidden' name='serie' value='{$_REQUEST["serie"]}' />";
					echo "<input type='hidden' name='nota_acerto' value='{$_REQUEST["nota_acerto"]}' />";
					echo "<input type='hidden' name='serie_acerto' value='{$_REQUEST["serie_acerto"]}' />";
					echo "<input type='hidden' name='busca' value='{$_REQUEST["busca"]}' />";
					echo "<input type='hidden' name='consulta_faturamento_item' value='{$_REQUEST["consulta_faturamento_item"]}' />";
					echo "<input type='hidden' name='formato' value='{$_REQUEST["formato"]}' />";
					echo "<input type='hidden' name='btn_acao' value='{$_REQUEST["btn_acao"]}' />";
					echo "<input type='hidden' name='qtde_itens' value='{$i}' />";
					echo "<input type='submit' name='btnacao' value=' Gravar ' />";
				echo "</td>";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
}

if ($_REQUEST["formato"] == "xls" && strlen($msg_erro) == 0) {
	$hora = time();
	$xls = "xls/estoque_previsao_".$login_posto."_data_".$hora.".xls";
	$arquivo = fopen($xls, "w");
	$saida = ob_get_clean();
	fwrite($arquivo, $saida);
	fclose($arquivo);
	 #echo "<input type='button' value='Download em Excel' onclick=' window.location=\"".$xls."\" '>";
	//Redireciona a saida da tela, que estava em buffer, para a variÃ¡vel
	header("location:$xls");
}

include "rodape.php";
?>
