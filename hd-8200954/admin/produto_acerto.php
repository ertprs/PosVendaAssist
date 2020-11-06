<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btn_acao"]) > 0) $btn_acao      = $_POST["btn_acao"];
else $btn_acao      = $_GET["btn_acao"];
//$btn_acao     = $_POST["btn_acao"];
if (strlen($_POST["produto"]) > 0) $produto      = $_POST["produto"];
else $produto      = $_GET["produto"];
if (strlen($_POST["referencia"]) > 0) $referencia      = $_POST["referencia"];
else $referencia      = $_GET["referencia"];
if (strlen($_POST["descricao"]) > 0) $descricao      = $_POST["descricao"];
else $descricao      = $_GET["descricao"];

if ($btn_acao == "gravar") {
	$peca_qtde = $_POST["peca_qtde"];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for ($i = 0 ; $i < $peca_qtde ; $i++) {
		$peca					= trim($_POST["peca_".$i]);
		$origem					= trim($_POST["origem_".$i]);
		$garantia_diferenciada	= trim($_POST["garantia_diferenciada_".$i]);
		$devolucao_obrigatoria	= trim($_POST["devolucao_obrigatoria_".$i]);
		$item_aparencia			= trim($_POST["item_aparencia_".$i]);
		$acumular_kit			= trim($_POST["acumular_kit_".$i]);
		$retorna_conserto		= trim($_POST["retorna_conserto_".$i]);
		$bloqueada_garantia		= trim($_POST["bloqueada_garantia_".$i]);

		if (strlen($garantia_diferenciada) == 0) $garantia_diferenciada = 'null';
		if (strlen($devolucao_obrigatoria) == 0) $devolucao_obrigatoria = 'f';
		if (strlen($item_aparencia) == 0)        $item_aparencia        = 'f';
		if (strlen($acumular_kit) == 0)          $acumular_kit          = 'f';
		if (strlen($retorna_conserto) == 0)      $retorna_conserto      = 'f';
		if (strlen($bloqueada_garantia) == 0)    $bloqueada_garantia    = 'f';

		$sql =	"UPDATE tbl_peca SET
					origem					= '$origem'               ,
					garantia_diferenciada	= $garantia_diferenciada  ,
					devolucao_obrigatoria	= '$devolucao_obrigatoria',
					item_aparencia			= '$item_aparencia'       ,
					acumular_kit			= '$acumular_kit'         ,
					retorna_conserto		= '$retorna_conserto'     ,
					bloqueada_garantia		= '$bloqueada_garantia'
				WHERE	fabrica	= $login_fabrica
				AND		peca	= $peca;";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$pagina_atual = $_POST["pagina_atual"];
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		if (strlen($produto) > 0) {
			header ("Location: $PHP_SELF?produto=$produto&referencia=$referencia&descricao=$descricao&btn_acao=listar_por_produto");
		}else{
			header ("Location: $PHP_SELF?pagina=$pagina_atual");
		}
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "Manutenção de Peças";
include 'cabecalho.php';

?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}

</style>

<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_peca.produto;
		janela.focus();
	}
}
</script>

<br>
<form name="frm_peca" method="post" action="<? echo $PHP_SELF ?>">
<?
if ($_POST["btn_acao"] <> 'listar_por_produto' AND strlen($produto) == 0)
{
?>
	<input type='hidden' name='produto' value=''>
	<table width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1'>
		<tr bgcolor='#D9E2EF'>
			<td align='center'><b>Referência</b></td>
			<td align='center'><b>Descrição</b></td>
		</tr>
		<tr>
			<td><input class='frm' type="text" name="referencia" value="<? echo $referencia; ?>" size="15" maxlength="20">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"></td>
			<td><input class='frm' type="text" name="descricao" value="<? echo $descricao; ?>" size="50" maxlength="50">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"></td>
		</tr>
	</table>

	<br>

	<center><img src='imagens/btn_listabasicademateriais.gif' onclick="javascript: if (document.frm_peca.btn_acao.value == '') { document.frm_peca.btn_acao.value='listar_por_produto' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" alt='Listar Lista Básica de Materiais por Produto' border='0' style="cursor:pointer;"></center>

	<?
	echo "<br>";
}else{
?>
<input type='hidden' name='produto' value='<?echo $produto?>'>
<input type='hidden' name='referencia' value='<?echo $referencia?>'>
<input type='hidden' name='descricao' value='<?echo $descricao?>'>
	<table width='400' border='0' class='conteudo' cellpadding='2' cellspacing='1'>
		<tr bgcolor='#D9E2EF'>
			<td align='center'><b>Referência</b></td>
			<td align='center'><b>Descrição</b></td>
		</tr>
		<tr>
			<td><? echo $referencia; ?></td>
			<td><? echo $descricao; ?></td>
		</tr>
	</table>
	<p align='center'><a href='lbm_cadastro.php?produto=<?echo $produto?>&btn_lista=listar'>Clique aqui para acessar a lista básica deste produto</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 
	 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href='<?echo $PHP_SELF?>?'>Clique aqui para localizar outro produto</a></p>
<?
}

$sql = "SELECT  tbl_peca.peca                  ,
				tbl_peca.referencia            ,
				tbl_peca.descricao             ,
				tbl_peca.origem                ,
				tbl_peca.garantia_diferenciada ,
				tbl_peca.devolucao_obrigatoria ,
				tbl_peca.item_aparencia        ,
				tbl_peca.acumular_kit          ,
				tbl_peca.retorna_conserto      ,
				tbl_peca.bloqueada_garantia
		FROM	tbl_peca ";

if ($btn_acao == 'listar_por_produto') {
	$sql .= "JOIN tbl_lista_basica   ON tbl_lista_basica.peca    = tbl_peca.peca
									AND tbl_lista_basica.fabrica = $login_fabrica
			JOIN  tbl_produto        ON tbl_produto.produto      = tbl_lista_basica.produto
									AND tbl_produto.produto      = $produto ";
}
$sql .= "WHERE tbl_peca.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao ";

if ($btn_acao == 'listar_por_produto') {
	$res = pg_exec ($con,$sql);
}

if ($btn_acao <> 'listar_por_produto') {
	// ##### PAGINACAO ##### //
	
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";
	
	require "_class_paginacao.php";
	
	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //
}

if (pg_numrows($res) == 0) {
	echo "<TABLE width='700' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
?>

	
	<table border='0' class='conteudo' cellpadding='2' cellspacing='1'>
		<tr bgcolor='#D9E2EF'>
			<td><b>Referência</b></td>
			<td><b>Descrição</b></td>
			<td><b>Origem</b></td>
			<td><b>Garantia<br>Diferenciada</b></td>
			<td><b>Devolução<br>Obrigatória</b></td>
			<td><b>Item de<br>Aparência</b></td>
			<td><b>Peça acumulada<br>para kit</b></td>
			<td><b>Peça retorna<br>para conserto</b></td>
			<td><b>Bloqueada<br>para garantia</b></td>
		</tr>
<?
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$cor = ($i % 2 == 0) ? "#FFFFFF": '#F1F4FA';
?>
		<tr bgcolor='<? echo $cor; ?>'>
			<td align='left'>
				<input type="hidden" name="peca_<? echo $i; ?>" value="<? echo pg_result($res,$i,peca); ?>">
				<? echo pg_result($res,$i,referencia); ?>
			</td>
			<td align='left' nowrap><? echo pg_result($res,$i,descricao); ?></td>
			<td>
				<select name='origem_<? echo $i; ?>' size='1'>
					<option value='NAC' <? if (pg_result($res,$i,origem) == 'NAC' OR pg_result($res,$i,origem) == 1) echo "selected" ?>> Fabricação </option>
					<option value='IMP' <? if (pg_result($res,$i,origem) == 'IMP' OR pg_result($res,$i,origem) == 2) echo "selected" ?>> Importado </option>
					<option value='TER' <? if (pg_result($res,$i,origem) == 'TER') echo "selected" ?>> Terceiros </option>
				</select>
			</td>
			<td><input class='frm' type="text" name="garantia_diferenciada_<? echo $i; ?>" value="<? echo pg_result ($res,$i,garantia_diferenciada); ?>" size="3" maxlength="3"></td>
			<td><input type='checkbox' name='devolucao_obrigatoria_<? echo $i; ?>' value='t' <? if (pg_result($res,$i,devolucao_obrigatoria) == 't' ) echo "checked" ?>></td>
			<td><input type='checkbox' name='item_aparencia_<? echo $i; ?>'        value='t' <? if (pg_result($res,$i,item_aparencia) == 't' )        echo "checked" ?>></td>
			<td><input type='checkbox' name='acumular_kit_<? echo $i; ?>'          value='t' <? if (pg_result($res,$i,acumular_kit) == 't' )          echo "checked" ?>></td>
			<td><input type='checkbox' name='retorna_conserto_<? echo $i; ?>'      value='t' <? if (pg_result($res,$i,retorna_conserto) == 't' )      echo "checked" ?>></td>
			<td><input type='checkbox' name='bloqueada_garantia_<? echo $i; ?>'    value='t' <? if (pg_result($res,$i,bloqueada_garantia) == 't' )    echo "checked" ?>></td>
		</tr>
<?
	}
?>
	</table>
	<br>


	<input type="hidden" name="peca_qtde"           value="<? echo pg_numrows($res); ?>">
	<input type="hidden" name="pagina_atual"        value="<? echo $pagina; ?>">
	<input type='hidden' name='btn_acao' value=''>

	<center><img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_peca.btn_acao.value == '' ) { document.frm_peca.btn_acao.value='gravar'; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar' border='0' style='cursor:pointer;'></center>
	</form>
<?
}

if ($btn_acao <> 'listar_por_produto') {
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
}
?>

<? include "rodape.php"; ?>