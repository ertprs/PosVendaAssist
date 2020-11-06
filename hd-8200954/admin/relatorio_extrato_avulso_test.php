<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

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
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if(strlen($_GET['lancamento']) > 0){
	$lancamento = $_GET['lancamento'];

	$sql = " INSERT INTO tbl_extrato_lancamento_excluido (
				extrato_lancamento,
				posto             ,
				fabrica           ,
				lancamento        ,
				descricao         ,
				debito_credito    ,
				historico         ,
				valor             ,
				competencia_futura,
				data_lancamento   ,
				data_exclusao     ,
				admin    
			) SELECT extrato_lancamento,
					 posto             ,
					 fabrica           ,
					 lancamento        ,
					 descricao         ,
					 debito_credito    ,
					 historico         ,
					 valor             ,
					 competencia_futura,
					 data_lancamento   ,
					 current_timestamp ,
					 $login_admin    
			FROM tbl_extrato_lancamento
			WHERE extrato_lancamento = $lancamento; 

			UPDATE tbl_extrato_lancamento set fabrica=0 WHERE extrato_lancamento= $lancamento;
			";
	$res = pg_exec($con,$sql);
	echo (strlen(pg_errormessage($con)) == 0) ? "OK" : "Erro";
	exit;
}

if($_GET['ajax']=='sim') {

	if (strlen($_GET["data_inicial_01"]) == 0)$erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $erro .= "Favor informar a data final para pesquisa<br>";

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_GET["data_final_01"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	$tipo = $_GET['tipo'];
	$cond_1 = " 1=1 ";
	if(strlen($tipo)>0){
		$cond_1 = " tbl_extrato_lancamento.descricao = '$tipo' ";
	}

	$codigo_posto = $_GET["codigo_posto"];
	$cond_2 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$cond_2 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	$tipo_lancamento = $_GET["tipo_lancamento"] ;
	if($tipo_lancamento == 'lancamento_excluido') {
		$excluido = "_excluido" ;
		$sql_join = "";
		$sql_valor = ",TO_CHAR(tbl_extrato_lancamento_excluido.data_exclusao,'DD/MM/YY') AS data_exclusao ";
	}else{
		$sql_join = " LEFT JOIN tbl_extrato USING (extrato) LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato ";
		$sql_valor = ",tbl_extrato.extrato, TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,					tbl_extrato.protocolo ";
	}

	$cond_3 = ($tipo_lancamento =='sem_extrato') ? " AND tbl_extrato_lancamento.extrato IS NULL " : "";

	$tipo_lancamento = (!in_array($tipo_lancamento,array('sem_extrato','lancamento_excluido'))) ? "" : $tipo_lancamento;

	if (strlen($erro) > 0) {
		$data_inicial    = trim($_GET["data_inicial_01"]);
		$data_final      = trim($_GET["data_final_01"]);
		$codigo_posto    = trim($_GET["codigo_posto"]);
		$tipo            = trim($_GET["tipo"]);
		$tipo_lancamento = trim($_GET["tipo_lancamento"]);

		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;
		
	}else $listar = "ok";

	if ($listar == "ok") {
		$cond_1 = "1=1";

		$tipo_data = " tbl_extrato_lancamento$excluido.data_lancamento ";

		if($login_fabrica <> 3 AND $login_fabrica <> 51){
			if($login_fabrica == 1){
				$sql = "SELECT tbl_posto_fabrica.codigo_posto                                         ,
						tbl_posto.nome                                                                ,
						tbl_extrato.extrato                                                           ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao                ,
						tbl_extrato.protocolo                                                         ,
						tbl_extrato_lancamento.valor                                                  ,
						tbl_extrato_lancamento.descricao                                              ,
						tbl_extrato_lancamento.extrato_lancamento                                     ,
						TO_CHAR(tbl_extrato_lancamento.data_lancamento,'DD/MM/YY') AS data_lancamento ,
						tbl_extrato_financeiro.data_envio
					FROM tbl_extrato
					JOIN tbl_extrato_financeiro USING (extrato)
					JOIN tbl_extrato_lancamento USING (extrato)
					JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto 
					JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato 
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato_financeiro.data_envio IS NOT NULL 
					AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND $cond_1 
					AND $cond_2
					ORDER BY tbl_posto_fabrica.codigo_posto";
			}else{
				$sql = "SELECT tbl_posto_fabrica.codigo_posto                           ,
						tbl_posto.nome                                                  ,
						tbl_extrato.extrato                                             ,
						TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
						tbl_extrato.protocolo                                           ,
						tbl_extrato_lancamento.valor                                    ,
						tbl_extrato_lancamento.descricao                                ,
						tbl_extrato_lancamento.extrato_lancamento                       ,
						TO_CHAR(tbl_extrato_lancamento.data_lancamento,'DD/MM/YY') AS data_lancamento
					FROM tbl_extrato 
					JOIN tbl_extrato_lancamento USING (extrato)
					JOIN tbl_posto         ON tbl_extrato.posto = tbl_posto.posto 
					JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND $cond_1
					AND $cond_2
					ORDER BY tbl_posto_fabrica.codigo_posto";
			}
		}else{

			$sql = "SELECT tbl_posto_fabrica.codigo_posto                           ,
					tbl_posto.nome                                                  ,
					tbl_admin.login                                                 ,
					tbl_extrato_lancamento$excluido.valor                                    ,
					tbl_extrato_lancamento$excluido.descricao                                ,
					tbl_extrato_lancamento$excluido.historico                                ,
					tbl_extrato_lancamento$excluido.extrato_lancamento                       ,
					TO_CHAR(tbl_extrato_lancamento$excluido.data_lancamento,'DD/MM/YY') AS data_lancamento,
					TO_CHAR(tbl_extrato_lancamento$excluido.competencia_futura,'MM/YY') AS competencia_futura
					$sql_valor
				FROM tbl_extrato_lancamento$excluido 
				$sql_join
				JOIN tbl_posto         ON tbl_extrato_lancamento$excluido.posto = tbl_posto.posto 
				JOIN tbl_posto_fabrica ON tbl_extrato_lancamento$excluido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin         ON tbl_admin.admin = tbl_extrato_lancamento$excluido.admin
				WHERE tbl_extrato_lancamento$excluido.fabrica = $login_fabrica
				AND $tipo_data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND tbl_extrato_lancamento$excluido.admin is not null 
				AND $cond_1
				AND $cond_2
				$cond_3 
				ORDER BY tbl_posto_fabrica.codigo_posto";
		}

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			$resposta  .=  "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final <br>";
			$resposta  .=  ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : "");
			$resposta  .=  "</b>";

			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			$resposta  .=  "<TR class='Titulo'  background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<th>Posto</th>";
			$resposta  .=  (strlen($tipo_lancamento) == 0) ? "<th>Extrato</th>" : "";
			$resposta  .=  (strlen($tipo_lancamento) == 0) ? "<th>Dt. Extrato</th>" : "";
			$resposta  .=  "<th>Valor</th>";
			$resposta  .=  ($login_fabrica ==3) ? "<th>Ação</th>" : "";
			$resposta  .=  "<th>Data</th>";
			$resposta  .=  ($tipo_lancamento =='lancamento_excluido') ? "<th>Data Exclusão</th>" : "";
			$resposta  .=  "<th>Descricao</th>";
			$resposta  .=  ($login_fabrica == 3) ? "<th>Competência<br>Futura</th>" : "";
			$resposta  .=  ($login_fabrica == 3) ? "<th>Admin</th>" : "";
			$resposta  .=  "</TR>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto))   ;
				$nome            = trim(pg_result($res,$i,nome))           ;
				$descricao       = trim(pg_result($res,$i,descricao))      ;
				$valor           = trim(pg_result($res,$i,valor))          ;
				if($tipo_lancamento <> 'lancamento_excluido') {
					$extrato         = trim(pg_result($res,$i,extrato))        ;
					$data_geracao    = trim(pg_result($res,$i,data_geracao))   ;
					$protocolo       = trim(pg_result($res,$i,protocolo))      ;
				}else{
					$data_exclusao   = trim(pg_result($res,$i,data_exclusao))  ;
				}
				$data_lancamento    = trim(pg_result($res,$i,data_lancamento));
				$extrato_lancamento = trim(pg_result($res,$i,extrato_lancamento));
				if($login_fabrica == 3){
					$admin_login        = trim(pg_result($res,$i,login))      ;
					$historico          = trim(pg_result($res,$i,historico))  ;
					$competencia_futura = trim(pg_result($res,$i,competencia_futura))  ;
				}

				if($login_fabrica == 1) $extrato = $protocolo;

				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
	
				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='left'nowrap>$codigo_posto - $nome</TD>";
				if(strlen($tipo_lancamento) == 0) {
					$resposta  .=  "<TD align='center'>";
					$resposta  .= (strlen($extrato) > 0) ? "$extrato" : "-";
					$resposta  .= "</TD>";
					$resposta  .= "<TD align='center'>";
					$resposta  .= (strlen($data_geracao) > 0) ? "$data_geracao": "-";
					$resposta  .= "</TD>";
				}
				$resposta  .=  "<TD >R$". number_format($valor,2,",",".") ." </TD>";
				if($login_fabrica == 3) {
					$resposta  .=  (strlen($extrato) == 0 and $tipo_lancamento <>'lancamento_excluido') ? "<TD id='extrato_$extrato_lancamento'><a href=\"javascript: excluirLancamento('$extrato_lancamento','extrato_$extrato_lancamento')\" ><img src='imagens/btn_x.gif'  border='0'></a></TD>" : "<td></td>";
				}
				$resposta  .=  "<TD align='center'>$data_lancamento</TD>";
				$resposta  .=  ($tipo_lancamento =='lancamento_excluido') ? "<TD align='center'>$data_exclusao</TD>" : "";
				$resposta  .=  "<TD align='right'>$descricao</TD>";
				if($login_fabrica == 3){
					$resposta  .=  "<TD align='center'>$competencia_futura</TD>";
					$resposta  .=  "<TD align='right'>$admin_login</TD>";
				}
				$resposta  .=  "</TR>";

				if($login_fabrica == 3){
					$resposta  .=  "<TR  bgcolor='$cor'>";
					$resposta  .=  "<td colspan='100%' style='text-align:left;padding-left:10px;padding-right:10px' align='top'>";
					$resposta  .=  "<I>Histórico: $historico</I>";
					$resposta  .=  "</TD>";
					$resposta  .=  "</TR>";
				}

				$total = $valor + $total;

			}
			$resposta .=  "<tfoot><tr class='Conteudo' bgcolor='#d9e2ef'><td colspan='4'><font size='2'><b><CENTER>VALOR TOTAL DE AVULSO</b></td><td colspan='50%'><font size='2' color='009900'><b>R$". number_format($total,2,",",".") ." </b></td></tr></tfoot>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";

			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
			$linha        = trim($_POST["linha"]);
			$estado       = trim($_POST["estado"]);
			$criterio     = trim($_POST["criterio"]);
		}else{
			$resposta .= "<br>";
			$resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final <br>";
			$resposta .= ($tipo_lancamento=='lancamento_excluido') ? "Lançamento Excluído" : (($tipo_lancamento=='sem_extrato') ? "Lançamento em nenhum extrato" : "");
			$resposta .= "</b>";
		}
		$listar = "";
	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;
	}else{
		echo "ok|".$resposta;
	}
	exit;

	flush();
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE EXTRATOS AVULSOS LANÇADOS";

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>

<? include "javascript_calendario_new.php"; ?>

<script language="javascript" src="js/effects.explode.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.alerts.css" />
<script language="javascript" src="js/jquery.alerts.js"></script>
<script language='javascript'>

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var4 = document.frm_relatorio.tipo.value;
	var var5 = document.frm_relatorio.codigo_posto.value;
	var var6 = $('input[name=tipo_lancamento]:checked').val() ;
	
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'data_inicial_01='+var1+'&data_final_01='+var2+'&tipo='+var4+'&ajax=sim'+'&codigo_posto='+var5+'&tipo_lancamento='+var6,
		beforeSend: function(){
			$('#consultar').effect('bounce');
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >").removeClass("Erro");
		},
		complete: function(resposta){
			results = resposta.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				$('#consultar').show('');
				if (results[0] == 'ok') {
					$('#dados').html(results[1]);
					$('input[name=tipo_lancamento]:checked').attr("checked",false);
				}
				if (results[0] == 'no') {
					$('#dados').html("<br>" + results[1]).addClass("Erro");
				}
			}
		}
	});
}

function excluirLancamento(lancamento,id){
	var extrato_lancamento = document.getElementById(id);
	jConfirm('Deseja realmente excluir este lançamento?', 'Excluindo Lançamento', function(resposta) {
		if(resposta == true){
			$.ajax({
				type: "GET",
				url: "<?=$PHP_SELF?>",
				data: 'lancamento='+lancamento,
				beforeSend:function(){
					$(extrato_lancamento).html("<img src='../imagens/carregar_os.gif' width='8' border='0'>");
				},
				complete: function(){
					$(extrato_lancamento).html('OK').show();
				}
			});
		}else{
			return false;
		}
	});
}
</script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

});
</script>

<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div>
Serão mostrados somente os extratos que foram enviados para o financeiro.
</div>
<div id='erro' style='position: absolute; top: 150px; left: 80px;opacity:.85;visibility:hidden;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório Avulsos Pagos em Extrato</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='right'><font size='2'>Data Final</td> 
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Código Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr class="Conteudo">
					<td width="10">&nbsp;</td>
					<td align='right'nowrap><font size='2'>Tipo</td>
					<td align='left'  colspan='3'>
					<select name='tipo' size='1' style='width:320px'>
					<option></option>
					<?
					$sql = "select distinct descricao 
							from tbl_extrato_lancamento 
							where fabrica = $login_fabrica";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						for($i=0;pg_numrows($res)>$i;$i++){
							$extrato_lancamento = pg_result($res,$i,descricao);
							echo "<option value='$extrato_lancamento'>$extrato_lancamento</option>";
						}?>
					</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<?}?>
				<?if($login_fabrica == 3) { ?>
				<tr>
					<td colspan='100%'>
						<label for='sem_extrato'>
						<input type='radio' name='tipo_lancamento' value='sem_extrato' id='sem_extrato'>Lançamento em nenhum extrato
						</label>
						&nbsp;&nbsp;
						<label for='lancamento_excluido'>
						<input type='radio' name='tipo_lancamento' value='lancamento_excluido' id='lancamento_excluido'>Lançamento excluído
						</label>
						</td>
				</tr>
				<? } ?>
			</table><br>
			<input type='button' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar' id='consultar'>
		</td>
	</tr>
</table>
</form>

<?

echo "<div id='dados'></div>";

?>

<p>

<? include "rodape.php" ?>
