<?php
if ( in_array($login_fabrica, [173]) && $_REQUEST['ajax'] == 'sim' && $_REQUEST['action'] == 'novo_numero_serie_update') {
	$sqlUpdateNvSerie = "UPDATE tbl_os_extra SET serie_justificativa = {$_REQUEST['numero']} WHERE os = {$_REQUEST['os']} ";
	$resUpdateNvSerie = pg_query($con, $sqlUpdateNvSerie);
	return "ok";
}
if ( in_array($_REQUEST['fabrica'], [173]) && $_REQUEST['ajax'] == 'sim' && $_REQUEST['action'] == 'consultar_serie') {
	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include "autentica_usuario.php";

	$sqlConsulta = "SELECT serie, serie_justificativa FROM tbl_os JOIN tbl_os_extra USING (os) WHERE os = {$_REQUEST['os']} ";
	$resConsulta = pg_query($con, $sqlConsulta);
	$serie = pg_fetch_result($resConsulta, 0, 'serie');
	$serie_justificativa = pg_fetch_result($resConsulta, 0, 'serie_justificativa');	
	if ($serie_justificativa != "" && $serie_justificativa != null) {
		if ($serie_justificativa != $_REQUEST['numero']) {
			exit('false');
		}
	} else if ($serie != "" && $serie != null) {
		if ($serie != $_REQUEST['numero']) {
			exit('false');
		}
	} else {
		exit('false');
	}
	$sql = "UPDATE tbl_os SET status_checkpoint = 9, finalizada = now(), data_fechamento = now() WHERE fabrica = {$_REQUEST['fabrica']} and os = {$_REQUEST['os']}";
	$res = pg_query($con,$sql);
	exit('true');
}
?>
<script type="text/javascript">
	$(function() {
		$("[name='nv_numero_serie']").on('click', function() {
			$(this).removeAttr('readonly');
		});	
		$("[name='nv_numero_serie']").on('blur', function() {
			var id = <?php echo $_REQUEST['os'] ?>;
			$("[name='nv_numero_serie']").attr('style', 'background:red;opacity: 0.5;');
			$.ajax({
	            url: window.location.href,
	            type: "POST",
	            data: { ajax: 'sim', action: 'novo_numero_serie_update', os: id, numero: $(this).val() }
	        }).done(function(data){
	            alert('Novo Número de Série editado com Sucesso!');
	            $("[name='nv_numero_serie']").removeAttr('style');
	            $("[name='nv_numero_serie']").attr('style', 'background:green;opacity: 0.5;');
	        });
		});
		$("[name='cod_barra_serie']").on('blur', function() {
			alert('saiu');
		});
	});
</script>
