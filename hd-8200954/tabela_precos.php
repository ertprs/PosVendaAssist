<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);
if (isFabrica(1)) {
	header ("Location: tabela_precos_blackedecker_consulta.php");
	exit;
}

if (isFabrica(6)) {
	header ("Location: tabela_precos_tectoy.php");
	exit;
}

if (isFabrica(14, 66)) {
	header ("Location: tabela_precos_intelbras.php");
	exit;
}

if (isFabrica(42)) {
	header ("Location: tabela_precos_makita.php");
	exit;
}

if (isFabrica(24) and 1==2) {
	header ("Location: tabela_precos_pecas.php");
	exit;
}
if ($_GET['referenciaDePara'] != "") {
    $referenciaDePara = $_GET['referenciaDePara'];
    //Verifica se a peça tem depara e então envia false para frontend alertar usuário de que deve escolher o produto pela lupa
    $sql = "select depara from tbl_depara where fabrica = ".$login_fabrica." AND de = '".$referenciaDePara."';";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res)>0) {
        echo "FALSE";
    } else {
        echo "TRUE";
    }
    exit;
}

if (in_array($login_fabrica, array(11,172)) && $_GET["alterar_fabrica"]) {

    $fabrica = $_GET["fabrica"];

    $self = $_SERVER['PHP_SELF'];
    $self = explode("/", $self);

    unset($self[count($self)-1]);

    $page = implode("/", $self);
    $page = "http://".$_SERVER['HTTP_HOST'].$page."/token_cookie_changes.php";
    $pageReturn = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

    $params = "?cook_admin=&cook_fabrica={$fabrica}&page_return={$pageReturn}";
    $page = $page.$params;

    header("Location: {$page}");
    exit;

}

//14356 21/2/2008 - AND $login_fabrica <> 11
$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_query($con, $sql);

if (pg_num_rows($res)>0) {
	if (pg_result ($res,0,0) == 'f' AND !isFabrica(11, 24, 43, 50, 172,177)) {
		echo "<H4 style='text-transform: uppercase'>" . traduz(array('tabela.de.precos','bloqueada'), $con) . "</H4>";
		include "rodape.php";
		exit;
	}
}

$title = isFabrica(177) ? 'Listagem de Peças (Valor simbólico)' : traduz('tabela.de.precos', $con);
$layout_menu = 'preco';
include "cabecalho.php";

if ((isFabrica(15) and $login_posto <> 6359) ) { // hd 90490
	echo "<H4 style='text-transform: uppercase'>" . traduz('desativado.temporariamente', $con) . "</H4>";
	include "rodape.php";
	exit;
}

$liberar_preco = true ;
if (isFabrica(3) AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) $liberar_preco = false;

if ($_REQUEST['tabela']) 
    $tabela = $_REQUEST['tabela'];

if ($_REQUEST['referencia_produto']) 
    $referencia_produto = $_REQUEST['referencia_produto'];

if ($_REQUEST['descricao_produto']) 
    $descricao_produto  = $_REQUEST['descricao_produto'];

if ($_REQUEST['referencia_peca']) 
    $referencia_peca = $_REQUEST['referencia_peca'];

if ($_REQUEST['descricao_peca']) 
    $descricao_peca = $_REQUEST['descricao_peca'];

/* Retirado a fabrica 35 desta condição, pois o admin solicitou - hd-2612042 */
if (isFabrica(3, 66)) {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {
		$tabela = "";
	}
}

?>

<? include "javascript_pesquisas_novo.php" ?>
<script type="text/javascript" src="js/jquery-latest.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript">

	var traducao = {
		informar_parte_para_pesquisa: '<?=traduz('informar.toda.parte.informacao.para.realizar.pesquisa', $con)?>',
		aguarde_submissao:			  '<?=traduz('aguarde.submissao', $con, $cook_idioma)?>',
	}

	$(document).ready(function(){
		Shadowbox.init();
		$("#relatorio").tablesorter();
	});

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

function verificaPecaDePara(){

    var referencia = $("input[name='referencia_peca']").val();
    var ret = null;
    $.ajax({
        url: "tabela_precos.php",
            method: "GET",
            data:{
                referenciaDePara: referencia
            },
            async: false,
            success: function(response){
                console.log(response);
                ret = response;
            }
    });
    if (ret == 'FALSE') {
        return false;
    } else {
        return true;
    }
}

function pesquisaProduto(produto,tipo){

	var fabrica = "<?=$login_fabrica?>";

	if ( fabrica == "11" || fabrica == "172" ) {
		var extra = "l_mostra_produto=ok";
	}

	if (jQuery.trim(produto).length > 2){
		Shadowbox.open({
			content:	"produto_pesquisa_2_nv.php?"+tipo+"="+produto+"&"+extra,
			player:	"iframe",
			title:		"Produto",
			width:	800,
			height:	500
		});
	} else {
		alert(traducao.informar_parte_para_pesquisa);
		produto.focus();
	}
}

function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,posicao){
	gravaDados("referencia_produto",referencia);
	gravaDados("descricao_produto",descricao);
}

function gravaDados(name, valor){
	try{
		$("input[name="+name+"]").val(valor);
	} catch(err){
		return false;
	}
}

function onoff(id) {
	var el = document.getElementById(id);
	el.style.display = (el.style.display=="") ? "none" : "";
}

function addslashes(str) {
	str=str.replace(/([\\|\"|\'|0])/g,'\\$1');
	return str;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if (browser == "Microsoft Internet Explorer") {
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

function escondePeca(){
	if (document.getElementById('div_peca')){
		var style2 = document.getElementById('div_peca');
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		} else {
			style2.style.display = "block";
		}
	}
}
function mostraPeca(arquivo,peca) {
	var el = document.getElementById('div_peca');
	el.style.display = (el.style.display=="") ? "none" : "";
	imprimePeca(arquivo,peca);
}

function alterar_fabrica(fabrica){
    location.href = "tabela_precos.php?alterar_fabrica=sim&fabrica="+fabrica;
}

var http3 = new Array();
function imprimePeca(arquivo,peca){
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "peca_pesquisa_lista_new.php?ajax=true&idpeca="+peca+"&arquivo="+ arquivo;
	http3[curDateTime].open('get',url);
	var campo = document.getElementById('div_peca');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;
	http3[curDateTime].onreadystatechange = function(){
		if (http3[curDateTime].readyState == 1)  {
			campo.innerHTML = "<span style='font-familiy:Verdana,Arial,Sans-Serif;font-size:10px'>Aguarde..</span>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){

				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_peca').innerHTML ='';
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;
	//For old IE browsers
	if (document.all)  {
		fWidth = document.body.clientWidth;
		fHeight = document.body.clientHeight;
	}
	//For DOM1 browsers
	else if (document.getElementById &&!document.all) {
			fWidth = innerWidth;
			fHeight = innerHeight;
		}
		else if (document.getElementById)  {
				fWidth = innerWidth;
				fHeight = innerHeight;
			}
			//For Opera
			else if (is.op) {
					fWidth = innerWidth;
					fHeight = innerHeight;
				}
				//For old Netscape
				else if (document.layers) {
						fWidth = window.innerWidth;
						fHeight = window.innerHeight;
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}


</script>

<style>
.aviso{
    font: 14px Arial;
    color: #FFF;
    background-color: #F00;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border:1px solid #FFF;
    padding: 2px 0;
    font-weight: bold;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border:1px solid #596d9b;
    padding: 2px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important;
    color:#FFFFFF;
	text-align:center;
	text-transform: capitalize;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
	color: #FFFFFF;
	text-transform: uppercase;
}

table.tabela{
    padding: 0;
    margin: 0 auto;
    border:0;
    width: 700px;
    background-color: #CCC;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    padding: 2px;
    border: 1px solid #FFF;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
    width: 700px;
    margin: 0 auto;
}

.btn_submit{
   text-align: center;
   padding: 15px 0;
}

.espaco{
    padding-left: 140px;
}

#layout{
    width: 700px;
    margin:0 auto;
}
</style>
<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<?if (!isFabrica(15)) {?>
<script language=JavaScript>
//script from www.argnet.tk
function blockError(){return true;}
window.onerror = blockError;
</script>

<script language=JavaScript>

function FuncTabela (tabela) {
	if (tabela == 54) {
		document.forms[0].submit();
	}
}
</script>
<?}?>
<script>
    
function listar_produtos(){

    var referencia = $("#referencia_produto").val();
    var descricao  = $("#descricao_produto").val();

    location.href = "tabela_precos.php?relatorio=1&referencia_produto="+referencia+"&descricao_produto="+descricao;

}

</script>
<form method='get' action='<? echo $PHP_SELF ?>' name='frm_tabela'>
<div id='div_peca' style='display:none; Position:fixed;Top:30%;Left:35%; border: 1px solid #949494;background-color: #b8b7af;width:410px; heigth:400px'></div>

<br><br>
<table width="700px" border="0" cellpadding="4" cellspacing="1" align="center" class='formulario'>

<?php if(in_array($login_fabrica, array(11,172))){ ?>
    <div id="layout">
	    <div style="text-align: right;">
	        Logar em: 
	        <select class="frm" style="width: 120px;" onchange="alterar_fabrica(this.value);">
	            <option value="11" <?php echo ($login_fabrica == 11) ? "selected" : ""; ?> >Aulik</option>
	            <option value="172" <?php echo ($login_fabrica == 172) ? "selected" : ""; ?> >Pacific</option>
	        </select>
	    </div>
    </div>
    <br />
<?php } ?>

	<?php if (isFabrica(24)) {?>
		<tr>
			<td colspan='2' class='texto_avulso'><?=traduz('os.precos.apresentados.sao.o.valor.final.de.venda.com.impostos.inclusos', $con)?></td>
		</tr>
	<?php }?>
    <tr>
		<td colspan='2' class='titulo_tabela'><?=traduz('parametros.de.pesquisa', $con)?></td>
    </tr>
    <tr>
        <td width='350px;'>&nbsp;</td>
        <td width='*'>&nbsp;</td>
    </tr><?php
	if (!isFabrica(87)) {?>
    <tr>
	    <td colspan='2' class='espaco'>
			<?=traduz('tabela.de.precos', $con)?><br />
            <select name="tabela" size="1" tabindex="0" class='frm' onchange='javascript: FuncTabela(this.value);'>
		<?php

			$res          = pg_exec($con, "SELECT linha_pedido FROM tbl_fabrica WHERE fabrica = $login_fabrica");
			$linha_pedido = pg_result($res,0,0);

			$sql = "SELECT  tbl_posto_linha.tabela as tabela ,
							tbl_tabela.sigla_tabela      ,
							tbl_tabela.descricao
					FROM    tbl_tabela
					JOIN    tbl_posto_linha ON tbl_posto_linha.tabela = tbl_tabela.tabela
					JOIN    tbl_linha    ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
					WHERE   tbl_tabela.fabrica    = $login_fabrica
					AND     tbl_posto_linha.posto = $login_posto
					AND     tbl_tabela.ativa   = 't'
					GROUP BY tbl_posto_linha.tabela ,
							tbl_tabela.sigla_tabela,
							tbl_tabela.descricao ";

			if (isFabrica(1))
				$sql .= "ORDER BY tbl_tabela.tabela ASC";
			else
				$sql .= "ORDER BY tbl_tabela.sigla_tabela";

			if (ifFabrica(40,101,115,116,122,125,128,129,131,121,123,124, '132...')) {

				if (isFabrica(147)) {
					$sql = "SELECT contato_estado from tbl_posto_fabrica where posto = $login_posto and UPPER(contato_estado) = 'SP'";
					$res = pg_query($con,$sql);
					if (pg_num_rows($res)>0) {
						$cond_estado = " AND tbl_tabela.tabela_principal = TRUE ";
					}
				}


				if (!isFabrica(145,129)) {
					$cond_garantia = "AND tbl_tabela.tabela_garantia is not true";
				}

				$sql = " SELECT tbl_tabela.tabela      ,
								tbl_tabela.sigla_tabela,
								tbl_tabela.descricao
					FROM        tbl_tabela
					JOIN        tbl_posto_linha ON (tbl_tabela.tabela = tbl_posto_linha.tabela_posto or tbl_tabela.tabela = tbl_posto_linha.tabela)
					JOIN        tbl_linha       ON tbl_linha.linha   = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
					WHERE       tbl_tabela.fabrica    = $login_fabrica
					AND         tbl_posto_linha.posto = $login_posto
					AND         tbl_tabela.ativa      = 't'
					$cond_garantia
								{$cond_estado}
					GROUP BY    tbl_tabela.tabela      ,
								tbl_tabela.sigla_tabela,
								tbl_tabela.descricao ";

			}

		    if (isFabrica(50)) {

			    $sql = "SELECT *
						FROM   tbl_tabela
						WHERE  tbl_tabela.fabrica = $login_fabrica
						AND    tbl_tabela.ativa   = 't'
						AND    tbl_tabela.sigla_tabela not in ('001-GAR','003-DOA')
						ORDER BY tbl_tabela.sigla_tabela";

		    }

		    if (isFabrica(2)) {

			    $sql = "SELECT *
					    FROM   tbl_tabela
				    	WHERE  tbl_tabela.fabrica = $login_fabrica
				    	AND    tbl_tabela.tabela = 236
				    	ORDER BY tbl_tabela.sigla_tabela";

		    }

		    if (isFabrica(43)) {

			    $sql = "SELECT *
						FROM   tbl_tabela
						WHERE  tbl_tabela.fabrica = $login_fabrica
						AND    tbl_tabela.tabela = 273
						ORDER BY tbl_tabela.sigla_tabela";

		    }

		    $res = pg_exec($con,$sql);


		    if (!isFabrica(5)) {

		        if (pg_num_rows($res) == 0 and $linha_pedido <> 't') {

		        	if (isFabrica(147)) {
						$sql = "SELECT contato_estado from tbl_posto_fabrica where posto = $login_posto and UPPER(contato_estado) = 'SP'";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res)>0) {
							$cond_estado = " AND tbl_tabela.tabela_principal = true ";
						}
					}

			        $sql = "SELECT *
							FROM   tbl_tabela
							WHERE  tbl_tabela.fabrica = $login_fabrica
							AND    tbl_tabela.ativa   = 't'
							{$cond_estado} ";

			        if (isFabrica(1))
					$sql .= "AND tbl_tabela.sigla_tabela not in ('GARAN') ";
			
				if (isFabrica(203))
					$sql .= " AND tbl_tabela.tabela_garantia IS NOT TRUE ";

			        if (isFabrica(1))
			            $sql .= "ORDER BY tbl_tabela.tabela ASC";
			        else
			            $sql .= "ORDER BY tbl_tabela.sigla_tabela";

			        $res = pg_query($con,$sql);

		        }

		        for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

			        $aux_tabela       = trim(pg_fetch_result($res, $i, 'tabela'));
			        $aux_sigla_tabela = trim(pg_fetch_result($res, $i, 'descricao'));

			        echo "<option "; if ($tabela == $aux_tabela) echo " selected "; echo " value='$aux_tabela'>$aux_sigla_tabela</option>";

		        }

		    } else{
			    echo "<option ";
			    if ($tabela == 23) {
                                echo " selected ";
			    }
                            echo " value='23'>Tabela Unificada - 45 DDF</option>";
		    }
														   ?>
		    </select>

	    </td>
    </tr>
    <?
    if ($tabela != 54) {
    	if (!isFabrica(143)) {
    	?>
	        <tr>
	            <td class='espaco'>
	            	<?php
	            	if (isFabrica(11,172)){
	            		$onclick_js_ref  = "onclick=\" pesquisaProduto($('#referencia_produto').val(),'referencia') \" ";
	            		$onclick_js_desc = "onclick=\" pesquisaProduto($('#descricao_produto').val(),'descricao') \"   ";
	            	} else {
	            		$onclick_js_ref  = "onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.descricao_produto,document.frm_tabela.referencia_produto,\"referencia\", document.frm_tabela.voltagem_produto)'";
	            		$onclick_js_desc = "onclick='javascript: fnc_pesquisa_produto (document.frm_tabela.descricao_produto,document.frm_tabela.referencia_produto,\"descricao\", document.frm_tabela.voltagem_produto)'";
	            	}
	            	?>
					<? echo (isFabrica(1)) ? traduz('codigo.do.produto', $con) : traduz('referencia.do.produto', $con)?><br>
	                <input type='text' name='referencia_produto' id='referencia_produto' size='13' maxlength='30' value='<? echo $referencia_produto ?>' class='frm'>
		    	    <a <?php echo $onclick_js_ref ?>><img src='imagens/lupa.png' border='0' align='absmiddle' > </a>
	           </td>
	           <td>
					<? echo (isFabrica(1, 11, 24, 172)) ? traduz('descricao.do.produto', $con) : traduz('modelo.do.produto', $con);?><br>
		    	    <input type='text' name='descricao_produto' id='descricao_produto' size='20' maxlength='50' value='<? echo $descricao_produto ?>' class='frm'>
		    	    <a <?php echo $onclick_js_desc ?>><img src='imagens/lupa.png' border='0' align='absmiddle' ></a>
		    	    <input type="hidden" name="voltagem_produto" value="">
	           </td>
	        </tr>
	    <?php
		}
		?>
        <tr>
            <td class='espaco'>
				<? echo (isFabrica(1)) ? traduz('codigo.da.peca', $con) : traduz('referencia.da.peca', $con);?><br>
        		<input type='text' name='referencia_peca' size='13' maxlength='30' value='<? echo $referencia_peca ?>' onblur="this.className='frm'; displayText('&nbsp;');" class='frm'>
		        <a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"referencia")'></a>
            </td>
            <td>
				<?echo (isFabrica(1)) ? traduz('descricao.da.peca', $con) : traduz('modelo.da.peca', $con);?><br>
		        <input type='text' name='descricao_peca' size='20' maxlength='50' value='<? echo $descricao_peca ?>' class='frm'>
		        <a href="#"><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,"descricao")'></a>
            </td>
        </tr>
    <? }?>
    <tr>
        <td colspan='2' class='btn_submit'>
            <?php   if (!isFabrica(143, 168)) {
						if ($tabela != 54) { ?>
							<input type="button" name="btn_acao" value=" <?=traduz('listar.produtos', $con)?> " onclick="listar_produtos()" style='cursor: hand;'>
			<?			}
					} ?>

			<input type="hidden" name="btn_acao" value="">
			<input type="button" name="btn_acao" value=" <?=traduz ('listar.pecas', $con) ?> " onclick="javascript: if(verificaPecaDePara() == false){ alert('Por favor, use a lupa para selecionar sua peça'); return false; } if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() }" ALT="<?=traduz('listar.tabela.de.precos', $con)?>" border='0' style='cursor:pointer;'>
        </td>
    </tr>
    <? } else {?>
	<tr>
		<td colspan='2' class='btn_submit'>
			<input type="hidden" name="btn_acao" value="">
			<input type="hidden" name="tabela" value="439"><?php //a jacto só possui uma tabela 20/07/2011?>
			<input type="submit" name="btn_listar" value=" <?=traduz('listar.pecas', $con)?> " onclick="javascript: if (document.frm_tabela.btn_acao.value == '' ) { document.frm_tabela.btn_acao.value='continuar' ; document.frm_tabela.submit() }" ALT="<?=traduz('listar.tabela.de.precos', $con)?>" border='0' style='cursor:pointer;'>
		</td>
	</tr>
	<?php }?>
</table>
</form>

<div style='padding: 5px 0'>
<?php   if (!isFabrica(87)) { ?>
			<input type="button" name="btn_acao" value=" <?=traduz('nova.pesquisa', $con)?> " onclick="javascript: location.href='<? echo $PHP_SELF ?>';" style='cursor:pointer;'>
<?			
	}

    if (!isFabrica(3, 115)) {
		if (strlen($tabela) > 0) {
            $url = "tabela_precos_xls.php?tabela=$tabela";
            if (!empty($referencia_produto)) {
                $url .= "&ref_prod=$referencia_produto";
            }elseif (strlen($referencia_peca) > 0) {
                $url .= "&ref_peca=$referencia_peca";
            }
        ?>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="button" name="btn_acao" value=" <?=traduz('download.em.%', $con, $cook_idioma, 'XLS/TXT')?> " onclick="javascript: location.href='<?=$url;?>';" style='cursor:pointer;'>
		<? }
	}?>
</div>
<?
if (strlen ($_GET['relatorio']) > 0) {
	if (isFabrica(1)) $tab = "31";

	#HD 17663
	if (isFabrica(50)){
		$sql_ativo = " AND tbl_linha.ativo IS TRUE ";
	}

    if (strlen($_GET["referencia_produto"]) > 0) {
        $referencia_produto = trim($_GET["referencia_produto"]);
        $cond .= " AND LOWER(referencia) = LOWER('{$referencia_produto}') ";
    }

    if (strlen($_GET["descricao_produto"]) > 0) {
        $descricao_produto = trim($_GET["descricao_produto"]);
        $cond .= " AND LOWER(descricao) = LOWER('{$descricao_produto}') ";
    }

	$sql = "SELECT tbl_produto.*
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo = 't' 
            {$cond}
			".$sql_ativo."
			ORDER BY tbl_produto.descricao";
	$res = pg_exec ($con,$sql);

	echo "<br><table align='center' width='700px' border='0' cellpadding='0' cellspacing='1' class='tabela' >";

	echo "<tr class='titulo_tabela'>";
        echo "<td colspan='2'>" . traduz('lista.de.produtos', $con) . "</td>";
	echo "</tr>";

	echo "<tr class='titulo_coluna'>";
        echo "<td>" . traduz('referencia', $con) . "</td>";
        echo "<td>" . traduz('descricao', $con) . "</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		$refer = pg_result ($res,$i,referencia);
		$descr = pg_result ($res,$i,descricao);

		echo "<tr bgcolor='$cor'>";

		    echo "<td>";
		        if (isFabrica(1)) echo "<a href='$PHP_SELF?tabela=$tab&referencia_produto=$refer&descricao_produto=$descr&btn_acao=continuar'>";
		            echo $refer;
		        if (isFabrica(1)) echo "</a>";
		    echo "</td>";

		    echo "<td>{$descr}</td>";
		echo "</tr>";
	}
	echo "</table>";
}

# verifica se posto pode ver pecas de itens de aparencia
$sql = "SELECT   
            tbl_posto_fabrica.item_aparencia,
            tbl_posto_fabrica.desconto 
		FROM tbl_posto
		INNER JOIN tbl_posto_fabrica USING(posto)
		WHERE 
            tbl_posto.posto = $login_posto
		    AND tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if (pg_num_rows ($res) > 0) {
    $item_aparencia = pg_fetch_result($res, 0, "item_aparencia");
	$desconto = pg_fetch_result($res, 0, "desconto");

    if (strlen(trim($desconto)) > 0) {
        $desconto_posto = str_replace(",", ".", $desconto);
    } else {
        $desconto_posto = 0;
    }

}

if (strlen($tabela) > 0)  {
	if (strlen($descricao_produto) == 0 AND strlen($referencia_produto) == 0 AND strlen($descricao_peca) == 0 AND strlen($referencia_peca) == 0) {

		########## EXIBE TABELA DE PRECO
		$letra = (strlen($_GET['letra']) == 0) ? 'a' : $_GET['letra'];

		#HD 17663 - somente peças de linha ativa
		if (isFabrica(50)){
			$sql = "SELECT DISTINCT peca
					INTO TEMP tmp_pecas_ativas_$login_fabrica$login_posto
					FROM tbl_lista_basica
					JOIN tbl_produto USING(produto)
					JOIN tbl_linha USING(linha)
					WHERE tbl_linha.fabrica = $login_fabrica
					AND tbl_linha.ativo IS TRUE;

					CREATE INDEX tmp_pecas_ativas_peca ON tmp_pecas_ativas_$login_fabrica$login_posto(peca);
					";
			$res = pg_exec ($con,$sql);
			$join_linhas_ativas = " JOIN tmp_pecas_ativas_$login_fabrica$login_posto peca_ativa ON peca_ativa.peca = tbl_tabela_item.peca ";
		}

        $join_peca_fora_linha = '';
        $cond_peca_fora_linha = '';

        if (isFabrica(91)) {
            $join_peca_fora_linha = 'LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = tbl_peca.peca AND tbl_peca_fora_linha.fabrica = ' . $login_fabrica;
            $cond_peca_fora_linha = 'AND tbl_peca_fora_linha.peca IS NULL';
        }

		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99') AS total,
						tbl_peca.peca as peca_id
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
                $join_linhas_ativas
                $join_peca_fora_linha
				WHERE   tbl_peca.fabrica       = $login_fabrica
				AND		tbl_tabela_item.tabela = $tabela
				AND		tbl_peca.ativo         = 't'
                AND		tbl_peca.descricao ILIKE '$letra%'
                $cond_peca_fora_linha 
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";

		if (isFabrica(138)) {
			$sql = "	SELECT DISTINCT 
							CASE WHEN peca_para NOTNULL THEN para.referencia ELSE tbl_peca.referencia END AS peca_referencia ,
							CASE WHEN peca_para NOTNULL THEN para.descricao ELSE tbl_peca.descricao END AS peca_descricao , 
							CASE WHEN peca_para NOTNULL THEN para.unidade ELSE tbl_peca.unidade END AS unidade ,
							CASE WHEN peca_para NOTNULL THEN para_preco.preco ELSE tbl_tabela_item.preco END AS preco ,
							CASE WHEN peca_para NOTNULL THEN para.ipi ELSE tbl_peca.ipi END AS ipi ,
							CASE WHEN peca_para NOTNULL THEN to_char((para_preco.preco * ((1 + para.ipi))/10),'999999990.99') ELSE to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99') END AS total,
							CASE WHEN peca_para NOTNULL THEN peca_para ELSE tbl_peca.peca END AS peca_id
						FROM tbl_peca
						JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
						LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
						LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
						LEFT JOIN tbl_tabela_item para_preco ON para_preco.peca = para.peca
						WHERE (tbl_peca.fabrica = $login_fabrica
						AND tbl_tabela_item.tabela = $tabela
						AND tbl_peca.ativo = 't'
						AND tbl_peca.descricao ILIKE '$letra%' AND peca_para ISNULL) OR (para.fabrica = $login_fabrica AND para_preco.tabela = $tabela AND para.ativo AND peca_para NOTNULL AND para.descricao ILIKE '$letra%')
						ORDER BY 2, 1";
		}

//		$res = pg_exec ($con,$sql);
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

	echo "<div class='texto_avulso' style='padding: 5px 0'>" .
			 traduz('para.facilitar.a.visualizacao.dos.itens.separamos.por.iniciais', $con) .
			 '<br />' .
			 traduz('para.consultar.um.item.clique.na.inicial.correspondente', $con) .
		 '</div>';
		echo "<table width='700px' border='0' cellpadding='0' cellspacing='0' align='center' class='tabela'>";
		echo "<tr class='titulo_tabela'>";
	        echo "<td colspan='7'>" . traduz('lista.de.pecas', $con) . "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan='7' class='titulo_coluna'>";
		    echo "<table width='100%' align='center' border='0' cellpadding='0' cellspacing='0'>";

		        echo "<tr >";
		        $letras =  array(0=>'A', 'B', 'C', 'D', 'E',
							'F', 'G', 'H', 'I', 'J',
							'K', 'L', 'M', 'N', 'O',
							'P', 'Q', 'R', 'S', 'T',
							'U', 'V', 'W', 'X', 'Y', 'Z');
		        $totalLetras = count($letras);
		        for ($j=0; $j<$totalLetras; $j++) {
		    	    echo "<td align='center' class='texto_avulso'>";
		    	        echo "<a href='$PHP_SELF?letra=$letras[$j]&tabela=$tabela'>&nbsp;$letras[$j]&nbsp;</a>";
		    	    echo "</td>";
		        }
		        echo "</tr>";
		    echo "</table>";
		echo "</td>";
		echo "</tr>";

        if (pg_numrows($res) > 0) {
		echo "<tr class='titulo_coluna'>";
		    echo "<td width='80px'>" . traduz('referencia', $con) . "</td>";
		    echo "<td width='*'>" . traduz(array('descricao', 'peca'), $con) . "</td>";
		    echo "<td width='50px'>UN</td>";
		    if ($liberar_preco) {
		        if (isFabrica(88)) 
		            echo "<td width='120px'>" . traduz('preco.sugerido.para.o.consumidor', $con) . "</td>";
		        else
		    	    echo "<td width='80px'>" . traduz('preco', $con) . "</td>";
		    	echo "<td width='70px'>IPI % </td>";
		    	if (isFabrica(24, 140)) {
					echo "<td width='80px'>" . traduz('preco.sugerido.para.venda', $con) . "</td>";
				}
		    }
		    if (isFabrica(88))  {
		    	echo "<td>Imagem</td>";
		    }
            if (isFabrica(157)) {
                    echo "<td nowrap> Desconto {$desconto_posto}% </td>";
                }
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$unidade            = trim(pg_result ($res,$i,unidade));
			$preco              = trim(pg_result ($res,$i,preco));
			$ipi                = trim(pg_result ($res,$i,ipi));
			$peca_id            = trim(pg_result ($res,$i,'peca_id'));

            if (isFabrica(157)) {
                $preco_desconto_posto = $preco - (($preco / 100) * $desconto);
            }

			$preco_com_ipi = $preco * (1 + $ipi/100);

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			    echo "<td>{$peca_referencia}</td>";
                echo "<td>{$peca_descricao}</td>";
                echo "<td  align='center'>{$unidade}</td>";
                if ($liberar_preco) {

					#HD 380424
					if (isFabrica(24)){
						echo $preco;
						$preco_suggar = $preco + ($preco * 0.2);
						echo $preco_suggar;
						echo "<td align='center'>".number_format ( $preco_suggar - ($preco_suggar * 0.4) ,2,",",".")."</td>";
					} else {
						echo "<td align='center'>".number_format ($preco,2,",",".")."</td>";
					}

                    echo "<td align='center'>{$ipi}</td>";

					if (isFabrica(24, 140)) {
						$preco_segurido = (isFabrica(24)) ? $preco_suggar + ($preco_suggar * ($ipi/100)) : $preco + ($preco * 0.4) ;
						echo "<td align='right'>".number_format ( $preco_segurido ,2,",",".")."</td>";
					}
			    }

                if (isFabrica(157)) {
                    echo "<td align='center'> ".number_format($preco_desconto_posto, 2, ",", ".")." </td>";
                }

			if (isFabrica(88))  {
				$caminho = "imagens_pecas/$login_fabrica";
				$diretorio_verifica=$caminho."/pequena/";
				echo "<td>";
				$xpecas  = $tDocs->getDocumentsByRef($peca_id, "peca");
				if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<a href=\"javascript:mostraPeca('$fotoPeca','$peca_id')\">";
					echo "<img src='$fotoPeca' width='50'>";
					echo "</a>";
				} else {


					if (is_dir($diretorio_verifica) == true) {
						$contador=0;

						if ($dh = opendir($caminho."/pequena/")) {

							while (false !== ($filename = readdir($dh))) {

								if (strlen($peca_id) > 0)  {

									if ($contador == 1)  break;

									if (strpos($filename,$peca_id) !== false){

										$po = strlen($peca_id);
										$contador++;
										echo "<a href=\"javascript:mostraPeca('$filename','$peca_id')\">";
										echo "<img src='$caminho/pequena/$filename'>";
										echo "</a>";
									}

								}

							}

						}

					}
				}
				echo "</td>";
			}
			echo "</tr>";
		}

		} else {
		    echo "<tr bgcolor='#F1F4FA'>";
				echo "<td colspan='7' style='padding: 20px 0; text-align: center'>" .
						traduz('pecas.da.linha.com.iniciais.%.nao.encontradas', $con, $cook_idioma, $letra) .
						"</td>";
		    echo "</tr>";
		}

		echo "</table>";

		// ##### PAGINACAO ##### //

			// links da paginacao
			echo "<br>";

			echo "<div>";

			if ($pagina < $max_links)  {
				$paginacao = pagina + 1;
			} else {
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
				fecho('resultados.de.%.a.%.do.total.de.%.registros', $con, $cook_idioma, array($resultado_inicial,$resultado_final,$registros));
				echo "<font color='#cccccc' size='1'>";
				fecho('pagina.%.de.%', $con, $cook_idioma, array($valor_pagina, $numero_paginas));
				echo "</font>";
				echo "</div>";
			}

			// ##### PAGINACAO ##### //

	} else {

		$sem_lista_basica = false;
		########## EXIBE LISTA BÁSICA
		// SQL RETIRADO PARA MELHORAR PERFORMANCE

		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						tbl_peca.peca,
						to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99') AS total             ,
						tbl_produto.referencia                                                  AS produto_referencia,
						tbl_produto.descricao                                                   AS produto_descricao
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				JOIN    tbl_lista_basica ON tbl_peca.peca        = tbl_lista_basica.peca
				JOIN    tbl_produto      ON tbl_produto.produto  = tbl_lista_basica.produto
				WHERE   tbl_peca.fabrica = $login_fabrica
				AND     tbl_produto.ativo = 't'
				AND     tbl_peca.ativo    = 't' 
                {$cond}";

		if (!isFabrica(6)) {
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia <> 't' ";
		}
		if (strlen($descricao_produto) > 0) {
			$sql .= " AND tbl_produto.descricao like '$descricao_produto%' ";
		}

		if (strlen($referencia_produto) > 0) {
			$sql .= "AND upper(tbl_produto.referencia) = upper('$referencia_produto') ";
		}

		if (strlen($descricao_peca) > 0 and empty($referencia_peca)) {
			$sql .= " AND tbl_peca.descricao like '$descricao_peca%' ";
		}

		if (strlen($referencia_peca) > 0) {
			$sql .= "AND upper(tbl_peca.referencia) = upper('$referencia_peca') ";
		}

		// ORDENACAO
		if (isFabrica(3, 35, 66)) {
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.descricao ,
								tbl_peca.descricao    ,
								tbl_produto.referencia";
		} else {
			$sql .= "AND tbl_tabela_item.tabela = $tabela
					ORDER BY    tbl_produto.referencia,
								tbl_produto.descricao";
		}

		$campos_case = "tbl_peca.peca                         ,
						tbl_peca.referencia AS peca_referencia,
						tbl_peca.descricao  AS peca_descricao ,
						tbl_peca.unidade                      ,
						tbl_peca.ipi,
						tbl_peca.localizacao";
		$dist        = ""; 

		if (isFabrica(138)) {
			$dist        = "DISTINCT";
			$campos_case = "CASE WHEN peca_para NOTNULL THEN para.referencia ELSE tbl_peca.referencia END AS peca_referencia ,
							CASE WHEN peca_para NOTNULL THEN para.descricao ELSE tbl_peca.descricao END AS peca_descricao , 
							CASE WHEN peca_para NOTNULL THEN para.unidade ELSE tbl_peca.unidade END AS unidade ,
							CASE WHEN peca_para NOTNULL THEN para.ipi ELSE tbl_peca.ipi END AS ipi ,
							CASE WHEN peca_para NOTNULL THEN peca_para ELSE tbl_peca.peca END AS peca,
							CASE WHEN peca_para NOTNULL THEN para.localizacao ELSE tbl_peca.localizacao END AS localizacao";
		}

		// SQL INSERIDO PARA MELHORAR PERFORMANCE
		$sql = "SELECT  c.peca,
						c.produto_referencia  ,
						c.produto_descricao   ,
						c.peca_referencia     ,
						c.peca_descricao      ,
						c.unidade             ,
						c.ipi                 ,
						c.posicao             ,
						tbl_tabela_item.preco ,
						c.localizacao 		  ,

						to_char((tbl_tabela_item.preco * ((1 + c.ipi))/10),'999999990.99')::float AS total
				FROM (
						SELECT $dist b.produto_referencia                  ,
									 b.produto_descricao                   ,
									 b.posicao                             ,
									 $campos_case
						FROM (
								SELECT  a.produto_referencia    ,
										a.produto_descricao     ,
										tbl_lista_basica.produto,
										tbl_lista_basica.peca   ,
										tbl_lista_basica.posicao
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

		if (!isFabrica(6)) {
			if ($item_aparencia <> 't') $sql .= " AND tbl_peca.item_aparencia IS FALSE ";
		}

		if (isFabrica(138)) {
			$sql .= "	LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
						LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca AND para.fabrica = $login_fabrica AND para.ativo IS TRUE AND para.item_aparencia IS FALSE ";
		}

		if (strlen($descricao_peca) > 0 and empty($referencia_peca)) {
			$sql .= " WHERE tbl_peca.descricao ilike '%$descricao_peca%' ";
		}elseif (strlen($referencia_peca) > 0) {
			 $sql .= "WHERE upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca')) ";
		}

		$sql .= ") AS c
				JOIN tbl_tabela_item ON tbl_tabela_item.peca   = c.peca
									AND tbl_tabela_item.tabela = $tabela ";

		// ORDENACAO
		if (isFabrica(1)){
			$sql .= "ORDER BY   c.peca_descricao    ,
								c.produto_descricao ,
								c.produto_referencia";
		}elseif (isFabrica(3, 35, 66)) {
			$sql .= "ORDER BY   c.produto_descricao ,
								c.peca_descricao    ,
								c.produto_referencia";
		}elseif (isFabrica(11,172)) {

			$sql .= "Order by  c.peca_descricao";

		} else {
			$sql .= "ORDER BY   c.produto_referencia,
								c.produto_descricao";
		}

		if (isFabrica(1) && $tabela == 54) {
			$sql = "SELECT  a.peca_referencia     ,
							a.peca_descricao      ,
							a.unidade             ,
							a.ipi                 ,
							tbl_tabela_item.preco ,
							to_char((tbl_tabela_item.preco * ((1 + a.ipi))/10),'999999990.99')::float AS total
					FROM (
							SELECT  tbl_peca.peca                         ,
									tbl_peca.referencia AS peca_referencia,
									tbl_peca.descricao  AS peca_descricao ,
									tbl_peca.unidade                      ,
									tbl_peca.ipi
							FROM  tbl_peca
							WHERE tbl_peca.fabrica = $login_fabrica
							AND   tbl_peca.ativo IS TRUE ";
		if (!isFabrica(6)) {
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

		if (strlen($referencia_peca) > 0 && isFabrica(140,143,153,157)) {
			$sql = "SELECT tbl_peca.peca ,
				tbl_peca.referencia AS peca_referencia,
				tbl_peca.descricao AS peca_descricao ,
				tbl_peca.unidade ,
				tbl_peca.ipi,
				tbl_peca.localizacao,
				tbl_tabela_item.preco,
				to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99')::float AS total
				FROM tbl_peca
				JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
				AND tbl_tabela_item.tabela = {$tabela}
				WHERE tbl_peca.fabrica = {$login_fabrica}
				AND tbl_peca.ativo IS TRUE
				AND tbl_peca.item_aparencia IS FALSE
				AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca'))";
		}

		$res = pg_exec ($con,$sql);

		if (strlen($msg_erro) == 0){

			if (pg_num_rows($res) == 0) {
				$sem_lista_basica = true;
				$sql = "SELECT  tbl_peca.peca ,
								tbl_peca.referencia AS peca_referencia,
								tbl_peca.descricao AS peca_descricao ,
								tbl_peca.unidade ,
								tbl_peca.ipi,
								tbl_peca.localizacao,
								tbl_tabela_item.preco,
								to_char((tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10),'999999990.99')::float AS total
								FROM tbl_peca
								JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
								AND tbl_tabela_item.tabela = {$tabela}
								WHERE tbl_peca.fabrica = {$login_fabrica}
								AND tbl_peca.ativo IS TRUE
								AND tbl_peca.item_aparencia IS FALSE
								AND tbl_peca.descricao ilike '%$descricao_peca%'
								AND upper(trim(tbl_peca.referencia)) = upper(trim('$referencia_peca'))";
				$res = pg_exec ($con, $sql);
			}

			#--------- Criacao do arquivo em XLS ------------
			$arquivo = "download/tabela" . $tabela . ".csv";
			$fp = @fopen ($arquivo, 'w');

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				if (!isFabrica(1) && $tabela != 54) {
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

				if (isFabrica(11,172)) {

					if (strlen(pg_result($res,$i,localizacao)) > 0) {
						$estoque = "DISPONÍVEL";
					} else {
						$estoque = "INDISPONÍVEL";
					}
					$linha .= $estoque;
				}

				@fwrite ($fp,$linha);
				@fwrite ($fp,"\n");
			}
			@fclose ($fp);

			if (pg_numrows($res) == 0) {
				echo "<center><font face='arial' size='-1'>" . traduz('produto.nao.encontrado', $con) . "</font></center>";
			}

			if (isFabrica(1)) {
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
				if (!isFabrica(1) && $tabela != 54) {
					$produto_referencia = trim(@pg_result ($res,$i,'produto_referencia'));
					$produto_descricao  = trim(@pg_result ($res,$i,'produto_descricao'));
					$prox_refer         = trim(@pg_result ($res,$i-1,'produto_referencia'));
					$prox_descr         = trim(@pg_result ($res,$i-1,'produto_descricao'));
				}
				$peca_referencia    = trim(pg_result ($res,$i,'peca_referencia'));
				$peca_descricao     = trim(pg_result ($res,$i,'peca_descricao'));
				$unidade            = trim(pg_result ($res,$i,'unidade'));
				$peca_id            = trim(pg_result ($res,$i,'peca'));
				$preco              = trim(pg_result ($res,$i,'preco'));
				$posicao            = trim(pg_result ($res,$i,'posicao'));
				$ipi                = trim(pg_result ($res,$i,'ipi'));
				$estoque            = trim(pg_result ($res,$i,'localizacao'));

                if (isFabrica(157)) {
                    $preco_desconto_posto = $preco - (($preco / 100) * $desconto_posto);
                }

				$preco_com_ipi = $preco * (1 + $ipi/100);
				if (isFabrica(24, 140)) {
					$preco_sugerido_venda = (isFabrica(24)) ? $preco + ($preco * ($ipi/100)) : $preco + ($preco * 0.4);
				}
//IGOR HD 1916 - MOSTRAR PREÇO SUGERIDO AO POSTO A PARTIR DE UMA TABELA DE PORCENTAGEM DE LUCRO(TBL_FATOR_MULTIPLICACAO)
				//if ($login_fabrica = 3) {
				//HD 276320
				if (isFabrica(3,81)) {
					$sql= "SELECT porcentagem_fator
						   FROM tbl_fator_multiplicacao
						   WHERE  $preco >= valor_inicio
						      AND $preco <= valor_fim";

					$res_fat = pg_exec ($con,$sql);
					if (@pg_numrows($res_fat)>0)  {
						$porcentagem_fator	= trim(pg_result($res_fat,0,porcentagem_fator));
						$preco_sugerido_venda = $preco_com_ipi * $porcentagem_fator;
					}
				}
				if (isFabrica(35,125,128)) {
					$porcentagem_fator	= (isFabrica(125)) ? 1.538: 1.5; //hd 161451 50%
					$preco_sugerido_venda = $preco_com_ipi * $porcentagem_fator;
				}

				if (isFabrica(140)) {
					$preco_sugerido_venda = $preco + ($preco * 0.4);
				}

				if (isFabrica(1)) {
					$preco = $preco * $acrescimo_financeiro * $acrescimo_tabela_base;
				}

				if (!isFabrica(1) AND $tabela != 54) {
					if ($mostraTopo <> 'n'){
						if ($prox_refer <> $produto_referencia OR $prox_descr <> $produto_descricao) {
							flush();

							$colspan = 3;

							if ($liberar_preco)
							    $colspan += 2;
							if (isFabrica(11,88,172))
								$colspan +=1;
						    if (isFabrica(3,35,24,81,125,128,140,157))
							    $colspan += 1;
							if (isFabrica(3))
							    $colspan += 2;
					  	    if (isFabrica(11,172))
							    $colspan += 1;

							echo "<br><table width='700px' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
							    echo "<tr class='titulo_tabela'> ";
							        echo "<td colspan='{$colspan}'>$produto_referencia - $produto_descricao</td>";
							echo "</tr>";
							echo "<tr class='titulo_coluna'>";
							    echo "<td width='80px'>" . traduz('referencia', $con) . "</td>";
							    echo "<td width='*'>" . traduz(array('descricao','peca'), $con) . "</td>";
							    echo "<td width='40px'>UN</td>";
							    if (isFabrica(3)) { // HD 38821
							        echo "<td width='60px'>" . traduz('localizacao', $con) . "</td>";
							    }
							    if ($liberar_preco) {
							        if (isFabrica(88))
										echo "<td width='140px'>" . traduz('preco.sugerido.para.o.consumidor', $con) . "</td>";
								    else
								       echo "<td  width='60px'>" . traduz('preco', $con) . "</td>";

								    echo "<td  width='30px'>IPI % </td>";
								    if (isFabrica(3,35,24,81,128,125,140)){
										echo "<td width='140px'>" . traduz('preco.sugerido.para.venda', $con) . "</td>";
								    }

								    if (isFabrica(11,88,172)){
								    	echo "<td>" . traduz('imagem', $con) . "</td>";
								    }

								    if (isFabrica(11,172)) {
											echo "<td>". traduz('estoque', $con) ."</td>";
								    }

								    if (isFabrica(3) and 1 == 2) echo "<td width='80px'>" . traduz(array('preco','com.ipi'), $con) . " % </td>";
							    }

                                if (isFabrica(157)) {
                                    echo "<td align='center' nowrap> Desconto {$desconto_posto}% </td>";
                                }

							echo "</tr>";
							echo "<tbody>";

						}

						if (isFabrica(140,143,153,157) || ($sem_lista_basica == true)) {
							echo "<br><table width='700px' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
							echo "<tr class='titulo_coluna'>";
							echo "<td width='80px'>" . traduz('referencia', $con) . "</td>";
							echo "<td width='*'>" . traduz(array('descricao','peca'), $con) . "</td>";
							echo "<td width='40px'>UN</td>";
							if(in_array($login_fabrica,array(157))){
									echo "<td align='center' nowrap> Preco Sugerido p/ Venda</td>";
							}


							if(in_array($login_fabrica,array(157))){
							    echo "<td align='center' nowrap> Desconto % </td>";
							    echo "<td  width='60px'>Preço com Desconto</td>";
							} else {
								echo "<td  width='60px'>" . traduz('preco', $con) . "</td>";
							}
						
							echo "<td  width='30px'>IPI % </td>";
							if (isFabrica(11,88,172)){
						    	echo "<td>" . traduz('imagem', $con) . "</td>";
						    }elseif (!isFabrica(94, 143, 153, 157)) {
								echo "<td width='140px'>" . traduz('preco.sugerido.para.venda', $con) . "</td>";
							}
							if (isFabrica(11,172)) {
									echo "<td>". traduz('estoque', $con) ."</td>";
							}

							echo "</tr>";
							echo "<tbody>";

						}

					}

				} else {

					if ($i == 0) {

						flush();

						echo "<br><table width='700px' align='center' border='0' cellpadding='0' cellspacing='1' class='tabela'>";
						echo "<tr class='titulo_coluna'>";
						    echo "<td width='80px'>" . traduz('referencia', $con) . "</td>";
						    echo "<td width='*'>" . traduz(array('descricao','peca'), $con) . "</td>";
						    echo "<td width='40px'>UN</td>";

						    if (isFabrica(3))
							    echo "<td width='60px'>" . traduz('localizacao', $con) . "</td>";

						    if ($liberar_preco) {

							    echo "<td width='60px'>" . traduz('preco', $con) . "</td>";
							    echo "<td width='30px'>IPI % </td>";
							    if (isFabrica(3,35,24,81,125,128,140))
								    echo "<td width='140px'>" . traduz('preco.sugerido.para.venda', $con) . "</td>";
							    if (isFabrica(3) and 1 == 2)
									echo "<td width='80px'>" . traduz(array('preco','com.ipi'), $con) . "</td>";

						    }

						echo "</tr>";

					}

				}

                $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr bgcolor='$cor'>";
				    echo "<td>{$peca_referencia}</td>";
				    echo "<td>{$peca_descricao}</td>";
				    echo "<td>{$unidade}</td>";

				    if (isFabrica(3))
				    	echo "<td>{$posicao}</td>";

				    if ($liberar_preco) {


						if (isFabrica(24)) {
							#$preco_suggar = $preco + ($preco * 0.2); //COMENTADO NO HD-2784232
                            $preco_suggar = $preco;
                            echo "<td align='right'>".number_format ($preco_suggar - ($preco_suggar * 0.4),2,",",".")."</td>";
						} else {
							echo "<td align='right'>".number_format ($preco,2,",",".")."</td>";
						}

						if($login_fabrica <> 157) {
							echo "<td align='right'>{$ipi}</td>";
						}

					    if (isFabrica(3,35,24,81,125,128,140)){

					    	if (isFabrica(128) AND $peca_id == 1490963) {
					    		$preco_sugerido_venda = 79.90;
					    	}
					    	echo "<td align='right'>".number_format ($preco_sugerido_venda,2,",",".")."</td>";
					    }

					    if (isFabrica(3) and 1 == 2)
						    echo "<td align='right'>".number_format ($preco_com_ipi,2,",",".")."</td>";

						if (isFabrica(11,88,172)) {

							$caminho = "imagens_pecas/$login_fabrica";
							$diretorio_verifica=$caminho."/pequena/";
							echo "<td>";
								$xpecas  = $tDocs->getDocumentsByRef($peca_id, "peca");
								if (!empty($xpecas->attachListInfo)) {

									$a = 1;
									foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
									    $fotoPeca = $vFoto["link"];
									    if ($a == 1){break;}
									}
									echo "<a href=\"javascript:mostraPeca('$fotoPeca','$peca_id')\">";
									echo "<img src='$fotoPeca' width='50'>";
									echo "</a>";
								} else {
									if (is_dir($diretorio_verifica) == true) {

										$contador=0;

										if ($dh = opendir($caminho."/pequena/")) {
											while (false !== ($filename = readdir($dh))) {
												if (strlen($peca_id) > 0)  {
													if($contador == 1) break;
													if (strpos($filename,$peca_id) !== false){
														$po = strlen($peca_id);
														$contador++;
														echo "<a href=\"javascript:mostraPeca('$filename','$peca_id')\">";
														echo "<img src='$caminho/pequena/$filename'>";
														echo "</a>";
													} 
												}
											}

										}

									}
								}
							echo "</td>";

						}

						if (isFabrica(11,172)) {
							$sql_estoque = "SELECT tbl_posto_estoque.qtde as qtde_estoque FROM tbl_peca 
									JOIN tbl_posto_estoque on tbl_posto_estoque.peca = tbl_peca.peca
									WHERE tbl_peca.referencia = '$peca_referencia' and tbl_peca.fabrica in (11,172) and tbl_posto_estoque.qtde > 0 ";
							$res_estoque = pg_query($con, $sql_estoque);
							if(pg_num_rows($res_estoque)>0){
								$qtde_estoque = pg_fetch_result($res_estoque, 0, 'qtde_estoque');
							}

							if ($qtde_estoque > 0) {
								$estoque = "<font color='green'>DISPONÍVEL</font>";
							} else {
								$estoque = "<font color='red'>INDISPONÍVEL</font>";
							}

							echo "<td align='right'>".$estoque."</td>";
						}

				    }

                    if (isFabrica(157)) {
                    	echo "<td align='right'>".number_format($desconto_posto,0,',','.')."</td>";
                        echo "<td align='center'> ".number_format($preco_desconto_posto, 2, ",", ".")." </td>";
						echo "<td align='right'>{$ipi}</td>";
                    }

				echo "</tr>";

			}

			echo "</table>";

		}

	}

	if (isFabrica(88))
		echo "<div class='aviso'>" . traduz('desconto.para.assistencia.tecnica.de.%', $con, $cook_idioma, '40%') . "</div>";
}

include "rodape.php"; ?>
