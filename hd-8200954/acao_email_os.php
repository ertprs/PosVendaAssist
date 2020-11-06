<?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/* ini_set("display_errors", 1);
error_reporting(E_ALL); */

$validaUsuarioLogadoEmail = $_GET['validaUsuarioLogadoEmail'];

include __DIR__.'/autentica_usuario.php';

if (isset($_POST["justificativa"])) {
	$justificativa = $_POST["justificativa"];
	$os            = $_POST["os"];
	$url           = $_POST["url"];

	$sql = "SELECT tbl_os.fabrica
			FROM tbl_os WHERE os = {$os}";
	$res = pg_query($con, $sql);

	$fabrica     = pg_fetch_result($res, 0, 'fabrica');
	$email_posto = pg_fetch_result($res, 0, 'contato_email');

	$sql = "UPDATE arquivo_acao3_dados SET data_resposta = current_timestamp, justificativa = '$justificativa' WHERE os = $os";
	pg_query($con, $sql);

	header('Location: menu_inicial.php');
	exit;
}

$acao     = $_GET['acao'];
$os       = $_GET['os'];
$url_base = $_GET['url_base'];
?>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
.txt_justificativa {border: 1px solid #3b4274;width: 75%;text-align: left;}

table {
	width: 60%;
	height: 15%;
}

.txt {
	font-size: 25px;
	font-weight: lighter;
	font-family: Calibri;
	
}

button {
	width: 140px;
	height: 50px;
	cursor: pointer;
	font-size: 13px;
}
</style>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
    <script type="text/javascript" src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
    <script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
    <script type='text/javascript' src='inc_soMAYSsemAcento.js'></script>
    <script src="js/cabecalho.js"></script>
<script>
	$(function(){
		$("#btn_enviar").click(function(){
			if ($(".txt_justificativa").val() != "") {
				$(".form").submit();
			} else {
				alert("Por favor, informe a justificativa");
			}
		});
	});
</script>
<?php
if ($acao == 'fechar') {
	?>
	<script language="javascript">
		var curDateTime = new Date();

		function fechar_os() {
			$.ajax({
		        type:"GET",
		        url: 'os_consulta_lite.php',
		        data: {
		            fechar: <?= $os ?>,
		            acao_email_os : "sim"
		        },
		    }).done(function(data) {
		    	window.location.href = "menu_inicial.php";
		    });
		}

		$.ajax({
	        type:"GET",
	        url: 'os_consulta_lite.php',
	        data: {
	            consertado: <?= $os ?>,
	            acao_email_os : "sim"
	        },
	    }).done(function(data) {
	    	fechar_os();
	    });

	</script>
	<?php
	exit;
} else if ($acao == 'manter') { ?>

	<img src="https://www.telecontrol.com.br/images/logo.png" id="logo" alt="Logo Telecontrol" title="Site institucional da Telecontrol" width="220">
	<br /><br />
	<center>
		<span class="txt">
			Por favor, informe no campo abaixo a justificativa para manter a Ordem de Serviço <?= $os ?> aberta
		</span>
	</center>
	<br /><br />
		<table align="center" border="0" cellspacing="0" cellpadding="2">
			<form class="form conteudo" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
				<input type="hidden" name="os"   value="<?= $os ?>" />
				<input type="hidden" name="url"  value="<?= $_SERVER['REQUEST_URI'] ?>" />
				<tr class="table_line">
					<td>
						<center>		
							<select name="justificativa">
								<option value="">Selecionar</option>
								<option value="Aguardando consumidor">Aguardando consumidor</option>
								<option value="Problemas ao contactar consumidor">Problemas ao contactar consumidor</option>
								<option value="Recusa do consumidor de retirar o produto reparado">Recusa do consumidor de retirar o produto reparado</option>
								<option value="Aguardando peça">Aguardando peça</option>
								<option value="Peça recebida avariada">Peça recebida avariada</option>
								<option value="Reparo não pode ser executado">Reparo não pode ser executado</option>
							</select>
						</center>
					</td>
				</tr>
				<tr class="table_line">
					<td>
						<center>					
							<p>
								<button id="btn_enviar" type="button">
									Enviar Justificativa
								</button>
							</p>
						</center>
					</td>
				</tr>
			</form>
		</table>
<?php 
} else {
	header("Location: menu_inicial.php");
	exit;
}
?>
