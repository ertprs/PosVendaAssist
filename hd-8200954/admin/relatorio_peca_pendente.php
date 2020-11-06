<?php
$admin_privilegios = "financeiro,gerencia,call_center";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = traduz("RELATÓRIO DE OS/PEDIDOS COM PEÇAS PENDENTES");
# Pesquisa pelo AutoComplete AJAX

flush();
//Recebe Data
if($_REQUEST) {
	if (strlen($_GET['data_inicial']) > 0)
		$data_inicial = $_POST['data_inicial'];

	if (strlen($_GET['data_final']) > 0)
		$data_final   = $_POST['data_final'];

	if (strlen($_GET['numero_os']) > 0)
		$numero_os   = $_POST['numero_os'];
	//Valida data inicial e data final
	if ($data_inicial && $data_final) {
		if($data_inicial && $data_final){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
				$msg_erro = "Data Inválida";

			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
				$msg_erro = "Data Inválida";

			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";

			if(strlen($msg_erro)==0){
				if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
					$msg_erro = "Data Inválida.";
				}
			}

			if ($login_fabrica == 152) {
				$interval_dias = ' -90 day';
				$interval_diass = ' 90 ';
			}else if (in_array($login_fabrica, array(169,170))){
				$interval_dias = ' -120 day';
				$interval_diass = ' 120 ';
			}else{
				$interval_dias = ' -31 day';
				$interval_diass = ' 31 ';
			}

			if ($telecontrol_distrib) {
				$interval_dias = ' -365 day';
				$interval_diass = ' 365 ';
			}

			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . $interval_dias)) {

				$msg_erro = "Perí­odo não pode ser maior de".$interval_diass."dias";

			}

		}else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
			$msg_erro = "Data Inválida.";
		}
	}else{
		if (empty($numero_os)) {
			$msg_erro .=" Favor preencher a data para a pesquisa" ;
		}
	}

	if (strlen($_GET['status_os']) > 0)
		$status_os   = $_POST['status_os'];
	if (strlen($_GET['qtde_dias']) > 0)
		$qtde_dias   = $_POST['qtde_dias'];
	if (strlen($_GET['mais_menos']) > 0)
		$mais_menos   = $_POST['mais_menos'];

	if (strlen($_GET['produto_referencia']) > 0)
		$produto_referencia = $_POST['produto_referencia'];

	if (strlen($_GET['numero_os']) > 0)
		$numero_os = $_POST['numero_os'];

	if (strlen($_GET['produto_descricao']) > 0)
		$produto_descricao = $_POST['produto_descricao'];

	if (strlen($_GET['peca_referencia']) > 0)
		$peca_referencia = $_POST['peca_referencia'];

	if (strlen($_GET['peca_descricao']) > 0)
		$peca_descricao = $_POST['peca_descricao'];

	if (strlen($_GET['referencia']) > 0)
		$referencia = $_POST['referencia'];

	if (strlen($_REQUEST['estado_posto_autorizado']) > 0)
		$estado_posto_autorizado = $_REQUEST['estado_posto_autorizado'];

	if (strlen($_GET['descricao']) > 0)
		$descricao = $_POST['descricao'];


	$codigo_posto = $_POST['posto_referencia'];
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
			FROM tbl_posto_fabrica
			WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
		$res = @pg_query($con,$sql);
		if(pg_num_rows($res)<1){
			$msg_erro .= " Posto Inválido ";
		}else{
			$posto = pg_fetch_result($res,0,0);
			if(strlen($posto)==0){
				$msg_erro .= " Selecione o Posto! ";
			}else{
				$cond_3 = " AND tmp_produto_peca.posto = $posto";
			}		}
	}

	if(strlen($_GET['numero_os']) > 0){
		$estado = $_GET['numero_os'];
	}

	if(strlen($_GET['estado']) > 0){
		$estado = $_GET['estado'];
	}

	if(strlen($_GET['cidade']) > 0){
		$cidade = $_GET['cidade'];
	}

	if(strlen($_POST['pendente'])>0){
		$dia_pendente = $_POST['pendente'];
	}

	if ($login_fabrica == 158 && $_POST["pedido"]) {
		$pedido = trim($_POST["pedido"]);
	}
}

if($btn_acao=="Consultar"){

	if(strlen($msg_erro)==0){

		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
		$cont = 0;
		while($nova_data_inicial <= $nova_data_final){
			$nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
			$cont++;
		}

		if($telecontrol_distrib){
			if($cont > 365)
				$msg_erro="Perí­odo Não Pode ser Maior que 12 meses";
		}else{
			if($cont > 31)
				$msg_erro="Período Não Pode ser Maior que 31 Dias";
		}
		$cond_produto = ' AND 1=1 ';
		$cond_peca = ' AND 1=1 ';
		$cond_dias_pendentes = 'AND 1=1 ';
		$cond_estado = 'AND 1=1 ';
		$cond_cidade = ' AND 1=1 ';

		if (strlen($produto_referencia) > 0) {
			$cond_produto = " AND tmp_produto_peca.referencia_produto = '{$produto_referencia}'";
		}

		if (strlen($peca_referencia) > 0) {
			$cond_peca = " AND tmp_produto_peca.referencia_peca = '{$peca_referencia}'";
		}

		$qtde_dias = "";
		if ($login_fabrica == 43) {
			if (strlen($referencia) > 0) {
				$cond_2 = " AND (tbl_peca.referencia = '{$referencia}' OR tbl_peca.referencia_pesquisa = '{$referencia}') AND tbl_peca.fabrica = {$login_fabrica}";
			}
		}

		if (!empty($numero_os)) {
			$condOs = "AND tbl_os.os = {$numero_os} ";
		}

		if (strlen($estado) > 0) {
			$cond_estado = " AND tbl_posto.estado = '{$estado}' " ;
		}

		if (strlen($cidade) > 0) {
			$cond_cidade = " AND tbl_posto.cidade ILIKE '$cidade%' " ;
		}

		if ($login_fabrica == 72) {
			$cond_dias_pendentes = " AND ((current_date)::date - tmp_produto_peca.data_pedido::date) > ".$dia_pendente." " ;
		}

		if ((strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0) || empty($numero_os)) {
			$cond_interval = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
		}

		if ($telecontrol_distrib) {
			if ($tipo_pedido) {
				$cond_tipo_pedido = " AND tbl_pedido.tipo_pedido = {$tipo_pedido}";
			}
		}

		if ($login_fabrica == 30) {

			if ($posto) {
				$cond_posto = " AND tbl_os.posto = {$posto} ";
			}else{
				$cond_posto = " AND tbl_os.posto <> 6359 ";
			}

			if ($linha) {
				$cond_linha = " AND tbl_produto.linha = {$linha}";
			}

			if ($familia) {
				$cond_familia = " AND tbl_produto.familia = {$familia}";
			}

			if ($tipo_pedido) {
				$cond_tipo_pedido = " AND tbl_pedido.tipo_pedido = {$tipo_pedido}";
			}

			if ($uf_posto) {
				$cond_uf_posto = " AND tbl_posto_fabrica.contato_estado = '{$uf_posto}' ";
			}

		}else{

			if ($login_fabrica == 158) {

				if ($tipo_pedido) {
					$cond_tipo_pedido = " AND tbl_pedido.tipo_pedido = {$tipo_pedido}";
				}

				if ($posto)	{
					$cond_posto = " AND tbl_os.posto = {$posto} ";
					$cond_posto_pedido = " AND tbl_pedido.posto = {$posto} ";
				}

				$campoFlagGarantia = "tbl_tipo_atendimento.fora_garantia,";

				$joinTipoAtendimento = "JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tmp_produto_peca.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}";

			} else {

				$cond_posto = " AND tbl_os.posto <> 6359 ";

			}

		}

		if ($login_fabrica == 158 && !empty($pedido)) {
			$cond_pedido = " AND tbl_pedido.pedido = {$pedido} ";
		}

		if ($login_fabrica == 158 && !empty($estado_posto_autorizado)) {
			$condUFPosto = " AND tbl_posto_fabrica.contato_estado = '$estado_posto_autorizado'";
		}

		if($login_fabrica == 163){
			$join_163 = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica ";
			$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		}

		if(in_array($login_fabrica, array(11,172))) {
			$condEstoque = " LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca AND tbl_posto_estoque.posto = 4311 ";
			$column_estoque_distrib = ", coalesce(tbl_posto_estoque.qtde,0) AS estoque_distrib ";
		}

		######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
		if (in_array($login_fabrica, array(138))) {
			$sql = "
				SELECT
					tbl_os.os AS os,
					tbl_os.sua_os AS sua_os,
					tbl_os.posto AS posto,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada,
					tbl_os_produto.produto,
					tbl_os.tipo_atendimento,
					tbl_produto.referencia AS referencia_produto,
					tbl_produto.referencia_fabrica AS referencia_produto_fabrica,
					tbl_peca.referencia_fabrica AS referencia_peca_fabrica,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_fone,
					tbl_os_item.qtde,
					tbl_produto.descricao AS descricao_produto,
					tbl_peca.referencia AS referencia_peca,
					tbl_peca.peca, tbl_peca.descricao AS descricao_peca,
					tbl_posto_fabrica.contato_estado AS posto_estado,
					tbl_pedido.data AS data_pedido,
					tbl_pedido.pedido,
					tbl_pedido.tipo_pedido,
					tbl_status_pedido.descricao AS status_pedido,
					tbl_produto.linha,
					tbl_produto.familia,
					tbl_pedido_item.preco,
					tbl_peca.peso
				INTO TEMP tmp_produto_peca
				FROM tbl_os_produto
				JOIN tbl_os USING(os)
				JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
				JOIN tbl_pedido_item ON tbl_os_item.pedido = tbl_pedido_item.pedido AND (tbl_os_item.peca = tbl_pedido_item.peca  or tbl_os_item.pedido_item = tbl_pedido_item.pedido_item) AND (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada + qtde_faturada_distribuidor)  < tbl_pedido_item.qtde
				JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = {$login_fabrica}
				JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			";
		} else {
			$sql = "
				SELECT
					DISTINCT 
					tbl_os.os AS os,
					tbl_os.sua_os AS sua_os,
					tbl_os.posto AS posto,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY') AS finalizada,
					TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') AS data_lancamento_peca,
					tbl_os.produto,
					tbl_os.tipo_atendimento,
					tbl_produto.referencia AS referencia_produto,
					tbl_produto.referencia_fabrica AS referencia_produto_fabrica,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_fone,
					tbl_os_item.qtde,
					tbl_produto.descricao AS descricao_produto,
					tbl_peca.referencia AS referencia_peca,
					tbl_peca.referencia_fabrica AS referencia_peca_fabrica,
					tbl_peca.peca, tbl_peca.descricao AS descricao_peca,
					tbl_posto_fabrica.contato_estado AS posto_estado,
					tbl_pedido.data as data_pedido,
					tbl_pedido.pedido,
					tbl_pedido.tipo_pedido,
					tbl_status_pedido.descricao AS status_pedido,
					tbl_produto.linha,
					tbl_produto.familia,
					tbl_pedido_item.preco,
					tbl_pedido_item.qtde as qtde_pedido,
					tbl_pedido_item.qtde_cancelada,
					tbl_pedido_item.qtde_faturada,
					tbl_pedido_item.qtde_faturada_distribuidor,
					tbl_peca.peso

				INTO TEMP tmp_produto_peca_$login_admin
				FROM tbl_os
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
				JOIN tbl_pedido_item ON tbl_os_item.pedido = tbl_pedido_item.pedido AND ((tbl_os_item.pedido_item IS NULL and tbl_os_item.peca = tbl_pedido_item.peca) OR tbl_os_item.pedido_item = tbl_pedido_item.pedido_item) 				JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = {$login_fabrica}
				JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				$join_163			
			";
		}

		if ($login_fabrica == 80) {
			$sql .= " LEFT JOIN tbl_faturamento_item USING(pedido)";
		} else if ($login_fabrica == 2) { // HD 244957
			$sql .= " JOIN (SELECT pedido, pedido_item, peca FROM tbl_pedido_item WHERE qtde_cancelada < qtde) pd ON tbl_os_item.pedido = pd.pedido AND tbl_os_item.peca = pd.peca
				LEFT JOIN tbl_pedido_item_faturamento_item on pd.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
				LEFT JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item =  tbl_faturamento_item.faturamento_item ";		
		} else {
			$sql .= " LEFT JOIN tbl_faturamento_item  on tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca ";
		}

		$sql .= "
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os_item.fabrica_i = {$login_fabrica}
			AND tbl_pedido.finalizado IS NOT NULL
			$cond_posto
			$cond_uf_posto
			$cond_linha
			$cond_familia
			$cond_tipo_pedido
			$cond_163
			$cond_pedido
			$condUFPosto
			$condOs
			AND tbl_faturamento_item.faturamento IS NULL
			AND NOT (tbl_pedido.status_pedido in (14,13,4,18,31))";

		if($login_fabrica != 178){
			$sql .= " AND NOT (tbl_os.os IN (SELECT os FROM tbl_os_troca WHERE fabric = {$login_fabrica}))";
		}

		if (strlen($qtde_dias) == 0) {
			if (strlen($aux_data_inicial) > 0 AND strlen($aux_data_final) > 0) {
				$sql .= " AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00' AND '{$aux_data_final} 23:59' AND tbl_os.excluida IS NOT TRUE";
			}
		}

		if ($login_fabrica == 43){
			$sql .= " AND tbl_os.consumidor_revenda = '{$cons_reve}' ";
		}

		if (strlen($status_os) > 0) {
			if ($status_os == 'aberta') {
				/*Aberta*/
				$sql .= " AND tbl_os.finalizada IS NULL ";
				if (strlen($qtde_dias) > 0) {
					if ($mais_menos == 'mais'){
						$sql .= " AND tbl_os.data_abertura < (CURRENT_DATE - INTERVAL '{$qtde_dias} days')";
					}else{
						$sql .= " AND tbl_os.data_abertura >= (CURRENT_DATE - INTERVAL '{$qtde_dias} days')";
					}
				}
			} else if ($status_os=='fechada') {
				/*Fechada*/
				$sql .= " AND   NOT (tbl_os.finalizada IS NULL )";
				$qtde_dias = "";
			}
		}

		if($login_fabrica == 30) {
			$campos = ",tmp_produto_peca.referencia_peca,
				tmp_produto_peca.descricao_peca";
		}

		if(!isset($_POST['gerar_excel'])){
			$sql .= " limit 500 ;  ";
		}
		

		$sql .= "
			;
			SELECT * INTO temp tmp_produto_peca FROM tmp_produto_peca_$login_admin where (qtde_cancelada + qtde_faturada + qtde_faturada_distribuidor) < qtde_pedido 
			;
			CREATE INDEX tmp_produto_peca_os on tmp_produto_peca(os);
		";

		if ($login_fabrica == 122) {
			$into_tmp = " INTO TEMP tmp_wanke ";
		}

		$sql .= "
			SELECT DISTINCT
				tmp_produto_peca.os,
				tmp_produto_peca.sua_os,
				tmp_produto_peca.referencia_produto,
				tmp_produto_peca.referencia_produto_fabrica,
				tmp_produto_peca.referencia_peca_fabrica,
				tmp_produto_peca.referencia_peca,
				tmp_produto_peca.descricao_produto,
				tmp_produto_peca.descricao_peca,
				tmp_produto_peca.posto_estado,
				TO_CHAR(tmp_produto_peca.data_pedido,'DD/MM/YYYY') AS data_pedido,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_linha.nome AS nome_linha,
				tbl_familia.descricao AS nome_familia,
				tbl_tipo_pedido.descricao AS nome_tipo_pedido,
				{$campoFlagGarantia}
				tmp_produto_peca.consumidor_nome,
				tmp_produto_peca.consumidor_fone,
				tmp_produto_peca.pedido,
				tmp_produto_peca.status_pedido,
				tmp_produto_peca.qtde,
				tmp_produto_peca.data_digitacao,
				tmp_produto_peca.data_abertura,
				tmp_produto_peca.data_fechamento,
				tmp_produto_peca.data_lancamento_peca,
				tmp_produto_peca.finalizada,
				((current_date)::date - tmp_produto_peca.data_pedido::date) AS dias_pendentes,
				tmp_produto_peca.preco,
				tmp_produto_peca.qtde_pedido,
				tmp_produto_peca.qtde_cancelada,
				tmp_produto_peca.peso,
				tmp_produto_peca.peca
				$campos
				$into_tmp
				{$column_estoque_distrib}
			FROM tmp_produto_peca
			{$joinTipoAtendimento}
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_produto_peca.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tmp_produto_peca.posto
			JOIN tbl_linha ON tmp_produto_peca.linha = tbl_linha.linha
			JOIN tbl_familia ON tmp_produto_peca.familia = tbl_familia.familia
			JOIN tbl_tipo_pedido ON tmp_produto_peca.tipo_pedido = tbl_tipo_pedido.tipo_pedido
			LEFT JOIN tbl_posto_estoque ON tmp_produto_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = 4311
			WHERE 1 = 1
			$cond_produto
			$cond_peca
			$cond_3
			$cond_dias_pendentes
			$cond_estado
			$cond_cidade
			$condUFPosto
		";

		if ($login_fabrica == 158 || $telecontrol_distrib) {
			if (!empty($campoFlagGarantia)) {
				$campoFlagGarantia = "NULL AS fora_garantia,";
			}

			$sql .= "
				UNION
				SELECT
					null AS os,
					'' AS sua_os,
					'' AS referencia_produto,
					'' AS referencia_produto_fabrica,
					'' AS referencia_peca_fabrica,
					tbl_peca.referencia AS referencia_peca,
					'' AS descricao_produto,
					tbl_peca.descricao AS descricao_peca,
					tbl_posto_fabrica.contato_estado AS posto_estado,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome,
					'' AS nome_linha,
					'' AS nome_familia,
					tbl_tipo_pedido.descricao AS nome_tipo_pedido,
					{$campoFlagGarantia}
					'' AS consumidor_nome,
					'' AS consumidor_fone,
					tbl_pedido.pedido,
					tbl_status_pedido.descricao AS status_pedido,
					tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_cancelada AS qtde,
					'' AS data_digitacao,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_abertura,
					'' AS data_fechamento,
					TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizada,
					'' AS data_lancamento_peca,
					((current_date)::date - tbl_pedido.data::date) AS dias_pendentes,
					tbl_pedido_item.preco,
					tbl_pedido_item.qtde AS qtde_pedido,
					tbl_pedido_item.qtde_cancelada,
					tbl_peca.peso,
					tbl_peca.peca
					{$column_estoque_distrib}
				FROM tbl_pedido 
				JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
				JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada + qtde_faturada_distribuidor) < tbl_pedido_item.qtde
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
				LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_pedido_item.peca
				LEFT JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = 4311
				WHERE tbl_pedido.fabrica = {$login_fabrica}
				$cond_posto_pedido
				$cond_uf_posto
				$cond_tipo_pedido
				$cond_pedido
				$condUFPosto
				$condOs
				AND tbl_faturamento_item.faturamento IS NULL
				AND NOT (tbl_pedido.status_pedido = 14)
				AND tbl_pedido.pedido NOT IN (SELECT pedido FROM tmp_produto_peca)
				AND tbl_pedido.data BETWEEN '{$aux_data_inicial} 00:00' AND '{$aux_data_final} 23:59'
				AND tbl_pedido.finalizado IS NOT NULL
				ORDER BY data_pedido
			";
		} else {
			$sql .= "
				ORDER BY tmp_produto_peca.data_abertura,tmp_produto_peca.os
				$camposf
			";
		}	

		if(!isset($_POST['gerar_excel'])){
			$sql .= " limit 500 ";
		}

		$resC = pg_query($con,$sql);
		$total_reg = pg_num_rows($resC);

        if(isset($_POST['gerar_excel'])){
			$data                 = date("dmYHi");
			$tipo_arquivo         = $telecontrol_distrib ? 'csv' : 'xls';
			$arquivo_nome3        = "relatorio_peca_pendente-$login_fabrica-$data.$tipo_arquivo";
			$path                 = __DIR__ . DIRECTORY_SEPARATOR . 'xls/';
			$path_tmp             = "/tmp/assist/";
			$arquivo_completo     = $path.$arquivo_nome3;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome3;

			echo `rm -f $arquivo_completo_tmp `;
			echo `rm -f $arquivo_completo_tmp.zip `;
			echo `rm -f $arquivo_completo `;
			echo `rm -f $arquivo_completo.zip `;
			$fp = fopen ($arquivo_completo_tmp,"w");

			if ($login_fabrica == 72) {
				fputs ($fp, "OS \t  Produto \t. Descrição Produto\t  Peça \t Descrição Peça \t Qtde dias Pendentes \t Abertura \t Fechamento \t Cód. Posto \t Posto Nome \t Nome Consumidor \t Fone Consumidor \r\n");
			} else if($login_fabrica == 30) {

				fputs ($fp, "Pedido \t Data do Pedido \t Tipo do Pedido \t Cód. Posto \t Posto Nome\t Posto UF\t  Produto \t Descrição Produto\t Linha \t Famí­lia\t  Peça \t Descrição Peça \t Qtde dias Pendentes \r\n");

			} else if ($login_fabrica == 158) {
				fputs ($fp, "OS \t  Produto \t. Descrição Produto\t  Peça \t Descrição Peça \t Qtde \t Data Lançamento Peça \t Qtde dias Pendentes \t Abertura \t Fechamento \t Posto \t Posto Nome \t Pedido \t Status Pedido \t Tipo Pedido \r\n");
			} else if($login_fabrica == 136){
				fputs ($fp, "Data Pedido \t  Cod. Posto \t Nome Posto\t  Pedido \t OS \t Peca \t Desc. Peca \t Qtde \t Valor Unitario \t Peso Kg \r\n");
			} else if($login_fabrica == 171){
				fputs ($fp, "OS \t  Referência Fábrica \t  Produto \t. Descrição Produto \t  Referência Fábrica \t  Peça \t Descrição Peça \t Qtde \t Qtde dias Pendentes \t Abertura \t Fechamento \t Posto \r\n");
			} else if ($telecontrol_distrib) {
				if (in_array($login_fabrica, array(11,172))) {								
					fputs ($fp, "OS;Pedido;Produto;Descrição Produto;Peça;Descrição Peça;Qtde Selecionada;Qtde Estoque;Qtde dias Pendentes;Estoque Peça;Estoque Total;Abertura OS;Abertura Pedido;Posto;Recebido/Distribuidor \r\n");
				} else {			
					fputs ($fp, "OS;Pedido;Produto;Descrição Produto;Peça;Descrição Peça;Qtde;Qtde dias Pendentes;Estoque Peça;Estoque Total;Abertura;Fechamento;Posto;Recebido/Distribuidor \r\n");
				}
			} else if(in_array($login_fabrica, array(11,172))) {
				fputs ($fp, "OS \t  Produto \t. Descrição Produto\t  Peça \t Descrição PeÃ§a \t Qtde Selecionada\t Qtde EStoque \t Qtde dias Pendentes \t Abertura \t Fechamento \t Posto \r\n");
			} else {
				fputs ($fp, "OS \t  Produto \t. Descrição Produto\t  Peça \t Descrição Peça \t Qtde \t Qtde dias Pendentes \t Abertura \t Fechamento \t Posto \r\n");
			}

			for ($i = 0; $i < $total_reg; $i++) {
				$os                         = trim(pg_fetch_result($resC,$i,'os'));
				$sua_os                     = trim(pg_fetch_result($resC,$i,'sua_os'));
				$referencia_produto         = trim(pg_fetch_result($resC,$i,'referencia_produto'));
				$referencia_produto_fabrica = trim(pg_fetch_result($resC,$i,'referencia_produto_fabrica'));
				$referencia_peca_fabrica    = trim(pg_fetch_result($resC,$i,'referencia_peca_fabrica'));
				$descricao_produto          = trim(pg_fetch_result($resC,$i,'descricao_produto'));
				$referencia_peca            = trim(pg_fetch_result($resC,$i,'referencia_peca'));
				$descricao_peca             = trim(pg_fetch_result($resC,$i,'descricao_peca'));
				$codigo_posto               = trim(pg_fetch_result($resC,$i,'codigo_posto'));
				$data_abertura              = trim(pg_fetch_result($resC,$i,'data_abertura'));
				$data_fechamento            = trim(pg_fetch_result($resC,$i,'data_fechamento'));
				$data_lancamento_peca       = trim(pg_fetch_result($resC,$i,'data_lancamento_peca'));
				$dias_pendentes             = trim(pg_fetch_result($resC,$i,'dias_pendentes'));
				$consumidor_nome            = trim(pg_fetch_result($resC,$i,'consumidor_nome'));
				$consumidor_fone            = trim(pg_fetch_result($resC,$i,'consumidor_fone'));
				$qtde                       = trim(pg_fetch_result($resC,$i,'qtde'));
				$posto_nome                 = trim(pg_fetch_result($resC,$i,'nome'));
				$nome_linha                 = trim(pg_fetch_result($resC,$i,'nome_linha'));
				$nome_familia               = trim(pg_fetch_result($resC,$i,'nome_familia'));
				$pedido                     = trim(pg_fetch_result($resC,$i,'pedido'));
				$status_pedido              = trim(pg_fetch_result($resC,$i,'status_pedido'));
				$data_pedido                = trim(pg_fetch_result($resC,$i,'data_pedido'));
				$nome_tipo_pedido           = trim(pg_fetch_result($resC,$i,'nome_tipo_pedido'));
				$posto_estado               = trim(pg_fetch_result($resC,$i,'posto_estado'));
				$fora_garantia              = trim(pg_fetch_result($resC,$i,'fora_garantia'));
				$preco                      = trim(pg_fetch_result($resC,$i,'preco'));
				$peso                       = trim(pg_fetch_result($resC,$i,'peso'));
				$estoque_distrib            = trim(pg_fetch_result($resC,$i,'estoque_distrib'));
				$qtde_pedido 				= trim(pg_fetch_result($resC,$i,'qtde_pedido'));
				$qtde_cancelada				= trim(pg_fetch_result($resC,$i,'qtde_cancelada'));

				$descGarantia = "";

				if ($fora_garantia == 'f') {
					$descGarantia = ' - GARANTIA' ;
				}

				if ($nome_tipo_pedido != 'NTP') {
					unset($descGarantia);
				}

				if ($login_fabrica <> 171) {
					$escreve = "$sua_os\t$referencia_produto\t $descricao_produto\t$referencia_peca\t$descricao_peca\t$qtde\t$dias_pendentes\t$data_abertura\t$data_fechamento\t$codigo_posto\t";
					if ($login_fabrica == 158) {
						
						$escreve = "$sua_os\t$referencia_produto\t $descricao_produto\t$referencia_peca\t$descricao_peca\t$qtde\t";

						if ($nome_tipo_pedido == 'NTP') {
							if($qtde_pedido <= $qtde_cancelada){
								$escreve.="Cancelado\t";
							}else{
								$escreve.= "$data_lancamento_peca\t";
							}
							
						}else{
							$escreve.= "\t";
						}

						$escreve.= "$dias_pendentes\t$data_abertura\t$data_fechamento\t$codigo_posto\t$posto_nome\t$pedido\t$status_pedido\t$nome_tipo_pedido$descGarantia\t";

					}
					if ($login_fabrica == 72) {
						$escreve .= "$posto_nome\t$consumidor_nome\t$consumidor_fone\t";
					}
					if ($login_fabrica == 30) {
						$escreve = "$pedido\t$data_pedido\t$nome_tipo_pedido\t$codigo_posto\t$posto_nome\t$posto_estado\t$referencia_produto\t $descricao_produto\t$nome_linha\t$nome_familia\t$referencia_peca\t$descricao_peca\t$dias_pendentes";
					}

					if ($login_fabrica == 136) {
						$escreve = "$data_pedido\t$codigo_posto\t$posto_nome\t$pedido\t$sua_os\t$referencia_peca\t$descricao_peca\t $qtde\t$preco\t$peso";
					}

					if ($telecontrol_distrib) {
						$sqlRecebido = "SELECT tbl_faturamento_item.peca
							FROM tbl_faturamento_item
							JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							WHERE tbl_faturamento.posto = 4311
							AND tbl_faturamento.fabrica = 10
							AND tbl_faturamento_item.peca = $peca
							AND tbl_faturamento_item.qtde_estoque > 0";
						$resRecebido = pg_query($con, $sqlRecebido);

						$recebido_distribuidor = (pg_num_rows($resRecebido) > 0) ? "Sim" : "Não";

						$sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia_peca'";
						$resPeca = pg_query($con, $sqlPeca);
						$peca_id = pg_fetch_result($resPeca, 0, 'peca');	
						
						if (in_array($login_fabrica, [11,172])) {

				            $pecas_lenoxx = [];

				            $sql_pecas = "SELECT peca 
				                          FROM tbl_peca 
				                          WHERE referencia = 
				                            (SELECT referencia 
				                            FROM tbl_peca 
				                            WHERE peca = $peca_id 
				                          AND fabrica = $login_fabrica)
				                          AND fabrica IN (11, 172)";

				            $res_pecas = pg_query($con, $sql_pecas);
            
				            for ($q=0; $q < pg_num_rows($res_pecas); $q++) { 
				                $pecas_lenoxx[] = pg_fetch_result($res_pecas, $q, 'peca');
				            }
            
            				$pecas_lenoxx = implode(",", $pecas_lenoxx);

				            $sqlEstoque = " SELECT  SUM(tbl_posto_estoque.qtde) AS estoque_distrib
				                            FROM    tbl_posto_estoque
				                            JOIN    tbl_peca        USING(peca_id)
				                            WHERE   tbl_posto_estoque.posto = 4311
				                            AND     tbl_peca.peca           IN ($pecas_lenoxx)";
        				} else {  

				            $sqlEstoque = "
				                SELECT DISTINCT tbl_posto_estoque.qtde AS estoque_distrib
				                FROM    tbl_posto_estoque
				                JOIN    tbl_peca        USING(peca)
				                WHERE   tbl_posto_estoque.posto = 4311
				                AND     tbl_peca.peca           = $peca_id
				                
				            ";
        				}
					  
				        $resEstoque = pg_query($con, $sqlEstoque);

						$estoquePeca = pg_fetch_result($resEstoque, 0, 'estoque_distrib');

						if ($estoquePeca == False) {

							$estoquePeca = intval(0);
						}

			            $sqlTotalAlternativa = "SELECT sum(tbl_posto_estoque.qtde) as total_alternativa  
                                        		FROM tbl_peca_alternativa 
                                         		JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca_alternativa.peca_para 
                                        		WHERE peca_de = $peca_id 
                                        		AND tbl_peca_alternativa.fabrica = $login_fabrica";

            			$resTotalAlternativa = pg_query($con, $sqlTotalAlternativa);

            			$totalAlternativa = 0;
			            
			            if (pg_num_rows($resTotalAlternativa) > 0) {
			                
			                $totalAlternativa = pg_fetch_result($resTotalAlternativa, 0, "total_alternativa");
			            }

    					$estoqueTotal = $totalAlternativa + $estoquePeca;

						if(in_array($login_fabrica, array(11,172))) {
							if(!empty($os)){
								$dt_abertura = $data_abertura;
							} else {
								$dt_abertura = " ";
							}
							if(!empty($pedido)){
								$dt_pedido = $data_pedido;
							} else {
								$dt_pedido = " ";
							}
							$escreve = "$sua_os;$pedido;$referencia_produto;$descricao_produto;$referencia_peca;$descricao_peca;$qtde;$estoque_distrib;$dias_pendentes;$estoquePeca;$estoqueTotal;$dt_abertura;$dt_pedido;$codigo_posto - $posto_nome;$recebido_distribuidor";								
						} else {
							$escreve = "$sua_os;$pedido;$referencia_produto;$descricao_produto;$referencia_peca;$descricao_peca;$qtde;$dias_pendentes;$estoquePeca;$estoqueTotal;$data_abertura;$data_fechamento;$codigo_posto - $posto_nome;$recebido_distribuidor";
						}
					}
				} else {
					$escreve = "$sua_os\t$referencia_produto_fabrica\t$referencia_produto\t $descricao_produto\t$referencia_peca_fabrica\t$referencia_peca\t$descricao_peca\t$qtde\t$dias_pendentes\t$data_abertura\t$data_fechamento\t$codigo_posto\t";
				}

				$escreve.= "\r\n";
				fwrite($fp, $escreve);
			}
			fclose ($fp);
			echo `cd $path_tmp; rm -f $arquivo_nome3.zip; zip -o $arquivo_nome3.zip $arquivo_nome3 > /dev/null ; mv  $arquivo_nome3.zip $path `;
			echo "xls/".$arquivo_nome3.".zip";
			exit;
		}
	}
}
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script>
	function abreOpcao(valor){
		if (valor == 'aberta'){
			$("#opcao_os_aberta").css('display','');
		}else{
			$("#opcao_os_aberta").css('display','none');
		}
	}
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		} else {
			alert("<?php echo traduz("Preencha toda ou parte da informação para realizar a pesquisa!"); ?>");		
		}
	}

	function fnc_pesquisa_produto2 (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_peca_pendente.php";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.referencia   = campo;
			janela.descricao    = campo2;


			janela.focus();
		} else {
			alert("<?php echo traduz("Preencha toda ou parte da informação para realizar a pesquisa!"); ?>");			
		}
	}

	function fnc_pesquisa_peca2 (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/admin/relatorio_peca_pendente.php";
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.referencia   = campo;
			janela.descricao    = campo2;


			janela.focus();
		} else {
			alert("<?php echo traduz("Preencha toda ou parte da informação para realizar a pesquisa!"); ?>");	
		}
	}

</script>
<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();
	$().ready(function() {

		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));

		$('#data_inicial').mask("99/99/9999");
		$('#data_final').mask("99/99/9999");


		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$('tr[id^=linha_]').click(function(){
			var linha = $(this).attr('id');
			var posicao = linha.split("_");
			var id = posicao[1];
			var os_table = $('#os_'+id);

			if (os_table.css("display") == 'none'){
				os_table.show();
			}else{
				os_table.hide();
			}
		});

	})

function fnc_pesquisa_posto_novo(codigo, nome) {
	var codigo = jQuery.trim(codigo.value);
	var nome   = jQuery.trim(nome.value);
	if (codigo.length > 2 || nome.length > 2){
		Shadowbox.open({
			content:	"posto_pesquisa_2_nv.php?os=&codigo=" + codigo + "&nome=" + nome,
			player:	"iframe",
			title:	"<?php echo traduz("Pesquisa Posto"); ?>",
			width:	800,
			height:	500
		});
	}else{
		alert("<?php echo traduz("Preencha toda ou parte da informação para realizar a pesquisa!"); ?>");	
	}

}
function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
	gravaDados("posto_referencia",codigo_posto);
	gravaDados("posto_descricao",nome);
	gravaDados("posto",posto);
	$('#uf_posto').val(estado);
}


function gravaDados(name, valor){
	try {
		$("input[name="+name+"]").val(valor);
	} catch(err){
		return false;
	}
}

function retorna_produto (retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
}

function retorna_peca(retorno){
	$("#peca_referencia").val(retorno.referencia);
	$("#peca_descricao").val(retorno.descricao);
}

function retorna_posto(retorno){
	$("#codigo_posto, #posto_referencia").val(retorno.codigo);
	$("#descricao_posto, #posto_descricao").val(retorno.nome);
}
</script>
<?

flush();
//Recebe Data
if (strlen($_GET['data_inicial']) > 0)
	$data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)
	$data_final   = $_POST['data_final'];
//Valida data inicial e data final
if ($data_inicial && $data_final) {
	if($data_inicial && $data_final){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi))
			$msg_erro = traduz("Data Inválida");

		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf))
			$msg_erro = traduz("Data Inválida");

		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";

		if(strlen($msg_erro)==0){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
				$msg_erro = traduz("Data Inválida");
			}
		}

		if (in_array($login_fabrica, [152, 180, 181, 182])) {
			$interval_dias = ' -90 day';
			$interval_diass = ' 90 ';
		}else if (in_array($login_fabrica, array(169,170))){
			$interval_dias = ' -120 day';
			$interval_diass = ' 120 ';
		}else{
			$interval_dias = ' -31 day';
			$interval_diass = ' 31 ';
		}

		if ($telecontrol_distrib) {
			$interval_dias = ' -365 day';
			$interval_diass = ' 365 ';
		}

		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . $interval_dias)) {
			$msg_erro = traduz("Período não pode ser maior de") . $interval_diass. traduz("dias");
		}

	}else if(($data_inicial && !$data_final) || (!$data_inicial && $data_final)){
		$msg_erro = traduz("Data Inválida");;
	}
}else{
	$msg_erro .= traduz("Favor preencher a data para a pesquisa");
}

if (strlen($_GET['status_os']) > 0)
	$status_os   = $_POST['status_os'];
if (strlen($_GET['qtde_dias']) > 0)
	$qtde_dias   = $_POST['qtde_dias'];
if (strlen($_GET['mais_menos']) > 0)
	$mais_menos   = $_POST['mais_menos'];

if (strlen($_GET['produto_referencia']) > 0)
	$produto_referencia = $_POST['produto_referencia'];

if (strlen($_GET['produto_descricao']) > 0)
	$produto_descricao = $_POST['produto_descricao'];

if (strlen($_GET['peca_referencia']) > 0)
	$peca_referencia = $_POST['peca_referencia'];

if (strlen($_GET['peca_descricao']) > 0)
	$peca_descricao = $_POST['peca_descricao'];

if (strlen($_GET['referencia']) > 0)
	$referencia = $_POST['referencia'];

if (strlen($_REQUEST['estado_posto_autorizado']) > 0)
	$estado_posto_autorizado = $_REQUEST['estado_posto_autorizado'];

if (strlen($_GET['descricao']) > 0)
	$descricao = $_POST['descricao'];


$codigo_posto = $_POST['posto_referencia'];
if(strlen($codigo_posto)>0){
	$sql = "SELECT posto
	FROM tbl_posto_fabrica
	WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	if(pg_num_rows($res)<1){
		$msg_erro .= " Posto Inválido ";
	}else{
		$posto = pg_fetch_result($res,0,0);
		if(strlen($posto)==0){
			$msg_erro .= " Selecione o Posto! ";
		}else{
			$cond_3 = " AND tmp_produto_peca.posto = $posto";
		}
	}
}

if(strlen($_GET['estado']) > 0){
	$estado = $_GET['estado'];
}

if(strlen($_GET['cidade']) > 0){
	$cidade = $_GET['cidade'];
}

if(strlen($_POST['pendente'])>0){
	$dia_pendente = $_POST['pendente'];
}

if ($login_fabrica == 158 && $_POST["pedido"]) {
	$pedido = trim($_POST["pedido"]);
}

if($btn_acao=="Consultar"){

	if(strlen($msg_erro)==0){

		$nova_data_inicial = mktime(0,0,0,$d_ini[1],$d_ini[0],$d_ini[2]); // timestamp da data inicial
		$nova_data_final = mktime(0,0,0,$d_fim[1],$d_fim[0],$d_fim[2]); // timestamp da data final
		$cont = 0;
		while($nova_data_inicial <= $nova_data_final){//enquanto uma data for inferior a outra {
		  $nova_data_inicial += 86400; // adicionando mais 1 dia (em segundos) na data inicial
		  $cont++;
		}

		if($telecontrol_distrib){
			if($cont > 365)
				$msg_erro= traduz("Período Não Pode ser Maior que 12 meses");
		}else{
			if($cont > 31)
				$msg_erro= traduz("Período Não Pode ser Maior que 31 Dias");
		}

	}
}

 if (strlen($msg_erro) > 0 && $btn_acao == "Consultar") { ?>
	<div class="alert alert-error">
		<h4><?= $msg_erro ?></h4>
	</div>
<?php
}
?>
<div class="alert alert-warning">
<strong><?php echo traduz("ATENÇÃO: "); ?></strong><?php echo traduz("Se a pesquisa retornar mais de <strong>500</strong> registros, apenas os primeiros 500 serão mostrados em tela, para obter todas as informações recomenda-se baixar a planilha que irá conter TODOS os registros."); ?>
</div>
<div class="row">
	<b class="obrigatorio pull-right">* <?php echo traduz("Campos obrigatórios"); ?></b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$_SERVER['PHP_SELF']?>' align='center' class='form-search form-inline tc_formulario'>
		<div class='titulo_tabela '><?php echo traduz("Parâmetros de Pesquisa"); ?></div>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial (abertura)"); ?></label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value= "<?=$data_inicial?>" >
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?php echo traduz("Data Final (abertura)"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?php echo traduz("Status da OS"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="status_os" style="width: 100px" class="frm" >
								<option <?if ($status_os == "aberta") echo " selected ";?> value='aberta'><?php echo traduz("Aberta"); ?></option>
								<option <?if ($status_os == "fechada" || strlen($status_os) == 0) echo " selected ";?> value='fechada'><?php echo traduz("Fechada"); ?></option>
								<? if (in_array($login_fabrica, array(43,14,30,158)) || $telecontrol_distrib) { ?>
									<option <?if ($status_os == "todas" || strlen($status_os) == 0) echo " selected ";?> value='todas'>
									<?php echo traduz("Todas"); ?></option>
								<?}?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Número da OS</label>
					<div class='controls controls-row'>
						<div class='span2'>
							<input type='text' size='3' name="numero_os" value='<?php echo $numero_os;?>' class='frm'>&nbsp;
						</div>
					</div>
				</div>
			</div>
			<?php
			if ($login_fabrica == 74) { ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'><?php echo traduz("Pendentes a mais de"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
								<input type='text' size='5' name="pendente" value='<?php if ( strlen($dia_pendente) == 0 ) echo "25"; else echo $dia_pendente;?>' class='frm'>&nbsp; <?php echo traduz("dias"); ?>
						</div>
					</div>
				</div>
			</div>
			<?php
			} ?>
		</div>
		<div class='row-fluid' style='display:none'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?php echo traduz("Status da OS"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'> <?php echo traduz("a");?>
								<select name="mais_menos" size="1" class="frm">
									<option <?if ($mais_menos == "mais") echo " selected ";?> value='mais'><?php echo traduz("mais");?></option>
									<option <?if ($mais_menos == "menos" || strlen($status_os) == 0) echo " selected ";?> value='menos'><?php echo traduz("menos");?></option>
								</select>
								<?php echo traduz("de");?>
								<input class="frm" type="text" name="qtde_dias" value="<?if (strlen($qtde_dias)==0) echo "15"; else echo $qtde_dias; ?>" size="6" maxlength="5">
								<?php echo traduz("dias");?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?
		if ($login_fabrica != 72) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?php echo traduz("Ref. Produto");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>">
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?php echo traduz("Descrição Produto");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" value="<? echo $produto_descricao ?>" >
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
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'><?php echo traduz("Ref. Peças");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_descricao'><?php echo traduz("Descrição Peça");?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="peca_descricao" name="peca_descricao" value="<? echo $peca_descricao ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php
		}
		if (in_array($login_fabrica, array(43,72,14)) || $telecontrol_distrib) { ?>
		<!-- HD 191781 -->
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Código Posto");?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="12" value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto"); ?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?
		} ?>
		<? if ($login_fabrica == 72) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Estado"); ?></label>
					<div class='controls controls-row'>
						<select name="estado" id="estado" style="width:120px; font-size:11px" class="frm" >
							<option value=""   <?php if (strlen($estado) == 0)    echo " selected ";?> >TODOS OS ESTADOS</option>
							<option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><?php echo traduz("Cidade"); ?></label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type='text' name='cidade' id="cidade" size='20' value="<?php echo $cidade; ?>" class='frm'>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<? }
		if ($login_fabrica == 43) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span6'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'><?php echo traduz("Tipo de consulta"); ?></label>
					<div class='controls controls-row'>
						<select name="cons_reve" style="width: 100px" class="frm">
							<option <?if ($cons_reve == "C") echo " selected ";?> value='C'><?php echo traduz("Consumidor"); ?></option>
							<option <?if ($cons_reve == "R") echo " selected ";?> value='R'><?php echo traduz("Revenda"); ?></option>
						</select>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<? }
		if (in_array($login_fabrica, array(30,158)) || $telecontrol_distrib) {
			if (!$telecontrol_distrib) {
			?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'> <?php echo traduz("Cód. Posto"); ?></label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<input type="hidden" name="posto" value="<?=$posto?>" >
								<input type="text" name="posto_referencia" id="posto_referencia" style="width:80%" class="frm" value="<?=$posto_referencia?>" >
								<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto"); ?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" name="posto_descricao" id="posto_descricao" class="frm" value="<?=$posto_descricao?>" size="45" >
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
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'><?php echo traduz("Estado do Posto Autorizado"); ?></label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<?php
								if ($login_fabrica == 158) {
								?>
								<select name='estado_posto_autorizado' class='frm'>
									<option value=''><?php echo traduz("- Selecione -"); ?></option>
									<?php
									foreach ($array_estados() as $sigla => $estados) {
										$ufSelected = ($estado_posto_autorizado == $sigla) ? 'selected="selected"' : '';
										echo "<option value='{$sigla}' {$ufSelected}>{$estados}</option>";
									}
									?>
								</select>
								<?php
								} else {
									$estados = array(
										"AC"=>"Acre", "AL"=>"Alagoas", "AM"=>"Amazonas", "AP"=>"Amapá", "BA"=>"Bahia", "CE"=>"Ceará", "DF"=>"Distrito Federal", "ES"=>"Espírito Santo", "GO"=>"Goiás", "MA"=>"Maranhão", "MT"=>"Mato Grosso", "MS"=>"Mato Grosso do Sul", "MG"=>"Minas Gerais", "PA"=>"Pará", "PB"=>"Paraíba", "PR"=>"Paraná", "PE"=>"Pernambuco", "PI"=>"Piauí", "RJ"=>"Rio de Janeiro", "RN"=>"Rio Grande do Norte", "RO"=>"Rondônia", "RS"=>"Rio Grande do Sul", "RR"=>"Roraima", "SC"=>"Santa Catarina", "SE"=>"Sergipe", "SP"=>"São Paulo", "TO"=>"Tocantins"
									);
									?>

									<select name="uf_posto" id="uf_posto" class="frm">
										<option value=""></option>
										<? foreach ($estados as $key => $value) {
											if (!empty($uf_posto) && ($uf_posto == $key)) {
												$selected_uf = "SELECTED";
											} else {
												$selected_uf = "";
											} ?>
											<option value="<?=$key?>" <?=$selected_uf?> ><?=$key?></option>
										<? } ?>
									</select>
								<?php
								} ?>
							</div>
						</div>
					</div>
				</div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='descricao_posto'><?php echo traduz("Número do Pedido"); ?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<input type="text" name="pedido" id="pedido" style="width:40%" class="frm" value="<?=$pedido?>" >
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
			<?php
			} ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='descricao_posto'><?php echo traduz("Tipo do Pedido"); ?></label>
						<div class='controls controls-row'>
							<div class='span12 input-append'>
								<select name="tipo_pedido" id="tipo_pedido" class="frm">
									<option value=""></option>
									<?
									$sql = "SELECT tipo_pedido, descricao FROM tbl_tipo_pedido WHERE fabrica = {$login_fabrica}";
									$res = pg_query($con,$sql);
									foreach (pg_fetch_all($res) as $key) {
										$selected_tipo_pedido = ( isset($tipo_pedido) and ($tipo_pedido == $key['tipo_pedido']) ) ? "SELECTED" : '' ; ?>
										<option value="<?php echo $key['tipo_pedido']?>" <?php echo $selected_tipo_pedido ?> >
											<?php echo $key['descricao']?>
										</option>
									<? } ?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<? }
		if ($login_fabrica == 30) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?php echo traduz("Linha"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="linha" id="linha" class="frm">
								<option value=""></option>
								<?
								$sql = "SELECT linha, nome FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo";
								$res = pg_query($con,$sql);

								foreach (pg_fetch_all($res) as $key) {
									$selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ; ?>
									<option value="<?= $key['linha']; ?>" <?=$selected_linha?>><?= $key['nome']; ?></option>
								<? } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'><?php echo traduz("Familia"); ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="familia" id="familia" class="frm">
								<option value=""></option>
								<?
								$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica} AND ativo order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {
									$selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ; ?>
									<option value="<?= $key['familia']; ?>" <?=$selected_familia?>><?= $key['descricao']; ?></option>
								<? } ?>
							</select>
						</div>
						<div class='span2'></div>
					</div>
				</div>
			</div>
		</div>
		<? } ?>
		<? if ($status_os == "fechada" || strlen($status_os) == 0 || 1 == 1) { ?>
			<script language='JavaScript'>
				abreOpcao('fechada');
			</script>
		<? } ?>
		<center>
			<br />
			<input class="btn" type="button" style="cursor:pointer;" value="Pesquisar" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();"  alt="<?php echo traduz('Preencha as opções e clique aqui para pesquisar');?>">
			<input type='hidden' name='btn_acao' value='<?=$acao?>'>
		</center>
		<br />
</form>
<br />
<? if ($btn_acao == "Consultar" && strlen($msg_erro) == 0) {
    $so_excel = ($total_reg > 500);

	if ($login_fabrica == 122) {
		if (strlen($total_reg) > 0) {
			$sql_wanke = "
				SELECT DISTINCT
					tmp_wanke.nome ,
					tmp_wanke.codigo_posto
				FROM tmp_wanke
				WHERE os notnull
				GROUP BY tmp_wanke.nome ,tmp_wanke.codigo_posto;
			";

			$res_wanke = pg_query($con,$sql_wanke);
			$cont_wanke = pg_num_rows($res_wanke);

			for ($w = 0; $w < $cont_wanke; $w++) {

				$posto_nome = 	pg_fetch_result($res_wanke, $w, 'nome');
				$codigo_posto = pg_fetch_result($res_wanke, $w, 'codigo_posto');

				$sql_ped = "
					SELECT
						sum(qtde) as pecas
					FROM tmp_wanke
					WHERE tmp_wanke.codigo_posto = '{$codigo_posto}' and os notnull;
				";
				$res_ped = pg_query($con,$sql_ped);
				$pecas = pg_fetch_result($res_ped, 0, "pecas");

				if ($cor == "#F1F4FA") {
					$cor = '#F7F5F0';
				} else {
					$cor = '#F1F4FA';
				} ?>

				<table border='0' cellpadding='2' cellspacing='1' class='tabela' width='90%' align='center'>
					<tr class='titulo_coluna' height='25'>
						<td width='15%'><?php echo traduz("Cód. Posto"); ?></td>
						<td width='70%'><?php echo traduz("Posto"); ?></td>
						<td width='15%'><?php echo traduz("Quantidade de peças"); ?></td>
					</tr>
					<tr id='linha_<?=$w?>'>
						<td width='15%' ><?=$codigo_posto?></td>
						<td width='70%'><?=$posto_nome?></td>
						<td width='15%' align='center'><?=$pecas?></td>
					</tr>
					<!-- //  OS / Produto / Descrição Produto / Peças / Descrição Peça / Qtde dias Pendente / Abertura / Fechamento -->
					<?
					$sql_os = "
						SELECT
							tmp_wanke.os,
							tmp_wanke.referencia_produto,
							tmp_wanke.descricao_produto,
							tmp_wanke.dias_pendentes,
							tmp_wanke.data_abertura,
							tmp_produto_peca.referencia_peca ,
							tmp_produto_peca.descricao_peca ,
							tmp_wanke.data_fechamento
						FROM tmp_wanke
						JOIN tmp_produto_peca ON tmp_produto_peca.os = tmp_wanke.os AND tmp_produto_peca.pedido = tmp_wanke.pedido
						WHERE tmp_wanke.codigo_posto = '{$codigo_posto}'
						GROUP BY
							tmp_wanke.os,
							tmp_wanke.referencia_produto,
							tmp_wanke.descricao_produto,
							tmp_wanke.dias_pendentes,
							tmp_wanke.data_abertura,
							tmp_produto_peca.referencia_peca,
							tmp_produto_peca.descricao_peca,
							tmp_wanke.data_fechamento
					";
					$res_os = pg_query($con,$sql_os);
					$cont_os = pg_num_rows($res_os);
					?>
					<table border='0' id='os_<?=$w?>' cellpadding='2' cellspacing='1' class='tabela' width='90%' align='center' style='display: none;'>
						<tr class='titulo_coluna' height='25'>
							<td><?php echo traduz("OS"); ?></td>
							<td><?php echo traduz("Produto"); ?></td>
							<td><?php echo traduz("Descrição Produto"); ?></td>
							<td><?php echo traduz("Peça"); ?></td>
							<td><?php echo traduz("Descrição Peça"); ?>"); ?>"); ?>/td>
							<td><?php echo traduz("Qtde dias Pendentes"); ?>"); ?></td>
							<td><?php echo traduz("Abertura"); ?></td>
							<td><?php echo traduz("Fechamento"); ?></td>
						</tr>
						<? for ($o = 0; $o < $cont_os; $o++) {
							$os                 = trim(pg_fetch_result($res_os,$o,'os'));
							$referencia_produto = trim(pg_fetch_result($res_os,$o,'referencia_produto'));
							$descricao_produto  = trim(pg_fetch_result($res_os,$o,'descricao_produto'));
							$referencia_peca    = trim(pg_fetch_result($res_os,$o,'referencia_peca'));
							$descricao_peca     = trim(pg_fetch_result($res_os,$o,'descricao_peca'));
							$dias_pendentes     = trim(pg_fetch_result($res_os,$o,'dias_pendentes'));
							$data_abertura     	= trim(pg_fetch_result($res_os,$o,'data_abertura'));
							$data_fechamento    = trim(pg_fetch_result($res_os,$o,'data_fechamento'));

							if($cor == "#F1F4FA") {
								$cor = '#F7F5F0';
							} else {
								$cor = '#F1F4FA';
							} ?>

							<tr bgcolor='<?=$cor?>'>
								<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
								<td><?=$referencia_produto?></td>
								<td><?=$descricao_produto?></td>
								<td><?=$referencia_peca?></td>
								<td><?=$descricao_peca?></td>
								<td><?=$dias_pendentes?></td>
								<td><?=$data_abertura?></td>
								<td><?=$data_fechamento?></td>
							</tr>
						<? } ?>
					</table>
				</table>
			<? }
			$data = date ("dmYHi");
			$arquivo_nome3        = "relatorio_peca_pendente-$login_fabrica-$data.xls";
			$path                 = "/home/williamcastro/public_html/PosVendaNovo/admin/xls/";
			#$path                 = "/var/www/assist/www/admin/xls/";
			$path_tmp             = "/tmp/";
			$arquivo_completo     = $path.$arquivo_nome3;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome3;
			echo `rm -f $arquivo_completo_tmp `;
			echo `rm -f $arquivo_completo `;
			$arquivo = fopen($arquivo_completo_tmp, "w");

			fwrite(
				$arquivo,
				"<table border='0' cellspacing='1' cellpadding='3' class='tabela' id='tbl_resultado' align='center' style='border: 1px solid #596D9B; font-family:Calibri, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 13px'>
					<tr align='center'>
						<th bgcolor='#c2d99a' nowrap>" . traduz("Nome do posto") . "</th>
						<th bgcolor='#c2d99a' nowrap>" . traduz("Código do posto") . "</th>
						<th bgcolor='#c2d99a' nowrap>" . traduz("Quantidade de pedidos") . "</th>
					</tr>"
			);

			$sql_wanke = "
				SELECT DISTINCT
					tmp_wanke.nome,
					tmp_wanke.codigo_posto
				FROM tmp_wanke
				WHERE os notnull
				GROUP BY tmp_wanke.nome ,tmp_wanke.codigo_posto;
			";

			$res_wanke = pg_query($con,$sql_wanke);
			$cont_wanke = pg_num_rows($res_wanke);

			for ($w = 0; $w < $cont_wanke; $w++) {

				$posto_nome = 	pg_fetch_result($res_wanke, $w, 'nome');
				$codigo_posto = pg_fetch_result($res_wanke, $w, 'codigo_posto');

				$sql_ped = "
								SELECT
						sum(qtde) as pecas
					FROM tmp_wanke
					WHERE tmp_wanke.codigo_posto = '{$codigo_posto}' and os notnull;				";
				$res_ped = pg_query($con,$sql_ped);
				$pecas = pg_fetch_result($res_ped, 0, "pecas");
				$cor = ($i % 2) ? "#95b3d7" : "#95b3d8";

				fwrite (
					$arquivo,
					"<tr id='linha_$w'>
						<td >$codigo_posto.</td>
						<td >$posto_nome</td>
						<td >$pecas</td>
					</tr>
					<tr>
					<td colspan='3'>
					<table border='0' cellspacing='1' cellpadding='3' class='tabela' id='tbl_resultado' align='center' style='border: 1px solid #596D9B; font-family:Calibri, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 13px'>"
				);

				$sql_os = "
					SELECT
						tmp_wanke.os,
						tmp_wanke.referencia_produto,
						tmp_wanke.descricao_produto,
						tmp_wanke.dias_pendentes,
						tmp_wanke.data_abertura,
						tmp_produto_peca.referencia_peca,
						tmp_produto_peca.descricao_peca,
						tmp_wanke.data_fechamento
					FROM tmp_wanke
					JOIN tmp_produto_peca on tmp_produto_peca.os = tmp_wanke.os AND tmp_wanke.pedido = tmp_produto_peca.pedido
					WHERE tmp_wanke.codigo_posto = '{$codigo_posto}'
					GROUP BY
						tmp_wanke.os,
						tmp_wanke.referencia_produto,
						tmp_wanke.descricao_produto,
						tmp_wanke.dias_pendentes,
						tmp_wanke.data_abertura,
						tmp_produto_peca.referencia_peca,
						tmp_produto_peca.descricao_peca,
						tmp_wanke.data_fechamento
				";
				$res_os = pg_query($con,$sql_os);

				$msg_erro = pg_last_error($con);
				fwrite (
					$arquivo,
					"<tr>
						<th >" . traduz("OS") . "</th>
						<th >" . traduz("Produto") . "</th>
						<th >" . traduz("Descrição Produto") . "</th>
						<th >" . traduz("Peça") . "</th>
						<th >" . traduz("Descrição Peça") . "</th>
						<th >" . traduz("Qtde dias Pendentes") . "</th>
						<th >" . traduz("Abertura") . "</th>
						<th >" . traduz("Fechamento") . "</th>
					</tr>"
					);
				$cont_os = pg_num_rows($res_os);

				for ($o = 0; $o < $cont_os; $o++) {
					$os                 = trim(pg_fetch_result($res_os,$o,'os'));
					$referencia_produto = trim(pg_fetch_result($res_os,$o,'referencia_produto'));
					$descricao_produto  = trim(pg_fetch_result($res_os,$o,'descricao_produto'));
					$referencia_peca    = trim(pg_fetch_result($res_os,$o,'referencia_peca'));
					$descricao_peca     = trim(pg_fetch_result($res_os,$o,'descricao_peca'));
					$dias_pendentes     = trim(pg_fetch_result($res_os,$o,'dias_pendentes'));
					$data_abertura      = trim(pg_fetch_result($res_os,$o,'data_abertura'));
					$data_fechamento    = trim(pg_fetch_result($res_os,$o,'data_fechamento'));
                    $cor = ($cor == "#F1F4FA") ? '#F7F5F0' : '#F1F4FA';

					fwrite (
						$arquivo,
						"<tr bgcolor='$cor'>
							<th>$os</th>
							<th>$referencia_produto</th>
							<th>$descricao_produto</th>
							<th>$referencia_peca</th>
							<th>$descricao_peca</th>
							<th>$dias_pendentes</th>
							<th>$data_abertura</th>
							<th>$data_fechamento</th>
						</tr> "
					);
				}
				fwrite (
					$arquivo,
					"</table>
					</td>
					</tr>"
				);
			}
			fwrite (
				$arquivo,
				"</table>"
			);
			echo `cd $path_tmp;  cp $arquivo_nome3 $path ; rm -f $arquivo_nome3 `; ?>
			<p id='id_download3'>
				<input type='button' value='<?php echo traduz("Baixar Relatório em Excel"); ?>' onclick="window.location='xls/<?=$arquivo_nome3?>' " />
			</p>
		<? } else { ?>
			<p style='font-size:12px;text-align:center;'><?php echo traduz("Nenhum resultado encontrado"); ?></p>
		<? }
		include 'rodape.php';
		exit;
	}

    if ($total_reg > 0) {
        // Irá mostrar no máximo 500 registros em tela
        $max_tela = $total_reg > 500 ? 500 : $total_reg;
		// if (!$so_excel) { ?>
			<br />
			</div>
        <? if ($total_reg > 500) {
            echo "<p class='alert alert-warning'>". traduz("MOSTRANDO APENAS OS PRIMEIROS 500 REGISTROS. BAIXE A PLANILHA PARA TER TODAS AS INFORMAÇÕES.") . "</p>";
        } ?>
			<table id="tabela" class="table table-bordered table-fixed">
                <thead>
				<tr class='titulo_coluna'>
					<? if (!in_array($login_fabrica, array(30,122,136))) { ?>
						<th><?php echo traduz("OS"); ?></th>
						<?php
						if ($telecontrol_distrib) { ?>
							<th><?php echo traduz("Pedido"); ?></th>
						<?php
						}
						?>
						<?php if ($login_fabrica == 171) { ?>
						<th><?php echo traduz("Referência Fábrica"); ?></th>
						<?php } ?>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Descrição Produto"); ?></th>
						<?php if ($login_fabrica == 171) { ?>
						<th><?php echo traduz("Referência Fábrica"); ?></th>
						<?php } ?>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Descrição Peça"); ?></th>
						<th><?php echo traduz("Qtde"); ?></th>
						<?php if(in_array($login_fabrica, array(11,172))) { ?>
							<th><?php echo traduz("Qtde Estoque"); ?></th>
						<?php } ?>

						<th><?php echo traduz("Qtde dias Pendentes"); ?></th>
						<th><?php echo traduz("Abertura"); ?></th>
						<th><?php echo traduz("Fechamento"); ?></th>
                        <th><?=$telecontrol_distrib ? traduz('Posto'): traduz('Cód. Posto')?></th>

					<? }
					if ($login_fabrica == 158) { ?>
						<th><?php echo traduz("Nome Posto"); ?></th>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("Status Pedido"); ?></th>
						<th><?php echo traduz("Tipo do Pedido"); ?></th>
					<? }
					if ($login_fabrica == 72) { ?>
						<th><?php echo traduz("Nome Posto"); ?></th>
						<th><?php echo traduz("Nome Consumidor"); ?></th>
						<th><?php echo traduz("Fone Consumidor"); ?></th>
					<? }
					if ($login_fabrica == 30) { ?>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("Data Pedido"); ?></th>
						<th><?php echo traduz("Tipo do Pedido"); ?></th>
						<th><?php echo traduz("Cód. Posto"); ?></th>
						<th><?php echo traduz("Posto"); ?></th>
						<th><?php echo traduz("Posto UF"); ?></th>
						<th><?php echo traduz("Produto"); ?></th>
						<th><?php echo traduz("Descrição Produto"); ?></th>
						<th><?php echo traduz("Linha"); ?></th>
						<th><?php echo traduz("Familia"); ?></th>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Descrição Peça"); ?></th>
						<th><?php echo traduz("Qtde dias Pendentes"); ?></th>
					<? }
					if ($login_fabrica == 122) { ?>
						<th><?php echo traduz("Cód. Posto"); ?></th>
						<th><?php echo traduz("Posto"); ?></th>
						<th><?php echo traduz("Pedidos"); ?></th>
					<? }
					if ($login_fabrica == 136) { ?>
						<th><?php echo traduz("Data Pedido"); ?></th><
						<th><?php echo traduz("Código Posto"); ?></th>
						<th><?php echo traduz("Nome Posto"); ?></th>
						<th><?php echo traduz("Pedido"); ?></th>
						<th><?php echo traduz("OS"); ?></th>
						<th><?php echo traduz("Peça"); ?></th>
						<th><?php echo traduz("Descrição Peça"); ?>
						<th><?php echo traduz("Qtde"); ?></th>
						<th><?php echo traduz("Valor Unitário"); ?></th>
						<th><?php echo traduz("Peso Kg"); ?></th>
					<? }
					if ($telecontrol_distrib) { ?>
						<th><?php echo traduz("Recebido/Distribuidor"); ?></th>
					<?php
					}
					?>
				</tr>
                </thead>
                <tbody>
				<? for ($i = 0; $i < $max_tela; $i++) {
					$os                         = trim(pg_fetch_result($resC,$i,'os'));
					$sua_os                     = trim(pg_fetch_result($resC,$i,'sua_os'));
					$referencia_produto_fabrica = trim(pg_fetch_result($resC,$i,'referencia_produto_fabrica'));
					$referencia_produto         = trim(pg_fetch_result($resC,$i,'referencia_produto'));
					$descricao_produto          = trim(pg_fetch_result($resC,$i,'descricao_produto'));
					$referencia_peca_fabrica    = trim(pg_fetch_result($resC,$i,'referencia_peca_fabrica'));
					$referencia_peca            = trim(pg_fetch_result($resC,$i,'referencia_peca'));
					$descricao_peca             = trim(pg_fetch_result($resC,$i,'descricao_peca'));
					$codigo_posto               = trim(pg_fetch_result($resC,$i,'codigo_posto'));
					$data_abertura              = trim(pg_fetch_result($resC,$i,'data_abertura'));
					$data_fechamento            = trim(pg_fetch_result($resC,$i,'data_fechamento'));
					$dias_pendentes             = trim(pg_fetch_result($resC,$i,'dias_pendentes'));
					$consumidor_nome            = trim(pg_fetch_result($resC,$i,'consumidor_nome'));
					$consumidor_fone            = trim(pg_fetch_result($resC,$i,'consumidor_fone'));
					$posto_nome                 = trim(pg_fetch_result($resC,$i,'nome'));
					$nome_linha                 = trim(pg_fetch_result($resC,$i,'nome_linha'));
					$qtde                       = trim(pg_fetch_result($resC,$i,'qtde'));
					$estoque_distrib            = trim(pg_fetch_result($resC,$i,'estoque_distrib'));
					$nome_familia               = trim(pg_fetch_result($resC,$i,'nome_familia'));
					$pedido                     = trim(pg_fetch_result($resC,$i,'pedido'));
					$status_pedido              = trim(pg_fetch_result($resC,$i,'status_pedido'));
					$data_pedido                = trim(pg_fetch_result($resC,$i,'data_pedido'));
					$nome_tipo_pedido           = trim(pg_fetch_result($resC,$i,'nome_tipo_pedido'));
					$posto_estado               = trim(pg_fetch_result($resC,$i,'posto_estado'));
					$fora_garantia              = trim(pg_fetch_result($resC,$i,'fora_garantia'));
					$preco                      = trim(pg_fetch_result($resC,$i,'preco'));
					$peso                       = trim(pg_fetch_result($resC,$i,'peso'));
					$peca                       = trim(pg_fetch_result($resC,$i,'peca'));
					$descGarantia               = "";

					if ($fora_garantia == 'f') {
						$descGarantia = ' - GARANTIA' ;
					}

					if ($nome_tipo_pedido != 'NTP') {
						unset($descGarantia);
					} ?>
                    <tr>
						<? if (!in_array($login_fabrica, array(30,122,136))) { ?>
							<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
							<?php
							if ($telecontrol_distrib) { ?>
                                <td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$pedido?></a></td>
							<?php
							}
							?>
							<?php if ($login_fabrica == 171) { ?>
							<td><?=$referencia_produto_fabrica?></td>
							<?php } ?>
							<td><?=$referencia_produto?></td>
							<td><?=$descricao_produto?></td>
							<?php if ($login_fabrica == 171) { ?>
							<td><?=$referencia_peca_fabrica?></td>
							<?php } ?>
							<td><?=$referencia_peca?></td>
							<td><?=$descricao_peca?></td>
							<td><?=$qtde?></td>
							<?php if(in_array($login_fabrica, array(11,172))) { ?>
								<td><?=$estoque_distrib?></td>
							<?php } ?>
							<td><?=$dias_pendentes?></td>
							<?php if(in_array($login_fabrica, array(11,172))) {
									if(!empty($os)) { ?>
										<td><?=$data_abertura?></td>
									<? } else { ?>
										<td>&nbsp;</td>
									<? } 
									if(!empty($pedido)) { ?>
										<td><?=$data_pedido?></td>
									<? } else {	?>	
										<td>&nbsp;</td>
									<? } ?>
							<?php } else { ?>
								<td><?=$data_abertura?></td>
								<td><?=$data_fechamento?></td>
							<?php } ?>								
							<td><?=$telecontrol_distrib ? "$codigo_posto - $posto_nome" : $codigo_posto?></td>
						<? }
						if ($login_fabrica == 158) { ?>
							<td><?=$posto_nome?></td>
							<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$pedido?></a></td>
							<td><?=$status_pedido?></td>
							<td><?= $nome_tipo_pedido.$descGarantia; ?></td>
						<? }
						if ($login_fabrica == 72) { ?>
							<td><?= $posto_nome?></td>
							<td><?= $consumidor_nome?></td>
							<td><?= $consumidor_fone?></td>
						<? }
						if ($login_fabrica == 30) { ?>
							<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$pedido?></a></td>
							<td nowrap><?=$data_pedido?></td>
							<td><?=$nome_tipo_pedido?></td>
							<td><?=$codigo_posto?></td>
							<td><?=$posto_nome?></td>
							<td><?=$posto_estado?></td>
							<td><?=$referencia_produto?></td>
							<td><?=$descricao_produto?></td>
							<td><?=$nome_linha?></td>
							<td><?=$nome_familia?></td>
							<td><?=$referencia_peca?></td>
							<td><?=$descricao_peca?></td>
							<td><?=$dias_pendentes?></td>
						<? }
						if ($login_fabrica == 122) { ?>
							<td><?=$codigo_posto?></td>
							<td><?=$posto_nome?></td>
							<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$pedido?></a></td>
						<? } if ($login_fabrica == 136) {?>
							<td nowrap><?=$data_pedido?></td>
							<td><?=$codigo_posto?></td>
							<td align='left'><?=$posto_nome?></td>
							<td><a href='pedido_admin_consulta.php?pedido=<?=$pedido?>' target='_blank'><?=$pedido?></a></td>
							<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$sua_os?></a></td>
							<td><?=$referencia_peca?></td>
							<td align='left'><?=$descricao_peca?></td>
							<td><?=$qtde?></td>
							<td align='right'><?= number_format($preco,2,",","."); ?></td>
							<td><?= number_format($peso,2,",","."); ?></td>
						<? }

						if ($telecontrol_distrib and !empty($peca)) {
							if(array_key_exists($peca, $recebidas_pecas)) {
								$recebido_distribuidor = $recebidas_pecas[$peca];
							}else{
								$sqlRecebido = "SELECT tbl_faturamento_item.peca
									FROM tbl_faturamento_item
									JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
									WHERE tbl_faturamento.posto = 4311
									AND tbl_faturamento.fabrica = 10
									AND tbl_faturamento_item.peca = $peca
									AND tbl_faturamento_item.qtde_estoque > 0 limit 1";
								$resRecebido = pg_query($con, $sqlRecebido);

								$recebido_distribuidor = (pg_num_rows($resRecebido) > 0) ? traduz("Sim") : traduz("Não");
								$recebidas_pecas[$peca] = $recebido_distribuidor;
							}

						?>
							<td class="tac"><?= $recebido_distribuidor ?></td>
						<?php
						}
						?>
					</tr>
				<? } ?>
                </tbody>
			</table>
		<? // } ?>
		<br />
		<center>
			<h4>Total de <b><?=$total_reg?></b> peças.</h4>
		<br>
        <?php //$jsonPOST = excelPostToJson($_POST); ?>
        <!-- <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
            <p id='id_download3'><input type='button' class="btn btn-success" value='Baixar Relatório em Excel' onclick="window.location='xls/<?=$arquivo_nome3?>.zip'" /></p>
        </div> -->

    <?php } else { ?>
		<center>
            <p style='font-size:12px;text-align:center;'><?php echo traduz("Nenhum resultado encontrado"); ?></p>
    <? } ?>
	<!-- // ##### PAGINACAO ##### //

		// INÍCIO DO PROCESSO DE GERAÇÃO DE ARQUIVO -->

	<? if ($total_reg) {
			if ($nome_tipo_pedido != 'NTP') {
				unset($descGarantia);
			}

			if ($login_fabrica <> 171) {
				$escreve = "$sua_os\t$referencia_produto\t $descricao_produto\t$referencia_peca\t$descricao_peca\t$qtde\t$dias_pendentes\t$data_abertura\t$data_fechamento\t$codigo_posto\t";
				if ($login_fabrica == 158) {
					$escreve .= "$posto_nome\t$pedido\t$status_pedido\t$nome_tipo_pedido.$descGarantia\t";
				}
				if ($login_fabrica == 72) {
					$escreve .= "$posto_nome\t$consumidor_nome\t$consumidor_fone\t";
				}
				if ($login_fabrica == 30) {
					$escreve = "$pedido\t$data_pedido\t$nome_tipo_pedido\t$codigo_posto\t$posto_nome\t$posto_estado\t$referencia_produto\t $descricao_produto\t$nome_linha\t$nome_familia\t$referencia_peca\t$descricao_peca\t$dias_pendentes";
				}

				if ($login_fabrica == 136) {
					$escreve = "$data_pedido\t$codigo_posto\t$posto_nome\t$pedido\t$sua_os\t$referencia_peca\t$descricao_peca\t $qtde\t$preco\t$peso";
				}

				if ($telecontrol_distrib) {					
					$sqlRecebido = "SELECT tbl_faturamento_item.peca
											FROM tbl_faturamento_item
											JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
											WHERE tbl_faturamento.posto = 4311
											AND tbl_faturamento.fabrica = 10
											AND tbl_faturamento_item.peca = $peca
											AND tbl_faturamento_item.qtde_estoque > 0";
					$resRecebido = pg_query($con, $sqlRecebido);

					$recebido_distribuidor = (pg_num_rows($resRecebido) > 0) ? traduz("Sim") : traduz("Não");

					if(in_array($login_fabrica, array(11,172))) {
						if(!empty($os)){
							$dt_abertura = $data_abertura;
						} else {
							$dt_abertura = " ";
						}
						if(!empty($pedido)){
							$dt_pedido = $data_pedido;
						} else {
							$dt_pedido = " ";
						}
						$escreve = "$sua_os;$pedido;$referencia_produto;$descricao_produto;$referencia_peca;$descricao_peca;$qtde;$estoque_distrib;$dias_pendentes;$dt_abertura;$dt_pedido;$codigo_posto - $posto_nome;$recebido_distribuidor";								
					} else {
						$escreve = "$sua_os;$pedido;$referencia_produto;$descricao_produto;$referencia_peca;$descricao_peca;$qtde;$dias_pendentes;$data_abertura;$data_fechamento;$codigo_posto - $posto_nome;$recebido_distribuidor";
					}
				}
			} else {
				$escreve = "$sua_os\t$referencia_produto_fabrica\t$referencia_produto\t $descricao_produto\t$referencia_peca_fabrica\t$referencia_peca\t$descricao_peca\t$qtde\t$dias_pendentes\t$data_abertura\t$data_fechamento\t$codigo_posto\t";
			}

			$escreve.= "\r\n";
			fwrite($fp, $escreve);
		}
		fclose ($fp);
		echo `cd $path_tmp; rm -f $arquivo_nome3.zip; zip -o $arquivo_nome3.zip $arquivo_nome3 > /dev/null ; mv  $arquivo_nome3.zip $path `;
		$jsonPOST = excelPostToJson($_POST);
		?>

		<div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <p id='id_download3'><input type='button' class="btn btn-success" value='<?=traduz("Baixar Relatório em Excel")?>' /></p>
        </div>     
	<? 
	// FIM DO PROCESSO DE GERAï¿½ï¿½O DE ARQUIVO 
}
?>
		</center>
<script>
	$.dataTableLoad('#tabela');
</script>
<?php
include 'rodape.php';
