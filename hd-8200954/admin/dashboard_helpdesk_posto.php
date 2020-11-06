<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "../helpdesk.inc.php";

function cmp($a,$b) {
	return $a['data'] > $b['data'];
}

function retiraCaracterEspecial($string) {
    
    $especiais = array( 'ä','ã','à','á','â','ê','ë','è','é','ï','ì','í','ö','õ','ò','ó','ô','ü','ù','ú','û','À','Á','É','Í','Ó','Ú','ñ','Ñ','ç','Ç','-','(',')',',',';',':','|','!','"','#','$','%','&','/','=','?','~','^','>','<','ª','º' );

    $neutros   = array( 'a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','A','A','E','I','O','U','n','n','c','C','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_','_' );

    return str_replace($especiais, $neutros, $string);
}

//$_GET['data_inicial'] = '01/05/2019'; /*$_GET['data_final'] = '15/05/2019';*/ $_GET['data_final'] = '30/07/2019';
if (isset($_GET['data_inicial']) && isset($_GET['data_final'])) {
	$data_inicial = $_GET['data_inicial'];
	$data_final = $_GET['data_final'];

	list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);

    $aux_data_inicial = "{$yi}-{$mi}-{$di}";
    $aux_data_final   = "{$yf}-{$mf}-{$df}";

    $dt_dia_1 = "{$yi}-{$mi}-01";
    $ultimo_dia = date("t", mktime(0,0,0,$mf,'01',$yf)); 
	$dt_dia_30 =  "{$yf}-{$mf}-$ultimo_dia";

	$cond_data_dia = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $cond_data_mes = " AND tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
    $cond_data_res = " AND tbl_hd_chamado.data_resolvido BETWEEN '$aux_data_inicial 00:00' AND '$aux_data_inicial 23:59' ";
    $cond_item_dia = " AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $cond_item_mes = " AND tbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
    $cond_data_ap  = " AND tbl_hd_chamado.data_aprovacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $xcond_data_dia = " AND xtbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $xcond_data_mes = " AND xtbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
    $xres_cond_item_dia = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $xres_cond_item_mes = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
    $xcond_data_res = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$aux_data_inicial 00:00' AND '$aux_data_inicial 23:59' ";
    $xcond_item_dia = " AND xtbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    $xcond_item_mes = " AND xtbl_hd_chamado_item.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
    $xcond_data_ap  = " AND xtbl_hd_chamado.data_aprovacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_inicial 23:59:59' ";
    //$xcond_data_ap  = " AND xtbl_hd_chamado.data_aprovacao BETWEEN '2019-07-01 00:00:00' AND '2019-07-30 23:59:59' ";
} else {

	$dt_dia = date("Y-m-d");
	list($y, $m, $d) = explode("-", $dt_dia);	
	$dt_dia_1 = "{$y}-{$m}-01";
	$ultimo_dia = date("t", mktime(0,0,0,$m,'01',$y)); 
	$dt_dia_30 =  "{$y}-{$m}-$ultimo_dia";

	$cond_data_dia = " AND tbl_hd_chamado.data BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $cond_data_mes = " AND tbl_hd_chamado.data BETWEEN '$dt_dia_1 00:00:00' AND '$dt_dia_30 23:59:59' ";
    $cond_data_res = " AND tbl_hd_chamado.data_resolvido BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $cond_item_dia = " AND tbl_hd_chamado_item.data BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $cond_item_mes = " AND tbl_hd_chamado_item.data BETWEEN '$dt_dia_1 00:00:00' AND '$dt_dia_30 23:59:59' ";
    $cond_data_ap  = " AND tbl_hd_chamado.data_aprovacao BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $xcond_data_dia = " AND xtbl_hd_chamado.data BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $xcond_data_mes = " AND xtbl_hd_chamado.data BETWEEN '$dt_dia_1 00:00:00' AND '$dt_dia_30 23:59:59' ";
    $xcond_data_res = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $xcond_item_dia = " AND xtbl_hd_chamado_item.data BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $xcond_item_mes = " AND xtbl_hd_chamado_item.data BETWEEN '$dt_dia_1 00:00:00' AND '$dt_dia_30 23:59:59' ";
    $xres_cond_item_dia = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
    $xres_cond_item_mes = " AND xtbl_hd_chamado.data_resolvido BETWEEN '$dt_dia_1 00:00:00' AND '$dt_dia_30 23:59:59' ";
    $xcond_data_ap  = " AND xtbl_hd_chamado.data_aprovacao BETWEEN '$dt_dia 00:00:00' AND '$dt_dia 23:59:59' ";
}

$data_corte = "2019-01-01 00:00:00";

if (isset($_GET['atendimento'])) {
	$atendimento = $_GET['atendimento'];
	$cond_atendimento = " AND tbl_hd_chamado.hd_chamado = $atendimento ";
	$xcond_atendimento = " AND xtbl_hd_chamado.hd_chamado = $atendimento ";
}

if (isset($_GET['codigo_posto']) || isset($_GET['descricao_posto'])) {
	$posto_codigo = $_GET['codigo_posto'];
	$descricao_posto = $_GET['descricao_posto'];
	$posto = "";
	$sql = "SELECT tbl_posto_fabrica.posto
            FROM tbl_posto
            JOIN tbl_posto_fabrica USING(posto)
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND (
                (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}'))
                OR
                (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
            )";
    $res = pg_query($con ,$sql);
    if (pg_num_rows($res) > 0) {
    	$posto = pg_fetch_result($res, 0, 'posto');
    }

	if (!empty($posto)) {
        $cond_posto = " AND tbl_hd_chamado.posto = {$posto} ";
        $xcond_posto = " AND xtbl_hd_chamado.posto = {$posto} ";
    } else if (empty($posto) && empty($atendimento)) {
        $cond_posto = " AND tbl_hd_chamado.posto <> 6359 ";
        $xcond_posto = " AND xtbl_hd_chamado.posto <> 6359 ";
    }
} else {
	$cond_posto = " AND tbl_hd_chamado.posto <> 6359 ";
    $xcond_posto = " AND xtbl_hd_chamado.posto <> 6359 ";
}

if (isset($_GET['atendente'])) {
	$atendente = $_GET['atendente'];
	$cond_atendente = " AND tbl_hd_chamado.atendente = $atendente ";
	$xcond_atendente = " AND xtbl_hd_chamado.atendente = $atendente ";
}

if (isset($_GET['regiao'])) {
	$regiao = $_GET['regiao'];
	$sql_regiao = "SELECT estados_regiao FROM tbl_regiao WHERE regiao = $regiao AND fabrica = $login_fabrica";
    $res_regiao = pg_query($con, $sql_regiao);
    if (pg_num_rows($res_regiao) > 0) {
        $regioes = strtoupper(pg_fetch_result($res_regiao, 0, 'estados_regiao'));
        $reg = explode(',', $regioes);
        $regioes = '';
        foreach ($reg as $key => $value) {
            if ($key == 0) {
                $regioes .= "'".trim($value)."'"; 
            } else {
                $regioes .= ", '".trim($value)."'"; 
            }
        }
        $cond_regiao = " AND UPPER(tbl_posto.estado) IN ($regioes) ";
        $join_posto = " JOIN tbl_posto ON tbl_hd_chamado.posto = tbl_posto.posto ";
        $xjoin_posto = " JOIN tbl_posto ON xtbl_hd_chamado.posto = tbl_posto.posto ";
    }
}  
     
if (isset($_GET['categoria'])) {
	$categoria = $_GET['categoria'];
	$cond_categoria = " AND tbl_hd_chamado.categoria = '$categoria' ";
	$xcond_categoria = " AND xtbl_hd_chamado.categoria = '$categoria' ";
} 
  
if (isset($_GET['nota'])) {
	$nota = $_GET['nota'];
    if ($nota == 'ruim') {
        $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 4 ";
    } else if ($nota == 'regular') {
        $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 6 AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 5 ";
    } else if ($nota == 'bom') {
        $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int <= 8 AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 7 ";
    } else {
        $cond_nota = " AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::int >= 9 ";
    }

    $join_hd_chamado_extra = " LEFT JOIN tbl_hd_chamado_extra ON  tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado ";
    $xjoin_hd_chamado_extra = " LEFT JOIN tbl_hd_chamado_extra ON  xtbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado ";
}

// Volume de chamados Ag. Fábrica por Atendente
$sql_volume_at = " 	SELECT COUNT(DISTINCT tbl_hd_chamado.hd_chamado) AS volume_chamados_at,
						   tbl_admin.login
					FROM tbl_hd_chamado_item 
					JOIN tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
					$join_posto
					$join_hd_chamado_extra
					WHERE tbl_hd_chamado.fabrica = $login_fabrica
					AND tbl_hd_chamado.posto NOTNULL
					AND tbl_hd_chamado.status ILIKE 'Ag. F%' 
					AND tbl_hd_chamado.data_resolvido IS NULL
					AND tbl_admin.ativo
	  				AND tbl_admin.admin_sap
	  				AND tbl_hd_chamado_item.interno IS NOT TRUE
	  				AND tbl_hd_chamado.data > '$data_corte'
	  				AND credenciamento <> 'DESCREDENCIADO'
					{$cond_atendimento}
					{$cond_atendente}
					{$cond_regiao}
					{$cond_categoria}
					{$cond_nota}
					{$cond_posto}
					GROUP BY tbl_admin.login";
$res_volume_at = pg_query($con, $sql_volume_at);
if (pg_num_rows($res_volume_at) > 0) {
	$volume_at = pg_fetch_all($res_volume_at);
} else {
	$volume_at = 0;
}

// Novos - Deve apresentar os chamados abertos apenas para atendentes SAP, que ainda não foram interagidos pelos mesmos.
$sql_novo = " 	SELECT COUNT(DISTINCT tbl_hd_chamado.hd_chamado) AS novos
				FROM tbl_hd_chamado_item 
				JOIN tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
				$join_posto
				$join_hd_chamado_extra
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.posto NOTNULL
				AND tbl_hd_chamado.status ILIKE 'Ag. F%' 
				AND tbl_hd_chamado.data_resolvido IS NULL
				AND tbl_admin.ativo
  				AND tbl_admin.admin_sap
  				AND tbl_hd_chamado.data > '$data_corte'
  				AND credenciamento <> 'DESCREDENCIADO'
				AND (
					 SELECT count(1) 
					 FROM tbl_hd_chamado_item 
					 WHERE hd_chamado = tbl_hd_chamado.hd_chamado
					) = 1
				{$cond_atendimento}
				{$cond_atendente}
				{$cond_regiao}
				{$cond_categoria}
				{$cond_nota}
				{$cond_posto}";
$res_novo = pg_query($con, $sql_novo);
if (pg_num_rows($res_novo) > 0) {
	$novos = pg_fetch_result($res_novo, 0, 'novos');
} else {
	$novos = 0;
}

// Pendente Posto e Fabrica
$sql_p_f = " 	SELECT COUNT(DISTINCT tbl_hd_chamado.hd_chamado) FILTER(WHERE tbl_hd_chamado.status = 'Ag. Posto') AS pendente_posto,
					   COUNT(DISTINCT tbl_hd_chamado.hd_chamado) FILTER(WHERE tbl_hd_chamado.status ILIKE 'Ag. F%') AS pendente_fabrica
				FROM tbl_hd_chamado_item 
				JOIN tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
				JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				$join_posto
				$join_hd_chamado_extra
				WHERE tbl_hd_chamado.fabrica = 1
				AND tbl_hd_chamado.posto NOTNULL
				AND tbl_hd_chamado.status NOTNULL
				AND tbl_hd_chamado.data_resolvido IS NULL 
				AND tbl_admin.ativo
				AND tbl_hd_chamado.data > '$data_corte'
				AND credenciamento <> 'DESCREDENCIADO'
  				AND tbl_admin.admin_sap
  				AND tbl_hd_chamado_item.interno IS NOT TRUE 
				{$cond_atendimento}
				{$cond_atendente}
				{$cond_regiao}
				{$cond_categoria}
				{$cond_nota}
				{$cond_posto} ";
$res_p_f = pg_query($con, $sql_p_f);

$pendente_posto   = 0;
$pendente_fabrica = 0;

if (pg_num_rows($res_p_f) > 0) {
	$pendente_posto = pg_fetch_result($res_p_f, 0, 'pendente_posto');
	$pendente_fabrica = pg_fetch_result($res_p_f, 0, 'pendente_fabrica');
}

// Aberto Hoje / Resolvido HOJE
$sql_hoje = "	SELECT COUNT(1) FILTER(WHERE tbl_hd_chamado.fabrica = $login_fabrica $cond_data_dia) AS qtde_aberto_hoje,
					   COUNT(1) FILTER(WHERE tbl_hd_chamado.fabrica = $login_fabrica $cond_data_res) AS qtde_resolvido_hoje
				FROM tbl_hd_chamado 
				$join_posto
				$join_hd_chamado_extra
				JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
				JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				AND tbl_hd_chamado.posto NOTNULL
				AND tbl_admin.ativo
				AND credenciamento <> 'DESCREDENCIADO'
  				AND tbl_admin.admin_sap 
				{$cond_atendimento}
				{$cond_atendente}
				{$cond_regiao}
				{$cond_categoria}
				{$cond_nota}
				{$cond_posto}";
$res_hoje = pg_query($con, $sql_hoje);
if (pg_num_rows($res_hoje) > 0) {
	$qtde_aberto_hoje = pg_fetch_result($res_hoje, 0, 'qtde_aberto_hoje');
	$qtde_resolvido_hoje = pg_fetch_result($res_hoje, 0, 'qtde_resolvido_hoje');
} else {
	$qtde_aberto_hoje = 0;
	$qtde_resolvido_hoje = 0;
}

$drop = pg_query($con, "DROP TABLE xtbl_hd_chamado_item; DROP TABLE xtbl_hd_chamado");

$sql_xhd = "SELECT tbl_hd_chamado.hd_chamado,
				   tbl_hd_chamado.fabrica,
				   tbl_hd_chamado.posto,
				   tbl_hd_chamado.data,
				   tbl_hd_chamado.data_resolvido,
				   tbl_hd_chamado.data_aprovacao,
				   tbl_hd_chamado.atendente,
				   tbl_hd_chamado.categoria
			INTO TEMP xtbl_hd_chamado
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
			JOIN tbl_posto_fabrica ON tbl_hd_chamado.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND tbl_hd_chamado.posto NOTNULL
			AND tbl_admin.ativo
			AND tbl_hd_chamado.data > '$data_corte'
			AND credenciamento <> 'DESCREDENCIADO'
  			AND tbl_admin.admin_sap 
			$cond_data_mes";
$res_xhd = pg_query($con, $sql_xhd);
if (!pg_last_error()) {
	$res_index = pg_query($con, "create index xhd_chamado on xtbl_hd_chamado(hd_chamado)");
}			

$sql_xhd_item = "	SELECT tbl_hd_chamado_item.hd_chamado_item,
						   tbl_hd_chamado_item.hd_chamado,
						   tbl_hd_chamado_item.data,
						   tbl_hd_chamado_item.interno,
						   tbl_hd_chamado_item.admin,
						   tbl_hd_chamado_item.posto
					INTO TEMP xtbl_hd_chamado_item
					FROM tbl_hd_chamado_item
					JOIN xtbl_hd_chamado USING(hd_chamado)
					WHERE tbl_hd_chamado_item.hd_chamado = xtbl_hd_chamado.hd_chamado";
$res_xhd_item = pg_query($con, $sql_xhd_item);
if (!pg_last_error()) {
	$res_index = pg_query($con, "create index xhd_chamado_item on xtbl_hd_chamado_item(hd_chamado_item)");
}

// Média Resposta Hoje e Mensal
$sql_resposta = "	WITH itens1 AS (
					            SELECT
					            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
					            xtbl_hd_chamado_item.hd_chamado_item,
					            xtbl_hd_chamado_item.data,
					            xtbl_hd_chamado_item.hd_chamado,
					            xtbl_hd_chamado.fabrica
					            FROM xtbl_hd_chamado_item
					            JOIN xtbl_hd_chamado USING(hd_chamado)
					            $xjoin_posto
					            $xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_dia
					            AND fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_dia}
					            {$xcond_posto}
					            UNION
					            SELECT 0 AS ordem,
					            0 AS hd_chamado_item ,
					            xtbl_hd_chamado.data,
					            xtbl_hd_chamado.hd_chamado,
					            fabrica 
					            FROM xtbl_hd_chamado
					            JOIN xtbl_hd_chamado_item USING(hd_chamado)
					            $xjoin_posto
					            $xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_dia
					            AND fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_dia}
					            {$xcond_posto}
					    ),
					    itens2 AS (
					            SELECT
					            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
					            xtbl_hd_chamado_item.hd_chamado_item,
					            xtbl_hd_chamado_item.data,
					            xtbl_hd_chamado_item.hd_chamado
					            FROM xtbl_hd_chamado_item
					         	JOIN xtbl_hd_chamado USING(hd_chamado)
					         	$xjoin_posto
					         	$xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_dia
					            AND xtbl_hd_chamado.fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_dia}
					            {$xcond_posto}
					    ),
					    itens3 AS (
					            SELECT  itens1.hd_chamado_item AS item1,
					            		itens1.fabrica, 
					                    itens2.hd_chamado_item AS item2,
					                    itens2.data AS data2, 
					                    itens1.data AS data1 
					            FROM itens2 
					            JOIN itens1 USING(hd_chamado) 
					            WHERE itens1.ordem = itens2.ordem - 1 
					            ORDER BY itens1.hd_chamado_item
					    ),
					    itens4 AS (
					                SELECT item1,
					                	   fabrica, 
					                      item2, 
					                      data2 - data1 AS intervalo 
					                FROM itens3
					                ),
					    itens5 AS (
					    			SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media_hoje, fabrica FROM itens4 GROUP BY fabrica
					    		 ),
			    		resposta_hoje AS (
					    			SELECT extract(epoch FROM media_hoje)/3600 as media_hoje, fabrica FROM itens5
					    		),
					    itens6 AS (
					            SELECT
					            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
					            xtbl_hd_chamado_item.hd_chamado_item,
					            xtbl_hd_chamado_item.data,
					            xtbl_hd_chamado_item.hd_chamado,
					            xtbl_hd_chamado.fabrica
					            FROM xtbl_hd_chamado_item
					            JOIN xtbl_hd_chamado USING(hd_chamado)
					            $xjoin_posto
					            $xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_mes
					            AND fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_mes}
					            {$xcond_posto}
					            UNION
					            SELECT 0 AS ordem,
					            0 AS hd_chamado_item ,
					            xtbl_hd_chamado.data,
					            xtbl_hd_chamado.hd_chamado,
					            fabrica
					            FROM xtbl_hd_chamado
					            JOIN xtbl_hd_chamado_item USING(hd_chamado)
					            $xjoin_posto
					            $xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_mes
					            AND fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_mes}
					            {$xcond_posto}
					    ),
					    itens7 AS (
					            SELECT
					            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
					            xtbl_hd_chamado_item.hd_chamado_item,
					            xtbl_hd_chamado_item.data,
					            xtbl_hd_chamado_item.hd_chamado
					            FROM xtbl_hd_chamado_item
					            JOIN xtbl_hd_chamado USING(hd_chamado)
					            $xjoin_posto
					            $xjoin_hd_chamado_extra
					            WHERE interno IS false
					            $xcond_item_mes
					            AND xtbl_hd_chamado.fabrica = $login_fabrica
					            AND xtbl_hd_chamado.posto NOTNULL
					            AND xtbl_hd_chamado_item.admin NOTNULL
					            {$xcond_atendimento}
					            {$xcond_atendente}
					            {$cond_regiao}
					            {$xcond_categoria}
					            {$cond_nota}
					            {$xcond_item_mes}
					            {$xcond_posto}
					    ),
					    itens8 AS (
					            SELECT  itens6.hd_chamado_item AS item6,
					            		itens6.fabrica, 
					                    itens7.hd_chamado_item AS item7,
					                    itens7.data AS data7, 
					                    itens6.data AS data6 
					            FROM itens7 
					            JOIN itens6 USING(hd_chamado) 
					            WHERE itens6.ordem = itens7.ordem - 1 
					            ORDER BY itens6.hd_chamado_item
					    ),
					    itens9 AS (
					                SELECT item6,
					                	   fabrica, 
					                      item7, 
					                      data7 - data6 AS intervalo 
					                FROM itens8
					                ),
					    itens10 AS (
					    		SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens9) AS media_mes, fabrica FROM itens9 GROUP BY fabrica
					    ), 
					    resposta_mes AS (
							    SELECT extract(epoch FROM media_mes)/3600 as media_mes, fabrica FROM itens10
						) 
						SELECT * FROM resposta_mes LEFT JOIN resposta_hoje USING(fabrica)";
$res_resposta = pg_query($con, $sql_resposta);
if (pg_num_rows($res_resposta) > 0) {
	$media_hoje = pg_fetch_result($res_resposta, 0, 'media_hoje');
    $media_hoje = str_replace(['days','Days'], 'Dias', $media_hoje);
    $media_hoje = str_replace(['day','Day'], 'Dia', $media_hoje);
    $media_hoje = preg_replace('/\.\d+$/','',$media_hoje);
    $media_hoje = (empty($media_hoje)) ? 0 : $media_hoje;

	$media_mes = pg_fetch_result($res_resposta, 0, 'media_mes');
	$media_mes = str_replace(['days','Days'], 'Dias', $media_mes);
    $media_mes = str_replace(['day','Day'], 'Dia', $media_mes);
    $media_mes = preg_replace('/\.\d+$/','',$media_mes);
    $media_mes = (empty($media_mes)) ? 0 : $media_mes;

} else {
	$media_hoje = 0;
	$media_mes = 0;
}

// Média Conclusão Hoje e Mensal
$sql_conclusao_interacao = "	WITH itens1 AS (
							            SELECT
							            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
							            xtbl_hd_chamado_item.hd_chamado_item,
							            xtbl_hd_chamado_item.data,
							            xtbl_hd_chamado_item.hd_chamado,
							            xtbl_hd_chamado.fabrica
							            FROM xtbl_hd_chamado_item
							            JOIN xtbl_hd_chamado USING(hd_chamado)
							            $xjoin_posto
							            $xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_dia}
							            {$xcond_posto}
							            UNION
							            SELECT 0 AS ordem,
							            0 AS hd_chamado_item ,
							            xtbl_hd_chamado.data,
							            xtbl_hd_chamado.hd_chamado,
							            fabrica 
							            FROM xtbl_hd_chamado
							            JOIN xtbl_hd_chamado_item USING(hd_chamado)
							            $xjoin_posto
							            $xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_dia}
							            {$xcond_posto}
							    ),
							    itens2 AS (
							            SELECT
							            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
							            xtbl_hd_chamado_item.hd_chamado_item,
							            xtbl_hd_chamado_item.data,
							            xtbl_hd_chamado_item.hd_chamado
							            FROM xtbl_hd_chamado_item
							         	JOIN xtbl_hd_chamado USING(hd_chamado)
							         	$xjoin_posto
							         	$xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND xtbl_hd_chamado.fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_dia}
							            {$xcond_posto}
							    ),
							    itens3 AS (
							            SELECT  itens1.hd_chamado_item AS item1,
							            		itens1.fabrica, 
							                    itens2.hd_chamado_item AS item2,
							                    itens2.data AS data2, 
							                    itens1.data AS data1 
							            FROM itens2 
							            JOIN itens1 USING(hd_chamado) 
							            WHERE itens1.ordem = itens2.ordem - 1 
							            ORDER BY itens1.hd_chamado_item
							    ),
							    itens4 AS (
							                SELECT item1,
							                	   fabrica, 
							                      item2, 
							                      data2 - data1 AS intervalo 
							                FROM itens3
							                ),
							    itens5 AS (
							    			SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens4) AS media_hoje_conclusao, fabrica FROM itens4 GROUP BY fabrica
							    		 ),
								hora_hoje AS (
							    			SELECT extract(epoch FROM media_hoje_conclusao)/3600 as media_hoje_conclusao, fabrica FROM itens5
							    		),
							    itens6 AS (
							            SELECT
							            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
							            xtbl_hd_chamado_item.hd_chamado_item,
							            xtbl_hd_chamado_item.data,
							            xtbl_hd_chamado_item.hd_chamado,
							            xtbl_hd_chamado.fabrica
							            FROM xtbl_hd_chamado_item
							            JOIN xtbl_hd_chamado USING(hd_chamado)
							            $xjoin_posto
							            $xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_mes}
							            {$xcond_posto}
							            UNION
							            SELECT 0 AS ordem,
							            0 AS hd_chamado_item ,
							            xtbl_hd_chamado.data,
							            xtbl_hd_chamado.hd_chamado,
							            fabrica
							            FROM xtbl_hd_chamado
							            JOIN xtbl_hd_chamado_item USING(hd_chamado)
							            $xjoin_posto
							            $xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_mes}
							            {$xcond_posto}
							    ),
							    itens7 AS (
							            SELECT
							            row_number() OVER(ORDER BY xtbl_hd_chamado.hd_chamado, hd_chamado_item) AS ordem,
							            xtbl_hd_chamado_item.hd_chamado_item,
							            xtbl_hd_chamado_item.data,
							            xtbl_hd_chamado_item.hd_chamado
							            FROM xtbl_hd_chamado_item
							            JOIN xtbl_hd_chamado USING(hd_chamado)
							            $xjoin_posto
							            $xjoin_hd_chamado_extra
							            WHERE interno IS false
							            AND xtbl_hd_chamado.fabrica = $login_fabrica
							            AND xtbl_hd_chamado.posto NOTNULL
							            AND xtbl_hd_chamado.data_resolvido NOTNULL
							            {$xcond_atendimento}
							            {$xcond_atendente}
							            {$cond_regiao}
							            {$xcond_categoria}
							            {$cond_nota}
							            {$xres_cond_item_mes}
							            {$xcond_posto}
							    ),
							    itens8 AS (
							            SELECT  itens6.hd_chamado_item AS item6,
							            		itens6.fabrica, 
							                    itens7.hd_chamado_item AS item7,
							                    itens7.data AS data7, 
							                    itens6.data AS data6 
							            FROM itens7 
							            JOIN itens6 USING(hd_chamado) 
							            WHERE itens6.ordem = itens7.ordem - 1 
							            ORDER BY itens6.hd_chamado_item
							    ),
							    itens9 AS (
							                SELECT item6,
							                	   fabrica, 
							                      item7, 
							                      data7 - data6 AS intervalo 
							                FROM itens8
							                ),
							    itens10 AS (
							    		SELECT SUM(intervalo)/(SELECT COUNT(1) FROM itens9) AS media_mes_conclusao, fabrica FROM itens9 GROUP BY fabrica
							    ),
							    hora_mes AS (
							    		SELECT extract(epoch FROM media_mes_conclusao)/3600 as media_mes_conclusao, fabrica FROM itens10
							    ) 
							    SELECT * FROM hora_mes LEFT JOIN hora_hoje USING(fabrica)";
$res_conclusao_interacao = pg_query($con, $sql_conclusao_interacao);
if (pg_num_rows($res_conclusao_interacao) > 0) {
	$media_hoje_conclusao = pg_fetch_result($res_conclusao_interacao, 0, 'media_hoje_conclusao');
	$media_hoje_conclusao = str_replace(['days','Days'], 'Dias', $media_hoje_conclusao);
    $media_hoje_conclusao = str_replace(['day','Day'], 'Dia', $media_hoje_conclusao);
    $media_hoje_conclusao = preg_replace('/\.\d+$/','',$media_hoje_conclusao);
    $media_hoje_conclusao = (empty($media_hoje_conclusao)) ? 0 : $media_hoje_conclusao;

	$media_mes_conclusao = pg_fetch_result($res_conclusao_interacao, 0, 'media_mes_conclusao');
	$media_mes_conclusao = str_replace(['days','Days'], 'Dias', $media_mes_conclusao);
    $media_mes_conclusao = str_replace(['day','Day'], 'Dia', $media_mes_conclusao);
    $media_mes_conclusao = preg_replace('/\.\d+$/','',$media_mes_conclusao);
    $media_mes_conclusao = (empty($media_mes_conclusao)) ? 0 : $media_mes_conclusao;

} else {
	$media_hoje_conclusao = 0;
	$media_mes_conclusao = 0;
}

//Interações Hoje / Mensal
$sql_interacao = "	SELECT COUNT(1) AS qtde_interacao_mensal,
					 	   COUNT(1) FILTER(WHERE 1=1 $xcond_item_dia) AS qtde_interacao_hoje
					FROM xtbl_hd_chamado_item 
					JOIN xtbl_hd_chamado USING(hd_chamado)
					$xjoin_posto
					$xjoin_hd_chamado_extra
					WHERE xtbl_hd_chamado.fabrica = $login_fabrica
					AND xtbl_hd_chamado.posto NOTNULL
					AND xtbl_hd_chamado_item.admin NOTNULL
					$xcond_item_mes
					{$xcond_atendimento}
					{$xcond_atendente}
					{$cond_regiao}
					{$xcond_categoria}
					{$cond_nota}
					{$xcond_posto}";
$res_interacao = pg_query($con, $sql_interacao);
if (pg_num_rows($res_interacao) > 0) {
	$qtde_interacao_hoje = pg_fetch_result($res_interacao, 0, 'qtde_interacao_hoje');
	$qtde_interacao_mensal = pg_fetch_result($res_interacao, 0, 'qtde_interacao_mensal');
} else {
	$qtde_interacao_hoje = 0;
	$qtde_interacao_mensal = 0;
}

?>

<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>

		<title>Dashboard HelpDesk Posto</title>
		<style>
			body {
				background-color: #FFFFFF;
			}
			
			.grafico {
				width: 96%; 
				margin-top: 2.5%;
				border: 1px solid black;
				box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;
   				height: 380px;

			}
	
			.div_info {
				width: 100%;
				margin-left: 2.5%;
				margin-top: 2.5%;
				background-color: #FFFFFF;
				text-align: center;
				padding-top: 4%;
				padding-bottom: 4%;
				padding-left: 4%;
    			padding-right: 4%;
				border: 1px solid black;
				box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;
			}
			
			.div_grafico {
				margin-top: -1%;
			}
			
			.div_tabelas {
				border: solid 1px gray;
			}

			.div_titulo, .div_titulo h4 {
				background-color: #4d4d4d;
				color: #FFFFFF;
				font-size: 18px;
				margin-top: 0px;
				padding-top: 5px;
				padding-bottom: 5px;
			}

			.span_sub_titulo {
				font-size: 16 2rem;
			}

			.sombra_grafico {
			    -webkit-box-shadow: 6px 6px 0 #333;
			    -moz-box-shadow: 6px 6px 0 #333;
			    box-shadow: 6px 6px 0 #333;	
			}

			/*.div_tabelas:hover {
				background-color: #FFFFFF;
				transition: 0.15s ease-in;
				box-shadow: 5px 5px 0 #333;
   				-webkit-box-shadow: 5px 5px 0 #333;
   				-moz-box-shadow: 5px 5px 0 #333;
			}*/

			.iframe_div {
				margin-left: 0.5%;
				width: 97%; 
				padding : 10px;
				margin-top: 10px;
				border: 1px solid black;
				background-color: #FFFFFF;
				box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;
   				margin-bottom: 30px;	
			}
			
			.div_scroll {
				overflow-y: auto;
				max-height: 100%;
			}

			.bloco_img_10 {
				border: 1px solid black;
				/*box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;*/
   				background-color: #7EBA4E;	
   				color: #FFFFFF;
			}

			.bloco_img_7 {
				border: 1px solid black;
				/*box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;*/
   				background-color: #485FA6;	
   				color: #FFFFFF;
			}

			.bloco_img_4 {
				border: 1px solid black;
				/*box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;*/
   				background-color: #E6B228;	
   				color: #FFFFFF;
			}

			.bloco_img_0 {
				border: 1px solid black;
				/*box-shadow: 0px 0px 0 #333;
   				-webkit-box-shadow: 0px 0px 0 #333;
   				-moz-box-shadow: 0px 0px 0 #333;*/
   				background-color: #E24242;	
   				color: #FFFFFF;
			}

			/*.quadros {
				margin-bottom: -3.5px;
				background-color: #E8E8E8;
			}*/

			.container-info{ padding: 5px; }
			.row-info{ display: flex; justify-content: space-between; }
			.row-info div{ font-size: 16 2rem; padding: 5px 0;}
			
			.desc {
				display: none;
			}

			.desc_volume {
			  	position: absolute;
			    margin-left: 5%;
    			margin-top: 8%;
			    height: 18%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}

			.volume_chamados:hover .desc_volume {
				display: block;
			}

			.volume_chamados {
				margin-left: -2%;
			}

			.volume_atendente:hover .desc_media_respostas {
				display: block;
			}

			.volume_atendente {
				margin-left: -2%;
			}

			.desc_media_respostas {
			  	position: absolute;
			    margin-left: 6%;
    			margin-top: 6%;
			    height: 9%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}
			
			.desc_chamados_hoje {
			  	position: absolute;
			    margin-left: 5%;
    			margin-top: 5%;
			    height: 18%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}

			.chamados_hoje:hover .desc_chamados_hoje {
				display: block;
			}

			.chamados_hoje {
				margin-left: -2%;
			}

			.desc_tempo_medio {
			  	position: absolute;
			    margin-left: 5%;
    			margin-top: 6%;
			    height: 28%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}

			.tempo_medio:hover .desc_tempo_medio {
				display: block;
			}

			.tempo_medio {
				margin-left: -2%;
			}

			.desc_media_conclusao {
			  	position: absolute;
			    margin-left: 4%;
    			margin-top: 6%;
			    height: 18%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}

			.media_conclusao:hover .desc_media_conclusao {
				display: block;
			}

			.media_conclusao {
				margin-left: -2%;
			}
			
			.desc_media_interacoes {
			  	position: absolute;
			    margin-left: 5%;
    			margin-top: 5%;
			    height: 18%;
			    width: 11%;
			    overflow: hidden;
			    background-color: #3CC1B2F2;
			    border-radius: 10px;
    			-moz-border-radius: 10px;
			    -webkit-border-radius: 10px;
			}

			.media_interacoes:hover .desc_media_interacoes {
				display: block;
			}

			.media_interacoes {
				margin-left: -2%;
			}

			.span_class {
				margin-left: 4% !important;
    			margin-top: 2% !important;
    			padding-right: 10px !important;
			}

			.alinha_div {
				display: flex;
				flex-direction: row;
				justify-content: center;
				align-items: center
			}

			.cor_class {
				background-color: #F7F7F7;
			}

			.alinha_right {
				text-align: right;
			} 

		</style>
	</head>
	<body>
		<div class="quadros row-fluid">
			<div class="span2 cabecalho">
				<div class="div_info sombra_grafico">

					<?php if ($volume_at != 0) { ?>


						<div class="row-fluid volume_atendente">
							<div class="desc desc_media_respostas">
								<span class="span_class span_media_respostas">
								    <b>Índice:</b>
								    <br />
								    Chamados Ag. Fábrica por Atendente.
								</span>
								
							</div>
							<div class="span12 div_tabelas">
								<div class="row-fluid">
									<div class="span12 ">
										<div class="div_titulo tac">
											<h4>VOLUME ATENDENTES</h4>
										</div>
									</div>
								</div>
								<!-- <div class="row-fluid">
									<div class="container-info" style="">
										<div class="row-info">
											<div class="span9">
												<div><b>Atendente</b></div>
											</div>
											<div class="span3">
												<div><b>Qtde</b></div>
											</div>
										</div>
									</div>
								</div> -->
								<div class="row-fluid">
									<div class="container-info" style="">
								<?php 
									foreach ($volume_at as $key => $value) { 

										$cor_class = ($key % 2 == 0) ? "" : "cor_class	";
								?>
										<div class="row-info  <?=$cor_class?>" style="line-height:9px">
											<div class="span9">
												<div><?=strtoupper($value['login'])?></div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$value['volume_chamados_at']?></div>
											</div>
										</div>
								<? } ?>
									</div>
								</div>
							</div>
						</div>
						<br />
					<?php } ?>
					<div class="row-fluid volume_chamados">
						<div class="desc desc_volume">
							<span class="span_class span_volume">
								<b>Período:</b>
								<br />
							    Todos os chamados em aberto.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    Novo: Chamados que estão Ag.Fábrica que até o momento não teve interação do Admin.
							</span>
							
						</div>
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>VOLUME CHAMADOS</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info" style="">
										<div class="row-info"  style="line-height:9px">
											<div class="span9">
												<div>NOVO</div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$novos?></div>
											</div>
										</div>
										<div class="row-info cor_class"  style="line-height:9px">
											<div class="span9">
												<div>PENDENCIA FÁBRICA</div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$pendente_fabrica?></div>
											</div>
										</div>
										<div class="row-info"  style="line-height:9px">
											<div class="span9">
												<div>PENDENCIA POSTO</div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$pendente_posto?></div>
											</div>
										</div>
										<div class="row-info cor_class"  style="line-height:9px">
											<div class="span9">
												<div><b>TOTAL</b></div>
											</div>
											<?php $total_volume = $novos + $pendente_posto + $pendente_fabrica; ?>
											<div class="span3">
												<div class="alinha_right"><?=$total_volume?></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br />
					<div class="row-fluid chamados_hoje">
						<div class="desc desc_chamados_hoje">
							<span class="span_class span_chamados_hoje">
								<b>Período:</b>
								<br />
							    Hoje.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    Quantidade de chamados Abertos hoje ou que foram Resolvidos hoje.
							</span>
							
						</div>
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>CHAMADOS HOJE</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info">
										<div class="row-info"  style="line-height:9px">
											<div class="span9">
												<div>ABERTOS</div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$qtde_aberto_hoje?></div>
											</div>
										</div>
										<div class="row-info cor_class" style="line-height:9px">
											<div class="span9">
												<div>RESOLVIDOS</div>
											</div>
											<div class="span3">
												<div class="alinha_right"><?=$qtde_resolvido_hoje?></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br />
					<div class="row-fluid tempo_medio">
						<div class="desc desc_tempo_medio">
							<span class="span_class span_media_respostas">
								<b>Período:</b>
								<br />
							    Hoje: Data Atual;
							    <br />
							    Mensal: Início do mês até hoje.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    <b>Respostas</b>
							    Calcula a média que os atendentes levaram para responder os chamados.
							    <br />
							    <b>Conclusão</b>
							    Calcula a média que os atendentes levaram para concluir os chamados.
							    <br />
							    <b>Interação</b>
							    Calcula a média de interações realizadas no período.
							</span>
						</div> 
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>TEMPO MÉDIO</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info" style="">
										<div class="row-info"  style="line-height:9px">
											<div class="span5"></div>
											<div class="span3">HOJE</div>
											<div class="span4">MENSAL</div>
										</div>
										<div class="row-info cor_class"  style="line-height:9px">
											<div class="span5"><div>RESPOSTAS</div></div>
											<div class="span3"><div><?=$media_hoje?>h</div></div>
											<div class="span4"><div><?=$media_mes?>h</div></div>
										</div>
										<div class="row-info"  style="line-height:9px">
											<div class="span5"><div>CONCLUSÃO</div></div>
											<div class="span3"><div><?=$media_hoje_conclusao?>h</div></div>
											<div class="span4"><div><?=$media_mes_conclusao?>h</div></div>
										</div>
										<div class="row-info cor_class">
											<div class="span5"><div>INTERAÇÕES</div></div>
											<div class="span3"><div><?=$qtde_interacao_hoje?> Qtde</div></div>
											<div class="span4"><div><?=$qtde_interacao_mensal?> Qtde</div></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br />


					<!-- <div class="row-fluid media_respostas">
						<div class="desc desc_media_respostas">
							<span class="span_class span_media_respostas">
								<b>Período:</b>
								<br />
							    Hoje: Data Atual;
							    <br />
							    Mensal: Início do mês até hoje.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    Calcula a média que os atendentes levaram para responder os chamados.
							</span>
							
						</div>
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>Média Respostas</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info" style="">
										<div class="row-info">
											<div>Hoje</div>
											<div><?=$media_hoje?></div>
										</div>
										<div class="row-info">
											<div>Mensal</div>
											<div><?=$media_mes?></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br />
					<div class="row-fluid media_conclusao">
						<div class="desc desc_media_conclusao">
							<span class="span_class span_media_conclusao">
								<b>Período:</b>
								<br />
							    Hoje: Data Atual;
							    <br />
							    Mensal: Início do mês até hoje.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    Calcula a média que os atendentes levaram para concluir os chamados.
							</span>
							
						</div>
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>Média Conclusão</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info" style="">
										<div class="row-info">
											<div>Hoje</div>
											<div><?=$media_hoje_conclusao?></div>
										</div>
										<div class="row-info">
											<div>Mensal</div>
											<div><?=$media_mes_conclusao?></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br />
					<div class="row-fluid media_interacoes">
						<div class="desc desc_media_interacoes">
							<span class="span_class span_media_interacoes">
								<b>Período:</b>
								<br />
							    Hoje: Data Atual;
							    <br />
							    Mensal: Início do mês até hoje.
							    <br /><br />
							    <b>Índice:</b>
							    <br />
							    Calcula a média de interações realizadas no período.
							</span>
							
						</div>
						<div class="span12 div_tabelas">
							<div class="row-fluid">
								<div class="span12 ">
									<div class="div_titulo tac">
										<h4>Média Interações</h4>
									</div>
								</div>
							</div>
							<div class="row-fluid">
								<div class="span12">
									<div class="container-info" style="">
										<div class="row-info">
											<div>Hoje</div>
											<div><?=$qtde_interacao_hoje?></div>
										</div>
										<div class="row-info">
											<div>Mensal</div>
											<div><?=$qtde_interacao_mensal?></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div> -->
				</div>
			</div>
			<div class="span5 corpo">
				<div class="div_grafico">
					<div id="grafico_resposta_atendente" class="grafico sombra_grafico" style="margin-left: 20px;">
						<?php 

							$sql = "	WITH data_admin_dia AS ( 
															SELECT xtbl_hd_chamado_item.admin,
															xtbl_hd_chamado_item.hd_chamado_item,
															(SELECT a.hd_chamado_item FROM xtbl_hd_chamado_item a JOIN tbl_admin ON a.admin = tbl_admin.admin AND fabrica = {$login_fabrica} WHERE a.hd_chamado = xtbl_hd_chamado_item.hd_chamado AND a.admin = xtbl_hd_chamado_item.admin {$xcond_item_mes} AND a.hd_chamado_item < xtbl_hd_chamado_item.hd_chamado_item AND tbl_admin.admin_sap AND interno IS NOT TRUE ORDER BY 1 DESC LIMIT 1) AS hd_item,
															(SELECT a.hd_chamado_item FROM xtbl_hd_chamado_item a WHERE a.hd_chamado = xtbl_hd_chamado_item.hd_chamado AND a.admin NOTNULL AND a.hd_chamado_item < xtbl_hd_chamado_item.hd_chamado_item AND interno IS NOT TRUE ORDER BY 1 DESC LIMIT 1) AS hd_item2,
															xtbl_hd_chamado_item.hd_chamado,
															xtbl_hd_chamado_item.data
															FROM xtbl_hd_chamado_item
															JOIN xtbl_hd_chamado USING(hd_chamado)
															$xjoin_posto
															$xjoin_hd_chamado_extra
															JOIN tbl_admin ON xtbl_hd_chamado_item.admin = tbl_admin.admin
															WHERE xtbl_hd_chamado.fabrica = $login_fabrica
															$xcond_item_dia
															AND xtbl_hd_chamado_item.admin NOTNULL
															AND tbl_admin.admin_sap 
															AND xtbl_hd_chamado.posto NOTNULL
															AND xtbl_hd_chamado_item.interno IS NOT TRUE
															{$xcond_atendimento}
															{$xcond_atendente}
															{$cond_regiao}
															{$xcond_categoria}
															{$cond_nota}
															{$xcond_posto}
															GROUP BY xtbl_hd_chamado_item.admin, 
															xtbl_hd_chamado_item.hd_chamado_item,
															xtbl_hd_chamado_item.hd_chamado,
															xtbl_hd_chamado_item.data
															),
											 data_conta_dia AS (
															SELECT hd_chamado, admin ,hd_item, hd_item2,  data - (SELECT data FROM xtbl_hd_chamado_item WHERE xtbl_hd_chamado_item.posto NOTNULL AND xtbl_hd_chamado_item.hd_chamado = data_admin_dia.hd_chamado AND ((hd_item NOTNULL AND xtbl_hd_chamado_item.hd_chamado_item BETWEEN hd_item AND data_admin_dia.hd_chamado_item) OR (hd_item ISNULL AND hd_item2 ISNULL AND xtbl_hd_chamado_item.hd_chamado_item < data_admin_dia.hd_chamado_item) OR (hd_item ISNULL AND hd_item2 NOTNULL AND xtbl_hd_chamado_item.hd_chamado_item BETWEEN hd_item2 AND data_admin_dia.hd_chamado_item)) $xcond_item_mes ORDER BY 1 LIMIT 1 ) AS tempo
															FROM data_admin_dia),
											 qtde_chamado_dia AS (
															    SELECT COUNT(distinct hd_chamado) AS qtde,  
															    	   admin FROM data_conta_dia 
															    	   GROUP BY 2
															),
											 qtde_admin_dia AS (
															SELECT ((SUM(DATE_PART('DAY', tempo)*24) + SUM(DATE_PART('HOURS', tempo)) + SUM(DATE_PART('MINUTES', tempo)/60))/qtde) AS media_dia, 
																   admin 
															FROM data_conta_dia 
															JOIN qtde_chamado_dia USING (admin) 
															GROUP BY admin, qtde
															ORDER BY media_dia ASC
											 				),
											 data_admin_mes AS ( 
															SELECT xtbl_hd_chamado_item.admin,
															xtbl_hd_chamado_item.hd_chamado_item,
															(SELECT a.hd_chamado_item FROM xtbl_hd_chamado_item a JOIN tbl_admin ON a.admin = tbl_admin.admin AND fabrica = {$login_fabrica}  WHERE a.hd_chamado = xtbl_hd_chamado_item.hd_chamado {$xcond_item_mes} AND tbl_admin.admin_sap AND a.admin = xtbl_hd_chamado_item.admin AND a.hd_chamado_item < xtbl_hd_chamado_item.hd_chamado_item AND interno IS NOT TRUE ORDER BY 1 DESC LIMIT 1) AS hd_item,
															(SELECT a.hd_chamado_item FROM xtbl_hd_chamado_item a WHERE a.hd_chamado = xtbl_hd_chamado_item.hd_chamado AND a.admin NOTNULL AND a.hd_chamado_item < xtbl_hd_chamado_item.hd_chamado_item AND interno IS NOT TRUE ORDER BY 1 DESC LIMIT 1) AS hd_item2,
															xtbl_hd_chamado_item.hd_chamado,
															xtbl_hd_chamado_item.data
															FROM xtbl_hd_chamado_item
															JOIN xtbl_hd_chamado USING(hd_chamado)
															$xjoin_posto
															$xjoin_hd_chamado_extra
															JOIN tbl_admin ON xtbl_hd_chamado_item.admin = tbl_admin.admin
															WHERE xtbl_hd_chamado.fabrica = $login_fabrica 
															{$xcond_item_mes}
															AND xtbl_hd_chamado_item.admin NOTNULL
															AND tbl_admin.admin_sap 
															AND xtbl_hd_chamado.posto NOTNULL
															AND xtbl_hd_chamado_item.interno IS NOT TRUE
															{$xcond_atendimento}
															{$xcond_atendente}
															{$cond_regiao}
															{$xcond_categoria}
															{$cond_nota}
															{$xcond_posto}
															GROUP BY xtbl_hd_chamado_item.admin, 
															xtbl_hd_chamado_item.hd_chamado_item,
															xtbl_hd_chamado_item.hd_chamado,
															xtbl_hd_chamado_item.data
															),
											 data_conta_mes AS (
															SELECT hd_chamado, admin ,hd_item, hd_item2,  data - (SELECT data FROM xtbl_hd_chamado_item WHERE xtbl_hd_chamado_item.posto NOTNULL AND xtbl_hd_chamado_item.hd_chamado = data_admin_mes.hd_chamado AND ((hd_item NOTNULL AND xtbl_hd_chamado_item.hd_chamado_item BETWEEN hd_item AND data_admin_mes.hd_chamado_item) OR (hd_item ISNULL AND hd_item2 ISNULL AND xtbl_hd_chamado_item.hd_chamado_item < data_admin_mes.hd_chamado_item) OR (hd_item ISNULL AND hd_item2 NOTNULL AND xtbl_hd_chamado_item.hd_chamado_item BETWEEN hd_item2 AND data_admin_mes.hd_chamado_item)) $xcond_item_mes ORDER BY 1 LIMIT 1 ) AS tempo
															FROM data_admin_mes
															),
											 qtde_chamado_mes AS (
															    SELECT COUNT(distinct hd_chamado) AS qtde,  
															    	   admin FROM data_conta_mes 
															    	   GROUP BY 2
															),
											 qtde_admin_mes AS (
															SELECT ((SUM(DATE_PART('DAY', tempo)*24) + SUM(DATE_PART('HOURS', tempo)) + SUM(DATE_PART('MINUTES', tempo)/60))/qtde) AS media_mes, 
																   admin 
															FROM data_conta_mes 
															JOIN qtde_chamado_mes USING(admin) 
															GROUP BY admin, qtde
															ORDER BY media_mes ASC
											 				)
											 				SELECT admin, 
											 					   tbl_admin.login AS nome_completo,
											 					   media_dia,
											 					   media_mes
											 				FROM qtde_admin_mes
											 				LEFT JOIN qtde_admin_dia USING(admin)
											 				JOIN tbl_admin USING(admin)
											 				WHERE tbl_admin.admin_sap is true
											 				GROUP BY admin, 
											 						 tbl_admin.login,
											 						 media_dia, 
											 						 media_mes
											 						 ORDER BY media_mes ASC";

							$res = pg_query($con, $sql);							
							$total_interacao_admin_minuto = [];
							if (pg_num_rows($res) > 0) {
								$total_interacao_admin = pg_fetch_all($res);
								$total_admin_name = [];
								$total_interacao_admin_hoje = [];
								$total_interacao_admin_mensal = [];
								
								foreach ($total_interacao_admin as $key => $value) {

									if ((int)preg_replace('/\.\d+$/','',$value['media_dia']) == 0 && (int)preg_replace('/\.\d+$/','',$value['media_mes']) == 0) {
										continue;
									}

									$total_interacao_admin_hoje[$key] = (int)preg_replace('/\.\d+$/','',$value['media_dia']);
																			
									$total_interacao_admin_mensal[$key] = (int)preg_replace('/\.\d+$/','',$value['media_mes']);						   			  
																					 
									$total_admin_name[$key] = utf8_encode($value['nome_completo']); 
								}
							}

							$admins_name = implode(", ", $total_admin_name);
							unset($total_admin_name);
							$admins_name = "'".strtoupper(str_replace(",", "','", $admins_name))."'";

							$total_interacao_admin_hoje = implode(",", $total_interacao_admin_hoje);
							$total_interacao_admin_mensal = implode(",", $total_interacao_admin_mensal);
							#$total_interacao_admin_minuto = json_encode($total_interacao_admin_minuto);

						?>
						<script>
							/*Highcharts.chart('grafico_resposta_atendente', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'Média Resposta Atendente'
							    },
							    xAxis: {
							        categories: [
							            'Diario',
							            'Mensal'
							            
							        ],
							        crosshair: true
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Tempo de Respostas (minutos)'
							        },
							        visible: false
							    },
							    tooltip: {
							        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
							            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
							        footerFormat: '</table>',
							        shared: true,
							        useHTML: true
							    },
							     plotOptions: {
							        series: {
							            borderWidth: 0,
							            dataLabels: {
							                enabled: true,
							                format: '{point.y} H'
							            }
							        }
							    },
							    series: <?= $total_interacao_admin_minuto ?>
							});*/

							Highcharts.chart('grafico_resposta_atendente', {
							    chart: {
							        type: 'bar'
							    },
							    title: {
							        text: 'MÉDIA RESPOSTA ATENDENTE (HORAS): DIÁRIO E MENSAL',
    						        style: {
			                            fontWeight: 'bold',
			                            fontSize:'23px'
			                        }
							    },
							    xAxis: {
							    	labels:{
						                style:{
						                    color:'black',
						                    fontSize:'20px',
						                }
						            },
							        categories: [<?=$admins_name?>],
							        title: {
							            text: null
							        }
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Tempo de Respostas (HORAS)',
							            align: 'high'
							        },
							        labels: {
							            overflow: 'justify'
							        },
							        visible: false
							    },
							    tooltip: {
							        valueSuffix: ' horas'
							    },
							    plotOptions: {
							        bar: {
							            dataLabels: {
							                enabled: true
							            }
							        },
							        series: {
						                dataLabels: {
						                    enabled: true,
						                    style: {
						                        fontSize:'20px'
						                    }
						                }
						            }
							    },
							    legend: {
							        layout: 'vertical',
							        align: 'right',
							        verticalAlign: 'top',
							        x: -40,
							        y: 25,
							        floating: true,
							        borderWidth: 1,
							        backgroundColor:
							            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
							        shadow: true,
							        itemStyle: {
						                fontSize: '12px'
						            }
							    },
							    credits: {
							        enabled: false
							    },
							    series: [{
							        name: 'Diário',
							        data: [<?=$total_interacao_admin_hoje?>]
							    }, {
							        name: 'Mensal',
							        data: [<?=$total_interacao_admin_mensal?>]
							    }]
							});

						</script>
					</div>
					<div id="grafico_qtde_tipo_solicitacao" class="grafico sombra_grafico" style="margin-top: 2%; margin-left: 20px;">
						<?php 

							$sql = " WITH todas_cat AS (
														SELECT  categoria, fabrica
														FROM xtbl_hd_chamado 
														WHERE posto NOTNULL
														$xcond_data_mes
														AND fabrica = $login_fabrica
														AND categoria NOTNULL

													   ),
										 GROUP_cat AS  (
										 				SELECT  categoria,
										 						count(categoria) AS total_cat
														FROM todas_cat
														WHERE fabrica = $login_fabrica
														GROUP BY categoria
														ORDER BY total_cat ASC
										 				)
										 SELECT * FROM GROUP_cat ORDER BY total_cat ASC";
							$res = pg_query($con, $sql);
							if (pg_num_rows($res) > 0) {
								$qtde_tp_solicitacao = pg_fetch_all($res);
							}

							$dados_tipo_solicitacao = [];

							foreach ($qtde_tp_solicitacao as $key => $value) {
								foreach ($categorias as $chave => $valor) {

									if ($value['categoria'] == $chave) {
										$dados_tipo_solicitacao[] = ["name"=> retiraCaracterEspecial($valor['descricao']),
																	 "data"=> [(int)$value['total_cat']]
																	]; 										
									}
								}
							}

							if ($login_fabrica == 1) {
								$tipos_extras = array(
                                        "nova_duvida_pecas"   => "Dúvida sobre peças",
                                        "nova_duvida_pedido"  => "Dúvidas sobre Pedido",
                                        "nova_duvida_produto" => "Dúvidas sobre produtos",
                                        "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                                    );
							}

							foreach ($qtde_tp_solicitacao as $key => $value) {
								foreach ($tipos_extras as $chave => $valor) {

									if ($value['categoria'] == $chave) {
										$dados_tipo_solicitacao[] = ["name"=> retiraCaracterEspecial($valor), 
																	 "data"=> [(int)$value['total_cat']]
																	]; 										
									}
								}
							}

							$dados_tipo_solicitacao_para_ordenar = [];
							$dados_tipo_solicitacao_para_ordenar_new = [];

							foreach ($dados_tipo_solicitacao as $k => $v) {
								$dados_tipo_solicitacao_para_ordenar[] = ["data"=>$v['data'][0]];  
							}

							usort($dados_tipo_solicitacao_para_ordenar, 'cmp');

							$tipo_solicitacao_nome = [];
							$tipo_solicitacao_valor = [];
							$tp_nome = '';
							$tp_valor = '';

							foreach ($dados_tipo_solicitacao_para_ordenar as $ky => $val) {
								foreach ($dados_tipo_solicitacao as $y => $vl) {
									if ($val['data'] == $vl['data'][0]) {
										$dados_tipo_solicitacao_para_ordenar_new[] = $vl;
										unset($dados_tipo_solicitacao[$y]);
									}
								}
							}

							foreach ($dados_tipo_solicitacao_para_ordenar_new as $x => $xx) {
									$tipo_solicitacao_nome[$x] = $xx['name'];
									$tipo_solicitacao_valor[$x] = $xx['data'][0];
							}

							$tp_nome = implode(",", $tipo_solicitacao_nome);
							$tp_nome = "'".strtoupper(str_replace(",", "','", $tp_nome))."'";
							$tp_valor = implode(",", $tipo_solicitacao_valor);
							
							//$dados_tipo_solicitacao = json_encode($dados_tipo_solicitacao_para_ordenar_new);
						?>
						<script>
							/*Highcharts.chart('grafico_qtde_tipo_solicitacao', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'Quantidade Tipo Solicitação'
							    },
							    xAxis: {
							        categories: [
							            '',
							        ],
							        crosshair: true
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Quantidade de Chamados'
							        },
							        visible: false
							    },
							    tooltip: {
							        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
							            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
							        footerFormat: '</table>',
							        shared: true,
							        useHTML: true
							    },
							    plotOptions: {
							        series: {
							            borderWidth: 0,
							            dataLabels: {
							                enabled: true,
							                format: '{point.y}'
							            }
							        }
							    },
							    series: <?= $dados_tipo_solicitacao ?>
							});*/

							Highcharts.chart('grafico_qtde_tipo_solicitacao', {
							    chart: {
							        type: 'bar'
							    },
							    title: {
							        text: 'QUANTIDADE TIPO SOLICITAÇÃO MENSAL',
							        style: {
			                           fontWeight: 'bold',
			                           fontSize:'23px'
			                        }
							    },
							    xAxis: {
							    	labels:{
						                style:{
						                    color:'black',
						                    fontSize:'17px',
						                }
						            },
							        categories: [<?=$tp_nome?>],
							        title: {
							            text: null
							        }
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Quantidade de Chamados',
							            align: 'high'
							        },
							        labels: {
							            overflow: 'justify'
							        },
							        visible: false
							    },
							    tooltip: {
							        valueSuffix: ' Qtde'
							    },
							    plotOptions: {
							        bar: {
							            dataLabels: {
							                enabled: true
							            }
							        },
							        series: {
						                dataLabels: {
						                    enabled: true,
						                    style: {
						                        fontSize:'20px'
						                    }
						                }
						            }
							    },
							    legend: {
							        layout: 'vertical',
							        align: 'right',
							        verticalAlign: 'top',
							        x: -40,
							        y: 40,
							        floating: true,
							        borderWidth: 1,
							        backgroundColor:
							            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
							        shadow: true,
							        itemStyle: {
						                fontSize: '15px'
						            },
						            enabled: false
							    },
							    credits: {
							        enabled: false
							    },
							    series: [{
							        name: 'Mensal',
							        data: [<?=$tp_valor?>]
							    }]
							});

						</script>
					</div>
				</div>
			</div>
			<div class="span5 corpo">
				<div class="div_grafico">
					<div id="grafico_media_satisfacao_atendente" class="grafico sombra_grafico">
						<?php 
							$sql = "WITH qtde_hd_dia     AS (
																SELECT DISTINCT
														                        COUNT(xtbl_hd_chamado.hd_chamado) AS qtde_hd_aberto_dia,
														                        SUM(JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::integer) AS nota_dia,
														                        xtbl_hd_chamado.atendente 
														        FROM xtbl_hd_chamado
														        JOIN tbl_hd_chamado_extra ON xtbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
														        JOIN xtbl_hd_chamado_item ON xtbl_hd_chamado.hd_chamado = xtbl_hd_chamado_item.hd_chamado
														        $xjoin_posto
														        WHERE xtbl_hd_chamado.fabrica = $login_fabrica
														        AND xtbl_hd_chamado.posto NOTNULL
														        {$xcond_item_dia}
														        AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais) NOTNULL
														        {$xcond_atendimento}
																{$xcond_atendente}
																{$cond_regiao}
																{$xcond_categoria}
																{$cond_nota}
																{$xcond_posto}
														        GROUP BY xtbl_hd_chamado.atendente
														        ORDER BY qtde_hd_aberto_dia ASC
															), 
									     qtde_hd_mes     AS (
											 					SELECT DISTINCT
														                        COUNT(xtbl_hd_chamado.hd_chamado) AS qtde_hd_aberto_mes,
														                        SUM(JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::integer) AS nota_mes,
														                        xtbl_hd_chamado.atendente 
														        FROM xtbl_hd_chamado
														        JOIN tbl_hd_chamado_extra ON xtbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
														        JOIN xtbl_hd_chamado_item ON xtbl_hd_chamado.hd_chamado = xtbl_hd_chamado_item.hd_chamado
														        $xjoin_posto
														        WHERE xtbl_hd_chamado.fabrica = $login_fabrica
														        AND xtbl_hd_chamado.posto NOTNULL
														        {$xcond_item_mes}
														        AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais) NOTNULL
														        {$xcond_atendimento}
																{$xcond_atendente}
																{$cond_regiao}
																{$xcond_categoria}
																{$cond_nota}
																{$xcond_posto}
														        GROUP BY xtbl_hd_chamado.atendente
														        ORDER BY qtde_hd_aberto_mes ASC
											 				)
									     						SELECT nota_dia/qtde_hd_aberto_dia AS media_nota_dia,
									     							   nota_mes/qtde_hd_aberto_mes AS media_nota_mes,
									     							   tbl_admin.login
									     						FROM qtde_hd_mes
									     						LEFT JOIN qtde_hd_dia ON qtde_hd_mes.atendente = qtde_hd_dia.atendente
									     						JOIN tbl_admin ON qtde_hd_mes.atendente = tbl_admin.admin
									     						WHERE tbl_admin.admin_sap is true
									     						ORDER BY media_nota_mes ASC";
							$res = pg_query($con, $sql);
							if (pg_num_rows($res) > 0) {
								$qtde_nota_admin = pg_fetch_all($res);
								$qtde_nota_admin_total = [];

								$qtde_nota_admin_nome = [];
								$qtde_nota_admin_valor_dia = [];
								$qtde_nota_admin_valor_mes = [];

								$qtde_ad_nome = '';
								$qtde_ad_dia = '';
								$qtde_ad_mes = '';

								foreach ($qtde_nota_admin as $key => $value) {
									if (empty($value['media_nota_dia'])) {
										$value['media_nota_dia'] = 0;
									}

									if (empty($value['media_nota_mes'])) {
										$value['media_nota_mes'] = 0;
									}

									$qtde_nota_admin_nome[$key] = utf8_encode($value['login']);
									$qtde_nota_admin_valor_dia[$key] = (int)$value['media_nota_dia'];
									$qtde_nota_admin_valor_mes[$key] = (int)$value['media_nota_mes'];

									/*$qtde_nota_admin_total[] = ['name'=>utf8_encode($value['login']),
																'data'=>[
																			(int)$value['media_nota_dia'],	
																			(int)$value['media_nota_mes']
																		]
															   ];*/
									
								}
								
								$qtde_ad_nome = implode(",", $qtde_nota_admin_nome);
								$qtde_ad_nome = "'".strtoupper(str_replace(",", "','", $qtde_ad_nome))."'";
								$qtde_ad_dia = implode(",", $qtde_nota_admin_valor_dia);
								$qtde_ad_mes = implode(",", $qtde_nota_admin_valor_mes);

								//$qtde_nota_admin_total = json_encode($qtde_nota_admin_total);
								
							}

						?>
						<script>
							/*Highcharts.chart('grafico_media_satisfacao_atendente', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'Média Satisfação Atendente'
							    },
							    xAxis: {
							        categories: [
							            'Diario',
							            'Mensal'
							        ],
							        crosshair: true
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Qtde. Chamados'
							        },
							        visible: false
							    },
							    tooltip: {
							        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
							            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
							        footerFormat: '</table>',
							        shared: true,
							        useHTML: true
							    },
							    plotOptions: {
							        column: {
							            pointPadding: 0.2,
							            borderWidth: 0
							        },
							        series: {
							            borderWidth: 0,
							            dataLabels: {
							                enabled: true,
							                format: '{point.y}'
							            }
							        }
							    },
							    series: <?= $qtde_nota_admin_total ?>
							});*/

							Highcharts.chart('grafico_media_satisfacao_atendente', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'MÉDIA SATISFAÇÃO ATENDENTE (NOTA): DIÁRIO E MENSAL',
							        style: {
			                            fontWeight: 'bold',
			                            fontSize:'23px'
			                        }
							    },
							    xAxis: {
							    	labels:{
						                style:{
						                    color:'black',
						                    fontSize:'20px',
						                }
						            },
							        categories: [<?=$qtde_ad_nome?>],
							        title: {
							            text: null
							        }
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Qtde. Chamados',
							            align: 'high'
							        },
							        labels: {
							            overflow: 'justify'
							        },
							        visible: false
							    },
							    tooltip: {
							        valueSuffix: ' Nota'
							    },
							    plotOptions: {
							        bar: {
							            dataLabels: {
							                enabled: true
							            }
							        },
							        series: {
						                dataLabels: {
						                    enabled: true,
						                    style: {
						                        fontSize:"20px"
						                    }
						                }
						            }
							    },
							    legend: {
							        layout: 'vertical',
							        align: 'right',
							        verticalAlign: 'top',
							        x: -40,
							        y: 25,
							        floating: true,
							        borderWidth: 1,
							        backgroundColor:
							            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
							        shadow: true,
							        itemStyle: {
						                fontSize: '12px'
						            }
							    },
							    credits: {
							        enabled: false
							    },
							    series: [{
							        name: 'Diário',
							        data: [<?=$qtde_ad_dia?>],
							        color:'yellow'
							    }, {
							        name: 'Mensal',
							        data: [<?=$qtde_ad_mes?>],
							        color:'black'
							    
							    }]
							}); 

						</script>
					</div>
					<div id="grafico_media_satisfacao_regiao" class="grafico sombra_grafico" style="margin-top: 2%;">
						<?php 

						$sql_mes = " WITH todos_chamados_mes AS (
																	SELECT xtbl_hd_chamado.hd_chamado, 
																		   tbl_posto.estado,
																		   JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::integer AS nota
																	FROM xtbl_hd_chamado
															        JOIN tbl_hd_chamado_extra ON xtbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
															        JOIN xtbl_hd_chamado_item ON xtbl_hd_chamado.hd_chamado = xtbl_hd_chamado_item.hd_chamado
															        JOIN tbl_posto ON xtbl_hd_chamado.posto = tbl_posto.posto
															        WHERE xtbl_hd_chamado.fabrica = $login_fabrica
															        AND xtbl_hd_chamado.posto NOTNULL
															        {$xcond_item_mes}
															        AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais) NOTNULL
															        {$xcond_atendimento}
																	{$xcond_atendente}
																	{$cond_regiao}
																	{$xcond_categoria}
																	{$cond_nota}
																	{$xcond_posto}
															 	 ),
										 	todos_mes AS (
										 					SELECT DISTINCT
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('AC','AP','AM','PA','RO','RR','TO')) AS chamados_regiao_norte,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('AC','AP','AM','PA','RO','RR','TO')) AS nota_regiao_norte,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('AL','BA','CE','MA','PB','PI','PE','RN','SE')) AS chamados_regiao_nordeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('AL','BA','CE','MA','PB','PI','PE','RN','SE')) AS nota_regiao_nordeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('DF','GO','MT','MS')) AS chamados_regiao_centro_oeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('DF','GO','MT','MS')) AS nota_regiao_centro_oeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('ES','MG','RJ','SP')) AS chamados_regiao_sudeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('ES','MG','RJ','SP')) AS nota_regiao_sudeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('PR','RS','SC')) AS chamados_regiao_sul,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_mes
												 					 WHERE UPPER(todos_chamados_mes.estado) IN ('PR','RS','SC')) AS nota_regiao_sul
												 			FROM todos_chamados_mes

										 				 )
										 					SELECT nota_regiao_norte/chamados_regiao_norte AS norte,
										 						   nota_regiao_nordeste/chamados_regiao_nordeste AS nordeste,
										 						   nota_regiao_centro_oeste/chamados_regiao_centro_oeste AS centro_oeste,
										 						   nota_regiao_sudeste/chamados_regiao_sudeste AS sudeste,
										 						   nota_regiao_sul/chamados_regiao_sul AS sul
										 					FROM todos_mes";
						$res_mes = pg_query($con, $sql_mes);
						if (pg_num_rows($res_mes) > 0) {
							$regiao_mes = pg_fetch_all($res_mes);
						}

						$sql_dia = "WITH	todos_chamados_dia AS (
									 							SELECT xtbl_hd_chamado.hd_chamado, 
																	   tbl_posto.estado,
																	   JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais)::integer AS nota
																FROM xtbl_hd_chamado
														        JOIN tbl_hd_chamado_extra ON xtbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
														        JOIN xtbl_hd_chamado_item ON xtbl_hd_chamado.hd_chamado = xtbl_hd_chamado_item.hd_chamado
														        JOIN tbl_posto ON xtbl_hd_chamado.posto = tbl_posto.posto
														        WHERE xtbl_hd_chamado.fabrica = $login_fabrica
														        AND xtbl_hd_chamado.posto NOTNULL
														        {$xcond_item_dia}
														        AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais) NOTNULL
														        {$xcond_atendimento}
																{$xcond_atendente}
																{$cond_regiao}
																{$xcond_categoria}
																{$cond_nota}
																{$xcond_posto}
									 						  ),
							   				todos_dia AS (
										 					SELECT DISTINCT
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('AC','AP','AM','PA','RO','RR','TO')) AS chamados_regiao_norte,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('AC','AP','AM','PA','RO','RR','TO')) AS nota_regiao_norte,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('AL','BA','CE','MA','PB','PI','PE','RN','SE')) AS chamados_regiao_nordeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('AL','BA','CE','MA','PB','PI','PE','RN','SE')) AS nota_regiao_nordeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('DF','GO','MT','MS')) AS chamados_regiao_centro_oeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('DF','GO','MT','MS')) AS nota_regiao_centro_oeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('ES','MG','RJ','SP')) AS chamados_regiao_sudeste,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('ES','MG','RJ','SP')) AS nota_regiao_sudeste,
												 					(SELECT count(1) AS hd
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('PR','RS','SC')) AS chamados_regiao_sul,
												 					(SELECT SUM(nota) 
												 					 FROM todos_chamados_dia
												 					 WHERE UPPER(todos_chamados_dia.estado) IN ('PR','RS','SC')) AS nota_regiao_sul
												 			FROM todos_chamados_dia

									 				 )
									 					SELECT nota_regiao_norte/chamados_regiao_norte AS norte,
									 						   nota_regiao_nordeste/chamados_regiao_nordeste AS nordeste,
									 						   nota_regiao_centro_oeste/chamados_regiao_centro_oeste AS centro_oeste,
									 						   nota_regiao_sudeste/chamados_regiao_sudeste AS sudeste,
									 						   nota_regiao_sul/chamados_regiao_sul AS sul
									 					FROM todos_dia";
						$res_dia = pg_query($con, $sql_dia);
						if (pg_num_rows($res_dia) > 0) {
							$regiao_dia = pg_fetch_all($res_dia);
						}

						$regiao_chamados = [];
						$regiao_chamados[0] = ["name"=>"Norte",
											   "data"=>[
											   			(int)$regiao_dia[0]['norte'],
											   			(int)$regiao_mes[0]['norte']
											   		   ]
											  ];
						$regiao_chamados[1] = ["name"=>"Nordeste",
											   "data"=>[
											   			(int)$regiao_dia[0]['nordeste'],
											   			(int)$regiao_mes[0]['nordeste']
											   		   ]
											   	];
						$regiao_chamados[2] = ["name"=>"Centro Oeste",
											   "data"=>[
											   			(int)$regiao_dia[0]['centro_oeste'],
											   			(int)$regiao_mes[0]['centro_oeste']
											   		   ]
											   	];
						$regiao_chamados[3] = ["name"=>"Sudeste",
											   "data"=>[
											   			(int)$regiao_dia[0]['sudeste'],
											   			(int)$regiao_mes[0]['sudeste']
											   		   ]
											   	];
						$regiao_chamados[4] = ["name"=>"Sul",
											   "data"=>[
											   			(int)$regiao_dia[0]['sul'],
											   			(int)$regiao_mes[0]['sul']
											   		   ]
											   	];

						$dados_regiao_chamados_para_ordenar = [];
						$dados_regiao_chamados_para_ordenar_new = [];

						$qtde_regiao_p_nome = [];
						$qtde_regiao_p_dia = [];
						$qtde_regiao_p_mes = [];

						$qtde_p_nome = '';
						$qtde_p_dia = '';
						$qtde_p_mes = '';

						foreach ($regiao_chamados as $k => $v) {
							$dados_regiao_chamados_para_ordenar[] = ["data"=>$v['data'][1]];  
						}

						usort($dados_regiao_chamados_para_ordenar, 'cmp');

						foreach ($dados_regiao_chamados_para_ordenar as $ky => $val) {
							foreach ($regiao_chamados as $y => $vl) {
								if ($val['data'] == $vl['data'][1]) {
									$dados_regiao_chamados_para_ordenar_new[] = $vl;
									unset($regiao_chamados[$y]);
								}
							}
						}

						$soma_regiao_dia  = "";
						$soma_regiao_mes  = "";

						foreach ($dados_regiao_chamados_para_ordenar_new as $t => $tt) {
							if ($tt['data'][0] == 0 && $tt['data'][1] == 0) {
								continue;
							}

							$qtde_regiao_p_nome[$t] = $tt['name']; 
							$qtde_regiao_p_dia[$t] = $tt['data'][0];
							$soma_regiao_dia +=  $tt['data'][0];
							$qtde_regiao_p_mes[$t] = $tt['data'][1];
							$soma_regiao_mes += $tt['data'][1];
						}

						$qtde_regiao_p_nome[] = "Total";
						$qtde_regiao_p_dia[] = round($soma_regiao_dia/5);
						$qtde_regiao_p_mes[] = round($soma_regiao_mes/5);

						$qtde_p_nome = implode(",", $qtde_regiao_p_nome);
						$qtde_p_nome = "'".strtoupper(str_replace(",", "','", $qtde_p_nome))."'";
						$qtde_p_dia  = implode(",", $qtde_regiao_p_dia);
						$qtde_p_mes  = implode(",", $qtde_regiao_p_mes);

						//$regiao_chamados = json_encode($dados_regiao_chamados_para_ordenar_new);

		
						?>
						<script>
							/*Highcharts.chart('grafico_media_satisfacao_regiao', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'Média Satisfação Região'
							    },
							    xAxis: {
							        categories: [
							            'Diario',
							            'Mensal',
							        ],
							        crosshair: true
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Qtde. Chamados'
							        },
							        visible: false
							    },
							    tooltip: {
							        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
							            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>',
							        footerFormat: '</table>',
							        shared: true,
							        useHTML: true
							    },
							    plotOptions: {
							        column: {
							            pointPadding: 0.2,
							            borderWidth: 0
							        },
							        series: {
							            borderWidth: 0,
							            dataLabels: {
							                enabled: true,
							                format: '{point.y}'
							            }
							        }
							    },
							    series: <?= $regiao_chamados ?>
							});*/

							Highcharts.chart('grafico_media_satisfacao_regiao', {
							    chart: {
							        type: 'column'
							    },
							    title: {
							        text: 'MÉDIA SATISFAÇÃO REGIÃO (NOTA): DIÁRIO E MENSAL',
							        style: {
			                        	fontWeight: 'bold',
			                        	fontSize:'23px'
			                        }
							    },
							    xAxis: {
							    	labels:{
						                style:{
						                    color:'black',
						                    fontSize:"20px",
						                }
						            },
							        categories: [<?=$qtde_p_nome?>],
							        title: {
							            text: null
							        }
							    },
							    yAxis: {
							        min: 0,
							        title: {
							            text: 'Qtde. Chamados',
							            align: 'high'
							        },
							        labels: {
							            overflow: 'justify'
							        },
							        visible: false
							    },
							    tooltip: {
							        valueSuffix: ' Nota'
							    },
							    plotOptions: {
							        bar: {
							            dataLabels: {
							                enabled: true
							            }
							        },
							        series: {
						                dataLabels: {
						                    enabled: true,
						                    style: {
						                        fontSize:'20px'
						                    }
						                }
						            }
							    },
							    legend: {
							        layout: 'vertical',
							        align: 'right',
							        verticalAlign: 'top',
							        x: -40,
							        y: 25,
							        floating: true,
							        borderWidth: 1,
							        backgroundColor:
							            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
							        shadow: true,
							        itemStyle: {
						                fontSize: '12px'
						            }
							    },
							    credits: {
							        enabled: false
							    },
							    series: [{
							        name: 'Diário',
							        data: [<?=$qtde_p_dia?>],
							        color:'yellow'
							    }, {
							        name: 'Mensal',
							        data: [<?=$qtde_p_mes?>],
							        color:'black'
							    
							    }]
							});

						</script>
					</div>
				</div>
			</div>
		</div>
		<?php

		$sql = "SELECT DISTINCT(xtbl_hd_chamado.hd_chamado), 
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							xtbl_hd_chamado.data_aprovacao,
							extract (epoch FROM (now() - xtbl_hd_chamado.data_aprovacao)) / 60 AS tempo_interacao,
							tbl_hd_chamado_extra.array_campos_adicionais AS array_campos,
							tbl_admin.login
					FROM tbl_hd_chamado_extra
					JOIN xtbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = xtbl_hd_chamado.hd_chamado
					JOIN tbl_posto ON xtbl_hd_chamado.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_admin ON xtbl_hd_chamado.atendente = tbl_admin.admin
					WHERE xtbl_hd_chamado.fabrica = $login_fabrica
					AND xtbl_hd_chamado.posto NOTNULL
					AND xtbl_hd_chamado.data_aprovacao NOTNULL
					{$xcond_data_ap}
					AND JSON_FIELD('avaliacao_pontuacao', tbl_hd_chamado_extra.array_campos_adicionais) <> ''
					AND JSON_FIELD('avaliacao_mensagem', tbl_hd_chamado_extra.array_campos_adicionais) <> ''
					--AND array_campos_adicionais::jsonb->>'avaliacao_pontuacao' <> ''
					--AND array_campos_adicionais::jsonb->>'avaliacao_mensagem' <> ''
					{$xcond_atendimento}
					{$xcond_atendente}
					{$cond_regiao}
					{$xcond_categoria}
					{$cond_nota}
					{$xcond_posto}
					ORDER BY xtbl_hd_chamado.data_aprovacao DESC LIMIT 100";
		$res = pg_query($con, $sql);

		$listaDeChamados = pg_fetch_all($res);

		if (!empty($listaDeChamados)) {
		?>
			<div class="row-fluid div_msg" style="margin: 15px -10px 0 4px; height: 260px !important; overflow:auto;">
		<?php 
			
			$count_top = 0;
			$count_primeira = 0;

			foreach ($listaDeChamados as $key => $chamado) {
			
				$nomePosto   = "{$chamado['codigo_posto']} - {$chamado['nome']}";
				$arrayCampos = json_decode( $chamado['array_campos']);
				$mensagem    = (mb_check_encoding( $arrayCampos->avaliacao_mensagem, 'UTF-8' )) ? utf8_decode( $arrayCampos->avaliacao_mensagem ) : $arrayCampos->avaliacao_mensagem; 
				
				$log_at = "";
				if (!empty($chamado['login'])) {
					$log_at = '<h5 style="font-size: 12px; margin: 3px">Atendente: '.$chamado['login'].'</h5>';
					$log_at_new = '      /      ATENDENTE: '.$chamado['login'];
				}

				$notaAvaliacao = $arrayCampos->avaliacao_pontuacao;
				$hdChamado = $chamado['hd_chamado'];
				$tempoPassadoEmMinutos = preg_replace('/\.\d+$/','', $chamado['tempo_interacao']);

				if ($notaAvaliacao <= 4) {
					$div_nota = "bloco_img_0";
					$img_caminho = "imagens/ruim_dashboard.png";
				} elseif ($notaAvaliacao <= 6) {
					$div_nota = "bloco_img_4";
					$img_caminho = "imagens/regular_dashboard.png";
				} elseif ($notaAvaliacao <= 8) {
					$div_nota = "bloco_img_7";
					$img_caminho = "imagens/bom_dashboard.png";
				} else {
					$div_nota = "bloco_img_10";
					$img_caminho = "imagens/otimo_dashboard.png";
				}

				$top = "";
				if ($count_top > 2) {
					$top = "margin-top: 1%";
				}

				$mg_left = "margin-left: 1.2%;";

				if ($count_primeira == 0) {
					$mg_left = "margin-left: 0.2%;";
				}

				?>

				<div class="span4 <?=$div_nota?> sombra_grafico" style="height: 100px; display:flex; box-sizing:border-box; <?=$mg_left?> <?=$top?>">

					<div style="max-width: 120px; padding: 5px; display: flex; justify-content: center;">
						<img src="<?=$img_caminho?>" style="max-width: 100%; max-height: 100%">
					</div>

					<div style="width: 100%">
						<div style="padding: 7px;">
							<div style="display: flex; font-size: 17px; justify-content: space-between; width: 100%">
								<span><b>HD - <?= $hdChamado.$log_at_new ?></b></span>
								<span><b>Há <?= $tempoPassadoEmMinutos ?> minutos</b></span>
							</div>
							<h5 style="font-size: 19px; margin: 3px"><?=substr($nomePosto,0,37)."..." ?></h5>
							<!-- <?=$log_at?> -->

							<?php 
								$mensagem = substr($mensagem, 0, 80);
							?>

							<h5 style="font-size: 17px; margin: 3px">
								<?= $mensagem . '...' ?>
							</h5>
							
						</div>
					</div>

				</div>
		<?php			

			$count_top++;
			$count_primeira = ($count_primeira == 2) ? 0 : $count_primeira + 1;
		
			}
		?>	
			</div>
		<?php
		}

		/*if( !empty($listaDeChamados) ){
			$listaDeChamadosAgrupados = [];
			foreach ($listaDeChamados as $key => $value) {

				static $posicao = 0;

				if( $key == 0) {
					$listaDeChamadosAgrupados[$posicao][] = $value;
					continue;
				}

				if( $key % 3 == 0 ){
					$posicao++;
				}

				$listaDeChamadosAgrupados[$posicao][] = $value;
			}

			foreach ($listaDeChamadosAgrupados as $chamados) { ?>

				<div class="row-fluid" style="margin: 15px -10px 0 4px;">
					<?php foreach ($chamados as $key => $chamado) {

					$nomePosto   = "{$chamado['codigo_posto']} - {$chamado['nome']}";
					$arrayCampos = json_decode( $chamado['array_campos'] );
					$mensagem    = (mb_check_encoding( $arrayCampos->avaliacao_mensagem, 'UTF-8' )) ? utf8_decode( $arrayCampos->avaliacao_mensagem ) : $arrayCampos->avaliacao_mensagem; 

					$notaAvaliacao = $arrayCampos->avaliacao_pontuacao;
					$hdChamado = $chamado['hd_chamado'];
					$tempoPassadoEmMinutos = preg_replace('/\.\d+$/','', $chamado['tempo_interacao']);

					if ($notaAvaliacao <= 4) {
						$div_nota = "bloco_img_0";
						$img_caminho = "imagens/ruim_dashboard.png";
					} elseif ($notaAvaliacao <= 6) {
						$div_nota = "bloco_img_4";
						$img_caminho = "imagens/regular_dashboard.png";
					} elseif ($notaAvaliacao <= 8) {
						$div_nota = "bloco_img_7";
						$img_caminho = "imagens/bom_dashboard.png";
					} else {
						$div_nota = "bloco_img_10";
						$img_caminho = "imagens/otimo_dashboard.png";
					}?>
					
					<div class="span4 <?=$div_nota?>" style="height: 135px; display:flex; box-sizing:border-box; margin-right: -10px">

						<div style="max-width: 120px; padding: 5px; display: flex; justify-content: center;">
							<img src="<?=$img_caminho?>" style="max-width: 100%; max-height: 100%">
						</div>

						<div style="width: 100%">
							<div style="padding: 7px;">
								<div style="display: flex; justify-content: space-between; width: 100%">
									<span>HD - <?= $hdChamado ?></span>
									<small>Há <?= $tempoPassadoEmMinutos ?> minutos</small>
								</div>
								<h5 style="font-size: 12px; margin: 3px"><?= $nomePosto ?></h5>

								<?php 
									$mensagem = substr($mensagem, 0, 110);
								?>

								<h5 style="font-size: 10px; margin: 3px">
									<?= $mensagem . '...' ?>
								</h5>
								
							</div>
						</div>

					</div>

				<?php } ?>

				</div>

			<?php }

		}*/ ?>
	</body>
</html>
		
	<script type="text/javascript">
		$(document).ready(function () {
			/*var tm_div_box = $(".div_info").height();
			$(".grafico").height(tm_div_box/2);*/
			atualizaHeight()
			
			<?php
				if (!isset($_GET['data_inicial']) && !isset($_GET['data_final']) && !isset($_GET['atendimento'])) {
			?>
					setTimeout(function() {
					  window.location.reload(1);
					}, 300000);
			<?php
				}
			?>
		
			$(".div_info").click(function() {
				atualizaHeight();
			});

		});

		function atualizaHeight() {
			var tm_div_box = $(".div_info").height();
			$(".grafico").height(tm_div_box/2);
		}
	</script>
