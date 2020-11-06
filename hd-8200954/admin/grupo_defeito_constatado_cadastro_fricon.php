<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

if (strlen($_REQUEST["defeito_constatado_grupo"]) > 0) {
	$defeito_constatado_grupo = trim($_GET["defeito_constatado_grupo"]);
}

if (strlen($_POST["msg_success"]) > 0) {
	$msg_success = trim($_POST["msg_success"]);
}

if (isset($_POST["acao"])) {
	
	if (strlen($_POST["grupo_codigo"]) > 0) {
		$aux_grupo_codigo = trim($_POST["grupo_codigo"]);
	}else{
		$msg_erro["msg"][] = "Informe o código";
		$msg_erro["campos"][] = "grupo_codigo";
	}

	if (strlen($_POST["descricao"]) > 0) {
		$aux_descricao = trim($_POST["descricao"]);
	}else{
		if ($login_fabrica == 175){
			$msg_erro["msg"][] = "Informe a descrição do grupo de defeito";
		}else{
			$msg_erro["msg"][] = "Informe o defeito constatado";
		}
		$msg_erro["campos"][] = "descricao";
	}
	
	$ativo = (strlen($_POST["ativo"]) == 0) ? "f" : "t";
	$defeito_constatado_grupo = $_POST["defeito_constatado_grupo"];

	if (count($msg_erro["msg"]) == 0) {

		$res = pg_query($con,"BEGIN TRANSACTION");
		
		if (strlen($defeito_constatado_grupo) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_defeito_constatado_grupo (
						fabrica,
						descricao,
						grupo_codigo,
						ativo
					) VALUES (
						$login_fabrica,
						'$aux_descricao',
						'$aux_grupo_codigo',
						'$ativo'
					)";
			$res = pg_query($con, $sql);

			$erro = pg_last_error();

			if(strlen($erro) == 0){
				$msg_success = "Grupo de Defeitos cadastrado com Sucesso";
			}

		}else{

			###ALTERA REGISTRO
			$sql = "UPDATE tbl_defeito_constatado_grupo SET
						descricao  		 = '$aux_descricao',
						grupo_codigo     = '$aux_grupo_codigo',
						ativo 			 = '$ativo' 
			WHERE tbl_defeito_constatado_grupo.fabrica = $login_fabrica
			AND tbl_defeito_constatado_grupo.defeito_constatado_grupo = $defeito_constatado_grupo";
			$res = pg_query($con, $sql);

			$erro = pg_last_error();

			if(strlen($erro) == 0){
				$msg_success = "Grupo de Defeitos alterado com Sucesso";
			}

		}

		if(strpos($erro, 'duplicate key violates unique constraint "tbl_defeito_constatado_grupo.grupo_codigo"')){
			$msg_erro["msg"][] = "O código digitado já esta cadastrado em outro defeito";
		}


	}


	if (count($msg_erro) == 0) {
		$res = pg_query($con, "COMMIT TRANSACTION");
		header ("Location: {$PHP_SELF}?msg_success={$msg_success}");
		exit;
	}else{
		$defeito_constatado_grupo    = $_POST["defeito_constatado_grupo"];
		$grupo_codigo                = $_POST["grupo_codigo"];
		$descricao                   = $_POST["descricao"];
		$ativo                       = $_POST["ativo"];
		$res = pg_query($con, "ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($defeito_constatado_grupo) > 0) {

	$sql = "SELECT  tbl_defeito_constatado_grupo.grupo_codigo,
					tbl_defeito_constatado_grupo.descricao,
					tbl_defeito_constatado_grupo.ativo 
			FROM    tbl_defeito_constatado_grupo
			WHERE   tbl_defeito_constatado_grupo.fabrica = $login_fabrica
			AND     tbl_defeito_constatado_grupo.defeito_constatado_grupo= $defeito_constatado_grupo";

	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0) {
		$grupo_codigo    = trim(pg_fetch_result($res, 0, "grupo_codigo"));
		$descricao       = trim(pg_fetch_result($res, 0, "descricao"));
		$ativo       	 = trim(pg_fetch_result($res, 0, "ativo"));

	}
}


$layout_menu = "cadastro";
$title = "Cadastro de Grupo Defeitos Constatados";

include 'cabecalho_new.php';

$plugins = array("dataTable");

include ("plugin_loader.php");

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?php echo implode("<br />", $msg_erro["msg"]); ?></h4>
    </div>
<?php
} 
if (strlen($msg_success) > 0) { ?>
	<div class="alert alert-success">
        <h4><?php echo $msg_success; ?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form class="form-inline tc_formulario" method="post" action="<?php echo $PHP_SELF ?>">

	<input type="hidden" name="defeito_constatado_grupo" value="<? echo $defeito_constatado_grupo ?>">

	<div class="titulo_tabela ">
	   	<?php echo $title; ?>
	</div>

	<br />

	<div class='row-fluid'>
	    <div class='span1'></div>
	    <div class='span3'>
			<label class="control-label" for="grupo_codigo">Código</label>
			<div class="controls controls-row">
				<div class='control-group <?=(in_array('grupo_codigo', $msg_erro['campos'])) ? "error" : "" ?>' >
		            <div class="span11">
		                <h5 class="asteristico">*</h5>
		                <?php echo "<input type='text' name='grupo_codigo' value=\"$grupo_codigo\" class='span12' maxlength='2' />";?>
		            </div>
		        </div>		
	        </div>		
	    </div>
	    <div class='span5'>
			<label class="control-label" for="descricao">Descrição</label>
			<div class="controls controls-row">
				<div class='control-group <?=(in_array('grupo_codigo', $msg_erro['campos'])) ? "error" : "" ?>' >
		            <div class="span12">
		                <h5 class="asteristico">*</h5>
		                <?php echo	"<input type='text' name='descricao' value=\"$descricao\" class='span12' maxlength='100' />";?>
		            </div>
		        </div>	
	        </div>	
	    </div>
	    <div class='span2'>
            <div class="span12">
            	<label class="ativo"> 
            		<br />
            		<input type="checkbox" name="ativo" value="t" <?php echo ($ativo == "t") ? "checked" : ""; ?>> Ativo
            	</label>
            </div>
    	</div>
	</div>

	<br />

	<p class="tac">
		<input type="hidden" name="acao" value="gravar">
		<input type="submit" value="Gravar" class="btn" />
	</p>

	<br />

</form>

<?php

if (strlen ($defeito_constatado) == 0) {

	?>

	<div class='alert alert-warning tac'>
		Para efetuar alterações, clique na descrição do Grupo Constatado.
	</div>

	<?php 

	$sql = "SELECT	tbl_defeito_constatado_grupo.defeito_constatado_grupo,
					tbl_defeito_constatado_grupo.grupo_codigo,
					tbl_defeito_constatado_grupo.descricao,
					tbl_defeito_constatado_grupo.ativo       
			FROM    tbl_defeito_constatado_grupo
			WHERE   tbl_defeito_constatado_grupo.fabrica = $login_fabrica
			ORDER BY tbl_defeito_constatado_grupo.grupo_codigo ASC";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		?>

		<table class="table table-bordered border-large" id="relatorio" style="width: 100%;">
			<thead>
				<tr class="titulo_coluna">
					<th>Código</th>
					<th>Descrição</th>
					<th>Ativo</th>
				</tr>
			</thead>

			<tbody>

			<?php

			for ($x = 0; $x < pg_num_rows($res); $x++){

				$defeito_constatado_grupo   = trim(pg_fetch_result($res, $x, "defeito_constatado_grupo"));
				$descricao            		= trim(pg_fetch_result($res, $x, "descricao"));
				$grupo_codigo               = trim(pg_fetch_result($res, $x, "grupo_codigo"));
				$ativo                      = trim(pg_fetch_result($res, $x, "ativo"));

				$status = ($ativo == "t") ? "status_verde.png" : "status_vermelho.png";

				echo "<tr>";

					echo "<td><a href='$PHP_SELF?defeito_constatado_grupo=$defeito_constatado_grupo'>$grupo_codigo</a></td>";
					echo "<td><a href='$PHP_SELF?defeito_constatado_grupo=$defeito_constatado_grupo'>$descricao</a></td>";
					echo "<td class='tac'><img src='imagens/$status' /></td>";

				echo "</tr>";
			}

			echo "</tbody>";

		echo "</table>";
	}
}

?>

<script>
	$.dataTableLoad({
	    table : "#relatorio"
	});
</script>

<?php

echo "<br />";

include "rodape.php";
?>
