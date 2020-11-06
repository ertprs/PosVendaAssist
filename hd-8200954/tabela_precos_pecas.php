<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$liberar_preco = true ;

$title = "Tabela de Preços";

$layout_menu = 'preco';
include "cabecalho.php";

if($_POST['tabela']) $tabela = $_POST['tabela'];
if($_GET['tabela'])  $tabela = $_GET['tabela'];

if($_POST['referencia_produto']) $referencia_produto = $_POST['referencia_produto'];
if($_GET['referencia_produto'])  $referencia_produto = $_GET['referencia_produto'];

if($_POST['descricao_produto'])  $descricao_produto  = $_POST['descricao_produto'];
if($_GET['descricao_produto'])   $descricao_produto  = $_GET['descricao_produto'];

if($_POST['voltagem_produto'])   $voltagem_produto   = $_POST['voltagem_produto'];
if($_GET['voltagem_produto'])    $voltagem_produto   = $_GET['voltagem_produto'];

if($_POST['referencia_peca'])    $referencia_peca    = $_POST['referencia_peca'];
if($_GET['referencia_peca'])     $referencia_peca    = $_GET['referencia_peca'];

if($_POST['descricao_peca'])     $descricao_peca     = $_POST['descricao_peca'];
if($_GET['descricao_peca'])      $descricao_peca     = $_GET['descricao_peca'];

$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
				tbl_tipo_posto.acrescimo_tabela_base        ,
				tbl_tipo_posto.acrescimo_tabela_base_venda  ,
				tbl_condicao.acrescimo_financeiro           ,
				((100 - tbl_icms.indice) / 100) AS icms     ,
				tbl_posto_fabrica.pedido_em_garantia
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
									and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
		JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
									and tbl_condicao.condicao     = 50
		JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto.estado
		WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
		AND     tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";

$res = @pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$icms                        = pg_result($res, 0, icms);
	$descricao                   = pg_result($res, 0, descricao);
	$acrescimo_tabela_base       = pg_result($res, 0, acrescimo_tabela_base);
	$acrescimo_tabela_base_venda = pg_result($res, 0, acrescimo_tabela_base_venda);
	$acrescimo_financeiro        = pg_result($res, 0, acrescimo_financeiro);
	$pedido_em_garantia          = pg_result($res, 0, pedido_em_garantia);
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
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
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

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;Tabela</b></font>
	</td>

	<td align="left" width="300">
		<select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
<?
		$sql = "SELECT *
				FROM   tbl_tabela
				WHERE  tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.tabela  IN (54,108,109)
				ORDER BY tbl_tabela.ordem ASC";
		$res = pg_exec($con,$sql);
		
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

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;Código do produto</b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='referencia_produto' size='20' maxlength='30' value='<? echo $referencia_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"referencia", document.frm_tabela.voltagem_produto)'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;ou Descrição do produto</b></font>
	</td>
	
	<td align="left" width="300">
		<input type='text' name='descricao_produto' size='20' maxlength='50' value='<? echo $descricao_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"descricao", document.frm_tabela.voltagem_produto)'></a>
		<input type="hidden" name="voltagem_produto" value="<?echo $voltagem_produto?>">
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;Código da peça</b></font>
	</td>
	
	<td align="left" width="300">
		<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFERÊNCIA DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;ou Descrição da peça</b></font>
	</td>
	
	<td align="left" width="300">
		<input type='text' name='descricao_peca' size='20' maxlength='50' value='<? echo $descricao_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRIÇÃO DA PEÇA ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('Aguarde submissão') }" ALT="Listar tabela de preços" border='0' style='cursor: hand;'>
		<img src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" ALT="Listar tabela de preços" border='0' style='cursor: hand;'>
	</td>
</tr>
</table>

</form>

<p align="center"><a href="<? echo $PHP_SELF ?>?relatorio=1">Clique aqui</a> para ver relação de produtos.<p>

<p align='center'>Para fazer o download da tabela de preços em formato XLS ou TXT, <a href='tabela_precos_xls.php?tabela=<?echo $tabela?>'>clique aqui</a></p>

<?

if (strlen ($_GET['relatorio']) > 0) {
	if (strlen($tabela) == 0) $tab = "108";
	
	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);
	
	echo "<table align='center' border='0' width='65%'>";
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
		
		$sql = "SELECT  distinct
						tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_peca.origem                                                                              ,
						tbl_peca.linha_peca                                                                          ,
						tbl_peca.ipi                                                                                 ,
						tbl_peca.multiplo                                                                            ,
						tbl_depara.para                                                                              ,
						tbl_peca_fora_linha.peca_fora_linha                                                          , ";
		
		if ($tabela <> 54) {
			switch ( substr($descricao,0,3) ) {
				case "Dis" :
					$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco  ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Vip" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms)                                                                 AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Loc" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms) AS preco ";
				break;
				default :
					$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro             AS compra,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
			}
		}else{
			$sql .= "(tbl_tabela_item.preco / $icms) AS preco ";
		}
		$sql .= "FROM   tbl_peca
				JOIN    tbl_tabela_item        ON tbl_tabela_item.peca     = tbl_peca.peca
				LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
				LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
				WHERE   tbl_peca.fabrica       = $login_fabrica
				AND     tbl_tabela_item.tabela = $tabela
				AND     tbl_peca.ativo         = 't'
				AND     tbl_peca.descricao ILIKE '$letra%'
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
//if ($ip == "201.0.9.216") echo $sql;
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
			echo "<table width='700' align='center' cellspacing='3' border='0'>";
			echo "<tr bgcolor='#007711'>";
			echo "<td align='center' colspan='9'>";
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
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Peça</b></font></td>";
			echo "<td bgcolor='#007711' align='center' nowrap><font face='arial' color='#ffffff' size='2'><b>Descrição</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Origem</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Linha</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Status</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Mudou para</b></font></td>";
			
			if ($tabela == 54) {
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço</b></font></td>";
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<BR>múltipla</b></font></td>";
			}else{
				if ($liberar_preco) {
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>Sem IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						break;
						case "Vip" :
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						break;
						case "Loc" :
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						break;
						default :
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						break;
					}
				}
			}
			
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_result ($res,$i,unidade));
				$ipi                = trim(pg_result ($res,$i,ipi));
				$multiplo           = trim(pg_result ($res,$i,multiplo));
				$origem             = trim(pg_result ($res,$i,origem));
				$linha_peca         = trim(pg_result ($res,$i,linha_peca));
				$para               = trim(pg_result ($res,$i,para));
				$peca_fora_linha    = trim(pg_result ($res,$i,peca_fora_linha));
				
				if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
				if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
				if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";
				
				if ($linha_peca == 198)       $linha = "Ferramenta DEWALT";
				if ($linha_peca == 199)       $linha = "ELETRO";
				if ($linha_peca == 200)       $linha = "Ferramenta B&D";
				//retirado Wellington HD 1826
				//if (strlen($linha_peca) == 0) $linha = "COMPRESSOR";
				if (strlen($linha_peca) == 0) $linha = "";
				if ($multiplo < 2) $multiplo = '1';
				
				if ($tabela == 54) {
					$preco = pg_result($res, $i, preco);
				}else{
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
							$preco            = pg_result($res, $i, preco);
							$preco_distrib    = pg_result($res, $i, distrib);
							$preco_venda      = pg_result($res, $i, venda);
						break;
						case "Vip" :
							$preco            = pg_result($res, $i, preco);
							$preco_venda      = pg_result($res, $i, venda);
						break;
						case "Loc" :
							$preco            = pg_result($res, $i, preco);
						break;
						default :
							$preco            = pg_result($res, $i, preco);
							$preco_compra     = pg_result($res, $i, compra);
							$preco_venda      = pg_result($res, $i, venda);
						break;
					}
				}
				
				$cor = '#ffffff';
				if ($i % 2 == 0) $cor = '#f8f8f8';
				
				echo "<tr bgcolor='$cor'>";
				
				echo "<td>";
				echo "<font face='arial' size='-2'>";
				echo $peca_referencia;
				echo "</font>";
				echo "</td>";
				
				echo "<td align='left'>";
				echo "<font face='arial' size='-2'>";
				echo $peca_descricao;
				echo "</font>";
				echo "</td>";
				
				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				echo $origem;
				echo "</font>";
				echo "</td>";
				
				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				echo $linha;
				echo "</font>";
				echo "</td>";
				
				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				if ( strlen($para) > 0 ) {
					echo "SUBST";
				}
				if ( strlen($peca_fora_linha) > 0 ) {
					echo "OBSOLETO";
				}
				echo "</font>";
				echo "</td>";

				echo "<td align='left'>";
				echo "<font face='arial' size='-2'>";
				if ( strlen($para) > 0 ) {
					echo $para;
				}
				echo "</font>";
				echo "</td>";

				/*
				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				echo $unidade;
				echo "</font>";
				echo "</td>";
				*/

				if ($tabela == 54) {
					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo $ipi;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo number_format ($preco,2,",",".");
					echo "</font>";
					echo "</td>";
					
					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo $multiplo;
					echo "</font>";
					echo "</td>";

				}else{
					if ($liberar_preco) {
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo $ipi;
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco_distrib,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo $multiplo;
								echo "</font>";
								echo "</td>";
								
							break;
							case "Vip" :
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo $ipi;
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo $multiplo;
								echo "</font>";
								echo "</td>";
								
							break;
							case "Loc" :
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo $ipi;
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo $multiplo;
								echo "</font>";
								echo "</td>";
								
							break;
							default :
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco_compra,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='right'>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco_venda,2,",",".");
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo $multiplo;
								echo "</font>";
								echo "</td>";
								
							break;
						}
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
			echo "<td align='center' colspan='9'>";
			
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
		if ($tabela == 54) {
			$sql = "SELECT  distinct
							a.peca_referencia     ,
							a.peca_descricao      ,
							a.unidade             ,
							a.origem              ,
							a.linha_peca          ,
							a.ipi                 ,
							a.multiplo            ,
							tbl_tabela_item.preco ,
							to_char((tbl_tabela_item.preco / $icms),999999990.99)::float AS total,
							tbl_depara.para                                                       ,
							tbl_peca_fora_linha.peca_fora_linha                                   
					FROM (
							SELECT  tbl_peca.peca                         ,
									tbl_peca.referencia AS peca_referencia,
									tbl_peca.descricao  AS peca_descricao ,
									tbl_peca.unidade                      ,
									tbl_peca.origem                       ,
									tbl_peca.linha_peca                   ,
									tbl_peca.ipi                          ,
									tbl_peca.multiplo
							FROM  tbl_peca
							WHERE tbl_peca.fabrica = $login_fabrica
							AND   tbl_peca.ativo IS TRUE ";
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";

			if (strlen($referencia_peca) > 0) {
				$sql .= "AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
			}elseif (strlen($descricao_peca) > 0) {
				$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
			}

			$sql .= ") AS a
					JOIN tbl_tabela_item ON tbl_tabela_item.peca   = a.peca
										AND tbl_tabela_item.tabela = $tabela 
					LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = a.peca AND tbl_depara.fabrica = $login_fabrica
					LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = a.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica";

			// ORDENACAO
			$sql .= "ORDER BY   a.peca_descricao";
		}else{
			$sql = "SELECT  distinct
							tbl_peca.peca                         ,
							tbl_peca.referencia AS peca_referencia,
							tbl_peca.descricao  AS peca_descricao ,
							tbl_peca.unidade                      ,
							tbl_peca.origem                       ,
							tbl_peca.linha_peca                   ,
							tbl_peca.multiplo                     ,
							tbl_peca.ipi                          ,
							tbl_depara.para                       ,
							tbl_peca_fora_linha.peca_fora_linha   , ";
			
			switch ( substr($descricao,0,3) ) {
				case "Dis" :
					$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco  ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Vip" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms)                                                                 AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Loc" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms) AS preco ";
				break;
				default :
					$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro             AS compra,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
			}
			
			$sql .= "FROM tbl_peca
					JOIN tbl_tabela_item     ON tbl_tabela_item.peca     = tbl_peca.peca
											AND tbl_tabela_item.tabela   = $tabela
					LEFT JOIN  tbl_lista_basica   ON tbl_lista_basica.peca    = tbl_peca.peca
											AND tbl_lista_basica.fabrica = $login_fabrica
					LEFT JOIN  tbl_produto   ON tbl_produto.produto      = tbl_lista_basica.produto
					LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
					LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca=tbl_peca.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica
					WHERE tbl_peca.fabrica = $login_fabrica
					AND   tbl_peca.ativo    IS TRUE ";
			
			if (strlen($referencia_produto) > 0) {
				$sql .= "AND   tbl_produto.ativo IS TRUE ";
			}
			
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";
			
			if (strlen($referencia_peca) > 0) {
				$sql .= "AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
			}elseif (strlen($descricao_peca) > 0) {
				$sql .= " AND tbl_peca.descricao ilike '%$descricao_peca%' ";
			}
			
			if (strlen($referencia_produto) > 0) {
				$sql .= "AND upper(trim(tbl_produto.referencia)) = upper(trim('$referencia_produto')) ";
			}elseif (strlen($descricao_produto) > 0) {
				$sql .= " AND tbl_produto.descricao ilike '%$descricao_produto%' ";
			}
			
			if (strlen($voltagem_produto) > 0) {
				$sql .= "AND upper(trim(tbl_produto.voltagem)) = upper(trim('$voltagem_produto')) ";
			}
			
			// ORDENACAO
			
			$sql .= "ORDER BY   tbl_peca.descricao";
		}
		
//if ($ip == "201.43.10.71") echo $sql;
		$res = @pg_exec ($con,$sql);
		if (strlen($msg_erro) == 0){
			if (@pg_numrows($res) == 0) {
				echo "<center><font face='arial' size='-1'>Produto informado não encontrado</font></center>";
			}else{
			
			#---------- listagem -------------
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
					$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
					$unidade            = trim(pg_result ($res,$i,unidade));
					$ipi                = trim(pg_result ($res,$i,ipi));
					$multiplo           = trim(pg_result ($res,$i,multiplo));
					$origem             = trim(pg_result ($res,$i,origem));
					$linha_peca         = trim(pg_result ($res,$i,linha_peca));
					$para               = trim(pg_result ($res,$i,para));
					$peca_fora_linha    = trim(pg_result ($res,$i,peca_fora_linha));
					
					if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
					if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
					if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";
					
					if ($linha_peca == 198) $linha = "Ferramenta DEWALT";
					if ($linha_peca == 199) $linha = "ELETRO";
					if ($linha_peca == 200) $linha = "Ferramenta B&D";
					//retirado Wellington HD 1826
					//if (strlen($linha_peca) == 0) $linha = "COMPRESSOR";
					if (strlen($linha_peca) == 0) $linha = "";
					
					if ($multiplo < 2) $multiplo = '1';
					
					if ($tabela == 54) {
						$preco = pg_result($res, $i, total);
					}else{
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
								$preco            = pg_result($res, $i, preco);
								$preco_distrib    = pg_result($res, $i, distrib);
								$preco_venda      = pg_result($res, $i, venda);
							break;
							case "Vip" :
								$preco            = pg_result($res, $i, preco);
								$preco_venda      = pg_result($res, $i, venda);
							break;
							case "Loc" :
								$preco            = pg_result($res, $i, preco);
							break;
							default :
								$preco            = pg_result($res, $i, preco);
								$preco_compra     = pg_result($res, $i, compra);
								$preco_venda      = pg_result($res, $i, venda);
							break;
						}
					}
					
					$cor = '#ffffff';
					if ($i % 2 == 0) $cor = '#f8f8f8';
					
					if ($i == 0) {
						flush();
						echo "<table width='700' align='center' cellspacing='3' border='0'>";
						echo "<tr>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Peça</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Descrição</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Origem</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Linha</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Status</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Mudou para</b></font></td>";
						
						if ($tabela == 54) {
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
						}else{
							if ($liberar_preco) {
								switch ( substr($descricao,0,3) ) {
									case "Dis" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "Vip" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "Loc" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									default :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
								}
							}
						}
						
						echo "</tr>";
					}
					
					echo "<tr bgcolor='$cor'>";
					
					echo "<td>";
					echo "<font face='arial' size='-2'>";
					echo $peca_referencia;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $peca_descricao;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $origem;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $linha;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					if ( strlen($para) > 0 ) {
						echo "SUBST";
					}
					if ( strlen($peca_fora_linha) > 0 ) {
						echo "OBSOLETO";
					}
					echo "</font>";
					echo "</td>";
					
					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					if ( strlen($para) > 0 ) {
						echo $para;
					}
					echo "</font>";
					echo "</td>";
					
					/*
					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					echo $unidade;
					echo "</font>";
					echo "</td>";
					*/
					
					if ($tabela == 54) {
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

						echo "<td align='center'>";
						echo "<font face='arial' size='-2'>";
						echo $multiplo;
						echo "</font>";
						echo "</td>";

					}else{
						if ($liberar_preco) {
							switch ( substr($descricao,0,3) ) {
								case "Dis" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_distrib,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								case "Vip" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								case "Loc" :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo $ipi;
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
								default :
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_compra,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='right'>";
									echo "<font face='arial' size='-2'>";
									echo number_format ($preco_venda,2,",",".");
									echo "</font>";
									echo "</td>";
									
									echo "<td align='center'>";
									echo "<font face='arial' size='-2'>";
									echo $multiplo;
									echo "</font>";
									echo "</td>";
								break;
							}
						}
					}
					echo "</tr>";
				}
			}
			echo "</table>";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
