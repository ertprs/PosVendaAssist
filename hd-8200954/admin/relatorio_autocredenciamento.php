<?php
//admin/relatorio_autocredenciamento.php
$autocredenciamento_fabricas = array(10,30,81,114,122,123,125,127,128,124,126,136,35,151,153,160,169);

$admin_privilegios = 'auditoria';

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($replica_einhell) $autocredenciamento_fabricas[] = $login_fabrica;
if(isset($_POST['codPostoAlteracao'])){
	$codPostoAlteracao = $_POST['codPostoAlteracao'];

	$sql = "SELECT fabrica_credenciada FROM tbl_posto_alteracao WHERE posto_alteracao = $codPostoAlteracao";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$fabrica_credenciada = pg_fetch_result($res, 0, 'fabrica_credenciada');

		$fabrica_credenciada = str_replace("{", "", $fabrica_credenciada);
		$fabrica_credenciada = str_replace("}", "", $fabrica_credenciada);

		$fabricas = explode(",", $fabrica_credenciada);

		if(in_array($login_fabrica, $fabricas)){

			$cont = 0;
			$new_fabricas_credenciadas = "{";

			while($cont < count($fabricas)){

				if($login_fabrica != $fabricas[$cont]){

					$new_fabricas_credenciadas .= $fabricas[$cont].",";

				}

				$cont++;
			}

			$new_fabricas_credenciadas = substr($new_fabricas_credenciadas, 0, strlen($new_fabricas_credenciadas) - 1);

			$new_fabricas_credenciadas .= "}";

			$sql2 = "UPDATE tbl_posto_alteracao SET fabrica_credenciada = '$new_fabricas_credenciadas' WHERE posto_alteracao = $codPostoAlteracao";
			$res2 = pg_query($con, $sql2);

			if(pg_affected_rows($res2) > 0){
				echo "Posto Excluido da Fabrica com Sucesso! | 1";
			}else{
				echo pg_last_error();
			}

		}else{

			echo "Posto não está em credenciamento para esta Fábrica | 0";

		}

	}else{

		echo pg_last_error();

	}

	exit;

}

if($login_fabrica == 147 AND $login_admin == 9135){
	$autocredenciamento_fabricas[] = $login_fabrica;
}	

if($login_fabrica == 146 AND $login_admin == 9140){
	$autocredenciamento_fabricas[] = $login_fabrica;
}

if (!in_array($login_fabrica, $autocredenciamento_fabricas)) {
	echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
	exit;
}

if (!empty($_GET['cnpj'])) {
	$cnpj = $_GET['cnpj'];
	$sql = "SELECT razao_social,
					ie,
					endereco,
					numero,
					complemento,
					contato,
					bairro,
					cep,
					cidade,
					estado,
					email,
					fone,
					fax,
					contato,
					nome_fantasia
				FROM tbl_posto_alteracao
				WHERE cnpj = '$cnpj' and auto_credenciamento is true";
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) == 0) {
		echo 'ERRO: Posto não encontrado.';
		exit;
	} else {
		$query_tbl_posto = pg_query($con, "SELECT posto FROM tbl_posto WHERE cnpj = '$cnpj'");

		if (pg_num_rows($query_tbl_posto) > 0) {
			$posto = pg_fetch_result($query_tbl_posto, 0, 'posto');
			$location = 'posto_cadastro.php?posto=' . $posto;

			header("Location: $location");
			exit;
		} else {
			$razao_social  = pg_fetch_result($query, 0, 'razao_social');
			$ie            = pg_fetch_result($query, 0, 'ie');
			$endereco      = pg_fetch_result($query, 0, 'endereco');
			$numero        = pg_fetch_result($query, 0, 'numero');
			$complemento   = pg_fetch_result($query, 0, 'complemento');
			$contato       = pg_fetch_result($query, 0, 'contato');
			$bairro        = pg_fetch_result($query, 0, 'bairro');
			$cep           = pg_fetch_result($query, 0, 'cep');
			$cidade        = pg_fetch_result($query, 0, 'cidade');
			$estado        = pg_fetch_result($query, 0, 'estado');
			$email         = pg_fetch_result($query, 0, 'email');
			$fone          = pg_fetch_result($query, 0, 'fone');
			$fax           = pg_fetch_result($query, 0, 'fax');
			$contato       = pg_fetch_result($query, 0, 'contato');
			$nome_fantasia = pg_fetch_result($query, 0, 'nome_fantasia');

			$insert = "INSERT INTO tbl_posto (
							nome,
							cnpj,
							endereco,
							numero,
							complemento,
							cep,
							cidade,
							estado,
							bairro,
							email,
							fone,
							fax,
							contato,
							nome_fantasia,
							ie
						) VALUES (
							'$razao_social',
							'$cnpj',
							'$endereco',
							'$numero',
							'$complemento',
							'$cep',
							'$cidade',
							'$estado',
							'$bairro',
							'$email',
							'$fone',
							'$fax',
							'$contato',
							'$nome_fantasia',
							'$ie'
						)";
			$exec = pg_query($con, $insert);

			if (!pg_last_error($con)) {
				$query = pg_query($con, "SELECT CURRVAL('seq_posto') AS posto");
				$posto = pg_fetch_result($query, 0, 'posto');

				$location = 'posto_cadastro.php?posto=' . $posto;

				header("Location: $location");
			} else {
				echo 'ERRO: Erro ao cadastrar posto ' . $cnpj;
			}

			exit;
		}
	}


}

if (!empty($_POST['gera_excel'])) {
	$gerar = array();
	foreach ($_POST as $key => $value) {
		preg_match('/excel_[0-9]/', $key, $match);

		if (!empty($match)) {
			$gerar[] = $value;
		}
	}

	$s_gerar = implode(', ', $gerar);
	unset($gerar);

	$sql = "SELECT razao_social,
					nome_fantasia,
					contato,
					fone,
					email,
					fax,
					endereco,
					numero,
					complemento,
					bairro,
					cidade,
					estado,
					atende_cidade_proxima,
					visita_tecnica,
					atende_consumidor_balcao,
					atende_revendas
				FROM tbl_posto_alteracao
				WHERE posto_alteracao IN ($s_gerar) and auto_credenciamento is true
				ORDER BY razao_social";
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {

		$filename = 'relatorio-autocredenciamento-' . $login_admin . '.xls';

		// definimos o tipo de arquivo
		header("Content-type: application/msexcel");

		// Como será gravado o arquivo
		header("Content-Disposition: attachment; filename=$filename");

		$w = '<html>';
		$w.= '<head></head>';
		$w.= '<body>';
		$w.= '<table border="1" cellpadding="1" cellspacing="1">';
		$w.= '<tr>';
		$w.= '<td><strong>Razão Social</strong></td>';
		$w.= '<td><strong>Nome Fantasia</strong></td>';
		$w.= '<td><strong>Contato</strong></td>';
		$w.= '<td><strong>Telefone</strong></td>';
		$w.= '<td><strong>Email</strong></td>';
		$w.= '<td><strong>Fax</strong></td>';
		$w.= '<td><strong>Endereço</strong></td>';
		$w.= '<td><strong>Bairro</strong></td>';
		$w.= '<td><strong>Cidade/Estado</strong></td>';
		$w.= '<td><strong>Atende Cidades Próximas</strong></td>';
		$w.= '</tr>';

		while ($fetch = pg_fetch_assoc($query)) {
			$razao_social = trim($fetch['razao_social']);
			$nome_fantasia = trim($fetch['nome_fantasia']);
			$contato = trim($fetch['contato']);
			$fone = trim($fetch['fone']);
			$email = trim($fetch['email']);
			$fax = trim($fetch['fax']);
			$endereco = trim($fetch['endereco']);
			$numero = trim($fetch['numero']);
			$complemento = trim($fetch['complemento']);
			$bairro = trim($fetch['bairro']);
			$cidade = trim($fetch['cidade']);
			$estado = trim($fetch['estado']);
			$atende_cidade_proxima = trim($fetch['atende_cidade_proxima']);

			$w.= '<tr>';
			$w.= '<td nowrap>' . $razao_social . '</td>';
			$w.= '<td nowrap>' . $nome_fantasia . '</td>';
			$w.= '<td nowrap>' . $contato . '</td>';
			$w.= '<td nowrap>' . $fone . '</td>';
			$w.= '<td nowrap>' . $email . '</td>';
			$w.= '<td nowrap>' . $fax . '</td>';
			$w.= '<td nowrap>' . $endereco . ', ' . $numero;
			if (!empty($complemento)) {
				$w.= ' - ' . $complemento;
			}
			$w.= '</td>';
			$w.= '<td nowrap>' . $bairro . '</td>';
			$w.= '<td nowrap>' . $cidade . '/' . $estado . '</td>';
			$w.= '<td nowrap>' . $atende_cidade_proxima . '</td>';
			$w.= '</tr>';
		}

		$w.= '</table>';
		$w.= '</body>';
		$w.= '</html>';
	}

	$f = fopen("xls/$filename", "w");
	fwrite($f, $w);
	fclose($f);

	header("Location: xls/$filename");
	exit;
}elseif($_POST['cadastrar_todos']){
	$autocredenciar = array();

	if(!$_POST['posto_unico']){
		foreach ($_POST as $key => $value) {
			preg_match('/cadastrar_[0-9]/', $key, $match);

			if (!empty($match)) {
				$autocredenciar[] = $value;
			}

		}
	}else{
		$autocredenciar[] = $_POST['posto_unico'];
	}
	$sql = "select * from tbl_tipo_posto where fabrica = ".$login_fabrica." and descricao  not ilike('posto interno');";
	$query = pg_query($con, $sql);
	if(pg_num_rows($query) > 0){
		$tipo_posto = pg_fetch_result($query, 0, tipo_posto);
	}else{
		$msg_erro = "Nenhum Tipo de Posto Cadastrado";
	}

	$array_posto_erro = array();
	for($i=0;$i<count($autocredenciar);$i++){
		$auxposto_alteracao = $autocredenciar[$i];

		$sql = "SELECT razao_social,
					cnpj,
					ie,
					endereco,
					numero,
					complemento,
					contato,
					bairro,
					cep,
					cidade,
					estado,
					email,
					fone,
					fax,
					contato,
					nome_fantasia
				FROM tbl_posto_alteracao
				WHERE posto_alteracao = '".$autocredenciar[$i]."' and auto_credenciamento is true";
		$query = pg_query($con, $sql);

		if(pg_num_rows($query) == 0){
			$array_posto_erro[] = $autocredenciar[$i];
		}else{
			$cnpj = pg_fetch_result($query, 0, cnpj);
			$sql = "SELECT * FROM tbl_posto_alteracao WHERE cnpj like('$cnpj')";
			$query_tbl_posto = pg_query($con, $sql);
			if(pg_num_rows($query_tbl_posto) > 0){

				$posto 		   = pg_fetch_result($query_tbl_posto, 0, 'posto');
				$razao_social  = pg_fetch_result($query, 0, 'razao_social');
				$ie            = pg_fetch_result($query, 0, 'ie');
				$endereco      = pg_fetch_result($query, 0, 'endereco');
				$numero        = pg_fetch_result($query, 0, 'numero');
				$complemento   = pg_fetch_result($query, 0, 'complemento');
				$contato       = pg_fetch_result($query, 0, 'contato');
				$bairro        = pg_fetch_result($query, 0, 'bairro');
				$cep           = pg_fetch_result($query, 0, 'cep');
				$cidade        = pg_fetch_result($query, 0, 'cidade');
				$estado        = pg_fetch_result($query, 0, 'estado');
				$email         = pg_fetch_result($query, 0, 'email');
				$fone          = pg_fetch_result($query, 0, 'fone');
				$fax           = pg_fetch_result($query, 0, 'fax');
				$contato       = pg_fetch_result($query, 0, 'contato');
				$nome_fantasia = pg_fetch_result($query, 0, 'nome_fantasia');
				$credenciamento = $_POST['acao'];
				if(strlen($credenciamento) == 0){
					$credenciamento = "CREDENCIADO";
				}

				$res = pg_query ($con,"BEGIN TRANSACTION");
				if($posto == 0 ){
					$msg_error = "";


					$sql = " INSERT INTO tbl_posto(
						nome            ,
                        cnpj            ,
                        ie              ,
                		endereco        ,
                        numero          ,
                        complemento     ,
                        contato  		,
                        bairro          ,
                        cep             ,
                        cidade          ,
                        estado          ,
                        email           ,
                		fone          	,
               			fax             ,
                        nome_fantasia
                    )VALUES(
                    	'$razao_social' 	,
                    	'$cnpj'           	,
                    	'$ie'             	,
                    	'$endereco'       	,
                    	'$numero'         	,
                    	'$complemento'    	,
                    	'$contato'   		,
                    	'$bairro'         	,
                    	'$cep'            	,
                    	'$cidade' 			,
                    	'$estado' 			,
                    	'$email' 			,
                    	'$fone' 			,
                    	'$fax' 				,
                    	'$nome_fantasia'
                    )";
					$res = pg_query($con,$sql);
					$msg_error = pg_errormessage ($con);

	                if (strlen ($msg_error) == 0) {

		       			$res = pg_query ($con,"COMMIT TRANSACTION");
					}else{
					    $res = pg_query ($con,"ROLLBACK TRANSACTION");
					}

					if (strlen($msg_error) == 0){

	                    $sql = "SELECT CURRVAL ('seq_posto')";
	                    $res = pg_query ($con,$sql);
	                    $posto = pg_fetch_result ($res,0,0);
	                    $msg_erro = pg_errormessage ($con);
	                }

	                $sql = "UPDATE tbl_posto_alteracao set posto = {$posto}
	                           where cnpj = '$cnpj'
                            ";

                    $res = pg_query($con,$sql);
					$msg_error = pg_errormessage ($con);

	                if (strlen ($msg_error) == 0) {

		       			$res = pg_query ($con,"COMMIT TRANSACTION");
					}else{
					    $res = pg_query ($con,"ROLLBACK TRANSACTION");
					}

 				}
				$insert = "INSERT INTO tbl_posto_fabrica (
								posto,
								contato_nome,
								contato_endereco,
								contato_numero,
								contato_complemento,
								contato_cep,
								contato_cidade,
								contato_estado,
								contato_bairro,
								contato_email,
								contato_fone_comercial,
								contato_fax,
								senha,
								tipo_posto,
								fabrica,
								credenciamento
							) VALUES (
								$posto,
								'$contato',
								'$endereco',
								'$numero',
								'$complemento',
								'$cep',
								'$cidade',
								'$estado',
								'$bairro',
								'$email',
								'$fone',
								'$fax',
								'*',
								$tipo_posto,
								$login_fabrica,
								'$credenciamento'
							)";
				//echo nl2br($insert);exit;
				$retorno = pg_query($con, $insert);
				$msg_error = pg_errormessage ($con);

                if (strlen ($msg_error) == 0) {

	       			$res = pg_query ($con,"COMMIT TRANSACTION");
				}else{
				    $res = pg_query ($con,"ROLLBACK TRANSACTION");
				}


				if (pg_last_error($con)) {
					$msg_erro = "Erro ao credenciar posto";
				}else{

					if($_POST["posto_unico"]){
						echo $posto;exit;
					}

					$msg_success = "Postos credenciados";
				}
			}else{
				$msg_erro = "Posto não encontrado";
			}

		}
	}
}

$array_estado = array(
						"AC" => "AC - Acre",
						"AL" => "AL - Alagoas",
						"AM" => "AM - Amazonas",
						"AP" => "AP - Amapá",
						"BA" => "BA - Bahia",
						"CE" => "CE - Ceará",
						"DF" => "DF - Distrito Federal",
						"ES" => "ES - Espírito Santo",
						"GO" => "GO - Goiás",
						"MA" => "MA - Maranhão",
						"MG" => "MG - Minas Gerais",
						"MS" => "MS - Mato Grosso do Sul",
						"MT" => "MT - Mato Grosso",
						"PA" => "PA - Pará",
						"PB" => "PB - Paraíba",
						"PE" => "PE - Pernambuco",
						"PI" => "PI - Piauí",
						"PR" => "PR - Paraná",
						"RJ" => "RJ - Rio de Janeiro",
						"RN" => "RN - Rio Grande do Norte",
						"RO" => "RO - Rondônia",
						"RR" => "RR - Roraima",
						"RS" => "RS - Rio Grande do Sul",
						"SC" => "SC - Santa Catarina",
						"SE" => "SE - Sergipe",
						"SP" => "SP - São Paulo",
						"TO" => "TO - Tocantins"
					);

$layout_menu = "auditoria";
$title = "RELATÓRIO DE AUTO-CREDENCIAMENTO";

include "cabecalho.php";

$posto_cidade = '';
$posto_estado = '';
$linhas = array();
$a_fabricas = array();
$fabricas = array();
$marcas = array();
$fabricaz = '';
$todas_linhas = '';
$fabrica_outras = '';
$procura_linhas = '';
$procura_fabricas = '';
$procura_marcas = '';

$cond_cidade = '';
$cond_estado = '';
$cond_linhas = '';
$cond_marcas = '';
$cond_cnpj = '';
$cond_fabricas = '';
$cond_fabricas_outras = '';

$resultado = '';
$msg_erro = '';

if (!empty($_POST["consultar"])) {
	if (!empty($_POST['posto_cidade'])) {
		$posto_cidade = trim(strtoupper($_POST['posto_cidade']));
	}

	if (!empty($_POST['posto_estado'])) {
		$posto_estado = trim(strtoupper($_POST['posto_estado']));
	} /*else {
		$msg_erro.= 'Por favor, selecione o Estado.';
	}*/

	if (!empty($_POST['pesquisa_cnpj'])) {
		$cnpj = $_POST['pesquisa_cnpj'];
	}

	if (!empty($_POST['razao_social'])) {
		$razao_social = $_POST['razao_social'];
	}

	if (!empty($_POST['linhas'])) {
		$todas_linhas = $_POST['linhas'];
	}

	$linhas_post = array("branca", "marrom", "eletroportateis", "informatica", "ferramentas", "lavadoras", "outras");

	foreach ($linhas_post as $l) {
		if (!empty($_POST["$l"])) {
			$linhas[] = $_POST["$l"];
		}
	}

	if (!empty($_POST["total_fab"])) {
		$total_fabricas = $_POST["total_fab"];

		for ($i = 0; $i < $total_fabricas; $i++) {
			if (!empty($_POST['fabrica_' . $i])) {
				$a_fabricas[] = $_POST['fabrica_' . $i];
			}
		}
	}
	elseif (!empty($_POST["fabricaz"])) {
		$fabricaz = $_POST["fabricaz"];
	}

	if (!empty($_POST['fabrica_outras'])) {
		$fabrica_outras = $_POST['fabrica_outras'];
	}

	if (!empty($a_fabricas)) {
		foreach ($a_fabricas as $v) {
			$tmp = explode(":", $v);

			switch ($tmp[0]) {
				case 'f':
					$fabricas[] = $tmp[1];
					break;
				case 'm':
					$marcas[] = $tmp[1];
					break;
			}
		}

		if (!empty($fabricas)) {
			$procura_fabricas = implode(", ", $fabricas);
		}

		if (!empty($marcas)) {
			$procura_marcas = implode(", ", $marcas);
		}
	}

	if (!empty($linhas)) {
		$procura_linhas = implode("|", $linhas);
	}

	if (!empty($posto_cidade)) {
		$cond_cidade = " AND trim(upper(fn_retira_especiais(cidade))) = fn_retira_especiais('$posto_cidade') ";
	}

	if (!empty($posto_estado)) {
		$cond_estado = " AND trim(upper(estado)) = '$posto_estado' ";
	}

	if(in_array($login_fabrica,array(30,35))){
		if (!empty($cnpj)) {
			$cond_cnpj = " AND cnpj = '$cnpj' ";
		}
	}

	if(in_array($login_fabrica,array(30,35))){
		if (!empty($razao_social)) {
			$cond_razao_social = " AND razao_social ILIKE '%$razao_social%'";
		}
	}

	if (!empty($procura_linhas)) {
		$cond_linhas = " AND linhas ~* '$procura_linhas' ";
	}

	if($login_fabrica == 114){
		if (!empty($procura_linhas)) {
			$cond_linhas = " AND (linhas ilike '%michelin%' OR linhas ilike '%chef%')";
		}
	}

	if (!empty($procura_fabricas)) {
		$cond_fabricas = " AND ( ARRAY[$procura_fabricas] && fabrica_credenciada ";
	}
	elseif (!empty($fabricaz)) {
		if ($fabricaz == "-1") {
			$cond_fabricas = " AND (ARRAY[$login_fabrica] <> fabrica_credenciada ";
		} else {
			$cond_fabricas = " AND ($login_fabrica = ANY(fabrica_credenciada) ";
			$sql = "SELECT marca FROM tbl_marca WHERE fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			for($i=0;$i<pg_num_rows($res);$i++){
				$marca = pg_fetch_result($res,$i,0);
				$cond_fabricas .= " OR $marca = any(marca_credenciada) ";
			}
		}
	}

	if (!empty($procura_marcas)) {
		$cond_fabricas .= " or ARRAY[$procura_marcas] && marca_credenciada ";
	}

	if ($fabrica_outras == "1") {
		$cond_fabricas .= " or (marca_ser_autorizada IS NOT NULL AND marca_ser_autorizada <> '')  ";
	}


	if(!empty($cond_fabricas))
		$cond_fabricas .= " ) ";

	$rows = 0;
	if (empty($msg_erro)) {

			$sql = "select  array_to_string(array(select posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto <> 0),',') as postos";
			$query = pg_query($con, $sql);
			$postos_credenciados = pg_fetch_result($query, 0, postos);
		if(!in_array($login_fabrica,array(10,30,35,114))){
			if(!empty($postos_credenciados)) $cond_postos = " and tbl_posto_alteracao.posto not in($postos_credenciados) ";
		}

		$sql = "SELECT  tbl_posto_alteracao.posto_alteracao                                     ,
                        tbl_posto_alteracao.posto                                           ,
                        to_char(tbl_posto_alteracao.data_input,'DD/MM/YYYY') AS data_input  ,
                        tbl_posto_alteracao.fabrica                                         ,
                        tbl_posto_alteracao.razao_social                                    ,
                        tbl_posto_alteracao.cnpj                                            ,
                        tbl_posto_alteracao.ie                                              ,
                        tbl_posto_alteracao.endereco                                        ,
                        tbl_posto_alteracao.numero                                          ,
                        tbl_posto_alteracao.complemento                                     ,
                        tbl_posto_alteracao.bairro                                          ,
                        tbl_posto_alteracao.cep                                             ,
                        tbl_posto_alteracao.cidade                                          ,
                        tbl_posto_alteracao.estado                                          ,
                        tbl_posto_alteracao.email                                           ,
                        tbl_posto_alteracao.fone                                            ,
                        tbl_posto_alteracao.fax                                             ,
                        tbl_posto_alteracao.contato                                         ,
                        tbl_posto_alteracao.nome_fantasia                                   ,
                        tbl_posto_alteracao.linhas                                          ,
                        tbl_posto_alteracao.funcionario_qtde                                ,
                        tbl_posto_alteracao.os_qtde                                         ,
                        tbl_posto_alteracao.atende_cidade_proxima                           ,
                        tbl_posto_alteracao.marca_nao_autorizada                            ,
                        tbl_posto_alteracao.marca_ser_autorizada                            ,
                        tbl_posto_alteracao.melhor_sistema                                  ,
                        tbl_posto_alteracao.outras_fabricas                                 ,
                        tbl_posto_alteracao.fabrica_credenciada                             ,
                        tbl_posto_alteracao.marca_credenciada                               ,
                        tbl_posto_alteracao.observacao                                      ,
                        tbl_posto_alteracao.informacao_marca                                ,
                        tbl_posto_alteracao.informacao_vantagem                             ,
                        tbl_posto_alteracao.informacao_comentario                           ,
                        tbl_posto_alteracao.informacao_sistema                              ,
                        tbl_posto_alteracao.visita_tecnica                                  ,
                        tbl_posto_alteracao.atende_consumidor_balcao                        ,
                        tbl_posto_alteracao.atende_revendas
                FROM    tbl_posto_alteracao
                WHERE 1 = 1
				$cond_cidade
				$cond_estado
				$cond_linhas
				$cond_fabricas
				$cond_cnpj
				$cond_fabricas_outras
				$cond_postos
				$cond_razao_social
				$cond_where
				and tbl_posto_alteracao.auto_credenciamento is true
				ORDER BY razao_social";
				#echo nl2br($sql);
		$query = pg_query($con, $sql);
		$rows = pg_num_rows($query);
	}

	if($login_fabrica == 114){
		$legenda_fabrica = "Fábricas credenciadas na Cobimex";
		$cores = "#8dbe24;";
	}else if($login_fabrica == 30){
		$legenda_fabrica = "Postos credenciados na Esmaltec";
		$cores = "#f78009;";
	}else{
		$legenda_fabrica = "Postos credenciados na Cadence";
		$cores = "#f78009;";
	}

	if ($rows > 0) {
		$resultado.= '<form id="frm_relatorio" name="relatorio" action="' . $_SERVER['PHP_SELF'] . '" method="post" target="_blank">';

		if(in_array($login_fabrica,array(30,35,114))){
			$resultado.='<table border="0" align="center" style="width: 1200px;"><tr><td class="status_checkpoint" style="background-color:'.$cores.'"></td><td align="left">'.$legenda_fabrica.'</td></tr></table>';
		}
		$resultado.= '<table border="0" cellpadding="3" cellspacing="1" class="tabela" align="center" style="width: 1200px;">';
		$resultado.= '<tr class="titulo_coluna">';
		$resultado.= '<td style="width: 320px;">Razão Social</td>';
		$resultado.= '<td style="width: 140px;">CNPJ</td>';
		$resultado.= '<td style="width: 140px;">IE</td>';
		$resultado.= '<td style="width: 140px;">Cidade/Estado</td>';
		$resultado.= '<td style="width: 360px;">Linhas</td>';

		$resultado.= '<td style="width: 20px;">Excel</td>';
		$resultado.= '<td colspan="2" style="width: 20px;">Selecione para Cadastrar';
		$resultado.= '<select style="margin: 6px 0 0 0;" title="Ação" name="acao">
							<option value="CREDENCIADO">Credenciar</option>
							<option value="DESCREDENCIADO">Descredenciar</option>
						</select>
						</td>';
		if(in_array($login_fabrica,array(30,35))){
			$resultado .= '<td>Credenciar</td>';
		}
		$resultado .= '<td>Opção</td>';
		$resultado.= '</tr>';

		for ($i = 0; $i < $rows; $i++) {
			$posto_alteracao       = pg_fetch_result($query, $i, 'posto_alteracao');
			$posto                 = pg_fetch_result($query, $i, 'posto');
			$data_entrada          = pg_fetch_result($query, $i, 'data_input');
			$fabrica               = pg_fetch_result($query, $i, 'fabrica');
			$razao_social          = pg_fetch_result($query, $i, 'razao_social');
			$cnpj                  = pg_fetch_result($query, $i, 'cnpj');
			$ie                    = pg_fetch_result($query, $i, 'ie');
			$endereco              = pg_fetch_result($query, $i, 'endereco');
			$numero                = pg_fetch_result($query, $i, 'numero');
			$complemento           = pg_fetch_result($query, $i, 'complemento');
			$bairro                = pg_fetch_result($query, $i, 'bairro');
			$cep                   = pg_fetch_result($query, $i, 'cep');
			$cidade                = pg_fetch_result($query, $i, 'cidade');
			$estado                = pg_fetch_result($query, $i, 'estado');
			$email                 = pg_fetch_result($query, $i, 'email');
			$fone                  = pg_fetch_result($query, $i, 'fone');
			$fax                   = pg_fetch_result($query, $i, 'fax');
			$contato               = pg_fetch_result($query, $i, 'contato');
			$nome_fantasia         = pg_fetch_result($query, $i, 'nome_fantasia');
			$s_linhas              = pg_fetch_result($query, $i, 'linhas');
			$funcionario_qtde      = pg_fetch_result($query, $i, 'funcionario_qtde');
			$os_qtde               = pg_fetch_result($query, $i, 'os_qtde');
			$atende_cidade_proxima = pg_fetch_result($query, $i, 'atende_cidade_proxima');
			$marca_nao_autorizada  = pg_fetch_result($query, $i, 'marca_nao_autorizada');
			$marca_ser_autorizada  = pg_fetch_result($query, $i, 'marca_ser_autorizada');
			$melhor_sistema        = pg_fetch_result($query, $i, 'melhor_sistema');
			$outras_fabricas       = pg_fetch_result($query, $i, 'outras_fabricas');
			$fabrica_credenciada   = pg_fetch_result($query, $i, 'fabrica_credenciada');
			$marca_credenciada     = pg_fetch_result($query, $i, 'marca_credenciada');
			$observacao            = pg_fetch_result($query, $i, 'observacao');
			$informacao_marca      = pg_fetch_result($query, $i, 'informacao_marca');
			$informacao_vantagem   = pg_fetch_result($query, $i, 'informacao_vantagem');
			$informacao_comentario = pg_fetch_result($query, $i, 'informacao_comentario');
			$informacao_sistema    = pg_fetch_result($query, $i, 'informacao_sistema');
			$condicao_1    		   = pg_fetch_result($query, $i, 'visita_tecnica');
			$condicao_2            = pg_fetch_result($query, $i, 'atende_consumidor_balcao');
			$condicao_3       	   = pg_fetch_result($query, $i, 'atende_revendas');
			$posto_credenciado     = pg_fetch_result($query, $i, 'credenciamento');

			$fabrica_credenciada = preg_replace('/[{}]/', '', $fabrica_credenciada);
			$marca_credenciada = preg_replace('/[{}]/', '', $marca_credenciada);

			$t_informacao_marca = str_replace('|', '', $informacao_marca);
			$t_informacao_vantagem = str_replace('|', '', $informacao_vantagem);
			$t_informacao_sistema = str_replace('|', '', $informacao_sistema);

			$completo = '';
			$tem_fabrica = 0;
			$busca_fabrica = 0;
			$busca_marca = 0;


			if (!empty($fabrica_credenciada)) {
				$tem_fabrica = 1;
				$busca_fabrica = 1;
			}

			if (!empty($marca_credenciada)) {
				$tem_fabrica = 1;
				$busca_marca = 1;
			}



			if ($tem_fabrica == 1) {
				$sql_fabr = '';
				if ($busca_fabrica == 1) {
					$sql_fabr.= "SELECT nome FROM tbl_fabrica WHERE fabrica IN ($fabrica_credenciada)";
				}


				$sql_fabr.= " ORDER BY nome ";

				$query_fabr = pg_query($con, $sql_fabr);
				$rows_fabr = pg_num_rows($query_fabr);

				if ($rows_fabr > 0) {
					while ($fetch_fabr = pg_fetch_assoc($query_fabr)) {
						$completo.= $fetch_fabr['nome'] . ', ';
					}
				}
			}

			if (!empty($s_linhas)) {
				$s_linhas = str_replace('ELETROPORTATEIS', 'ELETROPORTÁTEIS', $s_linhas);
				$s_linhas = str_replace('INFORMATICA', 'INFORMÁTICA', $s_linhas);
				$s_linhas = str_replace('OUTRAS,', '', $s_linhas);
				$s_linhas = str_replace(',', ', ', $s_linhas);
			}

			if ($i%2 == 0) {
				$bgcolor = ' style="background-color: #FFFFF0;" ';
			} else {
				$bgcolor = ' style="background-color: #F3F3F3;" ';
			}


			if(in_array($login_fabrica,array(30,35))){
				$array = explode(",", $postos_credenciados);

				if(in_array($posto, $array) AND $posto != 0){
					$bgcolor = ' style="background-color: '.$cores.'" ';
					$visivel = "display:none";
				}else{
					$visivel = "";
				}
			}

			if($login_fabrica == 114){
				if(strpos($postos_credenciados, $posto) != false){
					$bgcolor = ' style="background-color: '.$cores.'" ';
					$visivel = "display:none";
				}else{
					$visivel = "";
				}
			}

			$resultado.= '<tr ' . $bgcolor . ' class="box-'.$posto_alteracao.'">';
			$resultado.= '<td style="cursor: pointer; text-align: left; padding-left: 10px;" onClick="expande(\'' . $i . '\')">';
			$resultado.= '<img src="../imagens/mais.bmp" id="icone_expande_' . $i . '" />&nbsp;';
		  	$resultado.= $razao_social . '</td>';
			$resultado.= '<td>' . $cnpj . '</td>';
			$resultado.= '<td>' . $ie . '</td>';
			$resultado.= '<td>' . $cidade . '/' . $estado . '</td>';
			$resultado.= '<td>' . $s_linhas . '</td>';
			//$resultado.= '<td><img src="imagens/icone_ok.gif" onClick="cadastraPosto(\'' . $cnpj . '\')" style="cursor: pointer;" /></td>';
			$resultado.= '<td><input type="checkbox"  class="ck_excel" name="excel_' . $i . '" value="' . $posto_alteracao . '" onClick="mostraSubmit(this.checked)" /></td>';
			$resultado.= '<td colspan="2"><input type="checkbox"  class="ck_cadastro" name="cadastrar_' . $i . '" value="' . $posto_alteracao . '"  onClick="mostraCadastrar(this.checked)" style="'.$visivel.'" /></td>';
			if(in_array($login_fabrica,array(30,35))){
				$resultado.='<td norap> <button type="button" style="cursor: pointer; '.$visivel.'" onClick="credenciarPosto('.$i.')">Credenciar</button> </td>';
			}
			$resultado .= '<td norap> <button type="button" style="cursor: pointer;" onClick="excluiPosto('.$posto_alteracao.')">Excluir</button> </td>';

			$resultado.= '</tr>';

			$resultado.= '<tr id="completo_' . $i . '" style="display: none" align="left">';
			$resultado.= '<td colspan="9">';
			$resultado.= '<div style="padding-left: 20px; margin: 10px 0; float: left; width: 1100px;">';

			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Data do Autocredenciamento:</strong> ' . $data_entrada;
			$resultado.= '</div>';

            $resultado.= '<div style="width: 100%; float: left;">';
            $resultado.= '<strong>Razão Social:</strong> ' . $razao_social;
            $resultado.= '</div>';

			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Nome Fantasia:</strong> ' . $nome_fantasia;
			$resultado.= '</div>';

			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<div style="width: 20%; float: left;">';
			$resultado.= '<strong>CNPJ:</strong> ' . $cnpj;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 80%; float: left;">';
			$resultado.= '<strong>IE:</strong> ' . $ie;
			$resultado.= '</div>';
			$resultado.= '</div>';

			$resultado.= '<div style="width: 100%; margin-top: 10px; float: left;">';
			$resultado.= '<div style="width: 20%; float: left;">';
			$resultado.= '<strong>Contato:</strong> ' . $contato;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 80%; float: left;">';
			$resultado.= '<strong>Email:</strong> ' . $email;
			$resultado.= '</div>';
			$resultado.= '</div>';
			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<div style="width: 20%; float: left;">';
			$resultado.= '<strong>Telefone:</strong> ' . $fone;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 80%; float: left;">';
			$resultado.= '<strong>Fax:</strong> ' . $fax;
			$resultado.= '</div>';
			$resultado.= '</div>';

			$resultado.= '<div style="width: 100%; margin-top: 10px; float: left;">';
			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Endereco:</strong> ' . $endereco . ', ' . $numero;
			if (!empty($complemento)) {
				$resultado.= ' - ' . $complemento;
			}
			$resultado.= '</div>';
			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Bairro:</strong> ' . $bairro;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 20%; float: left;">';
			$resultado.= '<strong>Cidade:</strong> ' . $cidade;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 80%; float: left;">';
			$resultado.= '<strong>Estado:</strong> ' . $estado;
			$resultado.= '</div>';
			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>CEP:</strong> ' . substr($cep,0,5)."-".substr($cep,5,3);
			$resultado.= '</div>';

			$resultado.= '</div>';

			$a_informacao_marca = array();
			$a_informacao_sistema = array();
			$a_informacao_vantagem = array();

			if (!empty($t_informacao_marca)) {
				$a_informacao_marca = explode('|', $informacao_marca);
			}

			if (!empty($t_informacao_sistema)) {
				$a_informacao_sistema = explode('|', $informacao_sistema);
			}

			if (!empty($t_informacao_vantagem)) {
				$a_informacao_vantagem = explode('|', $informacao_vantagem);
			}

			if (!empty($a_informacao_sistema) and $login_fabrica == 10) {
				$resultado.= '<div style="width: 100%; float: left; margin-top: 10px;">';

				$resultado.= '<div style="width: 100%; float: left;">';
				$resultado.= '<strong>Usa outros sistemas além do Telecontrol? Para quais marcas? Quais são as vantagens?</strong>';
				$resultado.= '</div>';

				$resultado.= '<div style="width: 100%; float: left;">';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Nome do Sistema:</strong> ' . $a_informacao_sistema[0];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Marca:</strong> ' . $a_informacao_marca[0];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Vantagens:</strong> ' . $a_informacao_vantagem[0];
				$resultado.= '</div>';

				$resultado.= '</div>';
				$resultado.= '<div style="width: 100%; float: left;">';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Nome do Sistema:</strong> ' . $a_informacao_sistema[1];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Marca:</strong> ' . $a_informacao_marca[1];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Vantagens:</strong> ' . $a_informacao_vantagem[1];
				$resultado.= '</div>';

				$resultado.= '</div>';
				$resultado.= '<div style="width: 100%; float: left;">';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Nome do Sistema:</strong> ' . $a_informacao_sistema[2];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Marca:</strong> ' . $a_informacao_marca[2];
				$resultado.= '</div>';

				$resultado.= '<div style="width: 20%; float: left;">';
				$resultado.= '<strong>Vantagens:</strong> ' . $a_informacao_vantagem[2];
				$resultado.= '</div>';

				$resultado.= '</div>';

				$resultado.= '</div>';
			}

			$resultado.= '<div style="width: 100%; float: left; margin-top: 10px;">';
			$resultado.= '<strong>Linhas: </strong>' . $s_linhas;
			$resultado.= '</div>';

			$resultado.= '<div style="width: 100%; float: left; margin-top: 10px;">';

			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Quantidade de funcionários:</strong> ' . $funcionario_qtde;
			$resultado.= '</div>';

			if (!empty($os_qtde)) {
				$resultado.= '<div style="width: 100%; float: left;">';
				$resultado.= '<strong>Quantidade de ordens de serviço mensal:</strong> ' . $os_qtde;
				$resultado.= '</div>';
			}

				 if (!in_array($login_fabrica,array(30,124,126,35))) {
					$resultado.= '<div style="width: 100%; float: left;">';
					$resultado.= '<strong>É autorizada atualmente:</strong> ' . $outras_fabricas;
					$resultado.= '</div>';
				 }
			$resultado.= '<div style="width: 100%; float: left;">';
			$resultado.= '<strong>Atende cidades próximas:</strong> ' . $atende_cidade_proxima;
			$resultado.= '</div>';

			if (!empty($marca_nao_autorizada)) {
				$resultado.= '<div style="width: 100%; float: left;">';
				$resultado.= '<strong>Não gostaria de ser autorizada:</strong> ' . $os_qtde;
				$resultado.= '</div>';
			}

			if (!empty($melhor_sistema) and $login_fabrica == 10) {
				$resultado.= '<div style="width: 100%; float: left;">';
				$resultado.= '<strong>Qual o melhor sistema informatizado de ordens de serviço:</strong> ' . $melhor_sistema;
				$resultado.= '</div>';
			}

			$resultado.= '</div>';

			if ($fabricaz <> $login_fabrica) {
				$arr_fabricas = explode(",", $completo);
				$pop = array_pop($arr_fabricas);

				$resultado.= '<div style="width: 660px; float: left; margin-top: 10px;">';

				$resultado.= '<div style="width: 100%; float: left;">';
				$resultado.= '<strong>Fábricas de interesse:</strong>';
				$resultado.= '</div>';

				$resultado.= '<div style="width: 660px; float: left; margin-left: 20px;">';
				foreach ($arr_fabricas as $f) {
					$resultado.= '<div style="width: 220px; float: left">' . $f . '</div>';

				}
				$resultado.= '</div>';

				if (!empty($marca_ser_autorizada)) {
					$resultado.= '<div style="width: 660px; float: left;">';
					$resultado.= '<strong>Outras: </strong>' . $marca_ser_autorizada;
					$resultado.= '</div>';
				}

				$resultado.= '</div>';
			}

			$resultado .= '<div style="width: 660px; margin-top: 10px;">';
				$resultado .= "<strong>Posto tem condições de atender: </strong> <br />";
				if($condicao_1 != 'f'){ $resultado .= "Visita tecnica <br />"; }
				if($condicao_2 != 'f'){ $resultado .= "Atende consumidor - balcão <br />"; }
				if($condicao_3 != 'f'){ $resultado .= "Atende revendas"; }
			$resultado .= '</div>';

		 if (!in_array($login_fabrica,array(30,124,126,35,153,151))) {
			$sql_informacoes = "select  nome, tbl_fabrica.fabrica,
							 		count(distinct tbl_os.os) as qtde_os,
									avg(data_fechamento-data_abertura) as media,
									sum(tbl_os_item.qtde)/count(distinct tbl_os.os) as media_pecas
									from tbl_os
									join tbl_os_produto on tbl_os.os = tbl_os_produto.os
									join tbl_os_item on tbl_os_item.os_item = tbl_os_produto.os_produto and tbl_os_item.fabrica_i = tbl_os.fabrica
									join tbl_fabrica on tbl_fabrica.fabrica = tbl_os.fabrica
									where finalizada >= current_date - interval '6 month' and tbl_os.posto = $posto
									group by nome, tbl_fabrica.fabrica ";

			$res_informacoes = pg_query($con,$sql_informacoes);

			if(pg_num_rows($res_informacoes) > 0){

				$resultado .= "
				<div style='margin-top: 10px;'>
					<table width=650px>
						<thead>
							<tr class='titulo_coluna' style='height:25px;'>
                                <th colspan='4'>Avaliação Posto Autorizado</th>
                            </tr>
							<tr class='titulo_coluna' style='height:25px;'>
								<th align='center'>Fabricante</th>
								<th align='center'>Qtde OS Mês</th>
								<th align='center'>Média Dias em Aberto</th>
								<th align='center'>Média de Peças</th>
							</tr>
						</thead>
						<tbody>";

						$j= 0;

				while ($os_valores = pg_fetch_object($res_informacoes)) {

					$c = $j++;
					$nome 			= $os_valores->nome;
					$fabrica		= $os_valores->fabrica;
					$qtde_os 		= $os_valores->qtde_os;
					$media 			= ceil($os_valores->media);
					$media_pecas 	= ceil($os_valores->media_pecas);

					$sql_os = "select distinct tbl_os.os,
								data_abertura,
								data_fechamento,
								(data_fechamento-data_abertura) as dias_aberto
								from tbl_os
								join tbl_os_produto on tbl_os.os = tbl_os_produto.os
								join tbl_os_item on tbl_os_item.os_item = tbl_os_produto.os_produto and tbl_os_item.fabrica_i = tbl_os.fabrica
								where finalizada >= current_date - interval '6 month' and tbl_os.posto = $posto and tbl_os.fabrica = '$fabrica'
								";

					$res_os = pg_query($con,$sql_os);

					$links_os = "";

					if(pg_num_rows($res_os) > 0){

						$links_os .= "<table width=100%>
						<thead>
							<tr class='titulo_coluna' style='height:25px;'>
								<th align='center'>OS</th>
								<th align='center'>Data abertura</th>
								<th align='center'>Data fechamento</th>
								<th align='center'>Dias em aberto</th>
							</tr>
						</thead>
						<tbody>

						";

						while ($result_os = pg_fetch_object($res_os)) {
							$os_res = $result_os->os;
							$data_abertura = $result_os->data_abertura;
							list($ano,$mes,$dia) = explode("-", $data_abertura);
							$data_abertura = $dia."/".$mes."/".$ano;
							$data_fechamento = $result_os->data_fechamento;
							list($ano,$mes,$dia) = explode("-", $data_fechamento);
							$data_fechamento = $dia."/".$mes."/".$ano;
							$dias_aberto = $result_os->dias_aberto;
							//$links_os .= "<a href='os_press.php?os={$os_res}' target='_blank'>{$os_res}</a> <br />";
							$links_os .=
										"<tr style='height:25px;'>
											<td valign=top align=center>$os_res</td>
											<td valign=top align=center>$data_abertura</td>
											<td valign=top align=center>$data_fechamento</td>
											<td valign=top align=center>$dias_aberto</td>
										</tr>";

						}

						$links_os .="
									</tbody>
									</table>";
					}
					$box_os			= "<div id='box_os".$i."_".$c."' style='display:none;'>$links_os</div>";
					$resultado .= "
							<tr>
								<td style='text-align:center;vertical-align:middle;'>$nome</td>
								<td style='text-align:center;padding-top:10px;'><a href='javascript: abreos(\"".$i."_".$c."\")'>$qtde_os Visualizar OS</a><br /><br /> $box_os </td>
								<td style='text-align:center;vertical-align:middle;'>$media</td>
								<td style='text-align:center;vertical-align:middle;'>$media_pecas</td>
							</tr>

					";
				}
				$resultado .= "
						</tbody>
					</table>
				</div>";

			}
		 }
			$resultado.= '<div style="width: 100%; float: left; margin-top: 10px;">';

			//2780042 descomentar depois

			 $caminho_imagem = dirname(__FILE__) . '/../autocredenciamento/fotos/';
			 $caminho_path	= dirname($_SERVER['PHP_SELF']) . '/../autocredenciamento/fotos/';
    		$img_path = $caminho_path.$cnpj;
			$img_caminho = $caminho_imagem.$cnpj;
			$img_ext = '';

			if (file_exists($img_caminho."_1.jpg")) {
				$img_ext = "jpg";
			}

			if (file_exists($img_caminho."_1.png")) $img_ext = "png";
			if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";

			if ($img_ext) {
				$img_src = $img_path . '_1.' . $img_ext;
				$resultado.= '<div style="float: left; width: 270px;">';
				$resultado.= '<a class="fotos_ac_' . $i . '" href="' . $img_src . '">';
				$resultado.= '<img width="260" height="163" src="' . $img_src . '" />';
				$resultado.= '</a>';
				$resultado.= '</div>';
			}

			$img_ext = '';

			if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
			if (file_exists($img_caminho."_2.png")) $img_ext = "png";
			if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";

			if ($img_ext) {
				$img_src = $img_path . '_2.' . $img_ext;
				$resultado.= '<div style="float: left; width: 270px; margin-left: 40px;">';
				$resultado.= '<a class="fotos_ac_' . $i . '" href="' . $img_src . '">';
				$resultado.= '<img width="260" height="163" src="' . $img_src . '" />';
				$resultado.= '</a>';
				$resultado.= '</div>';
			}

			$img_ext = '';

			if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
			if (file_exists($img_caminho."_3.png")) $img_ext = "png";
			if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";

			if ($img_ext) {
				$img_src = $img_path . '_3.' . $img_ext;
				$resultado.= '<div style="float: left; width: 270px; margin-left: 40px;">';
				$resultado.= '<a class="fotos_ac_' . $i . '" href="' . $img_src . '">';
				$resultado.= '<img width="260" height="163" src="' . $img_src . '" />';
				$resultado.= '</a>';
				$resultado.= '</div>';
			}

			$resultado.= '<script>jQuery(document).ready(function () { jQuery(\'a.fotos_ac_' . $i . '\').colorbox(); })</script>';

			$resultado.= '</div>';

			if (!empty($observacao)) {
				$resultado.= '<div style="width: 100%; float: left; margin-top: 10px;">';
				$resultado.= '<strong>Descrição da autorizada:</strong> ' . $observacao;
				$resultado.= '</div>';
			}

			$resultado.= '</div>';
			$resultado.= '</td>';
			$resultado.= '</tr>';

		}

		$resultado.= '<tr>';
		$resultado.= '<td colspan="6" style="background-color: #FFFFFF; border: none; font-weight: bold; text-align: right; padding-right: 10px;">';
		$resultado.= 'Selecionar todos';
		$resultado.= '</td>';
		$resultado.= '<td><input type="checkbox" id="marca_todos_excel" onClick="marcaTodas(\'excel\')" />';
		$resultado.= '</td>';
		$resultado.= '<td><input type="checkbox" id="marca_todos_cadastro" onClick="marcaTodas(\'cadastrar\')" />';
		$resultado.= '</td>';
		$resultado.= '</tr>';

		$resultado.= '</table><br/>';


		$resultado.= '<div style="text-align: center; margin-bottom: 20px;">
						<input type="submit" name="gera_excel" id="gera_excel" value="Gerar Excel" style="display: none;" />
						<input type="submit" name="cadastrar_todos" id="cadastrar_todos" value="Cadastrar Todos" style="display: none;" />
					</div>';

		$resultado.= '</form>';

	} else {
		if (empty($msg_erro)) {
			$resultado.= '<div style="text-align: center; font-weight: bold; margin: 20px 0;">Não foram encontrados resultados para os parâmetros pesquisados</div>';
		}
	}

}

?>

<style type="text/css">

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #596D9B;
}

.border {
	border: 1px solid #ced7e7;
}


.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: normal;
    border: 0px solid;
    background-color: #D9E2EF;
}

.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: normal;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

.status_checkpoint{width:9px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>


<link rel="stylesheet" href="colorbox.css" />
<script type="text/javascript" src="js/ajax.js"></script>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/colorbox/jquery.colorbox-min.js"></script>

<script type="text/javascript">


<?
if(in_array($login_fabrica,array(30,35))){

?>
	function SomenteNumero(e){
		var tecla=(window.event)?event.keyCode:e.which;
		if((tecla > 47 && tecla < 58)) return true;
		else{
			if (tecla != 8) return false;
			else return true;
		}
	}

    function credenciarPosto(i){

    	var num = $("input[name=cadastrar_"+i+"]").val();

    	$.ajax({
            url : "<?php echo $_SERVER['PHP_SELF']; ?>",
            type: "POST",
            data: {
                acao : "CREDENCIADO",
                posto_unico : num,
                cadastrar_todos : "Cadastrar Todos"
            },
            complete: function(data){
               data = data.responseText;
               alert("Posto Credenciado com Sucesso");
               $(".box-"+num).remove();
               window.open('posto_cadastro.php?posto='+data ,'_blank');
           	}
        });

    }

<?php

}
?>

	function excluiPosto(codPostoAlteracao){
		$.ajax({
			url: '<?php echo $_SERVER['PHP_SELF']; ?>',
			type: 'POST',
			data: "codPostoAlteracao="+codPostoAlteracao,
			complete: function(data){
				data = data.responseText;
				data = data.split("|");
				alert(data[0]);
				if(data[1] != 0){
					$('.box-'+codPostoAlteracao).remove();
				}
			}
		});
	}


	function abreos(num){

		if ($("#box_os"+num).is(':visible')){
			$("#box_os"+num).css({'display':'none'});
		}else{

			$("#box_os"+num).css({'display':'block'});
		}
	}

	function expande(ordem) {
		var elemento = document.getElementById('completo_' + ordem);
		var display = elemento.style.display;

		if (display == "none") {
			elemento.style.display = "";
			document.getElementById('icone_expande_' + ordem).src = "../imagens/menos.bmp";
		} else {
			elemento.style.display = "none";
			document.getElementById('icone_expande_' + ordem).src = "../imagens/mais.bmp";
		}

	}

	function cadastraPosto(cnpj) {
		var txtConfirma = 'Confirma o cadastro do posto: ' + cnpj + '?';
		txtConfirma+= "\n\n";
		txtConfirma+= "Você será direcionado para o Cadastra de Postos onde\ndeverá completar as informações referentes\nao Credenciamento do Posto.";

		if (confirm(txtConfirma)) {
			var url = 'relatorio_autocredenciamento.php?cnpj=' + cnpj;
			window.open(url, '_blank');
		}
	}

	var total_chk = 0;

	function marcaTodas(campos) {
		var todas = '';

		if (campos == "linhas") {
			todas = document.getElementById('todas_linhas').checked;

			if (todas) {
				document.getElementById('branca').checked = 'true';
				document.getElementById('marrom').checked = 'true';
				document.getElementById('eletroportateis').checked = 'true';
				document.getElementById('informatica').checked = 'true';
				document.getElementById('ferramentas').checked = 'true';
				document.getElementById('lavadoras').checked = 'true';
				document.getElementById('outras').checked = 'true';
			} else {
				document.getElementById('branca').checked = '';
				document.getElementById('marrom').checked = '';
				document.getElementById('eletroportateis').checked = '';
				document.getElementById('informatica').checked = '';
				document.getElementById('ferramentas').checked = '';
				document.getElementById('lavadoras').checked = '';
				document.getElementById('outras').checked = '';
			}
		}
		else if (campos == "fabricas") {
			todas = document.getElementById('fabricas').checked;
			var total_fab = document.getElementById('total_fab').value;

			if (todas) {
				for (var i = 0; i < total_fab; i++) {
					document.getElementById('fabrica_' + i).checked = 'true';
				}
			} else {
				for (var i = 0; i < total_fab; i++) {
					document.getElementById('fabrica_' + i).checked = '';
				}
			}

		}
		else if (campos == "excel") {
			//var chkboxes = document.relatorio.getElementsByTagName('input');
			var chkboxes = document.relatorio.getElementsByClassName('ck_excel');
			var todos = document.getElementById('marca_todos_excel').checked;

			var j = 0;

			for (var i in chkboxes) {
				if (chkboxes[i].type == "checkbox") {
					chkboxes[i].checked = todos;
					j++;
				}
			}

			if (todos) {
				total_chk = j - 2;
			} else {
				total_chk = 1;
			}

			mostraSubmit(todos);

		}else if (campos == "cadastrar") {
			//var chkboxes = document.relatorio.getElementsByTagName('input');
			var chkboxes = document.relatorio.getElementsByClassName('ck_cadastro');
			var todos = document.getElementById('marca_todos_cadastro').checked;

			var j = 0;

			for (var i in chkboxes) {
				if (chkboxes[i].type == "checkbox") {
					chkboxes[i].checked = todos;
					j++;
				}
			}

			if (todos) {
				total_chk = j - 2;
			} else {
				total_chk = 1;
			}

			mostraCadastrar(todos);

		}

	}

	function selecionaLinha(linha) {
		var isChecked = document.getElementById(linha).checked;

		if (!isChecked) {
			document.getElementById('todas_linhas').checked = '';
		}
	}

	function selecionaFabrica(fabrica) {
		var isChecked = document.getElementById(fabrica).checked;

		if (!isChecked) {
			document.getElementById('fabricas').checked = '';
		}

	}


	function mostraSubmit(chk) {

		(chk) ? total_chk++ : total_chk--;

		if (total_chk > 0) {
			document.getElementById('gera_excel').style.display = '';
		} else {
			document.getElementById('gera_excel').style.display = 'none';
		}

	}

	function mostraCadastrar(chk) {

		(chk) ? total_chk++ : total_chk--;

		if (total_chk > 0) {
			document.getElementById('cadastrar_todos').style.display = '';
			document.getElementById('frm_relatorio').target = '_self';
		} else {
			document.getElementById('cadastrar_todos').style.display = 'none';
			document.getElementById('frm_relatorio').target = '_blank';
		}

	}


	function buscaCidade(estado) {
		if (http) {
			var url = "autocomplete_cidade_autocredenciamento.php?e=" + estado;

			http.open("GET", url, true);
			http.onreadystatechange = function () {
				if (http.readyState == 4) {
					if (http.status == 200) {
						processaRetorno(http.responseText);
					}
				}
			}
			http.send(null);
		}
	}

	function processaRetorno(retorno) {
		if (retorno) {
			var retJSON = JSON.parse(retorno);

			var option = document.createElement('option');
			option.setAttribute('value', '');

			var option_txt = document.createTextNode('Selecione');
			option.appendChild(option_txt);

			var cidades = document.getElementById('posto_cidade');

			cidades.innerHTML = '';
			cidades.appendChild(option);

			for (var i in retJSON) {
				var valor = retJSON[i].cidade_pesquisa;
				var nome = retJSON[i].cidade;

				option = document.createElement('option');
				option.setAttribute('value', valor);

				option_txt = document.createTextNode(nome);

				option.appendChild(option_txt);
				cidades.appendChild(option);

			}
		}
	}
</script>

<?php

if(strlen($msg_erro)>0){
	echo "<div align='center'><div class='msg_erro' style='width:700px;'>$msg_erro</div></div>";
}

?>

<table width="700" align="center" border="0" cellspacing="1" cellpadding="1" class="formulario">
	<form method="post" name="frm_posto" action="<?php echo $_SERVER['PHP_SELF'] ?>">
	<tr class="titulo_tabela">
		<td colspan="4" align="center">Parâmetros de Pesquisa</td>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
	<tr>
		<td align="right" width="10%"></td>
		<td align="left" width="30%">
			Estado <br/>
			<select id="posto_estado" name="posto_estado" class="frm" onChange="buscaCidade(this.value)">
				<option value="">Selecione</option>
					<?php
					foreach ($array_estado as $k => $v) {
						echo '<option value="'.$k.'"'.($posto_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
					}
					?>
			</select>
		</td>
		<td align="left" width="30%">
			Cidade <br/>
			<select id="posto_cidade" name="posto_cidade" class="frm" style="width: 240px;">
			<?php
			if (!empty($posto_estado)) {
				$query_cid = pg_query($con, "SELECT cidade, cidade_pesquisa FROM tbl_ibge WHERE estado = '$posto_estado' ORDER BY cidade");

				if (pg_num_rows($query_cid) > 0) {
					echo '<option value="">Selecione</option>';
					while ($fetch = pg_fetch_assoc($query_cid)) {
						echo '<option value="'.$fetch['cidade_pesquisa'].'"'.($posto_cidade == $fetch['cidade_pesquisa'] ? ' selected="selected"' : '').'>'.$fetch['cidade']."</option>\n";
					}
				}
			} else {
			?>
				<option value="">Selecione um estado</option>
			</select>
			<?php } ?>
		</td>
	</tr>
	<?php
		if(in_array($login_fabrica,array(30,35))){
	?>
	<tr>
		<td align="right" width="10%"></td>

        <td align="left" width="30%">
        	<label>Pesquisa CNPJ</label><br />
        	<input class='frm' type="text" name="pesquisa_cnpj" id="pesquisa_cnpj" style="float: left; width: 143px;" maxlength="18" onkeypress='return SomenteNumero(event)' >
        </td>
        <td align="left" width="30%">
        	<label>Pesquisa RAZÃO SOCIAL</label><br />
        	<input class='frm' type="text" name="razao_social" id="razao_social" style="float: left; width: 143px;" maxlength="100">

        <td>
	</tr>

	<?php
		}
	?>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
<? if (!in_array($login_fabrica,array(30,124,126))) {?>
	<tr>
		<td align="right"></td>
		<td align="left" colspan="2">
			Linhas <br/>
			<div style="width: 580px; float: left;">
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="branca" value="BRANCA" id="branca" onClick="selecionaLinha('branca')"
					<?php
					if (in_array("BRANCA", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> BRANCA
				</div>
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="marrom" value="MARROM" id="marrom" onClick="selecionaLinha('marrom')"
					<?php
					if (in_array("MARROM", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> MARROM
				</div>
				<div style="width: 260px; float: left;">
					<input type="checkbox" name="eletroportateis" value="ELETROPORTATEIS" id="eletroportateis" onClick="selecionaLinha('eletroportateis')"
					<?php
					if (in_array("ELETROPORTATEIS", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> ELETROPORTÁTEIS
				</div>
			</div>
			<div style="width: 580px; float: left;">
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="informatica" value="INFORMATICA" id="informatica" onClick="selecionaLinha('informatica')"
					<?php
					if (in_array("INFORMATICA", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> INFORMÁTICA
				</div>
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="ferramentas" value="FERRAMENTAS" id="ferramentas" onClick="selecionaLinha('ferramentas')"
					<?php
					if (in_array("FERRAMENTAS", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> FERRAMENTAS
				</div>
				<div style="width: 260px; float: left;">
					<input type="checkbox" name="lavadoras" value="LAVADORAS DE ALTA PRESSAO" id="ferramentas" onClick="selecionaLinha('lavadoras')"
					<?php
					if (in_array("LAVADORAS DE ALTA PRESSAO", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> LAVADORAS DE ALTA PRESSÃO
				</div>
			</div>
			<div style="width: 480px; float: left;">
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="outras" value="OUTRAS" id="outras" onClick="selecionaLinha('outras')"
					<?php
					if (in_array("OUTRAS", $linhas)) {
						echo ' checked="checked" ';
					}
					?>
					/> OUTRAS
				</div>
				<div style="width: 160px; float: left;">
					<input type="checkbox" name="linhas" value="TODAS" id="todas_linhas" onClick="marcaTodas('linhas')"
					<?php
					if ($todas_linhas == "TODAS") {
						echo ' checked="checked" ';
					}
					?>
					/> TODAS
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td align="right"></td>
		<td align="left" colspan="2">
			Fábricas de interesse <br/>
			<?php
			if ($login_fabrica <> 10) {
				echo '<div style="width: 580px; float: left;">';
					echo '<div style="width: 290px; float: left;">';
						echo '<input type="radio" name="fabricaz" value="' , $login_fabrica , '"';
						if ($fabricaz == $login_fabrica or in_array($login_fabrica,array(124,126,35,30))) {
							echo ' checked="checked" ';
						}
				   		echo '/> Minha fábrica';
					echo '</div>';
					if(!in_array($login_fabrica,array(30,124,126))) {
						echo '<div style="width: 290px; float: left;">';
							echo '<input type="radio" name="fabricaz" value="-1"';
							if ($fabricaz == "-1") {
								echo ' checked="checked" ';
							}
							echo '/> Outras';
						echo '</div>';
					}
				echo '</div>';
			} else {
				$not_in_fabricas = '108, 93, 47, 89, 63, 92, 8, 14, 66, 5, 43, 61, 77, 76, 110, 78, 107, 112, 113, 75, 111, 109, 10,46,119';
				$not_in_marcas = '131, 178, 177, 184';

				$sql = "
						SELECT
						fabrica,
						ativo_fabrica,
						nome,
						'f' AS fabrica_marca
						FROM tbl_fabrica
						where ativo_fabrica = 't'
						AND fabrica NOT IN ($not_in_fabricas)

						/*UNION

						SELECT
						tbl_marca.marca,
						tbl_marca.ativo,
						tbl_marca.nome,
						'm' AS fabrica_marca
						FROM tbl_fabrica
						JOIN tbl_marca
						ON tbl_fabrica.fabrica = tbl_marca.fabrica
						AND tbl_marca.ativo = 't'
						where tbl_fabrica.ativo_fabrica = 't'
						AND marca NOT IN ($not_in_marcas)
						*/
						ORDER BY nome
					";
				$res = pg_query($con, $sql);
				$rows = pg_num_rows($res);

				if ($rows > 0) {
					echo '<div style="width: 580px; float: left;">';
						echo '<input type="hidden" name="total_fab" id="total_fab" value="' , $rows , '" />';
						for ($i = 0; $i < $rows; $i++) {
							$id_fabrica   = pg_fetch_result($res, $i, 'fabrica');
							$nome_fabrica = ucwords(strtolower(trim(pg_fetch_result($res, $i, 'nome'))));
							$fabrica_marca = pg_fetch_result($res, $i, 'fabrica_marca');

							$literals = array (
												"Delonghi" => "DeLonghi",
												"Dwt" => "DWT",
												"Taurus Ferramentas Premium" => "Taurus",
												"Ibbl" => "IBBL",
												"Nks" => "NKS"
											);

							if (array_key_exists($nome_fabrica, $literals)) {
								$nome_fabrica = $literals["$nome_fabrica"];
							}

							echo '<div style="float: left; width: 145px;">';
								$cvalue = $fabrica_marca . ':' . $id_fabrica;
								echo '<input type="checkbox" name="fabrica_' , $i , '" value="' , $cvalue , '" id="fabrica_' , $i , '" onClick="selecionaFabrica(\'fabrica_' , $i , '\')"';
								if (in_array($cvalue, $a_fabricas)) {
									echo ' checked="checked" ';
								}
								echo '/> ' , $nome_fabrica;
						   	echo '</div>';

						}
						echo '<div style="float: left; width: 145px;">';
							echo '<input type="checkbox" name="fabrica_outras" value="1"';
							if ($fabrica_outras == "1") {
								echo ' checked="checked" ';
							}
					   		echo '/> Outras';
						echo '</div>';
						echo '<div style="float: left; width: 120px;">';
							echo '<input type="checkbox" name="todas_fabricas" id="fabricas" onClick="marcaTodas(\'fabricas\')" /> Todas';
						echo '</div>';
					echo '</div>';
				}

			}

			?>
		</td>
	</tr>
<?}else{?>
	<input type='hidden' value='<?=$login_fabrica?>' name='fabricaz'/>
<?}?>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="submit" name="consultar" value="Consultar" />
		</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	</form>
</table><br/>

<?php

	echo $resultado;
	include 'rodape.php';

?>

