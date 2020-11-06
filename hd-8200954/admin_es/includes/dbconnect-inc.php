<?php
/* 	Esta include conecta um banco de dados conforme parametros
	enviados
	Banco de Dados:	$dbbanco
	Nome do Banco:  $dbnome	
	Porta:		$dbport
	Usuario:	$dbusuario
	Senha:		$dbsenha
*/

	global $con;

	if ($dbport == 0 OR $dbport == NULL) {
		$dbport 	= 5432;
	}

	if (strlen ($dbbanco) == 0) {
		$dbbanco 	= "postgres";
		$dbport         = 5432;
	}
	#-------------------- PostgreSQL ----------------
	if (strlen ($dbhost) == 0) $dbhost = "200.212.63.68";
	
	if ($REMOTE_ADDR == "201.0.9.216") {
	//		echo "<br><br><br>";
	//		echo "<center><h1><strong><img src='/assist/logos/telecontrol.gif'><br><br>Estamos trabalhando para a melhoria do sistema.<br>Por favor, aguarde que em instantes o sistema voltará a sua normalidade.<br><br>Atenciosamente<br>Equipe Telecontrol.</strong></h1></center>";
	}
	
	if ($dbbanco == "postgres") {
		$parametros = "host=$dbhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha";
		//echo $parametros;
		
		$erro_conexao = true ;
		for ($i == 0 ; $i < 10 ; $i++) {
			if ($con = pg_connect($parametros)) {
				$erro_conexao = false ;
				break ;
			}
			sleep (5);
		}


		if ($erro_conexao == true) {	
			if (1 == 2) {
				$subject    = "Sistema temporariamente em manutenção";
				
				$mensagem   = "Sistema entrou em manutenção em: " .date('d/m/Y H:i:s') ."<br>";
				$mensagem  .= "Login-Posto  :". $HTTP_COOKIE_VARS['cook_login_posto'] ."<br>";
				$mensagem  .= "Login-Fábrica:". $HTTP_COOKIE_VARS['cook_fabrica']     ."<br>";
				$mensagem  .= "Página       :   $PHP_SELF                               <br>";
				
				$cabecalho  = "MIME-Version: 1.0\n";
				$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
				$cabecalho .= "From: Serafim < suporte@telecontrol.com.br >\n";
				$cabecalho .= "To: Suporte < suporte@telecontrol.com.br >\n";
				$cabecalho .= "Return-Path: < suporte@telecontrol.com.br >\n";
				$cabecalho .= "X-Priority: 1\n";
				$cabecalho .= "X-MSMail-Priority: High\n";
				$cabecalho .= "X-Mailer: PHP/" . phpversion();
				
				#mail ("" , "$subject" , "$mensagem" , "$cabecalho");
			}
			
			
			if (1 == 1) {
				echo "<meta http-equiv='refresh' content='5'> ";
				echo "<br><br><br>";
				echo "<center><h2><strong><img src='/assist/logos/telecontrol.gif'>
				<br><br>
				Sistema temporalmente en mantenimiento.
				<br>
				Por favor, espera que en instantes se volverá el sistema a su normalidad.
				<br><br>
				Atentamente,
				<br>
				Equipe Telecontrol.
				</strong></h1></center>";
				
				exit;
			}
			
			if (1 == 2) {
				echo "<meta http-equiv=\"refresh\" content=\"10\"> ";
				echo "<br><br><br>";
				echo "<center><h1><strong><img src='/assist/logos/telecontrol.gif'>
				<br><br>
				Em virtude de problemas físicos de nosso servidor de banco de dados, estaremos procedendo a troca do equipamento para que a performance do sistema volte a sua normalidade.
				<br>
				Não é um procedimento normal efetuarmos mudanças no horário comercial, mas em virtude da urgência isto se faz necessário.
				<br>
				O sistema irá retornar dentro de uma hora e meia.
				<br><br>
				Atenciosamente
				<br>
				Equipe Telecontrol.
				</strong></h1></center>";
				exit;
			}
		}
	}
	
	$usuario = $PHP_AUTH_USER;
?>
