<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = $_POST['btn_acao'];

// O Campo origem em tbl_produto.origem só aceita 3 caracteres, por isso a necessidade de criar o De Para
$deParaOrigem = array(
	"MNS" => "Manaus",
	"IMP" => "Importado",
	"CNS" => "Canoas"
);

/* Pesquisa Padrão */
if(isset($btn_acao)){
	$data_inicial		= $_POST["data_inicial"];
	$data_final 		= $_POST["data_final"];
	$defeito_constatado	= $_POST["defeito_constatado"];
	$status				= $_POST["status"];
	$linha				= $_POST["linha"];
	$linha_producao 	= $_POST["linha_producao"];
	$origem 			= $_POST["origem"];
	$familia_sap 		= $_POST["familia_sap"];
	$pd 				= $_POST["pd"];
	$tipo_atendimento 	= $_POST["ta"]; 
	if($login_fabrica == 169){
		$dt_referencia = $_POST["data_referencia"];		
	}


	if (is_array($linha)) {
		$linha = implode(",", $linha);
	}

	if (is_array($tipo_atendimento)) {
		$tipo_atendimento = implode(",", $tipo_atendimento);
	}

	if (is_array($status)) {
		$status = implode(",", $status);
	}

	if (is_array($origem)) {
		$origem = array_map(function($e) {
			return "'{$e}'";
		} , $origem);
		$origem = implode(",", $origem);
	}

	if (is_array($linha_producao)) {
		$linha_producao = array_map(function($e) {
			return "'{$e}'";
		} , $linha_producao);
		$linha_producao = implode(",", $linha_producao);
	}

	if (is_array($familia_sap)) {
		$familia_sap = array_map(function($e) {
			return "'{$e}'";
		} , $familia_sap);
		$familia_sap = implode(",", $familia_sap);
	}

	if (is_array($pd)) {
		$pd = array_map(function($e) {
			return "'{$e}'";
		} , $pd);
		$pd = implode(",", $pd);
	}

	if (is_array($tipo_atendimento)) {
		$tipo_atendimento = array_map(function($e) {
			return "'{$e}'";
		} , $tipo_atendimento);
		$tipo_atendimento = implode(",", $tipo_atendimento);
	}	

	if (empty($data_inicial) || empty($data_final) || ($login_fabrica == 169 && empty($dt_referencia))) {
		$msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
		if($login_fabrica == 169 ){
			$msg_erro["campos"][] = "data_referencia";
		}
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
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

		$sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' >= '$aux_data_final'";
		$resSubmitX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 6 meses";
		}
	}

	if (count($msg_erro['msg']) == 0) {

		if (!empty($linha)) {
			$whereLinha = "AND l.linha IN ({$linha})";
		}

		if (!empty($defeito_constatado)) {
			$whereDefeitoConstatado = "AND odrc.defeito_constatado IN ({$defeito_constatado})";
		}

		if (!empty($status)) {
			$whereStatus = "AND o.status_checkpoint IN ({$status})";
		}

		if (!empty($origem)) {
			$whereOrigem = "AND prt.origem IN ({$origem})";
		}

		if (!empty($linha_producao)) {
			if(isset($_POST["gerar_excel"])) $linha_producao = utf8_decode($linha_producao);
			$whereLinhaProducao = "AND prt.nome_comercial IN ({$linha_producao})";
		}

		if (!empty($familia_sap)) {
			$whereFamiliaSap = "AND JSON_FIELD('familia_desc', prt.parametros_adicionais) IN ({$familia_sap})";
		}

		if (!empty($pd)) {
			$wherePd = "AND JSON_FIELD('pd', prt.parametros_adicionais) IN ({$pd})";
		}

		if ($login_fabrica == 169) {
			if(!empty($tipo_atendimento)) {
				$tipoAtend .= "JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.tipo_atendimento IN ({$tipo_atendimento}) AND ta.fabrica = {$login_fabrica}";
			} else {
				$tipoAtend .= "JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}";
			}			

			if($dt_referencia == "A"){
				$cond_dt_referencia = " AND o.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
			} else {
				$cond_dt_referencia = " AND o.data_fechamento BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
			}
		} else {
			$cond_dt_referencia = " AND o.data_abertura BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 500" : "";

		$sqlPesquisa = "
			SELECT DISTINCT
				TO_CHAR(ns.data_fabricacao, 'DD/MM/YY') AS data_fabricacao,
				TO_CHAR(ns.data_fabricacao, 'MM/YYYY') AS mes_fabricacao,
				TO_CHAR(ns.data_fabricacao, 'YYYY') AS ano_fabricacao,
				TO_CHAR(o.data_abertura, 'DD/MM/YY') AS data_abertura,
				TO_CHAR(o.data_abertura, 'MM/YYYY') AS mes_abertura,
				TO_CHAR(o.data_abertura, 'YYYY') AS ano_abertura,
				TO_CHAR(o.data_nf, 'DD/MM/YY') AS data_nf,
				TO_CHAR(o.data_nf, 'MM/YYYY') AS mes_nf,
				TO_CHAR(o.data_nf, 'YYYY') AS ano_nf,
				o.os AS os_id,
				o.sua_os AS os,
				o.consumidor_revenda,
				o.consumidor_nome,
				o.consumidor_estado,
				CASE WHEN o.consumidor_fone IS NOT NULL THEN o.consumidor_fone ELSE o.consumidor_celular END AS consumidor_contato,
				r.nome AS revenda_nome,
				cr.estado AS revenda_estado,
				r.fone AS revenda_fone,
				o.nota_fiscal,
				sc.descricao AS status_descricao,
				op.serie,
				ta.descricao AS tipo_atendimento_descricao,
				pf.codigo_posto AS posto_codigo,
				pf.codigo_posto||' - '||p.nome AS posto_descricao,
				p.estado AS posto_estado,
				pf.contato_nome||' - '||pf.contato_fone_comercial AS posto_contato,
				l.nome AS linha_descricao,
				REPLACE(prt.referencia, 'YY', '-') AS produto_referencia,
				prt.descricao AS produto_descricao,
				prt.nome_comercial AS produto_nome_comercial,
				prt.origem AS produto_origem,
				prt.parametros_adicionais,
				f.descricao AS familia_descricao,
				dr.descricao AS defeito_reclamado_descricao,
				dc.descricao AS defeito_constatado_descricao,
				dp.descricao AS defeito_peca_descricao,
				oi.qtde AS peca_qtde,
				pc.referencia AS peca_referencia,
				pc.descricao AS peca_descricao,
				sr.descricao AS servico_realizado_descricao
			FROM tbl_os o
			JOIN tbl_os_produto op USING(os)
			JOIN tbl_os_item oi USING(os_produto)
			JOIN tbl_servico_realizado sr USING(servico_realizado,fabrica)
			JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.fabrica = {$login_fabrica}
			JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
			JOIN tbl_posto p ON p.posto = pf.posto
			JOIN tbl_revenda r ON r.revenda = o.revenda
			JOIN tbl_cidade cr ON cr.cidade = r.cidade
			JOIN tbl_produto prt ON prt.produto = op.produto AND prt.fabrica_i = {$login_fabrica}
			JOIN tbl_linha l ON l.linha = prt.linha AND l.fabrica = {$login_fabrica}
			JOIN tbl_familia f ON f.familia = prt.familia AND f.fabrica = {$login_fabrica}
			JOIN tbl_defeito_reclamado dr ON dr.defeito_reclamado = o.defeito_reclamado AND dr.fabrica = {$login_fabrica}
			JOIN tbl_os_defeito_reclamado_constatado odrc ON odrc.os = o.os AND odrc.fabrica = {$login_fabrica}
			JOIN tbl_diagnostico diag ON diag.defeito_constatado = odrc.defeito_constatado AND ((diag.defeito = oi.defeito AND odrc.defeito IS NULL) OR diag.defeito = odrc.defeito) AND diag.fabrica = {$login_fabrica}
			JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = diag.defeito_constatado AND dc.fabrica = {$login_fabrica}
			JOIN tbl_defeito dp ON dp.defeito = diag.defeito AND dp.fabrica = {$login_fabrica}
			JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
			{$tipoAtend}
			LEFT JOIN tbl_numero_serie ns ON ns.serie = op.serie AND ns.fabrica = {$login_fabrica}
			WHERE o.fabrica = {$login_fabrica}
			AND o.excluida IS NOT TRUE
			AND sr.descricao != 'Cancelado'
			AND o.posto != 6359
			{$cond_dt_referencia}
			{$whereLinha}
			{$whereDefeitoConstatado}
			{$whereStatus}
			{$whereOrigem}
			{$whereLinhaProducao}
			{$whereFamiliaSap}
			{$wherePd}
			ORDER BY o.sua_os
			{$limit};
		";
		
		//die(nl2br($sqlPesquisa));
		$resPesquisa = pg_query($con, $sqlPesquisa);
		// var_dump($sqlPesquisa); exit;

		$count = pg_num_rows($resPesquisa);
		$dadosPesquisa = pg_fetch_all($resPesquisa);

		/* Gera arquivo CSV */
		if ($_POST["gerar_excel"] && $count > 0) {

			$arr_meses = array(
				'01' => 'Janeiro',
				'02' => 'Fevereiro',
				'03' => 'Março',
				'04' => 'Abril',
				'05' => 'Maio',
				'06' => 'Junho',
				'07' => 'Julho',
				'08' => 'Agosto',
				'09' => 'Setembro',
				'10' => 'Outubro',
				'11' => 'Novembro',
				'12' => 'Dezembro'
			);

			$data = date("d-m-Y-H:i");

			$arquivo_nome 	= "relatorio-qtde-os-qualidade-$data.xls";
			$path 			= "xls/";
			$path_tmp 		= "/tmp/";

			$arquivo_completo = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			$fp = fopen($arquivo_completo_tmp,"w");

			$thead = "<table border='1'>";
			$thead .= "<thead>";
			$thead .= "<tr>";
			$thead .= "<th>OS</th>";
			$thead .= "<th>Escape Count</th>";
			$thead .= "<th>Número de Série</th>";
			$thead .= "<th>Ref. Produto</th>";
			$thead .= "<th>Desc. Produto</th>";
			$thead .= "<th>Linha Fabricação</th>";
			$thead .= "<th>Cód. Família</th>";
			$thead .= "<th>Desc. Família</th>";
			$thead .= "<th>Família TC</th>";
			$thead .= "<th>Cód. Grupo Família</th>";
			$thead .= "<th>Desc. Grupo Família</th>";
			$thead .= "<th>Product Division</th>";
			$thead .= "<th>Origem</th>";
			$thead .= "<th>Defeito Reclamado</th>";
			$thead .= "<th>Defeito Constatado</th>";
			$thead .= "<th>Defeito da Peça</th>";
			$thead .= "<th>Tipo de Atendimento</th>";
			$thead .= "<th>Ref. Peça</th>";
			$thead .= "<th>Desc. Peça</th>";
			$thead .= "<th>Qtde. Peça</th>";
			$thead .= "<th>Serviço Realizado</th>";
			$thead .= "<th>Def. Reclamado X Desc. Grupo Família</th>";
			$thead .= "<th>Data Fabricação</th>";
			$thead .= "<th>Mês Fabricação</th>";
			$thead .= "<th>Ano Fabricação</th>";
			$thead .= "<th>Data Nota Fiscal</th>";
			$thead .= "<th>Mês Nota Fiscal</th>";
			$thead .= "<th>Ano Nota Fiscal</th>";
			$thead .= "<th>Data Abertura</th>";
			$thead .= "<th>Mês Abertura</th>";
			$thead .= "<th>Ano Abertura</th>";
			$thead .= "<th>T. Falha</th>";
			$thead .= "<th>T. Estoque</th>";
			$thead .= "<th>Status</th>";
			$thead .= "<th>Tipo de OS</th>";
			$thead .= "<th>Nota Fiscal</th>";
			$thead .= "<th>Posto</th>";
			$thead .= "<th>Contato Posto</th>";
			$thead .= "<th>UF Posto</th>";
			$thead .= "<th>Consumidor</th>";
			$thead .= "<th>UF Consumidor</th>";
			$thead .= "<th>Contato Consumidor</th>";
			$thead .= "<th>Revenda</th>";
			$thead .= "<th>UF Revenda</th>";
			$thead .= "<th>Contato Revenda</th>";
			$thead .= "</tr>";
			$thead .= "</thead>";

			fwrite($fp, $thead);

			$tbody .= "<tbody>";
			$contador = 0;
			$os_anterior = "";
			for ($fi = 0; $fi < $count; $fi++) {
				$fdata_fabricacao = pg_fetch_result($resPesquisa, $fi, "data_fabricacao");
				$fmes_fabricacao = pg_fetch_result($resPesquisa, $fi, "mes_fabricacao");
				$fano_fabricacao = pg_fetch_result($resPesquisa, $fi, "ano_fabricacao");
				$fdata_abertura = pg_fetch_result($resPesquisa, $fi, "data_abertura");
				$fmes_abertura = pg_fetch_result($resPesquisa, $fi, "mes_abertura");
				$fano_abertura = pg_fetch_result($resPesquisa, $fi, "ano_abertura");
				$fmes_fabricacao = pg_fetch_result($resPesquisa, $fi, "mes_fabricacao");
				$fdata_nf = pg_fetch_result($resPesquisa, $fi, "data_nf");
				$fmes_nf = pg_fetch_result($resPesquisa, $fi, "mes_nf");
				$fano_nf = pg_fetch_result($resPesquisa, $fi, "ano_nf");
				$fmeses_falha = pg_fetch_result($resPesquisa, $fi, "meses_falha");
				$fmeses_estoque = pg_fetch_result($resPesquisa, $fi, "meses_estoque");
				$fos = pg_fetch_result($resPesquisa, $fi, "os");
				$fstatus_descricao = pg_fetch_result($resPesquisa, $fi, "status_descricao");
				$fconsumidor_revenda = pg_fetch_result($resPesquisa, $fi, "consumidor_revenda");
				$fnota_fiscal = pg_fetch_result($resPesquisa, $fi, "nota_fiscal");
				$fserie = pg_fetch_result($resPesquisa, $fi, "serie");
				$ftipo_atendimento_descricao = pg_fetch_result($resPesquisa, $fi, "tipo_atendimento_descricao");
				$fposto_codigo = pg_fetch_result($resPesquisa, $fi, "posto_codigo");
				$fposto_descricao = pg_fetch_result($resPesquisa, $fi, "posto_descricao");
				$fposto_estado = pg_fetch_result($resPesquisa, $fi, "posto_estado");
				$fposto_contato = utf8_decode(pg_fetch_result($resPesquisa, $fi, "posto_contato"));
				$flinha_descricao = pg_fetch_result($resPesquisa, $fi, "linha_descricao");
				$fproduto_referencia = pg_fetch_result($resPesquisa, $fi, "produto_referencia");
				$fproduto_descricao = pg_fetch_result($resPesquisa, $fi, "produto_descricao");
				$fproduto_nome_comercial = pg_fetch_result($resPesquisa, $fi, "produto_nome_comercial");
				$fproduto_origem = pg_fetch_result($resPesquisa, $fi, "produto_origem");
				$ffamilia_descricao = pg_fetch_result($resPesquisa, $fi, "familia_descricao");
				$fdefeito_reclamado_descricao = pg_fetch_result($resPesquisa, $fi, "defeito_reclamado_descricao");
				$fdefeito_constatado_descricao = pg_fetch_result($resPesquisa, $fi, "defeito_constatado_descricao");
				$fdefeito_peca_descricao = pg_fetch_result($resPesquisa, $fi, "defeito_peca_descricao");
				$fpeca_qtde = pg_fetch_result($resPesquisa, $fi, "peca_qtde");
				$fpeca_referencia = pg_fetch_result($resPesquisa, $fi, "peca_referencia");
				$fpeca_descricao = pg_fetch_result($resPesquisa, $fi, "peca_descricao");
				$fservico_realizado_descricao = pg_fetch_result($resPesquisa, $fi, "servico_realizado_descricao");
				$fconsumidor_nome = pg_fetch_result($resPesquisa, $fi, "consumidor_nome");
				$fconsumidor_estado = pg_fetch_result($resPesquisa, $fi, "consumidor_estado");
				$fconsumidor_contato = pg_fetch_result($resPesquisa, $fi, "consumidor_contato");
				$frevenda_nome = pg_fetch_result($resPesquisa, $fi, "revenda_nome");
				$frevenda_estado = pg_fetch_result($resPesquisa, $fi, "revenda_estado");
				$frevenda_fone = pg_fetch_result($resPesquisa, $fi, "revenda_fone");

				$fparametros_adicionais = pg_fetch_result($resPesquisa, $fi, "parametros_adicionais");
				$fparametros_adicionais = json_decode($fparametros_adicionais, true);
				$fproduto_codigo_familia = $fparametros_adicionais['familia'];
				$fproduto_descricao_familia = $fparametros_adicionais['familia_desc'];
				$fproduto_codigo_grupo_familia = $fparametros_adicionais['grupo_familia'];
				$fproduto_descricao_grupo_familia = $fparametros_adicionais['grupo_familia_desc'];
				$fproduto_product_division = $fparametros_adicionais['pd'];

				if ($fos != $os_anterior) {
					$contador = 1;
				} else {
					$contador = 0;
				}

				if ($fserie == $fposto_codigo) {
					$fdata_fabricacao = "";
					$fmes_fabricacao = "";
					$fmes_fabricacao_aux = "";
					$fano_fabricacao = "";
					$fmeses_falha = "";
					$fmeses_estoque = "";
				} else {
					if ($fproduto_origem == "IMP" && !empty($fdata_fabricacao)) {
						$semana_fab = substr($fserie, 2, 2);
						$ano_fab = substr($fserie, 4, 2);
					
						$mes_aux = ($semana_fab * 7) / 30;
						$mes_aux = (int) $mes_aux;

						if ($mes_aux == 0) {
							$mes_aux = 1;
						}

						if (strlen($mes_aux) == 1) {
							$mes_aux = "0".$mes_aux;
						}

						$fdata_fabricacao = "01/".$mes_aux."/".$ano_fab;
						$fmes_fabricacao = $mes_aux."/20".$ano_fab;
						$fano_fabricacao = "20".$ano_fab;
					}
				}
				
				if (!empty($fmes_fabricacao)) {
					$fmes_fabricacao = explode("/", $fmes_fabricacao);
					$fmes_fabricacao_aux = strtoupper($arr_meses[$fmes_fabricacao[0]])."/".$fmes_fabricacao[1];
				} else {
					$fmes_fabricacao_aux = "";
				}

				if (!empty($fmes_abertura)) {
					$fmes_abertura = explode("/", $fmes_abertura);
					$fmes_abertura_aux = strtoupper($arr_meses[$fmes_abertura[0]])."/".$fmes_abertura[1];
				} else {
					$fmes_abertura_aux = "";
				}

				if (!empty($fmes_nf)) {
					$fmes_nf = explode("/", $fmes_nf);
					$fmes_nf_aux = strtoupper($arr_meses[$fmes_nf[0]])."/".$fmes_nf[1];
				} else {
					$fmes_nf_aux = "";
				}

				$fmeses_estoque = "";
				if (!empty($fdata_fabricacao) && !empty($fdata_nf)) {
					list($dfb, $mfb, $yfb) = explode("/", $fdata_fabricacao);
					list($dnf, $mnf, $ynf) = explode("/", $fdata_nf);

					$fmeses_estoque = strtotime("{$ynf}-{$mnf}-{$dnf}") - strtotime("{$yfb}-{$mfb}-{$dfb}");
					$fmeses_estoque = floor($fmeses_estoque / (60 * 60 * 24 * 30));
				}

				$fmeses_falha = "";
				if (!empty($fdata_abertura) && !empty($fdata_nf)) {
					list($dab, $mab, $yab) = explode("/", $fdata_abertura);
					list($dnf, $mnf, $ynf) = explode("/", $fdata_nf);

					$fmeses_falha = strtotime("{$yab}-{$mab}-{$dab}") - strtotime("{$ynf}-{$mnf}-{$dnf}");
					$fmeses_falha = floor($fmeses_falha / (60 * 60 * 24 * 30));
				}
				
				$tbody .= "<tr>";
				$tbody .= "<td>".$fos."</td>";
				$tbody .= "<td>".$contador."</td>";
				$tbody .= "<td>".$fserie."</td>";
				$tbody .= "<td>".$fproduto_referencia."</td>";
				$tbody .= "<td>".$fproduto_descricao."</td>";
				$tbody .= "<td>".$fproduto_nome_comercial."</td>";
				$tbody .= "<td>".$fproduto_codigo_familia."</td>";
				$tbody .= "<td>".$fproduto_descricao_familia."</td>";
				$tbody .= "<td>".$ffamilia_descricao."</td>";
				$tbody .= "<td>".$fproduto_codigo_grupo_familia."</td>";
				$tbody .= "<td>".$fproduto_descricao_grupo_familia."</td>";
				$tbody .= "<td>".$fproduto_product_division."</td>";
				$tbody .= "<td>".$deParaOrigem[$fproduto_origem]."</td>";
				$tbody .= "<td>".$fdefeito_reclamado_descricao."</td>";
				$tbody .= "<td>".$fdefeito_constatado_descricao."</td>";
				$tbody .= "<td>".$fdefeito_peca_descricao."</td>";
				$tbody .= "<td>".$ftipo_atendimento_descricao."</td>";
				$tbody .= "<td>".$fpeca_referencia."</td>";
				$tbody .= "<td>".$fpeca_descricao."</td>";
				$tbody .= "<td>".$fpeca_qtde."</td>";
				$tbody .= "<td>".$fservico_realizado_descricao."</td>";
				$tbody .= "<td>".$fdefeito_reclamado_descricao." - ".$fproduto_descricao_grupo_familia."</td>";
				$tbody .= "<td>".$fdata_fabricacao."</td>";
				$tbody .= "<td>".$fmes_fabricacao_aux."</td>";
				$tbody .= "<td>".$fano_fabricacao."</td>";
				$tbody .= "<td>".$fdata_nf."</td>";
				$tbody .= "<td>".$fmes_nf_aux."</td>";
				$tbody .= "<td>".$fano_nf."</td>";
				$tbody .= "<td>".$fdata_abertura."</td>";
				$tbody .= "<td>".$fmes_abertura_aux."</td>";
				$tbody .= "<td>".$fano_abertura."</td>";
				$tbody .= "<td>".$fmeses_falha."</td>";
				$tbody .= "<td>".$fmeses_estoque."</td>";
				$tbody .= "<td>".$fstatus_descricao."</td>";
				$tbody .= "<td>".$fconsumidor_revenda."</td>";
				$tbody .= "<td>".$fnota_fiscal."</td>";
				$tbody .= "<td>".$fposto_descricao."</td>";
				$tbody .= "<td>".$fposto_contato."</td>";
				$tbody .= "<td>".$fposto_estado."</td>";
				$tbody .= "<td>".$fconsumidor_nome."</td>";
				$tbody .= "<td>".$fconsumidor_estado."</td>";
				$tbody .= "<td>".$fconsumidor_contato."</td>";
				$tbody .= "<td>".$frevenda_nome."</td>";
				$tbody .= "<td>".$frevenda_estado."</td>";
				$tbody .= "<td>".$frevenda_fone."</td>";
				$tbody .= "</tr>";

				$os_anterior = $fos;
			}
			$tbody .= "</tbody>";
			$tbody .= "</table>";

			fwrite($fp, $tbody);

			fclose($fp);

			if (file_exists($arquivo_completo_tmp)) {
				system("mv ".$arquivo_completo_tmp." ".$arquivo_completo."");
				echo $arquivo_completo;
			}

			exit;
		}
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO OS QUALIDADE";
include "cabecalho_new.php";

$array_estados = $array_estados();

$plugins = array(
	"lupa",
	"autocomplete",
	"datepicker",
	"mask",
	"dataTable",
	"shadowbox",
	"select2",
);

include "plugin_loader.php";

if (count($msg_erro["msg"]) > 0) { ?>
	<div class="alert alert-error">
		<h4><?= implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<? } ?>
<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios</b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?= $data_inicial; ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span6'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= $data_final; ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<? if($login_fabrica == 169){ 
		if($data_referencia == "A") {
			$chk_abertura = "checked";
		} else {
			$chk_finalizada = "checked";
		} ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class="span8">
				<div class='control-group <?=(in_array("data_referencia", $msg_erro["campos"])) ? "error" : ""?>'>
					<h5 class='asteristico'>*</h5>
					<label class='control-label' for='data_referencia'>Data Referência</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<label class='radio'>Abertura
								<input type="radio" name="data_referencia" id="data_referencia" value="A" <?=$chk_abertura ?>>
							</label>
							<label class='radio'>Finalizada
								<input type="radio" name="data_referencia" id="data_referencia" value="F" <?=$chk_finalizada ?>>
							</label>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<? } ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='linha_producao'>Linha de Produto</label>
				<div class='controls controls-row'>
					<?
					$sqlLPrd = "SELECT DISTINCT nome_comercial FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(nome_comercial) IS NOT NULL ORDER BY nome_comercial;";
					$resLPrd = pg_query($con, $sqlLPrd); ?>
					<select name="linha_producao[]" id="linha_producao" multiple="multiple" class='span12'>
						<? if (is_array($_POST["linha_producao"])) {
							$selected_linha_producao = $_POST['linha_producao'];
						}
						foreach (pg_fetch_all($resLPrd) as $key) { ?>
							<option value="<?= $key['nome_comercial']?>" <?= (in_array($key['nome_comercial'], $selected_linha_producao)) ? "selected" : ""; ?>>
								<?= $key['nome_comercial']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
                <label class='control-label' for='status'>Status</label>
				<div class='controls controls-row'>
					<?
					$sql =	"SELECT * FROM tbl_status_checkpoint WHERE status_checkpoint IN(1,2,8,45,46,47,3,4,14,30,9,48,49,50,28);";
					$res = pg_query($con,$sql); ?>
					<select name="status[]" id="status" multiple="multiple" class='span12'>
						<? $selected_linha = explode(",", $status);
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['status_checkpoint']?>" <?= (in_array($key['status_checkpoint'], $selected_linha)) ? "selected" : ""; ?>>
								<?= $key['descricao']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Origem</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT origem FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND TRIM(origem) IS NOT NULL ORDER BY origem;";
					$res = pg_query($con,$sql); ?>
					<select name="origem[]" id="origem" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['origem'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['origem']?>" <?= (in_array($key['origem'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $deParaOrigem[$key['origem']]; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Desc. Família</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT JSON_FIELD('familia_desc', parametros_adicionais) AS familia_sap FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%familia_desc%' ORDER BY familia_sap;";
					$res = pg_query($con,$sql); ?>
					<select name="familia_sap[]" id="familia_sap" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['familia_sap'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['familia_sap']?>" <?= (in_array($key['familia_sap'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $key['familia_sap']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Product Division</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT JSON_FIELD('pd', parametros_adicionais) AS pd FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND parametros_adicionais like '%pd%' ORDER BY pd;";
					$res = pg_query($con,$sql); ?>
					<select name="pd[]" id="pd" multiple="multiple" class='span12'>
						<? $selected_origem = $_POST['pd'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['pd']?>" <?= (in_array($key['pd'], $selected_origem)) ? "selected" : ""; ?>>
								<?= $key['pd']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
		<?php if ($login_fabrica == 169) { ?>
		<div class="span4">
			<div class='control-group'>
				<label class='control-label' for='origem'>Tipo de Atndimento</label>
				<div class='controls controls-row'>
					<?
					$sql = "SELECT DISTINCT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND   ativo ORDER BY descricao;";
					$res = pg_query ($con,$sql); ?>
					<select name="ta[]" id="ta" multiple="multiple" class='span12'>
						<? $selected_tipo = $_POST['ta'];
						foreach (pg_fetch_all($res) as $key) { ?>
							<option value="<?= $key['tipo_atendimento']?>" <?= (in_array($key['tipo_atendimento'], $selected_tipo)) ? "selected" : ""; ?>>
								<?= $key['descricao']; ?>
							</option>
						<? } ?>
					</select>
				</div>
			</div>
		</div>
	<?php } ?>
		<div class='span2'></div>
	</div>

	<div class="row-fluid">
		<p class="tac">
			<button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p>
	</div>
</form>
</div>
<? if (isset($btn_acao) && count($msg_erro['msg']) == 0) {
	if ($count > 0) {
		if ($count > 500) { ?>
			<div class='alert'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<? } ?>
		<div class='tal' style='padding-rigth: 5px !important;'>
			<table id="resultado_pesquisa" class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna'>
						<th class="tac">OS</th>
						<th class="tac">Escape Count</th>
						<th class="tac">Número de Série</th>
						<th class="tac">Referencia Produto</th>
						<th class="tac">Tipo de Atendimento</th>
						<th class="tac">Desc. Produto</th>
						<th class="tac">Linha Fabricação</th>
						<th class="tac">Família</th>
						<th class="tac">Product Division</th>
						<th class="tac">Origem</th>
						<th class="tac">Defeito Constatado</th>
						<th class="tac">Defeito da Peça</th>
						<th class="tac">Desc. Peça</th>
						<th class="tac">Data Fabricação</th>
						<th class="tac">Data Nota Fiscal</th>
						<th class="tac">Data Abertura</th>
						<th class="tac">Status</th>
					</tr>
				</thead>
				<tbody>
					<?
					$contador = 0;
					$os_anterior = "";
					for ($xi = 0; $xi < $count; $xi++) {
						$xdata_fabricacao = pg_fetch_result($resPesquisa, $xi, "data_fabricacao");
						$xdata_abertura = pg_fetch_result($resPesquisa, $xi, "data_abertura");
						$xdata_nf = pg_fetch_result($resPesquisa, $xi, "data_nf");
						$xserie = pg_fetch_result($resPesquisa, $xi, "serie");
						$xposto_codigo = pg_fetch_result($resPesquisa, $xi, "posto_codigo");
						$xos = pg_fetch_result($resPesquisa, $xi, "os");
						$xos_id = pg_fetch_result($resPesquisa, $xi, "os_id");
						$xstatus_descricao = pg_fetch_result($resPesquisa, $xi, "status_descricao");
						$xproduto_referencia = pg_fetch_result($resPesquisa, $xi, "produto_referencia");
						$xtipo_atendimento = pg_fetch_result($resPesquisa, $xi, "tipo_atendimento_descricao");
						$xproduto_descricao = pg_fetch_result($resPesquisa, $xi, "produto_descricao");
						$xproduto_nome_comercial = pg_fetch_result($resPesquisa, $xi, "produto_nome_comercial");
						$xproduto_origem = pg_fetch_result($resPesquisa, $xi, "produto_origem");
						$xfamilia_descricao = pg_fetch_result($resPesquisa, $xi, "familia_descricao");
						$xdefeito_constatado_descricao = pg_fetch_result($resPesquisa, $xi, "defeito_constatado_descricao");
						$xdefeito_peca_descricao = pg_fetch_result($resPesquisa, $xi, "defeito_peca_descricao");
						$xpeca_descricao = pg_fetch_result($resPesquisa, $xi, "peca_descricao");

						$xparametros_adicionais = pg_fetch_result($resPesquisa, $xi, "parametros_adicionais");
                		$xparametros_adicionais = json_decode($xparametros_adicionais, true);
                        $xproduto_product_division = $xparametros_adicionais['pd'];

						if ($xos != $os_anterior) {
							$contador = 1;
						} else {
							$contador = 0;
						}

						if ($xserie == $xposto_codigo) {
							$xdata_fabricacao = "";
						} else {
							if ($xproduto_origem == "IMP" && !empty($xdata_fabricacao)) {
								$semana_fab = substr($xserie, 2, 2);
								$ano_fab = substr($xserie, 4, 2);
					
								$mes_aux = ($semana_fab * 7) / 30;
								$mes_aux = (int) $mes_aux;

								if (strlen($mes_aux) == 1) {
									$mes_aux = "0".$mes_aux;
								}

								$xdata_fabricacao = "01/".$mes_aux."/".$ano_fab;
							}
						} ?>
						<tr>
							<td class="tac"><a href="os_press.php?os=<?= $xos_id; ?>" target="_blank"><?= $xos; ?></a></td>
							<td class="tac"><?= $contador; ?></td>
							<td class="tac"><?= $xserie; ?></td>
							<td class="tac"><?= $xproduto_referencia; ?></td>
							<td class="tac"><?= $xtipo_atendimento; ?></td>
							<td class="tac"><?= $xproduto_descricao; ?></td>
							<td class="tac"><?= $xproduto_nome_comercial; ?></td>
							<td class="tac"><?= $xfamilia_descricao; ?></td>
							<td class="tac"><?= $xproduto_product_division; ?></td>
							<td class="tac"><?= $deParaOrigem[$xproduto_origem]; ?></td>
							<td class="tac"><?= $xdefeito_constatado_descricao; ?></td>
							<td class="tac"><?= $xdefeito_peca_descricao; ?></td>
							<td class="tac"><?= $xpeca_descricao; ?></td>
							<td class="tar"><?= $xdata_fabricacao; ?></td>
							<td class="tac"><?= $xdata_nf; ?></td>
							<td class="tac"><?= $xdata_abertura; ?></td>
							<td class="tac"><?= $xstatus_descricao; ?></td>
						</tr>
						<? $os_anterior = $xos;
					} ?>
				</tbody>
			</table>
		</div>
		<br />
		<? $jsonPOST = excelPostToJson($_POST); ?>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?= $jsonPOST; ?>' />
			<span><img src="imagens/excel.png" /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
	<? } else { ?>
		<div class="alert">
			<h4>Nenhum resultado encontrado para essa pesquisa.</h4>
		</div>
		<br />
	<? }
} ?>

<script type="text/javascript" charset="utf-8">
Shadowbox.init();
$.datepickerLoad(Array("data_final", "data_inicial"));

$("span[rel=lupa]").click(function () {
	$.lupa($(this));
});

$("select").select2();

<? if ($count > 50) { ?>
	$.dataTableLoad({ table: "#resultado_pesquisa" });
<? } ?>
</script>

<? include 'rodape.php'; ?>
