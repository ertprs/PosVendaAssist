<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
$title = "Cadastro de Dias para Gerar de Pedido";
include 'autentica_admin.php';
include 'funcoes.php';

## EXCLUIR ##

if($_POST["apagarTipopedido"] == "true") {
   $condicao = $_POST["condicao"];
   $tipo_pedido = $_POST["tipo_pedido"];

   if (strlen($condicao) > 0 AND strlen($tipo_pedido) > 0) {

		$sql = "SELECT tbl_tipo_pedido_condicao.fabrica,
							tbl_tipo_pedido_condicao.tipo_pedido,
							tbl_tipo_pedido_condicao.condicao
					FROM tbl_tipo_pedido_condicao
					WHERE tbl_tipo_pedido_condicao.fabrica = $login_fabrica
					AND tbl_tipo_pedido_condicao.tipo_pedido = $tipo_pedido
					AND tbl_tipo_pedido_condicao.condicao = $condicao";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
		   $sql = "DELETE FROM tbl_tipo_pedido_condicao
							WHERE tbl_tipo_pedido_condicao.fabrica = $login_fabrica
							AND tbl_tipo_pedido_condicao.tipo_pedido = $tipo_pedido
							AND tbl_tipo_pedido_condicao.condicao = $condicao";
			$res = pg_query($con, $sql);

		   if (strlen(pg_last_error()) > 0) {
				echo "erro";
		   }
		}else{
	   	echo "erro";
		}
   }else{
		echo "erro";
   }
   exit;
}


if($_POST["btn_acao"] == "submit") {
	$tipo_pedido = $_POST["tipo_pedido"];
	$condicao = $_POST["condicao"];

	if (!strlen($tipo_pedido)) {
		$msg_erro["msg"][]    = "Selecione um tipo de pedido";
		$msg_erro["campos"][] = "tipo_pedido";
	}

	if (!strlen($condicao)) {
		$msg_erro["msg"][]    = "Selecione uma condição";
		$msg_erro["campos"][] = "condicao";
	}

	$sqlValida = "SELECT fabrica,
							tipo_pedido,
							condicao,
							data_input
					FROM tbl_tipo_pedido_condicao
					WHERE fabrica = $login_fabrica
					AND tipo_pedido = $tipo_pedido
					AND condicao = $condicao";
	$res = pg_query($con, $sqlValida);
	if(pg_num_rows($res) > 0){
		$msg_erro["msg"][] = "Já existe um Tipo de Pedido para essa Condição";
	}

	if (!count($msg_erro["msg"])) {
		$sqlInsert = "INSERT INTO tbl_tipo_pedido_condicao(
								fabrica,
								tipo_pedido,
								condicao,
								data_input
							)VALUES(
								$login_fabrica,
								$tipo_pedido,
								$condicao,
								CURRENT_DATE
							)";
		$res = pg_query($con, $sqlInsert);

		if(pg_last_error($con) == 0){
			$msg_success = "sucesso";
		}
	}
}

if($_POST["pesquisar"] == "Pesquisar"){
	$tipo_pedido = $_POST["tipo_pedido"];
	$condicao = $_POST["condicao"];

	if (!count($msg_erro["msg"])) {

		if(strlen($tipo_pedido) > 0){
			$cond_tipo_pedido = " AND tbl_tipo_pedido_condicao.tipo_pedido = $tipo_pedido";
		}else{
			$cond_tipo_pedido = "";
		}

		if(strlen($condicao) > 0){
			$cond_condicao = " AND tbl_tipo_pedido_condicao.condicao = $condicao";
		}else{
			$cond_condicao = "";
		}

		$sqlResult = "SELECT tbl_tipo_pedido_condicao.fabrica,
							tbl_tipo_pedido_condicao.tipo_pedido,
							tbl_tipo_pedido_condicao.condicao,
							TO_CHAR(data_input, 'DD/MM/YYYY') AS data_cadastro,
							tbl_condicao.descricao AS descricao_condicao,
							tbl_tipo_pedido.descricao AS descricao_tipo_pedido
					FROM tbl_tipo_pedido_condicao
					JOIN tbl_condicao ON tbl_condicao.condicao = tbl_tipo_pedido_condicao.condicao AND tbl_condicao.fabrica = $login_fabrica
					JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_tipo_pedido_condicao.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
					WHERE tbl_tipo_pedido_condicao.fabrica = $login_fabrica
					$cond_tipo_pedido
					$cond_condicao";
		$resResult = pg_query($con, $sqlResult);
	}

}

$layout_menu = "cadastro";
$title       = "CADASTRO DE TIPO DE PEDIDO X CONDIÇÃO";
include "cabecalho_new.php";

$plugins = array(
	"dataTable"
);
include "plugin_loader.php";
?>

<script type="text/javascript">
$(function () {
	$("button[name=excluir]").click(function () {
		var tr = $(this).parents("tr");
		var condicao = $(this).parent("td").find("input[name=condicao]").val();
		var tipo_pedido = $(this).parent("td").find("input[name=tipo_pedido]").val();
		$.ajax({
		   url: "cadastro_tipo_pedido_condicao.php",
		   type: "POST",
		   data: { apagarTipopedido: true, condicao: condicao, tipo_pedido: tipo_pedido },
		   beforeSend: function () {
				loading("show");
		   },
		   complete: function (data) {
				if(data == "erro"){
				   alert("Erro ao deletar");
				}else{
				   $(tr).remove();
				   alert("Tipo de Pedido apagado com sucesso");
				}
				loading("hide");
		   }
		});
	});
});
</script>

<?php
if ($msg_success) {
?>
    <div class="alert alert-success">
		<h4>Gravado com Sucesso</h4>
    </div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>

		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("tipo_pedido", $msg_erro["tipo_pedido"])) ? "error" : ""?>'>
				<label class='control-label' for='linha'>Tipo Pedido</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="tipo_pedido" name="tipo_pedido" >
							<option value="">Selecione</option>
							<?php
							$sql  = "SELECT tipo_pedido,
											descricao,
											codigo
										FROM tbl_tipo_pedido
										WHERE fabrica = {$login_fabrica}
										ORDER BY tipo_pedido";
							$res  = pg_query($con, $sql);
							$rows = pg_num_rows($res);

							if ($rows > 0) {
								for ($i = 0; $i < $rows; $i++) {
									$tipo_pedido        = pg_fetch_result($res, $i, "tipo_pedido");
									$descricao_tipo_pedido = pg_fetch_result($res, $i, "descricao");
									$codigo_tipo_pedido = pg_fetch_result($res, $i, "codigo");

									$selected = ($_POST["tipo_pedido"] == $tipo_pedido) ? "selected" : "";

									echo "<option value='{$tipo_pedido}' {$selected} >{$descricao_tipo_pedido}</option>";
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["condicao"])) ? "error" : ""?>'>
				<label class='control-label' for='condicao'>Condição</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<h5 class='asteristico'>*</h5>
						<select id="condicao" name="condicao" >
							<option value="">Selecione</option>
							<?php
								$sql  = "SELECT condicao,
												descricao,
												codigo_condicao
											FROM tbl_condicao
											WHERE fabrica = {$login_fabrica}
											AND visivel IS TRUE
											ORDER BY descricao";
								$res  = pg_query($con, $sql);
								$rows = pg_num_rows($res);

								if ($rows > 0) {
									for ($i = 0; $i < $rows; $i++) {
										$condicao        = pg_fetch_result($res, $i, "condicao");
										$descricao_condicao      = pg_fetch_result($res, $i, "descricao");
										$codigo_condicao = pg_fetch_result($res, $i, "codigo_condicao");

										$selected = ($_POST["condicao"] == $condicao) ? "selected" : "";

										echo "<option value='{$condicao}' {$selected} >{$descricao_condicao}</option>";
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

	<p><br />
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<input class="btn" type='submit' name='pesquisar' value='Pesquisar'>
	</p><br />
</form>
</div>

<?php
if (isset($resResult)) {
	if (pg_num_rows($resResult) > 0) {
		$count = pg_num_rows($resResult);
?>
		<table id="resultado_tipo_pedido_condicao" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class='titulo_coluna' >
					<th>Tipo Pedido</th>
					<th>Condição</th>
					<th>Data Cadastro</th>
	            <th>Ações</th>
	         </tr>
			</thead>
			<tbody>
			<?php
				for ($i = 0; $i < $count; $i++) {
					$tipo_pedido = pg_fetch_result($resResult, $i, 'tipo_pedido');
					$condicao = pg_fetch_result($resResult, $i, 'condicao');
					$data_cadastro = pg_fetch_result($resResult, $i, 'data_cadastro');
					$descricao_condicao = pg_fetch_result($resResult, $i, 'descricao_condicao');
					$descricao_tipo_pedido = pg_fetch_result($resResult, $i, 'descricao_tipo_pedido');

					$body = "<tr>
									<td class='tac'>{$descricao_tipo_pedido}</td>
									<td class='tac'>{$descricao_condicao}</td>
									<td class='tac'>{$data_cadastro}</td>
									<td class='tac'>
										<button class='btn btn-danger' name='excluir' type='button'>Excluir</button>
										<input type='hidden' name='tipo_pedido' value='$tipo_pedido'>
										<input type='hidden' name='condicao' value='$condicao'>
									</td>";
					echo $body;
				}
			?>
			</tbody>
		</table>
<?php
	}else{
		echo '
		<div class="container">
		<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
		</div>
		</div>';
	}
}

?>