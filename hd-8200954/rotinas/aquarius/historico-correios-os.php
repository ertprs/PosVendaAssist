<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 174;

$sql = "SELECT  os, campos_adicionais, campos_adicionais::jsonb->>'rastreio' AS rastreio
        FROM tbl_os_campo_extra
        WHERE fabrica = $fabrica
        AND campos_adicionais::jsonb ? 'rastreio'
        AND (campos_adicionais::jsonb ? 'historico_correios') IS FALSE
        AND UPPER(campos_adicionais::jsonb->>'rastreio') ~ 'BR'";
$qry = pg_query($con, $sql);

$soap = new SoapClient(
    "http://webservice.correios.com.br/service/rastro/Rastro.wsdl",
    [
        "trace" => 1,
        "connection_timeout" => 30,
        "stream_context" => stream_context_create(
            [
                "http" => [
                    "protocol_version" => "1.0",
                    "header" => "Connection: Close"
                ]
            ]
        )
    ]
);

$prep = pg_prepare(
    $con,
    "verifica_situacao",
    'SELECT faturamento_correio FROM tbl_faturamento_correio WHERE conhecimento = $1 AND situacao = $2'
);

while ($fetch = pg_fetch_assoc($qry)) {
    $os = $fetch['os'];
    $rastreio = $fetch['rastreio'];
    $campos_adicionais = json_decode($fetch['campos_adicionais'], true);

    $res = pg_execute($con, "verifica_situacao", [$rastreio, 'Objeto entregue ao destinatário']);
    if (pg_num_rows($res) > 0) {
        continue;
    }

    $metodo = "buscaEventos";
    $buscaEventos = (object) [
        "usuario" => "9912358441",
        "senha" => "P?WPP?VZ@O",
        "tipo" => "L",
        "resultado" => "T",
        "lingua" => 101,
        "objetos" => $rastreio
    ];

    $soapResult = $soap->__soapCall($metodo, array($buscaEventos));

    foreach ($soapResult->return->objeto->evento as $linha) {
        $obs = '';

        $local    = $linha->local;
        $data     = $linha->data;
        $hora     = $linha->hora;
        $situacao = utf8_decode($linha->descricao);
        $cidade   = $linha->cidade;

        if (empty($data)) {
            continue;
        }

        if (!empty(trim($linha->comentario))) {
            $obs .= $linha->comentario;
        }

        if (isset($linha->destino)) {
            $destinoLocal  = $linha->destino->local;
            $destinoCidade = $linha->destino->cidade;
            $destinoCodigo = $linha->destino->codigo;
            $destinoUf     = $linha->destino->uf;

            $obs .= "Código: $destinoCodigo Encaminhado para ".$destinoLocal."/".$destinoCidade."-".$destinoUf;
        }

        $dataHora = DateTime::createFromFormat('d/m/Y H:i', $data . ' ' . $hora);

        $sql_grava_rastreio = "INSERT INTO tbl_faturamento_correio (
            fabrica,
            local,
            conhecimento,
            situacao,
            data,
            obs,
            numero_postagem
        ) VALUES (
            $fabrica,
            '$local',
            '$rastreio',
            '$situacao',
            '" . $dataHora->format('Y-m-d H:i:s') . "',
            '$obs',
            ' '
        )";
        $res_grava_rastreio = pg_query($con, $sql_grava_rastreio);

        if (!pg_last_error($con) and $situacao == 'Objeto entregue ao destinatário') {
            $campos_adicionais['historico_correios'] = 't';
            $update = "UPDATE tbl_os_campo_extra SET campos_adicionais = '" . json_encode($campos_adicionais) . "' WHERE os = $os";
            $res = pg_query($con, $update);
        }
    }
}
