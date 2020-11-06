<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$tipo_chamado = $_GET["tipo_chamado"];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv='pragma' content='no-cache'>
		<title></title>
		<script type="text/javascript" src="../js/jquery-1.4.2.js"></script>
		<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<style type="text/css">
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}

			.lp_tabela td{
				cursor: default; !important;
			}
		</style>
		<script type='text/javascript'>
			//fun��o para fechar a janela caso a telca ESC seja pressionada!
			$(window).keypress(function(e) {
				if(e.keyCode == 27) {
					 window.parent.Shadowbox.close();
				}
			});
		</script>
	</head>

	<body>
		<div class="lp_header">
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='../css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>
		</div>

		<div class='lp_pesquisando_por'>
			<?php
				switch ($tipo_chamado) {
					case '1':
						$text = '<h3>Altera��o de Dados</h3>
								Chamados de Altera��o de dados necessitam da aprova��o do usu�rio "Supervisor Help-Desk".<br/>
								Esta aprova��o � liberada na "Janela de aprova��o de Chamados", conforme calend�rio Telecontrol.
						';
					break;
					case '8':
						$text = "<h3>Altera��o de Raz�o Social</h3>
								Chamados de Altera��o de Raz�o Social podem ser abertos a qualquer momento<br>
								O Suporte Telecontrol iniciar� logo em seguida o processo de altera��o.
						";
					break;
					case '5':
						$text = "<h3>Chamados de Erro</h3>
								Chamados de Erro em programa podem ser abertos a qualquer momento<br>
								por qualquer usu�rio e a equipe Telecontrol tratar� como prioridade.
						";
					break;
					case '4':
						$text = '<h3>Novo Programa ou Processo</h3>
								Chamados de Novo programa ou processo necessitam de aprova��o do usu�rio "Supervisor Help-Desk".<br/>
								Esta aprova��o � liberada na "Janela de aprova��o de Chamados", conforme calend�rio Telecontrol.
						';
					break;
				}
				echo $text;
			?>
		</div>
	</body>
</html>
