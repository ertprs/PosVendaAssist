<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if($login_fabrica <> 10){
	header("Location: menu_cadastro.php");
	exit;
}


if ($_POST["btn_acao"] == "submit") {
	$fabricas      		= $_POST['fabrica'];
	$parametro         	= $_POST['parametro'];
	$valor	         	= $_POST['valor'];
	$valor = (empty($valor)) ? 't':$valor;
	$sucesso = "";

	if (count($fabrica) == 0 or !strlen($parametro)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "fabrica";
		$msg_erro["campos"][] = "parametro";
	}

	if(count($msg_erro) == 0){
		$novo_parametro = array($parametro => $valor);

		$resT = pg_query($con,"BEGIN");
		foreach ($fabricas as $fabrica) {
			$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $fabrica";
			$res = pg_query($con,$sql);
			$msg .= pg_errormessage($con);

			if(!empty($msg)){
				break;
			}
			$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');

			if(!empty($parametros_adicionais)){
				$parametros_adicionais = json_decode($parametros_adicionais,true);
				$parametros_adicionais = array_merge($parametros_adicionais, $novo_parametro);
			}else{
				$parametros_adicionais = $novo_parametro;
			}
			
			$parametros_adicionais = json_encode($parametros_adicionais);
			
			$sql = "UPDATE tbl_fabrica SET parametros_adicionais = '$parametros_adicionais' WHERE fabrica = $fabrica";
			$res = pg_query($con,$sql);
			$msg .= pg_errormessage($con);
			if(!empty($msg)){
				break;
			}
		}

		if(!empty($msg)){
			$resT = pg_query($con,"ROLLBACK");
		}else{
			$resT = pg_query($con,"COMMIT");
			$sucesso = "sucesso";
		}

	}

}

$sql = "SELECT fabrica,nome,parametros_adicionais 
		FROM tbl_fabrica 
		WHERE parametros_adicionais notnull
		AND ativo_fabrica 
		ORDER BY nome";
$resParametros = pg_query($con,$sql);


$layout_menu = "cadastro";
$title = "CADASTRO DE PARÂMETROS ADICIONAIS";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"shadowbox",
	"dataTable",
	"multiselect"
);

include("plugin_loader.php");
?>

<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();
		$.dataTableLoad();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#fabrica").multiselect({
		   selectedText: "# of # selected"
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
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<?php
if (!empty($sucesso)) {
?>
    <div class="alert alert-success">
		<h4>Novo parâmetro cadastrado com sucesso</h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Cadastro</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("fabrica", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='fabrica'>Fábrica</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<select name="fabrica[]" id="fabrica" multiple="multiple" >
							<?php
							$sql = "SELECT fabrica, nome
									FROM tbl_fabrica
									WHERE ativo_fabrica
									ORDER BY nome";
							$res = pg_query($con,$sql);

							foreach (pg_fetch_all($res) as $key) {

							?>
								<option value="<?php echo $key['fabrica']?>" <?php echo $selected_fabrica ?> >

									<?php echo $key['nome']?>

								</option>
							<?php
							}
							?>
						</select>
						<div><strong></strong></div>
					</div>	
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group <?=(in_array("parametro", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='parametro'>Parâmetro</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="parametro" id="parametro" class='span12' value="<? echo $parametro ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span3'>
			<div class='control-group <?=(in_array("valor", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='valor'>Valor</label>
				<div class='controls controls-row'>
					<div class='span6 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="valor" id="valor" class='span6' value="<? echo $valor ?>" >
					</div>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>

	<!-- <div class='row-fluid'>
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
	</div> -->
	<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form> <br />

<?php
	if(pg_num_rows($resParametros) > 0){
		$total_fabricas = pg_num_rows($resParametros);
?>
		<table class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Fábrica</th>
					<th>Parâmetros</th>
					<th>Exceções</th>
			</thead>
			<tbody>
<?php
		for($i = 0; $i < $total_fabricas; $i++){

			$fabrica_id 	= pg_fetch_result($resParametros, $i, 'fabrica');
			$fabrica_nome 	= pg_fetch_result($resParametros, $i, 'nome');
			$parametros 	= pg_fetch_result($resParametros, $i, 'parametros_adicionais');

			$parametros = json_decode($parametros,true);

			$parametros_fabrica = "";
			foreach ($parametros as $key => $value) {
				$parametros_fabrica .= $key. " : " .$value . "<br>";
			}

			echo 	"<tr>
						<td class='tal'><a href='cadastro_parametros_adicionais.php?fabrica={$fabrica_id}'>{$fabrica_nome}</a></td>
						<td class='tal'>$parametros_fabrica</td>
						<td class='tal'></td>
					</tr>";
		}
?>
			</tbody>
		</table>
<?php
	}

?>
</div> <!-- Aqui fecha a DIV Container que abre no cabeçãlho -->
<?php
	include 'rodape.php';
?>
