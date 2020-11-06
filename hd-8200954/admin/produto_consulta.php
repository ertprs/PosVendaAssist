<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';


if (strlen($_POST["referencia"]) > 0) $referencia = trim($_POST["referencia"]);
//if (strlen($_POST["descricao"]) > 0)  $descricao  = trim($_POST["descricao"]);

if (strlen($referencia) > 0){
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	$sql = "SELECT produto FROM tbl_produto WHERE referencia_pesquisa = '$referencia'";
	$res = @pg_exec($con,$sql);
	$produto = @pg_result($res,0,0);
}

###CARREGA REGISTRO
if (strlen($_GET["produto"]) > 0)  $produto = trim($_GET["produto"]);
if (strlen($_POST["produto"]) > 0) $produto = trim($_POST["produto"]);

if (strlen($produto) > 0) {
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.descricao ,
					tbl_produto.voltagem  ,
					tbl_produto.referencia,
					tbl_produto.referencia_fabrica,
					tbl_produto.garantia  ,
					tbl_produto.mao_de_obra,
					tbl_produto.mao_de_obra_admin,
					tbl_produto.ativo     ,
					tbl_produto.nome_comercial,
					tbl_produto.classificacao_fiscal,
					tbl_produto.ipi,
					tbl_produto.radical_serie,
					tbl_produto.numero_serie_obrigatorio,
					tbl_linha.nome AS linha,
					tbl_familia.descricao as familia ";
	
	if($login_fabrica == 35){
		$sql 	.= ", tbl_produto.preco
					, tbl_produto.qtd_etiqueta_os 
					, tbl_produto.uso_interno_ativo
					, tbl_produto.mao_de_obra_troca
					, tbl_marca.nome AS marca 
					, tbl_produto.origem
					, tbl_produto.lista_troca
					, tbl_produto.produto_principal
					, tbl_produto.parametros_adicionais::jsonb->> 'analise_obrigatoria' AS analise_obrigatoria 
					, tbl_produto.troca_obrigatoria
					, tbl_produto.produto_critico ";

		$join_marca = ' JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ';
	}

	if($login_fabrica==3) {
		$sql.=", tbl_produto.radical_serie2,
		           tbl_produto.radical_serie3,
			   tbl_produto.radical_serie4,
			   tbl_produto.radical_serie5,
			   tbl_produto.radical_serie6
			   ";
	}
	$sql.="FROM    tbl_produto
			JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			LEFT JOIN    tbl_familia ON tbl_familia.familia = tbl_produto.familia
			{$join_marca}
			WHERE   tbl_linha.fabrica   = $login_fabrica
			AND     tbl_produto.produto = $produto;";
	//die(nl2br($sql));
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$produto                  = trim(pg_result($res,0,produto));
		$linha                    = trim(pg_result($res,0,linha));
		$familia                  = trim(pg_result($res,0,familia));
		$descricao                = trim(pg_result($res,0,descricao));
		$referencia               = trim(pg_result($res,0,referencia));
		$voltagem                 = trim(pg_result($res,0,voltagem));
		$garantia                 = trim(pg_result($res,0,garantia));
		$mao_de_obra              = trim(pg_result($res,0,mao_de_obra));
		$mao_de_obra              = number_format($mao_de_obra,2,',','.');
		$mao_de_obra_admin        = trim(pg_result($res,0,mao_de_obra_admin));
		$mao_de_obra_admin        = number_format($mao_de_obra_admin,2,',','.');
		$ativo                    = trim(pg_result($res,0,ativo));
//		$off_line                 = trim(pg_result($res,0,off_line));
		$nome_comercial           = trim(pg_result($res,0,nome_comercial));
		$classificacao_fiscal     = trim(pg_result($res,0,classificacao_fiscal));
		$ipi				      = trim(pg_result($res,0,ipi));
		$radical_serie            = trim(pg_result($res,0,radical_serie));
		$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));
		$referencia_fabrica       = trim(pg_result($res,0,referencia_fabrica));
		if($login_fabrica == 35){
			$preco 					= trim(pg_result($res,0,preco));
			$preco 					= number_format($preco,2,',','.');
			$qtd_etiqueta 			= trim(pg_result($res,0,qtd_etiqueta_os));
			$uso_interno 			= trim(pg_result($res,0,uso_interno_ativo));
			$mao_obra_troca 		= trim(pg_result($res,0,mao_de_obra_troca));
			$mao_obra_troca 		= number_format($mao_obra_troca,2,',','.');
			$marca 					= trim(pg_result($res,0,marca));
			$origem 				= trim(pg_result($res,0,origem));
			$lista_troca			= trim(pg_result($res,0,lista_troca));
			$produto_principal		= trim(pg_result($res,0,produto_principal));
			$analise_obrigatoria 	= trim(pg_result($res,0,analise_obrigatoria));
			$analise_obrigatoria	= json_decode($analise_obrigatoria);
			$troca_obrigatoria 		= trim(pg_result($res,0,troca_obrigatoria));
			$produto_critico 		= trim(pg_result($res,0,produto_critico));
		}
		if($login_fabrica==3) {
			$radical_serie2            = trim(pg_result($res,0,radical_serie2));
			$radical_serie3            = trim(pg_result($res,0,radical_serie3));
			$radical_serie4            = trim(pg_result($res,0,radical_serie4));
			$radical_serie5            = trim(pg_result($res,0,radical_serie5));
			$radical_serie6            = trim(pg_result($res,0,radical_serie6));
		}
	}
}

$layout_menu = "callcenter";
$title = traduz("CONSULTA PRODUTOS");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script language="JavaScript">
	$(function() {
		$.autocompleteLoad(Array("produto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

function fnc_pesquisa_produto (campo, tipo) {

	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_produto.referencia;
		janela.descricao = document.frm_produto.descricao;
		janela.linha     = document.frm_produto.linha;
		janela.familia   = document.frm_produto.familia;
		janela.focus();
	}

	else
		alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
}
</script>

</head>

<form name="frm_produto" method="post" action="<? echo $PHP_SELF ?>" class='form-search form-inline tc_formulario'>
	<div class="titulo_tabela"><?=traduz('Parâmetros de Pesquisa')?></div>
	<br />
	<div class='row-fluid'>
	<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='produto_referencia'><?=traduz('Referência')?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" class="frm" id="produto_referencia" name="referencia" value="<? echo $referencia ?>" size="12" maxlength="20">
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
					</div>
				</div>		
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='produto_descricao'><?=traduz('Descrição')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" class="frm" id="produto_descricao" name="descricao" value="<? echo $descricao ?>"   size="40" maxlength="50">
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
			</div>
		</div>
	<div class='span2'></div>
	</div>
	<br />
	<div class='row-fluid'>
		<input type="hidden" name="btn_acao" value="">
		<center><input class="btn" type="button" value="<?=traduz("Pesquisar")?>" onclick="javascript: if ( document.frm_produto.btn_acao.value == '' ) { document.frm_produto.btn_acao.value='pesquisar'; document.frm_produto.submit() ; } else { alert ('<?=traduz('Aguarde a submissão...')?>'); }" alt='<?=traduz("Clique AQUI para pesquisar")?>'></center>
	</div>	
	
</form>

<br>
<br>

<?
if (strlen($_POST['btn_acao'])>0 AND strlen($produto) > 0) {

	$colspan = "";
	if ($login_fabrica == 171) {
		$colspan = ' colspan="2"';
	}	

	if($login_fabrica == 35){
		$col 		= ' colspan="2"';
		$centraliza = " style='text-align: center;'";
		$col_linha 	= ' colspan="9"';
	} else {
		$col_linha = ' colspan="5"';
	}

?>
<table border=0 width="700" align='center' class='table table-striped table-bordered table-large'>
<tr class='titulo_coluna'>
	<?php if ($login_fabrica == 171) {?>
	<th><?=traduz('Referência Fábrica')?></th>
	<?php }?>
	<th><?=traduz('Referência')?></th>
	<th<?=$col ?>><?=traduz('Descrição')?></th>
	<th><?=traduz('Garantia')?></th>
	<th><?=traduz('M.Obra Posto')?> (*)</th>
	<th><?=traduz('M.Obra Admin')?></th>
	<?php if($login_fabrica == 35) { ?>
		<th><?=traduz('Preço')?></th>
		<th><?=traduz('Qtd Etiqueta')?></th>
	<?php }	?>
</tr>

<tr>
	<?php if ($login_fabrica == 171) {?>
	<td ><? echo $referencia_fabrica; ?></td>
	<?php }?>
	<td ><? echo $referencia; ?></td>
	<td <?=$col ?>><? echo $descricao; ?></td>	
	<td ><? echo $garantia; ?></td>
	<td ><? echo $real . $mao_de_obra; ?></td>
	<td ><? echo $real . $mao_de_obra_admin; ?></td>
	<?php if($login_fabrica == 35){
		echo '<td>' . $real . $preco . '</td>';
		echo '<td>' . $qtd_etiqueta . '</td>';
	} ?>
</tr>

<tr bgcolor="#ffffff"><td <?=$col_linha ?>>&nbsp;</td></tr>

<tr class='titulo_coluna'>
	<th <?php echo $colspan;?>><?=traduz('Nome Comercial')?></th>
	<th><?=traduz('Linha')?></th>
	<th><?=traduz('Família')?></th>
	<th>Voltagem</th>	
	<?	if($login_fabrica == 35){
			echo '<th>Status Rede</th>';
			echo '<th>Status Uso Interno</th>';
			echo '<th' . $col . '>M. Obra Troca Admin</th>';
		} else {
			echo '<th>Status</th>';
		}
	?>
</tr>
<tr>
	<td <?php echo $colspan;?>><? echo $nome_comercial; ?></td>
	<td ><? echo $linha; ?></td>
	<td ><? echo $familia; ?></td>
	<td ><? echo $voltagem; ?></td>
	<td ><? if ($ativo == "t") echo "Ativo"; else echo "Inativo"; ?></td>
	<?	if($login_fabrica == 35){			
			if ($uso_interno == 't') echo '<td>Ativo</td>'; else echo '<td>Inativo</td>';
			echo '<td' . $col . '>R$ ' . $mao_obra_troca . '</td>';
		}
	?>
</tr>

<tr bgcolor="#ffffff"><td <?=$col_linha ?>>&nbsp;</td></tr>

<tr class='titulo_coluna'>
	<th <?php echo $colspan;?>><?=traduz('Classificação Fiscal')?></th>
	<th <?=$col ?>>I.P.I.</th>
	<th><?=traduz('Radical No. Série')?></th>
	<th colspan="2"><?=traduz('No. Série Obrigatório')?></th>
	<?	if($login_fabrica == 35){
			echo '<th>Marca</th>';
			echo '<th>Origem</th>';
		}
	?>
</tr>
<tr>
	<td  <?php echo $colspan;?>><? echo $classificacao_fiscal; ?></td>
	<td <?=$col ?> ><? echo $ipi; ?></td>

<? if($login_fabrica==3 ) { ?>
	<td ><? echo "$radical_serie<BR>";?>
	<? echo "$radical_serie2<BR>"; ?>
	<? echo "$radical_serie3<BR>"; ?>
	<? echo "$radical_serie4<BR>"; ?>
	<? echo "$radical_serie5<BR>"; ?>
	<? echo "$radical_serie6"; ?>
<? } else {?>
	<td ><? echo $radical_serie; ?>
<? } ?>
	</td>
	<td  colspan="2"><? if ($numero_serie_obrigatorio == 't' ) echo traduz("Sim"); else echo traduz("Não"); ?></td>
	<?	if($login_fabrica == 35){
			echo '<td>' . $marca . '</td>';
			echo '<td>' . $origem . '</td>';
		}
	?>
</tr>

<?	if($login_fabrica == 35) { ?>
	<tr bgcolor="#ffffff"><td <?=$col_linha ?>>&nbsp;</td></tr>

	<tr class='titulo_coluna'>
		<th>Lista Troca</th>
		<th>Produto Principal</th>
		<th <?=$col ?>>Análise Obrigatória no Posto Central</th>
		<th <?=$col ?>>Troca Obrigatória</th>
		<th <?=$col ?>>Produto Crítico</th>
	</tr>
	<tr>
		<td <?=$centraliza ?>><? if($lista_troca == 't') echo 'Sim'; else echo 'Não'; ?></td>
		<td <?=$centraliza ?>><? if($produto_principal == 't') echo 'Sim'; else echo 'Não'; ?></td>
		<td <?=$centraliza.$col ?>><? if($analise_obrigatoria == 't') echo 'Sim'; else echo 'Não'; ?></td>
		<td <?=$centraliza.$col ?>><? if($troca_obrigatoria == 't') echo 'Sim'; else echo 'Não'; ?></td>
		<td  <?=$centraliza.$col ?>><? if($produto_critico == 't') echo 'Sim'; else echo 'Não'; ?></td>
	</tr>
<? } ?>

</table>

<BR><BR><BR>
<?
}
?>

<!-- 
<center>
	<a href='<?echo $PHP_SELF;?>?listartudo=1'>CLIQUE AQUI PARA LISTAR TODOS OS PRODUTOS CADASTRADOS</a>
</center>
<p> -->


<? if ($login_fabrica == 3){ ?>
<!--
<center>
	<a href='PROVISORIO_produto_garantia.php' TARGET='_blank'>PRODUTOS E GARANTIAS</a>
</center>
<p>
-->
<? } ?>

<?
$listartudo = $_GET['listartudo'];
if ($listartudo == 1){

	$sql = "SELECT		tbl_produto.referencia,
						tbl_produto.referencia_fabrica,
						tbl_produto.voltagem  ,
						tbl_produto.produto   ,
						tbl_produto.descricao ,
						tbl_produto.ativo     ,
						tbl_familia.descricao AS familia,
						tbl_linha.nome        AS linha
			FROM		tbl_produto
			JOIN		tbl_linha     USING (linha)
			LEFT JOIN	tbl_familia USING (familia)
			WHERE		tbl_linha.fabrica = $login_fabrica
			ORDER BY	tbl_linha.nome        ASC,
						tbl_produto.voltagem  ASC,
						tbl_produto.descricao ASC,
						tbl_familia.descricao ASC;";
	$res = pg_exec ($con,$sql);
/*
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount = $sql;
$sqlCount .= ") AS count";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;				// máximo de links à serem exibidos
$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //
*/
	$registros = @pg_numrows($res);

	if ($registros > 0) {
		echo "<table width='700' align='center' border='1' class='tabela' cellpadding='2' cellspacing='1'>";
		echo "<tr class='titulo_coluna'>";

		echo "<td align='center' width='50'>";
		echo "<b>".traduz("Status")."</b>";
		echo "</td>";
		if ($login_fabrica == 171) {
			echo "<td align='center' width='200'>";
			echo "<b>".traduz("Referência Fábrica")."</b>";
			echo "</td>";
		}

		echo "<td align='center' width='200'>";
		echo "<b>".traduz("Referência")."</b>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<b>".traduz("Descrição")."</b>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<b>".traduz("Família")."</b>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<b>".traduz("Linha")."</b>";
		echo "</td>";

		echo "</tr>";
	}

	for ($i = 0 ; $i < $registros ; $i++) {
		
		if($i%2==0) $cor="#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>";

		echo "<td align='center'>";
		if (pg_result ($res,$i,ativo) <> 't') echo "<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>";
		else                                  echo "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
		echo "&nbsp;</td>";


		if ($login_fabrica == 171) {
			echo "<td align='left' nowrap>";
			echo pg_result ($res,$i,referencia_fabrica);
			echo "&nbsp;</td>";
		}

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,referencia);
		
		if (strlen(pg_result ($res,$i,voltagem)) > 0) echo " / ". pg_result ($res,$i,voltagem);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo "<a href='$PHP_SELF?produto=" . pg_result ($res,$i,produto) . "'>";
		echo pg_result ($res,$i,descricao);
		echo "</a>";
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,familia);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,linha);
		echo "&nbsp;</td>";

		echo "</tr>";
	}
	
	echo "</table>";

/*
// ##### PAGINACAO ##### //

// links da paginacao
echo "<br>";

echo "<div>";

if($pagina < $max_links) { 
	$paginacao = pagina + 1;
}else{
	$paginacao = pagina;
}

// paginacao com restricao de links da paginacao

// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("strings", "sim");

// função que limita a quantidade de links no rodape
$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

for ($n = 0; $n < count($links_limitados); $n++) {
	echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
}

echo "</div>";

$resultado_inicial = ($pagina * $max_res) + 1;
$resultado_final   = $max_res + ( $pagina * $max_res);
$registros         = $mult_pag->Retorna_Resultado();

$valor_pagina   = $pagina + 1;
$numero_paginas = intval(($registros / $max_res) + 1);

if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

if ($registros > 0){
	echo "<br>";
	echo "<div>";
	echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
	echo "<font color='#cccccc' size='1'>";
	echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
	echo "</font>";
	echo "</div>";
}

// ##### PAGINACAO ##### //

echo "<br>";
*/
}
?>

<p>

<?
include "rodape.php";
?>
