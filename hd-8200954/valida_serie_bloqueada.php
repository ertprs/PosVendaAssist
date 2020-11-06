<?php

/*
HD 2403711
Algumas vari�veis s�o definidas fora deste arquivo, antes de ser feito o include:
  $os
  $xproduto_serie
*/

$sql_dados_posto = "SELECT tbl_posto_fabrica.nome_fantasia,
                      tbl_posto_fabrica.codigo_posto
              FROM tbl_posto_fabrica
              WHERE tbl_posto_fabrica.fabrica = $login_fabrica
              AND tbl_posto_fabrica.posto = $login_posto";
$res_dados_posto = pg_query($con, $sql_dados_posto);

$cod_posto = pg_fetch_result($res_dados_posto, 0, 'codigo_posto');
$nome_posto = pg_fetch_result($res_dados_posto, 0, 'nome_fantasia');

if(strlen(trim($xproduto_serie)) > 0) {
	$sql = "SELECT tbl_serie_controle.serie
			  FROM tbl_serie_controle
			  WHERE tbl_serie_controle.serie = $xproduto_serie
			  /*AND tbl_serie_controle.produto = $produto*/
			  AND tbl_serie_controle.fabrica = $login_fabrica";
	$res = pg_exec($con, $sql);
	$produto_nserie = str_replace("'", "", $xproduto_serie);
	if(pg_num_rows($res) > 0){

	  if($login_fabrica == 11){
		$msg_erro .= "O Produto n�o pode ser atendido em garantia.<br />";
		$msg_erro .= "Se houver d�vidas, favor entrar em contato com o Inspetor respons�vel pela sua regi�o.";
	  }else{
		if($login_fabrica == 3){
		  $msg_erro .= "Serial: $produto_nserie Bloqueado para Garantia.<br />";
		  $msg_erro .= "Favor entrar em contato com suporte via helpdesk.<br/><br/>";
		}else{
		  $msg_erro .= "Serial Bloqueado para Garantia.<br />";
		  $msg_erro .= "Favor entrar em contato com suporte via helpdesk.";
		}
	  }

	  $titulo = "OS com n�mero de s�rie bloqueado";

	  if($login_fabrica == 11){
		$to = "dat@lenoxx.com.br";



		$message = "
		  <!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
		  \"http://www.w3.org/TR/html4/loose.dtd\">
		  <html>
		  <head>
		  <title>$titulo</title>
		  </head>
		  <body style='font-family:Lucida Sans Unicode', 'Lucida Grande', sans-serif>
		  <table style='border-collapse: collapse;'>
			<tr>
			  <td style='font-size: 10pt;' align=left width=740>

				O Sistema Telecontrol informa que o Posto Autorizado
				Tentou abrir uma Ordem de Servi�o com N�mero de S�rie bloqueado <br />
				C�digo Posto: ".$cod_posto."<br />
				Posto: ".$nome_posto."<br />
				N�mero de S�rie: ".$produto_nserie."<br/><br/>
				Mensagem autom�tica: Por favor, n�o responder este e-mail.
			  </td>
			</tr>
		  </table>
		  </body>
		  </html>";

		$headers  = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";

		$headers .= "To: $to" . "\r\n";
		$headers .= "From: Suporte Telecontrol <helpdesk@telecontrol.com.br>";

		if ($to !== false)
		  mail($to, utf8_encode($titulo), utf8_encode($message), $headers);

	  }
	}
}
?>
