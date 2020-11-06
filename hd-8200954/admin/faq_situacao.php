<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_sucesso = $_GET['msg'];

$parametros = array('btn_acao','faq');

if(filter_input(INPUT_GET,"btn_acao")){
	$btn_acao = $_GET['btn_acao'];
}else if(filter_input(INPUT_POST,"btn_acao")){
	$btn_acao = $_POST['btn_acao'];
}

if(filter_input(INPUT_GET,"faq")){
	$faq = $_GET['faq'];
}else if(filter_input(INPUT_POST,"faq")){
	$faq = $_POST['faq'];
}

if($btn_acao == "excluirFaq"){
	$faq = $_POST["faq"];
	pg_query($con, "BEGIN TRANSACTION");

	$sql = "SELECT faq_solucao FROM tbl_faq_solucao WHERE faq = $faq";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$sql = "DELETE FROM tbl_faq_solucao WHERE faq = $faq";
		pg_query($con, $sql);

		if(pg_last_error()){
			pg_query($con,"ROLLBACK");
			$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao excluir as soluções do FAQ!"));
			echo json_encode($resultado); exit;
		}
	}

	$sql = "SELECT faq_causa FROM tbl_faq_causa WHERE faq = $faq";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$sql = "DELETE FROM tbl_faq_causa WHERE faq = $faq";
		pg_query($con,$sql);

		if(pg_last_error()){
			pg_query($con,"ROLLBACK");
			$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao excluir a(s) causa(s) do FAQ!"));
			echo json_encode($resultado); exit;
		}
	}

	$sql = "DELETE FROM tbl_faq WHERE faq = $faq";
	pg_query ($con,$sql);

	if(pg_last_error()){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao excluir a(s) causa(s) do FAQ!"));
		echo json_encode($resultado); exit;
	}

	pg_query($con,"COMMIT");
	$resultado = array("resultado" => true);
	echo json_encode($resultado); exit;

}

if($btn_acao == "excluirCausa"){
	$faq_solucao = $_POST['faq_causa'];
	$faq = $_POST['faq'];

	pg_query($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_faq_causa WHERE faq = $faq AND faq_causa = $faq_causa";
	pg_query($con, $sql);

	if(pg_last_error()){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao excluir a causa do FAQ!"));
		echo json_encode($resultado); exit;
	}
	pg_query($con,"COMMIT");
	$resultado = array("resultado" => true);
	echo json_encode($resultado); exit;
}

if($btn_acao == "excluirSolucao"){
	$faq_solucao = $_POST['faq_solucao'];
	$faq = $_POST['faq'];

	pg_query($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_faq_solucao WHERE faq = $faq AND faq_solucao = $faq_solucao";
	pg_query($con, $sql);

	if(pg_last_error()){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao excluir a solução do FAQ!"));
		echo json_encode($resultado); exit;
	}
	pg_query($con,"COMMIT");
	$resultado = array("resultado" => true);
	echo json_encode($resultado); exit;
}

if($btn_acao == "inserirFaqLinha"){
	$situacao = utf8_decode($_POST['situacao']);
	$linha = $_POST['linha'];
	
	pg_query($con, "BEGIN");

	$sql = "INSERT INTO tbl_faq (fabrica, situacao, linha) VALUES ($login_fabrica, '".$situacao."',$linha) RETURNING faq";
	$new_id = pg_query($con,$sql);

	if(pg_last_error() > 0){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao gravar no sistema!"));
	}else{
		pg_query($con,"COMMIT");
		$new_id = pg_fetch_result($new_id, 0, "faq");
		$resultado = array("resultado" => true, "faq" => $new_id);
	}
	echo json_encode($resultado); exit;
}

if($btn_acao == "updateFaqLinha"){
	$situacao = utf8_decode($_POST['situacao']);
	$linha    = $_POST['linha'];
	$faq      = $_POST['faq'];
	
	pg_query($con, "BEGIN TRANSACTION");

	$sql = "UPDATE tbl_faq SET situacao = '".$situacao."', linha = $linha WHERE faq = $faq";
	pg_query($con,$sql);

	if(pg_last_error() > 0){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao realizar a atualização das informações do Faq!"));
	}else{
		pg_query($con,"COMMIT");
		$resultado = array("resultado" => true);
	}
	echo json_encode($resultado); exit;
}

if($btn_acao == "inserirFaqProduto"){
	$situacao = utf8_decode($_POST['situacao']);
	$produto = $_POST['produto'];

	pg_query($con, "BEGIN");

	foreach ($produto as $chave => $referencia) {

		$sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia' and fabrica_i = $login_fabrica ";
		$res_produto = pg_query($sql_produto);
		if(pg_num_rows($res_produto)>0){
			$produto = pg_fetch_result($res_produto, 0, produto);

			$sql = "INSERT INTO tbl_faq (situacao, produto, fabrica) VALUES ('{$situacao}', {$produto}, {$login_fabrica}) RETURNING faq";
			$res = pg_query($con,$sql);
			if(strlen(pg_last_error($con))==0){
				$new_id[] = pg_fetch_result($res, 0, "faq");		
			}
		}else{
			$msg_erro[] .= "Produto não encontrado";
		}
	}

	if($login_fabrica == 42 and strlen(trim($produto))==0){
		$sql = "INSERT INTO tbl_faq (situacao, fabrica) VALUES ('{$situacao}', {$login_fabrica}) RETURNING faq";
		$res = pg_query($con,$sql);
		if(strlen(pg_last_error($con))==0){
			$new_id[] = pg_fetch_result($res, 0, "faq");		
		}
	}
	$faqs_id = implode("|", $new_id);

	if(count($msg_erro) > 0){
		pg_query($con,"ROLLBACK");
		$resultado[] = array("resultado" => false, "mensagem" => traduz("Erro ao gravar no sistema!"));
	}else{
		pg_query($con,"COMMIT");		
		$resultado = array("resultado" => true, "faq" => $faqs_id);
	}	 
	echo json_encode($resultado); exit;
}

if($btn_acao == "updateFaqProduto"){
	$situacao   = utf8_decode($_POST['situacao']);
	$referencia = $_POST['referencia'];
	$faq        = $_POST['faq'];

	if(strlen(trim($referencia))>0){
		$sql = "SELECT produto FROM tbl_produto 
			WHERE referencia_pesquisa = $referencia AND fabrica_i = $login_fabrica";
		$res = pg_query($con,$sql);
		$produto = pg_fetch_result($res, 0, "produto");

		$campos_produtos = " , produto = $produto ";
	}else{
		$campos_produtos = "";
	}
	pg_query($con, "BEGIN");

	$sql = "UPDATE tbl_faq SET situacao = '".$situacao."' $campos_produtos WHERE faq = $faq";
	pg_query($con,$sql);

	if(pg_last_error() > 0){
		pg_query($con,"ROLLBACK");
		$resultado[] = array("resultado" => false, "mensagem" => traduz("Erro ao realizar a atualização das informações do Faq!"));
	}else{
		pg_query($con,"COMMIT");
		$resultado = array("resultado" => true);
	}
	echo json_encode($resultado); exit;
}

if($btn_acao == "inserirSolucao"){
	$solucao   = utf8_decode($_POST['solucao']);
	$faq       = $_POST['faq'];
	$faq_causa = $_POST['causa'];
	
	pg_query($con, "BEGIN");

	$sql = "INSERT INTO tbl_faq_solucao (faq_causa, solucao, faq) VALUES ($faq_causa, '$solucao', $faq) RETURNING faq_solucao";
	$new_id = pg_query($con,$sql);

	if(pg_last_error() > 0){
		pg_query($con,"ROLLBACK");
		$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao gravar no sistema!"));
	}else{
		pg_query($con,"COMMIT");
		$new_id = pg_fetch_result($new_id, 0, "faq_solucao");
		$resultado = array("resultado" => true, "faq_solucao" => $new_id, "solucao" => utf8_encode($solucao));
	}
	echo json_encode($resultado); exit;
}

if($btn_acao == "buscaCausa"){
	$faq = $_POST['faq'];

	$sql = "SELECT faq_causa, causa FROM tbl_faq_causa WHERE faq = $faq ORDER BY causa";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		while($causa = pg_fetch_object($res)){
			$resultado[] = array("faq_causa" => $causa->faq_causa, "causa" => $causa->causa);
		}
	}else{
		$$resultado = "";
	}
	echo json_encode($resultado); exit;
}


if($btn_acao == "inserirCausa"){
	$causa = $_POST['causa'];
	$faq = $_POST['faq'];
	
	pg_query($con, "BEGIN");

	$sql = "INSERT INTO tbl_faq_causa (faq,causa)VALUES($faq,'$causa') RETURNING faq_causa";
	$new_id = pg_query($con,$sql);

	if(pg_last_error() > 0){
		pg_query($con,"ROLLBACK");
		$resultado[] = array("resultado" => false, "mensagem" => traduz("Erro ao gravar no sistema!"));
	}else{
		pg_query($con,"COMMIT");
		$new_id = pg_fetch_result($new_id, 0, "faq_causa");
		$resultado = array("resultado" => true, "faq_causa" => $new_id);
	}
	echo json_encode($resultado); exit;
}

if ($btn_acao == "gravar") {

	$faq     = $_POST['faq'];
	$causa   = $_POST['data']['causa'];
	$solucao = $_POST['data']['solucao'];

	if(strlen(trim($faq))==0 and $login_fabrica != 42){
		$resultado = array("resultado" => false, "mensagem" => "Informar o produto");
		echo json_encode($resultado); exit;
	}

	$faq = explode("|",$faq);

	$erro = false;
	pg_query ($con,"BEGIN TRANSACTION");

	if (count($causa) > 0) {
		foreach($faq as $chave => $idFaq){	
			foreach($causa as $key => $value) {
				$sql = "INSERT INTO tbl_faq_causa (faq,causa) VALUES ($idFaq,'".utf8_decode($value)."')";
				$res = pg_query($con,$sql);

				if(pg_last_error() > 0){
					pg_query($con,"ROLLBACK");
					$resultado= array("resultado" => false, "mensagem" => traduz("Erro ao gravar no sistema!"));
					$erro = true;
					break;
				}
			}
		}
	}	

	if($erro == true){
		echo json_encode($resultado); exit;
	}else{
		pg_query($con,"COMMIT");

		pg_query($con,"BEGIN TRANSACTION");
	}

	if (count($solucao) > 0) {
		$causa_descricao = "";
		foreach($faq as $chave => $idFaq){
			foreach($solucao as $key => $value) {
				if($causa_descricao != $value['causa']){
					$sql = "SELECT faq_causa FROM tbl_faq_causa WHERE faq = $idFaq AND causa = '".utf8_decode($value['causa'])."'";
					$res = pg_query($con,$sql);
					$faq_causa = pg_fetch_result($res, 0, "faq_causa");
					$causa_descricao = $value['causa'];
				}

				$sql = "INSERT INTO tbl_faq_solucao (faq_causa, solucao, faq) VALUES (".$faq_causa.", '".utf8_decode($value['solucao'])."', $idFaq)";
				$res = pg_query($con,$sql);

				if(pg_last_error() > 0){
					pg_query($con,"ROLLBACK");
					$resultado = array("resultado" => false, "mensagem" => traduz("Erro ao gravar a solução no sistema!"));
					$erro = true;
					break;
				}
			}
		}
	}

	if($erro == false){
		pg_query($con,"COMMIT");
		$resultado = array("resultado" => true);
	}

	echo json_encode($resultado); exit;

}

if ($faq != "") {

	if(in_array($login_fabrica, array(148))){
		$coluna = " tbl_linha.linha, tbl_linha.codigo_linha, tbl_linha.nome ";
		$join = " JOIN tbl_linha ON tbl_linha.linha = tbl_faq.linha AND tbl_linha.fabrica = $login_fabrica ";
	}else{
		$coluna = " tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia ";
		$join = " JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto AND tbl_produto.fabrica_i = $login_fabrica ";
	}

	$sql = "SELECT tbl_faq.situacao, $coluna FROM tbl_faq
				$join
			WHERE tbl_faq.faq = $faq";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		if(in_array($login_fabrica, array(148))){
			$situacao     = trim(pg_fetch_result($res,0,'situacao'));
			$linha        = trim(pg_fetch_result($res,0,'linha'));
			$codigo_linha = trim(pg_fetch_result($res,0,'codigo_linha'));
			$nome_linha   = trim(pg_fetch_result($res,0,'nome'));

		}else{
			$produto    = trim(pg_fetch_result($res,0,'produto'));
			$referencia = trim(pg_fetch_result($res,0,'referencia'));
			$descricao  = trim(pg_fetch_result($res,0,'descricao'));
			$situacao   = trim(pg_fetch_result($res,0,'situacao'));
		}

	}
}

$visual_black = "manutencao-admin";
$title        = traduz("CADASTRO DE PERGUNTAS FREQUENTES - FAQ");
$cabecalho    = traduz("Cadastro de Perguntas Frequentes - FAQ");
$layout_menu  = "cadastro";

include 'cabecalho_new.php';

$plugins = array(

	"autocomplete",
	"shadowbox"

);

include("plugin_loader.php");


if (count($lista_produtos) > 0){
	$display_um_produto    = "display:none";
	$display_multi_produto = "";
	$display_um            = "";
	$display_multi         = " CHECKED ";
}else{
	$display_um_produto    = "";
	$display_multi_produto = "display:none";
	$display_um            = " CHECKED ";
	$display_multi         = "";
}

?>

<script language="JavaScript">

$(function() {

	Shadowbox.init();

	//Auto complete
	//$.autocompleteLoad(Array("produto"));
	$.autocompleteLoad(["produto"], ["produto"]);

	/**
	* Evento que chama a função de lupa para a lupa clicada
	*/
	/**
	*$("span[rel=lupa]").click(function() {
	*	$.lupa($(this));
	*});
	**/
	$(document).on("click", "span[rel=lupa]", function () {
		$.lupa($(this),Array('posicao'));
	});

	<?php if(in_array($login_fabrica, array(148))){ ?>

		$("#nome_linha").click(function(){
			var codigo_linha = this.value.split("|");
			$("#codigo_linha").val(codigo_linha[1]);
			$("#linha").val(codigo_linha[0]);
		});
	<?php 
	} ?>
});

function retorna_produto (retorno) {

	<?php if($login_fabrica == 42){ ?>
		var radio_qtde_produtos = $('input[name=radio_qtde_produtos]:checked').val();
		
		if(radio_qtde_produtos == "muitos"){
			$("#produto_referencia_multi").val(retorno.referencia);
	    	$("#produto_descricao_multi").val(retorno.descricao);
		}else{
			$("#produto_id").val(retorno.produto);
			$("#referencia").val(retorno.referencia);
			$("#descricao").val(retorno.descricao);
		}
	<?php }else{ ?>
		$("#produto_id").val(retorno.produto);
		$("#referencia").val(retorno.referencia);
		$("#descricao").val(retorno.descricao);
	<?php } ?>

}

function fnc_pesquisa_produto (campo, tipo) {

	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_situacao.referencia;
		janela.descricao = document.frm_situacao.descricao;
		janela.focus();
	}else{
		alert('<?=traduz("Preencha toda ou parte da informação para realizar a pesquisa!")?>')
	}

}

function inserirFaqLinha(){
	var retorno;
	var dataAjax = {
		linha: $("#linha").val(),
		situacao: $("#situacao").val(),
		btn_acao: "inserirFaqLinha"
	}

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
        async: false
    }).done(function(data){
    	var value = JSON.parse(data);
    	// var value = $.parseJSON(data.responseText);
    	$("#faq").val(value.faq);
    	return true;

    }).fail(function(data){
    	var value = JSON.parse(data);
    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
		return false;
    });
}

function updateFaqLinha(){
	var retorno;
	var dataAjax = {
		faq: $("#faq").val(),
		linha: $("#linha").val(),
		situacao: $("#situacao").val(),
		btn_acao: "updateFaqLinha"
	}

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
        async: false
    }).done(function(data){
    	var value = JSON.parse(data);
    	return true;

    }).fail(function(data){
    	var value = JSON.parse(data);
    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
		return false;
    });
}

function inserirFaqProduto(){

	var produtos = [];
	<?php if($login_fabrica == 42){ ?>

		var radio_qtde_produtos = $('input[name=radio_qtde_produtos]:checked').val();
		
		if(radio_qtde_produtos == "muitos"){
			$('#PickList').find('option').each(function() {
			    produtos.push($(this).val());
			});

			if(produtos.length === 0){
				produtos = null; 
			}

		}else{
			produtos.push($("#referencia").val());
			if(produtos[0].length === 0){
				produtos = null; 		
			}
		}
		
	<?php }else{ ?>
		produtos.push($("#referencia").val());
	<?php } ?>

	var dataAjax = {
		produto: produtos,
		situacao: $("#situacao").val(),
		btn_acao: "inserirFaqProduto"
	}

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
        async: false
    }).done(function(data){
    	var value = JSON.parse(data);
    	$("#faq").val(value.faq);

    	return true;
    }).fail(function(data){

    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    		return false;
    	}
    });
}

function updateFaqProduto(){
	var dataAjax = {
		faq: $("#faq").val(),
		linha: $("#codigo_linha").val(),
		situacao: $("#situacao").val(),
		btn_acao: "updateFaqProduto"
	}

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
        async: false
    }).done(function(data){
    	var value = JSON.parse(data);
    	return true;
    }).fail(function(data){
    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    		return false;
    	}
    });
}

function adicionaCausa() {
	if($("#causa").val() == ""){
		$("#mensagem").html('<div class="alert alert-error"><h4><?=traduz("Preenche o campo Causa!")?></h4> </div>');
	}else{
		$("#mensagem").html('');
		var count = parseInt($("#count_causa").val());
		$("#count_causa").val(count+1);
    	var linha = "";
		linha += "<tr id='causa_"+$("#count_causa").val()+"'>\
			<td nowrap align='center'>\
				<span>"+$('#causa').val()+"</span>\
				<input type='hidden' id='status_causa_"+$("#count_causa").val()+"' value='nao_gravado'>\
			</td>\
			<td nowrap class='tac'>\
				<input type='button' class='btn btn-danger' alt='Excluir' title='Excluir' onclick='excluirCausa("+$("#count_causa").val()+");' value='<?=traduz("Excluir")?>'>\
			</td>\
		</tr>";
		$('.tbody_causa').append(linha);
    	atualizaSelectCausa($("#causa").val());
    	$("#causa").val("");
    	 // var json_string = JSON.stringify(objeto);
	}
}

function atualizaSelectCausa(causa){
	var option = $("<option></option>", {
		value: $("#count_causa").val(),
		text: causa
	});
	$('#causa_selecionada').append(option);
}

function adicionaSolucao(){
	if($("#solucao").val() == "" || $("#causa_selecionada option:selected").text() == ""){
		$("#mensagem_solucao").html('<div class="alert alert-error"><h4><?=traduz("Selecione uma causa e preencha o campo solução!")?></h4> </div>');
	}else{
		$("#mensagem_solucao").html('');
		var count = parseInt($("#count_solucao").val());
		$("#count_solucao").val(++count);
		var linha = "<tr id='solucao_"+$("#count_solucao").val()+"'>\
			<td id='solucao_causa_"+$("#causa_selecionada").val()+"' nowrap align='center'>\
				<span>"+$("#causa_selecionada option:selected").text()+"</span>\
				<input type='hidden' id='status_solucao_"+$("#count_solucao").val()+"' value='nao_gravado'>\
			</td>\
			<td nowrap align='center'>"+$('#solucao').val()+"</td>\
			<td nowrap class='tac'>\
				<input type='button' class='btn btn-danger' alt='Excluir' title='Excluir' onclick='excluirSolucao("+$("#count_solucao").val()+");' value='<?=traduz("Excluir")?>'>\
			</td>\
		</tr>";
		$('.tbody_solucao').append(linha);
		$("#solucao").val("");
	}
}

function excluirCausa(faq_causa){
	var aux = 0;
	$(".tbody_solucao tr td[id^=solucao_causa_]").each(function(){
		if(this.id == "solucao_causa_"+faq_causa){
			aux = 1;
			return false;
		}
	});

	if(aux == 0){
		$("#mensagem_causa").html('');
		if($("#status_causa_"+faq_causa).val() == "nao_gravado"){
			$('.tbody_causa tr[id=causa_'+faq_causa+']').remove();
			$("#causa_selecionada option[value='"+faq_causa+"']").remove();
		}else{
			var dataAjax = {
		        faq: $('#faq').val(),
		        faq_causa: faq_causa,
		        btn_acao: "excluirCausa"
		    };

			$.ajax({
		        url: '<?=$PHP_SELF?>',
		        type: 'POST',
		        data: dataAjax,
		    }).done(function(data){
		    	var value = JSON.parse(data);
		    	if(value.resultado == true){
			    	$('.tbody_causa tr[id=causa_'+faq_causa+']').remove();
			    	$("#causa_selecionada option[value='"+faq_causa+"']").remove();
		    	}else{
		    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
		    	}
		    }).fail(function(data){
		    	if(value.resultado == false){
		    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
		    	}
		    });
		}
	}else{
		$("#mensagem_causa").html('<div class="alert alert-error"><h4><?=traduz("Realize as exclusões das soluções registradas para esta causa!")?></h4> </div>');
	}
}

function excluirSolucao(faq_solucao){
	if($("#status_solucao_"+faq_solucao).val() == "nao_gravado"){
		$('.tbody_solucao tr[id=solucao_'+faq_solucao+']').remove();
	}else{
		var dataAjax = {
	        faq_solucao: faq_solucao,
	        faq: $("#faq").val(),
	        btn_acao: "excluirSolucao"
	    };

		$.ajax({
	        url: '<?=$PHP_SELF?>',
	        type: 'POST',
	        data: dataAjax,
	    }).done(function(data){
	    	var value = JSON.parse(data);
	    	if(value.resultado == true){
		    	$('.tbody_solucao tr[id=solucao_'+faq_solucao+']').remove();
	    	}else{
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
	    	}
	    }).fail(function(data){
	    	if(value.resultado == false){
	    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
	    	}
	    });
	}
}

function excluirSituacao(faq){
	var dataAjax = {
        faq: faq,
        btn_acao: "excluirFaq"
    };

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
    }).done(function(data){
    	var value = JSON.parse(data);
    	if(value.resultado == true){
	    	$('.tbody_faq tr[id='+faq+']').remove();
    	}else{
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
    }).fail(function(data){
    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
    });
}

function gravar(){
	$("#mensagem").html('');

	var referencia = $("#referencia").val();
	var descricao  = $("#descricao").val();

	/*if(referencia == "" || descricao == ""){
		$("#mensagem").html('<div class="alert alert-error"><h4>Insira as informações do produto</h4> </div>');
		if(referencia == ""){
			$(".control-referencia").addClass("error");
		}
		if(descricao == ""){
			$(".control-descricao").addClass("error");
		}
		return;
	}
*/
	var data = {causa:[],solucao:[]};

	$('.tbody_causa > tr').each(function(){
		var input = $(this).find("input");
		if($(input).val() == "nao_gravado"){
			var causa = $.trim($(this).find("td:first > span").text());
			data.causa.push(causa);
		}
    });

	$('.tbody_solucao > tr').each(function(){
		var input = $(this).find("input");
		if ($(input).val() == "nao_gravado") {
			var causa = $.trim($(this).find("td:first > span").text());
			var solucao = $.trim($(this).find("td:first").next("td").text());
			data.solucao.push({causa: causa, solucao: solucao });
		}
    });

    if(data.causa.length == 0 || data.solucao.length == 0){

    	$("#mensagem").html('<div class="alert alert-error"><h4><?=traduz("Insira as informações de Causa x Solução")?></h4> </div>');
    	return;
    }

    if($("#faq").val() == ""){
    <?php
    	if(in_array($login_fabrica, array(148))){
    		echo "inserirFaqLinha();";
    	}else{
    		echo "inserirFaqProduto();";
    	}
    ?>
	}else{
	<?php
		if(in_array($login_fabrica, array(148))){
    		echo "updateFaqLinha();";
    	}else{
    		echo "updateFaqProduto();";
    	}
	?>
	}

    var dataAjax = {
        faq: $("#faq").val(),
        data: data,
        btn_acao: "gravar"
    };

	$.ajax({
        url: '<?=$PHP_SELF?>',
        type: 'POST',
        data: dataAjax,
    }).done(function(data){
    	var value = JSON.parse(data);

    	if(value.resultado == true){
	    	 window.location='<?=$PHP_SELF?>?mensagem_sucesso=ok';
    	}else{
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
    }).fail(function(data){

    	console.log("falha");
    	console.log(data);

    	if(value.resultado == false){
    		$("#mensagem").html('<div class="alert alert-error"><h4>'+value.mensagem+'</h4> </div>');
    	}
    });
}

var singleSelect = true;  // Allows an item to be selected once only
	var sortSelect = true;  // Only effective if above flag set to true
	var sortPick = true;  // Will order the picklist in sort sequence

	// Initialise - invoked on load
	function initIt() {
		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
	}

	// Adds a selected item into the picklist
	function addIt() {

		if ($('#produto_referencia_multi').val()=='')
			return false;

		if ($('#produto_descricao_multi').val()=='')
			return false;


		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
		pickOptions[pickOLength] = new Option($('#produto_referencia_multi').val()+" - "+ $('#produto_descricao_multi').val());
		pickOptions[pickOLength].value = $('#produto_referencia_multi').val();

		$('#produto_referencia_multi').val("");
		$('#produto_descricao_multi').val("");

		if (sortPick) {
			var tempText;
			var tempValue;
			// Sort the pick list
			while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
				tempText = pickOptions[pickOLength-1].text;
				tempValue = pickOptions[pickOLength-1].value;
				pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
				pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
				pickOptions[pickOLength].text = tempText;
				pickOptions[pickOLength].value = tempValue;
				pickOLength = pickOLength - 1;
			}
		}

		pickOLength = pickOptions.length;
		$('#produto_referencia_multi').focus();
	}

	// Deletes an item from the picklist
	function delIt() {
		var pickList = document.getElementById("PickList");
		var pickIndex = pickList.selectedIndex;
		var pickOptions = pickList.options;
		while (pickIndex > -1) {
			pickOptions[pickIndex] = null;
			pickIndex = pickList.selectedIndex;
		}
	}
	// Selection - invoked on submit
	function selIt(btn) {
		var pickList = document.getElementById("PickList");
		var pickOptions = pickList.options;
		var pickOLength = pickOptions.length;
	/*	if (pickOLength < 1) {
			alert("Nenhuma produto selecionado!");
			return false;
		}*/
		for (var i = 0; i < pickOLength; i++) {
			pickOptions[i].selected = true;
		}
	/*	return true;*/
	}


function toogleProd(radio){

	var obj = document.getElementsByName('radio_qtde_produtos');

	$("#produto_serie").val("");
	$("#produto_serie_multi").val("");
	$("#produto_referencia").val("");
	$("#produto_descricao").val("");
	$("#produto_descricao_multi").val("");
	$("#produto_referencia_multi").val("");
	$("#PickList > option").remove();

	if (obj[0].checked){
		$('#id_um').show("slow");
		$('#id_multi').hide("slow");
	}
	if (obj[1].checked){
		$('#id_um').hide("slow");
		$('#id_multi').show("slow");
	}
}

</script>

<?php if (strlen($msg_erro) > 0) { ?>
	<p>	<div class="alert alert-error">	<h4><?php echo $msg_erro; ?></h4></div>	</p>
<?php } ?>

<?php if (strlen($msg_sucesso) > 0) { ?>
	<p> <div class="alert alert-success"><h4><?php echo $msg_sucesso; ?></h4></div>	</p>
<?php }

if($faq != ""){
	$sql = "SELECT c.causa, n.numero FROM (SELECT count(faq_causa) AS causa FROM tbl_faq_causa WHERE faq = $faq) c,
		(SELECT max(faq_causa) AS numero FROM tbl_faq_causa WHERE faq = $faq) AS n";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$count_causa = (int) pg_fetch_result($res, 0, "numero");
	}else{
		$count_causa = 0;
	}

	$sql = "SELECT c.solucao, n.numero FROM (SELECT count(faq_solucao) AS solucao FROM tbl_faq_solucao WHERE faq = $faq) c,
		(SELECT max(faq_solucao) AS numero FROM tbl_faq_solucao WHERE faq = $faq) AS n";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$count_solucao = (int) pg_fetch_result($res, 0, "solucao");
	}else{
		$count_solucao = 0;
	}
}else{
	$count_causa   = 0;
	$count_solucao = 0;
}
?>

<div id="mensagem"></div>

<?php
	
	if(isset($_GET["mensagem_sucesso"])){
		?>
		<div class="alert alert-success">
			<h4><?=traduz('FAQ cadastrado com Sucesso')?></h4>
		</div>
		<?php
	}

?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>

<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
	<input type="hidden" id="faq" name="faq" value="<?=$faq?>">
	<input type="hidden" id="count_causa" name="count_causa" value="<?=$count_causa?>">
	<input type="hidden" id="count_solucao" name="count_solucao" value="<?=$count_solucao?>">
	<div class="titulo_tabela"><?=traduz('Cadastro')?></div>
	<br />
	
			<?php if(in_array($login_fabrica,array(148))){ ?>
			<div class="container tc_container">
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span2">
					<div class="control-group">
					<label class="control-label" for="tabela"> <?=traduz('Código da Linha')?></label>
					<div class="controls controls-row">
						<div class="span9 input-append">
							<h5 class="asteristico">*</h5>
							<input type="text" readonly id="codigo_linha" value="<?=$codigo_linha ?>" size="15" maxlength="20" class='span8'>
							<input type="hidden" id="linha" value="<?=$linha?>">
							</span>
						</div>
					</div>
				</div>	
			</div>
			<div class="span5">
				<div class="control-group">
					<label class="control-label" for="tabela"> <?=traduz('Descrição do Linha<')?>/label>
					<div class="controls controls-row">
						<div class="span11 input-append">
							<h5 class="asteristico">*</h5>
							<select  id="nome_linha" class="span12">
								<option value=""></option>
								<?php
									$sqlLinha = "SELECT tbl_linha.linha, tbl_linha.nome, tbl_linha.codigo_linha FROM tbl_linha
										WHERE fabrica = $login_fabrica";
									$resLinha = pg_query($con, $sqlLinha);
									if(pg_num_rows($resLinha) > 0){
										while($linha_resultado = pg_fetch_object($resLinha)) {
											$selected = "";

											if($linha_resultado->linha == $linha){
												$selected = "selected";
											}

											?><option value="<?php echo $linha_resultado->linha.'|'.$linha_resultado->codigo_linha; ?>" <?=$selected?>><?=$linha_resultado->nome?></option><?php 
										}
									}
								?>
							</select>
						</div>
					</div>
				</div>
				</div>
				<div class='span2'></div>
			</div>	
				<?php }elseif($login_fabrica != 42){ ?>
				<div class="container tc_container">
				<div class="row-fluid">
					<div class="span2"></div>
				<div class="span3">
					<input type="hidden" name="produto" id="produto_id" value="<?=$produto?>" />
						<div class='control-group control-referencia'>
						<label class='control-label' for='referencia'><?=traduz("Ref. Produto")?></label>
						<div class='controls controls-row'>
							<div class='span8 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="referencia" name="referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
								<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
							</div>
						</div>
					</div>	
				</div>
				<div class="span5">
					<div class="control-group control-descricao">
						<label class='control-label' for='descricao'><?=traduz("Descrição Produto")?></label>
						<div class='controls controls-row'>
							<div class='span11 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
								<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
							</div>
						</div>
					</div>	
					</div>
				<div class='span2'></div>
			</div>
					<?php } ?>
				
		<?php if($login_fabrica == 42){ 

			if($faq==0){
		?>
		<div class='container'>
			<div class="row-fluid" id="box_produtos">
				<div class="span2"></div>
				<div class="span8">
					<div class="row-fluid">
						<div class="span6">
							<?php
							$titulo_produto = "
								Para selecionar vários produtos, clique na opção Vários Produtos e
								adicione os produtos a lista. Todos os produtos da lista serão
								referenciados ao comunicado. Para remover algum produto,
								selecione-o na lista e clique no botão Remover à seguir.
							";
							?>
							<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>' id="error">
								<label class='control-label' for='codigo_posto'>
									Para:
									<i id="btnPopover1" rel="popover" data-placement="top" data-trigger="hover" data-delay="500" title="Informação" data-content="<?=$titulo_produto?>" class="icon-question-sign"></i>
								</label>
								<div class='controls controls-row'>
									<h5 class='asteristico' style="margin-top: 3px !important;">*</h5>
									<div class='span12 input-append'>
										<label class="checkbox">
											<input type="radio" name="radio_qtde_produtos" value='um' <?=$display_um?> onClick='javascript:toogleProd(this)'>
										  	Um produto
										</label>
									</div>
								</div>
							</div>
						</div>
						<div class="span6">
							<div class='control-group'>
								<label class='control-label' for='codigo_posto'>&nbsp;</label>
								<div class='controls controls-row'>
									<div class='span12 input-append'>
										<label class="checkbox">
											<input type="radio" name="radio_qtde_produtos" value='muitos' <?=$display_multi?> onClick='javascript:toogleProd(this)'>
										  	Vários Produtos
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		<!-- Um Produto -->

		<div id='id_um' style='<?php echo $display_um_produto;?>'>
				
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span3">
					<input type="hidden" name="produto" id="produto_id" value="<?=$produto?>" />
						<div class='control-group control-referencia'>
						<label class='control-label' for='referencia'>Ref. Produto</label>
						<div class='controls controls-row'>
							<div class='span8 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="referencia" name="referencia" class='span12' maxlength="20" value="<? echo $referencia ?>" >
								<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
							</div>
						</div>
					</div>	
				</div>
				<div class="span5">
					<div class="control-group control-descricao">
						<label class='control-label' for='descricao'>Descrição Produto</label>
						<div class='controls controls-row'>
							<div class='span11 input-append'>
								<h5 class='asteristico'>*</h5>
								<input type="text" id="descricao" name="descricao" class='span12' value="<? echo $descricao ?>" >
								<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
							</div>
						</div>
					</div>	
				</div>
				<div class="span2"></div>
			</div>
		</div>

		

		<!-- Multi Produtos -->
	    <div id='id_multi' style='<?php echo $display_multi_produto;?>'>
	    	<div class='row-fluid'>
		        <div class='span2'></div>
		        <div class='span2'>
		            <div class='control-group'>
		                <label class='control-label' for='produto_referencia_multi'>Ref. Produto</label>
		                <div class='controls controls-row'>
		                    <div class='span10 input-append'>
		                        <input type="text" id="produto_referencia_multi" name="produto_referencia_multi" class='span12' maxlength="20" value="<?php echo $produto_referencia ?>" >
		                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
		                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" posicao="multi_produto" />
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span4'>
		            <div class='control-group'>
		                <label class='control-label' for='produto_descricao_multi'>Descrição Produto</label>
		                <div class='controls controls-row'>
		                    <div class='span11 input-append'>
		                        <input type="text" id="produto_descricao_multi" name="produto_descricao_multi" class='span12' value="<? echo $produto_descricao ?>" >
		                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
		                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" posicao="multi_produto" />
		                    </div>
		                </div>
		            </div>
		        </div>
		        <div class='span2'>
		        	<label>&nbsp;</label>
		        	<input type='button' name='adicionar' id='adicionar' value='Adicionar' class='btn btn-success' onclick='addIt();' style="width: 100%;">
		        </div>
		        <div class='span2'></div>
		    </div>
		    <p class="tac">
		    	(Selecione o produto e clique em <strong>Adicionar</strong>)
		    </p>
		    <div class='row-fluid'>
		        <div class='span2'></div>
		        <div class='span8'>
		        	<select multiple size='6' id="PickList" name="PickList[]" class='span12'>

					<?php
						if (count($lista_produtos)>0){
							for ($i=0; $i<count($lista_produtos); $i++){
								$linha_prod = $lista_produtos[$i];
								echo "<option value='".$linha_prod[1]."'>".$linha_prod[1]." - ".$linha_prod[2]."</option>";
							}
						}
					?>

					</select>

					<p class="tac">
						<input type="button" value="Remover" onclick="delIt();" class='btn btn-danger' style="width: 126px;">
					</p>

		        </div>

		        <div class='span2'></div>

		    </div>

		</div>

		<?php } ?>
		<div class='container'>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span8">
					<div class="control-group">
						<label class="control-label" for="tabela"><?=traduz('Situação')?></label>
						<div class="controls controls-row">		
							<input type="text" class="span12" id="situacao" value="<?=$situacao?>"/>
						</div>
					</div>	
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<div class='container'>
			<div class="row-fluid">
				<div class='span2'></div>
				<div class="span8">
					<div class="control-group">
						<label class="control-label" for="tabela"><?=traduz('Causa')?><?=traduz('</label')?>>
						<div class="controls controls-row">
							<textarea rows='2' cols='80' name="causa" id='causa' class="span12"></textarea>
							<input type="button" onclick="javascript: adicionaCausa()" alt="Adicionar" value="Adicionar Causa" class="btn" style="margin-top: 10px;">
						</div>
					</div>	
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<br>
		<div id="mensagem_causa"></div>
		<table style='margin: 0 auto !important; width:700px;' class='table table-striped table-bordered table-hover table-large' id='tbl_causa' cellspacing='1' cellpadding='3'>
			<thead>
				<tr class='titulo_tabela' id='titulo_coluna'>
					<th nowrap class='span6'><?=traduz('Causa')?></th>
					<th nowrap class='span2'><?=traduz('Ação')?></th>
				</tr>
			</thead>
			<tbody class="tbody_causa">
				<?php
					if($faq != ""){
						$sql = "SELECT faq_causa, causa FROM tbl_faq_causa WHERE faq = $faq ORDER BY causa";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){
							while($objeto_causa = pg_fetch_object($res)){
								?><tr id='causa_<?=$objeto_causa->faq_causa?>'>
									<td nowrap align='center'>
										<span><?=$objeto_causa->causa?></span>
										<input type="hidden" id="status_causa_<?=$objeto_causa->faq_causa?>" value="gravado">
									</td>
									<td nowrap class="tac"><input type='button' class='btn btn-danger' alt='Excluir' title='Excluir' onclick='excluirCausa("<?=$objeto_causa->faq_causa?>");' value='<?=traduz("Excluir")?>'></td>
								</tr><?php
							}
						}
					}
				?>
			</tbody>
		</table>
		<br>
		<div class='container'>
			<div id="mensagem_solucao"></div>
			<div class="row-fluid">
				<div class='span2'></div>
				<div class="span8">
					<div class="control-group">
						<label class="control-label" for="tabela"><?=traduz('Selecionar Causa Cadastrada')?></label>
						<div class="controls controls-row">
							<select id="causa_selecionada">
								<option value=""></option>
								<?php
									if($faq != ""){
										$sql = "SELECT faq_causa, causa FROM tbl_faq_causa WHERE faq = $faq ORDER BY causa";
										$resCausa = pg_query($con, $sql);

										if(pg_num_rows($resCausa) > 0){
											$count = pg_num_rows($resCausa);

											for($i = 0; $i < $count; $i++){
												$aux_faq_causa = pg_fetch_result($resCausa, $i, "faq_causa");
												$aux_causa = pg_fetch_result($resCausa, $i, "causa");

												?>
												<option value="<?=$aux_faq_causa?>"><?=$aux_causa?></option>
												<?php
											}
										}
									}
								?>
							</select>
						</div>
					</div>	
				</div>
				<div class='span2'></div>
			</div>
			<div class="row-fluid">
				<div class='span2'></div>
				<div class="span8">
					<div class="control-group">
						<label class="control-label" for="tabela"><?=traduz('Solução')?></label>
						<div class="controls controls-row">
							<textarea rows='2' cols='80' name="solucao" id='solucao' class="span12"></textarea>
							<input type="button" onclick="javascript: adicionaSolucao()" alt="Adicionar" value='<?=traduz("Adicionar Solução")?>' class="btn" style="margin-top: 10px;">
						</div>
					</div>	
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<br />
		<div class='container'>
			<div class="row-fluid">
			<div class='span1'></div>
				<div class="span8">	
					<table style='margin: 0 auto !important; width:700px;' class='table table-striped table-bordered table-hover table-large' id='tbl_solucao' cellspacing='1' cellpadding='3'>
						<thead class='formulario'>
							<tr class='titulo_tabela'>
								<th align='center' class='span6'><?=traduz('Causa')?></th>
								<th align='center' class='span6'><?=traduz('Solução')?></th>
								<th align='center' class='span3'><?=traduz('Ações')?></th>
							</tr>
						</thead>
						<tbody class="tbody_solucao">
						<?php
							if($faq != ""){
								$sql = "SELECT tbl_faq_solucao.faq_solucao, 
											tbl_faq_causa.causa,
											tbl_faq_causa.faq_causa,
											tbl_faq_solucao.solucao
										FROM tbl_faq_solucao
										JOIN tbl_faq_causa ON tbl_faq_causa.faq_causa = tbl_faq_solucao.faq_causa
									WHERE tbl_faq_solucao.faq = $faq";
								$resSolucao = pg_query($con, $sql);

								if(pg_num_rows($resSolucao) > 0){

									while($objeto_solucao = pg_fetch_object($resSolucao)){ ?>
										<tr id='solucao_<?=$objeto_solucao->faq_solucao?>'>
											<td id="solucao_causa_<?=$objeto_solucao->faq_causa?>" align='left'>
												<span><?=$objeto_solucao->causa?></span>
												<input type="hidden" id="status_solucao_<?=$objeto_solucao->faq_solucao?>" value="gravado">
											</td>
											<td><?=$objeto_solucao->solucao?></td>
											<td class='tac'>
												<input type='button' class='btn btn-danger' alt='Excluir' title='<?=traduz("Excluir")?>' src='imagens/status_vermelho.png' onclick='excluirSolucao(<?=$objeto_solucao->faq_solucao?>);' value='Excluir'>
											</td>
										</tr>
										<?php
									}
								}
							} ?>
						</tbody>
					</table>
				</div>
				<div class='span2'></div>
			</div>
		</div>
		<br />
		<div class="container">
			<div class="row-fluid">
				<div class='span2'></div>
				<div class='span8' style="text-align: center !important;">
					<input type='hidden' name='btn_acao' value=''>
					<input type="button" value='<?=traduz("Gravar")?>' onclick="gravar()" ALT="Gravar formulário" class='btn btn-default'>
					<?php if($faq != ""){ ?>
						<a href='<?=$PHP_SELF?>'><input type="button" value='<?=traduz("Limpar")?>' alt="Limpar campos" class='btn btn-warning'></a>
					<?php } ?>
					<a href = 'faq_relatorio.php'><input type="button" value='<?=traduz("Pesquisar")?>' alt="Relatório" class='btn btn-info'></a>
				</div>
				<div class='span2'></div>
			</div>
		</div>
	</div>
</form>

<?php
if(in_array($login_fabrica, array(148))){
	$coluna = ", tbl_linha.nome as linha";
	$join = " JOIN tbl_linha ON tbl_linha.linha = tbl_faq.linha AND tbl_linha.fabrica = $login_fabrica ";
}else{
	$coluna = ", tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto ";
	if($login_fabrica == 42){	
		$join = "left JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto AND tbl_produto.fabrica_i = $login_fabrica ";
	}else{
		$join = " JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto AND tbl_produto.fabrica_i = $login_fabrica ";	
	}	
}
$sql = "SELECT tbl_faq.faq, tbl_faq.situacao {$coluna}
	FROM tbl_faq
	$join
	WHERE tbl_faq.fabrica = {$login_fabrica}";
$res = pg_query ($con,$sql);

if(pg_num_rows($res) > 0){ ?>
	<div class="titulo_tabela"><?=traduz('Perguntas Cadastradas')?></div>
        <br />
	<table class='table table-striped table-bordered table-large' cellspacing='0' align='center'>
	<thead>
		<tr class='titulo_tabela'>
			<th align='left'><?=traduz('Situação')?></th>
			<?php
			if ($login_fabrica == 148) {
				echo "<th>".traduz("Linha")."</th>";
			} else {
				echo "<th>".traduz("Produto")."</th>";
			}
			?>
			<th><?=traduz('Ações')?></th>
		</tr>
	</thead>
	<tbody class="tbody_faq">
	<?php
	$count = pg_num_rows($res);
	for ($i = 0 ; $i < $count ; $i++){
		$aux_faq  = pg_fetch_result($res,$i,"faq");
		$situacao = pg_fetch_result($res,$i,"situacao");

		if ($login_fabrica == 148) {
			$linha = pg_fetch_result($res,$i,"linha");
		} else {
			$produto = pg_fetch_result($res,$i,"produto");
		}
		?>
		<tr id="<?=$aux_faq?>">
			<td align='left' style='padding-left: 20px; width: 70%;'><label style="margin: 5px; text-align: center;"><h4><?=$situacao?></h4></label><br>
		<?php
		unset($sql);
		unset($resCausa);
		$sql = "SELECT tbl_faq_causa.faq_causa, 
					tbl_faq_causa.causa,
					tbl_faq_solucao.solucao
				FROM tbl_faq_causa
				LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq_causa = tbl_faq_causa.faq_causa
			WHERE tbl_faq_causa.faq = $aux_faq ORDER BY causa, solucao";
		$resCausa = pg_query($con, $sql);

		if(pg_num_rows($resCausa) > 0){
			$count_causa = pg_num_rows($resCausa);
			?>
			<table style='margin: 0 auto !important; width: 100%;' class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th align='center' width="50%"><?=traduz('Causa')?></th>
						<th align='center' width="50%"><?=traduz('Solução')?></th>
					</tr>
				</thead>
				<tbody class="tbody_resultado">
				<?php
					for($x=0; $x<$count_causa; $x++){
						$aux_causa     = pg_fetch_result($resCausa,$x,"causa");
						$aux_solucao   = pg_fetch_result($resCausa,$x,"solucao"); ?>
						<tr>
							<td align='left'><?=$aux_causa?></td>
							<td><?=$aux_solucao?></td>
						</tr>
					<?php
					} ?>
				</tbody>
			</table>
		<?php 
		} ?>
			</td>
			<td class="tac">
				<?php
				if ($login_fabrica == 148) {
					echo $linha;
				} else {
					echo $produto;
				}
				?>
			</td>
			<td class='tac'>
				<input type="button" id="btn_alterar_<?=$faq?>" name="btn_alterar_<?=$faq?>" class="btn btn-info" value='<?=traduz("Alterar")?>' onclick="window.location='<?php echo $PHP_SELF.'?faq='.$aux_faq; ?>'">
				<input type="button" id="btn_excluir_<?=$faq?>" class="btn btn-danger" value='<?=traduz("Excluir")?>' onclick="excluirSituacao(<?=$aux_faq?>)">
			</td>
		</tr>
	<?php 
	}
}
?>
</table>
<?php include "rodape.php"; ?>
