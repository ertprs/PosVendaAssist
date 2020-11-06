<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica == 35) {
	$array_campos_pesquisa = array(
		"tbl_os.sua_os"                    										=> "Número OS",
		"tbl_os.os_posto"                  										=> 'OS Interna',
		"tbl_hd_chamado_extra.hd_chamado"  										=> "Protocolo",
		"tbl_posto_fabrica.codigo_posto"   										=> "Código posto",
		"tbl_posto.nome"                   										=> "Razão Social",
		"tbl_posto.cnpj AS posto_cnpj"     										=> "CNPJ Posto",
		"tbl_posto_fabrica.contato_cidade AS posto_cidade "       				=> "Cidade Posto",
		"tbl_posto_fabrica.contato_estado AS posto_estado"        				=> "Estado Posto",
		"tbl_status_checkpoint.descricao as status_os"         					=> "Status OS",
		"tbl_marca.nome AS marca"          										=> 'Marca',
		"tbl_linha.nome AS linha"          										=> "Linha",
		"tipo_os"                       										=> "Tipo OS",
		"data_abertura"                         			                    => "Data Abertura OS",
		"finalizada"                                                            => "Data Finalização OS",
		"data_conserto"                                                         => "Data Conserto OS",
		"data_fechamento"                                                       => "Data Fechamento OS",
		"data_nf"                                                               => "Data Compra",
		"tipo_atendimento"                                                      => "Tipo Atendimento",
		"tbl_os.qtde_km"                                                        => 'Qtde KM',
		"tbl_os.qtde_visitas"            										=> 'Qtde Visitas',
		"tbl_os.qtde_km_calculada"   									        => 'Total KM',
		"ref_produto"                                                           => 'Referência do Produto',
		"desc_produto"                                                          => 'Descrição do produto',
		"tbl_os_produto.serie"                                                  => 'PO#',
		"tbl_os.aparencia_produto"  								            => "Aparência",
		"tbl_os.acessorios"             									    => "Acessórios",
		"defeito_reclamado"                                                     => 'Defeito Reclamado',
		"solucao_os"                                                            => 'Solução',
		"defeito_constatado"                                                    => 'Defeito Constatado',
		"tbl_os.consumidor_nome"    									        => 'Consumidor',
		"tbl_os.consumidor_celular"    											=> 'Consumidor Celular',
		"tbl_os.consumidor_cpf"          									    => 'CPF / CNPJ do consumidor',
		"tbl_os.consumidor_endereco"    									    => 'Consumidor Rua',
		"tbl_os.consumidor_numero"         										=> 'Consumidor Número',
		"tbl_os.consumidor_bairro"         										=> 'Consumidor Bairro',
		"tbl_os.consumidor_cep"            										=> 'Consumidor CEP',
		"tbl_os.consumidor_complemento"    										=> 'Consumidor Complemento',
		"tbl_os.consumidor_cidade"         										=> 'Consumidor Cidade',
		"tbl_os.consumidor_estado"         										=> 'Consumidor UF',
		"tbl_os.consumidor_fone"           										=> 'Consumidor Telefone',
		"tbl_os.consumidor_email"          										=> 'Consumidor Email',
		"tbl_os.revenda_nome"          										    => 'Revenda Razão Social',
		"tbl_os.revenda_cnpj"    										        => 'Revenda CNPJ',
		"tbl_os.nota_fiscal"          										    => "NF Compra",
		"data_nf"                                                               => "Data Compra",
		"ref_peca"                                                              => 'Referência Peça',
		"desc_peca"                                                             => 'Descrição Peça',
		"po_peca"                                                               => 'PO Peça',
		"data_digitacao_item"                                                   => 'Data Digitação Peça',
		"tbl_pedido_item.qtde"			 									    => 'Qtde Solicitada',
		"tbl_pedido_item.qtde_faturada"	                                        => 'Qtde Faturada',
		"qtde_pendente"	                                                        => 'Pendente',
		"tbl_os_item.pedido"        								            => 'Pedido',
		"data_pedido"                                                           => 'Data pedido',
		"nota_faturamento"                                                      => 'NF do Faturamento',
		"data_emissao"                                                          => 'Data Emissão Nota',
		"data_postagem"                                                         => 'Data Saída NF',
		"data_postagem" 	                                                    => 'Data Postagem',
		"data_entrega"                                                          => 'Data Entrega',
		"tbl_os_extra.extrato"		                                            => 'Extrato',
		"obs"                                                                   => 'Observação',
		"tbl_os.mao_de_obra"	                                                => 'M.O.',
		"defeito_peca"                                                          => "Defeito Peça",
		"tbl_faturamento.conhecimento"                                          => 'Número de Rastreamento',
		'tbl_admin.nome_completo'												=> 'Admin',
		'tbl_causa_troca.descricao'												=> 'Motivo da Troca'
	);

	#asort($array_campos_pesquisa);
}

if($_POST["btn_acao"] == "submit"){
	$data_inicial   	 = $_POST['data_inicial'];
	$data_final		     = $_POST['data_final'];
	$os				     = $_POST['os'];
	$codigo_posto        = $_POST['codigo_posto'];
	$descricao_posto     = $_POST['descricao_posto'];
	$tipo_data 			 = $_POST['tipo_data'];
	$situacao			 = $_POST['situacao'];
	$sem_diagnostico 	 = $_POST['sem_diagnostico'];
	$centro_distribuicao = $_POST['centro_distribuicao'];

	if(empty($os)){
		if (!strlen($data_inicial) or !strlen($data_final)) {
			$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
			$msg_erro["campos"][] = "data";
		} else {
			list($di, $mi, $yi) = explode("/", $data_inicial);
			list($df, $mf, $yf) = explode("/", $data_final);

			if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
				$msg_erro["msg"][]    = "Data Inválida";
				$msg_erro["campos"][] = "data";
			} else {

				$aux_data_inicial = "{$yi}-{$mi}-{$di}";
				$aux_data_final   = "{$yf}-{$mf}-{$df}";

				if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
					$msg_erro["msg"][]    = "Intervalo de pesquisa não pode ser no do que 6 meses.";
					$msg_erro["campos"][] = "data";
				}else{
					if(strlen($tipo_data) > 0){
						if($tipo_data == "extrato_geracao"){
							$cond = " AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
						}else if($tipo_data == "extrato_aprovacao"){
							$cond = " AND tbl_extrato.aprovado BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
						}else{
							$cond = " AND tbl_os.{$tipo_data} BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
						}
					}else{
						$cond = " AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";
					}
				}

			}


			if(isset($_POST["linha"])){
				if(count($linha)>0){
					$linha = $_POST["linha"];
					$cond .= " AND tbl_produto.linha IN(".implode(',',$linha).") ";

					if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_linha = " AND l.linha IN(".implode(',',$linha).") ";
				}
			}


			/**
			* @author William Castro <william.castro@telecontrol.com.br>
			*
			* hd-6262759  : filtro de combo com os serviços realizados da fabrica 
			* e Os sem defeito constatado cadastrado
			*/

			if ($sem_diagnostico > 0) {
				$cond = " AND tbl_os.defeito_constatado IS NULL";
			}

			if (isset($_POST['servico_realizado'])) {
			   $servico_realizado = $_POST['servico_realizado'];

			   if (count($servico_realizado) > 0 AND $login_fabrica != 35) {
			       $cond .= " AND (tbl_servico_realizado.servico_realizado IN(" . implode(",", $servico_realizado) . ") OR tbl_servico_realizado.servico_realizado IS NULL) ";
			   } else {
			   	   $cond .= " AND (tbl_servico_realizado.servico_realizado IN(" . implode(",", $servico_realizado) . ")) ";
			   }
			}
			

			if(strlen($_POST["familia"]) > 0){
				$cond .= " AND tbl_produto.familia = ".$_POST['familia'];

				if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_familia = " AND f.familia = ".$_POST['familia'];
			}

			if(strlen($_POST["estado"]) > 0){
				$cond .= " AND tbl_posto_fabrica.contato_estado = '".$_POST['estado']."' ";

				if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_estado = " AND jpf.contato_estado = '".$_POST['estado']."' ";
			}

			if(strlen($produto_referencia) > 0) {

				$sql = "SELECT produto
					from tbl_produto
					where tbl_produto.fabrica_i = $login_fabrica
					and tbl_produto.referencia = '$produto_referencia'";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$produto = pg_fetch_result($res,0 ,produto );
					$cond .= " AND tbl_os.produto = {$produto} ";

					if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_produto = " AND pd.produto = {$produto} ";
				}else{
					$msg_erro['msg'][] = "Produto não encontrado";
				}
			}

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
					$cond .= " AND tbl_os.posto = {$posto} ";

					if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_posto = " AND jpf.posto = {$posto} ";
				}
			} else if ($login_fabrica == 35) {
				$cond .= " AND tbl_os.posto <> 6359 ";
			}

			if(!empty($situacao)){

				if($situacao == "a"){
					$cond .= " AND tbl_os.data_fechamento IS NULL AND tbl_os.finalizada IS NULL ";
				}else if($situacao == "c"){
					$cond .= " AND tbl_os.data_fechamento IS NULL AND tbl_os.finalizada IS NULL AND tbl_os.data_conserto IS NOT NULL ";
				}else{
					$cond .= " AND tbl_os.data_fechamento IS NOT NULL AND tbl_os.finalizada IS NOT NULL ";
				}

			}
		}
	}

	if(count($msg_erro['msg']) == 0){

		if(!empty($os)){
			$cond = " AND tbl_os.os = {$os} ";

			if ($login_fabrica == 151 && $tipo_data == "extrato_geracao") $cond_os = " AND oe.os = {$os} ";
		}

		if($login_fabrica == 151 && $tipo_data == "extrato_geracao"){ /*HD - 6236027*/
			$sql = "
				SELECT 
				    x.os,
				    x.extrato,
				    x.sua_os,
				    CASE WHEN x.consumidor_revenda = 'R' THEN 'Revenda' ELSE 'Consumidor' END AS tipo_os,
				    x.consumidor_nome,
				    x.consumidor_fone,
				    x.consumidor_email,
				    TO_CHAR(x.data_abertura,'DD/MM/YYYY') AS data_abertura,
				    TO_CHAR(x.data_digitacao,'DD/MM/YYYY HH:MM') AS data_digitacao,
				    TO_CHAR(x.data_conserto,'DD/MM/YYYY HH:MM') AS data_conserto,
				    TO_CHAR(x.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
				    TO_CHAR(x.finalizada,'DD/MM/YYYY HH:MM') AS finalizada,
				    x.nota_fiscal,
				    TO_CHAR(x.data_nf,'DD/MM/YYYY') AS data_nf,
				    x.revenda_nome,
				    x.defeito_reclamado,
				    x.mao_de_obra,
				    x.codigo_posto,
				    x.nome,
				    x.posto_estado,
				    x.ref_produto,
				    x.desc_produto,
				    x.serie,
				    x.linha,
				    x.defeito_constatado,
				    x.os_bloqueada,
				    x.status_os,
				    x.pedido,
				    TO_CHAR(x.data_digitacao_pedido, 'DD/MM/YYYY HH:MM') AS data_digitacao_pedido,
				    x.ref_peca,
				    x.desc_peca,
				    x.servico_peca,
				    x.defeito_peca,
				    TO_CHAR(x.data_pedido,'DD/MM/YYYY') AS data_pedido,
				    x.qtde,
				    x.qtde_faturada,
				    TO_CHAR(x.data_emissao,'DD/MM/YYYY') AS data_emissao,
				    TO_CHAR(x.data_postagem,'DD/MM/YYYY') AS data_postagem,
				    TO_CHAR(x.data_entrega,'DD/MM/YYYY') AS data_entrega,
				    x.nota_faturamento,
				    x.conhecimento,
				    x.array_campos_adicionais,
				    (x.qtde - (x.qtde_faturada + x.qtde_cancelada)) AS qtde_pendente,
				    JSON_FIELD('nome_titular_nf', x.array_campos_adicionais) AS titular_nf, 
				    JSON_FIELD('cpf_titular_nf', x.array_campos_adicionais) AS cpf_titular_nota 
				FROM ( 
				    SELECT 
				        o.*,
				        pf.codigo_posto,
				        p.nome,
				        pf.contato_estado AS posto_estado,
				        pd.referencia AS ref_produto,
				        pd.descricao AS desc_produto,
				        op.serie,
				        l.nome AS linha,
				        dc.descricao AS defeito_constatado,
				        oce.os_bloqueada,
				        sc.descricao AS status_os,
				        oi.pedido,
				        oi.digitacao_item AS data_digitacao_pedido,
				        pc.referencia AS ref_peca,
				        pc.descricao AS desc_peca,
				        sr.descricao AS servico_peca,
				        dpc.descricao AS defeito_peca,
				        pdi.data AS data_pedido,
				        pditem.qtde,
				        pditem.qtde_faturada,
				        pditem.qtde_cancelada,
				        ft.emissao AS data_emissao,
				        ft.saida AS data_postagem,
				        ft.previsao_chegada AS data_entrega,
				        ft.nota_fiscal AS nota_faturamento,
				        ft.conhecimento,
				        hce.array_campos_adicionais
				    FROM (
				        SELECT 
				            oe.os,
				            oe.extrato,
				            o.sua_os,
				            o.consumidor_revenda,
				            o.consumidor_nome,
				            o.consumidor_fone,
				            o.consumidor_email,
				            o.data_abertura,
				            o.data_digitacao,
				            o.data_conserto,
				            o.data_fechamento,
				            o.finalizada,
				            o.nota_fiscal,
				            o.data_nf,
				            o.revenda_nome,
				            o.defeito_reclamado_descricao AS defeito_reclamado,
				            o.mao_de_obra,
				            o.posto,
				            o.status_checkpoint
				        FROM (
				            SELECT oe.os, e.extrato
				            FROM (
				                SELECT e.extrato
				                FROM tbl_extrato e
				                JOIN tbl_posto_fabrica jpf ON jpf.fabrica = $login_fabrica AND jpf.posto = e.posto
				                $cond_posto
				                $cond_estado
				                WHERE e.fabrica = $login_fabrica
				                AND (e.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59')
				            ) e
				            INNER JOIN tbl_os_extra oe ON oe.i_fabrica = $login_fabrica AND oe.extrato IS NOT NULL AND oe.extrato = e.extrato
				            $cond_os
				        ) oe
				        INNER JOIN tbl_os o ON o.os = oe.os AND o.excluida IS NOT TRUE
				    ) o
				    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
				    INNER JOIN tbl_posto p ON p.posto = pf.posto
				    INNER JOIN tbl_os_produto op ON op.os = o.os
				    INNER JOIN tbl_produto pd ON pd.fabrica_i = $login_fabrica AND pd.produto = op.produto
				    INNER JOIN tbl_linha l ON l.fabrica = $login_fabrica AND l.linha = pd.linha
				    INNER JOIN tbl_familia f ON f.fabrica = $login_fabrica AND f.familia = pd.familia
				    LEFT JOIN tbl_defeito_constatado dc ON dc.fabrica = $login_fabrica AND dc.defeito_constatado = op.defeito_constatado
				    LEFT JOIN tbl_os_campo_extra oce ON oce.fabrica = $login_fabrica AND oce.os = o.os
				    INNER JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
				    LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
				    LEFT JOIN tbl_peca pc ON pc.fabrica = $login_fabrica AND pc.peca = oi.peca
				    LEFT JOIN tbl_servico_realizado sr ON sr.fabrica = $login_fabrica AND sr.servico_realizado = oi.servico_realizado
				    LEFT JOIN tbl_defeito dpc ON dpc.fabrica = $login_fabrica AND dpc.defeito = oi.defeito
				    LEFT JOIN tbl_pedido pdi ON pdi.fabrica = $login_fabrica AND pdi.pedido = oi.pedido
				    LEFT JOIN tbl_pedido_item pditem ON pditem.pedido = pdi.pedido AND pditem.pedido_item = oi.pedido_item
				    LEFT JOIN tbl_faturamento_item ftitem ON ftitem.pedido = pditem.pedido AND ftitem.pedido_item = pditem.pedido_item
				    LEFT JOIN tbl_faturamento ft ON ft.fabrica = $login_fabrica AND ft.faturamento = ftitem.faturamento
				    LEFT JOIN tbl_hd_chamado_item hci ON hci.os = o.os
				    LEFT JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hci.hd_chamado
				    $cond_linha
				    $cond_familia
				    $cond_produto
				) x
			";
		} else {
			if($login_fabrica == 151 ){ /*HD - 6177097*/
			    $left_campos_extra = " LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os and tbl_os_campo_extra.fabrica = $login_fabrica ";
			    $left_campos_extra .= " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.os = tbl_os.os ";
			    $left_campos_extra .= " LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado ";
			    $campo_os_bloqueada = " tbl_os_campo_extra.os_bloqueada,  ";
			    $campoTitular       = " ,JSON_FIELD('nome_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS titular_nf, JSON_FIELD('cpf_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS cpf_titular_nota ";
			    if($centro_distribuicao != "mk_vazio"){
					$campo_p_adicionais = "
					, tbl_produto.parametros_adicionais::json->>'centro_distribuicao' AS centro_distribuicao";
				    $p_adicionais = " AND tbl_produto.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
				}
			}
			if ($login_fabrica == 35) {
				$left_campo_solucao = " LEFT JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica ";
				$campo_solucao = " tbl_solucao.descricao AS solucao_os, ";
			}
			
			if($novaTelaOs) {
					$joinDC = " LEFT JOIN    tbl_defeito_constatado  ON  tbl_defeito_constatado.defeito_constatado   = tbl_os_produto.defeito_constatado
	                                            AND tbl_defeito_constatado.fabrica              = $login_fabrica " ;

			}else{
					$joinDC = " LEFT JOIN    tbl_defeito_constatado  ON  tbl_defeito_constatado.defeito_constatado   = tbl_os.defeito_constatado
	                                            AND tbl_defeito_constatado.fabrica              = $login_fabrica " ;
			}

			$joins = "LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					  LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					  LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
					  LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica";

			if ($login_fabrica == 35) {
				$joinDC = " LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado 
	            AND tbl_defeito_constatado.fabrica = {$login_fabrica} " ;

	            $joins = "JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				  	JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
				  	LEFT JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
	             	LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
	             	LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
				  	LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os = tbl_os.os
				  	LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
				  	LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os.fabrica = {$login_fabrica}
					LEFT JOIN tbl_admin ON tbl_os_troca.admin = tbl_admin.admin
					LEFT JOIN tbl_causa_troca ON tbl_os_troca.causa_troca = tbl_causa_troca.causa_troca";
				
			$camposCadence = ",tbl_hd_chamado_extra.hd_chamado,
					tbl_marca.nome AS marca, 
					tbl_tipo_atendimento.descricao AS tipo_atendimento,
					JSON_FIELD('po_peca',tbl_os_item.parametros_adicionais) AS po_peca ";
			}

			$sql = "
	            SELECT  tbl_os.os,
			    tbl_os.sua_os,
			    tbl_os.os_posto,
	                    CASE
	                        WHEN tbl_os.consumidor_revenda = 'R' THEN
	                            'Revenda'
	                        ELSE
	                            'Consumidor'
	                    END AS tipo_os,
	                    tbl_os_extra.extrato,
	                    tbl_os.consumidor_nome,
			    tbl_os.consumidor_fone,
			    tbl_os.consumidor_celular,
			    tbl_os.consumidor_cpf,
			    tbl_os.consumidor_endereco,
			    tbl_os.consumidor_numero,
			    tbl_os.consumidor_bairro,
			    tbl_os.consumidor_cep,
			    tbl_os.consumidor_complemento,
			    tbl_os.consumidor_cidade,
			    tbl_os.consumidor_estado,
	                    tbl_os.consumidor_email,
	                    to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
	                    to_char(tbl_os.data_digitacao,'DD/MM/YYYY HH:MM') AS data_digitacao,
	                    to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
	                    to_char(tbl_os.data_conserto,'DD/MM/YYYY HH:MM') AS data_conserto,
	                    to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
	                    to_char(tbl_os.finalizada,'DD/MM/YYYY HH:MM') AS finalizada,
	                    tbl_produto.referencia AS ref_produto,
	                    tbl_produto.descricao AS desc_produto,
	                    tbl_os_produto.serie,
	                    tbl_os.nota_fiscal,
	                    to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
			    tbl_os.revenda_nome,
			    tbl_os.revenda_cnpj,
			    tbl_os.qtde_km,
			    tbl_os.qtde_km_calculada,
			    tbl_os.qtde_visitas,
			    tbl_os.obs,
	                    tbl_posto_fabrica.codigo_posto,
			    tbl_posto.nome,
			    tbl_posto.cnpj AS posto_cnpj,
	                    tbl_os.defeito_reclamado_descricao AS defeito_reclamado,
	                    tbl_defeito_constatado.descricao AS defeito_constatado,
	                    tbl_linha.nome AS linha,
	                    tbl_peca.referencia AS ref_peca,
	                    tbl_peca.descricao AS desc_peca,
	                    tbl_defeito.descricao AS defeito_peca,
	                    tbl_servico_realizado.descricao AS servico_peca,
			    tbl_os.mao_de_obra,
			    tbl_os.aparencia_produto,
			    tbl_os.acessorios,
	                    tbl_pedido_item.qtde,
	                    $campo_os_bloqueada
	                    $campo_solucao
	                    tbl_pedido_item.qtde_faturada,
	                    (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente,
	                    to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS data_emissao,
	                    to_char(tbl_faturamento.saida,'DD/MM/YYYY') AS data_postagem,
	                    to_char(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS data_entrega,
	                    tbl_faturamento.nota_fiscal AS nota_faturamento,
	                    tbl_faturamento.conhecimento,
	                    tbl_os_item.pedido,
	                    TO_CHAR(tbl_os_item.digitacao_item, 'DD/MM/YYYY HH:MM') AS data_digitacao_item,
			    tbl_status_checkpoint.descricao AS status_os,
			    tbl_posto_fabrica.contato_cidade AS posto_cidade,
	            tbl_posto_fabrica.contato_estado AS posto_estado
				{$campoTitular}
				{$campoCadence}
				{$campo_p_adicionais}				
	            FROM    tbl_os
	            JOIN    tbl_os_extra            ON  tbl_os_extra.os                             = tbl_os.os
	                                            AND tbl_os_extra.i_fabrica                      = $login_fabrica
	            JOIN    tbl_status_checkpoint   ON  tbl_status_checkpoint.status_checkpoint     = tbl_os.status_checkpoint
	            JOIN    tbl_posto               ON  tbl_posto.posto                             = tbl_os.posto
	            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                     = tbl_posto.posto
	                                            AND tbl_posto_fabrica.fabrica                   = $login_fabrica
				$joins 
	       		LEFT JOIN    tbl_linha               ON  tbl_produto.linha                           = tbl_linha.linha
	                                            AND tbl_linha.fabrica                           = $login_fabrica
	    		$left_campos_extra
	    		$left_campo_solucao
				$joinDC
		        LEFT JOIN    tbl_servico_realizado   ON  tbl_servico_realizado.servico_realizado     = tbl_os_item.servico_realizado
		                                            AND tbl_servico_realizado.fabrica               = $login_fabrica
		        LEFT JOIN    tbl_defeito             ON  tbl_os_item.defeito                         = tbl_defeito.defeito
		                                            AND tbl_defeito.fabrica                         = $login_fabrica
		        LEFT JOIN    tbl_pedido_item         ON  tbl_pedido_item.pedido_item                 = tbl_os_item.pedido_item
		        LEFT JOIN    tbl_pedido              ON  tbl_pedido_item.pedido                      = tbl_pedido.pedido
		                                            AND tbl_pedido.fabrica                          = $login_fabrica
		        LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.pedido                 = tbl_pedido.pedido
		                                            AND tbl_faturamento_item.peca                   = tbl_os_item.peca
		        LEFT JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento                 = tbl_faturamento_item.faturamento
		                                            AND tbl_faturamento.fabrica                     = $login_fabrica
		        LEFT JOIN    tbl_extrato             ON  tbl_os_extra.extrato                        = tbl_extrato.extrato
	                                            AND tbl_extrato.fabrica                         = $login_fabrica
	            WHERE   tbl_os.fabrica  = $login_fabrica
	            AND     tbl_os.excluida IS NOT TRUE
				$cond
				$p_adicionais";
			
			if ($login_fabrica == 35) {		

				$campos_pesquisa = $_POST['campos_pesquisa'];
				$sql = '';

				foreach ($array_campos_pesquisa as $campo => $value) {
					if (count($campos_pesquisa) && !in_array($campo, $campos_pesquisa)) {
						continue;
					}

					if (!empty($campos_cadence)) { $campos_cadence .= ','; }

					if ($campo == 'tipo_os') {	
						$campo = "CASE
										WHEN tbl_os.consumidor_revenda = 'R' THEN
											'Revenda'
										ELSE
											'Consumidor'
									END AS tipo_os ";
					}elseif ($campo == 'data_abertura') {
						$campo = "TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura";
					}elseif ($campo == 'finalizada') {
						$campo = "TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY') AS finalizada";
					}elseif ($campo == 'data_conserto') {
						$campo = "TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY') AS data_conserto";
					}elseif ($campo == 'data_fechamento') {
						$campo = "to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento";
					}elseif ($campo == 'tbl_os.data_nf') {
						$campo = "TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf";
					}elseif ($campo == 'tipo_atendimento') {
						$campo = "tbl_tipo_atendimento.descricao AS tipo_atendimento";
					}elseif ($campo == 'ref_produto') {
						$campo = "tbl_produto.referencia AS ref_produto";					
					}elseif ($campo == 'desc_produto') {
						$campo = "tbl_produto.descricao AS desc_produto";
					}elseif ($campo == 'defeito_reclamado') {
						$campo = "tbl_os.defeito_reclamado_descricao AS defeito_reclamado";
					}elseif ($campo == 'solucao_os') {
						$campo = "tbl_solucao.descricao AS solucao_os";
					}elseif ($campo == 'defeito_constatado') {
						$campo = "tbl_defeito_constatado.descricao AS defeito_constatado";
					}elseif ($campo == 'data_nf') {
						$campo = "to_char(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf";
					}elseif ($campo == 'ref_peca') {
						$campo = "tbl_peca.referencia AS ref_peca";
					}elseif ($campo == 'desc_peca') {
						$campo = "tbl_peca.descricao AS desc_peca";
					}elseif ($campo == 'po_peca') {
						$campo = "JSON_FIELD('po_peca',tbl_os_item.parametros_adicionais) AS po_peca";
					}elseif ($campo == 'data_digitacao_item') {
						$campo = "TO_CHAR(tbl_os_item.digitacao_item, 'DD/MM/YYYY HH:MM') AS data_digitacao_item";
					}elseif ($campo == 'qtde_pendente') {
						$campo = "(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS qtde_pendente";
					}elseif ($campo == 'data_pedido') {
						$campo = "to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido";
					}elseif ($campo == 'nota_faturamento') {
						$campo = "tbl_faturamento.nota_fiscal AS nota_faturamento";
					}elseif ($campo == 'data_emissao') {
						$campo = "to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS data_emissao";
					}elseif ($campo == 'data_postagem') {
						$campo = "to_char(tbl_faturamento.saida,'DD/MM/YYYY') AS data_postagem";
					}elseif ($campo == 'data_entrega') {
						$campo = "to_char(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS data_entrega";
					}elseif ($campo == 'defeito_peca') {
						$campo = "tbl_defeito.descricao AS defeito_peca";
					}elseif ($campo == 'obs') {
						$campo = "regexp_replace(tbl_os.obs, E'[\\n\\r]+', ' ', 'g' ) AS obs";
					}

					$campos_cadence .= $campo;
				}

				$sql = "
					SELECT $campos_cadence
					FROM    tbl_os
		            JOIN    tbl_os_extra            ON  tbl_os_extra.os                             = tbl_os.os
		                                            AND tbl_os_extra.i_fabrica                      = $login_fabrica
		            JOIN    tbl_status_checkpoint   ON  tbl_status_checkpoint.status_checkpoint     = tbl_os.status_checkpoint
		            JOIN    tbl_posto               ON  tbl_posto.posto                             = tbl_os.posto
		            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                     = tbl_posto.posto
		                                            AND tbl_posto_fabrica.fabrica                   = $login_fabrica
					$joins 
		       		LEFT JOIN    tbl_linha               ON  tbl_produto.linha                           = tbl_linha.linha
		                                            AND tbl_linha.fabrica                           = $login_fabrica
		    		$left_campos_extra
		    		$left_campo_solucao
					$joinDC
			        LEFT JOIN    tbl_servico_realizado   ON  tbl_servico_realizado.servico_realizado     = tbl_os_item.servico_realizado
			                                            AND tbl_servico_realizado.fabrica               = $login_fabrica
			        LEFT JOIN    tbl_defeito             ON  tbl_os_item.defeito                         = tbl_defeito.defeito
			                                            AND tbl_defeito.fabrica                         = $login_fabrica
			        LEFT JOIN    tbl_pedido_item         ON  tbl_pedido_item.pedido_item                 = tbl_os_item.pedido_item
			        LEFT JOIN    tbl_pedido              ON  tbl_pedido_item.pedido                      = tbl_pedido.pedido
			                                            AND tbl_pedido.fabrica                          = $login_fabrica
			        LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.pedido                 = tbl_pedido.pedido
			                                            AND tbl_faturamento_item.peca                   = tbl_os_item.peca
			        LEFT JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento                 = tbl_faturamento_item.faturamento
			                                            AND tbl_faturamento.fabrica                     = $login_fabrica
			        LEFT JOIN    tbl_extrato             ON  tbl_os_extra.extrato                        = tbl_extrato.extrato
													AND tbl_extrato.fabrica                         = $login_fabrica
		            WHERE   tbl_os.fabrica  = $login_fabrica
		            AND     tbl_os.excluida IS NOT TRUE
					$cond";

			}
		}

		if (!$_POST["gerar_excel"]) {
			$sql .= " limit 500 ";
		}

		//die(nl2br($sql));

		$resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);
        $count = ($count <= 500) ? $count : 500;
		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {

				if ($login_fabrica == 35) {
					$data = date("d-m-Y-H:i");

					$fileName = "relatorio_visa-geral-os-{$login_fabrica}-{$data}.csv";

					$file = fopen("/tmp/{$fileName}", "w");

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

					if (file_exists("/tmp/{$fileName}")) {
						system("mv /tmp/{$fileName} xls/{$fileName}");

						echo "xls/{$fileName}";
					}
					exit;
				}


				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_visa-geral-os-{$login_fabrica}-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");

	            $thead = "OS;Pedido;Tipo de OS;Status OS;Extrato;Consumidor;Consumidor Fone;";
	            if (in_array($login_fabrica,array(35,151))) {
	                 $thead .= "Consumidor Email;";
	            }

	             $thead .= "Data Abertura OS;Data digitação;";

	            if ($login_fabrica == 35) {
	                $thead .= "Data Digitação Pedido;";
	            }
		
		    	$coluna_consertado_finalizado = ($login_fabrica == 35) ? "Finalizado" : "Data Consertado";
		   		$coluna_servico_solucao       = ($login_fabrica == 35) ? "Solução"    : "Serviço"; /*HD - 6056809*/

	    		$campoTitular = "";

	            if ($login_fabrica == 151) {
	                $campoTitular = "Titular da NF;CPF do Titular;";
	                $campo_cd = "Centro Distribuicao";
	            }

	            $thead .= "Data Pedido;Data Emissão Nota;Data Saida NF;NF;{$campoTitular}Qtde Solicitada;Qtde Faturada;Pendente;Data Postagem;Data Entrega;$coluna_consertado_finalizado;Data Fechamento;Referência;Produto;Série;Nº NF;Data NF;Revenda;Código Posto;Posto;UF;Defeito reclamado;Defeito Constatado;$coluna_servico_solucao;Linha;Codigo Componente;Componente;Item Peça;Defeito Peça;M.O;Numero Rastreamento1;Numero Rastreamento2;Numero Rastreamento3;Numero Rastreamento4;Numero Rastreamento5;Numero Rastreamento6;Numero Rastreamento7;Numero Rastreamento8;$campo_cd\n";
	    
				fwrite($file, $thead);

				$contador_resSubmit = pg_num_rows($resSubmit);

				for($j = 0; $j < $contador_resSubmit; $j++){

					$xos                    = pg_fetch_result($resSubmit, $j, 'os');
					$xsua_os                = pg_fetch_result($resSubmit, $j, 'sua_os');
					$os_posto				= pg_fetch_result($resSubmit, $j, 'os_posto');
					$tipo_os                = pg_fetch_result($resSubmit, $j, 'tipo_os');
					$extrato                = pg_fetch_result($resSubmit, $j, 'extrato');
					$consumidor_nome        = pg_fetch_result($resSubmit, $j, 'consumidor_nome');
					$consumidor_fone        = pg_fetch_result($resSubmit, $j, 'consumidor_fone');
					$consumidor_celular     = pg_fetch_result($resSubmit, $j, 'consumidor_celular');
					$consumidor_cpf         = pg_fetch_result($resSubmit, $j, 'consumidor_cpf');
					$consumidor_endereco    = pg_fetch_result($resSubmit, $j, 'consumidor_endereco');
					$consumidor_numero      = pg_fetch_result($resSubmit, $j, 'consumidor_numero');
					$consumidor_bairro      = pg_fetch_result($resSubmit, $j, 'consumidor_bairro');
					$consumidor_cep         = pg_fetch_result($resSubmit, $j, 'consumidor_cep');
					$consumidor_complemento = pg_fetch_result($resSubmit, $j, 'consumidor_complemento');
					$consumidor_cidade      = pg_fetch_result($resSubmit, $j, 'consumidor_cidade');
					$consumidor_estado      = pg_fetch_result($resSubmit, $j, 'consumidor_estado');
					$consumidor_email       = pg_fetch_result($resSubmit, $j, 'consumidor_email');
					$data_abertura          = pg_fetch_result($resSubmit, $j, 'data_abertura');
					$data_digitacao         = pg_fetch_result($resSubmit, $j, 'data_digitacao');
					$data_digitacao_pedido  = pg_fetch_result($resSubmit, $j, 'data_digitacao_item');
					$data_pedido            = pg_fetch_result($resSubmit, $j, 'data_pedido');
					$data_conserto          = pg_fetch_result($resSubmit, $j, 'data_conserto');
					$data_fechamento        = pg_fetch_result($resSubmit, $j, 'data_fechamento');
					$finalizada             = pg_fetch_result($resSubmit, $j, 'finalizada');
					$ref_produto            = pg_fetch_result($resSubmit, $j, 'ref_produto');
					$desc_produto           = pg_fetch_result($resSubmit, $j, 'desc_produto');
					$serie                  = pg_fetch_result($resSubmit, $j, 'serie');
					$data_nf                = pg_fetch_result($resSubmit, $j, 'data_nf');
					$nota_fiscal            = pg_fetch_result($resSubmit, $j, 'nota_fiscal');
					$revenda_nome           = pg_fetch_result($resSubmit, $j, 'revenda_nome');
					$revenda_cnpj           = pg_fetch_result($resSubmit, $j, 'revenda_cnpj');
					$defeito_reclamado      = pg_fetch_result($resSubmit, $j, 'defeito_reclamado');
					$defeito_constatado     = pg_fetch_result($resSubmit, $j, 'defeito_constatado');
					$linha                  = pg_fetch_result($resSubmit, $j, 'linha');
					$xcodigo_posto           = pg_fetch_result($resSubmit, $j, 'codigo_posto');
					$posto_nome             = pg_fetch_result($resSubmit, $j, 'nome');
					$ref_peca               = pg_fetch_result($resSubmit, $j, 'ref_peca');
					$desc_peca              = pg_fetch_result($resSubmit, $j, 'desc_peca');
					$defeito_peca           = pg_fetch_result($resSubmit, $j, 'defeito_peca');
					$servico_peca           = pg_fetch_result($resSubmit, $j, 'servico_peca');
					$mao_de_obra            = pg_fetch_result($resSubmit, $j, 'mao_de_obra');
					$qtde_item              = pg_fetch_result($resSubmit, $j, 'qtde');
					$qtde_faturada          = pg_fetch_result($resSubmit, $j, 'qtde_faturada');
					$qtde_pendente          = pg_fetch_result($resSubmit, $j, 'qtde_pendente');
					$nota_faturamento       = pg_fetch_result($resSubmit, $j, 'nota_faturamento');
					$data_emissao           = pg_fetch_result($resSubmit, $j, 'data_emissao');
					$data_postagem          = pg_fetch_result($resSubmit, $j, 'data_postagem');
					$data_entrega           = pg_fetch_result($resSubmit, $j, 'data_entrega');
					$conhecimento           = pg_fetch_result($resSubmit, $j, 'conhecimento');
					$pedido			= pg_fetch_result($resSubmit, $j, 'pedido');
					$status_os		= pg_fetch_result($resSubmit, $j, 'status_os');
					$posto_estado	    	= pg_fetch_result($resSubmit, $j, 'posto_estado');
					$posto_cidade           = pg_fetch_result($resSubmit, $j, 'posto_cidade');
					$posto_cnpj             = pg_fetch_result($resSubmit, $j, 'posto_cnpj');
					$qtde_km                = pg_fetch_result($resSubmit, $j, 'qtde_km');
					$qtde_visitas           = pg_fetch_result($resSubmit, $j, 'qtde_visitas');
					$total_km               = pg_fetch_result($resSubmit, $j, 'qtde_km_calculada');
					$aparencia_produto      = pg_fetch_result($resSubmit, $j, 'aparencia_produto');
					$acessorios             = pg_fetch_result($resSubmit, $j, 'acessorios');
					$obs	                = pg_fetch_result($resSubmit, $j, 'obs');

					if ($login_fabrica == 35) { /* HD - 6056809*/
						unset($solucao_os);

						$solucao_os = trim(pg_fetch_result($resSubmit, $j, 'solucao_os'));

						if (strlen($solucao_os) > 0) {
							$servico_peca = $solucao_os;
						} else {
							$servico_peca = "";
						}

						$protocolo = trim(pg_fetch_result($resSubmit, $j, 'hd_chamado'));
						$marca     = trim(pg_fetch_result($resSubmit, $j, 'marca'));
						$tipo_atendimento     = trim(pg_fetch_result($resSubmit, $j, 'tipo_atendimento'));
						$po_peca   = trim(pg_fetch_result($resSubmit, $j, 'po_peca'));
					}
					

					if($login_fabrica == 151){
						$os_bloqueada       	= pg_fetch_result($resSubmit, $i, 'os_bloqueada');

						if($os_bloqueada == 't' and empty($finalizada)){
							$os_bloqueada= "Congelada";
						}elseif($os_bloqueada != 't' and empty($finalizada)){
							$os_bloqueada= "Descongelada";
						}
					}else{
						$os_bloqueada = $status_os;
					}

					if($xos != $os_anterior){
						$item = 1;
					}else{
						$item++;
					}

					$os_anterior = $xos;

					$tbody = "$xsua_os;$pedido;$tipo_os;$os_bloqueada;$extrato;$consumidor_nome;$consumidor_fone;";

					if (in_array($login_fabrica, array(35,151))) {
                        $tbody .= "$consumidor_email;";
					}

					$tbody .= "$data_abertura;$data_digitacao;";

					if ($login_fabrica == 35) {
			 			$tbody .= "$data_digitacao_pedido;";
					}

					$data_conserto = ($login_fabrica == 35) ? $finalizada : $data_conserto;

					$valTitular = "";

					if ($login_fabrica == 151) {
                        $titular_nf       = pg_fetch_result($resSubmit,$j, "titular_nf");
                        $cpf_titular_nota = pg_fetch_result($resSubmit,$j, "cpf_titular_nota");

                        $valTitular = "$titular_nf;$cpf_titular_nota;";

	                    $parametros_adicionais = pg_fetch_result($resSubmit, $j, "centro_distribuicao");

	             		$cd = json_decode($parametros_adicionais);
	             		
						if($parametros_adicionais == "mk_nordeste"){
							$campo_p_adicionais = ";MK Nordeste";
						}else if($parametros_adicionais == "mk_sul") {
							$campo_p_adicionais = ";MK Sul";	
						} else{
							$campo_p_adicionais = ";";	
						}
                    }

					$tbody .= "$data_pedido;$data_emissao;$data_postagem;$nota_faturamento;{$valTitular}$qtde_item;$qtde_faturada;$qtde_pendente;$data_postagem;$data_entrega;$data_conserto;$data_fechamento;$ref_produto;$desc_produto;$serie;$nota_fiscal;$data_nf;$revenda_nome;$xcodigo_posto;$posto_nome;$posto_estado;$defeito_reclamado;$defeito_constatado;$servico_peca;$linha;$ref_peca;$desc_peca;$item;$defeito_peca;$mao_de_obra";

					if(strlen($conhecimento) == 0){
						$codigo_rastreio = array();
					}else if (preg_match("/^\[.+\]$/", $conhecimento)) {
						$codigo_rastreio = json_decode($conhecimento,true);
					}else{
						$codigo_rastreio = array();
						$codigo_rastreio[] = $conhecimento;
					}

					for($x = 0; $x < 8; $x++){
						$tbody .= ";".$codigo_rastreio[$x];
					}

					if($login_fabrica == 151){
						$tbody .= $campo_p_adicionais;
					}

					$tbody .= "\n";

					fwrite($file, $tbody);
				}

				
				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					if($login_fabrica == 151) {
						echo "<script>window.open('xls/{$fileName}')</script>";
					}else{
						echo "xls/{$fileName}";
						exit;
					}
				}

			}

		}
	}
}

$layout_menu = "callcenter";
$title= "RELATÓRIO DE VISÃO GERAL DE ORDEM DE SERVIÇO";
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"shadowbox",
	"multiselect"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		var fab = '<?=$login_fabrica?>';
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "posto"), Array("produto", "posto"), null, "../");
		Shadowbox.init();

		if (fab = 35) {
			$("#campos_pesquisa").multiselect({
	        	selectedText: "selecionados # de #",
	        });
		}

		$("#linha").multiselect({
		      	selectedText: "selecionados # de #"
		});

	
		$(".servico_realizado").multiselect({
	      	selectedText: "selecionados # de #"
		});
		

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function retorna_produto (retorno) {
		$("#produto").val(retorno.produto);
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
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

$spn = (in_array($login_fabrica, array(35,151))) ? "span4" : "span8"; 

?>

	<div class="row">
		<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'>Parametros de Pesquisa </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='atendente'>OS</label>
					<div class='controls controls-row'>
						<div class='span6'>
							<input type="text" name="os" id="os" class='span12' value= "<?=$os?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
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
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12 text-center' value= "<?=$data_final?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				Data de Referência
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="data_digitacao" <?if($tipo_data=="data_digitacao") echo "checked";?>>
			        Digitação
			    </label>
			</div>
			<div class='span3'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="data_abertura" <?if($tipo_data=="data_abertura") echo "checked";?>>
			        Abertura
			    </label>
			</div>
			<div class='span3'>
					<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="data_fechamento" <?if($tipo_data=="data_fechamento" or $tipo_data=="") echo "checked";?> >
			        Fechamento
			    </label>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="finalizada" <?if($tipo_data=="finalizada") echo "checked";?> >
			        Finalizada
			    </label>
			</div>
			<div class='span3'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_geracao" <?if($tipo_data=="extrato_geracao") echo "checked";?>>
			        Geração de Extrato
			    </label>
			</div>
			<div class='span3'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="extrato_aprovacao" <?if($tipo_data=="extrato_aprovacao") echo "checked";?>>
			        Aprovação do Extrato
			    </label>
			</div>
			<div class='span2'></div>
		</div>

		<?php

			/**
			* @author William Castro <william.castro@telecontrol.com.br>
			*
			* hd-6262759  : filtro OS sem diagnostico
			*
			*/
		?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'>
				<label class="radio">
			        <input type="radio" name="tipo_data" id="optionsRadios1" value="sem_diagnostico" <?if($tipo_data=="sem_diagnostico") echo "checked";?> >
			        OS sem diagnostico
			    </label>
			</div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='linha'>Linha</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?php
							$sql_linha = "SELECT
							linha,
							nome
							FROM tbl_linha
							WHERE tbl_linha.fabrica = $login_fabrica
							ORDER BY tbl_linha.nome ";
							$res_linha = pg_query($con, $sql_linha); ?>
							<select name="linha[]" id="linha" multiple="multiple" class='span12'>
							<?php

							$selected_linha = array();
							foreach (pg_fetch_all($res_linha) as $key) {
								if(isset($linha)){
									foreach ($linha as $id) {
										if ( isset($linha) && ($id == $key['linha']) ){
											$selected_linha[] = $id;
										}
									}
								} ?>

								<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

								<?php echo $key['nome']?>

								</option>
							<?php } ?>
							</select>

						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='familia'>Família</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="familia" id="familia">
							<?
							$sql = "SELECT  *
								FROM    tbl_familia
								WHERE   tbl_familia.fabrica = $login_fabrica
								ORDER BY tbl_familia.descricao;";
							$res = pg_query ($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<option value=''>ESCOLHA</option>\n";								
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_familia   = trim(pg_fetch_result($res,$x,familia));
									$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

									echo "<option value='$aux_familia'";
									if ($familia == $aux_familia){
										echo " SELECTED ";
										$mostraMsgLinha = "<br> da FAMÍLIA $aux_descricao";
									}
									echo ">$aux_descricao</option>\n";
								}
							}
							?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php

			/**
			* @author William Castro <william.castro@telecontrol.com.br>
			*
			* hd-6262759  : filtro de combo com os serviços realizados da fabrica
			*
			*/
		?>

       <div class="row-fluid">
           <div class='span2'></div>
           <div class='span4'>
               <div class='control-group'>
                   <label class='control-label' for='servico_realizado'>Serviço Realizado</label>
                   <div class='controls controls-row'>
                       <div class='span12'>
        
                   		<?php
                   			if($login_fabrica == 35){
								$sqlServicos = "SELECT servico_realizado, descricao 
	                                            FROM tbl_servico_realizado
	                                            WHERE fabrica = {$login_fabrica}
	                                            AND servico_realizado NOT IN (11332)";
                   			} else {
								$sqlServicos = "SELECT servico_realizado, descricao 
	                                            FROM tbl_servico_realizado
	                                            WHERE fabrica = {$login_fabrica}";
	                        }

							$resultadoServicos = pg_query($con, $sqlServicos); 
						?>
							<select name="servico_realizado[]" id="servico_realizado" multiple="multiple" class='span12 servico_realizado'>
						<?php

							$servicosSelecionados = array();

							foreach (pg_fetch_all($resultadoServicos) as $key) {

								if(isset($servico_realizado)) {

									foreach ($servico_realizado as $id) {
										if ( isset($servico_realizado) && ($id == $key['servico_realizado']) ){
											$servicosSelecionados[] = $id;
										}
									}
								} 
						?>
								<option value="<?php echo $key['servico_realizado']?>" <?php if( in_array($key['servico_realizado'], $servicosSelecionados)) echo "SELECTED"; ?> >

									<?php echo $key['descricao']?>

								</option>

						<?php } ?>

							</select>

 						
                           
                     
                       </div>
                   </div>
               </div>
           </div>
           <div class='span2'></div>
       </div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span10 input-append'>
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
				<div class='control-group'>
					<label class='control-label' for='pais'>País</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="pais" id="pais">
								<?
								$sql = "SELECT  *
										FROM    tbl_pais
										where america_latina is TRUE
										ORDER BY tbl_pais.nome;";
								$res = pg_query ($con,$sql);

								if (pg_num_rows($res) > 0) {
									if(strlen($pais) == 0 ) $pais = 'BR';

									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_pais  = trim(pg_fetch_result($res,$x,pais));
										$aux_nome  = trim(pg_fetch_result($res,$x,nome));

										echo "<option value='$aux_pais'";
										if ($pais == $aux_pais){
											echo " SELECTED ";
											$mostraMsgPais = "<br> do PAÍS $aux_nome";
										}
										echo ">$aux_nome</option>\n";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='estado'>Por Região</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select name="estado" id="estado">
								<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS OS ESTADOS</option>
								<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
								<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
								<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
								<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
								<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
								<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
								<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
								<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
								<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
								<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
								<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
								<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
								<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
								<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
								<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
								<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
								<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
								<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
								<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
								<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
								<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
								<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
								<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
								<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
								<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
								<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
								<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
							</select>
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
                                         <label class='control-label' for='codigo_posto'>Código Posto</label>
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
                                         <label class='control-label' for='descricao_posto'>Nome Posto</label>
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
		    <div class='<?=$spn?>'>		    	
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
			    <label class='control-label' for='data_inicial'>Situação</label>
			    <div class='controls controls-row'>
				<div class='span4'>
				    <select name="situacao" id="situacao">
					<option value=""></option>
					<option value="a" <?php if($situacao == 'a') echo "SELECTED"; ?>>Abertas</option>
					<option value="c" <?php if($situacao == 'c') echo "SELECTED"; ?>>Consertadas</option>
					<option value="f" <?php if($situacao == 'f') echo "SELECTED"; ?>>Fechadas</option>
                                     </select>
		                </div>
		            </div>
			</div>
		    </div>

			<?php if($login_fabrica == 151){ ?>	                                    
	            <div class='span4'>
	                <div class='control-group'>
	                    <label class='control-label' for='centro_distribuicao'>Centro Distribuição</label>
	                    <div class='controls controls-row'>
	                        <div class='span12 input-append'>
	                            <select name="centro_distribuicao" id="centro_distribuicao">
	                                <option value="mk_vazio" name="mk_vazio" <?php echo ($centro_distribuicao == "mk_vazio") ? "SELECTED" : ""; ?>>ESCOLHA</option>
	                                <option value="mk_nordeste" name="mk_nordeste" <?php echo ($centro_distribuicao == "mk_nordeste") ? "SELECTED" : ""; ?>>MK Nordeste</option>
	                                <option value="mk_sul" name="mk_sul" <?php echo ($centro_distribuicao == "mk_sul") ? "SELECTED" : ""; ?>>MK Sul</option>    
	                            </select>
	                        </div>                          
	                    </div>                      
	                </div>
	            </div>	            
			<?php } ?>

			<?php
				if ($login_fabrica == 35) {
			?>

				<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='campos_pesquisa'>Campos Pesquisa</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<select name="campos_pesquisa[]" id="campos_pesquisa" multiple="multiple" class='span12'>
							<?php

							$selected_campos_pesquisa = array();
							foreach ($array_campos_pesquisa as $xcampo => $xvalue) {
								if(isset($campos_pesquisa)){
									foreach ($campos_pesquisa as $xid) {
										if ( isset($campos_pesquisa) && ($xid == $xcampo) ){
											$selected_campos_pesquisa[] = $xid;
										}
									}
								} ?>

								<option value="<?php echo $xcampo?>" <?php if( in_array($xcampo, $selected_campos_pesquisa)) echo "SELECTED"; ?> >

								<?php echo $xvalue; ?>

								</option>
							<?php } ?>

							</select>

						</div>
					</div>
				</div>
			</div>
			<?php
				}
			?>
		    <div class='span2'></div>


		</div>

		<p><br />
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<? if ($login_fabrica == 151) { ?>
				<input type='hidden'  name='gerar_excel' value='t' />
			<? } ?>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
	</form>
</div>
	<br />
<?php
	if($btn_acao == "submit" and $login_fabrica <> 151){	
		if(strlen ($msg_erro["msg"]) == 0  AND pg_num_rows($resSubmit) > 0){
            if($count == 500){
?>
<div class="alert alert-block text-center" style="width:850px;margin:0 auto;">
    A busca foi condicionada a mostrar apenas 500 resultados na tela. Para ver em sua totalidade, por favor, verifique a planilha abaixo dos resultados.
</div>
<?
            }
?>
			<table id="resultado_os" class = 'table table-striped table-bordered table-hover table-large'>
				<thead>
<?php
	    			$head = "
					<tr class = 'titulo_coluna'>
						<th>OS</th>
						<th>Pedido</th>
						<th>Tipo de OS</th>
						<th>Status OS</th>
						<th>Extrato</th>
						<th>Consumidor</th>
						<th>Consumidor Fone</th>";
	    
	    					if($login_fabrica == 151){ 
							$head .= "<th> Consumidor E-mail</th>";
						}
	   					$head .= " 
						<th>Data Abertura OS</th>
						<th>Data digitação</th>
						<th>Data Pedido</th>
						<th>Data Emissão Nota</th>
						<th>Data Saida NF</th>
						<th>NF</th>";
	    					if($login_fabrica == 151) { 
							$head .= " <th>Titular da NF</th>
                            					<th>CPF do Titular</th>";
                         			}
	    					$head .= "
						<th>Qtde Solicitada</th>
						<th>Qtde Faturada</th>
						<th>Pendente</th>
						<th>Data Postagem</th>
						<th>Data Entrega</th>
						<th>Data Consertado</th>
						<th>Data Fechamento</th>
						<th>Referência</th>
						<th>Produto</th>
						<th>Série</th>
						<th>Nº NF</th>
						<th>Data NF</th>
						<th>Revenda</th>
						<th>Código Posto</th>
						<th>Posto</th>
						<th>UF</th>
						<th>Defeito reclamado</th>
						<th>Defeito Constatado</th>
						<th>Serviço</th> 
						<th>Linha</th>
						<th>Codigo Componente</th>
						<th>Componente</th>
						<th>Item Peça</th>
						<th>Defeito Peça</th>
						<th>M.O</th>
						<th>Numero Rastreamento1</th>
						<th>Numero Rastreamento2</th>
						<th>Numero Rastreamento3</th>
						<th>Numero Rastreamento4</th>
						<th>Numero Rastreamento5</th>
						<th>Numero Rastreamento6</th>
						<th>Numero Rastreamento7</th>
						<th>Numero Rastreamento8</th>";
						if($login_fabrica == 151){
							$head .= "<td>Centro Distribuicao</td>";
						}
						$head .= "						
					</tr>";

					if($login_fabrica == 35){
						$head = "<tr class = 'titulo_coluna'>";

						foreach ($array_campos_pesquisa as $campo => $value) {
							if (count($campos_pesquisa) && !in_array($campo, $campos_pesquisa)) {
								continue;
							}
							$head .= "<th>$value</th>";
						}
							$head .= "</tr>";
					}
					
					echo $head;
?>
				</thead>
				<tbody>
					<?php

					$qtde_colunas = (count($campos_pesquisa)) ? count($campos_pesquisa) : count($array_campos_pesquisa);

						for($i = 0; $i < $count; $i++){
							$os 			= pg_fetch_result($resSubmit, $i, 'os');
							$sua_os			= pg_fetch_result($resSubmit, $i, 'sua_os');
							$os_posto		= pg_fetch_result($resSubmit, $i, 'os_posto');
							$tipo_os		= pg_fetch_result($resSubmit, $i, 'tipo_os');
							$extrato		= pg_fetch_result($resSubmit, $i, 'extrato');
							$consumidor_nome 	= pg_fetch_result($resSubmit, $i, 'consumidor_nome');
							$consumidor_fone 	= pg_fetch_result($resSubmit, $i, 'consumidor_fone');
							$consumidor_celular     = pg_fetch_result($resSubmit, $i, 'consumidor_celular');
							$consumidor_cpf         = pg_fetch_result($resSubmit, $i, 'consumidor_cpf');
							$consumidor_endereco    = pg_fetch_result($resSubmit, $i, 'consumidor_endereco');
							$consumidor_numero      = pg_fetch_result($resSubmit, $i, 'consumidor_numero');
							$consumidor_bairro      = pg_fetch_result($resSubmit, $i, 'consumidor_bairro');
							$consumidor_cep         = pg_fetch_result($resSubmit, $i, 'consumidor_cep');
							$consumidor_complemento = pg_fetch_result($resSubmit, $i, 'consumidor_complemento');
							$consumidor_cidade      = pg_fetch_result($resSubmit, $i, 'consumidor_cidade');
							$consumidor_estado      = pg_fetch_result($resSubmit, $i, 'consumidor_estado');
							$consumidor_email	= pg_fetch_result($resSubmit, $i, 'consumidor_email');
							$data_abertura 		= pg_fetch_result($resSubmit, $i, 'data_abertura');
							$data_digitacao 	= pg_fetch_result($resSubmit, $i, 'data_digitacao');
							$data_pedido 		= pg_fetch_result($resSubmit, $i, 'data_pedido');
							$data_conserto		= pg_fetch_result($resSubmit, $i, 'data_conserto');
							$data_fechamento 	= pg_fetch_result($resSubmit, $i, 'data_fechamento');
							$ref_produto	 	= pg_fetch_result($resSubmit, $i, 'ref_produto');
							$desc_produto	 	= pg_fetch_result($resSubmit, $i, 'desc_produto');
							$serie		 	= pg_fetch_result($resSubmit, $i, 'serie');
							$data_nf	 	= pg_fetch_result($resSubmit, $i, 'data_nf');
							$nota_fiscal	 	= pg_fetch_result($resSubmit, $i, 'nota_fiscal');
							$revenda_nome	 	= pg_fetch_result($resSubmit, $i, 'revenda_nome');
							$defeito_reclamado 	= pg_fetch_result($resSubmit, $i, 'defeito_reclamado');
							$defeito_constatado 	= pg_fetch_result($resSubmit, $i, 'defeito_constatado');
							$linha		 	= pg_fetch_result($resSubmit, $i, 'linha');
							$codigo_posto	 	= pg_fetch_result($resSubmit, $i, 'codigo_posto');
							$posto_nome	 	= pg_fetch_result($resSubmit, $i, 'nome');
							$ref_peca	 	= pg_fetch_result($resSubmit, $i, 'ref_peca');
							$desc_peca	 	= pg_fetch_result($resSubmit, $i, 'desc_peca');
							$defeito_peca	 	= pg_fetch_result($resSubmit, $i, 'defeito_peca');
							$servico_peca           = pg_fetch_result($resSubmit, $i, 'servico_peca');
							$mao_de_obra	 	= pg_fetch_result($resSubmit, $i, 'mao_de_obra');
							$qtde_item	 	= pg_fetch_result($resSubmit, $i, 'qtde');
							$qtde_faturada	 	= pg_fetch_result($resSubmit, $i, 'qtde_faturada');
							$qtde_pendente	 	= pg_fetch_result($resSubmit, $i, 'qtde_pendente');
							$nota_faturamento	= pg_fetch_result($resSubmit, $i, 'nota_faturamento');
							$data_emissao	 	= pg_fetch_result($resSubmit, $i, 'data_emissao');
							$data_postagem	 	= pg_fetch_result($resSubmit, $i, 'data_postagem');
							$data_entrega	 	= pg_fetch_result($resSubmit, $i, 'data_entrega');
							$conhecimento	 	= pg_fetch_result($resSubmit, $i, 'conhecimento');
							$pedido		        = pg_fetch_result($resSubmit, $i, 'pedido');
							$os_bloqueada       = pg_fetch_result($resSubmit, $i, 'os_bloqueada');
							$finalizada       = pg_fetch_result($resSubmit, $i, 'finalizada');
							$status_os        = pg_fetch_result($resSubmit, $i, 'status_os');
							$posto_estado	  = pg_fetch_result($resSubmit, $i, 'posto_estado');
							$posto_cidade           = pg_fetch_result($resSubmit, $i, 'posto_cidade');
							$posto_cnpj             = pg_fetch_result($resSubmit, $i, 'posto_cnpj');
							$qtde_km                = pg_fetch_result($resSubmit, $i, 'qtde_km');
							$qtde_visitas           = pg_fetch_result($resSubmit, $i, 'qtde_visitas');
							$total_km               = pg_fetch_result($resSubmit, $i, 'qtde_km_calculada');
							$aparencia_produto      = pg_fetch_result($resSubmit, $i, 'aparencia_produto');
							$acessorios             = pg_fetch_result($resSubmit, $i, 'acessorios');
							$obs                    = pg_fetch_result($resSubmit, $i, 'obs');
							$parametros_adicionais = pg_fetch_result($resSubmit, $i, "centro_distribuicao");

							if ($login_fabrica == 35) { /* HD - 6056809*/
								unset($solucao_os);

								$solucao_os = trim(pg_fetch_result($resSubmit, $i, 'solucao_os'));

								if (strlen($solucao_os) > 0) {
									$servico_peca = $solucao_os;
								} else {
									$servico_peca = "";
								}

								$protocolo = trim(pg_fetch_result($resSubmit, $i, 'hd_chamado'));
								$marca     = trim(pg_fetch_result($resSubmit, $i, 'marca'));                                                                               
								$tipo_atendimento     = trim(pg_fetch_result($resSubmit, $i, 'tipo_atendimento'));
								$po_peca   = trim(pg_fetch_result($resSubmit, $i, 'po_peca'));
							}	

							if($os_bloqueada == 't' and empty($finalizada)){
								$os_bloqueada= "Congelada";
							}elseif($os_bloqueada != 't' and empty($finalizada)){
								$os_bloqueada= "Descongelada";
							} else if ($login_fabrica == 151) {
								$os_bloqueada = $status_os;
							}

							if($os != $os_anterior){
								$item = 1;
							}else{
								$item++;
							}

							$os_anterior = $os;

							if ($login_fabrica == 35) {
								echo "<tr style='text-align:center'>";
								for ($z = 0; $z < $qtde_colunas; $z++) {
									echo "<td>".pg_fetch_result($resSubmit,$i, $z)."</td>";
								}
								echo "</tr>";
							} else {

								$body .= "<tr>
										<td><a href='os_press.php?os={$os}' target='_blank'>{$sua_os}</a></td>
										<td><a href='pedido_admin_consulta.php?pedido={$pedido}' target='_blank'>{$pedido}</td>
										<td>{$tipo_os}</td>";
								if($login_fabrica == 151){
									$body .= "<td>{$os_bloqueada}</td>";
								} else {
									$body .= "<td>{$status_os}</td>";
								}

								$data_conserto = ($login_fabrica == 35) ? $finalizada : $data_conserto;
								$coluna_consumidor_email = ($login_fabrica == 151) ? "<td>{$consumidor_email}</td>" : "";

								$valTitular = "";

								if($login_fabrica == 151){
		                            $titular_nf       = pg_fetch_result($resSubmit,$i, "titular_nf");
		                            $cpf_titular_nota = pg_fetch_result($resSubmit,$i, "cpf_titular_nota");

		                            $valTitular = "
		                                <td class= 'tal'>$titular_nf</td>
		                                <td class= 'tal'>$cpf_titular_nota</td>
		                            ";		                            
		                     		
		                            echo $parametros_adicionais;

									if($parametros_adicionais == "mk_nordeste"){
										$campo_p_adicionais = "<td>MK Nordeste</td>";
									}else if($parametros_adicionais == "mk_sul") {
										$campo_p_adicionais = "<td>MK Sul</td>";	
									} else{
										$campo_p_adicionais = "<td>&nbsp;</td>";	
									}
								}

								$body .= "<td><a href='extrato_consulta_os.php?extrato={$extrato}' target='_blank'>{$extrato}</a></td>
										<td>{$consumidor_nome}</td>
										<td>{$consumidor_fone}</td>
										{$coluna_consumidor_email}
										<td>{$data_abertura}</td>
										<td>{$data_digitacao}</td>
										<td>{$data_pedido}</td>
										<td>{$data_emissao}</td>
										<td>{$data_postagem}</td>
										<td>{$nota_faturamento}</td>
										{$valTitular}
										<td>{$qtde_item}</td>
										<td>{$qtde_faturada}</td>
										<td>{$qtde_pendente}</td>
										<td>{$data_postagem}</td>
										<td>{$data_entrega}</td>
										<td>{$data_conserto}</td>
										<td>{$data_fechamento}</td>
										<td>{$ref_produto}</td>
										<td>{$desc_produto}</td>
										<td>{$serie}</td>
										<td>{$nota_fiscal}</td>
										<td>{$data_nf}</td>
										<td>{$revenda_nome}</td>
										<td>{$codigo_posto}</td>
										<td>{$posto_nome}</td>
										<td>{$posto_estado}</td>
										<td>{$defeito_reclamado}</td>
										<td>{$defeito_constatado}</td>
										<td>{$servico_peca}</td>
										<td>{$linha}</td>
										<td>{$ref_peca}</td>
										<td>{$desc_peca}</td>
										<td>{$item}</td>
										<td>{$defeito_peca}</td>
										<td>{$mao_de_obra}</td>";

										if(strlen($conhecimento) == 0){
											$codigo_rastreio = array();
										}else if (preg_match("/^\[.+\]$/", $conhecimento)) {
											$codigo_rastreio = json_decode($conhecimento,true);
										}else{
											$codigo_rastreio = array();
											$codigo_rastreio[] = $conhecimento;
										}

										for($x = 0; $x < 8; $x++){
											$body .= "<td>".$codigo_rastreio[$x]."</td>";
										}

										$body .= "</tr>";
								}
						echo $body;
					}
					?>
				</tbody>
			</table>
			<?php
			if ($count > 1) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os" });
				</script>
			<?php
			}

				$jsonPOST = excelPostToJson($_POST);
			?>
			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
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

include 'rodape.php';
?>