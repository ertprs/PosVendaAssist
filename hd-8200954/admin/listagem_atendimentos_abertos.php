<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

$atendente = $_GET['atendente'];
$situacao  = $_GET['situacao']; 
$login	   = $_GET['login'];

if ($login_fabrica == 189) {
        $sqlTempo = 'minutes';
        $sqlPrazo = 'horas';
        $divisor = '60';
} else {
        $sqlTempo = 'hours';
        $sqlPrazo = 'dias';
        $divisor = '24';
}

switch($situacao){
	case "em_atraso":
		$cond = " AND tbl_hd_chamado.data_providencia < CURRENT_TIMESTAMP ";
	break;
	case "no_prazo":
		$cond = " AND tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP < tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.5 ";
	break;
	case "prazo_50":
		$cond = " AND tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP >= tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.5 AND CURRENT_TIMESTAMP < tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.2 ";
	break;
	case "prazo_20":
		$cond = " AND tbl_hd_chamado.data_providencia > CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP >= tbl_hd_chamado.data_providencia - INTERVAL '1 {$sqlTempo}' * tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} * {$divisor} * 0.2 ";
	break;
}

if(!empty($atendente)){
	$cond .= " AND tbl_hd_chamado.atendente = {$atendente} ";
}

$sql = "SELECT  tbl_hd_chamado.hd_chamado,
		to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_atendimento,
		to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY HH24:MM') AS data_retorno
	FROM tbl_hd_chamado
	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
        JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica} AND tbl_hd_motivo_ligacao.prazo_{$sqlPrazo} IS NOT NULL
	WHERE fabrica_responsavel = {$login_fabrica}
	AND tbl_hd_chamado.status NOT IN('Cancelado','Resolvido')
	{$cond}
	ORDER BY tbl_hd_chamado.data_providencia";
$resSubmit = pg_query($con,$sql);
?>

<!DOCTYPE html />
<html>
        <head>
                <meta http-equiv=pragma content=no-cache>
                <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
        	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
        	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
        	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
                <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
                <link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
                <link href='plugins/select2/select2.css' type='text/css' rel='stylesheet' />

                <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                <script src="bootstrap/js/bootstrap.js"></script>
                <script src="plugins/dataTable.js"></script>

<script>
	$(function() {
        	$.dataTableLoad("#tabela");
        });
</script>
	</head>
<?php
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);

	$titulo = (!empty($login)) ? "Atendimentos do atendente {$login}" : "Atendimentos abertos";
?>
	<table id="tabela" class='table table-striped table-bordered table-hover table-fixed' >
		<caption class='titulo_tabela'><?=$titulo?></caption>
		<thead>
			<tr class='titulo_coluna'>
			    <th>Protocolo</th>
			    <th>Data Protocolo</th>
			    <th>Data Retorno</th>
			</tr>
	    </thead>
	    <tbody>
	<?php
		for($i=0; $i < $count; $i++){

			$hd_chamado = pg_fetch_result($resSubmit,$i,'hd_chamado');
			$data_atendimento = pg_fetch_result($resSubmit,$i,'data_atendimento');
			$data_retorno  = pg_fetch_result($resSubmit,$i,'data_retorno');

			echo "<tr>";
				echo "<td><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank'>{$hd_chamado}</a></td>";
				echo "<td class='tac'>{$data_atendimento}</td>";
				echo "<td class='tac'>{$data_retorno}</td>";
			echo "</tr>";

		}
	?>

	    </tbody>
	</table>
<?php
}else{
	echo "<div class='alert alert-warning'><h4><b>Nenhum resultado encontrado</b></h4></div>";
}
?>
