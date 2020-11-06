<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

// somente Ibratele
$sql = "SELECT COUNT(1) as total FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente IS TRUE";
$res = pg_query($con,$sql);
if(pg_numrows($res) > 0){
	$total = pg_result($res,0,0);
	if($total == 0){
		header("Location: pedido_parametros.php");
		exit;
	}
}

if(strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

$pedido = $_GET['pedido'];
if(strlen($_POST['pedido']) > 0) $pedido = $_POST['pedido'];

if($btn_acao == 'gravar'){
	$exportado = $_POST['exportado'];

	if(strlen($exportado) > 0 AND $exportado == 't' AND strlen($pedido) > 0){
		if($login_fabrica <> 88){
			$campos = "exportado        = current_timestamp,
					   status_pedido    = 2,";
		}
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = " SELECT fn_pedido_finaliza(pedido,fabrica)
				FROM tbl_pedido
				WHERE pedido = $pedido
				AND   finalizado IS NULL";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		
		$sql = "UPDATE tbl_pedido SET
					$campos
					exportado_manual = '$exportado' 
				WHERE pedido = $pedido 
				AND   fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
	}

	if(empty($msg_erro)) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		echo "<script>";
		echo "	window.location = 'pedido_nao_exportado_consulta.php';";
		echo "</script>";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
	//header("Location: pedido_nao_exportado_consulta.php");
	//exit;
	
}

$title = "PEDIDO NÃO EXPORTADO";
$layout_menu = 'callcenter';
//$sem_menu = true;

include "cabecalho.php";

#------------ Le OS da Base de dados ------------#
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                                ,
					tbl_pedido.seu_pedido                                            ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data                    ,
					tbl_pedido.tipo_frete                                            ,
					tbl_pedido.pedido_cliente                                        ,
					tbl_pedido.validade                                              ,
					tbl_pedido.entrega                                               ,
                    tbl_pedido.obs                                                   ,
					tbl_pedido.valor_frete                                           ,
					tbl_posto.nome                                                   ,
					tbl_posto.cnpj                                                   ,
					tbl_posto.ie                                                     ,
					tbl_posto_fabrica.contato_cidade      AS cidade                  ,
					tbl_posto_fabrica.contato_estado      AS estado                  ,
					tbl_posto_fabrica.contato_endereco    AS endereco                ,
					tbl_posto_fabrica.contato_numero      AS numero                  ,
					tbl_posto_fabrica.contato_complemento AS complemento             ,
					tbl_posto.fone                                                   ,
					tbl_posto.fax                                                    ,
					tbl_posto.contato                                                ,
					tbl_pedido.tabela                                                ,
					tbl_tabela.sigla_tabela                                          ,
					tbl_condicao.descricao AS condicao                               ,
					tbl_admin.login                                                  ,
					tbl_posto_fabrica.desconto                                       ,
					tbl_tipo_posto.codigo AS codigo_tipo_posto                       ,
					CASE WHEN $login_fabrica = 88
                         THEN tbl_transportadora.fantasia
                         ELSE tbl_posto_fabrica.transportadora_nome
                    END                                     AS transportadora_nome      ,
					tbl_linha.nome       AS linha_nome
			FROM    tbl_pedido
			JOIN    tbl_posto         USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
       LEFT JOIN    tbl_condicao      USING (condicao)
       LEFT JOIN    tbl_tabela        ON tbl_pedido.tabela = tbl_tabela.tabela
       LEFT JOIN    tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
       LEFT JOIN    tbl_admin         ON tbl_admin.admin  = tbl_pedido.admin
       LEFT JOIN    tbl_linha         ON tbl_linha.linha  = tbl_pedido.linha
       LEFT JOIN    tbl_transportadora ON tbl_transportadora.transportadora = tbl_pedido.transportadora
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	
	//echo nl2br($sql);
	
	$pedido         = pg_fetch_result ($res,0,pedido);
	$seu_pedido     = pg_fetch_result ($res,0,seu_pedido);
	$tabela         = pg_fetch_result ($res,0,tabela);
	$desconto       = pg_fetch_result ($res,0,desconto);
	$obs_pa         = @pg_fetch_result ($res,0,obs);
    $data           = pg_fetch_result ($res,0,data);
	$valor_frete	= pg_fetch_result ($res,0,valor_frete);

	

	if (strlen ($tabela) == 0) $tabela = "null";

		$sql = "SELECT  
					CASE WHEN tbl_os_item.qtde > 0 THEN to_char(tbl_os_item.qtde,'000') ELSE to_char(tbl_pedido_item.qtde,'000') END AS qtde ,
					/*to_char (tbl_pedido_item.qtde,'000') AS qtde ,*/
					tbl_tabela_item.preco,
					tbl_peca.descricao,
					tbl_peca.referencia,
					tbl_peca.unidade,
					tbl_peca.ipi,
					tbl_peca.origem,
					tbl_peca.peso,
					tbl_os_produto.os,
					tbl_peca.classificacao_fiscal
				FROM tbl_pedido_item
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
					LEFT JOIN tbl_tabela_item ON (tbl_tabela_item.tabela = $tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca)
					LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido_item.pedido 
						AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item and tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE
					tbl_pedido_item.pedido = $pedido
				ORDER BY
					tbl_pedido_item.pedido_item";
	//echo nl2br($sql);
	flush();

	$resI = pg_query ($con,$sql);
	
	$total = 0;
	
	for ($i = 0 ; $i < pg_num_rows ($resI) ; $i++) {
		$ipi   = trim(pg_fetch_result ($resI,$i,ipi));
		$preco = pg_fetch_result ($resI,$i,qtde) * pg_fetch_result ($resI,$i,preco) ;
		if($login_fabrica <> 88){
			$preco = $preco + ($preco * $ipi / 100);
		}
		$total += $preco;
	}

}


$pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;

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
	background: #ffffff;
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

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 0px solid #000000;
}

</style>
<? if($login_fabrica == 15 and $obs_pa<>"null" and strlen($obs_pa) >0) { ?>
	<TABLE width='650px' align='center' bgcolor='#EEEEEE'>
	<TR align='left'>
		<td style='font-size: 25px' ><b>Observação da Assistência Técnica:</b> <? echo $obs_pa; ?></td>
	</tr>
	</table>
<? } ?>


<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
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
		<td class="conteudo">&nbsp;<? echo strtoupper (pg_fetch_result ($res,0,login))?></td>
		<td class="titulo">DATA</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,data)?></td>
		<td class="titulo">PEDIDO</td>
		<td class="conteudo">&nbsp;<? echo $pedido_aux;?></td>
		<td class="titulo">PEDIDO CLIENTE</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,pedido_cliente)?></td>
	</tr>
	<tr>
		<td class="titulo">NOME</td>
		<td class="conteudo" colspan="3" NOWRAP>&nbsp;<? echo pg_fetch_result ($res,0,nome)?></td>
		<td class="titulo">CONTATO</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,contato)?></td>
		<td class="titulo">FONE</td>
		<td class="conteudo" NOWRAP>&nbsp;<? echo pg_fetch_result ($res,0,fone)?></td>
	</tr>
	<tr>
		<td class="titulo">ENDEREÇO</td>
		<td class="conteudo" NOWRAP>&nbsp;<? echo pg_fetch_result ($res,0,endereco) . " " . pg_fetch_result ($res,0,numero) . " " . pg_fetch_result ($res,0,complemento) ?></td>
		<td class="titulo">CIDADE</td>
		<td class="conteudo" NOWRAp>&nbsp;<? echo pg_fetch_result ($res,0,cidade)?></td>
		<td class="titulo">ESTADO</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,estado)?></td>
		<td class="titulo">FAX</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,fax)?></td>
	</tr>
		<?
		$cnpj = trim (pg_fetch_result ($res,0,cnpj));
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
		<td class="conteudo" colspan='2'>&nbsp;<? echo pg_fetch_result ($res,0,ie)?></td>
		<td class="titulo">&nbsp;Linha</td>
		<td class="conteudo" colspan="2">&nbsp;<b><? echo pg_fetch_result ($res,0,linha_nome) ?></b></td>
	</tr>
	<tr>
		<td class="titulo">TRANS.</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,transportadora_nome)?></td>
		<td class="titulo">DOCUMENTO</td>
		<td class="conteudo">N/H</td>
		<td class="titulo">PAGAMENTO</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,condicao)?></td>
		<td class="titulo">MODALIDADE</td>
		<td class="conteudo">&nbsp;
			<? 
				if($login_fabrica == 88){
					echo (pg_fetch_result ($res,0,tipo_frete) == "NOR") ? "NORMAL" : "URGENTE";
				} else {
					echo pg_fetch_result ($res,0,tipo_frete);
				}
			?>
		</td>

	</tr>
	<tr>
		<td class="titulo">TOTAL</td>
		<td class="conteudo">&nbsp;
			<? 
				echo ($login_fabrica == 88) ? number_format ($total - (($total * $desconto) / 100),2,",",".") : number_format ($total,2,",",".");
			?>
		</td>
		<td class="titulo">VALIDADE</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,validade)?></td>
		<td class="titulo">ENTREGA</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,entrega)?></td>
		<td class="titulo">CLASSE</td>
		<td class="conteudo">&nbsp;<? echo pg_fetch_result ($res,0,codigo_tipo_posto)?></td>
	</tr>
<?
                if($login_fabrica == 88){
?>
    <tr>
        <td class="titulo">VALOR FRETE</td>
        <td class="conteudo" colspan="7">&nbsp;<?=number_format($valor_frete,2,',','');?></td>
    </tr>
<?
                }
?>
	<tr>
		<td class="titulo">MENSAGEM</td>
		<td class="conteudo" colspan="7">&nbsp;<? echo pg_fetch_result ($res,0,obs)?></td>
	</tr>
</table>

<br>
<TABLE width="650" border="1" cellspacing="0" cellpadding="0" align='center'>
<TR>
	<TD class="menu_top">IT</TD>
	<TD class="menu_top">Código</TD>
	<TD class="menu_top">Qte</TD>
	<TD class="menu_top">Atend.</TD>
	<TD class="menu_top">Descrição</TD>
	<? if ($login_fabrica == 80){?>
		<TD class="menu_top">Nº da OS</TD>
	<?}?>
	<TD class="menu_top">IPI</TD>
	<TD class="menu_top">C</TD>
	<? if ($login_fabrica == 88){?>
		<TD class="menu_top">Unitario</TD>
	<?} else {?>
	<TD class="menu_top">un/s IPI+desc</TD>
	<?}?>
	<TD class="menu_top">Total</TD>
	<TD class="menu_top" nowrap>PCP</TD>
</TR>

<?
for ($i = 0 ; $i < pg_num_rows ($resI) ; $i++) {
	$descricao                   = str_replace ('"','',pg_fetch_result ($resI,$i,descricao));
	$class_fiscal                = trim(pg_fetch_result ($resI,$i,classificacao_fiscal));
	$origem                      = trim(pg_fetch_result ($resI,$i,origem));
	$ipi                         = trim(pg_fetch_result ($resI,$i,ipi));
	$peso                        = trim(pg_fetch_result ($resI,$i,peso));
	$os                          = trim(pg_fetch_result ($resI,$i,os));
	$qtde 				= trim(pg_fetch_result($resI,$i,'qtde'));
	$peso_estimado               = $peso * $qtde;
	$peso_total +=$peso_estimado;
	$preco_unitario_item         = pg_fetch_result ($resI,$i,preco);
	$preco_unitario_item_sem_ipi = $preco_unitario_item - ($preco_unitario_item * $desconto / 100);
	if($login_fabrica <> 88){
		$preco_unitario_item         = $preco_unitario_item + ($preco_unitario_item * $ipi / 100);
	}
	$preco_total_item            = $preco_unitario_item * pg_fetch_result ($resI,$i,qtde);

	$preco_unitario_item_sem_ipi = ($login_fabrica == 88) ? $preco_unitario_item : $preco_unitario_item_sem_ipi;

	if ($origem == "TER") {
		$origem = "C";
	}else{
		$origem = "T";
	}
?>

<TR>
	<TD class="table_line"><? echo number_format ($i+1,0) ?></TD>
	<TD class="table_line" nowrap><? echo pg_fetch_result ($resI,$i,referencia) ?></TD>
	<TD class="table_line"><? echo pg_fetch_result ($resI,$i,qtde) ?></TD>
	<TD class="table_line" style="width: 30px;">&nbsp;</TD>
	<TD class="table_line" nowrap><? echo substr ($descricao,0,35) ?></TD>
	<? if ($login_fabrica == 80){?>
		<TD class="table_line"><?echo $os?></TD>
	<?}?>
	<TD class="table_line"><? echo $ipi ?>%</TD>
	<TD class="table_line"><? echo $origem ?></TD>
	<TD class="table_line" style="text-align: right;">
		<? 
			if($login_fabrica == 88){
				$preco_unitario_item_sem_ipi = $preco_unitario_item_sem_ipi - ( ($preco_unitario_item_sem_ipi * $desconto) / 100);
			}
			echo number_format($preco_unitario_item_sem_ipi,2,",",".") ;
		?>
	</TD>
	<TD class="table_line" style="text-align: right;">
	<? 
			if($login_fabrica == 88){
				$preco_total_item = $preco_total_item - ( ($preco_total_item * $desconto) / 100);
			} 
			echo number_format ($preco_total_item,2,",",".") 
	?>
	</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>
<?
}
?>

<TR>
	<TD colspan="8" class="table_line">Valor total do saldo com IPI, em R$, com desconto de <? echo $desconto ?>% <?=$login_fabrica == 88 ? "mais valor do frete " : ""?></TD>
	<TD class="table_line" style="text-align: right;"><? $total = $total - ($total * $desconto / 100); if($login_fabrica == 88){ $total = $total + $valor_frete; }; echo number_format ($total,2,",",".")?></TD>
    <TD class="table_line">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="8" class="table_line">Peso total estimado do saldo: </TD>
	<TD class="table_line" style="text-align: right;" nowrap><? echo $peso_total ?> kgs</TD>
	<TD class="table_line">&nbsp;</TD>
</TR>

</TABLE>


<?
$sql = "SELECT      tbl_os.sua_os         ,
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
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	echo "<TABLE width='650' border='1' cellspacing='0' cellpadding='0' align='center'>";  
	echo "<TR>";
	echo "<TD class='menu_top'>SUA OS</TD>";
	echo "<TD class='menu_top'>EQUIPAMENTO</TD>";
	echo "</TR>";
	
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$sua_os = trim(pg_fetch_result($res,$i,sua_os));
		$equip  = trim(pg_fetch_result($res,$i,referencia)) ."-". trim(pg_fetch_result($res,$i,descricao));
		echo "<TR>";
		echo "<TD class='table_line' align='center'>$sua_os</TD>";
		echo "<TD class='table_line' align='left'>$equip</TD>";
		echo "</TR>";
	}
	
	echo "</TABLE>";
}
?>

<TABLE style="width: 650px;" align='center'>
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
<br>

<!-- 
<TABLE style="width: 650px;">
<TR>
	<TD class="table_line" style="width: 150px;">POSIÇÃO DO PEDIDO...:</TD>
	<TD class="table_line" style="width: 250px;">### COM SALDO EM ABERTO ###</TD>
	<TD class="table_line" style="width: 100px;">SEPARADO POR...:</TD>
	<TD class="table_line" style="width: 150px;">&nbsp;</TD>
</TR>
</TABLE>
 -->
<?
// mostra etiqueta fabrica precision
if ($login_fabrica==80){
	echo "<table border='1' cellpadding='5' cellspacing='10' align='center'>";

	$sql = "SELECT  to_char (tbl_pedido_item.qtde,'000') AS qtde ,
						tbl_tabela_item.preco   ,
						tbl_peca.descricao as descricao_peca    ,
						tbl_peca.peca      ,
						tbl_peca.referencia as referencia_peca    ,
						tbl_peca.classificacao_fiscal
				FROM      tbl_pedido_item
				JOIN      tbl_peca        ON tbl_pedido_item.peca = tbl_peca.peca
				LEFT JOIN tbl_tabela_item ON (tbl_tabela_item.tabela = $tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca)
				WHERE     tbl_pedido_item.pedido = $pedido
				ORDER BY  tbl_pedido_item.pedido_item";

	$res = pg_query ($con,$sql);
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$descricao_peca                   = str_replace ('"','',pg_fetch_result ($res,$i,descricao_peca));
		$peca               = trim(pg_fetch_result ($res,$i,peca));
		$referencia_peca               = trim(pg_fetch_result ($res,$i,referencia_peca));
		$qtde               = trim(pg_fetch_result ($res,$i,qtde));

		$sql2 = "SELECT     tbl_os.sua_os         ,
							tbl_os.consumidor_nome         ,
							tbl_produto.referencia as referencia_prod,
							tbl_produto.descricao as decricao_prod,
							tbl_posto_fabrica.codigo_posto as codigo_posto,
							tbl_posto.nome as posto_nome
					FROM    tbl_pedido
					JOIN    tbl_pedido_item     USING (pedido)
					JOIN    tbl_peca            on tbl_peca.peca =$peca 
					JOIN    tbl_os_item         USING (pedido)
					JOIN    tbl_os_produto      USING (os_produto)
					JOIN    tbl_os              USING (os)
					JOIN    tbl_produto         ON tbl_produto.produto = tbl_os_produto.produto
					join	tbl_posto_fabrica	ON tbl_pedido.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
					join	tbl_posto			ON tbl_pedido.posto = tbl_posto.posto
					WHERE tbl_pedido_item.pedido = $pedido and tbl_os_item.peca =$peca 
					ORDER BY    tbl_os.sua_os;";

		$res2 = pg_query ($con,$sql2);

		if (pg_num_rows($res2)>0) {
			$sua_os              = trim(pg_fetch_result ($res2,0,sua_os));
			$decricao_prod             = trim(pg_fetch_result ($res2,0,decricao_prod));
			$consumidor_nome              = trim(pg_fetch_result ($res2,0,consumidor_nome));
			$referencia_prod              = trim(pg_fetch_result ($res2,0,referencia_prod));
			$codigo_posto              = trim(pg_fetch_result ($res2,0,codigo_posto));
			$posto_nome              = trim(pg_fetch_result ($res2,0,posto_nome));
		

			if (($i%4)==0){
				if($i==0){
					echo "<tr><td>";
					}
				else{
					echo "</td></tr><tr><td>";
					}
			}else{
			echo "</td><td>";
			}
		?>
				<table align='center' width='220' border='0' cellpadding='2 ' cellspacing='0' style='font-family:Verdana, Arial, Helvetica, sans-serif; font-size:10px'>
				  <tr>
					<td  width='220'colspan='2' align='center'><font face='Arial, Helvetica, sans-serif' style='font-size:12px;'><B>AMVOX PRECISION</B></font></td>
				  </tr>
				  <tr>
					<td width='70'><?=$referencia_peca?></td>
					<td rowspan='2' width='150' ><?=$descricao_peca?></td>
				  </tr>
				  <tr>
					<td>QTD <?=$qtde?></td>
				  </tr>
				  <tr>
					<td colspan='2' align='center'><font face='Arial, Helvetica, sans-serif' style='font-size:12px;'><B>OS - <?=$sua_os?></B></font></td>
				  </tr>
				  <tr>
					<td colspan='2'><?=$consumidor_nome?></td>
				  </tr>
				  <tr>
					<td><?=$referencia_prod?></td>
					<td height='30'><?=$decricao_prod?></td>
				  </tr>
				  <tr>
					<td><font face='Arial, Helvetica, sans-serif' style='font-size:12px;'><B><?=$pedido_aux?></B></font></td>
					<td><font face='Arial, Helvetica, sans-serif' style='font-size:12px;'><B><?=$data?></B></font></td>
				  </tr>
				  <tr>
					<td colspan='2'><?=$codigo_posto?></td>
				  </tr>
				  <tr>
					<td colspan='2'><?=$posto_nome?></td>
				  </tr>
				</table>
	<?
		}
	}
}
?>
	</td>
  </tr>

</table>
<?// fim mostra etiqueta precision?>
<br>
<br>

<TABLE style="width: 500px;" class="table_line2" align='center'>
<FORM NAME='frm_exporta' METHOD='POST' ACTION="<? echo $PHP_SELF; ?>">
<input type='hidden' name='pedido' value='<? echo $pedido ?>'>
<TR>
	<TD>Pedido exportado? </TD>
	<TD>
		<INPUT TYPE="radio" NAME="exportado" value='t'> Sim &nbsp;&nbsp;
		<INPUT TYPE="radio" NAME="exportado" value='f' checked> Não &nbsp;&nbsp;
	</TD>
	<TD>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_exporta.btn_acao.value == '' ) { document.frm_exporta.btn_acao.value='gravar' ; document.frm_exporta.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar exportação" border='0'>
	</TD>
</TR>
</FORM>
</TABLE>

<p>

<? include "rodape.php"; ?>
