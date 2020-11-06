<?

$admin_privilegios="financeiro,gerencia,call_center";
include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../funcoes.php';


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
$qtde_mes = (in_array($login_fabrica, array(35,134,167,169,170,177,203))) ? 6 : $qtde_mes;
$qtde_mes = (in_array($login_fabrica, [148])) ? 12 : $qtde_mes;

if ($_POST["btn_acao"] == "submit") {
	
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');
    $estado             = filter_input(INPUT_POST,'estado');
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

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	} else {

		if (in_array($login_fabrica, [167, 203])) {
			$data_corte = '01/04/2019';

			if (!verifica_data_corte($data_corte, $data_inicial)) {
				$msg_erro["msg"][]    = "Data informada inferior a data limite para pesquisa";
				$msg_erro["campos"][] = "data_inicial";
				$msg_erro["campos"][] = "data_final";
			}
		}

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
				$texto_mes = (in_array($login_fabrica,array(35,50))) ? "$qtde_mes meses" : "1 mês";
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo ".$texto_mes;
			}
		}else{
			$sqlX = "SELECT '$aux_data_inicial'::date + interval '$qtde_mes months' >= '$aux_data_final'";
			$resSubmitX = pg_query($con,$sqlX);
			$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
			if($periodo_6meses == 'f'){
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo $qtde_mes meses";
			}
		}
	}

	if (!count($msg_erro["msg"])) {

		if(in_array($login_fabrica,array(50,157,164))) {
			$rows = 46;
		} else {
            $rows = 39;
		}

		if ($login_fabrica == 50) {
			$distinct = " DISTINCT ";
		}

		if (in_array($login_fabrica, [167, 203])) {
			$distinct = " DISTINCT ON (os.os)";
		}

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}else{
			$cond_posto = " AND tbl_os.posto <> 6359 ";
		}

		if ($estado) {
			$cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
		}

		$os_d_r_c = "LEFT JOIN tbl_solucao ON tbl_solucao.fabrica = $login_fabrica
						AND tbl_solucao.solucao = os.solucao_os";

		if ($login_fabrica == 148) {
			$os_d_r_c = "LEFT JOIN tbl_os_defeito_reclamado_constatado ON os.os = tbl_os_defeito_reclamado_constatado.os
						 LEFT JOIN tbl_solucao ON tbl_os_defeito_reclamado_constatado.solucao = tbl_solucao.solucao";
			if (!empty($tipo_os)) {
				$cond_tipo_os = $tipo_os;
			}
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

		if (in_array($login_fabrica, array(148,167,169,170,203)) && !empty($tipo_atendimento)) {
			$cond_tipo_atendimento = " AND tbl_tipo_atendimento.tipo_atendimento IN (".implode(',', $tipo_atendimento).")";
		}

		if ($login_fabrica == 148 && !empty($n_serie)) {
			$cond_serie = "AND tbl_os.serie = '$n_serie'";
		}

		if ($login_fabrica == 148) {
			$distinct = " DISTINCT ON (os.os)";
		}

		if (in_array($login_fabrica, array(169,170))) {
			if (!empty($status)) {
				$cond_status = " AND tbl_os.status_checkpoint IN (".implode(',', $status).")";
			}

			$distinct = " DISTINCT ON (os.os)";

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
			#echo nl2br($sql); exit;

		}else{
			$datas = relatorio_data("$aux_data_inicial","$aux_data_final");
			$cont = 0;
			if($novaTelaOs) {
				$tbl_os = "tbl_os_defeito_reclamado_constatado";
				$join_dc = " left join tbl_os_defeito_reclamado_constatado on tbl_os.os = tbl_os_defeito_reclamado_constatado.os";
			}else{
				$tbl_os = "tbl_os";
			}
			foreach($datas as $data_pesquisa){
				$data_inicial = $data_pesquisa[0];
				$data_final = $data_pesquisa[1];
				$data_final = str_replace(' 23:59:59', '', $data_final);
				if($cont == 0){

					if($login_fabrica == 148){
						$campos_yanmar = ", tbl_os_extra.qtde_km as qtde_km_extra, tbl_os_extra.valor_por_km
							 ";
					}

					$sql = "SELECT
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
							tbl_os.defeito_reclamado_descricao AS dr_descricao,
							tbl_os.status_checkpoint AS status,
							tbl_os_campo_extra.campos_adicionais,
							tbl_os.produto,
							tbl_os.posto,
							tbl_os.hd_chamado,
							tbl_os.tipo_atendimento,
							case when tbl_os.defeito_constatado notnull then tbl_os.defeito_constatado else tbl_os_produto.defeito_constatado end as defeito_constatado,
							tbl_os.defeito_reclamado,
							tbl_os.solucao_os,
							tbl_os.revenda,
							tbl_os_extra.data_fabricacao,							
							tbl_auditoria_os.os AS os_auditoria,
							tbl_auditoria_os.liberada,
							tbl_auditoria_os.reprovada,
							tbl_auditoria_os.cancelada
							$campos_yanmar
						into temp relatorio_os_$login_admin
						FROM tbl_os
						INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
						$join_dc
						LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
						AND tbl_os.excluida IS FALSE
						{$cond_posto}
						{$cond_tipo_os}
						{$cond_status}
						{$cond_serie}
						{$cond_linha}
						{$cond_familia}
						;
							";
				}else{
					$sql = "insert into relatorio_os_$login_admin
							SELECT 	
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
							tbl_os.defeito_reclamado_descricao AS dr_descricao,
							tbl_os.status_checkpoint AS status,
							tbl_os_campo_extra.campos_adicionais,
							tbl_os.produto,
							tbl_os.posto,
							tbl_os.hd_chamado,
							tbl_os.tipo_atendimento,
							case when tbl_os.defeito_constatado notnull then tbl_os.defeito_constatado else tbl_os_produto.defeito_constatado end as defeito_constatado,
							tbl_os.defeito_reclamado,
							tbl_os.solucao_os,
							tbl_os.revenda,
							tbl_os_extra.data_fabricacao,
							tbl_auditoria_os.os AS os_auditoria,
							tbl_auditoria_os.liberada,
							tbl_auditoria_os.reprovada,
							tbl_auditoria_os.cancelada
						FROM tbl_os
						INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
						$join_dc
						LEFT JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.data_abertura BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
						AND tbl_os.excluida IS FALSE
						{$cond_posto}
						{$cond_tipo_os}
						{$cond_status}
						{$cond_serie}
						{$cond_linha}
						{$cond_familia} ; ";
				} 

				$res = pg_query($con,$sql);
				$cont++;

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

				SELECT $distinct
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
					tbl_defeito_constatado.descricao AS dc_defeito,
					tbl_defeito_reclamado.descricao AS dr_defeito,
					tbl_cidade.nome AS revenda_cidade,
					tbl_cidade.estado AS revenda_estado,
					tbl_tipo_atendimento.descricao AS desc_tipo_atendimento,
					tbl_tipo_posto.descricao AS tipo_posto,
					tbl_solucao.descricao AS solucao,
					tbl_numero_serie.serie AS valida_serie,
					CASE WHEN os.data_fabricacao IS NOT NULL THEN TO_CHAR(os.data_fabricacao, 'DD/MM/YYYY')
					ELSE TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY') END AS data_fabricacao
					$campos_midea
					FROM  relatorio_os_$login_admin  os
					LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = os.os 
						AND os.hd_chamado IS NULL
					INNER JOIN tbl_produto ON tbl_produto.fabrica_i = $login_fabrica
						AND os.produto = tbl_produto.produto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica
						AND tbl_posto_fabrica.posto = os.posto
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.fabrica = $login_fabrica
						AND tbl_tipo_atendimento.tipo_atendimento = os.tipo_atendimento
					INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
						AND tbl_tipo_posto.fabrica = {$login_fabrica}
					LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.fabrica = $login_fabrica
						AND tbl_defeito_constatado.defeito_constatado = os.defeito_constatado
					LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.fabrica = $login_fabrica
						AND tbl_defeito_reclamado.defeito_reclamado = os.defeito_reclamado
					$os_d_r_c
					LEFT JOIN tbl_revenda ON tbl_revenda.revenda = os.revenda
					LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda.cidade
					LEFT JOIN tbl_numero_serie ON tbl_numero_serie.fabrica = $login_fabrica
						AND tbl_numero_serie.produto = os.produto
						AND tbl_numero_serie.serie = os.serie
					LEFT JOIN tbl_admin inspetor_posto ON inspetor_posto.fabrica = $login_fabrica
						AND inspetor_posto.admin = tbl_posto_fabrica.admin_sap
					LEFT JOIN tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = os.os AND tbl_laudo_tecnico_os.fabrica = {$login_fabrica}
					WHERE 1 = 1
					{$cond_163}
					{$cond_estado}
					{$cond_tipo_atendimento}
					{$limit}
			";
		}
		$resSubmit = pg_query($con, $sql);
	}

	if(isset($_POST['gerar_excel'])){

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
			$th_pecas2 = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CÓDIGO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DESCRIÇÃO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>QTDE</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DATA DE DIGITAÇÃO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DEFEITO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>SERVIÇO REALIZ.</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>PEDIDO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>EMISSÃO</th>
			";
			$th_mao_obra = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>MÃO DE OBRA</th>";
		} else if(in_array($login_fabrica,array(157,164))) {
			$th_pecas2 = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CÓDIGO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DESCRIÇÃO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>QTDE</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DATA DE DIGITAÇÃO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DEFEITO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>SERVIÇO REALIZ.</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>PEDIDO</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>EMISSÃO</th>
			";
		} else if(!in_array($login_fabrica, array(167,203))) {
			$th_pecas2 = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peças</th>";
		}elseif(in_array($login_fabrica, [167, 203])) {

					$th_pecas2 = "
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CÓDIGO</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DESCRIÇÃO</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>QTDE</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DATA DE DIGITAÇÃO</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DEFEITO</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>SERVIÇO REALIZ.</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>SÉRIE DA PEÇA</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>PEDIDO</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>NF</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>EMISSÃO</th> ";

		}

		if ($login_fabrica == 50) {

			$th_extrato = "
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>VALOR TOTAL</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>ACRÉSCIMO</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DESCONTO</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>VALOR LÍQUIDO</th>
			";

			$th_revenda = "
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>REVENDA (CLIENTE COLORMAQ)</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>FONE</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DATA NF</th>

                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>REVENDA (CONSUMIDOR)</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>FONE</th>
                <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>DATA NF</th>
			";


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

			$th_revenda = "
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Revenda</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número NF</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Compra</th>
			";

		}

		$data = date("d-m-Y-H:i");

		if(in_array($login_fabrica, [1, 50])){ //HD-3263360
			$filename = "relatorio-os-{$data}.csv";
		}else{
			$filename = "relatorio-os-{$data}.xls";
		}

		$file = fopen("/tmp/{$filename}", "w");


		if (in_array($login_fabrica, [167, 203])) {
			$coluna_fone_posto_brother = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Fone Posto</th>";
			$coluna_auditoria_brother = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Auditoria</th>";
			$coluna_condicao_brother = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Contador</th>";
			$dias_em_aberto = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Dias em aberto</th>";
			$coluna_classificacao = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Classificação</th>";
			$coluna_regiao = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Região</th>";
			$coluna_mes_abertura = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Mês Abertura</th>";
			$coluna_engenharia = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Engenharia</th>";
			$coluna_motivo_reprova = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Motivo da reprovação</th>";
		}

		$data_fb         = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Fabricação</th>";
		$campo_horimetro = ""; 

		if ($login_fabrica == 148) {
			$data_fb = "";
			$campo_horimetro = 	"<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Horímetro</th>";		
			$colunaPin = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>PIN</th>";
		}

		$xxproduto_referencia_fabrica = "";
		if ($login_fabrica == 171) {
			$xxproduto_referencia_fabrica = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Referência Fábrica</th>";
		}

		if (in_array($login_fabrica, array(167,169,170,203))) {
			$coluna_tipo_atendimento = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Tipo Atendimento</th>";
		}
		if (in_array($login_fabrica, array(169,170))) {
			$colunas_midea = "
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Data Agendamento</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Data Confirmação</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Data Reagendamento</th>
				<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Inspetor</th>
			";
			$posto_codigo = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Código Posto</th>";
		}

		if ($login_fabrica == 148) {
			$parecer = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Parecer Final</th>";
			$colunaYanmer = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo Atendimento</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor Deslocamento</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor das peças</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor da mão de obra</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Valor total</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Auditoria Aprovada</th>";
			$colunaYanmer .= "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Auditoria - Justificativa</th>";
		}

		if($login_fabrica == 50){ //HD-3263360
			$titulo = implode(';', $titulo)."\r\n";
			fwrite($file, $titulo);
		}else{

            $coluna_consumidor_profissao = '';
            if ($login_fabrica == 1) {

	                fwrite($file, "CNPJ Posto;Razão Social;Cidade;UF;Protocolo;Extrato;Status;Número OS;Tipo;Data Abertura;Data Digitação;Data Finalização;Data Conserto;KM;Obs.;Defeito Reclamado;Defeito Constatado;Solução;Código Produto;Descrição Produto;Número de Série;Data Fabricação;Consumidor;Cidade;UF;Tel.;Tel. Cel.;Email;CEP;Bairro;Rua;Número; Compl.;Profissão; Revenda;CNPJ;Número NF;Data Compra; \r\n");
	                $total_colunas_csv = 37;
            } else {
            	fwrite($file, "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='$rows' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ Posto</th>
							{$coluna_classificacao}
							{$posto_codigo}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Razão Social</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
							{$coluna_fone_posto_brother}
							{$coluna_regiao}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Protocolo</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Extrato</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important'>Status</th>
							{$coluna_auditoria_brother}
							{$coluna_condicao_brother}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tipo</th>
							{$coluna_tipo_atendimento}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Abertura</th>
							{$coluna_mes_abertura}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Digitação</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Finalização</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Conserto</th>
							{$campo_horimetro}
							{$colunas_midea}
							{$dias_em_aberto}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>KM</th>
							{$th_mao_obra}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Obs.</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Reclamado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Defeito Constatado</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Solução</th>
							{$xxproduto_referencia_fabrica}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Descrição Produto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número de Série</th>
							{$colunaPin}
							{$coluna_engenharia}
							{$data_fb}
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cidade</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>UF</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tel.</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Tel. Cel.</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Email</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CEP</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Bairro</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Rua</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Número</th>
                            <th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Compl.</th>
                            $coluna_consumidor_profissao
							{$th_revenda}
							{$coluna_motivo_reprova}
							{$th_pecas2}
							{$parecer}
							{$colunaYanmer}
						</tr>
					</thead>
					<tbody>
				");
            }			
		}

		for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
			$os = pg_fetch_result($resSubmit,$i,'os');
			$sua_os = pg_fetch_result($resSubmit,$i,'sua_os');
			
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
			$descricao = pg_fetch_result($resSubmit,$i,'descricao');
			$serie = pg_fetch_result($resSubmit,$i,'serie');
			$revenda_cnpj = pg_fetch_result($resSubmit,$i,'revenda_cnpj');
			$revenda_nome = pg_fetch_result($resSubmit,$i,'revenda_nome');
			$revenda_cidade = pg_fetch_result($resSubmit,$i,'revenda_cidade');
			$revenda_estado = pg_fetch_result($resSubmit,$i,'revenda_estado');
			$revenda_fone = pg_fetch_result($resSubmit,$i,'revenda_fone');
			$consumidor_nome = pg_fetch_result($resSubmit,$i,'consumidor_nome');
			$consumidor_fone = pg_fetch_result($resSubmit,$i,'consumidor_fone');
			$consumidor_celular = pg_fetch_result($resSubmit,$i,'consumidor_celular');
			$consumidor_endereco = pg_fetch_result($resSubmit,$i,'consumidor_endereco');
			$consumidor_numero = pg_fetch_result($resSubmit,$i,'consumidor_numero');
			$consumidor_complemento = pg_fetch_result($resSubmit,$i,'consumidor_complemento');
			$consumidor_bairro = pg_fetch_result($resSubmit,$i,'consumidor_bairro');
			$consumidor_cep = pg_fetch_result($resSubmit,$i,'consumidor_cep');
			$consumidor_cidade = pg_fetch_result($resSubmit,$i,'consumidor_cidade');
			$consumidor_estado = pg_fetch_result($resSubmit,$i,'consumidor_estado');
			$consumidor_cpf = pg_fetch_result($resSubmit,$i,'consumidor_cpf');
			$consumidor_email = pg_fetch_result($resSubmit,$i,'consumidor_email');
			$consumidor_revenda = pg_fetch_result($resSubmit,$i,'consumidor_revenda');
			$obs = pg_fetch_result($resSubmit,$i,'obs');
			$solucao	 = pg_fetch_result($resSubmit,$i,'solucao');
			$dr_descricao = pg_fetch_result($resSubmit, $i, 'dr_descricao');
			if ($login_fabrica == 1 && $solucao == 'Troca de produto') {
				$dc_defeito = $dr_descricao;
			} else {
				$dc_defeito = pg_fetch_result($resSubmit,$i,'dc_defeito');
			}
			$dr_defeito = pg_fetch_result($resSubmit,$i,'dr_defeito');
			if (empty($dr_defeito) && !empty($dr_descricao) && $login_fabrica == 148) {
				$dr_defeito = $dr_descricao;
			}
			$cnpj = pg_fetch_result($resSubmit,$i,'cnpj');
			$nome = pg_fetch_result($resSubmit,$i,'nome');
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
			$dias_aberto_consumidor = pg_fetch_result($resSubmit,$i,'dias_aberto_consumidor');
			$dias_aberto_revenda = pg_fetch_result($resSubmit,$i,'dias_aberto_revenda');
            		$campos_adicionais = json_decode(pg_fetch_result($resSubmit, $i, "campos_adicionais"), true);
			$os_auditoria           = pg_fetch_result($resSubmit, $i, 'os_auditoria');
            		$os_liberada		= pg_fetch_result($resSubmit, $i, 'liberada');
			$os_cancelada		= pg_fetch_result($resSubmit, $i, 'cancelada');
			$os_reprovada		= pg_fetch_result($resSubmit, $i, 'reprovada');

            		$auditoria = (strlen($os_liberada) > 0) ? "APROVADA" : "PENDENTE";
            		$auditoria = (strlen($os_cancelada) > 0) ? "CANCELADA" : $auditoria;
            		$auditoria = (strlen($os_reprovada) > 0) ? "REPROVADA" : $auditoria;
            		$auditoria = (empty($os_auditoria)) ? "" : $auditoria;


            $consumidor_profissao = '';

            if ($login_fabrica == 1 and !empty($campos_adicionais)) {
                if (array_key_exists("consumidor_profissao", $campos_adicionais)) {
                    $consumidor_profissao = utf8_decode($campos_adicionais["consumidor_profissao"]);
                }
            }

	    if (in_array($login_fabrica, array(148,167,169,170,203))) {
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
						<td>$valor_total</td>
						<td>$acrescimo</td>
						<td>$desconto</td>
						<td>$valor_liquido</td>
					";

				}else{
					$td_extrato = "<td></td> <td></td> <td></td> <td></td>";
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

                $conteudo_revenda = "<td>$revenda_nome_1 </td><td> $revenda_cnpj_1 </td><td> $revenda_fone_1 </td><td> $data_venda</td>";
               	$conteudo_revenda .= "<td>$revenda_nome </td><td> $revenda_cnpj_2 </td><td> $nota_fiscal </td><td> $data_nf</td>";
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
						tbl_defeito.descricao as defeito,
						tbl_servico_realizado.descricao as servico_realizado,
						tbl_os_item.pedido,
						tbl_faturamento.nota_fiscal AS nf_peca,
						tbl_faturamento.emissao as data_nf_peca
					FROM tbl_os
					JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
					LEFT JOIN tbl_defeito ON tbl_os_item.defeito = tbl_defeito.defeito
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
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
				if(in_array($login_fabrica, [167, 203])){
					$conteudo_pecas = array();
				}else{
					$conteudo_pecas = "";
				}
				$xxreferencia_fabrica_peca = "";
				if ($login_fabrica == 171) {
					$xxreferencia_fabrica_peca = "<th style='background-color: #596d9b !important;'>REFERÊNCIA FÁBRICA</th>";
				}

				if (in_array($login_fabrica, [167, 203])){
					$coluna_peca_serie = "<th style='background-color: #596d9b !important;'>SÉRIE DA PEÇA</th>";
				}

				if (!in_array($login_fabrica,array(1,50,74,157,164,167,203))) { //HD-3141903
					$conteudo_pecas .= "
						<table>
							<tr>
								{$xxreferencia_fabrica_peca}
								<th style='background-color: #596d9b !important;'>CÓDIGO</th>
								<th style='background-color: #596d9b !important;'>DESCRIÇÃO</th>
								<th style='background-color: #596d9b !important;'>QTDE</th>
								<th style='background-color: #596d9b !important;'>DATA DE DIGITAÇÃO</th>
								<th style='background-color: #596d9b !important;'>DEFEITO</th>
								<th style='background-color: #596d9b !important;'>SERVIÇO REALIZ.</th>
								{$coluna_peca_serie}
								<th style='background-color: #596d9b !important;'>PEDIDO</th>
								<th style='background-color: #596d9b !important;'>NF</th>
								<th style='background-color: #596d9b !important;'>EMISSÃO</th>
							</tr>
					";
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
				for($k = 0; $k < pg_num_rows($resSubmit2); $k++) {

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

					$xreferencia_fabrica_peca = "";
					if ($login_fabrica == 171) {
						$xreferencia_fabrica_peca = "<td>$referencia_fabrica_peca</td>";
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
                        $conteudo_pecas .= "
                            <tr class='tac' style='text-align:center'>
                                <td>$cnpj</td>
                                <td>$nome</td>
                                <td>$contato_cidade</td>
                                <td align='center'>$contato_estado</td>
                                <td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>
                                <td>$extrato</td>
                                <td><strong>$status</strong></td>
                                $campos_condicao_brother
                                <td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>
                                <td>$consumidor_revenda</td>
                                <td>$data_abertura</td>
                                <td>$data_digitacao</td>
                                <td>$finalizada</td>
                                <td>$data_conserto</td>
                                $td_dias_em_aberto
                                <td align='center'>$qtde_km</td>
                                $td_mao_obra
                                <td>$obs</td>
                                <td>$dr_defeito</td>
                                <td>$dc_defeito</td>
                                <td>$solucao</td>
                                <td>$referencia</td>
                                <td nowrap>$descricao</td>
                                <td>$serie</td>
                                <td>$data_fabricacao</td>
                                <td>$consumidor_nome</td>
                                <td>$consumidor_cidade</td>
                                <td align='center'>$consumidor_estado</td>
                                <td>$consumidor_fone</td>
                                <td>$consumidor_celular</td>
                                <td>$consumidor_email</td>
                                <td>$consumidor_cep</td>
                                <td>$consumidor_bairro</td>
                                <td>$consumidor_endereco</td>
                                <td>$consumidor_numero</td>
                                <td>$consumidor_complemento</td>
                                <td>$revenda_nome</td>
                                <td>$revenda_cnpj</td>
                                <td>$nota_fiscal</td>
                                <td>$data_nf</td>
                                <td>$codigo_peca</td>
                                <td>$descricao_peca</td>
                                <td>$quantidade_peca</td>
                                <td>$digitacao_item</td>
                                <td>$defeito</td>
                                <td>$servico_realizado</td>
                                <td>$pedido</td>
                                <td>$nf_peca</td>
                                <td>$data_nf_peca</td>
                            </tr>
                        ";
					} else {
						if(in_array($login_fabrica, [167, 203])) {

							$conteudo_pecas[]= "
								<td>$codigo_peca</td>
								<td>$descricao_peca</td>
								<td>$quantidade_peca</td>
								<td>$digitacao_item</td>
								<td>$defeito</td>
								<td>$servico_realizado</td>
								<td>$serie_peca</td>
								<td>$pedido</td>
								<td>$nf_peca</td>
								<td>$data_nf_peca</td>";
						}else{
							if ($login_fabrica == 1) {

								$separador = "";
								for ($col=0;$col <= $total_colunas_csv;$col++) {
									$separador .= ";";
								}

								$conteudo_pecas_csv .= $separador."$codigo_peca;$descricao_peca;$quantidade_peca;$digitacao_item;$defeito;$servico_realizado;$pedido;$nf_peca;$data_nf_peca \r\n";
								
							} else {
								$conteudo_pecas .= "
									<tr>
										{$xreferencia_fabrica_peca}
										<td>$codigo_peca</td>
										<td>$descricao_peca</td>
										{$xpeca_serie}
										<td>$quantidade_peca</td>
										<td>$digitacao_item</td>
										<td>$defeito</td>
										<td>$servico_realizado</td>
										<td>$pedido</td>
										<td>$nf_peca</td>
										<td>$data_nf_peca</td>
									</tr>
									";
							}
						}
					}
				}

				if (!in_array($login_fabrica,array(50,157,167,203))) {
					if (!in_array($login_fabrica, array(74,164))) { //HD-3141903
						$conteudo_pecas .= "
							</table>
						";
					} else {
						$conteudo_pecas .= "
									<td>".implode("<br/>", $codigo_peca_arr)."</td>
									<td>".implode("<br/>", $descricao_peca_arr)."</td>
									<td>".implode("<br/>", $quantidade_peca_arr)."</td>
									<td>".implode("<br/>", $digitacao_item_arr)."</td>
									<td>".implode("<br/>", $defeito_arr)."</td>
									<td>".implode("<br/>", $servico_realizado_arr)."</td>
									<td>".implode("<br/>", $pedido_arr)."</td>
									<td>".implode("<br/>", $nf_peca_arr)."</td>
									<td>".implode("<br/>", $data_nf_peca_arr)."</td>
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
                    $conteudo_pecas .= "
                        <tr class='tac' style='text-align:center'>
                            <td>$cnpj</td>
                            <td>$nome</td>
                            <td>$contato_cidade</td>
                            <td align='center'>$contato_estado</td>
                            <td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>
                            <td>$extrato</td>
                            <td><strong>$status</strong></td>
                            $campos_condicao_brother
                            <td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>
                            <td>$consumidor_revenda</td>
                            <td>$data_abertura</td>
                            <td>$data_digitacao</td>
                            <td>$finalizada</td>
                            <td>$data_conserto</td>
                            $td_dias_em_aberto
                            <td align='center'>$qtde_km</td>
                            $td_mao_obra
                            <td>$obs</td>
                            <td>$dr_defeito</td>
                            <td>$dc_defeito</td>
                            <td>$solucao</td>
                            <td>$referencia</td>
                            <td nowrap>$descricao</td>
                            <td>$serie</td>
                            <td>$data_fabricacao</td>
                            <td>$consumidor_nome</td>
                            <td>$consumidor_cidade</td>
                            <td align='center'>$consumidor_estado</td>
                            <td>$consumidor_fone</td>
                            <td>$consumidor_celular</td>
                            <td>$consumidor_email</td>
                            <td>$consumidor_cep</td>
                            <td>$consumidor_bairro</td>
                            <td>$consumidor_endereco</td>
                            <td>$consumidor_numero</td>
                            <td>$consumidor_complemento</td>
                            <td>$revenda_nome</td>
                            <td>$revenda_cnpj</td>
                            <td>$nota_fiscal</td>
                            <td>$data_nf</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    ";
				}

				if (!in_array($login_fabrica,array(50,74,157,164,167,203))) {//HD-3141903
					$conteudo_pecas = "
						<table>
							<tr>
								{$xxreferencia_fabrica_peca}
								<th style='background-color: #596d9b !important;'>CÓDIGO</th>
								<th style='background-color: #596d9b !important;'>DESCRIÇÃO</th>
								{$coluna_peca_serie}
								<th style='background-color: #596d9b !important;'>QTDE</th>
								<th style='background-color: #596d9b !important;'>DATA DE DIGITAÇÃO</th>
								<th style='background-color: #596d9b !important;'>DEFEITO</th>
								<th style='background-color: #596d9b !important;'>SERVIÇO REALIZ.</th>
								<th style='background-color: #596d9b !important;'>PEDIDO</th>
								<th style='background-color: #596d9b !important;'>NF</th>
								<th style='background-color: #596d9b !important;'>EMISSÃO</th>
							</tr>
						</table>
					";
				}
			}
			}

			if ($login_fabrica == 74) {//HD-3141903
				$td_pecas = "$conteudo_pecas";
				$td_mao_obra = "<td align='center' >$mao_de_obra</td>";
			} else if($login_fabrica == 164) {
				$td_pecas = "$conteudo_pecas";
			} else if(!in_array($login_fabrica, array(50))) {
				$td_pecas = "<td>$conteudo_pecas</td>";
			}

			if (!in_array($login_fabrica,array(50,157))) {
				$conteudo_revenda = "
					<td>$revenda_nome</td>
					<td>$revenda_cnpj</td>
					<td>$nota_fiscal</td>
					<td>$data_nf</td>
				";
			}

			if (in_array($login_fabrica, [167, 203])) {
				if($consumidor_revenda == "C"){
					$qtde_dias_em_aberto = $dias_aberto_consumidor;
				}else{
					$qtde_dias_em_aberto = $dias_aberto_revenda;
				}
				$campos_fone_posto_brother = "<td>".$fone_posto."</td>";
				$campos_auditoria_brother = "<td>".$auditoria."</td>";
				$campos_condicao_brother = "<td>".$condicao."</td>";
				$td_dias_em_aberto = "<td>{$qtde_dias_em_aberto}</td>";
			}
			$xproduto_referencia_fabrica = "";
			if ($login_fabrica == 171) {
				$xproduto_referencia_fabrica = "<td>$produto_referencia_fabrica</td>";
			}

			$colunas_midea = "";
			$valor_tipo_atendimento = "";
			
			if (in_array($login_fabrica, array(167,169,170,203))) {
				$valor_tipo_atendimento = "<td>{$desc_tipo_atendimento}</td>";
			}

			if (in_array($login_fabrica, array(169,170))) {
				$colunas_midea = "
					<td>{$data_agendamento}</td>
					<td>{$data_confirmacao}</td>
					<td>{$data_reagendamento}</td>
					<td>{$inspetor_sap}</td>
				";
				$posto_codigo = "<td>{$posto_codigo}</td>";
			}

			if (in_array($login_fabrica, [167, 203])){
				$res_tipo_posto = "<td>$tipo_posto</td>";
				$res_nome_comercial = "<td>$nome_comercial</td>";
				$res_conclusao_laudo = "<td>$conclusao_laudo</td>";
				$res_mes_abertura = "<td>$mes_abertura</td>";
				$res_regiao = "<td>$regiao</td>";
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

            	$valor_parecer = "<td>$comentario_parecer</td>";
            	$valorYanmar = "<td>$desc_tipo_atendimento</td>";
            	$valorYanmar .= "<td>$valor_deslocamento</td>";
            	$valorYanmar .= "<td>$valor_peca</td>";
            	$valorYanmar .= "<td>$mao_de_obra</td>";
            	$valorYanmar .= "<td>$total_valor_os</td>";

            	$aud_liberada = "";
            	$linhaAuditoriaJustificativa = "";

            	$sqlAuditoria = "SELECT auditoria_os, tbl_auditoria_os.justificativa, tbl_auditoria_os.auditoria_status, tbl_auditoria_status.descricao as nome_auditoria, liberada from tbl_auditoria_os join tbl_auditoria_status on tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status where os = $os ";
            	$resAuditoria = pg_query($con, $sqlAuditoria);
            	for($aud = 0; $aud < pg_num_rows($resAuditoria); $aud++){
            		$liberada = pg_fetch_result($resAuditoria, $aud, 'liberada');
            		$justificativa = utf8_decode(pg_fetch_result($resAuditoria, $aud, 'justificativa'));
            		$nome_auditoria = pg_fetch_result($resAuditoria, $aud, 'nome_auditoria');

            		$linhaAuditoriaJustificativa .= "$nome_auditoria - $justificativa <Br>";
            		if(strlen(trim($liberada))>0){
            			$aud_liberada .= " $nome_auditoria <Br> ";	
            		}
            		
            	}
            	$justificativaAuditoria = "<td>$linhaAuditoriaJustificativa</td>";
            	$aprovadasAuditoria = "<td>$aud_liberada</td>";
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

					unset($aux_sql, $aux_res);
				} else {
					$auxiliar = "";
				}
				if(in_array($login_fabrica, [167, 203]) and count($conteudo_pecas) > 0) {
					foreach($conteudo_pecas as $pecas) {

					fwrite($file, "
					<tr class='tac' style='text-align:center'>
						<td>$cnpj</td>
						{$res_tipo_posto}
						{$posto_codigo}
						<td>$nome</td>
						<td>$contato_cidade</td>
						<td align='center'>$contato_estado</td>
						{$campos_fone_posto_brother}
						{$res_regiao}
						<td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>
						<td>$extrato</td>
						<td><strong>$status</strong></td>
						{$campos_auditoria_brother}
						{$campos_condicao_brother}
						<td>$auxiliar$sua_os</td>
						<td>$consumidor_revenda</td>
						{$valor_tipo_atendimento}
						<td>$data_abertura</td>
						{$res_mes_abertura}
						<td>$data_digitacao</td>
						<td>$finalizada</td>
						<td>$data_conserto</td>
						{$colunas_midea}
						{$td_dias_em_aberto}
						<td align='center'>$qtde_km</td>
						{$td_mao_obra}
						<td>$obs</td>
						<td>$dr_defeito</td>
						<td>$dc_defeito</td>
						<td>$solucao</td>
						{$xproduto_referencia_fabrica}
						<td>$referencia</td>
						<td nowrap>$descricao</td>
						<td>$serie</td>
						{$res_nome_comercial}
						<td>$data_fabricacao</td>
						<td>$consumidor_nome</td>
						<td>$consumidor_cidade</td>
						<td align='center'>$consumidor_estado</td>
						<td>$consumidor_fone</td>
						<td>$consumidor_celular</td>
						<td>$consumidor_email</td>
						<td>$consumidor_cep</td>
						<td>$consumidor_bairro</td>
						<td>$consumidor_endereco</td>
						<td>$consumidor_numero</td>
                        <td>$consumidor_complemento</td>
                        $linha_consumidor_profissao
						{$conteudo_revenda}
						{$res_conclusao_laudo}
						{$pecas}
					</tr>"
				);
					}
				}else{
					if ($login_fabrica == 1) {
						fwrite($file, "$cnpj;$nome;$contato_cidade;$contato_estado;$hd_chamado;$extrato;$status;$auxiliar$sua_os;$consumidor_revenda;$data_abertura;$data_digitacao;$finalizada;$data_conserto;$qtde_km;$obs;$dr_defeito;$dc_defeito;$solucao;$referencia;$descricao;$serie;$data_fabricacao;$consumidor_nome;$consumidor_cidade;$consumidor_estado;$consumidor_fone;$consumidor_celular;$consumidor_email;$consumidor_cep;$consumidor_bairro;$consumidor_endereco;$consumidor_numero;$consumidor_complemento;$linha_consumidor_profissao$revenda_nome;$revenda_cnpj;$nota_fiscal;$data_nf ;CÓDIGO;DESCRIÇÃO;QTDE;DATA DE DIGITAÇÃO;DEFEITO;SERVIÇO REALIZ.;PEDIDO;NF;EMISSÃO \r\n");
						fwrite($file, $conteudo_pecas_csv);

						$conteudo_pecas_csv = "";
					} else {

						$valor_dt_fb       = "<td>$data_fabricacao</td>";
						$valor_horimetro   = "";

						if ($login_fabrica == 148) {
							$valor_horimetro = "<td>$horimetro</td>";

							$ordem_Pin = "";
                            $sqlns = "SELECT ordem from tbl_numero_serie where referencia_produto  = '$referencia' and serie = '$serie'  and fabrica = $login_fabrica"; 
                            $resns = pg_query($con, $sqlns);
                            if(pg_num_rows($resns)>0){
                                $ordem_Pin =pg_fetch_result($resns, 0, 'ordem');
                            }
							$valorPin = "<td>$ordem_Pin</td>";
							$valor_dt_fb = "";
						}

						fwrite($file, "
							<tr class='tac' style='text-align:center'>
								<td>$cnpj</td>
								{$res_tipo_posto}
								{$posto_codigo}
								<td>$nome</td>
								<td>$contato_cidade</td>
								<td align='center'>$contato_estado</td>
								{$campos_fone_posto_brother}
								{$res_regiao}
								<td><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>
								<td>$extrato</td>
								<td><strong>$status</strong></td>
								{$campos_auditoria_brother}
								{$campos_condicao_brother}
								<td><a href='os_press.php?os=$os' target='_blank'>$auxiliar$sua_os</a></td>
								<td>$consumidor_revenda</td>
								{$valor_tipo_atendimento}
								<td>$data_abertura</td>
								{$res_mes_abertura}
								<td>$data_digitacao</td>
								<td>$finalizada</td>
								<td>$data_conserto</td>
								$valor_horimetro
								{$colunas_midea}
								{$td_dias_em_aberto}
								<td align='center'>$qtde_km</td>
								{$td_mao_obra}
								<td>$obs</td>
								<td>$dr_defeito</td>
								<td>$dc_defeito</td>
								<td>$solucao</td>
								{$xproduto_referencia_fabrica}
								<td>$referencia</td>
								<td nowrap>$descricao</td>
								<td>$serie</td>
								$valorPin
								{$res_nome_comercial}
								{$valor_dt_fb}
								<td>$consumidor_nome</td>
								<td>$consumidor_cidade</td>
								<td align='center'>$consumidor_estado</td>
								<td>$consumidor_fone</td>
								<td>$consumidor_celular</td>
								<td>$consumidor_email</td>
								<td>$consumidor_cep</td>
								<td>$consumidor_bairro</td>
								<td>$consumidor_endereco</td>
								<td>$consumidor_numero</td>
		                        <td>$consumidor_complemento</td>
		                        $linha_consumidor_profissao
								{$conteudo_revenda}
								{$res_conclusao_laudo}
								{$td_pecas}
								{$valor_parecer}
								{$valorYanmar}
								{$aprovadasAuditoria}
								{$justificativaAuditoria}
								
							</tr>"
						);
					}
				}
			}
            if ($login_fabrica == 157) {
                fwrite($file,$conteudo_pecas);
            }
		}

		if (!in_array($login_fabrica, [1, 50])) {
			fwrite($file, "
						<tr>
							<th colspan='$rows' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");
		}
		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}

		exit;
	}

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

include("../admin/plugin_loader.php");

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

if (in_array($login_fabrica, array(148,167,169,170,203))){
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
			WHERE status_checkpoint IN(1, 2, 3, 4, 9, 8, 28, 14, 0, 30) ";
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
		if (!in_array($login_fabrica, [167, 203])) {
		?>
			<h4>O período máximo para busca é de <?=$qtde_mes?> <?=$meses?></h4>
			<?=$frase?>
		<?php		
		} else {
		?>
			<h4>Data mínima para pesquisa 01/04/2019</h4>
		<?php
		}
	?>
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

		if(in_array($login_fabrica, [167, 203])){
			echo "<table style='margin: 0 auto;' width='700'>";
			echo "<tr><td colspan=2>Status das OS</td></tr>";
			$sql_status   = " SELECT status_checkpoint,descricao,cor FROM tbl_status_checkpoint WHERE status_checkpoint IN (0,1,2,3,4,8,9) ";
		    $res_status   = pg_query($con, $sql_status);
		    $total_status = pg_num_rows($res_status);

		    for($a= 0; $a<pg_num_rows($res_status); $a++){
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

			if(in_array($login_fabrica, [167, 203])){
				$coluna_fone_posto_brother = "<th>Fone Posto</th>";
			}

			echo "
				<table id='resultado' class=\"table table-striped table-bordered table-hover table-large\" style='margin: 0 auto;' >
					<thead>
					<tr class='titulo_coluna'>
					 	<th>OS</th>
					 	{$posto_codigo}
						<th>Posto</th>
						{$coluna_fone_posto_brother}
					 	{$xreferenciaFabrica}
						<th>Produto</th>";
						if($login_fabrica == 50){
							echo "<th>Validação NS</th>";
						}
					echo "
						<th>Extrato</th>
						<th>Status</th>";
				if(in_array($login_fabrica, [167, 203])){
					echo "<th>Auditoria</th>";
					echo "<th>Contador</th>";
				}
				echo "<th>Tipo</th>";

				if (in_array($login_fabrica, array(167,169,170,203))){
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
			$os_liberada		= pg_fetch_result($resSubmit, $i, 'liberada');
			$os_cancelada		= pg_fetch_result($resSubmit, $i, 'cancelada');
			$os_reprovada		= pg_fetch_result($resSubmit, $i, 'reprovada');
            		$campos_adicionais      = json_decode(pg_fetch_result($resSubmit, $i, "campos_adicionais"), true);

            $auditoria = (strlen($os_liberada) > 0) ? "APROVADA" : "PENDENTE";
            $auditoria = (strlen($os_cancelada) > 0) ? "CANCELADA" : $auditoria;
            $auditoria = (strlen($os_reprovada) > 0) ? "REPROVADA" : $auditoria;
            $auditoria = (empty($os_auditoria)) ? "" : $auditoria;

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

			if (in_array($login_fabrica, array(167,169,170,203))) {
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

				if (!in_array($login_fabrica, [167, 203])){
					unset($array_pecas['colunas'][3]);
				}

				for($k = 0; $k < pg_num_rows($resSubmit2); $k++) {
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

					if (!in_array($login_fabrica, [167, 203])){
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

			$informacaoCompleta = array(
				'titulo' => "Informação completa da OS: $sua_os",
				'campos' => array(
					array('coluna' => 'Posto', 'valor' => $nome, "span" => 2),
					array('coluna' => 'Posto CNPJ', 'valor' => $cnpj),
					array('coluna' => 'Classificação', 'valor' => $tipo_posto),
					array('coluna' => 'Contato Cidade', 'valor' => $contato_cidade, "span" => 2),
					array('coluna' => 'Contato UF', 'valor' => $contato_estado),
					array('coluna' => 'Região', 'valor' => $regiao),
					array('coluna' => 'Protocolo', 'valor' => $hd_chamado),
					array('coluna' => 'Extrato', 'valor' => $extrato),
					array('coluna' => 'Status', 'valor' => $status),
					array('coluna' => 'OS', 'valor' => $sua_os),
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
				if(in_array($login_fabrica, [167, 203])){
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

				if (in_array($login_fabrica, [167, 203])) {
					echo "$auxiliar$sua_os</td>";
				} else {
					echo "<a href='os_press.php?os=$os' target='_blank'>$auxiliar$sua_os</a></td>";
				}
				if (in_array($login_fabrica, array(169,170))) {
					echo "<td>{$posto_codigo}</td>";
				}

				if (in_array($login_fabrica, array(167,203))) {
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
				echo "<td>$extrato</td>
					<td nowrap ><strong>$status</strong></td>";
				if(in_array($login_fabrica, [167, 203])){
					echo "<td>$auditoria</td>";
					echo "<td>$condicao</td>";
				}
				echo "<td>$consumidor_revenda</td>";

				if (in_array($login_fabrica, array(167,169,170,203))){
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
				#var_dump($informacaoCompleta); 
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

		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='../admin/imagens/excel.png' /></span>
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

include '../admin/rodape.php';?>
