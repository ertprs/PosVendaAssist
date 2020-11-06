<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$liberar_preco = true ;

$title = "TABELA DE PRE�OS";

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

//HD 57324 18/12/2008
$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
				tbl_tipo_posto.acrescimo_tabela_base        ,
				tbl_tipo_posto.acrescimo_tabela_base_venda  ,
				tbl_tipo_posto.tx_administrativa            ,
				tbl_tipo_posto.desconto_5estrela            ,
				tbl_condicao.acrescimo_financeiro           ,
				case when tbl_tipo_posto.tipo_posto = 36 then ((100 - 18) / 100::float)
				else ((100 - tbl_icms.indice) / 100) end AS icms     ,
				tbl_posto_fabrica.pedido_em_garantia        ,
				tbl_tipo_posto.tipo_posto                   
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
									and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
		JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
									and tbl_condicao.condicao     = 50
		JOIN    tbl_icms             on tbl_icms.estado_destino   =  tbl_posto_fabrica.contato_estado
		WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
		AND     tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
	# HD 219253 ICMS para Locadoras
	$descricao                   = pg_fetch_result($res, 0, descricao);
	$acrescimo_tabela_base       = pg_fetch_result($res, 0, acrescimo_tabela_base);
	$acrescimo_tabela_base_venda = pg_fetch_result($res, 0, acrescimo_tabela_base_venda);
	$acrescimo_financeiro        = pg_fetch_result($res, 0, acrescimo_financeiro);
	$pedido_em_garantia          = pg_fetch_result($res, 0, pedido_em_garantia);
	$icms                        = pg_fetch_result($res, 0, icms);
	$desconto_5estrela           = pg_fetch_result($res, 0, desconto_5estrela);
	if(strlen($desconto_5estrela)==0 ){
		$desconto_5estrela = 1;
	}
}
?>

<?// include "javascript_pesquisas_341188_teste.php" #HD 341188?> 

<script language="JavaScript">

/* ============= Fun��o PESQUISA DE PRODUTOS ====================
Nome da Fun��o : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		refer�ncia (c�digo) ou descri��o (mesmo parcial).
=================================================================*/
function fnc_pesquisa_produtoXXX (referencia,descricao,tabela) {
	var url = "";
	if (referencia.value == 0){
		alert('Informe toda ou parte da informa��o para a pesquisa');
	}
	if (referencia.value != "" || descricao.value != "") {
		url = "pesquisa_tabela.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&retorno=<?echo $PHP_SELF?>";
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.referencia = referencia;
		janela.descricao  = descricao;
		janela.tabela     = tabela;
		janela.focus();
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	
	if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1"+ 
        "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>" ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia    = campo;
        janela.descricao    = campo2;
        janela.produto        = document.frm_lbm.produto;
        janela.focus();
    }
    else{
        alert("Preencha toda ou parte da informa��o para realizar a pesquisa!");
    }
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes,        directories=no, width=500, height=400, top=0, left=0");
        janela.retorno = "<? echo $PHP_SELF ?>";
        janela.referencia= campo;
        janela.descricao= campo2;
        janela.focus();
    }else{
        alert("Preencha toda ou parte da informa��o para realizar a pesquisa!");
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

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_coluna{
background-color: #596D9B;
color: white;
font: normal normal bold 11px normal Arial;
text-align: center;
}

</style>
<!-- AQUI COME�A O SUB MENU - �REA DE CABECALHO DOS RELAT�RIOS E DOS FORMUL�RIOS -->


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
</script>
<form method='get' action='<? echo $PHP_SELF ?>' name='frm_tabela'>

<table width="700" border="0" cellpadding="4" cellspacing="0" align="center" class="formulario">
<br>

<tr class="titulo_tabela">
	<td colspan="4">
		<? fecho("Par�metros da Pesquisa",$con,$cook_idioma); ?>
	</td>
</tr>
	
	<tr>
		<td width='10%'>&nbsp;
		</td>

		<td width="28%">
			<font face="arial" size='2'>Tabela</font>
		</td>

		<td width='52%'>
			&nbsp;
		</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>

	<tr>
		<td width='10%'>&nbsp;
		</td>
		
		<td width='28%'>
			<select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
				<?
						$sql = "SELECT *
								FROM   tbl_tabela
								WHERE  tbl_tabela.fabrica = $login_fabrica
								AND    tbl_tabela.tabela  IN (54,108,109)
								ORDER BY tbl_tabela.ordem ASC";
						$res = pg_query($con,$sql);
						
						for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
							$aux_tabela       = trim(pg_fetch_result($res,$i,tabela));
							$aux_sigla_tabela = trim(pg_fetch_result($res,$i,descricao));
							
							echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";
						}
				?>
			</select>
		</td>

		<td width='52%'>&nbsp;
		</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>

	<tr>
		<td width='10%'>&nbsp;
		</td>

		<td width='28%'>
			<font face="arial" size='2'>Cod. Produto</font>
		</td>

		<td width='52%'>
		<font face="arial" size='2'>Descri��o do produto</font>
		</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>

	<tr>
		<td width='10%'>&nbsp;
		</td>

		<td align="left" width='28%'>
			<input type='text' name='referencia_produto' size='20' maxlength='30' value='<? echo $referencia_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFER�NCIA DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
			&nbsp;
		<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"referencia")'></a>
		</td>
		
	<td align="left"width='52%'>
		<input type='text' name='descricao_produto' size='50' maxlength='50' value='<? echo $descricao_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRI��O DO PRODUTO ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
		&nbsp;
		<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.referencia_produto,document.frm_tabela.descricao_produto,"descricao")'></a>
		<input type="hidden" name="voltagem_produto" value="<?echo $voltagem_produto?>">
	</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>
	
	<tr>
		<td width='10%'>&nbsp;
		</td>

		<td width="28%">
			<font face="arial" size='2'>Cod. Pe�a</font>
		</td>
		
	<td align="left" width="52%">
		<font face="arial" size='2'>Descri��o da pe�a</font>
	</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>		
	
	<tr>
		<td width='10%'>&nbsp;
		</td>

		<td align="left" width='28%' >
			<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a REFER�NCIA DA PE�A ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
			&nbsp;
			<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")'></a>
		</td>
		
		<td align="left" width="52%">
				<input type='text' name='descricao_peca' size='50' maxlength='50' value='<? echo $descricao_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira a DESCRI��O DA PE�A ou parte dela, depois, clique na lupa a direita para realizar a busca.');">
				&nbsp;
				<a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
		</td>

		<td width='10%'>&nbsp;
		</td>
	</tr>
		

</table>

<!-- BOTOES INICIO -->
<table width="700" border="0" cellpadding="5" cellspacing="0" align="center" class="formulario">
<tr>

	<td height="27" width="50%" valign="middle" align="right">

		<input type="hidden" name="btn_acao" value="">

		<input type="button" value="Continuar" onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('Aguarde submiss�o') }" ALT="Listar tabela de pre�os" border='0'>

	</td>

	<td width="50%">

		<input type="button" value="Voltar" onclick="javascript: history.back(-1);" ALT="Listar tabela de pre�os" border='0' style='cursor: hand;'>
	
	</td>

</tr>
</table>
<!-- BOTOES FIM -->


</form>

<table align="center" class='formulario' border='0' width='700'>
<tr>
	<!--<td align="center"><a href="<? echo $PHP_SELF ?>?relatorio=1">Clique aqui</a> para ver rela��o de produtos.-->
	<td align="right" width="46%">
	<input type='button' value="Listar Produtos" style="cursor:pointer;font:12px Arial;" onclick="window.location='<? echo $PHP_SELF ?>?relatorio=1'">
	<td>
	<td align="left">
	<!--Para fazer o download da tabela de pre�os em formato XLS ou TXT, <a href='tabela_precos_xls.php?tabela=<?//echo $tabela?>'><b>Clique Aqui</b></a> -->
	<input type="button" value="Download em XLS/TXT" style="cursor:pointer;font:12px Arial;" onclick="window.location='tabela_precos_xls.php?tabela=<?echo $tabela?>'">
	</td>
<tr>
</table>
<?
//HD
if (strlen ($_GET['relatorio']) > 0) {
	if (strlen($tabela) == 0) $tab = "108";
	
	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_query ($con,$sql);
	
	echo "<br>";
	
	echo "<table align='center' width='700' cellspacing='1' class='tabela'>";

	echo "<tr>";
	
	echo "<td align='left' class='titulo_coluna'>Refer�ncia</td>";
	echo "<td align='left' class='titulo_coluna'>Descri��o</td>";
	
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		
		$refer = pg_fetch_result ($res,$i,referencia);
		$descr = pg_fetch_result ($res,$i,descricao);
		
		echo "<tr bgcolor='$cor'>";
		
		echo "<td>";
		if ($login_fabrica == 1) echo "<a href='$PHP_SELF?tabela=$tab&referencia_produto=$refer&descricao_produto=$descr&btn_acao=continuar'>";
		echo $refer;
		if ($login_fabrica == 1) echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo pg_fetch_result ($res,$i,descricao);
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
$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0) {
	$item_aparencia = pg_fetch_result($res,0,item_aparencia);
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




		//hd 20780
		/* a tabela de pre�os est� calculando os pre�os da seguinte forma:


		Se tabela for diferente de Acess�rio
					Se posto for Distribuidor
						   pre�o              = pre�o da tabela / ICMS do estado 
						  pre�o distribuidor = (pre�o da tabela / ICMS do estado) * (1 + (IPI da Pe�a / 100)) * "Acr�scimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acr�scimo Financeiro (cadastro de condi��o, condi��o 15 - � Vista - s/  juros)"
						   pre�o venda        = (pre�o da tabela / ICMS do estado) * (1 + (IPI da pe�a / 100)) * "Acr�scimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acr�scimo Financeiro (cadastro de condi��o, condi��o 15 - � Vista - s/  juros)" / 0.7

				 Se posto for Vip
						   pre�o       = (pre�o da tabela * "Acr�scimo sobre Tabela (cadastro de tipo de postos)" /  ICMS do estado)                                                                 AS preco
						   pre�o venda = (pre�o da tabela / ICMS do estado) * (1 + (IPI da pe�a / 100)) * "Acr�scimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acr�scimo Financeiro (cadastro de condi��o, condi��o 15 - � Vista - s/  juros)" / 0.7

				 Se posto for Locadora
						   pre�o = (pre�o da tabela * "Acr�scimo sobre Tabela (cadastro de tipo de postos)" / ICMS do estado)

				 Demais postos
						   pre�o        = (pre�o da tabela / ICMS do estado)
						   pre�o compra = (pre�o da tabela / ICMS do estado) * (1 + (IPI da pe�a / 100)) * "Acr�scimo sobre Tabela (cadastro de tipo de postos)" * "Acr�scimo Financeiro (cadastro de condi��o, condi��o 15 - � Vista - s/  juros)"
						   pre�o venda  = (pre�o da tabela / ICMS do estado) * (1 + (IPI da pe�a / 100)) * "Acr�scimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acr�scimo Financeiro (cadastro de condi��o, condi��o 15 - � Vista - s/  juros)" / 0.7



		Se tabela for de Acess�rio
				 pre�o = (pre�o da tabela / ICMS do estado)*/







		if ($tabela <> 54) {
			/*IGOR HD: 21333 - NOVA REGRA PARA TAB. BASE2 (108)*/
			if ($tabela == 108 AND (
				($descricao == "DistribSS5ESTRELAS") OR
				($descricao == "DistribMG5ESTRELAS") OR
				($descricao == "VipNNECO5ESTRELAS") OR
				($descricao == "VipSS5ESTRELAS") OR
				($descricao == "VipMG5ESTRELAS") OR
				($descricao == "DistribNNECO5ESTRELAS")				
			)) {
				if($descricao == "DistribSS5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela )                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							 (tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "DistribMG5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela )                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							 (tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				}elseif($descricao == "VipNNECO5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms*1.1* $desconto_5estrela )                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "VipSS5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * 1.1 * $desconto_5estrela )                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "VipMG5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * 1.1 * $desconto_5estrela)                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "DistribNNECO5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela )                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				}

			}else{

				switch ( substr($descricao,0,3) ) {
					case "Dis" :

						//hd 17399 - Pa Top Service tem Pre�o de compra diferenciado conforme chamado
						if ($login_posto == 5355) {
							/*HD: 126046*/
							//$sql .= "((tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)))     AS preco  ,";
							/*VOLTANDO 20/07/2009 - IGOR*/
							$sql .= "((tbl_tabela_item.preco / $icms) * 1.51 * 0.6)                                                                           AS preco  ,";
						} else {
							$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco  ,";
						}

						$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
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
			}
		}else{
			$sql .= "(tbl_tabela_item.preco / $icms) AS preco ";
		}

		$sql .= "FROM   tbl_peca
				JOIN    tbl_tabela_item        ON tbl_tabela_item.peca     = tbl_peca.peca
				LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca
				LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca
				WHERE   tbl_peca.fabrica       = $login_fabrica
				AND     tbl_tabela_item.tabela = $tabela
				AND     tbl_peca.ativo         = 't'
				AND     tbl_peca.descricao ILIKE '$letra%'
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
//if ($ip == "201.0.9.216") 

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";
		
		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";
		
		// definicoes de variaveis
		$max_links = 11;					// m�ximo de links � serem exibidos
		$max_res   = 100;					// m�ximo de resultados � serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();		// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o n�mero de pesquisas (detalhada ou n�o) por p�gina
		
		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
		
		// ##### PAGINACAO ##### //

		if (pg_num_rows($res) > 0) {
			#---------- listagem -------------
			echo "<br><table width='700' align='center' cellspacing='1' border='' class='tabela'>";
//			echo "<tr bgcolor='#007711'>";
			echo "<td align='center' colspan='9' class='titulo_coluna'>";
			echo "<b>Para facilitar a visualiza��o dos itens, separamos por iniciais.<br>Para consultar um item, clique na inicial correspondente.</b>";
			
			echo "<table width=700' align='center' cellspacing='1' border='0' class='formulario'>";
			
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
			
			echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Pe�a</td>";
				echo "<td align='center' nowrap>Descri��o</td>";
				echo "<td align='center'>Origem</td>";
				echo "<td align='center'>Linha</td>";
				echo "<td align='center'>Status</td>";
				echo "<td align='center'>Mudou para</td>";
				
				if ($tabela == 54) {
					echo "<td align='center'>Pre�o</td>";
					echo "<td align='center'>IPI</td>";
					echo "<td align='center'>Quantidade<BR>m�ltipla</td>";
				}else{
					if ($liberar_preco) {
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>Sem IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Distribui��o<br>com IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Pre�o<br>sugerido<br>com IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>m�ltipla</b></font></td>";
							break;
							case "Vip" :
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Pre�o<br>sugerido<br>com IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>m�ltipla</b></font></td>";
							break;
							case "Loc" :
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>m�ltipla</b></font></td>";
							break;
							default :
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>com IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Pre�o<br>sugerido<br>com IPI</b></font></td>";
								echo "<td align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>m�ltipla</b></font></td>";
							break;
						}
					}
				}
			
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$peca_referencia    = trim(pg_fetch_result ($res,$i,peca_referencia));
				$peca_descricao     = trim(pg_fetch_result ($res,$i,peca_descricao));
				$unidade            = trim(pg_fetch_result ($res,$i,unidade));
				$ipi                = trim(pg_fetch_result ($res,$i,ipi));
				$multiplo           = trim(pg_fetch_result ($res,$i,multiplo));
				$origem             = trim(pg_fetch_result ($res,$i,origem));
				$linha_peca         = trim(pg_fetch_result ($res,$i,linha_peca));
				$para               = trim(pg_fetch_result ($res,$i,para));
				$peca_fora_linha    = trim(pg_fetch_result ($res,$i,peca_fora_linha));
				
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
					$preco = pg_fetch_result($res, $i, preco);
				}else{
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
							$preco            = pg_fetch_result($res, $i, preco);
							$preco_distrib    = pg_fetch_result($res, $i, distrib);
							$preco_venda      = pg_fetch_result($res, $i, venda);
						break;
						case "Vip" :
							$preco            = pg_fetch_result($res, $i, preco);
							$preco_venda      = pg_fetch_result($res, $i, venda);
						break;
						case "Loc" :
							$preco            = pg_fetch_result($res, $i, preco);
						break;
						default :
							$preco            = pg_fetch_result($res, $i, preco);
							$preco_compra     = pg_fetch_result($res, $i, compra);
							$preco_venda      = pg_fetch_result($res, $i, venda);
						break;
					}
				}
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				
				echo "<tr bgcolor='$cor'>";
				
				echo "<td align='center'>";
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
					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					echo $ipi;
					echo "</font>";
					echo "</td>";
					
					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					echo number_format ($preco,2,",",".");
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
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $ipi;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<b>";
								echo "<font face='arial' size='-2'>";
								echo number_format ($preco,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco_distrib,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco_venda,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $multiplo;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
							break;
							case "Vip" :
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $ipi;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco_venda,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $multiplo;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
							break;
							case "Loc" :
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $ipi;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $multiplo;
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
							break;
							default :
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco_compra,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo number_format ($preco_venda,2,",",".");
								echo "</b>";
								echo "</font>";
								echo "</td>";
								
								echo "<td align='center'>";
								echo "<font face='arial' size='-2'>";
								echo "<b>";
								echo $multiplo;
								echo "</b>";
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
			// pega todos os links e define que 'Pr�xima' e 'Anterior' ser�o exibidos como texto plano
			$todos_links		= $mult_pag->Construir_Links("strings", "sim");
			// fun��o que limita a quantidade de links no rodape
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
				echo " (P�gina <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
				echo "</font>";
				echo "</div>";
			}
			// ##### PAGINACAO ##### //
		}else{
			// SE NAO ENCONTROU REGISTROS
			echo "<center><font face='arial' size='-1'>Pe�as da linha com iniciais <b>\"$letra\"</b> n�o encontradas</font></center><br>";
			echo "<table width='700' align='center' cellspacing='3' border='0'>";
			echo "<tr bgcolor='#007711'>";
			echo "<td align='center' colspan='9'>";
			
			echo "<table width='700' align='center' cellspacing='1' border='0' cellpadding='2'>";
			
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
							to_char((tbl_tabela_item.preco / $icms)::numeric,'999999990.99')::float AS total,
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
					LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = a.peca
					LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = a.peca ";

			// ORDENACAO
			$sql .= "ORDER BY   a.peca_descricao";
//			//echo $sql;
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
			

			/*IGOR HD: 21333 - NOVA REGRA PARA TAB. BASE2 (108)*/
			if ($tabela == 108 AND (
				($descricao == "DistribSS5ESTRELAS") OR
				($descricao == "DistribMG5ESTRELAS") OR
				($descricao == "VipNNECO5ESTRELAS") OR
				($descricao == "VipSS5ESTRELAS") OR
				($descricao == "VipMG5ESTRELAS") OR
				($descricao == "DistribNNECO5ESTRELAS")				
			)) {
				if($descricao == "DistribSS5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela )                    AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							 (tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "DistribMG5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela)                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				}elseif($descricao == "VipNNECO5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms*1.1*$desconto_5estrela)                   AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "VipSS5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * 1.1 * $desconto_5estrela)               AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "VipMG5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * 1.1 * $desconto_5estrela)               AS preco  ,";
					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

				}elseif($descricao == "DistribNNECO5ESTRELAS"){
					$sql .= "(tbl_tabela_item.preco /$icms * $desconto_5estrela)                     AS preco  ,";
					$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				}

			}else{
					switch ( substr($descricao,0,3) ) {
					case "Dis" :
						//hd 17399 - Pa Top Service tem Pre�o de compra diferenciado conforme chamado
						if ($login_posto == 5355 ) {
							/* HD: 126046*/
							//$sql .= "((tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)))     AS preco  ,";
							/*VOLTANDO 20/07/2009 - IGOR*/
							$sql .= "((tbl_tabela_item.preco / $icms) * 1.51 * 0.6)                                                                           AS preco  ,";
						} else {
							$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco  ,";
						}
						$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
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
			}
			
			$sql .= "FROM tbl_peca
					JOIN tbl_tabela_item     ON tbl_tabela_item.peca     = tbl_peca.peca
											AND tbl_tabela_item.tabela   = $tabela
					LEFT JOIN  tbl_lista_basica   ON tbl_lista_basica.peca    = tbl_peca.peca
											AND tbl_lista_basica.fabrica = $login_fabrica
					LEFT JOIN  tbl_produto   ON tbl_produto.produto      = tbl_lista_basica.produto
					LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca
					LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca
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
		//echo nl2br($sql);
		//if ($ip == "201.76.71.206") echo $sql;
		$res = @pg_query ($con,$sql);
		if (strlen($msg_erro) == 0){
			if (@pg_num_rows($res) == 0) {
				echo "<center><font face='arial' size='-1'>Produto informado n�o encontrado</font></center>";
			}else{
			
			#---------- listagem -------------
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
					$peca_referencia    = trim(pg_fetch_result ($res,$i,peca_referencia));
					$peca_descricao     = trim(pg_fetch_result ($res,$i,peca_descricao));
					$unidade            = trim(pg_fetch_result ($res,$i,unidade));
					$ipi                = trim(pg_fetch_result ($res,$i,ipi));
					$multiplo           = trim(pg_fetch_result ($res,$i,multiplo));
					$origem             = trim(pg_fetch_result ($res,$i,origem));
					$linha_peca         = trim(pg_fetch_result ($res,$i,linha_peca));
					$para               = trim(pg_fetch_result ($res,$i,para));
					$peca_fora_linha    = trim(pg_fetch_result ($res,$i,peca_fora_linha));
					
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
						$preco = pg_fetch_result($res, $i, total);
					}else{
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
								$preco            = pg_fetch_result($res, $i, preco);
								$preco_distrib    = pg_fetch_result($res, $i, distrib);
								$preco_venda      = pg_fetch_result($res, $i, venda);
							break;
							case "Vip" :
								$preco            = pg_fetch_result($res, $i, preco);
								$preco_venda      = pg_fetch_result($res, $i, venda);
							break;
							case "Loc" :
								$preco            = pg_fetch_result($res, $i, preco);
							break;
							default :
								$preco            = pg_fetch_result($res, $i, preco);
								$preco_compra     = pg_fetch_result($res, $i, compra);
								$preco_venda      = pg_fetch_result($res, $i, venda);
							break;
						}
					}
					
					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
					
					if ($i == 0) {
							flush();
						echo "<br>";

						echo "<table width='700' align='center' cellspacing='1' >";
						echo "<tr class='titulo_tabela'";
							echo "<td colspan='9'>";
								echo"Pe�as do Produto: ".$descricao_produto;
							echo "</td>"; 

						echo "</tr>";
						echo "<tr class='titulo_coluna'>";
						echo "<td align='center'>Pe�a</td>";
						echo "<td align='center'>Descri��o</td>";
						echo "<td  align='center'>Origem</td>";
						echo "<td align='center'>Linha</td>";
						echo "<td align='center'>Status</td>";
						echo "<td align='center'>Mudou para</td>";
						
						if ($tabela == 54) {
							echo "<td align='center'>Pre�o</td>";
							echo "<td align='center'>IPI</td>";
						}else{
							if ($liberar_preco) {
								switch ( substr($descricao,0,3) ) {
									case "Dis" :
										echo "<td align='center'>IPI</td>";
										echo "<td align='center'>Compra sem IPI</td>";
										echo "<td align='center'>Distribui��o<br>com IPI</td>";
										echo "<td align='center'>Pre�o<br>sugerido<br>com IPI</td>";
										echo "<td align='center'>Quantidade<br>m�ltipla</td>";
									break;
									case "Vip" :
										echo "<td  align='center'>IPI</td>";
										echo "<td  align='center'>Compra<br>sem IPI</td>";
										echo "<td align='center'>Pre�o<br>sugerido<br>com IPI</td>";
										echo "<td align='center'>Quantidade<br>m�ltipla</td>";
									break;
									case "Loc" :
										echo "<td align='center'>IPI</td>";
										echo "<td align='center'>Compra<br>sem IPI</td>";
										echo "<td align='center'>Quantidade<br>m�ltipla</td>";
									break;
									default :
										echo "<td align='center'>Compra<br>com IPI</td>";
										echo "<td align='center'>Pre�o<br>sugerido<br>com IPI</td>";
										echo "<td align='center'>Quantidade<br>m�ltipla</td>";
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
						//echo $sql."<br>";
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
