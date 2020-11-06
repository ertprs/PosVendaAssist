<?php
$areaAdminCliente = preg_match('/\/admin_cliente\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
define('ADMCLI_BACK', ($areaAdminCliente == true)?'../admin/':'../');

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

if ($areaAdminCliente == true) {
    include 'autentica_admin.php';
    include_once '../funcoes.php';
} else {
    $admin_privilegios = "gerencia";
    include_once '../includes/funcoes.php';
    include '../autentica_admin.php';
    include "../monitora.php";
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj         = trim(pg_result($res,$i,cnpj));
					$nome         = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			$sql .=  ($busca == "codigo") ? " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ": " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if ($_POST['ajax_status']) {
    if ($_POST['periodo']) {
        $resultado = "<option value=''>Selecione o Status</option>";
        $periodo = $_POST['periodo'];

        if ($periodo == '1') {
            $resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'YYYY-MM-DD')");
            $dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
            $dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

            $cond = " AND (tbl_pedido.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
            $dt = 1;

            $msg .= "Pedidos lançados hoje";
        }
        //  if($ip == '201.42.44.145') echo $monta_sql;

        # Dia anterior
        if ($periodo == '2') {
            $resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '1 day','YYYY-MM-DD')");
            $dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
            $dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

            $cond = " AND (tbl_pedido.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
            $dt = 1;

            $msg .= "Pedidos lançados ontem";
        }

        # Nesta Semana
        if ($periodo == '3') {
            $resX = pg_exec($con,"SELECT TO_CHAR(current_date,'D')");
            $dia_semana_hoje = pg_result($resX,0,0) - 1 ;

            $resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
            $dia_semana_inicial = pg_result($resX,0,0) . " 00:00:00";

            $resX = pg_exec($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
            $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

            $cond = " AND (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
            $dt = 1;

            $msg .= " Pedidos lançados nesta semana";
        }

        # Semana anterior
        if ($periodo == '4') {
            $resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'D')");
            $dia_semana_hoje = pg_result($resX,0,0) - 1 + 7 ;

            $resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
            $dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

            $resX = pg_exec ($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
            $dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

            $cond = " AND (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
            $dt = 1;

            $msg .= "Pedidos lançados na semana anterior";
        }

        # Neste mês
        if ($periodo == '5') {
            $mes_inicial = trim(date("Y")."-".date("m")."-01");
            $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
            $cond = " AND (tbl_pedido.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
            $dt = 1;

            $msg .= "Pedidos lançados neste mês";
        }
    } else {
        $data_inicial = $_POST['data_inicial'];
        $data_final = $_POST['data_final'];
        $resultado = "<option value=''>Selecione o Status</option>";

        $cond = "AND (tbl_pedido.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59')";
    }

    $sql = "SELECT DISTINCT tbl_status_pedido.status_pedido,tbl_status_pedido.descricao
            FROM tbl_pedido
            JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
            WHERE fabrica = $login_fabrica $cond";

    $res = pg_exec($con,$sql);
    if (pg_numrows ($res) > 0) {
        for ($i=0; $i<pg_numrows ($res); $i++ ){
            $status    = trim(pg_result($res,$i,status_pedido));
            $status_descricao = trim(pg_result($res,$i,descricao));

            $resultado .= "<option value='{$status}'>$status_descricao</option>";
        }
    } else {
        $resultado = "<option value=''>Sem resultados</option>";
    }

    exit($resultado);
}

    if ($login_fabrica == 42) {
        if ($_GET["reintegrar"]) {
            $pedido = $_GET['pedido'];
            $sql = "SELECT fn_reintegrar($pedido)";
            $res = pg_exec($con,$sql);
            if(strlen(pg_errormessage($con))>0){
                $msg_erro = "Falha na reintegração do pedido!";
            } else {
                header('Location:pedido_parametros.php');
            }
        }
    }

    if(filter_input(INPUT_POST,'ajax')){
        $tipo   = filter_input(INPUT_POST,'tipo');
        $pedido = filter_input(INPUT_POST,'pedido');

        switch($tipo){
            case "bloquear":
                $res = pg_query($con,"BEGIN TRANSACTION");

                $sql = "
                    UPDATE  tbl_pedido
                    SET     status_pedido = 18
                    WHERE   pedido = $pedido
                ";
                $res = pg_query($con,$sql);
                if(!pg_last_error($con)){
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    echo json_encode(array("msg"=>"Bloqueado com sucesso"));
                }else{
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "Não foi possível realizar a operação";
                }
            break;
            case "liberar":
                $res = pg_query($con,"BEGIN TRANSACTION");

                $sql = "
                    UPDATE  tbl_pedido
                    SET     status_pedido = 1
                    WHERE   pedido = $pedido ";
                $res = pg_query($con,$sql);
                if(!pg_last_error($con)){
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    echo json_encode(array("msg"=>"Liberado com sucesso"));
                }else{
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "Não foi possível realizar a operação";
                }
            break;
            case "cancelar":
                $res = pg_query($con,"BEGIN TRANSACTION");
                $sql = "SELECT tbl_pedido.posto, tbl_pedido_item.peca
                        FROM tbl_pedido
                        inner join tbl_pedido_item on tbl_pedido_item.pedido = tbl_pedido.pedido
                        WHERE tbl_pedido.pedido = $pedido ";
                $res = pg_query($con, $sql);

                for($i=0; $i<pg_num_rows($res); $i++){
                    $posto  = pg_fetch_result($res, $i, 'posto');
                    $peca   = pg_fetch_result($res, $i, 'peca');

                    $aux_motivo = "'Cancelamento admin'";

                    $sql  = "SELECT fn_pedido_cancela(null,$login_fabrica,$pedido,$peca,$aux_motivo,$login_admin)";
                    $resY = pg_query ($con,$sql);
                }

                $sql_posto = "SELECT contato_email from tbl_posto_fabrica where posto = $posto and fabrica = $login_fabrica";
                $res_posto = pg_query($con, $sql_posto);
                if(pg_num_rows($res_posto)>0){
                    $contato_email = pg_fetch_result($res_posto, 0, contato_email);
                }

                $mailTc = new TcComm($externalId);//classe
                $assunto = "Cancelamento do Pedido $pedido";
                $mensagem = "O pedido $pedido foi cancelado pelo admin, em caso de dúvida entrar em contato com o financeiro@acaciaeletro.com.br";
                $res = $mailTc->sendMail(
                    $contato_email,
                    $assunto,
                    $mensagem,
                    'helpdesk@telecontrol.com.br'
                );

                if(!pg_last_error($con)){
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    echo json_encode(array("msg"=>"Cancelado com sucesso"));
                }else{
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                    echo "Não foi possível realizar a operação";
                }
            break;

        }
        exit;
    }


$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if($login_fabrica <> 108 and $login_fabrica <> 111){
$layout_menu = "callcenter";
} else {
$layout_menu = "gerencia";
}
$title = "RELAÇÃO DE PEDIDOS LANÇADOS";

include "cabecalho_new.php";
$plugins = array(
    /*"select2",*/
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "multiselect",
    "dataTable",
	"alphanumeric"
);

include("../admin/plugin_loader.php");

?>
<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

var descricao;
var referencia;

</script>

<script type="text/javascript">

$(function() {

    if (($("#data_inicial_01").val() != "" && $("#data_final_01").val() != "")) {
        var data_final = $('#data_final_01').val();
        var data_inicial = $("#data_inicial_01").val();

        $.ajax({
            url:"pedido_parametros.php",
            type:"POST",
            data:{
                ajax_status:true,
                data_final: data_final,
                data_inicial: data_inicial
            },beforeSend: function() {
                $("#pedido_status").append("<option id='carregando' value=''>Carregando...</option>");
            }
            })
            .done(function(data){
                $("#carregando").remove();
                $("#pedido_status").prop("disabled", false);
                $("#pedido_status").append(data);
            });
    }

    $(".checkbox_periodo").each(function(){
        if ($(this).is(":checked") == true) {
            var checked_value = $(this).val();
            $.ajax({
                url:"pedido_parametros.php",
                type:"POST",
                data:{
                    ajax_status: true,
                    periodo: checked_value
                },beforeSend: function() {
                    $("#pedido_status").append("<option id='carregando' value=''>Carregando...</option>");
                }
                })
                .done(function(data){
                    $("#carregando").remove();
                    $("#pedido_status").prop("disabled", false);
                    $("#pedido_status").append(data);
            });
        }
    });

    $(".alterar_nota").click(function(){
        var pedido = $(this).data('pedido');
        Shadowbox.open({
            content: "alterar_dados_nota_faturamento.php?pedido="+pedido,
            player: "iframe",
            title: "Dados do Pedido",
            width: 850,
            height: 390
        });
    });

<?PHP
if($login_fabrica == 74){
?>
    /**
     * Ação para BLOQUEAR pedido
     */
    $("img[id^=bloquear_]").css({
        "cursor":"pointer"
    });

    $("img[id^=bloquear_]").click(function(e){
        var bloquear = $(this).attr("id");
        var aux = bloquear.split("_");
        var pedido = aux[1];

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"bloquear",
                pedido:pedido
            }
        })
        .done(function(data){
            $("#bloquear_"+pedido).css({
                "display":"none"
            });
            alert("Pedido "+pedido+" "+data.msg+"!");
        });
    });

    /**
     * Ação para LIBERAR pedido
     */
    $("img[id^=liberar_]").css({
        "cursor":"pointer"
    });
    $("img[id^=liberar_]").click(function(e){
        var liberar = $(this).attr("id");
        var aux = liberar.split("_");
        var pedido = aux[1];

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"liberar",
                pedido:pedido
            }
        })
        .done(function(data){
            $("#liberar_"+pedido).css({
                "display":"none"
            });
            alert("Pedido "+pedido+" "+data.msg+"!");
        });
    });
<?php
}
?>

<?php
if($login_fabrica == 168){
?>
    /**
     * Ação para LIBERAR pedido
     */
    $("img[id^=liberar_]").css({
        "cursor":"pointer"
    });
    $("img[id^=liberar_]").click(function(e){
        var liberar = $(this).attr("id");
        var aux = liberar.split("_");
        var pedido = aux[1];

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"liberar",
                pedido:pedido
            }
        })
        .done(function(data){
            $("#liberar_"+pedido).css({
                "display":"none"
            });
            alert("Pedido "+pedido+" "+data.msg+"!");
        });
    });

    /**
     * Ação para CANCELAR pedido
     */
    $("img[id^=cancelar_]").css({
        "cursor":"pointer"
    });
    $("img[id^=cancelar_]").click(function(e){
        var cancelar = $(this).attr("id");
        var aux = cancelar.split("_");
        var pedido = aux[1];

        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                tipo:"cancelar",
                pedido:pedido
            }
        })
        .done(function(data){
            $("#cancelar_"+pedido).css({
                "display":"none"
            });
            alert("Pedido "+pedido+" "+data.msg+"!");
        });
    });

<?php
}

if ($login_fabrica == 101) {
?>
    $("#tipo_pedido").change(function(){
        if ($(this).val() != "" && $(this).val() != 210 && $(this).val() != 214) {
            $("#destinatario_troca").attr("disabled",false);
        } else {
            $("#destinatario_troca").attr("disabled",true);
            $("#destinatario_troca").val("");
        }
    });
<?php
}
?>
    var span = 0;
    $('#tabela_pedidos tbody tr').eq(0).each(function(){
        $(this).find('td').each(function(){
            span += 1;
        });
    });

    $('#tabela_pedidos .titulo_tabela th').attr("colspan", span);

	$("#numero_pedido").change(function() {
		if ($(this).val() == "") {
			$(this).attr("value", "");
			$("#data_inicial_01").prev(".asteristico").show();
			$("#data_final_01").prev(".asteristico").show();
		} else {
			$("#data_inicial_01").prev(".asteristico").hide();
			$("#data_final_01").prev(".asteristico").hide();

			$("#campo_data_inicial").removeClass("error");
			$("#campo_data_final").removeClass("error");
		}
	});

	$(".click_periodo").on('click',function() {
		if ($('.checkbox_periodo').prop("disabled") == true) {
			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",false);
				$("#data_final_01").prop("disabled", true);
				$("#data_inicial_01").prop("disabled", true);
				$("#data_final_01").val("");
				$("#data_inicial_01").val("");
			});
		}
	});

	$("#click_data_inicial").on("click", function() {
		if ($('#data_inicial_01').prop("disabled") == true) {
			$('#data_inicial_01').prop("disabled", false);
			$("#data_final_01").prop("disabled", false);

			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",true);
				$(this).prop("checked",false);
			});
		}

		$('#data_inicial_01').focus();
	});

    $("#click_data_inicial").dblclick(function() {
        $('#data_inicial_01').select();
    });

	$("#click_data_final").on("click", function() {
		if ($('#data_final_01').prop("disabled") == true) {
			$('#data_final_01').prop("disabled", false);
			$("#data_inicial_01").prop("disabled", false);

			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",true);
				$(this).prop("checked",false);
			});
		}

		$('#data_final_01').focus();
	});

    $("#click_data_final").dblclick(function() {
        $('#data_inicial_01').select();
    });

	$("#data_inicial_01").change(function() {
		if ($(this).val() == "") {
			$(this).attr("value", "");
		}

		if ($(this).val() == "" && $("#data_final_01").val() == "") {
			$("#numero_pedido").prev(".asteristico").show();
			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",false);
			});
		} else {
			$("#numero_pedido").prev(".asteristico").hide();
			$("#campo_numero_pedido").removeClass("error");
			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",true);
                $(this).prop("checked",false);
			});
		}
        if ($("#data_final_01").val() != ""){
            var data_final = $('#data_final_01').val();
            var data_inicial = $("#data_inicial_01").val();

            $.ajax({
                url:"pedido_parametros.php",
                type:"POST",
                data:{
                    ajax_status:true,
                    data_final: data_final,
                    data_inicial: data_inicial
                },beforeSend: function() {
                        $("#pedido_status").html("<option value=''>Carregando...</option>");
                }
            })
            .done(function(data){
                $("#pedido_status").prop("disabled", false);
                $("#pedido_status").html(data);
            });
        }
	});

	$("#data_final_01").change(function() {
		if ($(this).val() == "") {
			$(this).attr("value", "");
		}

		if ($(this).val() == "" && $("#data_inicial_01").val() == "") {
			$("#numero_pedido").prev(".asteristico").show();
			$("#data_final_01").val("");
			$("#data_inicial_01").val("");
			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",false);
			});
		} else {
			$("#numero_pedido").prev(".asteristico").hide();
			$("#campo_numero_pedido").removeClass("error");
			$(".checkbox_periodo").each(function(){
				$(this).prop("disabled",true);
                $(this).prop("checked",false);
			});
		}

        if ($("#data_inicial_01").val() != ""){
            var data_final = $('#data_final_01').val();
            var data_inicial = $("#data_inicial_01").val();

            $.ajax({
                url:"pedido_parametros.php",
                type:"POST",
                data:{
                    ajax_status:true,
                    data_final: data_final,
                    data_inicial: data_inicial
                },beforeSend: function() {
                        $("#pedido_status").html("<option value=''>Carregando...</option>");
                }
            })
            .done(function(data){
                $("#pedido_status").prop("disabled", false);
                $("#pedido_status").html(data);
            });
        }
	});

	$(".checkbox_periodo").click(function() {
		if ($(this).is(":checked") == true) {
			$("#data_final_01").prop("disabled", true);
			$("#data_inicial_01").prop("disabled", true);
			$("#numero_pedido").prev(".asteristico").hide();
			$("#click_inicial").prev(".asteristico").hide();
            $("#click_final").prev(".asteristico").hide();

			$("#campo_numero_pedido").removeClass("error");
			$("#campo_data_inicial").removeClass("error");
			$("#campo_data_final").removeClass("error");

                var periodo = $(this).val();

                $.ajax({
                    url:"pedido_parametros.php",
                    type:"POST",
                    data:{
                        ajax_status: true,
                        periodo: periodo
                    },beforeSend: function() {
                        $("#pedido_status").html("<option value=''>Carregando...</option>");
                    }
                })
                .done(function(data){
                    $("#pedido_status").prop("disabled", false);
                    $("#pedido_status").html(data);
                });

		} else {
			$("#data_final_01").prop("disabled", false);
			$("#data_inicial_01").prop("disabled", false);
			$("#numero_pedido").prev(".asteristico").show();
			$("#click_inicial").prev(".asteristico").show();
            $("#click_final").prev(".asteristico").show();
		}
	});

	$.autocompleteLoad(Array("produto", "peca", "posto"));

	$("#data_inicial_01").datepicker().mask("99/99/9999");
	$("#data_final_01").datepicker().mask("99/99/9999");

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {

        <?php
        if ($login_fabrica == 85) { ?>
            var attrAdicionais = ["contratual"];
        <?php
        } else { ?>
            var attrAdicionais = [];
        <?php
        }
        ?>


        $.lupa($(this),attrAdicionais);
	});


    $("#estadoPostoAutorizado").multiselect({
        selectedText: "selecionados # de #"
    });


});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

    function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_nome").val(retorno.descricao);
	}

	function retorna_representante (retorno) {
		$("#codigo").val(retorno.codigo);
		$("#nome").val(retorno.nome);
		$("#id_representante").val(retorno.representante);
	}
</script>

<!-- Valida formularios -->
<script type="text/javascript">
function fcn_valida_formDatas()
{
	$("form[name=frm_pesquisa1]").submit();
}

function fcn_valida_formDatas2()
{
	var msg = "";
	var pesquisa = $("input[name=frm_submit]").val();


        <?php if ($login_fabrica == 1) {?>

        if (($('input[name=pedidos_nao_exportados]').is(":checked") == true) || ($('input[name=pdd_rpst]').is(":checked") == true) || ($('input[name=chk_opt]').is(":checked") == true) || ($("input[name=data_inicial_01]").val() != "" && $("input[name=data_final_01]").val() != "") || $("input[name=numero_pedido]").val() != "" || $("input[name=arquivo_pedido]").val() != "")
        {
            var msg_erro = "";
        }else{

            var msg = "Preencha os campos obrigatórios";

            $("#campo_numero_pedido").addClass("error");
            $("#campo_data_inicial").addClass("error");
            $("#campo_data_final").addClass("error");

            $('html,body').scrollTop(0);

        }

        <?php } else {?>
        //VALIDAÇÃO DE PREENCHIMENTO DAS DATASA E/OU NRO DO PEDIDO
        if (($('input[name=chk_opt]').is(":checked") == true) || ($("input[name=data_inicial_01]").val() != "" && $("input[name=data_final_01]").val() != "") || $("input[name=numero_pedido]").val() != "")
        {
            var msg_erro = "";
        }else{

            var msg = "Preencha os campos obrigatórios";

            $("#campo_numero_pedido").addClass("error");
            $("#campo_data_inicial").addClass("error");
            $("#campo_data_final").addClass("error");

            $('html,body').scrollTop(0);

        }
        <?php }?>

		var cod_posto = $("#codigo_posto").val();
		var nome_posto = $('#descricao_posto').val();
		if (cod_posto.length > 0 && nome_posto.length == 0) {
			if (msg == "") {
				msg = "Preencha o nome do posto";
				$("#campo_nome_posto").addClass("error");
			}
		}


		if (nome_posto.length > 0 && cod_posto.length == 0) {

			if (msg == "") {
				msg = "Preencha o código do posto";
				$("#campo_cod_posto").addClass("error");
			}
		}

		if (msg != ""){
			$("#div_erro").html("<h4 align='center'>"+msg+"</h4>");
			$("#div_erro").show();

		}else{

			$("form[name=frm_pesquisa]").submit();

		}


}

function retorna_cliente(retorno){
    $("#cliente_codigo").val(retorno.codigo_cliente);
    $("#cliente_nome").val(retorno.nome);
}

</script>
<div class="container">
	<div class="alert alert-warning">
     <h4>Data mínima para pesquisa 01/04/2019</h4>   
    </div>

    <div id="div_erro" class="msg_erro alert alert-danger" style="display: none;">
    </div>
    <?php if ($login_fabrica == 164) {?>
    <div class="alert alert-danger">
        <h4>GERAÇÃO E EXPORTAÇÃO DE PEDIDOS</h4><br />
        <p class="tal"><b>Pedidos de Peças:</b> São gerados toda Segunda-feira, Quarta-feira e Sexta-feira. A exportação para Gama é realizada todos dias.</p>
        <p class="tal"> <b>Pedidos de Troca de Produtos:</b> São gerados todos os dias, a exportação e o faturamento destes pedidos é manual através do Telecontrol</p>
    </div>
    <?php }?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<div class="tc_formulario">
<FORM class='form-search form-inline' name="frm_pesquisa" METHOD="POST" ACTION="pedido_parametros.php">
<input type="hidden" name="frm_submit" id="frm_submit" value="">
<input type="hidden" name="btn_acao">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
<?php if($login_fabrica == 168){ ?>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class="span4">
				<label class="checkbox">
					<INPUT TYPE="checkbox" <?= (strlen($_POST["pdpp"]) > 0) ? "checked" : ""; ?> NAME="pdpp" value="25" rel="5">Pedidos com Pagamento Pendente
				</label>
			</div>
		<div class="span2"></div>
	</div>
<?php } ?>


<? if ($login_fabrica == 1) { ?><br />
	<fieldset style="border: 2px solid lightgrey;border-radius: 10px;width: 50%;position: relative;left: 25%;">
	<legend class="titulo_tabela" style="border-radius: 10px 10px 0px 0px;background-color: darkblue;">Tipo de Pedido</legend>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" value="" checked >Todos
			</label>
		</div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "87|peca") ? "checked" : ""; ?> value="87|peca">Garantia Peças
			</label>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "87|produto") ? "checked" : ""; ?> value="87|produto">Garantia Produtos
			</label>
		</div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "86") ? "checked" : ""; ?> value="86">Faturado
			</label>
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "94") ? "checked" : ""; ?> value="94">Locador
			</label>
		</div>
		<div class="span4">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "93") ? "checked" : ""; ?> value="93">Acessórios
			</label>
		</div>
		<div class="span2"></div>
	</div>
	</fieldset>

<? } ?>
<? $disabled = (strlen($_POST["chk_opt1"]) > 0) ? "disabled" : ""; ?>
<? $disabled = (strlen($_POST["chk_opt2"]) > 0) ? "disabled" : ""; ?>
<? $disabled = (strlen($_POST["chk_opt3"]) > 0) ? "disabled" : ""; ?>
<? $disabled = (strlen($_POST["chk_opt4"]) > 0) ? "disabled" : ""; ?>
<? $disabled = (strlen($_POST["chk_opt5"]) > 0) ? "disabled" : ""; ?>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
        <div class="span2">
            <div class='control-group ' id="campo_nume+ro_pedido">
                    <label class="control-label" for='numero_pedido'>Número do pedido</label>
                    <div class="controls controls-row">
                        <h5 class='asteristico'>*</h5>
                        <INPUT TYPE="text" maxlength="20" id="numero_pedido" value="<?=$numero_pedido?>" NAME="numero_pedido">
                </div>
            </div>
        </div>
        <?php if ($login_fabrica == 1) {?>
        <div class="span2"></div>
        <div class="span2">
            <div class='control-group'>
                    <label class="control-label" for='arquivo_pedido'>Arquivo de pedido</label>
                    <div class="controls controls-row">
                        <INPUT TYPE="text" maxlength="20" id="arquivo_pedido" value="<?= $arquivo_pedido;?>" NAME="arquivo_pedido">
                </div>
            </div>
        </div>
        <?php }?>
		<div class="span2"></div>
	</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
			<div class='span4'>
				<div class='control-group' id="campo_data_inicial">
					<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<div id="click_inicial" style="display:inline-block; position:relative;">
									<input class="span12" <?= $disabled ?> value="<?= (isset($_REQUEST['data_inicial_01'])) ? $_REQUEST['data_inicial_01'] : "" ?>" type="text" name="data_inicial_01" id="data_inicial_01" />
									<div id='click_data_inicial' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
								</div>
							</div>
						</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group' id="campo_data_final">
					<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<div id="click_final" style="display:inline-block; position:relative;">
									<INPUT class="span12" <?= $disabled ?> value="<?= (isset($_REQUEST['data_final_01'])) ? $_REQUEST['data_final_01'] : "" ?>" maxlength="10" type="text" NAME="data_final_01" id="data_final_01" />
									<div id='click_data_final' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
								</div>
							</div>
						</div>
				</div>
			</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group" id="campo_cod_posto">
				<label class="control-label" for="">Código Posto</label>
				<div class="controls controls-row">
					<div class='span7 input-append'>
						<INPUT class='span12' TYPE="text" class="frm" NAME="codigo_posto" id="codigo_posto" value='<?= $_REQUEST['codigo_posto'] ?>'>
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group" id="campo_nome_posto">
				<label class="control-label" for="">Nome Posto</label>
				<div class="controls controls-row">
					<div class="span7 input-append">
						<INPUT TYPE="text" class="frm" NAME="nome_posto" id="descricao_posto" value="<?= $_REQUEST['nome_posto'] ?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
	<div class="span2"></div>
	</div>
    <?php
    if ($login_fabrica == 85) { ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cliente_codigo'>Código Cliente</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="cliente_codigo" name="cliente_codigo" class='span12' maxlength="20" value="<? echo $_REQUEST['cliente_codigo'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="cliente" parametro="codigo" contratual="t" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("cliente", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='cliente_nome'>Nome Cliente</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="cliente_nome" name="cliente_nome" class='span12' value="<? echo $_REQUEST['cliente_nome'] ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="cliente" parametro="nome" contratual="t" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
    <?php
    }
    ?>

<? if ($login_fabrica <> 6){ ?>
	<div class="row-fluid">
	<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="">Referência Peça</label>
				<div class="controls controls-row">
					<div class="span7 input-append">
						<INPUT TYPE="text" class='span12' value="<?= $_POST['peca_referencia'] ?>" class="frm" name="peca_referencia" id="peca_referencia">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for="">Descrição Peça</label>
				<div class="controls controls-row">
					<div class="input-append">
						<INPUT TYPE="text" class="frm" value="<?= $_POST['peca_descricao'] ?>" name="peca_descricao" id="peca_descricao" size="15">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
					</div>
				</div>
			</div>
		</div>
	<div class="span2"></div>
	</div>
<? if ($login_fabrica == 1) { ?>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="">Código Representante</label>
					<div class="controls controls-row">
						<div class="span7 input-append">
							<INPUT class='span12' TYPE="text" value="<?= $_POST['codigo'] ?>" class="frm" name="codigo" id="codigo">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo_representante" />
						</div>
					</div>
				</div>
			</div>
			<INPUT TYPE="hidden" class="frm" name="id_representante" id="id_representante">
			<div class="span4">
				<div class="control-group">
				<label class="control-label" for="">Descrição Representante</label>
					<div class="controls controls-row">
						<div class="input-append">
							<INPUT TYPE="text" value="<?= $_POST['nome'] ?>" class="frm" name="nome" id="nome">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="descricao_representante" />
						</div>
					</div>
				</div>
			</div>
		<div class="span2"></div>
	</div>
	<br />
<?
}
}else{ ?>
	<div class="row-fluid">
		<div class="span2"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for="">Referência do produto</label>
					<div class="controls controls-row">
						<div class="span7 input-append">
							<INPUT class='span12' TYPE="text" class="frm" name="produto_referencia" id="produto_referencia" SIZE="10" value="<?= $_POST['produto_referencia'] ?>">
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group">
				<label class="control-label" for="">Descrição do produto</label>
					<div class="controls controls-row">
						<div class="input-append">
							<INPUT TYPE="text" class="frm" value="<?= $_POST['produto_descricao'] ?>" name="produto_nome" id="produto_descricao" size="15">
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
		<div class="span2"></div>
		</div>

<? } ?>

<?php if($login_fabrica == 120){//hd_chamado=2765193 ?>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
				<div class="span8">
					<label class="control-label" for="">Linha</label>
					<div class="controls controls-row">
						<select class='frm' name="linha_produto" id="linha_produto">
							<option value="" ></option>
							<?php
							$sql = "SELECT linha, nome
									FROM tbl_linha
									WHERE fabrica = $login_fabrica
									AND ativo";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {
								$selected_linha = ( isset($_POST['linha_produto']) and ($_POST['linha_produto'] == $key['linha']) ) ? "SELECTED" : '' ;

							?>
								<option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

									<?php echo $key['nome']?>

								</option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			<div class="span2"></div>
	</div>
<?php } ?>
<? if (in_array($login_fabrica, array(1,72))) { #HD 280384 ?>
	<div class="row-fluid">
		<div class="span2"></div>
        <?php if ($login_fabrica == 72) {?>
		<div class="span4">
			<label class="control-label" for="">Estado</label>
			<div class="controls controls-row">
				<select name="estado" size="1" class="frm">
					<option value="" <? if (strlen($estado) == 0) echo "selected"; ?>></option>
					<option value="AC" <? if ($estado == "AC") echo "selected"; ?>>AC - Acre</option>
					<option value="AL" <? if ($estado == "AL") echo "selected"; ?>>AL - Alagoas</option>
					<option value="AM" <? if ($estado == "AM") echo "selected"; ?>>AM - Amazonas</option>
					<option value="AP" <? if ($estado == "AP") echo "selected"; ?>>AP - Amapá</option>
					<option value="BA" <? if ($estado == "BA") echo "selected"; ?>>BA - Bahia</option>
					<option value="CE" <? if ($estado == "CE") echo "selected"; ?>>CE - Ceará</option>
					<option value="DF" <? if ($estado == "DF") echo "selected"; ?>>DF - Distrito Federal</option>
					<option value="ES" <? if ($estado == "ES") echo "selected"; ?>>ES - Espírito Santo</option>
					<option value="GO" <? if ($estado == "GO") echo "selected"; ?>>GO - Goiás</option>
					<option value="MA" <? if ($estado == "MA") echo "selected"; ?>>MA - Maranhão</option>
					<option value="MG" <? if ($estado == "MG") echo "selected"; ?>>MG - Minas Gerais</option>
					<option value="MS" <? if ($estado == "MS") echo "selected"; ?>>MS - Mato Grosso do Sul</option>
					<option value="MT" <? if ($estado == "MT") echo "selected"; ?>>MT - Mato Grosso</option>
					<option value="PA" <? if ($estado == "PA") echo "selected"; ?>>PA - Pará</option>
					<option value="PB" <? if ($estado == "PB") echo "selected"; ?>>PB - Paraíba</option>
					<option value="PE" <? if ($estado == "PE") echo "selected"; ?>>PE - Pernambuco</option>
					<option value="PI" <? if ($estado == "PI") echo "selected"; ?>>PI - Piauí</option>
					<option value="PR" <? if ($estado == "PR") echo "selected"; ?>>PR - Paraná</option>
					<option value="RJ" <? if ($estado == "RJ") echo "selected"; ?>>RJ - Rio de Janeiro</option>
					<option value="RN" <? if ($estado == "RN") echo "selected"; ?>>RN - Rio Grande do Norte</option>
					<option value="RO" <? if ($estado == "RO") echo "selected"; ?>>RO - Rondônia</option>
					<option value="RR" <? if ($estado == "RR") echo "selected"; ?>>RR - Roraima</option>
					<option value="RS" <? if ($estado == "RS") echo "selected"; ?>>RS - Rio Grande do Sul</option>
					<option value="SC" <? if ($estado == "SC") echo "selected"; ?>>SC - Santa Catarina</option>
					<option value="SE" <? if ($estado == "SE") echo "selected"; ?>>SE - Sergipe</option>
                    <option value="SP" <? if ($estado == "SP") echo "selected"; ?>>SP - São Paulo</option>
					<option value="TO" <? if ($estado == "TO") echo "selected"; ?>>TO - Tocantins</option>
				</select>
			</div>
		</div>
        <?php }?>
        <?php if ($login_fabrica == 1) {?>
        <div class="span4">
            <label class="control-label" for="">Estado</label>
            <div class="controls controls-row">
                <select name="estado_posto_autorizado[]" id="estadoPostoAutorizado" multiple="multiple">
                    <?php
                      foreach ($array_estados() as $sigla => $estados) {
                          $ufSelected = (in_array($sigla, $estado_posto_autorizado)) ? 'selected="selected"' : '';
                          echo "<option value='{$sigla}' {$ufSelected}>".utf8_decode($estados)."</option>";
                      }
                     ?>
                </select>
            </div>
        </div>
        <div class="span2">
            <label class="control-label" for="">Região</label>
            <div class="controls controls-row">
                <select name="regiao" size="1" class="frm">
                    <option value=''></option>
                    <option value='1' <?php if ($regiao == 1) { echo " SELECTED ";}?>>Estado de São Paulo </option>
                    <option value='6' <?php if ($regiao == 6) { echo " SELECTED ";}?>>Sul (PR, SC e RS)</option>
                    <option value='2' <?php if ($regiao == 2) { echo " SELECTED ";}?>>Sudeste (RJ, ES e MG)</option>
                    <option value='4' <?php if ($regiao == 4) { echo " SELECTED ";}?>>Nordeste (AL, BA, CE, MA, PB, PE, PI, RN e SE)</option>
                    <option value='3' <?php if ($regiao == 3) { echo " SELECTED ";}?>>Centro-Oeste (GO, MS, MT e DF)</option>
                    <option value='5' <?php if ($regiao == 5) { echo " SELECTED ";}?>>Norte (AC, AP, AM, PA, RO, RR e TO)</option>
                </select>
            </div>
        </div>
        <?php }?>
		<div class="span2"></div>
	</div>
<? } ?>

<? if($login_fabrica == 3){ ?>
	<br />
	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span2">
			<label class="radio">
				<input type="radio" name="tipo_pedido" value="" checked> Todos
			</label>
		</div>
		<div class="span2">
			<label class="radio">
				<input type="radio" name="tipo_pedido" <?= ($_POST["tipo_pedido"] == "3") ? "checked" : ""; ?> value="3"> Garantia
			</label>
		</div>
		<div class="span2">
			<label class="radio">
				<input type="radio" <?= ($_POST["tipo_pedido"] == "2") ? "checked" : ""; ?> name="tipo_pedido" value="2"> Faturado
			</label>
		</div>
		<div class="span3"></div>
	</div>

<? } ?>

        <div class="row-fluid">
        <div class="span2"></div>
                <div class="span4">
                    <label class="control-label" for="">Status do Pedido</label> <span class="add-on" data-toggle="tooltip" data-placement="top" title="Apresenta os status de pedidos referentes ao periodo selecionado"><i class="icon-question-sign"></i></span>
                    <div class="controls controls-row">
                    <? if (isset($_REQUEST['pedido_status'])) {
                        $id_status_pedido =  $_REQUEST['pedido_status'];
                        $sql = "SELECT * FROM tbl_status_pedido WHERE status_pedido = {$id_status_pedido};";
                        $res = pg_query($con, $sql);

                        $status_descricao =pg_result($res,0,descricao);
                     } ?>
                        <select name="pedido_status" id="pedido_status" <?= $_REQUEST['pedido_status'] ? "" : "disabled" ?>>
                        <? if (!isset($_REQUEST['pedido_status'])) { ?>
                            <option>Selecione um período</option>
                        <? } else { ?>
                            <option value='<?= $_REQUEST['pedido_status'] ?>' selected><?= $status_descricao ?></option>
                        <? } ?>
                        </select>
                        <div id="load_pedido"></div>
                    </div>
                </div>
            <div class="span4">
                <label class="control-label" for="">Tipo de Pedido</label>
                <?

                    $sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
                    tbl_tipo_pedido.descricao
                    FROM    tbl_tipo_pedido
                    WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
                    ORDER BY tbl_tipo_pedido.descricao;";
                    $res1 = pg_query($con,$sql);

                if (pg_numrows($res1) > 0) {
                    echo "<select name='tipo' id='tipo_pedido' class='parametros_tabela'>\n";
                        echo "<option value=''>Todos os Pedidos</option>";

                        for ($i = 0 ; $i < pg_numrows ($res1) ; $i++){
                            $aux_tipo      = trim(pg_result($res1,$i,tipo_pedido));
                            $aux_descricao = trim(pg_result($res1,$i,descricao));

                            echo "<option value='$aux_tipo'";
                            if ($aux_tipo == $tipo) echo " selected";
                            echo ">$aux_descricao</option>\n";
                        }
                    echo "</select>";
                } ?>

            </div>
            <div class="span2"></div>
        </div>
    <?php
	if (in_array($login_fabrica, array(169,170))) {
	?>
		<div class="row-fluid" >
			<div class="span2" ></div>
			<div class="span4">
				<label class="control-label" for="">Inspetor</label>
				<div class="controls controls-row">
					<select name="admin_sap" class="span12" >
						<option value="" >Selecione</option>
						<?php
						$sqlInspetor = "
							SELECT admin, nome_completo, login
							FROM tbl_admin
							WHERE fabrica = {$login_fabrica}
							AND ativo IS TRUE
							AND admin_sap IS TRUE
						";
						$resInspetor = pg_query($con, $sqlInspetor);

						while ($row = pg_fetch_object($resInspetor)) {
							$selected = ($row->admin == $_REQUEST["admin_sap"]) ? "selected" : "";
							echo "<option value='{$row->admin}' {$selected} >".((empty($row->nome_completo)) ? $row->login : $row->nome_completo )."</option>";
						}
						?>
					</select>
				</div>
			</div>
            <div class="span4">
				<label class="control-label" for="">Tipo de Posto</label>
				<div class="controls controls-row">
					<select name="tipo_posto" class="span12" >
						<option value="" >Selecione</option>
						<?php
						$sqlTipoPosto = "
							SELECT tipo_posto, descricao
							FROM tbl_tipo_posto
							WHERE fabrica = {$login_fabrica}
							AND ativo IS TRUE
						";
						$resTipoPosto = pg_query($con, $sqlTipoPosto);

						while ($row = pg_fetch_object($resTipoPosto)) {
							$selected = ($row->tipo_posto == $_REQUEST["tipo_posto"]) ? "selected" : "";
							echo "<option value='{$row->tipo_posto}' {$selected} >{$row->descricao}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</div>
	<?php
	}

    if (in_array($login_fabrica, array(158,169,170))) {
    ?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
          <label class="control-label" for="">Estado do Posto Autorizado</label>
          <div class="controls controls-row">
            <select name='estado_posto_autorizado' class='frm'>
                    <option value=''> - Selecione -</option>
                    <?php
                      foreach ($array_estados() as $sigla => $estados) {
                          $ufSelected = ($estado_posto_autorizado == $sigla) ? 'selected="selected"' : '';
                          echo "<option value='{$sigla}' {$ufSelected}>{$estados}</option>";
                      }
                     ?>
                </select>
          </div>
        </div>
    </div>
<?php
    }
    if ($login_fabrica == 101) {
?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <label class="control-label" for="">Destinatário Troca</label>
            <div class="controls controls-row">
                <select name='destinatario_troca' id='destinatario_troca' class='frm' <?=empty($destinatario_troca) ? "disabled" : ""?>>
                    <option value=''> - Selecione -</option>
                    <option value='posto' <?=($destinatario_troca == "posto") ? "selected" : ""?>> Posto Autorizado</option>
                    <option value='consumidor' <?=($destinatario_troca == "consumidor") ? "selected" : ""?>> Consumidor</option>
                </select>
            </div>
        </div>
    </div>
<?php
    }

    if($login_fabrica == 153){ //HD-2921051
        $sql_tipo_posto = "SELECT tipo_posto,descricao FROM tbl_tipo_posto WHERE fabrica = $login_fabrica AND ativo IS TRUE;";
        $res_tipo_posto = pg_query ($con,$sql_tipo_posto);
?>
    <div class="row-fluid">
        <div class="span2"></div>
                <div class="span8">
                    <label class="control-label" for="">Tipo de Posto</label>
                        <div class="controls controls-row">
                        <?php
                            if (pg_num_rows($res_tipo_posto) > 0) {
                                echo "<select name='tipo_posto' id='tipo_posto' >\n";
                                echo "<option value=''>Escolha</option>\n";
                                for ($x = 0 ; $x < pg_num_rows($res_tipo_posto) ; $x++){
                                    $aux_tipo_posto = trim(pg_fetch_result($res_tipo_posto,$x,tipo_posto));
                                    $aux_tipo_posto_descricao  = trim(pg_fetch_result($res_tipo_posto,$x,descricao));

                                    if ($_POST['tipo_posto'] == $aux_tipo_posto || $tipo_posto == $aux_tipo_posto){
                                        $selected_linha = " SELECTED ";
                                        $mostraMsgLinha = "<br> da LINHA $aux_tipo_posto_descricao";
                                    }else{
                                        $selected_linha = "  ";
                                        $mostraMsgLinha = " ";
                                    }

                                    echo "<option value='$aux_tipo_posto' $selected_linha>$aux_tipo_posto_descricao</option>\n";
                                }
                                echo "</select>\n&nbsp;";
                            }
                        ?>
                    </div>
                </div>
            <div class="span1"></div>
    </div>
<?php
    }

    if ($login_fabrica == 1) {
?>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8">
            <label class="control-label" for="">Categoria do Pedido</label>
                <div class="controls controls-row">
                <select name="categoria_pedido" class="frm">
                    <option value="">SELECIONE</option>
                    <option <?=($categoria_pedido == "cortesia") ? "selected" : ""?> value="cortesia">CORTESIA</option>
                    <option <?=($categoria_pedido == "credito_bloqueado") ? "selected" : ""?> value="credito_bloqueado">CRÉDITO BLOQUEADO</option>
                    <option <?=($categoria_pedido == "erro_pedido") ? "selected" : ""?> value="erro_pedido">ERRO DE PEDIDO</optiuon>
                    <option <?=($categoria_pedido == "kit") ? "selected" : ""?> value="kit">KIT DE REPARO</option>
                    <option <?=($categoria_pedido == "midias") ? "selected" : ""?> value="midias">MÍDIAS</option>
                    <option <?=($categoria_pedido == "outros") ? "selected" : ""?> value="outros">OUTROS</option>
                    <option <?=($categoria_pedido == "valor_minimo") ? "selected" : ""?> value="valor_minimo">VALOR MÍNIMO</option>
                    <option <?=($categoria_pedido == "vsg") ? "selected" : ""?> value="vsg">VSG</option>
                </select>
            </div>
        </div>
        <div class="span1"></div>
    </div>

    <br />

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" <?= (strlen($_POST["pdd_rpst"]) > 0) ? "checked" : ""; ?> NAME="pdd_rpst" value="S" >Pedidos Representante
            </label>
        </div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" <?= (strlen($_POST["pedidos_nao_exportados"]) > 0) ? "checked" : ""; ?> NAME="pedidos_nao_exportados">Pedidos Não Exportados
            </label>
        </div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" <?= (strlen($_POST["valor_pedido"]) > 0) ? "checked" : ""; ?> VALUE='T' NAME="valor_pedido">Valor de Pedido
            </label>
        </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" NAME="chk_opt10" <?= (strlen($_POST["chk_opt10"]) > 0) ? "checked" : ""; ?> value="finalizado" rel="10">Pedido Não Finalizado
            </label>
        </div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" NAME="chk_opt11" <?= (strlen($_POST["chk_opt11"]) > 0) ? "checked" : ""; ?> value="promocional" rel="11">Pedido Promocional
            </label>
        </div>
        <div class="span3">
            <label class="checkbox">
                <INPUT TYPE="checkbox" NAME="chk_opt12" <?= (strlen($_POST["chk_opt12"]) > 0) ? "checked" : ""; ?> value="sedex" rel="12">Pedido Sedex
            </label>
        </div>
        <div class="span1"></div>
    </div>
<?php
    }
    $required = (strlen($_POST["data_inicial_01"]) > 0) ? "disabled" : "";
?>
		<div class="row-fluid">
			<div class="span2"></div>
				<div class="span4">
					<label class="radio">
                        <div id="click" style="display:inline-block; position:relative;">
						    <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="1" id='chk_opt1' rel="1" <?= $required ?> <?= ($_POST["chk_opt"] == "1") ? "checked" : ""; ?>>Pedidos Lançados Hoje
                            <div class='click_periodo' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
				<div class="span4">
					<label class="radio">
                        <div id="click" style="display:inline-block; position:relative;">
						    <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="2" rel="2" <?= $required ?> <?= ($_POST["chk_opt"] == "2") ? "checked" : ""; ?>>Pedidos Lançados Ontem
                            <div class='click_periodo' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
				<div class="span4">
					<label class="radio">
                        <div id="click" style="display:inline-block; position:relative;">
						    <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="3" rel="3" <?= $required ?> <?= ($_POST["chk_opt"] == "3") ? "checked" : ""; ?>>Pedidos Lançados Nesta Semana
                            <div class='click_periodo' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
				<div class="span4">
					<label class="radio">
                        <div id="click" style="display:inline-block; position:relative;">
						    <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="4" rel="4" <?= $required ?> <?= ($_POST["chk_opt"] == "4") ? "checked" : ""; ?>>Pedidos Lançados Na Semana Anterior
                            <div class='click_periodo' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
				<div class="span4">
					<label class="radio">
                        <div id="click" style="display:inline-block; position:relative;">
						    <INPUT TYPE="radio" class="checkbox_periodo" NAME="chk_opt" value="5" rel="5" <?= $required ?> <?= ($_POST["chk_opt"] == "5") ? "checked" : ""; ?>>Pedidos Lançados Neste Mês
                            <div class='click_periodo' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
				<? if($login_fabrica <> 1) { ?>
				<div class="span4">
					<label class="checkbox">
                        <div  style="display:inline-block; position:relative;">
						    <INPUT TYPE="checkbox"  NAME="csv" value="csv" rel="4"  <?= ($_POST["csv"] == "csv") ? "checked" : ""; ?>>Download de Arquivo em CSV
                            <div class='' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
					</label>
				</div>
				<? } ?>
			<div class="span2"></div>
		</div>
        <? if($login_fabrica == 160 or $replica_einhell) { ?>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <label class="checkbox">
                        <div  style="display:inline-block; position:relative;">
                            <INPUT TYPE="checkbox" <?= ($_POST["csv_detalhado"] == "csv_detalhado") ? "checked" : ""; ?> NAME="csv_detalhado" value="csv_detalhado" rel="4">Relatório Detalhado por Peça
                            <div class='' style="position:absolute; left:0; right:0; top:0; bottom:0;"></div>
                        </div>
                    </label>
                </div>
            </div>
        <? } ?>
		<br />
		<input class="btn" type="button" onclick="javascript: fcn_valida_formDatas2();" alt="Preencha as opções e clique aqui para pesquisar" value="Pesquisar">
		<br /><br />

</FORM>
<?
$sqlT = "SELECT COUNT(*) as total FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente IS TRUE";
$resT = pg_exec($con,$sqlT);

if(pg_numrows($resT) > 0){
	$total = pg_result($resT,0,0);
	if($total > 0 && !in_array($login_fabrica, array(14,40,152,154,165)) && !isset($FaturamentoManualArquivo)  ){
        if ($login_fabrica != 94) {
?>
		<form name="frm_pesquisa1" METHOD="POST" ACTION="pedido_nao_exportado_consulta.php">
			<button style="width: 40%;" class="btn btn-primary" src="imagens/btn_exibirpedidosnaoexportados.gif" alt="Preencha as opções e clique aqui para pesquisar" onclick="javascript: fcn_valida_formDatas();" style="cursor:pointer ">Pesquisar pedidos não exportados</button>
			<BR />
		</form>

<?php //if($login_fabrica != 88){ // HD 870865 - A Orbis apenas utilizará a tela de Pedidos não exportados para poder informar se o pedido poderá ser exportado ou não, já o faturamento é integrado
        }
?>
		<form name="frm_pesquisa3" method="POST" action="pedido_nao_faturado_consulta.php" id="frm_pesquisa3">
			<button class="btn btn-primary" style="width: 40%;" src="imagens/btn_exibirpedidosnaofaturados.gif" alt="Preencha as opções e clique aqui para pesquisar" onclick="document.getElementById('frm_pesquisa3').submit();">Pesquisar pedidos exportados e não faturados</button>
			<br /><br />
		</form>

	<?php
	}
		// }
		if((in_array($login_fabrica, array(40,152,154,158,165,173,178)) || isset($FaturamentoManualArquivo)) && !in_array($login_fabrica, [167, 203])) {
	?>
	<form name="frm_pesquisa4" method="POST" action="consulta_pedido_nao_faturado.php" id="frm_pesquisa4">

		<a href="consulta_pedido_nao_faturado.php">
			<input class="btn btn-default" type="button" class="btn" id="pedido_faturar" name="pedido_faturar" value="Pedidos a Faturar" style="margin-top: 5px; margin-bottom: 5px;"/>
		</a>
		<a href="upload_faturar_pedido.php">
			<input class="btn btn-success" type="button" class="btn" id="upload_faturamento" name="upload_faturamento" value="Realizar Faturamento" style="margin-top: 5px; margin-bottom: 5px;"/>
		</a>
	<?php if ($login_fabrica == 177) { ?>
		<a href="baixa_pedido_posto_interno.php" target="_blank" class="btn btn-info" type="button" id="pedido_posto_interno" style="margin-top: 5px; margin-bottom: 5px">Pedidos do Posto Interno</a>
	<?php } ?>
		<br /><br />
	</form>
	<?
	}
}
?>
	</div>
</div>
<?php
	if (isset($_REQUEST['btn_acao']) || isset($_POST['form_submit'])) {
		if ($login_fabrica == 1) {
			include "pedido_consulta_blackedecker.php";
		} else {
			include "pedido_consulta.php";
		}
	}
?>
<script>
    jQuery.extend(jQuery.fn.dataTableExt.oSort, {
        "currency-pre": function (a) {
            a = (a === "-") ? 0 : a.replace(/[^\d\-\.]/g, "");
            return parseFloat(a);
        },
        "currency-asc": function (a, b) {
            return a - b;
        },
        "currency-desc": function (a, b) {
            return b - a;
        }
    });
        var tds = $('#tabela_pedidos').find(".titulo_coluna");

        var colunas = [];

        $(tds).find("th").each(function(){
            if ($(this).attr("class") == "date_column") {
                colunas.push({"sType":"date"});
            }if ($(this).attr("class") == "money_column") {
                colunas.push({"sType":"numeric"});
            } else {
                colunas.push(null);
            }
        });

		$.dataTableLoad({ table: "#tabela_pedidos",aoColumns:colunas });
</script>
<? include "rodape.php" ?>
