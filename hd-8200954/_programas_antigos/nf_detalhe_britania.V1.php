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
					trim(tbl_faturamento.nota_fiscal)                         AS nota_fiscal     ,
					trim(tbl_faturamento.total_nota)                          AS total_nota      ,
					tbl_faturamento.cfop                                                         ,
					tbl_condicao.descricao                                    AS cond_pg         ,
					tbl_faturamento.transp
			FROM    tbl_faturamento
			LEFT JOIN tbl_condicao USING (condicao)
			WHERE   tbl_faturamento.posto       = $login_posto
			AND     tbl_faturamento.fabrica     = $login_fabrica
			AND     tbl_faturamento.faturamento = $faturamento";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$emissao          = trim(pg_result($res,0,emissao));
		$saida            = trim(pg_result($res,0,saida));
		$cancelada        = trim(pg_result($res,0,cancelada));
		$previsao_chegada = trim(pg_result($res,0,previsao_chegada));
		$nota_fiscal      = trim(pg_result($res,0,nota_fiscal));
		$total_nota       = trim(pg_result($res,0,total_nota));
		$cfop             = trim(pg_result($res,0,cfop));
		$cond_pg          = trim(pg_result($res,0,cond_pg));
		$transp           = trim(pg_result($res,0,transp));

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

		if (strtoupper ($transp) == "LOVATO" AND $login_fabrica == "3") {
			echo "<form name='Form1' action='http://www.ssw.inf.br/ssw_resultSSW.asp' method='POST' target='_blank'>";
#			echo "<form name='Form1' action='http://www.ssw.inf.br/ssw_SSWDetalhado.asp?zcnpj=76492701000157&znf=0	$nota_fiscal' method='POST' target='_new'>";
			echo "<input type='hidden' name='cnpj' value='76492701000157'>";
			echo "<input type='hidden' name='NR' value='$nota_fiscal'>";
			echo "<center><a href='javascript: document.Form1.submit();' style='font-size:12px'>Clique aqui para rastrear a encomenda</a></center>";

			echo "</form>";
		}


		if (strtoupper ($transp) == "MERCURIO" AND $login_fabrica == "3") {

			$sql = "SELECT cnpj FROM tbl_posto WHERE posto = $login_posto";
			$resX = pg_exec ($con,$sql);
			$posto_cnpj = pg_result ($resX,0,0);

			echo "<form method='POST' name='form_semsenha' id='form_semsenha' action='http://www.mercurio.com/localizacao/new_loc/aberta.asp' target='_blank'>";
			echo "<input type='hidden' name='cgc_semsenha' value='$posto_cnpj'>";
			echo "<input type='hidden' name='nota_semsenha' value='$nota_fiscal'>";
			echo "<center><a href='javascript: document.form_semsenha.submit();' style='font-size:12px'>Clique aqui para rastrear a encomenda</a></center>";
			echo "</form>";
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
							tbl_posto.nome                               AS posto_nome ,
							tbl_posto.cidade                             AS posto_cidade
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
			echo "<td class='menu_top'>POSTO</td>\n";
			echo "<td class='menu_top'>DATA <br> PEDIDO</td>\n";
			echo "<td class='menu_top'>O.S.</td>\n";
			echo "<td class='menu_top'>DATA <br> O.S</td>\n";
			echo "</tr>\n";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca        = trim(pg_result($res,$i,referencia)) ;
				$descricao   = trim(pg_result($res,$i,descricao)) ;
				$qtde        = trim(pg_result($res,$i,qtde));
				$preco       = trim(pg_result($res,$i,preco));
				$pedido      = trim(pg_result($res,$i,pedido));
				$data_pedido = trim(pg_result($res,$i,data_pedido));
				$sua_os      = trim(pg_result($res,$i,sua_os));
				$data_os     = trim(pg_result($res,$i,data_os));
				$posto_nome  = trim(pg_result($res,$i,posto_nome));
				$posto_cidade= trim(pg_result($res,$i,posto_cidade));
	
				$cor = "#ffffff";
				if ($i % 2 == 0) $cor = "#DDDDEE";

				echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
				echo "<td align='left' nowrap>" . ($i+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
				echo "<td align='left' nowrap>$peca</td>\n";
				echo "<td align='left' nowrap>$descricao</td>\n";
				echo "<td align='right'>$qtde</font></td>\n";
				echo "<td align='right'>". number_format($preco,2,",",".") ."</td>\n";
				echo "<td align='left'>$pedido</td>\n";
				echo "<td align='left' nowrap><a href='#' class='dica'>" . substr ($posto_nome,0,10) . "<span>$posto_nome<br>$posto_cidade</span></td>\n";
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