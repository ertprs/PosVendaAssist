<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";

$layout_menu = "gerencia";
$title = "ENVIO DE E-MAIL PARA POSTOS QUE N�O LAN�ARAM ORDEM DE SERVI�O";

include "cabecalho.php";

$sql = "SELECT	tbl_posto.email, 
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto_fabrica.credenciamento
		FROM tbl_posto
		JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		and tbl_posto_fabrica.fabrica = 24
		WHERE tbl_posto_fabrica.credenciamento='CREDENCIADO' 
		AND tbl_posto.email notnull
		AND tbl_posto.posto not in(select distinct posto from tbl_os where fabrica=24)";
$res = pg_exec($con,$sql);

if(pg_num_rows($res)>0){
//	for($x=0;pg_num_rows($res)>$x;$x++){
		for($x=0;pg_num_rows($res)>$x;$x++){
			$email = pg_result($res,$x,email);
			$razao_social = pg_result($res,$x,nome);
			$codigo_posto = pg_result($res,$x,codigo_posto);
			$remetente    = "Suporte Telecontrol <helpdesk@telecontrol.com.br>"; 
			$destinatario = "$email"; 
			$assunto      = "Acesso SUGGAR no sistema TELECONTROL"; 
			$mensagem     = "Caro posto autorizado $codigo_posto - $razao_social,<BR><BR>
						Para agilizar as informa��es de Ordem de Servi�o, pedido de Pe�a, e recebimento de m�o-de-obra, a Empresa Suggar passou a utilizar o Sistema Telecontrol.<BR>
						Notamos que at� agora o seu posto n�o fez o primeiro acesso no site www.telecontrol.com.br/assist, e nem digitou as Ordens de Servi�o no sistema on-line.<BR>
						Para se cadastrar basta acessar www.telecontrol.com.br/assist , digitar o CNPJ do seu posto na op��o <FONT COLOR='#990000'><B>PRIMEIRO ACESSO</b></FONT>, cadastre uma senha e aguarde um e-mail de confirma��o com seu login de acesso. <BR>
						Com este codigo de acesso e senha, poder� acessar o sistema Telecontrol para efetuar cadastro de suas Ordens de Servi�os, consultar vistas explodidas, pedidos de pe�as, e extratos das Ordens de Servi�os para o recebimento da m�o-de-obra.<BR>
						Qualquer d�vida ou dificuldade de acesso entre em contato pelo e-mail helpdesk@telecontrol.com.br ou pelos telefones Belo Horizonte (31) 4062-7401 ; Curitiba (41) 4063-9872 ; Florian�polis (48) 4052-8762 ; Mar�lia-SP: (14) 3413-6588; S�o Paulo (11) 4063-4230.<BR><BR>
						Suporte Telecontrol"; 
							echo "E-mail enviado para $codigo_posto - $razao_social - $email<BR>";
			$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n"; 
		/*	if ( @mail($destinatario,$assunto,$mensagem,$headers) ) {
				echo "E-mail enviado para $codigo_posto - $razao_social - $email<BR>";
			}else{
					$remetente    = "Suporte <helpdesk@telecontrol.com.br>"; 
					$destinatario = "helpdesk@telecontrol.com.br"; 
					$assunto      = "Erro ao enviar e-mail"; 
					$mensagem     = "* N�O ENVIADO E-MAIL PARA O POSTO ".$codigo_posto ." - " . $razao_social. " *"; 
					$headers="Return-Path: <helpdesk@telecontrol.com.br>\nFrom: ".$remetente."\nContent-type: text/html\n"; 
					
					@mail($destinatario,$assunto,$mensagem,$headers);
			}*/
		}
}
include "rodape.php";
?>