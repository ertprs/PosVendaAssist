<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0


include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


include 'autentica_usuario.php';

include "../funcoes.php";
if(isset($_POST["inserir_nota"])){
	$msg_erro = "";

	if(strlen($_POST["lote"]) > 0 ){
		$lote	= $_POST["lote"];
	}else{
		$msg_erro = "Lote não preenchido";
	}

	if(strlen($_POST["codigo_posto"]) > 0 ){
		$codigo_posto	= $_POST["codigo_posto"];
	}else{
		$msg_erro = "codigo_posto não preenchido";
	}

	if(strlen($_POST["total_sedex"]) > 0){
		$total_sedex = number_format($_POST["total_sedex"],2,".",",");
	}else{
		$total_sedex = 0;
	}


	if(strlen($_POST["nf_mobra"]) > 0){
		$nf_mobra = $_POST["nf_mobra"];
	}else{
		$msg_erro = "Insira o número da Nota Fiscal";
	}

	if(strlen($_POST["dt_nf_mobra"]) > 0){
		$valor = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $_POST["dt_nf_mobra"]);
		$dt_nf_mobra = $valor;
	}else{
		$msg_erro = "Insira a Data da Nota Fiscal";
	}

	if(strlen($_POST["total_nota_mobra"]) > 0){
		$total_nota_mobra = number_format($_POST["total_nota_mobra"],2,".",",") ;
	}else{
		$msg_erro = "Insira o total da Nota Fiscal";
	}

	if(strlen($_POST["recebimento_lote"]) > 0){
		$valor = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $_POST["recebimento_lote"]);
		$recebimento_lote = $valor;

	}else{
		$recebimento_lote = NULL;
	}

	if(strlen($_POST["identificador_objeto"]) > 0 ){
		$identificador_objeto	= $_POST["identificador_objeto"];
	}else{
		$identificador_objeto	= NULL;
	}
	$OSs = $_POST["os"];
	$bd_err = "";
	if(strlen($msg_erro)==0){
		pg_query($con, "BEGIN TRANSACTION");
		//encontra posto pelo codigo_posto
		$sql_posto = "SELECT posto
					  FROM tbl_posto_fabrica
					  where codigo_posto = '{$codigo_posto}'";
		$res = pg_query($con, $sql_posto);
		if(pg_num_rows($res) > 0){
			$posto = pg_fetch_result($res, 0, "posto");
		}

		$bd_err .= pg_last_error($con);


		$sql = "INSERT INTO tbl_distrib_lote_posto(
						distrib_lote,
						posto,
						nf_mobra,
						valor_mobra,
						total_sedex,
						data_nf_mobra,
						data_recebimento_lote,
						identificador_objeto
					)

				VALUES(
					{$lote},
					{$posto},
					'{$nf_mobra}',
					{$total_nota_mobra},
					{$total_sedex},
					'{$dt_nf_mobra} 00:00:00',
					'{$recebimento_lote} 00:00:00',
					'{$identificador_objeto}'
				)";

		pg_query($con,$sql);

		foreach ($OSs as $os) {
			$updateNotaOs = "UPDATE tbl_distrib_lote_os
							 set nota_fiscal_mo = '{$nf_mobra}'
							 where os = $os";
			pg_query($con, $updateNotaOs);
			$bd_err .= pg_last_error($con);
		}

		if(strlen($bd_err) == 0){
			pg_query($con, "COMMIT TRANSACTION");
			echo '{"success":"true", "nf_mobra":"'.$nf_mobra.'"}';
		}else{
			pg_query($con, "ROLLBACK TRANSACTION");
			echo '{"success":"false", "msg":"Erro ao inserir"}';
		}

	}else{
		echo '{"success":"false", "msg":"'.$msg_erro.'"}';
	}
	exit;
}
if(isset($_POST['fechamento'])){
	$fechamento = $_POST['fechamento'];
	$distrib_lote = $_POST['lote'];

	if($fechamento == 'Fechamento' AND strlen($distrib_lote) > 0){
		$data_fechamento = $_POST['data_fechamento'];
		$data_fechamento = fnc_formata_data_pg ($data_fechamento);

		$sql = "UPDATE tbl_distrib_lote SET fechamento = $data_fechamento WHERE distrib_lote = $distrib_lote ; ";
		$res = pg_exec($con,$sql);

		echo "sucess";
	}

	exit;
}


$lote = $_REQUEST['distrib_lote'];

$alterar = $_POST['alterar'];

if($alterar == 'sim') {
	$codigo_posto = $_POST['posto'];
	$nf           = $_POST['nf'];
	$lote         = $_POST['lote'];
	$valor        = trim($_POST['valor']);
	$valor        = str_replace(".","",$valor);
	$valor        = str_replace(",",".",$valor);
	$tipo         = $_POST['tipo'];

	if($tipo == 'data_nf_mobra') {
		$valor = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $valor);
	}

	$altera_nf_devolucao ="";
	if($tipo == 'nf_mobra') {

		$altera_nf_devolucao = ", nf_devolucao = '".$valor."'";
	}
	//echo "TIPO =".$tipo."<BR>  VALOR =".$valor."<BR>  COD POSTO =".$codigo_posto."<BR>  NOTA FISCAL =".$nf."<BR><BR>";

	//echo exit;
	if(empty($valor)) {
		$msg_erro = "Preenche informação para ser alterado";
	}else{
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $lote";
		$res = pg_exec ($con,$sql);
		$fabrica = pg_result ($res,0,0);

		$sql_at = " UPDATE tbl_distrib_lote_posto
						SET $tipo = '$valor'
						$altera_nf_devolucao
					FROM  tbl_distrib_lote
					WHERE tbl_distrib_lote_posto.distrib_lote = $lote
					AND   tbl_distrib_lote.distrib_lote = tbl_distrib_lote_posto.distrib_lote
					AND tbl_distrib_lote_posto.posto = (SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo_posto' AND fabrica=$fabrica)
					AND tbl_distrib_lote_posto.nf_mobra = '$nf'
					AND tbl_distrib_lote.fechamento ISNULL ";
		$res_at = pg_query($con,$sql_at);
		$qtde_alterada = pg_affected_rows($res_at);
		$msg_erro = pg_last_error($con);
		if(empty($msg_erro)) {
			if($qtde_alterada == 0) {
				$msg_erro = "Nenhum registro alterado";
			}elseif($qtde_alterada == 1){
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}

		if($tipo == 'nf_mobra') {
			$res = pg_query ($con,"BEGIN TRANSACTION");
			$sql_lote =	"UPDATE tbl_distrib_lote_os set nota_fiscal_mo ='$valor' where nota_fiscal_mo='$nf' and distrib_lote ='$lote'";
			$res_lote = pg_query($con,$sql_lote);
			$qtde_alterad_lote = pg_affected_rows($res_lote);
			$msg_erro = pg_last_error($con);
			if(empty($msg_erro)) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}

			$res = pg_query ($con,"BEGIN TRANSACTION");
			$sql_lote2 =	"UPDATE tbl_extrato_lancamento set nota_fiscal_mo ='$valor' where nota_fiscal_mo='$nf' and distrib_lote ='$lote'";
			$res_lote2 = pg_query($con,$sql_lote2);
			$qtde_alterad_lote2 = pg_affected_rows($res_lote2);
			$msg_erro = pg_last_error($con);
			if(empty($msg_erro)) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}


	}
	echo (empty($msg_erro)) ? "OK" : $msg_erro;
	die;
}


$nf_mobra = $_POST['nf_mobra'];
if (strlen($nf_mobra) == 0) $nf_mobra = $_GET['nf_mobra'];

$excluir = $_REQUEST['excluir'];

if (strlen ($lote) > 0) {
	$sql = "SELECT fabrica FROM tbl_distrib_lote WHERE distrib_lote = $lote";
	$res = pg_exec ($con,$sql);
	$fabrica = pg_result ($res,0,0);
}

if (strlen($excluir) > 0 AND $excluir == 'excluirLoteAjax') {
    $nf_mobra       = $_POST['nf_mobra'];
    $lote           = $_POST['distrib_lote'];
    $posto_codigo   = $_POST['posto_codigo'];
    $extrato        = $_POST['extrato'];

    $res = pg_query($con,"BEGIN;");

    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$posto_codigo' AND fabrica=$fabrica";
    $res = pg_query ($con,$sql);
    if(pg_num_rows($res) > 0){
        $posto = pg_fetch_result($res,0,'posto');
    }else
        $msg_erro = "Posto não encontrado!";

    if (strlen($msg_erro) == 0) {

		$sql = "
            UPDATE tbl_distrib_lote SET fechamento = null
            WHERE distrib_lote = $lote;";

	$sql .= "
            DELETE FROM tbl_distrib_lote_posto
            WHERE distrib_lote = $lote
                AND posto = $posto
                AND nf_mobra = '$nf_mobra';";

	$sql .= "
		DELETE FROM tbl_distrib_lote_os
		WHERE distrib_lote = $lote
		AND   os in (select os from tbl_os_extra
			WHERE i_fabrica=$fabrica
			AND   extrato = $extrato );

		UPDATE tbl_extrato_lancamento set
			distrib_lote = null,
			nota_fiscal_mo = null
		WHERE posto = $posto
		AND   fabrica = $fabrica
		AND   distrib_lote = $lote
		AND   nota_fiscal_mo = '$nf_mobra'
		AND   extrato = $extrato ;
	";
	$res = pg_query ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0)
        $msg_erro = pg_errormessage($con);


		$sql = "SELECT os, sua_os, posto INTO TEMP distrASF
                FROM tbl_os
                JOIN tbl_os_extra USING(os)
                JOIN tbl_os_status USING(os)
                WHERE
                    tbl_os_extra.os = tbl_os_status.os
                    AND tbl_os_extra.extrato ISNULL
                    AND tbl_os_status.extrato = $extrato
                    AND tbl_os_status.status_os = 13;

                UPDATE tbl_os_extra SET extrato = $extrato
                FROM tbl_os_status
                WHERE
                    tbl_os_extra.os = tbl_os_status.os
                    AND tbl_os_extra.extrato ISNULL
                    AND tbl_os_status.extrato = $extrato
                    AND tbl_os_status.status_os = 13;

                DELETE FROM tbl_os_status USING distrASF
                WHERE
                    distrASF.os = tbl_os_status.os
                    AND tbl_os_status.extrato = {$extrato}
                    AND status_os = 13;";

		$res = @pg_query ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0)
            $msg_erro = pg_errormessage($con);

        $sql = "SELECT os, sua_os, posto FROM distrASF;";
		$res = @pg_query ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0)
            $msg_erro = pg_errormessage($con);


        if(pg_num_rows($res) > 0){
            for($i = 0; $i < pg_num_rows($res); $i++){
                $sua_os = pg_fetch_result($res,$i,'sua_os');
                $posto  = pg_fetch_result($res,$i,'posto');

                $sql = "DELETE FROM tbl_extrato_lancamento
                        WHERE extrato ISNULL
                            AND posto = {$posto}
                            AND fabrica = {$fabrica}
                            AND lancamento IN (119,197)
                            AND historico LIKE '%$sua_os%'
                            AND historico LIKE '%$extrato%';";
                $res = @pg_query ($con,$sql);
                if (strlen (pg_errormessage ($con)) > 0)
                    $msg_erro = pg_errormessage($con);

                $sql = "DELETE FROM tbl_extrato_lancamento
                        WHERE extrato NOTNULL
                            AND extrato = {$extrato}
                            AND posto = {$posto}
                            AND fabrica = {$fabrica}
                            AND lancamento IN (198, 121)
                            AND historico LIKE '%$sua_os%'
                            AND historico LIKE '%$extrato%';";
                $res = @pg_query ($con,$sql);
                if (strlen (pg_errormessage ($con)) > 0)
                    $msg_erro = pg_errormessage($con);

            }
        }
	}

    if(strlen($msg_erro) == 0){

        $sql = "SELECT fn_calcula_extrato($fabrica, $extrato);";
        $res = @pg_query ($con,$sql);
        if (strlen (pg_errormessage ($con)) > 0)
            $msg_erro = pg_errormessage($con);
    }

	if (strlen($msg_erro) > 0) {
		$res = pg_query ($con,"ROLLBACK;");
		echo utf8_encode($msg_erro);
	} else {
		$res = pg_query ($con,"COMMIT;");
        //$res = pg_query ($con,"ROLLBACK;");
        echo 0;
	}

    exit;
}
?>
<? include 'menu.php' ?>
<body>

<link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">

<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script type="text/javascript" src="../admin/js/jquery.mask.js"></script>

<script type="text/javascript">
	$('document').ready(function(){

		$('#data_fechamento').mask("99/99/9999");
		$('input[rel=inp_data_nf_mobra]').mask("99/99/9999");
		$('input[rel=inp_recebimento_lote]').mask("99/99/9999");

		$('#fechamento').click(function(){
			var fechamento = $('#fechamento').val();
			var lote = $('#lote').val();
			var data_fechamento = $('#data_fechamento').val();

			if(data_fechamento == ""){
				alert('Data de Fechamento está vazio!');
				$('#data_fechamento').focus();
				return;
			}

			$.ajax({
				url: 'lote_conferencia.php',
				type: 'POST',
				dataType: 'JSON',
				data: { fechamento : fechamento, lote: lote, data_fechamento: data_fechamento},
				complete: function(data){
					data = data.responseText;
					if(data != ""){

						$('#lote').val('');
						$('#data_fechamento').val('');

						alert('Lote fechado com Sucesso!');
						location.reload();
					}
				}
			});
		});

	});

	$(function() {
		$( "#data_fechamento" ).datepicker({ dateFormat: "dd/mm/yy", dayNamesMin: ["D", "S", "T", "Q", "Q", "S", "S"] });
		$( "#data_fechamento" ).datepicker("option", "monthNames", ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]);
	});
</script>

<script type="text/javascript" src="../javascripts/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>
<!-- <script language='javascript' src='js/jquery-1.6.1.min.js'></script> -->
<script language='javascript' src='../admin/js/jquery.editable-1.3.3.js'></script>
<script language='javascript' src='../admin/js/jquery.price_format.js'></script>
<script language='javascript'>

//FUNÇAO USADA PARA CARREGAR UMA CONTA_PAGAR DA LISTA DE PENDENTES
function retornaPosto(http) {
	var f= document.getElementById('f1');
	f.style.display='inline';
	if (http.readyState == 1) {
		f.innerHTML = "<CENTER><BR><BR><BR><BR><BR>&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' ></CENTER>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					f.innerHTML = results[1];
				}else{
					f.innerHTML = "<h4>Ocorreu um erro</h4>"+results[1] +"teste -"+results[0] ;
				}
			}else{
				alert ('Posto nao processado');
			}
		}
	}
}

function exibirPosto() {
	var codigo_posto= document.getElementById('codigo_posto').value;
	url = "lote_conferencia_retorna_posto_ajax.php?ajax=sim&codigo_posto="+escape(codigo_posto) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaPosto(http) ; } ;
	http.send(null);
}

function alteraLote(posto,nf,lote,valor,tipo,tag,anterior){

	$.post(
		'<?=$PHP_SELF?>',
		{
			posto:posto,
			nf:nf,
			lote:lote,
			valor:valor,
			tipo:tipo,
			alterar:'sim'
		},
		function(data){
			if (data == 'OK'){
				$("div.mensagem").css('background-color','#00ff00').html('Alterado Com Sucesso').show('slow').delay(2000).hide('3000');
				return true;
			}else{
				if (!$.browser.msie){
					$("div.mensagem").css('position','fixed')
				}

				$("div.mensagem").css('background-color','red').html(data).show('slow').delay(2000).hide('3000');
				$(tag).html(anterior);
			}
		}
	)
}

function toogleOS(){
	$(".toggle_os").click(function(){
		var extrato = $(this).parent().attr('rel');

		$(".os_"+extrato).toggle();
	});
}

$(document).ready(function() {
	$("input[rel=inp_total_sedex]").priceFormat({
		prefix:'',
		centsSeparator: ',',
    	thousandsSeparator: '.'
    });
	$("input[rel=inp_total_nota_mobra]").priceFormat({
		prefix:'',
		centsSeparator: ',',
    	thousandsSeparator: '.'
    });

	toogleOS();
	$(".btn_insere_nota").click(function(){
		var tr = $(this).parent().parent();

		var el_total_sedex          = tr.find("input[rel=inp_total_sedex]");
		var el_nf_mobra             = tr.find("input[rel=inp_nf_mobra]");
		var el_data_nf_mobra        = tr.find("input[rel=inp_data_nf_mobra]");
		var el_total_nota_mobra     = tr.find("input[rel=inp_total_nota_mobra]");
		var el_recebimento_lote     = tr.find("input[rel=inp_recebimento_lote]");
		var el_identificador_objeto = tr.find("input[rel=inp_identificador_objeto]");
		var el_lote                 = tr.find("input[rel=lote]");
		var el_codigo_posto         = tr.find("input[rel=codigo_posto]");

		var total_sedex 				= el_total_sedex.val();
		var nf_mobra 					= el_nf_mobra.val();
		var dt_nf_mobra 				= el_data_nf_mobra.val();
		var total_nota_mobra 			= el_total_nota_mobra.val();
		var recebimento_lote 			= el_recebimento_lote.val();
		var identificador_objeto 		= el_identificador_objeto.val();
		var lote 						= el_lote.val();
		var codigo_posto 				= el_codigo_posto.val();

		var tr_os = $(tr).nextUntil("tr[id^=ln_]").filter(".tr_os");
		var td_os = tr_os.find("[type=hidden][name=os]");
		var os = [];
		$(td_os).each(function(){
			os.push($(this).val());
		});

		$.ajax({
			url:"<?=$PHP_SELF?>",
			type: "POST",
			data:{
				"inserir_nota"			: "true",
				"total_sedex" 			: total_sedex,
				"nf_mobra" 				: nf_mobra,
				"dt_nf_mobra" 	    	: dt_nf_mobra,
				"total_nota_mobra" 		: total_nota_mobra,
				"recebimento_lote" 		: recebimento_lote,
				"identificador_objeto"	: identificador_objeto,
				"lote"					: lote,
				"codigo_posto"			: codigo_posto,
				"os"					: os
			},
			complete: function(data){
				var resp = data.responseText;
				var json_resp = $.parseJSON(resp);
				if(json_resp.success=='true'){
					var divTotalSedex = $("<div></div>");
					$(divTotalSedex).attr({
						style:"cursor:pointer;",
						rel:"total_sedex"
					});
					$(divTotalSedex).addClass("td_total_sedex");
					$(divTotalSedex).text(el_total_sedex.val());
					$(el_total_sedex).after(divTotalSedex);
					el_total_sedex.remove();

					var spanNfMobra = $("<span></span>");
					$(spanNfMobra).attr({
						style:"cursor:pointer;",
						rel:"nf_mobra"
					});
					$(spanNfMobra).text(el_nf_mobra.val());
					$(el_nf_mobra).after(spanNfMobra);
					el_nf_mobra.remove();

					var spanDtNfMobra = $("<span></span>");
					$(spanDtNfMobra).attr({
						style:"cursor:pointer;",
						rel:"data_nf_mobra"
					});
					$(spanDtNfMobra).text(el_data_nf_mobra.val());
					$(el_data_nf_mobra).after(spanDtNfMobra);
					el_data_nf_mobra.remove();

					var divTotalNotaMobra = $("<div></div>");
					$(divTotalNotaMobra).attr({
						style:"cursor:pointer;",
						rel:"total_nota_mobra"
					});
					$(divTotalNotaMobra).addClass("td_total_nota_mobra");
					$(divTotalNotaMobra).text(el_total_nota_mobra.val());
					$(el_total_nota_mobra).after(divTotalNotaMobra);
					el_total_nota_mobra.remove();

					var divRecebimentoLote = $("<div></div>");
					$(divRecebimentoLote).attr({
						style:"cursor:pointer;",
						rel:"recebimento_lote",
					});
					$(divRecebimentoLote).text(el_recebimento_lote.val());
					$(el_recebimento_lote).after(divRecebimentoLote);
					el_recebimento_lote.remove();


					var divIdentificadorObjeto = $("<div></div>");
					$(divIdentificadorObjeto).attr({
						style:"cursor:pointer;",
						rel:"identificador_objeto"
					});
					$(divIdentificadorObjeto).text(el_identificador_objeto.val());
					$(el_identificador_objeto).after(divIdentificadorObjeto);
					el_identificador_objeto.remove();

					el_identificador_objeto.after(el_identificador_objeto.val());
					el_identificador_objeto.remove();

					var nota_fiscal_mo_hidden         = tr.find("input[rel=nota_fiscal_mo]");

					nota_fiscal_mo_hidden.attr("value", json_resp.nf_mobra);

					$(divTotalSedex).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'total_sedex',this,valor.previous);
							var soma = 0;

							$(".td_total_sedex").each(function(){
								var text = $(this).text();
								text = $.trim(text);
								text = text.replace(".","");
								text = text.replace(",",".");
								console.log(text);
								soma += parseFloat(text);

							});
							$(".soma_total_sedex").text(soma.toFixed(2).replace(".",","));


						}
					});
					$(spanNfMobra).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'nf_mobra',this,valor.previous);
						}
					});
					$(spanDtNfMobra).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'data_nf_mobra',this,valor.previous);
						}
					});
					$(divTotalNotaMobra).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'valor_mobra',this,valor.previous);
							var soma = 0;

							$(".td_total_nota_mobra").each(function(){
								var text = $(this).text();
								text = $.trim(text);
								text = text.replace(".","");
								text = text.replace(",",".");
								soma += parseFloat(text);

							});

							$(".soma_total_mobra").text(soma.toFixed(2).replace(".",","));
						}
					});
					$(divRecebimentoLote).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'data_recebimento_lote',this,valor.previous);
						}
					});
					$(divIdentificadorObjeto).editable({
						submit:'Gravar',
						cancel:'Cancelar',
						editClass:'{required:true,minlength:3}',
						onSubmit:function(valor){
							var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
							var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
							var lote= $(this).parent().parent().find("input[rel='lote']").val();
							alteraLote(posto,nf,lote,valor.current,'identificador_objeto',this,valor.previous);

						}
					});
					var soma = 0;

					$(".td_total_sedex").each(function(){
						var text = $(this).text();
						text = $.trim(text);
						text = text.replace(".","");
						text = text.replace(",",".");
						console.log(text);
						soma += parseFloat(text);

					});
					$(".soma_total_sedex").text(soma.toFixed(2).replace(".",","));


					var soma = 0;

					$(".td_total_nota_mobra").each(function(){
						var text = $(this).text();
						text = $.trim(text);
						text = text.replace(".","");
						text = text.replace(",",".");
						console.log(text);
						soma += parseFloat(text);

					});

					$(".soma_total_mobra").text(soma.toFixed(2).replace(".",","));
					alert("Inserido com Sucesso");
				}else{
					alert(json_resp.msg);
				}
			}
		});

	})
	$("input[rel='inp_nf_mobra']").change(function(){
		var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
		var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
		var lote= $(this).parent().parent().find("input[rel='lote']").val();
		var valor = $(this).val();

		if(alteraLote(posto,nf,lote,valor,'nf_mobra',this,valor.previous)){
			var parent = $(this).parent();

			var currentVal = $(this).val();

		}
	})

	$("div[rel='total_sedex']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		editClass:"{required:true,minlength:3, class:'td_total_sedex'}",
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'total_sedex',this,valor.previous);

			var soma = 0;

			$(".td_total_sedex").each(function(){
				var text = $(this).text();
				text = $.trim(text);
				text = text.replace(".","");
				text = text.replace(",",".");
				console.log(text);
				soma += parseFloat(text);

			});
			console.log(soma);
			$(".soma_total_sedex").text(soma.toFixed(2).replace(".",","));

		}
	});

	$("div[rel='identificador_objeto']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		editClass:'{required:true,minlength:3}',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'identificador_objeto',this,valor.previous);

		}
	});

	$("div[rel='recebimento_lote']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'data_recebimento_lote',this,valor.previous);
		}
	});

	$("span[rel='nf_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'nf_mobra',this,valor.previous);
		}
	});

	$("span[rel='data_nf_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'data_nf_mobra',this,valor.previous);
		}
	});

	$("div[rel='total_nota_mobra']").editable({
		submit:'Gravar',
		cancel:'Cancelar',
		onSubmit:function(valor){
			var posto = $(this).parent().parent().find("input[rel='codigo_posto']").val();
			var nf = $(this).parent().parent().find("input[rel='nota_fiscal_mo']").val();
			var lote= $(this).parent().parent().find("input[rel='lote']").val();
			alteraLote(posto,nf,lote,valor.current,'valor_mobra',this,valor.previous);

			var soma = 0;

			$(".td_total_nota_mobra").each(function(){
				var text = $(this).text();
				text = $.trim(text);
				text = text.replace(".","");
				text = text.replace(",",".");
				console.log(text);
				soma += parseFloat(text);

			});

			$(".soma_total_mobra").text(soma.toFixed(2).replace(".",","));
		}
	});

    excluirLote();
});

	function excluirLote(){
        $(".btn_excluir").click(function(){
            var data = $(this).attr('rel').split("|");
            var posto_codigo    = data[0];
            var posto_nome      = data[1];
            var distrib_lote    = data[2];
            var nf_mobra        = data[3];
            var extrato         = data[4];

            if(posto_codigo.length > 0 && posto_nome.length > 0){
                if(confirm("Deseja realmente excluir do lote o posto "+posto_codigo+" - "+posto_nome+"?")){
                    if(distrib_lote.length > 0 && nf_mobra.length > 0){
                        $(this).parent().html("Excluindo!");
                        $.ajax({
                            url: "<?php echo $PHP_SELF;?>",
                            type: "POST",
                            data: "excluir=excluirLoteAjax&posto_codigo="+posto_codigo+"&posto_nome="+posto_nome+"&distrib_lote="+distrib_lote+"&nf_mobra="+nf_mobra+"&extrato="+extrato,
                            success: function(resposta){
                                if(resposta == 0){
                                     $("#ln_"+extrato).fadeOut();
                                }else{
                                    $(this).parent().html("Erro ao excluir!");
                                    alert(resposta);
                                }
                            }
                        });
                    }else{
                        alert('Erro ao passar dados do Lote!');
                    }
                }else{
                    return false;
                }
            }else{
                alert('Erro ao passar dados do Posto!');
            }

            return false;
        });
	}
</script>

<style type="text/css">
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
}

table.tabela tr td{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

table.tabela tr th{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}


* html div.mensagem{
	position:expression(eval(document.compatMode && document.compatMode!='CSS1Compat') ? 'absolute':'fixed' );
	top:expression(eval(document.compatMode && document.compatMode!='CSS1Compat') ? document.body.scrollTop:'30px'  );
	color: white;
}

.hidden, .hidden label{
	display: none;
}

.toggle_os, .toggle_os *{
	cursor: pointer !important;
}
</style>

<?
echo "<div class='mensagem'></div>";

$tc_distrib = explode(",", $telecontrol_distrib);

echo "<table cellpadding=5>";
	echo "<tr>";
		foreach ($tc_distrib as $td) {

			$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $td";
			$res = pg_query($con, $sql);

			$nome_fabrica = pg_fetch_result($res, 0, nome);

			echo "<form method='post' name='frm_lote' action='$PHP_SELF'>";

			echo "<td nowrap>Conferência por Lote <strong>$nome_fabrica</strong><br>";
				$sql = "SELECT
							distrib_lote,
							LPAD (lote::text,6,'0') AS lote,
							TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento
				        FROM tbl_distrib_lote
				        WHERE  tbl_distrib_lote.fabrica = $td
				        ORDER BY distrib_lote DESC";
				$res = pg_exec ($con,$sql);


				echo "<select name='distrib_lote' size='1'>\n";
				echo "<option></option>";
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					    echo "<option value='" . pg_result ($res,$i,distrib_lote) . "'>" . pg_result ($res,$i,lote) . " - " . pg_result ($res,$i,fechamento) . "</option>\n";
					}
				echo "</select>\n";
				echo "<input type='submit' name='btn_acao' value='Consultar Lote'>\n";
			echo "</td>";

			echo "</form>";

		}
	echo "</tr>";
echo "</table>";


if (strlen ($lote) > 0 ) {

	$sql = "SELECT tbl_os.posto,
			tbl_os.os,
			tbl_os.produto,
			tbl_os.mao_de_obra,
			coalesce(tbl_os.qtde_km_calculada,0) as qtde_km_calculada,
			coalesce(tbl_os.valores_adicionais,0) as valores_adicionais,
			tbl_distrib_lote_os.nota_fiscal_mo,
			tbl_distrib_lote_os.distrib_lote,
			tbl_os_extra.extrato
			into temp table t_1
			FROM tbl_os
			JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
			JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			WHERE tbl_distrib_lote_os.distrib_lote = $lote
			AND tbl_os.posto <> 6359 ;

			CREATE INDEX t_1_posto_index ON t_1(posto);
			CREATE INDEX t_1_os_index ON t_1(os);
			CREATE INDEX t_1_produto_index ON t_1(produto);
			CREATE INDEX t_1_extrato_index ON t_1(extrato);

			SELECT t_1.distrib_lote, t_1.posto,
			t_1.nota_fiscal_mo,
			sum(t_1.qtde_km_calculada) as qtde_km_calculada,
			sum(t_1.valores_adicionais) as valores_adicionais,
			SUM (t_1.mao_de_obra) AS mobra_total,
			t_1.extrato,
			COUNT (t_1.os) AS qtde_os
			INTO TEMP TABLE tmp_tab1
			FROM t_1
			JOIN tbl_produto ON tbl_produto.produto = t_1.produto
			GROUP BY t_1.distrib_lote, t_1.posto , t_1.nota_fiscal_mo, t_1.extrato;

			CREATE INDEX tmp_tab1_posto_index ON tmp_tab1(posto);

			SELECT t_1.distrib_lote, t_1.posto, t_1.nota_fiscal_mo, COUNT (t_1.os) AS med_qtde_os,
			t_1.extrato
			into temp table tmp_tab2
			FROM t_1
			GROUP BY t_1.distrib_lote, t_1.posto, t_1.nota_fiscal_mo,
			t_1.extrato;

			CREATE INDEX tmp_tab2_posto_index ON tmp_tab2(posto);


			SELECT t_1.posto, t_1.nota_fiscal_mo, SUM (tbl_os_item.qtde) AS med_qtde_pecas,t_1.extrato
			into temp table tmp_tab3
			FROM t_1
			LEFT JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			GROUP BY t_1.posto, t_1.nota_fiscal_mo,
			t_1.extrato;

			CREATE INDEX tmp_tab3_posto_index ON tmp_tab3(posto);

			SELECT t_1.posto, t_1.nota_fiscal_mo, SUM (tbl_os_item.qtde * tbl_tabela_item.preco) AS med_custo,
			t_1.extrato
			into temp table tmp_tab4
			FROM t_1
			LEFT JOIN tbl_os_produto ON t_1.os = tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_produto ON t_1.produto = tbl_produto.produto
			LEFT JOIN tbl_posto_linha ON t_1.posto = tbl_posto_linha.posto AND tbl_produto.linha = tbl_posto_linha.linha
			LEFT JOIN tbl_tabela_item ON tbl_posto_linha.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
			GROUP BY t_1.posto, t_1.nota_fiscal_mo,
			t_1.extrato;

			CREATE INDEX tmp_tab4_posto_index ON tmp_tab4(posto);

			SELECT distinct on (tbl_posto.nome, tmp_tab1.nota_fiscal_mo,tmp_tab1.extrato)
			tmp_tab2.distrib_lote,
			tbl_posto_fabrica.posto, 
			tbl_posto_fabrica.codigo_posto ,
			tbl_posto_fabrica.contato_email,
			tbl_posto.nome ,
			tbl_posto_fabrica.banco ,
			tbl_posto_fabrica.agencia ,
			tbl_posto_fabrica.conta ,
			tmp_tab2.med_qtde_os ,
			tmp_tab3.med_qtde_pecas ,
			tmp_tab4.med_custo ,
			tmp_tab1.extrato,
			tmp_tab1.qtde_os ,
			t_1.mao_de_obra ,
			tmp_tab1.mobra_total ,
			tmp_tab1.qtde_km_calculada ,
			tmp_tab1.valores_adicionais ,
			tmp_tab1.nota_fiscal_mo
			into temp table tmp_tab5
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
			JOIN tmp_tab1 ON tbl_posto.posto = tmp_tab1.posto
			JOIN tmp_tab2 ON tbl_posto.posto = tmp_tab2.posto and tmp_tab2.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab2.extrato
			JOIN tmp_tab3 ON tbl_posto.posto = tmp_tab3.posto and tmp_tab3.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab3.extrato
			JOIN tmp_tab4 ON tbl_posto.posto = tmp_tab4.posto and tmp_tab4.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = tmp_tab4.extrato
			JOIN t_1 ON tbl_posto.posto = t_1.posto and t_1.nota_fiscal_mo = tmp_tab1.nota_fiscal_mo and tmp_tab1.extrato = t_1.extrato;

			/*ALTER TABLE tmp_tab5 add column extrato integer;

			update tmp_tab5 set extrato = tbl_os_extra.extrato
			FROM tbl_os_extra
			JOIN tbl_os using(os)
			JOIN tbl_distrib_lote_os ON tbl_distrib_lote_os.os = tbl_os_extra.os
			JOIN tbl_posto_fabrica   ON tbl_os.posto = tbl_posto_fabrica.posto
			WHERE tbl_distrib_lote_os.distrib_lote = tmp_tab5.distrib_lote
			AND tmp_tab5.codigo_posto   = tbl_posto_fabrica.codigo_posto
			AND tmp_tab5.nota_fiscal_mo = tbl_distrib_lote_os.nota_fiscal_mo;*/

			SELECT * from tmp_tab5
			UNION
			select distinct distrib_lote,
							tbl_posto_fabrica.posto, 
							tbl_posto_fabrica.codigo_posto,
							tbl_posto_fabrica.contato_email,
							tbl_posto.nome,
							tbl_posto_fabrica.banco,
							tbl_posto_fabrica.agencia,
							tbl_posto_fabrica.conta,
							0 as med_qtde_os,
							0 as med_qtde_pecas,
							0 as med_custo,extrato,
							0 as qtde_os,
							0 as mao_de_obra,
							0 as qtde_km_calculada,
							0 as valores_adicionais,
							0 as mobra_total,
							nota_fiscal_mo
			from tbl_extrato_lancamento
			join tbl_posto using(posto)
			join tbl_posto_fabrica on tbl_posto_fabrica.fabrica = tbl_extrato_lancamento.fabrica and tbl_posto_fabrica.posto = tbl_extrato_lancamento.posto
			where extrato not in (select extrato from tmp_tab5)
			and distrib_lote = $lote
			order by nome,codigo_posto, extrato, nota_fiscal_mo;

			;";

	// echo nl2br($sql);
	#exit;
	$res = pg_exec ($con,$sql);
	//echo "sql: $sql";

	$sql = "SELECT LPAD (lote::text,6,'0') AS lote , TO_CHAR (fechamento,'DD/MM/YYYY') AS fechamento FROM tbl_distrib_lote WHERE distrib_lote = $lote";
	$resX = pg_exec ($con,$sql);

	$arquivo_nome  = "xls/lote_conferencia.xls";

	if (is_file($arquivo_nome)) {
		unlink($arquivo_nome);
	}

	$arquivo = fopen($arquivo_nome, "w");

	if (!is_resource($arquivo)) {
		$msg_erro = 'Erro ao gerar arquivo, entre em contato com o suporte.';
	}

	$fechado = pg_result($resX, 0, fechamento);

	echo "<center><h1>Lote " . pg_result ($resX,0,lote) . " de " . pg_result ($resX,0,fechamento) . "</h1></center>";
	echo "<br>";

	ob_start();?>

	<table border='0' cellspacing='1' cellpadding='3' class='tabela' id='tbl_resultado' align="center" style='border: 1px solid #596D9B; font-family:Calibri, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 13px'>

		<tr align='center'>
			<th bgcolor='#c2d99a' nowrap>Código</th>
			<th bgcolor='#c2d99a' nowrap colspan='4'>Nome</th>
			<th bgcolor='#c2d99a' nowrap>E-mail</th>
			<th bgcolor='#c2d99a' nowrap>Banco</th>
			<th bgcolor='#c2d99a' nowrap>Conta</th>
			<th bgcolor='#c2d99a' nowrap>Agência</th>
			<th bgcolor='#c2d99a' nowrap>Peças</th>
			<th bgcolor='#c2d99a' nowrap>Custo</th>

	<?

	$sql = "SELECT DISTINCT xprod.mao_de_obra
			FROM (
				SELECT tbl_os.mao_de_obra FROM tbl_os JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
				WHERE tbl_distrib_lote_os.distrib_lote = $lote
			) xprod
			ORDER BY xprod.mao_de_obra ASC
			";

	$resX = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) { ?>
		<th bgcolor='#c2d99a' nowrap><?echo  number_format (pg_result ($resX,$i,mao_de_obra),2,",",".") ?></th>

		<?

		$array_mo[$i]= pg_result ($resX,$i,mao_de_obra) ;
	}
	$qtde_cab = $i;

	?>
		<th bgcolor='#c2d99a' nowrap>KM</th>
		<th bgcolor='#c2d99a' nowrap>VALOR ADICIONAL</th>
        <?php if($fabrica == 125){?>
            <th bgcolor='#c2d99a' nowrap>TAXA DE VISITA</th>
        <?php } ?>
		<th bgcolor='#c2d99a' nowrap>TOTAL</th>
		<th bgcolor='#c2d99a' nowrap style='width:150px'>EXTRATO/DATA</th>
		<th bgcolor='#c2d99a' nowrap>TOTAL AVULSO</th>
		<th bgcolor='#c2d99a' nowrap>TOTAL SEDEX</th>
		<th bgcolor='#c2d99a' nowrap style='width:80px;'>NF</th>
		<th bgcolor='#c2d99a' nowrap style='width:80px;'>DATA</th>
		<th bgcolor='#c2d99a' nowrap>TOTAL NOTA</th>
		<th bgcolor='#c2d99a' nowrap>RECEB. LOTE</th>
		<th bgcolor='#c2d99a' nowrap>Número Objeto</th>
		<th bgcolor='#c2d99a' nowrap colspan='2'>&nbsp;</th>
	</tr>

	<?
	$tbl_headers = ob_get_clean();

	$tbl_headers_arquivo = str_replace("<th bgcolor='#c2d99a' nowrap>&nbsp;</th>", '', $tbl_headers);

	$qtde_total_os = 0 ;
	$mobra_total   = 0 ;
	$mobra_posto   = 0 ;
	$total_total   = 0 ;
	$postos = array();
	$notas  = array();



	fwrite ($arquivo, $tbl_headers_arquivo);
	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {
		if ($i == pg_numrows ($res) ) $codigo_posto = "*";

			$posto          = pg_result ($res,$i,posto);
			$codigo_posto   = pg_result ($res,$i,codigo_posto);
			$nome           = pg_result ($res,$i,nome);
			$email          = pg_result ($res,$i,contato_email);
			$banco          = pg_result ($res,$i,banco);
			$conta          = pg_result ($res,$i,conta);
			$agencia        = pg_result ($res,$i,agencia);
			$nota_fiscal_mo = pg_result ($res,$i,nota_fiscal_mo);
			if (pg_result ($res,$i,med_qtde_os) > 0) {
				$media_pecas = pg_result ($res,$i,med_qtde_pecas) / pg_result ($res,$i,med_qtde_os);
				$custo       = pg_result ($res,$i,med_custo)      / pg_result ($res,$i,med_qtde_os);
			}else{
				$media_pecas = 0;
				$custo       = 0;
			}
			$extrato = pg_result($res,$i,extrato);
			$mobra_posto = 0 ;
			$mao_de_obra = pg_result ($res,$i,mao_de_obra);
			$qtde_km_calculada = pg_result ($res,$i,qtde_km_calculada);
			$valores_adicionais = pg_result ($res,$i,valores_adicionais);
			$qtde_os     = pg_result ($res,$i,qtde_os);
			/*for ($x = 0 ; $x < $qtde_cab ; $x++) {
				if ($mao_de_obra == $array_mo [$x][1]) {
					$array_mo [$x][2] = $qtde_os ;
				}
			}*/
			$cor = ($i % 2) ? "#95b3d7" : "#95b3d8";

			ob_start();

			echo "<tr id='ln_{$extrato}' rel='{$extrato}'>";
			echo "<input type='hidden' name='codigo_posto' rel='codigo_posto' value='$codigo_posto'>";
			echo "<input type='hidden' name='nota_fiscal_mo' rel='nota_fiscal_mo' value='$nota_fiscal_mo'>";
			echo "<input type='hidden' name='lote' rel='lote' value='$lote'>";
			echo "<td  bgcolor='$cor' nowrap class='toggle_os'  style=' width: 120px' width='120' >&nbsp;{$codigo_posto} &nbsp;{$mobra}&nbsp;</td>";

			echo "<td  bgcolor='$cor' nowrap class='toggle_os' colspan='4'>";
			echo "<label title='$nome' >";
			echo substr($nome,0,20);
			echo "</label></td>";
			echo "<td bgcolor='$cor' nowrap>{$email}</td>";
			echo "<td  bgcolor='$cor' nowrap align='left'>";
			#HD 243022
			if(strlen($banco)>0){
				$sqlB = "SELECT codigo, nome from tbl_banco where codigo = '$banco'";
				$resB = pg_exec ($con,$sqlB);

				if(pg_numrows($resB)>0){
					$codigo     = pg_result ($resB,0,codigo);
					$banco_nome = pg_result ($resB,0,nome);

					$banco_nome_sub = substr($banco_nome,0,20);

					echo "<label title='$banco - $banco_nome' >$banco - $banco_nome_sub</label>";
				}
			}
			echo "&nbsp;";
			echo "</td>";

			echo "<td bgcolor='$cor' nowrap align='right' >";
			echo $conta;
			echo "&nbsp;";
			echo "</td>";

			echo "<td bgcolor='$cor' nowrap align='right' >";
			echo $agencia;
			echo "&nbsp;";
			echo "</td>";

			echo "<td bgcolor='$cor' nowrap align='right' style='text-align:right; '>";
			echo number_format ($media_pecas,1,",",".");
			echo "</td>";

			echo "<td bgcolor='$cor' nowrap align='right' style='text-align:right; '>";
			echo number_format ($custo,2,",",".");
			echo "</td>";

			for ($x = 0 ; $x < $qtde_cab ; $x++) {
				echo "<td bgcolor='$cor' align='right'>";
				$valor_mao_de_obra = $array_mo[$x];
				if(strlen($valor_mao_de_obra) > 0) {
					$sql_qtde = "SELECT count(xprod.produto) as qtde
						FROM (
							SELECT tbl_os.mao_de_obra,
							tbl_os.produto
							FROM tbl_os
							JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
							JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
							WHERE tbl_distrib_lote_os.distrib_lote = $lote
							AND   extrato= $extrato
							and posto = $posto ) xprod
							JOIN tbl_produto ON tbl_produto.produto = xprod.produto
							where xprod.mao_de_obra = $valor_mao_de_obra;";
					$res_qtde = pg_exec ($con,$sql_qtde);
					//echo $sql_qtde;
					$qtde_os = pg_result($res_qtde,0,qtde);
					$total_qtde_os[$x] = $total_qtde_os[$x] + $qtde_os;
					if ($qtde_os > 0) {
						echo $qtde_os ;

						$mobra_posto = $mobra_posto + ($qtde_os * $valor_mao_de_obra) ;
					}else{

						echo "&nbsp;";
					}
				}
				echo "</td>";
			}

            if($fabrica == 125){
                $sql_tx_visita = "SELECT sum(tbl_os.taxa_visita) as taxa_visita from tbl_os_extra inner join tbl_os on tbl_os.os = tbl_os_extra.os where tbl_os_extra.extrato = $extrato ";
                $res_tx_visita = pg_query($con, $sql_tx_visita);
                if(pg_num_rows($res_tx_visita)> 0 ){
                    $taxa_visita = pg_fetch_result($res_tx_visita, 0, 'taxa_visita');
                }
            }

			$mobra_posto += $qtde_km_calculada;
			$mobra_posto += $valores_adicionais;
            if($fabrica == 125){
                $mobra_posto += $taxa_visita;
            }
			echo "<td bgcolor='$cor' nowrap align='right' style='text-align:right; '>";
			echo number_format ($qtde_km_calculada,2,",",".");
			echo "</td>";
			echo "<td bgcolor='$cor' nowrap align='right' style='text-align:right; '>";
			echo number_format ($valores_adicionais,2,",",".");
			echo "</td>";
            if($fabrica == 125){
                echo "<td bgcolor='$cor' align='right' >";
                echo number_format ($taxa_visita,2,",",".");
                echo "</td>";
            }  
			echo "<td bgcolor='$cor' align='right' >";
			echo number_format ($mobra_posto,2,",",".");
			$total_total += $mobra_posto ;
			echo "</td>";

			$sql2 = "SELECT DISTINCT nf_mobra, to_char(data_nf_mobra,'dd/mm/yyyy') as data_nf_mobra,
						valor_mobra, to_char(data_recebimento_lote,'dd/mm/yyyy') as data_recebimento_lote, tbl_distrib_lote_posto.total_sedex ,identificador_objeto
						FROM tbl_distrib_lote_posto
						JOIN tbl_distrib_lote USING(distrib_lote)
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_distrib_lote_posto.posto
						AND tbl_posto_fabrica.fabrica = tbl_distrib_lote.fabrica
						LEFT JOIN tbl_distrib_lote_os ON tbl_distrib_lote.distrib_lote = tbl_distrib_lote_os.distrib_lote
						LEFT JOIN tbl_os_extra USING(os)
						LEFT JOIN tbl_extrato_lancamento on tbl_extrato_lancamento.distrib_lote = tbl_distrib_lote_posto.distrib_lote
						and tbl_extrato_lancamento.nota_fiscal_mo = tbl_distrib_lote_posto.nf_mobra and tbl_extrato_lancamento.posto = $posto
						WHERE tbl_distrib_lote.distrib_lote = $lote AND tbl_posto_fabrica.posto = $posto AND tbl_distrib_lote_posto.nf_mobra = '$nota_fiscal_mo' ";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$total_sedex        = pg_result($res2,0,total_sedex);
				$nota_mobra         = pg_result($res2,0,nf_mobra);
				$data_nota_mobra    = pg_result($res2,0,data_nf_mobra);
				$total_nota_mobra   = pg_result($res2,0,valor_mobra);
				$recebimento_lote   = pg_result($res2,0,data_recebimento_lote);
				$identificador_objeto   = pg_result($res2,0,identificador_objeto);

				if(count($postos) > 0 and in_array($nota_fiscal_mo,$postos[$codigo_posto])) {
					$total_sedex = 0;
					$total_nota_mobra = 0;
				}
				$total_total_sedex  = $total_total_sedex + $total_sedex;
				$total_total_mobra  = $total_total_mobra + $total_nota_mobra;
			}else{
				$total_sedex        = "";
				$nota_mobra         = "";
				$data_nota_mobra    = "";
				$total_nota_mobra   = "";
				$recebimento_lote   = "";
				$identificador_objeto   = "";
			}

			$sql2 = "SELECT CASE WHEN SUM(tbl_extrato_lancamento.valor) IS NULL THEN 0 else SUM(tbl_extrato_lancamento.valor) END as total_avulso
						FROM tbl_extrato_lancamento
						WHERE extrato = $extrato;";
			#echo  "$sql2";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$total_extrato_avulso = pg_result($res2,0,total_avulso);
				$total_total_extrato_avulso = $total_total_extrato_avulso + $total_extrato_avulso;
			}

			$sql2 = "	SELECT DISTINCT to_char(data_geracao, 'dd/mm/yyyy') as data_geracao
						FROM tbl_extrato WHERE extrato  = $extrato
						;";
			#echo  "$sql2";
			$res2 = pg_exec($con,$sql2);
			if(pg_numrows($res2) > 0){
				$data_geracao = pg_result($res2,0,data_geracao);
			}
			$relacao_nome = "";
			$sqladmin = "select distinct tbl_admin.nome_completo
							from tbl_distrib_lote_os
							left join tbl_admin using(admin)
							left join tbl_os_extra using(os)
							where extrato = $extrato
							and admin is not null;";
			$resadmin = pg_exec($con,$sqladmin);
			$qtdadmin = pg_numrows($resadmin);

			if($qtdadmin > 0){
				for ($j = 0 ; $j < $qtdadmin ; $j++) {
					$relacao_nome .= " ".pg_result($resadmin,$j,nome_completo);
				}
			}

			echo "<td bgcolor='$cor' align='right' title='$relacao_nome' >";
			echo $extrato . " - " . $data_geracao;
			echo "</td>";

			echo "<td bgcolor='$cor' align='right'>";
			echo number_format ($total_extrato_avulso,2,",",".");
			echo "</td>";

			echo "<td bgcolor='$cor' align='right'>";
			if((strlen($total_sedex)>0) || (strlen($nota_mobra) > 0) ){
				echo "<div rel='total_sedex' class='td_total_sedex' style='cursor:pointer;'>";
				echo number_format ($total_sedex,2,",",".");
			}else{
				echo "<input type='text' rel='inp_total_sedex' />";
			}
			echo "</td>";

			echo "<td bgcolor='$cor' align='right' >";
			if(strlen($nota_mobra) > 0){
				echo "<span rel='nf_mobra' style='cursor:pointer;'>";
				echo  	$nota_mobra;
				echo "</span>";
			}else{
				echo "<input type='text' rel='inp_nf_mobra' maxlength='8'/>";
			}
			echo "</td>";

			echo "<td bgcolor='$cor' align='right'>";
			if(strlen($data_nota_mobra) > 0 || (strlen($nota_mobra) > 0) ){
				echo "<span rel='data_nf_mobra' style='cursor:pointer;'>";
				echo 	$data_nota_mobra;
				echo "</span>";
			}else{
				echo "<input type='text' rel='inp_data_nf_mobra' />";
			}
			echo "</td>";

			echo "<td bgcolor='$cor' align='right'>";
			if(strlen($total_nota_mobra) > 0 || (strlen($nota_mobra) > 0) ){
				echo "<div rel='total_nota_mobra' class='td_total_nota_mobra' style='cursor:pointer;'>";
				echo number_format ($total_nota_mobra,2,",",".");
				echo "</div>";
			}else{
				echo "<input type='text' rel='inp_total_nota_mobra' />";
			}
			echo "</td>";
			$total_nota_mobra = '0';

			echo "<td bgcolor='$cor' align='right'>";
			if(strlen($recebimento_lote) > 0 || (strlen($nota_mobra) > 0) ){
				echo "<div rel='recebimento_lote' style='cursor:pointer;'>";
				echo $recebimento_lote;
				echo "</div>";
			}else{
				echo "<input type='text' rel='inp_recebimento_lote' />";
			}
			echo "</td>";

			echo "<td bgcolor='$cor' align='right'>";
			if(strlen($identificador_objeto) > 0 || (strlen($nota_mobra) > 0) ){
				echo "<div rel='identificador_objeto' style='cursor:pointer;'>";
				echo $identificador_objeto;
				echo "</div>";
			}else
{				echo "<input type='text' rel='inp_identificador_objeto' />";
			}
			echo "</td>";
			$row = ob_get_clean();

			$rows .= $row."</tr>";
			$link = "<td bgcolor='$cor'>";
            $link .= "<a href='#' class='btn_insere_nota'>Inserir Nota</a>";
			$link .= "</td>";

			$link .= "<td bgcolor='$cor'>";
			    //$link = "<a href=\"javascript: if (confirm('Deseja realmente excluir do lote o posto $codigo_posto - $nome?') == true) { window.location='$PHP_SELF?excluir=$codigo_posto&distrib_lote=$lote&nf_mobra=$nota_fiscal_mo'; } \">Excluir</A>";]
                $link .= "<a href='javascript: void(0);' class='btn_excluir' rel='{$codigo_posto}|{$nome}|{$lote}|{$nota_fiscal_mo}|{$extrato}'>Excluir</a>";
			$link .= "</td> </tr>";

			$sql_extrato_os = "SELECT
								tbl_os.sua_os ,
								tbl_os.data_abertura,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_os.mao_de_obra,
								tbl_os.os
							FROM tbl_os
								JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE
								tbl_os_extra.extrato = $extrato
							ORDER BY tbl_os.os ASC;";
			$res_extrato_os = pg_query($sql_extrato_os);

			$os_extrato = "";
			if(pg_num_rows($res_extrato_os) > 0){
				// Imprimir as OS do extrato
				$os_extrato .= "<tr class='hidden os_{$extrato}'>";
					$os_extrato .= "<td bgcolor='#cdc1d9' nowrap>OS</td>";
					$os_extrato .= "<td bgcolor='#cdc1d9' nowrap>Data Abertura</td>";
					$os_extrato .= "<td bgcolor='#cdc1d9' nowrap>Referência</td>";
					$os_extrato .= "<td bgcolor='#cdc1d9' nowrap>Descrição</td>";
					$os_extrato .= "<td bgcolor='#cdc1d9' nowrap>Mão de Obra</td>";
				$os_extrato .= "</tr>";

				for ($a=0; $a < pg_num_rows($res_extrato_os); $a++) {
					$os = pg_fetch_result($res_extrato_os, $a, 'os');
					$sua_os = pg_fetch_result($res_extrato_os, $a, 'sua_os');
					$data_abertura = implode('/',array_reverse(explode('-', pg_fetch_result($res_extrato_os, $a, 'data_abertura'))));
					$referencia= pg_fetch_result($res_extrato_os, $a, 'referencia');
					$descricao = pg_fetch_result($res_extrato_os, $a, 'descricao');
					$mao_obra = number_format(pg_fetch_result($res_extrato_os, $a, 'mao_de_obra'), 2, '.','');

					$cor = ($a % 2) ? "#F7F5F0" : "#F1F4FA";
					$os_extrato .= "<tr class='hidden os_{$extrato} tr_os'>";
						$os_extrato .= "<td bgcolor='$cor' nowrap align='center' class='tr_os_os' >{$sua_os}";
						
						$os_extrato .= "<input type='hidden' name='os' value='$os' class='os_id'></td>";
						$os_extrato .= "<td bgcolor='$cor' nowrap align='center'>{$data_abertura}</td>";
						$os_extrato .= "<td bgcolor='$cor' nowrap align='left'>{$referencia}</td>";
						$os_extrato .= "<td bgcolor='$cor' nowrap align='left'>{$descricao}</td>";
						$os_extrato .= "<td bgcolor='$cor' nowrap align='right'>{$mao_obra}</td>";
					$os_extrato .= "</tr>";
				}

			}

			$rows .= $os_extrato;
			$tbl_contents .= $row.$link.$os_extrato;
		/*for ($x = 0 ; $x < $qtde_cab ; $x++) {
			if ($mao_de_obra == $array_mo [$x][1]) {
				$array_mo [$x][2] = $qtde_os ;
			}
			}*/
		$postos[$codigo_posto][] = $nota_fiscal_mo;
	}
	fwrite($arquivo, $rows);

	ob_start();
	echo "<tr align='center'>";
	echo "<th bgcolor='#c2d99a' colspan='11'>Qtde Total de OS</th>";

	/* echo "<th bgcolor='#c2d99a'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";

	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";

	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a'>&nbsp;</th>"; */

	for ($x = 0 ; $x < $qtde_cab ; $x++) {
		echo "<th bgcolor='#c2d99a' align='right'>";
		/*
		$valor_mao_de_obra = $array_mo[$x];
		$sql_qtde = "SELECT count(xprod.produto) as qtde
						FROM (
							SELECT tbl_os.produto
							FROM tbl_os
							JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
							WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote) xprod
						JOIN tbl_produto ON tbl_produto.produto = xprod.produto
						where tbl_produto.mao_de_obra = $valor_mao_de_obra;";
		$sql_qtde = "SELECT count(xprod.produto) as qtde
						FROM (
							SELECT tbl_os.produto
							FROM tbl_os
							JOIN tbl_distrib_lote_os ON tbl_os.os = tbl_distrib_lote_os.os
							WHERE tbl_distrib_lote_os.distrib_lote = $distrib_lote) xprod
						JOIN tbl_produto ON tbl_produto.produto = xprod.produto
						where tbl_produto.mao_de_obra = $valor_mao_de_obra;";

		#echo $sql_qtde;
		$res_qtde = pg_exec ($con,$sql_qtde);
		$qtde_os = pg_result($res_qtde,0,qtde);
		echo $qtde_os;*/

		echo $total_qtde_os[$x];
		echo "</th>";
	}

	echo "<th bgcolor='#c2d99a' align='right' colspan='1'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a' align='right' colspan='1'>&nbsp;</th>";
    if($fabrica ==125){
        echo "<th bgcolor='#c2d99a' align='right' colspan='1'>&nbsp;</th>";
    }   
	echo "<th bgcolor='#c2d99a' align='right'>" . number_format ($total_total,2,",",".") . "</th>";

	echo "<th bgcolor='#c2d99a' align='right' colspan='1'>&nbsp;</th>";

	echo "<th bgcolor='#c2d99a' align='right'>" . number_format ($total_total_extrato_avulso,2,",",".") . "</th>";

	echo "<th bgcolor='#c2d99a' align='right' class='soma_total_sedex'>" . number_format ($total_total_sedex,2,",",".") . "</th>";

	echo "<th bgcolor='#c2d99a' align='right'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a' align='right'>&nbsp;</th>";
	echo "<th bgcolor='#c2d99a' align='right' class='soma_total_mobra' >" . number_format ($total_total_mobra,2,",",".") . "</th>";


	echo "<th bgcolor='#c2d99a' colspan='4'>&nbsp;</th>";
	echo "</tr>";

	echo "</table>";
	$tbl_footer = ob_get_clean();

	$tbl_footer_arquivo = str_replace("colspan='3'", "colspan='2'", $tbl_footer);

	fwrite($arquivo, $tbl_footer_arquivo);

	fclose($arquivo);

	?>

	<div style='margin:auto;width:700px'>
		<p style='text-align:center'>
			<a href="<?= $arquivo_nome?>" target="_blank" >
				<img src="../imagens/excel.gif" alt="">
			</a>
		</p>
		<p style='text-align:center'>
			<a href="<?= $arquivo_nome?>" target="_blank" >
			Clique aqui para fazer o download do relatório.
			</a>
		</p>
	</div>

	<?
		$tbl_headers = str_replace("bgcolor='#c2d99a'","bgcolor='#eeeeee'",$tbl_headers);
		$tbl_footer = str_replace("bgcolor='#c2d99a'","bgcolor='#eeeeee'",$tbl_footer);

		$tbl_contents = str_replace("bgcolor='#95b3d7'", "bgcolor='#F7F5F0'",$tbl_contents);
		$tbl_contents = str_replace("bgcolor='#95b3d8'", "bgcolor='#F1F4FA'",$tbl_contents);
		$tbl_contents = str_replace("bgcolor='#cdc1d9'", "bgcolor='#5F70A0'",$tbl_contents);

		echo $tbl_headers.$tbl_contents.$tbl_footer;
}

if(strlen($fechado) == 0 and strlen($lote) > 0) echo "<input type='hidden' name='lote' id='lote' value='$lote'> <p align='center'>Data Fechamento<br><INPUT TYPE='text' NAME='data_fechamento' id='data_fechamento'><br><INPUT TYPE='submit' name='fechamento' id='fechamento' value='Fechamento'></p>";

?>
<br /><br /><br />
<? include "rodape.php"; ?>

</body>

</html>
