<?php
/*
* Rotinas créditos bloqueados.
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$login_fabrica = 1;
$arquivo = "creditos_bloqueados.txt";
$origem = "/home/blackedecker/black-telecontrol";
//$origem = "/home/william/public_html";
	if(file_exists("$origem/{$arquivo}")){
		$conteudo = file("$origem/{$arquivo}");

		foreach($conteudo as $linha){
			$dados = explode(";", $linha);

			$sql_pega_posto = "select tbl_posto.posto from tbl_posto 
				inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
				where tbl_posto_fabrica.codigo_posto = '$dados[0]'
				and fabrica = $login_fabrica ";
			$res_pega_posto = pg_query($con, $sql_pega_posto);
			if(pg_num_rows($res_pega_posto)>0){
				$posto = pg_fetch_result($res_pega_posto, 0, "posto");
			}

			$sql_verifica = "SELECT posto, fabrica, desbloqueio, admin from tbl_posto_bloqueio WHERE posto = $posto and fabrica = $login_fabrica AND pedido_faturado IS TRUE order by data_input DESC";
			$res_verifica = pg_query($con, $sql_verifica);
			$desbloqueio = "";
			$admin = "";

			if(pg_num_rows($res_verifica)>0){
				$desbloqueio = pg_fetch_result($res_verifica, 0, desbloqueio);
				$admin = pg_fetch_result($res_verifica, 0, admin);
			}
			if(strtolower(trim($dados[1])) == "sim" and ($desbloqueio == "t" or empty($desbloqueio) or ($desbloqueio == "f" and strlen($admin) > 0) )){
				$sql_bloqueia = "INSERT INTO tbl_posto_bloqueio (fabrica, posto, observacao, pedido_faturado) VALUES ($login_fabrica, $posto, 'credito bloqueado', true) ";
				$res_bloqueia = pg_query($con, $sql_bloqueia);
			}elseif(strtolower(trim($dados[1])) == "nao" and ($desbloqueio == "f" or $desbloqueio == null or ($desbloqueio == "t" and strlen($admin) > 0 ) )){
				$sql_bloqueia = "INSERT INTO tbl_posto_bloqueio (fabrica, posto, observacao, pedido_faturado, desbloqueio) VALUES ($login_fabrica, $posto, 'Desbloqueio Automático', true, true) ";
				$res_bloqueia = pg_query($con, $sql_bloqueia);
			}

			if(strlen(trim(pg_last_error($con))) > 0 ){
				$erro .= "Erro na importação de postos bloqueados - \n\n".pg_last_error($con);
			}
		}

		if(!empty($erro)){
			$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
				'Reply-To: webmaster@example.com' . "\r\n";	    
			$para = "helpdesk@telecontrol.com.br";	

			$assunto   = "ERRO de Importação no bloqueio Posto - Blackedecker";
			$mensagem  = "Favor verificar URGENTE a falha de importação!!! \n ".$erro;
			mail($para, $assunto, $mensagem, $headers);
		}
		$data= date('Y-m-d');
		system("mv $origem/$arquivo /tmp/blackedecker/$arquivo-$data.txt");
	}
?>
