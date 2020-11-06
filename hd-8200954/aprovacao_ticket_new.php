<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_usuario.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "APROVAÇÃO DE TICKETS";

include "cabecalho_new.php";

$plugins = ["shadowbox", "dataTable"];
include("plugin_loader.php");

$apiUrl = "https://api2.telecontrol.com.br";
$companyHash = $parametros_adicionais_posto['company_hash'];

$curl = curl_init();
curl_setopt_array($curl, array(
	CURLOPT_URL => "{$apiUrl}/ticket-checkin/ticket-finalizado/companyHash/{$companyHash}",
    CURLOPT_RETURNTRANSFER => true,
  	CURLOPT_ENCODING => "",
  	CURLOPT_MAXREDIRS => 10,
  	CURLOPT_TIMEOUT => 30,
  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  	CURLOPT_CUSTOMREQUEST => "GET",
  	CURLOPT_POSTFIELDS => "",
  	CURLOPT_HTTPHEADER => array(
  		"access-application-key: 652a4aed48fea22e13eafcfe9b3acde8fe66d8d1",
    	"access-env: PRODUCTION",
    	"cache-control: no-cache",
    	"content-type: application/json"
  	),
));

$resCurl = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
  	echo "cURL Error #:" . $err;
} else {   
    $listaDeTickets = json_decode($resCurl, true);
}

function getOs($os, $fabrica){
	global $con;

	$sql ="SELECT tbl_os.os, 
			   tbl_tipo_atendimento.descricao as tipo_atendimento, 
			   consumidor_nome, 
			   consumidor_cidade, 
			   tbl_os.fabrica, 
			   (SELECT tbl_tecnico.nome FROM tbl_tecnico_agenda 
				  JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico 
                 WHERE tbl_tecnico_agenda.os = tbl_os.os 
                   AND tbl_tecnico_agenda.fabrica = {$fabrica}
		      ORDER BY tecnico_agenda 
		    DESC LIMIT 1) AS nome_tecnico,
               (SELECT TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY HH24:MI')
                  FROM tbl_tecnico_agenda
                  JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico 
                 WHERE tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$fabrica}
              ORDER BY tecnico_agenda 
            DESC LIMIT 1) AS data_agendameto 
        FROM tbl_os 
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
       WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$fabrica}";

       	$resource = pg_query($con, $sql);
	   	return pg_affected_rows($resource) ? pg_fetch_assoc($resource) : null;
}
?>

<style>
	.container{ width: 100%; padding: 10px; }

	.j-row{ display: flex }

	.j-col-1 { flex-basis:  8.3333% }
	.j-col-2 { flex-basis: 16.6666% }
	.j-col-3 { flex-basis: 25% }
	.j-col-4 { flex-basis: 33.3333% }
	.j-col-5 { flex-basis: 41.6666% }
	.j-col-6 { flex-basis: 50% }
	.j-col-7 { flex-basis: 58.3333% }
	.j-col-8 { flex-basis: 66.6666% }
	.j-col-9 { flex-basis: 75% }
	.j-col-10 { flex-basis: 83.3333% }
	.j-col-11 { flex-basis: 91.6666% }
	.j-col-12 { flex-basis: 100% }

	.j-mr-1 { margin-right: 5px }

	.j-mt-1 { margin-top: 5px }
	.j-mt-2 { margin-top: 10px }
</style>

<div class="">
	<table class="table table-striped table-bordered" style="width: 100%" id="tabela">
		<thead>
			<tr style="background-color: #596D9B !important; color: #FFFFFF !important;">
				<th>Ticket</th>
				<th>OS</th>
				<th>Tipo atendimento</th>
				<th>Nome cliente</th>
				<th>Cidade cliente</th>
				<th>Nome técnico</th>
				<th>Data agendamento</th>
				<th>Data finalizado</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($listaDeTickets as $ticket):
				$response = json_decode($ticket['response'], true);
				$osInfo = getOs($ticket['reference_id'], $login_fabrica);

				if(!$osInfo) continue;
			?>
			<tr>
				<td> <?= $ticket['ticket'] ?> </td>
				<td>
					<a href="os_press.php?os=<?= $ticket['reference_id'] ?>" target="_blank"> <?= $ticket['reference_id'] ?> </a>
				</td>
				<td> <?= $osInfo['tipo_atendimento'] ?> </td>
				<td> <?= $osInfo['consumidor_nome'] ?> </td>
				<td> <?= $osInfo['consumidor_cidade'] ?> </td>
				<td> <?= $osInfo['nome_tecnico'] ?> </td>
				<td> <?= $osInfo['data_agendameto'] ?> </td>
				<td> 
					<?= (new DateTIme($ticket['data_finalizado']))->format('d/m/Y H:i') ?> 
				</td>
				<td style="display: flex; justify-content: center">
					<a  href="detalhes_ticket.php?ticket=<?=$ticket['ticket']?>&os=<?=$ticket['reference_id']?>" class="btn btn-primary" target="_blank">
						Detalhes
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<script>
$(function(){
	$('#tabela').DataTable({
		aaSorting: [[0, 'desc']],
		"oLanguage": {
			"sLengthMenu": "Mostrar <select>" +
							'<option value="10"> 10 </option>' +
							'<option value="50"> 50 </option>' +
							'<option value="100"> 100 </option>' +
							'<option value="150"> 150 </option>' +
							'<option value="200"> 200 </option>' +
							'<option value="-1"> Tudo </option>' +
							'</select> resultados',
			"sSearch": "Procurar:",
			"sInfo": "Mostrando de _START_ até _END_ de um total de _TOTAL_ registros",
			"oPaginate": {
				"sFirst": "Primeira página",
				"sLast": "Última página",
				"sNext": "Próximo",
				"sPrevious": "Anterior"
			}
		}
	});
});
</script>
