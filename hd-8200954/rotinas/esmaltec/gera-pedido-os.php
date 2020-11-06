<?php
/**
 *
 * gera-pedido-os.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ricardo Vicente
 * @version 2012.07.13
 *
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);
define('isCLI',  (PHP_SAPI == 'cli'));

$hostname = (isCLI) ? trim(`hostname`) : $_SERVER['SERVER_NAME'];

define('isRoot', (posix_geteuid() == 0));

define('ENV','producao');
//define('ENV','teste');

define('SEP',    (isCLI and !isRoot)?chr(10):'<br />'); // \n se roda no terminal, <br /> se estÃ¡ rodando no CRON ou no browser
unset($hostname);
//die(ENV);
/*************************************************************************************************
 * Interpreta os argumentos do CLI:                                                              *
 * -d      modo Debug                                                                            *
 * --debug  Ã­dem                                                                                 *
 *                                                                                               *
 ************************************************************************************************/

$sArgs ="d::h";
$longArgs = array('debug::','help');

if (isCLI):
	$cliArgs = getopt($sArgs, $longArgs);
else:
	$cliArgs = array_filter('anti_injection', $_GET);
endif;
if (isset($cliArgs['d']) or isset($cliArgs['debug'])) {
	$debug = $cliArgs['d'] . $cliArgs['debug'];
	$debug = (strlen($debug)) ? $debug : true;
}

if (isset($cliArgs['h']) or isset($cliArgs['help'])) {
	$hlp = <<<HELP
    Forma de usar:

    gera-pedido-os.php [-h|--help|-d[sql|res]|--debug=[sql|res]]

    Parâmetros:
        -h | --help   Esta ajuda
        -d | --debug  Aciona o modo debug.
                      Com --debug=res,    mostra o retorno das consultas.
                      Com --debug=sql,    mostra as consultas
                      Com --debug=noemail NãO manda o e-mail de LOG

HELP;
	die(utf8_encode($hlp));
}
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
	require_once __DIR__ . '/../../helpdesk/mlg_funciones.php';

    $data['fabrica'] 		= 30;
    $data['fabrica_nome'] 	= 'esmaltec';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['log'] 			= 2;
    $data['dir'] 			= "/tmp";
    $data['data_sistema'] 	= date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

	$fabrica = 30;
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();
    global $login_fabrica;
    $login_fabrica = 30;
	if (ENV == 'producao') {
		$data['dest'] 			= 'helpdesk@telecontrol.com.br';
		#$data['dest_cliente']   = 'ricardo.vicente@telecontrol.com.br';
		$data['dest_cliente']   = 'sistemas3@esmaltec.com.br,mario@esmaltec.com.br,oseletronica@esmaltec.com.br';
    }
	else {
    	$data['dest'] 			= 'thiago.tobias@telecontrol.com.br';
    	$data['dest_cliente']	= 'thiago.tobias@telecontrol.com.br';
    }

    extract($data);
	$dir = "$dir/$fabrica_nome";
    $arquivo_err = "{$dir}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$dir}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$dir}/ 2> /dev/null ; chmod 777 {$dir}/" );

    $sql = "SET DateStyle TO 'SQL,EUROPEAN';";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    }

    // ####################################################
    // INTERVENCAO REINCIDENCIA
    // ####################################################
    $sql = "SELECT  interv_reinc.os
    INTO TEMP    tmp_interv_reinc
    FROM    (
                SELECT  ultima_reinc.os,
                        (
                            SELECT  status_os
                            FROM    tbl_os_status
                            WHERE   tbl_os_status.os             = ultima_reinc.os
                            AND     tbl_os_status.fabrica_status = $fabrica
                            AND     status_os IN (13, 19, 67, 68, 70, 115, 118,187)
                      ORDER BY      os_status DESC
                            LIMIT   1
                        ) AS ultimo_reinc_status
                FROM    (
                            SELECT  DISTINCT
                                    os
                            FROM    tbl_os_status
                            WHERE   tbl_os_status.fabrica_status = $fabrica
                            AND     status_os IN (13, 19, 67, 68, 70, 115, 118,187)
                        ) ultima_reinc
            ) interv_reinc
    WHERE   interv_reinc.ultimo_reinc_status IN (13, 68, 70, 115, 118);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção Reincidente (13,19,68,67,70,115,118)'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

    // ####################################################
    // INTERVENCAO DE FÁBRICA
    // ####################################################

    $sql ="SELECT  interv_fabrica.os
       INTO TEMP    tmp_interv_fabrica
            FROM    (
                        SELECT  ultima_serie.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os             = ultima_serie.os
                                    AND     tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (62,64)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_serie_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (62,64)
                                ) ultima_serie
                    ) interv_fabrica
            WHERE   interv_fabrica.ultimo_serie_status IN (62);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção de Fábrica(13,62,64)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }


	// ####################################################
	// INTERVENCAO NUMERO DE SERIE
	// ####################################################
	$sql = "SELECT  interv_serie.os
       INTO TEMP    tmp_interv_serie
            FROM    (
                        SELECT  ultima_serie.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os             = ultima_serie.os
                                    AND     tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_serie_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                                ) ultima_serie
                    ) interv_serie
            WHERE   interv_serie.ultimo_serie_status IN (102,104);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção de Série (102,103,104)'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

	// ####################################################
	// NUMERO DE SERIE REPROVADAS
	// ####################################################
	$sql = "SELECT  serie_reprovadas.os
       INTO TEMP    tmp_serie_reprovadas
            FROM    (
                        SELECT  ultima_serie.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os             = ultima_serie.os
                                    AND     tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_serie_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                                ) ultima_serie
                    ) serie_reprovadas
            WHERE   serie_reprovadas.ultimo_serie_status IN (104);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Número de Série Rejeitadas (104)'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }


    // ####################################################
    // INTERVENCAO LGI
    // ####################################################
    $sql = "SELECT  interv_lgi.os
       INTO TEMP    tmp_interv_lgi
            FROM    (
                        SELECT ultima_lgi.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os                = ultima_lgi.os
                                    AND     tbl_os_status.fabrica_status    = $fabrica
                                    AND     status_os IN (105,106,107)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_lgi_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (105,106,107)
                                ) ultima_lgi
                    ) interv_lgi
            WHERE   interv_lgi.ultimo_lgi_status IN (105,107);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'intervenção de LGI (105,106,107)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }

    // ####################################################
	// LAUDO DE TROCA
	// ####################################################
    $sql = "SELECT  laudo_troca.os
       INTO TEMP    tmp_laudo_troca
            FROM    (
                        SELECT ultima_troca.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os                = ultima_troca.os
                                    AND     tbl_os_status.fabrica_status    = $fabrica
                                    AND     status_os IN (192,193,194)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_troca_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (192,193,194)
                                ) ultima_troca
                    ) laudo_troca
            WHERE   laudo_troca.ultimo_troca_status IN (192,194);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'LAUDO DE TROCA (192,193,194)'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

            // ####################################################
            // AUDITORIA DE PEÇAS OS COM PEÇAS EM AUDITORIA HD 2551514
            // ####################################################
            $sql = "SELECT  aud_peca.os
                INTO TEMP    tmp_auditoria_peca
                FROM    (
                            SELECT  ultima_aud.os,
                                    (
                                        SELECT  tbl_auditoria_os.auditoria_status
                                        FROM    tbl_auditoria_os
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_auditoria_os.os = ultima_aud.os
                                        AND     tbl_os.fabrica      = $fabrica
										AND     tbl_os.data_digitacao >= '2015-03-13 00:00:00'
                                        /* AND     tbl_os.posto        = 6359 */
                                        ORDER BY      tbl_auditoria_os.data_input DESC
                                        LIMIT   1
                                    ) AS ultimo_aud_status,
                                    (
                                        SELECT  tbl_auditoria_os.liberada
                                        FROM    tbl_auditoria_os
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_auditoria_os.os = ultima_aud.os
                                        AND     tbl_os.fabrica      = $fabrica
										AND     tbl_os.data_digitacao >= '2015-03-13 00:00:00'
                                        /* AND     tbl_os.posto        = 6359 */
                                        ORDER BY      tbl_auditoria_os.data_input DESC
                                        LIMIT   1
                                    ) AS ultimo_aud_liberada
                            FROM    (
                                        SELECT  DISTINCT
                                                os
                                        FROM    tbl_auditoria_os
                                        JOIN    tbl_os USING(os)
                                        WHERE   tbl_os.fabrica = $fabrica
										AND     tbl_os.data_digitacao >= '2015-03-13 00:00:00'
                                        /* AND     tbl_os.posto        = 6359 */
                                    ) ultima_aud
                        ) aud_peca
                WHERE   aud_peca.ultimo_aud_liberada IS NULL
                AND     aud_peca.ultimo_aud_status <> 1";
                $res = pg_query($con, $sql);

            if(pg_last_error($con)){
                $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'AUDITORIA DE PEÇAS'";
                $logs_erro[] = $sql;
                $logs[]      = pg_last_error($con);
                $erro        = true;
                throw new Exception ($msg_erro);
            }

	$data_filtro = '2010-07-20 00:00:00'; 	// data mínima da abertura da OS
						// gerarão pedidos dos itens de OSs a partir dessa data

	// ####################################################
	// POSTOS E SUAS OSs
	// ####################################################
	if (ENV == 'producao') {
        $lista_posto_teste = '6359';
        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND tipo_posto IN(SELECT tipo_posto FROM tbl_tipo_posto WHERE descricao = 'SAC' AND fabrica = {$fabrica})";

        $res = pg_query($con, $sql);

        for ($i=0; $i < pg_num_rows($res); $i++) {
            $lista_posto_teste .= ','.pg_fetch_result($res, $i, 'posto');
        }
		$sql_posto_teste = " AND tbl_os.posto NOT IN ({$lista_posto_teste})";
	 } else {
	 	// teste/dev
        //$sql_posto_teste = " AND tbl_posto.posto = 143632";
	 	 $sql_posto_teste = " AND tbl_os.posto = 6359";
	}

	$sqlPSE        = "SELECT tipo_posto FROM tbl_tipo_posto WHERE descricao = 'PSE' AND fabrica = $fabrica";
	$tipo_postoPSE = pg_fetch_result(pg_query($con, $sqlPSE), 0, 'tipo_posto');

	// HD-1203660 - JOIN com tbl_gera_pedido_dia
	$diaSemana = date('N');

    $sql = "SELECT  DISTINCT
                    tbl_os.posto,
                    tbl_os.os,
                    tbl_posto_fabrica.tipo_posto
       INTO TEMP    tmp_{$fabrica_nome}_gera_posto
            FROM    tbl_os_item
            JOIN    tbl_os_produto          ON  tbl_os_produto.os_produto               = tbl_os_item.os_produto
            JOIN    tbl_os                  ON  tbl_os.os                               = tbl_os_produto.os
            JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                 = tbl_os.posto
                                            AND tbl_posto_fabrica.fabrica               = $fabrica
            JOIN    tbl_servico_realizado   ON  tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                            AND tbl_servico_realizado.fabrica           = $fabrica
            JOIN    tbl_peca                ON  tbl_peca.peca                           = tbl_os_item.peca
                                            AND tbl_peca.fabrica                        = $fabrica
            WHERE   tbl_os_item.fabrica_i =  $fabrica
            AND     tbl_os_item.pedido    IS NULL
            AND     tbl_os.validada       IS NOT NULL
            AND     tbl_os.excluida       IS NOT TRUE
            AND     tbl_os.data_digitacao >= '$data_filtro'
            AND     tbl_os.fabrica        =  $fabrica
            {$sql_posto_teste}
            AND     (
                        credenciamento = 'CREDENCIADO'
                    OR  credenciamento = 'EM DESCREDENCIAMENTO'
                    )
            AND     tbl_servico_realizado.gera_pedido   IS TRUE
            AND     tbl_servico_realizado.ressarcimento IS FALSE
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_reinc )
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_auditoria_peca )
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_lgi )
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_laudo_troca )
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_serie )
            AND     (
                        tbl_os.os NOT IN ( SELECT os FROM tmp_serie_reprovadas )
                    OR  (
                            tbl_os.os IN ( SELECT os FROM tmp_serie_reprovadas )
                        AND tbl_os_item.admin IS NOT NULL
                        )
                    )
            AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_fabrica)
            AND     tbl_os.data_digitacao >= '2015-03-13 00:00:00'
            AND     (
                        tbl_posto_fabrica.tipo_posto    = $tipo_postoPSE
                    AND data_abertura                   > '2014-01-12'
                    OR  (
                            tbl_os.troca_garantia       IS NULL
                        AND tbl_os.troca_garantia_admin IS NULL
                        AND (
                            tbl_peca.remessa_garantia               IS TRUE
                        OR  tbl_peca.remessa_garantia_compressor    IS TRUE
                        )
                        AND tbl_os.finalizada     IS NULL
                    )
			);

			SELECT 	DISTINCT posto, tipo_posto
			FROM	tmp_{$fabrica_nome}_gera_posto ORDER BY posto;
        ";

	$res = pg_query($con, $sql);
    //echo $sql;

	if(pg_last_error($con)){
		if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'sql') !== false)
			echo $sql . "\n" . pg_last_error($con);
    	$logs[] 	 = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

	$condicao = 1825; // Garantia (banco de testes/desenvolvimento)

	if (ENV == 'producao') {
		$condicao = 1825; // Garantia (banco em produção)
	}

	$tipo_pedido['G'] = 231; // REMESSA EM GARANTIA
	$tipo_pedido['N'] = 231; // REMESSA EM GARANTIA
	$tipo_pedido['C'] = 233; // REMESSA EM GAR DE COMPRESSOR

	$total_postos = pg_num_rows($res);

    for ($i=0; $i < $total_postos; $i++) {

		$posto      = pg_fetch_result($res, $i, 'posto');
		$tipo_posto = pg_fetch_result($res, $i, 'tipo_posto');

		// ####################################################
		// PEÇAS/ITENS DA OS
		// ####################################################

        $sql = "SELECT  DISTINCT
                        tbl_os_item.peca,
                        tbl_os_item.qtde,
                        tbl_os_item.admin,
                        tbl_os.os,
                        tbl_os.posto,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_peca.referencia,
                        tbl_os_item.os_item,
                        (
                            SELECT  DISTINCT
                                    tbl_produto.linha
                            FROM    tbl_lista_basica
                            JOIN    tbl_produto USING (produto)
                            WHERE   tbl_lista_basica.peca = tbl_peca.peca
                            AND     tbl_os.produto        = tbl_produto.produto
                      ORDER BY      linha
                            LIMIT 1
                        ) AS linha,
                        0 as pedido,
                        CASE WHEN tbl_posto_fabrica.tipo_posto = $tipo_postoPSE AND tbl_peca.remessa_garantia IS TRUE
                             THEN 'N'
                             WHEN tbl_posto_fabrica.tipo_posto = $tipo_postoPSE AND tbl_peca.remessa_garantia_compressor IS TRUE
                             THEN 'C'
                             ELSE 'G'
                        END AS tipo_remessa
           INTO TEMP    tmp_os_item_{$fabrica_nome}_{$posto}
                FROM    tbl_os_item
                JOIN    tbl_os_produto         ON  tbl_os_produto.os_produto               = tbl_os_item.os_produto
                JOIN    tbl_os                 ON  tbl_os.os                               = tbl_os_produto.os
                JOIN    tbl_posto_fabrica      ON  tbl_posto_fabrica.posto                 = tbl_os.posto
                                               AND tbl_posto_fabrica.fabrica               = $fabrica
                JOIN    tbl_servico_realizado  ON  tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                                               AND tbl_servico_realizado.fabrica           = $fabrica
                JOIN    tbl_peca               ON  tbl_peca.peca                           = tbl_os_item.peca
                JOIN    (
                            SELECT  estado,
                                    tipo_posto,
                                    tipo_pedido
                            FROM    tbl_gera_pedido_dia
                            WHERE   dia_semana = (SELECT to_char(CURRENT_DATE - INTERVAL '1 day','D')::INTEGER )
                            AND     fabrica = $fabrica
                            AND     ativo
                        ) AS tbl_pedido_dia     ON  tbl_posto_fabrica.contato_estado        = tbl_pedido_dia.estado
                                                AND tbl_posto_fabrica.tipo_posto            = tbl_pedido_dia.tipo_posto
                                                AND tbl_pedido_dia.tipo_pedido IN (231, 233)
                                                AND tbl_peca.fabrica                        = $fabrica
                WHERE   tbl_os_item.pedido                  IS NULL
                AND     tbl_os.validada                     IS NOT NULL
                AND     tbl_os.data_digitacao               >= '$data_filtro'
                AND     tbl_os.excluida                     IS NOT TRUE
                AND     tbl_os.fabrica                      =  $fabrica
                AND     tbl_os.posto                        =  $posto
                AND     tbl_os.troca_garantia               IS NULL
                AND     tbl_os.troca_garantia_admin         IS NULL
                AND     tbl_os.os                           NOT IN (SELECT os FROM tmp_interv_reinc)
                AND     tbl_os.os                           NOT IN (SELECT os FROM tmp_interv_lgi)
                AND     tbl_os.os                           NOT IN (SELECT os FROM tmp_laudo_troca)
                AND     tbl_os.os                           NOT IN (SELECT os FROM tmp_auditoria_peca)
                AND     tbl_os.os                           NOT IN (SELECT os FROM tmp_interv_fabrica)
                AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_serie )
            AND     (
                        tbl_os.os NOT IN ( SELECT os FROM tmp_serie_reprovadas )
                    OR  (
                            tbl_os.os IN ( SELECT os FROM tmp_serie_reprovadas )
                        AND tbl_os_item.admin IS NOT NULL
                        )
                    )
                AND     tbl_servico_realizado.gera_pedido   IS TRUE
                AND     tbl_servico_realizado.ressarcimento IS FALSE
                AND     (
                            tbl_posto_fabrica.tipo_posto           =  $tipo_postoPSE and data_abertura > '2014-01-12'
                        OR  (
                                tbl_os.troca_garantia       IS NULL
                            AND tbl_os.troca_garantia_admin IS NULL
                            AND tbl_os.finalizada           IS NULL
                            AND (
                                    tbl_peca.remessa_garantia               IS TRUE
                                OR  tbl_peca.remessa_garantia_compressor    IS TRUE
                                )
                            )
                        )
           ORDER BY   linha,   tbl_os.os ASC;";

		$res_tmp = pg_query($con, $sql);

        $sql = "SELECT os FROM tmp_os_item_{$fabrica_nome}_{$posto}";
        $resT = pg_query($con,$sql);
        $os_interacao = pg_fetch_all($resT);
		$os_interacao = array_filter($os_interacao);

		if (!is_resource($res_tmp)) {
			if (isCLI and !isRoot and strpos($debug, 'sql') !== false)
				echo $sql . "\n" . pg_last_error($con);
			throw New Exception ('ERRO query temp pega peças.' . pg_errormessage($con) . $sql);
		}

		foreach($tipo_pedido as $key => $val) {

			// ####################################################
			// PEÇAS/ITENS DA OS - AGRUPA POR PEÇA E LINHA
			// SEPARA POR TIPO DE PEDIDO DE REMESSA
			// ####################################################
			if($key != 'C'){
				$sql = "SELECT  codigo_posto        ,
                                referencia          ,
                                peca                ,
                                os                    ,
                                os_item             ,
                                SUM(qtde) AS qtde   ,
								case when linha isnull then 420 else linha end as linha
						FROM    tmp_os_item_{$fabrica_nome}_{$posto}
                        WHERE   tipo_remessa = '$key'
                  GROUP BY      codigo_posto,referencia,peca, linha,os_item, os;";
			}else{
				$sql = "SELECT  codigo_posto        ,
                                referencia          ,
                                peca                ,
                                os                  ,
                                os_item             ,
                                qtde                ,
								case when linha isnull then 420 else linha end as linha
                        FROM    tmp_os_item_{$fabrica_nome}_{$posto}
                        WHERE   tipo_remessa = '$key';";
			}

			$res_peca = pg_query($con, $sql);
            $isPostoTipoAtende = isPostoTipoAtende($posto);
            if($isPostoTipoAtende){
                $dadosPecas = pg_fetch_all($res_peca);
                $pecasAgrupadas = agrupaPecasPorOrigem($dadosPecas);
            }


			if (is_resource($res_peca)) {

				if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'res') !== false)
					echo array2CSV($res_peca, chr(9)) . chr(10);

				$total_pecas = pg_num_rows($res_peca);

			}

			if ($total_pecas > 0) {
                /**#########################
                 * POSTO TIPO ATENDE = TRUE#
                 * #########################
                **/
//                 $numero_pedidos = array();
                if($isPostoTipoAtende){
					$pedido = null;
                    try{
                        pg_query($con, "BEGIN TRANSACTION");
                        foreach($pecasAgrupadas as $origem => $pecas){
            		if($key != 'C'){
            			if(count($pecas) > 0) {
            				$erro_pedido = false;

            				// ####################################################
            				// GRAVA PEDIDO
            				// ####################################################
            				// Coluna Linha não estava sendo utilizada, caso precisar é só adicionar novamente
            				$sql = "INSERT INTO tbl_pedido (
            					posto        ,
            					fabrica      ,
            					condicao     ,
            					tipo_pedido  ,
            					status_pedido,
            					pedido_os
            				) VALUES (

            					$posto      ,
            					$fabrica    ,
            					$condicao   ,
            			{$tipo_pedido[$key]},
            			1           ,
            			't'
            		) RETURNING pedido;";

            				$res_pedido = pg_query($con, $sql);

            				if(pg_last_error($con)){
            					$logs[] 	 = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO";
            					if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'sql') !== false)
            						echo $sql . "\n" . pg_last_error($con);
            					$logs_erro[] = $sql;
            					$logs[] 	 = pg_last_error($con);
            					$erro   	 = true;
            					$erro_pedido = true;
            				} else {
            					$pedido = pg_fetch_result($res_pedido, 0);
//             					array_push($numero_pedidos,$pedido);
            				}

            				if($origem === 'NAC'){
            					$seuPedido = 'F'.$pedido;
            				}else{
            					$seuPedido = 'T'.$pedido;
            				}

            				$update = "UPDATE tbl_pedido SET seu_pedido = '".$seuPedido."' WHERE pedido = ". $pedido;
            				pg_query($con, $update);
            				if(strlen(pg_last_error($con)) > 0){
            					throw new Exception("Erro ao gravar seu_pedido");
            				}
            				foreach($pecas as $peca => $dados){
            					if(!empty($pedido) AND !$erro_pedido){
            						// ####################################################
            						// GRAVA ITENS DO PEDIDO
            						// ####################################################
            						$sql = "INSERT INTO tbl_pedido_item (
            							pedido,
            							peca  ,
            							qtde  ,
            							qtde_faturada,
            							qtde_cancelada
            						) VALUES (
            							$pedido,"
            							.$dados['peca'] . " ,"
            							.$dados['qtde']."  ,
            							0      ,
            							0
            						) RETURNING pedido_item;";

            						$res_pedido_item = pg_query($con, $sql);

            						if(pg_last_error($con)){
            							$logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO ITEM (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
            							$logs_erro[] 	= $sql;
            							$logs[] 		= pg_last_error($con);
            							$erro   		= true;
            							$erro_pedido	= true;
            						}
            						else {
            							$pedido_item = pg_fetch_result($res_pedido_item, 0);
            						}

            						// ####################################################
            						// PASSA ITENS PELA FUNCAO
            						// ####################################################
            						$sql = "SELECT fn_atualiza_os_item_pedido_item(".$dados['os_item'].", $pedido, $pedido_item, $fabrica)";

            						$res_atualiza_pedido_item = pg_query($con, $sql);

            						if(pg_last_error($con)){
            							$logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função atualiza OS item (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
            							$logs_erro[] 	= $sql;
            							$logs[] 		= pg_last_error($con);
            							$erro   		= true;
            							$erro_pedido	= true;
            						}

            						// ####################################################
            						// ATUALIZA O PEDIDO NOS ITENS DA OS
            						// ####################################################
            						$sql = "UPDATE tmp_os_item_{$fabrica_nome}_{$posto}
            							SET pedido = $pedido
            							WHERE os_item = ".$dados['os_item'].";";
            						$res_atualiza_pedido_item = pg_query($con, $sql);

            						if(pg_last_error($con)){
            							$logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: TMP atualiza pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
            							$logs_erro[] 	= $sql;
            							$logs[] 		= pg_last_error($con);
            							$erro   		= true;
            							$erro_pedido	= true;
            						}
            					}
            				}
            				try{
            					// ####################################################
            					// PASSA NA FUNCAO DE FINALIZACAO DO PEDIDO
            					// ####################################################
            					$sql = "SELECT fn_pedido_finaliza($pedido, $fabrica);";
            					$res_atualiza_pedido_item = pg_query($con, $sql);

            					if(pg_last_error($con)){
            						throw new Exception("Erro ao gerar pedido".pg_last_error($con));
            					}
            				}catch(Exception $ex){

            					$logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
            					$logs_erro[] 	= $sql;
            					$logs[] 		= pg_last_error($con);
            					$logs_cliente[] = array(
            						'posto'	=> $posto,
            						'peca'	=> $peca,
            						'referencia'=>'referencia',
            						'erro'	=> pg_last_error($con)
            					);
            					$erro   		= true;
            					$erro_pedido	= true;

            				}
            			}
                            }else{
                                $erro_pedido = false;
                                for ($x=0; $x<$total_pecas; $x++) {
									$pedido = null;
                                    // ####################################################
                                    // GRAVA PEDIDO
                                    // ####################################################
                                    $sql = "INSERT INTO tbl_pedido (
                                                posto        ,
                                                fabrica      ,
                                                condicao     ,
                                                tipo_pedido  ,
                                                status_pedido,
                                                pedido_os    ,
                                                linha
                                            ) VALUES (
                                                $posto      ,
                                                $fabrica    ,
                                                $condicao   ,
                                                {$tipo_pedido[$key]},
                                                1           ,
                                                't'         ,
                                                $linha
                                            ) RETURNING pedido;";
                                    $res_pedido = pg_query($con, $sql);

                                    if(pg_last_error($con)){
                                        $logs[] 	 = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO";
                                        if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'sql') !== false)
                                            echo $sql . "\n" . pg_last_error($con);
                                        $logs_erro[] = $sql;
                                        $logs[] 	 = pg_last_error($con);
                                        $erro   	 = true;
                                        $erro_pedido = true;
                                    }else {
                                        $pedido = pg_fetch_result($res_pedido, 0);
//                                         array_push($numero_pedidos,$pedido);
                                    }

                                    $peca         = pg_fetch_result($res_peca,$x,'peca');
                                    $qtde         = pg_fetch_result($res_peca,$x,'qtde');
                                    $codigo_posto = pg_fetch_result($res_peca,$x,'codigo_posto');
                                    $referencia   = pg_fetch_result($res_peca,$x,'referencia');
                                    $os_item      = pg_fetch_result($res_peca,$x,'os_item');

                                    if(!empty($pedido) AND !$erro_pedido){
                                        // ####################################################
                                        // GRAVA ITENS DO PEDIDO
                                        // ####################################################
                                        $sql = "INSERT INTO tbl_pedido_item (
                                                    pedido,
                                                    peca  ,
                                                    qtde  ,
                                                    qtde_faturada,
                                                    qtde_cancelada
                                                ) VALUES (
                                                    $pedido,
                                                    $peca  ,
                                                    $qtde  ,
                                                    0      ,
                                                    0
                                                ) RETURNING pedido_item;";
                                        $res_pedido_item = pg_query($con, $sql);

                                        if(pg_last_error($con)){
                                            $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO ITEM (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                            $logs_erro[] 	= $sql;
                                            $logs[] 		= pg_last_error($con);
                                            $erro   		= true;
                                            $erro_pedido	= true;
                                        } else {
                                            $pedido_item = pg_fetch_result($res_pedido_item, 0);
                                        }

                                        // ####################################################
                                        // PASSA ITENS PELA FUNCAO
                                        // ####################################################
                                        $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item, $pedido, $pedido_item, $fabrica)";
                                        $res_atualiza_pedido_item = pg_query($con, $sql);

                                        if(pg_last_error($con)){
                                            $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função atualiza OS item (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                            $logs_erro[] 	= $sql;
                                            $logs[] 		= pg_last_error($con);
                                            $erro   		= true;
                                            $erro_pedido	= true;
                                        }

                                        // ####################################################
                                        // ATUALIZA O PEDIDO NOS ITENS DA OS
                                        // ####################################################
                                        $sql = "UPDATE tmp_os_item_{$fabrica_nome}_{$posto}
                                            SET pedido = $pedido
                                            WHERE os_item = $os_item;";
                                        $res_atualiza_pedido_item = pg_query($con, $sql);

                                        if(pg_last_error($con)){
                                            $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: TMP atualiza pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                            $logs_erro[] 	= $sql;
                                            $logs[] 		= pg_last_error($con);
                                            $erro   		= true;
                                            $erro_pedido	= true;
                                        }
                                    }
                                    try{
                                        // ####################################################
                                        // PASSA NA FUNCAO DE FINALIZACAO DO PEDIDO
                                        // ####################################################
                                        $sql = "SELECT fn_pedido_finaliza($pedido, $fabrica);";
                                        $res_atualiza_pedido_item = pg_query($con, $sql);

                                        if(pg_last_error($con)){
                                            throw new Exception("Erro ao gerar pedido");
                                        }
                                    }catch(Exception $ex){
                                        $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                        $logs_erro[] 	= $sql;
                                        $logs[] 		= pg_last_error($con);
                                        $logs_cliente[] = array(
                                            'posto'	=> $posto,
                                            'peca'	=> $peca,
                                            'referencia'=>'referencia',
                                            'erro'	=> pg_last_error($con)
                                        );
                                        $erro   		= true;
                                        $erro_pedido	= true;

                                    }
                                }
                            }

                        }
                    }catch(Exception $ex){
                        pg_query($con, "ROLLBACK TRANSACTION");
                        $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                        $logs_erro[] 	= $sql;
                        $logs[] 		= pg_last_error($con);
                        $logs_cliente[] = array(
                            'posto'	=> $posto,
                            'peca'	=> $peca,
                            'referencia'=>'referencia',
                            'erro'	=> pg_last_error($con)
                        );
                        $erro   		= true;
                        $erro_pedido	= true;


                    }

                    if(!$erro_pedido) {
                        pg_query($con, "COMMIT TRANSACTION");
                    }else{
                        pg_query($con, "ROLLBACK TRANSACTION");
                    }
                }else{
					extract(pg_fetch_assoc($res_peca));
					$pedido = null;
                    pg_query($con, "BEGIN TRANSACTION");
                    $total_pecas = pg_num_rows($res_peca);
                    if($key != 'C'){

                        $erro_pedido = false;

                        // ####################################################
                        // GRAVA PEDIDO
                        // ####################################################
                        $sql = "INSERT INTO tbl_pedido (
                                                 posto        ,
                                                 fabrica      ,
                                                 condicao     ,
                                                 tipo_pedido  ,
                                                 status_pedido,
                                                 pedido_os    ,
                                                 linha
                                             ) VALUES (
                                                 $posto      ,
                                                 $fabrica    ,
                                                 $condicao   ,
                                         {$tipo_pedido[$key]},
                                         1           ,
                                         't'         ,
                                         $linha
                                     ) RETURNING pedido;";
                        $res_pedido = pg_query($con, $sql);

                        if(pg_last_error($con)){
                            $logs[] 	 = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO";
                            if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'sql') !== false)
                                echo $sql . "\n" . pg_last_error($con);
                            $logs_erro[] = $sql;
                            $logs[] 	 = pg_last_error($con);
                            $erro   	 = true;
                            $erro_pedido = true;
                        }
                        else {
                            $pedido = pg_fetch_result($res_pedido, 0);
//                             array_push($numero_pedidos,$pedido);
                        }


                        for ($x=0; $x<$total_pecas; $x++) {

                            $peca         = pg_fetch_result($res_peca,$x,'peca');
                            $qtde         = pg_fetch_result($res_peca,$x,'qtde');
                            $codigo_posto = pg_fetch_result($res_peca,$x,'codigo_posto');
                            $referencia   = pg_fetch_result($res_peca,$x,'referencia');
                            $os_item      = pg_fetch_result($res_peca,$x,'os_item');

                            if(!empty($pedido) AND !$erro_pedido){
                                // ####################################################
                                // GRAVA ITENS DO PEDIDO
                                // ####################################################
                                $sql = "INSERT INTO tbl_pedido_item (
                                                         pedido,
                                                         peca  ,
                                                         qtde  ,
                                                         qtde_faturada,
                                                         qtde_cancelada
                                                     ) VALUES (
                                                         $pedido,
                                                         $peca  ,
                                                         $qtde  ,
                                                         0      ,
                                                         0
                                                     ) RETURNING pedido_item;";
                                $res_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO ITEM (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }
                                else {
                                    $pedido_item = pg_fetch_result($res_pedido_item, 0);
                                }

                                // ####################################################
                                // PASSA ITENS PELA FUNCAO
                                // ####################################################
                                $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item, $pedido, $pedido_item, $fabrica)";

                                $res_atualiza_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função atualiza OS item (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }

                                // ####################################################
                                // ATUALIZA O PEDIDO NOS ITENS DA OS
                                // ####################################################
                                $sql = "UPDATE tmp_os_item_{$fabrica_nome}_{$posto}
                                    SET pedido = $pedido
                                    WHERE os_item = $os_item;";
                                $res_atualiza_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: TMP atualiza pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }
                            }
                        }

                        try{
                            // ####################################################
                            // PASSA NA FUNCAO DE FINALIZACAO DO PEDIDO
                            // ####################################################
                            $sql = "SELECT fn_pedido_finaliza($pedido, $fabrica);";
                            $res_atualiza_pedido_item = pg_query($con, $sql);

                            if(pg_last_error($con)){
                                throw new Exception("Erro ao gerar pedido".pg_last_error($con));
                            }
                        }catch(Exception $ex){

                            $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                            $logs_erro[] 	= $sql;
                            $logs[] 		= pg_last_error($con);
                            $logs_cliente[] = array(
                                'posto'	=> $posto,
                                'peca'	=> $peca,
                                'referencia'=>'referencia',
                                'erro'	=> pg_last_error($con)
                            );
                            $erro   		= true;
                            $erro_pedido	= true;

                        }

                    }else{
                        $erro_pedido = false;
                        for ($x=0; $x<$total_pecas; $x++) {
							$pedido = null;
                            // ####################################################
                            // GRAVA PEDIDO
                            // ####################################################
                            $sql = "INSERT INTO tbl_pedido (
                                                     posto        ,
                                                     fabrica      ,
                                                     condicao     ,
                                                     tipo_pedido  ,
                                                     status_pedido,
                                                     pedido_os    ,
                                                     linha
                                                 ) VALUES (
                                                     $posto      ,
                                                     $fabrica    ,
                                                     $condicao   ,
                                             {$tipo_pedido[$key]},
                                             1           ,
                                             't'         ,
                                             $linha
                                         ) RETURNING pedido;";
                            $res_pedido = pg_query($con, $sql);

                            if(pg_last_error($con)){
                                $logs[] 	 = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO";
                                if (isCLI and !isRoot and ENV=='dev' and strpos($debug, 'sql') !== false)
                                    echo $sql . "\n" . pg_last_error($con);
                                $logs_erro[] = $sql;
                                $logs[] 	 = pg_last_error($con);
                                $erro   	 = true;
                                $erro_pedido = true;
                            }
                            else {
                                $pedido = pg_fetch_result($res_pedido, 0);
//                                 array_push($numero_pedidos,$pedido);
                            }

                            $peca         = pg_fetch_result($res_peca,$x,'peca');
                            $qtde         = pg_fetch_result($res_peca,$x,'qtde');
                            $codigo_posto = pg_fetch_result($res_peca,$x,'codigo_posto');
                            $referencia   = pg_fetch_result($res_peca,$x,'referencia');
                            $os_item      = pg_fetch_result($res_peca,$x,'os_item');

                            if(!empty($pedido) AND !$erro_pedido){
                                // ####################################################
                                // GRAVA ITENS DO PEDIDO
                                // ####################################################
                                $sql = "INSERT INTO tbl_pedido_item (
                                                         pedido,
                                                         peca  ,
                                                         qtde  ,
                                                         qtde_faturada,
                                                         qtde_cancelada
                                                     ) VALUES (
                                                         $pedido,
                                                         $peca  ,
                                                         $qtde  ,
                                                         0      ,
                                                         0
                                                     ) RETURNING pedido_item;";
                                $res_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO ITEM (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }
                                else {
                                    $pedido_item = pg_fetch_result($res_pedido_item, 0);
                                }

                                // ####################################################
                                // PASSA ITENS PELA FUNCAO
                                // ####################################################
                                $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item, $pedido, $pedido_item, $fabrica)";
                                $res_atualiza_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função atualiza OS item (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }

                                // ####################################################
                                // ATUALIZA O PEDIDO NOS ITENS DA OS
                                // ####################################################
                                $sql = "UPDATE tmp_os_item_{$fabrica_nome}_{$posto}
                                    SET pedido = $pedido
                                    WHERE os_item = $os_item;";
                                $res_atualiza_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: TMP atualiza pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                    $logs_erro[] 	= $sql;
                                    $logs[] 		= pg_last_error($con);
                                    $erro   		= true;
                                    $erro_pedido	= true;
                                }
                            }
                            try{
                                // ####################################################
                                // PASSA NA FUNCAO DE FINALIZACAO DO PEDIDO
                                // ####################################################
                                $sql = "SELECT fn_pedido_finaliza($pedido, $fabrica);";
                                $res_atualiza_pedido_item = pg_query($con, $sql);

                                if(pg_last_error($con)){
                                    throw new Exception("Erro ao gerar pedido");
                                }
                            }catch(Exception $ex){
                                $logs[] 		= $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$codigo_posto} - Peça: {$referencia} - Qtd: {$qtde})";
                                $logs_erro[] 	= $sql;
                                $logs[] 		= pg_last_error($con);
                                $logs_cliente[] = array(
                                    'posto'	=> $posto,
                                    'peca'	=> $peca,
                                    'referencia'=>'referencia',
                                    'erro'	=> pg_last_error($con)
                                );
                                $erro   		= true;
                                $erro_pedido	= true;

                            }
                        }
                    }

                    if(!$erro_pedido) {
                        pg_query($con, "COMMIT TRANSACTION");
                    }else{
                        pg_query($con, "ROLLBACK TRANSACTION");
                    }

                }
			}
		}

		reset($tipo_pedido);


		// ####################################################
		// DADOS DO POSTO PARA LOG
		// ####################################################
		$sql_posto = "
					  SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					    FROM tbl_posto
					    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_posto.posto
											  AND tbl_posto_fabrica.fabrica = $fabrica
					   WHERE tbl_posto.posto    = $posto
					   LIMIT 1";
    	$res_posto = pg_query($con, $sql_posto);

    	$codigo_posto = pg_fetch_result($res_posto, 0, 'codigo_posto');
    	$nome_posto   = pg_fetch_result($res_posto, 0, 'nome');

	    if(!$erro_pedido) {
	    	$logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";
	    }
		else {
	    	$logs[] = "ERRO => Posto: '{$codigo_posto} - {$nome_posto}' - Não gerou pedido!";
	    }
    }

    if(count($os_interacao) > 0){
        foreach($os_interacao as $oss){

            $os = $oss["os"];

            $sql = "
                SELECT  tbl_os_item.pedido      ,
                        tbl_os_item.pedido_item ,
                        tbl_peca.descricao
                FROM    tbl_os_item
                JOIN    tbl_os_produto  USING(os_produto)
                JOIN    tbl_os          USING(os)
                JOIN    tbl_peca        USING(peca)
                WHERE   tbl_os.fabrica  = $fabrica
                AND     tbl_os.os       = $os
				AND		tbl_os_item.pedido notnull
          ORDER BY      tbl_os_item.pedido,
                        tbl_os_item.pedido_item
            ";
            $res        = pg_query($con,$sql);
            $qtde       = pg_num_rows($res);

            if($qtde > 0){
                for($c = 0;$c < $qtde;$c++){
                    $pedido      = pg_fetch_result($res,$c,pedido);
                    $pedido_peca = pg_fetch_result($res,$c,descricao);

                    $results[$pedido][] = $pedido_peca;
                }
            }

            foreach($results as $res_pedido=>$valores){
                $pecas = implode(", ",$valores);
                $frase = "O pedido ".$res_pedido." foi gerado com as seguintes pe&ccedil;as: ".$pecas."\n";

                $res = pg_query($con,"BEGIN TRANSACTION");

                $sqlIns = "
                    INSERT INTO tbl_os_interacao (
                        os,
                        data,
                        comentario,
                        interno,
                        fabrica
                    ) VALUES (
                        $os,
                        CURRENT_TIMESTAMP,
                        '$frase',
                        TRUE,
                        $fabrica
                    )
                ";
                $resIns = pg_query($con,$sqlIns);

                if(!pg_last_error($con)){
                    $res = pg_query($con,"COMMIT TRANSACTION");
                }else{
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }
    }

    if(count($logs_cliente)) {
    	$msg = array();
    	$msg[] = "Erro na geração de pedido<br />";

    	foreach ($logs_cliente AS $log) {
    		$posto 	= $log['posto'];
    		$peca 	= $log['peca'];
    		$referencia 	= $log['referencia'];
    		$error 	= $log['erro'];

			// ####################################################
			// DADOS DO POSTO
			// ####################################################
			$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
					  FROM tbl_posto_fabrica
					  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					 WHERE tbl_posto_fabrica.posto      = $posto
					   AND tbl_posto_fabrica.fabrica    = $fabrica
					 LIMIT 1";
    		$res = pg_query($con, $sql);

    		$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');
    		$nome 	      = pg_fetch_result($res, 0, 'nome');

    		$msg[] = "O Posto '{$codigo_posto} - {$nome}' n&atilde;o gerou pedido! <br>Verifique a pe&ccedil;a $referencia";
    	}

    	$msg[] = "<br>Att.<br>Telecontrol Networking";

    	$mailer = new PHPMailer();
		$mailer->IsSMTP();
		$mailer->IsHTML();
		$mailer->AddReplyTo("helpdesk@telecontrol.com.br", "Helpdesk Telecontrol");

		$emails = explode(",", $dest_cliente);

		if(count($emails)){
		    foreach ($emails as $email) {
		        $mailer->AddAddress($email);
		    }
		}
		else {
		    $mailer->AddAddress($dest_cliente);
		}

		$mensagem  = implode("<br />", $msg);

		$mailer->Subject = date('d/m/Y')." - Erro na gera&ccedil;&atilde;o de pedido";
	    $mailer->Body = $mensagem;
	    $mailer->Send();
    }

    if(count($logs) > 0){
    	$file_log = fopen($arquivo_log, "w+");
        fputs($file_log, implode("\r\n", $logs));
        fclose($file_log);
    }

    //envia email para Suporte
    if($erro AND count($logs_erro) > 0) {
		$file_log = fopen($arquivo_err,"w+");
		fputs($file_log,implode("\r\n", $logs));
			fputs($file_log,"\r\n ####################### SQL ####################### \r\n");
			fputs($file_log,implode("\r\n", $logs_erro));
		}

		fclose($file_log);

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML();
		$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Helpdesk Telecontrol");
		$mail->Subject = date('d/m/Y')." - Erro na geração de pedido (gera-pedido-os.php)";
		$mail->Body = $mensagem;
		$mail->AddAddress($dest);

		if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0) {
			$mail->AddAttachment($arquivo_err);
		}

		if (strpos($debug, 'nomail') === false)
			$mail->Send();

	$phpCron->termino();

} catch (Exception $e) {

	echo $e->getMessage(); die();
	$imp_log = implode(SEP . SEP, $logs);
    $msg = "Arquivo: ".__FILE__."\r\n" . SEP . "Linha: " . $e->getLine() . "\r\n" . SEP . "Descrição do erro: " . $e->getMessage() ."<hr />" . SEP . SEP . $imp_log;

	if(isCLI and ENV=='dev') {
		if ($debug == '2' or strpos($debug, 'sql')!==false)
			print_r($logs_erro);
		echo strtoupper($fabrica_nome)." - Erro na geração de pedido (gera-pedido-os.php):\n$msg\n\n<END OF SCRIPT>\n";
	} else {
		Log::envia_email($data, date('d/m/Y H:i:s')." - ".strtoupper($fabrica_nome)." - Erro na geração de pedido (gera-pedido-os.php)", $msg);
	}
}

function agrupaPecasPorOrigem($dadosPeca){
    global $con;
    $pecasAgrupadas = array('NAC'=>array(), 'NAONAC'=>array());

    foreach($dadosPeca as $peca){
        $sqlOrigem = "SELECT origem FROM tbl_peca WHERE peca = ". $peca['peca'];
        $res = pg_query($con, $sqlOrigem);

        if(strlen(pg_last_error($con)) > 0){
            throw new Exception("Erro ao selecionar origem da peça");
        }

        $origem = pg_fetch_result($res,0,'origem');

        if(empty($origem) || $origem !== 'NAC'){
            $pecasAgrupadas['NAONAC'][$peca['os_item']] = array('codigo_posto'=>$peca['codigo_posto'], 'referencia'=>$peca['referencia'], 'os_item'=>$peca['os_item'], 'qtde'=>$peca['qtde'], 'linha'=>$peca['linha'] , 'peca' =>$peca['peca']);
        }else{
            $pecasAgrupadas['NAC'][$peca['os_item']] = array('codigo_posto'=>$peca['codigo_posto'], 'referencia'=>$peca['referencia'], 'os_item'=>$peca['os_item'], 'qtde'=>$peca['qtde'], 'linha'=>$peca['linha'], 'peca' =>$peca['peca']);
        }
    }
    return $pecasAgrupadas;
}
function isPostoTipoAtende($posto){
    global $con;
    global $login_fabrica;

    $sql = "SELECT tipo_atende FROM tbl_posto_fabrica where fabrica = $login_fabrica AND posto = $posto AND tipo_atende is true";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con)) > 0){
        throw new Exception("Erro ao verificar tipo do posto (tipo atende)");
    }

    if(pg_num_rows($res) > 0  ){
       return true;
    }
    return false;
}

?>
