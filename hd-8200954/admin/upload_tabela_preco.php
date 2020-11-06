<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {
	$fabrica_nome = strtolower(str_replace(' ', '', $login_fabrica_nome));

	$caminho = ($login_fabrica != 6) ? "/tmp/".strtolower($fabrica_nome) : "/tmp/tectoy";
    system("mkdir -m 777 -p $caminho");
    system("mkdir -p {$caminho}/nao_bkp");

	if($login_fabrica != 140){

		// arquivo
		$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

		if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
			flush();

			// Tamanho máximo do arquivo (em bytes) 
			$config["tamanho"] = 2048000;

			// Verifica o mime-type do arquivo
			if ($arquivo["type"] <> 'text/plain' AND !strpos($arquivo["type"],"zip")) {

				$msg_erro = traduz("Arquivo em formato inválido!");
			} else {
				// Verifica tamanho do arquivo 
				if ($arquivo["size"] > $config["tamanho"]) 
					$msg_erro = traduz("Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.");
			}

			if (strlen($msg_erro) == 0) {

				// Exclui anteriores, qquer extensao
				exec("mv -f $caminho/*txt $caminho/nao_bkp");
				
				// Faz o upload
				if (strlen($msg_erro) == 0) {

					if($login_fabrica == 6){
						if (!copy($arquivo["tmp_name"], $caminho."/" . $arquivo["name"])) {
							$msg_erro = traduz("Arquivo '").$arquivo['name']."' não foi enviado!!!";
						}

						exec ("cd $caminho/ ; unzip -o " . $arquivo['name']);

						$arq_data = date("Ymd-His");

						$res = pg_exec($con,"SELECT to_char(current_date,'MMYYYY'); ");
						$mes_ano = pg_result($res,0,0);

						exec ("mv -f $caminho/" . $arquivo["name"] . " $caminho/$arq_data-".$arquivo["name"]);
						exec ("mv -f $caminho/$arq_data-".$arquivo["name"]." $caminho/bkp-entrada");

						$msg = traduz("Arquivo importado com sucesso!");
					}else{
						if (!copy($arquivo["tmp_name"], $caminho."/telecontrol-preco.txt")) {
							$msg_erro = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
						}
					
						// deixar mensagem
						

						#exec ("cd $caminho/ ; unzip -o " . $arquivo['name']);

						$arq_data = date("Ymd-His");

						$res = pg_exec($con,"SELECT to_char(current_date,'MMYYYY'); ");
						$mes_ano = pg_result($res,0,0);

						#exec ("mv -f $caminho/" . $arquivo["name"] . " $caminho/$arq_data-".$arquivo["name"]);
						#exec ("mv -f $caminho/$arq_data-".$arquivo["name"]." $caminho/bkp-entrada");

						$msg = traduz("Arquivo importado com sucesso!");
					}
                			}
				if (strlen($msg_erro) == 0){
					$executa_1 = 't';
				}
			}
		}
	}else{

		$fabrica_nome = strtolower($login_fabrica_nome);
		// arquivo
		$arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

		if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){

			flush();

			if (!move_uploaded_file($arquivo["tmp_name"], $caminho."/telecontrol-preco.txt")) {
				$msg = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$msg = traduz("Arquivo importado com sucesso!");
			}

		}

	}
	
	flush();
	
	if (strlen($msg_erro) == 0 ) {
		if($login_fabrica != 6){
			system("php ../rotinas/telecontrol/importa-preco.php $login_fabrica $fabrica_nome 1",$ret);
		}else{
			system("php ../rotinas/tectoy/importa-precos.php",$ret);
		}
		
		if ($ret <> "0") {
			$msg_erro .= traduz("Não foi possível fazer a importação das tabelas de preços ($ret). Verifique seu arquivo.");
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$msg = traduz("Arquivos importados com sucesso!"); //!!<br>A atualização da tabela será feita nesta noite.";
	}else{
		echo "<h1></h1>";
	}
}

$layout_menu = "callcenter";
$title = traduz("Importação de Tabela de Preço");

include "cabecalho_new.php";

?>

<p>

<?
if (strlen($msg_erro) > 0) {
?>
	<div class="alert alert-danger"><h4><?= $msg_erro ?></h4></div>
<?
	$msg="";
}

if (strlen($msg) > 0) {
	?>
	<div class="alert alert-success"><h4><?= $msg ?></h4></div>
<?
}
?>
<form class="form-search form-inline tc_formulario" METHOD='POST' ACTION='<?= $PHP_SELF ?>' enctype='multipart/form-data'>
	<div class="titulo_tabela"><?=traduz('Importação da Tabela de Preços')?></div>
	<?
	if(in_array($login_fabrica,array(6))){
	?>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
			<div class="alert alert-warning span8">
		<center style='font-size:12px'><b><?=traduz('Compacte seus arquivos no formato ')?>.zip'. <br><br>
		<?=traduz('Os arquivos devem conter em seu nome')?> 'tab', <?=traduz("'MES', 'ANO',")?> '%' e '.txt'<br> Ex.: 'tab08200707.txt', 'tab08200712.txt', 'tab08200717.txt' e 'tab08200718.txt'  </b><br><br>
		<b><?=traduz('IMPORTANTE: o arquivo não pode ultrapassar 2MB de tamanho. Caso ultrapasse, entre em contato com o suporte Telecontrol')?></b><br><br>
		<b style='color:red'><?=traduz('O arquivo será importado de madrugada. Não importe duas vezes o mesmo arquivo!')?></b></center>
		</div>
		<div class="span2"></div>
	</div>
	<?
	}else{
	?>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
			<div class="alert alert-warning span8">
				<h4><strong><?=traduz('Layout do Arquivo')?></strong></h4>
				<br />
			<p><?=traduz('Sigla da Tabela, Referência da Peça, Preço da Peça separados por <strong>TAB</strong>')?></p>
		    <p><?=traduz('Formato')?>: txt</p>
			<strong><?=traduz('Exemplo')?>: (GAR &nbsp; &nbsp; 3.099.0510  &nbsp; &nbsp; 2.70)</strong>
			</div>
		<div class="span2"></div>
	</div>	
	<?
	}
	?>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<input type='hidden' name='btn_acao' value=''>
			<label class='control-label'><?=traduz('Anexar Arquivo')?></label>
			<div class='controls controls-row'>
				<input type='file' name='arquivo_zip' size='30'>
			</div>	
		</div>
		<div class="span2"></div>
	</div>
	<div class="row-fluid">
	<br />	
		<?
		echo "<center><button class='btn btn-primary' onclick=\"javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }\" ALT=\"Gravar formulário\" border='0' style=\"cursor:pointer;\">".traduz("Gravar")."</button></center>";
		?>
	</div>
	<br />
</form>

<br>

<? include "rodape.php"; ?>
