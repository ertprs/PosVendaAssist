<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function cort($string, $num)
{
	if(strlen($string) > $num){
		$string = substr($string, 0, $num)."...";
	}
	return $string;
}

/* ---------------------- */
if ($login_fabrica == 35) {
	$array_campos_pesquisa = array(
		"tbl_os.sua_os"                    => "Número OS",
		"tbl_os.os_posto"                  => 'OS Interna',
		"tbl_hd_chamado_extra.hd_chamado"  => "Protocolo",
		"tbl_posto_fabrica.codigo_posto"   => "Código posto",
		"tbl_posto.nome"                   => "Razão Social",
		"tbl_posto.cnpj"                   => "CNPJ Posto",
		"tbl_posto.cidade"                 => "Cidade Posto",
		"tbl_posto.estado"                 => "Estado Posto",
		"tbl_status_checkpoint.descricao as status_check"         => "Status OS",
		"tbl_marca.nome AS marca"          => 'Marca',
		"tbl_produto.linha"                => "Linha",
		"tbl_os.consumidor_revenda"        => "Tipo OS",
		"data_abertura"                    => "Data Abertura OS",
		"data_finalizacao"                 => "Data Finalização OS",
		"data_conserto"                    => "Data Conserto OS",
		"data_fechamento"                  => "Data Fechamento OS",
		"tbl_os.data_nf"                   => "Data Compra",
		"tbl_tipo_atendimento.descricao AS tipo_atendimento" => "Tipo Atendimento",
		"tbl_os.qtde_km"                   => 'Qtde KM',
		"tbl_os.qtde_visitas"              => 'Qtde Visitas',
		"tbl_os.qtde_km_calculada"         => 'Total KM',
		"produto"                          => 'Referência do Produto',
		"tbl_produto.descricao"            => 'Descrição do produto',
		"tbl_os.serie"                     => 'PO#',
		"tbl_os.aparencia_produto"         => "Aparência",
		"tbl_os.acessorios"                => "Acessórios",
		"defeito_reclamado"                => 'Defeito Reclamado',
		"solucao"                          => 'Solução',
		"defeito_constatado"               => 'Defeito Constatado',
		"tbl_os.consumidor_nome"           => 'Consumidor',
		"tbl_hd_chamado_extra.celular"     => 'Consumidor Celular',
		"tbl_os.consumidor_cpf"            => 'CPF / CNPJ do consumidor',
		"tbl_os.consumidor_endereco"       => 'Consumidor Rua',
		"tbl_os.consumidor_numero"         => 'Consumidor Número',
		"tbl_os.consumidor_bairro"         => 'Consumidor Bairro',
		"tbl_os.consumidor_cep"            => 'Consumidor CEP',
		"tbl_os.consumidor_complemento"    => 'Consumidor Complemento',
		"tbl_os.consumidor_cidade"         => 'Consumidor Cidade',
		"tbl_os.consumidor_estado"         => 'Consumidor UF',
		"tbl_os.consumidor_fone"           => 'Consumidor Telefone',
		"tbl_os.consumidor_email"          => 'Consumidor Email',
		"tbl_os.revenda_nome"              => 'Revenda Razão Social',
		"tbl_os.revenda_cnpj"              => 'Revenda CNPJ',
		"tbl_os.nota_fiscal"               => "NF Compra",
		"tbl_os.data_nf"                   => "Data Compra",
		"tbl_peca.referencia"              => 'Referência Peça',
		"tbl_peca.descricao"               => 'Descrição Peça',
		"po_peca"                          => 'PO Peça',
		"tbl_os_item.digitacao_item"       => 'Data Digitação Peça',
		"tbl_pedido_item.qtde"			   => 'Qtde Solicitada',
		"tbl_pedido_item.qtde_faturada"	   => 'Qtde Faturada',
		"(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente"	   => 'Pendente',
		"tbl_pedido.pedido"                => 'Pedido',
		"tbl_pedido.data"                  => 'Data pedido',
		"tbl_faturamento.nota_fiscal"      => 'NF do Faturamento',
		"tbl_faturamento.emissao"          => 'Data Emissão Nota',
		"tbl_faturamento.saida" 		   => 'Data Saída',
		"tbl_faturamento.saida" 		   => 'Data Postagem',
		"tbl_faturamento.previsao_chegada" => 'Data Entrega',
		"tbl_faturamento.conhecimento"     => 'Número de Rastreamento',
		"tbl_os_extra.extrato"			   => 'Extrato',
		"observacao"                       => 'Observação',
		"tbl_os.mao_de_obra"			   => 'M.O.',
		"tbl_servico_realizado.descricao AS servico_realizado" => "Serviço Peça"
	);

	#asort($array_campos_pesquisa);
}


$qtde_mes = ($login_fabrica == 50) ? 3 : 1;
$qtde_mes = (in_array($login_fabrica, array(35,134,169,170,177))) ? 6 : $qtde_mes;
$qtde_mes = (in_array($login_fabrica, [148,167,186,203])) ? 12 : $qtde_mes;

if ($_POST["btn_acao"] == "submit") {
	
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');
    $estado             = filter_input(INPUT_POST,'estado');
    
    if($login_fabrica == 169){    	
    	$data_referencia = filter_input(INPUT_POST,'data_referencia');       	
		if($data_referencia == "A"){
			$dt_referencia = "abertura";
		} else {
			$dt_referencia = "finalizada";
		}    	
    }

	if ($login_fabrica == 148) {
		$familia        = filter_input(INPUT_POST,'familia');
		$tipo_os_array  = filter_input(INPUT_POST,'tipo_os',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
		if (isset($tipo_os_array)) {
			foreach ($tipo_os_array as $tp_os) {
				if ($tp_os == "C") {
					$tipo_os_consumidor = "'C'";
				} else {
					$tipo_os_revenda = "'R'";
				}
			}

			if (!empty($tipo_os_consumidor) && !empty($tipo_os_revenda)) {
				$tipo_os = "AND (tbl_os.consumidor_revenda = $tipo_os_consumidor OR tbl_os.consumidor_revenda = $tipo_os_revenda)";
			} elseif (!empty($tipo_os_consumidor)) {
				$tipo_os = "AND tbl_os.consumidor_revenda = $tipo_os_consumidor";
			} else {
				$tipo_os = "AND tbl_os.consumidor_revenda = $tipo_os_revenda";
			}

		} else {
			$tipo_os = "";
		}
	} else {
		$tipo_os            = filter_input(INPUT_POST,'tipo_os');
	}
	
	if (in_array($login_fabrica,array(167,186,203))) {
		$tipo_peca      	= filter_input(INPUT_POST,'tipo_peca');		
	} else {
		$tipo_peca      = "";
	}

    $status_os          = filter_input(INPUT_POST,'status_os',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
	$tipo_atendimento   = filter_input(INPUT_POST,'tipo_atendimento',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
    $n_serie            = filter_input(INPUT_POST,'n_serie');
	$status             = filter_input(INPUT_POST,'status',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
	$linha              = filter_input(INPUT_POST,'linha');

    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (!strlen($data_inicial) or !strlen($data_final) or ($login_fabrica == 169 and empty($data_referencia))) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
		if($login_fabrica == 169){
			$msg_erro["campos"][] = "data_referencia";
		}
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}

		if(empty($posto) AND !in_array($login_fabrica, array(169,170,177))){
			$sqlX = "SELECT '$aux_data_inicial'::date + interval '$qtde_mes months' >= '$aux_data_final'";
			$resSubmitX = pg_query($con,$sqlX);
			$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
			if($periodo_6meses == 'f'){
				$texto_mes = ($qtde_mes > 1) ? "$qtde_mes meses" : "1 mês";
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo ".$texto_mes;
			}
		}else{
			$sqlX = "SELECT '$aux_data_inicial'::date + interval '$qtde_mes months' >= '$aux_data_final'";
			$resSubmitX = pg_query($con,$sqlX);
			$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
			if($periodo_6meses == 'f'){
				$texto_mes = ($qtde_mes > 1) ? "$qtde_mes meses" : "1 mês";
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo ".$texto_mes;
			}
		}
	}

	if (!count($msg_erro["msg"])) {

		if(in_array($login_fabrica,array(50,157,164))) {
			$rows = 46;
		} else {
            $rows = 39;
		}

		if (in_array($login_fabrica,[1,30,50])) {
			$distinct = " DISTINCT ";
		}

		if (in_array($login_fabrica, array(167,186,203))) {
			$distinct = " DISTINCT ON (tbl_os.os)";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if ($estado) {
			$cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
		}

		if ($login_fabrica == 183) {
			$campo_atendente = ", CASE WHEN ade.nome_completo NOTNULL THEN ade.nome_completo ELSE ado.nome_completo END AS nome_atendente, inspetor_posto.nome_completo AS inspetor_sap_posto ";
			$joins_atendente = " LEFT JOIN tbl_hd_chamado hde ON tbl_hd_chamado_extra.hd_chamado = hde.hd_chamado
								 LEFT JOIN tbl_admin ade ON hde.admin = ade.admin AND ade.fabrica = $login_fabrica
								 LEFT JOIN tbl_hd_chamado hdo ON os.hd_chamado = hdo.hd_chamado
								 LEFT JOIN tbl_admin ado ON hdo.admin = ado.admin AND ado.fabrica = $login_fabrica";
		}

		$dc_defeitoAgg = " tbl_defeito_constatado.descricao AS dc_defeito, ";
		$dr_defeitoAgg = " tbl_defeito_reclamado.descricao AS dr_defeito, ";
		$solucaoAgg = " tbl_solucao.descricao AS solucao, ";

		$leftJoinDf = " LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_defeito_constatado.defeito_constatado = os.defeito_constatado ";

		$os_d_r_c = "LEFT JOIN tbl_solucao ON tbl_solucao.fabrica = $login_fabrica
						AND tbl_solucao.solucao = os.solucao_os";

		if ($login_fabrica == 148) {

			$leftJoinDf = "";

			$os_d_r_c = "LEFT JOIN tbl_os_defeito_reclamado_constatado ON os.os = tbl_os_defeito_reclamado_constatado.os
						 LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_defeito_constatado.defeito_constatado = tbl_os_defeito_reclamado_constatado.defeito_constatado
						 LEFT JOIN tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao = tbl_solucao.solucao";
			if (!empty($tipo_os)) {
				$cond_tipo_os = $tipo_os;
			}

			$groupBy = " GROUP BY os.*, os.os, os.sua_os, os.extrato, os.data_abertura, os.finalizada, os.data_digitacao, os.data_conserto, os.data_nf, os.serie, os.nota_fiscal, os.consumidor_revenda, os.dias_aberto_consumidor, os.dias_aberto_revenda, os.revenda_cnpj, os.revenda_nome, os.revenda_fone, os.qtde_km, os.qtde_hora, os.consumidor_nome, os.consumidor_fone, os.consumidor_celular, os.consumidor_endereco, os.consumidor_numero, os.condicao, os.consumidor_complemento, os.consumidor_bairro, os.consumidor_cep, os.consumidor_cidade, os.consumidor_estado, os.consumidor_cpf, os.consumidor_email, os.mao_de_obra, os.serie_reoperado, os.obs, os.dr_descricao, os.status, os.campos_adicionais, os.produto, os.posto, os.os_posto, os.hd_chamado, os.tipo_atendimento, os.defeito_constatado, os.defeito_reclamado, os.solucao_os, os.revenda, os.data_fabricacao, os.os_auditoria, os.status_auditoria, os.qtde_km_extra, os.valor_por_km, produto_referencia_fabrica, tbl_produto.referencia, tbl_produto.descricao, tbl_produto.nome_comercial, tbl_posto.cnpj, tbl_posto.nome, conclusao_laudo, posto_codigo, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.contato_fone_comercial, revenda_cidade, revenda_estado, desc_tipo_atendimento, tbl_hd_chamado_extra.hd_chamado, tbl_tipo_posto.descricao, tbl_numero_serie.data_fabricacao, valida_serie ";

			$dc_defeitoAgg = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_defeito_constatado.descricao)), ', ', null) AS dc_defeito, ";
			$dr_defeitoAgg = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_defeito_reclamado.descricao)), ', ', null) AS dr_defeito, ";
			$solucaoAgg = " ARRAY_TO_STRING(array_agg(DISTINCT(tbl_solucao.descricao)), ', ', null) AS solucao, ";


		} else {
			if ($tipo_os) {
				$cond_tipo_os = " AND tbl_os.consumidor_revenda= '$tipo_os' ";
			}
		}

        if (is_array($status_os)) {
            $cond_status = " AND tbl_os.status_os_ultimo = ".$status_os[0];
        }

        if($login_fabrica == 163){
        	$cond_163 = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
		}
		
		if (!empty($linha)) {
			$cond_linha = "AND tbl_produto.linha = $linha";
		}

		if (!empty($familia)) {
			$cond_familia = "AND tbl_produto.familia = $familia";	
		}

		if(!isset($_POST['gerar_excel'])){
			$limit = " LIMIT 501 ";
		}

		if (in_array($login_fabrica, array(148,167,169,170,177,186,203)) && !empty($tipo_atendimento)) {
			$cond_tipo_atendimento = " AND tbl_tipo_atendimento.tipo_atendimento IN (".implode(',', $tipo_atendimento).")";
		}

		if ($login_fabrica == 148 && !empty($n_serie)) {
			$cond_serie = "AND tbl_os.serie = '$n_serie'";
		}

		if ($login_fabrica == 148) {
			$distinct = " DISTINCT ON (tbl_os.os)";
		}

		if (in_array($login_fabrica, array(169,170))) {
			if (!empty($status)) {
				$cond_status = " AND tbl_os.status_checkpoint IN (".implode(',', $status).")";
			}

			$distinct = " DISTINCT ON (tbl_os.os)";

            $campos_midea = "
            	, CASE WHEN tbl_tipo_atendimento.km_google IS TRUE THEN
				(	SELECT
						TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY')
					FROM tbl_tecnico_agenda
					WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
					AND tbl_tecnico_agenda.os = os.os
					ORDER BY tbl_tecnico_agenda.data_input ASC
					LIMIT 1
				)
				ELSE NULL END AS data_agendamento,
				CASE WHEN tbl_tipo_atendimento.km_google IS TRUE THEN
				(	SELECT
						TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY')
					FROM tbl_tecnico_agenda
					WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
					AND tbl_tecnico_agenda.os = os.os
					AND tbl_tecnico_agenda.confirmado IS NOT NULL
					LIMIT 1
				)
				ELSE NULL END AS data_confirmacao,
				inspetor_posto.nome_completo AS inspetor_sap
			";
        }

        if ($login_fabrica == 35) {
			$campos_pesquisa = $_POST['campos_pesquisa'];
			$sql = '';

			foreach ($array_campos_pesquisa as $campo => $value) {
				if (count($campos_pesquisa) && !in_array($campo, $campos_pesquisa)) {
					continue;
				}

				if (!empty($sql)) { $sql .= ','; }

				if ($campo == 'data_abertura') {
					$campo = "TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura";
				}elseif ($campo == 'tbl_produto.linha') {
					$campo = "tbl_linha.nome ";
				}elseif ($campo == 'data_digitacao') {
					$campo = "TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao";
				}elseif ($campo == 'data_finalizacao') {
					$campo = "TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada";
				}elseif ($campo == 'data_conserto') {
					$campo = "TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto";
				}elseif ($campo == 'observacao') {
					$campo = "replace(replace(tbl_os.obs,'\r',' '),'\n',' ') AS observacao";
				}elseif ($campo == 'peca') {
					$campo = "tbl_peca.referencia||' - '||tbl_peca.descricao AS peca";
				}elseif ($campo == 'po_peca') {
					$campo = "JSON_FIELD('po_peca',tbl_os_item.parametros_adicionais) AS po_peca";
				}elseif ($campo == 'defeito_reclamado') {
					$campo = "CASE WHEN tbl_defeito_reclamado.descricao NOTNULL THEN
								tbl_defeito_reclamado.descricao
							ELSE
								tbl_os.defeito_reclamado_descricao
							END AS defeito_reclamado";
				}elseif ($campo == 'defeito_constatado') {
					$campo = "tbl_defeito_constatado.descricao AS defeito_constatado";
				}elseif ($campo == 'solucao') {
					$campo = "tbl_solucao.descricao AS solucao";
				}elseif ($campo == 'produto') {
					$campo = "tbl_produto.referencia";
				}elseif ($campo == 'tbl_os.data_nf') {
					$campo = "TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf";
				}elseif ($campo == "tbl_pedido.data") {
					$campo = "TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY hh:mm:ss') as data";
				}elseif ($campo == 'tbl_os_item.digitacao_item') {
					$campo = "TO_CHAR(tbl_os_item.digitacao_item, 'DD/MM/YYYY hh:mm:ss') as digitacao_item";
				}elseif ($campo == 'tbl_hd_chamado_extra.celular') {
					$campo = 'CASE WHEN tbl_hd_chamado_extra.celular IS NULL 
							  THEN tbl_os.consumidor_celular
							  ELSE tbl_hd_chamado_extra.celular END AS celular';
				}

				$sql .= $campo;
			}

			$sql = "
				SELECT $sql , 
				tbl_os.status_checkpoint as status
				FROM tbl_os
				JOIN tbl_os_extra USING(os)
				JOIN tbl_os_produto USING(os)
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = tbl_os.fabrica
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
				LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = {$login_fabrica}
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
				LEFT JOIN tbl_os_item ON(tbl_os_item.os_produto = tbl_os_produto.os_produto)
				LEFT JOIN tbl_peca ON(tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica})
				LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
				LEFT JOIN tbl_pedido ON(tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = {$login_fabrica})
				LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
				LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca
				LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
				LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
				LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os AND tbl_solucao.fabrica = {$login_fabrica}
				LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica = $login_fabrica 
				LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
				LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
				LEFT JOIN tbl_numero_serie ON tbl_numero_serie.serie = tbl_os.serie AND tbl_numero_serie.produto = tbl_os.produto AND tbl_numero_serie.serie = tbl_os.serie AND tbl_numero_serie.fabrica = {$login_fabrica}
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'
				AND tbl_os.excluida IS FALSE
				{$cond_posto}
				{$cond_estado}
				{$cond_tipo_os}
				{$cond_status}
				{$cond_serie}
				{$limit}
";
		}else{
			$datas = relatorio_data("$aux_data_inicial","$aux_data_final");			

			$join_dc = " left join tbl_os_defeito_reclamado_constatado on tbl_os.os = tbl_os_defeito_reclamado_constatado.os LEFT JOIN tbl_defeito_constatado ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado ";

			if($novaTelaOs) {
				$campoDc = " CASE WHEN tbl_os.defeito_reclamado_descricao IS NOT NULL AND tbl_os.defeito_reclamado_descricao <> '' THEN tbl_os.defeito_reclamado_descricao ELSE tbl_defeito_constatado.descricao END  AS dr_descricao, ";
				$tbl_os = "tbl_os_defeito_reclamado_constatado";
			}else{
				$campoDc = " tbl_os.defeito_reclamado_descricao AS dr_descricao, ";
				$tbl_os = "tbl_os";
			}

			if (in_array($login_fabrica,array(167,186,203))) {
				$join_linha = " LEFT JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha and tbl_linha.fabrica = $login_fabrica";
				$campo_linha = ", tbl_linha.nome AS nome_linha";
			}

			foreach($datas as $cont => $data_pesquisa){
				$data_inicial = $data_pesquisa[0];
				$data_final = $data_pesquisa[1];
				$data_final = str_replace(' 23:59:59', '', $data_final);

				$tempTableCreate = "";
				$tempTableInsert = "";
				$tempTableInsertP = "";
				if ($cont == 0) {
					$tempTableCreate = "INTO TEMP relatorio_os_{$login_admin}";
				} else if ($cont > 0) {
					$tempTableInsert = "INSERT INTO relatorio_os_{$login_admin} (";
					$tempTableInsertP = ")";
				}

				if($login_fabrica == 148){
					$campos_yanmar = ", tbl_os_extra.qtde_km as qtde_km_extra, tbl_os_extra.valor_por_km ";
				}

				$cond_excluida = " AND tbl_os.excluida IS FALSE ";

				if(in_array($login_fabrica, [169,170])) {
					if ($dt_referencia == 'abertura') {
						$cond_dt_ref = " AND tbl_os.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";
					} else if ($dt_referencia == 'finalizada') {
						$cond_dt_ref = " AND tbl_os.data_fechamento BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";
					}

					if (in_array(28, $status)) {
						$cond_status = " AND ((tbl_os.status_checkpoint IN (".implode(',', $status).") AND tbl_os.excluida IS FALSE) OR (tbl_os.status_checkpoint = 28)) ";
						$cond_excluida = "";
					}
                } else {
					$cond_dt = " AND tbl_os.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' ";					
				}

				$sql = "
					{$tempTableInsert}
					SELECT {$distinct}
						tbl_os.os,
						tbl_os.sua_os,
						tbl_os_extra.extrato,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada,
						TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
						TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto,
						TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
						tbl_os.serie,
						tbl_os.nota_fiscal,
						tbl_os.consumidor_revenda,
						(current_date - tbl_os.data_abertura) AS dias_aberto_consumidor,
						(current_date - tbl_os.data_digitacao::date) AS dias_aberto_revenda,
						tbl_os.revenda_cnpj,
						tbl_os.revenda_nome,
						tbl_os.revenda_fone,
						tbl_os.qtde_km,
						tbl_os.qtde_hora,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_fone,
						tbl_os.consumidor_celular,
						tbl_os.consumidor_endereco,
						tbl_os.consumidor_numero,
						tbl_os.condicao,
						tbl_os.consumidor_complemento,
						tbl_os.consumidor_bairro,
						tbl_os.consumidor_cep,
						tbl_os.consumidor_cidade,
						tbl_os.consumidor_estado,
						tbl_os.consumidor_cpf,
						tbl_os.consumidor_email,
						tbl_os.mao_de_obra,
						tbl_os.serie_reoperado,
						REPLACE(REPLACE(tbl_os.obs,'',' '),'',' ') AS obs,
						$campoDc
						tbl_os.status_checkpoint AS status,
						tbl_os_campo_extra.campos_adicionais,
						tbl_os.produto,
						tbl_os.posto,
						tbl_os.os_posto,
						tbl_os.hd_chamado,
						tbl_os.tipo_atendimento,
						CASE WHEN tbl_os.defeito_constatado IS NOT NULL THEN tbl_os.defeito_constatado WHEN tbl_os_defeito_reclamado_constatado.defeito_constatado NOTNULL THEN tbl_os_defeito_reclamado_constatado.defeito_constatado ELSE tbl_os_produto.defeito_constatado END AS defeito_constatado,
						tbl_os.defeito_reclamado,
						tbl_os.solucao_os,
						tbl_os.revenda,
						tbl_os_extra.data_fabricacao,							
						tbl_auditoria_os.os AS os_auditoria,
						case when tbl_auditoria_os.liberada isnull and tbl_auditoria_os.reprovada isnull and tbl_auditoria_os.cancelada isnull then 'PENDENTE'
							when tbl_auditoria_os.liberada notnull then 'APROVADA'
							WHEN tbl_auditoria_os.reprovada notnull then 'REPROVADA'
							when tbl_auditoria_os.cancelada notnull then 'CANCELADA' END as status_auditoria
						{$campos_yanmar}
						{$campo_linha}
					{$tempTableCreate}
					FROM tbl_os
					INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
					LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
					{$join_dc}
					{$join_linha}
					LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
					WHERE tbl_os.fabrica = {$login_fabrica}
					{$cond_dt}
					{$cond_dt_ref}
					{$cond_excluida}
					{$cond_posto}
					{$cond_tipo_os}
					{$cond_status}
					{$cond_serie}
					{$cond_linha}
					{$cond_familia}
					{$tempTableInsertP};
				";
				$res = pg_query($con,$sql);

				if($cont >=2 and !isset($_POST['gerar_excel'])){
					$sqlC = "select count(1) from relatorio_os_$login_admin";
					$resC = pg_query($con,$sqlC);
					if(pg_fetch_result($resC,0,0) > 500) break;
				}
			}

			if($login_fabrica == 140){
				$distinct_lavor = ' DISTINCT';
			}

			$sql = "
				CREATE INDEX relatorio_os_os_$login_admin on relatorio_os_$login_admin(os);				
				CREATE INDEX relatorio_os_posto_$login_admin on relatorio_os_$login_admin(posto);
				CREATE INDEX relatorio_os_produto_$login_admin on relatorio_os_$login_admin(produto);
				CREATE INDEX relatorio_os_tipo_atendimento_$login_admin on relatorio_os_$login_admin(tipo_atendimento);
				CREATE INDEX relatorio_os_df_$login_admin on relatorio_os_$login_admin(defeito_reclamado);
				CREATE INDEX relatorio_os_dc_$login_admin on relatorio_os_$login_admin(defeito_constatado);
				CREATE INDEX relatorio_os_serie_$login_admin on relatorio_os_$login_admin(serie);
				CREATE INDEX relatorio_os_solucao_$login_admin on relatorio_os_$login_admin(solucao_os);
				CREATE INDEX relatorio_os_revenda_$login_admin on relatorio_os_$login_admin(revenda);				

				SELECT $distinct_lavor
					os.*,																		
					tbl_produto.referencia_fabrica as produto_referencia_fabrica,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.nome_comercial,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_laudo_tecnico_os.observacao as conclusao_laudo,
					tbl_posto_fabrica.codigo_posto AS posto_codigo,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_fone_comercial,
					CASE WHEN os.hd_chamado notnull THEN os.hd_chamado
					ELSE tbl_hd_chamado_extra.hd_chamado END AS hd_chamado,
					$dc_defeitoAgg
					$dr_defeitoAgg
					tbl_cidade.nome AS revenda_cidade,
					tbl_cidade.estado AS revenda_estado,
					tbl_tipo_atendimento.descricao AS desc_tipo_atendimento,
					tbl_tipo_posto.descricao AS tipo_posto,
					$solucaoAgg
					tbl_numero_serie.serie AS valida_serie,
					CASE WHEN os.data_fabricacao IS NOT NULL THEN TO_CHAR(os.data_fabricacao, 'DD/MM/YYYY')
					ELSE TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY') END AS data_fabricacao
					$campos_midea
					$campo_linha
					$campo_atendente
				FROM relatorio_os_$login_admin os
				LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = os.os AND os.hd_chamado IS NULL
				INNER JOIN tbl_produto ON tbl_produto.fabrica_i = $login_fabrica AND os.produto = tbl_produto.produto
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = os.posto
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.fabrica = $login_fabrica	AND tbl_tipo_atendimento.tipo_atendimento = os.tipo_atendimento
				$leftJoinDf
				LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.fabrica = $login_fabrica AND tbl_defeito_reclamado.defeito_reclamado = os.defeito_reclamado
				$os_d_r_c
				$join_linha
				{$joins_atendente}
				LEFT JOIN tbl_revenda ON tbl_revenda.revenda = os.revenda
				LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
				LEFT JOIN tbl_numero_serie ON tbl_numero_serie.fabrica = $login_fabrica AND tbl_numero_serie.produto = os.produto AND tbl_numero_serie.serie = os.serie
				LEFT JOIN tbl_admin inspetor_posto ON inspetor_posto.fabrica = $login_fabrica AND inspetor_posto.admin = tbl_posto_fabrica.admin_sap
				LEFT JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = os.os AND tbl_laudo_tecnico_os.fabrica = {$login_fabrica}					
				WHERE 1 = 1
				{$cond_163}
				{$cond_estado}
				{$cond_tipo_atendimento}
				{$groupBy}
				{$limit};
			";
		}			
		//die(nl2br($sql));
		$resSubmit = pg_query($con, $sql);
	}

	if(isset($_POST['gerar_excel'])){

		if($login_fabrica == 169){
			$tipo_relatorio = $_POST['tipo_relatorio'];
		}
		
		if ($login_fabrica == 35) {
			$filename = "relatorio-os-".date('Ydm').".csv";
			$file     = fopen("/tmp/{$filename}", "w");

			$thead = '';
			$campos_pesquisa = $_POST['campos_pesquisa'];
			foreach ($array_campos_pesquisa as $campo => $value) {
				if (count($campos_pesquisa) && !in_array($campo, $campos_pesquisa)) {
					continue;
				}
				$thead .= (empty($thead)) ? $value : ";$value";
			}
			fwrite($file, "$thead\n");

			$qtde_colunas = (count($campos_pesquisa)) ? count($campos_pesquisa) : count($array_campos_pesquisa);
			$count = pg_num_rows($resSubmit);
			
			for ($i = 0; $i < $count; $i++) {
				$tbody = '';
				$primeira_coluna = true;
				for ($z = 0; $z < $qtde_colunas; $z++) {

					if ($primeira_coluna) {
						$tbody .= pg_fetch_result($resSubmit,$i, $z);
						$primeira_coluna = false;
					} else {
						$tbody .= ";".str_replace(array(";","null"),"",pg_fetch_result($resSubmit,$i, $z));
					}

				}

				if (!empty(str_replace(";","",trim($tbody)))) {
					fwrite($file, "$tbody\n");
				}
			}

			fclose($file);

			if (file_exists("/tmp/{$filename}")) {
				system("mv /tmp/{$filename} xls/{$filename}");

				echo "xls/{$filename}";
			}
			exit;
		}

		if ($login_fabrica == 74) { //HD-3141903
			$th_pecas2 = "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO;
			";
			$th_mao_obra = "MÃO DE OBRA;";
		} else if(in_array($login_fabrica,array(157,164))) {
			$th_pecas2 = "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO;
			";
		} else if(!in_array($login_fabrica, array(134,140,167,169,170,183,186,178,203))) {
			if($login_fabrica == 30){
				$th_pecas2 = "Peças;Descrição da Peça";
			} else {
				$th_pecas2 = "Peças;";	
			}			
		} else if(in_array($login_fabrica, array(167,169,170,186,203))) {
			$th_pecas2 = (in_array($login_fabrica,array(167,186,203)) && $tipo_peca != "S") ? "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;SÉRIE DA PEÇA;PEDIDO;NF;EMISSÃO;MOTIVO DUPLICIDADE " : "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO ";
		} else if(in_array($login_fabrica, array(183))) {
			$th_pecas2 = ($tipo_peca != "S") ? "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO;MOTIVO DUPLICIDADE " : "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO ";
		}

		if ($login_fabrica == 50) {

			$th_extrato = "VALOR TOTAL;ACRÉSCIMO;DESCONTO;VALOR LÍQUIDO;";

			$th_revenda = "REVENDA (CLIENTE COLORMAQ);CNPJ;FONE;DATA NF;REVENDA (CONSUMIDOR);CNPJ;FONE;DATA NF;";


			$titulo = array( //HD-3263360
						'cnpj_posto' 					=> 'CNPJ Posto',
						'razao_social' 					=> 'Razão Social',
						'cidade' 						=> 'Cidade',
						'uf' 							=> 'UF',
						'protocolo' 					=> 'Protocolo',
						'extrato'		 				=> 'Extrato',
						'status' 						=> 'Status',
						'numero_os' 					=> 'Número OS',
						'tipo' 							=> 'TIPO',
						'data_abertura' 				=> 'Data Abertura',
						'data_digitacao' 				=> 'Data Digitação',
						'data_finalizacao' 				=> 'Data Finalização',
						'data_conserto' 				=> 'Data Conserto',
						'km' 							=> 'KM',
						'obs'							=> 'Obs.',
						'defeito_reclamado'				=> 'Defeito Reclamado',
						'defeito_constatado'			=> 'Defeito Constatado',
						'solucao'						=> 'Solução',
						'codigo_produto'				=> 'Código Produto',
						'descricao_produto'				=> 'Descrição Produto',
						'numero_serie'					=> 'Número de Série',
						'validacao_ns'					=> 'Validação NS',
						'data_fabricacao'				=> 'Data Fabricação',
						'consumidor'					=> 'Consumidor',
						'consumidor_cidade'				=> 'Cidade',
						'consumidor_uf'					=> 'UF',
						'telefone'						=> 'Tel.',
						'celular'						=> 'Tel. Cel.',
						'email'							=> 'Email',
						'cep'							=> 'CEP',
						'bairro'						=> 'Bairro',
						'rua'							=> 'Rua',
						'numero'						=> 'Número',
						'complemento'					=> 'Compl.',
						'revenda'						=> 'REVENDA (CLIENTE COLORMAQ)',
						'cnpj_revenda'					=> 'CNPJ',
						'fone_revenda'					=> 'FONE',
						'data_nf'						=> 'DATA NF',
						'revenda_consumidor'			=> 'REVENDA (CONSUMIDOR)',
						'revenda_consumidor_cnpj'		=> 'CNPJ',
						'revenda_consumidor_fone'		=> 'FONE',
						'revenda_consumidor_data_nf'	=> 'DATA NF',
						'codigo_peca'					=> 'CÓDIGO',
						'descricao_peca'				=> 'DESCRIÇÃO',
						'qtde_peca'						=> 'QTDE',
						'data_digitacao_peca'			=> 'DATA DE DIGITAÇÃO',
						'defeito_peca'					=> 'DEFEITO',
						'servico_peca'					=> 'SERVIÇO REALIZADO',
						'pedido_peca'					=> 'PEDIDO',
						'nf_peca'						=> 'NF',
						'emissao_peca'					=> 'EMISSÃO'
			);

		} else {

			$th_revenda = "Revenda;CNPJ;Número NF;Data Compra;";

		}

		$data = date("d-m-Y-H:i");

		$filename = "relatorio-os-{$data}.csv";

		$file = fopen("/tmp/{$filename}", "w");

		$coluna_status = "Status;";
		$coluna_status_brother = "";

		if (in_array($login_fabrica, [167, 203])) {
			$coluna_fone_posto_brother = "Fone Posto;";
			$coluna_auditoria_brother = "Auditoria;";
			$coluna_condicao_brother = "Contador;";
			$dias_em_aberto = "Dias em aberto;";
			$coluna_classificacao = "Classificação;";
			$coluna_regiao = "Região;";
			$coluna_mes_abertura = "Mês Abertura;";
			$coluna_engenharia = "Engenharia;";
			$coluna_motivo_reprova = "Motivo da reprovação;";
			$coluna_linha = "LINHA;";
		}

		if(in_array($login_fabrica,array(167,183,186,203))){
			$coluna_status = "";
			$coluna_status_brother = "Status;";
		}

		$data_fb         = "Data Fabricação;";
		$campo_horimetro = ""; 

		if ($login_fabrica == 148) {
			$data_fb = "";
			$campo_horimetro = 	"Horímetro;";		
			$colunaPin = "Produto em Estoque;PIN;";
		}

		if ($login_fabrica == 178) {
			$colunaPin = "Familia;Grupo de Defeito;";

		}

		$xxproduto_referencia_fabrica = "";
		if ($login_fabrica == 171) {
			$xxproduto_referencia_fabrica = "Referência Fábrica;";
		}

		if (in_array($login_fabrica, array(167,169,170,177,183,186,203))) {
			$coluna_tipo_atendimento = "Tipo Atendimento;";
		}
		if (in_array($login_fabrica, array(169,170))) {
			$colunas_midea = "Data Agendamento;Data Confirmação;Data Reagendamento;Inspetor;";
			$posto_codigo = "Código Posto;";
		}

		if ($login_fabrica == 148) {
			$parecer = "Parecer Final;";
			$colunaYanmer = "Tipo Atendimento;";
			$colunaYanmer .= "Valor Deslocamento;";
			$colunaYanmer .= "Valor das peças;";
			$colunaYanmer .= "Valor da mão de obra;";
			$colunaYanmer .= "Valor total;";
			$colunaYanmer .= "Auditoria Aprovada;";
			$colunaYanmer .= "Auditoria - Justificativa;";
		}
		$xxreferencia_fabrica_peca = "";
		if ($login_fabrica == 171) {
			$xxreferencia_fabrica_peca = "REFERÊNCIA FÁBRICA;";
		}

		if (in_array($login_fabrica, [167, 203])){
			$coluna_peca_serie = "SÉRIE DA PEÇA;";
		}

		if (!in_array($login_fabrica,array(1,50,74,148,157,164,167,169,170,183,186,203))) { //HD-3141903
			if($login_fabrica == 30){
				$conteudo_pecas = "{$xxreferencia_fabrica_peca}QTDE;DATA DE DIGITAÇÃO;DEFEITO;DESCRIÇÃO;CÓDIGO;SERVIÇO REALIZ.;{$coluna_peca_serie}PEDIDO;NF;EMISSÃO;";	
			} else if($login_fabrica == 140){
				$conteudo_pecas = "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;{$coluna_peca_serie}PEDIDO;NF;EMISSÃO;";
			} else if($login_fabrica == 134){
				$conteudo_pecas = "CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;{$coluna_peca_serie}PEDIDO;NF;EMISSÃO;";
			} else {
				$conteudo_pecas = "{$xxreferencia_fabrica_peca}CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;{$coluna_peca_serie}PEDIDO;NF;EMISSÃO;";
			}
		}
		if($login_fabrica == 50){ //HD-3263360
			$titulo = implode(';', $titulo)."\r\n";
			fwrite($file, $titulo);
		}else{

			$coluna_atendente  = "";
			$coluna_inspetor   = "";
			if ($login_fabrica == 183) {
				$coluna_atendente  = "Atendente;";
				$coluna_inspetor   = "Inspetor;";
			}


            $coluna_consumidor_profissao = '';
            if ($login_fabrica == 1) {

	                fwrite($file, "CNPJ Posto;Razão Social;Cidade;UF;Protocolo;Extrato;Status;Número OS;Tipo;Data Abertura;Data Digitação;Data Finalização;Data Conserto;KM;Obs.;Defeito Reclamado;Defeito Constatado;Solução;Código Produto;Descrição Produto;Número de Série;Data Fabricação;Consumidor;Cidade;UF;Tel.;Tel. Cel.;Email;CEP;Bairro;Rua;Número; Compl.;Profissão; Revenda;CNPJ;Número NF;Data Compra; \r\n");
	                $total_colunas_csv = 37;
            } else {
				if($login_fabrica == 169){
					$excel_os_sap = "Número OS SAP;";
				}

				fwrite($file,"CNPJ Posto;{$coluna_classificacao}{$posto_codigo}Razão Social;Cidade;UF;{$coluna_fone_posto_brother}{$coluna_regiao}");

				if (!in_array($login_fabrica,array(167,183,186,203))) {
					fwrite ($file, "Protocolo;Extrato;"); 
					$coluna_km = "KM;";
					$coluna_solucao = "Solução;";
				} else {
					fwrite ($file, "Extrato;"); 
					$coluna_km = "";
					$coluna_solucao = "";
					if (in_array($login_fabrica,array(183))) {
						fwrite ($file, "Solução;Protocolo;"); 
					}
				}
				if($login_fabrica == 140){
					$cabecalho = "{$coluna_status}{$coluna_auditoria_brother}{$coluna_condicao_brother}Número OS;{$coluna_status_brother}{$excel_os_sap}Tipo;{$coluna_tipo_atendimento}Data Abertura;{$coluna_mes_abertura}Data Digitação;Data Finalização;Data Conserto;{$campo_horimetro}{$colunas_midea}{$dias_em_aberto}{$coluna_km}{$th_mao_obra}Obs.;Defeito Reclamado;Defeito Constatado;{$coluna_solucao}{$xxproduto_referencia_fabrica}Código Produto;Descrição Produto;Número de Série;{$coluna_linha}{$colunaPin}{$coluna_engenharia}{$data_fb}Consumidor;Cidade;UF;Tel.;Tel.Cel.;Email;CEP;Bairro;Rua;Número;Compl.;$coluna_consumidor_profissao{$th_revenda}{$coluna_motivo_reprova}{$th_pecas2}{$parecer}{$colunaYanmer}{$conteudo_pecas}\n";
				}else if($login_fabrica == 134){
					$cabecalho = "{$coluna_status}{$coluna_auditoria_brother}{$coluna_condicao_brother}Número OS;{$coluna_status_brother}{$excel_os_sap}Tipo;{$coluna_tipo_atendimento}Data Abertura;{$coluna_mes_abertura}Data Digitação;Data Finalização;Data Conserto;{$campo_horimetro}{$colunas_midea}{$dias_em_aberto}{$coluna_km}{$th_mao_obra}Obs.;{$coluna_solucao}{$xxproduto_referencia_fabrica}Código Produto;Descrição Produto;Número de Série;{$coluna_linha}{$colunaPin}{$coluna_engenharia}{$data_fb}Consumidor;Cidade;UF;Tel.;Tel.Cel.;Email;CEP;Bairro;Rua;Número;Compl.;$coluna_consumidor_profissao{$th_revenda}{$coluna_motivo_reprova}{$th_pecas2}{$parecer}{$colunaYanmer}{$conteudo_pecas}\n";
				} else {
					$cabecalho = "{$coluna_status}{$coluna_auditoria_brother}{$coluna_condicao_brother}Número OS;{$coluna_atendente}{$coluna_status_brother}{$excel_os_sap}Tipo;{$coluna_tipo_atendimento}{$coluna_inspetor}Data Abertura;{$coluna_mes_abertura}Data Digitação;Data Finalização;Data Conserto;{$campo_horimetro}{$colunas_midea}{$dias_em_aberto}{$coluna_km}{$th_mao_obra}Obs.;Defeito Reclamado;Defeito Constatado;{$coluna_solucao}{$xxproduto_referencia_fabrica}Código Produto;Descrição Produto;Número de Série;{$coluna_linha}{$colunaPin}{$coluna_engenharia}{$data_fb}Consumidor;Cidade;UF;Tel.;Tel.Cel.;Email;CEP;Bairro;Rua;Número;Compl.;$coluna_consumidor_profissao{$th_revenda}{$coluna_motivo_reprova}{$th_pecas2}{$parecer}{$colunaYanmer};{$conteudo_pecas}\n";
				}
				fwrite($file, $cabecalho);

				$conteudo_pecas = "";
			}			
		}

		$contador_submit = pg_num_rows($resSubmit); 

		for ($i = 0; $i < $contador_submit; $i++) {
			$os = pg_fetch_result($resSubmit,$i,'os');
			$sua_os = pg_fetch_result($resSubmit,$i,'sua_os');
			if($login_fabrica == 169){
				$os_sap = pg_fetch_result($resSubmit, $i, 'os_posto');							
			}			
			if($login_fabrica == 148){
				$valor_por_km = pg_fetch_result($resSubmit, $i, 'valor_por_km');
				$qtde_km_extra = pg_fetch_result($resSubmit, $i, 'qtde_km_extra');
			}
			$extrato = pg_fetch_result($resSubmit,$i,'extrato');
			$data_abertura = pg_fetch_result($resSubmit,$i,'data_abertura');
			$finalizada = pg_fetch_result($resSubmit,$i,'finalizada');
			$data_digitacao = pg_fetch_result($resSubmit,$i,'data_digitacao');
			$data_conserto = pg_fetch_result($resSubmit,$i,'data_conserto');
			$data_fabricacao = pg_fetch_result($resSubmit,$i,'data_fabricacao');
			$produto_referencia_fabrica = pg_fetch_result($resSubmit,$i,'produto_referencia_fabrica');
			$referencia = pg_fetch_result($resSubmit,$i,'referencia');
			$descricao = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'descricao'));
			$serie = pg_fetch_result($resSubmit,$i,'serie');
			$revenda_cnpj = pg_fetch_result($resSubmit,$i,'revenda_cnpj');
			$revenda_nome = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'revenda_nome'));
			$revenda_cidade = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'revenda_cidade'));
			$revenda_estado = pg_fetch_result($resSubmit,$i,'revenda_estado');
			$revenda_fone = pg_fetch_result($resSubmit,$i,'revenda_fone');
			$consumidor_nome = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'consumidor_nome'));
			$consumidor_fone = pg_fetch_result($resSubmit,$i,'consumidor_fone');
			$consumidor_celular = pg_fetch_result($resSubmit,$i,'consumidor_celular');
			$consumidor_endereco = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'consumidor_endereco'));
			$consumidor_numero = pg_fetch_result($resSubmit,$i,'consumidor_numero');
			$consumidor_complemento = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'consumidor_complemento'));
			$consumidor_bairro = pg_fetch_result($resSubmit,$i,'consumidor_bairro');
			$consumidor_cep = pg_fetch_result($resSubmit,$i,'consumidor_cep');
			$consumidor_cidade = pg_fetch_result($resSubmit,$i,'consumidor_cidade');
			$consumidor_estado = pg_fetch_result($resSubmit,$i,'consumidor_estado');
			$consumidor_cpf = pg_fetch_result($resSubmit,$i,'consumidor_cpf');
			$consumidor_email = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'consumidor_email'));
			$consumidor_revenda = pg_fetch_result($resSubmit,$i,'consumidor_revenda');
			$obs = pg_fetch_result($resSubmit,$i,'obs');
			if($login_fabrica == 30){
				$obs = str_replace(';', '', $obs);
				$obs = str_replace('\n', ' ', $obs);
				$obs = str_replace('\r', ' ', $obs);
				$obs = str_replace('"', '\'', $obs);
				$obs = str_replace('\r\n', ' ', $obs);
				$obs = preg_replace('/\s\s+/', ' ', $obs);
				$obs = preg_replace('/\n/', ' ', $obs);				
			}
			$solucao	 = pg_fetch_result($resSubmit,$i,'solucao');
			$dr_descricao = pg_fetch_result($resSubmit, $i, 'dr_descricao');

			if ($login_fabrica == 1 && $solucao == 'Troca de produto') {
				$dc_defeito = $dr_descricao;
			} else if ($login_fabrica == 183) {
				$txt_def_c = '';
				$sql_defeitos_multiplos = "SELECT  
				                                 tbl_defeito_constatado.descricao
				                             FROM tbl_os_defeito_reclamado_constatado 
				                             JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado=tbl_os_defeito_reclamado_constatado.defeito_constatado AND tbl_defeito_constatado.fabrica={$login_fabrica}
				                            WHERE tbl_os_defeito_reclamado_constatado.os = {$os} 
				                              AND tbl_os_defeito_reclamado_constatado.defeito_constatado IS NOT NULL";
				$res_defeitos_multiplos = pg_query($con, $sql_defeitos_multiplos);
				if (pg_num_rows($res_defeitos_multiplos) > 0) {
					for($inddc = 0; $inddc < pg_num_rows($res_defeitos_multiplos); $inddc++){
						$descricao_dc = pg_fetch_result($res_defeitos_multiplos, $inddc, "descricao");
						if ($inddc == 0) {
							$txt_def_c .= $descricao_dc;
						} else {

							$txt_def_c .= ",".$descricao_dc;
						}
					}
					$dc_defeito = $txt_def_c;
				} else {
					$dc_defeito = pg_fetch_result($resSubmit,$i,'dc_defeito');
				}

			} else {
				$dc_defeito = pg_fetch_result($resSubmit,$i,'dc_defeito');
			}
			$dr_defeito = pg_fetch_result($resSubmit,$i,'dr_defeito');
			if (empty($dr_defeito) && !empty($dr_descricao) && $login_fabrica == 148) {
				$dr_defeito = $dr_descricao;
			}
			$cnpj = pg_fetch_result($resSubmit,$i,'cnpj');
			$nome = str_replace(";", ",", pg_fetch_result($resSubmit,$i,'nome'));
			$contato_cidade = pg_fetch_result($resSubmit,$i,'contato_cidade');
			$contato_estado = pg_fetch_result($resSubmit,$i,'contato_estado');
			$fone_posto = pg_fetch_result($resSubmit,$i,'contato_fone_comercial');
			$data_nf = pg_fetch_result($resSubmit,$i,'data_nf');
			$nota_fiscal = pg_fetch_result($resSubmit,$i,'nota_fiscal');
			$hd_chamado = pg_fetch_result($resSubmit,$i,'hd_chamado');
			$status = pg_fetch_result($resSubmit,$i,'status');
			$qtde_km	 = pg_fetch_result($resSubmit,$i,'qtde_km');
			if ($login_fabrica == 148) {
				$horimetro = pg_fetch_result($resSubmit, $i, 'qtde_hora');
			}
			$mao_de_obra = pg_fetch_result($resSubmit, $i, 'mao_de_obra');
			$serie_reoperado = pg_fetch_result($resSubmit, $i, 'serie_reoperado');
			$valida_serie = pg_fetch_result($resSubmit, $i, 'valida_serie');
			$condicao = pg_fetch_result($resSubmit, $i, 'condicao');
			$obs = str_replace(';', '', $obs);
			$obs = str_replace('\n', ' ', $obs);
			$obs = str_replace('\r', ' ', $obs);
			$obs = str_replace('"', '\'', $obs);
			$obs = str_replace('\r\n', ' ', $obs);
			$obs = preg_replace('/\s\s+/', ' ', $obs);
			$obs = preg_replace('/\n/', ' ', $obs);

			$dias_aberto_consumidor = pg_fetch_result($resSubmit,$i,'dias_aberto_consumidor');
			$dias_aberto_revenda = pg_fetch_result($resSubmit,$i,'dias_aberto_revenda');
            $campos_adicionais = json_decode(pg_fetch_result($resSubmit, $i, "campos_adicionais"), true);
			$os_auditoria           = pg_fetch_result($resSubmit, $i, 'os_auditoria');
			$status_auditoria           = pg_fetch_result($resSubmit, $i, 'status_auditoria');
            		$auditoria = (empty($os_auditoria)) ? "" : $status_auditoria;


            $consumidor_profissao = '';

            if ($login_fabrica == 1 and !empty($campos_adicionais)) {
                if (array_key_exists("consumidor_profissao", $campos_adicionais)) {
                    $consumidor_profissao = utf8_decode($campos_adicionais["consumidor_profissao"]);
                }
            }

            if ($login_fabrica == 183) {
            	$valor_atendente = pg_fetch_result($resSubmit, $i, 'nome_atendente').";";
            	$valor_inspetor =  pg_fetch_result($resSubmit, $i, 'inspetor_sap_posto').";";
            }

		    if (in_array($login_fabrica, array(148,167,169,170,177,183,186,203))) {
				$desc_tipo_atendimento = pg_fetch_result($resSubmit, $i, 'desc_tipo_atendimento');
		    }

			if (in_array($login_fabrica, array(169,170))) {	
				$data_agendamento = pg_fetch_result($resSubmit, $i, "data_agendamento");
				$data_confirmacao = pg_fetch_result($resSubmit, $i, "data_confirmacao");
				$inspetor_sap = pg_fetch_result($resSubmit, $i, "inspetor_sap");
				$posto_codigo = pg_fetch_result($resSubmit, $i, "posto_codigo");

				$data_reagendamento = "";
				if (!empty($data_agendamento)) {
					$sqlReagendamento = "
						SELECT
							TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_reagendamento
	                    FROM tbl_tecnico_agenda
	                    WHERE tbl_tecnico_agenda.os = {$os}
	                    AND tbl_tecnico_agenda.data_agendamento::DATE != '{$data_agendamento}'::DATE
	                    AND tbl_tecnico_agenda.confirmado IS NOT NULL
	                    ORDER BY tbl_tecnico_agenda.data_input DESC
	                    LIMIT 1
	                ";

	                $resReagendamento = pg_query($con, $sqlReagendamento);

	                $data_reagendamento = pg_fetch_result($resReagendamento, 0, 'data_reagendamento');
	            }

			}

			if (in_array($login_fabrica, [167, 203])){

				$tipo_posto 		= pg_fetch_result($resSubmit, $i, 'tipo_posto');
				$nome_comercial     = pg_fetch_result($resSubmit, $i, 'nome_comercial');
				$condicao 			= pg_fetch_result($resSubmit, $i, 'condicao');
				$xconclusao_laudo 	= pg_fetch_result($resSubmit, $i, 'conclusao_laudo');
				$jconclusao_laudo   = json_decode($xconclusao_laudo,true);
				$conclusao_laudo 	= utf8_decode($jconclusao_laudo['motivo_reprova']);
				$valor_linha        = pg_fetch_result($resSubmit, $i, 'nome_linha');

				if (strlen($data_abertura > 0)){
					$mes_abertura = explode('/', $data_abertura);
					$mes_abertura = $mes_abertura[1].'/'.$mes_abertura[2];
				}

				if (in_array($contato_estado, array('AM', 'RR', 'AP', 'PA', 'TO', 'RO', 'AC'))){
					$regiao = "Região Norte";
				}else if (in_array($contato_estado, array('MA', 'PI', 'CE', 'RN', 'PE', 'PB', 'SE', 'AL', 'BA'))){
					$regiao = "Região Nordeste";
				}else if (in_array($contato_estado, array('MT', 'MS', 'GO','DF'))){
					$regiao = "Centro-Oeste";
				}else if (in_array($contato_estado, array('SP', 'RJ', 'ES', 'MG'))){
					$regiao = "Região Sudeste";
				}else if (in_array($contato_estado, array('PR', 'RS', 'SC'))) {
					$regiao = "Região Sul";
				}else{
					$regiao = "Região não encontrada";
				}

			}

			if (in_array($login_fabrica, array(50))) {
				if(strlen(trim($valida_serie)) > 0){
					$xserie_reoperado = "Válido";
				}else{
					$xserie_reoperado = "Inválido";
				}

				/* if(strlen(trim($dr_descricao)) == 0 OR $dr_descricao == "null"){
					$dr_descricao = $dr_defeito;
				} */

				if(strlen($dr_defeito) > 0){
					$dr_descricao = $dr_defeito;
				}

				/* COLOCANDO ASPAS SIMPLES NAS STRINGS */
				if (!empty($cnpj)){ $cnpj = '"'.$cnpj.'"'; }
				if (!empty($nome)){ $nome = '"'.$nome.'"'; }
				if (!empty($contato_cidade)){ $contato_cidade = '"'.$contato_cidade.'"'; }
				if (!empty($contato_estado)){ $contato_estado = '"'.$contato_estado.'"'; }
				if (!empty($consumidor_revenda)){ $consumidor_revenda = '"'.$consumidor_revenda.'"'; }
				if (!empty($obs)){ $obs = '"'.$obs.'"'; }
				if (!empty($dr_descricao)){ $dr_descricao = '"'.$dr_descricao.'"'; }
				if (!empty($dc_defeito)){ $dc_defeito = '"'.$dc_defeito.'"'; }
				if (!empty($solucao)){ $solucao = '"'.$solucao.'"'; }
				if (!empty($descricao)){ $descricao = '"'.$descricao.'"'; }
				if (!empty($consumidor_nome)){ $consumidor_nome = '"'.$consumidor_nome.'"'; }
				if (!empty($consumidor_cidade)){ $consumidor_cidade = '"'.$consumidor_cidade.'"'; }
				if (!empty($consumidor_estado)){ $consumidor_estado = '"'.$consumidor_estado.'"'; }
				if (!empty($consumidor_email)){ $consumidor_email = '"'.$consumidor_email.'"'; }
				if (!empty($consumidor_bairro)){ $consumidor_bairro = '"'.$consumidor_bairro.'"'; }
				if (!empty($consumidor_endereco)){ $consumidor_endereco = '"'.$consumidor_endereco.'"'; }
				if (!empty($consumidor_complemento)){ $consumidor_complemento = '"'.$consumidor_complemento.'"'; }
				if (!empty($revenda_nome)){ $revenda_nome = '"'.$revenda_nome.'"'; }
				if (!empty($data_nf)){ $data_nf = '"'.$data_nf.'"'; }
				if (!empty($data_fabricacao)){ $data_fabricacao = '"'.$data_fabricacao.'"'; }
				if (!empty($data_conserto)){ $data_conserto = '"'.$data_conserto.'"'; }
				if (!empty($data_digitacao)){ $data_digitacao = '"'.$data_digitacao.'"'; }
				if (!empty($data_abertura)){ $data_abertura = '"'.$data_abertura.'"'; }
				if (!empty($finalizada)){ $finalizada = '"'.$finalizada.'"'; }
				if (!empty($consumidor_fone)){ $consumidor_fone = '"'.$consumidor_fone.'"'; }
				if (!empty($referencia)){ $referencia = '"'.$referencia.'"'; }
				if (!empty($produto_referencia_fabrica)){ $produto_referencia_fabrica = '"'.$produto_referencia_fabrica.'"'; }
				if (!empty($revenda_fone)) { $revenda_fone = '"'.$revenda_fone.'"'; }
				if (!empty($auditoria)) { $auditoria = '"'.$auditoria.'"'; }
				if (!empty($fone_posto)) { $fone_posto = '"'.$fone_posto.'"'; }

			}

			/* Status */
			$sql3 = "SELECT descricao FROM tbl_status_checkpoint WHERE status_checkpoint = {$status};";
			$resSubmit3 = pg_query($con, $sql3);

			if(pg_num_rows($resSubmit3) > 0){
				$status = pg_fetch_result($resSubmit3, 0, 'descricao');
				$status = '"'.$status.'"';
			}

			/* Revenda */
			if($login_fabrica == 50){
				if(strlen($extrato) > 0){
					$sql_extrato_pgto = "SELECT valor_total, acrescimo, desconto, valor_liquido FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
					$res_extrato_pgto = pg_query($con, $sql_extrato_pgto);

					$valor_total = pg_fetch_result($res_extrato_pgto, 0, "valor_total");
					$acrescimo = pg_fetch_result($res_extrato_pgto, 0, "acrescimo");
					$desconto = pg_fetch_result($res_extrato_pgto, 0, "descricao");
					$valor_liquido = pg_fetch_result($res_extrato_pgto, 0, "valor_liquido");

					$td_extrato = "
						$valor_total;$acrescimo;$desconto;$valor_liquido;
					";

				}else{
					$td_extrato = "; ; ; ;";
				}

				$sql_serie = "SELECT cnpj, to_char(data_venda, 'dd/mm/yyyy') as data_venda
                        FROM tbl_numero_serie
                        WHERE serie = trim('$serie')";

                $res_serie = pg_query ($con,$sql_serie);

				$serie = '"'.$serie.'"';

                if (pg_num_rows ($res_serie) > 0) {
                    $txt_cnpj   = trim(pg_fetch_result($res_serie,0,cnpj));
                    $data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

                    $sql_dados_revenda = "SELECT tbl_revenda.nome              ,
                                        tbl_revenda.revenda           ,
                                        tbl_revenda.cnpj              ,
                                        tbl_revenda.cidade            ,
                                        tbl_revenda.fone              ,
                                        tbl_revenda.endereco          ,
                                        tbl_revenda.numero            ,
                                        tbl_revenda.complemento       ,
                                        tbl_revenda.bairro            ,
                                        tbl_revenda.cep               ,
                                        tbl_revenda.email             ,
                                        tbl_cidade.nome AS nome_cidade,
                                        tbl_cidade.estado
                            FROM        tbl_revenda
                            LEFT JOIN   tbl_cidade USING (cidade)
                            LEFT JOIN   tbl_estado using(estado)
                            WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

                    $res_dados_revenda = pg_query ($con,$sql_dados_revenda);


                    if (pg_num_rows ($res_dados_revenda) > 0) {
                        $revenda_nome_1       = trim(pg_fetch_result($res_dados_revenda,0,nome));
                        $revenda_cnpj_1       = trim(pg_fetch_result($res_dados_revenda,0,cnpj));

                        $revenda_bairro_1     = trim(pg_fetch_result($res_dados_revenda,0,bairro));
                        $revenda_cidade_1     = trim(pg_fetch_result($res_dados_revenda,0,cidade));
						$revenda_fone_1       = trim(pg_fetch_result($res_dados_revenda,0,fone));
	               		$revenda_fone_1 = '"'.$revenda_fone_1.'"';
	               		$revenda_nome_1 = '"'.$revenda_nome_1.'"';
                    }

                }

                $conteudo_revenda = "$revenda_nome_1 ; $revenda_cnpj_1 ; $revenda_fone_1 ; $data_venda;";
               	$conteudo_revenda .= "$revenda_nome ; $revenda_cnpj_2 ; $nota_fiscal ; $data_nf;";
			}
			/* fim Revenda */

			/* Busca Peças */

			if ($login_fabrica == 148) {
				$sql_parecer = "SELECT comentario FROM tbl_os_interacao WHERE os = $os AND atendido = true ORDER BY data DESC LIMIT 1";
				$res_parecer = pg_query($con, $sql_parecer);
				$comentario_parecer = "";
				if (pg_num_rows($res_parecer) > 0) {
					$comentario_parecer = pg_fetch_result($res_parecer, 0, 'comentario');
				}
			}

			if (!in_array($login_fabrica, array(0))) {
				
				$sql2 = "
					SELECT 
						tbl_peca.referencia_fabrica AS referencia_fabrica_peca,
						tbl_peca.referencia AS codigo_peca,
						tbl_peca.descricao AS descricao_peca,
						tbl_os_item.peca_serie AS serie_peca,
						tbl_os_item.qtde AS quantidade_peca,
						tbl_os_item.peca_serie,
						to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item,
						".(($login_fabrica == 134) ? "tbl_os.defeito_reclamado_descricao as defeito":"tbl_defeito.descricao as defeito").",
						tbl_servico_realizado.descricao as servico_realizado,
						tbl_os_item.pedido,
						tbl_faturamento.nota_fiscal AS nf_peca,
						tbl_faturamento.emissao as data_nf_peca,
						tbl_os_item.obs AS motivo_duplicidade 
					FROM tbl_os 
					LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os 
					LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
					LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca 
					LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito 
					LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
					$join_163
					LEFT JOIN tbl_faturamento_item ON  tbl_faturamento_item.".(($login_fabrica == 74) ? "pedido_item" : "os_item")." = tbl_os_item.".(($login_fabrica == 74) ? "pedido_item" : "os_item")." 
					LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento 
					AND tbl_peca.fabrica = $login_fabrica 
					WHERE tbl_os.os = $os 
					$cond_163
				";
				$resSubmit2 = pg_query($con, $sql2);

			unset($conteudo_pecas);
			$total_item_os = 0 ; 
			if (pg_num_rows($resSubmit2) > 0) {

				$total_item_os = pg_num_rows($resSubmit2); 
				if(in_array($login_fabrica,[169,170]) || (in_array($login_fabrica,array(167,186,203)) && $tipo_peca != "S")) {
					$conteudo_pecas = array();
				}else{
					$conteudo_pecas = "";
				}


				//HD-3141903
				unset($codigo_peca_arr);
				unset($descricao_peca_arr);
				unset($quantidade_peca_arr);
				unset($digitacao_item_arr);
				unset($defeito_arr);
				unset($servico_realizado_arr);
				unset($pedido_arr);
				unset($nf_peca_arr);
				unset($data_nf_peca_arr);

				$contador_submit2 = pg_num_rows($resSubmit2);
				if($login_fabrica == 140){
					$conteudo_pecas_lavor = null;
				}
				if($login_fabrica == 134){
					$conteudo_pecas_hydra = null;
				}
				for($k = 0; $k < $contador_submit2; $k++) {
					
					$referencia_fabrica_peca = pg_fetch_result($resSubmit2,$k,'referencia_fabrica_peca');
					$codigo_peca = pg_fetch_result($resSubmit2,$k,'codigo_peca');
					$descricao_peca = pg_fetch_result($resSubmit2,$k,'descricao_peca');
					$quantidade_peca = pg_fetch_result($resSubmit2,$k,'quantidade_peca');
					$digitacao_item = pg_fetch_result($resSubmit2,$k,'digitacao_item');
					$defeito = pg_fetch_result($resSubmit2,$k,'defeito');
					$servico_realizado = pg_fetch_result($resSubmit2,$k,'servico_realizado');
					$serie_peca = pg_fetch_result($resSubmit2,$k,'serie_peca');
					$pedido            = pg_fetch_result($resSubmit2,$k,'pedido');
					$peca_serie = pg_fetch_result($resSubmit2,$k,'peca_serie');

					if (empty($codigo_peca) && empty($descricao_peca) && empty($servico_realizado) && $login_fabrica == 178) {
						continue;
					}

					$xreferencia_fabrica_peca = "";
					if ($login_fabrica == 171) {
						$xreferencia_fabrica_peca = "$referencia_fabrica_peca;";
					}

					if (in_array($login_fabrica, [167, 203])) {
						$motivo_duplicidade = pg_fetch_result($resSubmit2, $k, 'motivo_duplicidade');
						$motivo_duplicidade = preg_replace('/\s+/', ' ', $motivo_duplicidade);
					}


					if(!empty($pedido)) {
						$sql_faturamento = "SELECT trim(tbl_faturamento.nota_fiscal) AS nf_peca , TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_nf_peca FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento) WHERE tbl_faturamento_item.pedido = {$pedido};";

						$resFaturamento = pg_query($con, $sql_faturamento);
						$nf_peca = pg_fetch_result($resFaturamento,0,'nf_peca');
						$data_nf_peca = pg_fetch_result($resFaturamento,0,'data_nf_peca');
					} else {
						$nf_peca = "";
						$data_nf_peca = "";
					}

					if (in_array($login_fabrica, array(74,164))) { //HD-3141903
						$codigo_peca_arr[] 			= $codigo_peca;
						$descricao_peca_arr[] 		= $descricao_peca;
						$quantidade_peca_arr[] 		= $quantidade_peca;
						$digitacao_item_arr[] 		= $digitacao_item;
						$defeito_arr[] 				= $defeito;
						$servico_realizado_arr[] 	= $servico_realizado;
						$pedido_arr[] 				= $pedido;
						$nf_peca_arr[] 				= $nf_peca;
						$data_nf_peca_arr[] 		= $data_nf_peca;
					} elseif($login_fabrica == 50) {
						// unset($cnpj,$nome,$contato_cidade,$contato_estado,$hd_chamado,$extrato,$status,$sua_os,$consumidor_revenda,
						// 	$data_abertura,$data_digitacao,$finalizada,$data_conserto,$qtde_km,$obs,$dr_descricao,$dc_defeito,$solucao,
						// 	$referencia,$descricao,$serie,$data_fabricacao,$consumidor_nome,$consumidor_cidade,$consumidor_estado,$consumidor_fone,
						// 	$consumidor_celular,$consumidor_email,$consumidor_cep,$consumidor_bairro,$consumidor_endereco,$consumidor_numero,
						// 	$consumidor_complemento,$revenda_nome_1,$revenda_cnpj_1,$revenda_fone_1,$data_venda,$revenda_nome,$revenda_cnpj_2,
						// 	$nota_fiscal,$data_nf,$codigo_peca,$descricao_peca,$quantidade_peca,$digitacao_item,$defeito,$servico_realizado,
						// 	$pedido,$nf_peca,$data_nf_peca);
						if (!empty($descricao_peca)){ $descricao_peca = '"'.$descricao_peca.'"'; }
						if (!empty($defeito)){ $defeito = '"'.$defeito.'"'; }
						if (!empty($servico_realizado)){ $servico_realizado = '"'.$servico_realizado.'"'; }
						if (!empty($digitacao_item)){ $digitacao_item = '"'.$digitacao_item.'"'; }
						if (!empty($codigo_peca)){ $codigo_peca = '"'.$codigo_peca.'"'; }
						$linhas_result = array(
							'cnpj_posto' 					=> $cnpj,
							'razao_social' 					=> $nome,
							'cidade' 						=> $contato_cidade,
							'uf' 							=> $contato_estado,
							'protocolo' 					=> $hd_chamado,
							'extrato'		 				=> $extrato,
							'status' 						=> $status,
							'numero_os' 					=> $sua_os,
							'tipo' 							=> $consumidor_revenda,
							'data_abertura' 				=> $data_abertura,
							'data_digitacao' 				=> $data_digitacao,
							'data_finalizacao' 				=> $finalizada,
							'data_conserto' 				=> $data_conserto,
							'km' 							=> $qtde_km,
							'obs'							=> $obs,
							'defeito_reclamado'				=> $dr_descricao,
							'defeito_constatado'			=> $dc_defeito,
							'solucao'						=> $solucao,
							'codigo_produto'				=> $referencia,
							'descricao_produto'				=> $descricao,
							'numero_serie'					=> $serie,
							'validacao_ns'					=> $xserie_reoperado,
							'data_fabricacao'				=> $data_fabricacao,
							'consumidor'					=> $consumidor_nome,
							'consumidor_cidade'				=> $consumidor_cidade,
							'consumidor_uf'					=> $consumidor_estado,
							'telefone'						=> $consumidor_fone,
							'celular'						=> $consumidor_celular,
							'email'							=> $consumidor_email,
							'cep'							=> $consumidor_cep,
							'bairro'						=> $consumidor_bairro,
							'rua'							=> $consumidor_endereco,
							'numero'						=> $consumidor_numero,
							'complemento'					=> $consumidor_complemento,
							'revenda'						=> $revenda_nome_1,
							'cnpj_revenda'					=> $revenda_cnpj_1,
							'fone_revenda'					=> $revenda_fone_1,
							'data_nf'						=> $data_venda,
							'revenda_consumidor'			=> $revenda_nome,
							'revenda_consumidor_cnpj'		=> $revenda_cnpj,
							'revenda_consumidor_fone'		=> $revenda_fone,
							'revenda_consumidor_data_nf'	=> $data_nf,
							'codigo_peca'					=> $codigo_peca,
							'descricao_peca'				=> $descricao_peca,
							'qtde_peca'						=> $quantidade_peca,
							'data_digitacao_peca'			=> $digitacao_item,
							'defeito_peca'					=> $defeito,
							'servico_peca'					=> $servico_realizado,
							'pedido_peca'					=> $pedido,
							'nf_peca'						=> $nf_peca,
							'emissao_peca'					=> $data_nf_peca
						);
						foreach($linhas_result as $key => $valor){
							$linhas_result[$key] = str_replace(";","",$valor);
						}


						$linhas_result = implode(";", $linhas_result)."\r\n";
						fwrite($file, $linhas_result);
						unset($codigo_peca,$descricao_peca,$quantidade_peca,$digitacao_item,$defeito,$servico_realizado,$pedido,$nf_peca,$data_nf_peca);

					} else if ($login_fabrica == 157) {

						$nome_sem_virgula = explode(",", $nome);
						$nome_sem_virgula = implode(" - ", $nome_sem_virgula);

						$dr_defeito_sem_virgula = explode(",", $dr_defeito);
						$dr_defeito_sem_virgula = implode(" - ", $dr_defeito_sem_virgula);

						$dc_defeito_sem_virgula = explode(",", $dc_defeito);
						$dc_defeito_sem_virgula = implode(" - ", $dc_defeito_sem_virgula);

						$obs_sem_virgula = explode(",", $obs);
						$obs_sem_virgula = implode(" - ", $obs_sem_virgula);

						$descricao_peca_sem_virgula = explode(",", $descricao_peca);
						$descricao_peca_sem_virgula = implode(" - ", $descricao_peca_sem_virgula);

						$consumidor_endereco_peca_sem_virgula = explode(",", $consumidor_endereco);
						$consumidor_endereco_peca_sem_virgula = implode(" - ", $consumidor_endereco_peca_sem_virgula);
						
						// em alguns casos, os campos estao trazendo uma ",", o que esta dando quebra de coluna

                        $conteudo_pecas .= "$cnpj;" . $nome_sem_virgula . ";$contato_cidade;$contato_estado; ;$extrato;$status;$sua_os;$consumidor_revenda;$data_abertura;$data_digitacao;$finalizada;$data_conserto;$qtde_km;" . $obs_sem_virgula . ";" . $dr_defeito_sem_virgula . ";" . $dc_defeito_sem_virgula . ";$solucao;$referencia;$descricao;$serie;$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;" . $consumidor_endereco_peca_sem_virgula . ";$consumidor_numero;$consumidor_complemento;$revenda_nome;$revenda_cnpj;$nota_fiscal;$data_nf;$codigo_peca;" . $descricao_peca_sem_virgula . ";$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;\n";

					} else {
						if(in_array($login_fabrica,array(167,186,203))) {

							$conteudo_pecas[]= "$codigo_peca;$descricao_peca;$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$serie_peca;$pedido;$nf_peca;$data_nf_peca;$motivo_duplicidade;";
						}elseif(in_array($login_fabrica,array(183))) {

							$conteudo_pecas[]= "$codigo_peca;$descricao_peca;$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;$motivo_duplicidade;";
						}elseif(in_array($login_fabrica,[169,170])) {
								$conteudo_pecas[] = "{$xreferencia_fabrica_peca}"."'"."$codigo_peca"."'".";$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;";
						}elseif($login_fabrica == 178) {
								$conteudo_pecas[] = "{$xreferencia_fabrica_peca}$codigo_peca;$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;";
						}else{
							if ($login_fabrica == 1) {

								$separador = "";
								for ($col=0;$col <= $total_colunas_csv;$col++) {
									$separador .= ";";
								}

								$conteudo_pecas_csv .= $separador."$codigo_peca;$descricao_peca;$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca \r\n";
							}elseif($login_fabrica == 148) {
								$conteudo_pecas .= (isset($codigo_peca)) ? "$codigo_peca - {$descricao_peca}," : "";
							} else {
								if($login_fabrica == 30){
									$conteudo_pecas .= "{$xreferencia_fabrica_peca}$codigo_peca;$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;;;";
								} else if($login_fabrica == 140){
									$conteudo_pecas_lavor[] = "$codigo_peca;$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;";
								} else if($login_fabrica == 134){
									$conteudo_pecas_hydra[] = "$codigo_peca;$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;";
								} else {
									$conteudo_pecas .= "{$xreferencia_fabrica_peca};$codigo_peca;$descricao_peca;{$xpeca_serie}$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca;";
								}
							}
						}
					}
				}
				
				if (!in_array($login_fabrica,array(50,157,167,183,186,203))) {
					if (!in_array($login_fabrica, array(74,164))) { //HD-3141903

					} else {
						$conteudo_pecas .= "".implode("<br/>", $codigo_peca_arr).";".implode("<br/>", $descricao_peca_arr).";".implode("<br/>", $quantidade_peca_arr).";".implode("<br/>", $digitacao_item_arr).";".implode("<br/>", $defeito_arr).";".implode("<br/>", $servico_realizado_arr).";".implode("<br/>", $pedido_arr).";".implode("<br/>", $nf_peca_arr).";".implode("<br/>", $data_nf_peca_arr).";
							";
					}
				}

			} else {

				if ($login_fabrica == 50) {
					$linhas_result = array(
						'cnpj_posto' 					=> $cnpj,
						'razao_social' 					=> $nome,
						'cidade' 						=> $contato_cidade,
						'uf' 							=> $contato_estado,
						'protocolo' 					=> $hd_chamado,
						'extrato'		 				=> $extrato,
						'status' 						=> $status,
						'numero_os' 					=> $sua_os,
						'tipo' 							=> $consumidor_revenda,
						'data_abertura' 				=> $data_abertura,
						'data_digitacao' 				=> $data_digitacao,
						'data_finalizacao' 				=> $finalizada,
						'data_conserto' 				=> $data_conserto,
						'km' 							=> $qtde_km,
						'obs'							=> $obs,
						'defeito_reclamado'				=> $dr_descricao,
						'defeito_constatado'			=> $dc_defeito,
						'solucao'						=> $solucao,
						'codigo_produto'				=> $referencia,
						'descricao_produto'				=> $descricao,
						'numero_serie'					=> $serie,
						'validacao_ns'					=> $xserie_reoperado,
						'data_fabricacao'				=> $data_fabricacao,
						'consumidor'					=> $consumidor_nome,
						'consumidor_cidade'				=> $consumidor_cidade,
						'consumidor_uf'					=> $consumidor_estado,
						'telefone'						=> $consumidor_fone,
						'celular'						=> $consumidor_celular,
						'email'							=> $consumidor_email,
						'cep'							=> $consumidor_cep,
						'bairro'						=> $consumidor_bairro,
						'rua'							=> $consumidor_endereco,
						'numero'						=> $consumidor_numero,
						'complemento'					=> $consumidor_complemento,
						'revenda'						=> $revenda_nome_1,
						'cnpj_revenda'					=> $revenda_cnpj_1,
						'fone_revenda'					=> $revenda_fone_1,
						'data_nf'						=> $data_venda,
						'revenda_consumidor'			=> $revenda_nome,
						'revenda_consumidor_cnpj'		=> $revenda_cnpj,
						'revenda_consumidor_fone'		=> $revenda_fone,
						'revenda_consumidor_data_nf'	=> $data_nf,
						'codigo_peca'					=> $codigo_peca,
						'descricao_peca'				=> $descricao_peca,
						'qtde_peca'						=> $quantidade_peca,
						'data_digitacao_peca'			=> $digitacao_item,
						'defeito_peca'					=> $defeito,
						'servico_peca'					=> $servico_realizado,
						'pedido_peca'					=> $pedido,
						'nf_peca'						=> $nf_peca,
						'emissao_peca'					=> $data_nf_peca
					);
					$linhas_result = implode(";", $linhas_result)."\r\n";
					
					fwrite($file, $linhas_result);

				} else if ($login_fabrica == 157) {



					$nome_sem_virgula = explode(",", $nome);
					$nome_sem_virgula = implode(" - ", $nome_sem_virgula);

					$dr_defeito_sem_virgula = explode(",", $dr_defeito);
					$dr_defeito_sem_virgula = implode(" - ", $dr_defeito_sem_virgula);

					$dc_defeito_sem_virgula = explode(",", $dc_defeito);
					$dc_defeito_sem_virgula = implode(" - ", $dc_defeito_sem_virgula);

					$obs_sem_virgula = explode(",", $obs);
					$obs_sem_virgula = implode(" - ", $obs_sem_virgula);

					$descricao_peca_sem_virgula = explode(",", $descricao_peca);
					$descricao_peca_sem_virgula = implode(" - ", $descricao_peca_sem_virgula);

					$consumidor_endereco_peca_sem_virgula = explode(",", $consumidor_endereco);
					$consumidor_endereco_peca_sem_virgula = implode(" - ", $consumidor_endereco_peca_sem_virgula);
					

						// em alguns casos, os campos estao trazendo uma ",", o que esta dando quebra de coluna

                    $conteudo_pecas .= "$cnpj;" . $nome_sem_virgula . ";$contato_cidade;$contato_estado;;$extrato;$status;$sua_os;$consumidor_revenda;$data_abertura;$data_digitacao;$finalizada;$data_conserto;$qtde_km;" . $obs_sem_virgula . ";" . $dr_defeito_sem_virgula . ";" . $dc_defeito_sem_virgula . ";$solucao;$referencia;$descricao;$serie;$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;$revenda_nome;$revenda_cnpj;$nota_fiscal;$data_nf;;;;;;;;;\n";

				}

			}
			}			

			if ($login_fabrica == 74) {//HD-3141903
				$td_pecas = "$conteudo_pecas";
				$td_mao_obra = "$mao_de_obra;";
			} else if($login_fabrica == 164) {
				$td_pecas = "$conteudo_pecas";
			} else if(!in_array($login_fabrica, array(50))) {
				$td_pecas = "$conteudo_pecas;";
			}

			if (in_array($login_fabrica,array(167,183,186,203)) && $tipo_peca == "S") {
				$td_pecas = "Sem Peças; Sem Peças;";
			}

			if (!in_array($login_fabrica,array(50,157))) {
				$conteudo_revenda = "$revenda_nome;$revenda_cnpj;$nota_fiscal;$data_nf;";
			}

			if (in_array($login_fabrica, [167, 203])) {
				if($consumidor_revenda == "C"){
					$qtde_dias_em_aberto = $dias_aberto_consumidor;
				}else{
					$qtde_dias_em_aberto = $dias_aberto_revenda;
				}
				$campos_fone_posto_brother = "".$fone_posto.";";
				$campos_auditoria_brother = "".$auditoria.";";
				$campos_condicao_brother = "".$condicao.";";
				$td_dias_em_aberto = "{$qtde_dias_em_aberto};";
			}
			$xproduto_referencia_fabrica = "";
			if ($login_fabrica == 171) {
				$xproduto_referencia_fabrica = "$produto_referencia_fabrica;";
			}

			$colunas_midea = "";
			$valor_tipo_atendimento = "";
			
			if (in_array($login_fabrica, array(167,169,170,183,186,203))) {
				$valor_tipo_atendimento = "{$desc_tipo_atendimento};";
			}

			if (in_array($login_fabrica, array(169,170))) {
				$colunas_midea = "{$data_agendamento};{$data_confirmacao};{$data_reagendamento};{$inspetor_sap};";
				$posto_codigo = "{$posto_codigo};";
			}

			if (in_array($login_fabrica, [167, 203])){
				$res_tipo_posto = "$tipo_posto;";
				$res_nome_comercial = "$nome_comercial;";
				$res_conclusao_laudo = "$conclusao_laudo;";
				$res_mes_abertura = "$mes_abertura;";
				$res_regiao = "$regiao;";
			}

            $linha_consumidor_profissao = '';
            if ($login_fabrica == 1) {
                $linha_consumidor_profissao = $consumidor_profissao . ';';
            }

            if ($login_fabrica == 148) {

            	$sqlCustoPeca = "SELECT SUM(custo_peca) as valor_peca  
            						from tbl_os_item 
            						join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
            						WHERE tbl_os_produto.os = $os ";
            	$resCustoPeca = pg_query($con, $sqlCustoPeca);
            	if(pg_num_rows($resCustoPeca) > 0){
            		$valor_peca = pg_fetch_result($resCustoPeca, 0, "valor_peca");
            	}

            	$valor_deslocamento = "";
            	$valor_deslocamento = $valor_por_km * $qtde_km_extra;

            	$total_valor_os = $valor_deslocamento + $valor_peca + $mao_de_obra;

				$comentario_parecer = str_replace(';', '', $comentario_parecer);
				$comentario_parecer = str_replace('\n', ' ', $comentario_parecer);
				$comentario_parecer = str_replace('\r', ' ', $comentario_parecer);
				$comentario_parecer = str_replace('"', '\'', $comentario_parecer);

				$valor_parecer = "$comentario_parecer;";

            	$valorYanmar = "$desc_tipo_atendimento;";
            	$valorYanmar .= "$valor_deslocamento;";
            	$valorYanmar .= "$valor_peca;";
            	$valorYanmar .= "$mao_de_obra;";
            	$valorYanmar .= "$total_valor_os;";

            	$aud_liberada = "";
            	$linhaAuditoriaJustificativa = "";

            	$sqlAuditoria = "SELECT auditoria_os, tbl_auditoria_os.justificativa, tbl_auditoria_os.auditoria_status, tbl_auditoria_status.descricao as nome_auditoria, liberada from tbl_auditoria_os join tbl_auditoria_status on tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status where os = $os ";
            	$resAuditoria = pg_query($con, $sqlAuditoria);

            	$contador_auditoria = pg_num_rows($resAuditoria); 

            	for($aud = 0; $aud < $contador_auditoria; $aud++){
            		$liberada = pg_fetch_result($resAuditoria, $aud, 'liberada');
            		$justificativa = utf8_decode(pg_fetch_result($resAuditoria, $aud, 'justificativa'));
            		$nome_auditoria = pg_fetch_result($resAuditoria, $aud, 'nome_auditoria');

            		$linhaAuditoriaJustificativa .= "{$nome_auditoria},";
            		if(strlen(trim($liberada))>0){
            			$aud_liberada .= " $nome_auditoria, ";	
            		}
            		
            	}
            	$justificativaAuditoria = "{$linhaAuditoriaJustificativa};$justificativa";
            }

			if (!in_array($login_fabrica,array(50,157))) {
				
				if ($login_fabrica == 1) {
					$aux_sql = "
						SELECT tbl_posto_fabrica.codigo_posto
						FROM tbl_posto_fabrica
						JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os LIMIT 1
					";
					
					$aux_res  = pg_query($con, $aux_sql);
					$auxiliar = pg_fetch_result($aux_res, 0, 0);

					$aux_os = $auxiliar.$sua_os;

					unset($aux_sql, $aux_res);
				} else {
					$aux_os = $sua_os;
				}
				if(in_array($login_fabrica,array(167,183,186,203)) and count($conteudo_pecas) > 0 && $tipo_peca != "S") {
					$valor_linha = (in_array($login_fabrica, [167, 203])) ? $valor_linha.";" : "";
					$xvalor_solucao = ($login_fabrica == 183) ? $solucao.";$hd_chamado;" : "";
					foreach($conteudo_pecas as $pecas) {

						fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$extrato;{$xvalor_solucao}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$valor_atendente}$status;$consumidor_revenda;{$valor_tipo_atendimento}{$valor_inspetor}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$colunas_midea}{$td_dias_em_aberto}{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$valor_linha$res_nome_comercial$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;$linha_consumidor_profissao{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}\n");
					}
				}elseif(in_array($login_fabrica,[170]) and count($conteudo_pecas) > 0) {
					foreach($conteudo_pecas as $pecas) {					
						fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$hd_chamado;$extrato;$status;{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}$qtde_km;{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;$solucao;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$data_fabricacao;$valorPin{$res_nome_comercial}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");
						
					}
				}elseif($login_fabrica == 169) {
					foreach($conteudo_pecas as $pecas) {					
						$campo_os_sap = $os_sap . ";";						
						fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$hd_chamado;$extrato;$status;{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$campo_os_sap}$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}$qtde_km;{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;$solucao;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$data_fabricacao;$valorPin{$res_nome_comercial}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");
						
					}					
				}else{
					if ($login_fabrica == 1) {
						fwrite($file, "$cnpj;$nome;$contato_cidade;$contato_estado;$hd_chamado;$extrato;$status;$aux_os;$consumidor_revenda;$data_abertura;$data_digitacao;$finalizada;$data_conserto;$qtde_km;$obs;$dr_defeito;$dc_defeito;$solucao;$referencia;$descricao;$serie;$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;$linha_consumidor_profissao$revenda_nome;$revenda_cnpj;$nota_fiscal;$data_nf;CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO \r\n");
						fwrite($file, $conteudo_pecas_csv);

						$conteudo_pecas_csv = "";
					} else {

						$valor_dt_fb       = "$data_fabricacao;";
						$valor_horimetro   = "";

						if ($login_fabrica == 148) {
							$valor_horimetro = "$horimetro;";

							$produto_em_estoque = getProdutoEmGarantia($os);

							$ordem_Pin = "";
                            $sqlns = "SELECT ordem from tbl_numero_serie where referencia_produto  = '$referencia' and serie = '$serie'  and fabrica = $login_fabrica"; 
                            $resns = pg_query($con, $sqlns);
                            if(pg_num_rows($resns)>0){
                                $ordem_Pin =pg_fetch_result($resns, 0, 'ordem');
                            }
							$valorPin = "$produto_em_estoque;$ordem_Pin;";
							$valor_dt_fb = "";
							$td_pecas = str_replace('\n', ' ', $td_pecas);
							$td_pecas = str_replace('\r', ' ', $td_pecas);	
							$td_pecas = str_replace('\r\n', ' ', $td_pecas);
							$td_pecas = preg_replace('/\s\s+/', ' ', $td_pecas);
							$td_pecas = preg_replace('/\n/', ' ', $td_pecas);
							


						}

						$valor_status = $status.";";
						$valor_status_brother = "";
						$valor_solucao = $solucao.";";
						$valor_qtde_km = $qtde_km.";";
						$valor_hd_chamado = $hd_chamado.";";
						$valor_coluna_linha = "";

						if (in_array($login_fabrica,array(167,203))) {
							$valor_status = "";
							$valor_solucao = "";
							$valor_qtde_km = "";
							$valor_hd_chamado = "";
							$valor_coluna_linha = $valor_linha.";";
						}

						if (in_array($login_fabrica,array(167,183,186,203))) {
							$valor_status_brother = $status.";";
						}



						if($login_fabrica == 178){

		 				  	$sqlGrupo = "
						        SELECT tbl_defeito_constatado_grupo.descricao AS nome_grupo, tbl_familia.descricao AS nome_familia, tbl_os.produto, tbl_os.defeito_constatado
						          FROM tbl_os
						          JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						          JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
						          LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
						          LEFT JOIN tbl_diagnostico ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_diagnostico.fabrica = {$login_fabrica}
						          LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = {$login_fabrica}
						         WHERE tbl_os.fabrica = {$login_fabrica} 
						           AND tbl_os.os = {$os}";

						    $resGrupo = pg_query($con, $sqlGrupo);

							$nome_grupo = pg_fetch_result($resGrupo, 0, 'nome_grupo');
							$nome_familia = pg_fetch_result($resGrupo, 0, 'nome_familia');

							unset($resGrupo, $sqlGrupo);
							$valorPin = "$nome_familia;$nome_grupo;";

							if (count($conteudo_pecas) > 0 && $td_pecas != ";") {
								foreach ($conteudo_pecas as $pecas) {
									fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$hd_chamado;$extrato;$status;{$campos_auditoria_brother}{$campos_condicao_brother}$auxiliar$sua_os;$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}$qtde_km;{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;$solucao;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo};{$pecas}{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");
								}
							} else {
								fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$hd_chamado;$extrato;$status;{$campos_auditoria_brother}{$campos_condicao_brother}$auxiliar$sua_os;$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}$qtde_km;{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;$solucao;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$td_pecas}{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");
							}
							
						}else if ($login_fabrica == 186) {

							fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}$extrato;{$xvalor_solucao}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;$status;$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$colunas_midea}{$td_dias_em_aberto}{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;$valor_linha$res_nome_comercial$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;$linha_consumidor_profissao{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}\n");

						} else if (in_array($login_fabrica, [148])) {

								fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}{$valor_hd_chamado}$extrato;{$valor_status}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$valor_status_brother}$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}{$valor_qtde_km}{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;{$valor_solucao}{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;{$valor_coluna_linha}$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$conteudo_pecas};{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");
							
						} else if($login_fabrica == 140){
							if (count($conteudo_pecas_lavor) > 0) {
								foreach ($conteudo_pecas_lavor as $pecas) {
									$body .= str_replace(array(','), " ", "$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}{$valor_hd_chamado}$extrato;{$valor_status}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$valor_status_brother}$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}{$valor_qtde_km}{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;{$valor_solucao}{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;{$valor_coluna_linha}$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}\n");
								}
							}
						} else if($login_fabrica == 134){
							if (count($conteudo_pecas_hydra) > 0) {
								foreach ($conteudo_pecas_hydra as $pecas) {
									$body .= str_replace(array(','), " ", "$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}{$valor_hd_chamado}$extrato;{$valor_status}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$valor_status_brother}$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}{$valor_qtde_km}{$td_mao_obra}$obs;$dc_defeito;{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;{$valor_coluna_linha}$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$pecas}\n");
								}
							}
						} else {

							fwrite($file,"$cnpj;{$res_tipo_posto}{$posto_codigo}$nome;$contato_cidade;$contato_estado;{$campos_fone_posto_brother}{$res_regiao}{$valor_hd_chamado}$extrato;{$valor_status}{$campos_auditoria_brother}{$campos_condicao_brother}$aux_os;{$valor_status_brother}$consumidor_revenda;{$valor_tipo_atendimento}$data_abertura;{$res_mes_abertura}$data_digitacao;$finalizada;$data_conserto;{$valor_horimetro}{$colunas_midea}{$td_dias_em_aberto}{$valor_qtde_km}{$td_mao_obra}$obs;$dr_defeito;$dc_defeito;{$valor_solucao}{$xproduto_referencia_fabrica}$referencia;$descricao;$serie;{$valor_coluna_linha}$valorPin{$res_nome_comercial}{$valor_dt_fb}$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;{$linha_consumidor_profissao}{$conteudo_revenda}{$res_conclusao_laudo}{$td_pecas}{$valor_parecer}{$valorYanmar}{$aprovadasAuditoria}{$justificativaAuditoria}\n");

						}
					}
				}
			}
            if ($login_fabrica == 157) {

                fwrite($file,$conteudo_pecas);
            }
		}

		if($login_fabrica == 140){
			fwrite($file,$body);
		}

		if($login_fabrica == 134){
			fwrite($file,$body);
		}

		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
		

			if($login_fabrica == 169 && $tipo_relatorio == 'xls'){

				$data = date("d-m-Y-H:i");
				$filepath = "/tmp/{$filename}";
				$filename = "relatorio-os-{$data}.xls";
			
				converteExcel($filepath, $filename);
				unlink($filepath);
			}

			system("mv /tmp/{$filename} xls/{$filename}");
			echo "xls/{$filename}";
		}

		exit;
	}

}


function converteExcel($filepath, $filename){

	require_once('../class/PHPExcel/Classes/PHPExcel.php');

	$objReader = PHPExcel_IOFactory::createReader('CSV');
	$objReader->setDelimiter(";");
	$objReader->setInputEncoding('iso-8859-1');

	$objPHPExcel = $objReader->load($filepath);

	foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

	    $objPHPExcel->setActiveSheetIndex($objPHPExcel->getIndex($worksheet));

	    $sheet = $objPHPExcel->getActiveSheet();
	    $lastrow = $sheet->getHighestRow();
	    $lastcol = $sheet->getHighestDataColumn();

	    $styleArray = array('font' => array('bold' => true));

	    $range = 'A1:'.$lastcol.'1';
	    $sheet->getStyle($range)->applyFromArray($styleArray);
	    $sheet->getStyle($range)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	    $last = $lastcol . $lastrow;
	    $range = 'A2:'.$last;
		$sheet->getStyle($range)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$sheet->getStyle($range)->getAlignment()->setWrapText(true);

		// Formata todos os campos como texto
	 	$sheet->getStyle($range)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

	 	// Formata apenas o campo quantidade como número
	 	$range = 'AU2:AU'.$lastrow;
	 	$sheet->getStyle($range)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

	    $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
	    $cellIterator->setIterateOnlyExistingCells(true);
	    foreach ($cellIterator as $cell) {
    		$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
	    }
	}

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('/tmp/' . $filename);
}


/* ---------------------- */

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"informacaoCompleta",
	"multiselect"
);

include("plugin_loader.php");

$form = array(
	"data_inicial" => array(
		"span"      => 4,
		"label"     => "Data Início",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),
	"data_final" => array(
		"span"      => 4,
		"label"     => "Data Final",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),	
	"tipo_os" => array(
		"span"      => 4,
		"label"     => "Tipo OS",
		"type"      => "radio",
		"radios"  => array(
			"C" => "Consumidor",
			"R" => "Revenda",
		)
	),

	"codigo_posto" => array(
		"span"      => 4,
		"label"     => "Código do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "codigo"
		)
	),

	"descricao_posto" => array(
		"span"      => 4,
		"label"     => "Nome do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "nome"
		)
	),

	"estado" => array(
		"span"      => 4,
		"label"     => "Estado",
		"type"      => "select",
		"width"     => 10,
		"options"=>array('AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas' ,
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal' ,
            'GO' => 'Goiás' ,
            'ES' => 'Espirito Santo',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piaui',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SE' => 'Sergipe',
            'SP' => 'São Paulo',
            'TO' => 'Tocantins',
        )
    )
);

if($login_fabrica == 30){
	$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => "Data Início",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => "Data Final",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),	
		"codigo_posto" => array(
			"span"      => 4,
			"label"     => "Código do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "codigo"
			)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => "Nome do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "nome"
			)
		),
		"tipo_os" => array(
			"span"      => 4,
			"label"     => "Tipo OS",
			"type"      => "radio",
			"radios"  => array(
				"C" => "Consumidor",
				"R" => "Revenda",
			)
		),
		"estado" => array(
			"span"      => 4,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 10,
			"options"=>array('AC' => 'Acre',
	            'AL' => 'Alagoas',
	            'AP' => 'Amapá',
	            'AM' => 'Amazonas' ,
	            'BA' => 'Bahia',
	            'CE' => 'Ceará',
	            'DF' => 'Distrito Federal' ,
	            'GO' => 'Goiás' ,
	            'ES' => 'Espirito Santo',
	            'MA' => 'Maranhão',
	            'MT' => 'Mato Grosso',
	            'MS' => 'Mato Grosso do Sul',
	            'MG' => 'Minas Gerais',
	            'PA' => 'Pará',
	            'PB' => 'Paraíba',
	            'PR' => 'Paraná',
	            'PE' => 'Pernambuco',
	            'PI' => 'Piaui',
	            'RJ' => 'Rio de Janeiro',
	            'RN' => 'Rio Grande do Norte',
	            'RS' => 'Rio Grande do Sul',
	            'RO' => 'Rondônia',
	            'RR' => 'Roraima',
	            'SC' => 'Santa Catarina',
	            'SE' => 'Sergipe',
	            'SP' => 'São Paulo',
	            'TO' => 'Tocantins',
	        )
	    )
	);
}

if($login_fabrica == 169){	
	$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => "Data Início",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => "Data Final",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),	
		"data_referencia" => array(		
			"span"      => 8,
			"required"  => true,
			"label"     => "Data de Referência",
			"type"      => "radio",		
			"id"		=> "data_referencia",
			"radios"  => array(
				"A" => "Abertura",
				"F" => "Finalizada",			
			)
		),
		"tipo_os" => array(
			"span"      => 4,
			"label"     => "Tipo OS",
			"type"      => "radio",
			"radios"  => array(
				"C" => "Consumidor",
				"R" => "Revenda",
			)
		),
		"codigo_posto" => array(
			"span"      => 4,
			"label"     => "Código do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "codigo"
			)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => "Nome do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "nome"
			)
		),
		"estado" => array(
			"span"      => 4,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 10,
			"options"=>array('AC' => 'Acre',
	            'AL' => 'Alagoas',
	            'AP' => 'Amapá',
	            'AM' => 'Amazonas' ,
	            'BA' => 'Bahia',
	            'CE' => 'Ceará',
	            'DF' => 'Distrito Federal' ,
	            'GO' => 'Goiás' ,
	            'ES' => 'Espirito Santo',
	            'MA' => 'Maranhão',
	            'MT' => 'Mato Grosso',
	            'MS' => 'Mato Grosso do Sul',
	            'MG' => 'Minas Gerais',
	            'PA' => 'Pará',
	            'PB' => 'Paraíba',
	            'PR' => 'Paraná',
	            'PE' => 'Pernambuco',
	            'PI' => 'Piaui',
	            'RJ' => 'Rio de Janeiro',
	            'RN' => 'Rio Grande do Norte',
	            'RS' => 'Rio Grande do Sul',
	            'RO' => 'Rondônia',
	            'RR' => 'Roraima',
	            'SC' => 'Santa Catarina',
	            'SE' => 'Sergipe',
	            'SP' => 'São Paulo',
	            'TO' => 'Tocantins',
	        )
	    )
	);
}

if ($login_fabrica == 148) {
	$form = array(
		"data_inicial" => array(
			"span"      => 4,
			"label"     => "Data Início",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),
		"data_final" => array(
			"span"      => 4,
			"label"     => "Data Final",
			"type"      => "input/text",
			"width"     => 4,
			"required"  => true,
			"maxlength" => 10
		),
		"tipo_os" => array(
			"span"      => 4,
			"label"     => "Tipo OS",
			"type"      => "checkbox",
			"checks"  => array(
				"C" => "Consumidor",
				"R" => "Revenda",
			)
		),
		"codigo_posto" => array(
			"span"      => 4,
			"label"     => "Código do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "codigo"
			)
		),
		"descricao_posto" => array(
			"span"      => 4,
			"label"     => "Nome do Posto",
			"type"      => "input/text",
			"width"     => 10,
			"lupa"      => array(
				"name" => "lupa_posto",
				"tipo" => "posto",
				"parametro" => "nome"
			)
		),
		"estado" => array(
			"span"      => 4,
			"label"     => "Estado",
			"type"      => "select",
			"width"     => 10,
			"options"=>array('AC' => 'Acre',
	            'AL' => 'Alagoas',
	            'AP' => 'Amapá',
	            'AM' => 'Amazonas' ,
	            'BA' => 'Bahia',
	            'CE' => 'Ceará',
	            'DF' => 'Distrito Federal' ,
	            'GO' => 'Goiás' ,
	            'ES' => 'Espirito Santo',
	            'MA' => 'Maranhão',
	            'MT' => 'Mato Grosso',
	            'MS' => 'Mato Grosso do Sul',
	            'MG' => 'Minas Gerais',
	            'PA' => 'Pará',
	            'PB' => 'Paraíba',
	            'PR' => 'Paraná',
	            'PE' => 'Pernambuco',
	            'PI' => 'Piaui',
	            'RJ' => 'Rio de Janeiro',
	            'RN' => 'Rio Grande do Norte',
	            'RS' => 'Rio Grande do Sul',
	            'RO' => 'Rondônia',
	            'RR' => 'Roraima',
	            'SC' => 'Santa Catarina',
	            'SE' => 'Sergipe',
	            'SP' => 'São Paulo',
	            'TO' => 'Tocantins',
	        )
	    )
	);
}

if ($login_fabrica == 148) {
	$form['linha'] = array(
			"span"    => 4,
			"id"      => "linha",
			"label"   => "Linha",
			"type"    => "select",
			"width"   => 10
		);
	
	$sql = "
		SELECT linha, nome
		FROM tbl_linha
		WHERE fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$form["linha"]["options"] = array();
		while ($linha = pg_fetch_object($res)) {
			$form["linha"]["options"][$linha->linha] = $linha->nome;
		}
	}

	$form['familia'] = array(
			"span"    => 4,
			"id"      => "familia",
			"label"   => "Familia",
			"type"    => "select",
			"width"   => 10
		);
	
	$sql = "
		SELECT familia, descricao
		FROM tbl_familia
		WHERE fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$form["familia"]["options"] = array();
		while ($familia = pg_fetch_object($res)) {
			$form["familia"]["options"][$familia->familia] = $familia->descricao;
		}
	}
}

if (in_array($login_fabrica, array(148,167,169,170,177,186,203))){
    $form['tipo_atendimento[]'] = array(
		"span"    => 4,
		"id"      => "tipo_atendimento",
		"label"   => "Tipo Atendimento",
		"type"    => "select",
		"width"   => 10,
		"extra"   => array('multiple' => 'multiple')
    );

	$sql = "
		SELECT tipo_atendimento, descricao
		FROM tbl_tipo_atendimento
		WHERE fabrica = {$login_fabrica} ";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$form["tipo_atendimento[]"]["options"] = array();
		while ($tipo_atendimento = pg_fetch_object($res)) {
			$form["tipo_atendimento[]"]["options"][$tipo_atendimento->tipo_atendimento] = $tipo_atendimento->descricao;
		}
	}

	if (in_array($login_fabrica, array(169,170))) {
		$form['status[]'] = array(
			"span"    => 4,
			"id"      => "status",
			"label"   => "Status da Ordem de Serviço",
			"type"    => "select",
			"width"   => 10,
			"extra"   => array('multiple' => 'multiple')
		);
	
		$sql = "
			SELECT status_checkpoint, descricao
			FROM tbl_status_checkpoint
			WHERE (status_checkpoint IN(1, 2, 3, 4, 9, 8, 28, 14, 0, 30) or $login_fabrica = any(fabricas))";
		$res = pg_query($con, $sql);
	
		if (pg_num_rows($res) > 0){
			$form["status[]"]["options"] = array();
			while ($status = pg_fetch_object($res)) {
				$form["status[]"]["options"][$status->status_checkpoint] = $status->descricao;
			}
		}	

		$form['linha'] = array(
			"span"    => 4,
			"id"      => "linha",
			"label"   => "Linha",
			"type"    => "select",
			"width"   => 10
		);
	
		$sql = "
			SELECT linha, nome
			FROM tbl_linha
			WHERE fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
	
		if (pg_num_rows($res) > 0){
			$form["linha"]["options"] = array();
			while ($linha = pg_fetch_object($res)) {
				$form["linha"]["options"][$linha->linha] = $linha->nome;
			}
		}	
	}
}

if (in_array($login_fabrica,array(167,186,203))) {
	$form['tipo_peca'] = array(
		"span"      => 4,
		"label"     => "Tipo Relatório",
		"type"      => "radio",
		"radios"  => array(
			"C" => "Com Peça",
			"S" => "Sem Peça",
		)
	);
}

if ($login_fabrica == 148) {
	$form['n_serie'] = array(
		"span"      => 4,
		"label"     => "Número de Série",
		"type"      => "input/text",
		"width"     => 10
	);
}

if ($login_fabrica == 35) {
    $form['campos_pesquisa[]'] = array(
		"span"    => 4,
		"id"      => "campos_pesquisa",
		"label"   => "Campos Pesquisa",
		"type"    => "select",
		"width"   => 10,
		"extra"   => array('multiple' => 'multiple'),
		"options" => $array_campos_pesquisa
    );
}

if ($login_fabrica == 50) {
    $form["status_os"] = array(
        "span" => 4,
        "type" => "checkbox",
        "checks" => array(
            81 => "OS Mão-de-obra Zerada"
        ),
	);
}
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("#campos_pesquisa").multiselect({
        	selectedText: "selecionados # de #",
        });

        $("#tipo_atendimento").multiselect({
        	selectedText: "selecionados # de #",
		});
		
		<?php
		if (in_array($login_fabrica, array(169,170))) {
		?>
			$("#status").multiselect({
				selectedText: "selecionados # de #",
			});
		<?php
		}
		?>

		$("span[rel=lupa_posto]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function abreBoxPecas(box){
    	if($('#boxpecas'+box).is(':visible')){
    		$('#boxpecas'+box).hide();
    	}else{
    		$('#boxpecas'+box).show();
    	}
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

<style>
	table #resultado{
		margin-top: 5px !important;
	}
</style>

<div class="alert alert-warning">
	<?php
		$frase = "";
		if ($login_fabrica == 35) {
			$frase = "<br /><b>O Sistema Considera a Data de Abertura da OS</b>";
		}
		$meses = ($qtde_mes == 1) ? "mês" : "meses";
	?>
		<h4>O período máximo para busca é de <?=$qtde_mes?> <?=$meses?></h4>
		<h4>Devido ao grande volume de registros o arquivo pode demorar de 7 a 10 minutos caso a busca seja no maior intervalo permitido.</h4>
		<?=$frase?>
</div>


<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<? echo montaForm($form,null);?>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php

if(isset($resSubmit)){

	if (pg_num_rows($resSubmit) > 0) {

		if (pg_num_rows($resSubmit) > 500) {
			$count = 500;
		?>
			<div id='registro_max'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<?php
		} else {
			$count = pg_num_rows($resSubmit);
		}

		echo "<div class='tal' style='padding-rigth: 5px !important;'>";

		if(in_array($login_fabrica,array(167,186,203))){
			echo "<table style='margin: 0 auto;' width='700'>";
			echo "<tr><td colspan=2>Status das OS</td></tr>";
			$sql_status   = " SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,8,9) ";
		    $res_status   = pg_query($con, $sql_status);
		    $total_status = pg_num_rows($res_status);

		    $contador_status = pg_num_rows($res_status);

		    for($a= 0; $a < $contador_status; $a++){
		    	$cor 		= pg_fetch_result($res_status, $a, 'cor');
		    	$descricao 	= pg_fetch_result($res_status, $a, 'descricao');

		    	echo "<tr><td width='35'>
		    		<span style='width:20px; background-color:$cor;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		    		</td>
		    		<td>$descricao</td></tr>";
			}
			echo "</table><br>";
		}

		$xreferenciaFabrica =  "";
		if ($login_fabrica == 171) {
			$xreferenciaFabrica =  "<th>Referência Fábrica</th>";
		}

		$posto_codigo = "";
		$colunas_midea = "";
		if (in_array($login_fabrica, array(169,170))) {
			$posto_codigo =  "<th>Codigo Posto</th>";
			$colunas_midea = "
				<th>Data Agendamento</th>
				<th>Data Confirmação</th>
				<th>Data Reagendamento</th>
				<th>Inspetor</th>
			";
		}

		if ($login_fabrica == 35) {
			echo "
			<table id='resultado' class=\"table table-striped table-bordered table-hover table-large table-fixed\" style='margin: 0 auto;' >
				<thead>
					<tr class='titulo_coluna'>";
					foreach ($array_campos_pesquisa as $campo => $value) {
						if (count($campos_pesquisa) && !in_array($campo, $campos_pesquisa)) {
							continue;
						}
						echo "<th>$value</th>";
					}
			echo "</tr>
				</thead>
				<tbody>";
		}else{

			if(in_array($login_fabrica,array(167,186,203))){
				$coluna_fone_posto_brother = "<th>Fone Posto</th>";
			}

			if($login_fabrica == 169){
				$titulo_os_sap = "<th>OS SAP</th>";
			}

			echo "
				<table id='resultado' class=\"table table-striped table-bordered table-hover table-large\" style='margin: 0 auto;' >
					<thead>
					<tr class='titulo_coluna'>
					 	<th>OS</th>
					 	{$titulo_os_sap}
					 	{$posto_codigo}
						<th>Posto</th>
						{$coluna_fone_posto_brother}
					 	{$xreferenciaFabrica}
						<th>Produto</th>";
						if($login_fabrica == 50){
							echo "<th>Validação NS</th>";
						}
						if($login_fabrica == 178){
							echo "<th nowrap>Familia</th>";
							echo "<th nowrap>Grupo de Defeito</th>";
						}
					echo "
						<th>Extrato</th>
						<th>Status</th>";
				if(in_array($login_fabrica, [167, 203])){
					echo "<th>Auditoria</th>";
					echo "<th>Contador</th>";
				}
				echo "<th>Tipo</th>";

				if (in_array($login_fabrica, array(167,169,170,177,186,203))){
					echo "<th>Tipo Atendimento</th>";
				}

				echo "<th>Data Abertura</th>
						<th>Data Finalização</th>
						{$colunas_midea}
						<th>Informação completa</th>
					</tr>
					</thead>
					<tbody>
			";
		}

		$qtde_colunas = (count($campos_pesquisa)) ? count($campos_pesquisa) : count($array_campos_pesquisa);

		for ($i = 0; $i < $count; $i++) {
			$os                     	= pg_fetch_result($resSubmit,$i,'os');
			$sua_os                 	= pg_fetch_result($resSubmit,$i,'sua_os');
			$extrato                	= pg_fetch_result($resSubmit,$i,'extrato');
			$data_abertura          	= pg_fetch_result($resSubmit,$i,'data_abertura');
			$finalizada             	= pg_fetch_result($resSubmit,$i,'finalizada');
			$data_digitacao         	= pg_fetch_result($resSubmit,$i,'data_digitacao');
			$data_conserto          	= pg_fetch_result($resSubmit,$i,'data_conserto');
			$data_fabricacao        	= pg_fetch_result($resSubmit,$i,'data_fabricacao');
			$produto_referencia_fabrica = pg_fetch_result($resSubmit,$i,'produto_referencia_fabrica');
			$referencia             = pg_fetch_result($resSubmit,$i,'referencia');
			$descricao              = pg_fetch_result($resSubmit,$i,'descricao');
			$serie                  = pg_fetch_result($resSubmit,$i,'serie');
			$revenda_cnpj           = pg_fetch_result($resSubmit,$i,'revenda_cnpj');
			$revenda_nome           = pg_fetch_result($resSubmit,$i,'revenda_nome');
			$revenda_cidade         = pg_fetch_result($resSubmit,$i,'revenda_cidade');
			$revenda_estado         = pg_fetch_result($resSubmit,$i,'revenda_estado');
			$consumidor_nome        = pg_fetch_result($resSubmit,$i,'consumidor_nome');
			$consumidor_fone        = pg_fetch_result($resSubmit,$i,'consumidor_fone');
			$consumidor_celular     = pg_fetch_result($resSubmit,$i,'consumidor_celular');
			$consumidor_endereco    = pg_fetch_result($resSubmit,$i,'consumidor_endereco');
			$consumidor_numero      = pg_fetch_result($resSubmit,$i,'consumidor_numero');
			$consumidor_complemento = pg_fetch_result($resSubmit,$i,'consumidor_complemento');
			$consumidor_bairro      = pg_fetch_result($resSubmit,$i,'consumidor_bairro');
			$consumidor_cep         = pg_fetch_result($resSubmit,$i,'consumidor_cep');
			$consumidor_cidade      = pg_fetch_result($resSubmit,$i,'consumidor_cidade');
			$consumidor_estado      = pg_fetch_result($resSubmit,$i,'consumidor_estado');
			$consumidor_cpf         = pg_fetch_result($resSubmit,$i,'consumidor_cpf');
			$consumidor_email       = pg_fetch_result($resSubmit,$i,'consumidor_email');
			$consumidor_revenda     = pg_fetch_result($resSubmit,$i,'consumidor_revenda');
			$obs                    = pg_fetch_result($resSubmit,$i,'obs');
			$solucao                = pg_fetch_result($resSubmit,$i,'solucao');
			$dr_descricao		    = pg_fetch_result($resSubmit, $i, 'dr_descricao');
			if ($login_fabrica == 1 && $solucao == 'Troca de produto') {
				$dc_defeito       = $dr_descricao;
			} else {
				$dc_defeito         = pg_fetch_result($resSubmit,$i,'dc_defeito');
			}
			$dr_defeito             = pg_fetch_result($resSubmit,$i,'dr_defeito');
			$cnpj                   = pg_fetch_result($resSubmit,$i,'cnpj');
			$nome                   = pg_fetch_result($resSubmit,$i,'nome');
			$contato_cidade         = pg_fetch_result($resSubmit,$i,'contato_cidade');
			$contato_estado         = pg_fetch_result($resSubmit,$i,'contato_estado');
			$fone_posto    		= pg_fetch_result($resSubmit,$i,'contato_fone_comercial');
			$data_nf                = pg_fetch_result($resSubmit,$i,'data_nf');
			$nota_fiscal            = pg_fetch_result($resSubmit,$i,'nota_fiscal');
			$hd_chamado             = pg_fetch_result($resSubmit,$i,'hd_chamado');
			$status                 = pg_fetch_result($resSubmit,$i,'status');
			$qtde_km                = pg_fetch_result($resSubmit,$i,'qtde_km');
			$mao_de_obra		= pg_fetch_result($resSubmit, $i, 'mao_de_obra');
			$serie_reoperado	= pg_fetch_result($resSubmit, $i, 'serie_reoperado');
			$valida_serie		= pg_fetch_result($resSubmit, $i, 'valida_serie');
			$os_auditoria           = pg_fetch_result($resSubmit, $i, 'os_auditoria');
			$status_auditoria   = pg_fetch_result($resSubmit, $i, 'status_auditoria');
            		$campos_adicionais      = json_decode(pg_fetch_result($resSubmit, $i, "campos_adicionais"), true);

            $auditoria = (empty($os_auditoria)) ? "" : $status_auditoria;

            $consumidor_profissao = '';

            if ($login_fabrica == 1 and !empty($campos_adicionais)) {
                if (array_key_exists("consumidor_profissao", $campos_adicionais)) {
                    $consumidor_profissao = utf8_decode($campos_adicionais["consumidor_profissao"]);
                }
            }

			if(in_array($login_fabrica, [167, 203])){
				$tipo_posto 		= pg_fetch_result($resSubmit, $i, 'tipo_posto');
				$nome_comercial     = pg_fetch_result($resSubmit, $i, 'nome_comercial');
				$condicao 			= pg_fetch_result($resSubmit, $i, 'condicao');
				$xconclusao_laudo 	= pg_fetch_result($resSubmit, $i, 'conclusao_laudo');
				$jconclusao_laudo   = json_decode($xconclusao_laudo,true);
				$conclusao_laudo 	= utf8_decode($jconclusao_laudo['motivo_reprova']);

			}

			if($obs == 'null') $obs = "";

			if(strlen(trim($valida_serie)) > 0){
				$xserie_reoperado = "Válido";
			}else{
				$xserie_reoperado = "Inválido";
			}

			if (in_array($login_fabrica, array(167,169,170,177,186,203))) {
				$desc_tipo_atendimento  = pg_fetch_result($resSubmit, $i, 'desc_tipo_atendimento');

			}
			if (in_array($login_fabrica, array(169,170))) {
				$data_agendamento = pg_fetch_result($resSubmit, $i, "data_agendamento");
				$data_confirmacao = pg_fetch_result($resSubmit, $i, "data_confirmacao");
				$inspetor_sap = pg_fetch_result($resSubmit, $i, "inspetor_sap");
				$posto_codigo = pg_fetch_result($resSubmit, $i, "posto_codigo");

				$data_reagendamento = "";
				if (!empty($data_agendamento)) {
					$sqlReagendamento = "
						SELECT
							TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_reagendamento
	                    FROM tbl_tecnico_agenda
	                    WHERE tbl_tecnico_agenda.os = {$os}
	                    AND tbl_tecnico_agenda.data_agendamento::DATE != '{$data_agendamento}'::DATE
	                    AND tbl_tecnico_agenda.confirmado IS NOT NULL
	                    ORDER BY tbl_tecnico_agenda.data_input DESC
	                    LIMIT 1
	                ";

	                $resReagendamento = pg_query($con, $sqlReagendamento);
	                $data_reagendamento = pg_fetch_result($resReagendamento, 0, 'data_reagendamento');
	            }

			}

			$obs = str_replace(';', '', $obs);
			/* Busca Peças */

			$sql2 = "
				SELECT
					tbl_os.os_posto AS os_sap,
					tbl_peca.referencia_fabrica AS peca_referencia_fabrica,
					tbl_peca.referencia AS codigo_peca,
					tbl_peca.descricao AS descricao_peca,
					tbl_os_item.qtde AS quantidade_peca,
					to_char(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item,
					tbl_defeito.descricao as defeito,
					tbl_servico_realizado.descricao as servico_realizado,
					tbl_os_item.pedido,
					tbl_os_item.peca_serie,
					tbl_faturamento.nota_fiscal AS nf_peca,
					tbl_faturamento.emissao as data_nf_peca
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				$join_163
				LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				LEFT JOIN tbl_faturamento_item ON  tbl_faturamento_item.".(($login_fabrica == 74) ? "pedido_item" : "os_item")." = tbl_os_item.".(($login_fabrica == 74) ? "pedido_item" : "os_item")."
				LEFT JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				AND tbl_peca.fabrica = $login_fabrica
				WHERE tbl_os.os = $os
				$cond_163
			";

			$resSubmit2 = pg_query($con, $sql2);

			if(pg_num_rows($resSubmit2) > 0){

				$array_pecas = array();
				$array_pecas["colunas"] = array(
					'OS SAP',
					'Referência Fábrica',
					'Código',
					'Descrição',
					'Serial da peça',
					'QTDE',
					'Digitação',
					'Defeito',
					'Serviço Realizado',
					'Pedido',
					'NF',
					'Emissão'
				);

				if (!in_array($login_fabrica,array(167,186,203))){
					unset($array_pecas['colunas'][3]);
				}


				$contador_submit2 = pg_num_rows($resSubmit2);

				for($k = 0; $k < $contador_submit2; $k++) {
					$peca_referencia_fabrica = pg_fetch_result($resSubmit2,$k,'peca_referencia_fabrica');
					$codigo_peca       = pg_fetch_result($resSubmit2,$k,'codigo_peca');
					$descricao_peca    = pg_fetch_result($resSubmit2,$k,'descricao_peca');
					$quantidade_peca   = pg_fetch_result($resSubmit2,$k,'quantidade_peca');
					$digitacao_item    = pg_fetch_result($resSubmit2,$k,'digitacao_item');
					$defeito           = pg_fetch_result($resSubmit2,$k,'defeito');
					$servico_realizado = pg_fetch_result($resSubmit2,$k,'servico_realizado');
					$pedido            = pg_fetch_result($resSubmit2,$k,'pedido');
					$nf_peca           = pg_fetch_result($resSubmit2,$k,'nf_peca');
					$data_nf_peca      = pg_fetch_result($resSubmit2,$k,'data_nf_peca');
					$peca_serie 	   = pg_fetch_result($resSubmit2,$k,'peca_serie');
					$os_sap 	 	   = pg_fetch_result($resSubmit2,$k,'os_sap');


					$array_pecas["valores"][$k] = array(
						$peca_referencia_fabrica,
						$codigo_peca,
						$descricao_peca,
						$peca_serie,
						$quantidade_peca,
						$digitacao_item,
						$defeito,
						$servico_realizado,
						$pedido,
						$nf_peca,
						$data_nf_peca
					);

					if (!in_array($login_fabrica,array(167,186,203))){
						unset($array_pecas['valores'][$k][3]);
					}
				}

			}else{
                $array_pecas = "";
			}

			/* Status */
			$sql3 = "SELECT descricao, cor FROM tbl_status_checkpoint WHERE status_checkpoint = $status";
			$resSubmit3 = pg_query($con, $sql3);

			if(pg_num_rows($resSubmit3) > 0){
				$status = pg_fetch_result($resSubmit3, 0, 'descricao');
				$cor = pg_fetch_result($resSubmit3, 0, 'cor');
			}

			$consumidor_revenda = ($consumidor_revenda == "C") ? "Consumidor" : "Revenda";

			/* if(strlen(trim($dr_descricao)) == 0 OR $dr_descricao == "null"){
				$dr_descricao = $dr_defeito;
			} */

			if(in_array($login_fabrica, array(50))){
				$dr_descricao = "";
			}

			if ($login_fabrica == 171 && strlen($produto_referencia_fabrica) > 0) {
				$xproduto_referencia_fabrica = $produto_referencia_fabrica . " - ";
			}

			if(strlen($dr_defeito) > 0){
				$dr_descricao = $dr_defeito;
			}

			if (in_array($login_fabrica, [167, 203])){
				if (strlen($data_abertura > 0)){
					$mes_abertura = explode('/', $data_abertura);
					$mes_abertura = $mes_abertura[1].'/'.$mes_abertura[2];
				}

				if (in_array($consumidor_estado, array('AM', 'RR', 'AP', 'PA', 'TO', 'RO', 'AC'))){
					$regiao = "Região Norte";
				}else if (in_array($consumidor_estado, array('MA', 'PI', 'CE', 'RN', 'PE', 'PB', 'SE', 'AL', 'BA'))){
					$regiao = "Região Nordeste";
				}else if (in_array($consumidor_estado, array('MT', 'MS', 'GO'))){
					$regiao = "Centro-Oeste";
				}else if (in_array($consumidor_estado, array('SP', 'RJ', 'ES', 'MG'))){
					$regiao = "Região Sudeste";
				}else if (in_array($consumidor_estado, array('PR', 'RS', 'SC'))) {
					$regiao = "Região Sul";
				}else{
					$regiao = "Região não encontrada";
				}
			}

			// Remover Caracteres especiais da Observação
			$obs = str_replace(';', '', $obs);
			$obs = str_replace('\n', ' ', $obs);
			$obs = str_replace('\r', ' ', $obs);
			$obs = str_replace('"', '\'', $obs);
			$obs = str_replace('\r\n', ' ', $obs);
			$obs = preg_replace('/\s\s+/', ' ', $obs);
			$obs = preg_replace('/\n/', ' ', $obs);
			
			$informacaoCompleta = array(
				'titulo' => 'Informação completa da OS '.$sua_os,
				'campos' => array(
					array('coluna' => 'Posto', 'valor' => $nome, "span" => 2),
					array('coluna' => 'Posto CNPJ', 'valor' => $cnpj),
					array('coluna' => 'Classificação', 'valor' => $tipo_posto),
					array('coluna' => 'Contato Cidade', 'valor' => $contato_cidade, "span" => 2),
					array('coluna' => 'Contato UF', 'valor' => $contato_estado),
					array('coluna' => 'Região', 'valor' => $regiao),
					array('coluna' => 'Protocolo', 'valor' => $hd_chamado, 'link' => "callcenter_interativo_new.php?callcenter=$hd_chamado"),
					array('coluna' => 'Extrato', 'valor' => $extrato),
					array('coluna' => 'Status', 'valor' => $status),
					array('coluna' => 'OS', 'valor' => $sua_os, 'link' => "os_press.php?os=$os"),											
					array('coluna' => 'Tipo OS', 'valor' => $consumidor_revenda),
					array('coluna' => 'Data Abertura', 'valor' => $data_abertura),
					array('coluna' => 'Mês Abertura', 'valor' => $mes_abertura),
					array('coluna' => 'Data Digitação', 'valor' => $data_digitacao, "span" => 2),
					array('coluna' => 'Data Finalização', 'valor' => $finalizada),
					array('coluna' => 'Data Conserto', 'valor' => $data_conserto, "span" => 2),
					array('coluna' => 'KM', 'valor' => $qtde_km),
					array('coluna' => 'Mão de obra', 'valor' => $mao_de_obra),
					array('coluna' => 'Observação', 'valor' => $obs, "span" => 2),
					array('coluna' => 'Defeito Reclamado', 'valor' => $dr_descricao),
					array('coluna' => 'Defeito Constatado', 'valor' => $dc_defeito),
					array('coluna' => 'Solução', 'valor' => $solucao),
					array('coluna' => 'Produto', 'valor' => $xproduto_referencia_fabrica . $referencia ." - ". $descricao, "span" => 2),
					array('coluna' => 'Número de Série', 'valor' => $serie),
					array('coluna' => 'Engenharia', 'valor' => $nome_comercial),
					array('coluna' => 'Data Fabricação', 'valor' => $data_fabricacao),
					array('coluna' => 'Consumidor', 'valor' => $consumidor_nome),
					array('coluna' => 'Consumidor Cidade', 'valor' => $consumidor_cidade, "span" => 2),
					array('coluna' => 'Consumidor UF', 'valor' => $consumidor_estado),
					array('coluna' => 'Consumidor Telefone', 'valor' => $consumidor_fone),
					array('coluna' => 'Consumidor Celular', 'valor' => $consumidor_celular, "span" => 2),
					array('coluna' => 'Consumidor Email', 'valor' => $consumidor_email, "span" => 2),
					array('coluna' => 'Consumidor CEP', 'valor' => $consumidor_cep),
					array('coluna' => 'Consumidor Bairro', 'valor' => $consumidor_bairro),
					array('coluna' => 'Consumidor Rua', 'valor' => $consumidor_endereco, "span" => 2),
					array('coluna' => 'Consumidor Número', 'valor' => $consumidor_numero),
					array('coluna' => 'Consumidor Complemento', 'valor' => $consumidor_complemento),
					array('coluna' => 'Consumidor Profissão', 'valor' => $consumidor_profissao, "span" => 2),
					array('coluna' => 'Revenda', 'valor' => $revenda_nome, "span" => 2),
					array('coluna' => 'Revenda CNPJ', 'valor' => $revenda_cnpj),
					array('coluna' => 'Nota Fiscal', 'valor' => $nota_fiscal),
					array('coluna' => 'Data Nota Fiscal', 'valor' => $data_nf),
					array('coluna' => 'Motivo da reprovação', 'valor' => $conclusao_laudo),
					array('coluna' => 'Peças', 'valor' => $array_pecas)
				)
			);

			if($login_fabrica <> 74){
				unset($informacaoCompleta['campos'][14]);
			}

			if (!in_array($login_fabrica, [167, 203])){
				unset($informacaoCompleta['campos'][2]);
				unset($informacaoCompleta['campos'][5]);
				unset($informacaoCompleta['campos'][12]);
				unset($informacaoCompleta['campos'][24]);
				unset($informacaoCompleta['campos'][42]);
			}

            if ($login_fabrica <> 1) {
				unset($informacaoCompleta['campos'][33]);
            }



			$informacaoCompleta = array_map_recursive(function ($value) { return utf8_encode(preg_replace("(\"|')", "", $value)); }, $informacaoCompleta);

			if ($login_fabrica == 35) {
				echo "<tr style='text-align:center'>";
				for ($z = 0; $z < $qtde_colunas; $z++) {
					echo "<td>".pg_fetch_result($resSubmit,$i, $z)."</td>";
				}
				echo "</tr>";
			}else{
				$referenciaFabricaProduto =  "";
				if ($login_fabrica == 171) {
					$referenciaFabricaProduto =  "<td class='tac'>".$produto_referencia_fabrica."</td>";
				}
				echo "
					<tr style='text-align:center'>
					<td nowrap >
				";
				if(in_array($login_fabrica,array(167,186,203))){
					echo "<span style='width:20px; background-color:$cor;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> ";
				}
				
				if ($login_fabrica == 1) {
					$aux_sql = "
						SELECT tbl_posto_fabrica.codigo_posto
						FROM tbl_posto_fabrica
						JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_os.os = $os LIMIT 1
					";
					
					$aux_res  = pg_query($con, $aux_sql);
					$auxiliar = pg_fetch_result($aux_res, 0, 0);

					unset($aux_sql, $aux_res);
				} else {
					$auxiliar = "";
				}

				echo "<a href='os_press.php?os=$os' target='_blank'>$auxiliar$sua_os</a></td>";

				if($login_fabrica == 169){					
					echo "<td>{$os_sap}</td>";						
				}

				if (in_array($login_fabrica, array(169,170))) {
					echo "<td>{$posto_codigo}</td>";
				}

				if (in_array($login_fabrica, array(167,186,203))) {
					$campos_fone_posto_brother = "<td>{$fone_posto}</td>";
				}

				echo "
					<td nowrap >$nome</td>
					{$campos_fone_posto_brother}
					{$referenciaFabricaProduto}
					<td nowrap>$referencia - $descricao</td>
				";
				if($login_fabrica == 50){
					echo "<td class='tac'>$xserie_reoperado</td>";
				}
				if($login_fabrica == 178){

 				  $sqlGrupo = "
				        SELECT tbl_defeito_constatado_grupo.descricao AS nome_grupo, tbl_familia.descricao AS nome_familia, tbl_os.produto, tbl_os.defeito_constatado
				          FROM tbl_os
				          JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
				          JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
				          LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
				          LEFT JOIN tbl_diagnostico ON tbl_diagnostico.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_diagnostico.fabrica = {$login_fabrica}
				          LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo AND tbl_defeito_constatado_grupo.fabrica = {$login_fabrica}
				         WHERE tbl_os.fabrica = {$login_fabrica} 
				           AND tbl_os.os = {$os}";

				    $resGrupo = pg_query($con, $sqlGrupo);

					$nome_grupo = pg_fetch_result($resGrupo, 0, 'nome_grupo');
					$nome_familia = pg_fetch_result($resGrupo, 0, 'nome_familia');

					unset($resGrupo, $sqlGrupo);

					echo "<td class='tac'>$nome_familia</td>";
					echo "<td class='tac'>$nome_grupo</td>";
				}
				echo "<td>$extrato</td>
					<td nowrap ><strong>$status</strong></td>";
				if(in_array($login_fabrica, [167, 203])){
					echo "<td>$auditoria</td>";
					echo "<td>$condicao</td>";
				}
				echo "<td>$consumidor_revenda</td>";

				if (in_array($login_fabrica, array(167,169,170,177,186,203))){
					echo "<td>$desc_tipo_atendimento</td>";
				}

				echo "<td>$data_abertura</td>
					<td>$finalizada</td>";
				if (in_array($login_fabrica, array(169,170))) {
					echo "
						<td>{$data_agendamento}</td>
						<td>{$data_confirmacao}</td>
						<td>{$data_reagendamento}</td>
						<td>{$inspetor_sap}</td>
					";
				}				

				echo "<td class='informacao_completa'><button rel='$i' type='button' class='btn btn-info btn-small informacaoCompleta' >Visualizar</button></td>
				</tr>
				<script>$.informacaoCompleta($i, '".preg_replace("/\r|\n|\r\n/", "<br />", json_encode($informacaoCompleta))."');</script>";
			}

		}

		echo "
				</tbody>

			</table>
		";

		echo "<br />";

		if ($count > 1) {
		?>
			<script>
				$.dataTableLoad({ table: "#resultado" });
			</script>
		<?php
		}

		$jsonPOST = excelPostToJson($_POST);

		?>

		<br />

		<?if($login_fabrica == 169) : ?>

			<div style="display:flex;width: 185px; margin: 0 auto;">
				<div class="form-check" style="display:flex; padding:20px">
				  <input class="form-check-input" type="radio" name="tipo_relatorio" id="relatorio_csv" value="csv" checked>
				  <label class="form-check-label" for="relatorio_csv">&nbspCSV</label>
				</div>
				<div class="form-check" style="display:flex; padding:20px">
				  <input class="form-check-input" type="radio" name="tipo_relatorio" id="relatorio_xls" value="xls">
				  <label class="form-check-label" for="relatorio_xls">&nbspXLS</label>
				</div>
			</div>

		<? endif; ?>

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>

		<?php

		echo "</div>";

	}else{
		echo '
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>';
	}

}

include 'rodape.php';?>

<script type="text/javascript">

	$('#relatorio_xls').click(function(){
		<?php $_POST['tipo_relatorio'] = 'xls'?>
		$("#jsonPOST").val('<?php echo $jsonPOST = excelPostToJson($_POST);?>');
	});

	$('#relatorio_csv').click(function(){
		<?php $_POST['tipo_relatorio'] = 'csv'?>
		$("#jsonPOST").val('<?php echo $jsonPOST = excelPostToJson($_POST);?>');
	});

</script>
