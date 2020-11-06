<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

session_start();

$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result ($res,0,os_item_subconjunto);
	if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}


#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.obs                                                        ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_os.serie                                                      ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.consumidor_revenda                                         ,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN    tbl_produto USING (produto)
			JOIN         tbl_posto USING (posto)
			JOIN         tbl_posto_fabrica  ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$data_fechamento             = pg_result ($res,0,data_fechamento);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_result ($res,0,referencia);
		$produto_descricao           = pg_result ($res,0,descricao);
		$produto_voltagem            = pg_result ($res,0,voltagem);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$obs                         = pg_result ($res,0,obs);
		$codigo_posto                = pg_result ($res,0,codigo_posto);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
	}
}

$title = "Finalização de lançamento de itens na Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>

<div id="container">
<!-- ------------- Formulário ----------------- -->
<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->
	<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="os" value="<? echo $HTTP_GET_VARS['os'] ?>">
	<div id="page">
		<h2>Informações sobre a Ordem de Serviço
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft2" style="width: 200px; ">
				OS FABRICANTE
			</div>
			<div id="contentleft2" style="width: 200px; ">
				DATA DE ABERTURA
			</div>
		</div>

		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 200px;font:75%">
				<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
			</div>
			<div id="contentleft" style="width: 200px;font:75%">
				<? echo $data_abertura ?>
			</div>
		</div>
		</h2>
	</div>

</div>

<!-- ------------- INFORMAÇÕES DO CONSUMIDOR ------------------ -->

<? if ($consumidor_revenda <> 'R') { ?>
<div id="container">
<div id="page">
	<h2>Informações sobre o CONSUMIDOR
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 250px; ">
			NOME DO CONSUMIDOR
		</div>
		<div id="contentleft2" style="width: 150px; ">
			CIDADE
		</div>
		<div id="contentleft2" style="width: 80px; ">
			ESTADO
		</div>
		<div id="contentleft2" style="width: 130px; ">
			FONE
		</div>
	</div>

	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $consumidor_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $consumidor_cidade ?>
		</div>
		<div id="contentleft" style="width: 80px;font:75%">
			<? echo $consumidor_estado ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $consumidor_fone ?>
		</div>
	</div>
	</h2>
</div>
</div>
<? } ?>

<!-- ------------- INFORMAÇÕES DA REVENDA------------------ -->

<div id="container">
<div id="page">
	<h2>Informações sobre a REVENDA
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 150px; ">
			CNPJ REVENDA
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NOME DA REVENDA
		</div>
		<div id="contentleft2" style="width: 150px; ">
			NOTA FISCAL N.
		</div>
		<div id="contentleft2" style="width: 130px; ">
			DATA DA N.F.
		</div>
	</div>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_cnpj ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_nome ?>
		</div>
		<div id="contentleft" style="width: 150px;font:75%">
			<? echo $nota_fiscal ?>
		</div>
		<div id="contentleft" style="width: 130px;font:75%">
			<? echo $data_nf ?>
		</div>
	</div>
	</h2>
</div>

</div>

<!-- ------------- INFORMAÇÕES DO PRODUTO------------------ -->

<div id="container">
<div id="page">
	<h2>Informações sobre o PRODUTO
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft2" style="width: 100px; ">
			REFERÊNCIA
		</div>
		<div id="contentleft2" style="width: 250px; ">
			DESCRIÇÃO
		</div>
		<? if ($login_fabrica == 1) { ?>
		<div id="contentleft2" style="width: 75px; ">
			VOLTAGEM
		</div>
		<div id="contentleft2" style="width: 125px; ">
			CÓD. FABRICAÇÃO
		</div>
		<? } ?>
		<div id="contentleft2" style="width: 75px; ">
			SÉRIE
		</div>
	</div>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 100px;font:75%">
			<? echo $produto_referencia ?>
		</div>
		<div id="contentleft" style="width: 250px;font:75%">
			<? echo $produto_descricao ?>
		</div>
		<? if ($login_fabrica == 1) { ?>
		<div id="contentleft" style="width: 75px;font:75%">
			<? echo $produto_voltagem ?>
		</div>
		<div id="contentleft" style="width: 125px;font:75%">
			<? echo $codigo_fabricacao ?>
		</div>
		<? } ?>
		<div id="contentleft" style="width: 75px;font:75%">
			<? echo $serie ?>
		</div>
	</div>
	</h2>
</div>

</div>

<!-- ------------- INFORMAÇÕES DO DEFEITO ------------------ -->

<div id="container">
<div id="page">
	<h2>Defeito Apresentado <? if ($consumidor_revenda <> 'R') { ?> pelo Cliente<? } ?>
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $defeito_reclamado ?>
		</div>
	</div>
	</h2>
</div>
</div>
<div id="container">
<div id="page">
	<h2>Aparência Geral do Produto
	<div id="contentcenter" style="width: 650px;">
		<div id="contentleft" style="width: 650px;font:75%">
			<? echo $aparencia_produto ?>
		</div>
	</div>
	</h2>
</div>
</div>

<div id="container">
	<div id="page">
		<h2>Acessórios Deixados <? if ($consumidor_revenda <> 'R') { ?> pelo Cliente<? } ?>
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 650px;font:75%">
				<? echo $acessorios; ?>
			</div>
		</div>
		</h2>
	</div>
</div>

<div id="container">
	<div id="page">
<?
// ITENS
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os_produto.os_produto                                     ,
					tbl_os_item.qtde                                              ,
					tbl_os_item.peca_original                                     ,
					tbl_defeito.descricao AS defeito_descricao                    ,
					tbl_servico_realizado.servico_realizado                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_peca.referencia                                           ,
					tbl_peca.descricao                                            ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao
			FROM	tbl_os_produto
			JOIN	tbl_os_item      ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN	tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
			JOIN	tbl_lista_basica ON  tbl_lista_basica.produto = tbl_os_produto.produto
									 AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN tbl_defeito           USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_produto      ON tbl_os_produto.produto    = tbl_produto.produto
			WHERE   tbl_os_produto.os = $os ";

	$sql = "SELECT  tbl_os_produto.os_produto                                              ,
					tbl_os_item.qtde                                                       ,
					tbl_os_item.peca_original                                              ,
					tbl_os_item.posicao                                                    ,
					tbl_os_item.pedido                                                     ,
					tbl_defeito.descricao                   AS defeito_descricao           ,
					tbl_servico_realizado.servico_realizado                                ,
					tbl_servico_realizado.descricao         AS servico_realizado_descricao ,
					tbl_peca.referencia                                                    ,
					tbl_peca.descricao                                                     ,
					tbl_produto.referencia                  AS subproduto_referencia       ,
					tbl_produto.descricao                   AS subproduto_descricao        ,
					tbl_pedido.pedido_blackedecker          AS pedido_blackedecker
			FROM	tbl_os_produto
			JOIN	tbl_os_item      USING (os_produto)
			JOIN    tbl_produto      USING (produto)
			JOIN	tbl_peca         USING (peca)
			LEFT JOIN tbl_defeito           USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
			WHERE   tbl_os_produto.os = $os ORDER BY os_item ASC";

	$res = pg_exec ($con,$sql);

	echo "<table width='100%' border='0' cellspacing='0' cellspadding='0'>";
	echo "<tr bgcolor='#cccccc'>";
	if($os_item_subconjunto == 't') {
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Subconjuto</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Posição</font></b></td>";
	}
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Referência</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Descrição</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Qtde</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Defeito</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Serviço</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Pedido</font></b></td>";

	if (pg_numrows ($res) > 0) {
		for ($i=0; $i< pg_numrows ($res); $i++){
			$qtde                  = pg_result ($res,$i,qtde);
			$peca_original         = pg_result ($res,$i,peca_original);
			$referencia            = pg_result ($res,$i,referencia);
			$descricao             = pg_result ($res,$i,descricao);
			$defeito               = pg_result ($res,$i,defeito_descricao);
			$servico               = pg_result ($res,$i,servico_realizado_descricao);
			$cod_servico           = pg_result ($res,$i,servico_realizado);
			$subproduto_referencia = pg_result ($res,$i,subproduto_referencia);
			$subproduto_descricao  = pg_result ($res,$i,subproduto_descricao);
			$posicao               = pg_result ($res,$i,posicao);
			$pedido                = pg_result ($res,$i,pedido);
			$pedido_blackedecker   = pg_result ($res,$i,pedido_blackedecker);

			$cor = ($i%2==0) ? '#f8f8f8' : '#ffffff';
			echo "<tr bgcolor='$cor'>";

			if($os_item_subconjunto == 't'){
				echo "<td><font face='arial' size='-2'> $subproduto_referencia - $subproduto_descricao </font></td>";
				echo "<td><font face='arial' size='-2'> $posicao </font></td>";
			}

			echo "<td nowrap>";
			echo "<font face='verdana' size='1'>";
			echo $referencia;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap>";
			echo "<font face='verdana' size='1'>";
			echo $descricao;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo "<font face='verdana' size='1'>";
			echo $qtde;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap align='center'>";
			echo "<font face='verdana' size='1'>";
			echo $defeito;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='verdana' size='1'>";
			echo $servico;
			echo "</font>";
			echo "</td>";

			echo "<td align='center'>";
			echo "<font face='verdana' size='1'>";
			if ($login_fabrica == 1) echo $pedido_blackedecker;
			else                     echo $pedido;
			echo "</font>";
			echo "</td>";
			echo "</tr>";

			if (strlen($peca_original) > 0) {
				$sql = "SELECT referencia from tbl_peca where peca = $peca_original and fabrica = $login_fabrica";
				$resOriginal = pg_exec ($con,$sql);
				$referencia_original = pg_result ($resOriginal,0,referencia);
				echo "<tr bgcolor='$cor'>";

				echo "<td colspan='6'>";
				echo "<font face='Verdana' size='1' color='#CC0066'>";
				echo "A peça <B>$referencia_original</B> digitada pelo posto foi substituída automaticamente pela peça <B>$referencia</B>";
				echo "</font>";

				echo "</td>";
				echo "</tr>";
			}

			if ($cod_servico == "62" and $login_fabrica == 1 and strlen($pedido) == 0) {
				echo "<tr bgcolor='$cor'>";

				echo "<td colspan='6'>";
				echo "<font face='Verdana' size='2' color='0000ff'><b>";
				echo "O item acima, constará em um pedido em garantia que será gerado e exportado no horário padrão, às 11h45 de terça-feira. Os pedidos gerados estarão disponíveis para sua consulta no seguinte caminho: Pedidos/Consulta de pedidos/Listar todos os pedidos";
				echo "</b></font>";

				echo "</td>";
				echo "</tr>";
			}

		}
	}
	echo "</table>";
}
?>
	</div>
</div>

<div id="container">
	<div id="page">
		<h2>Observação
		<div id="contentcenter" style="width: 650px;">
			<div id="contentleft" style="width: 650px;font:75%">
				<? echo $obs; ?>
			</div>
		</div>
		</h2>
	</div>
</div>

</form>

</table>

</div>


<TABLE cellpadding='5' cellspacing='5'>
<TR>
	<TD><a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a></TD>
	<?
	if ($login_fabrica == 1) {
		echo "<TD><a href='os_cadastro.php?os=$os'><img src='imagens/btn_alterarcinza.gif'></a></TD>";
	}
	?>
	<TD><a href="os_print.php?os=<? echo $os ?>" target="blank"><img src="imagens/btn_imprimir.gif"></a></TD>
	<?

	if (strlen($_SESSION["sua_os_explodida"]) > 0) {
		echo "<TD><a href='os_revenda_explodida_blackedecker.php?sua_os=".$_SESSION["sua_os_explodida"]."'><img src='imagens/btn_voltar.gif'></a></TD>";
		session_destroy();
	}else{
		echo "<TD><a href='os_consulta.php?".$_COOKIE['cookget']."'><img src='imagens/btn_voltarparaconsulta.gif'></a></TD>";
	}
	?>
</TR>
</TABLE>

<p>
<p>

<? include "rodape.php";?>
