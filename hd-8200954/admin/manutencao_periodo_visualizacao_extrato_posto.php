<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';
$lista_postos_credenciados = 't';

//Funcões
function mascara($val, $mascara){
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mascara)-1; $i++){
		if($mascara[$i] == '#'){
			if(isset($val[$k]))
				$maskared .= $val[$k++];
		}else{
			if(isset($mascara[$i]))
				$maskared .= $mascara[$i];
		}
	}
	return $maskared;
}

//AJAX
if($_POST["visualizacao_extrato_mes"] == "true"){
	$valor_mes = $_POST['valor'];
	$cod_posto = $_POST['cod_posto'];

	$sql_pa = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$cod_posto};";
	$res_pa = pg_query($con,$sql_pa);
	$parametros_adicionais_json = pg_fetch_result($res_pa, 0, 'parametros_adicionais');

	$parametros_adicionais_json = json_decode($parametros_adicionais_json,true);
	$valor_mes_ant = $parametros_adicionais_json["meses_extrato"];

	if($valor_mes >= 3 AND $valor_mes <= 36 ){
		
		$parametros_adicionais_json["meses_extrato"] = $valor_mes;
		$parametros_adicionais_json = json_encode($parametros_adicionais_json);

		pg_query($con,'BEGIN');

		$sql_up = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametros_adicionais_json}' WHERE fabrica = {$login_fabrica} AND posto = {$cod_posto};";
		$res_up = pg_query($con,$sql_up);

		if(strlen(pg_last_error($con)) > 0) {
	    	$result["erro"] = utf8_encode("Não foi possível atualizar o Posto!");
	    	$result["ant"] = $valor_mes_ant;
	    	pg_query($con,'ROLLBACK');
	  	}else{
	    	$result["ok"] = utf8_encode("Procedimento atualizado com sucesso!");	    	
	    	pg_query($con,'COMMIT');
	  	}
	}else{
		$result["erro"] = utf8_encode("Valor mês inválido!");
	    $result["ant"] = $valor_mes_ant;
	}	

	echo json_encode($result);
	exit;
}

if($_POST["btn_acao"] == "Cadastrar") {

	$valor_mes = $_POST['periodo_mes'];	
	$todos_postos = $_POST['todos_posto'];
	$cod_posto = $_POST['codigo_posto'];

	if ($valor_mes < 3) {
		$msg_erro["msg"][]    = "Favor selecionar o período!";
		$msg_erro["campos"][] = "periodo_mes";		
	}

	if (strlen($todos_postos) == 0 AND strlen($cod_posto) == 0 ) {
		$msg_erro["msg"][]    = "Favor selecionar o Posto ou Todos os Postos!";
	}
	

	if (count($msg_erro["msg"]) == 0) {
		if ($todos_postos == 'todos_postos') {
			$sql_todos = "SELECT 	tbl_posto_fabrica.posto as posto,
									tbl_posto.nome as nome_posto, 
									tbl_posto.cnpj as cnpj,
									tbl_posto_fabrica.parametros_adicionais as parametros_adicionais
							FROM tbl_posto_fabrica 
							JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
							WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
							AND tbl_posto_fabrica.credenciamento in ('CREDENCIADO')
							ORDER BY nome_posto ; ";
			$res_todos = pg_query($con,$sql_todos);

			if (pg_num_rows($res_todos) > 0) {
				for ($i=0; $i < pg_num_rows($res_todos) ; $i++) { 
					$cod_posto 						= pg_fetch_result($res_todos, $i, posto);
					$posto_nome 					= pg_fetch_result($res_todos, $i, nome_posto);
					$parametros_adicionais_todos 	= pg_fetch_result($res_todos, $i, parametros_adicionais);

					$parametros_adicionais_todos = json_decode($parametros_adicionais_todos, true);

					$parametros_adicionais_todos['meses_extrato'] = $valor_mes;

					$parametros_adicionais_todos = json_encode($parametros_adicionais_todos);

					pg_query($con,'BEGIN');
					$sql_up = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametros_adicionais_todos}' WHERE fabrica = {$login_fabrica} AND posto = {$cod_posto};";
					$res_up = pg_query($con,$sql_up);
					
					//echo pg_last_error($con);
					if(strlen(pg_last_error($con)) > 0){
				    	$msg_erro["msg"][]    = "Não foi possível atualizar o posto: $posto_nome.<br>";
				    	pg_query($con,'ROLLBACK');
				  	}else{
				  		$msg_ok["ok"] = "Todos os postos foram atualizados com sucesso!";	    	
				    	pg_query($con,'COMMIT');
				  	}
				}
			}		
		}else{			

			if (strlen($cod_posto)>0) {
				$sql_unico = "SELECT 	tbl_posto_fabrica.posto as posto,
										tbl_posto.nome as nome_posto, 
										tbl_posto.cnpj as cnpj,
										tbl_posto_fabrica.parametros_adicionais as parametros_adicionais
								FROM tbl_posto_fabrica 
								JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
								WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
								AND tbl_posto_fabrica.codigo_posto = '{$cod_posto}'
								AND tbl_posto_fabrica.credenciamento in ('CREDENCIADO')
								ORDER BY nome_posto ; ";
				$res_unico = pg_query($con,$sql_unico);

				// echo pg_last_error($con);
				if (pg_num_rows($res_unico) == 1) {
					$posto_codigo					= pg_fetch_result($res_unico, 0, posto);
					$posto_nome 					= pg_fetch_result($res_unico, 0, nome_posto);
					$parametros_adicionais_todos 	= pg_fetch_result($res_unico, 0, parametros_adicionais);

					$parametros_adicionais_todos = json_decode($parametros_adicionais_todos, true);

					$parametros_adicionais_todos['meses_extrato'] = $valor_mes;

					$parametros_adicionais_todos = json_encode($parametros_adicionais_todos);

					pg_query($con,'BEGIN');
					$sql_up = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametros_adicionais_todos}' WHERE fabrica = {$login_fabrica} AND posto = {$posto_codigo};";
					$res_up = pg_query($con,$sql_up);

					// echo pg_last_error($con);
					if(strlen(pg_last_error($con)) > 0){
				    	$msg_erro["msg"][]    = "Não foi possível atualizar o posto: $posto_nome.<br>";
				    	pg_query($con,'ROLLBACK');
				  	}else{
				  		$msg_ok["ok"] = "Atualizado com sucesso!";	    	
				    	pg_query($con,'COMMIT');
				  	}
					
				}
				
			}
		}	
	}
}

if ($lista_postos_credenciados == 't') {
	$sql_tabela = "SELECT 	tbl_posto_fabrica.posto as posto,
							tbl_posto.nome as nome_posto, 
							tbl_posto.cnpj as cnpj,
							tbl_posto_fabrica.parametros_adicionais as parametros_adicionais
					FROM tbl_posto_fabrica 
					JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
					AND tbl_posto_fabrica.credenciamento in ('CREDENCIADO')
					ORDER BY nome_posto ; ";
	$res_tabela = pg_query($con,$sql_tabela);

}


$layout_menu = "gerencia";
$title = "CADASTRO / CONSULTA - Período visualização extrato.";

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	//"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");
?>

<script type="text/javascript">
$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
	
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	var table = new Object();
	table['table'] = '#postos_credenciados';
	table['type'] = 'full';
	$.dataTableLoad(table);

	$("input.descricao_posto").change(function(){
		var valor = $(this).val();
		var cod_posto = $(this).data("codigo-posto");
		var obj = $(this);

		$.ajax({
			url: "<?=$_SERVER['PHP_SELF']?>",
          	type: "POST",
          	data: {
          		visualizacao_extrato_mes: true,
            	valor: valor,
            	cod_posto: cod_posto
          	},
          	complete: function (data) {
          		data = $.parseJSON(data.responseText);
            
             	if (data.erro) {
                	alert(data.erro);
                	$(obj).val(data.ant);
            	} else {                	
                	alert(data.ok);                	
            	}             
          	}
        });		
	});
});

	function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
  }
</script>

<?php 
if (count($msg_erro["msg"]) > 0) {	
?>
	<div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	
}
if (count($msg_erro["msg"]) == 0 AND count($msg_ok['ok']) > 0) {
?>
	<div class="alert alert-success"> <h4><?php echo $msg_ok["ok"]; ?></h4> </div>		
<?php	
}
?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario" >
	<div class="titulo_tabela">Cadastro Período</div>
	<br />
	<div class="container tc_container">

		<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("periodo_mes", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for="periodo_mes">Perído Mês</label>
						<div class='controls controls-row'>							
							<h5 class='asteristico'>*</h5>
							<select name="periodo_mes" id="periodo_mes" class='span8'>
								<option value=""></option>
<?php
								for($j = 3; $j <= 96; $j++){
								
									if ($j == $_POST['periodo_mes']) {
										$selected = "selected";
									}else{
										$selected = '';
									}
									echo "<option value='{$j}' $selected >{$j} Meses</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group'>
						<label class='control-label' for="todos_postos">Todos os Postos:</label>
						<div class='controls controls-row'>
							<input type="checkbox" name="todos_posto" value="todos_postos">
						</div>	
					</div>
				</div>
				<div class='span2'></div>
			</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<br />
		<center>
			<input type='submit' class='btn' name='btn_acao' value='Cadastrar' />
		</center>
		<br />
	</div>
</form>
<?php

if(isset($res_tabela) > 0){
	if(pg_num_rows($res_tabela) > 0){
		?>
		<br />	
		<table id="postos_credenciados" class='table table-striped table-bordered table-hover table-large'>

			<thead>
				<tr class='titulo_coluna' >
					<th>Nome Posto</th>
					<th>CNPJ</th>
					<th>Visualização</th>						
				</tr>
			</thead>
			<tbody>
				<?php 
				for ($i=0; $i < pg_num_rows($res_tabela); $i++) {
					$posto_t					= pg_fetch_result($res_tabela, $i, 'posto');
					$nome_posto_t				= pg_fetch_result($res_tabela, $i, 'nome_posto');
					$cnpj_posto_t				= pg_fetch_result($res_tabela, $i, 'cnpj');
					$parametros_adicionais_t	= pg_fetch_result($res_tabela, $i, 'parametros_adicionais');
					
					$parametros_adicionais_array = json_decode($parametros_adicionais_t,true);

					if ( array_key_exists('meses_extrato',$parametros_adicionais_array) ) {
			
						$parametros_adicionais_t = $parametros_adicionais_array['meses_extrato'];	
	
					}else{
						$parametros_adicionais_t = 0;
					}
					
					if (strlen($cnpj_posto_t)>11) {
						$cnpj_posto_t = mascara($cnpj_posto_t,'##.###.###/####-##');
					}else{
						$cnpj_posto_t = mascara($cnpj_posto_t,'###.###.###-##');
					}

					$body = "<tr'>
									<td>{$nome_posto_t}</td>
									<td>{$cnpj_posto_t}</td>
									<td >
										<input type='text' name='descricao_posto' class='span1 descricao_posto' data-codigo-posto='{$posto_t}' value='{$parametros_adicionais_t}'>
									</td>
									</tr>";

				echo $body;

				}
				?>	
			</tbody>
		</table>
		<br />
	<?php		
	}else {
	?>
		<div class="container">
			<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}
include "rodape.php";
?>
