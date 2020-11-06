<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";

include "autentica_admin.php";
include 'funcoes.php';

$hoje = date("d/m/Y");
# Pesquisa pelo AutoComplete AJAX
$q = $_GET["q"];
if (isset($_GET["q"])) {

	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 2) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$resultados = pg_fetch_all($res);
			foreach ($resultados as $resultado){
				echo $resultado['cnpj']."|".$resultado['nome']."|".$resultado['codigo_posto'];
				echo "\n";
			}
		}
	}
	exit;
}


$layout_menu = "callcenter";
$title = "RELATÓRIO REQUISIÇÃO DE PEÇAS";

include "cabecalho.php";

?>

<style type="text/css">

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
	font: bold 16px "Arial";
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
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
}

</script>




<? include "javascript_calendario.php";  ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});;
		$("#data_inicial").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script language="JavaScript">
$().ready(function() {

	$("#os").keypress(function(e) {   
		var c = String.fromCharCode(e.which);   
		var allowed = '1234567890-';
		if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
	});


	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchCase: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
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
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});


</script>


<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){
	$data_inicial		= trim($_POST['data_inicial']);
	$posto_codigo		= trim($_POST["posto_codigo"]);
	$posto_nome      	= trim($_POST["posto_nome"]);
	
	if(strlen($data_inicial) == 0){
		$data_inicial = date('d/m/Y');
	}

	
    list($di, $mi, $yi) = explode("/", $data_inicial);
    if(!checkdate($mi,$di,$yi)) 
        $msg_erro = "Data Inválida";
	$hoje = date('Y-m-d');
	$xdata_inicial = "$yi-$mi-$di";
	
	
	if(strtotime($xdata_inicial) > strtotime($hoje)){
		$msg_erro = "Data Inválida";
	}

	
	if(strlen($posto_codigo)>0){
		$sql = " SELECT posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND   codigo_posto = '$posto_codigo' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$posto = pg_result($res,0,0);
			$sql_posto = " JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
							JOIN tbl_os ON tbl_os_produto.os=tbl_os.os AND tbl_os.posto=$posto AND tbl_os.fabrica=$login_fabrica  ";
		}else{
			$msg_erro = "Posto Inválido";
		}
		
	}

	if(strlen($posto_codigo) == 0 and (strlen($posto_nome) > 0)){
		$sql = " SELECT posto
				FROM tbl_posto_fabrica
				JOIN tbl_posto USING(posto)
				WHERE fabrica = $login_fabrica
				AND   tbl_posto.nome = '$posto_nome' ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$posto = pg_result($res,0,0);
			$sql_posto = " JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
							JOIN tbl_os ON tbl_os_produto.os=tbl_os.os AND tbl_os.posto=$posto AND tbl_os.fabrica=$login_fabrica ";
		}else{
			$msg_erro = "Posto Inválido";
		}
	}
}




?>
<br>
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>
<? if(strlen($msg_erro) > 0){ ?>
		<tr class="msg_erro">
			<td colspan='3' align='center'> <? echo $msg_erro; ?>
		</tr>
<? } ?>
<tr class="titulo_tabela"><td colspan='3' height="20px">Parâmetros de Pesquisa</td></tr>
<TBODY>

<TR>
	<td width="100">&nbsp;</td>
	<TD colspan='2'>Data<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? if(!empty($data_inicial))echo $data_inicial; else echo $hoje; ?>" class="frm" tabindex='2'></TD>
	
</TR>
<TR>
	<td width="100">&nbsp;</td>
	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $posto_codigo ?>" class="frm" tabindex='4'></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $posto_nome ?>" class="frm" tabindex='5'></TD>
</TR>

</tbody>

<TR>
	<TD colspan="3" align='center'>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" style="cursor:pointer;" value="Pesquisar" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar' tabindex='7'>
	</TD>
</TR>
</table>
</form>
<br />
<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
	
	$sql = "SELECT  tbl_peca.referencia,
			      tbl_peca.descricao,
			      SUM(tbl_os_item.qtde) as qtde
			FROM
			tbl_os_item
			JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = tbl_peca.fabrica
			$sql_posto
			WHERE
			tbl_peca.fabrica=$login_fabrica
			AND tbl_servico_realizado.troca_de_peca IS TRUE
			AND tbl_servico_realizado.gera_pedido IS FALSE
			AND tbl_os_item.digitacao_item BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_inicial 23:59:59'
			GROUP BY
			tbl_peca.referencia,
			tbl_peca.descricao;";
	//echo nl2br($sql);exit;
	$res = pg_exec($con,$sql);
	$total = pg_numrows($res);
	
	if($total > 0){
		ob_start();
		?>
		<table align='center' width='700' cellspacing='1' class='tabela'>
			<tr class='titulo_coluna'>
				<td>Ref. Peça</td>
				<td>Descrição</td>
				<td>Qtde</td>
			</tr>
		<?
		for($i = 0; $i < $total; $i++){
			$referencia = pg_result($res,$i,referencia);
			$descricao = pg_result($res,$i,descricao);
			$qtde = pg_result($res,$i,qtde);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
			<tr bgcolor='<? echo $cor ?>'>
				<td><a href="rel_qtde_peca_requisitada.php?referencia=<?= $referencia?>&data=<?= $xdata_inicial ?>&posto=<? echo $posto; ?>" target='_blank'><? echo $referencia;?></a></td>

				<td><a href="rel_qtde_peca_requisitada.php?referencia=<?= $referencia?>&data=<?= $xdata_inicial ?>&posto=<? echo $posto; ?>" target='_blank'><? echo $descricao;?></a></td>

				<td><a href="rel_qtde_peca_requisitada.php?referencia=<?= $referencia?>&data=<?= $xdata_inicial ?>&posto=<? echo $posto; ?>" target='_blank'><? echo $qtde;?></a></td>
			</tr>
		<?

		}
		?>
			<tr><td colspan='3' align='center' colspan='titulo_coluna'>Relatório Gerado em :  <? echo $data_inicial; ?></td></tr>
		</table>
		<?
		
		$conteudo_excel = ob_get_clean();
		echo $conteudo_excel ."<br>";
		$arquivo = fopen("xls/relatorio_requisicao_pecas_$login_fabrica$login_admin.xls", "w+");
		fwrite($arquivo, $conteudo_excel);
		fclose($arquivo);
		$caminho = "xls/relatorio_requisicao_pecas_$login_fabrica$login_admin.xls";
		echo "<input type='button' value='Download em Excel' onclick=\"window.location='".$caminho."'\">";
	}
	else{
		echo "<center>Nenhum resultado encontrado!</ceanter>";
	}
}

echo "<br>";
include "rodape.php" ?>