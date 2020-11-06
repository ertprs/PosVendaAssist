<?php
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



if($_GET['ajax']=='sim') {

	if (strlen($_GET["data_inicial_01"]) == 0)$erro .= "Data Inválida<br>";
	if ($_GET["data_inicial_01"] == 'dd/mm/aaaa') $erro .= "Data Inválida<br>";
	if ($_GET["data_final_01"] == 'dd/mm/aaaa')   $erro .= "Data Inválida<br>";

	if (strlen($erro) == 0) {
		$data_inicial   = trim($_GET["data_inicial_01"]);
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = 'Data Inválida';
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	$tipo = $_GET['tipo'];
	$cond_4 = " 1=1 ";
	if(strlen($tipo)>0){
		$cond_4 = " tbl_extrato_lancamento.descricao = '$tipo' ";
	}

	$codigo_posto = $_GET["codigo_posto"];
	$cond_5 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$cond_5 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if (strlen($erro) == 0) {
		if (strlen($_GET["data_final_01"]) == 0) $erro .= "Data Inválida<br>";
		if (strlen($erro) == 0) {
			$data_final   = trim($_GET["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = 'Data Inválida';
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}
	
	if($aux_data_inicial > $aux_data_final){
		$erro = 'Data Inválida';
	}

	if(strlen($_GET["compressor"]) > 0) $linha = trim($_POST["compressor"]);

	$data_inicial_x = trim($_GET['data_inicial_01']);
	$data_final_x   = trim($_GET['data_final_01']);

	if (strlen($data_inicial_x) > 0) {
		$ano_inicial = substr($data_inicial_x,6,4);
		$mes_inicial = substr($data_inicial_x,3,2);
		$dia_inicial = substr($data_inicial_x,0,2);
		$data_inicial_x = $ano_inicial."-".$mes_inicial."-".$dia_inicial;
	}

	if (strlen($data_final_x) > 0) {
		$ano_final = substr($data_final_x,6,4);
		$mes_final = substr($data_final_x,3,2);
		$dia_final = substr($data_final_x,0,2);
		$data_final_x = $ano_final."-".$mes_final."-".$dia_final;
	}

	$t1 = strtotime($data_inicial_x);
	$t2 = strtotime($data_final_x);

	$x = $t2-$t1;
	$final = $x/60/60/24;

	if($final>30){
		$erro = "Periodo não pode ser maior que 30 dias.";
	}

	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);
		$linha        = trim($_GET["linha"]);
		$estado       = trim($_GET["estado"]);
		$criterio     = trim($_GET["criterio"]);

		$msg .= $erro;


	}else $listar = "ok";
	if ($listar == "ok") {

			$sql = "SELECT  tbl_extrato.extrato,
							tbl_posto.nome,
							tbl_posto.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_extrato_pagamento.nf_autorizacao,
							TO_CHAR(tbl_extrato_pagamento.data_pagamento, 'DD/MM/YYYY') AS data_pagamento,
							SUM(tbl_extrato_pagamento.valor_liquido) AS valor_liquido
					FROM tbl_extrato
					JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
					WHERE tbl_extrato_pagamento.data_pagamento BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
					AND tbl_extrato.fabrica=$login_fabrica
					AND $cond_5
					GROUP BY	tbl_extrato.extrato, tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_extrato_pagamento.nf_autorizacao, tbl_extrato_pagamento.data_pagamento
					ORDER BY tbl_posto.nome";

		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;
			$formato_arquivo = "CSV";

			$arquivo_nome     = "relat-extrato-pago.".$formato_arquivo;
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen ($arquivo_completo_tmp,"w");

			if($formato_arquivo=='CSV'){
				$conteudo="";
				$conteudo.="CÓDIGO DO POSTO;POSTO;EXTRATO; DT BAIXADO;VALOR; NF \n";
			}
			fputs ($fp,$conteudo);

			$resposta  .= '<br>';
			//$resposta  .= "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";

			$resposta  .=  "<br>";
			$resposta  .=  '<table align="center" width="700" cellspacing="1" class="tabela">';
			$resposta  .=  "<tr class='titulo_coluna'>";
			$resposta  .=  "<td>Código Posto</td>";
			$resposta  .=  "<td>Posto</td>";
			$resposta  .=  "<td>Extrato</td>";
			$resposta  .=  "<td>Data Baixado</td>";
			$resposta  .=  "<td>Valor</td>";
			$resposta  .=  "<td>NF</td>";
			$resposta  .=  "</tr>";
			
			$totalResposta = pg_numrows($res);
			
			for ($i=0;$i<$totalResposta;$i++){

				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$posto          = trim(pg_result($res,$i,posto));
				$nome           = trim(pg_result($res,$i,nome));
				$nf_autorizacao = trim(pg_result($res,$i,nf_autorizacao));
				$valor_liquido  = trim(pg_result($res,$i,valor_liquido));
				$extrato        = trim(pg_result($res,$i,extrato));
				$data_pagamento = trim(pg_result($res,$i,data_pagamento));
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$resposta  .=  "<tr bgcolor='$cor'>";
				$resposta  .=  "<td align='left' nowrap>$codigo_posto</td>";
				$resposta  .=  "<td align='left' nowrap>$nome</td>";
				$resposta  .=  "<td align='center'>"; if(strlen($extrato) > 0) {$resposta .= "$extrato"; }else{ $resposta .= "-";} $resposta .= "</td>";
				$resposta  .=  "<td align='center'>"; if(strlen($data_pagamento) > 0) {$resposta .= "$data_pagamento"; }else{ $resposta .= "-";} $resposta .= "</td>";
				$resposta  .=  "<td align='right'>R$". number_format($valor_liquido,2,",",".") ." </td>";
				$resposta  .=  "<td align='right'>$nf_autorizacao</td>";
				$resposta  .=  "</tr>";

				$total = $valor_liquido + $total;

				if($formato_arquivo=='CSV'){
					$conteudo="";
					$conteudo.=$codigo_posto.";".$nome.";".$extrato.";".$data_pagamento.";".number_format($valor_liquido,2,",",".").";". $nf_autorizacao.";\n";
				}
				fputs ($fp,$conteudo);
				$resposta .= ` cp $arquivo_completo_tmp $path `;

			}
			$resposta .=  "<tfoot>
							<tr class='titulo_coluna'>
								<td colspan='4'>Valor Total Líquido</td>
								<td colspan='2' align='right'>R$". number_format($total,2,",",".") ."</td>
							</tr>
						</tfoot>";
			$resposta .= " </TABLE>";

			$resposta .=  "<br><br>";
			
			$resposta .=  '<input type="button" onclick="window.open(\'xls/$arquivo_nome\')" value="Download em '.strtoupper($formato_arquivo).'" style="cursor:pointer" />';

			// monta URL
			$data_inicial = trim($_POST["data_inicial_01"]);
			$data_final   = trim($_POST["data_final_01"]);
			$linha        = trim($_POST["linha"]);
			$estado       = trim($_POST["estado"]);
			$criterio     = trim($_POST["criterio"]);


		}else{
			$resposta .=  "<br>";
			$resposta .= "<div align='center'><p>Não foram Encontrados Resultados para esta Pesquisa</p></div>";
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
$title = "RELATÓRIO DE EXTRATOS PAGOS";

include "cabecalho.php";

?>
<style>
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.subtitulo{
	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.informacao{
	font: 14px Arial; color:rgb(89, 109, 155);
	background-color: #C7FBB5;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
.espaco{
	padding-left:80px;
}
.Carregando{
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function retornaPesquisa (http,componente,componente_erro,componente_carregando) {
	var com = document.getElementById(componente);
	var com2 = document.getElementById(componente_erro);
	var com3 = document.getElementById(componente_carregando);
	if (http.readyState == 1) {

		Page.getPageCenterX() ;
		com3.style.top = (Page.top + Page.height/2)-100;
		com3.style.left = Page.width/2-75;
		//com3.style.position = "absolute";

		com3.innerHTML   = "&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
		com3.style.display = "block";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {

			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML   = " "+results[1];
					com2.innerHTML  = " ";
					com2.style.display = "none";

					com3.innerHTML = "<br>&nbsp;&nbsp;Informações carregadas com sucesso!&nbsp;&nbsp;<br>&nbsp;&nbsp;";
					setTimeout('esconde_carregar()',1500);
				}
				if (results[0] == 'no') {
					Page.getPageCenterX() ;
					com2.style.top = (Page.top + Page.height/2)-100;
					com2.style.left = Page.width/2-75;

					com2.innerHTML   = " "+results[1];
					com.innerHTML   = " ";
					com2.style.display = "block";
					com3.style.display = "none";
				}

			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function esconde_carregar(componente_carregando) {
	document.getElementById('carregando').style.display = "none";
}

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	//var var4 = document.frm_relatorio.tipo.value;
	var var5 = document.frm_relatorio.codigo_posto.value;

	var parametros = 'data_inicial_01='+var1+'&data_final_01='+var2+'&ajax=sim'+'&codigo_posto='+var5;

	url = "<?=$PHP_SELF?>?"+parametros;

	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPesquisa (http,componente,componente_erro,componente_carregando) ; } ;
	http.send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.getPageCenterX = function (){

	var fWidth;
	var fHeight;
	//For old IE browsers
	if(document.all) {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if(document.getElementById &&!document.all){
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if(document.getElementById) {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}

</script>

<? include "javascript_pesquisas.php" ?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

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
		//alert(data[2]);
	});

});
</script>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
	<div class="texto_avulso">
		Serão mostrados somente os extratos que foram enviados para o financeiro.
	</div>
	<br>
	<div align="center">
		<div id='erro' align="center" style='width:700px;display:none;' class='msg_erro'></div>
	</div>
	
	<table align="center" class="formulario" width="700" border="0">

		<tr>
			<td class='titulo_tabela' colspan="3">Parâmetros de Pesquisa</td>
		</tr>

		<tr>
			<td width="30">&nbsp;</td>
			<td class="espaco">
				Data Inicial
				<br>
				<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
			</td>
			<td>
				Data Final
				<br>
				<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
			</td>
		</tr>
		<tr class="subtitulo">
			<td colspan="3">Informações do Posto </td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class="espaco">
				Código Posto
				<br>
				<input type="text" name="codigo_posto" id="codigo_posto" size="20"  value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
			</td>
			<td nowrap>
				Nome do Posto
				<br>
				<input type="text" name="posto_nome" id="posto_nome" size="50"  value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
			</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3" align="center">
				<input type='button' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>');" style="cursor:pointer " value='Pesquisar'>
			</td>
		</tr>
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>

	<div align="center">
		<div id='carregando' align="center" style='width:700px;display:none;' class='Carregando'></div>
	</div>
	
</form>
<?
echo "<div id='dados'></div>";
?>
<br>
<? include "rodape.php" ?>