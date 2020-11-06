<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

include_once '../anexaNF_inc.php';

$msg_erro = "";

if (strlen($_GET['os_sedex']) > 0)  $os_sedex = $_GET['os_sedex'];
if (strlen($_POST['os_sedex']) > 0) $os_sedex = $_POST['os_sedex'];

$btn_acao = $_POST['btn_acao'];

if (strlen($os_sedex) > 0) {
	$sql = "SELECT  tbl_os_sedex.posto_origem                       ,
					tbl_os_sedex.posto_destino                      ,
					tbl_os_sedex.obs                                ,
					to_char(tbl_os_sedex.data, 'DD/MM/YYYY') AS data,
					tbl_os_sedex.despesas                           ,
					tbl_os_sedex.controle                           ,
					tbl_os_sedex.sua_os_origem                      ,
					tbl_os_sedex.sua_os_destino                     ,
					tbl_os_sedex.finalizada                         ,
					tbl_os_sedex.extrato                            
			FROM    tbl_os_sedex
			WHERE   tbl_os_sedex.os_sedex = $os_sedex";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows($res) > 0) {
		$posto_origem    = trim (pg_result ($res,0,posto_origem));
		$posto_destino   = trim (pg_result ($res,0,posto_destino));
		$obs             = trim (pg_result ($res,0,obs));
		$data_lancamento = trim (pg_result ($res,0,data));
		$despesas        = trim (pg_result ($res,0,despesas));
		$controle        = trim (pg_result ($res,0,controle));
		$sua_os_origem   = trim (pg_result ($res,0,sua_os_origem));
		$sua_os_destino  = trim (pg_result ($res,0,sua_os_destino));
		$finalizada      = trim (pg_result ($res,0,finalizada));
		$extrato         = trim (pg_result ($res,0,extrato));

		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_origem
				AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
		$res1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res1) > 0) {
			$posto_origem      = trim(pg_result($res1,0,codigo_posto));
			$nome_posto_origem = trim(pg_result($res1,0,nome));
		}

		if($sua_os_destino == 'CR'){
			$sql = "SELECT tbl_os.sua_os FROM tbl_os WHERE os = '$sua_os_origem' ;";
			$res = pg_exec($con,$sql);
			$carta = "CR";
			$sua_os_destino = pg_result($res,0,sua_os);
		}

		
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
				FROM    tbl_posto
				JOIN    tbl_posto_fabrica USING(posto)
				WHERE   tbl_posto_fabrica.posto = $posto_destino
				AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
		$res2 = @pg_exec ($con,$sql);

		if (@pg_numrows($res2) > 0) {
			$posto_destino      = trim(pg_result($res2,0,codigo_posto));
			$nome_posto_destino = trim(pg_result($res2,0,nome));
		}
	}
}

$title     = "FINALIZAÇÃO DE OS SEDEX";
$cabecalho = "Finalização de OS Sedex";
$layout_menu = "callcenter";

include "cabecalho.php";

if(strlen($data_lancamento) == 0) $data_lancamento = date("d/m/Y");

$xos_sedex = "00000".$os_sedex;
$xos_sedex = substr($xos_sedex,strlen($xos_sedex) - 5,strlen($xos_sedex));
?>

<link rel="stylesheet" href="css/css.css" />
<style type="text/css">

.cabecalho {
	background-color: #D9E2EF;
	color: black;
	border: 2px SOLID WHITE;
	font-weight: normal;
	font-size: 10px;
	text-align: left;
}

.descricao {
	padding: 5px;
	color: black;
	font-size: 11px;
	font-weight: bold;
	text-align: justify;
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
	text-align:center;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

</style>

<br>

<? if($carta == 'CR' AND $login_fabrica == 1){ ?>
<p style='font-family; font-size: 12px; color:#FF9900' align='center'><b>*Carta Registrada<b></p>
<?}?>
<br>


<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' class='formulario'>
<tr class='titulo_tabela'>
	<td width="100%"><? if($carta == 'CR' AND $login_fabrica == 1){ echo "Número da OS";}else{ echo "Código OS Sedex"; }?></td>
</tr>
<tr bgcolor="#FFFFFF">
	<td><font size='3'><? if($carta == 'CR' AND $login_fabrica == 1){ echo $posto_origem.$sua_os_destino; }else{ echo $posto_origem.$xos_sedex;} ?>&nbsp;</font></td>
</tr>
</table>



<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' class='formulario'>
<tr class='subtitulo'>
	<td width="100%" colspan='3'>Posto Origem da Mercadoria</td>
</tr>
<tr >
	<td width="25%"><b>Código</b></td>
	<td width="57%"><b>Nome</b></td>
	<td width="18%">&nbsp;</td>
</tr>
<tr bgcolor="#FFFFFF">
	<td><? echo $posto_origem ?>&nbsp;</td>
	<td><? echo $nome_posto_origem ?>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
</table>



<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' class='formulario'>
<tr class='subtitulo'>
	<td width="100%" colspan='3'>Posto Destino da Mercadoria</td>
</tr>
<tr >
	<td width="25%"><b>Código</b></td>
	<td width="57%"><b>Nome</b></td>
	<td width="18%"><b>OS</b></td>
</tr>
<tr bgcolor="#FFFFFF">
	<td><? echo $posto_destino ?>&nbsp;</td>
	<td><? echo $nome_posto_destino ?>&nbsp;</td>
	<td><? echo $sua_os_destino ?>&nbsp;</td>
</tr>
</table>



<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' class='formulario'>
<tr >
	<td width="82%"><b>Observações</b></td>
	<td width="18%"><b>Data</b></td>
</tr>
<tr bgcolor="#FFFFFF">
	<td><? echo $obs ?>&nbsp;</td>
	<td><? echo $data_lancamento ?>&nbsp;</td>
</tr>
</table>



<? if (strlen($finalizada) > 0) { ?>
<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF' class='formulario'>
<tr >
	<td width="50%"><b>Controle</b></td>
	<td width="50%"><b>Despesas</b></td>
</tr>
<tr bgcolor="#FFFFFF">
	<td><? echo $controle ?>&nbsp;</td>
	<td>R$ <? echo number_format($despesas,2,',','.'); ?>&nbsp;</td>
</tr>
</table>
<br>
<? }

if (temNF("s_$os_sedex", 'bool'))
	echo temNF("s_$os_sedex", 'link') . $include_imgZoom;

$sql =	"SELECT tbl_os_sedex_item_produto.os_sedex_item_produto ,
				tbl_os_sedex_item_produto.qtde                  ,
				tbl_produto.referencia                          ,
				tbl_produto.descricao                           
		FROM    tbl_os_sedex_item_produto
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_os_sedex USING (os_sedex)
		WHERE   tbl_os_sedex.os_sedex = $os_sedex
		ORDER BY tbl_produto.referencia ASC;";
$res = pg_exec($con,$sql);
$qtde_produtos = pg_numrows($res);
if ($qtde_produtos > 0) {
	echo "<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>";
	echo "<tr class='cabecalho'>";
	echo "<td width='100%' colspan='3'>PRODUTO(S) SELECIONADO(S)</td>";
	echo "</tr>";
	echo "<tr class='cabecalho'>";
	echo "<td width='20%'>REFERÊNCIA</td>";
	echo "<td width='70%'>DESCRIÇÃO</td>";
	echo "<td width='10%'>QTDE</td>";
	echo "</tr>";
	for ($i = 0 ; $i < $qtde_produtos ; $i++) {
		$qtde       = trim(pg_result($res,$i,qtde));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		echo "<tr class='descricao'>";
		echo "<td>$referencia</td>";
		echo "<td>$descricao</td>";
		echo "<td>$qtde</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
?>

<?
$sql =	"SELECT tbl_os_sedex_item.os_sedex_item ,
				tbl_os_sedex_item.qtde          ,
				tbl_peca.referencia             ,
				tbl_peca.descricao              
		FROM    tbl_os_sedex_item
		JOIN    tbl_peca     USING (peca)
		JOIN    tbl_os_sedex USING (os_sedex)
		WHERE   tbl_os_sedex_item.os_sedex = $os_sedex
		ORDER BY tbl_peca.referencia ASC;";
$res = pg_exec ($con,$sql);
$qtde_pecas = pg_numrows($res);
if ($qtde_pecas > 0) {
	echo "<table align='center' width='700' border='1' cellpadding='0' cellspacing='4' bordercolor='#D9E2EF'>";
	echo "<tr class='cabecalho'>";
	echo "<td width='100%' colspan='3'>PEÇA(S) SELECIONADA(S)</td>";
	echo "</tr>";
	echo "<tr class='cabecalho'>";
	echo "<td width='20%'>REFERÊNCIA</td>";
	echo "<td width='70%'>DESCRIÇÃO</td>";
	echo "<td width='10%'>QTDE</td>";
	echo "</tr>";
	for ($i = 0 ; $i < $qtde_pecas ; $i++) {
		$qtde          = trim(pg_result($res,$i,qtde));
		$referencia    = trim(pg_result($res,$i,referencia));
		$descricao     = trim(pg_result($res,$i,descricao));
		echo "<tr class='descricao'>";
		echo "<td>$referencia</td>";
		echo "<td>$descricao</td>";
		echo "<td>$qtde</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "<br>";
}
?>

<center>
<?
if(strlen($finalizada) == 0 ){
?>
<input type="button" style="background:url(imagens/btn_alterarcinza.gif); width:75px;cursor:pointer;" value="&nbsp;" onclick="javascript: location.href='sedex_cadastro.php?os_sedex='+<? echo $os_sedex?>">
<?}?>
&nbsp; &nbsp;
<input type="button" style="background:url(imagens/btn_lancanovaos.gif); width:200px;cursor:pointer;" value="&nbsp;" onclick="javascript: location.href='sedex_cadastro.php' ">
</center>

<? include "rodape.php"; ?>
