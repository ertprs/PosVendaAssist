<?


if ($gera_automatico != 'automatico'){

	$parametros = "";
	foreach ($_POST as $key => $value){
		$parametros .= $key."=".$value."&";
	}
	$sql = "SELECT relatorio_agendamento 
			FROM tbl_relatorio_agendamento
			WHERE admin   = $login_admin
			AND programa  = '$PHP_SELF'
			AND executado IS NULL";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$msg_erro = "Por favor, aguarde, o relatório está sendo processado e dentro de instantes você receberá a confirmação por email.";
	}else{
		$sql = "INSERT INTO tbl_relatorio_agendamento (admin,fabrica,programa,parametros,titulo,agendado) VALUES ($login_admin,10,'$PHP_SELF','$parametros','$title','f')";
		$res = pg_exec($con,$sql);

		$sql = "SELECT CURRVAL ('seq_relatorio_agendamento')";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$relatorio_agendamento = pg_result($res,0,0);

		echo "<p class='sucesso'>O relatório está sendo processado neste momento. Aguarde alguns instantes.</p>";
		flush();
		$ret = "";
		system("/www/cgi-bin/relatorio-execucao.pl $relatorio_agendamento",$ret);
		if ($ret <> "0"){
			$msg_erro .= "<br>Ocorreu um erro inesperado duante o processamento. Tente novamente<br>";
		}
	}
	$btn_acao = "";
}

?>