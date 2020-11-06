<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 14) {
	header ("Location: tabela_precos_intelbras.php");
	exit;
}
if ($login_fabrica == 1) {
	header ("Location: tabela_precos_blackedecker_consulta.php");
	exit;
}

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {
	$title = "Tabela de Preços";
	$layout_menu = 'preco';
	include "cabecalho.php";
	echo "<H4>TABELA DE PREÇOS BLOQUEADA</H4>";
	include "rodape.php";
	exit;
}




include 'funcoes.php';

$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) $liberar_preco = false;


$title = "Tabela de Preços";

$layout_menu = 'preco';
include "cabecalho.php";

if($_POST['tabela'])             $tabela             = $_POST['tabela'];

if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto'];
if($_POST['descricao_produto'])  $descricao_produto  = $_POST['descricao_produto'];

if($_GET['tabela'])             $tabela              = $_GET['tabela'];

if($_GET['referencia_produto']) $referencia_produto  = $_GET['referencia_produto'];
if($_GET['descricao_produto'])  $descricao_produto   = $_GET['descricao_produto'];

if($_POST['referencia_peca']) $referencia_peca       = $_POST['referencia_peca'];
if($_POST['descricao_peca'])  $descricao_peca        = $_POST['descricao_peca'];

if($_GET['referencia_peca']) $referencia_peca        = $_GET['referencia_peca'];
if($_GET['descricao_peca'])  $descricao_peca         = $_GET['descricao_peca'];

if ($login_fabrica == 3) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
		$tabela = "";
	}
}

?>

<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
		janela.focus();
	}
}
</script>

<style>
.letras {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: bold;
	border: 0px solid;
	color:#007711;
	background-color: #ffffff
}

.lista {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}
</style>
<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->


<script language=JavaScript>
//script from www.argnet.tk
function blockError(){return true;}
window.onerror = blockError;
</script>

<script language=JavaScript>
function disableselect(e){
    return false
}
function reEnable(){
    return true
}

//if IE4+
document.onselectstart=new Function ("return false")
 //if NS6
if (window.sidebar){
    document.onmousedown=disableselect
    document.onclick=reEnable
}

function FuncTabela (tabela) {
	if (tabela == 54) {
		document.forms[0].submit();
	}
}
</script>

<form method='get' action='<? echo $PHP_SELF ?>' name='frm_tabela'>

<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial"><b>&nbsp;&nbsp;Tabela</b></font>
	</td>

	<td align="left" width="300">
		<select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
<?

		$res = pg_exec ($con,"SELECT linha_pedido FROM tbl_fabrica WHERE fabrica = $login_fabrica");
		$linha_pedido = pg_result ($res,0,0);


		$sql = "SELECT      tbl_tabela.tabela      ,
							tbl_tabela.sigla_tabela,
							tbl_tabela.descricao
				FROM        tbl_tabela
				JOIN        tbl_posto_linha USING (tabela)
				JOIN        tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
				WHERE       tbl_tabela.fabrica    = $login_fabrica
				AND         tbl_posto_linha.posto = $login_posto
				AND         tbl_tabela.ativa   = 't'
				GROUP BY    tbl_tabela.tabela      ,
							tbl_tabela.sigla_tabela,
							tbl_tabela.descricao ";
		if ($login_fabrica == 1) $sql .= "ORDER BY tbl_tabela.tabela ASC";
		else                     $sql .= "ORDER BY tbl_tabela.sigla_tabela";

//		if ($ip == '201.42.112.110') echo $sql; exit;
		$res = pg_exec($con,$sql);

		if (pg_numrows ($res) == 0 and $linha_pedido <> 't' ) {
			$sql = "SELECT *
					FROM   tbl_tabela
					WHERE  tbl_tabela.fabrica = $login_fabrica
					AND    tbl_tabela.ativa   = 't' ";

			if ($login_fabrica == 1) $sql .= "AND tbl_tabela.sigla_tabela not in ('GARAN') ";

			if ($login_fabrica == 1) $sql .= "ORDER BY tbl_tabela.tabela ASC";
			else                     $sql .= "ORDER BY tbl_tabela.sigla_tabela";

			$res = pg_exec($con,$sql);
		}

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$aux_tabela       = trim(pg_result($res,$i,tabela));
			$aux_sigla_tabela = trim(pg_result($res,$i,descricao));

			echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";
		}
?>
		</select>
	</td>
</tr>
</table>

<? if ($tabela != 54) { ?>

<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;<? if ($login_fabrica == 1) { ?>Código<? }else{ ?>Referência<? } ?> do produto</b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='referencia_produto' size='20' maxlength='30' value='<? echo $referencia_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"referencia", document.frm_tabela.voltagem_produto)'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;ou <? if (($login_fabrica == 1) or ($login_fabrica == 11)) { ?>Descrição<? }else{ ?>Modelo<? } ?> do produto</b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='descricao_produto' size='20' maxlength='50' value='<? echo $descricao_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"descricao", document.frm_tabela.voltagem_produto)'></a>
		<input type="hidden" name="voltagem_produto" value="">
	</td>
</tr>
</table>

<? if ($login_fabrica == 3 OR $login_fabrica == 1) { ?>
<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;<? if ($login_fabrica == 1) { ?>Código<? }else{ ?>Referência<? } ?> da peça</b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="0" cellspacing="4" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200" bgcolor='#d9e2ef'>
		<font face="arial" size='2'><b>&nbsp;&nbsp;ou <? if ($login_fabrica == 1) { ?>Descrição<? }else{ ?>Modelo<? } ?> da peça</b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='descricao_peca' size='20' maxlength='50' value='<? echo $descricao_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
	</td>
</tr>
</table>
<? } ?>
<? } ?>

<table width="500" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao"   value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('Aguarde submissão') }" ALT="Listar tabela de preços" border='0' style='cursor: hand;'>
		<img src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" ALT="Listar tabela de preços" border='0' style='cursor: hand;'>
	</td>
</tr>
</table>

</form>

<? if ($tabela != 54) { ?>
<p align="center"><a href="<? echo $PHP_SELF ?>?relatorio=1">Clique aqui</a> para ver relação de produtos.<p>
<? } ?>

<?
if ($login_fabrica <> 3) {
	echo "<p align='center'>Para fazer o download da tabela de preços em formato XLS ou TXT, <a href='tabela_precos_xls.php?tabela=$tabela'>clique aqui</a></p>";
}

if (strlen ($_GET['relatorio']) > 0) {
	if ($login_fabrica == 1) $tab = "31";

	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);

	echo "<table align='center' border='1' width='65%'>";

	echo "<tr bgcolor='$cor'>";

	echo "<td class='lista'><b>REFERÊNCIA</b></td>";

	echo "<td class='lista'><b>DESCRIÇÃO</b></td>";

	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#f8f8f8';

		$refer = pg_result ($res,$i,referencia);
		$descr = pg_result ($res,$i,descricao);

		echo "<tr bgcolor='$cor'>";

		echo "<td class='lista'>";
		if ($login_fabrica == 1) echo "<a href='$PHP_SELF?tabela=$tab&referencia_produto=$refer&descricao_produto=$descr&btn_acao=continuar'>";
		echo $refer;
		if ($login_fabrica == 1) echo "</a>";
		echo "</td>";

		echo "<td class='lista'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "</tr>";
	}
	echo "</table>";
}

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   tbl_posto_fabrica.item_aparencia
		FROM     tbl_posto
		JOIN     tbl_posto_fabrica USING(posto)
		WHERE    tbl_posto.posto           = $login_posto
		AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$item_aparencia = pg_result($res,0,item_aparencia);
}

if(strlen($tabela) > 0) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
		########## EXIBE TABELA DE PRECO
		$letra = (strlen($_GET['letra']) == 0) ? 'a' : $_GET['letra'];

		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),999999990.99) AS total
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				WHERE   tbl_peca.fabrica       = $login_fabrica
				AND		tbl_tabela_item.tabela = $tabela
				AND		tbl_peca.ativo         = 't'
				AND		tbl_peca.descricao ILIKE '$letra%'
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
//		$res = pg_exec ($con,$sql);
//echo $sql;
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

// ##### PAGINACAO ##### //
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 11;					// máximo de links à serem exibidos
$max_res   = 100;					// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();		// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //

	if (pg_numrows($res) > 0) {
		#---------- listagem -------------
		echo "<table width='600' align='center' cellspacing='3' border='1'>";
		echo "<tr bgcolor='#007711'>";
		echo "<td align='center' colspan='7'>";
		echo "<font face='verdana' size='2' color='#FFFFFF'><b>Para facilitar a visualização dos itens, separamos por iniciais.<br>Para consultar um item, clique na inicial correspondente.</b></font><br>";
		echo "<table width='100%' align='center' cellspacing='1' border='0' cellpadding='2'>";

		echo "<tr class='letras'>";
		$letras =  array(0=>'A', 'B', 'C', 'D', 'E',
							'F', 'G', 'H', 'I', 'J',
							'K', 'L', 'M', 'N', 'O',
							'P', 'Q', 'R', 'S', 'T',
							'U', 'V', 'W', 'X', 'Y', 'Z');
		$totalLetras = count($letras);
		for($j=0; $j<$totalLetras; $j++){
			//echo "<a href='$PHP_SELF?letra=a&tabela=$tabela&referencia_produto=$referencia_produto&descricao_produto=$descricao_produto'> A </a>";
			echo "<td align='center'>";
			echo "<a href='$PHP_SELF?letra=$letras[$j]&tabela=$tabela'>&nbsp;$letras[$j]&nbsp;</a>";
			echo "</td>";
		}
		echo "</tr>";
		echo "</table>";

		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Peça</b></font></td>";
		echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Descrição</b></font></td>";
		echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Unidade</b></font></td>";

		if ($liberar_preco) {
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>IPI</b></font></td>";
			if ($login_fabrica == 3 and 1 == 2) echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço c/ IPI</b></font></td>";
		}
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$unidade            = trim(pg_result ($res,$i,unidade));
			$preco              = trim(pg_result ($res,$i,preco));
			$ipi                = trim(pg_result ($res,$i,ipi));

			$preco_com_ipi = $preco * (1 + $ipi/100);

			$cor = '#ffffff';
			if ($i % 2 == 0) $cor = '#f8f8f8';

			echo "<tr bgcolor='$cor'>";

			echo "<td>";
			echo "<font face='arial' size='-2'>";
			echo $peca_referencia;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='arial' size='-2'>";
			echo $peca_descricao;
			echo "</font>";
			echo "</td>";

			echo "<td>";
			echo "<font face='arial' size='-2'>";
			echo $unidade;
			echo "</font>";
			echo "</td>";

			if ($liberar_preco) {
				echo "<td align='right'>";
				echo "<font face='arial' size='-2'>";
				echo number_format ($preco,2,",",".");
				echo "</font>";
				echo "</td>";

				echo "<td align='right'>";
				echo "<font face='arial' size='-2'>";
				echo $ipi;
				echo "</font>";
				echo "</td>";

				if ($login_fabrica == 3 and 1 == 2){
					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo number_format ($preco_com_ipi,2,",",".");
					echo "</font>";
					echo "</td>";
				}

			}

			echo "</tr>";
		}

		echo "</table>";

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

		}else{
			// SE NAO ENCONTROU REGISTROS
			echo "<center><font face='arial' size='-1'>Peças da linha com iniciais <b>\"$letra\"</b> não encontradas</font></center><br>";
			echo "<table width='600' align='center' cellspacing='3' border='0'>";
			echo "<tr bgcolor='#007711'>";
			echo "<td align='center' colspan='7'>";

			echo "<table width='100%' align='center' cellspacing='1' border='0' cellpadding='2'>";

			echo "<tr class='letras'>";
			$letras =  array(0=>'A', 'B', 'C', 'D', 'E',
								'F', 'G', 'H', 'I', 'J',
								'K', 'L', 'M', 'N', 'O',
								'P', 'Q', 'R', 'S', 'T',
								'U', 'V', 'W', 'X', 'Y', 'Z');
			$totalLetras = count($letras);
			for($j=0; $j<$totalLetras; $j++){
				echo "<td align='center'>";
				echo "<a href='$PHP_SELF?letra=$letras[$j]&tabela=$tabela'>&nbsp;$letras[$j]&nbsp;</a>";
				echo "</td>";
			}
			echo "</tr>";
			echo "</table>";

			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
	}else{
		########## EXIBE LISTA BÁSICA
		// SQL RETIRADO PARA MELHORAR PERFORMANCE

		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),999999990.99) AS total             ,
						tbl_produto.referencia                                                  AS produto_referencia,
						tbl_produto.descricao                                                   AS produto_descricao
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				JOIN    tbl_lista_basica ON tbl_peca.peca        = tbl_lista_basica.peca
				JOIN    tbl_produto      ON tbl_produto.produto  = tbl_lista_basica.produto
				WHERE   tbl_peca.fabrica = $login_fabrica
				AND     tbl_produto.ativo = 't'
				AND     tbl_peca.ativo    = 't' ";


		if ($login_fabrica <> 6) {
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia <> 't' ";
		}
		if (strlen($descricao_produto) > 0) {
			$sql .= " AND tbl_produto.descricao ilike '%$descricao_produto%' ";
		}

		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(tbl_produto.referencia) = upper('$referencia_produto') ";
		}

		if (strlen($descricao_peca) > 0) {
			$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
		}

		if (strlen($referencia_peca) > 0) {
			$sql .= "AND upper(tbl_peca.referencia) = upper('$referencia_peca') ";
		}

		// ORDENACAO
		if ($login_fabrica == 3){
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.descricao ,
								tbl_peca.descricao    ,
								tbl_produto.referencia";
		}else{
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.referencia,
								tbl_produto.descricao";
		}

		// SQL INSERIDO PARA MELHORAR PERFORMANCE
		$sql = "SELECT  c.produto_referencia  ,
						c.produto_descricao   ,
						c.peca_referencia     ,
						c.peca_descricao      ,
						c.unidade             ,
						c.ipi                 ,
						tbl_tabela_item.preco ,
						to_char((tbl_tabela_item.preco * ((1 + c.ipi))/10),999999990.99)::float AS total
				FROM (
						SELECT  b.produto_referencia                  ,
								b.produto_descricao                   ,
								tbl_peca.peca                         ,
								tbl_peca.referencia AS peca_referencia,
								tbl_peca.descricao  AS peca_descricao ,
								tbl_peca.unidade                      ,
								tbl_peca.ipi
						FROM (
								SELECT  a.produto_referencia    ,
										a.produto_descricao     ,
										tbl_lista_basica.produto,
										tbl_lista_basica.peca
								FROM (
										SELECT  tbl_produto.produto                         ,
												tbl_produto.referencia AS produto_referencia,
												tbl_produto.descricao  AS produto_descricao
										FROM  tbl_produto
										JOIN  tbl_linha ON tbl_linha.linha = tbl_produto.linha
										WHERE tbl_produto.ativo IS TRUE
										AND   tbl_linha.fabrica = $login_fabrica ";

		if (strlen($descricao_produto) > 0) {
			$sql .= " AND tbl_produto.descricao ilike '%$descricao_produto%' ";
		}

		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(tbl_produto.referencia) = upper('$referencia_produto') ";
		}

		$sql .= "		) AS a
						JOIN tbl_lista_basica    ON tbl_lista_basica.produto = a.produto
												AND tbl_lista_basica.fabrica = $login_fabrica
						) AS b
						JOIN tbl_peca    ON tbl_peca.peca    = b.peca
										AND tbl_peca.fabrica = $login_fabrica
										AND tbl_peca.ativo IS TRUE ";
		if ($login_fabrica <> 6) {
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";
		}

		if (strlen($descricao_peca) > 0) {
			$sql .= " WHERE tbl_peca.descricao ilike '%$descricao_peca%' ";
		}

		if (strlen($referencia_peca) > 0) {
			if (strlen($descricao_peca) > 0)  $sql .= "AND   upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
			if (strlen($descricao_peca) == 0) $sql .= "WHERE upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
		}

		$sql .= ") AS c
				JOIN tbl_tabela_item ON tbl_tabela_item.peca   = c.peca
									AND tbl_tabela_item.tabela = $tabela ";

		// ORDENACAO
		if ($login_fabrica == 1){
			$sql .= "ORDER BY   c.peca_descricao    ,
								c.produto_descricao ,
								c.produto_referencia";
		}elseif ($login_fabrica == 3){
			$sql .= "ORDER BY   c.produto_descricao ,
								c.peca_descricao    ,
								c.produto_referencia";
		}else{
			$sql .= "ORDER BY   c.produto_referencia,
								c.produto_descricao";
		}
		
		if ($login_fabrica == 1 && $tabela == 54) {
			$sql = "SELECT  a.peca_referencia     ,
							a.peca_descricao      ,
							a.unidade             ,
							a.ipi                 ,
							tbl_tabela_item.preco ,
							to_char((tbl_tabela_item.preco * ((1 + a.ipi))/10),999999990.99)::float AS total
					FROM (
							SELECT  tbl_peca.peca                         ,
									tbl_peca.referencia AS peca_referencia,
									tbl_peca.descricao  AS peca_descricao ,
									tbl_peca.unidade                      ,
									tbl_peca.ipi
							FROM  tbl_peca
							WHERE tbl_peca.fabrica = $login_fabrica
							AND   tbl_peca.ativo IS TRUE ";
		if ($login_fabrica <> 6) {
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";
		}

			if (strlen($referencia_peca) > 0) {
				$sql .= "AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
			}elseif (strlen($descricao_peca) > 0) {
				$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
			}

			$sql .= ") AS a
					JOIN tbl_tabela_item ON tbl_tabela_item.peca   = a.peca
										AND tbl_tabela_item.tabela = $tabela ";

			// ORDENACAO
			$sql .= "ORDER BY   a.peca_descricao";
		}

		echo "<!-- $sql -->";
		$res = pg_exec ($con,$sql);
//if ($ip == '201.0.9.216') { echo "$sql<BR>:: -> ".pg_numrows($res);  exit;}
		if (strlen($msg_erro) == 0){
			#--------- Criacao do arquivo em XLS ------------
			$arquivo = "download/tabela" . $tabela . ".csv";
			$fp = @fopen ($arquivo, 'w');

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if ($login_fabrica != 1 && $tabela != 54) {
					if ($mostraTopo == 'n'){
						$linha  = pg_result ($res,$i,produto_referencia);
						$linha .= ";";
						$linha  = pg_result ($res,$i,produto_descricao);
						$linha .= ";";
					}
				}
				$linha  = pg_result ($res,$i,peca_referencia);
				$linha .= ";";
				$linha .= pg_result ($res,$i,peca_descricao);
				$linha .= ";";
				$linha .= pg_result ($res,$i,unidade);
				if ($liberar_preco) {
					$linha .= ";";
					$linha .= pg_result ($res,$i,preco);
					$linha .= ";";
					$linha .= pg_result ($res,$i,ipi);
				}

				@fwrite ($fp,$linha);
				@fwrite ($fp,"\n");
			}
			@fclose ($fp);

			if (pg_numrows($res) == 0) {
				echo "<center><font face='arial' size='-1'>Produto informado não encontrado</font></center>";
			}

			if ($login_fabrica == 1) {
				$sql = "SELECT tbl_condicao.acrescimo_financeiro
						FROM   tbl_condicao
						WHERE  tbl_condicao.fabrica  = $login_fabrica
						AND    tbl_condicao.descricao ilike '%vista%';";
				$resx = pg_exec ($con,$sql);

				if (pg_numrows($resx) > 0) {
					$acrescimo_financeiro = pg_result ($resx,0,acrescimo_financeiro);
				}

				$sql = "SELECT tbl_tipo_posto.acrescimo_tabela_base
						FROM   tbl_tipo_posto
						JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
												AND tbl_posto_fabrica.posto      = $login_posto
												AND tbl_posto_fabrica.fabrica    = $login_fabrica
						WHERE  tbl_tipo_posto.fabrica  = $login_fabrica
						AND    tbl_posto_fabrica.posto = $login_posto;";
				$resx = pg_exec ($con,$sql);

				if (pg_numrows($resx) > 0) {
					$acrescimo_tabela_base = pg_result ($resx,0,acrescimo_tabela_base);
				}
			}

			#---------- listagem -------------
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if ($login_fabrica != 1 && $tabela != 54) {
					$produto_referencia = trim(@pg_result ($res,$i,produto_referencia));
					$produto_descricao  = trim(@pg_result ($res,$i,produto_descricao));
					$prox_refer         = trim(@pg_result ($res,$i-1,produto_referencia));
					$prox_descr         = trim(@pg_result ($res,$i-1,produto_descricao));
				}
				$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_result ($res,$i,unidade));
				$preco              = trim(pg_result ($res,$i,preco));
				$ipi                = trim(pg_result ($res,$i,ipi));

				$preco_com_ipi = $preco * (1 + $ipi/100);

				if ($login_fabrica == 1) {
					$preco = $preco * $acrescimo_financeiro * $acrescimo_tabela_base;
				}

				$cor = '#ffffff';
				if ($i % 2 == 0) $cor = '#f8f8f8';

				if ($login_fabrica != 1 && $tabela != 54) {
					if ($mostraTopo <> 'n'){
						if ($prox_refer <> $produto_referencia OR $prox_descr <> $produto_descricao) {
							flush();
							echo "<table width='600' align='center' cellspacing='3' border='0'>";
							echo "<tr>";
							echo "<td bgcolor='#007711' align='center' colspan='7'><font face='arial' color='#ffffff'><b>$produto_referencia - $produto_descricao</b></font></td>";
							echo "</tr>";
							echo "<tr>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Peça</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Descrição</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Unidade</b></font></td>";
							if ($liberar_preco) {
								echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço</b></font></td>";
								echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>IPI</b></font></td>";
								if ($login_fabrica == 3 and 1 == 2) echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço c/ IPI</b></font></td>";
							}
							echo "</tr>";
						}
					}
				}else{
					if ($i == 0) {
						flush();
						echo "<table width='600' align='center' cellspacing='3' border='0'>";
						echo "<tr>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Peça</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Descrição</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Unidade</b></font></td>";
						if ($liberar_preco) {
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>IPI</b></font></td>";
							if ($login_fabrica == 3 and 1 == 2) echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff'><b>Preço c/ IPI</b></font></td>";
						}
						echo "</tr>";
					}
				}

				echo "<tr bgcolor='$cor'>";

				echo "<td>";
				echo "<font face='arial' size='-2'>";
				echo $peca_referencia;
				echo "</font>";
				echo "</td>";

				echo "<td>";
				echo "<font face='arial' size='-2'>";
				echo $peca_descricao;
				echo "</font>";
				echo "</td>";

				echo "<td>";
				echo "<font face='arial' size='-2'>";
				echo $unidade;
				echo "</font>";
				echo "</td>";

				if ($liberar_preco) {
					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo number_format ($preco,2,",",".");
					echo "</font>";
					echo "</td>";

					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo $ipi;
					echo "</font>";
					echo "</td>";

					if ($login_fabrica == 3 and 1 == 2){
						echo "<td align='right'>";
						echo "<font face='arial' size='-2'>";
						echo number_format ($preco_com_ipi,2,",",".");
						echo "</font>";
						echo "</td>";
					}
				}

				echo "</tr>";
			}
			echo "</table>";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
