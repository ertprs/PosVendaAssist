<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';


	function cancelaHD($hd_chamado, $dias, $hora, $fabrica, $tipo_chamado,$tipo, $data_aux = NULL, $data_status = NULL){


		global $con;

		/*

		Tipos Chamado -> $tipo_chamado
		1 : Novo / Suspenso
		2 : Requisitos
		3 : Orçamento

		*/
		switch($tipo_chamado) {
			case 1 : $assunto = "Novo";break;
			case 2 : $assunto = "Requisitos";break;
			case 3 : $assunto = "Orçamento";break;
		}

		$texto = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
			      Prezado Cliente,<br><br><br>
				  O chamado $hd_chamado retornou para sua fila de chamados por falta de aprovação de requisitos e ou orçamento.<br><br>
				  Qualquer dúvida favor acessar o chat Telecontrol.<br><br>
				  Conforme regras Telecontrol chamados com requisitos ou orçamento tem um prazo de 10 dias úteis para aprovação.";

		// $texto = "O chamado <strong>$hd_chamado</strong> foi cancelado por falta de aprovação de <strong>$assunto</strong>. <br>
		// 		Qualquer dúvida favor acessar o chat.<br>
		// 		Conforme regras da telecontrol:
		// 		Chamados novos: 60 dias
		// 		Chamados Com requisitos ou Orçamento: 5 Dias.";

		/* Muda status do Chamado para 'Cancelado' */

		$sqlU = "UPDATE tbl_hd_chamado SET
						status = 'Novo',
						atendente = 435
					WHERE hd_chamado = $hd_chamado";
		$resU = pg_query($con,$sqlU);

		/* Insere um comentario sobre o cancelamento no Chamado */
		$sqlI = "INSERT INTO tbl_hd_chamado_item(hd_chamado, comentario, admin, interno) VALUES($hd_chamado, '$texto', 435, 'f')";
		$resI = pg_query($con,$sqlI);

		/*Envia e-mail para os admins*/
		$sqlM = "SELECT email FROM tbl_admin WHERE fabrica = $fabrica AND help_desk_supervisor IS TRUE ";
		$resM = pg_query($con,$sqlM);

		for($x = 0; $x < pg_num_rows($resM); $x++){
			$vet['dest'][$x] = pg_result($resM,$x,'email'); //Emails supervisor helpdesk
		}

		$sqlAdm = "SELECT admin,titulo FROM tbl_hd_chamado WHERE fabrica = $fabrica AND hd_chamado = $hd_chamado";
		$resAdm = pg_query($con,$sqlAdm);
		$admin 					= pg_result($resAdm,0,'admin');
		$titulo					= pg_result($resAdm,0,'titulo');

		$sqlM = "SELECT email FROM tbl_admin WHERE admin = $admin and fabrica = $fabrica";
		$resM = pg_query($con,$sqlM);
		$vet['dest'][] =  pg_result($resM,0,'email'); // Email Admin que abriu o chamado

		$titulo_email = "O chamado $hd_chamado retornou para fila de Aprovação";

		$data_atual = date("d/m/Y H:i:s");


		$msg = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

				Prezado Cliente,<br><br><br>
				O chamado $hd_chamado retornou para a sua fila de chamados devido a falta de aprovação requisitos e ou orçamento.<br><br>
				Qualquer dúvida favor acessar o chat Telecontrol.<br><br>
				Titulo: $titulo<br>
				Data de abertura: $data_aux<br>
				Data do $assunto: $data_status<br>
				Data de retorno: $data_atual<br>
				Tipo: $tipo<br><br>
				Telecontrol <br>
				www.telecontrol.com.br <br><br>";

		Log::envia_email($vet,$titulo_email,$msg);
	}

	/* Seleciona os Chamados */

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'helpdesk';
    $vet['log']     = 1;


	$sql = "SELECT  tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.fabrica,
					tbl_hd_chamado.admin,
					tbl_hd_chamado.status,
					tbl_hd_chamado.titulo,
					tbl_tipo_chamado.descricao AS tipo_chamado,
					tbl_hd_chamado.data::date AS data,
					tbl_hd_chamado.hora_desenvolvimento
			FROM tbl_hd_chamado
			JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
			WHERE tbl_hd_chamado.fabrica <> 10
			AND tbl_hd_chamado.tipo_chamado NOT IN(5,6)
			AND status IN('Requisitos','Orçamento')
			AND resolvido IS NULL
			AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
			ORDER BY hd_chamado DESC";

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$hoje = date("Y-m-d");
		$cancelados = "";

		for($i = 0; $i < pg_num_rows($res); $i++){

			/* Dados do Chamado */
			$hd_chamado 			= pg_result($res,$i,'hd_chamado');
			$fabrica 				= pg_result($res,$i,'fabrica');
			$admin 					= pg_result($res,$i,'admin');
			$status 				= pg_result($res,$i,'status');
			$titulo 				= pg_result($res,$i,'titulo');
			$tipo 					= pg_result($res,$i,'tipo_chamado');
			$data 					= pg_result($res,$i,'data');
			$hora_desenvolvimento 	= pg_result($res,$i,'hora_desenvolvimento');

			list($y,$m,$d) = explode("-",$data);
			$data_aux = "$d/$m/$y";

			$envia = "";

			/* Chamado com status Novo ou Suspenso */
			if($status == "Novo" OR $status == "Suspenso"){

				$dias = 60;
				$hora = 0;

				/* Se passou mais de 60 dias */
				if (strtotime($data.'+'.$dias.' days') < strtotime('today') ){

					/* Cancela o Chamado */
					// Mudança de regras Help-Desk Suporte
					//cancelaHD($hd_chamado,$dias,$hora,$fabrica, 1,$tipo);

					$envia = 2;
					$cancelados .= "$hd_chamado;$titulo;$admin;$fabrica;$status\n";
					$sqlD = "select (date '$data' + interval '$dias days')::date";
					$resD = pg_query($con,$sqlD);
					$data_fechamento = pg_result($resD,0,0);

					list($y,$m,$d) = explode("-",$data_fechamento);
					$data_fechamento = "$d/$m/$y";
				}
			}

			/* Chamado com status de Requisitos */
			elseif($status == "Requisitos"){

				$dias = 10;
				$hora = 1;

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

						$data_status = pg_result($resR,0,'data');
						$status_item = pg_result($resR,0,'status_item');

						$sqlS = "SELECT count(*) AS total_dias
									FROM fn_calendario(('$data_status'::date + 1),CURRENT_DATE)
									WHERE nome_dia not in ('Domingo','Sábado')";

						$resS = pg_query($con,$sqlS);

						$total_dias = pg_result($resS,0,'total_dias');

						$sqlD = "select fn_dias_uteis('$data_status',$dias)";
						$resD = pg_query($con,$sqlD);
						$data_fechamento = pg_result($resD,0,0);

						if ($total_dias > $dias AND $status_item == 'Ap.Requisitos'){

							list($y,$m,$d) = explode("-",$data_status);
							$data_status = "$d/$m/$y";
							//echo "chama funcao para cancela";
							/* Cancela o Chamado */
							// Mudança de regras Help-Desk Suporte
							//cancelaHD($hd_chamado,$dias,$hora,$fabrica, 2,$tipo,$data_aux,$data_status);

							$envia = 2;
							$cancelados .= "$hd_chamado;$titulo;$admin;$fabrica;$status\n";

						} else if($total_dias < $dias AND $status_item == 'Ap.Requisitos') {
							$envia = 1;
							list($y,$m,$d) = explode("-",$data_status);
							$data_status = "$d/$m/$y";

							$titulo_email = "Aprovação de Requisitos do chamado $hd_chamado";

							list($y,$m,$d) = explode("-",$data_fechamento);
							$data_cancelamento = "$d/$m/$y";

							$msg = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
									Prezado Cliente,<br><br><br>
									O chamado $hd_chamado necessita de sua aprovação dos requisitos para darmos continuidade.<br><br>
									Aguardamos aprovação dentro de 10 dias úteis para continuarmos o trabalho, qualquer dúvida favor acessar o chat Telecontrol.<br><br>
									Titulo: $titulo<br>
									Data de abertura: $data_aux<br>
									Data dos requisitos: $data_status<br>
									Tipo: $tipo";

							if(strtotime($hoje.' + 1 days') == strtotime($data_fechamento)){
								$msg .= "<br><br> <h2><b><font color='#FF0000'>ATENÇÃO : FALTA 1 DIA PARA O CANCELAMENTO DO CHAMADO</font></b></h2>";
							}

							if(strtotime($hoje) == strtotime($data_fechamento)){
								$msg .= "<br><br> <h2><b><font color='#FF0000'>ATENÇÃO : ÚLTIMO DIA PARA A APROVAÇÃO DO CHAMADO</font></b></h2>";
							}
						}
					}
				}

			/* Chamado com status de Orçamento */
			}else if($status == 'Orçamento'){
				$dias = 10;
				$hora = 2;
				if($hora_desenvolvimento > 0){
					$sqlR = "SELECT data::date
							FROM tbl_hd_chamado_item
							JOIN tbl_admin ON tbl_hd_chamado_item.admin = tbl_admin.admin
							WHERE hd_chamado = $hd_chamado
							AND tbl_hd_chamado_item.interno IS NOT TRUE
							ORDER BY hd_chamado_item DESC
							LIMIT 1";
					$resR = pg_query($con,$sqlR);

					if(pg_num_rows($resR) > 0){
						$data_status = pg_result($resR,0,0);

						$sqlS = "SELECT count(*) AS total_dias
									FROM fn_calendario(('$data_status'::date + 1),CURRENT_DATE)
									WHERE nome_dia not in ('Domingo','Sábado')";
						$resS = pg_query($con,$sqlS);

						$total_dias = pg_result($resS,0,'total_dias');

						$sqlD = "select fn_dias_uteis('$data_status',$dias)";
						$resD = pg_query($con,$sqlD);
						$data_fechamento = pg_result($resD,0,0);

						if ($total_dias > $dias ) {

							list($y,$m,$d) = explode("-",$data_status);
							$data_status = "$d/$m/$y";

							/* Cancela o Chamado */
							// Mudança de regras Help-Desk Suporte
							// cancelaHD($hd_chamado,$dias,$hora,$fabrica, 3,$tipo,$data_aux,$data_status);
							// cancelaHD($hd_chamado,$dias,$hora,$fabrica, 3);

							$envia = 2;
							$cancelados .= "$hd_chamado;$titulo;$admin;$fabrica;$status\n";

						} else {
							$envia = 1;

							list($y,$m,$d) = explode("-",$data_status);
							$data_status = "$d/$m/$y";

							$titulo_email = "Aprovação de Orçamento do chamado $hd_chamado";

							list($y,$m,$d) = explode("-",$data_fechamento);
							$data_cancelamento = "$d/$m/$y";

							$msg = "Prezado Cliente,<br><br><br>
									O chamado $hd_chamado necessita de aprovação do orçamento para darmos continuidade.<br><br>
									Aguardamos aprovação dentro de 10 dias úteis para continuarmos o trabalho, qualquer dúvida favor acessar o chat Telecontrol.<br><br>
									Titulo: $titulo<br>
									Data de abertura: $data_aux<br>
									Data do orçamento: $data_status<br>
									Tipo: $tipo";

							if(strtotime($hoje.' + 1 days') == strtotime($data_fechamento)){
								$msg .= "<br><br> <h2><b><font color='#FF0000'>ATENÇÃO : FALTA 1 DIA PARA O CANCELAMENTO DO CHAMADO</font></b></h2>";
							}

							if(strtotime($hoje) == strtotime($data_fechamento)){
								$msg .= "<br><br> <h2><b><font color='#FF0000'>ATENÇÃO : ÚLTIMO DIA PARA A APROVAÇÃO DO CHAMADO</font></b></h2>";
							}

							$msg .= "<br><br><P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
						NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>";


						}
					}

				}

			/* Chamado com status de Cancelado */
			}else if($status == 'Cancelado'){
				$sqlS = "SELECT tbl_hd_chamado_item.data
						FROM tbl_hd_chamado_item
						WHERE hd_chamado = $hd_chamado
						ORDER BY hd_chamado_item DESC LIMIT 1";
				$resS = pg_query($con,$sqlS);
				$data_aux = pg_result($resS,0,'data');

				if(strtotime($data_aux.'+ 1 month') < strtotime('today')){
				}
			}

			/* Envia email para o Admin*/
			$sqlM = "SELECT email FROM tbl_admin WHERE fabrica = $fabrica AND help_desk_supervisor IS TRUE ";
			$resM = pg_query($con,$sqlM);

			for($x = 0; $x < pg_num_rows($resM); $x++){
				$vet['dest'][$x] = pg_result($resM,$x,'email'); //Emails supervisor helpdesk
			}

			$sqlM = "SELECT email FROM tbl_admin WHERE admin = $admin and fabrica = $fabrica";
			$resM = pg_query($con,$sqlM);

			$vet['dest'][$x] =  pg_result($resM,0,'email'); // Email Admin que abriu o chamado

			if($envia == 1){				
				Log::envia_email($vet,$titulo_email,$msg);
			}
			//  e-mail está sendo enviado dentro da Função cacelaHD.
			// if($envia == 2){

			// 	list($y,$m,$d) = explode("-",$data_fechamento);
			// 	$data_fechamento = "$d/$m/$y";

			// 	list($y,$m,$d) = explode("-",$data_status);
			// 	$data_status = "$d/$m/$y";

			// 	$msg2 = "  O chamado $hd_chamado foi cancelado por falta de aprovação. <br>
			// 	Qualquer dúvida favor acessar o chat.<br><br>
			// 	Titulo: $titulo <br>";
			// 	if($status <> "Novo"){
			// 		$msg2 .= "Data de abertura: $data_aux <br>
			// 		Data do $status: $data_status<br>";
			// 	}
			// 	$msg2 .= "Data de cancelamento: $data_fechamento<br>
			// 	Tipo: $tipo<br><br>
			// 	<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
			// 			NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>";

			// 	if($status == "Novo"){
			// 		$status = "";
			// 	} else {
			// 		$status = " de ".$status;
			// 	}


			// 	$vetT['dest'][0] = 'thiago.tobias@telecontrol.com.br';
			// 	$vetT['dest'][1] = 'felipe.vaz@telecontrol.com.br';
			// 	Log::envia_email($vetT,utf8_encode($titulo_email),utf8_decode($msg2));

			// 	//Log::envia_email($vet,$titulo_email,$msg2);
			// }

			$vet['dest'] = array();

			if(!empty($cancelados)){
				$fp = fopen("/tmp/telecontrol/hd_chamados/chamados-cancelados-$hoje.txt","w");
				fwrite($fp,$cancelados);
				fclose($fp);
			}
		}
	}
	//Colocando chamado para Resolvido após 10 dias na fila do Admin
	$sqlC = "	SELECT  tbl_hd_chamado.hd_chamado,
						tbl_hd_chamado.fabrica,
						tbl_hd_chamado.admin,
						tbl_hd_chamado.status,
						tbl_hd_chamado.titulo,
						tbl_tipo_chamado.descricao AS tipo_chamado,
						tbl_hd_chamado.data::date AS data,
						tbl_hd_chamado.hora_desenvolvimento,
						tbl_hd_chamado.data_resolvido::date AS data_resolvido
				FROM tbl_hd_chamado
				JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				WHERE tbl_hd_chamado.fabrica <> 10
				AND status = 'Concluido'
				AND tbl_hd_chamado.data >= '2012-04-09 00:00:00'
				ORDER BY hd_chamado DESC";

	$resC = pg_query($con,$sqlC);


	if (pg_num_rows($resC)>0) {
		$hoje = date("Y-m-d");
		$cancelados = "";

		for ($i=0; $i <  pg_num_rows($resC) ; $i++) {
			/* Dados do Chamado */
			$hd_chamado 			= pg_result($resC,$i,'hd_chamado');
			$fabrica 				= pg_result($resC,$i,'fabrica');
			$admin 					= pg_result($resC,$i,'admin');
			$status 				= pg_result($resC,$i,'status');
			$titulo 				= pg_result($resC,$i,'titulo');
			$tipo 					= utf8_encode(pg_result($resC,$i,'tipo_chamado'));
			$data 					= pg_result($resC,$i,'data');
			$hora_desenvolvimento 	= pg_result($resC,$i,'hora_desenvolvimento');
			$data_resolvido 		= pg_result($resC,$i,'data_resolvido');

			list($y,$m,$d) = explode("-",$data);
			$data_aux = "$d/$m/$y";
			$dias = 10; //quantidade de dias úteis

			$sqlS = "SELECT count(*) AS total_dias
									FROM fn_calendario(('$data_resolvido'::date),CURRENT_DATE)
									WHERE nome_dia not in ('Domingo','Sábado')";
			$resS = pg_query($con,$sqlS);

			$total_dias = pg_result($resS,0,'total_dias');
			$sqlD = "select fn_dias_uteis('$data_resolvido',$dias)";
			$resD = pg_query($con,$sqlD);
			$data_fechamento = pg_result($resD,0,0);

			if ($total_dias > $dias ) {


				/* Muda o Status do chamado para Resolvido */
				$sqlUC = "UPDATE tbl_hd_chamado SET
								status = 'Resolvido',
								atendente = 435,
								resolvido = CURRENT_TIMESTAMP
							WHERE hd_chamado = $hd_chamado";
				$resUC = pg_query($con,$sqlUC);

				/* Insere um comentario sobre o cancelamento no Chamado */
				$texto = "<P align=left><STRONG>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****</STRONG> </P>
			      Prezado Cliente,<br><br><br>
				  O chamado <b>$hd_chamado</b> foi resolvido automaticamente pelo sistema <b>Help-Desk</b> após ter aguardado a confirmação do fabricante pelo prazo de <b>$dias dias úteis</b>.<br><br>
				  Qualquer dúvida favor acessar o Chat Telecontrol.<br><br>
				  Conforme regras Telecontrol chamados concluídos tem um prazo de <b>$dias dias úteis</b> para aprovação.";

				$texto = utf8_decode($texto);

				$sqlIC = "INSERT INTO tbl_hd_chamado_item(hd_chamado, comentario, admin, interno)
													VALUES($hd_chamado, '$texto', 435, 'f')";
				$resIC = pg_query($con,$sqlIC);

				/*Envia e-mail para os admins*/
				$sqlM = "SELECT email FROM tbl_admin WHERE fabrica = $fabrica AND help_desk_supervisor IS TRUE ";
				$resM = pg_query($con,$sqlM);

				if (pg_num_rows($resM)>0) {
					for($x = 0; $x < pg_num_rows($resM); $x++){
						$vetC['dest'][] = pg_fetch_result($resM,$x,'email'); //Emails supervisor helpdesk
					}
				}

				$sqlAdm = "SELECT admin,titulo FROM tbl_hd_chamado WHERE fabrica = $fabrica AND hd_chamado = $hd_chamado";
				$resAdm = pg_query($con,$sqlAdm);

				if (pg_num_rows($resAdm)>0) {
					for ($i=0; $i <  pg_num_rows($resAdm); $i++) {
						$admin 					= pg_fetch_result($resAdm,0,'admin');
						$titulo					= pg_fetch_result($resAdm,0,'titulo');

						$sqlM = "SELECT email FROM tbl_admin WHERE admin = $admin and fabrica = $fabrica";
						$resM = pg_query($con,$sqlM);
						for ($i=0; $i < pg_num_rows($resM); $i++) {
							$vetC['dest'][] =  pg_fetch_result($resM,0,'email'); // Email Admin que abriu o chamado
						}
					}
				}


				$titulo_email = "O chamado $hd_chamado foi Resolvido";

				$data_atual = date("d/m/Y H:i:s");

				$msg = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

						Prezado Cliente,<br><br><br>
						O chamado <b>$hd_chamado</b> foi resolvido automaticamente pelo sistema <b>Help-Desk</b> após ter aguardado a confirmação do fabricante pelo prazo de <b>$dias dias úteis</b>.<br><br>
						Qualquer dúvida favor acessar o Chat Telecontrol.<br><br>
						Conforme regras Telecontrol chamados concluídos tem um prazo de <b>$dias dias úteis</b> para aprovação.<br><br>
						Titulo: $titulo<br>
						Data de abertura:   $data_aux<br>
						Data de fechamento: $data_atual<br>
						Tipo: $tipo<br><br>
						Telecontrol <br>
						www.telecontrol.com.br <br><br>";
				Log::envia_email($vetC,$titulo_email,$msg);
			}
		}
	}

	if (date('w') == '1') {
			$sqlC = "	SELECT  tbl_hd_chamado.hd_chamado,
							tbl_hd_chamado.titulo,
							tbl_hd_chamado.data::date AS data,
							tbl_hd_chamado.status,
							tbl_fabrica.nome,
							tbl_admin.nome_completo,
							tbl_admin.fone as telefone,
							tbl_admin.email
					FROM tbl_hd_chamado
					JOIN tbl_tipo_chamado ON tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
					JOIN tbl_fabrica using(fabrica)
					JOIN tbl_admin using(admin)
					WHERE tbl_hd_chamado.fabrica <> 10
					AND status = 'Novo'
					AND tbl_hd_chamado.data > current_timestamp - interval '90 days'
					AND tbl_hd_chamado.data < current_timestamp		
					and ((current_timestamp - tbl_hd_chamado.data) > '10 days')			
					ORDER BY hd_chamado ASC";
					//AND length(trim(categoria)) = 0 retirado no chamado hd-2510246
		$resC = pg_query($con,$sqlC);

		if (pg_num_rows($resC)>0) {
			$hoje = date("Y-m-d");
			$msg = "Suporte, verificar os chamados seguintes que ainda não foram aprovados na última janela de aprovação - Status Novo<br>";
			$msg .= "<table align='center'>";
			$msg .="<tr><td>Chamado</td>
				<td>Titulo</td>
				<td>Data</td>
				<td>Fabricante</td>
				<td>Admin</td>
				<td>Telefone</td>
				<td>Status</td>
			</tr>";
			$msg = utf8_decode($msg);
			for ($i=0; $i <  pg_num_rows($resC) ; $i++) {
				/* Dados do Chamado */
				$hd_chamado 			= pg_result($resC,$i,'hd_chamado');
				$nome	 				= pg_result($resC,$i,'nome');
				$nome_completo			= pg_result($resC,$i,'nome_completo');
				$telefone 				= pg_result($resC,$i,'telefone');
				$email  				= pg_result($resC,$i,'email');
				$data 					= pg_result($resC,$i,'data');
				$titulo					= pg_result($resC,$i,'titulo');
				$status					= pg_result($resC,$i,'status');

				list($y,$m,$d) = explode("-",$data);
				$data_aux = "$d/$m/$y";

				$msg .="<tr><td>$hd_chamado</td>
				<td>$titulo</td>
				<td>$data_aux</td>
				<td>$nome</td>
				<td>$nome_completo</td>
				<td>$telefone</td>
				<td>$status<td>
				</tr>";
			}
			$msg .= "</table>";
			$vetC['dest'][] ='suprote.fabricantes@telecontrol.com.br';				
			Log::envia_email($vetC,'Chamados abertos sem aprovação do admin - Status Novo',$msg);
		}
	}
} catch (Exception $e) {
    echo $e->getMessage();
}
