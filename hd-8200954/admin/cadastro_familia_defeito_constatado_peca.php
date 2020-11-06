<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if(isset($_POST["excluir_peca_familia"]) && $_POST["excluir_peca_familia"] == true){

	$peca_familia = $_POST["peca_familia"];

	$sql = "DELETE FROM tbl_defeito_constatado_familia_peca WHERE defeito_constatado_familia_peca = {$peca_familia} AND fabrica = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error()) > 0){
		$retorno = array("erro" => true, "descricao" => pg_last_error());
	}else{
		$retorno = array("retorno" => true);
	}

	exit(json_encode($retorno));

}

$layout_menu = "cadastro";
$title = "CADASTRO DA RELAÇÃO DO DEFEITO CONSTATADO X FAMÍLIA DA PEÇA";

include "cabecalho_new.php";
$plugins = array("tooltip");
include ("plugin_loader.php");

if (strlen($_GET["familia_peca"]) > 0) {
	$familia_peca = trim($_GET["familia_peca"]);
}

if (strlen($_GET["defeito_constatado"]) > 0) {
	$defeito_constatado = trim($_GET["defeito_constatado"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {

	$defeito_constatado = trim($_POST["defeito_constatado"]);
	$familia_peca = trim($_POST["familia_peca"]);
	$defeito_constatado_familia_peca = trim($_POST["defeito_constatado_familia_peca"]);

	if (strlen($defeito_constatado) == 0)
		$msg_erro = "Favor selecione um defeito constatado.";

	if (strlen($familia_peca) == 0)
		$msg_erro = "Favor selecione uma familia da peça.";

	if (strlen($defeito_constatado_familia_peca) == 0) {

        $sql = "SELECT defeito_constatado, familia_peca FROM tbl_defeito_constatado_familia_peca WHERE defeito_constatado = {$defeito_constatado} AND familia_peca = {$familia_peca}";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $msg_erro = "Defeito Constatado x Família da Peça já cadastrado no Sistema.";
        }

	}

	if(in_array($login_fabrica, array(158))){

		$tipo_atendimento = $_POST["tipo_atendimento"];
		$tipo_garantia    = $_POST["tipo_garantia"];

		$campos_insert1 = ", tipo_atendimento, garantia ";
		$campos_insert2 = ", {$tipo_atendimento}, '{$tipo_garantia}' ";

		$campos_update = ", tipo_atendimento = {$tipo_atendimento}, garantia = '{$tipo_garantia}' ";

	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen($defeito_constatado_familia_peca) == 0) {
			$sql = "INSERT INTO tbl_defeito_constatado_familia_peca (
						defeito_constatado,
						familia_peca,
						fabrica
					) VALUES (
						$defeito_constatado,
						$familia_peca,
						$login_fabrica
					);";
// echo $sql; exit;
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

		}else{
			$sql = "UPDATE tbl_defeito_constatado_familia_peca SET
					defeito_constatado = '$defeito_constatado',
					familia_peca = $familia_peca
				WHERE defeito_constatado_familia_peca = $defeito_constatado_familia_peca
					AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Gravado com Sucesso!";

		$familia_peca = "";
		$defeito_constatado = "";
		$defeito_constatado_familia_peca = "";
	}else{
		$familia_peca 					 = $_POST["familia_peca"];
		$defeito_constatado      		 = $_POST["defeito_constatado"];
		$defeito_constatado_familia_peca = $_POST["defeito_constatado_familia_peca"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["familia_peca"]) > 0 && strlen($_GET["defeito_constatado"]) > 0) {
	$familia_alteracao = $_GET["familia_peca"];
	$defeito_alteracao = $_GET["defeito_constatado"];

	$sql = "SELECT
				DCFP.familia_peca,
				DCFP.defeito_constatado,
				DCFP.defeito_constatado_familia_peca,
				DCFP.tipo_atendimento,
				DCFP.garantia AS tipo_garantia,
				tbl_familia_peca.descricao       AS familia_descricao,
				tbl_defeito_constatado.descricao AS defeito_descricao
			FROM tbl_defeito_constatado_familia_peca AS DCFP
            JOIN tbl_familia_peca
              ON tbl_familia_peca.familia_peca = DCFP.familia_peca
            JOIN tbl_defeito_constatado
              ON tbl_defeito_constatado.defeito_constatado = DCFP.defeito_constatado
			WHERE tbl_familia_peca.fabrica       = $login_fabrica
              AND tbl_defeito_constatado.fabrica = $login_fabrica
              AND DCFP.familia_peca              = $familia_peca
              AND DCFP.defeito_constatado        = $defeito_constatado
			ORDER BY
				tbl_defeito_constatado.descricao DESC,
				tbl_familia_peca.descricao;";
	$res = pg_query ($con,$sql);
//	echo $sql;
	if (pg_num_rows($res) > 0) {
		$defeito_constatado_familia_peca = trim(pg_fetch_result($res, 0, "defeito_constatado_familia_peca"));
		$familia_alteracao               = trim(pg_fetch_result($res, 0, "familia_peca"));
		$tipo_atendimento                = trim(pg_fetch_result($res, 0, "tipo_atendimento"));
		$tipo_garantia                   = trim(pg_fetch_result($res, 0, "tipo_garantia"));
	}
}

if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?echo $msg_erro;?></h4>
	</div>
<? } ?>
<? if (strlen($msg) > 0) {
	$familia_peca = "";
	$defeito_constatado = "";
?>
	<div class="alert alert-success">
		<h4><?echo $msg;$msg="";?></h4>
	</div>
<? } ?>

<script>

$(function(){
	$(document).on("click", "#excluir", function(){

		var peca_familia = $(this).attr("data-peca-familia");

		var conf = confirm("Dejesa realmente excluir essa Peça x Família");

		if(conf == true){

			$.ajax({
				url : "<?= $_SERVER['PHP_SELF'] ?>",
				type : "POST",
				data : {
					excluir_peca_familia : true,
					peca_familia : peca_familia
				}
			}).always(function(data){

				if(data.erro){
					alert("Erro ao Excluir a Defeito da Peça x Família Peça");
				}else{
					$("#row-"+peca_familia).remove();
				}

			});

		}

	});

	$(document).on("click", "#tipo_atendimento", function() {
		$(this).val() == 1 ?  $(".garantia-box").show() : $(".garantia-box").hide();

	});

});

</script>

<br/>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<?= $PHP_SELF.(isset($semcab) ? "?semcab=yes" : ""); ?>">
<input type="hidden" name="defeito_constatado_familia_peca" value="<?= $defeito_constatado_familia_peca ?>" />

<div class="titulo_tabela">Cadastro da Relação do Defeito Constatado x Família de Peça</div>
<br/>
<div class="row-fluid">
	<div class="span2"></div>
	<div class="span4">
		<div class='control-group <?=(strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
			<label class="control-label" for="">Defeito</label>
			<div class="controls controls-row">
				<h5 class="asteristico">*</h5>
				<?
				$sql =	"SELECT * FROM tbl_defeito_constatado WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
				$res = pg_query($con,$sql);

				echo "<select name='defeito_constatado' size='1' class='frm' style='width:200px;'>";
				echo "<option value=''>ESCOLHA</option>";
				if (pg_num_rows($res) > 0) {
					for ($i = 0; $i < pg_num_rows($res) ; $i++) {
						$aux_defeito = trim(pg_fetch_result($res,$i,defeito_constatado));
						$aux_codigo = trim(pg_fetch_result($res,$i,codigo));
						$aux_descricao  = trim(pg_fetch_result($res,$i,descricao));
						echo "<option value='$aux_defeito'";
						if ($defeito_alteracao == $aux_defeito) echo " selected";
						echo ">".(in_array($login_fabrica, array(158)) ? $aux_codigo." - " : "")."$aux_descricao</option>";
					}
				}
				echo "</select>";
			?>
			</div>
		</div>
	</div>
	<div class="span4">
		<div class='control-group <?=(strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
			<label class="control-label" for="">Família</label>
			<div class="controls controls-row">
				<h5 class="asteristico">*</h5>
				<?
				$sql =	"SELECT * FROM tbl_familia_peca
						WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
				$res = pg_query($con,$sql);
				?>
				<select name='familia_peca' size='1' class='frm' style='width:200px;'>
				<?
				if (pg_num_rows($res) > 0) {
					#echo "<select name='familia_peca' size='1' class='frm' style='width:200px;'>";
					echo "<option value=''>ESCOLHA</option>";
					for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$aux_familia = trim(pg_fetch_result($res,$i,familia_peca));
						$aux_nome  = trim(pg_fetch_result($res,$i,descricao));
						echo "<option value='$aux_familia'";
						if ($familia_alteracao == $aux_familia) echo " selected";
						echo ">$aux_nome</option>";
					}
					#echo "</select>";
				}
			?>
				</select>
			</div>
		</div>
	</div>
    <div class="span2"></div>
</div>

	<?php if(in_array($login_fabrica, array(158))){ ?>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<label class="control-label">Tipo</label>
			<div class="controls controls-row">
				<label class="radio-inline">
					<input type="radio" name="tipo_atendimento" id="tipo_atendimento" value="1" <?php echo ($tipo_atendimento == "1") ? "checked" : ""; ?> > Garantia
				</label> &nbsp;
				<label class="radio-inline">
					<input type="radio" name="tipo_atendimento" id="tipo_atendimento" value="2" <?php echo ($tipo_atendimento == "2") ? "checked" : ""; ?> > Atendimento Tipo Piso
				</label>
			</div>
		</div>
		<div class="span4 garantia-box" <?php if($tipo_atendimento != "1"){ ?> style="display: none;" <?php } ?> >
			<label class="control-label">Garantia</label> <br />
			<div class="controls controls-row">
				<select name="tipo_garantia" class="span10">
					<option value="t" <?php echo ($tipo_garantia == "t") ? "selected" : ""; ?> >Sim</option>
					<option value="f" <?php echo ($tipo_garantia == "f") ? "selected" : ""; ?> >Não</option>
				</select>
			</div>
		</div>
	</div>

	<?php } ?>

	<br/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4 tac">
			<button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</div>
		<div class="span4"></div>
	</div>

	<?php if(in_array($login_fabrica, array(158))){ ?>

	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span6 tac">
			<?php if(isset($_GET["apenas_tipo"]) && $_GET["apenas_tipo"] == "sim"){ ?>
				<a href="cadastro_familia_defeito_constatado_peca.php" class="btn" alt="Listar Todos" >Listar Todos</a>
			<?php }else{ ?>
				<a href="cadastro_familia_defeito_constatado_peca.php?apenas_tipo=sim" class="btn" alt="Listar apenas com Tipo" >Listar apenas com Tipo</a>
			<?php } ?>
		</div>
		<div class="span3"></div>
	</div>

	<?php } ?>

</form>

<table class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
		<tr class='titulo_tabela'>
			<th colspan='<?php echo (in_array($login_fabrica, array(158))) ? "6" : "4"; ?>'>Relação Defeito Constatado com Família de Peça</th>
		</tr>
		<tr class='titulo_coluna'>
			<th>Defeito Constatado</th>
			<th>Família</th>
			<?php if(in_array($login_fabrica, array(158))){ ?>
			<th>Tipo</th>
			<th>Garantia</th>
			<?php } ?>
			<th width="20%">Ações</th>
		</tr>
	</thead>
	<tbody>
		<?

		if (in_array($login_fabrica, array(158))) {

			if(isset($_GET["apenas_tipo"]) && $_GET["apenas_tipo"] == "sim"){
				$cond_tipo = " AND tbl_defeito_constatado_familia_peca.tipo_atendimento != 0 ";
			}

		}

		$sql = "SELECT
					tbl_defeito_constatado_familia_peca.familia_peca,
					tbl_defeito_constatado_familia_peca.defeito_constatado_familia_peca,
					tbl_defeito_constatado_familia_peca.defeito_constatado,
					tbl_defeito_constatado_familia_peca.tipo_atendimento,
					tbl_defeito_constatado_familia_peca.garantia AS tipo_garantia,
					tbl_familia_peca.descricao AS familia_descricao,
					tbl_defeito_constatado.descricao AS defeito_descricao,
					tbl_defeito_constatado.codigo AS defeito_codigo
				FROM tbl_defeito_constatado_familia_peca
				INNER JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_defeito_constatado_familia_peca.familia_peca
				INNER JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_familia_peca.defeito_constatado
				WHERE
					tbl_familia_peca.fabrica = $login_fabrica
					AND tbl_defeito_constatado.fabrica = $login_fabrica
					{$cond_tipo}
				ORDER BY tbl_defeito_constatado.descricao, tbl_familia_peca.descricao;";
		$res = pg_query ($con,$sql);
		//echo nl2br($sql);
		for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {

			$defeito_constatado_familia_peca = trim(pg_fetch_result($res, $x, "defeito_constatado_familia_peca"));
			$familia_peca                    = trim(pg_fetch_result($res, $x, "familia_peca"));
			$defeito_constatado              = trim(pg_fetch_result($res, $x, "defeito_constatado"));
			$tipo_atendimento                = trim(pg_fetch_result($res, $x, "tipo_atendimento"));
			$tipo_garantia                   = trim(pg_fetch_result($res, $x, "tipo_garantia"));
			$familia_descricao               = trim(pg_fetch_result($res, $x, "familia_descricao"));
			$defeito_descricao               = trim(pg_fetch_result($res, $x, "defeito_descricao"));
			$defeito_codigo                  = trim(pg_fetch_result($res, $x, "defeito_codigo"));

			?>
			<tr id="row-<?=$defeito_constatado_familia_peca?>">
				<td>
					<a href="<?= $PHP_SELF.'?familia_peca='.$familia_peca.'&defeito_constatado='.$defeito_constatado.(isset($semcab) ? '&semcab=yes' : ''); ?>"><?= (in_array($login_fabrica, array(158)) ? $defeito_codigo." - " : "").$defeito_descricao; ?></a>
				</td>
				<td>
					<a href="<?= $PHP_SELF.'?familia_peca='.$familia_peca.'&defeito_constatado='.$defeito_constatado.(isset($semcab) ? '&semcab=yes' : ''); ?>"><?= $familia_descricao; ?></a>
				</td>
				<?php if(in_array($login_fabrica, array(158))){ ?>
				<td>
					<?php if($tipo_atendimento > 0) { echo ($tipo_atendimento == "1") ? "Garantia" : "Atendimento Tipo Piso"; } ?>
				</td>
				<td class="tac">
					<?php if($tipo_atendimento > 0 && $tipo_atendimento == "1") { echo ($tipo_garantia == "t") ? "Sim" : "Não"; } ?>
				</td>
				<?php } ?>
				<td class="tac">
					<button type="button" class="btn btn-danger" id="excluir" data-peca-familia="<?= $defeito_constatado_familia_peca; ?>"><i class="icon-remove icon-white"></i>Excluir</button>
				</td>
			</tr>
		<? } ?>
	</tbody>
</table>
<? include "rodape.php"; ?>
