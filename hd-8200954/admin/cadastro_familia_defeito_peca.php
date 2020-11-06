<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DA RELAÇÃO DO DEFEITO X FAMÍLIA DE PEÇA";

include "cabecalho_new.php";
$plugins = array("tooltip");
include ("plugin_loader.php");

if (strlen($_GET["familia_peca"]) > 0) {
	$familia_peca = trim($_GET["familia_peca"]);
}

if (strlen($_GET["defeito"]) > 0) {
	$defeito = trim($_GET["defeito"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
	$defeito = trim($_POST["defeito"]);
	$familia_peca = trim($_POST["familia_peca"]);
	$peca_defeito = trim($_POST["peca_defeito"]);

	if (strlen($_POST["defeito"]) == 0) 
		$msg_erro = "Favor selecione um defeito da peça.";

	if (strlen($_POST["familia_peca"]) == 0) 
		$msg_erro = "Favor selecione uma familia da peça.";

	$sql = "SELECT defeito, familia_peca FROM tbl_peca_defeito WHERE defeito = {$defeito} AND familia_peca = {$familia_peca}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$msg_erro = "Defeito x Família já cadastrado no Sistema.";
	}

	if (strlen($ativo)==0) 
		$aux_ativo = "f";
	else 
		$aux_ativo = "t";

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if (strlen($peca_defeito) == 0) {
			$sql = "INSERT INTO tbl_peca_defeito (
						defeito,
						familia_peca,
						ativo
					) VALUES (
						$defeito,
						$familia_peca,
						'$aux_ativo'
					);";
// echo $sql; exit;
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

		}else{
			$sql = "UPDATE tbl_peca_defeito SET
					defeito = '$defeito',
					familia_peca = $familia_peca,
					ativo = '$aux_ativo'
				WHERE peca_defeito = $peca_defeito";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Gravado com Sucesso!";

		$familia_peca = "";
		$defeito = "";
		$ativo = "";
		$peca_defeito = "";
	}else{
		$familia_peca = $_POST["familia_peca"];
		$defeito      = $_POST["defeito"];
		$ativo        = $_POST["ativo"];
		$peca_defeito = $_POST["peca_defeito"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["familia_peca"]) > 0 && strlen($_GET["defeito"]) > 0) {
	$familia_alteracao = $_GET["familia_peca"];
	$defeito_alteracao = $_GET["defeito"];

	$sql = "SELECT  tbl_peca_defeito.familia_peca,
			tbl_peca_defeito.defeito,
			tbl_peca_defeito.peca_defeito,
			tbl_peca_defeito.ativo,
			tbl_familia_peca.descricao AS familia_descricao,
			tbl_defeito.descricao AS defeito_descricao
		FROM    tbl_peca_defeito
			JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_peca_defeito.familia_peca
			JOIN tbl_defeito ON tbl_defeito.defeito = tbl_peca_defeito.defeito
		WHERE   tbl_familia_peca.fabrica = $login_fabrica AND tbl_defeito.fabrica = $login_fabrica
			AND tbl_peca_defeito.familia_peca = $familia_peca AND tbl_peca_defeito.defeito = $defeito
		ORDER BY tbl_defeito.descricao DESC, tbl_familia_peca.descricao;";
	$res = pg_query ($con,$sql);
//	echo $sql;
	if (pg_num_rows($res) > 0) {
		$peca_defeito = trim(pg_fetch_result($res,0,peca_defeito));
		$familia_alteracao = trim(pg_fetch_result($res,0,familia_peca));
		$ativo             = trim(pg_fetch_result($res,0,ativo));
	}
}

if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?echo $msg_erro;?></h4>
	</div>
<? } ?>
<? if (strlen($msg) > 0) { 
	$familia_peca = "";
	$defeito = "";
	$ativo = "f";
?>
	<div class="alert alert-success">
		<h4><?echo $msg;$msg="";?></h4>
	</div>
<? } ?>

<br/>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_familia" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
<input type="hidden" name="peca_defeito" value="<? echo $peca_defeito ?>" />

<div class="titulo_tabela">Cadastro da Relação do Defeito da Peça x Família</div>
<br/>
<div class="row-fluid">
	<div class="span2"></div>
	<div class="span4">
		<div class='control-group <?=(strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
			<label class="control-label" for="">Defeito</label>
			<div class="controls controls-row">
				<h5 class="asteristico">*</h5>
				<?
				$sql =	"SELECT * FROM tbl_defeito
						WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
				$res = pg_query($con,$sql);

				echo "<select name='defeito' size='1' class='frm' style='width:200px;'>";
				echo "<option value=''>ESCOLHA</option>";
				if (pg_num_rows($res) > 0) {
					for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$aux_defeito = trim(pg_fetch_result($res,$i,defeito));
						$aux_descricao  = trim(pg_fetch_result($res,$i,descricao));
						echo "<option value='$aux_defeito'";
						if ($defeito_alteracao == $aux_defeito) echo " selected";
						echo ">$aux_descricao</option>";
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

				if (pg_num_rows($res) > 0) {
					echo "<select name='familia_peca' size='1' class='frm' style='width:200px;'>";
					echo "<option value=''>ESCOLHA</option>";
					for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$aux_familia = trim(pg_fetch_result($res,$i,familia_peca));
						$aux_nome  = trim(pg_fetch_result($res,$i,descricao));
						echo "<option value='$aux_familia'";
						if ($familia_alteracao == $aux_familia) echo " selected";
						echo ">$aux_nome</option>";
					}
					echo "</select>";
				}
			?>
			</div>
		</div>
	</div>
	<div class="span1">
		<div class="control-group tac">
			<label class="control-label" for="">Ativo</label>
			<div class="controls controls-row tac">
				<input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?> />
			</div>
		</div>
	</div>
    <div class="span2"></div>
</div>
	<br/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4 tac">
			<button type="button" class="btn"  onclick="submitForm($(this).parents('form'),'gravar');" alt="Gravar formulário" >Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />		
		</div>
		<div class="span4"></div>
	</div>
	<br/>
</form>

<table class='table table-striped table-bordered table-hover table-fixed'>
	<thead>
		<tr class='titulo_tabela'>
			<th colspan='4'>Relação Defeito da Peça com Família</th>
		</tr>
		<tr class='titulo_coluna'>
			<th>Defeito</th>
			<th>Família</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<? $sql = "SELECT  tbl_peca_defeito.familia_peca,
		tbl_peca_defeito.defeito,
		tbl_peca_defeito.ativo,
		tbl_familia_peca.descricao AS familia_descricao,
		tbl_defeito.descricao AS defeito_descricao
	FROM    tbl_peca_defeito
		JOIN tbl_familia_peca ON tbl_familia_peca.familia_peca = tbl_peca_defeito.familia_peca
		JOIN tbl_defeito ON tbl_defeito.defeito = tbl_peca_defeito.defeito
	WHERE   tbl_familia_peca.fabrica = $login_fabrica AND tbl_defeito.fabrica = $login_fabrica
	ORDER BY tbl_defeito.descricao, tbl_familia_peca.descricao;";
$res = pg_query ($con,$sql);
//echo nl2br($sql);
for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
	$familia_peca 	   = trim(pg_fetch_result($res,$x,familia_peca));
	$defeito 		   = trim(pg_fetch_result($res,$x,defeito));
	$familia_descricao = trim(pg_fetch_result($res,$x,familia_descricao));
	$defeito_descricao = trim(pg_fetch_result($res,$x,defeito_descricao));
	$ativo   		   = trim(pg_fetch_result($res,$x,ativo));
	$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	if($ativo=='t') $ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
	else            $ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
?>
	<tr >
		<td>
			<a href="<? echo $PHP_SELF.'?familia_peca='.$familia_peca.'&defeito='.$defeito;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $defeito_descricao;?></a>
		</td>
		<td>
			<a href="<? echo $PHP_SELF.'?familia_peca='.$familia_peca.'&defeito='.$defeito;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $familia_descricao;?></a>
		</td>
		<td class="tac"><? echo $ativo;?></td>

	</tr>
<? } ?>
	</tbody>
</table>
<? include "rodape.php"; ?>
