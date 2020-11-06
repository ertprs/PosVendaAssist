<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	
	$data_inicial = $_REQUEST['data_inicial'];
	$data_final   = $_REQUEST['data_final'];
	$tipo         = $_REQUEST['tipo'];
	$grupo_admin  = $_REQUEST['grupo'];
	$admin        = $_REQUEST['admin'];

	switch($grupo_admin){
		case 2: $responsavel = 'analista';break;
		case 4: $responsavel = 'desenvolvedor';break;
		case 6: $responsavel = 'suporte';break;
	}

	/* CHAMADOS SEM CAUSADOR*/
	
	if($tipo == 1){
		$sql = "SELECT tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.titulo,
				tbl_fabrica.nome,
				TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
				TO_CHAR(tbl_hd_chamado.resolvido,'DD/MM/YYYY') AS resolvido
				FROM tbl_hd_chamado
				LEFT JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
				WHERE tbl_backlog_item.chamado_causador IS NULL
				AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
				AND tbl_hd_chamado.tipo_chamado = 5";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			
			$conteudo = "<table width='700' align='center' class='tabela' cellspancing='1' cellpadding='1'>
							<tr class='titulo_coluna'>
								<th>Nº Chamado</th>
								<th>Título</th>
								<th>Fábrica</th>
								<th>Data Abertura</th>
								<th>Resolvido</th>
							</tr>";
			for($i = 0; $i < pg_num_rows($res); $i++){

				$hd_chamado    = pg_result($res,$i,'hd_chamado');
				$titulo        = pg_result($res,$i,'titulo');
				$data_abertura = pg_result($res,$i,'data_abertura');
				$resolvido     = pg_result($res,$i,'resolvido');
				$fabrica       = pg_result($res,$i,'nome');
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$conteudo .= "<tr bgcolor='$cor'>
								<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a></td>
								<td>$titulo</td>
								<td>$fabrica</td>
								<td>$data_abertura</td>
								<td>$resolvido</td>
							  </tr>";

			}
			$conteudo .= "</table>";
		} else{
			$conteudo = "Nenhum resultado encontrado";
		}
	} else if($tipo == 2){
		
			$conteudo = "<table width='700' align='center' class='tabela' cellspancing='1' cellpadding='1'>
							<tr class='titulo_coluna'>
								<th>Grupo Responsável</th>
								<th>Qtde</th>
							</tr>";

		$sql = "SELECT tbl_grupo_admin.descricao,
				tbl_grupo_admin.grupo_admin,
				COUNT(tbl_hd_chamado.hd_chamado) AS qtde
				FROM tbl_hd_chamado
				JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado AND tbl_backlog_item.chamado_causador IS NOT NULL
				JOIN tbl_grupo_admin ON tbl_backlog_item.grupo_admin = tbl_grupo_admin.grupo_admin
				WHERE tbl_backlog_item.grupo_admin IN(2,4,6)
				AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
				GROUP BY tbl_grupo_admin.grupo_admin,tbl_grupo_admin.descricao";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			
			for($i = 0; $i < pg_num_rows($res); $i++){
				
				$grupo_admin  = pg_result($res,$i,'grupo_admin');
				$descricao    = pg_result($res,$i,'descricao');
				$qtde         = pg_result($res,$i,'qtde');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$conteudo .= "<tr bgcolor='$cor'>
								<td><a href='javascript: void(0);' onclick=\"listaChamadoGrupo('3','$grupo_admin','$data_inicial','$data_final','$i');\">$descricao</a></td>
								<td align='center'>$qtde</td>
							  </tr>
							  
							  <tr id='linha_".$grupo_admin."_".$i."' style='display:none;'>
								<td id='coluna_".$grupo_admin."_".$i."' colspan='2'></td>
							  </tr>";

			}
			$conteudo .= "</table>";
		} else{
			$conteudo = "Nenhum resultado encontrado";
		}
	} else if($tipo == 3){
		
		$sql = "SELECT COUNT(tbl_backlog_item.chamado_causador) AS qtde,
						tbl_admin.login,
						tbl_admin.admin
					FROM tbl_backlog_item
					JOIN tbl_admin ON tbl_backlog_item.$responsavel = tbl_admin.admin
					WHERE tbl_backlog_item.hd_chamado IN(
					SELECT tbl_hd_chamado.hd_chamado
					FROM tbl_hd_chamado
					JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado AND tbl_backlog_item.chamado_causador IS NOT NULL
					WHERE tbl_backlog_item.grupo_admin = $grupo_admin
					AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
					)
					GROUP BY tbl_admin.login, tbl_admin.admin
					ORDER BY COUNT(tbl_backlog_item.chamado_causador), tbl_admin.login";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			
			$conteudo = "<table width='700' align='center' class='tabela' cellspancing='1' cellpadding='1'>
									<tr class='titulo_coluna'>
										<th>Responsável</th>
										<th>Total</th>
									</tr>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				
				$qtde  = pg_result($res,$i,'qtde');
				$login = pg_result($res,$i,'login');
				$admin = pg_result($res,$i,'admin');

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$conteudo .= "<tr bgcolor='$cor'>
								<td>
									<a href='javascript: void(0);' onclick=\"listaChamadoAdmin('4','$admin','$grupo_admin','$data_inicial','$data_final','$i')\">$login</a>
								</td>
								<td>$qtde</td>
							  </tr>
							  
							  <tr id='linha_".$admin."_".$i."' style='display:none;'>
								<td id='coluna_".$admin."_".$i."' colspan='2'></td>
							  </tr>";
			}
			$conteudo .= "</table>";
		}

	}else if($tipo == 4){
		
		$sql = "SELECT tbl_backlog_item.chamado_causador AS chamado,
						tbl_backlog_item.hd_chamado AS chamado_origem,
						tbl_admin.login AS responsavel,
						tbl_hd_chamado.titulo,
						tbl_fabrica.nome AS fabrica,
						TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_hd_chamado.resolvido,'DD/MM/YYYY') AS resolvido
						FROM tbl_backlog_item
						JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.chamado_causador
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
						JOIN tbl_admin ON tbl_backlog_item.$responsavel = tbl_admin.admin
						WHERE tbl_backlog_item.hd_chamado IN(
						SELECT tbl_hd_chamado.hd_chamado
						FROM tbl_hd_chamado
						JOIN tbl_backlog_item ON tbl_hd_chamado.hd_chamado = tbl_backlog_item.hd_chamado AND tbl_backlog_item.chamado_causador IS NOT NULL
						WHERE tbl_backlog_item.grupo_admin = $grupo_admin
						AND tbl_backlog_item.$responsavel = $admin
						AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
						)";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			
			$conteudo = "<table width='700' align='center' class='tabela' cellspancing='1' cellpadding='1'>
							<tr class='titulo_coluna'>
								<th>Chamado Causador</th>
								<th>Responsável</th>
								<th>Título</th>
								<th>Fábrica</th>
								<th>Data Abertura</th>
								<th>Resolvido</th>
								<th>Novo Chamado</th>
							</tr>";

			for($i = 0; $i < pg_num_rows($res); $i++){
				
				$hd_chamado     = pg_result($res,$i,'chamado');
				$chamado_origem = pg_result($res,$i,'chamado_origem');
				$titulo         = pg_result($res,$i,'titulo');
				$data_abertura  = pg_result($res,$i,'data_abertura');
				$resolvido      = pg_result($res,$i,'resolvido');
				$fabrica        = pg_result($res,$i,'fabrica');
				$resp           = pg_result($res,$i,'responsavel');
				
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$conteudo .= "<tr bgcolor='$cor'>
								<td>
								  <a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a>
								</td>
								<td>$resp</td>
								<td>$titulo</td>
								<td>$fabrica</td>
								<td>$data_abertura</td>
								<td>$resolvido</td>
								<td>
								  <a href='adm_chamado_detalhe.php?hd_chamado=$chamado_origem'>$chamado_origem</a>
								</td>
							  </tr>";

			}

			$conteudo .= "</table>";
			
		}
		
	}

	echo $conteudo; exit;

	/* CHAMADOS SEM CAUSADOR*/