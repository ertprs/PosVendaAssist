<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$pedido = trim($_GET['pedido']);
if ($_POST['pedido']) $pedido = trim($_POST['pedido']);

/*$sql = "SELECT      tbl_pedido.pedido                                                ,
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
						tbl_tipo_posto.codigo AS codigo_tipo_posto                       ,
						tbl_posto_fabrica.transportadora_nome                            ,
						tbl_linha.nome       AS linha_nome                               
			FROM        tbl_pedido
			JOIN        tbl_posto         USING (posto)
			JOIN        tbl_posto_fabrica USING (posto)
			LEFT JOIN   tbl_condicao      USING (condicao)
			LEFT JOIN   tbl_tabela        ON tbl_pedido.tabela = tbl_tabela.tabela
			LEFT JOIN   tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			LEFT JOIN   tbl_admin         USING (admin)
			LEFT JOIN   tbl_linha         ON tbl_pedido.linha = tbl_linha.linha
			WHERE       tbl_pedido.pedido  = $pedido
			AND         tbl_pedido.fabrica = $login_fabrica";
	*/

#------------ Le OS da Base de dados ------------#
if (strlen ($pedido) > 0) {
	
		$sql = "SELECT  tbl_admin.login              ,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data,
						tbl_pedido.pedido            ,
						tbl_pedido.pedido_cliente    ,
						tbl_pedido.tipo_frete        ,
						tbl_pedido.obs               ,
						tbl_pedido.entrega           ,
						tbl_pedido.validade          ,
						tbl_posto.nome               ,
						tbl_posto.contato            ,
						tbl_posto.fone               ,
						tbl_posto.cidade             ,
						tbl_posto.estado             ,
						tbl_posto.numero             ,
						tbl_posto.complemento        ,
						tbl_posto.endereco           ,
						tbl_posto.numero             ,
						tbl_posto.complemento        ,
						tbl_posto.fax                ,
						tbl_posto.cnpj               ,
						tbl_posto.ie                 ,
						tbl_linha.nome AS linha_nome ,
						tbl_posto_fabrica.transportadora_nome AS transportadora_nome,
						tbl_condicao.descricao AS condicao
					FROM tbl_admin, tbl_pedido, tbl_posto, tbl_linha, tbl_posto_fabrica, tbl_condicao
					WHERE tbl_pedido.pedido = $pedido
					AND   tbl_pedido.posto = tbl_posto_fabrica.posto
					AND   tbl_admin.fabrica = tbl_pedido.fabrica
					AND   tbl_pedido.posto = tbl_posto.posto
					AND   tbl_pedido.condicao = tbl_condicao.condicao
					AND   tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	$login           = pg_result ($res,0,login);
	$data_pedido     = pg_result ($res,0,data);
	$pedido          = pg_result ($res,0,pedido);
	$pedido_cliente	 = pg_result ($res,0,pedido_cliente);
	$entrega		 = pg_result ($res,0,entrega);
	$validade		 = pg_result ($res,0,validade);
	$nome_posto      = pg_result ($res,0,nome);
	$contato_posto   = pg_result ($res,0,contato);
	$fone_posto      = pg_result ($res,0,fone);
	$cidade_posto    = pg_result ($res,0,cidade);
	$endereco_posto  = pg_result ($res,0,endereco);
	$numero_posto    = pg_result ($res,0,numero);
	$complemento_posto = pg_result ($res,0,complemento);
	$estado_posto    = pg_result ($res,0,estado);
	$fax_posto       = pg_result ($res,0,fax);
	$cnpj_posto      = pg_result ($res,0,cnpj);
	$ie_posto        = pg_result ($res,0,ie);
	$frete_pedido    = pg_result ($res,0,tipo_frete);
	$obs_pedido      = pg_result ($res,0,obs);
	$linha_nome      = pg_result ($res,0,linha_nome);
	$transportadora_nome  = pg_result ($res,0,transportadora_nome);
	$condicao_descricao   = pg_result ($res,0,condicao);

/*
	$pedido   = pg_result ($res,0,pedido);
	$tabela   = pg_result ($res,0,tabela);
	$desconto = pg_result ($res,0,desconto);

	if (strlen ($tabela) == 0) $tabela = "null";

	$sql = "SELECT  to_char (tbl_pedido_item.qtde,'000') AS qtde ,
					tbl_tabela_item.preco   ,
					tbl_peca.descricao      ,
					tbl_peca.referencia     ,
					tbl_peca.unidade        ,
					tbl_peca.ipi            ,
					tbl_peca.origem         ,
					tbl_peca.peso           ,
					tbl_peca.classificacao_fiscal,
					tbl_os_produto.os
			FROM      tbl_pedido_item
			JOIN      tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
			LEFT JOIN tbl_os_item ON (tbl_os_item.pedido = tbl_pedido_item.pedido AND tbl_os_item.peca = tbl_pedido_item.peca)
			LEFT JOIN tbl_os_produto   ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			LEFT JOIN tbl_tabela_item ON (tbl_tabela_item.tabela = $tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca)
			WHERE     tbl_pedido_item.pedido = $pedido
			ORDER BY  tbl_pedido_item.pedido_item";

	flush();

	$resI = pg_exec ($con,$sql);
	
	$total = 0;
	
	for ($i = 0 ; $i < pg_numrows ($resI) ; $i++) {
		$ipi   = trim(pg_result ($resI,$i,ipi));
		$preco = pg_result ($resI,$i,qtde) * pg_result ($resI,$i,preco) ;
		$preco = $preco + ($preco * $ipi / 100);
		$total += $preco;
	}
}*/

	$sql = "SELECT  tbl_admin.login     ,
					tbl_peca.referencia ,
					tbl_peca.descricao  ,
					tbl_pedido_item.qtde,
					tbl_os_produto.os
			FROM    tbl_pedido, tbl_peca, tbl_pedido_item, tbl_admin
			WHERE   tbl_peca.fabrica        = $login_fabrica
			AND     tbl_pedido_item.pedido  = $pedido
			AND     tbl_admin.fabrica = tbl_peca.fabrica
			AND     tbl_pedido.pedido = tbl_pedido_item.pedido
			AND     tbl_pedido_item.peca = tbl_peca.peca
			AND     tbl_pedido_item.peca = tbl_os_item.peca
			AND     tbl_os_item.os_produto = tbl_os_produto.os_produto";

	$resI = pg_exec ($con,$sql);

		$login           = pg_result ($resI,0,login);
		$referencia_peca = pg_result ($resI,0,referencia);
		$descricao_peca  = pg_result ($resI,0,descricao);
		$qtde_peca       = pg_result ($resI,0,qtde);
		$os              = pg_result ($resI,0,os);

$title = "PEDIDO";
$layout_menu = 'callcenter';

include "cabecalho.php";

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
	border-bottom: solid 1px #000000;
	border-right: solid 1px #000000;
	border-left: solid 1px #000000;
	padding: 1px,1px,1px,1px;
}

.conteudo {
	font-family: bold Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	text-align: left;
	background: #ffffff;
	border-right: solid 1px #000000;
	border-left: solid 1px #000000;
	border-bottom: solid 1px #000000;
	padding: 1px,1px,1px,1px;
}

.borda {
	border: solid 1px #000000;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid #000000;
	color:#000000;
	padding: 1px,1px,1px,1px;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid #000000;
	padding: 1px,1px,1px,1px;
}

.table_line1 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1px solid #000000;
}
</style>

<br>

<table width="650px" align="center" border="1" cellpadding="1" cellspacing="0">
	<tr>
		<td class="titulo">EMISSOR</td>
		<td class="conteudo">&nbsp;<? echo strtoupper($login) ?> </td>

		<td class="titulo">DATA</td>
		<td class="conteudo">&nbsp;<? echo $data_pedido ?></td>
		<td class="titulo">PEDIDO</td>
		<td class="conteudo">&nbsp;<? echo $pedido ?></td>
		<td class="titulo">PEDIDO CLIENTE</td>
		<td class="conteudo">&nbsp;<? echo $pedido_cliente ?></td>
	</tr>
	<tr>
		<td class="titulo">NOME</td>
		<td class="conteudo" colspan="3" NOWRAP>&nbsp;<? echo $nome_posto ?></td>
		<td class="titulo">CONTATO</td>
		<td class="conteudo">&nbsp; <? echo $contato_posto ?> </td>
		<td class="titulo">FONE</td>
		<td class="conteudo" NOWRAP>&nbsp; <? echo $fone_posto?> </td>
	</tr>
	<tr>
		<td class="titulo">ENDEREÇO</td>
		<td class="conteudo" NOWRAP>&nbsp; <? echo $endereco_posto . " " . $numero_posto . " " . $complemento_posto ?> </td>
		<!-- <? echo pg_result ($res,0,endereco) . " " . pg_result ($res,0,numero) . " " . pg_result ($res,0,complemento) ?> -->
		<td class="titulo">CIDADE</td>
		<td class="conteudo" NOWRAp>&nbsp; <? echo $cidade_posto ?> </td>
		<td class="titulo">ESTADO</td>
		<td class="conteudo">&nbsp; <? echo $estado_posto ?> </td>
		<td class="titulo">FAX</td>
		<td class="conteudo">&nbsp; <? echo $fax_posto ?> </td>
	</tr>

		<!-- FORMATAÇÃO DO CNPJ -->
		<?
		$cnpj_posto = trim (pg_result ($res,0,cnpj));

		if (strlen ($cnpj_posto) == 14 ) {
			$cnpj_posto = substr ($cnpj_posto,0,2) . "." . substr ($cnpj_posto,2,3) . "." . substr ($cnpj_posto,5,3) . "/" . substr ($cnpj_posto,8,4) . "-" . substr ($cnpj_posto,12,2);
		}

		if (strlen ($cnpj_posto) == 11 ) {
			$cnpj_posto = substr ($cnpj_posto,0,3) . "." . substr ($cnpj_posto,3,3) . "." . substr ($cnpj_posto,6,3) . "-" . substr ($cnpj_posto,9,2);
		}

		?>

	<tr>
		<td class="titulo">CNPJ</td>
		<td class="conteudo">&nbsp;<b> <? echo $cnpj_posto?> </b></td>
		<td class="titulo">I.E.</td>
		<td class="conteudo" colspan='2'>&nbsp; <? echo $ie_posto ?></td>
		<td class="titulo">&nbsp;Linha</td>
		<td class="conteudo" colspan="2">&nbsp;<b> <? echo $linha_nome ?> </b></td>
	</tr>
	<tr>
		<td class="titulo">TRANS.</td>
		<td class="conteudo">&nbsp; <? echo $transportadora_nome?> </td>
		<td class="titulo">DOCUMENTO</td>
		<td class="conteudo">N/H</td>
		<td class="titulo">PAGAMENTO</td>
		<td class="conteudo">&nbsp; <? $condicao_descricao?> </td>
		<td class="titulo">MODALIDADE</td>
		<td class="conteudo">&nbsp; <? echo $frete_pedido?> </td>
	</tr>
	<tr>
		<td class="titulo">TOTAL</td>
		<td class="conteudo">&nbsp;<!-- <? echo number_format ($total,2,",",".")?> --></td>
		<td class="titulo">VALIDADE</td>
		<td class="conteudo">&nbsp; <? echo $validade?> </td>
		<td class="titulo">ENTREGA</td>
		<td class="conteudo">&nbsp; <? echo $entrega?> </td>
		<td class="titulo">CLASSE</td>
		<td class="conteudo">&nbsp;<!-- <? echo pg_result ($res,0,codigo_tipo_posto)?> --></td>
	</tr>
	<tr>
		<td class="titulo">MENSAGEM</td>
		<td class="conteudo" colspan="7">&nbsp; <? echo $obs_pedido ?> </td>
	</tr>
</table>

<br>
<TABLE width="650" border="1" cellspacing="0" cellpadding="0">
<TR>
	<TD class="menu_top">IT</TD>
	<TD class="menu_top">Código</TD>
	<TD class="menu_top">Qte</TD>
	<TD class="menu_top">Descrição</TD>
	<TD class="menu_top">Total</TD>
	<TD class="menu_top" nowrap>OS</TD>
</TR>

<?
/*for ($i = 0 ; $i < pg_numrows ($resI) ; $i++) {
	$descricao                   = str_replace ('"','',pg_result ($resI,$i,descricao));
	$preco_unitario_item         = pg_result ($resI,$i,preco);
	$preco_unitario_item_sem_ipi = $preco_unitario_item - ($preco_unitario_item * $desconto / 100);
	$preco_unitario_item         = $preco_unitario_item + ($preco_unitario_item * $ipi / 100);
	$preco_total_item            = $preco_unitario_item * pg_result ($resI,$i,qtde);
*/
?>

 <TR>
	<TD class="table_line">&nbsp; <? echo number_format ($i+1,0) ?> </TD>
	<TD class="table_line" nowrap>&nbsp;<? echo $referencia_peca ?></TD>
	<TD class="table_line">&nbsp;<? echo $qtde_peca ?></TD>
	<TD class="table_line" nowrap>&nbsp;<? echo $descricao_peca ?></TD>
	<TD class="table_line" style="text-align: right;">&nbsp;<!-- <? echo number_format ($preco_total_item,2,",",".") ?> --></TD>
	<TD class="table_line"> <? $os ?> &nbsp;</TD>
</TR> 
<?
// }
?>

<!-- <TR>
	<TD colspan="4" class="table_line" style="text-align: right;"><b>Valor total</b>&nbsp;&nbsp;</TD>
	<TD class="table_line" style="text-align: right;"><b><? $total = $total - ($total * $desconto / 100) ; echo number_format ($total,2,",",".")?></b></TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
-->
</TABLE>


<?
/*$sql = "SELECT      tbl_os.sua_os         ,
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
			GROUP BY    tbl_os.sua_os         ,
						tbl_produto.referencia,
						tbl_produto.descricao
			ORDER BY    tbl_os.sua_os;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<TABLE width='650' border='1' cellspacing='0' cellpadding='0'>";
	echo "<TR>";
	echo "<TD class='menu_top'>SUA OS</TD>";
	echo "<TD class='menu_top'>EQUIPAMENTO</TD>";
	echo "</TR>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$sua_os = trim(pg_result($res,$i,sua_os));
		$equip  = trim(pg_result($res,$i,referencia)) ."-". trim(pg_result($res,$i,descricao));
		echo "<TR>";
		echo "<TD class='table_line' align='center'>$sua_os</TD>";
		echo "<TD class='table_line' align='left'>$equip</TD>";
		echo "</TR>";
	}
	
	echo "</TABLE>";*/
}
?>

<p>

<? include 'rodape.php'; ?>