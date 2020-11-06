<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

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

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj			= trim(pg_fetch_result($res,$i,cnpj));
				$nome			= trim(pg_fetch_result($res,$i,nome));
				$codigo_posto	= trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if($_GET['ajax']=='sim') {

	if (strlen($_GET["data_inicial_01"]) == 0)$erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $erro .= "Favor informar a data inicial para pesquisa<br>";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $erro .= "Favor informar a data final para pesquisa<br>";

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_fetch_result ($fnc,0,0);
	}

	$codigo_posto = trim($_GET["codigo_posto"]);
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$sql = " SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica ";
		$res   = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$cond_1 = " tbl_extrato.posto = ".pg_fetch_result($res,0,0);
		}else{
			$erro = "Posto não encontrado";
		}
		
	}

	if (strlen($erro) == 0) {
		if (strlen($_GET["data_final_01"]) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_fetch_result ($fnc,0,0);
		}
	}

	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);

		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;

	}else $listar = "ok";

	if ($listar == "ok") {

		$sql = "SELECT count(*) as os_aprovada,tbl_extrato.posto
				INTO TEMP conta_os_$login_admin
				FROM tbl_extrato
				JOIN tbl_os_extra USING (extrato)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND $cond_1 
				GROUP BY POSTO;

				CREATE INDEX conta_os_$login_admin_posto ON conta_os_$login_admin (posto);

				SELECT count(distinct tbl_os.os) as os_recusada,tbl_os.posto
				INTO TEMP conta_os_recusada_$login_admin
				FROM tbl_os
				JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os.fabrica = tbl_os_status.fabrica_status
				JOIN tbl_extrato USING (extrato)
				WHERE tbl_os_status.status_os = 13
				AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND tbl_os.fabrica =$login_fabrica
				AND $cond_1 
				GROUP BY tbl_os.posto;

				CREATE INDEX conta_os_recusada_$login_admin_posto ON conta_os_recusada_$login_admin (posto);

				SELECT sum(distinct tbl_os.mao_de_obra + tbl_os.pecas) as valor_recusada,tbl_os.posto
				INTO TEMP soma_valor_recusada_$login_admin
				FROM tbl_os
				JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os.fabrica = tbl_os_status.fabrica_status
				JOIN tbl_extrato USING (extrato)
				WHERE tbl_os_status.status_os = 13
				AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
				AND tbl_os.fabrica =$login_fabrica
				AND $cond_1 
				GROUP BY tbl_os.posto;

				CREATE INDEX soma_valor_recusada_$login_admin_posto ON soma_valor_recusada_$login_admin (posto);

				SELECT tbl_posto.posto                                          ,
				tbl_posto_fabrica.codigo_posto                                  ,
				tbl_posto.nome                                                  ,
				tbl_posto_fabrica.posto                                         ,
				conta_os_$login_admin.os_aprovada                               ,
				conta_os_recusada_$login_admin.os_recusada                      ,
				soma_valor_recusada_$login_admin.valor_recusada                 ,
				(SELECT sum(valor_liquido) from tbl_extrato join tbl_extrato_pagamento USING(extrato) where tbl_posto_fabrica.posto=tbl_extrato.posto AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' AND tbl_extrato.fabrica =$login_fabrica) as valor_liquido
			FROM tbl_extrato
			JOIN tbl_os_extra USING (extrato)
			JOIN tbl_posto         ON tbl_extrato.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN conta_os_$login_admin ON tbl_extrato.posto = conta_os_$login_admin.posto
			LEFT JOIN conta_os_recusada_$login_admin ON tbl_extrato.posto = conta_os_recusada_$login_admin.posto
			LEFT JOIN soma_valor_recusada_$login_admin ON  tbl_extrato.posto = soma_valor_recusada_$login_admin.posto
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
			AND $cond_1
			GROUP BY tbl_posto.posto                ,
					 tbl_posto_fabrica.codigo_posto ,
					 tbl_posto.nome                 ,
					 tbl_posto_fabrica.posto        ,
					 conta_os_$login_admin.os_aprovada ,
					 conta_os_recusada_$login_admin.os_recusada,
					 soma_valor_recusada_$login_admin.valor_recusada
			ORDER BY tbl_posto.nome ";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$total = 0;

			$resposta  .= "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final </b>";

			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			$resposta  .=  "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .=  "<TD><b>Código</b></TD>";
			$resposta  .=  "<TD><b>Posto</b></TD>";
			$resposta  .=  "<TD><b>Valor Líquido Aprovado</b></TD>";
			$resposta  .=  "<TD><b>Qtde OS aprovada</b></TD>";
			$resposta  .=  "<TD><b>Qtde OS recusada</b></TD>";
			$resposta  .=  "<TD><b>Valor Recusada</b></TD>";
			$resposta  .=  "</TR>";
			for ($i=0; $i<pg_num_rows($res); $i++){
				$posto           = trim(pg_fetch_result($res,$i,posto));
				$codigo_posto    = trim(pg_fetch_result($res,$i,codigo_posto));
				$nome            = trim(pg_fetch_result($res,$i,nome));
				$valor_liquido   = trim(pg_fetch_result($res,$i,valor_liquido));
				$os_aprovada     = trim(pg_fetch_result($res,$i,os_aprovada));
				$os_recusada     = trim(pg_fetch_result($res,$i,os_recusada));
				$valor_recusada  = trim(pg_fetch_result($res,$i,valor_recusada));

				$os_recusada = (strlen($os_recusada) == 0) ? 0 : $os_recusada;

				$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='left'nowrap>$codigo_posto</TD>";
				$resposta  .=  "<TD align='center'nowrap>$nome</TD>";
				$resposta  .=  "<TD align='center'>R$ ".number_format($valor_liquido,2,",",".")."</a></TD>";
				$resposta  .=  "<TD align='center'nowrap>$os_aprovada</TD>";
				$resposta  .=  "<TD align='center'>$os_recusada</TD>";
				$resposta  .=  "<TD align='center'>R$ ".number_format($valor_recusada,2,",",".")."</TD>";
				$resposta  .=  "</TR>";

				$total                += $valor_liquido;
				$total_aprovada       += $os_aprovada;
				$total_recusada       += $os_recusada;
				$total_valor_recusada += $valor_recusada;
			}
			$resposta .=  "<tfoot><tr class='Conteudo' bgcolor='#3399FF' style='font-weight:bold;color:#000000;font-size:13px;'><td colspan='2'>Total</td>";
			$resposta .="<td>R$ ". number_format($total,2,",",".") ." </b></td>";
			$resposta .="<td>$total_aprovada</td>";
			$resposta .="<td>$total_recusada</td>";
			$resposta .="<td>R$ ". number_format($total_valor_recusada,2,",",".") ."</td>";
			$resposta .="</tr></tfoot>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='600'>";
			$resposta .=  "<br>";
		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado entre $data_inicial e $data_final</b>";
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
$title = "RELATÓRIO DAS OSs RECUSADAS";

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


.Caixa{
	border-right: #6699cc 1px solid;
	border-top: #6699cc 1px solid;
	font: 8pt Arial ;
	border-left: #6699cc 1px solid;
	border-bottom: #6699cc 1px solid;
	background-color: #ffffff;
}

.Erro{
	border-right: #990000 1px solid;
	border-top: #990000 1px solid;
	font: 12pt Arial ;
	color: #ffffff;
	border-left: #990000 1px solid;
	border-bottom: #990000 1px solid;
	background-color: #ff0000;
}
</style>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script language='javascript'>

function Exibir (componente) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var3 = document.frm_relatorio.codigo_posto.value;

	$.ajax({
		type:"GET",
		url: "<?=$PHP_SELF?>",
		data:'data_inicial_01='+var1+'&data_final_01='+var2+'&ajax=sim'+'&codigo_posto='+var3,
		beforeSend: function(){
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >").removeClass('Erro');
		},
		complete: function(resposta) {
			resultado = resposta.responseText.split("|");
			if (typeof (resultado[0]) != 'undefined') {
				if (resultado[0] == 'ok') {
					$('#dados').html(resultado[1]);
				}
				if (resultado[0] == 'no') {
					$('#dados').html(resultado[1]).addClass('Erro');
				}
			}
		}
	})
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


<form name="frm_relatorio" method="POST" action="<? echo $PHP_SELF ?>">
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório das OSs recusadas</td>
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
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<?=$codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?=$posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='button' onclick="javascript:Exibir('dados');" style="cursor:pointer " value='Consultar'>
		</td>
	</tr>
</table>
</form>

<? echo "<br><div id='dados'></div>"; ?>

<p>

<? include "rodape.php" ?>