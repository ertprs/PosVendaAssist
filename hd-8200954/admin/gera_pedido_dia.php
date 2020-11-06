<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
$title = "Cadastro de Dias para Gerar de Pedido";
include 'autentica_admin.php';
include 'funcoes.php';
if(strlen($_GET["msg"]) > 0 ){
	$msg = $_GET["msg"];
}

function trocaStatus($gera_pedido_dia, $status){
	global $login_admin, $con;
	$atualiza = "UPDATE tbl_gera_pedido_dia
						 SET ativo = '{$status}',
						 	 admin_alterou = {$login_admin},
						 	 data_alterou = now()
						 WHERE gera_pedido_dia = {$gera_pedido_dia}";

			pg_query($con, $atualiza);
			$err = pg_last_error($con);
			return $err;
}

function existe($estados, $tipo_posto, $dia_semana, $tipo_pedido){
	global $login_fabrica, $con;
	$existe = array();
	foreach ($estados as $sigla) {
		foreach ($tipo_posto as $tipo) {
			foreach ($dia_semana as $dia) {
				$sql = "SELECT estado
					FROM tbl_gera_pedido_dia
					WHERE fabrica = {$login_fabrica} AND 
					estado = '{$sigla}' AND
					tipo_posto = {$tipo} AND
					tipo_pedido = '{$tipo_pedido}' AND
					dia_semana = {$dia}";

				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$existe[] = pg_fetch_result($res, 0, 'estado');break;
				}
			}
			break;
		}

	}
	if(count($existe) > 0){
		return $existe;
	}else{
		return $existe;
	}
}

if($_POST['btn_acao'] == "inativar"){

	$trocaStatus = trocaStatus($_POST["gera_pedido_dia"], "f");

	if(strlen($trocaStatus) > 0){
		echo "Erro ao alterar";die;
	}else{
		echo "success";
		die;
	}
}else if($_POST['btn_acao'] == "ativar"){

	trocaStatus($_POST["gera_pedido_dia"], "t");

	if(strlen($err) > 0){
		echo "Erro ao alterar";die;
	}else{
		echo "success";
		die;
	}
}

//seleciona estados ja cadastrados para popular o form
if(strlen($_GET["geraPedidoDia"]) > 0){
	$gera_pedido_dia = $_GET["geraPedidoDia"];
	$sql = "SELECT gera_pedido_dia,
				   tipo_posto,	
				   estado,
				   dia_semana,
				   tipo_pedido,
				   ativo
			FROM tbl_gera_pedido_dia
			WHERE gera_pedido_dia = {$gera_pedido_dia}";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$gera_pedido_dia 	= pg_fetch_result($res, 0, "gera_pedido_dia");
		$tipo_posto 		= pg_fetch_result($res, 0, "tipo_posto");
		$estado 			= pg_fetch_result($res, 0, "estado");
		$dia_semana 		= pg_fetch_result($res, 0, "dia_semana");
		$tipo_pedido 		= pg_fetch_result($res, 0, "tipo_pedido");
		$ativo 				= pg_fetch_result($res, 0, "ativo");
	}
}
if($_POST['btn_acao'] == "gravar"){
	if(count($_POST["estado"]) == 0){
		$msg_erro["campos"][] = "estado";
	}

	if(count($_POST["tipo_posto"]) > 0){
		$tipo_posto = $_POST["tipo_posto"];
	}else{
		$msg_erro["campos"][] = "tipo_posto";
	}

	if(count($_POST["dia_semana"]) > 0){
		$dia_semana = $_POST["dia_semana"];

	}else{
		$msg_erro["campos"][] = "dia_semana";
	}

	if(strlen($_POST["tipo_pedido"]) > 0){
		$tipo_pedido = $_POST["tipo_pedido"];

	}else{
		$msg_erro["campos"][] = "tipo_pedido";
	}

	if(strlen($_POST["ativo"]) > 0 ){
		$ativo = "t";
	}else{
		$ativo = "f";
	}
	if(count($msg_erro) > 0){
		$msg_erro["msg"][] = "Preencha os campos obrigatórios.";
	}else{
		if(strlen($_POST["gera_pedido_dia"]) > 0){
			
			$gera_pedido_dia = $_POST["gera_pedido_dia"];

			$atualiza = "UPDATE tbl_gera_pedido_dia
						 SET ativo = '{$ativo}',
						 	 admin_alterou = {$login_admin},
						 	 data_alterou = now(),
						 	 tipo_pedido = {$tipo_pedido}
						 WHERE gera_pedido_dia = {$gera_pedido_dia}";

			pg_query($con, $atualiza);
			$err = pg_last_error($con);
			if(strlen($err) > 0){
				$msg_erro["msg"][] = $err;
				$msg_erro["msg"][] = "Erro ao alterar.";
			}else{
				unset($_POST);
				$gera_pedido_dia = "";
				$tipo_posto	= "";
				$dia_semana	= "";
				$ativo		= "";
				$estado		= "";

				$msg = "Alterado com sucesso.";
				header("Location: $PHP_SELF?msg={$msg}");
			}
			
		}else{
			
			$estado = $_POST['estado'];
			$existe = existe($estado, $tipo_posto, $dia_semana, $tipo_pedido);

			if( count($existe) == 0 ){
				pg_query($con, "BEGIN TRANSACTION");
				foreach ($estado as $sigla) {
					foreach ($tipo_posto as $id) {
						foreach ($dia_semana as $dia) {
							$sql = "INSERT INTO tbl_gera_pedido_dia(
											fabrica,
											tipo_posto,
											estado,
											dia_semana,
											ativo,
											admin_alterou,
											data_alterou,
											tipo_pedido
							) VALUES (
								{$login_fabrica},
								{$id},
								'{$sigla}',
								{$dia},
								'{$ativo}',
								{$login_admin},
								now(),
								{$tipo_pedido}
							)";
							pg_query($con,$sql);
						}
					}
					
				}
				$conErr = pg_last_error($con);
				if(strlen($conErr) > 0 ){
					
					$msg_erro["msg"][] = "Erro ao inserir";
					pg_query($con, "ROLLBACK TRANSACTION");
					
				}else{
					unset($_POST);
					pg_query($con, "COMMIT TRANSACTION");
					$tipo_posto	= "";
					$dia_semana	= "";
					$ativo		= "";
					$estado		= "";
					
					$msg = "Cadastrado com sucesso.";
				}
			}else{
				$msg_erro["msg"][] = "Já existem estados com estes dados: ". implode(" ", $existe);	
			}
		}
		
	}
}


include 'cabecalho_new.php';

$plugins = array(
	"multiselect",
	"dataTable"
);

include("plugin_loader.php");

?>

<script language="javascript">
	
	$(function() {
		$("#estado").multiselect({
		   selectedText: "# of # selected"
		});

		$("#tipo_posto").multiselect({
		   selectedText: "# of # selected"
		});

		$("#dia_semana").multiselect({
		   selectedText: "# of # selected"
		});

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var condicao = $(this).parent().find("input[name=hidden_gera_pedido_dia]").val();
				var el     = $(this);
				
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", gera_pedido_dia: condicao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(el).removeClass("btn-success").addClass("btn-danger");
							$(el).attr({ "name": "inativar"});
							$(el).text("Inativar");
							var td_status = $(el).parents("tr").find(".td_status");

							var img = $(el).parents("tr").find("img[name=img_status_verde]");

							img.remove();
							td_status.html("<img src='imagens/status_verde.png' name='img_status_verde' border='0' align='center' title='Ativo' alt='Ativo'>");
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var condicao = $(this).parent().find("input[name=hidden_gera_pedido_dia]").val();
				var el     = $(this);
				
				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", gera_pedido_dia: condicao },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(el).removeClass("btn-danger").addClass("btn-success");
							$(el).attr({ "name": "ativar"});
							$(el).text("Ativar");
							var td_status = $(el).parents("tr").find(".td_status");

							var img = $(el).parents("tr").find("img[name=img_status_verde]");

							img.remove();
							td_status.html("<img src='imagens/status_vermelho.png' name='img_status_vermelho' border='0' align='center' title='Inativo' alt='Inativo'>");							
						}

						loading("hide");
					}
				});
			}
		});

	});
</script>
<?
//tipos de posto para o combo
$sqlTiposPosto = "SELECT tipo_posto,
						 descricao
				  FROM tbl_tipo_posto WHERE 
				  fabrica = {$login_fabrica} AND
				  descricao <> 'E02'";
$res = pg_query($con, $sqlTiposPosto);
$optionsTipoPosto = array();
$rows = pg_num_rows($res);

if(pg_num_rows($res)>0){
	for($i = 0; $i < $rows; $i++){
		$value = pg_fetch_result($res, $i, "tipo_posto");
		$desc = pg_fetch_result($res, $i, "descricao");
		$optionsTipoPosto[$value] = $desc;
	}
}

//tipos de pedido para o combo
$sqlTipoPedido = "SELECT tipo_pedido,
						 descricao
				  FROM tbl_tipo_pedido
				  WHERE fabrica = {$login_fabrica}";
$res = pg_query($con, $sqlTipoPedido);
$optionsTipoPedido = array();
$rows = pg_num_rows($res);

if(pg_num_rows($res)>0){
	for($i = 0; $i < $rows; $i++){
		$value = pg_fetch_result($res, $i, "tipo_pedido");
		$desc = pg_fetch_result($res, $i, "descricao");
		$optionsTipoPedido[$value] = $desc;
	}
}

//array para options de Estado
$optionsEstado = array( "AC"=>"AC - Acre"                   ,
                        "AL"=>"AL - Alagoas"                ,
                        "AM"=>"AM - Amazonas"               ,
                        "AP"=>"AP - Amapá"                  ,
                        "BA"=>"BA - Bahia"                  ,
                        "CE"=>"CE - Ceará"                  ,
                        "DF"=>"DF - Distrito Federal"       ,
                        "ES"=>"ES - Espírito Santo"         ,
                        "GO"=>"GO - Goiás"                  ,
                        "MA"=>"MA - Maranhão"               ,
                        "MG"=>"MG - Minas Gerais"           ,
                        "MS"=>"MS - Mato Grosso do Sul"     ,
                        "MT"=>"MT - Mato Grosso"            ,
                        "PA"=>"PA - Pará"                   ,
                        "PB"=>"PB - Paraíba"                ,
                        "PE"=>"PE - Pernambuco"             ,
                        "PI"=>"PI - Piauí"                  ,
                        "PR"=>"PR - Paraná"                 ,
                        "RJ"=>"RJ - Rio de Janeiro"         ,
                        "RN"=>"RN - Rio Grande do Norte"    ,
                        "RO"=>"RO - Rondônia"               ,
                        "RR"=>"RR - Roraima"                ,
                        "RS"=>"RS - Rio Grande do Sul"      ,
                        "SC"=>"SC - Santa Catarina"         ,
                        "SE"=>"SE - Sergipe"                ,
                        "SP"=>"SP - São Paulo"              ,
                        "TO"=>"TO - Tocantins"
                    );

$options_dia_semana = array(
				"1"	=> "Segunda-Feira",
				"2"	=> "Terça-Feira",
				"3"	=> "Quarta-Feira",
				"4"	=> "Quinta-Feira",
				"5"	=> "Sexta-Feira",
				"6"	=> "Sábado",
				"7"	=> "Domingo"
);

if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<? if (strlen($msg) > 0 AND count($msg_erro["msg"])==0) { ?>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>
<? } ?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_produto" method="post" action="<?=$PHP_SELF ?>" <?php echo $onsubmit;?> class='form-search form-inline tc_formulario'>
<?if(strlen($gera_pedido_dia) > 0){
    ?><div class="titulo_tabela">Alterando cadastro</div><?
}else{
    ?><div class="titulo_tabela">Cadastro</div><?
}?>

        <br/>
<input type="hidden" name="gera_pedido_dia" id="gera_pedido_dia" value="<? echo $gera_pedido_dia;?>" />
<div class="row-fluid">
	<div class="span2"></div>

	<div class="span4">
		<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='estado'>Estado</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<h5 class='asteristico'>*</h5>
					<select name="estado[]" id="estado" multiple="multiple" >
						<?php
						foreach ($optionsEstado as $sigla => $desc) {
								
									$selected_estado = ( ($sigla == $estado) || in_array($sigla, $estado)) ? "SELECTED" : '' ;
								
						?>
							<option value="<?php echo $sigla?>" <?php echo $selected_estado ?> >
								<?php echo $desc?>
							</option>
						<?php
						}
						?>
					</select>
				</div>	
			</div>
		</div>
	</div>

	<div class="span4">
		<div class='control-group <?=(in_array("tipo_posto", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='tipo_posto'>Tipo Posto</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<h5 class='asteristico'>*</h5>
					<select name="tipo_posto[]" id="tipo_posto" multiple="multiple" >
						<?php

						foreach ($optionsTipoPosto as $key => $desc) {
								
								$selected_tpPosto = ( ($key == $tipo_posto) || in_array($key, $tipo_posto)) ? "SELECTED" : '' ;
								
						?>
							<option value="<?php echo $key?>" <?php echo $selected_tpPosto ?> >
								<?php echo $desc?>
							</option>
						<?php
						}
						?>
					</select>
				</div>	
			</div>
		</div>
	</div>
	<div class="span2"></div>
</div>

<div class="row-fluid">
	<div class="span2"></div>
	<div class="span4">
		<div class='control-group <?=(in_array("dia_semana", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='estado'>Dia Semana</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<h5 class='asteristico'>*</h5>
					<select name="dia_semana[]" id="dia_semana" multiple="multiple" >
						<?php
						
						foreach ($options_dia_semana as $key => $desc) {
								
								$selected_dia_semana = ( ($key == $dia_semana) || in_array($key, $dia_semana) ) ? "SELECTED" : '' ;
								
						?>
							<option value="<?php echo $key?>" <?php echo $selected_dia_semana ?> >
								<?php echo $desc?>
							</option>
						<?php
						}
						?>
					</select>
				</div>	
			</div>
		</div>
	</div>
	<div class="span4">
		<div class='control-group <?=(in_array("tipo_pedido", $msg_erro["campos"])) ? "error" : ""?>'>
			<label class='control-label' for='estado'>Tipo Pedido</label>
			<div class='controls controls-row'>
				<div class='span4'>
					<h5 class='asteristico'>*</h5>
					<select name="tipo_pedido" id="tipo_pedido">
					<option value="">Selecione</option>
						<?php
						
						foreach ($optionsTipoPedido as $key => $desc) {
								$selected_tipo_pedido = ( ($key == $tipo_pedido) || in_array($key, $tipo_pedido) ) ? "SELECTED" : '' ;
								
						?>
							<option value="<?php echo $key?>" <?php echo $selected_tipo_pedido ?> >
								<?php echo $desc?>
							</option>
						<?php
						}
						?>
					</select>
				</div>	
			</div>
		</div>
	</div>
	<div class="span2">
		<div class='control-group <?=(in_array("ativo", $msg_erro["campos"])) ? "error" : ""?>'>
		<label class='control-label' for='ativo'></label>
			<div class='controls controls-row'>
				<div class="span">
					<label class="checkbox">
						<input type='checkbox' name='ativo' id="ativo" value='t' <?if($ativo == 't') echo "CHECKED";?> > Ativo
					</label>
				</div>
			</div>
		</div>
	</div>
	<div class="span2"></div>
</div>

<div class="row-fluid">
    <!-- margem -->
    <div class="span4"></div>

    <div class="span4">
        <div class="control-group">
            <div class="controls controls-row tac">
            	<button class='btn' id="btn_acao" 	type="button"  onclick="submitForm($(this).parents('form'),'gravar');">Gravar</button>
            	<button class='btn' id="btn_listar" type="button"  onclick="window.location='<?echo $PHP_SELF;?>?listartudo=1'">Listar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' />
                <? if (strlen($gera_pedido_dia) > 0){?>
                    <button type="button" class="btn btn-warning" value="Limpar" onclick="javascript:  window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos">Limpar</button>
                <?}?>
            </div>
        </div>
    </div>

    <!-- margem -->
    <div class="span4"></div>
</div>
</form>
<!-- Listagem -->
<?
if($_GET["listartudo"] == 1){
	$sql = "SELECT 	gera_pedido_dia,
					estado,
					tbl_gera_pedido_dia.tipo_posto,
					tbl_tipo_posto.descricao as desc_tipo_posto,
					dia_semana,
					tbl_gera_pedido_dia.tipo_pedido,
					tbl_gera_pedido_dia.ativo
			FROM tbl_gera_pedido_dia
			JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_gera_pedido_dia.tipo_posto
			LEFT JOIN tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido =  tbl_gera_pedido_dia.tipo_pedido
			WHERE tbl_gera_pedido_dia.fabrica = {$login_fabrica}";

	$res = pg_query($con, $sql);
	$numRows = pg_num_rows($res);
	if($numRows > 0){ 
		
	?>
	
		<table id="listagemGeraPedidoDia" style="margin: 0 auto;" class="table table-striped table-bordered table-hover table-fixed">
			<thead>
				<tr class="titulo_coluna">
					<th>Estado</th>
					<th>Tipo Posto</th>
					<th>Dia Semana</th>
					<th>Tipo Pedido</th>
					<th>Ativo</th>
					<th>Ações</th>
				</tr>
				<tbody>

				
				<?
					for ($i=0; $i < $numRows; $i++) {	
						$gera_pedido_dia = pg_fetch_result($res, $i, "gera_pedido_dia");
						$ativo = pg_fetch_result($res, $i, "ativo");
					?>
						<tr>
							<td><a href="<? echo $PHP_SELF."?geraPedidoDia={$gera_pedido_dia}";?>"><?=$optionsEstado[pg_fetch_result($res, $i, "estado")]?></a></td>
							<td><?=pg_fetch_result($res, $i, "desc_tipo_posto")?></td>
							<td><?=$options_dia_semana[pg_fetch_result($res, $i, "dia_semana")]?></td>
							<td><?=$optionsTipoPedido[pg_fetch_result($res, $i, "tipo_pedido")]?></td>
							<td class="tac td_status">
								<?if($ativo == "t"){?>
									<img src='imagens/status_verde.png' name="img_status_verde" border='0' align="center" title='Inativo' alt='Ativo'>
								<?}else{?>
									<img src='imagens/status_vermelho.png' name="img_status_vermelho" border='0' align="center" title='Inativo' alt='Inativo'>
								<?}?>
							</td>
							<td class="tac" >
								<input type="hidden" name="hidden_gera_pedido_dia" value="<?=$gera_pedido_dia?>" />
								<?if($ativo == "t"){?>
									<button type='button' name="inativar" class='btn btn-danger' >Inativar</button>
								<?}else{?>
									<button type='button' name="ativar" class='btn btn-success'>Ativar</button> 
								<?}?>
							</td>
						</tr>
					<?}?>
				</tbody>
			</thead>
		</table>
		<script>
		    var obj = new Object();
		    obj['table'] = $("#listagemGeraPedidoDia");
		    obj['type'] = 'basic';
		    obj['config'] = null;    
		    $.dataTableLoad(obj);
		</script>
	<? }else{ 
  ?>
		
    	<div class="alert">
        	<h4>Nenhum registro encontrado</h4>
    	</div>  
    	
  <? 
	}
}?>

<? include "rodape.php"; ?>
