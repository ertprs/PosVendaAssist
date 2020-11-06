123
<?php
echo 123;
die;
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='cadastros';

$fabricas_contrato_lite = array(81,95,98,99,101);

include 'autentica_admin.php';
include 'funcoes.php';

$title = "Cadastros do Sistema";
$layout_menu = "cadastro";

include 'cabecalho.php';

		    <br>
<?php
echo "Clic-to-call;";
require_once("tulio.swf");
?>


//VETORES QUE ADICIONAM FUNCIONALIDADES - HD 383687
$vet_peca_sem_preco      = array(2,5,7,8,11,24,35,43,80,86,72);
$vet_produto_sem_mo      = array(3,6,86);
$vet_produto_sem_preco   = array(51,86);
$vet_produto_sem_familia = array(3,6,7,14,43,85,86);
$vet_produto_sem_linha   = array(43,86);
$vet_produto_sem_capacidade_divisao = array(7);

#----------------- Produtos sem Linha ---------------
if (in_array($login_fabrica, $vet_produto_sem_linha)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica";
	if ($login_fabrica == 43) {
		$sql .= " AND tbl_produto.linha = '466'";
	} else {
		$sql .= " AND tbl_produto.linha IS NULL";
	}
	$sql .= " AND tbl_produto.ativo IS TRUE ";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<caption class='titulo_coluna'>Produtos sem Linha</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {
			echo "<tr>";

			echo "<td align='left'>";
			echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}

}

#----------------- Produtos sem Capacidade Divisão---------------
if (in_array($login_fabrica, $vet_produto_sem_capacidade_divisao)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			 AND tbl_produto.ativo IS TRUE  
			 AND (tbl_produto.divisao isnull or tbl_produto.capacidade isnull) ";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<caption class='titulo_coluna'>Produtos sem Capacidade ou Divisão</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {
			echo "<tr>";

			echo "<td align='left'>";
			echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table><br>";
	}

}

#----------------- Produtos sem Familia ---------------
if (in_array($login_fabrica, $vet_produto_sem_familia)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica   = $login_fabrica
			   AND tbl_produto.familia IS NULL
			   AND tbl_produto.ativo   IS TRUE ";

	if ($login_fabrica == 14) {
		$sql .= " AND tbl_produto.abre_os IS TRUE and substr(tbl_produto.referencia,1,1) = '4'";
	}

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<caption class='titulo_coluna'>Produtos sem Família</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {
			echo "<tr>";

			echo "<td align='left'>";
			echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
}

#----------------- Produtos sem Mão-de-Obra ---------------
if (in_array($login_fabrica, $vet_produto_sem_mo)) {

	$sql = "SELECT produto, referencia, descricao
			  FROM tbl_produto
			  JOIN tbl_linha USING (linha)
			 WHERE tbl_linha.fabrica = $login_fabrica
			   AND (tbl_produto.mao_de_obra IS NULL OR tbl_produto.mao_de_obra = 0)
			   AND tbl_produto.ativo IS NOT FALSE  ";

	$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			echo "<caption class='titulo_coluna'>Produtos sem Mão-de-Obra</caption>";
			echo "<tr class='titulo_coluna'>";
			echo "<td align='center'>Referência</td>";
			echo "<td align='center'>Descrição</td>";
			echo "</tr>";

			for ($i = 0; $i < pg_numrows($res); $i++) {
				echo "<tr>";
				echo "<td align='left'>";
				echo "<a href='produto_cadastro.php?produto=" . pg_result ($res,$i,produto) . "'>";
				echo pg_result ($res,$i,referencia);
				echo "</a>";
				echo "</td>";

				echo "<td align='left'>";
				echo pg_result ($res,$i,descricao);
				echo "</td>";

				echo "</tr>";
			}
			echo "</table>";
		}
}


#------------ Produtos sem preço ------------
if (in_array($login_fabrica, $vet_produto_sem_preco)) {

	

	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca
				LEFT JOIN tbl_tabela_item USING(peca)
				WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.referencia in(SELECT referencia FROM tbl_produto JOIN tbl_linha USING(linha) WHERE fabrica = $login_fabrica)
				AND   tbl_peca.produto_acabado = 'f'
				AND   tbl_tabela_item.preco IS NULL";


	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<caption class='titulo_coluna'>Produtos sem Preço</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {
			echo "<tr>";

			echo "<td align='left'>";
			echo "<a href='preco_cadastro.php?peca=" . pg_result ($res,$i,peca) . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}
}

#------------ Peças sem preço ------------
if (in_array($login_fabrica,$vet_peca_sem_preco) or $login_fabrica >= 86) {
	$lista_basica = ($login_fabrica == 72) ? " JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca join tbl_produto on tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.ativo " : "";

	$sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
				FROM tbl_peca 
				$lista_basica ";
	if($login_fabrica == 2){
		$sql .= " LEFT JOIN tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela = 2";
	}else{
		$sql .= " LEFT JOIN tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca ";
	}
	$sql .= "   WHERE tbl_peca.fabrica = $login_fabrica
				AND   tbl_peca.produto_acabado IS NOT TRUE
				AND   tbl_peca.ativo           IS TRUE
				AND   tbl_tabela_item.preco    IS NULL";

	//echo nl2br($sql);
	//exit;
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<caption class='titulo_coluna'>Peça sem Preço</caption>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>Referência</td>";
		echo "<td align='center'>Descrição</td>";
		echo "</tr>";

		for ($i = 0; $i < pg_numrows($res); $i++) {

			$peca = pg_result ($res,$i,peca);

			/**
			 * @description HD 754908 Jacto - Não mostrar peças que tem de para, e o para contenha preço.
			 * @author Brayan
			 **/
			if ( $login_fabrica == 87 ) {

				$sql2 = "SELECT tbl_peca.peca
						 FROM tbl_depara
						 JOIN tbl_peca ON tbl_depara.peca_para = tbl_peca.peca AND tbl_peca.fabrica = tbl_depara.fabrica
						 WHERE tbl_peca.fabrica 		= $login_fabrica
						 AND   tbl_depara.peca_de 		= $peca
						 AND   tbl_peca.produto_acabado IS NOT TRUE
						 AND   tbl_peca.ativo           IS TRUE";

				$res2 = pg_query($con,$sql2);

				if ( pg_num_rows($res2) > 0 ) {

					continue;

				}

			}

			echo "<tr>";

			echo "<td align='left'>";
			echo "<a href='preco_cadastro.php?peca=" . $peca . "'>";
			echo pg_result ($res,$i,referencia);
			echo "</a>";
			echo "</td>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "</tr>";
		}
		echo "</table>";
	}

}?>


<style type="text/css">

body {
	text-align: center;
}

.cabecalho {
	color: black;
	border-bottom: 2px dotted WHITE;
	font-size: 12px;
	font-weight: bold;
}
.descricao {
	padding: 5px;
	color: black;
	font-size: 10px;
	font-weight: normal;
	text-align: justify;
}
/*========================== MENU ===================================*/
a:link.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}
a:visited.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: navy;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
}
a:hover.menu {
	padding: 3px;
	display:block;
	font: normal small Verdana, Geneva, Arial, Helvetica, sans-serif;
	color: black;
	font-size: 12px;
	font-weight: bold;
	text-align: left;
	text-decoration: none;
	background-color: #ced7e7;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
caption.titulo_coluna{
	padding:2px;
	font-family:verdana;
}

</style>


<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<TR>
		<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
		<TD class=cabecalho><?=in_array($login_fabrica, $fabricas_contrato_lite) ? 'CADASTROS DE PRODUTOS' : 'CADASTROS REFERENTES A PRODUTOS';?>
		</TD>
		<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>


<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'><?php
	if ($login_fabrica == 3 OR $login_fabrica==10 or $login_fabrica == 30 or $login_fabrica == 52 or $login_fabrica == 101) {?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='marca_cadastro.php' class='menu'>Marca de Produtos</a></td>
			<td nowrap class='descricao'>Consulta - Inclusão - Exclusão de Marcas.</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='produto_fornecedor_cadastro.php' class='menu'>Fornecedor de Produtos</a></td>
			<td nowrap class='descricao'>Consulta - Inclusão - Exclusão de Fornecedores de Produto.</td>
		</tr><?php
	}?>

	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='tipo_posto_cadastro.php' class='menu'>Tipo de Postos</a></td>
		<td nowrap class='descricao'>Consulta - Inclusão - Exclusão dos Tipos de Postos.</td>
	</tr>

	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='linha_cadastro.php' class='menu'>Linhas de Produtos</a></td>
		<td nowrap class='descricao'>Consulta - Inclusão - Exclusão de Linha de Produtos.</td>
	</tr>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='familia_cadastro.php' class='menu'>Família de Produtos</a></td>
		<td class='descricao'>Consulta - Inclusão - Exclusão de Família de Produtos.</td>
	</tr>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='produto_cadastro.php' class='menu'>Cadastro de Produtos</a></td>
		<td class='descricao'>Consulta - Inclusão - Exclusão de Produtos.</td>
	</tr><?php

	//hd 19043 - Selecionei as fábricas que usam tbl_subproduto e coloquei no array. Ébano
	$usam_subproduto = array(43, 8, 3, 14, 46, 17, 66, 4, 10, 2, 5);
	if (in_array($login_fabrica, $usam_subproduto)) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='subproduto_cadastro.php' class='menu'>Cadastro de Sub-Produtos</a></td>
			<td class='descricao'>Consulta - Inclusão - Exclusão de Sub-Produtos.</td>
		</tr><?php
	}

	if (in_array($login_fabrica, array(7,10,11,40,87))) {//HD 20300 ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='transportadora_cadastro.php' class='menu'>Cadastro de Transportadora</a></td>
			<td class='descricao'>Consulta - Inclusão - Exclusão de Transportadoras.</td>
		</tr><?php
	}

	if ($login_fabrica == 14 or $login_fabrica == 66) { #HD 264560 ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='produto_consulta_detalhe.php' class='menu'>Estrutura do produto</a></td>
			<td class='descricao'>Consulta dados da estrutura do produto (Produto > Subconjunto > Peças).</td>
		</tr><?php
	}

	if ($login_fabrica == 5) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='serie_controle_cadastro.php' class='menu'>Cadastro de Números de Série</a></td>
			<td class='descricao'>Consulta - Inclusão - Exclusão de Número de Série e quantidade produzida por produto.</td>
		</tr><?php
	}?>
	<tr bgcolor='#D9E2EF'>
		<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>
</table><?php

if ($login_fabrica == 10 OR $login_fabrica == 35)
	echo "<center><a href='loja_virtual_adm.php'> Administrador da Loja Virtual</a></center>";?>

<br />

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<TR>
		<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
		<TD class=cabecalho><?=in_array($login_fabrica, $fabricas_contrato_lite) ? 'CADASTROS DE PEÇAS' : 'CADASTROS REFERENTES A PEDIDOS DE PEÇAS';?></TD>
		<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>

	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='peca_cadastro.php' class='menu'>Cadastro de Peças</a></td>
		<td class='descricao'>Consulta - Inclusão - Exclusão de Componentes utilizados pela fábrica.</td>
	</tr><?php

	//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
	if ($login_fabrica == 24) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='peca_amarracao.php' class='menu'>Amarração de Peças</a></td>
			<td class='descricao'>Ferramenta de amarração de peças. Quando lançar uma peça é obrigado a lançar a peça amarrada.</td>
		</tr><?php
	}

	if ($login_fabrica == 6) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='peca_amarracao_lista.php' class='menu'>Lista Peça X Peça</a></td>
			<td class='descricao'>Cadastro e exclusão de peça e subpeça da lista básica.</td>
		</tr><?php
	}

	if (!in_array($login_fabrica, $fabricas_contrato_lite)){?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='lbm_cadastro.php' class='menu'>Lista Básica</a></td>
			<td class='descricao'>Estrutura de peças aplicadas a cada produto</td>
		</tr><?php
	}

	if (in_array($login_fabrica, array(42))){?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='lbm_excel.php' class='menu'>Lista Básica Upload</a></td>
			<td class='descricao'>Upload de arquivo xls para atualização da lista básica</td>
		</tr><?php
	}?>

	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='condicao_cadastro.php' class='menu'>Condições de Pagamento</a></td>
		<td class='descricao'>Cadastramento de condições de pagamentos para pedidos de peças</td>
	</tr><?php

	if ($login_fabrica <> 86 && !in_array($login_fabrica, $fabricas_contrato_lite)) { #HD 383687 - Opção Não visivel para FAMASTIL?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='tipo_posto_condicao_cadastro.php' class='menu'>Condições de Pagamento para Postos</a></td>
			<td class='descricao'>Cadastramento de condições de pagamentos para pedidos de peças específica para postos</td>
		</tr><?php
	}

	if ($login_fabrica == 7) {?>

		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='tabela_vigencia.php' class='menu'>Vigência das Tabela Promocionais</a></td>
			<td class='descricao'>Altera a vigência das tabelas promocionais</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='desconto_pedido_cadastro.php' class='menu'>Cadastro de Descontos</a></td>
			<td class='descricao'>Cadastro de desconto em pedidos, com data de vigência.</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='capacidade_manutencao.php' class='menu'>Valores por Capacidade</a></td>
			<td class='descricao'>Define os valores de regulagem e certificado por capacidade</td>
		</tr><?php

	}

	//PARA BLACK - ADICIONADO DIA 30-03-2007 IGOR - HD:1666
	if ($login_fabrica == 1 OR $login_fabrica == 72) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='condicao_pagamento_manutencao.php' class='menu'>Alteração de Condições de Pagamento</a></td>
			<td class='descricao'>Alteração  de condições de pagamentos dos postos</td>
		</tr><?php
	}

	if ($login_fabrica == 86) {?>
		<tr bgcolor='#fafafa'><?php
	} else {?>
		<tr bgcolor='#f0f0f0'><?php
	}?>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='preco_cadastro.php' class='menu'>Preços de Peças</a></td>
		<td class='descricao'>Cadastramento e alteração em preços de peças.</td>
	</tr><?php

	//HD 17541
	if ($login_fabrica == 1) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='preco_upload.php' class='menu'>Atualização de Preços de Acessórios</a></td>
			<td class='descricao'>Atualiza preço de peça Acessórios para pedido Acessório e Loja Virtual.</td>
		</tr><?php
	}

	if ($login_fabrica == 3) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='fator_multiplicacao.php' class='menu'>Preços Sugeridos</a></td>
			<td class='descricao'>Cadastro de preços sugeridos para que o PA se baseie para vender ao consumidor.</td>
		</tr><?php
	}

	if ($login_fabrica == 40) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='upload_importa_masterfrio.php' class='menu'>Atualização de Preços(Via Upload) </a></td>
			<td class='descricao'>Cadastramento e alteração em preços de peças via upload pelo arquivo xls.</td>
		</tr><?php
	}

	if (!in_array($login_fabrica,$fabricas_contrato_lite)){?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='tipo_pedido.php' class='menu'>Tipo do Pedido</a></td>
			<td class='descricao'>Cadastro de Tipo de Pedidos</td>
		</tr><?php
	} ?>

	<tr bgcolor='#fafafa'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='depara_cadastro.php' class='menu'>De -> Para</a></td>
		<td class='descricao'>Cadastro de peças DE-PARA (alteração em códigos de peças).</td>
	</tr>
	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='peca_alternativa_cadastro.php' class='menu'>Peças Alternativas</a></td>
		<td class='descricao'>Cadastro de peças ALTERNATIVAS.</td>
	</tr>
	<tr bgcolor='#fafafa'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='peca_fora_linha_cadastro.php' class='menu'>Peças Fora de Linha</a></td>
		<td class='descricao'>Cadastro de peças FORA DE LINHA</td>
	</tr><?php

	if (!in_array($login_fabrica,$fabricas_contrato_lite)){?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='peca_analise_cadastro.php' class='menu'>Peças em Análise</a></td>
			<td class='descricao'>Cadastro de peças em ANÁLISE</td>
		</tr><?php
	}?>

	<tr bgcolor='#fafafa'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='peca_acerto.php' class='menu'>Acerto de Peças</a></td>
		<td class='descricao'>Lista todas as peças e seus dados para acerto comum.</td>
	</tr>

	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='produto_acerto_linha.php' class='menu'>Acerto de Produtos</a></td>
		<td class='descricao'>Lista todos os produtos e seus dados para acerto comum.</td>
	</tr><?php

	if (!in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='peca_previsao_entrega.php' class='menu'>Previsão de Entrega de Peças</a></td>
			<td class='descricao'>Cadastra a previsão de entrega de peças com abastecimento crítico. Os postos serão informados da previsão, e pode-se consultar as pendências destas peças para tomada de providências.</td>
		</tr><?php
	}

	if ($login_fabrica <> 6 and $login_fabrica <> 24 and $login_fabrica <> 50 and $login_fabrica <> 86 && !in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/tela25.gif'></td>
		<!--<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Peças Represadas</a></td>-->
			<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Peças Utilizadas do Estoque do Distribuidor</a></td>
			<td class='descricao'>Cadastro de Peças que o distribuidor não vai mais receber automaticamente. As peças irão gerar crédito. A finalidade deste processo é permitir que o distribuidor possa abaixar o estoque de determinadas peças.</td>
		</tr><?php
	}

	if ($login_fabrica == 40) { ?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<!--<td nowrap width='260'><a href='peca_represada_cadastro.php' class='menu'>Peças Represadas</a></td>-->
			<td nowrap width='260'><a href='defeito_constatado_peca_cadastro.php' class='menu'>Defeito Constatado Por Peça</a></td>
			<td class='descricao'>Cadastro de Defeito Constatado por Peças</td>
		</tr><?php
	}

	if ($login_fabrica == 1) { ?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='acrescimo_tributario.php' class='menu'>Acréscimo Tributário por Estado</a></td>
			<td class='descricao'>Cadastro de Acréscimo Tributário definido para cada Estado.</td>
		</tr><?php
	}

	if ($login_fabrica == 15 || $login_fabrica == 24) { ?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='kit_pecas_cadastro.php' class='menu'>Kit Peças</a></td>
			<td class='descricao'>Cadastro de Kit de Peças.</td>
		</tr><?php
	}

	if ($login_fabrica == 5) {?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/tela25.gif'></td>
			<td><a href='producao_cadastro.php' class='menu'>Cadastro de Itens de Produção</a></td>
			<td class='descricao'>Cadastro de itens produzidos.</td>
		</tr><?php
	}?>
	<tr bgcolor='#D9E2EF'>
		<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>
</table><?php

if ($login_fabrica == 1) { ?>
	<br />
	<table width="700" border="0" cellspacing="0" cellpadding="0" bgcolor="#D9E2EF" align = 'center'>
		<tr>
			<td width="10"><img border="0" src="imagens/corner_se_laranja.gif"></td>
			<td class="cabecalho">LOCAÇÃO</td>
			<td width="10"><img border="0" src="imagens/corner_sd_laranja.gif"></td>
		</tr>
	</table>
	<table width="700" border="0" cellpadding="0" cellspacing="0" align = 'center'>
		<tr bgcolor="#F0F0F0">
			<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
			<td nowrap width="260"><a href="os_cadastro_locacao.php" class="menu">Cadastro de Produtos Locação</a></td>
			<td nowrap class="descricao">Produtos liberados para Locação</td>
		</tr>
		<tr bgcolor="#FAFAFA">
			<td width="25"><img border="0" src="imagens/pasta25.gif"></td>
			<td nowrap width="260"><a href="pedido_consulta_locacao.php" class="menu">Consulta de Produtos Locação</a></td>
			<td nowrap class="descricao">Consulta Produtos liberados para Locação</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td colspan="3"><img border="0" src="imagens/spacer.gif" height="3"></td>
		</tr>
	</table><?php
}?>

<br />

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<TR>
		<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
		<TD class=cabecalho><?=in_array($login_fabrica, $fabricas_contrato_lite) ? 'CADASTROS DE DEFEITOS' : 'CADASTROS DE DEFEITOS - EXCEÇÕES';?></TD>
		<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'><?php 

	$sql = "SELECT pedir_defeito_reclamado_descricao FROM tbl_fabrica WHERE fabrica = $login_fabrica and (pedir_defeito_reclamado_descricao is null or pedir_defeito_reclamado_descricao is false);";
	$res = @pg_exec($con,$sql);

	if ($login_fabrica == 52) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='motivo_reincidencia.php' class='menu'>Motivo da Reincidência</a></td>
			<td class='descricao'>Cadastro de Motivos de Reincidência</td>
		</tr> <?php
	}

	if (@pg_numrows($res) > 0 || ($login_fabrica == 42 || $login_fabrica == 81 || $login_fabrica == 86 || $login_fabrica == 74 || $login_fabrica == 96 || $login_fabrica == 94) ) {?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='defeito_reclamado_cadastro.php' class='menu'>Defeitos Reclamados</a></td>
			<td nowrap class='descricao'>Tipos de defeitos reclamados pelo CLIENTE</td>
		</tr><?php
	}

	if ($login_fabrica == 25) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='defeito_reclamado_cadastro_callcenter.php' class='menu'>Defeitos Reclamados Call Center</a></td>
			<td class='descricao'>Cadastro de defeitos reclamados no CallCenter</td>
		</tr><?php
	}?>

	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='defeito_constatado_cadastro.php' class='menu'>Defeitos Constatados</a></td>
		<td class='descricao'>Tipos de defeitos constatados pelo TÉCNICO</td>
	</tr><?php

	if (in_array($login_fabrica, array(42,86,74,81,94,95,96,98,99,104,105,108,101,111))) {//HD 415872 adicionado eterny

		if (!in_array($login_fabrica, array(98,99,104,105,108,111,101))) { //HD 415872 - eterny nÃo utiliza defeito reclamado na integridade ?>

			<tr bgcolor='#fafafa'>
				<td><img src='imagens/pasta25.gif'></td>
				<td><a href='familia_integridade_reclamado.php' class='menu'>Família - Defeito Reclamado</a></td>
				<td class='descricao'>Relacionamento/Integridade - Família - Defeito Reclamado</td>
			</tr><?php

		}
	?>
	
	
	

		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='familia_integridade_constatado.php' class='menu'>Família - Defeito Constatado</a></td>
			<td class='descricao'>Relacionamento/Integridade - Família - Defeito Constatado</td>
		</tr><?php

	}
	
	if ($login_fabrica == 87) {
	?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td nowrap><a href='tipo_os_por_familia_cadastro.php' class='menu'>Manutenção de Tipo de OS X Família</a></td>
			<td class='descricao'>Integridade - Tipo de OS X Família</td>
		</tr>
		
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td nowrap><a href='tipo_atendimento_cadastro.php' class='menu'>Cadastro de Tipos de Atendimento</a></td>
			<td class='descricao'>Manutenção do cadastro dos Tipos de Atendimentos que serão utilizados nas Ordens de Serviço</td>
		</tr>
		
	<? }
	
	if ($login_fabrica == 52) {?>

		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='grupo_defeito_constatado_cadastro_fricon.php' class='menu'>Grupo de Defeitos Constatados</a></td>
			<td class='descricao'>Cadastro/Manutenção nos grupos de defeitos constatados pelo TÉCNICO</td>
		</tr><?php

	}

	if (1 == 2 and $login_fabrica <> 24) {//chamado 2977?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='causa_defeito_cadastro.php' class='menu'>Causa de Defeitos</a></td>
			<td class='descricao'>Causas de defeitos constatados pelo TÉCNICO</td>
		</tr><?php
	}

	if ($login_fabrica <> 86 && !in_array($login_fabrica, $fabricas_contrato_lite)) {//HD 387824?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='excecao_cadastro.php' class='menu'>Exceção de mão-de-obra</a></td>
			<td class='descricao'>Cadastro das exceções de mão-de-obra</td>
		</tr><?php
	}

	if ($login_fabrica == 1) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='excecao_cadastro_black.php' class='menu'>Exceção de mão-de-obra(Nova Tela)</a></td>
			<td class='descricao'>Cadastro das exceções de mão-de-obra</td>
		</tr><?php
	}

	if ($login_fabrica == 45 or $login_fabrica == 80) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='extrato_lancamento_mensal.php' class='menu'>Valor fixo mensal para postos</a></td>
			<td class='descricao'>Cadastro de valores que serão incluídos todos os meses ao extrato</td>
		</tr><?php
	}?>

	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='servico_realizado_cadastro.php' class='menu'><? if ($login_fabrica==20){ echo "Cadastro de Identificação";}else {echo"Serviços Realizados";}?></a></td>
		<td class='descricao'><? if ($login_fabrica==20){ echo "Cadastro de Identificação, terceiro código de falha";}else {echo"Cadastro de serviços realizados";}?></td>
	</tr><?php

	if ($login_fabrica == 14) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='servico_realizado_tipo_posto.php' class='menu'>Cadastro de Serviços Realizados x Tipos de Postos</a></td>
			<td class='descricao'>Cadastro de serviços realizados x tipos de postos e cadastro de exceção por posto</td>
		</tr><?php
	}

	//chamado 2977
	if ($login_fabrica == 1 OR $login_fabrica == 2 OR $login_fabrica == 5 OR $login_fabrica == 8 OR $login_fabrica == 10 OR $login_fabrica == 14 OR $login_fabrica == 16 OR $login_fabrica == 20) { ?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='defeito_causa_defeito_cadastro.php' class='menu'>Defeitos x Causa do Defeito</a></td>
			<td class='descricao'>Cadastro da relação entre os defeitos e suas causas possíveis</td>
		</tr><?php
	}

	//chamado 2977
	if ($login_fabrica == 1 OR $login_fabrica == 2 OR $login_fabrica == 5 OR $login_fabrica == 8 OR $login_fabrica == 10 OR $login_fabrica == 14 OR $login_fabrica == 16 OR $login_fabrica == 20) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='defeito_reclamado_defeito_constatado.php' class='menu'>Defeito Constatado x Reclamado</a></td>
			<td class='descricao'>Cadastro da relação entre os defeitos reclamados e seus possíveis defeitos constatados</td>
		</tr><?php
	}

	if (!in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='defeito_cadastro.php' class='menu'>Defeito em Peças</a></td>
			<td class='descricao'>Cadastro de defeitos que podem ocorrer nas peças</td>
		</tr><?php
	}

	/*if (($login_fabrica == 6) or ($login_fabrica == 11) or ($login_fabrica == 15) or ($login_fabrica == 3) or ($login_fabrica == 24) or ($login_fabrica == 3) or ($login_fabrica == 5) or ($login_fabrica == 2) or ($login_fabrica == 29) or ($login_fabrica == 30) or ($login_fabrica == 31 or ($login_fabrica == 32) or ($login_fabrica == 33) or ($login_fabrica == 34) or ($login_fabrica == 35) or ($login_fabrica == 36) or ($login_fabrica == 37) or ($login_fabrica == 8))){*/
	if ($login_fabrica <> 14 and $login_fabrica <> 2 and $login_fabrica <> 19 and $login_fabrica <> 20 and $login_fabrica <> 86 and $login_fabrica <> 94 && !in_array($login_fabrica, $fabricas_contrato_lite)) {?>

		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='solucao_cadastro.php' class='menu'>Solução</a></td>
			<td class='descricao'>Cadastro de Solução de um defeito</td>
		</tr><?php

		if ($login_fabrica == 74) {?>
			<tr bgcolor='#fafafa'>
				<td><img src='imagens/pasta25.gif'></td>
				<td><a href='solucao_familia_cadastro.php' class='menu'> Integridade Família e Solução</a></td>
				<td class='descricao'>Cadastro de integridade de Solução x Família</td>
			</tr><?php
		}

		if ($login_fabrica == 1) {?>
			<tr bgcolor='#f0f0f0'>
				<td><img src='imagens/pasta25.gif'></td>
				<td><a href='linha_solucao_cadastro.php' class='menu'>Linha x Solução</a></td>
				<td class='descricao'>Cadastro de Solução de um defeito para cada linha (Objetivo é para o posto digitar a solução somente da linha)</td>
			</tr><?php
		}

	}

	/*	if (($login_fabrica == 6) or ($login_fabrica == 11) or ($login_fabrica == 15) or ($login_fabrica == 3) or ($login_fabrica == 24) or ($login_fabrica == 1) or ($login_fabrica == 3) or ($login_fabrica == 2) or ($login_fabrica == 5) or ($login_fabrica == 26) or ($login_fabrica == 25) or ($login_fabrica == 29) or ($login_fabrica == 30) or ($login_fabrica == 31) or ($login_fabrica == 32) or ($login_fabrica == 33) or ($login_fabrica == 34) or ($login_fabrica == 35) or ($login_fabrica == 36) or ($login_fabrica == 37) or ($login_fabrica == 8)){*/
	if (( !in_array($login_fabrica,array(20,74,86,94,108,111)) and !in_array($login_fabrica, $fabricas_contrato_lite)) OR $login_fabrica == 95) { //Volta o menu para LeaderShip HD 731929
		//HD - Ronaldo pediu para deixar tela da Etery igual a Dellar?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><? #HD 82470
			//HD 178624 Fabrica 35 pega no call-center integridade defeito reclamado. mas na OS sem o defeito reclamado.
			//Por isso tem que deixar a manutenção em cima do programa que aceita o defeito reclamado.
			//MLG HD 304636 - Adicionar Cadence (35) à lista de fábricas que usam a _new
			if (in_array($login_fabrica, array(2, 7, 15, 25, 28, 30, 35, 40, 42, 43, 45, 46, 47,96)) or
					($login_fabrica > 49 and !in_array($login_fabrica, array(59, 66)))) { ?>
				<a href='relacionamento_diagnostico_new.php' class='menu'>
			<? } else {?>
				<a href='relacionamento_diagnostico.php' class='menu'>
			<?}?>
				Relacionamento de Integridade</a></td>
			<td class='descricao'>Relacionamento de Linha, Familia, Defeito Reclamado, Defeito Constatado e Solução   para o Diagnóstico</td>
		</tr><?php
	}

	if ($login_fabrica == 15) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='os_acerto_defeito.php' class='menu'>Acertos de OS´s cadastradas</a></td>
			<td class='descricao'>Acerto dos cadastro dos defeitos das OS´s.</td>
		</tr><?php
	}

	if ($login_fabrica == 24 or $login_fabrica == 50 or $login_fabrica == 5) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='peca_integridade.php' class='menu'>Integridade de Peças</a></td>
			<td class='descricao'>Cadastro de integridade de peças</td>
		</tr>
		<!-- Esta tela não existe
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='acao_corretiva_cadastro.php' class='menu'>Ação Corretiva</a></td>
			<td class='descricao'>Cadastro de correções efetuadas em produtos.</td>
		</tr>--><?php
	}

	if ($login_fabrica == 20) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='produto_custo_tempo_cadastro.php' class='menu'>Cadastro de Custo Tempo</a></td>
			<td class='descricao'>Cadastro e atulização de custo tempo por produtos</td>
		</tr><?php
	}?>

	<tr bgcolor='#f0f0f0'>
		<td><img src='imagens/pasta25.gif'></td>
		<td><a href='causa_troca_cadastro.php' class='menu'>Cadastro de Causa de Troca</a></td>
		<td class='descricao'>Cadastro das causas da troca do produto</td>
	</tr><?php

	if ($login_fabrica == 6) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='causa_troca_item_cadastro.php' class='menu'>Cadastro dos Itens de Causa de Troca</a></td>
			<td class='descricao'>Cadastro dos Itens das causas da troca do produto</td>
		</tr><?php
	}

	if ($login_fabrica == 56 OR $login_fabrica == 57 OR $login_fabrica == 58 OR $login_fabrica == 46 OR $login_fabrica == 1 OR $login_fabrica == 43 OR $login_fabrica == 19) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='laudo_tecnico_cadastro.php' class='menu'>Cadastro de questionário</a></td>
			<td class='descricao'>
			<? if ($login_fabrica==19) echo "Cadastro de questionário por linha de produto para atendimento em domicílio"; else echo "Cadastro dos Laudos Ténicos por Produto ou Família";?>
			</td>
		</tr><?php
	}

	if ($login_fabrica == 92 || $login_fabrica == 30) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='cadastro_item_servico.php' class='menu'>Cadastro de Itens de Serviço</a></td>
			<td class='descricao'>Cadastro de Itens de Serviço</td>
		</tr><?php
	}

	if ($login_fabrica == 74) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='integridade_peca_defeito_cadastro.php' class='menu'>Cadastro de Integridade Peça Defeito</a></td>
			<td class='descricao'>
				Cadastro de Integridade entre Peças e Defeitos
			</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='servico_realizado_integridade_cadastro.php' class='menu'>Cadastro de Integridade de Serviço e Defeito</a></td>
			<td class='descricao'>
				Cadastro de Integridade de Serviço Realizado e Defeitos
			</td>
		</tr><?php
	}?>

	<tr bgcolor='#D9E2EF'>
		<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>
</table>

<br />

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
	<TR>
		<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
		<TD class=cabecalho>CADASTROS REFERENTES AO EXTRATO</TD>
		<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>

	<tr bgcolor='#fAfAfA'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='lancamentos_avulsos_cadastro.php' class='menu'>Lançamentos Avulsos</a></td>
		<td nowrap class='descricao'>Cadastro dos Lançamentos Avulsos ao Extrato</td>
	</tr><?php

	if ($login_fabrica == 50) :?>
		<tr bgcolor='#fAfAfA'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='colormaq_email_devolucao_cad.php' class='menu'>E-mail de NF de Devolução</a></td>
			<td nowrap class='descricao'>Cadastro do e-mail enviado aos postos cobrando a NF de devolução</td>
		</tr><?php
	endif;

	if ($login_fabrica == 3) :?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='tipo_nota_cadastro.php' class='menu'>Tipo de Nota</a></td>
			<td nowrap class='descricao'>Cadastro de tipo de nota para o extrato</td>
		</tr><?php
	endif;?>

	<tr bgcolor='#D9E2EF'>
		<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>

</table>

<br />

<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align='center'>
	<TR>
		<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
		<TD class=cabecalho>MANUTENÇÃO DE POSTOS AUTORIZADOS</TD>
		<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
	</TR>
</TABLE>

<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align='center'><?php

	if ($login_fabrica == 52 or $login_fabrica == 30 or $login_fabrica == 85) {?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='cliente_admin_cadastro.php' class='menu'>Clientes Admin</a></td>
			<td nowrap class='descricao'>Cadastramento de Clientes que terão acesso a abertura de Pré-Os</td>
		</tr><?php
	}

	if ($login_fabrica == 96) {?>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='cliente_admin_cadastro.php' class='menu'>Cadastro de Clientes</a></td>
			<td nowrap class='descricao'>Cadastramento de Clientes que terão acesso a abertura de Pré-Os</td>
		</tr><?php
	}?>

	<tr bgcolor='#f0f0f0'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='posto_cadastro.php' class='menu'>Postos Autorizados</a></td>
		<td nowrap class='descricao'>Cadastramento de postos autorizados</td>
	</tr><?php

	if ($login_fabrica == 81) {?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='controle_salton.php' class='menu'>Controle Boaz Credenciamento</a></td>
			<td nowrap class='descricao'>Controle dos postos que responderam o email de auto-credenciamento.</td>
		</tr><?php
	}

	if ($login_fabrica == 15) {?>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='relatorio_atualizacao_dados_posto.php' class='menu'>Consulta Atualização Cadastro Postos</a></td>
			<td nowrap class='descricao'>Consulta a atualização cadastral obrigatória dos postos.</td>
		</tr><?php
	}?>

	<tr bgcolor='#fafafa'>
		<td width='25'><img src='imagens/pasta25.gif'></td>
		<td nowrap width='260'><a href='credenciamento.php' class='menu'>Credenciamento de Postos</a></td>
		<td nowrap class='descricao'>Credenciamento de postos autorizados</td>
	</tr><?php

	if ($login_fabrica == 15) {?>
		<tr bgcolor='#fafafa';>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='valor_km_posto.php' class='menu'>Cadastro de Valor de KM por Posto</a></td>
			<td class='descricao'>Cadastro de Valor de KM por Posto Autorizado</td>
		</tr><?php
	}

	if (!in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='revenda_cadastro.php' class='menu'>Revendas</a></td>
			<td class='descricao'>Cadastro de Revendedores</td>
		</tr><?php
	}

	if ($login_fabrica == 7) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='cliente_consulta.php' class='menu'>Clientes</a></td>
			<td class='descricao'>Consulta de Clientes</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='cadastro_representante_posto.php' class='menu'>Representante Posto</a></td>
			<td class='descricao'>Cadastro de Representantes por Posto</td>
		</tr><?php
	} else if (!in_array($login_fabrica,$fabricas_contrato_lite)){?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='consumidor_cadastro.php' class='menu'>Consumidores</a></td>
			<td class='descricao'>Cadastro de Consumidores</td>
		</tr><?php
	}

	if (!in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='fornecedor_cadastro.php' class='menu'>Fornecedores</a></td>
			<td class='descricao'>Cadastro de Fornecedores</td>
		</tr><?php
	}

	if (!in_array($login_fabrica,$fabricas_contrato_lite)) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='faq_situacao.php' class='menu'>Perguntas Frequentes</a></td>
			<td class='descricao'>Cadastro de  perguntas e respostas sobre um determinado produto </td>
		</tr><?php
	}

	if ($login_fabrica == 1) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='comunicado_blackedecker.php' class='menu'>Comunicados por E-mail</a></td>
			<td class='descricao'>Envie comunicados por e-mail para os postos</td>
		</tr><?php
	}

	if ($login_fabrica == 3) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='distribuidor_posto_relatorio.php' class='menu'>Distribuidor e seus postos</a></td>
			<td class='descricao'>Relação para conferência da Distribuição</td>
		</tr><?php
	}

	if ($login_fabrica == 3 AND ($login_admin == 258 or $login_admin == 852)) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='cadastro_km.php' class='menu'>Quilometragem</a></td>
			<td class='descricao'>Cadastro do valor pago por Quilometragem para Ordens de Serviços com atendimento em Domicilio.</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='aprova_atendimento_domicilio.php' class='menu'>Aprovar OS Domicilio (EM TESTE)</a></td>
			<td class='descricao'>Aprovação de Ordens de Serviços que tenham atendimento em domicilio.</td>
		</tr><?php
	}

	if (in_array($login_fabrica, array(74,85,90,94,95,108,111))) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='manutencao_numero_serie.php' class='menu'>Cadastro de Número de Série</a></td>
			<td class='descricao'>Cadastro e Manutenção de Nº de Série</td>
		</tr><?php
	}

	if (in_array($login_fabrica,array(95,108,111)) ) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='upload_importacao_serie.php' class='menu'>Upload de Número de Série</a></td>
			<td class='descricao'>Upload de Arquivo de Número de Série</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='manutencao_numero_serie_peca.php' class='menu'>Inserir Componentes em Produtos</a></td>
			<td class='descricao'>Inserir Componentes em Produtos para lançamento de itens na Ordem de Serviço</td>
		</tr><?php
	}

	if ($login_fabrica <> 86 && !in_array($login_fabrica, $fabricas_contrato_lite)) {//HD 387824?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='feriado_cadastra.php' class='menu'>Cadastro de Feriado</a></td>
			<td class='descricao'>Cadastro de feriados no sistema</td>
		</tr><?php
	}

	if (!in_array($login_fabrica, $fabricas_contrato_lite)) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='callcenter_pergunta_cadastro.php' class='menu'>Cadastro de Perguntas do Callcenter</a></td>
			<td class='descricao'>Para que as frases padrões do callcenter sejam alteradas.</td>
		</tr><?php
	}

	if ($login_fabrica == 20) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='escritorio_regional_cadastro.php' class='menu'>Cadastro de Escritórios Regionais</a></td>
			<td class='descricao'>Faz o cadastramento e manutenção de escritórios regionais.</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='upload_importacao.php' class='menu'>Upload de Arquivos</a></td>
			<td class='descricao'>Faz o Upload de peças, preço, produto, lista básica do Brasil e América Latina.</td>
		</tr><?php
	}

	if ($login_fabrica == 1) {?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='atendente_cadastro.php' class='menu'>Atendente Manutenção</a></td>
			<td class='descricao'>Manutenção de Atendente de Help-Desk por Estado .</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='fale_conosco_cadastro.php' class='menu'>Fale Conosco Manutenção</a></td>
			<td class='descricao'>Manutenção de Fale Conosco na Tela do Posto.</td>
		</tr><?php
	}

	if ($login_fabrica == 7) {?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='classificacao_os_cadastro.php' class='menu'>Classificação de OS</a></td>
			<td class='descricao'>Cadastro de Clasificação de Ordem de Serviço</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='contrato_cadastro.php' class='menu'>Contrato</a></td>
			<td class='descricao'>Cadastro de Contrato</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='grupo_empresa_cadastro.php' class='menu'>Grupo de Empresa</a></td>
			<td class='descricao'>Cadastro Grupo de empresa</td>
		</tr><?php
	}

	if ($login_fabrica == 3) {//HD 34210 ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='dias_intervencao_cadastro.php' class='menu'>Dias para entrar na intervenção</a></td>
			<td class='descricao'>Alteração de quantidade de dias para OS entrar na intervenção</td>
		</tr><?php
	}

	if ($login_fabrica == 3 or $login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 101 or $login_fabrica == 99) { // HD 86636 HD 264560 ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='produto_serie_mascara.php' class='menu'>Cadastro de Mascara de Número de Série</a></td>
			<td class='descricao'>Manutenção de Mascara de Número de Série</td>
		</tr><?php
	}

	if ($login_fabrica == 50) { // HD 54668 ?>
		<tr bgcolor='#f0f0f0'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='posto_familia_cadastro.php' class='menu'>Posto X Deslocamento</a></td>
			<td class='descricao'>Autoriza deslocamento para familia de produto.</td>
		</tr><?php
	}

	if ($login_fabrica == 43) { // HD34210 ?>
		<tr bgcolor='#fafafa'>
			<td><img src='imagens/pasta25.gif'></td>
			<td><a href='indicadores_cadastro.php' class='menu'>Cadastro Indicadores Ranking</a></td>
			<td class='descricao'>Cadastro de notas de corte, peso de cada nota e meta para o ranking dos postos</td>
		</tr><?php
	}
	?>
	<tr bgcolor='#D9E2EF'>
		<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>
</table>

<?php if ($login_fabrica == 30 ) { // HD 408341 ?>
<br />
<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align='center'>
    <TR>
        <TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
        <TD class=cabecalho>PESQUISA DE SATISFAÇÃO</TD>
        <TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
    </TR>
</TABLE>
<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
	<tr bgcolor='#f0f0f0'>
		<td width='10'><IMG src="imagens/pasta25.gif"></td>
		<td><a href='cadastro_pergunta.php' class='menu'>Cadastro de Pergunta</a></td>
		<td class="descricao">Cadastro de Perguntas para a Pesquisa de Satisfação</td>
	</tr>
	<tr bgcolor='#fafafa'>
    	<td width='10'><IMG src="imagens/pasta25.gif"></td>
    	<td><a href='cadastro_tipo_resposta.php' class='menu'>Cadastro de Tipo de Respostas</a></td>
    	<td class="descricao">Cadastro de Tipos de Respostas para as perguntas da Pesquisa de Satisfação</td>
	</tr>
	<tr bgcolor='#f0f0f0'>
    	<td width='10'><IMG src="imagens/pasta25.gif"></td>
    	<td><a href='cadastro_pesquisa.php' class='menu'>Cadastro de Pesquisa</a></td>
    	<td class="descricao">Cadastro de Pesquisa de Satisfação</td>
	</tr>
	<tr bgcolor='#D9E2EF'>
    	<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
	</tr>
</table>
<?php } 

//Menu disponivel somente para a Britania, como teste, HD 3780
//Menu Liberado para Cadence - HD 30637
if ($login_fabrica == 3 OR $login_fabrica == 10 OR $login_fabrica == 35) {?>
	<br />
	<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
		<TR>
			<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
			<TD class=cabecalho>CONSULTA LOJA VIRTUAL</TD>
			<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
		</TR>
	</TABLE>

	<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'><?php
		if ($login_fabrica != 35) {?>
			<tr bgcolor='#fAfAfA'>
				<td width='25'><img src='imagens/pasta25.gif'></td>
				<td nowrap width='260'><a href='loja_completa.php' class='menu'>Listas de Produtos</a></td>
				<td nowrap class='descricao'>Listas dos Produtos Promoção Loja Virtual</td>
			</tr><?php
		}?>
		<tr bgcolor='#fAfAfA'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='manutencao_valormin.php' class='menu'>Manutenção</a></td>
			<td nowrap class='descricao'>Manutenção do Valor Minimo de Compra</td>
		</tr>
		<tr bgcolor='#D9E2EF'>
			<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
		</tr>
	</table><?php
}

echo '<br />';

if ($login_fabrica == 20) {?>
	<TABLE width="700px" border="0" cellspacing="0" cellpadding="0" bgcolor='#D9E2EF' align = 'center'>
		<TR>
			<TD width='10'><IMG src="imagens/corner_se_laranja.gif"></TD>
			<TD class=cabecalho>INFORMAÇÕES CADASTRAIS DA AMÉRICA LATINA</TD>
			<TD width='10'><IMG src="imagens/corner_sd_laranja.gif"></TD>
		</TR>
	</TABLE>
	<table border='0' width='700px' border='0' cellpadding='0' cellspacing='0' align = 'center'>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='peca_informacoes_pais.php' class='menu'>Tabela de Preços América Latina</a></td>
			<td nowrap class='descricao'>Todas tabelas de preço da América Latina</td>
		</tr>
		<tr bgcolor='#fafafa'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td nowrap width='260'><a href='produto_informacoes_pais.php' class='menu'>Produtos por País</a></td>
			<td nowrap class='descricao'>Todas os produtos cadastrados pelos países da América Latina</td>
		</tr>
		<tr bgcolor='#f0f0f0'>
			<td width='25'><img src='imagens/pasta25.gif'></td>
			<td width='260'><a href='informacoes_pais.php' class='menu'>Dados Países da América Latina</a></td>
			<td nowrap class='descricao'>Dados de conversão de moeda e desconto de cada país <br>usado na integração com a Alemanha</td>
		</tr>
		<tr bgcolor='#D9E2EF'>
			<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>
		</tr>
	</table><?php
}

include "rodape.php"?>

</body>
</html>
