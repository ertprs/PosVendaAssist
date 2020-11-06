<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

$jsonPOST = excelPostToJson($_POST);


if ($_POST["btn_acao"] == "submit" ) {
	$data_inicial	= $_REQUEST["data_inicial"];
    $data_final     = $_REQUEST["data_final"];
    $atendente      = $_REQUEST["atendente"];

    if($login_fabrica == 162){
    	$classificacao = $_REQUEST["classificacao"];
    	$origem = $_REQUEST["origem"];

    	$style_162 = "style='display:none;'";
    }

    if (!strlen($data_inicial) or !strlen($data_final)) {
		$data_inicial = date('dd/mm/yyyy');
		$data_final   = date('dd/mm/yyyy');
	}

	list($di, $mi, $yi) = explode("/", $data_inicial);
	list($df, $mf, $yf) = explode("/", $data_final);

	if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	} else {
		$aux_data_inicial = "{$yi}-{$mi}-{$di}";
		$aux_data_final   = "{$yf}-{$mf}-{$df}";

		if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
			$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
			$msg_erro["campos"][] = "data_inicial";
			$msg_erro["campos"][] = "data_final";
		}

		if ($login_fabrica == 11 or $login_fabrica == 172) {
			if (strtotime($aux_data_inicial.'+3 months') < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior do que 3 meses";
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		} else {
			if (strtotime($aux_data_inicial.'+1 year') < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior do que 1 ano";
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		}
	}

	if (!count($msg_erro["msg"])) {
		$categorias        = array("reclamacao_produto", "reclamacao_empresa", "reclamacao_at");
		$outras_categorias = array("duvida_produto", "sugestao", "procon", "onde_comprar", "informacoes");
		$chamados          = array();

		if (!empty($atendente)) {
			$where_admin = " AND tbl_admin.admin = $atendente ";
		}

		if($login_fabrica == 162){
			if(strlen(trim($classificacao)) > 0){
				$cond_162_classificacao = "AND tbl_hd_chamado.hd_classificacao = $classificacao ";
			}

			if(strlen(trim($origem)) > 0){
				$cond_162_origem = "AND tbl_hd_chamado_extra.origem = '$origem'";
			}
		}


		if($login_fabrica == 74){

			$tipo = "producao"; // teste - producao

			$admin_fale_conosco = ($tipo == "producao") ? 6409 : 6437;

			$cond_admin_fale_conosco = " AND tbl_admin.admin NOT IN ($admin_fale_conosco) ";

		}

		$sql = "SELECT DISTINCT tbl_admin.admin
				FROM tbl_admin
				WHERE tbl_admin.fabrica = {$login_fabrica}
				AND tbl_admin.ativo IS TRUE
				AND (tbl_admin.privilegios LIKE '%call_center%' OR tbl_admin.privilegios LIKE '*')
				$cond_admin_fale_conosco
				$where_admin";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$admin_sql_array  = array();

			for ($i = 0; pg_num_rows($res) > $i; $i++) {
				$admin_sql_array[]  = pg_fetch_result($res, $i, "admin");
				$admin = pg_fetch_result($res, $i, "admin");

				foreach ($categorias as $categoria) {
					/*
					INICIO ADMIN CHAMADOS ABERTOS

					quantos chamados o admin abriu dentro do periodo pesquisado e esta em aberto para tal categoria
					*/
					$sql_chamados = "SELECT
										COUNT(tbl_hd_chamado.hd_chamado) AS qtde_chamados
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
									JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
									AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
									$cond_162_classificacao
									$cond_162_origem
									";
									if ($login_fabrica <> 74){
									$sql_chamados .=  " AND UPPER(tbl_hd_chamado.status) <> 'RESOLVIDO' ";
									}
									$sql_chamados .= "
									AND tbl_hd_chamado.admin = {$admin}
									AND tbl_hd_chamado.categoria = '{$categoria}'
									AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
					$res_chamados = pg_query($con, $sql_chamados);
					if (pg_num_rows($res_chamados) > 0) {
						$chamados[$admin][$categoria]["abertos"] = pg_fetch_result($res_chamados, 0, "qtde_chamados");
					}
					/*
					FIM ADMIN CHAMADOS ABERTOS
					*/

					/*
					INICIO ADMIN INTERAÇÕES

					quantas interações o admin fez no periodo pesquisado com chamados de tal categoria
					*/

					if(in_array($login_fabrica,array(11,74,172))){
						$sql_cond = " AND tbl_hd_chamado_item.interno IS FALSE ";
					}

					$sql_chamados = "SELECT
										COUNT(tbl_hd_chamado_item.hd_chamado_item) AS qtde_interacoes
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
									JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
									AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
									AND tbl_hd_chamado_item.admin = {$admin}
									AND tbl_hd_chamado.categoria = '{$categoria}'
									$sql_cond
									$cond_162_classificacao
									$cond_162_origem
									AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
					$res_chamados = pg_query($con, $sql_chamados);

					if (pg_num_rows($res_chamados) > 0) {
						$chamados[$admin][$categoria]["interacoes"] = pg_fetch_result($res_chamados, 0, "qtde_interacoes");
					}
					/*
					FIM ADMIN INTERAÇÕES
					*/

					/*
					INICIO ADMIN CHAMADOS RESOLVIDOS

					quantos chamados o admin resolveu dentro do periodo pesquisado de tal categoria
					*/
					$sql_chamados = "SELECT
										COUNT(DISTINCT tbl_hd_chamado_item.hd_chamado) AS qtde_resolvidos
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
									JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
									WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
									AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
									AND tbl_hd_chamado_item.admin = {$admin}
									AND UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
									AND UPPER(tbl_hd_chamado_item.status_item) = 'RESOLVIDO'
									AND tbl_hd_chamado.categoria = '{$categoria}'
									AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)
									$cond_162_classificacao
									$cond_162_origem";
					$res_chamados = pg_query($con, $sql_chamados);

					if (pg_num_rows($res_chamados) > 0) {
						$chamados[$admin][$categoria]["resolvidos"] = pg_fetch_result($res_chamados, 0, "qtde_resolvidos");
					}
					/*
					FIM ADMIN CHAMADOS RESOLVIDOS
					*/
				}

				foreach ($outras_categorias as $categoria) {
					$sql_chamados = "SELECT
										COUNT(tbl_hd_chamado.hd_chamado) AS qtde_chamados
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
									JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
									AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
									AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
									AND tbl_hd_chamado.admin = {$admin}
									AND tbl_hd_chamado.categoria = '{$categoria}'
									$cond_162_classificacao
									$cond_162_origem
									AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
					$res_chamados = pg_query($con, $sql_chamados);

					if (pg_num_rows($res_chamados) > 0) {
						$chamados[$admin][$categoria] = pg_fetch_result($res_chamados, 0, "qtde_chamados");
					}
				}
			}

			/*
			INICIO CALCULO DOS TOTAIS
			*/
			if (count($chamados) > 0) {
				foreach ($chamados as $admin => $admin_array) {
					$total_abertos    = 0;
					$total_interacoes = 0;
					$total_resolvidos = 0;
					$total_geral      = 0;

					foreach ($admin_array as $categoria => $categoria_array) {
						if (in_array($categoria, $outras_categorias)) {
							$total_geral += $categoria_array;

							continue;
						}

						$total_categoria = 0;

						foreach ($categoria_array as $tipo) {
							$total_categoria += (int) $tipo;
						}

						$chamados[$admin][$categoria]["total"] = $total_categoria;

						$total_abertos    += (int) $categoria_array["abertos"];
						$total_interacoes += (int) $categoria_array["interacoes"];
						$total_resolvidos += (int) $categoria_array["resolvidos"];

						$total_geral += $total_categoria;
					}

					if ($total_geral == 0) {
						unset($chamados[$admin]);
					} else {
						$chamados[$admin]["totais"] = array(
							"abertos"    => $total_abertos,
							"interacoes" => $total_interacoes,
							"resolvidos" => $total_resolvidos,
							"geral"      => $total_geral
						);
					}
				}
			}
			/*
			FIM CALCULO DOS TOTAIS
			*/
		}

		$chamados_estados = array();

		$admin_sql_array = implode(", ", $admin_sql_array);

		$estado_array	= array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO", "MA",
			"MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR", "RJ", "RN", "RO", "RR", "RS",
			"SC", "SE", "SP", "TO"
		);

		foreach ($estado_array as $estado) {
			foreach ($categorias as $categoria) {
				/*
				INICIO ADMIN CHAMADOS ABERTOS

				quantos chamados o admin abriu dentro do periodo pesquisado e esta em aberto para tal categoria
				*/
				$sql_chamados = "SELECT
									COUNT(tbl_hd_chamado.hd_chamado) AS qtde_chamados
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
								AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
								AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
								$cond_162_classificacao
								$cond_162_origem";
								if ($login_fabrica <> 74){
									$sql_chamados .=  " AND UPPER(tbl_hd_chamado.status) <> 'RESOLVIDO' ";
								}
								$sql_chamados .= "
								AND tbl_hd_chamado.categoria = '{$categoria}'
								AND UPPER(tbl_cidade.estado) = '{$estado}'
								AND tbl_hd_chamado.admin IN ({$admin_sql_array})
								AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
				$res_chamados = pg_query($con, $sql_chamados);

				if (pg_num_rows($res_chamados) > 0) {
					$chamados_estados[$estado][$categoria]["abertos"] = pg_fetch_result($res_chamados, 0, "qtde_chamados");
				}
				/*
				FIM ADMIN CHAMADOS ABERTOS
				*/

				/*
				INICIO ADMIN INTERAÇÕES

				quantas interações o admin fez no periodo pesquisado com chamados de tal categoria
				*/

				if($login_fabrica == 74){
					$sql_cond = " AND tbl_hd_chamado_item.interno IS FALSE ";
				}

				$sql_chamados = "SELECT
									COUNT(tbl_hd_chamado_item.hd_chamado_item) AS qtde_interacoes
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
								AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
								AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
								AND tbl_hd_chamado.categoria = '{$categoria}'
								AND UPPER(tbl_cidade.estado) = '{$estado}'
								AND tbl_hd_chamado_item.admin IN ({$admin_sql_array})
								$sql_cond
								$cond_162_classificacao
									$cond_162_origem
								AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
				$res_chamados = pg_query($con, $sql_chamados);

				if (pg_num_rows($res_chamados) > 0) {
					$chamados_estados[$estado][$categoria]["interacoes"] = pg_fetch_result($res_chamados, 0, "qtde_interacoes");
				}
				/*
				FIM ADMIN INTERAÇÕES
				*/

				/*
				INICIO ADMIN CHAMADOS RESOLVIDOS

				quantos chamados o admin resolveu dentro do periodo pesquisado de tal categoria
				*/
				$sql_chamados = "SELECT
									COUNT(DISTINCT tbl_hd_chamado_item.hd_chamado) AS qtde_resolvidos
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
								WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
								AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
								AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
								AND UPPER(tbl_hd_chamado.status) = 'RESOLVIDO'
								AND UPPER(tbl_hd_chamado_item.status_item) = 'RESOLVIDO'
								AND tbl_hd_chamado.categoria = '{$categoria}'
								AND UPPER(tbl_cidade.estado) = '{$estado}'
								AND tbl_hd_chamado_item.admin IN ({$admin_sql_array})
								$cond_162_classificacao
									$cond_162_origem
								AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
				$res_chamados = pg_query($con, $sql_chamados);

				if (pg_num_rows($res_chamados) > 0) {
					$chamados_estados[$estado][$categoria]["resolvidos"] = pg_fetch_result($res_chamados, 0, "qtde_resolvidos");
				}
				/*
				FIM ADMIN CHAMADOS RESOLVIDOS
				*/
			}

			foreach ($outras_categorias as $categoria) {
				$sql_chamados = "SELECT
									COUNT(tbl_hd_chamado.hd_chamado) AS qtde_chamados
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
								JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
								WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
								AND tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
								AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
								AND tbl_hd_chamado.categoria = '{$categoria}'
								AND UPPER(tbl_cidade.estado) = '{$estado}'
								AND tbl_hd_chamado.admin IN ({$admin_sql_array})
								$cond_162_classificacao
									$cond_162_origem
								AND (tbl_hd_chamado_extra.posto <> 6359 OR tbl_hd_chamado_extra.posto IS NULL)";
				$res_chamados = pg_query($con, $sql_chamados);

				if (pg_num_rows($res_chamados) > 0) {
					$chamados_estados[$estado][$categoria] = pg_fetch_result($res_chamados, 0, "qtde_chamados");
				}
			}
		}

		/*
		INICIO CALCULO DOS TOTAIS
		*/
		if (count($chamados_estados) > 0) {
			foreach ($chamados_estados as $estado => $estado_array) {
				$total_abertos    = 0;
				$total_interacoes = 0;
				$total_resolvidos = 0;
				$total_geral      = 0;

				foreach ($estado_array as $categoria => $categoria_array) {
					if (in_array($categoria, $outras_categorias)) {
						$total_geral += $categoria_array;

						continue;
					}

					$total_categoria = 0;

					foreach ($categoria_array as $tipo) {
						$total_categoria += (int) $tipo;
					}

					$chamados_estados[$estado][$categoria]["total"] = $total_categoria;

					$total_abertos    += (int) $categoria_array["abertos"];
					$total_interacoes += (int) $categoria_array["interacoes"];
					$total_resolvidos += (int) $categoria_array["resolvidos"];

					$total_geral += $total_categoria;
				}

				if ($total_geral == 0) {
					unset($chamados_estados[$estado]);
				} else {
					$chamados_estados[$estado]["totais"] = array(
						"abertos"    => $total_abertos,
						"interacoes" => $total_interacoes,
						"resolvidos" => $total_resolvidos,
						"geral"      => $total_geral
					);
				}
			}
		}
		/*
		FIM CALCULO DOS TOTAIS
		*/
	}
}

	if ( $_POST['gerar_excel'] == 'true' ) {

		$data = date("d-m-Y-H:i");
		$fileName = "relatorio_callcenter-{$data}.xls";
		$file = fopen("/tmp/{$fileName}", "w");

	fwrite($file, "
		<table id='resultado_atendimento' align='center' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' id='status' >
						<th>&nbsp;</th>
						<th>Produto/Defeito</th>
						<th>Recl. Empresa</th>
						<th>Recl. A.T</th>
						<th>Totais</th>
						<th>Contatos</th>
						<th>&nbsp;</th>
				</tr>
				<tr  id='status'>
					<td  bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF'>Atendente</td> ");


						for($j = 0; $j < 3; $j++){
						fwrite($file, "
							<TD>
							<table>
							<TR>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Abertos</TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Acomp.</TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Fechados</TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Total</TD>
							</TR>
							</table>
							</TD> ");
						}
						fwrite($file, "
						<TD>
							<table>
							<TR>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Abertos</TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Acomp.</TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Fechados</TD>
							</TR>
							</table>
						</TD>
						<TD>
							<table>
							<TR>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Dúvida Prod.</span></TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Sugestão</span></TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Procon</span></TD>
							<TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Onde Comprar</span></TD>");
						if($login_fabrica <> 162){
							fwrite($file, " <TD bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important' >Informações</span></TD> ");
						}
						fwrite($file, "
							</TR>
							</table>
						</TD>
						<TD>
							<table>
							<TR>
							<TD  bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Total Geral</span></TD>
							</TR>
							</table>
						</TD>
					</tr>
			</thead>
			<tbody>
			");
				foreach ($chamados as $admin => $admin_array) {
					$sql_login = "SELECT login FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin";
					$res_login = pg_query($con, $sql_login);

					$login = pg_fetch_result($res_login, 0, "login");

				fwrite($file, "
					<tr class='chamados_admin' >
						<td>{$login}</td>
						");
						foreach ($admin_array as $categoria => $categoria_array) {
							if (in_array($categoria, $outras_categorias) || $categoria == "totais") {
								continue;
							}
						fwrite($file, "
							<td>
								<table>
									<tr>
										<TD class='tac resultados' nowrap>{$categoria_array['abertos']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['interacoes']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['resolvidos']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['total']}</TD>
									</tr>
								</table>
							</td>
						");


							if ($categoria == 'reclamacao_produto'){
								$soma_resultados['reclamacao_produto']['abertos']		+= $categoria_array['abertos'];
								$soma_resultados['reclamacao_produto']['interacoes']	+= $categoria_array['interacoes'];
								$soma_resultados['reclamacao_produto']['resolvidos']	+= $categoria_array['resolvidos'];
								$soma_resultados['reclamacao_produto']['total'] 		+= $categoria_array['total'];
							}
							if ($categoria == 'reclamacao_empresa'){
								$soma_resultados['reclamacao_empresa']['abertos']		+= $categoria_array['abertos'];
								$soma_resultados['reclamacao_empresa']['interacoes']	+= $categoria_array['interacoes'];
								$soma_resultados['reclamacao_empresa']['resolvidos']	+= $categoria_array['resolvidos'];
								$soma_resultados['reclamacao_empresa']['total'] 		+= $categoria_array['total'];
							}
							if ($categoria == 'reclamacao_at'){
								$soma_resultados['reclamacao_at']['abertos']			+= $categoria_array['abertos'];
								$soma_resultados['reclamacao_at']['interacoes']			+= $categoria_array['interacoes'];
								$soma_resultados['reclamacao_at']['resolvidos']			+= $categoria_array['resolvidos'];
								$soma_resultados['reclamacao_at']['total'] 				+= $categoria_array['total'];
							}
						}
						//var_dump($soma_resultados);
					fwrite($file, "
						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>{$admin_array['totais']['abertos']}</TD>
							  		<TD class='tac resultados' nowrap>{$admin_array['totais']['interacoes']}</TD>
							  		<TD class='tac resultados' nowrap>{$admin_array['totais']['resolvidos']}</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>{$admin_array['duvida_produto']}</TD>
							  		<TD class='tac resultados' nowrap>{$admin_array['sugestao']}</TD>
							  		<TD class='tac resultados' nowrap>{$admin_array['procon']}</TD>
							  		<TD class='tac resultados' nowrap>{$admin_array['onde_comprar']}</TD>");
					if($login_fabrica <> 162){
						fwrite($file, " <TD class='tac resultados' nowrap>{$admin_array['informacoes']}</TD>");
					}
					fwrite($file, "
							  	</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>{$admin_array['totais']['geral']}</TD>
								</tr>
							</table>
						</td>

					</tr>
				");


					$total_admin['totais']['abertos']  		+= $admin_array['totais']['abertos'];
					$total_admin['totais']['interacoes']  	+= $admin_array['totais']['interacoes'];
					$total_admin['totais']['resolvidos']  	+= $admin_array['totais']['resolvidos'];
					$total_admin['duvida_produto']  		+= $admin_array['duvida_produto'];
					$total_admin['sugestao']  				+= $admin_array['sugestao'];
					$total_admin['procon']  				+= $admin_array['procon'];
					$total_admin['onde_comprar']  			+= $admin_array['onde_comprar'];
					$total_admin['informacoes']  			+= $admin_array['informacoes'];
					$total_admin['totais']['geral']  		+= $admin_array['totais']['geral'];
				}
		fwrite($file, "
				<tr class='total_admin' >
					<td>Total</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$soma_resultados['reclamacao_produto']['abertos']}</td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_produto']['interacoes']}</td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_produto']['resolvidos']}</td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_produto']['total']}</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$soma_resultados['reclamacao_empresa']['abertos']}  </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_empresa']['interacoes']}  </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_empresa']['resolvidos']}  </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_empresa']['total']}  </td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$soma_resultados['reclamacao_at']['abertos'] } </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_at']['interacoes']} </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_at']['resolvidos'] } </td>
								<td class='tac resultados'>{$soma_resultados['reclamacao_at']['total'] } </td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$total_admin['totais']['abertos']} </td>
								<td class='tac resultados'>{$total_admin['totais']['interacoes']} </td>
								<td class='tac resultados'>{$total_admin['totais']['resolvidos']} </td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$total_admin['duvida_produto']}</td>
								<td class='tac resultados'>{$total_admin['sugestao'] }</td>
								<td class='tac resultados'>{$total_admin['procon']} </td>
								<td class='tac resultados'>{$total_admin['onde_comprar']}</td>");
					if($login_fabrica <> 162){
						fwrite($file, " <td class='tac resultados'>{$total_admin['informacoes']}</td> ");
					}
					fwrite($file, "
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$total_admin['totais']['geral']}</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td colspan='7'>&nbsp;</tr></tr>
				");

				foreach ($chamados_estados as $estado => $estado_array) {
					fwrite($file, "

						<tr class='chamados_estado' >
							<td>{$estado}</td>
					");
						foreach ($estado_array as $categoria => $categoria_array) {
							if (in_array($categoria, $outras_categorias) || $categoria == "totais") {
								continue;
							}
				fwrite($file, "
							<td>
								<table>
									<tr>
										<TD class='tac resultados' nowrap>{$categoria_array['abertos']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['interacoes']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['resolvidos']}</TD>
								  		<TD class='tac resultados' nowrap>{$categoria_array['total']}</TD>
									</tr>
								</table>
							</td>
						");
								if ($categoria == 'reclamacao_produto'){
								$estado_total['reclamacao_produto']['abertos']		+= $categoria_array['abertos'];
								$estado_total['reclamacao_produto']['interacoes']	+= $categoria_array['interacoes'];
								$estado_total['reclamacao_produto']['resolvidos']	+= $categoria_array['resolvidos'];
								$estado_total['reclamacao_produto']['total'] 		+= $categoria_array['total'];
							}
							if ($categoria == 'reclamacao_empresa'){
								$estado_total['reclamacao_empresa']['abertos']		+= $categoria_array['abertos'];
								$estado_total['reclamacao_empresa']['interacoes']	+= $categoria_array['interacoes'];
								$estado_total['reclamacao_empresa']['resolvidos']	+= $categoria_array['resolvidos'];
								$estado_total['reclamacao_empresa']['total'] 		+= $categoria_array['total'];
							}
							if ($categoria == 'reclamacao_at'){
								$estado_total['reclamacao_at']['abertos']			+= $categoria_array['abertos'];
								$estado_total['reclamacao_at']['interacoes']		+= $categoria_array['interacoes'];
								$estado_total['reclamacao_at']['resolvidos']		+= $categoria_array['resolvidos'];
								$estado_total['reclamacao_at']['total'] 			+= $categoria_array['total'];
							}
						}
					fwrite($file, "
						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap> {$estado_array['totais']['abertos']}</TD>
							  		<TD class='tac resultados' nowrap> {$estado_array['totais']['interacoes']}</TD>
							  		<TD class='tac resultados' nowrap> {$estado_array['totais']['resolvidos']}</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap> {$estado_array['duvida_produto']}</TD>
							  		<TD class='tac resultados' nowrap> {$estado_array['sugestao']}</TD>
							  		<TD class='tac resultados' nowrap> {$estado_array['procon']}</TD>
							  		<TD class='tac resultados' nowrap> {$estado_array['onde_comprar']}</TD>");
						if($login_fabrica <> 162){
							fwrite($file, "<TD class='tac resultados' nowrap> {$estado_array['informacoes']}</TD>");
						}
				  		fwrite($file, "
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap> {$estado_array['totais']['geral']}</TD>
								</tr>
							</table>
						</td>

					</tr>
					");
							$totais_estado['totais']['abertos']		+=	$estado_array['totais']['abertos'];
							$totais_estado['totais']['interacoes']	+=	$estado_array['totais']['interacoes'];
							$totais_estado['totais']['resolvidos']	+=	$estado_array['totais']['resolvidos'];
							$totais_estado['duvida_produto']		+=	$estado_array['duvida_produto'];
							$totais_estado['sugestao']				+=	$estado_array['sugestao'];
							$totais_estado['procon']				+=	$estado_array['procon'];
							$totais_estado['onde_comprar']			+=	$estado_array['onde_comprar'];
							$totais_estado['informacoes']			+=	$estado_array['informacoes'];
							$totais_estado['totais']['geral']		+=	$estado_array['totais']['geral'];
				}
				fwrite($file, "
				<tr class='total_estado' >
					<td>Total</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$estado_total['reclamacao_produto']['abertos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_produto']['interacoes']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_produto']['resolvidos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_produto']['total']}</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$estado_total['reclamacao_empresa']['abertos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_empresa']['interacoes']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_empresa']['resolvidos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_empresa']['total']}</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$estado_total['reclamacao_at']['abertos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_at']['interacoes']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_at']['resolvidos']}</td>
								<td class='tac resultados'>{$estado_total['reclamacao_at']['total']}</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$totais_estado['totais']['abertos']}</td>
								<td class='tac resultados'>{$totais_estado['totais']['interacoes']}</td>
								<td class='tac resultados'>{$totais_estado['totais']['resolvidos']}</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$totais_estado['duvida_produto']}</td>
								<td class='tac resultados'>{$totais_estado['sugestao']}</td>
								<td class='tac resultados'>{$totais_estado['procon']}</td>
								<td class='tac resultados'>{$totais_estado['onde_comprar']}</td>");
					if($login_fabrica <> 162){
						fwrite($file, "<td class='tac resultados'>{$totais_estado['informacoes']}</td>");
					}
					fwrite($file, "
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'>{$totais_estado['totais']['geral']}</td>
							</tr>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
	");
	fclose($file);
	if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");
		// mv xls xls2
		// mkdir -m 777 xls
        // devolve para o ajax o nome doa rquivo gerado

        echo "xls/{$fileName}";
    }
	exit;
}




$layout_menu = "callcenter";
$title = "RELATÓRIO DE PRODUTIVIDADE";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask"
);

include("plugin_loader.php");

$inputs = array(
	"data_inicial" => array(
		"span"     => 4,
		"label"    => "Data Inicial",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"data_final" => array(
		"span"     => 4,
		"label"    => "Data Final",
		"type"     => "input/text",
		"width"    => 5,
		"required" => true
	),
	"atendente" => array(
		"span"    => 4,
		"label"   => "Atendente",
		"type"    => "select",
		"width"   => 5,
		"options" => array()
	),
);
if($login_fabrica == 162){
	$inputs_162 = array(
		"classificacao" => array(
			"span"    => 4,
			"label"   => "Classificação",
			"type"    => "select",
			"width"   => 5,
			"options" => array()
		),
		"origem" => array(
			"span"    => 4,
			"label"   => "Origem",
			"type"    => "select",
			"width"   => 5,
			"options" => array()
		)
	);

	$inputs = array_merge($inputs, $inputs_162);
}
$sql = "SELECT admin, login
		FROM tbl_admin
		WHERE fabrica = {$login_fabrica}
		AND ativo IS TRUE
		AND (privilegios LIKE '%call_center%' OR privilegios LIKE '*')
		ORDER BY login";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
	for ($i = 0; pg_num_rows($res) > $i; $i++) {
		$atend          = pg_fetch_result($res, $i, 'admin');
		$atendente_nome = pg_fetch_result($res, $i, 'login');

		$inputs['atendente']['options'][$atend] = $atendente_nome;
	}
}

if($login_fabrica == 162){
	$sql_classif = "SELECT hd_classificacao, descricao
						FROM tbl_hd_classificacao
						WHERE fabrica = $login_fabrica
						AND ativo IS TRUE
						ORDER BY descricao ASC";
	$res_classif = pg_query($con, $sql_classif);

	if(pg_num_rows($res_classif) > 0){
		for ($j=0; $j < pg_num_rows($res_classif); $j++) {
			$id_classif = pg_fetch_result($res_classif, $j, "hd_classificacao");
			$descricao_classif = pg_fetch_result($res_classif, $j, "descricao");

			$inputs['classificacao']['options'][$id_classif] = $descricao_classif;
		}
	}

	$origemOptions = array(
        'Telefone'  => 'Telefone',
        'Email'     => 'Email',
        'Chat'      => "Chat",
        'CIP'       => "CIP",
        'Juizado'   => "Juizado",
        'Procon'    => "Procon",
        'Midias Sociais' => "Midias Sociais" //HD-3352176
	);

	foreach ($origemOptions as $key => $value) {
		$inputs['origem']['options'][$key] = $value;
	}
}

?>
<script language="javascript">
	$(function () {
		Shadowbox.init();
		$("#gerar_excel_detalhado").click(function () {
		var params = JSON.parse($("#jsonPOST_detalhado").val());
		params.admin = JSON.parse($("#admin_detalhado").val());
		params.tipo = 'total_geral';
		$.ajax({
				url: "listar_atendimento_produtividade.php?params="+JSON.stringify(params),
				type: "GET",
				data: params,
				beforeSend: function () {
					loading("show");
				},
				complete: function (params) {
					window.open(params.responseText, "_blank");

					loading("hide");
				}
			});
		});
		$.datepickerLoad(Array("data_final", "data_inicial"));
	});

	function listarChamados (params) {
		Shadowbox.open({
			content: "listar_atendimento_produtividade.php?params="+JSON.stringify(params),
			player: "iframe",
			width: 1280,
			height: 800
		});
	}

</script>

<style>
.vertical{
	width: 40px !important;
	height: 95px !important;
	border: solid 1px #DDDDDD !important;
	text-align: center !important;
	font-weight: bold;
	background: none !important;
	background-color: none !important;
	background-image: none !important;
	-webkit-transform: rotate(-90deg);
	-moz-transform: rotate(-90deg);
	-ms-writing-mode:tb-rl;
	-o-transform: rotate(-90deg);
	transform: rotate(-90deg);
	padding-left:35px !important;
}

.h_align{
	display: inline-block;
	width: 20px !important;
	margin-top: 28px !important;
	margin-left: -75px !important;
	padding-right: 15px !important;
}

table > tbody > tr > td.resultados{
	min-width: 30px !important;
	max-width: 30px !important;
	border: solid 1px #DDDDDD !important;
}

#resultado_atendimento > thead > #status > td > table > tbody > tr:hover td.vertical{
	background: none !important;
	background-color: none !important;
	background-image: none !important;
}

.btn-link{
	font-size: 13px !important;
}
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<?php
		echo montaForm($inputs, $hiddens);
	?>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Consultar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>

<?php
if ($_POST["btn_acao"] == "submit" AND count($msg_erro['msg']) == 0) {
	if (count($chamados) > 0) {
?>
		<script>
			$(function () {
				var tables  = $("#resultado_atendimento > tbody > tr.total_admin").find("table");

				$.each(tables, function (table_key, table) {
					var columns = $(table).find("td");

					var admin_tables = [];

					$("#resultado_atendimento > tbody > tr.chamados_admin").each(function () {
						admin_tables.push($(this).find("table")[table_key]);
					});

					$.each(columns, function (column_key, column) {
						var admin_columns = [];

						$.each(admin_tables, function () {
							admin_columns.push($(this).find("td")[column_key]);
						});

						var total = 0;

						$.each(admin_columns, function () {
							total += parseInt($(this).find("input").val());
						});

						$(column).text(total);
					});
				});

				var tables_estados  = $("#resultado_atendimento > tbody > tr.total_estado").find("table");

				$.each(tables_estados, function (table_key, table) {
					var columns = $(table).find("td");

					var estado_tables = [];

					$("#resultado_atendimento > tbody > tr.chamados_estado").each(function () {
						estado_tables.push($(this).find("table")[table_key]);
					});

					$.each(columns, function (column_key, column) {
						var estado_columns = [];

						$.each(estado_tables, function () {
							estado_columns.push($(this).find("td")[column_key]);
						});

						var total = 0;

						$.each(estado_columns, function () {
							total += parseInt($(this).find("input").val());
						});

						$(column).text(total);
					});
				});
			});
		</script>

		<table id="resultado_atendimento" align='center' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' id='status' >
					<th>&nbsp;</th>
					<th>Produto/Defeito</th>
					<th>Recl. Empresa</th>
					<th>Recl. A.T</th>
					<th>Totais</th>
					<th>Contatos</th>
					<th>&nbsp;</th>
				</tr>
				<tr  id="status">
					<td>&nbsp;</td>
					<?php
						for($j = 0; $j < 3; $j++){
							echo "<TD>";
							echo "<table>";
							echo "<TR>";
							echo "<TD class='tac vertical'><span class='h_align'>Abertos</span></TD>";
							echo "<TD class='tac vertical'><span class='h_align'>Acomp.</span></TD>";
							echo "<TD class='tac vertical'><span class='h_align'>Fechados</span></TD>";
							echo "<TD class='tac vertical'><span class='h_align'>Total</span></TD>";
							echo "</TR>";
							echo "</table>";
							echo "</TD>";
						}
						echo "<TD>";
							echo "<table>";
							echo "<TR>";
							echo "<TD class='tac vertical'><span class='h_align'>Abertos</span></TD>";
							echo "<TD class='tac vertical'><span class='h_align'>Acomp.</span></TD>";
							echo "<TD class='tac vertical'><span class='h_align'>Fechados</span></TD>";
							echo "</TR>";
							echo "</table>";
						echo "</TD>";
						echo "<TD>";
							echo "<table>";
							echo "<TR>";
							echo "<TD class='tac vertical' nowrap><span class='h_align'>Dúvida Prod.</span></TD>";
							echo "<TD class='tac vertical' nowrap><span class='h_align'>Sugestão</span></TD>";
							echo "<TD class='tac vertical' nowrap><span class='h_align'>Procon</span></TD>";
							echo "<TD class='tac vertical' nowrap><span class='h_align'>Onde Comprar</span></TD>";
							echo "<TD class='tac vertical' ".$style_162." nowrap><span class='h_align'>Informações</span></TD>";
							echo "</TR>";
							echo "</table>";
						echo "</TD>";
						echo "<TD>";
							echo "<table>";
							echo "<TR>";
							echo "<TD class='tac vertical' nowrap><span class='h_align'>Total Geral</span></TD>";
							echo "</TR>";
							echo "</table>";
						echo "</TD>";

					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($chamados as $admin => $admin_array) {
					$sql_login = "SELECT login FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $admin";
					$res_login = pg_query($con, $sql_login);

					$login = pg_fetch_result($res_login, 0, "login");
					?>

					<tr class="chamados_admin" >
						<td><?=$login?></td>

						<?php
						foreach ($admin_array as $categoria => $categoria_array) {
							if (in_array($categoria, $outras_categorias) || $categoria == "totais") {
								continue;
							}
							?>

							<td>
								<table>
									<tr>
										<TD class='tac resultados' nowrap>
											<?php if($login_fabrica == 162){ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['abertos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
											<?php }else{ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['abertos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
											<?php } ?>
								  		</TD>
								  		<TD class='tac resultados' nowrap>
									  		<?php if($login_fabrica == 162){ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['interacoes']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
									  		<?php }else{ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['interacoes']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
									  		<?php } ?>
								  		</TD>
								  		<TD class='tac resultados' nowrap>
								  			<?php if($login_fabrica == 162){ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['resolvidos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  			<?php }else{ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['resolvidos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  			<?php }?>
								  		</TD>
								  		<TD class='tac resultados' nowrap>
								  			<?php if($login_fabrica == 162){ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['total']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', tipo: 'total', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  			<?php }else{ ?>
												<input type='button' class='btn-link' value='<?=$categoria_array['total']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: '<?=$categoria?>', tipo: 'total', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  			<?php } ?>
								  		</TD>
									</tr>
								</table>
							</td>
						<?php
						}
						?>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['abertos']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
										<?php }else{ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['abertos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
										<?php } ?>
							  		</TD>
							  		<TD class='tac resultados' nowrap>
							  			<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['interacoes']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }else{?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['interacoes']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }?>
							  		</TD>
							  		<TD class='tac resultados' nowrap>
							  			<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['resolvidos']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }else{?>
											<input type='button' class='btn-link' value='<?=$admin_array['totais']['resolvidos']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php } ?>
							  		</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['duvida_produto']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: 'duvida_produto', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
										<?php }else{ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['duvida_produto']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: 'duvida_produto', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
										<?php } ?>
							  		</TD>
							  		<TD class='tac resultados' nowrap>
							  			<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['sugestao']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: 'sugestao', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }else{ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['sugestao']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: 'sugestao', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php } ?>
							  		</TD>
							  		<TD class='tac resultados' nowrap>
							  			<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['procon']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: 'procon', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }else{ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['procon']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: 'procon', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php } ?>
							  		</TD>
							  		<TD class='tac resultados' nowrap>
							  			<?php if($login_fabrica == 162){ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['onde_comprar']?>' onclick="listarChamados({ admin: <?=$admin?>, origem:'<?=$origem?>', classificacao:'<?=$classificacao?>', categoria: 'onde_comprar', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php }else{ ?>
											<input type='button' class='btn-link' value='<?=$admin_array['onde_comprar']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: 'onde_comprar', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  			<?php } ?>
							  		</TD>
							  		<TD class='tac resultados' <?=$style_162?> nowrap>
										<input type='button' class='btn-link' value='<?=$admin_array['informacoes']?>' onclick="listarChamados({ admin: <?=$admin?>, categoria: 'informacoes', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
									</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$admin_array['totais']['geral']?>' onclick="listarChamados({ admin: <?=$admin?>, tipo: 'total_geral', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
								</tr>
							</table>
						</td>

					</tr>
				<?php
					$admtotal[] = $admin;
				}
				//$adminjson = json_encode($admtotal);
				//var_dump($admtotal);exit;
				?>
				<tr class="total_admin" >
					<td>Total</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados' <?=$style_162?> ></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td colspan="7">&nbsp;</tr></tr>
				<?php
				foreach ($chamados_estados as $estado => $estado_array) {
				?>

					<tr class="chamados_estado" >
						<td><?=$estado?></td>

						<?php
						foreach ($estado_array as $categoria => $categoria_array) {
							if (in_array($categoria, $outras_categorias) || $categoria == "totais") {
								continue;
							}
							?>

							<td>
								<table>
									<tr>
										<TD class='tac resultados' nowrap>
											<input type='button' class='btn-link' value='<?=$categoria_array['abertos']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: '<?=$categoria?>', tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  		</TD>
								  		<TD class='tac resultados' nowrap>
											<input type='button' class='btn-link' value='<?=$categoria_array['interacoes']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: '<?=$categoria?>', tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  		</TD>
								  		<TD class='tac resultados' nowrap>
											<input type='button' class='btn-link' value='<?=$categoria_array['resolvidos']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: '<?=$categoria?>', tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  		</TD>
								  		<TD class='tac resultados' nowrap>
											<input type='button' class='btn-link' value='<?=$categoria_array['total']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: '<?=$categoria?>', tipo: 'total', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
								  		</TD>
									</tr>
								</table>
							</td>
						<?php
						}
						?>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['totais']['abertos']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'abertos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['totais']['interacoes']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'interacoes', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['totais']['resolvidos']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: ['\'reclamacao_produto\'', '\'reclamacao_empresa\'', '\'reclamacao_at\''], tipo: 'resolvidos', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['duvida_produto']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: 'duvida_produto', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['sugestao']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: 'sugestao', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['procon']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: 'procon', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['onde_comprar']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: 'onde_comprar', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
							  		<TD class='tac resultados' <?=$style_162?> nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['informacoes']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], categoria: 'informacoes', tipo: 'outra_categoria', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
								</tr>
							</table>
						</td>

						<td>
							<table>
								<tr>
									<TD class='tac resultados' nowrap>
										<input type='button' class='btn-link' value='<?=$estado_array['totais']['geral']?>' onclick="listarChamados({ estado: '<?=$estado?>', admin: [<?=$admin_sql_array?>], tipo: 'total_geral', data_inicial: '<?=$aux_data_inicial?>', data_final: '<?=$aux_data_final?>' });" />
							  		</TD>
								</tr>
							</table>
						</td>

					</tr>
				<?php
				}
				?>
				<tr class="total_estado" >
					<td>Total</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados'></td>
								<td class='tac resultados' <?=$style_162?> ></td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td class='tac resultados'></td>
							</tr>
						</table>
					</td>
				</tr>
			</tbody>
		</table>

		<input type="hidden" id="jsonPOST" value='<?php echo $jsonPOST ?>' />

			<div id='gerar_excel' class="btn_excel">
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>
			<br />
		<input type="hidden" id="jsonPOST_detalhado" value='<?php echo $jsonPOST ?>' />
		<input type="hidden" id="admin_detalhado" value='<?php echo json_encode($admtotal); ?>' />
			<div id='gerar_excel_detalhado' class="btn_excel">
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Excel Detalhado </span>
			</div>

<?php

	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
include "rodape.php";
?>
