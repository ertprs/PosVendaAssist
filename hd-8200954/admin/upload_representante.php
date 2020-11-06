<?
/*include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
require_once 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "Upload de Representante";
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Cadastro de Representantes";
$layout_menu = "cadastro";
$admin_privilegios="cadastros";

if(isset($_POST['btnacao'])){

	$conteudo = file($_FILES['arquivo']['tmp_name']);

	$sql = "UPDATE tbl_representante set ativo = false where fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);

	if(empty($conteudo)){
		$erro .= "Por favor forneça o arquivo a ser importado. ";
	}else{

		foreach ($conteudo as $linha){
			$conteudo = explode(';', $linha);

			$codigo 	= trim($conteudo[0]);
			$nome		= trim($conteudo[1]);
			$contato	= trim($conteudo[2]);
			$codigo = substr($codigo, 0, 20);
			$nome   = substr($nome, 0, 50);
			$contato   = substr($contato, 0, 30);

			$sql_select = "SELECT representante FROM tbl_representante WHERE codigo = '$codigo' and fabrica = $login_fabrica";
			$res_select = pg_query($con, $sql_select);
			if(pg_num_rows($res_select) == 0){
				$sql = "INSERT INTO tbl_representante (codigo, nome, contato, fabrica) VALUES ('$codigo', '$nome', '$contato', $login_fabrica)";
			}else{
				$sql = "UPDATE tbl_representante SET nome = '$nome', contato = '$contato',ativo = true  where codigo = '$codigo' ";
			}
			$res = pg_query($con, $sql);

			if(strlen(trim(pg_last_error($con)))>0){
				$erro .= "Falha ao inserir o representante - $nome <br> ". pg_last_error($con);
			}
		}

		if(strlen(trim($erro))==0){
			$ok .= "Upload de Representante realizado com sucesso. <Br>";


		}
	}

}

	$data 		= date("d-m-Y");
	$fileName 	= "relatorio_representante_importados_{$data}.csv";
	$file 		= fopen("/tmp/{$fileName}", "w");
	$head 		= "Código;Representações;Representante;CNPJ;IE;Endereço;Bairro;Cidade;Estado;Cep;Fone;Fax;\r\n";
	fwrite($file, $head);

	$sql_representante = "SELECT representante, codigo, nome, cnpj, ie, endereco, bairro, cidade, estado, cep, fone, fax, contato
		from tbl_representante
		where fabrica = $login_fabrica
	   and ativo	";

	$res_representante = pg_query($con, $sql_representante);
	for($i=0; $i<pg_num_rows($res_representante); $i++){
		$representante 			= pg_fetch_result($res_representante, $i, 'representante');
		$codigo 				= pg_fetch_result($res_representante, $i, 'codigo');
		$nome					= pg_fetch_result($res_representante, $i, 'nome');
		$cnpj					= pg_fetch_result($res_representante, $i, 'cnpj');
		$ie 					= pg_fetch_result($res_representante, $i, 'ie');
		$endereco 				= pg_fetch_result($res_representante, $i, 'endereco');
		$bairro 				= pg_fetch_result($res_representante, $i, 'bairro');
		$cidade 				= pg_fetch_result($res_representante, $i, 'cidade');
		$estado 				= pg_fetch_result($res_representante, $i, 'estado');
		$cep 					= pg_fetch_result($res_representante, $i, 'cep');
		$fone 					= pg_fetch_result($res_representante, $i, 'fone');
		$fax 					= pg_fetch_result($res_representante, $i, 'fax');
		$contato 				= pg_fetch_result($res_representante, $i, 'contato');

		$body .= "{$codigo};{$nome};{$contato};{$cnpj};{$ie};{$endereco};{$bairro};{$cidade};{$estado};{$cep};{$fone};{$fax}";
		$body .= "\r\n";
	}
	fwrite($file, $body);
	fclose($file);
	if (file_exists("/tmp/{$fileName}")) {
		system("mv /tmp/{$fileName} xls/{$fileName}");
	}

	$link_download = "<a href='xls/{$fileName}'>Gerar Excel</a>";

include 'cabecalho_new.php';

$plugins = array(
   	"datepicker",
   	"shadowbox",
   	"maskedinput",
   	"alphanumeric",
   	"ajaxform",
	"price_format"
);

include("plugin_loader.php");

?>
<!--
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
-->


<style type="text/css">
	.txt a{
		color:#ffffff;
	}
	.layout{
		font-size: 10px;
	}
	.ok {
		color:green;
	}
	.erro {
		color:red;
	}
</style>

<?php
if(strlen($msg_erro) > 0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<? } ?>

<?php
if(isset($_GET['Message'])){
?>
<div class="alert alert-success">
	<h4><? echo $_GET['Message']; ?></h4>
</div>
<?php
}
?>
<form name="frm_marca" method="post" action="<? $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data'>

	<input type="hidden" name="marca" value="<? echo $marca ?>">

	<div class='titulo_tabela '>Cadastro</div>
	<br/>
		<?php if(strlen(trim($ok))>0){?>
		<div class='row-fluid'>
			<div class='span5'></div>
			<div class='span4'>
				<?php echo "<div class='ok'>$ok</div>";?>
			</div>
		</div>
		<? } ?>

		<?php if(strlen(trim($erro))>0){?>
		<div class='row-fluid'>
			<div class='span5'></div>
			<div class='span5'>
				<?php echo "<div class='erro'>$erro</div>";	?>
			</div>
		</div>
		<? } ?>

		<div class='row-fluid'>
			<div class='span5'></div>
			<div class='span5'>
				<div class='control-group'>
					<label class='control-label' for='qtde_de_fotos'>Selecione o Arquivo</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="file" name="arquivo" value="">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class="container">
	</div>
	<p><br/>
		<input type='submit' class="btn btn-success" name="btnacao" value='Gravar'  ALT="Gravar formulário" border='0'>
		<input type='reset' class="btn" value='Limpar' ALT="Limpar campos" border='0'>
	</p><br/>
	<div class='row-fluid'>
			<div class='span5 layout'>
					Layout do Arquivo: código; representação; representante;<br>
					Separado por (;)
			</div>
		</div>
	<?php if(!empty($link_download)):?>
	<div class='row-fluid'>
		<div class='span5'></div>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?php echo $link_download ?></span>
		</div>
		<div class='span2'></div>
	</div>
<?php endif; ?>

</form>

</body>
</html>

<?php include "rodape.php"; ?>
