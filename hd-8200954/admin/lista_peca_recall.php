<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

use model\ModelHolder;

//error_reporting(E_ALL);
//ini_set('display_errors',1);

if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
    require_once "../class/importa_arquivos/ImportaArquivo.php";
}

$fabrica_cadastra_lbm_excel = in_array($login_fabrica, array(40, 46));
$fabrica_cadastra_lbm_txt = in_array($login_fabrica, array(1));

if($_GET['produto']){
	$produto = $_GET['produto'];
}


if(isset($_POST["btn_gravar"])){

	$produto 		= $_POST["produto"];
	$serie_inicial 	= $_POST["serie_inicial"];
	$serie_final 	= $_POST["serie_final"];

	$sql_delete = "DELETE FROM tbl_produto_recall 
					WHERE produto = $produto 
					AND serie_inicial = '$serie_inicial'
					AND $serie_final = '$serie_final' ";
	$res_delete = pg_query($con, $sql_delete);
//	echo nl2br($sql_delete);
	$total = (int)$_POST["total_itens"];

	for($i=0; $i<$total_itens; $i++){
		if(isset($_POST["pecas_$i"])){
			$peca = $_POST["pecas_$i"];
			$sql_recall = "INSERT INTO tbl_produto_recall (produto, fabrica, serie_inicial, serie_final, peca, admin) 
							VALUES ($produto, $login_fabrica, $serie_inicial, $serie_final, $peca, $login_admin) ";
			//echo $sql_recall. "<Br><Br>";
			$res_recall = pg_query($con, $sql_recall);
		}
	}
	if(strlen(trim(pg_last_error($con)))==0){
		$ok = "Peças para recall cadastradas com sucesso";
	}
}


if ($_POST["btn_acao"] == "pesquisar" OR !empty($produto)) {

	if (strlen($produto) > 0) {
		$sql = "SELECT  tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_produto.voltagem
				FROM    tbl_produto
				WHERE   tbl_produto.produto = $produto
				AND     tbl_produto.fabrica_i = $login_fabrica";
		
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$referencia_produto = pg_fetch_result($res,0,'referencia');
			$descricao_produto  = pg_fetch_result($res,0,'descricao');
			if ($login_fabrica == 1){
				$voltagem  = pg_fetch_result($res,0,'voltagem');
				$descricao = $descricao." ".$voltagem;
			}
		}
	}else{

		$produto = $_POST['produto'];
		$produto_referencia = $_POST['produto_referencia'];
		$produto_descricao 	= $_POST['produto_descricao'];

		if(empty($produto_referencia)){
			$msg_erro['msg'][] = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "produto";
		}

		if(count($msg_erro) == 0){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao ,
							tbl_produto.voltagem
					FROM tbl_produto
					WHERE referencia = '$produto_referencia'
					AND   fabrica_i  = $login_fabrica";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0){
				$msg_erro['msg'][] = "Referencia $produto_referencia não encontrada";
				$msg_erro["campos"][] = "produto";
			}else{
				$produto 			= pg_fetch_result($res,0,'produto');
				$referencia_produto = pg_fetch_result($res,0,'referencia');
				$descricao_produto  = pg_fetch_result($res,0,'descricao');
				if ($login_fabrica == 1){
					$voltagem  = pg_fetch_result($res,0,'voltagem');
					$descricao = $descricao." ".$voltagem;
				}
			}
		}
	}

}

##### DUPLICAR PEÇAS P/ NOVO TYPE P/ BLACK & DECKER #####
if ($_POST['btn_acao'] == "duplicartype" && ($login_fabrica == 1 or $login_fabrica == 51)) {
	$produto               = $_POST["produto"];
	$type_duplicar_origem  = $_POST["type_duplicar_origem"];
	$type_duplicar_destino = $_POST["type_duplicar_destino"];

	if (strlen($type_duplicar_origem) == 0)  $msg_erro['msg'][] = " Selecione o \"Type Origem\" p/ duplicar. ";
	if (strlen($type_duplicar_destino) == 0) $msg_erro['msg'][] = " Selecione o \"Type Destino\" p/ duplicar. ";

	if ($type_duplicar_origem == $type_duplicar_destino) $msg_erro['msg'][] = " Selecione o \"Type Destino\" diferente do \"Type Origem\". ";

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type    = '$type_duplicar_origem';";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) $msg_erro['msg'][] = " Não foi encontrado lista básica p/ este produto com o Type Origem \"$type_duplicar_origem\". ";

		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type    = '$type_duplicar_destino';";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) $msg_erro['msg'][] = " Type Destino \"$type_duplicar_destino\" já cadastrado na lista básica p/ este produto. ";

		if (count($msg_erro['msg']) == 0) {
			$sql =	"INSERT INTO tbl_lista_basica (
						fabrica       ,
						posicao       ,
						ordem         ,
						serie_inicial ,
						serie_final   ,
						qtde          ,
						peca          ,
						produto       ,
						type          ,
						admin         ,
						data_alteracao
					)	SELECT  fabrica                          ,
								posicao                          ,
								ordem                            ,
								serie_inicial                    ,
								serie_final                      ,
								qtde                             ,
								peca                             ,
								produto                          ,
								'$type_duplicar_destino' AS type ,
								$login_admin                     ,
								current_timestamp
						FROM tbl_lista_basica
						WHERE fabrica = $login_fabrica
						AND   produto = $produto
						AND   type    = '$type_duplicar_origem';";
			$res = @pg_query($con,$sql);
			if(pg_last_error($con)){
				$msg_erro['msg'][] = pg_last_error($con);
			}

			if (count($msg_erro['msg']) == 0) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
				header ("Location: $PHP_SELF?produto=$produto&msg=type");
				exit;
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}


if(!empty($produto)){
	if ($login_fabrica == 45) {
		$slt_preco  = " tbl_tabela_item.preco                 ,";
		$join_preco = " LEFT JOIN tbl_tabela_item USING (peca) ";
	}

	$sql = "SELECT		$slt_preco
						tbl_lista_basica.peca AS peca_de_verdade,
						tbl_lista_basica.lista_basica  ,
						tbl_lista_basica.ordem         ,
						tbl_lista_basica.posicao       ,
						tbl_lista_basica.serie_inicial ,
						tbl_lista_basica.serie_final   ,
						tbl_lista_basica.qtde          ,
						tbl_lista_basica.type          ,
						tbl_lista_basica.somente_kit   ,
						tbl_lista_basica.ativo         ,
						tbl_peca.referencia            ,
                        tbl_peca.descricao             ,
						tbl_peca.garantia_diferenciada ,
						(select tbl_peca.descricao from tbl_peca where tbl_peca.peca = tbl_lista_basica.peca_pai) as descricao_pai,
						(select tbl_peca.referencia from tbl_peca where tbl_peca.peca = tbl_lista_basica.peca_pai) as referencia_pai,
						tbl_lista_basica.peca_pai,
						tbl_peca.peca
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca)
				$join_preco
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto ";

	$order_by = trim($_GET['ordem']);

	if (strlen($order_by) == 0) {

		if ($login_fabrica == 1 or $login_fabrica == 51) $sql .= "ORDER BY tbl_lista_basica.type, tbl_lista_basica.ordem";
		elseif ($login_fabrica == 45)                    $sql .= "ORDER BY tbl_lista_basica.ordem"; // HD 8226 Gustavo
		elseif (in_array($login_fabrica,array(11,15,50)))                    $sql .= "ORDER BY tbl_peca.descricao"; // HD 8226 Gustavo
		else                                             $sql .= "ORDER BY tbl_peca.referencia, tbl_peca.descricao";

	} else {

		switch ($order_by) {
			case 'referencia':	$sql .= "ORDER BY tbl_peca.referencia";      break;
			case 'descricao':	$sql .= "ORDER BY tbl_peca.descricao";       break;
			case 'posicao':		$sql .= "ORDER BY tbl_lista_basica.posicao"; break;
			case 'qtde':		$sql .= "ORDER BY tbl_lista_basica.qtde";    break;
			case 'ordem':		$sql .= "ORDER BY tbl_lista_basica.ordem";   break;
			case 'preco':	    $sql .= "ORDER BY tbl_tabela_item.preco";    break;
		}

	}
	#echo nl2br($sql);
	$resLista = pg_query ($con,$sql);
	if(pg_last_error($con)){
		$msg_erro['msg'][] = pg_last_error($con);
	}

	//INÍCIO -- Verificação de Vista Explodida
	$sql = "SELECT DISTINCT comunicado,extensao
			FROM tbl_comunicado
			LEFT JOIN tbl_comunicado_produto USING(comunicado)
			WHERE fabrica=$login_fabrica
			AND (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
			AND tipo = 'Vista Explodida' ORDER BY comunicado DESC LIMIT 1";
	$res = pg_query($con,$sql);
	if(pg_last_error($con)){
		$msg_erro['msg'][] = pg_last_error($con);
	}

	if (pg_num_rows($res) > 0) {
		$vista_explodida = pg_fetch_result($res,0,'comunicado');
		$ext             = pg_fetch_result($res,0,'extensao');
	}

	if ($S3_sdk_OK) {
		include_once S3CLASS;
		if ($S3_online)
			$s3 = new anexaS3('ve', (int) $login_fabrica);
	}
	if (strlen($vista_explodida) > 0) {
		$linkVE = null;
		if ($S3_online) {
			if ($s3->temAnexos($vista_explodida))
				$linkVE = $s3->url;
		} else {
//			echo '../comunicados/'.$vista_explodida.'.'.$ext;
			if (file_exists ('../comunicados/'.$vista_explodida.'.'.$ext)) {
				$linkVE = "../comunicados/$vista_explodida.$ext";
			}
		}
	}

	//FIM -- Verificação de Vista Explodida

	// INÍCIO -- Verifica alteração
	$sql = " SELECT tbl_admin.login,to_char(tbl_lista_basica.data_alteracao,'DD/MM/YYYY HH24:MI') as data_alteracao2, data_alteracao
			FROM tbl_lista_basica
			JOIN tbl_admin USING(admin)
			WHERE produto = $produto
			AND   tbl_lista_basica.admin IS NOT NULL
			AND   tbl_lista_basica.data_alteracao IS NOT NULL
			ORDER BY data_alteracao desc limit 1";
	$res = pg_query($con,$sql);
	if(pg_last_error($con)){
		$msg_erro['msg'][] = pg_last_error($con);
	}

	if (pg_num_rows($res) > 0) {
		$login_alt 		= pg_fetch_result($res,0,'login');
		$data_alteracao = pg_fetch_result($res,0,'data_alteracao2');
	}
	//FIM -- Verifica alteração
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE RECALL";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"shadowbox",
	"alphanumeric",
	"price_format"
);

include("plugin_loader.php");

if(empty($qtde_linhas) OR $qtde_linhas == 0){
	$qtde_linhas = 1;
}

//Array de Legendas (cor => titulo)

$arrayLegenda = array(array(
						"cor" => "#91C8FF",
						"titulo" => "Peça Alternativa",
					  ),
					  array(
						"cor" => "#5BB75B",
						"titulo" => "De-Para"
					  )
				);
if($login_fabrica == 14){
	$arrayLegenda[] = array("cor" => "#F2ED84",
							"titulo" => "Peça Inativa"
							);
}

?>

<style>
.emptyLine td {
	background-color: #F00 !important;
}
</style>
<link type="text/css" href="../js/pikachoose/css/css3.css" rel="stylesheet" />		
<script type="text/javascript" src="../js/pikachoose/js/jquery.jcarousel.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.touchwipe.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.pikachoose.js"></script>
<link href="../js/imgareaselect/css/imgareaselect-default.css" rel="stylesheet" type="text/css"/>
<link href="../js/imgareaselect/css/imgareaselect-animated.css" rel="stylesheet" type="text/css"/>		
<script type="text/javascript" src="../js/imgareaselect/js/jquery.imgareaselect.js"></script>		
<script type="text/javascript" src="../js/ExplodeView.js"></script>
<script type="text/javascript" src="../js/jquery.form.js"></script>

<script type="text/javascript" src="plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.autocompleteLoad(Array("produto"), Array("produto"));
		Shadowbox.init();

		$(document).on("focus","input[name^=qtde_],input[name^=ordem_]",function () {
			$(this).numeric({'allow':','});
		});

		$(document).on("click", "span[rel=lupa]", function () {
			$.lupa($(this),Array('posicao'));
		});

		$(".fixedTableHeader").fixedtableheader();

		$(document).on("blur", "table.pecas > tbody > tr > td input", function (e, input) {
			var linha = $(this).parents("tr");

			var peca_ref = $.trim($(linha).find("input[name^=peca_referencia_]").val());
			var peca_des = $.trim($(linha).find("input[name^=peca_descricao_]").val());
			var qtde     = $.trim($(linha).find("input[name^=qtde_]").val());

			if (qtde.length > 0 && peca_ref.length > 0 && peca_des.length > 0) {
				//alert("verificalinhas");
				verificaLinhas();
			}
		});;

		$(document).on("click","button[id^=remove_linha_]",function(){
			var linha = $(this).parents("tr");
			var lbm   = $(linha).find("input[name^=lbm_]").val();
			var produto = $(this).parents("table").find("input[name=produto]").val();
			$.ajax({
				url: "lbm_cadastro.php?ajax_remove=sim&lbm="+lbm+"&produto="+produto,
				complete: function(data){
					if(data.responseText == "ok"){
						$(linha).html("<td colspan='100%' class='tac'><div class='alert-success'>Item excluído com sucesso</div></td>");
						setTimeout(function(){
							$(linha).hide();
						},1000);
					}else{
						alert(data.responseText);
					}
				}
			});
		});

		$(document).on("click","button[id^=gravar_linha_]",function(){
			var linha 		    = $(this).parents("tr");
			var produto 	    = $(this).parents("table.pecas").find("input[name=produto]").val();
			var lbm 		    = $(linha).find("input[name^=lbm_]").val();
			var ordem 		    = $(linha).find("input[name^=ordem_]").val();
			var posicao 	    = $(linha).find("input[name^=posicao_]").val();
			var referencia 	    = $(linha).find("input[name^=peca_referencia_]").val();
			var qtde 		    = $(linha).find("input[name^=qtde_]").val().replace(",",".");
			var ativo 		    = $(linha).find("input[name^=ativo_]:checked").val();
			var somente_kit	    = $(linha).find("input[name^=somente_kit_]:checked").val();
			var unica_os	    = $(linha).find("input[name^=unica_os_]:checked").val();
            var type            = $(linha).find("select[name^=type_]").val();
			var desgaste        = $(linha).find("input[name^=desgaste_]").val();
			var peca_pai 	    = $(linha).find("input[name^=peca_pai_]").val();
			var serie_inicial   = $(linha).find("input[name^=serie_inicial_]").val();
			var serie_final     = $(linha).find("input[name^=serie_final_]").val();
			<? if(in_array($login_fabrica, array(151))){ ?>
				var versao     = $(linha).find("input[name^=versao_]").val();
			<? } ?>

			if(referencia != "" && qtde != ""){
				$.ajax({
					url: "lbm_cadastro.php",
					type: "POST",
					data: {
                        ajax_item:'sim',
                        lbm 			: lbm,
                        produto 		: produto,
                        ordem 			: ordem,
                        posicao 		: posicao,
                        peca_referencia : referencia,
                        qtde            : qtde,
                        desgaste        : desgaste,
                        ativo 			: ativo,
                        somente_kit 	: somente_kit,
                        unica_os 	    : unica_os,
                        type 			: type,
                        peca_pai 		: peca_pai,
                        serie_inicial 	: serie_inicial,
                        <? if(in_array($login_fabrica, array(151))){ ?>
                        	versao : versao,
                    	<? } ?>
                        serie_final 	: serie_final
                    },
                })
                .done(function(data){
                    data = data.split('|');
                    if(data[0] == "ok"){
                        $(linha).find("input[name^=lbm_]").val(data[1]);
                        $(linha).find("button[id^=gravar_linha_]").hide();

                        $(linha).find(".alert-success").show();
                        setTimeout(function(){
                            $(linha).find(".alert-success").hide();
                            $(linha).find("button[id^=remove_linha_]").show();
                        },1000);
                    } else {
                        alert(data[1]);
                    }
                });
			}
		});

		$("table.pecas > tbody > tr > td input,table.pecas > tbody > tr > td select").change(function(){
			var linha = $(this).parents("tr");
			var lbm = $(linha).find("input[name^=lbm_]").val();

			if(lbm != ""){
				$(linha).find("button[id^=remove_linha_]").hide();
				$(linha).find("button[id^=gravar_linha_]").show();
			}
		});

		$("input[id^=versao_]").keyup(function(){
			var letra = jQuery.trim($(this).val());
			
	        letra = letra.replace(/.|-|/gi,''); // elimina .(ponto), -(hifem) e /(barra)
	        
	        var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
		});
	});

	function verificaLinhas () {
		var create = true;
		var inputFocus;

		$("table.pecas > tbody > tr[id!=linhaModelo]").each(function () {
			var peca_ref = $.trim($(this).find("input[name^=peca_referencia_]").val());
			var peca_des = $.trim($(this).find("input[name^=peca_descricao_]").val());
			var qtde     = $.trim($(this).find("input[name^=qtde_]").val());
			//console.log(peca_ref+" "+peca_des+" "+qtde);

			// if (qtde.length == 0 || peca_ref.length == 0 || peca_des.length == 0) {
			// 	inputFocus = $(this).find("input:first");
			// 	create = false;
			// 	return false;
			// }
		});
		// console.log(create);
		// alert("verificando "+create);
		if (create == true) {
			var qtde_linhas = $("table.pecas > tbody > tr[id!=linhaModelo]").length;
			//alert(qtde_linhas);
			$("input[name=qtde_linhas]").val(qtde_linhas);
			var newTr = $("#linhaModelo").clone();
			$("table.pecas > tbody > tr[id!=linhaModelo]:last").after("<tr>"+$(newTr).html().replace(/__model__/g, qtde_linhas)+"</tr>");
		} else {
			if( $(inputFocus).parents("tr").next("tr[id!=linhaModelo]").length > 0 ){
				var scroll = $(inputFocus).offset();
				$(document).scrollTop(parseInt(scroll.top) - 50);

				$(inputFocus).parents("tr").find("input").bind("verifica", function () {
					var linha = $(this).parents("tr");

					var peca_ref = $.trim($(linha).find("input[name^=peca_referencia_]").val());
					var peca_des = $.trim($(linha).find("input[name^=peca_descricao_]").val());
					var qtde     = $.trim($(linha).find("input[name^=qtde_]").val());

					if (qtde.length > 0 && peca_ref.length > 0 && peca_des.length > 0) {
						$(this).unbind("verifica");
					}
				}).blur(function () {
					$(this).trigger("verifica");
				});
			}
		}
	}


	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca(retorno){
		var posicao = retorno.posicao;
        $("#peca_referencia_"+posicao).val(retorno.referencia);
		$("#peca_descricao_"+posicao).val(retorno.descricao);
    }

    function fnc_impresssao(produto) {
		var url = "";
		url = "lbm_cadastro_impressao.php?produto="+produto;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=920, height=500, top=18, left=0");
		janela.focus();
	}

	function jsFnVistaExplodidaClick(event){
		console.debug(event);
	}

</script>

<style type="text/css">
	.table-produto div{
		text-align: center !important;
	}

	.container_legenda{
		margin-bottom: 4px;
		border: solid #e2e2e2 1px;
		border-radius: 4px;
		padding: 5px;
	}

	#linhaModelo{
		display: none;
	}

	.titulo_legenda{
		font-size: 12px;
		font-weight: bold;
		text-align: left;
		padding-left: 2px;
		padding-right: 10px;
	}

	.cor_legenda{
		width: 10px !important;
		height: 10px !important;
		padding: 5px !important;
	}

	.valign-center {
		vertical-align: top !important;
	}

	.valign-center div{
		margin-bottom: 0px !important;
	}

	.valign-center span{
		color: #FFFFFF !important;
		margin-bottom: 0px !important;
		margin-left: 20px;
	}

	.pecaAlternativa td{
		background-color: #91C8FF !important;
	}

	.pecaDePara td{
		background-color: #5BB75B !important;
	}

	.pecaInativa td{
		background-color: #F2ED84 !important;
	}

	table.pecas{
		margin: 0 auto !important;
	}

	i{
		cursor: pointer;
	}

	.icon-edit,.icon-remove-sign{
		display: none;
		float:left;
		padding: 3px;
	}

	button.atualiza{
		display: none;
	}
	a{
		text-decoration: underline;
		color:#1E90FF;
	}
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if ($_GET['msg']) {
	$msg = array(	"gravar" 	=> "Itens gravados com sucesso",
					"exclui" 	=> "Lista básica excluída com sucesso",
					"importar" 	=> "Itens importados com sucesso",
					"type" 		=> "Type duplicado com sucesso",
					"duplicar" 	=> "Lista básica duplicada com sucesso");
?>
    <div class="alert alert-success">
		<h4><?=$msg[$_GET['msg']]?></h4>
    </div>
<?php
}
?>

<?php

if(isset($_GET["produto"])){
	$produto = (int)$_GET["produto"];
	$serie_inicial_ = (int)$_GET["serie_inicial"];
	$serie_final_ = (int)$_GET["serie_final"];
	$acao = $_GET["acao"];
}

if(!empty($produto) and !empty($serie_inicial) and $acao != 'excluir'){
	$sql = "SELECT peca FROM tbl_produto_recall 
			WHERE fabrica = $login_fabrica 
			AND produto = $produto
			AND serie_inicial = '$serie_inicial'
			AND serie_final = '$serie_final' ";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$dados_pecas = array();
		for($b=0; $b<pg_num_rows($res); $b++){
			$peca = pg_fetch_result($res, $b, peca);
			$dados_pecas[$peca] = $peca;
		}
	}
}

if($acao == "excluir"){

	$sql_delete = "DELETE FROM tbl_produto_recall 
					WHERE produto = $produto 
					AND serie_inicial = '$serie_inicial_'
					AND $serie_final = '$serie_final_' ";
	$res_delete = pg_query($con, $sql_delete);
					

}

if(empty($produto)){
?>
	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

	<form name='frm_lbm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<input type="hidden" name="produto" id="produto_id" value="<?=$produto?>" />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'pesquisar');">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
	</form>
<?php
}else{

?>

	<div class="container table-produto">
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_tabela' >
					<th colspan="2">Produto</th>
				</tr>
				<tr class='subtitulo_tabela' >
					<th>Referência</th>
					<th>Descrição</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $referencia_produto; ?></td>
					<td><?php echo $descricao_produto; ?></td>
				</tr>
			</tbody>
		</table>
	<div>

	<?php if(strlen(trim($ok))>0){ ?>
		<div class='alert-success'>
			<h4><?php echo $ok ?></h4>
		</div>
	<?php } ?>

	<?php
		$sql = "SELECT serie_inicial, serie_final, produto FROM tbl_produto_recall 
				WHERE fabrica = $login_fabrica 
				AND produto = $produto
				GROUP BY serie_inicial, serie_final,produto
				ORDER BY serie_inicial ";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
	?>
	<div class="container table-produto">
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_tabela' >
					<th colspan="3">Números de Série Cadastrados Para Este Produto</th>
				</tr>
				<tr class='subtitulo_tabela' >
					<th>Inicial</th>
					<th>Final</th>
					<th class="span1">Ação</th>
				</tr>
			</thead>
			<tbody>
			<?php
				for($a=0; $a<pg_num_rows($res); $a++){
					$serie_inicial 	= pg_fetch_result($res, $a, serie_inicial);
					$serie_final   	= pg_fetch_result($res, $a, serie_final);
					$produto   		= pg_fetch_result($res, $a, produto);

					echo "<tr>";
						echo "<td> <a href='?serie_inicial=$serie_inicial&serie_final=$serie_final&produto=$produto'>$serie_inicial</a></td>";
						echo "<td> <a href='?serie_inicial=$serie_inicial&serie_final=$serie_final&produto=$produto'>$serie_final</a></td>";
						echo "<td> <a href='?serie_inicial=$serie_inicial&serie_final=$serie_final&produto=$produto&acao=excluir' class='btn btn-danger btn-small'>Excluir</a></td>";
					echo "</tr>";

				}
			?>


		
			</tbody>
		</table>
	<div>
<?php
	}
	if (strlen ($produto) > 0 and ($login_fabrica == 11 or $login_fabrica == 20 or $login_fabrica == 46)) {
?>
		<div class="btn_excel">
			<span><img src='imagens/excel.png' /></span>
			<span class="txt" onclick="window.open('lbm_cadastro_xls.php?produto=<?=$produto?>');">Gerar Arquivo Excel</span>
		</div> <br />
<?php
	}


	if(isFabrica(46)):
		$model = ModelHolder::init('Produto');
		$explodeViews = $model->getExplodeViewImages($produto);
		$model = ModelHolder::init('ListaBasica');
		$basicLists = $model->find(array('produto'=>$produto));
		$model = ModelHolder::init('Peca');
	?>
	<div id="explodeView" class="ExplodeView">
		<?php foreach($explodeViews as $index => $explodeView):  ?>
			<img explode-view="<?php echo $index;?>" src="<?php echo $explodeView ?>" />
		<?php endforeach; ?>
		<?php if(empty($explodeViews)): ?>
		<br /><br />
		<?php endif; ?>	
		<?php foreach($basicLists as $basicList): ?>
			<?php
				$coords = array('vista'=>'1','x1'=>'0','x2'=>'0','y1'=>'0','y2'=>'0');
				$coordenadas = json_decode($basicList['coordenadas'],true);
				if(!is_array($coordenadas)){
					$coordenadas = array();
				}
				$coords = array_merge($coords,$coordenadas);
				$basicList['peca'] = $model->select($basicList['peca']);
			?>
			<input
				type="hidden"
				title="<?php echo $basicList['peca']['descricao'] ?>"
				href="#basic-list-<?php echo $basicList['listaBasica']; ?>"
				basic-list="<?php echo $basicList['listaBasica']; ?>"
				explode-view="<?php echo $coords['vista'] ?>"
				x1="<?php echo $coords['x1'] ?>"
				x2="<?php echo $coords['x2'] ?>"
				y1="<?php echo $coords['y1'] ?>"
				y2="<?php echo $coords['y2'] ?>"
			 />
		<?php endforeach; ?>
		</div>
	</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span6">
			<form id="explode-view-add" class="ajax-form" method="POST" action="vista_explodida_ajax.php" enctype="multipart/form-data">
				<input type="hidden" name="action" value="addVista" />
				<input type="hidden" name="produto" value="<?php echo $produto ?>" />
				<input type="hidden" name="fabrica" value="<?php echo $login_fabrica ?>" />
				<input type="file" name="vista" />
				<input class="btn btn-success" type="submit" value="Adicionar Vista" />
			</form>
		</div>
		<div class="span2">
		<form id="explode-view-remove" class="ajax-form" method="POST" action="vista_explodida_ajax.php" >
			<input type="hidden" name="action" value="removeVista" />
			<input type="hidden" name="produto" value="<?php echo $produto ?>" />
			<input type="hidden" name="fabrica" value="<?php echo $login_fabrica ?>" />
			<input type="hidden" name="vista" value="" />
			<input class="btn btn-danger" style="margin-top:35px" type="submit" value="Remover Vista" />
		</form>
		</div>
		<div class="span2"></div>
	</div>	
	<br />
	<?php endif; ?>
	<?php


	/*$vistaExplodida = new VistaExplodida($produto);
	$vistas = $vistaExplodida->getVistas();
	//if(!empty($vistas)){
		$element = new VistaExplodidaElement($vistaExplodida);
		$element->addListener('jsFnVistaExplodidaClick');	
		echo $element->toHTML();	
	//}*/
	if ($fabrica_cadastra_lbm_excel) { ?>

		<form name='frm_lbm_excel' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
			<div class='titulo_tabela '>Cadastrar Lista Básica com arquivo Excel (XLS)</div>
			<br/>
			<input type="hidden" name="produto_excel" value="<?=$produto?>" />
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8'>
					<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
						<?if($login_fabrica == 1) { ?>
						<label class='control-label' for='arquivo'>O Layout de arquivo deve ser igual o que está nessa tela. Não precisa de cabeçalho. <br />Será aceito apenas arquivos com extensão XLS.</label>
						<? }else{?>
						<label class='control-label' for='arquivo'>O Layout de arquivo deve ser Produto, Peça e Quantidade</label>
						<? } ?>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="file" id="arquivo" name="arquivo" class='span12' >
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<p><br/>
				<input type='hidden' value='<?=$produto?>' name='produto_excel'>
				<input type='hidden' name='btn_lista' value='listar'>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'importar');">Importar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p><br/>
		</form>
<?php
	}	
	if ($fabrica_cadastra_lbm_txt) { ?>

		<form name='frm_lbm_excel' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
			<div class='titulo_tabela '>Cadastrar Lista Básica com arquivo TXT</div>
			<br/>
			<input type="hidden" name="produto" value="<?=$produto?>" />
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span8'>
					<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='arquivo'>O Layout de arquivo deve ser igual o que está nessa tela. Não precisa de cabeçalho. <br />Será aceito apenas arquivos com extensão TXT ou CSV (colunas separadas por ";" ).</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="file" id="arquivo" name="arquivo" class='span12' >
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<p><br/>
				<input type='hidden' value='<?=$produto?>' name='produto_txt'>
				<input type='hidden' name='btn_lista' value='listar'>
				<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'importar_txt');">Importar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
			</p><br/>
		</form>
<?php
	}	
?>
	</div>
	<center><button class='btn' id="btn_acao" type="button"  onclick="javascript: window.location='<?echo $PHP_SELF?>?'">Nova Pesquisa</button></center>
	<br />
	</div>
<?php $i = 0;
	if(pg_num_rows($resLista) > 0) { ?>
		<form name='frm_lbm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline' >
			<div class="container">
				<table class='table table-striped table-bordered table-hover  table-large pecas'>
					<thead>
						<tr class="titulo_coluna">
							<th>Série Inicial</th>
							<th>Série Final</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="text-align:center"><input type="text" name="serie_inicial" value="<?=$serie_inicial_?>" class='span4' maxlength='25'></td>	
							<td style="text-align:center"><input type="text" name="serie_final" value="<?=$serie_final_?>"  class='span4' maxlength='25'></td>
						</tr>
					</tbody>
				</table>
				<br>
			</div>

			<table class='table table-striped table-bordered table-hover  table-large pecas' >
				<input type="hidden" name="produto" value="<?=$produto?>" />
				<input type="hidden" name="qtde_linhas" value="<?=$qtde_linhas?>" />
				<thead>
					<tr class="titulo_coluna">					
						<th>#</th>
						<th>Peça</th>
						<th>Descrição</th>
					</tr>
				</thead>
				<tbody>
<?php
	

		$total_itens = pg_num_rows($resLista);

		for($i = 0; $i < $total_itens; $i++){

			$lbm           = pg_fetch_result ($resLista,$i,'lista_basica');
			$ordem         = pg_fetch_result ($resLista,$i,'ordem');
			$posicao       = pg_fetch_result ($resLista,$i,'posicao');
			$serie_inicial = pg_fetch_result ($resLista,$i,'serie_inicial');
			$serie_final   = pg_fetch_result ($resLista,$i,'serie_final');
			$somente_kit   = pg_fetch_result ($resLista,$i,'somente_kit');
			$peca_de_verdade = pg_fetch_result ($resLista,$i,'peca_de_verdade');
			$peca          = pg_fetch_result ($resLista,$i,'referencia');
			$peca_pai      = pg_fetch_result ($resLista,$i,'referencia_pai');
			$descricao     = pg_fetch_result ($resLista,$i,'descricao');
			$descricao_pai = pg_fetch_result ($resLista,$i,'descricao_pai');
			$type          = pg_fetch_result ($resLista,$i,'type');

            $qtde          = pg_fetch_result ($resLista,$i,'qtde');
			$desgaste      = pg_fetch_result ($resLista,$i,'garantia_diferenciada');
			$ativo         = pg_fetch_result ($resLista,$i,'ativo');
			$xpeca         = pg_fetch_result ($resLista,$i,'peca');
			$xpeca_pai     = pg_fetch_result ($resLista,$i,'peca_pai');

			if ($login_fabrica == 45) {
				$preco = pg_fetch_result ($resLista,$i,'preco');
				$preco = number_format($preco, 2);
				$preco = str_replace(".",",",$preco);
			}

			$class = "";

			$sql = "SELECT  tbl_peca_alternativa.para
					FROM    tbl_peca_alternativa
					WHERE   tbl_peca_alternativa.para    = '$peca'
					AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) $class = "pecaAlternativa";

			$sql = "SELECT  tbl_depara.de,
							tbl_peca.descricao,
							tbl_peca.referencia
					FROM    tbl_depara
					JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de and tbl_peca.fabrica = $login_fabrica
					WHERE   tbl_depara.para    = '$peca'
					AND     tbl_depara.fabrica = $login_fabrica;";


			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$xpeca_de            = pg_fetch_result ($res1,0,'de');
				$xreferencia_peca_de = pg_fetch_result ($res1,0,'referencia');
				$xdescricao_peca_de  = pg_fetch_result ($res1,0,'descricao');
				$class = "pecaDePara";
			}else{
				$xpeca_de            = "";
				$xreferencia_peca_de = "";
				$xdescricao_peca_de  = "";
			}

			if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0) {
				$class = "pecaInativa";
			}

			$tamanho = ($login_fabrica == 6) ? "inptc7":"inptc2";
			if(strlen($ativo) == 0) $ativo = "";
?>
				<tr class="<?=$class?>">
					<td class="valign-center tac">
						<?php

							if(isset($dados_pecas["$xpeca"])){
								$checked = " checked ";
							}else{
								$checked = " ";
							}
						?>
						<input type="checkbox" id="pecas_<?=$i?>" name="pecas_<?=$i?>" class='inptc2' <?php echo $checked ?> value="<? echo $xpeca ?>" >
					</td>			

					<td class="valign-center">
						<input type='hidden' value="<?=$lbm?>" name="lbm_<?=$i?>" />
						<div class='input-append'>
							<input type="text" id="peca_referencia_<?=$i?>" name="peca_referencia_<?=$i?>" class='span3 inp-peca' maxlength="20" value="<? echo $peca ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" />
						</div>
						<? if($xpeca_de){ ?><br />
						<span><?=$xpeca_de?></span>
						<? } ?>
					</td>
					<td class="valign-center">
						<div class='input-append'>
							<input type="text" id="peca_descricao_<?=$i?>" name="peca_descricao_<?=$i?>" class='span4 inp-descricao' value="<? echo $descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao" />
						</div>					
					</td>	          

				</tr>
<?php

		}
?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="100%" class="tac">
						<input type="submit" class='btn' name="btn_gravar" value="Gravar">
						<input type="hidden" name="total_itens" value="<?=$total_itens?>">
					</td>
				</tr>
			</tfoot>			
		</table>
	</form>
<?php

	}else{
		echo "<div class='alert'><h4>Produto sem lista básica</h4></div>";
	}
	
?>		


<?php
}
?>
</div>
<script type="text/javascript">
	$(function(){
		$(document).on('submit','form#explode-view-remove.ajax-form',function(){
			window.loading("show");
			var explodeViewIndex = $(window.explodeView.getSelectView()).attr('explode-view');
			$(this).find("input[name='vista']").val(explodeViewIndex);
			$(this).ajaxSubmit({
				success : function(data){
					if(!data)
						return;
					window.explodeView.removeView(explodeViewIndex);
				},
				complete : function(){
					window.loading("hide");
				}
			});
			return false;
		});
		$(document).on('submit','form#explode-view-add.ajax-form',function(){
			window.loading("show");
			$(this).ajaxSubmit({
				success : function(data){
					if(!data)
						return;
					window.explodeView.putView(data.src,data.vista);
				},
				complete : function(){
					window.loading("hide");
				}
			});
			return false;
		});
	});



	$(document).on('submit','form.ajax-form',function(){
		window.loading('show');
		$(this).ajaxSubmit({
			complete : function(){
				window.loading('hide');
			}
		});
		return false;
	});
</script>
<?php
	include "rodape.php";
	function verificaPeca($referencia){
		global $con;
		global $login_fabrica;
		
		$sql = "SELECT peca FROM tbl_peca where referencia = '$referencia' AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			throw new Exception("Peça não encontrada. Referencia: " . $referencia);
		}

		return pg_fetch_result($res, 0, "peca");
	}
?>
