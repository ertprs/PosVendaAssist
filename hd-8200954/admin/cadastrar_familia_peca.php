<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "cadastro";
$title = "CADASTRO DE FAMÍLIAS DE PEÇAS";

include "cabecalho_new.php";
$plugins = array("tooltip");
include ("plugin_loader.php");

if (strlen($_GET["familia_peca"]) > 0) {
	$familia_peca = trim($_GET["familia_peca"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}

if ($btnacao == "gravar") {
	$codigo = trim($_POST["codigo"]);

	if (strlen($_POST["descricao"]) > 0) 
		$aux_descricao  = trim($_POST["descricao"]);
	else
		$msg_erro = "Favor informar o nome da familia.";

	if (strlen($codigo)==0) 
		$codigo = '';

	if (strlen($ativo)==0) 
		$aux_ativo = "f";
	else 
		$aux_ativo = "t";

    $familia_peca = $_POST["familia_peca"];

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		if(strlen($familia_peca) == 0 and $codigo <> 'null' and strlen($codigo) > 0){
			$sql = "SELECT codigo FROM tbl_familia_peca WHERE fabrica = $login_fabrica AND codigo = '$codigo';";
			$res = pg_query($con,$sql);
			
			if(pg_num_rows($res) > 0){
				$msg_erro = "Código $codigo já existente. ";
				if(strlen($codigo)==0){
					$msg_erro .= "Código da família não pode ser em branco.";
				}
			}else{
				$sql_familia = ",codigo ";
				$var_familia = " ,'$codigo' ";
			}
		}

		if(strlen($msg_erro) == 0){
			if (strlen($familia_peca) == 0) {
				$sql = "INSERT INTO tbl_familia_peca (
							fabrica,
							descricao,
							ativo
							$sql_familia
						) VALUES (
							$login_fabrica,
							'".$aux_descricao."',
							'".$aux_ativo."'
							$var_familia
						);";

				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				// if (strlen($msg_erro) == 0){
				// 	$res = pg_query ($con,"SELECT CURRVAL('seq_familia')");
				// 	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				// 	else                                    $familia = pg_fetch_result($res,0,0);
				// }

			}else{
				$sql = "UPDATE tbl_familia_peca SET
						codigo = '$codigo',
						descricao = '$aux_descricao',
						ativo = '$aux_ativo'
					WHERE tbl_familia_peca.fabrica = $login_fabrica
					AND tbl_familia_peca.familia_peca = $familia_peca;";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Gravado com Sucesso!";

		$familia_peca = "";
		$codigo = "";
		$descricao = "";
	}else{
		$codigo_familia = $POST["codigo_familia"];
		$descricao      = $POST["descricao"];
		$ativo          = $POST["ativo"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($_GET["familia_peca"]) > 0) {
	$familia_alteracao = $_GET["familia_peca"];

	$sql = "SELECT  tbl_familia_peca.familia_peca,
					tbl_familia_peca.descricao,
					tbl_familia_peca.codigo,
					tbl_familia_peca.ativo
			FROM    tbl_familia_peca
			WHERE   tbl_familia_peca.fabrica = $login_fabrica
			AND     tbl_familia_peca.familia_peca = $familia_alteracao;";
	$res = pg_query ($con,$sql);
//	echo $sql;
	if (pg_num_rows($res) > 0) {
		$familia_alteracao = trim(pg_fetch_result($res,0,familia_peca));
		$codigo    		   = trim(pg_fetch_result($res,0,codigo));
		$descricao         = trim(pg_fetch_result($res,0,descricao));
		$ativo             = trim(pg_fetch_result($res,0,ativo));
	}
}

if (strlen($msg_erro) > 0) { ?>
	<div class="alert alert-error">
		<h4><?echo $msg_erro;?></h4>
	</div>
<? } ?>
<? if (strlen($msg) > 0) { 
	$codigo_familia						= "";
	$descricao 							= "";
	$ativo 								= "f";
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
<input type="hidden" name="familia_peca" value="<? echo $familia_alteracao ?>" />

<div class="titulo_tabela">Cadastro de Família</div>
<br/>
<div class="row-fluid">
	<!-- Margem -->
	<div class="span2"></div>
	<div class="span3">
		<div class="control-group">
			<label class="control-label" for=''>Código da Família</label>
			<div class='controls controls-row'>
			      <input class="span10" type="text" id="codigo" name="codigo" value="<? echo $codigo ?>" maxlength="30" />
		    </div>
		</div>
	</div>

	<div class="span4">
		<div class='control-group <?=(strpos($msg_erro,"nome da familia") !== false) ? "error" : "" ?>'>
			<label class="control-label" for="">Descrição da Família</label>
			<div class="controls controls-row">
				<h5 class="asteristico">*</h5>
				<input  type="text" id="descricao" name="descricao" value="<? echo $descricao ?>" maxlength="30" />
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
			<th colspan='4'>Relação das Famílias Cadastradas</th>
		</tr>
		<tr class='titulo_coluna'>
			<th>Código</th>
			<th>Descrição</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<? $sql = "SELECT  tbl_familia_peca.familia_peca,
		tbl_familia_peca.descricao,
		tbl_familia_peca.codigo,
		tbl_familia_peca.ativo
	FROM    tbl_familia_peca
	WHERE   tbl_familia_peca.fabrica = $login_fabrica
	ORDER BY tbl_familia_peca.ativo DESC, tbl_familia_peca.descricao;";
$res = pg_query($con,$sql);
//echo nl2br($sql);
for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
	$familia        		= trim(pg_fetch_result($res,$x,familia_peca));
	$descricao    			= trim(pg_fetch_result($res,$x,descricao));
	$codigo_familia 		= trim(pg_fetch_result($res,$x,codigo));
	$ativo         		    = trim(pg_fetch_result($res,$x,ativo));
	$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	if($ativo=='t') $ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
	else            $ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
?>
	<tr >
		<td><? echo $codigo_familia;?></td>
		<td>
			<a href="<? echo $PHP_SELF.'?familia_peca='.$familia;if(isset($semcab)) echo '&semcab=yes';echo '' ?>"><? echo $descricao;?></a>
		</td>
		<td class="tac"><? echo $ativo;?></td>

	</tr>
<? } ?>
	</tbody>
</table>
<? include "rodape.php"; ?>
