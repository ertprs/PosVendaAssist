<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";

include 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

include_once '../class/AuditorLog.php';


if($_POST["desfazer_excluir"]){

	

	$os 			 = $_POST["os"];
	$motivo          = $_POST['motivo'];
    $programa_insert = $_SERVER['PHP_SELF'];
	
    $sql_os_excluida = "SELECT fabrica, os 
			    		FROM tbl_os_excluida 
			    		WHERE os = $os 
			    		and fabrica = $login_fabrica ";
    $res_os_excluida = pg_query($con, $sql_os_excluida);

    $res = pg_query($con,"BEGIN TRANSACTION");
    if(pg_num_rows($res_os_excluida)>0){    	

    	$sqlRemove = "DELETE FROM tbl_os_excluida WHERE os = $os and fabrica = $login_fabrica ";
    	$resRemove = pg_query($con, $sqlRemove);
    	$msg_erro .= pg_last_error($con);

		$sql = "
	            UPDATE  tbl_os
	            SET     excluida = FALSE, 
	            fabrica = $login_fabrica 
	            WHERE   os = $os
	        ";
	    $res = pg_query($con, $sql);
	    $msg_erro .= pg_last_error($con);

	    $mensagem = 'Desfazendo a exclusão da OS em  '.date("d-m-Y H:i").' por '.$login_login. " Motivo: ". utf8_decode($motivo);

	    if($motivo != ""){
	        $sql = "INSERT INTO tbl_os_interacao
	                (programa,fabrica, os, admin, comentario, interno, exigir_resposta)
	                VALUES
	                ('$programa_insert',$login_fabrica, $os, $login_admin, '$mensagem', TRUE, FALSE)";
	        $res = pg_query($con,$sql);
	        $msg_erro .= pg_last_error($con);
	    }

	    if(strlen(trim($msg_erro))==0){
		     //Desfazendo a exclusão da OS log 
		    $AuditorLog = new AuditorLog;
		    $dados = 'Desfazendo a '. utf8_encode("exclusão").' da OS em  '.date("d-m-Y H:i").' por '.$login_login. " Motivo: ". $motivo ;
		    $PrimaryKey = $login_fabrica . '*' . $os;
		    $Table = 'tbl_os';
		    $retorno_auditor = $AuditorLog->enviarLog("INSERT", $Table, $PrimaryKey, 'relatorio_os_excluida.php', $dados);
		    if($retorno_auditor == false){
		    	$msg_erro .= "Falha ao registrar o log"; 
		    }
		}
	}

    if(strlen(trim($msg_erro))>0){
    	$res = pg_query($con,"ROLLBACK TRANSACTION");
    	echo json_encode(array("erro"=>"ok"));
    }else{
		$res = pg_query($con,"COMMIT TRANSACTION");    	
		echo json_encode(array("result"=>"ok"));
    }




        

	exit; 
}



if ($_POST || $_GET["os"]) {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];
	$serie              = $_POST['serie'];
	$nota_fiscal        = $_POST['nota_fiscal'];
	$consumidor         = $_POST['consumidor'];
	$os                 = $_REQUEST["os"];

	if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$produto_referencia}'))
                    OR
                    (UPPER(descricao) = UPPER('{$produto_descricao}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = traduz("Produto não encontrado");
			$msg_erro["campos"][] = traduz("produto");
		} else {
			$produto = pg_fetch_result($res, 0, "produto");
		}
	}

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = traduz("Posto não encontrado");
			$msg_erro["campos"][] = traduz("posto");
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if ((!strlen($data_inicial) or !strlen($data_final)) && empty($os)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = traduz("data");
	} else if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = traduz("data");
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = traduz("Data Final não pode ser menor que a Data Inicial");
				$msg_erro["campos"][] = traduz("data");
			} else {
				if(in_array($login_fabrica, array(152,180,181,182))) {
					$cond_data = " AND tbl_os_excluida.data_exclusao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
					$cond_data_union = " AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
				}else{
					$cond_data = " AND tbl_os_excluida.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
					$cond_data_union = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
				}
			}
		}
	}

	if(in_array($login_fabrica,[152,179,180,181,182,186,191]) or $replica_einhell){
		$campo_auditoria = "(
                               SELECT justificativa
                                 FROM tbl_auditoria_os
                                WHERE os     = tbl_os_excluida.os
                                  AND cancelada IS NOT NULL
                             ORDER BY auditoria_os DESC LIMIT 1) AS justificativa, ";
		$campo_auditoria_union = "(
                                     SELECT justificativa
                                       FROM tbl_auditoria_os
                                      WHERE os        = tbl_os.os
                                        AND cancelada IS NOT NULL
                                   ORDER BY auditoria_os DESC LIMIT 1) AS justificativa, ";
	}

	if (!count($msg_erro["msg"])) {
		if (!empty($produto)){
			if (isset($fabrica_usa_subproduto)) {
				$join_os_produto 	       = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_excluida.os ";
				$join_os_produto_union 	   = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os ";

				$join_os_produto_union = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os ";
				$cond_produto = " AND tbl_os_produto.produto = {$produto} ";
			} else {
				$cond_produto       = " AND tbl_os_excluida.produto = {$produto} ";
				$cond_produto_union = " AND tbl_os.produto = {$produto} ";
			}
		}

		if (!empty($posto)) {
			$cond_posto 	  = " AND tbl_os_excluida.posto = {$posto} ";
			$cond_posto_union = " AND tbl_os.posto = {$posto} ";
		}

		if (!empty($serie)) {
			if (isset($fabrica_usa_subproduto)) {
				$join_os_produto = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_excluida.os ";
				$join_os_produto_union = " LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os ";

				$cond_serie = " AND tbl_os_produto.serie = '{$serie}' ";
			} else {
				$cond_serie 	  = " AND tbl_os_excluida.serie = '{$serie}' ";
				$cond_serie_union = " AND tbl_os.serie = '{$serie}' ";
			}
		}

		if (!empty($nota_fiscal)) {
			$cond_nota_fiscal 	    = " AND tbl_os_excluida.nota_fiscal = '{$nota_fiscal}' ";
			$cond_nota_fiscal_union = " AND tbl_os.nota_fiscal = '{$nota_fiscal}' ";
		}

		if (!empty($consumidor)) {
			$cond_consumidor = " AND UPPER(fn_retira_especiais(tbl_os_excluida.consumidor_nome)) ~ UPPER(fn_retira_especiais('{$consumidor}')) ";
			$cond_consumidor_union = " AND UPPER(fn_retira_especiais(tbl_os.consumidor_nome)) ~ UPPER(fn_retira_especiais('{$consumidor}')) ";
		}

		if (!empty($os)) {
			if (ctype_digit($os)) {

				$cond_os 	   = " AND ((tbl_os_excluida.sua_os like '{$os}-%' or tbl_os_excluida.os = {$os}) AND tbl_os_excluida.fabrica = {$login_fabrica} ) ";
				$cond_os_union = " AND ((tbl_os.sua_os like '{$os}-%' or tbl_os.os = {$os}) AND tbl_os.fabrica = {$login_fabrica} ) ";

			} else {
				$conteudo = explode("-", $os);
                $sua_os_numero    = $conteudo[0];
                $sua_os_sequencia = $conteudo[1];

				$cond_os = " AND tbl_os_excluida.sua_os like '{$sua_os_numero}-%' AND tbl_os_excluida.fabrica = {$login_fabrica}  ";
				$cond_os_union = " AND tbl_os.sua_os like '{$sua_os_numero}-%' AND tbl_os.fabrica = {$login_fabrica}  ";

			}
		}

		if (!isset($fabrica_usa_subproduto)) {
			$column_produto            = " tbl_produto.referencia_fabrica AS produto_referencia_fabrica, (tbl_produto.referencia || ' - ' || tbl_produto.descricao ) AS produto,";

			$column_serie              = "tbl_os_excluida.serie,";
			$column_serie_union        = "tbl_os_produto.serie,";

			$column_defeito_constatado = "tbl_defeito_constatado.descricao AS defeito_constatado,";
			//modificado de join para left por causa das os de entrega técnica da esab

			$join_produto              		 = "LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_excluida.produto";
			$join_produto_union              = "
			LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto";

			$join_defeito_constatado   		 = "LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_excluida.defeito_constatado";
			$join_defeito_constatado_union   = "LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado";
		}

		if (!isset($_POST["gerar_excel"])) {
			$limit = "LIMIT 501";
		}

		$sql = "SELECT DISTINCT ON (o.os) * FROM (
                SELECT
					tbl_os_excluida.os,
					tbl_os_excluida.sua_os,
                    COALESCE(tbl_os.fabrica, tbl_os_excluida.fabrica, 0) AS fabrica_os,
					TO_CHAR(tbl_os_excluida.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					tbl_posto.nome AS posto,
					tbl_os_excluida.nota_fiscal,
					tbl_os_excluida.defeito_reclamado_descricao AS defeito_reclamado,
					{$column_produto}
					{$column_serie}
					{$column_defeito_constatado}
					COALESCE(tbl_os_excluida.consumidor_nome,     tbl_os_excluida.consumidor_nome)     AS consumidor_nome,
					COALESCE(tbl_os_excluida.consumidor_estado,   tbl_os_excluida.consumidor_estado)   AS consumidor_estado,
					COALESCE(tbl_os_excluida.consumidor_cidade,   tbl_os_excluida.consumidor_cidade)   AS consumidor_cidade,
					COALESCE(tbl_os_excluida.consumidor_bairro,   tbl_os_excluida.consumidor_bairro)   AS consumidor_bairro,
					COALESCE(tbl_os_excluida.consumidor_endereco, tbl_os_excluida.consumidor_endereco) AS consumidor_endereco,
					COALESCE(tbl_os_excluida.consumidor_numero,   tbl_os_excluida.consumidor_numero)   AS consumidor_numero,
					tbl_os_excluida.revenda_nome AS revenda,
					tbl_admin.nome_completo AS admin,
					$campo_auditoria
					TO_CHAR(tbl_os_excluida.data_exclusao, 'DD/MM/YYYY') AS data_de_exclusao,
					tbl_os_excluida.motivo_exclusao,
					tbl_os_excluida.data_exclusao,
					tbl_os_status.observacao,
					tbl_os.admin_excluida
				FROM tbl_os_excluida
				JOIN tbl_posto     ON tbl_posto.posto    = tbl_os_excluida.posto
           LEFT JOIN tbl_admin     ON tbl_admin.admin    = tbl_os_excluida.admin
           LEFT JOIN tbl_os        ON tbl_os.os          = tbl_os_excluida.os
		   LEFT JOIN tbl_os_status ON tbl_os_excluida.os = tbl_os_status.os AND tbl_os_status.status_os = 15
				{$join_os_produto}
				{$join_produto}
				{$join_defeito_constatado}
				WHERE tbl_os_excluida.fabrica = {$login_fabrica}
				{$cond_data}
				{$cond_posto}
				{$cond_produto}
				{$cond_serie}
				{$cond_nota_fiscal}
				{$cond_consumidor}
				{$cond_os}
				UNION
				SELECT
					tbl_os.os,
					tbl_os.sua_os,
                    tbl_os.fabrica,
					TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					tbl_posto.nome AS posto,
					tbl_os.nota_fiscal,
					tbl_os.defeito_reclamado_descricao AS defeito_reclamado,
					{$column_produto}
					{$column_serie_union}
					{$column_defeito_constatado}
					tbl_os.consumidor_nome,
					tbl_os.consumidor_estado,
					tbl_os.consumidor_cidade,
					tbl_os.consumidor_bairro,
					tbl_os.consumidor_endereco,
					tbl_os.consumidor_numero,
					tbl_os.revenda_nome AS revenda,
					tbl_admin.nome_completo AS admin,
					$campo_auditoria_union
					TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_de_exclusao,
					tbl_os.obs,
					tbl_os.data_digitacao,
					tbl_os_status.observacao,
					tbl_os.admin_excluida
				FROM tbl_os
				INNER JOIN tbl_posto    ON tbl_posto.posto = tbl_os.posto
				LEFT JOIN tbl_os_status ON tbl_os.os       = tbl_os_status.os    AND tbl_os_status.status_os = 15
				LEFT JOIN tbl_admin     ON tbl_admin.admin = tbl_os_status.admin
				{$join_os_produto_union}
				{$join_produto_union}
				{$join_defeito_constatado_union}
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.excluida IS TRUE
				{$cond_data_union}
				{$cond_posto_union}
				{$cond_produto_union}
				{$cond_serie_union}
				{$cond_nota_fiscal_union}
				{$cond_consumidor_union}
				{$cond_os_union}
			) AS o
			ORDER BY o.os DESC $limit";
		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_excluida-{$data}.xls";

			$colspan = 18;
			$xproduto_referencia_fabrica = "";
			if ($login_fabrica == 171) {
				$xproduto_referencia_fabrica = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Referência Fábrica")."</th>";
				$colspan = 19;
			}

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1' style='table-layout: fixed'>
					<thead>
						<tr>
							<th colspan='18' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >".traduz("
								RELATÓRIO DE ORDENS DE SERVIÇOS EXCLUÍDAS")."
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("OS")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Data Abertura")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Posto")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Nota Fiscal")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Defeito Reclamado")."</th>
							{$xproduto_referencia_fabrica}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Produto")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Série")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Defeito Constatado")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Nome")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Estado")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Cidade")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Bairro")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Endereço")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Consumidor Número")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Revenda")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Admin")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Data Exclusão")."</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz("Motivo Exclusão")."</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$os                  = pg_fetch_result($resSubmit, $i, "os");
				$sua_os              = pg_fetch_result($resSubmit, $i, "sua_os");
				$data_abertura       = pg_fetch_result($resSubmit, $i, "data_abertura");
				$posto               = pg_fetch_result($resSubmit, $i, "posto");
				$nota_fiscal         = pg_fetch_result($resSubmit, $i, "nota_fiscal");
				$defeito_reclamado   = pg_fetch_result($resSubmit, $i, "defeito_reclamado");
				$consumidor_nome     = pg_fetch_result($resSubmit, $i, "consumidor_nome");
				$consumidor_estado   = pg_fetch_result($resSubmit, $i, "consumidor_estado");
				$consumidor_cidade   = pg_fetch_result($resSubmit, $i, "consumidor_cidade");
				$consumidor_bairro   = pg_fetch_result($resSubmit, $i, "consumidor_bairro");
				$consumidor_endereco = pg_fetch_result($resSubmit, $i, "consumidor_endereco");
				$consumidor_numero   = pg_fetch_result($resSubmit, $i, "consumidor_numero");
				$revenda             = pg_fetch_result($resSubmit, $i, "revenda");
				$admin               = pg_fetch_result($resSubmit, $i, "admin");
				$admin_excluida      = pg_fetch_result($resSubmit, $i, "admin_excluida");
				$data_de_exclusao    = pg_fetch_result($resSubmit, $i, "data_de_exclusao");
				$motivo_exclusao     = convert(pg_fetch_result($resSubmit, $i, "motivo_exclusao"), 'ISO-8859-1');
				$observacao          = convert(pg_fetch_result($resSubmit, $i,"observacao"), 'ISO-8859-1');
                $produto_referencia_fabrica  = pg_fetch_result($resSubmit, $i, "produto_referencia_fabrica");

				$vproduto_referencia_fabrica = "";
				if ($login_fabrica == 171) {
					$vproduto_referencia_fabrica = "<td nowrap valign='top'>{$produto_referencia_fabrica}</td>";

				}
				$motivo_exclusao = strlen(trim($motivo_exclusao)) == 0  ? $observacao : $motivo_exclusao;

				if(in_array($login_fabrica,[152,179,186,191]) or $replica_einhell){
					$justificativa = convert(pg_fetch_result($resSubmit, $i, "justificativa"), 'ISO-8859-1');
					if(strlen($justificativa) > 0 or strlen($motivo_exclusao) == 0 ) {
						$motivo_exclusao .= $justificativa;
					}
				}

				if(!empty($admin_excluida)) {
					$sql = "SELECT nome_completo FROM tbl_admin WHERE admin = $admin_excluida";
					$resa = pg_query($con,$sql);
					$admin   =  (empty($admin) ) ? pg_fetch_result($resa, 0, 'nome_completo')  :  $admin;
					$sql = "SELECT TO_CHAR(data, 'DD/MM/YYYY') AS data FROM tbl_os_interacao WHERE os = $os  AND comentario ~ 'Ordem de Serviço cancelada pela fábrica:' ORDER BY data DESC LIMIT 1 ";
					$resd = pg_query($con, $sql);
					if(pg_num_rows($resd) > 0) {
						$data_de_exclusao = pg_fetch_result($resd, 0, 'data');
					}
				}

				if (!isset($fabrica_usa_subproduto)) {
					$produto            = pg_fetch_result($resSubmit, $i, "produto");
					$serie              = pg_fetch_result($resSubmit, $i, "serie");
					$defeito_constatado = pg_fetch_result($resSubmit, $i, "defeito_constatado");
				}

				$body .= "
				<tr>
					<td valign='top'>{$sua_os}</td>
					<td valign='top'>{$data_abertura}</td>
					<td nowrap valign='top'>{$posto}</td>
					<td valign='top'>{$nota_fiscal}</td>
					<td nowrap valign='top'>{$defeito_reclamado}</td>
					{$vproduto_referencia_fabrica}
				";

				if (isset($fabrica_usa_subproduto)) {
					$sqlOsProduto = "SELECT tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto, tbl_os_produto.serie, tbl_defeito_constatado.descricao AS defeito_constatado
									 FROM tbl_os_produto
									 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
									 LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado
									 WHERE tbl_os_produto.os = {$os}";
					$resOsProduto = pg_query($con, $sqlOsProduto);

					$body .= "
					<td nowrap valign='top'>
						<ul style='list-style-type: none;'>
					";
							while ($osProduto = pg_fetch_object($resOsProduto)) {
								$body .= "<li>{$osProduto->produto}</li>";
							}
					$body .= "
						</ul>
					</td>
					<td nowrap valign='top'>
						<ul style='list-style-type: none;'>
					";
							pg_result_seek($resOsProduto, 0);

							while ($osProduto = pg_fetch_object($resOsProduto)) {
								$body .= "<li>{$osProduto->serie}</li>";
							}
					$body .= "
						</ul>
					</td>
					<td nowrap valign='top'>
						<ul style='list-style-type: none;'>
					";
							pg_result_seek($resOsProduto, 0);

							while ($osProduto = pg_fetch_object($resOsProduto)) {
								$body .= "<li>{$osProduto->defeito_constatado}</li>";
							}
					$body .= "
						</ul>
					</td>
					";
				} else {
					$body .= "
					<td valign='top'>{$produto}</td>
					<td valign='top'>{$serie}</td>
					<td valign='top'>{$defeito_constatado}</td>
					";
				}

				$body .= "
					<td nowrap valign='top'>{$consumidor_nome}</td>
					<td valign='top'>{$consumidor_estado}</td>
					<td nowrap valign='top'>{$consumidor_cidade}</td>
					<td nowrap valign='top'>{$consumidor_bairro}</td>
					<td nowrap valign='top'>{$consumidor_endereco}</td>
					<td valign='top'>{$consumidor_numero}</td>
					<td nowrap valign='top'>{$revenda}</td>
					<td nowrap valign='top'>{$admin}</td>
					<td valign='top'>{$data_de_exclusao}</td>
					<td valign='top'>{$motivo_exclusao}</td>
				</tr>
				";
			}

			fwrite($file, $body);

			fwrite($file, "
						<tr>
							<th colspan='{$colspan}' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >".traduz("Total de ").pg_num_rows($resSubmit)."".traduz(" registros")."</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}

$layout_menu = "callcenter";
$title = traduz("RELATÓRIO DE ORDENS DE SERVIÇOS EXCLUÍDAS");
include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<style>
.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

table#resultado tr:nth-child(2n + 1) td {
    border-top-width: 4px !important;
    border-top-color: grey !important;

}
</style>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(["data_final", "data_inicial"]);
		$.autocompleteLoad(["produto", "posto"]);
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$(".desfazer").on('click' ,function(){
			var os = $(this).data("os");
			var sua_os = $(this).data("sua_os");

			if (confirm('Deseja realmente desfazer a exclusão da OS '+sua_os+' ?')){
				var motivo = prompt("Qual o Motivo da Exclusão da os "+sua_os+"? (Máx 150 caracteres)");
			}

			if(os > 0 && motivo.length > 0){
				$.ajax({
	                url: "relatorio_os_excluida.php",
	                type: "post",
	                data: { desfazer_excluir: true, os: os, motivo: motivo },
	                complete: function(data) {
	                    data = $.parseJSON(data.responseText);

	                    if (data.result == 'ok') {
	                    	alert("Operação realizada com sucesso"); 
	                        $(".linha_"+os).remove();
	                    } else {
	                       alert("Falha ao realizar operação"); 
	                    }
	                }
	            });
			}

		});
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>


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
	<b class="obrigatorio pull-right">  *<?=traduz('Campos obrigatórios ')?></b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?=traduz('Data Final')?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?=traduz('Código Posto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?=traduz('Nome Posto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("serie", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='serie'><?=traduz('Número de Série')?></label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" name="serie" id="serie" class='span12' value="<? echo $serie ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='nota_fiscal'><?=traduz('Nota Fiscal')?></label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" name="nota_fiscal" id="nota_fiscal" class='span12' value="<? echo $nota_fiscal ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='consumidor'><?=traduz('Nome do Consumidor')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="consumidor" id="consumidor" class='span12' value="<? echo $consumidor ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='os'><?=traduz('Número da OS')?></label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" name="os" id="os" class='span12' value="<? echo $os ?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
</form>
</div>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
		echo "<br />";

		if (pg_num_rows($resSubmit) > 500) {
			$count = 500;
			?>
			<div id='registro_max'>
				<h6><?=traduz('Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.')?></h6>
			</div>
		<?php
		} else {
			$count = pg_num_rows($resSubmit);
		}
		?>
		<table style="max-width: 95%; table-layout: fixed" id="resultado" class='table table-bordered' >
			<thead>
				<tr class='titulo_coluna' >
					<th><?=traduz('OS')?></th>
					<th><?=traduz('Data Abertura')?></th>
					<th width="200px"><?=traduz('Posto')?></th>
					<th><?=traduz('Nota Fiscal')?></th>
					<th><?=traduz('Defeito Reclamado')?></th>
					<?php if ($login_fabrica == 171) {?>
					<th><?=traduz('Referência Fábrica')?></th>
					<?php }?>
					<th><?=traduz('Produto')?></th>
					<th><?=traduz('Série')?></th>
					<th><?=traduz('Defeito Constatado')?></th>
					<th><?=traduz('Consumidor')?></th>
					<th><?=traduz('Revenda')?></th>
					<th><?=traduz('Ação')?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$os                         = pg_fetch_result($resSubmit, $i, "os");
					$sua_os                     = pg_fetch_result($resSubmit, $i, "sua_os");
					$data_abertura              = pg_fetch_result($resSubmit, $i, "data_abertura");
					$posto                      = pg_fetch_result($resSubmit, $i, "posto");
					$nota_fiscal                = pg_fetch_result($resSubmit, $i, "nota_fiscal");
					$defeito_reclamado          = pg_fetch_result($resSubmit, $i, "defeito_reclamado");
					$consumidor_nome            = pg_fetch_result($resSubmit, $i, "consumidor_nome");
					$consumidor_estado          = pg_fetch_result($resSubmit, $i, "consumidor_estado");
					$consumidor_cidade          = pg_fetch_result($resSubmit, $i, "consumidor_cidade");
					$consumidor_bairro          = pg_fetch_result($resSubmit, $i, "consumidor_bairro");
					$consumidor_endereco        = pg_fetch_result($resSubmit, $i, "consumidor_endereco");
					$consumidor_numero          = pg_fetch_result($resSubmit, $i, "consumidor_numero");
					$revenda                    = pg_fetch_result($resSubmit, $i, "revenda");
					$admin                      = pg_fetch_result($resSubmit, $i, "admin");
					$admin_excluida             = pg_fetch_result($resSubmit, $i, "admin_excluida");
					$data_de_exclusao           = pg_fetch_result($resSubmit, $i, "data_de_exclusao");
					$produto_referencia_fabrica = pg_fetch_result($resSubmit, $i, "produto_referencia_fabrica");
					$observacao                 = (pg_fetch_result($resSubmit, $i,"observacao"));
					$motivo_exclusao            = Convert(pg_fetch_result($resSubmit, $i, "motivo_exclusao"), 'latin1');
					$motivo_exclusao            = strlen(trim($motivo_exclusao)) == 0  ? $observacao : $motivo_exclusao;
					$motivo_exclusao            = trim($motivo_exclusao);
					$fabrica_os                 = pg_fetch_result($resSubmit, $i, 'fabrica_os');
					$os_cancelada               = $fabrica_os != '0';

					if(in_array($login_fabrica, [152,179,186,191]) or $replica_einhell){
						$justificativa = Convert(pg_fetch_result($resSubmit, $i, "justificativa"), 'latin1');
                        if (preg_match('/[.!?/]$/', $motivo_exclusao) === 0) {
                            $motivo_exclusao .= '. ';
                        }
						$motivo_exclusao .= $justificativa;
					}

                    $consumidor = trim($consumidor_nome) . '<br />' . trim( $consumidor_endereco ) ;
                    if(empty($consumidor_numero) == false) {
                        $consumidor .= ', ' . $consumidor_numero ;
                    }
                    $consumidor .= '<br />' ;
                    if(empty($consumidor_bairro) != false) {
                        $consumidor .= ' - ' ;
                    }
                    $consumidor .= trim($consumidor_cidade) . ' - ' . $consumidor_estado ;

					if(!empty($admin_excluida)) {
						$sql = "SELECT nome_completo from tbl_admin where admin = $admin_excluida";
						$resa = pg_query($con,$sql);
						$admin   =  (empty($admin) ) ? pg_fetch_result($resa, 0, 'nome_completo')  :  $admin;
						$sql = "SELECT to_char(data, 'DD/MM/YYYY') as data FROM tbl_os_interacao WHERE os = $os  and comentario ~'Ordem de Serviço cancelada pela fábrica:' order by data desc limit 1 ";
						$resd = pg_query($con, $sql);
						if(pg_num_rows($resd) > 0) {
							$data_de_exclusao = pg_fetch_result($resd, 0, 'data');
						}
					}


					if (!isset($fabrica_usa_subproduto)) {
						$produto            = pg_fetch_result($resSubmit, $i, "produto");
						$serie              = pg_fetch_result($resSubmit, $i, "serie");
						$defeito_constatado = pg_fetch_result($resSubmit, $i, "defeito_constatado");
					}

                    $cor = ($icor % 2 == 0) ? '#f5f5f5' : '#fff';
					?>

                        <tr bgcolor="<?=$cor;?>" class='linha_<?=$os?>'>
                        <td>
                            <?php
                            if ($os_cancelada) {
                                echo('<a href="os_press.php?os=' . $os . '" target="_blank">' . $sua_os . '</a>');
                            } else {
                                echo( $sua_os );
                            }
                            ?>
                        </td>
						<td><?=$data_abertura?></td>
						<td><?=$posto?></td>
						<td><?=$nota_fiscal?></td>
						<td><?=$defeito_reclamado?></td>
						<?php if ($login_fabrica == 171) {?>
						<td nowrap><?=$produto_referencia_fabrica?></td>
						<?php }?>

						<?php
						if (isset($fabrica_usa_subproduto)) {
							$sqlOsProduto = "SELECT tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto, tbl_os_produto.serie, tbl_defeito_constatado.descricao AS defeito_constatado
											 FROM tbl_os_produto
											 INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
											 LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os_produto.defeito_constatado
											 WHERE tbl_os_produto.os = {$os}";
							$resOsProduto = pg_query($con, $sqlOsProduto);
							?>

							<td nowrap>
								<ul style="list-style-type: none;">
									<?php
									while ($osProduto = pg_fetch_object($resOsProduto)) {
										echo "<li>{$osProduto->produto}</li>";
									}
									?>
								</ul>
							</td>
							<td nowrap>
								<ul style="list-style-type: none;">
									<?php
									pg_result_seek($resOsProduto, 0);

									while ($osProduto = pg_fetch_object($resOsProduto)) {
										echo "<li>{$osProduto->serie}</li>";
									}
									?>
								</ul>
							</td>
							<td nowrap>
								<ul style="list-style-type: none;">
									<?php
									pg_result_seek($resOsProduto, 0);

									while ($osProduto = pg_fetch_object($resOsProduto)) {
										echo "<li>{$osProduto->defeito_constatado}</li>";
									}
									?>
								</ul>
							</td>
						<?php
						} else {
						?>
							<td><?=$produto?></td>
							<td><div class="truncate" style="width:120px"><?=$serie?></div></td>
							<td><?=$defeito_constatado?></td>
						<?php
						}
						?>
						<td nowrap><?=$consumidor?></td>
						<td><?=$revenda?></td>
						<?php if($login_fabrica == 35){ ?>
							<td rowspan="2">
								<button type='button' class='btn btn-danger desfazer' data-os="<?=$os?>" data-sua_os="<?=$sua_os?>">Desfazer</button>
	                        </td>
                    	<?php } ?>
					</tr>

                    <tr bgcolor="<?=$cor;?>" class='linha_<?=$os?>'>
                        <td colspan="2">
                            <span class="label">Data</span>&nbsp;<?php echo($data_de_exclusao);?>
                        </td>

                        <td colspan="3">
                            <?php
                            if(!empty($admin)) {
                                echo("<span class='label'>Respons&aacute;vel</span>&nbsp;$admin") ;
                            }
                            ?>
                        </td>

                        <td colspan="5">
							<span class="label">Motivo</span>&nbsp;<?=Convert($motivo_exclusao, 'ISO-8859-1', 'latin1,UTF-8');?>
                        </td>                        
                    </tr>
                    <?php
                    $icor++;
				}
				?>
			</tbody>
		</table>

		<?php
		if ($count > 50) {
		?>
			<script>
				//$.dataTableLoad({ table: "#resultado" });
			</script>
		<?php
		}
		?>

		<br />

		<?php
		if(isset($_GET["os"])){
			$jsonPOST = excelGetToJson($_GET);
		}else{
			$jsonPOST = excelPostToJson($_POST);
		}
		?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
		</div>
	<?php
	}else{
	?>
		<div class="container"><div class="alert"><h4><?=traduz('Nenhum resultado encontrado')?></h4></div></div>
	<?php
	}
}

include "rodape.php";
?>
