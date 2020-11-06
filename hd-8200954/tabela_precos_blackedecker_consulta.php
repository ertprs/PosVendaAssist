<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$liberar_preco = true ;
if($login_fabrica <> 1 ) {
	header("Location: 'tabela_precos.php'");
	exit;
}
$title = traduz('tabela.de.precos', $con);


$sqlP_adicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
$resP_adicionais = pg_query($con, $sqlP_adicionais);

if (pg_num_rows($resP_adicionais) > 0) {
    $parametrosAdicionais = json_decode(pg_fetch_result($resP_adicionais, 0, "parametros_adicionais"), true);
    extract($parametrosAdicionais);

    $tipo_contribuinte = utf8_decode($tipo_contribuinte);

    if(!empty($tipo_contribuinte) and $tipo_contribuinte <> 't'){
    	$tipo_contribuinte = 'f';
    }
}else{
	$tipo_contribuinte = ' ';
}

$layout_menu = 'preco';
include "cabecalho.php";

$sql_estado = "SELECT contato_estado, pedido_faturado from tbl_posto_fabrica where posto = $login_posto and fabrica = $login_fabrica";
$res_estado = pg_query($con,$sql_estado);

if (pg_num_rows($res_estado)>0) {
	$estado_posto = pg_result($res_estado,0,0);
	$pedido_faturado_manual = pg_result($res_estado,0,1);
}


$hoje = date('Y-m-d');
$data_corte = "2012-06-21";

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
if(strtotime($hoje) > strtotime($data_corte)){
	$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
					tbl_tipo_posto.acrescimo_tabela_base        ,
					tbl_tipo_posto.acrescimo_tabela_base_venda  ,
					tbl_tipo_posto.tx_administrativa            ,
					tbl_tipo_posto.desconto_5estrela            ,
					tbl_tipo_posto.descontos[1] AS desconto1    ,
					tbl_tipo_posto.descontos[2] AS desconto2    ,
					tbl_condicao.acrescimo_financeiro           ,
					case when tbl_tipo_posto.tipo_posto = 36 then ((100 - 18) / 100::float)
					else (100 - tbl_icms.indice)/100 end AS icms     ,
					tbl_icms.indice,
					tbl_peca_icms.indice as icms_peca,
					tbl_posto_fabrica.pedido_em_garantia        ,
					tbl_posto_fabrica.pedido_faturado           ,
					tbl_tipo_posto.tipo_posto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   =  tbl_posto_fabrica.contato_estado
			LEFT JOIN tbl_peca_icms      on tbl_peca_icms.estado_destino = tbl_posto_fabrica.contato_estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
}else{
	$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
				tmp_tipo_posto_black.acrescimo_tabela_base        ,
				tmp_tipo_posto_black.acrescimo_tabela_base_venda  ,
				tmp_tipo_posto_black.tx_administrativa            ,
				tmp_tipo_posto_black.desconto_5estrela            ,
				tbl_condicao.acrescimo_financeiro           ,
				case when tmp_tipo_posto_black.tipo_posto = 36 then ((100 - 18) / 100::float)
				else ((100 - tbl_icms.indice) / 100) end AS icms     ,
				tbl_icms.indice,
				tbl_posto_fabrica.pedido_em_garantia        ,
				tmp_tipo_posto_black.tipo_posto
		FROM    tbl_posto
		JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
									and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		JOIN    tmp_tipo_posto_black       on tmp_tipo_posto_black.tipo_posto = tbl_posto_fabrica.tipo_posto
		JOIN    tbl_tipo_posto  on tmp_tipo_posto_black.tipo_posto = tbl_tipo_posto.tipo_posto
		JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
									and tbl_condicao.condicao     = 50
		JOIN    tbl_icms             on tbl_icms.estado_destino   =  tbl_posto_fabrica.contato_estado
		WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
		AND     tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
}

$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
	# HD 219253 ICMS para Locadoras
	$descricao                   = pg_fetch_result($res, 0, descricao);
	$acrescimo_tabela_base       = pg_fetch_result($res, 0, acrescimo_tabela_base);
	$acrescimo_tabela_base_venda = pg_fetch_result($res, 0, acrescimo_tabela_base_venda);
	$acrescimo_financeiro        = pg_fetch_result($res, 0, acrescimo_financeiro);
	$pedido_em_garantia          = pg_fetch_result($res, 0, pedido_em_garantia);
	$icms                        = pg_fetch_result($res, 0, icms);
	$indice_icms                 = pg_fetch_result($res, 0, indice);
	$icms_peca                   = pg_fetch_result($res, 0, icms_peca);
	$desconto_5estrela           = pg_fetch_result($res, 0, desconto_5estrela);
	$desconto1           		 = pg_fetch_result($res, 0, desconto1);
	$desconto2           		 = pg_fetch_result($res, 0, desconto2);

	if(strlen($desconto_5estrela)==0 ){
		$desconto_5estrela = 1;
	}

	if(strlen($desconto1)==0 ){
		$desconto1 = 1;
	}

	if(strlen($desconto2)==0 ){
		$desconto2 = 1;
	}

	if(strtotime($hoje) > strtotime($data_corte)){
		$pedido_faturado = pg_fetch_result($res, 0, pedido_faturado);
	}
}



if(!empty($referencia_peca)){
	$sql = " SELECT indice
		FROM tbl_peca
		JOIN tbl_posto_fabrica USING(fabrica)
		JOIN tbl_peca_icms USING(peca)
		WHERE tbl_peca.fabrica = $login_fabrica
		AND   tbl_peca_icms.estado_destino = tbl_posto_fabrica.contato_estado
		AND   tbl_posto_fabrica.posto = $login_posto
		AND tbl_peca.referencia ilike '%$referencia_peca%'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0) {
		$icms_peca = pg_fetch_result($res,0,'indice');
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
.lista>b {
	text-transform: uppercase;
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
</script>
<form method='get' action='<? echo $PHP_SELF ?>' name='frm_tabela'>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;<?=traduz('tabela.de.precos', $con)?></b></font>
	</td>

	<td align="left" width="300">
		<select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
<?
		if($pedido_faturado_manual == 't'){
			$add_tabela = "54,";
		}
		$sql = "SELECT *
				FROM   tbl_tabela
				WHERE  tbl_tabela.fabrica = $login_fabrica
				AND    tbl_tabela.tabela  IN ($add_tabela 108,1053,1054)
				AND ativa
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
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align="left" width="200">
		<font face="arial" size='2'><b>&nbsp;&nbsp;<?=traduz('codigo.do.produto', $con)?></b></font>
	</td>

	<td align="left" width="300">
		<input type='text' name='referencia_produto' size='20' maxlength='30' value='<? echo $referencia_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?=traduz('informe.parte.da.descricao.ou.da.referencia.e.clique.na.lupa.ao.lado.do.campo.para.pesquisar', $con)?>');">
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
		<input type='text' name='descricao_produto' size='20' maxlength='50' value='<? echo $descricao_produto ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?=traduz('informe.parte.da.descricao.ou.da.referencia.e.clique.na.lupa.ao.lado.do.campo.para.pesquisar', $con)?>');">
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
		<input type='text' name='referencia_peca' size='20' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?=traduz('informe.parte.da.descricao.ou.da.referencia.e.clique.na.lupa.ao.lado.do.campo.para.pesquisar', $con)?>');">
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
		<input type='text' name='descricao_peca' size='20' maxlength='50' value='<? echo $descricao_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?=traduz('informe.parte.da.descricao.ou.da.referencia.e.clique.na.lupa.ao.lado.do.campo.para.pesquisar', $con)?>');">
		&nbsp;
		<a href="#"><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
	</td>
</tr>
</table>

<table width="500" border="0" cellpadding="2" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() } else { alert ('<?=traduz('aguarde.submissao', $con)?>') }" ALT="<?=traduz('listar.tabela.de.precos', $con)?>" border='0' style='cursor:pointer;'>
		<img src='imagens/btn_voltar.gif' onclick="javascript: history.back(-1);" ALT="<?=traduz('voltar', $con)?>" border='0' style='cursor:pointer;'>
	</td>
</tr>
</table>

</form>

<p align="center"><a href="<? echo $PHP_SELF ?>?relatorio=1"><?=traduz('clique.aqui', $con)?></a> <?=traduz('para.ver.a.relacao.de.produtos', $con)?><p>

<p align='center'><?=traduz('para.fazer.o.download.da.tabela.de.precos.em.formato.%', $con, $cook_idioma, 'XLS/TXT')?> <a href='tabela_precos_xls.php?tabela=<?echo empty($tabela) ? '1053' : $tabela;?>'><?=traduz('clique.aqui', $con)?></a></p>

<?

if (strlen ($_GET['relatorio']) > 0) {
	if (strlen($tabela) == 0) $tab = "1053";

	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			WHERE  tbl_produto.fabrica_i = $login_fabrica
			AND    tbl_produto.ativo = 't'
			ORDER BY tbl_produto.descricao";
	$res = pg_query ($con,$sql);

	echo "<table align='center' border='0' width='65%'>";
	echo "<tr bgcolor='$cor'>";

	echo "<td class='lista'><b>" . traduz('referencia', $con) . "</b></td>";
	echo "<td class='lista'><b>" . traduz('descricao', $con) . "</b></td>";

	echo "</tr>";

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#f8f8f8';

		$refer = pg_fetch_result ($res,$i,referencia);
		$descr = pg_fetch_result ($res,$i,descricao);

		echo "<tr bgcolor='$cor'>";

		echo "<td class='lista'>";
		if ($login_fabrica == 1) echo "<a href='$PHP_SELF?tabela=$tab&referencia_produto=$refer&descricao_produto=$descr&btn_acao=continuar'>";
		echo $refer;
		if ($login_fabrica == 1) echo "</a>";
		echo "</td>";

		echo "<td class='lista'>";
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
						tbl_peca.referencia                                                     AS peca_referencia,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_peca.origem                                                                              ,
						tbl_peca.classificacao_fiscal                                                             ,
						tbl_peca.linha_peca                                                                          ,
						tbl_peca.ncm                                                                                 ,
						tbl_peca.ipi                                                                                 ,
						tbl_peca.multiplo                                                                            ,
						tbl_depara.para                                                                              ,
						tbl_peca_fora_linha.peca_fora_linha                                                          ,
						tbl_peca.parametros_adicionais								     ,";




		//hd 20839
		/* a tabela de preços está calculando os preços da seguinte forma:


		Se tabela for diferente de Acessório
					Se posto for Distribuidor
						   preço              = preço da tabela / ICMS do estado
						  preço distribuidor = (preço da tabela / ICMS do estado) * (1 + (IPI da Peça / 100)) * "Acréscimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acréscimo Financeiro (cadastro de condição, condição 15 - À Vista - s/  juros)"
						   preço venda        = (preço da tabela / ICMS do estado) * (1 + (IPI da peça / 100)) * "Acréscimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acréscimo Financeiro (cadastro de condição, condição 15 - À Vista - s/  juros)" / 0.7

				 Se posto for Vip
						   preço       = (preço da tabela * "Acréscimo sobre Tabela (cadastro de tipo de postos)" /  ICMS do estado)                                                                 AS preco
						   preço venda = (preço da tabela / ICMS do estado) * (1 + (IPI da peça / 100)) * "Acréscimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acréscimo Financeiro (cadastro de condição, condição 15 - À Vista - s/  juros)" / 0.7

				 Se posto for Locadora
						   preço = (preço da tabela * "Acréscimo sobre Tabela (cadastro de tipo de postos)" / ICMS do estado)

				 Demais postos
						   preço        = (preço da tabela / ICMS do estado)
						   preço compra = (preço da tabela / ICMS do estado) * (1 + (IPI da peça / 100)) * "Acréscimo sobre Tabela (cadastro de tipo de postos)" * "Acréscimo Financeiro (cadastro de condição, condição 15 - À Vista - s/  juros)"
						   preço venda  = (preço da tabela / ICMS do estado) * (1 + (IPI da peça / 100)) * "Acréscimo sobre Tabela Venda (cadastro de tipo de postos)" * "Acréscimo Financeiro (cadastro de condição, condição 15 - À Vista - s/  juros)" / 0.7



		Se tabela for de Acessório
				 preço = (preço da tabela / ICMS do estado)*/



        // $sql_venda = "
        //     CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
        //       (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100)))
        //     ELSE
        //       (tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7
        //     END AS venda ";

		if($tabela == 54) {
			$sql .= "tbl_tabela_item_erp.preco AS preco ";
		}else{
			//Coreção do calculo quando pesquisado todos os produtos


			// switch ( substr($descricao,0,3) ) {
			// 	case "Dis" :
			// 	case "DIS" :
			// 		//hd 17399 - Pa Top Service tem Preço de compra diferenciado conforme chamado
			// 		if ($login_posto == 5355) {
			// 			/*HD: 126046*/
			// 			//$sql .= "((tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)))     AS preco  ,";
			// 			/*VOLTANDO 20/07/2009 - IGOR*/
   //                      $sql .= "
   //                          CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
   //                            (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100)))
   //                          ELSE
   //                            ((tbl_tabela_item.preco / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100)) * 1.51 * 0.6)
   //                          END AS preco ,";
			// 		} else {
   //                      $sql .= "
   //                          CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
   //                            (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100)))
   //                          ELSE
   //                            (tbl_tabela_item.preco / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100) * $desconto_5estrela)
   //                          END AS preco ,";
			// 		}

   //                  $sql.= "CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
   //                        (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)
   //                      ELSE
   //                        (tbl_tabela_item.preco / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $icms END)/100) ) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro
   //                      END AS compra, ";

			// 		$sql .= "(tbl_tabela_item.preco / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100)) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,";

			// 	break;
			// 	case "Vip" :
			// 		$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100) * $desconto_5estrela)  AS preco,";
			// 	break;
			// 	case "Loc" :
			// 		$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100) * $desconto_5estrela) AS preco, ";

			// 	break;
			// 	default :
   //                  $sql .= "
   //                      CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
   //                        (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100)))
   //                      ELSE
   //                        (tbl_tabela_item.preco /((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $indice_icms END)/100)  * $desconto_5estrela)
   //                      END AS preco,

   //                      CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
   //                        (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)
   //                      ELSE
   //                        (tbl_tabela_item.preco / ((1-CASE WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice ELSE $icms END)/100) ) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro
   //                      END AS compra, ";
			// 	break;
			// }



			$sqlO = "SELECT origem FROM tbl_peca WHERE upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) AND fabrica = $login_fabrica";
			$resO = pg_query($con,$sqlO);
			$origemO = pg_fetch_result($resO, 0, 'origem');

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){
					$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100) * (1 + (tbl_peca.ipi / 100))) AS preco, ";
				}else{
					$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) AS preco, ";
				}
			}else if(substr($descricao,0,3) == "TMI"){

			$sql .= "tbl_tabela_item.preco / (1 - (1.65 + 7.6 + $indice_icms) /100 )/ 0.9/ 0.7/ 0.7 * $desconto_5estrela * $desconto1 * $desconto2 AS preco, ";
			}else if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){

				$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * (1 + (tbl_peca.ipi / 100))) AS preco, ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql .= " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100))) AS preco, ";
            }
            else{
                $sql .= "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                      (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100)))
                    ELSE
                    	(tbl_tabela_item.preco/(1-(9.25 + CASE
				WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
	                      	WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
	                      	ELSE tbl_peca_icms.indice END)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2
						)
                    END AS preco, ";
			}
			//CASE WHEN $tipo_contribuinte = 'f' AND $estado_posto = 'RJ' THEN 20 CASE WHEN $tipo_contribuinte = 'f' AND $estado_posto <> 'RJ' THEN 18 hd_chamado=2693784
			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) AS compra, ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql .= " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) AS compra, ";
			}else{
                $sql .= "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                    	(tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)
                    ELSE
                    	(tbl_tabela_item.preco/(1-(9.25 + CASE
				WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
                    		WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                    		WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
                    		ELSE $indice_icms END) /100)/0.9/0.7/0.7 * $desconto_5estrela *  $desconto1 * $desconto2 * $acrescimo_tabela_base * $acrescimo_financeiro
						)
                   END AS compra, ";
			}

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				$sql_venda = "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100))) AS venda ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql_venda = " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100))) AS venda  ";
			}else{
                $sql_venda = "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                      (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100)))
                    ELSE
                      (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)/0.9/0.7/0.7 * $acrescimo_tabela_base_venda * $acrescimo_financeiro * (1 + (tbl_peca.ipi/100)))
                    END AS venda
                    ";
			}


			if( substr($descricao,0,3) == "DIS" OR substr($descricao,0,3) == "TOP") {
				if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
					$sql .= "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100)))       AS distrib,";
				}else{

					$sqlPreco = "SELECT desconto_5estrela,
										descontos[1] AS desconto1,
										descontos[2] AS desconto2
								FROM tbl_tipo_posto
								WHERE fabrica = $login_fabrica
								AND descricao = '".substr($descricao,0,3)."'";
					$resPreco = pg_query($con,$sqlPreco);
					$desconto_5estrela_aux = pg_fetch_result($resPreco, 0, 'desconto_5estrela');
					$desconto1_aux = pg_fetch_result($resPreco, 0, 'desconto1');
					$desconto2_aux = pg_fetch_result($resPreco, 0, 'desconto2');

					$desconto_5estrela_aux = ($desconto_5estrela_aux == "") ? 1 : $desconto_5estrela_aux;
					$desconto1_aux = ($desconto1_aux == "") ? 1 : $desconto1_aux;
					$desconto2_aux = ($desconto2_aux == "") ? 1 : $desconto2_aux;


					$sql .= "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100)/0.9/0.7/0.7 * 0.7 * (1 + (tbl_peca.ipi/100)))       AS distrib,";

				}
			}

		}
		$sql .= $sql_venda;

        $join_tabela = "JOIN    tbl_tabela_item        ON tbl_tabela_item.peca     = tbl_peca.peca
                        LEFT JOIN tbl_peca_icms        ON tbl_peca_icms.peca           = tbl_peca.peca AND tbl_peca_icms.fabrica = $login_fabrica
                                                      AND tbl_peca_icms.codigo         = tbl_peca.classificacao_fiscal
                                                      AND tbl_peca_icms.estado_destino = '$estado_posto'";
        $tabela_tabela = "tbl_tabela_item";

        if ($tabela == 54) {
            $join_tabela = "JOIN tbl_tabela_item_erp ON tbl_tabela_item_erp.peca = tbl_peca.peca AND tbl_tabela_item_erp.estado = '$estado_posto'";
            $tabela_tabela = "tbl_tabela_item_erp";
        }
        
		$sql .= "
				FROM   tbl_peca
				$join_tabela
				LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
				LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
				WHERE   tbl_peca.fabrica       = $login_fabrica
				AND     {$tabela_tabela}.tabela = $tabela
				AND     tbl_peca.ativo         = 't'
				AND     tbl_peca.descricao ILIKE '$letra%'
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
//if ($ip == "201.0.9.216")
		//echo nl2br($sql); exit;
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
		//echo $sql;
		// ##### PAGINACAO ##### //

		if (pg_num_rows($res) > 0) {
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

			for($j = 0; $j < $totalLetras; $j++) {
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
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Codigo Origem</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Linha</b></font></td>";
			echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>NCM</b></font></td>";

            if ($tabela <> 54) {
                echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Status</b></font></td>";
                echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Mudou para</b></font></td>";
            }

			if ($tabela == 54) {
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço</b></font></td>";
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
				echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<BR>múltipla</b></font></td>";
			}else{
				if ($liberar_preco) {
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
						case "DIS" :
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>Sem IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						break;
						case "TOP" :
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

                    if ($tabela <> 54) {
			    echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Caixa<br>Unitário</b></font></td>";
			    echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Caixa<br>Coletiva</b></font></td>";
                        echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Estoque</b></font></td>";
                        echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Previsão</b></font></td>";
                    }
            }
			echo "</tr>";

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				$peca_referencia   = trim(pg_fetch_result($res, $i, 'peca_referencia'));
				$peca_descricao    = trim(pg_fetch_result($res, $i, 'peca_descricao'));
				$unidade           = trim(pg_fetch_result($res, $i, 'unidade'));
				$ipi               = trim(pg_fetch_result($res, $i, 'ipi'));
				$multiplo          = trim(pg_fetch_result($res, $i, 'multiplo'));
				$origem            = trim(pg_fetch_result($res, $i, 'origem'));
				$codigo_origem     = trim(pg_fetch_result($res, $i, 'classificacao_fiscal'));
				$linha_peca        = trim(pg_fetch_result($res, $i, 'linha_peca'));
				$ncm               = trim(pg_fetch_result($res, $i, 'ncm'));
				$para              = trim(pg_fetch_result($res, $i, 'para'));
				$peca_fora_linha   = trim(pg_fetch_result($res, $i, 'peca_fora_linha'));
				$parametros_adicionais  = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);
				$unitario = $parametros_adicionais["caixa_unitario"];
				$coletiva = $parametros_adicionais["caixa_coletiva"];

				$estoque = $parametros_adicionais["estoque"];
				$previsao = $parametros_adicionais["previsao"];
				$estoque = strtoupper($estoque);

				if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
				if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
				if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";
				if (strtoupper($origem) == 'FAB/SUB') $origem = "Fabricação/Subsidiado";
				if (strtoupper($origem) == 'IMP/SUB') $origem = "Importado/Subsidiado";
				if (strtoupper($origem) == 'TER/SUB') $origem = "Terceirizado/Subsidiado";

                if ($origem == 'FAB/SA') {
                    $origem = 'Fabricação/Semi acabado';
                }
                elseif ($origem == 'IMP/SA') {
                    $origem = 'Importado/Semi acabado';
                }

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
						case "DIS" :
						case "TOP" :
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

                    if (strpos($origem, 'Semi acabado') !== false) {
                        $tmp_preco = $preco;

                        if (empty($preco_compra)) {
                            $preco_compra = pg_fetch_result($res, $i, 'compra');
                        }

                        $tmp_compra = $preco_compra;

                        if (!empty($preco_distrib)) {
                            $preco_distrib = $tmp_preco;
                        }

                        $preco = $tmp_compra;
                        $preco_compra = $tmp_compra;

                        unset($tmp_compra);
                        unset($tmp_preco);
                    }
				}

				$cor = '#ffffff';
				if ($i % 2 == 0) $cor = '#f8f8f8';

				echo "<tr bgcolor='$cor'>";

				echo "<td>";
				echo "<font face='arial' size='-2'> ";
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
				echo $codigo_origem;
				echo "</font>";
				echo "</td>";

				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				echo $linha;
				echo "</font>";
				echo "</td>";

				echo "<td align='center'>";
				echo "<font face='arial' size='-2'>";
				echo $ncm;
				echo "</font>";
				echo "</td>";

                if ($tabela <> 54) {
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
                }
				
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

					echo "<td align='right'>";
					echo "<font face='arial' size='-2'>";
					echo $multiplo;
					echo "</font>";
					echo "</td>";

				}else{
					if ($liberar_preco) {
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
							case "DIS" :
							case "TOP" :
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

                            if ($tabela <> 54) {

				    echo "<td align='center'>";
				    echo "<font face='arial' size='-2'>";
				    echo $unitario;
				    echo "</font>";
				    echo "</td>";

				    echo "<td align='center'>";
				    echo "<font face='arial' size='-2'>";
				    echo $coletiva;
				    echo "</font>";
				    echo "</td>";

                                echo "<td align='center'>";
                                echo "<font face='arial' size='-2'>";
                                echo $estoque;
                                echo "</font>";
                                echo "</td>";

                                echo "<td align='center'>";
                                echo "<font face='arial' size='-2'>";
                                echo $previsao;
                                echo "</font>";
                                echo "</td>";

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


			//$preco = (strtoupper($estado_posto) == "MG") ? " tbl_tabela_item.preco_avista " : " tbl_tabela_item.preco ";

			$sql = "SELECT  distinct
							a.peca_referencia     ,
							a.peca_descricao      ,
							a.marca      		  ,
							a.unidade             ,
							a.origem              ,
							a.linha_peca          ,
							a.classificacao_fiscal,
							a.ncm                 ,
							a.ipi                 ,
							a.multiplo            ,
							tbl_tabela_item_erp.preco ,
							to_char((tbl_tabela_item_erp.preco)::numeric,'999999990.99')::float AS total,
							tbl_depara.para                                                       ,
							tbl_peca_fora_linha.peca_fora_linha
					FROM (
							SELECT  tbl_peca.peca                         ,
									tbl_peca.referencia AS peca_referencia,
									tbl_peca.descricao  AS peca_descricao ,
									tbl_peca.unidade                      ,
									tbl_peca.marca                        ,
									tbl_peca.origem                       ,
									tbl_peca.linha_peca                   ,
									tbl_peca.classificacao_fiscal         ,
									tbl_peca.ncm                          ,
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
                    JOIN tbl_tabela_item_erp ON tbl_tabela_item_erp.peca = a.peca
                      AND tbl_tabela_item_erp.estado = '{$estado_posto}'
                      AND tbl_tabela_item_erp.tabela = $tabela
					LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = a.peca AND tbl_depara.fabrica=$login_fabrica
					LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = a.peca AND tbl_peca_fora_linha.fabrica=$login_fabrica";

			// ORDENACAO
			$sql .= "ORDER BY   a.peca_descricao";
			//echo nl2br($sql);exit;
		}else{
			$sql = "SELECT  distinct
							tbl_tabela_item.preco AS preco_base,
							tbl_peca.peca                         ,
							tbl_peca.referencia AS peca_referencia,
							tbl_peca.descricao  AS peca_descricao ,
							tbl_peca.unidade                      ,
							tbl_peca.origem                       ,
							tbl_peca.classificacao_fiscal         ,
							tbl_peca.linha_peca                   ,
							tbl_peca.ncm                          ,
							tbl_peca.multiplo                     ,
                            tbl_peca.ipi                          ,
							tbl_peca.parametros_adicionais        ,
							tbl_depara.para                       ,
							tbl_peca_fora_linha.peca_fora_linha   , ";


		if($hoje < $data_corte){
			/*IGOR HD: 21333 - NOVA REGRA PARA TAB. BASE2 (108)*/
			$sql_venda = "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

			echo "descricao ". substr($descricao,0,3);


			switch ( substr($descricao,0,3) ) {
				case "Dis" :
				case "DIS" :

					//hd 17399 - Pa Top Service tem Preço de compra diferenciado conforme chamado
					if ($login_posto == 5355) {
						/*HD: 126046*/
						//$sql .= "((tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)))     AS preco  ,";
						/*VOLTANDO 20/07/2009 - IGOR*/
						$sql .= "((tbl_tabela_item.preco / $icms) * 1.51 * 0.6) AS preco ,";
					} else {
							$sql .= "(tbl_tabela_item.preco / $icms * $desconto_5estrela) AS preco ,";
					}

					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,";

				break;
				case "Vip" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms * $desconto_5estrela)  AS preco,";
				break;
				case "Loc" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms * $desconto_5estrela) AS preco, ";

				break;
				default :
					$sql .= "(tbl_tabela_item.preco / $icms * $desconto_5estrela) AS preco ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro             AS compra, ";
				break;
			}
		} else {


			$sqlO = "SELECT origem FROM tbl_peca WHERE upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) AND fabrica = $login_fabrica";
			$resO = pg_query($con,$sqlO);
			$origemO = pg_fetch_result($resO, 0, 'origem');

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){
					$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100) * (1 + (tbl_peca.ipi / 100))) AS preco, ";
				}else{
					$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) AS preco, ";
				}
			}else if(substr($descricao,0,3) == "TMI" ){
			$sql .= "tbl_tabela_item.preco / (1 - (1.65 + 7.6 + $indice_icms) /100 )/ 0.9/ 0.7/ 0.7 * $desconto_5estrela * $desconto1 * $desconto2 AS preco, ";

			}else if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){

				$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * (1 + (tbl_peca.ipi / 100))) AS preco, ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql .= " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100))) AS preco, ";
            }
            else{
                $sql .= "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                    	(tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6 * (1 + (tbl_peca.ipi/100)))
                    ELSE
                    	(tbl_tabela_item.preco/(1-(9.25 + CASE
							WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
							WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
							WHEN tbl_peca_icms.indice is not null then tbl_peca_icms.indice
                    		ELSE 0 END)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2
						)
                    END AS preco, ";
			}

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				$sql .= " (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) AS compra, ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql .= " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) AS compra, ";
			}else{
                $sql .= "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                    	(tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)
                    ELSE
                    	(tbl_tabela_item.preco/(1-(9.25 + CASE
				WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
                    		WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
                    		WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
                    		ELSE $indice_icms END) /100)/0.9/0.7/0.7 * $desconto_5estrela *  $desconto1 * $desconto2 * $acrescimo_tabela_base * $acrescimo_financeiro
						)
                   END AS compra, ";
			}

			if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
				$sql_venda = "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100))) AS venda ";
            } elseif (in_array($origemO, array('FAB/SA', 'IMP/SA'))) {
                $sql_venda = " (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100))) AS venda  ";
			}else{
                $sql_venda = "
                    CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                      (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100)))
                    ELSE
                      (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)/0.9/0.7/0.7 * $acrescimo_tabela_base_venda * $acrescimo_financeiro * (1 + (tbl_peca.ipi/100)))
                    END AS venda
                    ";
			}


			if( substr($descricao,0,3) == "DIS" OR substr($descricao,0,3) == "TOP") {
				if(in_array($origemO,array('FAB/SUB','IMP/SUB','TER/SUB'))){
					$sql .= "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100)))       AS distrib,";
				}else{

					$sqlPreco = "SELECT desconto_5estrela,
										descontos[1] AS desconto1,
										descontos[2] AS desconto2
								FROM tbl_tipo_posto
								WHERE fabrica = $login_fabrica
								AND descricao = '".substr($descricao,0,3)."'";
					$resPreco = pg_query($con,$sqlPreco);
					$desconto_5estrela_aux = pg_fetch_result($resPreco, 0, 'desconto_5estrela');
					$desconto1_aux = pg_fetch_result($resPreco, 0, 'desconto1');
					$desconto2_aux = pg_fetch_result($resPreco, 0, 'desconto2');

					$desconto_5estrela_aux = ($desconto_5estrela_aux == "") ? 1 : $desconto_5estrela_aux;
					$desconto1_aux = ($desconto1_aux == "") ? 1 : $desconto1_aux;
					$desconto2_aux = ($desconto2_aux == "") ? 1 : $desconto2_aux;


					$sql .= "(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100)/0.9/0.7/0.7 * 0.7 * (1 + (tbl_peca.ipi/100)))       AS distrib,";

				}
			}
		}

		$sql .= $sql_venda;

			$sql .= "FROM tbl_peca
					JOIN tbl_tabela_item          ON tbl_tabela_item.peca     = tbl_peca.peca AND tbl_tabela_item.tabela = $tabela
					LEFT JOIN  tbl_lista_basica   ON tbl_lista_basica.peca    = tbl_peca.peca AND tbl_lista_basica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto         ON tbl_produto.produto      = tbl_lista_basica.produto AND tbl_produto.fabrica_i=$login_fabrica
					LEFT JOIN tbl_depara          ON tbl_depara.peca_de       = tbl_peca.peca AND tbl_depara.fabrica = $login_fabrica
					LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = $login_fabrica
					LEFT JOIN tbl_peca_icms       ON tbl_peca_icms.peca       = tbl_peca.peca AND tbl_peca_icms.fabrica = $login_fabrica
					                             AND tbl_peca_icms.codigo     = tbl_peca.classificacao_fiscal
								AND tbl_peca_icms.estado_destino = '$estado_posto'
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
		//if ($ip == "201.76.71.206")

		$res = @pg_query ($con,$sql);
		if (strlen($msg_erro) == 0){
			if (@pg_num_rows($res) == 0) {
				echo "<center><font face='arial' size='-1'>Produto informado não encontrado</font></center>";
			}else{

			#---------- listagem -------------
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
					$peca_referencia        = trim(pg_fetch_result($res,$i,peca_referencia));
					$peca_descricao         = trim(pg_fetch_result($res,$i,peca_descricao));
					$unidade                = trim(pg_fetch_result($res,$i,unidade));
					$ipi                    = trim(pg_fetch_result($res,$i,ipi));
					$multiplo               = trim(pg_fetch_result($res,$i,multiplo));
					$origem                 = trim(pg_fetch_result($res,$i,origem));
					$codigo_origem          = trim(pg_fetch_result($res,$i,classificacao_fiscal));
					$linha_peca             = trim(pg_fetch_result($res,$i,linha_peca));
					$marca             		= trim(pg_fetch_result($res,$i,marca));
					$ncm                    = trim(pg_fetch_result($res,$i,ncm));
					$para                   = trim(pg_fetch_result($res,$i,para));
                    $peca_fora_linha        = trim(pg_fetch_result($res,$i,peca_fora_linha));
					$parametros_adicionais  = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);

                    $unitario = $parametros_adicionais["caixa_unitario"];
                    $coletiva = $parametros_adicionais["caixa_coletiva"];

                    $estoque 	= ucfirst($parametros_adicionais["estoque"]);
                    $previsao 	= mostra_data($parametros_adicionais["previsao"]);
					$estoque = strtoupper($estoque);
                    if($estoque == "INDISPONIVEL" or $estoque == "INDISPONÍVEL"){
                    	$estoque = "<font face='arial' size='-2'>$estoque </font>";
                    }else{
                    	$previsao = " - ";
                    	$estoque = "<font face='arial' size='-2'>$estoque </font>";
                    }

                    // regra para o obsoleto
                    if ( strlen($peca_fora_linha) > 0 ) {
						$estoque = "<font face='arial' size='-2'>OBSOLETO</font>";
						$previsao = " - ";
					}
					// regra para o subst
					if ( strlen($para) > 0 ) {
						$estoque = "<font face='arial' size='-2'>SUBST</font>";
						$previsao = " - ";
					}


					if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
					if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
					if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";
					if (strtoupper($origem) == 'FAB/SUB') $origem = "Fabricação Subsidiado";
					if (strtoupper($origem) == 'TER/SUB') $origem = "Terceirizada Subsidiado";
					if (strtoupper($origem) == 'IMP/SUB') $origem = "Importada Subsidiado";

                    if ($origem == 'FAB/SA') {
                        $origem = 'Fabricação/Semi acabado';
                    }
                    elseif ($origem == 'IMP/SA') {
                        $origem = 'Importado/Semi acabado';
                    }

					//retirado Wellington HD 1826
					//if (strlen($linha_peca) == 0) $linha = "COMPRESSOR";

					if (strlen($linha_peca) == 0){
						$linha = "";

						if(!empty($marca)){
							$sql = "SELECT nome FROM tbl_marca WHERE fabrica = $login_fabrica AND marca = $marca";
							$resMarca = pg_query($con,$sql);

							if(pg_num_rows($resMarca) > 0){
								$linha = pg_fetch_result($resMarca, 0, "nome");
							}
						}
					}else{
						$sql = "SELECT nome FROM tbl_linha WHERE linha = $linha_peca AND fabrica = $login_fabrica";
						$resLinha = pg_query($con,$sql);

						if(pg_num_rows($resLinha) > 0){
							$linha = pg_fetch_result($resLinha, 0, "nome");
						}
					}

					if ($multiplo < 2) $multiplo = '1';

					if ($tabela == 54) {
						$preco = pg_fetch_result($res, $i, total);

						if(strlen($preco) == 0){
							$preco = "";
						}

					}else{
						switch ( substr($descricao,0,3) ) {
							case "Dis" :
							case "DIS" :
							case "TOP" :
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

                        if (strpos($origem, 'Semi acabado') !== false) {
                            $tmp_preco = $preco;

                            if (empty($preco_compra)) {
                                $preco_compra = pg_fetch_result($res, $i, 'compra');
                            }

                            $tmp_compra = $preco_compra;

                            if (!empty($preco_distrib)) {
                                $preco_distrib = $tmp_preco;
                            }

                            $preco = $tmp_compra;
                            $preco_compra = $tmp_compra;

                            unset($tmp_compra);
                            unset($tmp_preco);
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
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Codigo Origem</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Linha</b></font></td>";
						echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>NCM</b></font></td>";

                        if ($tabela <> 54) {
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Status</b></font></td>";
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Mudou para</b></font></td>";
                        }

						if ($tabela == 54) {
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
							echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
						}else{
							if ($liberar_preco) {
								switch ( substr($descricao,0,3) ) {
									case "Dis" :
									case "DIS" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "TOP" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Distribuição<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "5SA" :
									case "5SB" :
									case "5SC" :
									case "VIP" :
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Preço<br>sugerido<br>com IPI</b></font></td>";
										echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Quantidade<br>múltipla</b></font></td>";
									break;
									case "AUT" :
										if($pedido_faturado == 't'){
											echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>IPI</b></font></td>";
											echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>sem IPI</b></font></td>";
										}else{
											echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Compra<br>com IPI</b></font></td>";
										}
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
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Caixa<br>Unitário</b></font></td>";
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Caixa<br>Coletiva</b></font></td>";
						}

                        if ($tabela <> 54) {
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Estoque</b></font></td>";
                            echo "<td bgcolor='#007711' align='center'><font face='arial' color='#ffffff' size='2'><b>Previsão</b></font></td>";
                        }


						echo "</tr>";
					}

					echo "<tr bgcolor='$cor'>";

					echo "<td nowrap>";
					echo "<font face='arial' size='-2'>";
					echo $peca_referencia;
					echo "</font>";
					echo "</td>";

					echo "<td align='left' nowrap>";
					echo "<font face='arial' size='-2'>";
					echo $peca_descricao;
					echo "</font>";
					echo "</td>";

					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $origem;
					echo "</font>";
					echo "</td>";

					echo "<td align='center'>";
					echo "<font face='arial' size='-2'>";
					echo $codigo_origem;
					echo "</font>";
					echo "</td>";

					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $linha;
					echo "</font>";
					echo "</td>";

					echo "<td align='left'>";
					echo "<font face='arial' size='-2'>";
					echo $ncm;
					echo "</font>";
					echo "</td>";

                    if ($tabela <> 54) {
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
                    }

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
								case "DIS" :
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
								case "TOP" :
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
								case "5SA" :
								case "5SB" :
								case "5SC" :
								case "VIP" :
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
								case "AUT" :
									if($pedido_faturado == 't'){
										echo "<td align='right'>";
										echo "<font face='arial' size='-2'>";
										echo $ipi;
										echo "</font>";
										echo "</td>";
									}
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
                        echo "<td align='center'>";
                        echo "<font face='arial' size='-2'>";
                        echo $unitario;
                        echo "</font>";
                        echo "</td>";

                        echo "<td align='center'>";
                        echo "<font face='arial' size='-2'>";
                        echo $coletiva;
                        echo "</font>";
                        echo "</td>";
					}
                    
                    if ($tabela <> 54) {

                        echo "<td align='center'>$estoque";

                        echo "</td>";

                        echo "<td align='center'>";
                        echo "<font face='arial' size='-2'>";
                        echo $previsao;
                        echo "</font>";
                        echo "</td>";

                    }

					echo "</tr>";
				}
			}
			echo "</table>";
			echo "<br><br><br>";
            if ($tabela <> 54) {
                echo "<table>";
                    echo "<tr>";
                        echo "<td align='center' bgcolor='#f4f4f4'><p align='center'>
                            <font size='1'><b> A previsão informada refere-se a disponibilidade da peça na fábrica. Para entrega é necessário considerar o prazo de envio de acordo com sua região. <Br> Previsão sujeita a alteração.</b></font></p>
                        </td>";
                    echo "</tr>";
                echo "</table>";
            }
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>
