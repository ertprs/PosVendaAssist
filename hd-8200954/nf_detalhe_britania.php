<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 1){
	header("Location: pedido_blackedecker_finalizado.php?pedido=".$_GET['pedido']);
	exit;
}

$faturamento = $_GET['faturamento'];

$title = "DETALHAMENTO DE NOTA FISCAL";
$layout_menu = 'pedido';

include "cabecalho.php";
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<style type="text/css">
a.dica{
position:relative; 
font:10px arial, verdana, helvetica, sans-serif; 
padding:0;
color:#333399;
text-decoration:none;
cursor:help; 
z-index:24;
}

a.dica:hover{
background:transparent;
z-index:25; 
}

a.dica span{display: none}
a.dica:hover span{ 
display:block;
position:absolute;
width:180px; 
text-align:justify;
left:0;
font: 10px arial, verdana, helvetica, sans-serif; 
padding:5px 10px;
border:1px solid #000099;
background:#FFCC00; 
color:#330066;
}
</style>
<?


if (strlen($faturamento) > 0) {
	$sql = "SELECT  to_char(tbl_faturamento.emissao, 'DD/MM/YYYY')            AS emissao         ,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY')              AS saida           ,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY')   AS previsao_chegada,
					to_char(tbl_faturamento.cancelada, 'DD/MM/YYYY')          AS cancelada       ,
					trim(tbl_faturamento.nota_fiscal::text)                   AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota::text)                    AS total_nota      ,
					tbl_faturamento.cfop                                                         ,
					tbl_condicao.descricao                                    AS cond_pg         ,
					tbl_faturamento.transp,
					tbl_faturamento.conhecimento
			FROM    tbl_faturamento
			LEFT JOIN tbl_condicao USING (condicao)
			WHERE   tbl_faturamento.posto       = $login_posto
			AND     tbl_faturamento.fabrica     = $login_fabrica
			AND     tbl_faturamento.faturamento = $faturamento";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$emissao			= trim(pg_result($res,0,emissao));
		$saida			= trim(pg_result($res,0,saida));
		$cancelada		= trim(pg_result($res,0,cancelada));
		$previsao_chegada	= trim(pg_result($res,0,previsao_chegada));
		$nota_fiscal		= trim(pg_result($res,0,nota_fiscal));
		$total_nota		= trim(pg_result($res,0,total_nota));
		$cfop			= trim(pg_result($res,0,cfop));
		$cond_pg			= trim(pg_result($res,0,cond_pg));
		$transp			= trim(pg_result($res,0,transp));
		$conhecimento	= trim(pg_result($res,0,conhecimento));

		echo "<br>";

		echo "<table width='650' border='0' cellspacing='1' cellpadding='3' align='center'>\n";
		echo "<tr>\n";
		echo "<td class='menu_top'>NOTA FISCAL</td>\n";
		echo "<td class='menu_top'>EMISSÃO</td>\n";
		echo "<td class='menu_top'>CFOP</td>\n";
		echo "<td class='menu_top'>COND.PG.</td>\n";
		echo "<td class='menu_top'>TRANSP.</td>\n";
		echo "<td class='menu_top'>TOTAL DA NOTA</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$cfop</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$cond_pg</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$transp</font></td>\n";
		echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total_nota,2,",",".") ."</font></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		
		
		// ADICIONADO POR FABIO - 28/11/2006 - Para mostrar o link pro posto rastrear seus pedido
		if (strlen($conhecimento)>0){
			if ((strtoupper ($transp) == "E-SEDEX" OR strtoupper ($transp) == "SEDEX" OR strtoupper ($transp) == "PAC") AND $login_fabrica == "3") {
				echo "<br><center><a href='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento"."BR' target='_blank' class='link'>Clique aqui para rastrear a encomenda</a></center><br>";
					
			}
			if (strtoupper ($transp) == "BRASPRESS" AND $login_fabrica == "3") {
				echo "<br><center><a href='http://tracking.braspress.com.br/trk/trkisapi.dll' target='_blank' class='link'>Clique aqui para rastrear a encomenda - Utilize o código $conhecimento para rastrear</a></center><br>";
			}
		}


		$sql = "SELECT      tbl_peca.referencia                                        ,
							tbl_peca.descricao                                         ,
							tbl_faturamento_item.qtde                                  ,
							tbl_faturamento_item.preco                                 ,
							tbl_faturamento_item.pedido                                ,
							to_char(tbl_pedido.data, 'DD/MM/YYYY')       AS data_pedido,
							tbl_faturamento_item.os                                    ,
							tbl_os.sua_os                                              ,
							to_char(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_os    ,
							tbl_os.revenda_cnpj                          AS revenda_cnpj ,
							tbl_os.revenda_nome                          AS revenda_nome
				FROM        tbl_faturamento_item
				JOIN        tbl_peca       ON tbl_peca.peca     = tbl_faturamento_item.peca
				LEFT JOIN   tbl_pedido     ON tbl_pedido.pedido = tbl_faturamento_item.pedido
				LEFT JOIN   tbl_os         ON tbl_os.os         = tbl_faturamento_item.os
				LEFT JOIN   tbl_posto      ON tbl_pedido.posto  = tbl_posto.posto
				WHERE       tbl_faturamento_item.faturamento = $faturamento
				ORDER BY    tbl_posto.nome, tbl_peca.referencia";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
			echo "<tr>\n";
			echo "<td class='menu_top'>#</td>\n";
			echo "<td class='menu_top'>PEÇA</td>\n";
			echo "<td class='menu_top'>DESCRIÇÃO</td>\n";
			echo "<td class='menu_top'>QTDE</td>\n";
			echo "<td class='menu_top'>PREÇO</td>\n";
			echo "<td class='menu_top'>PEDIDO</td>\n";
			echo "<td class='menu_top'>REVENDA</td>\n";
			echo "<td class='menu_top'>DATA <br> PEDIDO</td>\n";
			echo "<td class='menu_top'>O.S.</td>\n";
			echo "<td class='menu_top'>DATA <br> O.S</td>\n";
			echo "</tr>\n";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca         = trim(pg_result($res,$i,referencia)) ;
				$descricao    =  trim(pg_result($res,$i,descricao)) ;
				$qtde         = trim(pg_result($res,$i,qtde));
				$preco        = trim(pg_result($res,$i,preco));
				$pedido       = trim(pg_result($res,$i,pedido));
				$data_pedido  = trim(pg_result($res,$i,data_pedido));
				$sua_os       = trim(pg_result($res,$i,sua_os));
				$data_os      = trim(pg_result($res,$i,data_os));
				$revenda_cnpj = trim(pg_result($res,$i,revenda_cnpj));
				$revenda_nome = trim(pg_result($res,$i,revenda_nome));
	
				$cor = "#ffffff";
				if ($i % 2 == 0) $cor = "#DDDDEE";

				echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
				echo "<td align='left' nowrap>" . ($i+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
				echo "<td align='left' nowrap>$peca</td>\n";
				echo "<td align='left' nowrap>$descricao</td>\n";
				echo "<td align='right'>$qtde</font></td>\n";
				echo "<td align='right'>". number_format($preco,2,",",".") ."</td>\n";
				echo "<td align='left'>$pedido</td>\n";
				echo "<td align='left' nowrap><a href='#' class='dica'>" . substr ($revenda_nome,0,10) . "<span>$revenda_cnpj<br>$revenda_nome</span></td>\n";
				echo "<td align='center'>$data_pedido</td>\n";
				echo "<td nowrap align='center'>$sua_os</td>\n";
				echo "<td nowrap align='center'>$data_os</td>\n";
				echo "</tr>\n";
			}
			
			echo "</table>\n";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
