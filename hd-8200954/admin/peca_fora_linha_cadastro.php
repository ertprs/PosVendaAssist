<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_REQUEST["peca_fora_linha"]) > 0) {
	$peca_fora_linha = trim($_REQUEST["peca_fora_linha"]);
}

if ($_GET["btn_acao"] == "deletar" && strlen($_GET["peca"]) > 0) {

	$res = pg_query ($con, "BEGIN TRANSACTION");

	$peca_fora_linha = $_GET["peca"];

	$sql = "DELETE FROM tbl_peca_fora_linha
			WHERE
				tbl_peca_fora_linha.peca_fora_linha = $peca_fora_linha
				AND tbl_peca_fora_linha.fabrica = $login_fabrica;";
	$res = pg_query ($con, $sql);

	$msg_erro = pg_last_error();

	if (strlen($msg_erro) == 0) {

		/* CONCLUI OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E SUBMETE */

		$res = pg_query ($con, "COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");

		exit;

	}else{

		/* ABORTA OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E RECARREGA CAMPOS */

		$referencia = $_POST["referencia"];
		$descricao  = $_POST["descricao"];
		$digitacao  = $_POST["digitacao"];

		$res = pg_query ($con, "ROLLBACK TRANSACTION");

	}

}

if ($_GET["btn_acao"] == "liberarBloquear" && !empty($_GET["peca"])) {
	$res = pg_query ($con, "BEGIN TRANSACTION");

	$peca_fora_linha = $_GET["peca"];
	$acao = ($_GET["acao"] == "sim") ? " libera_garantia = false " : "  libera_garantia = true ";


	$sql = "UPDATE tbl_peca_fora_linha SET $acao WHERE tbl_peca_fora_linha.peca_fora_linha = $peca_fora_linha AND tbl_peca_fora_linha.fabrica = $login_fabrica;";
	$res = pg_query ($con, $sql);

	$msg_erro = pg_last_error();

	if (strlen($msg_erro) == 0) {

		$ref  = $_GET["referencia"];
		$desc = $_GET["descricao"];
		$gar  = $_GET["garantia"];

		$locationNew = $PHP_SELF."?referencia=$ref&descricao=$desc&garantia_select=$gar&btn_acao=pesquisar";

		$res = pg_query ($con, "COMMIT TRANSACTION");
		
		header ("Location: $locationNew");

		exit;

	}else{

		$res = pg_query ($con, "ROLLBACK TRANSACTION");

	}
}

if(isset($_POST["upload_arquivo_pecas"])){

	$arquivo = $_FILES["arquivo"];

	if($arquivo["size"] == 0){
		$msg_erro["msg"][] = traduz("Por favor, insira um arquivo para realizar o Upload");
	}

	$arquivo_nome = explode(".", $arquivo["name"]);
	$ext          = $arquivo_nome[count($arquivo_nome) - 1];

	if(!in_array($ext, array("csv", "txt"))){
		$msg_erro["msg"][] = traduz("Por favor, insira um arquivo TXT ou CSV");
	}

	if(count($msg_erro) == 0){

		$conteudo   = file_get_contents($arquivo["tmp_name"]);
		$linhas     = explode("\n", $conteudo);

		$cont_linha     = 1;
		$pecas_gravadas = 0;

		foreach ($linhas as $linha) {

			if (empty($linha)) {
				continue;
			}

			$dados = explode(";", $linha);

			$peca_referencia   = trim($dados[0]);
			$liberado_garantia = trim($dados[1]);
			$excluirPeca       = (isset($dados[2]) && strtolower(trim($dados[2])) == 'sim') ? true : false; 

			$linha_erro = false;

			if(strlen($peca_referencia) == 0){

				$linha_erro = true;
				$msg_erro["msg"][] = traduz("A linha {$cont_linha} n�o est� com as informa��es corretas");

			}else if( ($login_fabrica != 3 && !in_array(strtolower($liberado_garantia), array("n�o", "nao", "sim", "", " "))) || ($login_fabrica == 3 && !$excluirPeca && !in_array(strtolower($liberado_garantia), array("n�o", "nao", "sim", "", " "))) ){

				$linha_erro = true;
				$msg_erro["msg"][] = traduz("A linha {$cont_linha} est� com a informa��o de <u>liberado para garantia</u> diferente de <u>sim</u> ou <u>nao</u>");

			}else{

				$sql_peca = "SELECT peca FROM tbl_peca WHERE UPPER(TRIM(referencia)) = UPPER(TRIM('{$peca_referencia}')) AND fabrica = {$login_fabrica}";
				$res_peca = pg_query($con, $sql_peca);

				if(pg_num_rows($res_peca) == 0){

					$linha_erro = true;
					$msg_erro["msg"][] = traduz("A linha {$cont_linha}: A peca <u>{$peca_referencia}</u> n�o foi localizada na base dados");

				}else{

					$peca = pg_fetch_result($res_peca, 0, "peca");

				}

			}

			if($linha_erro == false && strlen($peca) > 0){

                /*
                 * - Pe�a FORA DE LINHA
                 */

                if (count($msg_erro) == 0 && (($liberado_garantia != "sim" && $login_fabrica != 3) || ($login_fabrica == 3 && !$excluirPeca && $liberado_garantia != "sim"))) {
                    $sqlFora = "SELECT  tbl_kit_peca_peca.peca
                                FROM    tbl_kit_peca_peca
                                JOIN    tbl_kit_peca USING(kit_peca)
                                WHERE   tbl_kit_peca.fabrica = $login_fabrica
                                AND     tbl_kit_peca_peca.peca = $peca
                    ";

                    $resFora = pg_query($con,$sqlFora);
                    if (pg_num_rows($resFora) > 0) {
                        $msg_erro["msg"][] = traduz("Pe�a $peca_referencia pertence a kits com outras pe�as para cadastro em OS. <br />Fa�as as configura��es desses kits para retirada efetiva da pe�a de linha.");
                    }
                }

				$sql_peca_fora_linha = "SELECT peca_fora_linha FROM tbl_peca_fora_linha WHERE fabrica = {$login_fabrica} AND peca = {$peca}";
				$res_peca_fora_linha = pg_query($con, $sql_peca_fora_linha);

				$liberado_garantia = ($liberado_garantia == "sim") ? "t" : "f";

				if(pg_num_rows($res_peca_fora_linha) > 0){
					
					if ($excluirPeca) {
						$opr = "deletar";
						$peca_fora_linha = pg_fetch_result($res_peca_fora_linha, 0, 'peca_fora_linha');

						if (!empty($peca_fora_linha)) {
							$sql = "DELETE FROM tbl_peca_fora_linha
									WHERE
										tbl_peca_fora_linha.peca_fora_linha = $peca_fora_linha
										AND tbl_peca_fora_linha.fabrica = $login_fabrica;";
						} else {
							$sql = "";
							$msg_erro["msg"][] = traduz("Pe�a: $peca_referencia n�o encontrada para exclus�o.");
						}
					} else {
						$opr = "update";
						$sql = "UPDATE tbl_peca_fora_linha SET libera_garantia = '{$liberado_garantia}', referencia = '{$peca_referencia}' WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
					}

				}else{

					$opr = "insert";
					$sql = "INSERT INTO tbl_peca_fora_linha (
												fabrica,
												referencia,
												libera_garantia,
												peca
											) VALUES (
												$login_fabrica,
												'$peca_referencia',
												'$liberado_garantia',
												$peca
											);";

				}

				pg_query($con, "BEGIN TRANSACTION");

				$res = pg_query($con, $sql);

				if(strlen(pg_last_error()) > 0){

					pg_query($con, "ROLLBACK TRANSACTION");

					if ($opr == "insert") {
						$opr = "gravar";
					} else if ($opr == "deletar") {
						$opr = "Excluir";
					} else {
						$opr = "atualizar";
					}
					
					$msg_erro["msg"][] = traduz("A linha {$cont_linha}: Erro ao {$opr} a peca <u>{$peca_referencia}</u>");

				}else{

					pg_query($con, "COMMIT TRANSACTION");

					$pecas_gravadas++;

				}

			}

			$cont_linha++;

		}

		if($pecas_gravadas > 0){

			$msg_sucesso = traduz("Upload realizado com Sucesso");

		}

	}

}

if ($_POST["btn_acao"] == "gravar") {

	if ($login_fabrica == 3) {

		if (!empty($_POST["referencia"])) {
			$sqlFl = "SELECT peca_fora_linha FROM tbl_peca_fora_linha WHERE referencia = '".trim($_POST["referencia"])."' AND fabrica = $login_fabrica LIMIT 1";
			$resFl = pg_query($con, $sqlFl);
			if (pg_num_rows($resFl) > 0) {
				$peca_fora_linha = pg_fetch_result($resFl, 0, 'peca_fora_linha');
			}
		}
	} else {
		$peca_fora_linha = $_POST["peca_fora_linha"];
	}

	if (strlen($_POST["referencia"]) > 0) {
		$aux_referencia = "'". trim($_POST["referencia"]) ."'";
	}else{
		$msg_erro["msg"][] = traduz("Por favor informe a Refer�ncia");
		$msg_erro["campos"][] = "peca";
	}

	if ($login_fabrica == 3) {
		$xlibera_garantia = "'f'";
		if ($_POST['garantia_select'] == "sim") {
			$xlibera_garantia = "'t'";
		}
	} else {
		$xlibera_garantia = (strlen($_POST['libera_garantia']) > 0) ? "'t'" : "'f'";
	}

	if ($login_fabrica == 40) {
		$cond = "trim(tbl_peca.referencia) = trim($aux_referencia)";
	} else {
		$cond = "trim(tbl_peca.referencia) = upper(trim($aux_referencia))";
	}
	if (count($msg_erro["msg"]) == 0) {

		$sql = "SELECT * FROM tbl_peca WHERE {$cond} AND tbl_peca.fabrica = $login_fabrica;";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){
			$msg_erro["msg"][] = traduz("Pe�a informada n�o encontrada");
		}else{
			$ypeca = pg_fetch_result($res, 0, "peca");
		}

	}

	if (count($msg_erro) == 0) {

		$res = pg_query($con, "BEGIN TRANSACTION");

		if (strlen($peca_fora_linha) == 0) {

            /*
             * - Pe�a FORA DE LINHA
             */
            if (count($msg_erro) == 0 && $xlibera_garantia == "'f'") {
                $sqlFora = "SELECT  tbl_kit_peca_peca.peca
                            FROM    tbl_kit_peca_peca
                            JOIN    tbl_kit_peca USING(kit_peca)
                            WHERE   tbl_kit_peca.fabrica = $login_fabrica
                            AND     tbl_kit_peca_peca.peca = $ypeca
                ";
                $resFora = pg_query($con,$sqlFora);
                if (pg_num_rows($resFora) > 0) {
                    $msg_erro["msg"][] = traduz("Pe�a $aux_referencia pertence a kits com outras pe�as para cadastro em OS. <br />Fa�as as configura��es desses kits para retirada efetiva da pe�a de linha.");
                }
            }
			/* INSERE NOVO REGISTRO */

			$sql = "SELECT * FROM tbl_peca_fora_linha WHERE fabrica = {$login_fabrica} AND referencia = {$aux_referencia}";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$msg_erro["msg"][] = traduz("A Pe�a {$aux_referencia} j� est� cadastrada");

			}else{

				$opr = "insert";

				$sql = "INSERT INTO tbl_peca_fora_linha (
							fabrica,
							referencia,
							libera_garantia,
							peca
						) VALUES (
							$login_fabrica,
							$aux_referencia,
							$xlibera_garantia,
							$ypeca
						);";

			}

		}else{

			$opr = "update";

			/* ALTERA REGISTRO */

			$sql = "UPDATE tbl_peca_fora_linha SET
						referencia = $aux_referencia,
						libera_garantia = $xlibera_garantia
					WHERE tbl_peca_fora_linha.peca_fora_linha = $peca_fora_linha
					AND tbl_peca_fora_linha.fabrica = $login_fabrica;";

		}

		if(count($msg_erro) == 0){

			$res = pg_query($con,$sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro["msg"][] = pg_last_error();
			}

		}

	}

	if (count($msg_erro) == 0) {

		$res = pg_query($con, "COMMIT TRANSACTION");
		$msg_sucesso = ($opr == "insert") ? traduz("Pe�a Gravada com Sucesso") : traduz("Pe�a Alterada com Sucesso");

		if($opr == "insert"){
			unset($_POST);
		}

	}else{

		$referencia      = $_POST["referencia"];
		$descricao       = $_POST["descricao"];
		$libera_garantia = $_POST["libera_garantia"];
		$digitacao       = $_POST["digitacao"];

		$res = pg_query($con, "ROLLBACK TRANSACTION");

	}
}

/* CARREGA REGISTRO */
if (strlen($peca_fora_linha) > 0) {
	$sql = "SELECT  tbl_peca_fora_linha.referencia,
					(
					SELECT tbl_peca.descricao
					FROM   tbl_peca
					WHERE  tbl_peca.referencia = tbl_peca_fora_linha.referencia
					AND tbl_peca.fabrica = $login_fabrica
					) AS descricao,
					libera_garantia,
					digitacao
			FROM    tbl_peca_fora_linha
			WHERE   tbl_peca_fora_linha.fabrica = $login_fabrica
			AND     tbl_peca_fora_linha.peca_fora_linha  = $peca_fora_linha;";
	$res = pg_query ($con,$sql);

	if (pg_numrows($res) > 0) {
		$referencia      = trim(pg_fetch_result($res,0,referencia));
		$descricao       = trim(pg_fetch_result($res,0,descricao));
		$libera_garantia = trim(pg_fetch_result($res,0,libera_garantia));
		$digitacao       = trim(pg_fetch_result($res,0,digitacao));
	}
}

$garantiaPecaValue = (!empty($_REQUEST["garantia_select"])) ? $_REQUEST["garantia_select"] : '';

$layout_menu = 'cadastro';
$title = traduz("Cadastro de Pe�as Fora de Linha");

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"shadowbox",
	"dataTable"
);

include("plugin_loader.php");

?>

<script>
	$(function() {

		// $.autocompleteLoad(Array("peca"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$.dataTableLoad({
	        table : "#listagem"
	    });

	});

	function retorna_peca(retorno){
		// $("#peca").val(retorno.peca);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

    function deletar_peca(peca, referencia){

    	var r = confirm('<?=traduz("Voc� tem certeza que quer excluir a pe�a ")?>' +referencia+ "?");

    	if(r == true){

    		location.href = "peca_fora_linha_cadastro.php?btn_acao=deletar&peca="+peca;

    	}

    }

    function liberarBloquear_peca(peca, referencia, descricao, garantia, acao){

    	var r = confirm('<?=traduz("Voc� tem certeza que quer alterar � pe�a ")?>' +referencia+ "?");

    	if(r == true){

    		location.href = "peca_fora_linha_cadastro.php?btn_acao=liberarBloquear&peca="+peca+"&acao="+acao+"&referencia="+referencia+"&descricao="+descricao+"&garantia="+garantia;

    	}

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
if(strlen($msg_sucesso) > 0){
	echo "<div class='alert alert-success'><h4> {$msg_sucesso} </h4></div>";
}
?>

<?php if ($login_fabrica == 3) { ?>
	
	<div class="alert alert-warning">
		<h4><?=traduz('Para exibir todas as pe�as, fa�a a busca sem filtros.')?></h4>
	</div>

	<form name="frm_relatorio" method="post" action="<?php echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

	    <div class='titulo_tabela '><?=traduz('Par�metros de Pesquisa')?></div>
	    <input type="hidden" name="peca_fora_linha" id="peca" value="<? echo $peca_fora_linha ?>">

	    <br/>

	    <div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'><?=traduz('Ref. Pe�as')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="referencia" class='span12' maxlength="20" value="<?php echo $referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'><?=traduz('Descri��o Pe�a')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="descricao" class='span12' value="<?php echo $descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<label class="checkbox"><?=traduz("Garantia")?></label>
				<div class='controls controls-row'>
					<div class='span8 input-append'>
						<select name="garantia_select" class="span12">
							<option value="" <?=(empty($garantiaPecaValue)) ? 'selected' : ''?> ><?=traduz("Selecione")?></option>
							<option value="sim" <?=($garantiaPecaValue == 'sim') ? 'selected' : ''?>><?=traduz("Sim")?></option>
							<option value="nao" <?=($garantiaPecaValue == 'nao') ? 'selected' : ''?>><?=traduz("N�o")?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<br />
		<div class="row-fluid">
			<div class="span12 tac">
				<button class='btn btn-info' id="btn_acao" name="btn_acao" value="pesquisar" ><?=traduz("Pesquisar")?> </button>
				<button class='btn btn-success' id="btn_acao" name="btn_acao" value="gravar" ><?=traduz("Gravar")?> </button>
				<!-- <input type='hidden' id="btn_click" name='btn_acao' value='pesquisar' /> -->
			</div>
		</div>
	</form>
<?php } else { ?>
	<div class="row">
		<strong class="obrigatorio pull-right"> * <?=traduz('Campos obrigat�rios')?></strong>
	</div>

	<form name="frm_relatorio" method="post" action="<?php echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>

	    <div class='titulo_tabela '><?=traduz('Par�metros de Pesquisa')?></div>
	    <input type="hidden" name="peca_fora_linha" id="peca" value="<? echo $peca_fora_linha ?>">

	    <br/>

	    <div class="row-fluid">

			<div class="span2"></div>

			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'><?=traduz('Ref. Pe�as')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" id="peca_referencia" name="referencia" class='span12' maxlength="20" value="<?php echo $referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'><?=traduz('Descri��o Pe�a')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="descricao" class='span12' value="<?php echo $descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>

		</div>

		<div class="row-fluid" style="min-height: 25px !important;">

			<div class="span2"></div>

			<div class="span8">
				<label class="checkbox">
					<input type='checkbox' name='libera_garantia'<? if ($libera_garantia == 't' ) echo " checked "; ?> value='t'> <strong><?=traduz('Liberado para garantia')?></strong>
				</label>
			</div>

		</div>

		<p>
			<button class='btn' id="btn_acao" > <?php echo (strlen($peca_fora_linha) == 0) ? traduz("Gravar") : traduz("Alterar"); ?> </button>
			<input type='hidden' id="btn_click" name='btn_acao' value='gravar' />
		</p>

		<br />

	</form>
<?php } ?>

<?php if(in_array($login_fabrica, array(3,85))){ ?>

<!--

	refer�ncia;liberado_garantia
	EX.: 007197.02;nao
	006185.01;sim

-->

<?php if ($login_fabrica == 3) { ?>
	<div class="alert alert-warning">
		<?=traduz('O arquivo dever� seguir o seguinte layout em seu conte�do, sendo a')?> <strong><?=traduz('ref�rencia da pe�a')?></strong>, <?=traduz(' se a mesma est�')?> <strong><?=traduz('liberada para garantia')?></strong> <strong><?=traduz(" e se ser� excluida, ")?></strong> <?=traduz('separados por')?> <strong><?=traduz('ponto e virgula')?> (;)</strong>.
		<br />
		<strong><?=traduz('Obs: Adicionar informa��o de exclus�o, somente para as que ser�o exclu�das. As demais pode deixar sem valor.')?></strong> <br /> <br />
		<?=traduz('Confira o exemplo abaixo')?>: <br /> <br />
		peca123;<?=traduz('sim')?>;<?=traduz('sim')?> <br />
		peca456;<?=traduz('nao')?> <br />
		<br>
		<strong><?=traduz('Para exclus�o seguir o layout abaixo:')?></strong> <br /> <br />
		peca123;;<?=traduz('sim')?> <br />
		peca456;;<?=traduz('sim')?> <br />
	</div>
<?php } else { ?>
	<div class="alert alert-warning">
		<?=traduz('O arquivo dever� seguir o seguinte layout em seu conte�do, sendo a')?> <strong><?=traduz('ref�rencia da pe�a')?></strong> <?=traduz('e se a mesma est�')?> <strong><?=traduz('liberada para garantia')?></strong>, <?=traduz('separados por')?> <strong><?=traduz('ponto e virgula')?> (;)</strong>.
		<?=traduz('Confira o exemplo abaixo')?>: <br /> <br />
		peca123;<?=traduz('sim')?> <br />
		peca456;<?=traduz('nao')?> <br />
	</div>
<?php } ?>

<form name="frm_relatorio" method="post" action="<?php echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data">

    <div class='titulo_tabela '><?=traduz('Upload de Arquivos de Pe�as')?></div>

    <input type="hidden" name="upload_arquivo_pecas" value="sim">

    <br/>

    <div class="row-fluid">

		<div class="span2"></div>

		<div class='span8'>
			<label class='control-label' for='peca_referencia'><?=traduz('Arquivo')?></label>
			<div class='controls controls-row'>
				<div class='span7 input-append'>
					<h5 class='asteristico'>*</h5>
					<input type="file" id="arquivo" name="arquivo" class='span12'>
				</div>
			</div>
		</div>

	</div>

	<div class="row-fluid">

		<div class="span2"></div>

		<div class="span8 tac">
			<input type="submit" class="btn btn-info" value='<?=traduz("Realizar Upload")?>'>
		</div>

	</div>

</form>

<?php } ?>

<br />

<?php
if($login_fabrica != 1){
	if ($login_fabrica == 3) {
		if ($_REQUEST["btn_acao"] == "pesquisar") {

			if  (!empty($_REQUEST["referencia"])) {
				$referenciaPeca = " AND trim(tbl_peca.referencia) = trim('".$_REQUEST["referencia"]."') ";
			} 

			if  (!empty($_REQUEST["descricao"]) && empty($_REQUEST["referencia"])) {
				$descricaoPeca = " AND tbl_peca.descricao = '".$_REQUEST["descricao"]."' ";
			}

			if  (!empty($_REQUEST["garantia_select"])) {
				$garantiaPecaValue = $_REQUEST["garantia_select"];
				$garantiaPeca = ($_REQUEST["garantia_select"] == 'sim') ? "  AND libera_garantia IS TRUE " : " AND libera_garantia IS NOT TRUE ";
			}

			$sqlR = "SELECT
					tbl_peca_fora_linha.peca_fora_linha,
					tbl_peca.referencia_fabrica,
					tbl_peca.referencia,
					tbl_peca.descricao,
					libera_garantia,
					TO_CHAR(tbl_peca_fora_linha.digitacao,'DD/MM/YYYY') AS digitacao
				FROM tbl_peca_fora_linha
				JOIN tbl_peca ON tbl_peca.peca = tbl_peca_fora_linha.peca
				WHERE
					tbl_peca_fora_linha.fabrica = $login_fabrica
					$referenciaPeca
					$descricaoPeca
					$garantiaPeca
				ORDER BY descricao";
		}		
	} else {
		$sqlR = "SELECT
					tbl_peca_fora_linha.peca_fora_linha,
					tbl_peca.referencia_fabrica,
					tbl_peca.referencia,
					tbl_peca.descricao,
					libera_garantia,
					TO_CHAR(tbl_peca_fora_linha.digitacao,'DD/MM/YYYY') AS digitacao
				FROM tbl_peca_fora_linha
				JOIN tbl_peca ON tbl_peca.peca = tbl_peca_fora_linha.peca
				WHERE
					tbl_peca_fora_linha.fabrica = $login_fabrica
				ORDER BY descricao";
	}

}else{ //chamado 1257

	$sqlR = "SELECT
				DISTINCT tbl_peca_fora_linha.peca_fora_linha,
				tbl_peca_fora_linha.referencia,
				tbl_peca.descricao,
				libera_garantia
			FROM tbl_peca_fora_linha
			JOIN tbl_peca using(peca)
			WHERE
				tbl_peca_fora_linha.fabrica = $login_fabrica
				AND tbl_peca.fabrica = $login_fabrica
			ORDER BY descricao;";

}

$res = pg_query($con, $sqlR);

if (pg_num_rows($res) > 0) {

	$colspan = (!in_array($login_fabrica, array(3,171))) ? 4 : 5;

	$dados = "<table id='listagem' class='table table-bordered table-striped' style='width: 100%;'>";
		$dados .= "<thead>";
			$dados .= "<tr class='titulo_tabela'><th colspan='{$colspan}'>".traduz("Relat�rio de Pe�as Fora de Linha")."</th></tr>";
			$dados .= "<tr class='titulo_coluna'>";

				if($login_fabrica == 171){
					$dados .= "<th>".traduz("Refer�ncia F�brica")."</th>";
				}
				$dados .= "<th>".traduz("Refer�ncia")."</th>";
				$dados .= "<th>".traduz("Descri��o")."</th>";
				if($login_fabrica == 3){
					$dados .= "<th>".traduz("Inclus�o")."</th>";
				}
				$dados .= "<th>".traduz("Liberado Garantia")."</th>";
				$dados .= "<th>".traduz("A��o")."</th>";
			$dados .= "</tr>";
		$dados .= "</thead>";

		$dados .= "<tbody>";

		/**
		 * Formata��o para Excel
		 * Inserido separadamento pois receber� uma formata��o especifica
		 * */
		$arquivo_nome     = "xls/peca-fora-linha-cadastro-$login_fabrica.xls";

		$fp = fopen("$arquivo_nome", "w+");

		if($login_fabrica == 3){
			$xls = utf8_encode(traduz("Refer�ncia")).";".utf8_encode(traduz("Descri��o")).";".utf8_encode(traduz("Inclus�o")).traduz(";Liberado Garantia\n");
		}elseif($login_fabrica == 171){
			$xls = utf8_encode("Refer�ncia F�brica").";".utf8_encode(traduz("Refer�ncia")).";".utf8_encode(traduz("Descri��o")).";".utf8_encode(traduz("Inclus�o")).traduz(";Liberado Garantia\n");
		}else{
			$xls = utf8_encode(traduz("Refer�ncia")).";".utf8_encode(traduz("Descri��o")).traduz(";Liberado Garantia\n");
		}
		
			/*
			 * Fim do XLS HEAD
			 */

			for ($y = 0 ; $y < pg_numrows($res) ; $y++){

				$peca_fora_linha = trim(pg_fetch_result($res, $y, "peca_fora_linha"));
				$referencia      = trim(pg_fetch_result($res, $y, "referencia"));
				$descricao       = trim(pg_fetch_result($res, $y, "descricao"));
				$referencia_fabrica       = trim(pg_fetch_result($res, $y, "referencia_fabrica"));

				if($login_fabrica == 3){
					$digitacao = trim(pg_fetch_result($res, $y, "digitacao"));
				}

				$libera_garantia = trim(pg_fetch_result($res, $y, "libera_garantia"));
				$libera_garantiaId = $libera_garantia;
				$libera_garantia_xls = ($libera_garantia == "t") ? traduz("Sim") : traduz("N�o");
				$libera_garantia = ($libera_garantia == "t") ? "<img src='imagens/status_verde.png' border='0' alt='Sim'>" :  "<img src='imagens/status_vermelho.png' border='0' alt='N�o'>";
				

				$dados .= "<tr>";
					if($login_fabrica == 171){
						$dados .= "<td>$referencia_fabrica</td>";
					}
					$dados .= "<td>";
					if ($login_fabrica == 3) {
						$dados .= $referencia;
					} else {
						$dados .= "<a href='$PHP_SELF?peca_fora_linha=$peca_fora_linha'>$referencia</a>";
					}
					$dados .= "</td>";
					$dados .= "<td align='left'>";
					if ($login_fabrica == 3) {
						$dados .= $descricao;
					} else {
						$dados .= "<a href='$PHP_SELF?peca_fora_linha=$peca_fora_linha'>$descricao</a>";
					}
					$dados .= "</td>";
					if($login_fabrica == 3){
						$dados .= "<td>$digitacao</td>";
						if ($libera_garantiaId == "t") {
							$dados .= "<td class='tac'><a href='javascript: liberarBloquear_peca({$peca_fora_linha}, \"{$referencia}\", \"{$descricao}\", \"$garantiaPecaValue\", \"sim\")' class='btn btn-success'>Sim</a></td>";
						} else {
							$dados .= "<td class='tac'><a href='javascript: liberarBloquear_peca({$peca_fora_linha}, \"{$referencia}\", \"{$descricao}\", \"$garantiaPecaValue\", \"nao\")' class='btn btn-danger'>N�o</a></td>";
						}
					} else {
						$dados .= "<td class='tac'>$libera_garantia</td>";
					}
					$dados .= "<td class='tac'> <a href='javascript: deletar_peca({$peca_fora_linha}, \"{$referencia}\")' class='btn btn-danger'> Excluir </a> </td>";
				$dados .= "</tr>";

				/**
				 * Formata��o para Excel
				 * Inserido separadamento pois receber� uma formata��o especifica
				 * */

				if($login_fabrica == 3){
					$xls .= "$referencia;".utf8_encode("$descricao").";$digitacao;".utf8_encode("$libera_garantia_xls")." \r\n";
				}elseif($login_fabrica == 171){
					$xls .= "$referencia_fabrica;"."$referencia;".utf8_encode("$descricao").";$digitacao;".utf8_encode("$libera_garantia_xls")." \r\n";
				}else{
					$xls .= "$referencia;".utf8_encode("$descricao").";".utf8_encode("$libera_garantia_xls")." \r\n";
				}


				/*
				 * Fim do XLS BODY
				 */
			}

		$dados .= "</tbody>";
	$dados .= "</table>";

	echo $dados;

	fwrite($fp, "$xls");

	fclose($fp); 

	flush();
	$data = date ("d/m/Y H:i:s");
	
	$path_tmp         = "/tmp/";

	$arquivo_completo     = $arquivo_nome;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

	/*echo `rm $arquivo_completo_tmp `;
	echo `rm $arquivo_completo `;

	$fp = fopen ($arquivo_completo_tmp,"w");
	fputs ($fp,$xls);

	echo ` cp $arquivo_completo_tmp $path `;
	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;*/

	?>

	<br /> <br />

	<a href='<?=$arquivo_nome;?>' target='_blank'>
		<div class="btn_excel">
			<span>
				<img src="imagens/excel.png" />
			</span>
			<span class="txt"><?=traduz('Download em Excel')?></span>
		</div>
	</a>

	<br />

	<?php

} else if ($_REQUEST["btn_acao"] == 'pesquisar') { ?>
	<div class="container">
		<div class="alert">
			    <h4>Nenhum resultado encontrado</h4>
		</div>
	</div>
<?php }


include "rodape.php";

?>
