<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';

require dirname(__FILE__) . '/../../classes/Mirrors/Queue/PushQueue.php';

$login_fabrica = 169;
$os = $argv[1];

$pushQueue = new PushQueue();

$dataInicial = new \DateTime('now - 1 day',new \DateTimeZone('America/Sao_Paulo'));
$dataFinal = new \DateTime('now',new \DateTimeZone('America/Sao_Paulo'));
$data_inicial = $dataInicial->format('Y-m-d H:i:s');
$data_final = $dataFinal->format('Y-m-d H:i:s');

if(!empty($os)){
	$cond = " AND o.os = {$os} ";
}else{
	$cond = " AND (o.finalizada BETWEEN '{$data_inicial}' AND '{$data_final}') ";
}

$sql = "SELECT
    o.consumidor_nome,
    o.consumidor_cpf,
    o.consumidor_cidade,
    o.consumidor_estado,
    o.consumidor_celular,
    o.consumidor_fone,
    o.consumidor_email,
    pf.codigo_posto,
    p.nome AS nome_posto,
    o.os,
    o.sua_os,
    hcs.descricao AS classificacao,
    hml.descricao AS providencia,
    a.nome_completo AS inspetor,
    ta.descricao AS tipo_atendimento,
    TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
    (
        SELECT TO_CHAR(data_agendamento, 'DD/MM/YYYY')
        FROM tbl_tecnico_agenda
        WHERE fabrica = {$login_fabrica}
        AND os = o.os
        AND confirmado IS NOT NULL
        ORDER BY confirmado ASC
        LIMIT 1
    ) AS data_visita,
    (
        SELECT TO_CHAR(confirmado, 'DD/MM/YYYY')
        FROM tbl_tecnico_agenda
        WHERE fabrica = {$login_fabrica}
        AND os = o.os
        AND confirmado IS NOT NULL
        ORDER BY confirmado ASC
        LIMIT 1
    ) AS data_confirmacao_agendamento,
    CASE WHEN (SELECT COUNT(tecnico_agenda) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} AND os = o.os) > 1 THEN
        (
            SELECT TO_CHAR(data_agendamento, 'DD/MM/YYYY')
            FROM tbl_tecnico_agenda
            WHERE fabrica = {$login_fabrica}
            AND os = o.os
            AND confirmado IS NOT NULL
            ORDER BY confirmado DESC
            LIMIT 1
        )
    ELSE NULL END AS data_reagendamento,
    TO_CHAR(o.data_conserto, 'DD/MM/YYYY') AS data_conserto,
    TO_CHAR(o.finalizada, 'DD/MM/YYYY') AS finalizada,
    prd.descricao AS descricao_produto,
    dr.descricao AS defeito_reclamado,
    (SELECT ARRAY_TO_STRING(ARRAY(
        SELECT dc.descricao
        FROM tbl_os_defeito_reclamado_constatado odrc
        INNER JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = odrc.defeito_constatado AND dc.fabrica = {$login_fabrica}
        WHERE odrc.fabrica = {$login_fabrica}
        AND odrc.os = o.os
    ), '|')) AS defeito_constatado,
    t.nome AS tecnico,
    tbl_familia.codigo_familia,
    ahc.login,
    tbl_hd_chamado_origem.descricao AS origem
FROM tbl_os o
LEFT JOIN tbl_hd_chamado_extra hce ON hce.os = o.os
INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
INNER JOIN tbl_posto p ON p.posto = pf.posto
INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
LEFT JOIN tbl_hd_chamado hc ON hc.hd_chamado = hce.hd_chamado AND hc.fabrica = {$login_fabrica}
LEFT JOIN tbl_hd_classificacao hcs ON hcs.hd_classificacao = hc.hd_classificacao AND hcs.fabrica = {$login_fabrica}
LEFT JOIN tbl_hd_motivo_ligacao hml ON hml.hd_motivo_ligacao = hce.hd_motivo_ligacao AND hml.fabrica = {$login_fabrica}
LEFT JOIN tbl_nps_lote_protocolo nlp ON nlp.protocolo = o.os
LEFT JOIN tbl_admin a ON a.admin = pf.admin_sap AND a.fabrica = {$login_fabrica}
LEFT JOIN tbl_admin ahc ON ahc.admin = hc.admin AND ahc.fabrica = {$login_fabrica}
INNER JOIN tbl_os_produto op ON op.os = o.os
INNER JOIN tbl_produto prd ON prd.produto = op.produto AND prd.fabrica_i = {$login_fabrica}
INNER JOIN tbl_familia ON prd.familia = tbl_familia.familia AND tbl_familia.fabrica = {$login_fabrica}
LEFT JOIN tbl_defeito_reclamado dr ON dr.defeito_reclamado = o.defeito_reclamado AND dr.fabrica = {$login_fabrica}
LEFT JOIN tbl_tecnico t ON t.tecnico = o.tecnico
LEFT JOIN tbl_hd_chamado_origem ON hce.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
WHERE o.fabrica = {$login_fabrica}
AND o.finalizada IS NOT NULL
AND (
    (hcs.hd_classificacao = 185 AND hml.hd_motivo_ligacao IN(374, 447))
    OR
    (o.hd_chamado IS NULL AND ta.fora_garantia IS NOT TRUE AND ta.km_google IS NOT TRUE)
)
AND nlp.protocolo IS NULL
AND o.consumidor_revenda = 'C' 
$cond
limit 10";

$res = pg_query($con, $sql);
$res = pg_fetch_all($res);

foreach ($res as $item) {
    $data = [
        "fabrica" => $login_fabrica,
        "customers" => [
            [
                "consumidor_nome" => utf8_encode($item['consumidor_nome']),
                "consumidor_cpf" => utf8_encode($item['consumidor_cpf']),
                "consumidor_cidade" => utf8_encode(utf8_encode($item['consumidor_cidade'])),
                "consumidor_estado" => utf8_encode($item['consumidor_estado']),
                "consumidor_celular" => $item['consumidor_celular'],
                "consumidor_fone" => $item['consumidor_fone'],
                "consumidor_email" => $item['consumidor_email'],
                "codigo_posto" => $item['codigo_posto'],
                "nome_posto" => utf8_encode($item['nome_posto']),
                "os" => $item['os'],
                "sua_os" => $item['sua_os'],
                "classificacao" => utf8_encode($item['classificacao']),
                "providencia" => utf8_encode($item['providencia']),
                "inspetor" => utf8_encode($item['inspetor']),
                "tipo_atendimento" => utf8_encode($item['tipo_atendimento']),
                "data_abertura" => $item['data_abertura'],
                "data_visita" => $item['data_visita'],
                "data_confirmacao_agendamento" => $item['data_confirmacao_agendamento'],
                "data_reagendamento" => $item['data_reagendamento'],
                "data_conserto" => $item['data_conserto'],
                "finalizada" => $item['finalizada'],
                "descricao_produto" => utf8_encode($item['descricao_produto']),
                "defeito_reclamado" => utf8_encode($item['defeito_reclamado']),
                "defeito_constatado" => array_map(function($dc) { return utf8_encode($dc); }, explode('|', $item['defeito_constatado'])),
                "tecnico" => utf8_encode($item['tecnico']),
		"familia_produto" => $item['codigo_familia']

            ]
        ]
    ];

	$pushQueue->post("TRACKSALE_INTEGRACAO", $data);
}

$pushQueue->post("TRACKSALE_ENVIAR_CAMPANHA",["fabrica" => $login_fabrica]);
$pushQueue->post("TRACKSALE_GET_RESPOSTAS",["fabrica" => $login_fabrica]);
?>
