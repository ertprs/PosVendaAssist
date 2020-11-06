<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';

$fabrica_nome = strtolower($login_fabrica_nome);

##### A T U A L I Z A R #####

if (strlen($_POST["btn_acao"]) > 0) {

	$caminho = "/tmp/".$fabrica_nome;
	system("mkdir -m 777 -p $caminho");
	system("mkdir -p $caminho/nao_bkp");

	// arquivo
	$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		flush();

		// Tamanho máximo do arquivo (em bytes)
		$config["tamanho"] = 2048000;

		// Verifica o mime-type do arquivo
		if ($arquivo["type"] <> 'text/plain') {
			$msg_erro = traduz("Arquivo em formato inválido!");
		} else {
			// Verifica tamanho do arquivo
			if ($arquivo["size"] > $config["tamanho"])
				$msg_erro = traduz("Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.");
		}

		if (strlen($msg_erro) == 0) {

			// Exclui anteriores, qquer extensao
			system("mv -f $caminho/*txt $caminho/nao_bkp");

			// Faz o upload
			if (strlen($msg_erro) == 0) {
				if (!move_uploaded_file($arquivo["tmp_name"], $caminho."/telecontrol-lista-basica.txt")) {
					$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
				}

				// deixar mensagem

				#exec ("cd $caminho/ ; unzip -o " . $arquivo['name']);

				$arq_data = date("Ymd-His");

				$res = pg_query($con,"SELECT to_char(current_date,'MMYYYY');");
				$mes_ano = pg_fetch_result($res, 0, 0);

				#exec ("mv -f $caminho/" . $arquivo["name"] . " $caminho/$arq_data-".$arquivo["name"]);
				#exec ("mv -f $caminho/$arq_data-".$arquivo["name"]." $caminho/bkp-entrada");
			}
		}
	} else {
		$msg_erro = traduz("Selecionar uma arquivo para upload!");
	}

	flush();

	if (strlen($msg_erro) == 0 ) {


		if(file_exists("../rotinas/telecontrol/importa-lista-basica.php")){

			$sql = "SELECT email FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}; ";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				$email_admin_rotina = pg_fetch_result($res, 0, email);
			} else {
				$email_admin_rotina = "";
			}

			exec("php ../rotinas/telecontrol/importa-lista-basica.php $login_fabrica $email_admin_rotina",$ret);

			if ($ret <> "0") {
				//var_dump($ret);
				$msg_erro .= $ret[0];
				//$msg_erro .= "Não foi possível fazer a importação da lista básica. Verifique seu arquivo.";
			}
		} else {
			$msg_erro = traduz("Rotina de importação lista básica, não encontrada!");
		}
	}

	if (strlen($msg_erro) == 0) {
		$msg = traduz("Arquivos importados com sucesso!"); //!!<br>A atualização da tabela será feita nesta noite.";
	}
}

$layout_menu = "callcenter";
$title = traduz("Importação de Lista Básica");

include "cabecalho_new.php";

?>


<?php
if (strlen($msg_erro) > 0) {
?>
<br />
	<div class="alert alert-error">
		<h4><?=$msg_erro;?></h4>
	</div>
<?php
}
?>
<?php
if (strlen($msg) > 0) {
?>
<br />
    <div class="alert alert-success">
		<h4> <?=$msg;?></h4>
    </div>
<?php
} ?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<FORM name='upload_lista_basica' METHOD='POST' ACTION='<?=$PHP_SELF?>' class="form-search form-inline" enctype="multipart/form-data">
	<div id="div_upload" class="tc_formulario">
		<div class="titulo_tabela"><?=traduz('Importação de Lista Básica')?></div>
		<br>
		<div class='row-fluid'>
			<div class='span1'></div>
			<div class='span10'>
				<div class='control-group'>
					<div class="alert alert-block alert-warning">
	         			** <?=traduz('O formato do arquivo deve ser texto(.txt), ter no máximo 2MB e o dados devem ser separados por')?> <strong>TAB</strong><br />     
	         			<strong><?=traduz('Layout do arquivo:')?></strong>
	         			<br />
	         			<?=traduz('referencia_produto')?>&nbsp;&nbsp;<?=traduz('referencia_peca')?>&nbsp;&nbsp;<?=traduz('quantidade')?>&nbsp;&nbsp;<?=traduz('posicao')?>
	         			<br />
					 	<!-- <strong>* Campos obrigatórios:</strong>(*referencia_produto&nbsp;&nbsp;*referencia_peca&nbsp;&nbsp;*quantidade&nbsp;&nbsp;posicao) -->
				    </div>
				</div>
			</div>
	   		<div class="span1"></div>
	   	</div>
	   	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group'>
					<label class='control-label'><?=traduz('Anexar Arquivo')?>:</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type='file' name='arquivo_zip' size='30'>
						</div>
					</div>
				</div>
			</div>
	   		<div class="span2"></div>
	   	</div>
		<br />
		<p class="tac">
			<input type="submit" class="btn btn-primary" name="btn_acao" value="<?=traduz('Gravar')?>" />			
		</p>
		<br />
	</div>
</FORM>
<br />
<?php
include "rodape.php";
