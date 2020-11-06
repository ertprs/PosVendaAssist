<?php 

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';
include "../class/log/log.class.php";

$os 			= $_GET['os'];
$callcenter 	= $_GET['callcenter'];
$login_admin 	= $_GET['login_admin'];

if(isset($_POST['reabrir_os']) && $_POST['reabrir_os'] == "ok"){

	$ip_devel = $_SERVER['REMOTE_ADDR'];
	$log = new Log();

	$os 			= $_POST['os'];
	$motivo 		= utf8_decode($_POST['motivo']);
	$callcenter 	= $_POST['callcenter'];
	$login_admin 	= $_POST['login_admin'];

	$sql_reabrir_os = "UPDATE tbl_os SET data_fechamento = null, finalizada = null,excluida = false , data_conserto = null WHERE os = {$os}";
	$res_reabrir_os = pg_query($con, $sql_reabrir_os);

	$erro .= pg_last_error($con);

	$motivo_email = $motivo;
	$motivo = "<strong>Reaberta a OS: {$os}</strong> <br /> <strong>Motivo:</strong> ".$motivo;
	$motivo = quotemeta($motivo);

	$sql_interagir_chamado = "
		INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, status_item, enviar_email, atendimento_telefone) VALUES 
										($callcenter, '$motivo', $login_admin, 'Aberto', 'f', 'f');
	";
	$res_interagit_chamado = pg_query($con, $sql_interagir_chamado);

	$erro .= pg_last_error($con);

	$sql_posto = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res_posto = pg_query($con, $sql_posto);

	$erro .= pg_last_error($con);

	$posto = pg_fetch_result($res_posto, 0, "posto");
	$sua_os = pg_fetch_result($res_posto, 0, "sua_os");

	$sql_comunicado = "INSERT INTO tbl_comunicado (
				fabrica,
				posto,
				obrigatorio_site,
				tipo,
				ativo,
				descricao,
				mensagem
			) VALUES (
				{$login_fabrica},
				{$posto},
				true,
				'Com. Unico Posto',
				true,
				'A OS {$os} foi Reaberta pela Fabrica',
				'{$motivo}'
			)";
	$res_comunicado = pg_query($con, $sql_comunicado);

	$erro .= pg_last_error($con);

	if(empty($erro)){

		$sql_contato_posto = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
		$res_contato_posto = pg_query($con, $sql_contato_posto);

		if(pg_num_rows($res_contato_posto) > 0){

			$email_posto = pg_fetch_result($res_contato_posto, 0, 'contato_email');

			$log->adicionaLog("Informamos que a OS $os foi reaberta pela fabrica");
			$log->adicionaLog("Motivo: $motivo_email");

			$log->adicionaTituloEmail("Reabertura da OS $os");

			if($ip_devel == "179.96.146.28"){
				$log->adicionaEmail("guilherme.silva@telecontrol.com.br");
			}else{
				$log->adicionaEmail($email_posto);
			}

			$log->enviaEmails();

		}

	}

	echo $erro;

	exit;

}

?>

<!DOCTYPE>
<html>
	<head>
		<title>Tela de Motivo para Reabrir a OS</title>
		<script src="../js/jquery-1.7.2.js" ></script>

		<style type="text/css">
			h1{
				font: 20px arial; 
				margin: 20px;
			}
		</style>

		<script>

			function enviarMotivoReabrirOS(os){

				var motivo = $('#motivo_reabrir_os').val();

				if(motivo.length == 0){
					alert("Por favor coloque o Motivo para reabrir a OS");
					$('#motivo_reabrir_os').focus();
					return;
				}

				$.ajax({
					url : "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: {
						reabrir_os 	: "ok",
						os 			: os,
						motivo 		: motivo,
						callcenter 	: <?=$callcenter?>,
						login_admin : <?=$login_admin?>,
					},
					complete: function(data){
						data = data.responseText;
						if(data.length == 0){
							$('#motivo_reabrir_os').attr('value', '');
							alert("A OS <?=$os?> foi reaberta com Sucesso!");
							parent.Shadowbox.close();
						}
					}
				});

			}

		</script>

	</head>
	<body>
		<div style="text-align: center;"> 
			<h1 align="center">Insira o Motivo para Reabrir a OS</h1> 
			<textarea id="motivo_reabrir_os" style="width: 90%;" rows="7"></textarea> 
			<br /> 
			<button type="button" onClick="enviarMotivoReabrirOS('<?php echo $os; ?>')" style="padding-left: 10px; padding-right: 10px;">Reabrir OS</button> 
		</div>
	</body>
</html>
