<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';


if ($_REQUEST["treinamento"]) {
	$treinamento   = $_REQUEST["treinamento"];
}

if (!empty($treinamento)){
	$sql_dados_treinamento = "SELECT tbl_treinamento.treinamento,
			tbl_treinamento.titulo,
			tbl_treinamento.local,
			TO_CHAR(tbl_treinamento.data_inicio,'DD/MM/YYYY')     AS data_inicio,
			TO_CHAR(tbl_treinamento.data_fim,'DD/MM/YYYY')        AS data_fim,
			( data_fim::DATE  - data_inicio::DATE ) AS total_dias,
			tbl_treinamento.cidade
		FROM tbl_treinamento
		WHERE tbl_treinamento.fabrica = $login_fabrica
		AND   tbl_treinamento.treinamento = $treinamento
		ORDER BY tbl_treinamento.data_inicio,tbl_treinamento.titulo" ;
	$res_dados_treinamento = pg_query($con,$sql_dados_treinamento);
	if (pg_num_rows($res_dados_treinamento) > 0){
		$res_dados_treinamento = pg_fetch_assoc($res_dados_treinamento);
	}

	if (!in_array($login_fabrica, array(169,170))) {
	    $confirma_insc = "AND tbl_treinamento_posto.confirma_inscricao IS TRUE";
	}

	if (in_array($login_fabrica, [169,170])) { 
		$campo_posto   = ", tbl_posto.nome AS nome_posto";
	}
	
	$sql_inscritos = "SELECT  tbl_treinamento_posto.treinamento_posto,
					tbl_tecnico.nome     AS tecnico_nome,
					tbl_posto_fabrica.codigo_posto
					{$campo_posto}
			   FROM tbl_treinamento_posto
		  LEFT JOIN tbl_promotor_treinamento USING(promotor_treinamento)
		  LEFT JOIN tbl_posto USING(posto)
		  LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto       = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		  LEFT JOIN tbl_admin         ON tbl_treinamento_posto.admin   = tbl_admin.admin
		  LEFT JOIN tbl_tecnico       ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
			  WHERE tbl_treinamento_posto.treinamento = $treinamento
				AND tbl_treinamento_posto.ativo IS TRUE
				AND tbl_treinamento_posto.tecnico IS NOT NULL
				{$confirma_insc}
		   ORDER BY tbl_posto.nome" ;
	$res_inscritos = pg_query($con,$sql_inscritos);
}

?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen, print" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen,print" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen,print" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen,print" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;z-index:1">
			<?php
				if (in_array($login_fabrica, array(169,170))) {
					$span_titulo = '7';
				}else{
					$span_titulo = '2';
				}
			?>
			<table id="lista_presencao" class='table table-bordered table-fixed'>
				<thead>
					<tr class='titulo_tabela'>
						<th colspan="<?= $span_titulo; ?>"><?=$res_dados_treinamento['titulo']?></th>
					</tr>
					<tr>
						<?php
							if (in_array($login_fabrica, array(169,170))){
						?>
							<th class="tal" colspan="<?= $span_titulo; ?>">
										Data: <?= $res_dados_treinamento['data_inicio'].' ~ '.$res_dados_treinamento['data_fim']; ?>
							</th>
						<?php			
							}else { ?>	
								<th class="tal" colspan="2">
									Data: <?= $res_dados_treinamento['data_inicio']; ?>	
								</th>
						<?php } ?>
						
					</tr>
					<tr>
						<th class='tal' colspan="<?= $span_titulo; ?>">
							Local: <?=$res_dados_treinamento['local']?>
						</th>
					</tr>
					<tr class="titulo_coluna">
						<?php
							if (in_array($login_fabrica, array(169,170))){ $colspan = '7'; $ass_class = 'class="span7"'; $span = '5';}  
						?>
						<th class="span<?php echo $span; ?>">
							Nome
						</th>
						
						<th colspan="<?php echo $colspan; ?>" <?php echo $ass_class; ?>>
							Assinatura
						</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						if (pg_num_rows($res_inscritos) > 0){
							$aux_exibe_dia = false;
							for ($i=0; $i < pg_num_rows($res_inscritos); $i++) { 
								$nome_tecnico = pg_fetch_result($res_inscritos, $i, 'tecnico_nome');
								$nome_tecnico = substr($nome_tecnico, 0, 35);

								if (in_array($login_fabrica, [169,170])){
									$nome_posto   = pg_fetch_result($res_inscritos, $i, 'nome_posto');
									$nome_posto   = substr($nome_posto, 0, 35);
									if (empty($nome_posto)){
										$nome_posto = 'Convidado';
									}
								}else{
									$codigo_posto = pg_fetch_result($res_inscritos, $i, 'codigo_posto');
									if (empty($codigo_posto)){
										$codigo_posto = 'Convidado';
									}	
								}
								
						if (in_array($login_fabrica, array(169,170))) {  
							$dias  = $res_dados_treinamento['total_dias']+1;
							$dias  = ($dias == 0) ? '1' : $dias;       
						?>				
							<tr>
								<td style='font-size: 11px;'>&nbsp;<?=$nome_tecnico?> &nbsp;&nbsp; - &nbsp;&nbsp; <?=$nome_posto?>&nbsp;</td>
								<?php for ($i_data=1; $i_data<=$dias; $i_data++){ ?>
								<td style='font-size: 11px;'>
									<?php if ($aux_exibe_dia == false) { ?>
										<label align='center'>dia <?=$i_data?></label>
									<?php } else { ?>
										<label>&nbsp;</label>
									<?php } ?>
									<hr style="margin-bottom: 0px !important; border-top: 1px solid #adadad !important; ">
								</td>
								<?php } ?>		
							</tr>
					<?php }else{ ?>				
							<tr>
								<td><?=$nome_tecnico?></td>
								<td><hr style="margin-bottom: 0px !important; border-top: 1px solid #adadad !important; "></td>
							</tr>
					<?php }
						$aux_exibe_dia = true;
						}
					}

				?>	
				</tbody>
			</table>
		</div>
		</div>

	<script language="JavaScript">
       window.print();
    </script>
	</body>
</html>
