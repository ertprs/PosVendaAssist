<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$faturamento = $_GET['faturamento'];

if(in_array($login_fabrica, array(11,172))){

    if(strlen($faturamento) > 0){

        $sql_fabrica = "SELECT fabrica FROM tbl_faturamento WHERE faturamento = {$faturamento}";
        $res_fabrica = pg_query($con, $sql_fabrica);

        if(pg_num_rows($res_fabrica) > 0){

            $fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

            if($fabrica_os != $login_fabrica){

                $self = $_SERVER['PHP_SELF'];
                $self = explode("/", $self);

                unset($self[count($self)-1]);

                $page = implode("/", $self);
                $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
                $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?faturamento={$faturamento}";

                $params = "?cook_admin=&cook_fabrica={$fabrica_os}&page_return={$pageReturn}";
                $page = $page.$params;

                header("Location: {$page}");
                exit;

            }

        }

    }

}

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
					trim(tbl_faturamento.valor_frete::text)                   AS valor_frete     ,
					tbl_faturamento.cfop                                                         ,
					tbl_condicao.descricao                                    AS cond_pg         ,
					tbl_faturamento.transp,
					tbl_faturamento.conhecimento
			FROM    tbl_faturamento
			LEFT JOIN tbl_condicao USING (condicao)
			WHERE   tbl_faturamento.posto       = $login_posto
			AND     tbl_faturamento.fabrica     in ($login_fabrica,10)
			AND     tbl_faturamento.faturamento = $faturamento";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$emissao			= trim(pg_result($res,0,emissao));
		$saida				= trim(pg_result($res,0,saida));
		$cancelada			= trim(pg_result($res,0,cancelada));
		$previsao_chegada	= trim(pg_result($res,0,previsao_chegada));
		$nota_fiscal		= trim(pg_result($res,0,nota_fiscal));
		$valor_frete		= trim(pg_result($res,0,valor_frete));
		$total_nota			= trim(pg_result($res,0,total_nota));
		$cfop				= trim(pg_result($res,0,cfop));
		$cond_pg			= trim(pg_result($res,0,cond_pg));
		$transp				= trim(pg_result($res,0,transp));
		$conhecimento		= trim(pg_result($res,0,conhecimento));

		echo "<br>";

		echo "<table width='650' border='0' cellspacing='1' cellpadding='3' align='center'>\n";
		echo "<tr>\n";
		echo "<td class='menu_top'>NOTA FISCAL</td>\n";
		echo "<td class='menu_top'>EMISSÃO</td>\n";
		echo "<td class='menu_top'>CFOP</td>\n";
		echo "<td class='menu_top'>FRETE</td>\n";
		echo "<td class='menu_top'>TOTAL DA NOTA</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>\n";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$cfop</font></td>\n";
		echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($valor_frete,2,",",".") ."</font></td>\n";
		echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total_nota,2,",",".") ."</font></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		
		$sql = "SELECT      tbl_peca.referencia                                        ,
							tbl_peca.descricao                                         ,
							tbl_peca.classificacao_fiscal                              ,
							tbl_peca.fabrica AS peca_fabrica                           ,
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
			echo "<td class='menu_top'>Classif.Fiscal</td>\n";
			echo "<td class='menu_top'>QTDE</td>\n";
			echo "<td class='menu_top'>PREÇO</td>\n";
			echo "<td class='menu_top'>PEDIDO</td>\n";
			echo "<td class='menu_top'>DATA <br> PEDIDO</td>\n";
			echo "</tr>\n";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca                 = trim(pg_result($res,$i,referencia)) ;
				$descricao            = trim(pg_result($res,$i,descricao)) ;
				$classificacao_fiscal = trim(pg_result($res,$i,classificacao_fiscal)) ;
				$qtde                 = trim(pg_result($res,$i,qtde));
				$preco                = trim(pg_result($res,$i,preco));
				$pedido               = trim(pg_result($res,$i,pedido));
				$data_pedido          = trim(pg_result($res,$i,data_pedido));
				$sua_os               = trim(pg_result($res,$i,sua_os));
				$data_os              = trim(pg_result($res,$i,data_os));
				$posto_cidade         = trim(pg_result($res,$i,posto_cidade));
				$peca_fabrica         = trim(pg_result($res,$i,peca_fabrica));
	
				$cor = "#ffffff";
				if ($i % 2 == 0) $cor = "#DDDDEE";

				if($login_fabrica==51 or $login_fabrica==81){
					echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
					echo "<td align='center' nowrap>" . ($i+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
					if( ($login_fabrica ==81 and $peca_fabrica==51 ) or ($login_fabrica ==51 and $peca_fabrica==81 )) {
						echo "<td align='center' nowrap>xxxx</td>\n";
					}else{
						echo "<td align='center' nowrap>$peca</td>\n";
					}
					echo "<td align='center' nowrap>";
					if($peca_fabrica==51 and $login_fabrica ==81){ echo "xxxx Peça GAMAITALY xxxx"; }
					if($peca_fabrica==81 and $login_fabrica ==51){ echo "xxxx Peça BESTWAY xxxx"; }
					if($peca_fabrica==$login_fabrica){ echo $descricao; }
					echo "</td>\n";
					if(strlen($classificacao_fiscal)==0){
						echo "<td align='left' nowrap>&nbsp;</td>\n";
					}else{
						echo "<td align='left' nowrap>$classificacao_fiscal</td>\n";
					}
					echo "<td align='right'>$qtde</font></td>\n";
					if( ($login_fabrica ==81 and $peca_fabrica==51 ) or ($login_fabrica ==51 and $peca_fabrica==81 )) {
						echo "<td align='center' nowrap>xxxx</td>\n";
					}else{
						echo "<td align='center' nowrap>".number_format($preco,2,",",".")."</td>\n";
					}

					if($peca_fabrica==$login_fabrica){
						echo "<td align='center'><a href=pedido_finalizado.php?pedido=$pedido target='_blank'>$pedido</a></td>\n";
					}else{
						echo "<td align='center'>$pedido</td>\n";
					}
					echo "<td align='center'>$data_pedido</td>\n";
					echo "</tr>\n";
				}else{
					echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
					echo "<td align='center' nowrap>" . ($i+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
					echo "<td align='left' nowrap>$peca</td>\n";
					echo "<td align='left' nowrap>$descricao</td>\n";
					if(strlen($classificacao_fiscal)==0){
						echo "<td align='left' nowrap>&nbsp;</td>\n";
					}else{
						echo "<td align='left' nowrap>$classificacao_fiscal</td>\n";
					}
					echo "<td align='right'>$qtde</font></td>\n";
					echo "<td align='right'>". number_format($preco,2,",",".") ."</td>\n";
					echo "<td align='center'><a href=pedido_finalizado.php?pedido=$pedido target='_blank'>$pedido</a></td>\n";
					echo "<td align='center'>$data_pedido</td>\n";
					echo "</tr>\n";
				}
			}
			
			echo "</table>\n";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
