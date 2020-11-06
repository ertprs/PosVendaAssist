<?php
/**
 * Autor: Felipe Marttos Putti
 * Data: 26/01/2017
 */

error_reporting(E_ALL ^ E_NOTICE);

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

// define('DEBUG', true);
// include '../../helpdesk/mlg_funciones.php'; // para debugar...

$login_fabrica = 158;
$data_ontem    = date('Y-m-d', strtotime('-1 day'));
$dia_mes_ontem = date('d/m', strtotime('-1 day'));

$email_admin['dest'] = array(
    'telecontrol.resumoos@imberacooling.com'
);

#$email_admin['dest'] = array('gaspar.lucas@telecontrol.com.br', 'rafael.rodrigues@telecontrol.com.br');


#$email_admin['dest'] = array('ronald.santos@telecontrol.com.br','wagfelipe49@gmail.com','bruno.santos@telecontrol.com.br');

/**
 * Permite receber dois prâmetros por linha de comando, úteis para fazer
 * testes em produção:
 * -d<data ISO>
 * -e<destinatário(s)>
 * ex.:
 *   $ php resumo_os.php -d=2017-05-08 -e=suporte@telecontrol.com.br
 */
if ($argc > 1) {
    $opt = getopt('d::e:');

    if (isset($opt['d'])) {
        $data_ontem    = $opt['d'];
        $dia_mes_ontem = substr($data_ontem, -2) . '/' . substr($data_ontem, 5, 2);
    }
    if (isset($opt[e])) {
        $email_admin['dest'] = explode(',', $opt['e']);
    }
}

$de_para = array(
    "ZKR5" => "Preventiva",   // "preventiva",
    "ZKR6" => "Sanitização",  // "sanitizacao",
    "ZKR3" => "Corretiva",    // "corretiva",
    "ZKR9" => "Piso",         // "piso",
    "ZKR1" => "Movimentação", // "movimentacao",
    "ZKR2" => "Movimentação"  // "movimentacao"
);

$sqlAbe = "
    SELECT
          COALESCE(ta.codigo, '999') AS codigo_atendimento,
          COALESCE(ta.descricao, 'NÃO DEFINIDO') AS atendimento_descricao,
          f.familia,
          f.descricao AS descricao,
          COALESCE(xabertas.oss,0) AS Abertas,
          COALESCE(xfechadas.oss,0) AS Fechadas,
          COALESCE(xrecebidas.oss,0) AS Recebidas
    FROM tbl_familia f
    LEFT JOIN (
          SELECT DISTINCT p.familia, JSON_FIELD(JSON_FIELD('tipoOrdem', hcc.dados), '{\"ZKR5\":\"90\",\"ZKR6\":\"90\",\"ZKR3\":\"10\",\"ZKR9\":\"40\",\"ZKR1\":\"30\",\"ZKR2\":\"30\"}')::INT AS codigo, COUNT(hcc.hd_chamado_cockpit) AS oss
            FROM tbl_hd_chamado_cockpit hcc
              JOIN tbl_routine_schedule_log rsl USING(routine_schedule_log)
              JOIN tbl_routine_schedule rs USING(routine_schedule)
              JOIN tbl_routine r USING(routine)
              JOIN tbl_produto p ON p.referencia = JSON_FIELD('modeloKof', hcc.dados) AND p.fabrica_i = {$login_fabrica}
              JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
            WHERE hcc.fabrica = {$login_fabrica}
              AND rsl.create_at BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
              AND r.active IS TRUE
              AND r.context ILIKE '%Imp. Arquivos%'
          GROUP BY codigo, p.familia
        ) AS xrecebidas ON xrecebidas.familia = f.familia
        LEFT JOIN (
          SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
            FROM tbl_os AS o
              JOIN tbl_os_produto op USING(os)
              JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
              JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = o.hd_chamado AND hcc.fabrica = {$login_fabrica}
              JOIN tbl_produto xp ON xp.produto = op.produto AND xp.referencia = JSON_FIELD('modeloKof', hcc.dados) AND xp.fabrica_i = {$login_fabrica}
	      JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
            WHERE o.finalizada IS NULL
              AND o.data_abertura BETWEEN '{$data_ontem}'::date - INTERVAL '6 MONTHS' AND '{$data_ontem}'
              AND o.fabrica = {$login_fabrica}
              AND o.excluida IS NOT TRUE
          GROUP BY ta.codigo, xp.familia
        ) AS xabertas ON xabertas.familia = f.familia
        LEFT JOIN (
          SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
            FROM tbl_os o
              JOIN tbl_os_produto op USING(os)
              JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
              JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = o.hd_chamado AND hcc.fabrica = {$login_fabrica}
              JOIN tbl_produto xp ON xp.produto = op.produto AND xp.referencia = JSON_FIELD('modeloKof', hcc.dados) AND xp.fabrica_i = {$login_fabrica}
              JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
            WHERE o.finalizada BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
              AND o.fabrica = {$login_fabrica}
              AND o.excluida IS NOT TRUE
          GROUP BY ta.codigo, xp.familia
        ) xfechadas ON xfechadas.familia = f.familia
        JOIN tbl_tipo_atendimento ta ON ta.codigo IN (xrecebidas.codigo,xabertas.codigo,xfechadas.codigo) AND ta.fabrica = {$login_fabrica}
	WHERE f.fabrica = {$login_fabrica}
        AND f.ativo IS TRUE
    GROUP BY ta.codigo, ta.descricao, f.descricao, f.familia, xabertas.oss, xfechadas.oss, xrecebidas.oss
    ORDER BY ta.codigo;
";

$sqlAbe = "
    WITH xrecebidas AS (
	  SELECT DISTINCT p.familia, JSON_FIELD(JSON_FIELD('tipoOrdem', hcc.dados), '{\"ZKR5\":\"90\",\"ZKR6\":\"90\",\"ZKR3\":\"10\",\"ZKR9\":\"40\",\"ZKR1\":\"30\",\"ZKR2\":\"30\"}')::INT AS codigo, COUNT(hcc.hd_chamado_cockpit) AS oss
		FROM tbl_hd_chamado_cockpit hcc
		  JOIN tbl_routine_schedule_log rsl USING(routine_schedule_log)
		  JOIN tbl_routine_schedule rs USING(routine_schedule)
		  JOIN tbl_routine r USING(routine)
		  JOIN tbl_produto p ON p.referencia = JSON_FIELD('modeloKof', hcc.dados) AND p.fabrica_i = {$login_fabrica}
		  JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
		WHERE hcc.fabrica = {$login_fabrica}
		  AND rsl.create_at BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
		  AND r.active IS TRUE
		  AND r.context ILIKE '%Imp. Arquivos%'
	  GROUP BY codigo, p.familia
	), xabertas AS (
	  SELECT DISTINCT ta.codigo, ta.descricao, xp.familia, COUNT(o.os) AS oss
		FROM tbl_os AS o
		  JOIN tbl_os_produto op USING(os)
		  JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
		  JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = o.hd_chamado AND hcc.fabrica = {$login_fabrica}
		  JOIN tbl_produto xp ON xp.produto = op.produto AND xp.referencia = JSON_FIELD('modeloKof', hcc.dados) AND xp.fabrica_i = {$login_fabrica}
	  JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
		WHERE o.finalizada IS NULL
		  AND o.fabrica = {$login_fabrica}
      AND o.excluida IS NOT TRUE
      AND o.data_abertura BETWEEN '{$data_ontem}'::date - INTERVAL '6 MONTHS' AND '{$data_ontem}'
	  GROUP BY ta.codigo, ta.descricao, xp.familia
	), xfechadas AS (
	  SELECT DISTINCT ta.codigo, ta.descricao, xp.familia, COUNT(o.os) AS oss
		FROM tbl_os o
		  JOIN tbl_os_produto op USING(os)
		  JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
		  JOIN tbl_hd_chamado_cockpit hcc ON hcc.hd_chamado = o.hd_chamado AND hcc.fabrica = {$login_fabrica}
		  JOIN tbl_produto xp ON xp.produto = op.produto AND xp.referencia = JSON_FIELD('modeloKof', hcc.dados) AND xp.fabrica_i = {$login_fabrica}
		  JOIN tbl_distribuidor_sla ds ON ds.centro = JSON_FIELD('centroDistribuidor', hcc.dados) AND ds.unidade_negocio <> '6200'
		WHERE o.finalizada BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
		  AND o.fabrica = {$login_fabrica}
      AND o.excluida IS NOT TRUE
	  GROUP BY ta.codigo, ta.descricao, xp.familia
	)
	SELECT
	  CASE WHEN xfechadas.codigo IS NOT NULL THEN
          xfechadas.codigo
	    WHEN xabertas.codigo IS NOT NULL THEN
	      xabertas.codigo
		WHEN xrecebidas.codigo IS NOT NULL THEN
		  xrecebidas.codigo
	    ELSE '999'
	  END AS codigo_atendimento,
	  CASE WHEN xfechadas.descricao IS NOT NULL THEN
          xfechadas.descricao
        WHEN xabertas.descricao IS NOT NULL THEN
          xabertas.descricao
        ELSE 'NÃO DEFINIDO'
	  END AS atendimento_descricao,
	  f.familia,
	  f.descricao AS descricao,
	  COALESCE(xabertas.oss,0) AS Abertas,
	  COALESCE(xfechadas.oss,0) AS Fechadas,
	  COALESCE(xrecebidas.oss,0) AS Recebidas
    FROM tbl_familia f
    LEFT JOIN xabertas USING(familia)
    LEFT JOIN xfechadas USING(familia)
    LEFT JOIN xrecebidas USING(familia)
    WHERE f.fabrica = $login_fabrica
    AND f.ativo IS TRUE
	AND (COALESCE(xabertas.oss,0) > 0 OR COALESCE(xfechadas.oss,0) > 0 OR COALESCE(xrecebidas.oss,0) > 0)
	ORDER BY codigo_atendimento
";

$sqlITU = "SELECT
                ca.cliente_admin,
                ca.nome,
                COALESCE(xabertas.oss,0) AS abertas,
                COALESCE(xfechadas.oss,0) AS fechadas,
                xabertas.descricao AS abertas_tipo_atendimento,
                xfechadas.descricao AS fechadas_tipo_atendimento
          FROM tbl_cliente_admin ca
              LEFT JOIN (
                SELECT DISTINCT hc.cliente_admin, ca.nome, ta.descricao, COUNT(o.os) AS oss
                FROM tbl_os AS o
                  JOIN tbl_os_campo_extra oce ON o.os = oce.os AND oce.fabrica = {$login_fabrica}
                  JOIN tbl_hd_chamado hc USING (hd_chamado)
                  JOIN tbl_cliente_admin ca ON ca.cliente_admin  = hc.cliente_admin
                  JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
                WHERE o.finalizada IS NULL
                  AND o.data_abertura BETWEEN '{$data_ontem}'::date - INTERVAL '6 MONTHS' AND '{$data_ontem}'
                  AND o.fabrica = {$login_fabrica}
		  AND o.excluida IS NOT TRUE
		  AND o.cliente_admin NOT IN(44795)
                GROUP BY hc.cliente_admin, ca.nome, ta.descricao

              ) AS xabertas ON ca.cliente_admin = xabertas.cliente_admin
              LEFT JOIN (
                SELECT DISTINCT hc.cliente_admin, ca.nome, ta.descricao, COUNT(o.os) AS oss
                FROM tbl_os o
                  JOIN tbl_os_campo_extra oce ON o.os = oce.os AND oce.fabrica = {$login_fabrica}
                  JOIN tbl_hd_chamado hc USING (hd_chamado)
                  JOIN tbl_cliente_admin ca ON ca.cliente_admin  = hc.cliente_admin
                  JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
                WHERE o.finalizada BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
                  AND o.fabrica = {$login_fabrica}
		  AND o.excluida IS NOT TRUE
 		  AND o.cliente_admin NOT IN(44795)
                GROUP BY hc.cliente_admin, ca.nome, ta.descricao
              ) xfechadas ON ca.cliente_admin = xfechadas.cliente_admin
          WHERE ca.fabrica = {$login_fabrica}
          AND ca.cliente_admin IN (xfechadas.cliente_admin,xabertas.cliente_admin) 
	  GROUP BY  ca.cliente_admin,ca.nome,xabertas.oss, xfechadas.oss, xfechadas.descricao, xabertas.descricao;";

$sqlFRUKI = "SELECT
      COALESCE(ta.codigo, '999') AS codigo_atendimento,
      COALESCE(ta.descricao, 'NÃO DEFINIDO') AS atendimento_descricao,
      f.familia,
      f.descricao AS descricao,
      COALESCE(xabertas.oss,0) AS Abertas,
      COALESCE(xfechadas.oss,0) AS Fechadas
      FROM tbl_familia f
      JOIN tbl_tipo_atendimento ta ON ta.fabrica = $login_fabrica
      LEFT JOIN (
  SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
    FROM tbl_os AS o
      JOIN tbl_os_produto op USING(os)
      JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
      JOIN tbl_os_campo_extra oce ON o.os = oce.os
      JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = o.posto AND dsp.fabrica = $login_fabrica
      JOIN tbl_produto xp ON xp.produto = op.produto AND xp.fabrica_i = $login_fabrica
      JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = dsp.distribuidor_sla AND ds.unidade_negocio <> '6200'
    WHERE o.finalizada IS NULL
      AND o.data_abertura BETWEEN '{$data_ontem}'::date - INTERVAL '6 MONTHS' AND '{$data_ontem}'
      AND o.fabrica = $login_fabrica
      AND o.excluida IS NOT TRUE
      AND JSON_FIELD('unidadeNegocio',oce.campos_adicionais) = '6300'
  GROUP BY ta.codigo, xp.familia
) AS xabertas ON xabertas.familia = f.familia AND xabertas.codigo = ta.codigo
LEFT JOIN (
  SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
    FROM tbl_os o
      JOIN tbl_os_produto op USING(os)
      JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
      JOIN tbl_os_campo_extra oce ON o.os = oce.os
      JOIN tbl_distribuidor_sla_posto dsp ON dsp.posto = o.posto AND dsp.fabrica = $login_fabrica
      JOIN tbl_produto xp ON xp.produto = op.produto AND xp.fabrica_i = $login_fabrica
      JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = dsp.distribuidor_sla AND ds.unidade_negocio <> '6200'
    WHERE o.finalizada BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
      AND o.fabrica = $login_fabrica
      AND o.excluida IS NOT TRUE
      AND JSON_FIELD('unidadeNegocio',oce.campos_adicionais) = '6300'
  GROUP BY ta.codigo, xp.familia
) xfechadas ON xfechadas.familia = f.familia AND xfechadas.codigo = ta.codigo
WHERE f.fabrica = $login_fabrica
AND f.ativo IS TRUE
AND (xabertas.oss > 0 or xfechadas.oss > 0)
GROUP BY ta.codigo, ta.descricao, f.descricao, f.familia, xabertas.oss, xfechadas.oss";

$sqlTa = "SELECT
      COALESCE(ta.codigo, '999') AS codigo_atendimento,
      COALESCE(ta.descricao, 'NÃO DEFINIDO') AS atendimento_descricao,
      f.familia,
      f.descricao AS descricao,
      COALESCE(xabertas.oss,0) AS Abertas,
      COALESCE(xfechadas.oss,0) AS Fechadas
      FROM tbl_familia f
      JOIN tbl_tipo_atendimento ta ON ta.fabrica = $login_fabrica
      LEFT JOIN (
  SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
  FROM tbl_os AS o
  JOIN tbl_os_produto op USING(os)
  JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
  JOIN tbl_produto xp ON xp.produto = op.produto AND xp.fabrica_i = $login_fabrica
  JOIN tbl_os_campo_extra ON o.os = tbl_os_campo_extra.os AND JSON_FIELD('unidadeNegocio',campos_adicionais) <> '6200'
  WHERE o.finalizada IS NULL
  AND o.data_abertura BETWEEN '{$data_ontem}'::date - INTERVAL '6 MONTHS' AND '{$data_ontem}'
  AND o.fabrica = $login_fabrica
  AND o.excluida IS NOT TRUE
  GROUP BY ta.codigo, xp.familia
) AS xabertas ON xabertas.familia = f.familia AND xabertas.codigo = ta.codigo
LEFT JOIN (
	SELECT DISTINCT ta.codigo, xp.familia, COUNT(o.os) AS oss
	FROM tbl_os o
	JOIN tbl_os_produto op USING(os)
	JOIN tbl_tipo_atendimento ta ON o.tipo_atendimento = ta.tipo_atendimento
	JOIN tbl_produto xp ON xp.produto = op.produto AND xp.fabrica_i = $login_fabrica
	JOIN tbl_os_campo_extra ON o.os = tbl_os_campo_extra.os AND JSON_FIELD('unidadeNegocio',campos_adicionais) <> '6200'
	WHERE o.finalizada BETWEEN '{$data_ontem} 00:00:00' AND '{$data_ontem} 23:59:59'
	AND o.fabrica = $login_fabrica
	AND o.excluida IS NOT TRUE
	GROUP BY ta.codigo, xp.familia
) xfechadas ON xfechadas.familia = f.familia AND xfechadas.codigo = ta.codigo
WHERE f.fabrica = $login_fabrica
AND f.ativo IS TRUE
AND (xabertas.oss > 0 or xfechadas.oss > 0)
GROUP BY ta.codigo, ta.descricao, f.descricao, f.familia, xabertas.oss, xfechadas.oss;";

// pre_echo($sqlAbe, 'Consulta', true);
/*
$resAbeMG = pg_query($con, str_replace("negocio <> '6200'", "negocio IN ('6108','6107','6103','6102','6101','6104','6105','6106')", $sqlAbe));
$resAbeSP = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6200'", $sqlAbe));
$resAbeSPO = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6201'", $sqlAbe));
$resAbeMS = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6500'", $sqlAbe));
$resAbeRJ = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6600'", $sqlAbe));

$resAbeRS = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6900'", $sqlAbe));
$resAbePR = pg_query($con, str_replace("negocio <> '6200'", "negocio = '7000'", $sqlAbe));

//HD - 4330910
$resAbeFRUKI = pg_query($con, str_replace("negocio <> '6200'", "negocio = '6300'", $sqlFRUKI));
$resAbeWOW = pg_query($con, str_replace("<> '6200'", "= '6800'", $sqlTa));
$resAbeSolar = pg_query($con, str_replace("<> '6200'", "= '7100'", $sqlTa));
$resAbeDANONE = pg_query($con, str_replace("<> '6200'", "= '6700'", $sqlTa));
$resAbeITU = pg_query($con, $sqlITU);
$resAbeHaD = pg_query($con, str_replace("<> '6200'", "= '7200'", $sqlTa));
//FIM HD-4330910
*/
$unidadesSqlAbe   = \Posvenda\Regras::getUnidades("resumoOsSqlAbe", $login_fabrica);
$unidadesSqlFruki = \Posvenda\Regras::getUnidades("resumoOsSqlFruki", $login_fabrica);
$unidadesSqlTa    = \Posvenda\Regras::getUnidades("resumoOsSqlTa", $login_fabrica);
$unidadesSqlItu   = \Posvenda\Regras::getUnidades("resumoOsSqlItu", $login_fabrica);
$unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);

$condUnidadesMinas = implode("','", $unidadesMinasGerais);

$arrayResources = [];

$arrayResources["operacao_minas"] = pg_query($con, str_replace("negocio <> '6200'", "negocio IN ('{$condUnidadesMinas}')", $sqlAbe));

$unidadesSqlAbe = array_diff($unidadesSqlAbe, $unidadesMinasGerais);

foreach ($unidadesSqlAbe as $key => $unidadeCodigo) {

  $arrayResources[$unidadeCodigo] = pg_query($con, str_replace("negocio <> '6200'", "negocio = '{$unidadeCodigo}'", $sqlAbe));

}

foreach ($unidadesSqlFruki as $key => $unidadeCodigo) {

  $arrayResources[$unidadeCodigo] = pg_query($con, str_replace("negocio <> '6200'", "negocio = '{$unidadeCodigo}'", $sqlFRUKI));
  
}

foreach ($unidadesSqlTa as $key => $unidadeCodigo) {

  $arrayResources[$unidadeCodigo] = pg_query($con, str_replace("<> '6200'", "= '{$unidadeCodigo}'", $sqlTa));
  
}

foreach ($unidadesSqlItu as $key => $unidadeCodigo) {

  $arrayResources[$unidadeCodigo] = pg_query($con, str_replace("negocio <> '6200'", "negocio = '{$unidadeCodigo}'", $sqlITU));
  
}

function nomeUnidade($codigo) {
  global $con;

  $sql = "SELECT nome
          FROM tbl_unidade_negocio
          WHERE codigo = '{$codigo}'";
  $res = pg_query($con, $sql);

  return pg_fetch_result($res, 0, 'nome');

}

function agrupar_atendimentos(array $dadosAbe) {
    $retorno = array();
    foreach ($dadosAbe as $kDados => $vDados) {
        $retorno['tipo_atendimento'][$vDados['codigo_atendimento']] = $vDados['atendimento_descricao'];
        $retorno['dados'][$vDados['codigo_atendimento']][] = $vDados;
    }
    // print_r($retorno);
    return $retorno;
}
function agrupar_atendimentos_itu(array $dadosAbe){

    $retorno = [];
    foreach ($dadosAbe as $values) {
        $tipo_atendimento = (!is_null($values['abertas_tipo_atendimento'])) ? $values['abertas_tipo_atendimento'] : $values['fechadas_tipo_atendimento'];
        $retorno[$tipo_atendimento][] =
        [
            'nome' => $values['nome'],
            'abertas' => $values['abertas'],
            'fechadas' => $values['fechadas']
        ];
    }
    return $retorno;
}

function tabular_dados_atendimentos(array $dadosOP, $dia) {
    if (!count($dadosOP))
        return "<p><strong>Sem dados para o dia</strong></p>\n";

    if (DEBUG === true)
        pre_echo($dadosOP['tipo_atendimento'], 'TIPOS');

    foreach ($dadosOP['tipo_atendimento'] as $kDados => $vDados) {
        $tipoAtendimento = strtoupper($vDados);
        $body .= "<br>
            <table width='100%' align = 'center' cellpadding='2' cellspacing='2' border='0' style='font-family: arial ; font-size:12px ; color: #666666'>
              <thead>
                <tr style='background-color:#3e83c9;color:#ffffff;'>
                  <th align='center' colspan='4'>TIPO DE ATENDIMENTO: $tipoAtendimento - DIA: $dia</th>
                </tr>
                <tr style='background-color:#CCC;color:#222;'>
                  <th align='center'>Familia</th>
                  <th align='center'>Total de Recebidas</th>
                  <th align='center'>Total de Fechadas</th>
                  <th align='center'>Total em Aberto</th>
                </tr>
              </thead>
              <tbody>";

        $zebra = '#ffffff';
        $total_recebidas = 0;
        $total_fechadas  = 0;
        $total_abertas   = 0;

        if (DEBUG === true)
            pre_echo ($dadosOP['dados'][$kDados], "DADOS DE $kDados");

        foreach ($dadosOP['dados'][$kDados] as $vResult) {
            if (DEBUG === true)
                pre_echo($vResult, 'DADOS DO TIPO '.$tipoAtendimento);

            $zebra = ($zebra == "#ffffff") ? "#eeeeee" : "#ffffff";
            $body .= "
                <tr style='background-color:$zebra;color:#222222;'>
                  <td align='left' width='200'>".$vResult['descricao']."</td>
                  <td align='center'><b>".$vResult['recebidas']."</b></td>
                  <td align='center'><b>".$vResult['fechadas']."</b></td>
                  <td align='center'><b>".$vResult['abertas']."</b></td>
                </tr>";
            $total_recebidas += $vResult['recebidas'];
            $total_fechadas += $vResult['fechadas'];
            $total_abertas += $vResult['abertas'];
        }

        $body .= "
                <tr style='background-color:#CCC;color:#222;'>
                    <td align='right' width='200'><b>TOTAIS:</b></td>
                    <td align='center'><b>".$total_recebidas."</b></td>
                    <td align='center'><b>".$total_fechadas."</b></td>
                    <td align='center'><b>".$total_abertas."</b></td>
                </tr>
              </tbody>
            </table>
            <br />";
    }
    return $body;
}

function tabular_dados_atendimentos_itu(array $dadosOP, $dia) {
    if (!count($dadosOP))
        return "<p><strong>Sem dados para o dia</strong></p>\n";

    foreach ($dadosOP as $kDados => $vDados) {
        $tipoAtendimento = strtoupper($kDados);
        $body .= "<br>
            <table width='100%' align = 'center' cellpadding='2' cellspacing='2' border='0' style='font-family: arial ; font-size:12px ; color: #666666'>
              <thead>
                <tr style='background-color:#3e83c9;color:#ffffff;'>
                  <th align='center' colspan='4'>TIPO DE ATENDIMENTO: $tipoAtendimento - DIA: $dia</th>
                </tr>
                <tr style='background-color:#CCC;color:#222;'>
                  <th align='center'>Cliente Admin</th>
                  <th align='center'>Total de Fechadas</th>
                  <th align='center'>Total em Aberto</th>
                </tr>
              </thead>
              <tbody>";

        $zebra = '#ffffff';
        $total_fechadas  = 0;
        $total_abertas   = 0;

        foreach ($vDados as $vResult) {

            $zebra = ($zebra == "#ffffff") ? "#eeeeee" : "#ffffff";
            $body .= "
                <tr style='background-color:$zebra;color:#222222;'>
                  <td align='left' width='200'>".$vResult['nome']."</td>
                  <td align='center'><b>".$vResult['fechadas']."</b></td>
                  <td align='center'><b>".$vResult['abertas']."</b></td>
                </tr>";
            $total_fechadas += $vResult['fechadas'];
            $total_abertas += $vResult['abertas'];
        }

        $body .= "
                <tr style='background-color:#CCC;color:#222;'>
                    <td align='right' width='200'><b>TOTAIS:</b></td>
                    <td align='center'><b>".$total_fechadas."</b></td>
                    <td align='center'><b>".$total_abertas."</b></td>
                </tr>
              </tbody>
            </table>
            <br />";
    }
    return $body;
}

try {
    
    /* $dadosMG = pg_fetch_all($resAbeMG);
    $dadosSP = pg_fetch_all($resAbeSP);
    $dadosSPO = pg_fetch_all($resAbeSPO);
    $dadosMS = pg_fetch_all($resAbeMS);
    $dadosRJ = pg_fetch_all($resAbeRJ);
    $dadosRS = pg_fetch_all($resAbeRS);
    $dadosPR = pg_fetch_all($resAbePR);

    //HD-4330910
    $dadosFRUKI = pg_fetch_all($resAbeFRUKI);
    $dadosWOW = pg_fetch_all($resAbeWOW);
    $dadosSolar = pg_fetch_all($resAbeSolar);
    $dadosDANONE = pg_fetch_all($resAbeDANONE);
    $dadosHaD = pg_fetch_all($resAbeHaD);
    $dadosITU = pg_fetch_all($resAbeITU); */

    $mensagem = "Segue o KOF - Resumo OS:  <br>\n";

    foreach ($arrayResources as $codUnidade => $resource) {

      $dadosUnidade = pg_fetch_all($resource);

      if (!is_array($dadosUnidade)) {
        $dadosUnidade = [];
      }

      if ($codUnidade == "operacao_minas") {
        $descricaoUnidade = "Minas Gerais";
      } else {
        $descricaoUnidade = nomeUnidade($codUnidade);
      }

      if ($codUnidade == "6400") {
        $retornoTabela = tabular_dados_atendimentos_itu(agrupar_atendimentos_itu($dadosUnidade), $dia_mes_ontem);
      } else {
        $retornoTabela = tabular_dados_atendimentos(agrupar_atendimentos($dadosUnidade), $dia_mes_ontem);
      }

      $mensagem .= "<h2 align='center'>Operação {$descricaoUnidade}:</h2>\n".$retornoTabela;

    }

    $mensagem .= "<p>&nbsp;</p>\n"
        . "<p>Att.<br> <b>Telecontrol</b></p>\n"
        . "<br /><i>Não responda este e-mail, pois ele é gerado automaticamente pelo sistema.</i>\n"
        . "<br>\n";

    /*
    if (!is_array($dadosSolar)) 
        $dadosSolar = array();
    if (!is_array($dadosFRUKI))
        $dadosFRUKI = array();
    if (!is_array($dadosWOW))
        $dadosWOW = array();
    if (!is_array($dadosDANONE))
        $dadosDANONE = array();
    if (!is_array($dadosITU))
        $dadosITU = array();
    //FIM HD-4330910
    if (!is_array($dadosMG))
        $dadosMG = array();

    if (!is_array($dadosHaD))
      $dadosHaD = array();

    if (!is_array($dadosSP))
	$dadosSP = array();

    if (!is_array($dadosSPO))
	$dadosSPO = array();

    if (!is_array($dadosMS))
	$dadosMS = array();

    if (!is_array($dadosRJ))
	$dadosRJ = array();

    if (!is_array($dadosRS))
      $dadosRS = array();
    if (!is_array($dadosPR))
        $dadosPR = array();

    if (!is_array($dadosSPO))
        $dadosSPO = array();


    if (!is_array($dadosMS))
        $dadosMS = array();

  */
    // $dados = array(
    //     'SP' => $dadosSP,
    //     'MG' => $dadosMG
    // );
    // save_var($dados, '/home/manuel/test/resumo_os_imbera.php');
    // die;

    /* if (!count($dadosSP) && !count($dadosMG) && !count($dadosSPO) && !count($dadosRJ) && !count($dadosMS) && !count($dadosFRUKI) && !count($dadosWOW) && !count($dadosDANONE) && !count($dadosITU) && !count($dadosSolar) && !count($dadosHaD))
        throw new Exception("Sem dados para o dia."); */
     /* 
    $mensagem = "Segue o KOF - Resumo OS:  <br>\n"
        . "<h2 align='center'>Operação SP:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosSP), $dia_mes_ontem)
        . "<h2 align='center'>Operação SP Oeste:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosSPO), $dia_mes_ontem)
        . "<h2 align='center'>Operação MG:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosMG), $dia_mes_ontem)
        . "<h2 align='center'>Operação RJ:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosRJ), $dia_mes_ontem)        
        . "<h2 align='center'>Operação RS:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosRS), $dia_mes_ontem)
        . "<h2 align='center'>Operação PR:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosPR), $dia_mes_ontem)        
        . "<h2 align='center'>Operação MS:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosMS), $dia_mes_ontem)
        //HD-4330910
        . "<h2 align='center'>Operação FRUKI:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosFRUKI), $dia_mes_ontem)
        . "<h2 align='center'>Operação WOW:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosWOW), $dia_mes_ontem)
        . "<h2 align='center'>Operação DANONE:</h2>\n"
	. tabular_dados_atendimentos(agrupar_atendimentos($dadosDANONE), $dia_mes_ontem)
        . "<h2 align='center'>Operação Itu:</h2>\n"
        . tabular_dados_atendimentos_itu(agrupar_atendimentos_itu($dadosITU), $dia_mes_ontem)
         //FIM HD-4330910
        . "<h2 align='center'>Operação Haagen Dazs:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosHaD), $dia_mes_ontem)
        . "<h2 align='center'>Operação Solar Gr:</h2>\n"
        . tabular_dados_atendimentos(agrupar_atendimentos($dadosSolar), $dia_mes_ontem)
        . "<p>&nbsp;</p>\n"
        . "<p>Att.<br> <b>Telecontrol</b></p>\n"
        . "<br /><i>Não responda este e-mail, pois ele é gerado automaticamente pelo sistema.</i>\n"
        . "<br>\n"; */

} catch (Exception $e) {
    $mensagem = "<p>Ocorreu um erro durante a consulta, por favor contate com o Suporte.</p>";
    if (!pg_num_rows($resAbeSP) && !pg_num_rows($resAbeMG) && !pg_num_rows($resAbeSPO) && !pg_num_rows($resAbeRJ) && !pg_num_rows($resAbeMS) && !pg_num_rows($resAbeFRUKI) && !pg_num_rows($resAbeWOW) && !pg_num_rows($resAbeDANONE) && !pg_num_rows($resAbeITU) && !pg_num_rows($resAbeSolar))
        $mensagem = "<p>Sem dados para processar.</p>";
}

Log::envia_email($email_admin, "RESUMO DE OSs $dia_mes_ontem", $mensagem);
