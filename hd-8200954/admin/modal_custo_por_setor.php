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
if ($_GET["callcenter"]) {
    $callcenter = $_GET["callcenter"];
    $admin = $_REQUEST["admin"];
    
if ($_POST) {

	$cs_taxa_banco 		= geraValorBD($_POST["cs_taxa_banco"]);
	$cs_juros 			= geraValorBD($_POST["cs_juros"]);
	$cs_frete_ida 		= geraValorBD($_POST["cs_frete_ida"]);
	$cs_frete_volta 	= geraValorBD($_POST["cs_frete_volta"]);
	$cs_reentrega 		= geraValorBD($_POST["cs_reentrega"]);
	$cs_reprocesso 		= geraValorBD($_POST["cs_reprocesso"]);
	$cs_extras 			= geraValorBD($_POST["cs_extras"]);
	$cs_setor 			= $_POST["cs_setor"];

	if (strlen($cs_setor) == 0) {
		$msg_erro["msg"][] = "Selecione o Setor";
	} else {
		$sql = "SELECT hd_chamado_categoria_custo 
		          FROM tbl_hd_chamado_categoria_custo 
		         WHERE fabrica=".$login_fabrica." 
		         AND hd_chamado_categoria_custo=".$cs_setor;
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = "Setor não encontrado";
		}
	}

	if (count($msg_erro["msg"]) == 0) {

		$campos  = ",hd_chamado";
		$valores = ",{$callcenter}";

		if (strlen($cs_setor) > 0) {
			$campos  .= ",hd_chamado_categoria_custo";
			$valores .= ",{$cs_setor}";
		}

		if (strlen($cs_taxa_banco) > 0) {
			$campos  .= ",taxa_banco";
			$valores .= ",'{$cs_taxa_banco}'";
		}

		if (strlen($cs_juros) > 0) {
			$campos  .= ",juros";
			$valores .= ",'{$cs_juros}'";
		}

		if (strlen($cs_frete_ida) > 0) {
			$campos  .= ",frete_ida";
			$valores .= ",'{$cs_frete_ida}'";
		}

		if (strlen($cs_frete_volta) > 0) {
			$campos  .= ",frete_volta";
			$valores .= ",'{$cs_frete_volta}'";
		}

		if (strlen($cs_reentrega) > 0) {
			$campos  .= ",reentrega";
			$valores .= ",'{$cs_reentrega}'";
		}

		if (strlen($cs_reprocesso) > 0) {
			$campos  .= ",reprocesso";
			$valores .= ",'{$cs_reprocesso}'";
		}

		if (strlen($cs_extras) > 0) {
			$campos  .= ",extras";
			$valores .= ",'{$cs_extras}'";
		}
		$msg = "";
		$msg_erro = [];
	    $sql = "INSERT INTO  tbl_hd_chamado_custo (admin,fabrica{$campos}) VALUES ({$admin},{$login_fabrica}{$valores})";
	    $res = pg_query($con, $sql);
	    if (pg_last_error()) {
			$msg_erro["msg"][] = "Erro ao gravar";
	    } else {
	    	$msg = "Gravado com sucesso";
	    }

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

            	$(".btn-sim").click(function(){

					$("#div_confirm").hide();
					$("#forms").show();
                   window.parent.$("#sb-wrapper").css({width: '600px', left: '394px',top: '130px'});
                   window.parent.$("#sb-wrapper-inner").css({ height: '400px'});
                });

            	$(".btn-nao").click(function(){
                   window.parent.Shadowbox.close();
                   window.parent.carrega_custos_atendimentos('<?php echo $callcenter;?>');
                });
            	$("input[name=cs_taxa_banco]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_juros]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_frete_ida]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_frete_volta]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_reentrega]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_reprocesso]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_extras]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	$("input[name=cs_total]").priceFormat({
                    prefix: '',
                    centsSeparator: ',',
                    thousandsSeparator: '.'
                });
            	
            });
        </script>
       
    </head>
    <body style="margin: 0px;padding: 0px;background: #f5f5f5">
    	<div id="div_confirm" class="well tac" style="padding-bottom: 48px;padding-top: 48px;margin-bottom: 0px;border: none;">
    		<h4>
    			O atendimento Gerou Custo para seu Setor?
    		</h4>
    		<button type="button" class="btn btn-lg btn-sim btn-success">Sim</button>
    		<button type="button" class="btn btn-lg btn-nao btn-danger">Não</button>
    	</div>
    	<div id="forms" style="display: none;">
    		
    	
        <?php

            $sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = {$callcenter}";
            $res = pg_query($con, $sql);
            if (pg_last_error()) {
                exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
            }

            $dados = json_decode(pg_fetch_result($res, 0, 'array_campos_adicionais'), 1);
            extract($dados);
         
        ?>
        <div class='titulo_coluna'>
            <h2 style='color:#fff;font-size:18px;margin-top: 10px;padding-bottom: 10px;'>Custos para seu Setor</h2>
        </div>
        <?php if (count($msg_erro["msg"]) > 0) {?>
        	<div class="alert alert-danger"><?php echo implode("<br>", $msg_erro["msg"]);?></div>
        <?php }?>
        <?php if (strlen($msg) > 0) {?>
        	<div class="alert alert-success"><?php echo $msg;?></div>
        	<script>
        		setTimeout(function(){
        			$("#div_confirm").hide();
        			window.parent.carrega_custos_atendimentos('<?php echo $callcenter;?>');
        			window.parent.Shadowbox.close();
        		}, 1000);
        	</script>
        <?php }?>
        <form action="modal_custo_por_setor.php?callcenter=<?php echo $callcenter;?>" method="post">
        	<input type="hidden" name="admin" value="<?php echo $admin;?>">
	        <div class="container" style="width: 580px">
		        <div class="row">
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Taxa Banco</label>
		        		<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_taxa_banco;?>"  name='cs_taxa_banco'  class="form-control input-lg">
						</div>
		        	</div>
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Juros</label>
						<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_juros;?>" name='cs_juros'  class="form-control input-lg">
						</div>
		        	</div>
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Frete Ida</label>
		        		<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_frete_ida;?>" name='cs_frete_ida'  class="form-control input-lg">
						</div>
		        	</div>
		        </div><br/>
		        <div class="row">
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Frete Volta</label>
						<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_frete_volta;?>" name='cs_frete_volta'  class="form-control input-lg">
						</div>
		        	</div>
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Reentrega</label>
		        		<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_reentrega;?>" name='cs_reentrega'  class="form-control input-lg">
						</div>
		        	</div>
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Reprocesso / Descarte</label>
						<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_reprocesso;?>" name='cs_reprocesso'  class="form-control input-lg">
						</div>
		        	</div>
		        </div><br>
		        <div class="row">
		        	<div class="col-xs-4 col-sm-4 col-md-4">
						<label>Custos Extras</label>
		        		<div class="input-group">
						  	<span class="input-group-addon" id="basic-addon2">R$</span>
						  	<input type='text' value="<?php echo $cs_extras;?>" name='cs_extras'  class="form-control input-lg">
						</div>
		        	</div>
		        	<div class="col-xs-8 col-sm-8 col-md-8">
						<label>Setor</label>
		        		<select name="cs_setor" class="form-control input-lg">
		        			<option value="">Selecione ...</option>
		        			<?php 
		        				$sql = "SELECT * FROM tbl_hd_chamado_categoria_custo WHERE fabrica=".$login_fabrica;
		        				$res = pg_query($con, $sql);
		        				foreach (pg_fetch_all($res) as $key => $value) {
		        					echo '<option value="'.$value['hd_chamado_categoria_custo'].'">'.$value['descricao'].'</option>';
		        				}

		        			?>
		        		</select>
		        	</div>
		        </div>
			</div>
	        <div style="background: #eee;width: 100%;padding: 10px;margin-top: 20px;
	        text-align: center;">
	        	<button type='submit' class="btn btn-success">Gravar</button>
	        </div>
	    </form>
		</div>
    </body>
</html>


<?php } else {
    exit("<div class='alert alert-danger'> Nenhum dados cadastrados</div>");
}
?>

