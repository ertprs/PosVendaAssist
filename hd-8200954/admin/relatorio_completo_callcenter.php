<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


if (filter_input(INPUT_POST,"btn_acao")) {
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $atendente          = filter_input(INPUT_POST,'atendente');
    $providencia        = filter_input(INPUT_POST,'providencia');
    $cliente            = filter_input(INPUT_POST,"cliente");
    $cpf                = filter_input(INPUT_POST,"cpf");
    $analitico          = filter_input(INPUT_POST,'analitico');
    $situacao_protocolo = filter_input(INPUT_POST,'situacao_protocolo');
    $tipo_data          = filter_input(INPUT_POST,'tipo_data');
    $providencia3       = filter_input(INPUT_POST,'providencia_nivel_3');
    $motivo_contato     = filter_input(INPUT_POST,'motivo_contato');
    $centro_distribuicao = $_POST['centro_distribuicao'];


	if (!strlen($data_inicial) || !strlen($data_final) || empty($tipo_data)) {
		$msg_erro["msg"][]    = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

            if($telecontrol_distrib OR in_array($login_fabrica, [134,174])) {
                if (strtotime($aux_data_inicial."+6 months" ) < strtotime($aux_data_final)) {
                    $msg_erro["msg"][]    = traduz("Intervalo de pesquisa não pode ser maior do que 6 mês.");
                    $msg_erro["campos"][] = "data";
                }
            } elseif (strtotime($aux_data_inicial."+1 months" ) < strtotime($aux_data_final)) {
				$msg_erro["msg"][]    = traduz("Intervalo de pesquisa não pode ser maior do que 1 mês.");
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (count($msg_erro['msg']) == 0) {

        if (in_array($login_fabrica, [169, 170])) {
            if (!empty($providencia3)) {
                $condProv3 = "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
            }

            if (!empty($motivo_contato)) {
                $condMotivoContato = "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
            }
        }    

        if($login_fabrica == 151){
            if($centro_distribuicao != 'mk_vazio') {
                $p_adicionais = " AND TPE.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'";
            }
        }

		if(!empty($atendente)){
			$cond = " AND tbl_hd_chamado.atendente = {$atendente} ";
		}

		if(!empty($providencia)){
			$cond .= " AND tbl_hd_chamado_extra.hd_motivo_ligacao = {$providencia} ";
		}

		if(!empty($cliente)){
			$cond .= " AND tbl_hd_chamado_extra.nome ilike '$cliente%' ";
		}

		if(!empty($cpf)){
			$cpf = str_replace("-", "", $cpf);
			$cpf = str_replace(".", "", $cpf);
			$cpf = str_replace("/", "", $cpf);

			$cond .= " AND tbl_hd_chamado_extra.cpf = '$cpf' ";
		}

        if (in_array($login_fabrica, [169, 170])) {
            if (!empty($providencia3)) {
                $cond .= " AND tbl_hd_chamado_extra.hd_providencia = {$providencia3} ";
            }

            if (!empty($motivo_contato)) {
                $cond .= " AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato} ";
            }
        }    


		if (empty($_POST["gerar_excel"])) {
			$limit = "LIMIT 500";
		}

        if(!empty($situacao_protocolo)){
            switch($situacao_protocolo){
                case "todos":
                break;
                case "abertos":
                    $situacao = " AND tbl_hd_chamado.status = 'Aberto'";
                break;
                case "cancelados":
                    $situacao = " AND tbl_hd_chamado.status = 'Cancelado'";
                break;
                case "resolvidos":
                    $situacao = " AND tbl_hd_chamado.status = 'Resolvido'";
                break;
            }
        }

		if($analitico == "a"){

			$campos = "
                tbl_os_item.pedido,
				(SELECT ARRAY_TO_STRING(ARRAY_AGG(referencia || ' - ' || descricao || ' | ' || qtde), ',')
				FROM ( SELECT
				 referencia, tbl_peca.descricao, tbl_os_item.qtde FROM  tbl_os,tbl_os_produto, tbl_os_item,tbl_peca
				 WHERE  (tbl_os.os in (COALESCE(hi2.os, tbl_hd_chamado_extra.os)) AND tbl_os_produto.os = tbl_os.os  AND tbl_os_item.os_produto = tbl_os_produto.os_produto  )
				 AND tbl_os_item.peca = tbl_peca.peca
				 UNION
				 SELECT referencia, descricao, qtde
                   FROM tbl_pedido_item, tbl_peca
                  WHERE ( ( tbl_pedido_item.pedido = tbl_hd_chamado_extra.pedido))
		    AND tbl_pedido_item.peca         = tbl_peca.peca) x ) AS itens,
		hi2.interno, hi2.hd_chamado_item, hi2.comentario,
		UPPER((SELECT login FROM tbl_admin
		WHERE tbl_admin.admin = hi2.admin)) AS atentende_interacao,
to_char(hi2.data,'DD/MM/YYYY') AS data_interacao, ";

			$join = " LEFT JOIN tbl_hd_chamado_item hi2 ON hi2.hd_chamado = tbl_hd_chamado.hd_chamado ";

			if($login_fabrica != 189){
				$leftJoin = "	LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_hd_chamado_extra.pedido OR (tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item)
					LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
					LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica} ";
			}else{
				$leftJoin = "   LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica} ";
			}

			$complemento_join = " OR (tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca) ";

			$join_produto = " LEFT JOIN tbl_produto TPI ON TPI.produto = hi2.produto AND TPI.fabrica_i = {$login_fabrica} ";

		}else{
			$complemento_join = " OR tbl_faturamento_item.os = tbl_os.os";
			$campos = "	'' AS itens,";

			$join = " LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.produto IS NOT NULL ";
			$join .= " LEFT JOIN tbl_hd_chamado_item hi2 ON hi2.hd_chamado = tbl_hd_chamado.hd_chamado AND hi2.os IS NOT NULL ";

			$join_produto = " LEFT JOIN tbl_produto TPI ON TPI.produto = tbl_hd_chamado_item.produto AND TPI.fabrica_i = {$login_fabrica} ";


            $sql_pedido_troca = "(SELECT tbl_os_item.pedido
                            FROM tbl_os_item
                            INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            WHERE tbl_os_produto.os = tbl_os.os and tbl_servico_realizado.troca_produto is true limit 1) as pedido_troca, ";
		}

		switch ($tipo_data){
            case "abertura":
                $condData = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                break;
            case "retorno":
                $condData = " AND tbl_hd_chamado.data_providencia BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";
                break;
            case "ultima":
                $condData = " AND tbl_hd_chamado.hd_chamado in (
                                SELECT  WHDI.hd_chamado
                                FROM    tbl_hd_chamado_item WHDI
                                WHERE   WHDI.hd_chamado = tbl_hd_chamado.hd_chamado
                                AND     WHDI.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
                                AND     WHDI.hd_chamado_item = (
                                    SELECT  MAXHDI.hd_chamado_item
                                    FROM    tbl_hd_chamado_item MAXHDI
                                    WHERE   MAXHDI.hd_chamado = tbl_hd_chamado.hd_chamado
                              ORDER BY      MAXHDI.data DESC
                                    LIMIT   1
                                )
                            )
                ";
                break;
            case "troca":
                $condData = "
                            AND tbl_hd_chamado.hd_chamado in (
                                SELECT  DISTINCT
                                        THDI.hd_chamado
                                FROM    tbl_hd_chamado_item THDI
                                JOIN    tbl_os_troca    USING (os)
                                WHERE   THDI.hd_chamado = tbl_hd_chamado.hd_chamado
                                AND     tbl_os_troca.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
                            )
                ";


                break;
            case "finalizado":
                $condData = " AND tbl_hd_chamado.hd_chamado in (
                                SELECT HDID.hd_chamado
                                FROM    tbl_hd_chamado_item HDID
                                WHERE   HDID.hd_chamado = tbl_hd_chamado.hd_chamado
                                AND     HDID.status_item = 'Resolvido'
                                AND     HDID.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
                            )
                ";
                break;
		}

		$sql = " SELECT hd_chamado,
						tbl_hd_chamado.data,
						tbl_hd_chamado.data_providencia,
						status,
						fabrica,
						admin,
						titulo,
						atendente,
						hd_classificacao,
						produto
				into temp tmp_hdc_$login_admin
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra using(hd_chamado)
				WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
			    AND tbl_hd_chamado.titulo !~* 'HELP-DESK' 
                $situacao
				$condData
				$cond 
				$limit ; 

				CREATE INDEX tmp_hdc_f_$login_admin ON tmp_hdc_$login_admin(fabrica);
				CREATE INDEX tmp_hdc_hc_$login_admin ON tmp_hdc_$login_admin(hd_classificacao);
				CREATE INDEX tmp_hdc_hd_$login_admin ON tmp_hdc_$login_admin(hd_chamado) ; 

				UPDATE tmp_hdc_$login_admin set produto = tbl_hd_chamado_item.produto from tbl_hd_chamado_item where tbl_hd_chamado_item.hd_chamado = tmp_hdc_$login_admin.hd_chamado and tmp_hdc_$login_admin.produto isnull and tbl_hd_chamado_item.produto notnull;
";
        //echo nl2br($sql) . '<br>';
		$res = pg_query($con, $sql);

        if (in_array($login_fabrica, array(151))) { /*HD - 6232912*/
            $whereCancelamento = " AND tbl_faturamento.cancelada IS NULL";
            $campoTitular      = " JSON_FIELD('nome_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS titular_nf, JSON_FIELD('cpf_titular_nf', tbl_hd_chamado_extra.array_campos_adicionais) AS cpf_titular_nota, ";
        }
        $campoPlanta = "";
        if (in_array($login_fabrica, array(189))) { 
            $campoPlanta = " JSON_FIELD('planta', tbl_hd_chamado_extra.array_campos_adicionais) AS planta, ";
/*            $campoPlanta .= " JSON_FIELD('planta', tbl_hd_chamado_extra.array_campos_adicionais) AS planta, ";


tbl_hd_chamado_origem.descricao AS depto_gerador,
                       tbl_hd_classificacao.descricao AS registro_ref,
                       tbl_hd_subclassificacao.descricao AS especif_ref_registro,
                       tbl_hd_motivo_ligacao.descricao AS acao,


*/

        }

        if (in_array($login_fabrica, [174])) {
            $joinLinha = " LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto 
                           LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
            $linha = " tbl_linha.nome AS linha ,";
        }
		if (in_array($login_fabrica, [134,169,170])) {
			$left_join = " left ";
		}

        if($login_fabrica == 151){
            if($centro_distribuicao != 'mk_vazio') {
                $campo_p_adicionais = " 
                                TPE.parametros_adicionais::json->>'centro_distribuicao' as   centro_distribuicao,";                
                $condicao_p_adicionais= " AND (   TPE.parametros_adicionais::json->>'centro_distribuicao' = '$centro_distribuicao'
                    )";
            }
        }

		$sql = "SELECT DISTINCT	tbl_hd_chamado.hd_chamado,
						to_char(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data_abertura,
                       
                        (SELECT tbl_hd_motivo_ligacao.descricao 
                         FROM tbl_hd_chamado_item 
                         LEFT JOIN tbl_hd_motivo_ligacao 
                            ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_item.hd_motivo_ligacao
                         WHERE hd_chamado_item = hi2.hd_chamado_item) AS providencia_tomada,
						tbl_hd_chamado_extra.nome                   AS nome_cliente,
						tbl_hd_chamado_extra.cpf                    AS cpf_cliente,
						tbl_hd_chamado_extra.email                  AS email_cliente,
						tbl_hd_chamado_extra.fone                   AS fone_cliente,
						tbl_hd_chamado_extra.celular                AS fone2,
						tbl_cidade.nome                             AS cidade,
						tbl_cidade.estado,
						tbl_hd_chamado_extra.dias_aberto,
						tbl_hd_chamado_extra.dias_ultima_interacao,
						tbl_hd_chamado_extra.origem,
                        tbl_hd_chamado_extra.tipo_venda,     
                        $campoTitular
                        $campoPlanta
                        $campo_p_adicionais                  
						CASE
                            WHEN tbl_hd_chamado_extra.consumidor_revenda = 'C'
                            THEN 'Consumidor'
							ELSE 'Revenda'
                        END                                                         AS tipo,
                        CASE
                            WHEN tbl_hd_chamado_extra.defeito_reclamado IS NULL
                            THEN tbl_os.defeito_reclamado
                            ELSE tbl_hd_chamado_extra.defeito_reclamado
                        END                                                         AS defeito_reclamado,
                        CASE
                            WHEN tbl_hd_chamado_extra.defeito_reclamado_descricao IS NULL
                              OR LENGTH(tbl_hd_chamado_extra.defeito_reclamado_descricao) = 0
                            THEN tbl_os.defeito_reclamado_descricao
                            ELSE tbl_hd_chamado_extra.defeito_reclamado_descricao
                        END                                                         AS defeito_reclamado_descricao,

                        TPE.referencia                                              AS referencia_produto,
                        TPE.descricao                                               AS descricao_produto,
                        TO_CHAR(tbl_hd_chamado.data_providencia,'DD/MM/YYYY')       AS data_providencia,
						tbl_hd_motivo_ligacao.descricao AS providencia,
                        $sql_pedido_troca
						upper(AB.login) AS login_abertura,
						upper(AA.login) AS login_atendente,
						(
                            SELECT  UPPER(AUI.login)
                            FROM    tbl_hd_chamado_item HDI
                            JOIN    tbl_admin AUI USING(admin)
                            WHERE   HDI.hd_chamado = tbl_hd_chamado.hd_chamado
                      ORDER BY      HDI.hd_chamado_item DESC
                            LIMIT   1
                        )                                                           AS login_ultima_interacao,
						(
                            SELECT  TO_CHAR(HDIDT.data,'DD/MM/YYYY')
                            FROM    tbl_hd_chamado_item HDIDT
                            WHERE   HDIDT.hd_chamado = tbl_hd_chamado.hd_chamado
                      ORDER BY      HDIDT.hd_chamado_item DESC
                            LIMIT   1
                        )                                                           AS data_ultima_interacao,
						(
                            SELECT  TO_CHAR(HDID.data,'DD/MM/YYYY')
                            FROM    tbl_hd_chamado_item HDID
                            WHERE   HDID.hd_chamado = tbl_hd_chamado.hd_chamado
                            AND     HDID.status_item = 'Resolvido'
                      ORDER BY      HDID.hd_chamado_item DESC
                            LIMIT   1
                        )                                                           AS data_finalizado,
						(
                            SELECT  UPPER(AUIF.login)
                            FROM    tbl_hd_chamado_item HDIF
                            JOIN    tbl_admin AUIF USING(admin)
                            WHERE   HDIF.hd_chamado = tbl_hd_chamado.hd_chamado
                            AND     HDIF.status_item = 'Resolvido'
                      ORDER BY      HDIF.hd_chamado_item DESC
                            LIMIT   1
                        )                                                           AS login_finalizado,
						(
                            SELECT  COUNT(CI.hd_chamado_item)
                            FROM    tbl_hd_chamado_item CI
                            WHERE   CI.hd_chamado = tbl_hd_chamado.hd_chamado
                        )                                                           AS total_interacoes,
						COALESCE(hi2.os, tbl_hd_chamado_extra.os)                   AS os,
						tbl_posto_fabrica.codigo_posto,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data_digitacao,
						tbl_hd_chamado.status                                       AS situacao,
						tbl_hd_classificacao.descricao                              AS classificacao,
						(
                            SELECT  gerar_pedido
                            FROM    tbl_os_troca
                            WHERE   tbl_os_troca.os IN (COALESCE(hi2.os, tbl_hd_chamado_extra.os))
                      ORDER BY      os_troca DESC
                            LIMIT   1
                        )                                                           AS gerar_pedido,
						(
                            SELECT  TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY')
                            FROM    tbl_os_troca
                            WHERE   tbl_os_troca.os IN (COALESCE(hi2.os, tbl_hd_chamado_extra.os))
                      ORDER BY      os_troca DESC
                            LIMIT   1
                        )                                                           AS data_troca_recompra,
						(
                            SELECT  tbl_causa_troca.descricao
                            FROM    tbl_os_troca
                            JOIN    tbl_causa_troca USING(causa_troca)
                            WHERE   tbl_os_troca.os in (COALESCE(hi2.os, tbl_hd_chamado_extra.os))
                      ORDER BY      os_troca DESC
                            LIMIT   1
                        )                                                           AS motivo,
						$campos
						tbl_ressarcimento.valor_original                            AS valor_ressarcimento,
						tbl_ressarcimento.autorizacao_pagto,
						TO_CHAR(tbl_ressarcimento.previsao_pagamento, 'DD/MM/YYYY') AS previsao_pagamento,
						TO_CHAR(tbl_ressarcimento.finalizado,         'DD/MM/YYYY') AS data_pagamento,
						tbl_faturamento.nota_fiscal,
						tbl_faturamento.conhecimento,
						TO_CHAR(tbl_faturamento.saida,   'DD/MM/YYYY')              AS saida,
						TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')              AS emissao_nota,
                        tbl_motivo_contato.descricao as motivo_contato_descricao,
                        tbl_hd_providencia.descricao as hd_providencia_descricao,
                        tbl_hd_subclassificacao.descricao                              AS subclassificacao
					FROM tmp_hdc_$login_admin tbl_hd_chamado
					JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
					$join
                    $left_join JOIN tbl_hd_classificacao      ON tbl_hd_classificacao.hd_classificacao    = tbl_hd_chamado.hd_classificacao
                                                  AND tbl_hd_classificacao.fabrica             = tbl_hd_chamado.fabrica
               LEFT JOIN tbl_hd_subclassificacao  ON tbl_hd_subclassificacao.hd_subclassificacao    = tbl_hd_chamado_extra.hd_subclassificacao
                                                  AND tbl_hd_subclassificacao.fabrica = {$login_fabrica}
                    JOIN tbl_admin AA              ON AA.admin                                 = tbl_hd_chamado.atendente
                                                  AND AA.fabrica                               = tbl_hd_chamado.fabrica
                    JOIN tbl_admin AB              ON AB.admin                                 = tbl_hd_chamado.admin
                                                  AND AB.fabrica                               = tbl_hd_chamado.fabrica
					$left_join JOIN tbl_hd_motivo_ligacao     ON tbl_hd_motivo_ligacao.hd_motivo_ligacao  = tbl_hd_chamado_extra.hd_motivo_ligacao
                                                  AND tbl_hd_motivo_ligacao.fabrica            = tbl_hd_chamado.fabrica
				$left_join	JOIN tbl_cidade                ON tbl_cidade.cidade                        = tbl_hd_chamado_extra.cidade
                    LEFT JOIN tbl_produto TPE      ON TPE.produto                              =  tbl_hd_chamado.produto
                                                  AND TPE.fabrica_i                            = tbl_hd_chamado.fabrica
                    LEFT JOIN tbl_os               ON COALESCE(hi2.os, tbl_hd_chamado_extra.os) = tbl_os.os
                                                  AND tbl_os.fabrica                           = tbl_hd_chamado.fabrica
                                                  AND tbl_os.excluida                         IS NOT TRUE
                    LEFT JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto                  = tbl_os.posto
                                                  AND tbl_posto_fabrica.fabrica                = tbl_hd_chamado.fabrica
					$leftJoin
                    LEFT JOIN tbl_ressarcimento    ON tbl_ressarcimento.os                     = tbl_os.os
                                                  AND tbl_ressarcimento.fabrica                = tbl_hd_chamado.fabrica
					LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido              = tbl_hd_chamado_extra.pedido $complemento_join
                    LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento              = tbl_faturamento_item.faturamento
						  AND tbl_faturamento.fabrica                  = tbl_hd_chamado.fabrica
						  AND tbl_faturamento.cancelada IS NULL 
						  AND tbl_faturamento.distribuidor IS NULL
                    $joinLinha
                    LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
                    AND tbl_hd_providencia.fabrica = {$login_fabrica}
                    LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
                    AND tbl_motivo_contato.fabrica = {$login_fabrica}                    
    				WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		    AND tbl_hd_chamado.titulo !~* 'HELP-DESK'
                    $situacao
					$condData
					$cond
                    $condProv3
                    $condMotivoContato
                    $whereCancelamento					
                    $condicao_p_adicionais
                    $limit";
        //ho nl2br($sql); exit;
        $resSubmit = pg_query($con, $sql);
		$count = pg_num_rows($resSubmit);          

		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {

				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_geral-atendimentos-{$login_fabrica}-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");

				if(in_array($agrupar,array("n","a"))){
					$thLogin = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Login')."</th>";
				}

				if(in_array($agrupar,array("n","p"))){
					$thProvidencia = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>".traduz('Providência')."</th>";
				}

				$thead = "Protocolo;Origem;Ação;Atendente Abertura;Atendente Atual;Data Abertura;Ultima Interação;Dias em Aberto;Dias Última Interção;Última interação;Nome Cliente;CPF / CNPJ;E-mail Cliente;Telefone Cliente;Telefone 1;UF;Cidade;";
                if (in_array($login_fabrica, [189])) {
                    $thead = "Protocolo;Planta;Depto Gerador da RRC;Registro Ref a;Especificação de Referência de Registro;Ação;Custo Total Absorvido VIAPOL;Atendente Abertura;Atendente Atual;Data Abertura;Ultima Interação;Dias em Aberto;Dias Última Interção;Última interação;Nome Cliente;CPF / CNPJ;E-mail Cliente;Telefone Cliente;Telefone 1;UF;Cidade;";
                }


                if (in_array($login_fabrica, [174])) {
                    $thead .= "Referência do Atendimento;";
                }

                $thead .= "Tipo;Defeito Reclamado;Defeito Reclamado Combo;";
                if (in_array($login_fabrica, [174])) {
                    $thead .= "Linha;";
                }

                $campoTitular = "";

                if ($login_fabrica == 151) {
                    $campoTitular = "Titular da NF;CPF do Titular;";
                    $cd_distribuicao = "Centro Distribuição;";
                }
                if ($login_fabrica <> 189) {
                    $thead .= "Referência;Descrição Produto;Situação;Classificação;Providência Tomada;";
                }

                if ($login_fabrica == 189) {
                    $thead .= "Referência;Descrição Produto;Situação;";
                }

                if (in_array($login_fabrica, [169,170])) {
                    $thead .= "Providência nv. 3;Motivo Contato;";
                }

                $thead .= "Data Retorno;Data Finalizado;Finalizado Por;";
                
                if(!in_array($login_fabrica, [189])){
                    $thead .= "OS relacionada;OS Data Digitação;Posto Aut.;";
                }
                if(!in_array($login_fabrica, [134,189])){
                    $thead .= "Recompra / Troca;Nr. Pedido;Motivo;Itens;Dta. Recompra / Troca;Valor;Correção;Indenização;Multa;Outros;Valor Total;NF;Data Nota;{$campoTitular}Data de Saída Troca;SPD;DATA Venc;DATA Pagamento;Numero Rastreamento1;Numero Rastreamento2;Numero Rastreamento3;Numero Rastreamento4;Numero Rastreamento5;Numero Rastreamento6;Numero Rastreamento7;Numero Rastreamento8;{$cd_distribuicao}";
                }

                $thead .= "\n";

				fwrite($file, $thead);                

                $contador_resSubmit = pg_num_rows($resSubmit);
				for ($j = 0; $j < $contador_resSubmit; $j++) {

					$hd_chamado             = pg_fetch_result($resSubmit,$j,'hd_chamado');
					$data_abertura          = pg_fetch_result($resSubmit,$j,'data_abertura');
					$dias_aberto            = pg_fetch_result($resSubmit,$j,'dias_aberto');
					$dias_ultima_interacao  = pg_fetch_result($resSubmit,$j,'dias_ultima_interacao');
					$nome_cliente           = pg_fetch_result($resSubmit,$j,'nome_cliente');
					$cpf_cliente            = pg_fetch_result($resSubmit,$j,'cpf_cliente');
					$email_cliente          = pg_fetch_result($resSubmit,$j,'email_cliente');
					$fone_cliente           = pg_fetch_result($resSubmit,$j,'fone_cliente');
					$fone2                  = pg_fetch_result($resSubmit,$j,'fone2');
					$cidade                 = pg_fetch_result($resSubmit,$j,'cidade');
					$estado                 = pg_fetch_result($resSubmit,$j,'estado');
					$referencia_produto     = pg_fetch_result($resSubmit,$j,'referencia_produto');
					$descricao_produto      = pg_fetch_result($resSubmit,$j,'descricao_produto');
					$data_providencia       = pg_fetch_result($resSubmit,$j,'data_providencia');
					$providencia            = pg_fetch_result($resSubmit,$j,'providencia');

                    $providencia_tomada     = pg_fetch_result($resSubmit,$j,'providencia_tomada');
                    $hd_chamado_item        = pg_fetch_result($resSubmit,$j,'hd_chamado_item');
                    $atentende_interacao    = pg_fetch_result($resSubmit,$j,'atentende_interacao');
                    $data_interacao         = pg_fetch_result($resSubmit,$j,'data_interacao'); 
                    $comentario             = pg_fetch_result($resSubmit,$j,'comentario'); 

					$login_abertura         = pg_fetch_result($resSubmit,$j,'login_abertura');
					$login_atendente        = pg_fetch_result($resSubmit,$j,'login_atendente');
					$login_ultima_interacao = pg_fetch_result($resSubmit,$j,'login_ultima_interacao');
					$data_ultima_interacao  = pg_fetch_result($resSubmit,$j,'data_ultima_interacao');
					$os                     = pg_fetch_result($resSubmit,$j,'os');
					$pedido                 = pg_fetch_result($resSubmit,$j,'pedido');
					$codigo_posto           = pg_fetch_result($resSubmit,$j,'codigo_posto');
					$data_digitacao         = pg_fetch_result($resSubmit,$j,'data_digitacao');
					$situacao               = pg_fetch_result($resSubmit,$j,'situacao');
                    $classificacao          = pg_fetch_result($resSubmit,$j,'classificacao');
					$troca                  = pg_fetch_result($resSubmit,$j,'gerar_pedido');
					$motivo                 = pg_fetch_result($resSubmit,$j,'motivo');
					$login_finalizado       = pg_fetch_result($resSubmit,$j,'login_finalizado');
					$data_troca_recompra    = pg_fetch_result($resSubmit,$j,'data_troca_recompra');
					$data_finalizado        = pg_fetch_result($resSubmit,$j,'data_finalizado');
                    $valor_ressarcimento    = pg_fetch_result($resSubmit,$j,'valor_ressarcimento');
                    $nota_fiscal            = pg_fetch_result($resSubmit,$j,'nota_fiscal');
                    $emissao_nota           = pg_fetch_result($resSubmit,$j,'emissao_nota');
                    $rastreamento           = pg_fetch_result($resSubmit,$j,'conhecimento');
                    $spd                    = pg_fetch_result($resSubmit,$j, "autorizacao_pagto");
                    $data_saida             = pg_fetch_result($resSubmit,$j, "saida");
					$previsao_pagamento     = pg_fetch_result($resSubmit,$j, "previsao_pagamento");
					$data_pagamento         = pg_fetch_result($resSubmit,$j, "data_pagamento");
					$total_interacoes       = pg_fetch_result($resSubmit,$j, "total_interacoes");
					$origem                 = pg_fetch_result($resSubmit,$j, "origem");
					$tipo                   = pg_fetch_result($resSubmit,$j, "tipo");
					$defeito_reclamado_descricao = pg_fetch_result($resSubmit,$j, "defeito_reclamado_descricao");
					$defeito_reclamado_combo = pg_fetch_result($resSubmit,$j, "defeito_reclamado");
                    $pedido_troca = pg_fetch_result($resSubmit,$j, "pedido_troca");
                    $descricao_providencia  = pg_fetch_result($resSubmit,$j, "hd_providencia_descricao");
                    $motivo_contato         = pg_fetch_result($resSubmit,$j, "motivo_contato_descricao");
                    $parametros_adicionais  = pg_fetch_result($resSubmit, $j, 'centro_distribuicao');
                    $subclassificacao  = pg_fetch_result($resSubmit, $j, 'subclassificacao');
                    $planta  = str_replace('"', "", pg_fetch_result($resSubmit, $j, 'planta'));

                    if (in_array($login_fabrica, [174])) {
                        $tipo_venda = pg_fetch_result($resSubmit, $j, "tipo_venda");
                        $tipo_venda == 'POS' ? $tipo_venda = 'Pós-Venda' : ' ';
                        $tipo_venda == 'PRE' ? $tipo_venda = 'Pré-Venda' : ' ';

                        $linha = pg_fetch_result($resSubmit, $j, "linha");
                    }

					if (strlen($defeito_reclamado_combo) > 0) {
						$sql_df = "SELECT descricao FROM tbl_defeito_reclamado WHERE fabrica = $login_fabrica AND defeito_reclamado = $defeito_reclamado_combo;";
						$res_df = pg_query($con,$sql_df);
						if (pg_num_rows($res_df) > 0) {
							$defeito_reclamado_combo = pg_fetch_result($res_df, 0, descricao);
						}
					}
					$itens                   = pg_fetch_result($resSubmit,$j, "itens");

					$ressar_troca = ($troca == "t") ? "TROCA" : "";
					$ressar_troca = ($valor_ressarcimento > 0) ? "RESSARCIMENTO" : $ressar_troca;
                    
                    $acao = ($hd_chamado != $hd_chamado_anterior) ? 1 : $acao + 1;                            
                    if ($login_fabrica == 151 && $analitico == "a" && $interno == 't') continue;

					$acao = ($analitico != "a") ? $total_interacoes : $acao;
                    $pedido = ($analitico != "a") ? $pedido_troca : $pedido;

					$hd_chamado_anterior = $hd_chamado;

					if(!empty($pedido)){
                        if($pedido != $pedido_anterior AND empty($item)){
                            $item = 1;
                        }else if($referencia_item != $referencia_item_anterior){
                            $item++;
                        }else{
                            $item = $item;
                        }
                        $pedido_anterior = $pedido;
                        $referencia_item_anterior = $referencia_item;
					}else{
						 $item = "";
					}

					$itens = explode(",",$itens);
					$tbItem = implode(" - ", $itens);

                    $email_cliente = str_replace(";", "", $email_cliente);

					$body = "{$hd_chamado};{$origem};{$acao};{$login_abertura};{$login_atendente};{$data_abertura};";

                    if ($login_fabrica == 151 && $analitico == "a") {
                        
                        $body .= "{$atentende_interacao};";

                    } else { 
                        
                        $body .= "{$login_ultima_interacao};";
                    }
                    
                    $body .= "{$dias_aberto};{$dias_ultima_interacao};";

                    if ($login_fabrica == 151 && $analitico == "a") {
                        
                        $body .= "{$data_interacao};";

                    } else { 
                        
                        $body .= "{$data_ultima_interacao};";
                    }

                    $body .= "{$nome_cliente};{$cpf_cliente};{$email_cliente};{$fone_cliente};{$fone2};{$estado};{$cidade};";

                    if (in_array($login_fabrica, [189])) {
                        $body ="";

                       $sqlCusto = "SELECT 
                                        tbl_hd_chamado_custo.hd_chamado, 
                                        sum(taxa_banco) AS total_taxa_banco,
                                        sum(juros) AS total_juros,
                                        sum(frete_ida) AS total_frete_ida, 
                                        sum(frete_volta) AS total_frete_volta, 
                                        sum(reentrega) AS total_reentrega, 
                                        sum(reprocesso) AS total_reprocesso, 
                                        sum(extras) AS total_extras
                                        FROM tbl_hd_chamado_custo 
                                        JOIN tbl_hd_chamado_categoria_custo ON tbl_hd_chamado_categoria_custo.hd_chamado_categoria_custo=tbl_hd_chamado_custo.hd_chamado_categoria_custo AND tbl_hd_chamado_categoria_custo.fabrica= {$login_fabrica}
                                        WHERE tbl_hd_chamado_custo.fabrica= {$login_fabrica}
                                        AND tbl_hd_chamado_custo.hd_chamado={$hd_chamado}
                                        AND tbl_hd_chamado_categoria_custo.codigo='ABS VIAPOL'
                                        GROUP BY 
                                        tbl_hd_chamado_custo.hd_chamado";
                        $resCusto = pg_query($con, $sqlCusto);
                        $totalCusto = "0.00";
                        $xtotalCusto = "R$ 0.00";
                        if (pg_num_rows($resCusto) > 0) {

                            $total_taxa_banco = pg_fetch_result($resCusto, 0, 'total_taxa_banco');
                            $total_juros = pg_fetch_result($resCusto, 0, 'total_juros');
                            $total_frete_ida = pg_fetch_result($resCusto, 0, 'total_frete_ida');
                            $total_frete_volta = pg_fetch_result($resCusto, 0, 'total_frete_volta');
                            $total_reentrega = pg_fetch_result($resCusto, 0, 'total_reentrega');
                            $total_reprocesso = pg_fetch_result($resCusto, 0, 'total_reprocesso');
                            $total_extras = pg_fetch_result($resCusto, 0, 'total_extras');
                            $totalCusto = ($total_taxa_banco+$total_juros+$total_frete_ida+$total_frete_volta+$total_reentrega+$total_reprocesso+$total_extras);
                            $xtotalCusto = "R$ ".number_format($totalCusto, 2, '.', '');
                        }

                        $body ="{$hd_chamado};{$planta};{$origem};{$classificacao};{$subclassificacao};{$providencia};{$xtotalCusto};{$login_abertura};{$login_atendente};{$data_abertura};{$login_ultima_interacao};{$dias_aberto};{$dias_ultima_interacao};{$data_ultima_interacao};{$nome_cliente};{$cpf_cliente};{$email_cliente};{$fone_cliente};{$fone2};{$estado};{$cidade};";
                    }
                    if (in_array($login_fabrica, [174])) {
                        $body .= "{$tipo_venda};";
                    }

                    $body .= "{$tipo};'{$defeito_reclamado_descricao}';'{$defeito_reclamado_combo}';";
                    if (in_array($login_fabrica, [174])) {
                        $body .= "{$linha};";
                    }

                    $valTitular = "";

                    if ($login_fabrica == 151) {
                        $titular_nf       = pg_fetch_result($resSubmit,$j, "titular_nf");
                        $cpf_titular_nota = pg_fetch_result($resSubmit,$j, "cpf_titular_nota");

                        $valTitular = "$titular_nf;$cpf_titular_nota;";

                    }
                    if(!in_array($login_fabrica, [189])){
                        $body .= "'{$referencia_produto}';'{$descricao_produto}';{$situacao};{$classificacao};";

                        if ($login_fabrica == 151 && $analitico == "a") {
                            
                            $body .= "{$providencia_tomada};";

                        } else {

                            $body .= "{$providencia};";   
                        }
                    }
                    if (in_array($login_fabrica, [169,170])) {
                        $body .= "$descricao_providencia;$motivo_contato;";
                    }

                    if(in_array($login_fabrica, [189])){
                        $body .= "{$referencia_produto};{$descricao_produto};{$situacao};";
                    }
                    $body .= "{$data_providencia};{$data_finalizado};";
                    if(!in_array($login_fabrica, [189])){
                        $body .= "{$login_finalizado};{$os};{$data_digitacao};{$codigo_posto};";
                    }
                    if(in_array($login_fabrica, [189])){
                        $body .= "{$login_finalizado};";
                    }

                    if(!in_array($login_fabrica, [134,189])){
                        $body .= "{$ressar_troca};{$pedido};{$motivo};{$tbItem};{$data_troca_recompra};$valor_ressarcimento;;;;;$valor_ressarcimento;$nota_fiscal;$emissao_nota;{$valTitular}$data_saida;$spd;$previsao_pagamento;$data_pagamento";

    					if (strlen($rastreamento) == 0) {
    						$codigo_rastreio = array();
    					} else if (preg_match("/^\[.+\]$/", $rastreamento)) {
    						$codigo_rastreio = json_decode($rastreamento,true);
    					} else {
    						$codigo_rastreio = array();
    						$codigo_rastreio[] = $rastreamento;
    					}

    					for($x = 0; $x < 8; $x++){
    						$body .= ";".$codigo_rastreio[$x];
    					}
                    }

                    if($login_fabrica == 151){                        
                        if($parametros_adicionais == "mk_nordeste"){
                            $conteudo = ";MK Nordeste";    
                        }else if($parametros_adicionais == "mk_sul") {
                            $conteudo = ";MK Sul"; 
                        } else {
                            $conteudo = ";";   
                        }
                        $body .= $conteudo;
                    }

					$body .= "\n";

					fwrite($file, $body);
				}

				fclose($file);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}

			}

			exit;
		}
	}
}

$layout_menu = "callcenter";
$title= traduz("RELATÓRIO DE VISÃO GERAL DE ATENDIMENTOS");
include "cabecalho_new.php";
$plugins = array(
	"datepicker",
	"mask",
	"dataTable",
	"ajaxform"
);
include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));

	});
</script>
<style type="text/css">
    #optionsRadios1{
        margin-left: 14px;
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
		<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>

<!--form-->
	<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class ='titulo_tabela'><?=traduz('Parametros de Pesquisa')?> </div>
		<br/>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz('Data Inicial')?></label>
					<div class='controls controls-row'>
						<div class='span6'>
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
						<div class='span6'>
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

		<div class='span4'>
			<div class='control-group <?=(in_array("atendente", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='atendente'><?=traduz('Atendente')?></label>
				<div class='controls controls-row'>
					<div class='span12'>
						<select name='atendente' class='span12' >
							<option></option>
							<?php

							$sql = "SELECT admin, nome_completo
									FROM tbl_admin
									WHERE fabrica = {$login_fabrica}
									AND callcenter_supervisor IS TRUE
									ORDER BY nome_completo";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) > 0) {

								for ($i = 0; $i < pg_num_rows($res); $i++) {
									$admin = pg_fetch_result($res, $i, "admin");
									$nome_completo = pg_fetch_result($res, $i, "nome_completo");

									$selected = ($admin == $atendente) ? "selected" : "";

									echo "<option value='{$admin}' {$selected}>{$nome_completo}</option>";
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
				<label class='control-label' for='data_inicial'><?php echo ($login_fabrica == 189) ? traduz("Ação") : traduz("Providência");?></label>
				<div class='controls controls-row'>
					<div class='span4'>

						<select name="providencia" id="providencia">
							<option value=""></option>
							<?php

								$sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = $login_fabrica and ativo order by descricao";
								$res = pg_query($con,$sql);
								foreach (pg_fetch_all($res) as $key) {

									$selected_providencia = ( isset($providencia) and ($providencia == $key['hd_motivo_ligacao']) ) ? "SELECTED" : '' ;

								?>
									<option value="<?php echo $key['hd_motivo_ligacao']?>" <?php echo $selected_providencia ?> >
										<?php echo $key['descricao']?>
									</option>
								<?php
								}

							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <?php if(in_array($login_fabrica, array(169,170))){ ?>
        <div class="row-fluid">
            <span class="span2"></span>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label'>Providência Nível 3</label>
                    <div class='controls controls-row'>
                        <select name="providencia_nivel_3" id='providencia_nivel_3' class='frm'>
                            <option value=""></option>
                            <?php
                                $sqlProvidencia3 = "SELECT hd_providencia, descricao
                                                    FROM tbl_hd_providencia WHERE fabrica = {$login_fabrica}
                                                    AND ativo IS TRUE
                                                    ORDER BY descricao DESC";
                                $resProvidencia3 = pg_query($con,$sqlProvidencia3);

                                if(pg_num_rows($resProvidencia3) > 0){
                                    while($dadosProv = pg_fetch_object($resProvidencia3)){
                                        
                                        $selected = ($dadosProv->hd_providencia == $_POST['providencia_nivel_3']) ? "selected" : "";

                                        ?>
                                        <option value="<?=$dadosProv->hd_providencia?>" <?=$selected?>>
                                            <?= $dadosProv->descricao ?>
                                        </option>
                                        <?php
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label'>Motivo Contato</label>
                    <div class='controls controls-row'>
                        <select name="motivo_contato" id='motivo_contato' class='frm'>
                            <option value=""></option>
                            <?php
                                $sqlMotivoContato = "SELECT motivo_contato, descricao
                                                    FROM tbl_motivo_contato WHERE fabrica = {$login_fabrica}
                                                    AND ativo IS TRUE
                                                    ORDER BY descricao DESC";
                                $resMotivoContato = pg_query($con,$sqlMotivoContato);

                                if(pg_num_rows($resMotivoContato) > 0){
                                    while($dadosContato = pg_fetch_object($resMotivoContato)){
                                        
                                        $selected = ($dadosContato->motivo_contato == $_POST['motivo_contato']) ? "selected" : "";

                                        ?>
                                        <option value="<?=$dadosContato->motivo_contato?>" <?=$selected?>>
                                            <?= $dadosContato->descricao ?>
                                        </option>
                                        <?php
                                    }
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cliente'><?=traduz('Cliente')?></label>
					<div class='controls controls-row'>
						<div class='span12'>
								<input type="text" name="cliente" id="cliente" class='span12' value= "<?=$cliente?>">
						</div>
					</div>
				</div>
			</div>
            <?php
                if(!in_array($login_fabrica,[180,181,182])) { ?>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='cpf'>CPF</label>
					<div class='controls controls-row'>
						<div class='span10'>
								<input type="text" name="cpf" id="cpf" class='span12 text-center' value= "<?=$cpf?>">
						</div>
					</div>
				</div>
			</div>
            <? }?>
			<div class='span2'></div>
		</div>           
        <?php if($login_fabrica == 151){ ?>         
            <div class='row-fluid'>
                <div class='span2'></div>
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
            </div>                       
        <?php } ?>
        <br />   
		<div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <?=traduz('Situação do Protocolo:')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="todos" checked><?=traduz('Todos')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="abertos" <?if($situacao_protocolo=="abertos") echo "checked";?>> <?=traduz('Abertos')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="cancelados" <?if($situacao_protocolo=="cancelados") echo "checked";?>> <?=traduz('Cancelados')?>
                <input type="radio" name="situacao_protocolo" id="optionsRadios1" value="resolvidos" <?if($situacao_protocolo=="resolvidos") echo "checked";?>> <?=traduz('Resolvidos')?>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <?=traduz('Tipo do Relatório:')?>
                <input type="radio" name="analitico" id="optionsRadios1" value="a" checked> <?=traduz('Analítico')?>
                <input type="radio" name="analitico" id="optionsRadios1" value="s" <?if($analitico=="s") echo "checked";?>> <?=traduz('Sintético')?>
            </div>
        </div>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span10'>
                <div class='control-group <?=(in_array("tipo", $msg_erro["campos"])) ? "error" : ""?>'>
                    <h5 class='asteristico'>*</h5><?=traduz('Tipo de Data:')?>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="abertura" <?if($tipo_data=="abertura" or empty($tipo_data)) echo "checked";?>> <?=traduz('Abertura')?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="retorno" <?if($tipo_data=="retorno") echo "checked";?>> <?=traduz('Retorno')?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="ultima" <?if($tipo_data=="ultima") echo "checked";?>> <?=traduz('Última Interação')?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="troca" <?if($tipo_data=="troca") echo "checked";?>> <?=traduz('Troca / Recompra')?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="tipo_data" id="optionsRadios1" value="finalizado" <?if($tipo_data=="finalizado") echo "checked";?>> <?=traduz('Finalizado')?>
                    </label>
                </div>
            </div>
        </div>
		<p>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));"><?=traduz('Pesquisar')?></button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br />
	</form>
	<br />
<?php            
	if(filter_input(INPUT_POST,"btn_acao") and count($msg_erro["msg"]) == 0 ){
		if(count($msg_erro["msg"]) == 0 AND pg_num_rows($resSubmit) > 0){
?>
            </div>
			<table id="resultado_atendimentos" class = 'table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class = 'titulo_coluna'>
						<th><?=traduz('Protocolo')?></th>
                        			<?php if (in_array($login_fabrica, [189])) { ?>
                                            <th><?=traduz('Planta')?></th>
                            				<th><?=traduz('Depto. Gerador da RRC')?></th>
                            				<th><?=traduz('Registro Ref. a')?></th>
                            				<th><?=traduz('Especificação de Referência de Registro')?></th>
                            				<th><?=traduz('Ação')?></th>
                            				<th><?=traduz('Custo Total do Atendimento')?></th>
                        			<?php } else { ?>
			                        	<th><?=traduz('Origem')?></th>
							<th><?=traduz('Ação')?></th>
						<?php } ?>
						<th><?=traduz('Atendente Abertura')?></th>
						<th><?=traduz('Atendente Atual')?></th>
						<th><?=traduz('Data Abertura')?></th>
						<th><?=traduz('Ultima Interação')?></th>
						<th><?=traduz('Dias em Aberto')?></th>
						<th><?=traduz('Dias Última Interção')?></th>
						<th><?=traduz('Última interação')?></th>
						<th><?=traduz('Nome Cliente')?></th>
						<th><?=traduz('CPF / CNPJ')?></th>
						<th><?=traduz('E-mail Cliente')?></th>
						<th><?=traduz('Telefone Cliente')?></th>
						<th><?=traduz('Telefone 1')?></th>
						<th><?=traduz('UF')?></th>
						<th><?=traduz('Cidade')?></th>
                        			<?= in_array($login_fabrica, [174]) ? '<th>'.traduz('Referência do Atendimento').'</th>' : '' ?>
						<th><?=traduz('Tipo')?></th>
						<th><?=traduz('Defeito Reclamado')?></th>
						<th><?=traduz('Defeito Reclamado Combo')?></th>
			                        <?= in_array($login_fabrica, [174]) ? '<th>'.traduz('Linha').'</th>' : '' ?>
						<th><?=traduz('Referência')?></th>
						<th><?=traduz('Descrição Produto')?></th>
						<th><?=traduz('Situação')?></th>
                        			<?php if (!in_array($login_fabrica, [189])) { ?>
							<th><?=traduz('Classificação')?></th>
							<th><?=traduz('Providência Tomada')?></th>
                        			<?php }
			                        if (in_array($login_fabrica, [169,170])) { ?>
                        				<th><?=traduz('Providência niv. 3')?></th>
                        				<th><?=traduz('Motivo Contato')?></th>
                        			<?php } ?>
						<th><?=traduz('Data Retorno')?></th>
						<th><?=traduz('Data Finalizado')?></th>
						<th><?=traduz('Finalizado Por')?></th>
                        			<?php if (!in_array($login_fabrica, [189])) { ?>
						<th><?=traduz('OS relacionada')?></th>
						<th><?=traduz('OS Data Digitação')?></th>
						<th><?=traduz('Posto Aut.')?></th>
						<th><?=traduz('Recompra / Troca')?></th>
						<th><?=traduz('Nr. Pedido')?></th>
						<th><?=traduz('Motivo')?></th>
						<th><?=traduz('Itens')?> <br /> <?=traduz('Peça')?> | <?=traduz('Qtde')?></th>
						<th><?=traduz('Dta. Recompra')?> / <?=traduz('Troca')?></th>
						<th><?=traduz('Valor')?></th>
						<th><?=traduz('Correção')?></th>
						<th><?=traduz('Indenização')?></th>
						<th><?=traduz('Multa')?></th>
						<th><?=traduz('Outros')?></th>
						<th><?=traduz('Valor Total')?></th>
						<th>NF</th>
						<th><?=traduz('Data Nota')?></th>
			                        <?php }
						if ($login_fabrica == 151) { /*HD - 6177097*/ ?>
							<th><?=traduz('Titular da NF'); ?></th>
							<th><?=traduz('CPF do Titular'); ?></th>
						<?php }
                        			if (!in_array($login_fabrica, [189])) { ?>
						<th><?=traduz('Data de Saída Troca')?></th>
						<th>SPD</th>
						<th><?=traduz('DATA VENC')?></th>
						<th><?=traduz('DATA PAGAMENTO')?></th>
						<th><?=traduz('Numero Rastreamento1')?></th>
						<th><?=traduz('Numero Rastreamento2')?></th>
						<th><?=traduz('Numero Rastreamento3')?></th>
						<th><?=traduz('Numero Rastreamento4')?></th>
						<th><?=traduz('Numero Rastreamento5')?></th>
						<th><?=traduz('Numero Rastreamento6')?></th>
						<th><?=traduz('Numero Rastreamento7')?></th>
						<th><?=traduz('Numero Rastreamento8')?></th>
						<?php }
						if($login_fabrica == 151){ ?>
							<th><?= traduz('Centro Distribuição'); ?></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php
                        for($i = 0; $i < $count; $i++){
							$hd_chamado             = pg_fetch_result($resSubmit,$i,'hd_chamado');
							$data_abertura          = pg_fetch_result($resSubmit,$i,'data_abertura');
							$dias_aberto            = pg_fetch_result($resSubmit,$i,'dias_aberto');
							$dias_ultima_interacao  = pg_fetch_result($resSubmit,$i,'dias_ultima_interacao');
							$nome_cliente           = pg_fetch_result($resSubmit,$i,'nome_cliente');
							$cpf_cliente            = pg_fetch_result($resSubmit,$i,'cpf_cliente');
							$email_cliente          = pg_fetch_result($resSubmit,$i,'email_cliente');
							$fone_cliente           = pg_fetch_result($resSubmit,$i,'fone_cliente');
							$fone2                  = pg_fetch_result($resSubmit,$i,'fone2');
							$cidade                 = pg_fetch_result($resSubmit,$i,'cidade');
							$estado                 = pg_fetch_result($resSubmit,$i,'estado');
							$referencia_produto     = pg_fetch_result($resSubmit,$i,'referencia_produto');
							$descricao_produto      = pg_fetch_result($resSubmit,$i,'descricao_produto');
							$data_providencia       = pg_fetch_result($resSubmit,$i,'data_providencia');
							$providencia            = pg_fetch_result($resSubmit,$i,'providencia');
                            
                            $providencia_tomada     = pg_fetch_result($resSubmit,$i,'providencia_tomada');
                            $hd_chamado_item        = pg_fetch_result($resSubmit,$i,'hd_chamado_item');
                            $atentende_interacao    = pg_fetch_result($resSubmit,$i,'atentende_interacao');
                            $data_interacao         = pg_fetch_result($resSubmit,$i,'data_interacao'); 
                            $comentario             = pg_fetch_result($resSubmit,$i,'comentario'); 
                            $interno                = pg_fetch_result($resSubmit,$i,'interno'); 
                        
							$login_abertura         = pg_fetch_result($resSubmit,$i,'login_abertura');
							$login_atendente        = pg_fetch_result($resSubmit,$i,'login_atendente');
							$login_ultima_interacao = pg_fetch_result($resSubmit,$i,'login_ultima_interacao');
							$data_ultima_interacao  = pg_fetch_result($resSubmit,$i,'data_ultima_interacao');
							$data_finalizado        = pg_fetch_result($resSubmit,$i,'data_finalizado');
							$os                     = pg_fetch_result($resSubmit,$i,'os');
							$pedido                 = pg_fetch_result($resSubmit,$i,'pedido');
							$codigo_posto           = pg_fetch_result($resSubmit,$i,'codigo_posto');
							$data_digitacao         = pg_fetch_result($resSubmit,$i,'data_digitacao');
							$situacao         	    = pg_fetch_result($resSubmit,$i,'situacao');
							$classificacao     	    = pg_fetch_result($resSubmit,$i,'classificacao');
							$troca                  = pg_fetch_result($resSubmit,$i,'gerar_pedido');
							$motivo                 = pg_fetch_result($resSubmit,$i,'motivo');
							$login_finalizado       = pg_fetch_result($resSubmit,$i,'login_finalizado');
							$data_troca_recompra	= pg_fetch_result($resSubmit,$i,'data_troca_recompra');
							$itens                  = pg_fetch_result($resSubmit,$i, "itens");
							$valor_ressarcimento	= pg_fetch_result($resSubmit,$i,'valor_ressarcimento');
							$nota_fiscal		    = pg_fetch_result($resSubmit,$i,'nota_fiscal');
							$emissao_nota		    = pg_fetch_result($resSubmit,$i,'emissao_nota');
							$conhecimento		    = pg_fetch_result($resSubmit,$i,'conhecimento');
							$spd 			        = pg_fetch_result($resSubmit,$i, "autorizacao_pagto");
							$data_saida		        = pg_fetch_result($resSubmit,$i, "saida");
							$previsao_pagamento     = pg_fetch_result($resSubmit,$i, "previsao_pagamento");
							$data_pagamento         = pg_fetch_result($resSubmit,$i, "data_pagamento");
							$total_interacoes       = pg_fetch_result($resSubmit,$i, "total_interacoes");
							$origem                 = pg_fetch_result($resSubmit,$i, "origem");
							$tipo                   = pg_fetch_result($resSubmit,$i, "tipo");
							$defeito_reclamado_descricao = pg_fetch_result($resSubmit,$i, "defeito_reclamado_descricao");
							$defeito_reclamado_combo = pg_fetch_result($resSubmit,$i, "defeito_reclamado");
                            $pedido_troca = pg_fetch_result($resSubmit,$i, "pedido_troca");
                            $descricao_providencia  = pg_fetch_result($resSubmit,$i, "hd_providencia_descricao");
                            $motivo_contato         = pg_fetch_result($resSubmit,$i, "motivo_contato_descricao");
                            $subclassificacao         = pg_fetch_result($resSubmit,$i, "subclassificacao");
                            $planta         = pg_fetch_result($resSubmit,$i, "planta");
                            
                            if (in_array($login_fabrica, [174])) {
                                $tipo_venda = pg_fetch_result($resSubmit, $i, 'tipo_venda');
                                $tipo_venda == 'POS' ? $tipo_venda = 'Pós-Venda' : ' ';
                                $tipo_venda == 'PRE' ? $tipo_venda = 'Pré-Venda' : ' ';

                                $linha = pg_fetch_result($resSubmit, $i, 'linha');
                            }

                            if (strlen($defeito_reclamado_combo) > 0) {
								$sql_df = "SELECT descricao FROM tbl_defeito_reclamado WHERE fabrica = $login_fabrica AND defeito_reclamado = $defeito_reclamado_combo;";
								$res_df = pg_query($con,$sql_df);
								if (pg_num_rows($res_df) > 0) {
									$defeito_reclamado_combo = pg_fetch_result($res_df, 0, descricao);
								}
							}

							$ressar_troca = ($troca == "t") ? "TROCA" : "";
							$ressar_troca = ($valor_ressarcimento > 0) ? "RESSARCIMENTO" : $ressar_troca;

							$acao = ($hd_chamado != $hd_chamado_anterior) ? 1 : $acao + 1;
                            
                            if ($login_fabrica == 151 && $analitico == "a" && $interno == 't') continue;

							$acao = ($analitico != "a") ? $total_interacoes : $acao;
                            $pedido = ($analitico != "a") ? $pedido_troca : $pedido;
							$hd_chamado_anterior = $hd_chamado;

							if(!empty($pedido)){
								if($pedido != $pedido_anterior AND empty($item)){
									$item = 1;
								}else if($referencia_item != $referencia_item_anterior){
									$item++;
								}else{
									$item = $item;
								}
								$pedido_anterior = $pedido;
								$referencia_item_anterior = $referencia_item;
							}else{
								$item = "";
							}

							$itens = explode(",",$itens);

							$tbItem = "<table width='100%'>";
							foreach($itens AS $item){
								$item = explode("|",$item);

								$tbItem .= "<tr>";
								foreach($item AS $value){
										$tbItem .= "<td nowrap>{$value}</td>";
								}
								$tbItem .= "</tr>";
							}
							$tbItem .= "</table>";

                            $hasTipoVenda = "";
                            if (in_array($login_fabrica, [189])) {

                                $sqlCusto = "SELECT 
                                                tbl_hd_chamado_custo.hd_chamado, 
                                                sum(taxa_banco) AS total_taxa_banco,
                                                sum(juros) AS total_juros,
                                                sum(frete_ida) AS total_frete_ida, 
                                                sum(frete_volta) AS total_frete_volta, 
                                                sum(reentrega) AS total_reentrega, 
                                                sum(reprocesso) AS total_reprocesso, 
                                                sum(extras) AS total_extras
                                                FROM tbl_hd_chamado_custo 
                                                JOIN tbl_hd_chamado_categoria_custo ON tbl_hd_chamado_categoria_custo.hd_chamado_categoria_custo=tbl_hd_chamado_custo.hd_chamado_categoria_custo AND tbl_hd_chamado_categoria_custo.fabrica= {$login_fabrica}
                                                WHERE tbl_hd_chamado_custo.fabrica= {$login_fabrica}
                                                AND tbl_hd_chamado_custo.hd_chamado={$hd_chamado}
                                                AND tbl_hd_chamado_categoria_custo.codigo='ABS VIAPOL'
                                                GROUP BY 
                                                tbl_hd_chamado_custo.hd_chamado";
                                $resCusto = pg_query($con, $sqlCusto);
                                $totalCusto = 0.00;
                                if (pg_num_rows($resCusto) > 0) {

                                    $total_taxa_banco = pg_fetch_result($resCusto, 0, 'total_taxa_banco');
                                    $total_juros = pg_fetch_result($resCusto, 0, 'total_juros');
                                    $total_frete_ida = pg_fetch_result($resCusto, 0, 'total_frete_ida');
                                    $total_frete_volta = pg_fetch_result($resCusto, 0, 'total_frete_volta');
                                    $total_reentrega = pg_fetch_result($resCusto, 0, 'total_reentrega');
                                    $total_reprocesso = pg_fetch_result($resCusto, 0, 'total_reprocesso');
                                    $total_extras = pg_fetch_result($resCusto, 0, 'total_extras');
                                    $totalCusto = ($total_taxa_banco+$total_juros+$total_frete_ida+$total_frete_volta+$total_reentrega+$total_reprocesso+$total_extras);
                                }
                            }
                            if (in_array($login_fabrica, [174])) {
                                $hasTipoVenda = "<td class= 'tac'>{$tipo_venda}</td>";
                            }

                            $hasLinha = "";
                            if (in_array($login_fabrica, [174])) {
                                $hasLinha = "<td class= 'tac'>{$linha}</td>";
                            }

                            if ($login_fabrica == 151) { /*HD - 6177097*/
                                $titular_nf       = pg_fetch_result($resSubmit,$i, "titular_nf");
                                $cpf_titular_nota = pg_fetch_result($resSubmit,$i, "cpf_titular_nota");

                                $titular_nf = str_replace(array(";","<br>","\n","\r"), '', $titular_nf);

                                $valTitular = "
                                    <td class= 'tal'>$titular_nf</td>
                                    <td class= 'tal'>$cpf_titular_nota</td>
                                ";

                                $parametros_adicionais  = pg_fetch_result($resSubmit, $i, 'centro_distribuicao');
                            }


							$body .= "<tr>
										<td class= 'tac'>{$hd_chamado}</td>";
                                        if ($login_fabrica == 189) {
                                            $body .= "<td class= 'tac'>$planta</td>";
                                            $body .= "<td class= 'tac'>$origem</td>";
                                            $body .= "<td class= 'tac'>$classificacao</td>";
                                            $body .= "<td class= 'tac'>$subclassificacao</td>";
                                            $body .= "<td class= 'tac'>$providencia</td>";
                                            $body .= "<td class= 'tac'>R$ ".number_format($totalCusto, 2, ',', '.')."</td>";
                                        } else {
                                            $body .=   "<td class= 'tac'>{$origem}</td>
            										  <td class= 'tac'>{$acao}</td>";
                                        }
                            $body .=   "<td class= 'tac'>{$login_abertura}</td>
										<td class= 'tac'>{$login_atendente}</td>
                                        <td class= 'tac'>{$data_abertura}</td>";

							if ($login_fabrica == 151 && $analitico == "a") { 

                                $body .=   "<td class= 'tac'>{$atentende_interacao}</td>";
                            
                            } else {

                                $body .=   "<td class= 'tac'>{$login_ultima_interacao}</td>";
                            }

					        $body .= "<td class= 'tac'>{$dias_aberto}</td>
									  <td class= 'tac'>{$dias_ultima_interacao}</td>";
                                        
                            if ($login_fabrica == 151 && $analitico == "a") {

                                $body .=   "<td class= 'tac'>{$data_interacao}</td>";

                            } else {
                                
                                $body .=   "<td class= 'tac'>{$data_ultima_interacao}</td>";
                            }

							$body .=   "<td class= 'tac' nowrap>{$nome_cliente}</td>
										<td class= 'tac'>{$cpf_cliente}</td>
										<td class= 'tac'>{$email_cliente}</td>
										<td class= 'tac'>{$fone_cliente}</td>
										<td class= 'tac'>{$fone2}</td>
										<td class= 'tac'>{$estado}</td>
										<td class= 'tac'>{$cidade}</td>
                                        {$hasTipoVenda}
										<td class= 'tac'>{$tipo}</td>
										<td class= 'tac'>{$defeito_reclamado_descricao}</td>
										<td class= 'tac'>{$defeito_reclamado_combo}</td>
                                        {$hasLinha}
										<td class= 'tac'>{$referencia_produto}</td>
										<td class= 'tac' nowrap>{$descricao_produto}</td>
										<td class= 'tac'>{$situacao}</td>";
                            if (!in_array($login_fabrica, [189])) {
    							$body .= "	<td class= 'tac'>{$classificacao}</td>";

                                if ($login_fabrica == 151 && $analitico == "a") {

                                    $body .= "<td class= 'tac' nowrap>{$providencia_tomada}</td>";
                                
                                } else {
                                    
                                    $body .= "<td class= 'tac' nowrap>{$providencia}</td>";
                                }
                            }
                            if (in_array($login_fabrica, [169,170])) { 
                                $body .= "<td class= 'tac' nowrap>{$descricao_providencia}</td>
                                          <td class= 'tac' nowrap>{$motivo_contato}</td>";
                            }
                            
                            $body .= "
										<td class= 'tac'>{$data_providencia}</td>
										<td class= 'tac'>{$data_finalizado}</td>
										<td class= 'tac'>{$login_finalizado}</td>
                                    ";
                            if (!in_array($login_fabrica, [189])) {
                            $body .= "
										<td class= 'tac'>{$os}</td>
										<td class= 'tac'>{$data_digitacao}</td>
										<td class= 'tac'>{$codigo_posto}</td>
										<td class= 'tac'>{$ressar_troca}</td>
										<td class= 'tac'>{$pedido}</td>
										<td class= 'tac'>{$motivo}</td>
										<td class= 'tac' nowrap>{$tbItem}</td>
										<td class= 'tac'>{$data_troca_recompra}</td>
										<td class= 'tac'>".number_format($valor_ressarcimento,2,',','.')."</td>
										<td class= 'tac'></td>
										<td class= 'tac'></td>
										<td class= 'tac'></td>
										<td class= 'tac'></td>
										<td class= 'tac'>".number_format($valor_ressarcimento,2,',','.')."</td>
										<td class= 'tac'>{$nota_fiscal}</td>
										<td class= 'tac'>{$emissao_nota}</td>";
                                    
                            $body .= "
                                        {$valTitular}
										<td class= 'tac'>{$data_saida}</td>
										<td class= 'tac'>{$spd}</td>
										<td class= 'tac'>{$previsao_pagamento}</td>
										<td class= 'tac'>{$data_pagamento}</td>";

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
                                        if($login_fabrica == 151){
                                            if($parametros_adicionais == "mk_nordeste"){
                                                $body .= "<td class='tac'>MK Nordeste</td>";    
                                            }else if($parametros_adicionais == "mk_sul") {
                                                $body .= "<td class='tac'>MK Sul</td>"; 
                                            } else{
                                                $body .= "<td class='tac'>&nbsp;</td>"; 
                                            }                       
                                        }
                                }        
									$body .= "</tr>";
						}
						echo $body;
					?>
				</tbody>
			</table>
			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_atendimentos" });
				</script>
			<?php
			}

				$jsonPOST = excelPostToJson($_POST);
			?>
			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz('Gerar Arquivo Excel')?></span>
			</div>
<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>'.traduz("Nenhum resultado encontrado").'</h4>
			</div>
			</div>';
		}
	}

include 'rodape.php';
?>
