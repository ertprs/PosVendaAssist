<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$now = date('Y-m-d');


/// requisitos

$query_requisitos_one = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Requisitos' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_requisitos_one);
$requisitos_one = pg_fetch_all($result);

$query_requisitos_two = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Requisitos' AND tc.ordem = 2 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_requisitos_two);
$requisitos_two = pg_fetch_all($result);
/// orcamento

$query_orcamento_one = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Orcamento' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_orcamento_one);
$orcamento_one = pg_fetch_all($result);

$query_orcamento_two = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Orcamento' AND tc.ordem = 2 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_orcamento_two);
$orcamento_two = pg_fetch_all($result);

/// analise

$query_analise_one = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Analise' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_analise_one);
$analise_one = pg_fetch_all($result);

$query_analise_two = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Analise' AND tc.ordem = 2 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_analise_two);
$analise_two = pg_fetch_all($result);

$query_analise_three = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Analise' AND tc.ordem = 3 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_analise_three);
$analise_three = pg_fetch_all($result);

$query_aguard_exec = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Aguard.Execucao' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_aguard_exec);
$aguard_exec = pg_fetch_all($result);

$query_correcao = "SELECT ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Correcao' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_correcao);
$correcao = pg_fetch_all($result);

$query_validacao = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Validacao' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_validacao);
$validacao = pg_fetch_all($result);

$query_valid_homologacao = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'ValidacaoHomologacao' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_valid_homologacao);
$valid_homologacao = pg_fetch_all($result);

$query_efetivacao = "SELECT th.status, ts.hd_chamado, tc.etapa, ta.nome_completo, tf.nome, ts.data_inicio, ts.data_prazo, ts.data_entrega, (current_date - ts.data_prazo::date) as atraso 
FROM tbl_status_chamado ts 
JOIN tbl_admin ta ON ta.admin = ts.admin 
JOIN tbl_controle_status tc ON ts.controle_status = tc.controle_status 
JOIN tbl_hd_chamado th ON ts.hd_chamado = th.hd_chamado 
JOIN tbl_fabrica tf ON tf.fabrica = th.fabrica 
WHERE tc.status = 'Efetivacao' AND tc.ordem = 1 
ORDER BY ts.data_prazo ASC;";
$result = pg_query($con, $query_efetivacao);
$efetivacao = pg_fetch_all($result);

?>

<!DOCTYPE html>
<html lang="pt-Br">
	<head>
		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
		<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700" rel="stylesheet"> 
		<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
		<title>Painel Chamados | Telecontrol</title>
	</head>
	<style type="text/css">
		td:first-child {font-weight:bold;}
		td:nth-child(6) {font-weight:bold;}
		tr {text-align:center;}
	</style>
	<body>
		<div class="container-fluid requisitos container-active" data-processo="requisito_one">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#85C940;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">AGUARDANDO REQUISITOS</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($requisitos_one, function ($r) {
									if ($r['etapa'] == 'Aguardando Requisitos' and $r['status'] == 'Requisitos') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_reqone = 0;
								foreach ($requisitos_one as $item) {
									if ($item['etapa'] == 'Aguardando Requisitos' and $item['status'] == 'Requisitos') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_reqone++;
										}
									}
								}
								echo $prazo_reqone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_reqone = 0;
								foreach ($requisitos_one as $item) {
									if ($item['etapa'] == 'Aguardando Requisitos' and $item['status'] == 'Requisitos') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_reqone++;
										}
									}
								}
								echo $atrasado_reqone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_reqone = 0;
										foreach ($requisitos_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data == $now) {
												$dia_reqone++;
											}
										}
										echo $dia_reqone;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_reqone = 0;
										foreach ($requisitos_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date('W', strtotime($data)) == date('W')) {
												$sem_reqone++;
											}
										}
										echo $sem_reqone;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_reqone = 0;
										foreach ($requisitos_one as $item) {
											if(!empty($item['data_entrega'])) {
												list($data, $hora) = explode(" ", $item['data_entrega']);
												if (date("Y m", strtotime($data)) == date("Y m")) {
													$mes_reqone++;
												}
											}
										}
										echo $mes_reqone;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_reqone = 0;
						foreach ($requisitos_one as $item) {
							if ($item['etapa'] == 'Aguardando Requisitos' and $item['status'] == 'Requisitos') {
								if ($total_reqone <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_reqone++;
								}
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="container-fluid requisitos_two" data-processo="requisito_two" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#85C940;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">REQUISITOS ANALISTA</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($requisitos_two, function ($r) {
									if ($r['etapa'] == 'Requisitos Analista' and $r['status'] == 'Requisitos') {
										return $r['data_entrega'] == "" ? true : false;
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_reqtwo = 0;
								foreach ($requisitos_two as $item) {
									if ($item['etapa'] == 'Requisitos Analista' and $item['status'] == 'Requisitos') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_reqtwo++;
										}
									}
								}
								echo $prazo_reqtwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_reqtwo = 0;
								foreach ($requisitos_two as $item) {
									if ($item['etapa'] == 'Requisitos Analista' and $item['status'] == 'Requisitos') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_reqtwo++;
										}
									}
								}
								echo $atrasado_reqtwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_reqtwo = 0;
										foreach ($requisitos_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data == $now) {
												$dia_reqtwo++;
											}
										}
										echo $dia_reqtwo;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_reqtwo = 0;
										foreach ($requisitos_two as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) == date('W')) {
												$sem_reqtwo++;
											}
										}
										echo $sem_reqtwo;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_reqtwo = 0;
										foreach ($requisitos_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_reqtwo++;
											}
										}
										echo $mes_reqtwo;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_reqtwo = 0;
						foreach ($requisitos_two as $item) {
							if ($item['etapa'] == 'Requisitos Analista' and $item['status'] == 'Requisitos') {
								if ($total_reqtwo <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_reqtwo++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// ORÇAMENTO - ETAPA 1 /// -->

		<div class="container-fluid orcamento" data-processo="orcamento_one" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#d82f2f;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">AGUARDANDO ORÇAMENTO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($orcamento_one, function ($r) {
									if ($r['etapa'] == 'Aguardando Orçamento' and $r['status'] == 'Orçamento') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_orcone = 0;
								foreach ($orcamento_one as $item) {
									if ($item['etapa'] == 'Aguardando Orçamento' and $item['status'] == "Orçamento") {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_orcone++;
										}
									}
								}
								echo $prazo_orcone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_orcone = 0;
								foreach ($orcamento_one as $item) {
									if ($item['etapa'] == 'Aguardando Orçamento' and $item['status'] == 'Orçamento') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_orcone++;
										}
									}
								}
								echo $atrasado_orcone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_orcone = 0;
										foreach ($orcamento_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_orcone++;
											}
										}
										echo $dia_orcone;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_orcone = 0;
										foreach ($orcamento_one as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_orcone++;
											}
										}
										echo $sem_orcone;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_orcone = 0;
										foreach ($orcamento_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_orcone++;
											}
										}
										echo $mes_orcone;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-transform:uppercase;text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_orcone = 0;
						foreach ($orcamento_one as $item) {
							if ($item['etapa'] == 'Aguardando Orçamento' and $item['status'] == 'Orçamento') {
								if ($total_orcone <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_orcone++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// ORÇAMENTO - ETAPA 2 /// -->

		<div class="container-fluid orcamento_two" data-processo="orcamento_two" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#d82f2f;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">ORÇAMENTO EM APROVAÇÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($orcamento_two, function ($r) {
									if ($r['etapa'] == 'Orçamento em Aprovação' and $r['status'] == 'Orçamento') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_orctwo = 0;
								foreach ($orcamento_two as $item) {
									if ($item['etapa'] == 'Orçamento em Aprovação' and $item['status'] == 'Orçamento') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_orctwo++;
										}
									}
								}
								echo $prazo_orctwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_orctwo = 0;
								foreach ($orcamento_two as $item) {
									if ($item['etapa'] == 'Orçamento em Aprovação' and $item['status'] == 'Orçamento') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_orctwo++;
										}
									}
								}
								echo $atrasado_orctwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_orctwo = 0;
										foreach ($orcamento_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_orctwo++;
											}
										}
										echo $dia_orctwo;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_orctwo = 0;
										foreach ($orcamento_two as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_orctwo++;
											}
										}
										echo $sem_orctwo;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_orctwo = 0;
										foreach ($orcamento_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_orctwo++;
											}
										}
										echo $mes_orctwo;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_orctwo = 0;
						foreach ($orcamento_two as $item) {
							if ($item['etapa'] == 'Orçamento em Aprovação' and $item['status'] == 'Orçamento') {
								if ($total_orctwo <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_orctwo++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// ANALISE - ETAPA 1 /// -->

		<div class="container-fluid analise_one" data-processo="analise_one" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#ffcd1c;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">PRAZO EM ANÁLISE</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($analise_one, function ($r) {
									if ($r['etapa'] == 'Prazo em Análise' and $r['status'] == 'Análise') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_anone = 0;
								foreach ($analise_one as $item) {
									if ($item['etapa'] == 'Prazo em Análise' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_anone++;
										}
									}
								}
								echo $prazo_anone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_anone = 0;
								foreach ($analise_one as $item) {
									if ($item['etapa'] == 'Prazo em Análise' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_anone++;
										}
									}
								}
								echo $atrasado_anone;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_anone = 0;
										foreach ($analise_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_anone++;
											}
										}
										echo $dia_anone;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_anone = 0;
										foreach ($analise_one as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem++;
											}
										}
										echo $sem_anone;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_anone = 0;
										foreach ($analise_one as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_anone++;
											}
										}
										echo $mes_anone;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_anone = 0;
						foreach ($analise_one as $item) {
							if ($item['etapa'] == 'Prazo em Análise' and $item['status'] == 'Análise') {
								if ($total_anone <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_anone++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// ANALISE - ETAPA 2 /// -->

		<div class="container-fluid analise_two" data-processo="analise_two" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#ffcd1c;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">AGUARDANDO ANÁLISE</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($analise_two, function ($r) {
									if ($r['etapa'] == 'Aguardando Análise' and $r['status'] == 'Análise') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_antwo = 0;
								foreach ($analise_two as $item) {
									if ($item['etapa'] == 'Aguardando Análise' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_antwo++;
										}
									}
								}
								echo $prazo_antwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_antwo = 0;
								foreach ($analise_two as $item) {
									if ($item['etapa'] == 'Aguardando Análise' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_antwo++;
										}
									}
								}
								echo $atrasado_antwo;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_antwo = 0;
										foreach ($analise_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_antwo++;
											}
										}
										echo $dia_antwo;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_antwo = 0;
										foreach ($analise_two as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_antwo++;
											}
										}
										echo $sem_antwo;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_antwo = 0;
										foreach ($analise_two as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_antwo++;
											}
										}
										echo $mes_antwo;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_antwo = 0;
						foreach ($analise_two as $item) {
							if ($item['etapa'] == 'Aguardando Análise' and $item['status'] == 'Análise') {
								if ($total_antwo <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_antwo++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// ANALISE - ETAPA 3 /// -->

		<div class="container-fluid analise_three" data-processo="analise_three" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#ffcd1c;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">SEM PREVISÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($analise_three, function ($r) {
									if ($r['etapa'] == 'Sem Previsão' and $r['status'] == 'Análise') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_anthree = 0;
								foreach ($analise_three as $item) {
									if ($item['etapa'] == 'Sem Previsão' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_anthree++;
										}
									}
								}
								echo $prazo_anthree;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_anthree = 0;
								foreach ($analise_three as $item) {
									if ($item['etapa'] == 'Sem Previsão' and $item['status'] == 'Análise') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_anthree++;
										}
									}
								}
								echo $atrasado_anthree;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_anthree = 0;
										foreach ($analise_three as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_anthree++;
											}
										}
										echo $dia_anthree;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_anthree = 0;
										foreach ($analise_three as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_anthree++;
											}
										}
										echo $sem_anthree;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_anthree = 0;
										foreach ($analise_three as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_anthree++;
											}
										}
										echo $mes_anthree;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_anthree = 0;
						foreach ($analise_three as $item) {
							if ($item['etapa'] == 'Sem Previsão' and $item['status'] == 'Análise') {
								if ($total_anthree <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_anthree++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// AGUARDANDO EXECUÇÃO - ETAPA 1 /// -->

		<div class="container-fluid aguard_exec" data-processo="aguard_exec" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#4ed870;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">DESENVOLVIMENTO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($aguard_exec, function ($r) {
									if ($r['etapa'] == 'Desenvolvimento' and ($r['status'] == 'Aguard.Execução' or $r['status'] == 'Execução')) {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_exec = 0;
								foreach ($aguard_exec as $item) {
									if ($item['etapa'] == 'Desenvolvimento' and ($item['status'] == 'Aguard.Execução' or $item['status'] == 'Execução')) {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_exec++;
										}
									}
								}
								echo $prazo_exec;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_exec = 0;
								foreach ($aguard_exec as $item) {
									if ($item['etapa'] == 'Desenvolvimento' and ($item['status'] == 'Aguard.Execução' or $item['status'] == 'Execução')) {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_exec++;
										}
									}
								}
								echo $atrasado_exec;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_exec = 0;
										foreach ($aguard_exec as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_exec++;
											}
										}
										echo $dia_exec;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_exec = 0;
										foreach ($aguard_exec as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_exec++;
											}
										}
										echo $sem_exec;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_exec = 0;
										foreach ($aguard_exec as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_exec++;
											}
										}
										echo $mes_exec;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_exec = 0;
						foreach ($aguard_exec as $item) {
							if ($item['etapa'] == 'Desenvolvimento' and ($item['status'] == 'Aguard.Execução' or $item['status'] == 'Execução')) {
								if ($total_exec <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_exec++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>
		
		<!-- /// CORREÇÃO - ETAPA 1 /// -->

		<div class="container-fluid correcao" data-processo="correcao" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#dd352c;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">CORREÇÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($correcao, function ($r) {
									if ($r['etapa'] == 'Correção' and ($r['status'] == 'Correção')) {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_correc = 0;
								foreach ($correcao as $item) {
									if ($item['etapa'] == 'Correção' and $item['status'] == 'Correção') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_correc++;
										}
									}
								}
								echo $prazo_correc;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_correc = 0;
								foreach ($correcao as $item) {
									if ($item['etapa'] == 'Correção' and $item['status'] == 'Correção') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_correc++;
										}
									}
								}
								echo $atrasado_correc;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_correc = 0;
										foreach ($correcao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_correcd++;
											}
										}
										echo $dia_correc;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_correc = 0;
										foreach ($correcao as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_correc++;
											}
										}
										echo $sem_correc;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_correc = 0;
										foreach ($correcao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_correc++;
											}
										}
										echo $mes_correc;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_correc = 0;
						foreach ($correcao as $item) {
							if ($item['etapa'] == 'Correção' and $item['status'] == 'Correção') {
								if ($total_correc <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_correc++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// VALIDAÇÃO - ETAPA 1 /// -->

		<div class="container-fluid aguard_exec" data-processo="validacao" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#3380b7;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">AGUARDANDO VALIDAÇÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($validacao, function ($r) {
									if ($r['etapa'] == 'Aguardando Validação' and $r['status'] == 'Validação') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_valid = 0;
								foreach ($validacao as $item) {
									if ($item['etapa'] == 'Aguardando Validação' and $item['status'] == 'Validação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_valid++;
										}
									}
								}
								echo $prazo_valid;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_valid = 0;
								foreach ($validacao as $item) {
									if ($item['etapa'] == 'Aguardando Validação' and $item['status'] == 'Validação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_valid++;
										}
									}
								}
								echo $atrasado_valid;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_valid = 0;
										foreach ($validacao as $item) {
											if ($item['etapa'] == 'Aguardando Validação' and $item['status'] == 'Validação') {
												list($data, $hora) = explode(" ", $item['data_entrega']);
												if ($data === $now) {
													$dia_valid++;
												}
											}
										}
										echo $dia_valid;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_valid = 0;
										foreach ($validacao as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_valid++;
											}
										}
										echo $sem_valid;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_valid = 0;
										foreach ($validacao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_valid++;
											}
										}
										echo $mes_valid;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_valid = 0;
						foreach ($validacao as $item) {
							if ($item['etapa'] == 'Aguardando Validação' and $item['status'] == 'Validação') {
								if ($total_valid <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_valid++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// VALIDAÇÃO HOMOLOGAÇÃO - ETAPA 1 /// -->

		<div class="container-fluid aguard_exec" data-processo="valid_homologacao" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#4ba5e5;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">HOMOLOGAÇÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($valid_homologacao, function ($r) {
									if ($r['etapa'] == 'Homologação' and $r['status'] == "ValidaçãoHomologação") {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_validh = 0;
								foreach ($valid_homologacao as $item) {
									if ($item['etapa'] == 'Homologação' and $item['status'] == 'ValidaçãoHomologação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_validh++;
										}
									}
								}
								echo $prazo_validh;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_validh = 0;
								foreach ($valid_homologacao as $item) {
									if ($item['etapa'] == 'Homologação' and $item['status'] == 'ValidaçãoHomologação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_validh++;
										}
									}
								}
								echo $atrasado_validh;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_validh = 0;
										foreach ($valid_homologacao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_validh++;
											}
										}
										echo $dia_validh;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_validh = 0;
										foreach ($valid_homologacao as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_validh++;
											}
										}
										echo $sem_validh;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_validh = 0;
										foreach ($valid_homologacao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_validh++;
											}
										}
										echo $mes_validh;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_validh = 0;
						foreach ($valid_homologacao as $item) {
							if ($item['etapa'] == 'Homologação' and $item['status'] == 'ValidaçãoHomologação') {
								if ($total_validh <= 10 and empty($item['data_entrega'])) { 
									$inicio = explode(" ", $item['data_inicio']);
									$prazo = explode(" ", $item['data_prazo']);

									if ($item['atraso'] > 0) {
										echo "<tr style='background-color:#fcb0b0;'>";
									} else {
										echo "<tr>";
									}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_validh++;
								}
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- /// EFETIVAÇÃO - ETAPA 1 /// -->

		<div class="container-fluid aguard_exec" data-processo="valid_homologacao" style="display:none;">
			<div class="col-lg-12" style="padding:0px;">
				<div class="row" style="background-color:#CCC;">
					<div class="col-lg-12">
						<h2 style="font-size:2.6em;text-align:center;padding:40px 0 20px 0;font-family:'Roboto';font-weight:bold;color:#FFF;">EFETIVAÇÃO</h2>
					</div>
				</div>
				<div class="row" style="background-color:#5B5B5B;">
					<div class="col-lg-9" style="padding:10px 0 0 0;">
						<h3 style="text-align:center;color:#FFF;">FILA</h3>
					</div>
					<div class="col-lg-3" style="padding:10px 0 0 0;color:#FFF;background-color:#5B5B5B;font-family:'Roboto';font-weight:500;box-shadow:0 0 8px #2D2D2D;z-index:1">
						<h3 style="text-align:center;">FINALIZADOS</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL CHAMADOS</h3>
						</div>
						<div class="col-lg-12 total-chamados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								echo count(array_filter($efetivacao, function ($r) {
									if ($r['etapa'] == 'Efetivação' and $r['status'] == 'Efetivação') {
										if (empty($r['data_entrega'])) {
											return true;
										} else {
											return false;
										}
									}
								}));
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#596D9B;color:#FFF">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL NO PRAZO</h3>
						</div>
						<div class="col-lg-12 total-prazo">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$prazo_eft = 0;
								foreach ($efetivacao as $item) {
									if ($item['etapa'] == 'Efetivação' and $item['status'] == 'Efetivação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) >= strtotime(date('Y-m-d'))) {
											$prazo_eft++;
										}
									}
								}
								echo $prazo_eft;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="padding:5px;background-color:#F44E42;color:#FFF;">
						<div class="col-lg-12" style="padding:10px;">
							<h3 style="text-align:center;">TOTAL ATRASADOS</h3>
						</div>
						<div class="col-lg-12 total-atrasados">
							<h1 style="text-align:center;font-size:8em;font-family:'Roboto';font-weight:500;">
								<?php
								$atrasado_eft = 0;
								foreach ($efetivacao as $item) {
									if ($item['etapa'] == 'Efetivação' and $item['status'] == 'Efetivação') {
										list($data, $hora) = explode(" ", $item['data_prazo']);
										if (empty($item['data_entrega']) and strtotime($data) < strtotime(date('Y-m-d'))) {
											$atrasado_eft++;
										}
									}
								}
								echo $atrasado_eft;
								?>
							</h1>
						</div>
					</div>
					<div class="col-lg-3" style="background-color:#596D9B;color:#2D2D2D;">
						<div class="row" style="height:233px;">
							<div class="col-lg-4" style="background-color:#EFEFEF;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">DIA</h5>
								</div>
								<div class="col-lg-12 final-dia" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$dia_eft = 0;
										foreach ($efetivacao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if ($data === $now) {
												$dia_eft++;
											}
										}
										echo $dia_eft;
										?>	
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#E8E8E8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">SEM.</h5>
								</div>
								<div class="col-lg-12 final-semana" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$sem_eft = 0;
										foreach ($efetivacao as $item) {
											if (date('W', strtotime(explode(" ", $item['data_entrega'])[0])) === date('W')) {
												$sem_eft++;
											}
										}
										echo $sem_eft;
										?>
									</h1>
								</div>
							</div>
							<div class="col-lg-4" style="background-color:#D8D8D8;padding:20px 0 0 0;">
								<div class="col-lg-12">
									<h5 style="text-align:center;">MÊS</h5>
								</div>
								<div class="col-lg-12 final-mes" style="padding:50px 0 0 0;">
									<h1 style="text-align:center;">
										<?php
										$mes_eft = 0;
										foreach ($efetivacao as $item) {
											list($data, $hora) = explode(" ", $item['data_entrega']);
											if (date("m Y", strtotime($data)) == date("m Y")) {
												$mes_eft++;
											}
										}
										echo $mes_eft;
										?>
									</h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12" style="padding:0;">
				<div class="row" style="background-color:#FFF;">
					<div class="col-lg-12">
						<h3 style="text-align:center;padding:20px 0 10px 0;font-family:'Roboto';font-weight:500;color:#2D2D2D;">PRINCIPAIS CHAMADOS</h3>
					</div>
				</div>
				<table class="table table-fixed header-fixed" style="font-family:'Roboto';font-size:1.2em;">
					<thead>
						<tr>
							<th>HD Chamado</th>
							<th>Fábrica</th>
							<th>Data Abertura</th>
							<th>Admin</th>
							<th>Prazo</th>
							<th>Dias em Atraso</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$total_eft = 0;
						foreach ($efetivacao as $item) {
							if ($item['etapa'] == 'Efetivação' and $item['status'] == 'Efetivação') {
							if ($total <= 10 and empty($item['data_entrega'])) { 
								$inicio = explode(" ", $item['data_inicio']);
								$prazo = explode(" ", $item['data_prazo']);

								if ($item['atraso'] > 0) {
									echo "<tr style='background-color:#fcb0b0;'>";
								} else {
									echo "<tr>";
								}
						?>
								<td><?= $item['hd_chamado'] ?></td>
								<td><?= $item['nome'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $inicio[0]))) . " " . $inicio[1] ?></td>
								<td><?= $item['nome_completo'] ?></td>
								<td><?= implode("/", array_reverse(explode("-", $prazo[0]))) . " " . $prazo[1] ?></td>
								<td>
									<?php 
									if ($item['atraso'] > 0) {
										echo $item['atraso'];
									} else {
										echo "0";
									}
									?>
								</td>
							</tr>
						<?php
									$total_eft++;
								}
							}
						}
						?>						
					</tbody>
				</table>
			</div>
		</div>
		<div class="container-fluid erro" data-processo="erro" style="display:none;">
			<iframe src='painel_erro_semanal2.php' width='100%' height='800'></iframe>
		</div>
		<!-- end -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
		<script>
			var a = null;
			$(function () {
				a = setInterval(function () {					
					var active = $(".container-active");
					$(active).fadeOut(800, function () {
						if ($(active).next(".container-fluid").length != 0) {
							var next = $(active).next(".container-fluid");
						} else {
							window.location.reload();
						}
						$(active).removeClass("container-active");
						
						$(next).addClass("container-active");
						$(next).fadeIn(800);

						var data = $(next).data("processo");
						console.log(data);
					});
				}, 30000);
			});

			function getData(process) {
				$.ajax('painel_novo.php', {
					method: 'POST',
					data: {
						getData: process
					}
				}).done(function (response) {
					var response = JSON.parse(response);
					console.log(response);
				});
			}
		</script>
	</body>
</html>
