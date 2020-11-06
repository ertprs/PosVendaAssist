<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$login_fabrica = "(10,51,81)";
$login_posto= 4311;
include "menu.php";
include "javascript_calendario_new.php";
if(strlen($data_inicial)==0 AND strlen($data_final)==0){
	$fnc  = @pg_exec($con,"SELECT to_char(current_date - interval '30 days','DD/MM/YYYY');");
	$data_inicial = @pg_result ($fnc,0,0);

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	$data_final = date("d/m/Y");
}
?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<style>
.Pesquisa{
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	color: #333333;
	border:#485989 1px solid;
	background-color: #EFF4FA;
}

.Pesquisa caption {
	font-size:14px;
	font-weight:bold;
	color: #FFFFFF;
	background-color: #596D9B;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

.Pesquisa thead td{
	text-align: center;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Pesquisa tbody th{
	font-size: 12px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}
.Pesquisa tbody td{
	font-size: 10px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}

.Pesquisa tfoot td{
	font-size:10px;
	font-weight:bold;
	color: #000000;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

</style>
<p>

<table width="700" align="center" border="0" cellspacing="2" cellpadding="2" bgcolor='#D9E2EF' class='Pesquisa'>
<caption>Pesquisa de Pedido</caption>
<form name='frm_pedido_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr>
	<th nowrap>Número do pedido</th>
	<td nowrap><input type='text' name='pedido' value=''></td>
</tr>
<tr>
	<th nowrap>Código da peça</th>
	<td nowrap><input type='text' name='referencia' value=''></td>
</tr>
<tr>
	<th>Data Inicial</th>
	<td><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
	</td>
</tr>
<tr>
	<th>Data Final</th>
	<td><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" ></TD>
</tr>

<tfoot>
<tr>
	<td colspan=2 style='text-align:center' valign='middle' nowrap><img src='../admin/imagens_admin/btn_pesquisar_400.gif' onclick="javascript: if (document.frm_pedido_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pedido_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_pedido_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pelo Pedido" border='0' style='cursor: pointer'></td>

</tr>
</tfoot>
</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$pedido = $_POST['pedido'];
if (strlen($_GET['pedido']) > 0) $pedido = $_GET['pedido'];

$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$referencia = $_POST['referencia'];
if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];


if (( (strlen($pedido) > 0 OR strlen($referencia) > 0 OR (strlen($data_inicial)>0 and strlen($data_final)>0)) AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){

	if(strlen($data_inicial)>0 and strlen($data_final)>0 and strlen($pedido) == 0) {

		$fnc  = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_inicial = @pg_fetch_result ($fnc,0,0);

		$fnc  = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_final = @pg_fetch_result ($fnc,0,0);
		$add_1 = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}

	if(empty($erro)) {
		$sql = "SELECT  tbl_fabrica.nome as fabrica_nome                                   ,
						tbl_pedido.pedido                                                  ,
						tbl_pedido.fabrica                                                 ,
						tbl_pedido.seu_pedido                                              ,
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data                      ,
						TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado          ,
						TO_CHAR(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS  recebido_posto ,
						tbl_pedido.exportado                                               ,
						tbl_pedido.distribuidor                                            ,
						tbl_pedido.total                                                   ,
						tbl_pedido.pedido_sedex                                            ,
						tbl_pedido.pedido_loja_virtual                                     ,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao                 ,
						tbl_linha.nome			  AS linha_descricao                       ,
						NULL  AS  pedido_status                                            ,
						tbl_status_pedido.status_pedido AS id_status                       ,
						tbl_status_pedido.descricao AS xstatus_pedido                      ,
						tbl_pedido.obs                                                     ,
						to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1))::numeric,'999999990.99' )::float AS preco_ipi 
				FROM    tbl_pedido
				JOIN    tbl_tipo_pedido     USING (tipo_pedido)
				JOIN    tbl_pedido_item     USING (pedido)
				JOIN    tbl_peca            USING (peca)
				LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
				LEFT JOIN tbl_linha         ON tbl_linha.linha = tbl_pedido.linha
				JOIN tbl_fabrica ON tbl_pedido.fabrica = tbl_fabrica.fabrica
				WHERE   tbl_pedido.posto   = $login_posto
				AND     tbl_pedido.fabrica in  $login_fabrica
				AND   (tbl_pedido.status_pedido in (1,2,5,7,9,11,12) OR tbl_pedido.status_pedido IS NULL)
				$add_1";

		if (strlen($pedido) > 0 ) {
			$sql .= "AND tbl_pedido.pedido = $pedido ";
		}

		if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia LIKE '%$referencia%' ";

		$sql .= "GROUP BY tbl_fabrica.nome            ,
						tbl_pedido.pedido             ,
						tbl_pedido.fabrica            ,
						tbl_pedido.seu_pedido         ,
						tbl_pedido.data               ,
						tbl_pedido.finalizado         ,
						tbl_pedido.recebido_posto,
						tbl_pedido.total              ,
						tbl_tipo_pedido.descricao     ,
						tbl_status_pedido.status_pedido,
						tbl_status_pedido.descricao   ,
						tbl_pedido.exportado          ,
						tbl_pedido.distribuidor       ,
						tbl_pedido.pedido_sedex       ,
						tbl_linha.nome,
						tbl_pedido.pedido_loja_virtual,
						tbl_pedido.obs                
				ORDER BY tbl_pedido.data DESC";
		$res = pg_query ($con,$sql);

		if (@pg_num_rows($res) > 0) {
			echo "<form name='frm_pedido_lista' method='post' action='$PHP_SELF'>";
			echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
			echo "<tr>";
			echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
			echo "<td valign='top' align='center'>";

			echo "<p>";

			if (strlen($referencia) > 0){
				echo "<table width='600' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#f1f1f1'>";
				echo "<tr height='25'>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Pedidos com a Peça</b></font></td>";
				echo "</tr>";
				echo "</table>";
			}
			echo "<p>";

			echo "<table width='670' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr height='20' bgcolor='#999999'>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Pedido</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Fábrica</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Data</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Finalizado</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Tipo Pedido</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Status</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Total</b></font></td>";
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';

				$total                 = pg_fetch_result($res,$i,preco_ipi);
				$pedido                = trim(pg_fetch_result($res,$i,pedido));
				$fabrica_nome          = trim(pg_fetch_result($res,$i,fabrica_nome));
				$fabrica               = trim(pg_fetch_result($res,$i,fabrica));
				$seu_pedido            = trim(pg_fetch_result($res,$i,seu_pedido));
				$data                  = trim(pg_fetch_result($res,$i,data));
				$finalizado            = trim(pg_fetch_result($res,$i,finalizado));
				$pedido_sedex          = trim(pg_fetch_result($res,$i,pedido_sedex));
				$pedido_loja_virtual   = trim(pg_fetch_result($res,$i,pedido_loja_virtual));
				$id_status             = trim(pg_fetch_result($res,$i,id_status));
				$pedido_status     = trim(pg_fetch_result($res,$i,pedido_status));
				$status_pedido         = trim(pg_fetch_result($res,$i,xstatus_pedido));
				$tipo_pedido_descricao = trim(pg_fetch_result($res,$i,tipo_pedido_descricao));
				$linha                 = trim(pg_fetch_result($res,$i,linha_descricao));
				$exportado             = trim(pg_fetch_result($res,$i,exportado));
				$distribuidor          = trim(pg_fetch_result($res,$i,distribuidor));
				$recebido_posto        = trim(pg_fetch_result($res,$i,recebido_posto));
				$obs                   = trim(pg_fetch_result($res,$i,obs));

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido&fabrica=$fabrica' target='_blank'>$pedido</a></font></td>";
				echo "<td align='center' nowrap><font size='1' face='Geneva, Arial, Helvetica,	san-serif'>$fabrica_nome</font></td>";
				echo "<td align='center' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$finalizado</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tipo_pedido_descricao</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_pedido</font></td>";
				echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total,2,",",".") ."</font></td>";

				echo "</td>";
				echo "</tr>";
			}

			echo "</table>";
			echo "</form>";
			echo "</td>";
			echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";

			echo "</tr>";
			echo "</table>";

		}else{
			echo "<p>";

			echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
			echo "<tr>";

			echo "<td valign='top' align='center'>";
			echo "<h4>Nenhum pedido encontrado</h4>";
			echo "</td>";

			echo "</tr>";
			echo "</table>";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
