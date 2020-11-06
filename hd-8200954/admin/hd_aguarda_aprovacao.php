<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros,call_center,gerencia";
include "autentica_admin.php";

$sql = "
	SELECT admin, responsavel_postos,help_desk_supervisor
	 FROM tbl_admin
	 WHERE admin = '$login_admin'
	   ";
	   //echo $sql;
$res_admin = pg_exec($con, $sql);
if (pg_num_rows($res_admin) == 0){
	if($login_fabrica == 189 AND $login_privilegios == "*"){
                header('Location: acompanhamento_atendimentos.php');
        }else{	
		header('Location: menu_cadastro.php');
	}
	die();
}else{
	//$link = (pg_fetch_result($res_admin, 0, 'responsavel_postos')=='t') ? "em_descredenciamento.php" : "menu_cadastro.php";
	$help_desk_supervisor = pg_fetch_result($res_admin, 0, 'help_desk_supervisor');
	 
	if ($help_desk_supervisor == t) {
		$adm_selec = '';
	}else{
		$adm_selec = "AND tbl_hd_chamado.admin = '$login_admin'";
	}

	//Chamados  com Or�amento
	$sql = "SELECT  DISTINCT ON (tbl_hd_chamado.hd_chamado) tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.status,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.data::date AS data,
					tbl_hd_chamado.hora_desenvolvimento,
					tbl_hd_chamado_requisito.data_requisito_aprova
				FROM tbl_hd_chamado
				JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				JOIN tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.tipo_chamado NOT IN(5,6)
				AND (status = 'Or�amento' AND hora_desenvolvimento > 0)
				AND resolvido IS NULL
				AND (SELECT tbl_admin.fabrica
				FROM tbl_hd_chamado_item
				JOIN tbl_admin ON tbl_hd_chamado_item.ADMIN = tbl_admin.ADMIN
				WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
				ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
				LIMIT 1) = 10
				AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
				$adm_selec
				ORDER BY hd_chamado DESC";
	$res_chamados = pg_query($con,$sql);

	//Chamados Conclu�dos
	$sql_c = "SELECT 	DISTINCT ON (tbl_hd_chamado.hd_chamado) tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.data::date AS data,
						tbl_hd_chamado.hora_desenvolvimento,
						tbl_hd_chamado_requisito.data_requisito_aprova
					FROM tbl_hd_chamado
					JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
					JOIN tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.tipo_chamado NOT IN(6)
					AND status = 'Concluido'
					AND resolvido IS NULL
					AND (SELECT tbl_admin.fabrica
							FROM tbl_hd_chamado_item
							JOIN tbl_admin ON tbl_hd_chamado_item.ADMIN = tbl_admin.ADMIN
							WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
							ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
							LIMIT 1) = 10
					AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
					$adm_selec
					ORDER BY hd_chamado DESC";
	//echo nl2br($sql_c);
	$res_c = pg_query($con,$sql_c);

	//Chamados com Requisitos
	$sql_req = "SELECT 	DISTINCT ON (tbl_hd_chamado.hd_chamado) tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.data::date AS data,
						tbl_hd_chamado.hora_desenvolvimento,
						tbl_hd_chamado_requisito.data_requisito_aprova
					FROM tbl_hd_chamado
					JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
					JOIN tbl_hd_chamado_requisito ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_requisito.hd_chamado
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.tipo_chamado NOT IN(5,6)
					AND status = 'Requisitos'
					AND resolvido IS NULL
					AND (SELECT tbl_admin.fabrica
							FROM tbl_hd_chamado_item
							JOIN tbl_admin ON tbl_hd_chamado_item.ADMIN = tbl_admin.ADMIN
							WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
							ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC
							LIMIT 1) = 10
					AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
					$adm_selec
					ORDER BY hd_chamado DESC";
	//echo nl2br($sql_req);
	$res_reqx = pg_query($con,$sql_req);

	//Chamados Novo
	$sql_n = "SELECT 	DISTINCT ON (tbl_hd_chamado.hd_chamado) tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_hd_chamado.data::date AS data,
						tbl_hd_chamado.hora_desenvolvimento
					FROM tbl_hd_chamado
					JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.tipo_chamado NOT IN(6)
					AND status = 'Novo'
					AND resolvido IS NULL					
					AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
					$adm_selec
					ORDER BY hd_chamado DESC";
	//echo nl2br($sql_n);
	$res_n = pg_query($con,$sql_n);


	//  echo nl2br($sql);
	//   echo nl2br($sql_c);
	//    echo nl2br($sql_req);
	//     echo nl2br($sql_n);
	// // echo "<br>";
	// echo pg_num_rows($res_chamados) ;

	if(pg_num_rows($res_chamados) == 0 AND pg_num_rows($res_c) == 0 AND pg_num_rows($res_reqx) == 0 AND pg_num_rows($res_n) == 0){
		//header("Location: $link");
		if($login_fabrica == 189 AND $login_privilegios == "*"){
                	header('Location: acompanhamento_atendimentos.php');
        	}else{

        	if ($login_privilegios != "*") {
				$arrPrivilegios = explode(",", $login_privilegios);

	        	if (in_array('cadastros', $arrPrivilegios)) {

					header("Location: menu_cadastro.php");

				} else {

					header("Location: menu_callcenter.php");

				}
				
			} else {

				header("Location: menu_cadastro.php");

			}
		}
	}

	$layout_menu = "gerencia";
	$title = "CHAMADOS AGUARDANDO APROVA��O";
	include "cabecalho_new.php";
	

	if (pg_num_rows($res_reqx)>0 OR pg_num_rows($res_c)>0 OR pg_num_rows($res_chamados)>0 OR pg_num_rows($res_n)>0) {?>	
		<div class="container">
		<!---<div class="alert">
				<p>Regras para a <b>CONCLUS�O</b> dos chamados helpdesk:</p>
						<ol style="list-style:outside decimal;">							
							<li>Ap�s o desenvolvimento ou corre��o dos chamados helpdesk, o <b>SUPORTE TELECONTROL</b> encaminhar� uma resposta de 
								<b>CONCLU�DO</b> ao respons�vel pela abertura do chamado, aguardando sua concord�ncia.</li>
							<li>O chamado s� ser� <b>RESOLVIDO</b> definitivamente ap�s o cliente concordar com a conclus�o do chamado. Caso n�o 
								concorde com a conclus�o, o chamado voltar� para a fila do <b>SUPORTE TELECONTROL</b>.</li>
							<li>Depois que o chmado for <b>CONCLU�DO</b> pelo <b>SUPORTE TELECONTROL</b>, o cliente ter� 10 dias �teis para concordar 
								com a resolu��o do chamado, caso contr�rio, ser� <b>RESOLVIDO</b> automaticamente.</li>-->
							<!-- <li class='vermelho'>O fabricante ter� <?=$backlog?> chamado(s) aprovado(s) e em desenvolvimento na <b>Telecontrol</b>, o restante ficar� em sua posse com o status �EM ESPERA�.</li> -->
						</ol>
		</div> <br>
		<?
		if(pg_num_rows($res_n) > 0){ ?>

			<table class='table table-hover table-striped table-bordered table-normal'>
				<caption class="titulo_tabela">Chamados Aguardando Aprova��o do Supervisor</caption>
				<thead>
				<tr class='titulo_coluna'>
					<th>Chamado</th>
					<th>T�tulo</th>
					<th>Status</th>
				</tr>
				</thead>
				<tbody>
			<?php
			for($i = 0; $i < pg_num_rows($res_n); $i++){
				$hd_chamado           = pg_fetch_result($res_n,$i,'hd_chamado');
				$status               = pg_fetch_result($res_n,$i,'status');
				$titulo               = pg_fetch_result($res_n,$i,'titulo');
				$data                 = pg_fetch_result($res_n,$i,'data');

			
				echo "<tr>";
					echo "<td>$hd_chamado</td>";
					echo "<td align='left'><a href='../helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$titulo</a></td>";
					echo "<td style='text-align:center'>$status</td>";
				echo "</tr>";
				
			}?>
			</tbody>
			</table>
		<?}
		if (pg_num_rows($res_reqx)>0) {?>
			<table class='table table-hover table-striped table-bordered table-normal'>
				<caption class="titulo_tabela">Chamados Aguardando Aprova��o do Requisitos</caption>
				<thead>
				<tr class='titulo_coluna'>
					<th>Chamado</th>
					<th>T�tulo</th>
					<th>Status</th>
					<th>Prazo para Resolu��o</th>
				</tr>
				</thead>
				<tbody>
			<?php
			for($i = 0; $i < pg_num_rows($res_reqx); $i++){
				$hd_chamado           = pg_fetch_result($res_reqx,$i,'hd_chamado');
				$status               = pg_fetch_result($res_reqx,$i,'status');
				$titulo               = pg_fetch_result($res_reqx,$i,'titulo');
				$data                 = pg_fetch_result($res_reqx,$i,'data');
				$hora_desenvolvimento = pg_fetch_result($res_reqx,$i,'hora_desenvolvimento');

				$dias = 10;
				$cria_linha = 1;

				$sqlR = "SELECT data::date
						FROM tbl_hd_chamado_item
						JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
						WHERE hd_chamado = $hd_chamado
						AND tbl_hd_chamado_item.interno IS NOT TRUE
						AND tbl_hd_chamado_item.status_item = 'Ap.Requisitos'
						ORDER BY hd_chamado_item DESC
						LIMIT 1";
				$resR = pg_query($con,$sqlR);

				if(pg_num_rows($resR) > 0){
					$data_status = pg_fetch_result($resR,0,0);

					$sqlS = "select fn_dias_uteis('$data_status + 1',$dias)";
					$resS = pg_query($con,$sqlS);
					$data_status = pg_fetch_result($resS,0,0);

					$sqlS = "SELECT '$data_status' - CURRENT_DATE";
					$resS = pg_query($con,$sqlS);

					$total_dias = pg_fetch_result($resS,0,0);
				}
			
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$cor_font = ($total_dias >= 3) ? "#000" : "#FF0000";

				if($cria_linha == 1){
					echo "<tr>";
						echo "<td>$hd_chamado</td>";
						echo "<td align='left'><a href='../helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$titulo</a></td>";
						echo "<td style='text-align:center'>$status</td>";
						echo "<td style='text-align: center'><font color='$cor_font'>$total_dias</font></td>";
					echo "</tr>";
				}

				$cria_linha = "";
				$total_dias = "";
			}?>
			</tbody>
			</table>
		<?	
		}
		if(pg_num_rows($res_chamados) > 0){?>

			<table class='table table-hover table-striped table-bordered table-normal'>
				<caption class="titulo_tabela">Chamados Aguardando Aprova��o do Or�amento</caption>
				<thead>
				<tr class='titulo_coluna'>
					<th>Chamado</th>
					<th>T�tulo</th>
					<th>Status</th>
					<th>Prazo para Aprova��o</th>
				</tr>
				</thead>
				<tbody>
			<?php
			for($i = 0; $i < pg_numrows($res_chamados); $i++){
				$hd_chamado           = pg_fetch_result($res_chamados,$i,'hd_chamado');
				$status               = pg_fetch_result($res_chamados,$i,'status');
				$titulo               = pg_fetch_result($res_chamados,$i,'titulo');
				$data                 = pg_fetch_result($res_chamados,$i,'data');
				$hora_desenvolvimento = pg_fetch_result($res_chamados,$i,'hora_desenvolvimento');

				if($status == "Requisitos"){
					$cria_linha = 1;
					$dias = 5;
					$sqlR = "SELECT hd_chamado
						FROM tbl_hd_chamado_requisito
						WHERE hd_chamado = $hd_chamado
						AND admin_requisito_aprova IS NULL
						AND data_requisito_aprova IS NULL
						LIMIT 1
						";
					$resR = pg_query($con,$sqlR);

					if(pg_num_rows($resR) > 0){
						$sqlR = "SELECT data::date AS data,
										status_item
								FROM tbl_hd_chamado_item
								JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
								WHERE hd_chamado = $hd_chamado
								AND tbl_hd_chamado_item.interno IS NOT TRUE
								ORDER BY hd_chamado_item DESC
								LIMIT 1";
						$resR = pg_query($con,$sqlR);

						if(pg_num_rows($resR) > 0){
							$data_status = pg_fetch_result($resR,0,'data');
							$status_item = pg_fetch_result($resR,0,'status_item');

							$sqlS = "select fn_dias_uteis('$data_status + 1',$dias)";
							$resS = pg_query($con,$sqlS);
							$data_status = pg_fetch_result($resS,0,0);

							$sqlS = "SELECT '$data_status' - CURRENT_DATE";
							$resS = pg_query($con,$sqlS);

							$total_dias = pg_fetch_result($resS,0,0);
						}
					}
				} else {
					if($hora_desenvolvimento > 0){
						$dias = 10;
						$cria_linha = 1;

						$sqlR = "SELECT data::date
								FROM tbl_hd_chamado_item
								JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
								WHERE hd_chamado = $hd_chamado
								AND tbl_hd_chamado_item.interno IS NOT TRUE
								ORDER BY hd_chamado_item DESC
								LIMIT 1";
						$resR = pg_query($con,$sqlR);

						if(pg_num_rows($resR) > 0){
							$data_status = pg_fetch_result($resR,0,0);

							$sqlS = "select fn_dias_uteis('$data_status + 1',$dias)";
							$resS = pg_query($con,$sqlS);
							$data_status = pg_fetch_result($resS,0,0);

							$sqlS = "SELECT '$data_status' - CURRENT_DATE";
							$resS = pg_query($con,$sqlS);

							$total_dias = pg_fetch_result($resS,0,0);
						}
					}
				}
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$cor_font = ($total_dias >= 3) ? "#000" : "#FF0000";

				if($cria_linha == 1){
					echo "<tr>";
						echo "<td>$hd_chamado</td>";
						echo "<td align='left'><a href='../helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$titulo</a></td>";
						echo "<td style='text-align:center'>$status</td>";
						echo "<td style='text-align: center'><font color='$cor_font'>$total_dias</font></td>";
					echo "</tr>";
				}

				$cria_linha = "";
				$total_dias = "";
			}?>
			</tbody>
			</table>
		<?}
		if (pg_num_rows($res_c)>0) {?>
			<table class='table table-hover table-striped table-bordered table-normal'>
				<caption class="titulo_tabela">Chamados Aguardando Aprova��o da Conclus�o</caption>
				<thead>
				<tr class='titulo_coluna'>
					<th>Chamado</th>
					<th>T�tulo</th>
					<th>Status</th>
					<th>Prazo para Resolu��o</th>
				</tr>
				</thead>
				<tbody>
			<?php
			for($i = 0; $i < pg_numrows($res_chamados); $i++){
				$hd_chamado           = pg_fetch_result($res_c,$i,'hd_chamado');
				$status               = pg_fetch_result($res_c,$i,'status');
				$titulo               = pg_fetch_result($res_c,$i,'titulo');
				$data                 = pg_fetch_result($res_c,$i,'data');
				$hora_desenvolvimento = pg_fetch_result($res_c,$i,'hora_desenvolvimento');

				$dias = 10;
				$cria_linha = 1;

				$sqlR = "SELECT data::date
						FROM tbl_hd_chamado_item
						JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
						WHERE hd_chamado = $hd_chamado
						AND tbl_hd_chamado_item.interno IS NOT TRUE
						AND tbl_hd_chamado_item.status_item = 'Concluido'
						ORDER BY hd_chamado_item DESC
						LIMIT 1";
				$resR = pg_query($con,$sqlR);

				if(pg_num_rows($resR) > 0){
					$data_status = pg_fetch_result($resR,0,0);

					$sqlS = "select fn_dias_uteis('$data_status + 1',$dias)";
					$resS = pg_query($con,$sqlS);
					$data_status = pg_fetch_result($resS,0,0);

					$sqlS = "SELECT '$data_status' - CURRENT_DATE";
					$resS = pg_query($con,$sqlS);

					$total_dias = pg_fetch_result($resS,0,0);
				}
			
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
				$cor_font = ($total_dias >= 3) ? "#000" : "#FF0000";

				if($cria_linha == 1){
					echo "<tr>";
						echo "<td>$hd_chamado</td>";
						echo "<td align='left'><a href='../helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado' target='_blank'>$titulo</a></td>";
						echo "<td style='text-align:center'>$status</td>";
						echo "<td style='text-align: center'><font color='$cor_font'>$total_dias</font></td>";
					echo "</tr>";
				}

				$cria_linha = "";
				$total_dias = "";
			}?>
			</tbody>
			</table>	
		<?	
		}

		$link = ($login_fabrica == 189 AND $login_privilegios == "*") ? "acompanhamento_atendimentos.php" : "menu_cadastro.php";
		?>
		<table class='table table-normal'>
			<tr>
				<td style='text-align:center;'>
						<br>
						<input type='button' class='btn'  value='Leio Depois' onclick="window.location='<?=$link?>'" style='cursor:pointer;' id='leio_depois'>
				</td>
			<tr>
		</table>
		</div>
	<?
	}
}
include "rodape.php";
