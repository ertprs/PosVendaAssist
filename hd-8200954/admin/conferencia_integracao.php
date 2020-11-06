<?php
$no_pdo = true;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';
include 'funcoes.php';

ini_set("memory_limit", "256M");
include '../class/communicator.class.php';

include 'cockpit/api/persys.php';
use Posvenda\DistribuidorSLA;
use Posvenda\DefeitoReclamado;
use Posvenda\Os;

$sql = "
    SELECT descricao
    FROM tbl_familia
    WHERE fabrica = {$login_fabrica}
    AND ativo IS TRUE
    ORDER BY descricao ASC
";
$qry = pg_query($con, $sql);

$familias = array();

while ($familia = pg_fetch_object($qry)) {
    $familias[] = $familia->descricao;
}
   
$sql = "
    SELECT codigo, descricao
    FROM tbl_defeito_reclamado
    WHERE fabrica = {$login_fabrica}
    ORDER BY descricao ASC
";
$qry = pg_query($con, $sql);

$defeitos = array();

while ($defeito = pg_fetch_object($qry)) {
    $defeitos["{$defeito->codigo}"] = $defeito->descricao;
}

if (!array_key_exists("ajax_refresh_content", $_POST)) {
    $sql = "
        SELECT
            hd_chamado_cockpit_prioridade AS id,
            cor AS color,
            descricao AS description,
            peso AS weight
        FROM tbl_hd_chamado_cockpit_prioridade
        WHERE fabrica = {$login_fabrica}
        ORDER BY peso ASC
    ";
    $qry = pg_query($con, $sql);

    $priorities = array();

    while ($priority = pg_fetch_object($qry)) {
        $priorities[$priority->id] = array(
            "color"       => $priority->color,
            "description" => $priority->description,
            "weight"      => $priority->weight
        );
    }

    $tipos_atendimentos = array(
        "ZKR1" => "ZKR1 - Movimentação",
        "ZKR2" => "ZKR2 - Movimentação Usado",
        "ZKR3" => "ZKR3 - Corretiva",
        "ZKR5" => "ZKR5 - Preventiva",
        "ZKR6" => "ZKR6 - Sanitização",
        "ZKR9" => "ZKR9 - Piso",
        "AMBV-GAR" => "AMBEV - Garantia Corretiva"
    );

    $oDistribuidorSLA = new DistribuidorSLA();
    $oDistribuidorSLA->setFabrica($login_fabrica);

    $distribuidores               = array();
    $unidades_negocio             = array();
    $distribuidor_unidade_negocio = array();

    foreach ($oDistribuidorSLA->select() as $i => $distribuidor) {
        $distribuidores[$distribuidor["centro"]]               = $distribuidor["descricao"];
        $distribuidor_unidade_negocio[$distribuidor["centro"]] = $distribuidor["cidade"];
        //$unidades_negocio[$distribuidor["unidade_negocio"]]    = $distribuidor["cidade"];
    }

    $sql = "SELECT DISTINCT 
            ds.unidade_negocio,
            c.nome
        FROM tbl_distribuidor_sla ds
        JOIN tbl_cidade c USING(cidade)
        WHERE ds.fabrica = {$login_fabrica}
          AND ds.centro IN('BAAA','GRAN','BFAT','BBBB','AAAA');";
    $resUnidadeNegocio   = pg_query($con, $sql);
    $countUnidadeNegocio = pg_num_rows($resUnidadeNegocio);

    $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();

    $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);

    foreach ($distribuidores_disponiveis as $unidadeNegocio) {
        if (in_array($unidadeNegocio["unidade_negocio"], $unidadesMinasGerais)) {
            unset($unidadeNegocio["unidade_negocio"]);
            continue;
        }
        $unidades_negocio[$unidadeNegocio["unidade_negocio"]] = $unidadeNegocio["cidade"];
    }


    $distribuidor_unidade_negocio = array_map(function($cidade) use($con) {
        $sql = "SELECT nome FROM tbl_cidade WHERE cidade = {$cidade}";
        $res = pg_query($con, $sql);

        return pg_fetch_result($res, 0, "nome");
    }, $distribuidor_unidade_negocio);
} else {
    $distribuidor_unidade_negocio = $_POST["distribuidor_unidade_negocio"];
    $priorities                   = $_POST["priorities"];
    $distribuidores               = $_POST["distribuidores"];
    $tipos_atendimentos           = $_POST["tipos_atendimentos"];
    $unidades_negocio             = $_POST["unidades_negocio"];

    $distribuidores = array_map(function($r) {
        return utf8_decode($r);
    }, $distribuidores);

    $tipos_atendimentos = array_map(function($r) {
        return utf8_decode($r);
    }, $tipos_atendimentos);
}

function getDataNew() {
    global $login_fabrica, $con;

    $sql = "
        SELECT DISTINCT ON (hcc.hd_chamado_cockpit)
            hcc.hd_chamado_cockpit,
            hccp.hd_chamado_cockpit_prioridade,
            hcc.dados,
            fb.fabrica,
            fb.nome AS fbNome,
            fb.cnpj AS fbCnpj,
            '3' AS timezone_type,
            'UTC' AS timezone,
            hc.hd_chamado,
            hc.admin,
            hc.posto,
            hc.data AS hcData,
            hc.titulo,
            hc.status,
            hc.atendente,
            hc.categoria,
            hc.esta_agendado,
            hc.data_providencia,
            rsl.routine_schedule_log,
            rsl.routine_schedule,
            TO_CHAR(rsl.date_start, 'YYYY-MM-DD HH24:MI:SS') AS date_start,
            TO_CHAR(rsl.date_finish, 'YYYY-MM-DD HH24:MI:SS') AS date_finish,
            rsl.file_name,
            rsl.total_line_file,
            rsl.total_record,
            rsl.total_record_processed,
            rsl.status,
            rsl.status_message,
            rsl.tdocs,
            TO_CHAR(rsl.create_at, 'YYYY-MM-DD HH24:MI:SS') AS create_at,
            hce.serie AS hceSerie,
            hce.reclamado AS hceReclamado,
            hce.data_abertura AS hceDataAbertura,
            hce.nome AS hceNome,
            hce.endereco AS hceEndereco,
            hce.numero AS hceNumero,
            hce.complemento AS hceComplemento,
            hce.bairro AS hceBairro,
            hce.cep AS hceCep,
            hce.fone AS hceFone,
            hce.fone2 AS hceFone2,
            hce.sua_os AS hceSuaOs,
            hce.dias_aberto AS hceDiasAberto,
            hce.dias_ultima_interacao AS hceDiasUltimaInteracao,
            hce.receber_info_fabrica AS hceReceberInfoFabrica,
            hce.origem AS hceOrigem,
            hce.consumidor_revenda AS hceConsumidorRevenda,
            hce.email AS hceEmail,
            hce.cpf AS hceCpf,
            hce.abre_os AS hceAbreOs,
            hce.atendimento_callcenter AS hceAtendimentoCallcenter,
            hce.tipo_atendimento AS hceTipoAtendimento,
            hce.array_campos_adicionais AS hceArrayCamposAdicionais,
            hce.qtde_km AS hceQtdeKm,
            hce.consumidor_final_nome AS hceConsumidorFinalNome,
            o.os,
            TO_CHAR(o.data_abertura, 'YYYY-MM-DD HH24:MI:SS') AS os_date,
            o.nota_fiscal,
            o.defeito_reclamado_descricao,
            o.consumidor_nome,
            o.key_code,
	    o.finalizada,
	    CASE WHEN oi.os_item IS NOT NULL THEN TRUE ELSE FALSE END AS tem_peca,
            oe.obs_fechamento,
            oe.os,
            sc.status_checkpoint,
            sc.descricao AS scDescricao,
            sc.cor,
            f.familia,
            f.descricao,
            f.codigo_familia,
            f.ativo,
            f.external_id,
            ta.tecnico_agenda,
            ta.tecnico,
            ta.admin AS taAdmin,
            TO_CHAR(ta.data_agendamento, 'YYYY-MM-DD HH24:MI:SS') AS data_agendamento,
            TO_CHAR(ta.hora_inicio_trabalho, 'YYYY-MM-DD HH24:MI:SS') AS hora_inicio_trabalho,
            TO_CHAR(ta.hora_fim_trabalho, 'YYYY-MM-DD HH24:MI:SS') AS hora_fim_trabalho,
            ta.ordem,
            TO_CHAR(ta.data_input, 'YYYY-MM-DD HH24:MI:SS') AS data_input,
            hcc.motivo_erro,
			((regexp_replace(hcc.dados,'\\\\u','\\\\\\\\u','g'))::jsonb-'comentario') as dados2
        FROM tbl_hd_chamado_cockpit hcc
        LEFT JOIN tbl_hd_chamado_cockpit_prioridade hccp ON hccp.hd_chamado_cockpit_prioridade = hcc.hd_chamado_cockpit_prioridade AND hccp.fabrica=$login_fabrica
        LEFT JOIN tbl_hd_chamado hc ON hc.hd_chamado = hcc.hd_chamado AND hc.fabrica = $login_fabrica
        LEFT JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hcc.routine_schedule_log
        LEFT JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado
	LEFT JOIN tbl_os o ON o.os = hce.os AND o.fabrica = $login_fabrica AND o.excluida IS NOT TRUE
	LEFT JOIN tbl_os_produto op ON op.os = o.os
	LEFT JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.fabrica_i=$login_fabrica
        LEFT JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
        LEFT JOIN tbl_os_extra oe ON oe.os = o.os AND oe.i_fabrica = $login_fabrica
        LEFT JOIN tbl_os_status osts ON osts.os = o.os AND osts.fabrica_status = $login_fabrica
        LEFT JOIN tbl_status_os so ON so.status_os = osts.status_os
        LEFT JOIN tbl_tecnico_agenda ta ON ta.os = o.os AND ta.fabrica = $login_fabrica
        LEFT JOIN tbl_familia f ON f.familia = hcc.familia AND f.fabrica = $login_fabrica
        JOIN tbl_fabrica fb ON hcc.fabrica = fb.fabrica
	WHERE hcc.fabrica = $login_fabrica
	AND f.descricao NOT IN ('POST MIX','CHOPEIRA')
	AND o.finalizada IS NULL
	AND rsl.create_at >= CURRENT_TIMESTAMP - INTERVAL '3 MONTHS';
    ";

    $resData = pg_query($con,$sql);
    $countDados = pg_num_rows($resData);

    for ($c = 0; $c < $countDados; $c++) {
        $arrData[$c] = array(
            'hd_chamado_cockpit'            => pg_fetch_result($resData, $c, hd_chamado_cockpit),
            'dados'                         => pg_fetch_result($resData, $c, dados),
            'dados2'                         => pg_fetch_result($resData, $c, dados2),
            'hd_chamado_cockpit_prioridade' => pg_fetch_result($resData, $c, hd_chamado_cockpit_prioridade),
            'motivo_erro'                   => pg_fetch_result($resData, $c, motivo_erro),
            'fabrica'                       => array(
                'fabrica' => pg_fetch_result($resData, $c, fabrica),
                'nome'    => pg_fetch_result($resData, $c, fabrica_nome),
                'cnpj'    => pg_fetch_result($resData, $c, fabrica_cnpj)
            ),
            'hd_chamado'                    => array(
                'hd_chamado'       => pg_fetch_result($resData, $c, hd_chamado),
                'admin'            => pg_fetch_result($resData, $c, admin),
                'posto'            => pg_fetch_result($resData, $c, posto),
                'data'             => array(
                    'date'          => pg_fetch_result($resData, $c, hcData),
                    'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                    'timezone'      => pg_fetch_result($resData, $c, timezone)
                ),
                'titulo'           => pg_fetch_result($resData, $c, titulo),
                'status'           => pg_fetch_result($resData, $c, status),
                'atendente'        => pg_fetch_result($resData, $c, atendente),
                'categoria'        => pg_fetch_result($resData, $c, categoria),
                'esta_agendado'    => pg_fetch_result($resData, $c, esta_agendado),
                'hd_chamado_extra' => array(
                    'serie'                   => pg_fetch_result($resData, $c, hceSerie),
                    'reclamado'               => pg_fetch_result($resData, $c, hceReclamado),
                    'data_abertura'           => pg_fetch_result($resData, $c, hceDataAbertura),
                    'nome'                    => pg_fetch_result($resData, $c, hceNonme),
                    'endereco'                => pg_fetch_result($resData, $c, hceEndereco),
                    'numero'                  => pg_fetch_result($resData, $c, hceNumero),
                    'complemento'             => pg_fetch_result($resData, $c, hceComplemento),
                    'bairro'                  => pg_fetch_result($resData, $c, hceBairro),
                    'cep'                     => pg_fetch_result($resData, $c, hceCep),
                    'fone'                    => pg_fetch_result($resData, $c, hceFone),
                    'fone2'                   => pg_fetch_result($resData, $c, hceFone2),
                    'sua_os'                  => pg_fetch_result($resData, $c, hceSuaOs),
                    'dias_aberto'             => pg_fetch_result($resData, $c, hceDiasAberto),
                    'dias_ultima_interacao'   => pg_fetch_result($resData, $c, hceDiasUltimaInteracao),
                    'receber_info_fabrica'    => pg_fetch_result($resData, $c, hceReceberInfoFabrica),
                    'origem'                  => pg_fetch_result($resData, $c, hceOrigem),
                    'consumidor_revenda'      => pg_fetch_result($resData, $c, hceConsumidorRevenda),
                    'email'                   => pg_fetch_result($resData, $c, hceEmail),
                    'cpf'                     => pg_fetch_result($resData, $c, hceCpf),
                    'abre_os'                 => pg_fetch_result($resData, $c, hceAbreOs),
                    'atendimento_callcenter'  => pg_fetch_result($resData, $c, hceAtendimentoCallcenter),
                    'tipo_atendimento'        => pg_fetch_result($resData, $c, hceTipoAtendimento),
                    'array_campos_adicionais' => pg_fetch_result($resData, $c, hceArrayCamposAdicionais),
                    'qtde_km'                 => pg_fetch_result($resData, $c, hceQtdeKm),
                    'consumidor_final_nome'   => pg_fetch_result($resData, $c, hceConsumidorFinalNome),
                    'hd_chamado'              => pg_fetch_result($resData, $c, hd_chamado),
                    'os'                      => array(
                        'os'            => pg_fetch_result($resData, $c, os),
                        'data_abertura' => array(
                            'date'          => pg_fetch_result($resData, $c, os_date),
                            'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                            'timezone'      => pg_fetch_result($resData, $c, timezone)
                        ),
                        'nota_fiscal'                 => pg_fetch_result($resData, $c, nota_fiscal),
                        'defeito_reclamado_descricao' => pg_fetch_result($resData, $c, defeito_reclamado_descricao),
                        'consumidor_nome'             => pg_fetch_result($resData, $c, consumidor_nome),
                        'key_code'                    => pg_fetch_result($resData, $c, key_code),
			'finalizada'                  => pg_fetch_result($resData, $c, finalizada),
			'tem_peca'                    => pg_fetch_result($resData, $c, tem_peca),
                        'os_extra'                    => array(
                            'obs_fechamento' => pg_fetch_result($resData, $c, obs_fechamento),
                            'os'             => pg_fetch_result($resData, $c, os)
                        ),
                        'status_os' => array(
                            'id'        => pg_fetch_result($resData, $c, status_checkpoint),
                            'descricao' => pg_fetch_result($resData, $c, scDescricao),
                            'cor'       => pg_fetch_result($resData, $c, cor)
                        ),
                        'tecnico_agenda' => array(
                            'tecnico_agenda'   => pg_fetch_result($resData, $c, tecnico_agenda),
                            'tecnico'          => pg_fetch_result($resData, $c, tecnico),
                            'taAdmin'          => pg_fetch_result($resData, $c, taAdmin),
                            'data_agendamento' => array(
                                'date'          => pg_fetch_result($resData, $c, data_agendamento),
                                'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                                'timezone'      => pg_fetch_result($resData, $c, timezone)
                            ),
                            'hora_inicio_trabalho' => pg_fetch_result($resData, $c, hora_inicio_trabalho),
                            'hora_fim_trabalho'    => pg_fetch_result($resData, $c, hora_fim_trabalho),
                            'ordem'                => pg_fetch_result($resData, $c, ordem),
                            'data_input'           => pg_fetch_result($resData, $c, data_input)
                        )
                    )
                )
            ),
            'routine_schedule_log' => array(
                'routine_schedule_log' => pg_fetch_result($resData, $c, routine_schedule_log),
                'routine_schedule'     => pg_fetch_result($resData, $c, routine_schedule),
                'date_start' => array(
                    'date'          => pg_fetch_result($resData, $c, date_start),
                    'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                    'timezone'      => pg_fetch_result($resData, $c, timezone)
                ),
                'date_finish' => array(
                    'date'          => pg_fetch_result($resData, $c, date_finish),
                    'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                    'timezone'      => pg_fetch_result($resData, $c, timezone)
                ),
                'file_name'              => pg_fetch_result($resData, $c, file_name),
                'total_line_file'        => pg_fetch_result($resData, $c, total_line_file),
                'total_record'           => pg_fetch_result($resData, $c, total_record),
                'total_record_processed' => pg_fetch_result($resData, $c, total_record_processed),
                'status'                 => pg_fetch_result($resData, $c, status),
                'status_message'         => pg_fetch_result($resData, $c, status_message),
                'tdocs'                  => pg_fetch_result($resData, $c, tdocs),
                'create_at' => array(
                    'date'          => pg_fetch_result($resData, $c, create_at),
                    'timezone_type' => pg_fetch_result($resData, $c, timezone_type),
                    'timezone'      => pg_fetch_result($resData, $c, timezone)
                )
            ),
            'familia' => array(
                'familia'        => pg_fetch_result($resData, $c, familia),
                'descricao'      => pg_fetch_result($resData, $c, descricao),
                'codigo_familia' => pg_fetch_result($resData, $c, codigo_familia),
                'ativo'          => pg_fetch_result($resData, $c, ativo),
                'external_id'    => pg_fetch_result($resData, $c, external_id)
            )
        );
    }

    return $arrData;

}

function linhaTabela($conteudo, $row, $ajax = false) {
    global $distribuidor_unidade_negocio, $distribuidores, $priorities, $tipos_atendimentos, $unidades_negocio, $login_fabrica, $con;

    //$row["dados"] = json_decode(utf8_encode($row["dados"]), true);
    $row["dados"] = json_decode(utf8_encode(stripslashes($row["dados"])), true);

    if(json_last_error() > 0) {
        $row["dados"] = json_decode(utf8_encode(stripslashes($row["dados2"])), true);
    }

    if(strlen(trim($row['dados']['defeito']))==0){

        $sqlD = "SELECT
                    DISTINCT tbl_defeito_reclamado.defeito_reclamado,
                    tbl_defeito_reclamado.codigo,
                    tbl_defeito_reclamado.descricao
                FROM tbl_diagnostico
                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} AND tbl_defeito_reclamado.ativo IS TRUE
                INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                AND tbl_produto.referencia = '". $row['dados']['modeloKof']."'
                and tbl_defeito_reclamado.codigo = '".$row['dados']['codDefeito']."'
                ORDER BY tbl_defeito_reclamado.codigo ASC, tbl_defeito_reclamado.descricao ASC "; 
        $resD = pg_query($con, $sqlD); 

        if(pg_num_rows($resD) > 0){
            $descricao = pg_fetch_result($resD, 0, 'descricao');
             $row["dados"]["defeito"] = $descricao; 
        }else{
            $row["dados"]["defeito"] = utf8_encode("Não encontrado");
            $row["dados"]["codDefeito"] = "";
        }
    }

    $row['dados']['cidadeCliente'] = (mb_detect_encoding($row['dados']['cidadeCliente']) == "UTF-8") ? utf8_decode($row['dados']['cidadeCliente']) : $row['dados']['cidadeCliente'];

    list($data, $hora)     = explode(" ", $row["routine_schedule_log"]["create_at"]["date"]);
    list($ano, $mes, $dia) = explode("-", $data);
    $abertura_telecontrol  = "{$dia}/{$mes}/{$ano} {$hora}";

    if ($row["hd_chamado"]["hd_chamado_extra"]["array_campos_adicionais"]) {
        $array_campos_adicionais = json_decode($row["hd_chamado"]["hd_chamado_extra"]["array_campos_adicionais"], true);

        $unidade_negocio = $unidades_negocio[$array_campos_adicionais["unidadeNegocio"]];
    } else {
        $unidade_negocio = $distribuidor_unidade_negocio[$row["dados"]["centroDistribuidor"]];
    }

    if ($row["hd_chamado"]["hd_chamado_extra"]["os"]["tem_peca"] == 't') {
	$btn_editar = "
	    <button type='button' class='btn btn-small btn-info btn-edit-ticket disabled'><i class='icon-edit icon-white'></i> Editar</button>
	";
    } else {
	$btn_editar = "
	    <button 
		type='button' 
		class='btn btn-small btn-info btn-edit-ticket' 
		data-ticket='".$row['hd_chamado_cockpit']."'
		data-os-kof='".$row['dados']['osKof']."'
		data-os-telecontrol='".$row['hd_chamado']['hd_chamado_extra']['os']['os']."'
		data-telecontrol-protocol='".$row['hd_chamado']['hd_chamado']."'
		data-priority='".$row['hd_chamado_cockpit_prioridade']."' 
		data-scheduled='".((!empty($row['hd_chamado']['hd_chamado_extra']['os']['tecnico_agenda']['tecnico_agenda'])) ? 'true' : 'false')."'
		data-client-name='".$row['dados']['nomeFantasia']."' >
		<i class='icon-edit icon-white' ></i> Editar
	    </button>
	";
    }

    if ($conteudo == "nao_processados") {       

        $linha = "
            <tr>
                <td>".$row["dados"]["protocoloKof"]."</td>
                <td>".$row["dados"]["osKof"]."</td>
                <td style='background-color: #".$priorities[$row["hd_chamado_cockpit_prioridade"]]["color"]." !important;' >".$priorities[$row["hd_chamado_cockpit_prioridade"]]["description"]."</td>
                <td>".$row["dados"]["idCliente"]." - ".utf8_decode($row["dados"]["nomeFantasia"])."</td>
                <td>".$row["dados"]["cidadeCliente"]." / ".$row["dados"]["estadoCliente"]."</td>
                <td>".$row["dados"]["centroDistribuidor"]." - ".$distribuidores[$row["dados"]["centroDistribuidor"]]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row["dados"]["descricaoTipo"])."</td>
                <td>".$tipos_atendimentos[$row["dados"]["tipoOrdem"]]."</td>
                <td>".$row["familia"]["descricao"]."</td>
                <td nowrap>".$row["dados"]["codDefeito"]." - ".utf8_decode($row["dados"]["defeito"])."</td>
                <td>".$row["dados"]["dataAbertura"]."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row["routine_schedule_log"]["file_name"]."' target='_blank' >".$row["routine_schedule_log"]["file_name"]."</a></td>
            </tr>
        ";
    } else if ($conteudo == "com_erros") {
        $linha = "
            <tr>
                <td>".$row['dados']['protocoloKof']."</td>
                <td>".$row['dados']['osKof']."</td>
                <td style='background-color: #".$priorities[$row['hd_chamado_cockpit_prioridade']]['color']." !important;' >".$priorities[$row['hd_chamado_cockpit_prioridade']]['description']."</td>
                <td>".$row['dados']['idCliente']." - ".utf8_decode($row['dados']['nomeFantasia'])."</td>
                <td>". $row['dados']['cidadeCliente'] ." / ".$row['dados']['estadoCliente']."</td>
                <td>".$row['dados']['centroDistribuidor']." - ".$distribuidores[$row['dados']['centroDistribuidor']]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row['dados']['descricaoTipo'])."</td>
                <td>".$tipos_atendimentos[$row['dados']['tipoOrdem']]."</td>
                <td>".$row['familia']['descricao']."</td>
                <td nowrap>".$row['dados']['codDefeito']." - ".utf8_decode($row['dados']['defeito'])."</td>
                <td>".$row['dados']['dataAbertura']."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row['routine_schedule_log']['file_name']."' target='_blank' >".$row['routine_schedule_log']['file_name']."</a></td>
                <td>".utf8_decode($row['motivo_erro'])."</td>
		<td nowrap >
		".$btn_editar."
                </td>
            </tr>
        ";
    } else if ($conteudo == "erro_mobile") {
        $linha = "
            <tr>
                <td>".$row["dados"]["protocoloKof"]."</td>
                <td>".$row["dados"]["osKof"]."</td>
                <td style='background-color: #".$priorities[$row['hd_chamado_cockpit_prioridade']]['color']." !important;' >".$priorities[$row["hd_chamado_cockpit_prioridade"]]["description"]."</td>
                <td>".$row["dados"]["idCliente"]." - ".utf8_decode($row["dados"]["nomeFantasia"])."</td>
                <td>".$row["dados"]["cidadeCliente"]." / ".$row["dados"]["estadoCliente"]."</td>
                <td>".$row["dados"]["centroDistribuidor"]." - ".$distribuidores[$row["dados"]["centroDistribuidor"]]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row["dados"]["descricaoTipo"])."</td>
                <td>".$tipos_atendimentos[$row["dados"]["tipoOrdem"]]."</td>
                <td>".$row["familia"]["descricao"]."</td>
                <td nowrap>".$row["dados"]["codDefeito"]." - ".utf8_decode($row["dados"]["defeito"])."</td>
                <td>".$row["dados"]["dataAbertura"]."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row['routine_schedule_log']['file_name']."' target='_blank' >".$row["routine_schedule_log"]["file_name"]."</a></td>
                <td><a href='os_press.php?os=".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' target='_blank' >".$row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]."</a></td>
                <td>".utf8_decode($row["hd_chamado"]["hd_chamado_extra"]["os"]["status_os"]["descricao"])."</td>
                <td>".utf8_decode($row["motivo_erro"])."</td>
		<td nowrap >
		    ".$btn_editar."
                    <button 
                        type='button' 
                        class='btn btn-small btn-info btn-send-email'                             
                        data-os-telecontrol='".$row['hd_chamado']['hd_chamado_extra']['os']['os']."'>
                        <i class='icon-envelope icon-white' ></i> Enviar OS via Email
                    </button>
                </td>
            </tr>
        ";
    } else if ($conteudo == "nao_agendados") {
        /*$linha = "
            <tr>
                <td>".$row['dados']['protocoloKof']."</td>
                <td>".$row['dados']['osKof']."</td>
                <td style='background-color: #".$priorities[$row['hd_chamado_cockpit_prioridade']]['color']." !important;' >".$priorities[$row['hd_chamado_cockpit_prioridade']]['description']."</td>
                <td>".$row['dados']['idCliente']." - ".utf8_decode($row['dados']['nomeFantasia'])."</td>
                <td>".$row['dados']['cidadeCliente']." / ".$row['dados']['estadoCliente']."</td>
                <td>".$row['dados']['centroDistribuidor']." - ".$distribuidores[$row['dados']['centroDistribuidor']]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row['dados']['descricaoTipo'])."</td>
                <td>".$tipos_atendimentos[$row['dados']['tipoOrdem']]."</td>
                <td>".$row['familia']['descricao']."</td>
                <td>".$row['dados']['codDefeito']." - ".utf8_decode($row['dados']['defeito'])."</td>
                <td>".$row['dados']['dataAbertura']."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row['routine_schedule_log']['file_name']."' target='_blank' >".$row['routine_schedule_log']['file_name']."</a></td>
                <td><a href='os_press.php?os=".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' target='_blank' >".$row['hd_chamado']['hd_chamado_extra']['os']['os']."</a></td>
                <td>".utf8_decode($row['hd_chamado']['hd_chamado_extra']['os']['status_os']['descricao'])."</td>
		<td nowrap >
		    ".$btn_editar."
                </td>
            </tr>
        ";*/
    } else if ($conteudo == "agendados") {
        $linha = "
            <tr>
                <td>".$row['dados']['protocoloKof']."</td>
                <td>".$row['dados']['osKof']."</td>
                <td style='background-color: #".$priorities[$row['hd_chamado_cockpit_prioridade']]['color']." !important;' >".$priorities[$row['hd_chamado_cockpit_prioridade']]['description']."</td>
                <td>".$row['dados']['idCliente']." - ".utf8_decode($row['dados']['nomeFantasia'])."</td>
                <td>".$row['dados']['cidadeCliente']." / ".$row['dados']['estadoCliente']."</td>
                <td>".$row['dados']['centroDistribuidor']." - ".$distribuidores[$row['dados']['centroDistribuidor']]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row['dados']['descricaoTipo'])."</td>
                <td>".$tipos_atendimentos[$row['dados']['tipoOrdem']]."</td>
                <td>".$row['familia']['descricao']."</td>
                <td nowrap>".$row['dados']['codDefeito']." - ".utf8_decode($row['dados']['defeito'])."</td>
                <td>".$row['dados']['dataAbertura']."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row['routine_schedule_log']['file_name']."' target='_blank' >".$row['routine_schedule_log']['file_name']."</a></td>
                <td><a href='os_press.php?os=".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' target='_blank' >".$row['hd_chamado']['hd_chamado_extra']['os']['os']."</a></td>
                <td>".utf8_decode($row['hd_chamado']['hd_chamado_extra']['os']['status_os']['descricao'])."</td>
		<td nowrap >
		    ".$btn_editar."
                    <button 
                        type='button' 
                        class='btn btn-small btn-info btn-send-email'                             
                        data-os-telecontrol='".$row['hd_chamado']['hd_chamado_extra']['os']['os']."'>
                        <i class='icon-envelope icon-white' ></i> Enviar OS via Email
                    </button>
                </td>
            </tr>
        ";
    } else if ($conteudo == "erro_fechamento") {
        $linha = "
            <tr>
                <td>".$row['dados']['protocoloKof']."</td>
                <td>".$row['dados']['osKof']."</td>
                <td style='background-color: #".$priorities[$row['hd_chamado_cockpit_prioridade']]['color']." !important;' >".$priorities[$row['hd_chamado_cockpit_prioridade']]['description']."</td>
                <td>".$row['dados']['idCliente']." - ".utf8_decode($row['dados']['nomeFantasia'])."</td>
                <td>".$row['dados']['cidadeCliente']." / ".$row['dados']['estadoCliente']."</td>
                <td>".$row['dados']['centroDistribuidor']." - ".$distribuidores[$row['dados']['centroDistribuidor']]." / ".$unidade_negocio."</td>
                <td>".utf8_decode($row['dados']['descricaoTipo'])."</td>
                <td>".$tipos_atendimentos[$row['dados']['tipoOrdem']]."</td>
                <td>".$row['familia']['descricao']."</td>
                <td nowrap>".$row['dados']['codDefeito']." - ".utf8_decode($row['dados']['defeito'])."</td>
                <td>".$row['dados']['dataAbertura']."</td>
                <td>".$abertura_telecontrol."</td>
                <td><a href='imbera/processado/".$row['routine_schedule_log']['file_name']."' target='_blank' >".$row['routine_schedule_log']['file_name']."</a></td>
                <td><a href='os_press.php?os=".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' target='_blank' >".$row['hd_chamado']['hd_chamado_extra']['os']['os']."</a></td>
                <td>".utf8_decode($row['hd_chamado']['hd_chamado_extra']['os']['status_os']['descricao'])."</td>
                <td>".utf8_decode($row['hd_chamado']['hd_chamado_extra']['os']['os_extra']['obs_fechamento'])."</td>
                <td nowrap >
                    <a href='cadastro_os.php?os_id=".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' target='_blank' >
                        <button type='button' class='btn btn-small btn-info' >
                            <i class='icon-edit icon-white' ></i> Alterar OS
                        </button>
                    </a>
                    <button type='button' data-os-telecontrol='".$row['hd_chamado']['hd_chamado_extra']['os']['os']."' class='btn btn-small btn-success btn-close-service-order' >
                        <i class='icon-check icon-white' ></i> Finalizar OS
                    </button>
                </td>
            </tr>
        ";
    }

    if ($ajax == true) {
        $linha = utf8_encode(preg_replace("/\n|\s{4}/", "", $linha));
    }

    return $linha;
}

/**
 * Area para colocar os AJAX
 */
if (array_key_exists("ajax_refresh_content", $_POST)) {
    try {
        $content = $_POST["content"];

        $token   = generateToken($applicationKey);
        $arrData = getDataNew();

        $tickets = array();

        array_map(function($row) {
            global $tickets, $content;

            if ($content == "nao_processados") {
                if (empty($row["motivo_erro"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && empty($row["hd_chamado"]["hd_chamado"])) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }

            if ($content == "com_erros") {
                if (!empty($row["motivo_erro"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"])) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }

            if ($content == "erro_mobile") {
                if (!empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }

            if ($content == "nao_agendados") {
                if ((!empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) || (empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado"]) && empty($row["motivo_erro"]))) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }

            if ($content == "erro_fechamento") {
                if (empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os_extra"]["obs_fechamento"])) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }

            if ($content == "agendados") {
                if (trim($row["familia"]["descricao"]) <> "POST MIX" && trim($row["familia"]["descricao"]) <> "CHOPEIRA" && empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) {
                    $tickets[] = linhaTabela($content, $row, true);
                    return true;
                }
            }
        }, $arrData);

        exit(json_encode($tickets));
    } catch(Exception $e) {
        exit(json_encode(array("error" => $e->getMessage())));
    }
}

if(array_key_exists("email_os", $_POST)){

    $os = $_POST['os'];

    $sql = "SELECT tbl_os.os, tbl_os.data_abertura, tbl_os.consumidor_nome, tbl_os.obs, tbl_os.consumidor_endereco, 
                    tbl_os.consumidor_numero, tbl_os.consumidor_complemento, tbl_os.consumidor_bairro, tbl_os.consumidor_cidade, 
                    tbl_os.consumidor_estado, tbl_os.consumidor_cep, tbl_os.consumidor_fone, tbl_os.consumidor_email, tbl_os.consumidor_celular,
                    tbl_tecnico_agenda.data_agendamento, 
                    tbl_hd_chamado_cockpit.dados, 
                    tbl_defeito_reclamado.descricao as defeito_reclamado, 
                    tbl_produto.referencia, tbl_produto.descricao, tbl_produto.voltagem,
                    tbl_os_produto.serie, 
                    tbl_os_extra.serie_justificativa,
                    tbl_posto_fabrica.contato_email
                    FROM tbl_os
                    LEFT JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                    LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                    LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
                    LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
                    LEFT JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os
                    LEFT JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_os.hd_chamado
                    LEFT JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_os.os = $os AND tbl_os.fabrica = $login_fabrica";
            

    try{
        $classOs = new \Posvenda\Os($login_fabrica, $os);
        $pdo     = $classOs->_model->getPDO();
        $query = $pdo->query($sql);
        if(!$query || $query->rowCount() == 0){
            throw new Exception("Erro ao buscar OS");        
        }


        $result = $query->fetch();

        $result['dados'] = json_decode($result['dados'],true);


        $body = "";

        $result['os']? $body.= "Número da OS: ".$result['os']."\n<br>":"";
        $result['data_abertura']? $body.= "Data de Abertura: ".$result['data_abertura']."\n<br>": "";
        $result['data_agendamento']? $body.= "Data de Agendamento: ".$result['data_agendamento']."\n<br>": "";
        $result['consumidor_nome']? $body.= "Nome do Cliente: ".utf8_decode($result['consumidor_nome'])."\n<br>": "";
        $result['dados']['nomeFantasia']? $body.= "Nome Fantasia: ".utf8_decode($result['dados']['nomeFantasia'])."\n<br>": "";

        $result['consumidor_endereco']? $endereco[]= utf8_decode($result['consumidor_endereco']): "";
        $result['consumidor_numero']? $endereco[] = $result['consumidor_numero']: "";
        $result['consumidor_complemento']? $endereco[] = utf8_decode($result['consumidor_complemento']): "";
        $result['consumidor_bairro']? $endereco[] = utf8_decode($result['consumidor_bairro']): "";
        $result['consumidor_cidade']? $endereco[] = utf8_decode($result['consumidor_cidade']): "";
        $result['consumidor_estado']? $endereco[] = utf8_decode($result['consumidor_estado']): "";
        $result['consumidor_cep']? $endereco[] = $result['consumidor_cep']: "";

        if(count($endereco)>0){
            $body .= "Endereço do Cliente: ".implode(", ", $endereco)."\n<br>";
        }

        $result['consumidor_email']? $body.= "Consumidor Email: ".$result['consumidor_email']."\n<br>":"";
        $result['consumidor_celular']? $body.= "Consumidor Celular: ". $result['consumidor_celular']."\n<br>":"";

        $result['defeito_reclamado']? $body.= "Defeito Reclamado: ".($result['defeito_reclamado'])."\n<br>":"";
        $result['referencia']? $body.= "Produto: ".$result['referencia']." - ".$result['descricao']." - ".$result['voltagem']."\n<br>":"";
        $result['serie']? $body.= "Série: ".$result['serie']."\n<br>":"";
        $result['serie_justificativa']? $body.= "Patrimônio: ".$result['serie_justificativa']."\n<br>":"";
        $result['obs']? $body.= "Observação: ".($result['obs'])."\n<br>":"";

        $communicator = new TcComm("noreply@tc");
        $communicator->addEmailDest($result['contato_email']);
        $communicator->setEmailFrom("noreply@telecontrol.com.br");
        $communicator->setEmailSubject("Chamado da OS #".$os);
        $communicator->setEmailBody($body);
        $email = $communicator->sendMail();
        if($email == true){
            echo json_encode(array("email"=>"ok"));
        }else{
            echo json_encode(array("exception" => "Não foi possivel enviar o email no momemto"));    
        }
        
    }catch(\Exception $e){
        echo json_encode(array("exception" => "Não foi possivel enviar o email no momemto","systemException" => $e->getMessage()));
    }


    exit;
}

if ($_GET["ajax_close_service_order"]) {
    date_default_timezone_set("America/Sao_Paulo");

    $os_telecontrol = $_GET["os_telecontrol"];

    try {
        $classOs = new \Posvenda\Fabricas\_158\Os($login_fabrica, $os_telecontrol);
        $pdo     = $classOs->_model->getPDO();

        $sql = "
            SELECT os_mobile
            FROM tbl_os_mobile
            WHERE fabrica = {$login_fabrica}
            AND os = {$os_telecontrol}
            AND conferido IS NOT TRUE
        ";
        $query = $pdo->query($sql);

        if ($query->rowCount() > 0) {
            throw new Exception("Erro ao finalizar OS, a OS {$os_telecontrol} possui registros de integração Mobile x Web que ainda não foram conferidos, por favor corrija a situação na tela CCT-0240 e tente finalizar novamente");
        }

        $sql = "
            SELECT tbl_tipo_posto.tecnico_proprio AS internal_technical
            FROM tbl_os 
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            WHERE tbl_os.fabrica = {$login_fabrica} 
            AND tbl_os.os = {$os_telecontrol}
        ";
        $query = $pdo->query($sql);

        if (!$query || $query->rowCount() == 0) {
            throw new Exception("Erro ao finalizar OS, OS não encontrada");
        }

        $result = $query->fetch();

        $internal_technical = $result["internal_technical"];

        $sql = "
            SELECT hora_inicio_trabalho AS start_date, hora_fim_trabalho AS end_date
            FROM tbl_tecnico_agenda
            WHERE fabrica = {$login_fabrica}
            AND os = {$os_telecontrol}
        ";
        $query = $pdo->query($sql);

        if (!$query || $query->rowCount() == 0) {
            throw new Exception("Erro ao finalizar OS, não foi possível buscar o agendamento");
        }

        $result = $query->fetch();

        $start_date = $result["start_date"];

        if (empty($result["end_date"])) {
            $sql = "
                SELECT dados
                FROM tbl_os_mobile
                WHERE fabrica = {$login_fabrica}
                AND os = {$os_telecontrol}
                AND status_os_mobile = 'PS5'
                ORDER BY data_input DESC
                LIMIT 1
            ";
            $query = $pdo->query($sql);

            $result = $query->fetch();

            $dados = json_decode($result["dados"], true);

            $end_date = date("Y-m-d H:i", $dados["status"]["dataAlteracao"] / 1000);
        } else {
            $end_date = $result["end_date"];
        }

        $pdo->beginTransaction();

        $sql = "
            UPDATE tbl_os_extra SET
                inicio_atendimento = '{$start_date}',
                termino_atendimento = '{$end_date}'
            WHERE os = {$os_telecontrol}
        ";
        $query = $pdo->query($sql);

        if (!$query) {
            throw new Exception("Erro ao finalizar a OS, não foi possível atualizar início e fim de trabalho");
        }

        $sql = "
            UPDATE tbl_os SET
                data_conserto = '{$end_date}'
            WHERE fabrica = {$login_fabrica}
            AND os = {$os_telecontrol}
        ";
        $query = $pdo->query($sql);

        if (!$query) {
            throw new Exception("Erro ao finalizar a OS, não foi possível atualizar a data de conserto");
        }

        if ($internal_technical != true) {
            $classOs->calculaOs();
        }

        $classOs->finaliza($con);

        $atendimento_callcenter = $classOs->verificaAtendimentoCallcenter($os_telecontrol);

        if ($atendimento_callcenter) {
            $classOs->finalizaAtendimento($atendimento_callcenter);
        }

        $cockpit = new \Posvenda\Cockpit($login_fabrica);

    	if ($internal_technical == true) {
    	    $id_externo = $cockpit->getOsIdExterno($os_telecontrol);

    	    $finalizou_mobile = $cockpit->finalizaOsMobile($id_externo);

    	    if (empty($finalizou_mobile) || $finalizou_mobile["error"]) {
    	        throw new Exception("Erro ao finalizar OS no dispostivo móvel");
    	    }
    	}

    	$sql = "
    		SELECT os, fora_garantia 
    		FROM tbl_os 
    		JOIN tbl_tipo_atendimento USING(tipo_atendimento,fabrica) 
    		WHERE fabrica = {$login_fabrica} AND os = {$os_telecontrol}
    	";
    	$query = $pdo->query($sql);
    	$res = $query->fetch();

    	$tipo_atendimento_fora_garantia = $res['fora_garantia'];

    	$oPedido            = new \Posvenda\Pedido($login_fabrica);
        $oExportaPedido     = new \Posvenda\Fabricas\_158\ExportaPedido($oPedido, $classOs, $login_fabrica);
        $oPedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($oPedido);

	    $pedido = $oExportaPedido->getPedido($os);

        $garantia_antecipada = $pedido[0]['garantia_antecipada'];
        $pedido_em_garantia = $pedido[0]['pedido_em_garantia'];

	    if ($garantia_antecipada != 't' && $pedido_em_garantia == 't' && $tipo_atendimento_fora_garantia == "t") {
            $pedido = $oPedidoBonificacao->organizaEstoque($pedido, true);

	    if (strtotime("today") > strtotime("2017-11-30 00:00:00")) {
	            $oExportaPedido->pedidoIntegracaoSemDeposito($pedido);
	    } else {
	            if ($oExportaPedido->pedidoIntegracao($pedido,"cobranca_kof", true) === false) {
                	throw new \Exception("Pedido não foi enviado para o SAP");
		    }
            }

        }

        $sql = "
            INSERT INTO tbl_os_interacao
            (os, data, admin, comentario, fabrica)
            VALUES
            ({$os_telecontrol}, CURRENT_TIMESTAMP, {$login_admin}, 'OS finalizada', {$login_fabrica})
        ";
        $query = $pdo->query($sql);

        $pdo->commit();

        exit(json_encode(array("success" => true)));
    } catch(Exception $e) {

       $pdo->rollBack();

        exit(json_encode(array("error" => utf8_encode($e->getMessage()))));
    }
}

/**
 * Também é usado pelo admin/cockpit/ticket_conference.js
 */
if (isset($_GET["ajax_busca_cidade"])) { 
    $estado = strtoupper($_GET["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }

    exit(json_encode($retorno));
}

/**
 * Também é usado pelo admin/cockpit/ticket_conference.js
 */
if(isset($_GET["defeito_reclamado"])){
    $referencia       = $_GET["referencia"];
    $tipo_atendimento = $_GET["tipo_atendimento"];

    if ($tipo_atendimento == "ZKR6") {
        $sql = "
            SELECT defeito_reclamado, codigo, descricao
            FROM tbl_defeito_reclamado
            WHERE fabrica = {$login_fabrica}
            AND codigo = 'SA'
        ";
    } else {
        $sql = "SELECT
                    DISTINCT tbl_defeito_reclamado.defeito_reclamado,
                    tbl_defeito_reclamado.codigo,
                    tbl_defeito_reclamado.descricao
                FROM tbl_diagnostico
                INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado AND tbl_defeito_reclamado.fabrica = {$login_fabrica} AND tbl_defeito_reclamado.ativo IS TRUE
                INNER JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                INNER JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                AND tbl_produto.referencia = '{$referencia}'
                ORDER BY tbl_defeito_reclamado.codigo ASC, tbl_defeito_reclamado.descricao ASC";
    }

    $res = pg_query($con, $sql);

    $defeitos_reclamados = array();

    if (pg_num_rows($res) > 0){
        for($i = 0; $i < pg_num_rows($res); $i++){
            $defeito_reclamado = pg_fetch_result($res, $i, defeito_reclamado);
            $codigo = pg_fetch_result($res, $i, codigo);

            if (in_array($login_fabrica, array(158))) {
                $descricao = $codigo." - ".pg_fetch_result($res, $i, descricao);
            } else {
                $descricao = pg_fetch_result($res, $i, descricao);
            }

            $defeitos_reclamados[$i] = array("defeito_reclamado" => $defeito_reclamado, "codigo" => $codigo, "descricao" => utf8_encode($descricao));
        }
    }else{
        $defeitos_reclamados[0] = array("defeito_reclamado" => "", "codigo" => "", "descricao" => "");
    }

    exit(json_encode(array("defeitos_reclamados" => $defeitos_reclamados)));
}

// try{
//     $token   = generateToken($applicationKey);
//     $arrData = getData($applicationKey, $token, '/fabrica/'.$login_fabrica);
// }catch(Exception $ex){
//     $msg_erro["msg"][] = $ex->getMessage();
// }

$arrData = getDataNew();

$title = "Monitor de Pré Ordens de Serviço";
$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "select2"
);

include __DIR__.'/plugin_loader.php';

?>

<style>
    
table.table-large, div.dataTables_wrapper {
    width: 1920px !important;
    max-width: 1920px !important;
}

div.div_date_filter_type {
    visibility: hidden;
}

th.toggle-table-content {
    cursor: pointer;
}

.action-table-content {
    margin-right: 10px;
    cursor: pointer;
}

.title-table-content {
    margin-left: 20px;
    cursor: pointer;
}

.icon-refresh {
    transform: rotate(0deg);
}

.icon-refresh-animate {
    transform: rotate(360deg);
    transition: transform 1s linear;
}

.table-content {
    display: none;
}

</style>

<div id="msg-alert" style="display:none;" class="alert alert-warning alert-dismissible fade in" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? } ?>

<form name='frm_relatorio' method='POST' action='<?= $PHP_SELF; ?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group">
                <label class="control-label" >&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type="checkbox" id="date_filter" />
                        Data
                    </label>
                </div>
            </div>
        </div>
        <div class="span3 div_date_filter_type" >
            <div class="control-group" >
                <div class="controls controls-row" >
                    <label class="control-label" for="osKof">Tipo</label>
                    <div class="controls controls-row">
                        <select id="date_filter_type" class="span12" >
                            <option value="kof" >Solic. do Cliente </option>
                            <option value="telecontrol" >Integração Telecontrol</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span2 div_date_filter_type" >
            <div class="control-group" >
                <div class="controls controls-row" >
                    <label class="control-label" for="init_date">Data Inicial</label>
                    <div class="controls controls-row">
                        <input type="text" class="date span12" id="init_date" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2 div_date_filter_type" >
            <div class="control-group" >
                <div class="controls controls-row" >
                    <label class="control-label" for="end_date">Data Final</label>
                    <div class="controls controls-row">
                        <input type="text" class="date span12" id="end_date" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="osKof">OS Cliente</label>
                <div class="controls controls-row">
                    <input type="text" id="osKof" class="span12" />
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="protocoloKof">Protocolo Cliente</label>
                <div class="controls controls-row">
                    <input type="text" id="protocoloKof" class="span12" />
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="osTelecontrol">OS Telecontrol</label>
                <div class="controls controls-row">
                    <input type="text" id="osTelecontrol" class="span12" />
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span2">
            <div class="control-group">
                <label class="control-label" for="prioridades">Prioridades</label>
                <div class="controls controls-row">
                    <select id="prioridades" class="span12 select-clear" >
                        <option value="" >Todas</option>
                        <option value="Baixa" >Baixal</option>
                        <option value="Baixa KA" >Baixa KA</option>
                        <option value="Normal" >Normal</option>
                        <option value="Normal KA" >Normal KA</option>
                        <option value="Alta" >Alta</option>
                        <option value="Alta KA" >Alta KA</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="tipo_atendimento">Tipo de Atendimento</label>
                <div class="controls controls-row">
                    <select id="tipo_atendimento" class="span12 select-clear" >
                        <option value="" >Todos</option>
                        <?php
                        foreach ($tipos_atendimentos as $tipo_atendimento) {
                        ?>
                            <option value="<?=$tipo_atendimento?>" ><?=$tipo_atendimento?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group">
                <label class="control-label" for="familia">Família</label>
                <div class="controls controls-row">
                    <select id="familia" class="span12 select-clear" >
                        <option value="" >Todas</option>
                        <?php
                        foreach ($familias as $i => $descricao) {
                        ?>
                            <option value="<?=$descricao?>" ><?=$descricao?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="defeito">Defeito</label>
                <div class="controls controls-row">
                    <select id="defeito" class="span12 select-clear" >
                        <option value="" >Todos</option>
                        <?php
                        foreach ($defeitos as $codigo => $descricao) {
                        ?>
                            <option value="<?=$codigo." - ".$descricao?>" ><?=$codigo." - ".$descricao?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="cliente">Cliente (código ou nome fantasia)</label>
                <div class="controls controls-row">
                    <input type="text" id="cliente" class="span12" />
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group">
                <label class="control-label" for="estado" >Estado</label>
                <div class="controls controls-row" >
                    <div class="span12">
                        <select id="estado" class="span12 select-clear" >
                            <option value="" >Todos</option>
                            <?php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                            ?>
                                <option value="<?=$sigla?>" ><?=utf8_decode($nome_estado)?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="span5">
            <div class="control-group">
                <label class="control-label" for="cidade" >Cidade</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="cidade" name="cidade[]" class="span12 select-clear" >
                            <option value="" >Todas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span1"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="distribuidor">Distribuidor</label>
                <div class="controls controls-row">
                    <select id="distribuidor" class="span12 select-clear" >
                        <option value="" >Todos</option>
                        <?php
                        foreach($distribuidores as $codigo => $descricao) {
                        ?>
                            <option value="<?=$codigo." - ".$descricao?>" ><?=$codigo." - ".$descricao?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="unidade_negocio">Unidade de Negócio</label>
                <div class="controls controls-row">
                    <select id="unidade_negocio" class="span12 select-clear" >
                        <option value="" >Todos</option>
                        <?php
                        foreach($unidades_negocio as $codigo => $cidade) {
                        ?>
                            <option value="<?=$cidade?>" ><?=$cidade?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="span3">
            <div class="control-group">
                <label class="control-label" for="status_os">Status OS</label>
                <div class="controls controls-row">
                    <select id="status_os" class="span12 select-clear" >
                        <option value="" >Todos</option>
                        <?php
                        $sql = "
                            SELECT descricao 
                            FROM tbl_status_checkpoint
                            WHERE status_checkpoint IN(1, 2, 3, 23, 24, 25, 26, 27)
                            ORDER BY descricao ASC
                        ";
                        $qry = pg_query($con, $sql);

                        while ($status = pg_fetch_object($qry)) {
                        ?>
                            <option value="<?=$status->descricao?>" ><?=$status->descricao?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <br />

    <div class="row-fluid tac">
        <button type="button" class="btn btn-filter" >Pesquisar</button>

        <button type="button" class="btn btn-warning btn-clear-filter" >Limpar</button>
    </div>
</form>

<table class="table table-striped table-bordered table-fixed" style="table-layout: fixed;" >
    <thead>
        <tr>
            <th class="titulo_coluna" colspan="<?=count($priorities)?>" >Prioridades</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php
            foreach ($priorities as $priority) {
                echo "<td class='status' style='background-color: #{$priority["color"]}' >{$priority["description"]}</td>";
            }
            ?>
        </tr>
    </tbody>
</table>

<br />

</div>

<?php

$tickets_nao_processados = array();
$tickets_com_erros       = array();
$tickets_nao_agendados   = array();
$tickets_agendados       = array();
$tickets_erro_mobile     = array();
$tickets_erro_fechamento = array();

array_map(function($row) {
    global $tickets_nao_processados, $tickets_com_erros, $tickets_nao_agendados, $tickets_agendados, $tickets_erro_mobile, $tickets_erro_fechamento;

    if (empty($row["motivo_erro"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && empty($row["hd_chamado"]["hd_chamado"])) {
        $tickets_nao_processados[] = $row;
        return true;
    }

    if (trim($row["familia"]["descricao"]) <> "POST MIX" && trim($row["familia"]["descricao"]) <> "CHOPEIRA" && !empty($row["motivo_erro"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"])) {
        $tickets_com_erros[] = $row;
        return true;
    }

    if (!empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) {
        $tickets_erro_mobile[] = $row;
        return true;
    }

    if ((!empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) || (empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado"]))) {
        $tickets_nao_agendados[] = $row;
        return true;
    }

    if (empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os_extra"]["obs_fechamento"])) {
        $tickets_erro_fechamento[] = $row;
        return true;
    }

    if (trim($row["familia"]["descricao"]) <> "POST MIX" && trim($row["familia"]["descricao"]) <> "CHOPEIRA" && empty($row["motivo_erro"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["os"]) && !empty($row["hd_chamado"]["hd_chamado_extra"]["os"]["tecnico_agenda"]["tecnico_agenda"])) {
        $tickets_agendados[] = $row;
        return true;
    }
}, $arrData);

?>
<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #337AB7; color: #FFFFFF;" colspan="13" >
            <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
            <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="nao_processados" title="Atualizar conteúdo" ></i>
            <span class="toggle-table-content pull-left title-table-content" >Chamados não processados</span>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        if (count($tickets_nao_processados) > 0) {
            foreach ($tickets_nao_processados as $row) {
                echo linhaTabela("nao_processados", $row);
            }
        }
        ?>
    </tbody>
</table>

<hr />

<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #DA4F49; color: #FFFFFF;" colspan="15" >
                <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
                <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="com_erros" title="Atualizar conteúdo" ></i>
                <span class="toggle-table-content pull-left title-table-content" >Chamados com erros</span>
            </th>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
            <th>Erro</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        if (count($tickets_com_erros) > 0) {
            foreach ($tickets_com_erros as $row) {
                echo linhaTabela("com_erros", $row);
            }
        }
        ?>
    </tbody>
</table>

<hr />

<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #DA4F49; color: #FFFFFF;" colspan="17" >
                <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
                <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="erro_mobile" title="Atualizar conteúdo" ></i>
                <span class="toggle-table-content pull-left title-table-content" >Erro ao enviar Ordem de Serviço para o Dispositivo Móvel</span>
            </th>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
            <th>OS</th>
            <th>Status</th>
            <th>Erro</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        if (count($tickets_erro_mobile) > 0) {
            foreach ($tickets_erro_mobile as $row) {
                echo linhaTabela("erro_mobile", $row);
            }
        }
        ?>
    </tbody>
</table>

<hr />

<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #FAA732; color: #FFFFFF;" colspan="16" >
                <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
                <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="nao_agendados" title="Atualizar conteúdo" ></i>
                <span class="toggle-table-content pull-left title-table-content" >Chamados não agendados</span>
            </th>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
            <th>OS</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        // hd-3485594
        //    Bloqueado a exibição de OS da familia POST MIX E CHOPEIRA

        // if (count($tickets_nao_agendados) > 0) {
        //     foreach ($tickets_nao_agendados as $row) {
        //         echo linhaTabela("nao_agendados", $row);
        //     }
        // }
        ?>
    </tbody>
</table>

<hr />

<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #5BB75B; color: #FFFFFF;" colspan="16" >
                <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
                <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="agendados" title="Atualizar conteúdo" ></i>
                <span class="toggle-table-content pull-left title-table-content" >Chamados agendados</span>
            </th>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
            <th>OS</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        if (count($tickets_agendados) > 0) {
            foreach ($tickets_agendados as $row) {
                echo linhaTabela("agendados", $row);
            }
        }
        ?>
    </tbody>
</table>

<hr />

<table class="table table-bordered table-large table-striped tickets-table" >
    <thead>
        <tr>
            <th style="background-color: #DA4F49; color: #FFFFFF;" colspan="17" >
                <i class="icon-resize-full icon-white toggle-table-content pull-left action-table-content" title="Mostrar/Esconder conteúdo" ></i>
                <i class="icon-refresh icon-white pull-left action-table-content content-refresh" data-refresh="erro_fechamento" title="Atualizar conteúdo" ></i>
                <span class="toggle-table-content pull-left title-table-content" >Erro no fechamento da Ordem de Serviço</span>
            </th>
        </tr>
        <tr class="titulo_coluna table-content" >
            <th>Protocolo Cliente</th>
            <th>OS Cliente</th>
            <th>Prioridade</th>
            <th>Cliente</th>
            <th>Localização</th>
            <th>Distribuidor / Unidade de Négocio</th>
            <th>Descrição</th>
            <th>Tipo Atendimento</th>
            <th>Família</th>
            <th>Defeito</th>
            <th>Solicitação Cliente</th>
            <th>Integração Telecontrol</th>
            <th>Arquivo</th>
            <th>OS</th>
            <th>Status</th>
            <th>Erro</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody class="table-content">
        <?php
        if (count($tickets_erro_fechamento) > 0) {
            foreach ($tickets_erro_fechamento as $row) {
                echo linhaTabela("erro_fechamento", $row);
            }
        }
        ?>
    </tbody>
</table>

<hr />

<script>
$(function() {
    Shadowbox.init();

    $("select").select2();

    $("#cidade").select2({
        multiple: true
    });

    $("#date_filter").change(function() {
        if ($(this).is(":checked")) {
            $("div.div_date_filter_type").css({ visibility: "visible" });
        } else {
            $("div.div_date_filter_type").css({ visibility: "hidden" });
        }
    });

    $("input.date").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

    $("#estado").change(function() {
        var estado = $(this).val();
        var cidade = $("#cidade");

        $(cidade).find("option:first").nextAll().remove();

        if (estado.length > 0) {
            $.ajax({
                async: true,
                url: "conferencia_integracao.php",
                type: "GET",
                data: { ajax_busca_cidade: true, estado: estado },
                beforeSend: function() {
                    $(cidade).prop({ disabled: true }).html("<option value='' >Carregando...</option>").select2();
                }
            }).done(function(response) {
                response = JSON.parse(response);

                $(cidade).prop({ disabled: false }).html("<option value='' >Todas</option>").select2();

                if (response.error) {
                    alert(response.error);
                } else {
                    response.cidades.forEach(function(value, i) {
                        var option = $("<option></option>", { value: value, text: value });
                        $(cidade).append(option);
                    });
                }
            });
        }
    });

    $(document).on("click", "button.btn-edit-ticket", function() {
        var i = $(this);

        var url = "conferencia_integracao_form.php?";

        url += "ticket="+$(i).data("ticket");
        url += "&os-kof="+$(i).data("os-kof");
        url += "&os-telecontrol="+$(i).data("os-telecontrol");
        url += "&telecontrol-protocol="+$(i).data("telecontrol-protocol");
        url += "&priority="+$(i).data("priority");
        url += "&scheduled="+$(i).data("scheduled");
        url += "&client-name="+$(i).data("client-name");

        Shadowbox.open({
            content: url,
            player: "iframe",
            title: "OS Cliente "+ $(i).data("os-kof")
        });
    });

    $(document).on("click", "button.btn-close-service-order", function() {
        var td             = $(this).parent();
        var os_telecontrol = $(this).data("os-telecontrol");
        var btn            = $(this);

        $.ajax({
            async: true,
            url: "conferencia_integracao.php",
            type: "GET",
            timeout: 60000,
            contentType: "application/json",
            dataType: "json",
            data: {
                ajax_close_service_order: true,
                os_telecontrol: os_telecontrol
            },
            beforeSend: function() {
                $(td).find("button").prop({ disabled: true });
                $(btn).html("<i class='icon-time icon-white' ></i> Finalizando...");
            }
        }).fail(function() {
            alert("Erro ao finalizar OS, tempo limite esgotado");
            $(td).find("button").prop({ disabled: false });
            $(btn).html("<i class='icon-check icon-white' ></i> Finalizar OS");
        }).done(function(response) {
            if (response.error) {
                alert(response.error);
                $(td).find("button").prop({ disabled: false });
                $(btn).html("<i class='icon-check icon-white' ></i> Finalizar OS");
            } else {
                $(td).html("<span class='label label-success'>OS Finalizada com sucesso</span>");
            }
        });
    });

    $.dataTableLoad({
        table: ".tickets-table",
	aaSorting: [],
	aoColumns: null,
        type: "custom",
        config: ["pesquisa", "paginacao", "info"]
    });

    $("div.dataTables_wrapper").find("div.row:first").hide();

    $("button.btn-clear-filter").on("click", function() {
        $(this).prop({ disabled: true }).text("Limpando...");

        $("form").find("input[type=text]").val("");
        $(".select-clear").val("").trigger("change");
        $("form").find("input[type=checkbox]").prop({ checked: false }).change();

        dataTables.forEach(function(t, i) {
            var c = (t.context.querySelectorAll("thead > tr"))[1].querySelectorAll("th");

            for (var n = 0; n < c.length; n++) {
                t.fnFilter("", n, true, false, false, true);
            }
        });

        $(this).prop({ disabled: false }).text("Limpar");
    });

    $("button.btn-filter").on("click", function() {
        $(this).prop({ disabled: true }).text("Pesquisando...");

        if ($("#date_filter").is(":checked") && $("#init_date").val().length > 0 && $("#end_date").val().length > 0) {
            var startDate = $("#init_date").val().split("/");
            var stopDate  = $("#end_date").val().split("/");

            startDate = new Date(startDate[2], (parseInt(startDate[1]) - 1), startDate[0]);
            stopDate  = new Date(stopDate[2], (parseInt(stopDate[1]) - 1), stopDate[0]);

            var betweenDates     = (new Date()).getDates(startDate, stopDate);
            var array_date_regex = [];

            betweenDates.forEach(function(date, i) {
                array_date_regex.push("(^"+date.dateToString("\\/")+")");
            });

            switch ($("#date_filter_type").val()) {
                case "kof":
                    filterDataTable(array_date_regex.join("|"), 10);
                    filterDataTable("", 11);
                    break;

                case "telecontrol":
                    filterDataTable(array_date_regex.join("|"), 11);
                    filterDataTable("", 10);
                    break;
            }
        } else {
            filterDataTable("", 10);
            filterDataTable("", 11);
        }

        if ($("#osKof").val().trim().length > 0) {
            filterDataTable("(^"+$("#osKof").val().trim()+"$)", 1);
        } else {
            filterDataTable("", 1);
        }

        if ($("#protocoloKof").val().trim().length > 0) {
            filterDataTable("(^"+$("#protocoloKof").val().trim()+"$)", 0);
        } else {
            filterDataTable("", 0);
        }

        if ($("#osTelecontrol").val().trim().length > 0) {
            filterDataTable("(^"+$("#osTelecontrol").val().trim()+"$)", 13);
        } else {
            filterDataTable("", 13);
        }

        if ($("#prioridades").val().length > 0) {
            filterDataTable("(^"+$("#prioridades").val()+"$)", 2);
        } else {
            filterDataTable("", 2);
        }

        if ($("#tipo_atendimento").val().length > 0) {
            filterDataTable("(^"+$("#tipo_atendimento").val().replace(/\s-.+/gi, "")+")", 7);
        } else {
            filterDataTable("", 7);
        }

        if ($("#familia").val().length > 0) {
            filterDataTable("(^"+$("#familia").val()+"$)", 8);
        } else {
            filterDataTable("", 8);
        }

        if ($("#defeito").val().length > 0) {
            filterDataTable("(^"+$("#defeito").val()+"$)", 9);
        } else {
            filterDataTable("", 9);
        }

        if ($("#cliente").val().trim().length > 0) {
            filterDataTable("("+$("#cliente").val().trim()+")", 3);
        } else {
            filterDataTable("", 3);
        }

        if ($("#cidade").val() !== null && $("#estado").val().length > 0) {
            cidades = $("#cidade").val();
            var array_cidades = [];
            cidades.forEach(function(cidade, i) {
                array_cidades.push("(^"+cidade+" \/ "+$("#estado").val()+"$)");
            });
            filterDataTable(array_cidades.join("|"), 4);
        } else if ($("#estado").val().length > 0) {
            filterDataTable("(\/ "+$("#estado").val()+"$)", 4);
        } else {
            filterDataTable("", 4);
        }

        if ($("#distribuidor").val().length > 0 && $("#unidade_negocio").val().length > 0) {
            filterDataTable("(^"+$("#distribuidor").val()+" / "+$("#unidade_negocio").val()+"$)", 5);
        } else if ($("#distribuidor").val().length > 0) {
            filterDataTable("(^"+$("#distribuidor").val()+")", 5);
        } else if ($("#unidade_negocio").val().length > 0) {
            filterDataTable("("+$("#unidade_negocio").val()+"$)", 5);
        } else {
            filterDataTable("", 5);
        }

        if ($("#status_os").val().length > 0) {
            filterDataTable("("+$("#status_os").val()+")", 14);
        } else {
            filterDataTable("", 14);   
        }

        $(this).prop({ disabled: false }).text("Pesquisar");
    });

    $(".toggle-table-content").on("click", function() {
        var i      = $(this).parent().find("i.icon-resize-full, i.icon-resize-small");
        var action = $(i).data("table-content-action");

        if (typeof action == "undefined" || action == null) {
            action = "show";
        }

        var table  = $(i).parents("table");

        if (action == "hide") {
            $(table).find(".table-content").hide("slow");
            $(i).data({ "table-content-action": "show" });
            $(i).removeClass("icon-resize-small").addClass("icon-resize-full");
        } else {
            $(table).find(".table-content").show("slow");
            $(i).data({ "table-content-action": "hide" });
            $(i).removeClass("icon-resize-full").addClass("icon-resize-small");
        }
    });

    $(document).on("click", "button.btn-send-email", function() {
        var i = $(this);

        $(i).html("Enviando...");
        $(i).addClass("disabled");

        var os = $(i).data("os-telecontrol");
        $.ajax("conferencia_integracao.php",{
            method: "POST",
            data:{
                os: os,
                email_os: true

            }
        }).done(function(response){
            $(i).html('<i class="icon-envelope icon-white"></i> Enviar OS via Email');
            $(i).removeClass("disabled");

            response = JSON.parse(response);

            if(response.exception == undefined){
                alert("Email da OS #"+os+", enviado com sucesso");
            }else{
                alert("Ocorreu um erro ao enviar o email da OS #"+os+", por favor tente novamente");
            }
        });        
    });
});

function filterDataTable(string_regex, column) {
   dataTables.forEach(function(t, i) {
        var c = (t[0].querySelectorAll("thead > tr"))[1].querySelectorAll("th");
        var n = column;
        var s = string_regex;

        if (n >= c.length) {
            if (s.length > 0) {
                s = "invalid_column";
            }

            n = c.length - 1;
        }

        t.fnFilter(s, n, true, false, false, true);
    });
}

var distribuidor_unidade_negocio = <?=json_encode($distribuidor_unidade_negocio)?>;
var priorities                   = <?=json_encode($priorities)?>;

<?php
$distribuidores = array_map(function($r) {
    return utf8_encode($r);
}, $distribuidores);
?>

var distribuidores = <?=json_encode($distribuidores)?>;

<?php
$tipos_atendimentos = array_map(function($r) {
    return utf8_encode($r);
}, $tipos_atendimentos);
?>

var tipos_atendimentos = <?=json_encode($tipos_atendimentos)?>;
var unidades_negocio   = <?=json_encode($unidades_negocio)?>;

$(".content-refresh").on("click", function() {
    var content = $(this).data("refresh");
    var icon    = $(this);
    var table   = $(this).parents("table");
    var tbody   = $(table).find("tbody");

    if ($(table).data("refreshing-content")) {
        alert("Já existe uma atualização do conteúdo em andamento, aguarde...");
        return false;
    }

    $(table).data({ "refreshing-content": true });

    var animate = setInterval(function() {
        var p = new Promise(function(resolve, reject) { 
            $(icon).addClass("icon-refresh-animate");

            setTimeout(function() { 
                resolve(true); 
            }, 1000); 
        }).then(function(r) {
            $(icon).removeClass("icon-refresh-animate");
        });
    }, 1100);

    var stopAnimate = function() {
        clearInterval(animate);
        $(icon).removeClass("icon-refresh-animate");
        $(table).data({ "refreshing-content": false });
    };

    $.ajax({
        url: "conferencia_integracao.php",
        type: "post",
        data: { 
            ajax_refresh_content: true, 
            content: content,
            distribuidor_unidade_negocio: distribuidor_unidade_negocio,
            priorities: priorities,
            distribuidores: distribuidores,
            tipos_atendimentos: tipos_atendimentos,
            unidades_negocio: unidades_negocio
        },
        timeout: 60000
    }).fail(function(response) {
        alert("Ocorreu um erro ao atualizar o conteúdo");
        stopAnimate();
    }).done(function(response) {
        response = JSON.parse(response);

        if (response.error) {
            alert(response.error);
            stopAnimate();
        } else if (typeof response.error != "undefined" && response.error == null) {
            alert("Erro ao atualizar o conteúdo");
            stopAnimate();
        } else {
            var destroyDataTable = [];

            dataTables.forEach(function(t, i) {
                destroyDataTable.push(
                    new Promise(function(resolve, reject) {
                        t.fnDestroy(false);
                        $(tbody).html("");

                        resolve(true);
                    })
                );
            });

            Promise.race(destroyDataTable).then(function(res) {
                var populateTable = new Promise(function(resolve, reject) {
                    dataTables = [];

                    $.each(response, function(i, row) {
                        $(tbody).append(row);
                    });

                    resolve(true);
                }).then(function(res) {
                    $.dataTableLoad({
                        table: ".tickets-table",
                        aaSorting: [],
                        type: "custom",
                        config: ["pesquisa", "paginacao", "info"]
                    });

                    $("button.btn-filter").trigger("click");

                    stopAnimate();
                });
            });
        }
    });
});

</script>

<?php

ini_set("memory_limit", "128M");
include "rodape.php";
?>
