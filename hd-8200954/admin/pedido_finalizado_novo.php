<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


$title = "PEDIDO IMPRESSÃO";
$layout_menu = 'callcenter';
$sem_menu = true;
#include "cabecalho.php";

?>

<!--
<a href="menu_callcenter.php"><font face='arial' size='-1' color='#dddddd'>Clique aqui para voltar.</font></a>
-->

<?
#------------ Le OS da Base de dados ------------#
$pedido = $_GET['pedido'];
if (strlen ($pedido) > 0) {
	$sql = "SELECT      tbl_pedido.pedido                                                ,
						tbl_pedido.pedido_blackedecker                                   ,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data                    ,
						tbl_pedido.tipo_frete                                            ,
						tbl_pedido.pedido_cliente                                        ,
						tbl_pedido.validade                                              ,
						tbl_pedido.entrega                                               ,
						tbl_pedido.obs                                                   ,
						tbl_posto.nome                                                   ,
						tbl_posto.cnpj                                                   ,
						tbl_posto.ie                                                     ,
						tbl_posto.cidade                                                 ,
						tbl_posto.estado                                                 ,
						tbl_posto.endereco                                               ,
						tbl_posto.numero                                                 ,
						tbl_posto.complemento                                            ,
						tbl_posto.fone                                                   ,
						tbl_posto.fax                                                    ,
						tbl_posto.contato                                                ,
						tbl_pedido.tabela                                                ,
						tbl_tabela.sigla_tabela                                          ,
						tbl_condicao.descricao AS condicao                               ,
						tbl_admin.login                                                  ,
						tbl_posto_fabrica.desconto                                       ,
						tbl_posto_fabrica.codigo_posto                                   ,
						tbl_tipo_posto.codigo AS codigo_tipo_posto                       ,
						tbl_posto_fabrica.transportadora_nome                            ,
						tbl_linha.nome       AS linha_nome                               
			FROM        tbl_pedido
			JOIN        tbl_posto         USING (posto)
			JOIN        tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
										AND  tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN   tbl_condicao      USING (condicao)
			LEFT JOIN   tbl_tabela        ON tbl_pedido.tabela            = tbl_tabela.tabela
			LEFT JOIN   tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN   tbl_admin         ON tbl_admin.admin              = tbl_pedido.admin
			LEFT JOIN   tbl_linha         ON tbl_pedido.linha             = tbl_linha.linha
			WHERE       tbl_pedido.pedido  = $pedido
			AND         tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	$pedido       = pg_result ($res,0,pedido);
	$tabela       = pg_result ($res,0,tabela);
	$desconto     = pg_result ($res,0,desconto);
	$obs		  = pg_result ($res,0,obs);
	if (strlen ($tabela) == 0) $tabela = "null";

	$sql = "SELECT  to_char (tbl_pedido_item.qtde,'000') AS qtde , ";
	if ($login_fabrica <> 14) {
		if($login_fabrica == 24){
			$sql .= "tbl_pedido_item.preco   , ";
		}else{
			$sql .= "tbl_tabela_item.preco   , ";
		}
	}else{
		$sql .= "tbl_pedido_item.preco,7,0 ,
				rpad (tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)),7,0)::float as total, ";
	}
		$sql .= "tbl_peca.descricao      ,
				tbl_peca.referencia     ,
				tbl_peca.unidade        ,
				tbl_peca.ipi            ,
				tbl_peca.origem         ,
				tbl_peca.peso           ,
				tbl_peca.classificacao_fiscal
			FROM      tbl_pedido_item
			JOIN      tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
			LEFT JOIN tbl_tabela_item ON (tbl_tabela_item.tabela = $tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca)
			WHERE     tbl_pedido_item.pedido = $pedido
			ORDER BY  tbl_pedido_item.pedido_item";

	flush();

	$resI = pg_exec ($con,$sql);
	
	$total = 0;
	
	for ($i = 0 ; $i < pg_numrows($resI) ; $i++) {
		if ($login_fabrica <> 14) {
			$ipi   = trim(pg_result ($resI,$i,ipi));
			$preco = pg_result ($resI,$i,qtde) * pg_result ($resI,$i,preco) ;
			$preco = $preco + ($preco * $ipi / 100);
			$total += $preco;
		}else{
			$preco       = trim(pg_result ($resI,$i,preco));
			$preco_total = trim(pg_result ($resI,$i,total));
			$total += $preco_total;
		}
	}
}

?>

<style type="text/css">

body {
	margin: 0px,0px,0px,0px;
	text-align: center;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #000000;*/
 	border-left: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	text-align: left;
	/*background: #ffffff;*/
	border-right: dotted 1px #000000;
	/*border-left: dotted 1px #000000;*/
	border-bottom: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #000000;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid #000000;
	color:#000000;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid #000000;
}

.table_line1 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #000000;
}
</style>

<table width="650px" align="center" border="0">
	<tr class="menu_top">
		<td><img src="imagens/cab_impressaopedidofinalizado.gif"></td>
	</tr>
</table>

<? if($login_fabrica == 15 and $obs<>"null" and strlen($obs) >0) { ?>
	<TABLE width='650px' >
	<TR align='left'>
		<td style='font-size: 25px' ><b>Observação da Assistência Técnica:</b> <? echo $obs; ?></td>
	</tr>
	</table>
<? } ?>

<table width="650px" class="borda" align="center" border="0">
	<tr class="menu_top">
		<td><? echo $login_fabrica_nome ?> - Depto Assistência Técnica - Pedidos</td>
	</tr>
</table>

<table width="650px" align="center" border="0" cellpadding="0" cellspacing="1">
	<tr>
		<td class="titulo">EMISSOR</td>
		<td class="conteudo">&nbsp;<? echo strtoupper (pg_result ($res,0,login))?></td>
		<td class="titulo">DATA</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,data)?></td>
		<td class="titulo" bgcolor='#eeeeee'>PEDIDO</td>
		<td class="conteudo" bgcolor='#eeeeee'>&nbsp;<b>
		<?
		if ($login_fabrica == 1) {
			$pedido_blackedecker = "00000".pg_result($res,0,pedido_blackedecker);
			$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));
			echo $pedido_blackedecker;
		}else{
			echo pg_result($res,0,pedido);
		}
		?>
		</b></td>
		<td class="titulo">PEDIDO CLIENTE</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,pedido_cliente)?></td>
	</tr>
	<tr>
		<td class="titulo" bgcolor='#eeeeee'>POSTO</td>
		<td class="conteudo" colspan="3" NOWRAP bgcolor='#eeeeee'>&nbsp;<U><B><? echo pg_result ($res,0,codigo_posto)."-".pg_result ($res,0,nome); ?></B></U></td>
		<td class="titulo">CONTATO</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,contato)?></td>
		<td class="titulo">FONE</td>
		<td class="conteudo" NOWRAP>&nbsp;<? echo pg_result ($res,0,fone)?></td>
	</tr>
	<tr>
		<td class="titulo">ENDEREÇO</td>
		<td class="conteudo" NOWRAP>&nbsp;<? echo pg_result ($res,0,endereco) . " " . pg_result ($res,0,numero) . " " . pg_result ($res,0,complemento) ?></td>
		<td class="titulo">CIDADE</td>
		<td class="conteudo" NOWRAp>&nbsp;<? echo pg_result ($res,0,cidade)?></td>
		<td class="titulo">ESTADO</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,estado)?></td>
		<td class="titulo">FAX</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,fax)?></td>
	</tr>
		<?
		$cnpj = trim (pg_result ($res,0,cnpj));
		if (strlen ($cnpj) == 14 ) {
			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		}

		if (strlen ($cnpj) == 11 ) {
			$cnpj = substr ($cnpj,0,3) . "." . substr ($cnpj,3,3) . "." . substr ($cnpj,6,3) . "-" . substr ($cnpj,9,2);
		}

		?>
	<tr>
		<td class="titulo">CNPJ</td>
		<td class="conteudo">&nbsp;<b><? echo $cnpj?></b></td>
		<td class="titulo">I.E.</td>
		<td class="conteudo" colspan='2'>&nbsp;<? echo pg_result ($res,0,ie)?></td>
		<td class="titulo">&nbsp;Linha</td>
		<td class="conteudo" colspan="2">&nbsp;<b><? echo pg_result ($res,0,linha_nome) ?></b></td>
	</tr>
	<tr>
		<td class="titulo">TRANS.</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,transportadora_nome)?></td>
		<td class="titulo">DOCUMENTO</td>
		<td class="conteudo">N/H</td>
		<td class="titulo">PAGAMENTO</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,condicao)?></td>
		<td class="titulo">MODALIDADE</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,tipo_frete)?></td>
	</tr>
	<tr>
		<td class="titulo">TOTAL</td>
		<td class="conteudo">&nbsp;<? echo number_format ($total,2,",",".")?></td>
		<td class="titulo">VALIDADE</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,validade)?></td>
		<td class="titulo">ENTREGA</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,entrega)?></td>
		<td class="titulo">CLASSE</td>
		<td class="conteudo">&nbsp;<? echo pg_result ($res,0,codigo_tipo_posto)?></td>
	</tr>
	<tr>
		<td class="titulo">MENSAGEM</td>
		<td class="conteudo" colspan="7">&nbsp;<? echo pg_result ($res,0,obs)?></td>
	</tr>
</table>

<br>
<TABLE width="650" border="1" cellspacing="0" cellpadding="1" align='center'>
<TR>
	<TD class="menu_top">IT</TD>
	<TD class="menu_top">Código</TD>
	<TD class="menu_top">Qte</TD>
	<TD class="menu_top">Atend.</TD>
	<TD class="menu_top">Descrição</TD>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<TD class="menu_top">Un s/ IPI+desc</TD>
	<TD class="menu_top">Total c/ IPI</TD>
	<TD class="menu_top" nowrap>PCP</TD>
</TR>

<?
if ($login_fabrica == 14) {
	$sql = "SELECT  case when $login_fabrica = 14 then 
						rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))),7,0)::float 
					else 
						sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) 
					end as total_pedido
			FROM  tbl_pedido
			JOIN  tbl_pedido_item USING (pedido)
			JOIN  tbl_peca        USING (peca)
			WHERE tbl_pedido_item.pedido = $pedido
			GROUP BY tbl_pedido.pedido";
	$resz = pg_exec ($con,$sql);
	
	if (pg_numrows($resz) > 0) $total  = trim(pg_result ($resz,0,total_pedido));
}

for ($i = 0 ; $i < pg_numrows ($resI) ; $i++) {
	$descricao                   = str_replace ('"','',pg_result ($resI,$i,descricao));
	$class_fiscal                = trim(pg_result ($resI,$i,classificacao_fiscal));
	$origem                      = trim(pg_result ($resI,$i,origem));
	$ipi                         = trim(pg_result ($resI,$i,ipi));
	$peso                        = trim(pg_result ($resI,$i,peso));
	$peso_estimado               = $peso_estimado + $peso;
	
	if ($login_fabrica <> 14) {
		$preco_unitario_item         = pg_result ($resI,$i,preco);
		$preco_unitario_item_sem_ipi = $preco_unitario_item - ($preco_unitario_item * $desconto / 100);
		$preco_unitario_item         = $preco_unitario_item + ($preco_unitario_item * $ipi / 100);
		$preco_total_item            = $preco_unitario_item * pg_result ($resI,$i,qtde);
	}else{
		$preco_unitario_item         = pg_result ($resI,$i,total);
		$preco_unitario_item_sem_ipi = pg_result ($resI,$i,preco);
		$preco_total_item            = $preco_unitario_item;
	}
	
	if ($origem == "TER") {
		$origem = "C";
	}else{
		$origem = "T";
	}

?>

<TR>
	<TD class="table_line"><? echo number_format ($i+1,0) ?></TD>
	<TD class="table_line" nowrap><? echo pg_result ($resI,$i,referencia) ?></TD>
	<TD class="table_line"><? echo pg_result ($resI,$i,qtde) ?></TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line" nowrap><? echo substr ($descricao,0,20) ?></TD>
	<TD class="table_line"><? echo $ipi ?>%</TD>
	<TD class="table_line"><? echo $origem ?></TD>
	<TD class="table_line" style="text-align: right;"><? echo str_replace(".",",",$preco_unitario_item_sem_ipi) ?></TD>
	<? if ($login_fabrica <> 14) { ?>
	<TD class="table_line" style="text-align: right;"><? echo number_format ($preco_total_item,2,",",".") ?></TD>
	<? }else{ ?>
	<TD class="table_line" style="text-align: right;"><? echo str_replace(".",",",$preco_total_item) ?></TD>	
	<? } ?>
	<TD class="table_line">&nbsp;</TD>
</TR>
<?
}
?>

<TR>
	<TD colspan="8" class="table_line">Valor total do saldo com IPI, em R$, com desconto de <? echo $desconto ?>% </TD>
	<TD class="table_line" style="text-align: right;"><?$total = $total - ($total * $desconto / 100) ; if ($login_fabrica <> 14) { echo number_format ($total,2,",","."); }else{ echo str_replace(".",",",$total); }?></TD>
</TR>
<TR>
	<TD colspan="8" class="table_line">Peso total estimado do saldo: </TD>
	<TD class="table_line" style="text-align: right;" nowrap><? echo $peso_estimado ?> kgs</TD>
</TR>

</TABLE>


<?
$sql = "SELECT      tbl_os.os             ,
					tbl_os.sua_os         ,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_pedido
			JOIN    tbl_pedido_item     USING (pedido)
			JOIN    tbl_peca            USING (peca)
			JOIN    tbl_os_item         USING (pedido)
			JOIN    tbl_os_produto      USING (os_produto)
			JOIN    tbl_os              USING (os)
			JOIN    tbl_produto         ON tbl_produto.produto = tbl_os_produto.produto
			WHERE tbl_pedido_item.pedido = $pedido
			GROUP BY    tbl_os.os             ,
						tbl_os.sua_os         ,
						tbl_produto.referencia,
						tbl_produto.descricao
			ORDER BY    tbl_os.sua_os;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<TABLE width='650' border='1' cellspacing='0' cellpadding='0' align='center'>";
	echo "<TR>";
	echo "<TD class='menu_top'>SUA OS</TD>";
	echo "<TD class='menu_top'>EQUIPAMENTO</TD>";
	echo "</TR>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os     = trim(pg_result($res,$i,os));
		$sua_os = trim(pg_result($res,$i,sua_os));
		$equip  = trim(pg_result($res,$i,referencia)) ."-". trim(pg_result($res,$i,descricao));
		echo "<TR>";
		echo "<TD class='table_line' align='center'><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>";
		echo "<TD class='table_line' align='left'>$equip</TD>";
		echo "</TR>";
	}
	
	echo "</TABLE>";
}
?>

<TABLE style="width: 650px;"  align='center'>
<TR>
	<TD class="menu_top">Nota Fiscal</TD>
	<TD class="menu_top">Série</TD>
	<TD class="menu_top">Valor</TD>
	<TD class="menu_top">Data</TD>
	<TD class="menu_top">Caixas</TD>
	<TD class="menu_top">Peso</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
</TABLE>

<TABLE style="width: 650px;" align='center'>
<TR>
	<TD class="table_line" style="width: 150px;">POSIÇÃO DO PEDIDO...:</TD>
	<TD class="table_line" style="width: 250px;">### COM SALDO EM ABERTO ###</TD>
	<TD class="table_line" style="width: 100px;">SEPARADO POR...:</TD>
	<TD class="table_line" style="width: 150px;">&nbsp;</TD>
</TR>
</TABLE>

<script language="JavaScript">
	window.print();
</script>

<? if ($login_fabrica == 1) { ?>
<br>
<a href="pedido_cadastro_blackedecker.php"><img border="0" src="imagens/btn_lancarnovopedido.gif"></a>
<? } ?>
