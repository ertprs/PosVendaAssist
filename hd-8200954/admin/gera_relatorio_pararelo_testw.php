<script language='javascript'>

	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http_forn = new Array();

	function verificarExecucao() {
		var curDateTime = new Date();
		var tempoexec   = curDateTime.getTime()
		url = "<?=$PHP_SELF?>?verificar_execucao=verificar&tempo="+tempoexec;
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4) {
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) {
					var response = http_forn[curDateTime].responseText;
					if (response == 'ok'){
						window.location = '<?=$PHP_SELF?>';
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
</script>

<?

if ($gera_automatico != 'automatico'){

	$parametros = "";
	foreach ($_POST as $key => $value){
		$parametros .= $key."=".$value."&";
	}
	foreach ($_GET as $key => $value){
		$parametros .= $key."=".$value."&";
	}
	
	$sql = "SELECT relatorio_agendamento 
			FROM tbl_relatorio_agendamento
			WHERE admin   = $login_admin
			AND programa  = '$PHP_SELF'
			AND data::DATE = current_date
			AND executado IS NULL";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$msg_erro = "Por favor, aguarde, o relatório está sendo processado e dentro de instantes você receberá a confirmação por email.";

		#Script para ficar verificando se o relatorio ja executou (em casos que o relatorio demora muito para executar)
		?>
			<script language='javascript'>
				validaExecucao = window.setInterval('verificarExecucao()', 15000);
			</script>
		<?
	}else{
		$sql = "INSERT INTO tbl_relatorio_agendamento (admin,fabrica,programa,parametros,titulo,agendado) VALUES ($login_admin,$login_fabrica,'$PHP_SELF','$parametros','$title','f')";
		$res = pg_exec($con,$sql);

		$sql = "SELECT CURRVAL ('seq_relatorio_agendamento')";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		echo $relatorio_agendamento = pg_result($res,0,0);

		echo "<p class='sucesso'>O relatório está sendo processado neste momento. Aguarde alguns instantes.</p>";

		#Script para ficar verificando se o relatorio ja executou (em casos que o relatorio demora muito para executar)
		?>
			<script language='javascript'>
				 validaExecucao = window.setInterval('verificarExecucao()', 15000);
			</script>
		<?
		flush();
		$ret = "";
		system("/www/cgi-bin/relatorio-execucao.pl $relatorio_agendamento",$ret);
		echo "chamou";
		echo $ret;
		die;
		if ($ret <> "0"){
			echo $ret.$relatorio_agendamento;
			$msg_erro .= "Ocorreu um erro inesperado durante o processamento. Tente novamente<br>";
			# Seta como executado
			$sql = "UPDATE tbl_relatorio_agendamento SET executado = CURRENT_TIMESTAMP
					WHERE relatorio_agendamento = $relatorio_agendamento";
			$res = pg_exec($con,$sql);
		}
	}
	$btn_acao = "";
}

?>