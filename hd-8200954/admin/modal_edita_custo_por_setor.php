<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
function geraValorBD($valor) {
    $valorTratado = str_replace(",",".",str_replace(".","",$valor));
    return $valorTratado;
}
function geraValorReais($valor) {
    $valorTratado = 'R$ '.number_format($valor, 2, ',', '.');
    return $valorTratado;
}

if ($_GET["callcenter"]) {
    $callcenter = $_GET["callcenter"];
    $admin = $_REQUEST["admin"];
    $setor = $_REQUEST["setor"];

	$sql = "SELECT tbl_hd_chamado_custo.*, tbl_hd_chamado_categoria_custo.descricao 
	          FROM tbl_hd_chamado_custo 
	          JOIN tbl_hd_chamado_categoria_custo USING(hd_chamado_categoria_custo,fabrica) 
	         WHERE tbl_hd_chamado_custo.hd_chamado = {$callcenter}
	           AND tbl_hd_chamado_custo.hd_chamado_categoria_custo = {$setor}";
	$res = pg_query($con, $sql);

    
if ($_POST["ajax"]  == "sim") {

	$taxa_banco     = geraValorBD($_POST["taxa_banco"]);
	$juros 			= geraValorBD($_POST["juros"]);
	$frete_ida 		= geraValorBD($_POST["frete_ida"]);
	$frete_volta 	= geraValorBD($_POST["frete_volta"]);
	$reentrega 		= geraValorBD($_POST["reentrega"]);
	$reprocesso     = geraValorBD($_POST["reprocesso"]);
	$extras         = geraValorBD($_POST["extras"]);
	$id 			= $_POST["id"];
	if (strlen($id) == 0) {
		$msg_erro["msg"][] = "Custo não encontrado";
	} else {
		$sql = "SELECT hd_chamado_custo 
		          FROM tbl_hd_chamado_custo 
		         WHERE fabrica=".$login_fabrica." 
		         AND hd_chamado_custo=".$id;
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = "Custo não encontrado";
		}
	}

	if (count($msg_erro["msg"]) == 0) {


		if (strlen($taxa_banco) > 0) {
			$campos  .= ",taxa_banco='{$taxa_banco}'";
		}

		if (strlen($juros) > 0) {
			$campos  .= ",juros='{$juros}'";
		}

		if (strlen($frete_ida) > 0) {
			$campos  .= ",frete_ida='{$frete_ida}'";
		}

		if (strlen($frete_volta) > 0) {
			$campos  .= ",frete_volta='{$frete_volta}'";
		}

		if (strlen($reentrega) > 0) {
			$campos  .= ",reentrega='{$reentrega}'";
		}

		if (strlen($reprocesso) > 0) {
			$campos  .= ",reprocesso='{$reprocesso}'";
		}

		if (strlen($extras) > 0) {
			$campos  .= ",extras='{$extras}'";
		}
		$msg = "";
		$msg_erro = [];
	    $sql = "UPDATE tbl_hd_chamado_custo SET admin={$admin}{$campos} WHERE hd_chamado_custo=".$id;
	    $res = pg_query($con, $sql);
	    if (pg_last_error()) {
			$msg_erro["msg"][] = "Erro ao gravar".pg_last_error();
	    } else {
	    	$msg = "Gravado com sucesso";
	    }

	    if (count($msg_erro["msg"]) > 0) {
	    	exit(json_encode(["erro" => true, "msg" => implode("<br>", $msg_erro["msg"])]));
	    }
	    exit(json_encode(["erro" => false, "msg" => $msg]));
	} else {
		exit(json_encode(["erro" => true, "msg" => implode("<br>", $msg_erro["msg"])]));
	}
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
	<style>
		.titulo_coluna{
			padding: 5px;
		}
	</style>
        <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <?php
        $plugins = array(
            "shadowbox",
            "price_format",
            "bootstrap3",
        );
        include("plugin_loader.php");

        ?>
        <script language="javascript">
            $(function() {

            	$(document).on("click", ".btn_edita", function(){
            		var id = $(this).data("id");
					$(".tr_list_"+id).hide();
					$(".tr_campos_"+id).show();
                });

            	$(document).on("click", ".btn_cancela", function(){
            		var id = $(this).data("id");
					$(".tr_list_"+id).show();
					$(".tr_campos_"+id).hide();
                });
            	$(document).on("click", ".btn_grava", function(){
            		var id = $(this).data("id");
					//$(".tr_list_"+id).show();
					var taxa_banco = $(".tr_campos_"+id).find(".cs_taxa_banco").val();
					var juros = $(".tr_campos_"+id).find(".cs_juros").val();
					var frete_ida = $(".tr_campos_"+id).find(".cs_frete_ida").val();
					var frete_volta = $(".tr_campos_"+id).find(".cs_frete_volta").val();
					var reentrega = $(".tr_campos_"+id).find(".cs_reentrega").val();
					var reprocesso = $(".tr_campos_"+id).find(".cs_reprocesso").val();
					var extras = $(".tr_campos_"+id).find(".cs_extras").val();

					$.ajax({
		                url: window.location,
		                type: "POST",
		                data: {
		                	ajax: 'sim', 
		                	taxa_banco: taxa_banco, 
		                	juros: juros, 
		                	frete_ida: frete_ida, 
		                	frete_volta: frete_volta, 
		                	reentrega: reentrega, 
		                	reprocesso: reprocesso, 
		                	extras: extras, 
		                	id: id
		                },
		                timeout: 6000
		            }).fail(function(){
						$("#mensagem_erro").html("Erro ao gravar");
		            }).done(function(data, idx) {
		            	data = JSON.parse(data);
		            	if (data.erro === true) {
		            		$("#mensagem_erro").show();
		            		$("#mensagem_sucesso").hide();
		            		$("#mensagem_erro").html(data.msg);
		            	} else {
		            		$("#mensagem_erro").hide();
		            		$("#mensagem_sucesso").show();
		            		$("#mensagem_sucesso").html(data.msg);
		            		setTimeout(function(){
		            			window.location.reload();
		            			 window.parent.carrega_custos_atendimentos('<?php echo $callcenter;?>');
		            		}, 1000)
		            	}
		            });
                });

            	$(".btn-nao").click(function(){
                   window.parent.Shadowbox.close();
                   window.parent.carrega_custos_atendimentos('<?php echo $callcenter;?>');
                });
            	$(".cs_taxa_banco").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_juros").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_frete_ida").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_frete_volta").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_reentrega").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_reprocesso").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$(".cs_extras").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            });
        </script>
       
    </head>
    <body style="margin: 0px;padding: 0px;background: #fff">
    	<div id="forms">
    		
    	
        <?php

            $sqlArr = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$callcenter}";
            $resArr = pg_query($con, $sqlArr);
            if (pg_last_error()) {
                exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
            }

            $dados = json_decode(pg_fetch_result($resArr, 0, 'array_campos_adicionais'), 1);
            extract($dados);
         
        ?>
        <div class='titulo_coluna'>
            <h2 style='color:#fff;font-size:18px;margin-top: 10px;padding-bottom: 10px;'>Selecione qual custo  deseja ediar</h2>
        </div>
        	<div id="mensagem_erro" style="display: none;" class="alert alert-danger"></div>
        	<div id="mensagem_sucesso" style="display: none;" class="alert alert-success"></div>
        	<script>
        		/*setTimeout(function(){
        			$("#div_confirm").hide();
        			window.parent.carrega_custos_atendimentos('<?php echo $callcenter;?>');
        			window.parent.Shadowbox.close();
        		}, 1000);*/
        	</script>
        <?php 
	        if (pg_num_rows($res) > 0) {
	    	
				   $conteudo = "<table border='0' class='table table-hover table-bordered' cellspacing='2' cellpadding='2' width='100%'>
                            <thead>
                                <tr>
                                    <th nowrap class='titulo_coluna'>Setor</th>
                                    <th nowrap class='titulo_coluna'>Taxa Banco</th>
                                    <th nowrap class='titulo_coluna'>Juros</th>
                                    <th nowrap class='titulo_coluna'>Frete Ida</th>
                                    <th nowrap class='titulo_coluna'>Frete Volta</th>
                                    <th nowrap class='titulo_coluna'>Reentrega</th>
                                    <th nowrap class='titulo_coluna'>Reprocesso / Descarte</th>
                                    <th nowrap class='titulo_coluna'>Custos Extras</th>
                                    <th nowrap class='titulo_coluna'>Ação</th>
                                </tr>
                            </thead>
                            <tbody>

            ";
            foreach (pg_fetch_all($res) as $key => $row) {
				$subtotal = ($row["taxa_banco"]+$row["juros"]+$row["frete_ida"]+$row["frete_volta"]+$row["reentrega"]+$row["reprocesso"]+$row["extras"]);

                $conteudo .= "
                            <tr class='tr_list_".$row["hd_chamado_custo"]."'>
                                <td nowrap class='tac'>".$row["descricao"]."</td>
                                <td nowrap class='tac'>".geraValorReais($row["taxa_banco"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["juros"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["frete_ida"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["frete_volta"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["reentrega"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["reprocesso"])."</td>
                                <td nowrap class='tac'>".geraValorReais($row["extras"])."</td>
                                <td nowrap class='tac' colspan='2'>
                                	<button data-id='".$row['hd_chamado_custo']."' class='btn_editar btn_edita' type='button'>
                                	Editar
                                	</button>
                                </td>
                            </tr>
                            <tr class='tr_campos_".$row["hd_chamado_custo"]."' style='display:none;'>
                                <td nowrap class='tac'>".$row["descricao"]."</td>
                                <td nowrap class='tac'><input size='8' class='tac cs_taxa_banco' type='text' name='taxa_banco' value='".($row["taxa_banco"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_juros' type='text' name='juros' value='".($row["juros"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_frete_ida' type='text' name='frete_ida' value='".($row["frete_ida"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_frete_volta' type='text' name='frete_volta' value='".($row["frete_volta"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_reentrega' type='text' name='reentrega' value='".($row["reentrega"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_reprocesso' type='text' name='reprocesso' value='".($row["reprocesso"])."' /></td>
                                <td nowrap class='tac'><input size='8' class='tac cs_extras' type='text' name='extras' value='".($row["extras"])."' /></td>
                                <td nowrap class='tac' colspan='2'>
                                	<button  class='btn_grava' data-id='".$row["hd_chamado_custo"]."' type='button'>
                                	Gravar
                                	</button> 
                                	<button data-id='".$row["hd_chamado_custo"]."' class='btn_cancela ' type='button'>
                                	Cancelar
                                	</button>
                                </td>
                            </tr>";
            }

            $conteudo .= "</tbody></table>";
            	echo $conteudo;


	    	} else {
   				 exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
			}?>
		</div>
    </body>
</html>


<?php } else {
    exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
}
?>

