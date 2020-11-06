<?php
/**
 *
 * importa-numero-serie.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author Guilherme Silva
 * @version 2013.08.30
 * @version 2013.10.01 // Alteração
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	if(ENV == 'producao'){
		$origem = "/www/assist/www/rotinas/suggar/entrada/";
		$email 	= 'helpdesk@telecontrol.com.br';
		$arquivos = '/tmp/suggar/';
	}else{
		$origem = "entrada/";
		$email	= 'guilherme.silva@telecontrol.com.br';
		$arquivos = '/tmp/suggar_bkp/';
	}

    /* FTP */
	    $ftp_server = "189.3.112.20";
	    $ftp_user_name = "suggar\\telecontrol";
	    $ftp_user_pass = "Suggar123@";

	    $local_file = dirname(__FILE__).'/entrada/numero_serie.txt';
	    $server_file = 'etiquetas/numero_serie.txt';

	    $conn_id = ftp_connect($ftp_server);

		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

		ftp_pasv($conn_id, true);

		ftp_chmod($conn_id, 0777, $server_file);

		ftp_rename($conn_id, "etiquetas/numero_serie.TXT", "etiquetas/numero_serie.txt");

		ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); 

    /* Fim FTP */

	$fabrica = "24";

	/* Inicio Processo */
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/* ---- */
	$sql = "DROP TABLE if exists suggar_numero_serie;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "
			CREATE TABLE suggar_numero_serie (
				txt_serie              CHARACTER VARYING(20) ,
				txt_referencia_produto CHARACTER VARYING(20) ,
				txt_data_fabricacao         CHARACTER VARYING(20)
			); ";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "GRANT ALL on suggar_numero_serie to telecontrol;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/* Carrega arquivo */
	$arquivo = file_get_contents($origem."numero_serie.txt");

	$linha = explode("\n", $arquivo);

	foreach($linha as $key => $l){

		$serie = "";
		$data_venda = "";

		list($intervalo1, $intervalo2, $produto, $cod1, $cod2, $dia,$matriz_filial) = explode(";", $l);

		/* Data */
		$data = substr($intervalo1, 0, 4);
		$mes = substr($data, 0, 2);
		$ano = "20".substr($data, 2, 4);

		$data_venda = $ano."-".$mes."-".trim($dia)."  00:00:00";
		/* Fim Data */

		if($produto != "000000000" && strlen($produto) > 0){

			$num1 = $intervalo1;
			$num2 = $intervalo2;

			if($num2 > $num1){
				$intervalo = $num2 - $num1;
			}else{
				$intervalo = $num1 - $num2;
			}

			if($intervalo > 0){

				$cont = 1;
				$intervalo_aux = $intervalo1;

				while($cont <= ($intervalo + 1)){

					if($cond == 1){
						$serie = $intervalo_aux;
					}else{
						$serie = $intervalo_aux++;
					}
					
					$matriz_filial = preg_replace("/\D/","",$matriz_filial);

					$serie = str_pad($serie, 12, "0", STR_PAD_LEFT);

					$serie = $serie . $matriz_filial;

					if(strlen($serie) != 14) continue;

					$sql = "
						INSERT INTO suggar_numero_serie 
						(txt_serie, txt_referencia_produto, txt_data_fabricacao) 
						VALUES 
						('$serie', '$produto', '$data_venda')";
					$res = pg_query($con, $sql);

					$cont++;

				}

				// echo "<hr />";

			}

			if($intervalo == 0){

				$serie = $intervalo1;
				$serie = str_pad($serie, 12, "0", STR_PAD_LEFT);

				$serie = $serie . $matriz_filial;

				// echo "<hr />";

				$sql = "
					INSERT INTO suggar_numero_serie 
					(txt_serie, txt_referencia_produto, txt_data_fabricacao) 
					VALUES 
					('$serie', '$produto', '$data_venda')";
				$res = pg_query($con, $sql);

			}

		}

	}

	$sql = "ALTER TABLE suggar_numero_serie ADD column produto int4";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "
		UPDATE suggar_numero_serie
			SET produto = tbl_produto.produto
		FROM tbl_produto
		WHERE tbl_produto.fabrica_i = $fabrica 
		AND upper(trim(suggar_numero_serie.txt_referencia_produto)) = upper(trim(tbl_produto.referencia))";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "DROP TABLE if exists suggar_numero_serie_falha;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "ALTER TABLE suggar_numero_serie ADD COLUMN tem_serie boolean;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "UPDATE  suggar_numero_serie
				SET tem_serie = 't'
			FROM tbl_numero_serie
			WHERE tbl_numero_serie.serie = suggar_numero_serie.txt_serie
			AND   tbl_numero_serie.produto = suggar_numero_serie.produto
			AND   tbl_numero_serie.fabrica = $fabrica;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/* Número de Series Nulos */
	$sql = "
		SELECT *
		INTO TEMP suggar_numero_serie_falha
		FROM suggar_numero_serie
		WHERE suggar_numero_serie.produto IS NULL";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	/* Deleta Número de Séries com Erro */
	$sql = "DELETE FROM suggar_numero_serie WHERE suggar_numero_serie.produto IS NULL";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$msg_erro = null;
	/* Begin */
	pg_query($con, "BEGIN");

	$sql = "SELECT count(suggar_numero_serie.txt_serie) AS total_update
			FROM suggar_numero_serie
			JOIN tbl_numero_serie ON tbl_numero_serie.serie = suggar_numero_serie.txt_serie
			AND tbl_numero_serie.produto = suggar_numero_serie.produto
			AND tbl_numero_serie.fabrica = $fabrica";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$total_update = pg_fetch_result($res, 0, 'total_update');


	$sql = "
		UPDATE tbl_numero_serie
		SET
			data_fabricacao 		= txt_data_fabricacao::date,
			data_carga 		= current_timestamp
		FROM suggar_numero_serie
		WHERE tbl_numero_serie.serie = suggar_numero_serie.txt_serie
		AND tbl_numero_serie.produto = suggar_numero_serie.produto
		AND tbl_numero_serie.fabrica = $fabrica";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "
		INSERT INTO tbl_numero_serie (
			fabrica           ,
			serie             ,
			referencia_produto,
			data_fabricacao        ,
			produto
		)
		SELECT DISTINCT
			$fabrica              ,
			txt_serie             ,
			txt_referencia_produto,
			txt_data_fabricacao::date,
			produto
		FROM suggar_numero_serie
		WHERE tem_serie is not true;";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "SELECT count(txt_serie) AS total_insert
		FROM suggar_numero_serie
		WHERE tem_serie is not true";
	$res = pg_query($con, $sql);
	$msg_erro .= pg_errormessage($con);
	$total_insert = pg_fetch_result($res, 0, 'total_insert');
	
	if(strlen($msg_erro) > 0){

		pg_query($con, "ROLLBACK");

		$mensagem = $msg_erro;
		$dest = "helpdesk@telecontrol.com.br";

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML();
		$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mail->Subject = Date('d/m/Y')." - Erro na importação de Número de Série";
	    $mail->Body = $mensagem;
	    $mail->AddAddress($dest);
	    $mail->Send();

	}else{

		$mensagem = "Total de Números de Série Atualizados: {$total_update} <br> Total de Números de Série inseridos: {$total_insert}";
		$dest  = "claudio.ramos@suggar.com.br";
		$dest2 = "helpdesk@telecontrol.com.br";

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML();
		$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mail->Subject = Date('d/m/Y')." - Números de Série importados com sucesso";
	    $mail->Body = $mensagem;
	    $mail->AddAddress($dest);
	    $mail->AddAddress($dest2);
	    $mail->Send();

		pg_query($con, "COMMIT");
	}

	$data_sistema = date("Y_m_d_h_i_s");

	system ("mv ".$origem."numero_serie.txt ".$arquivos."numero_serie_".$data_sistema.".txt && rm -f ".$origem."numero_serie.txt");

	system ("zip -oqm ".$arquivos."numero_serie_$data_sistema.zip ".$arquivos."numero_serie_".$data_sistema.".txt");

	/* Fim Processo */
	$phpCron->termino();

}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - suggar - Importa faturamento (importa-faturamento.php)", $msg);

}
