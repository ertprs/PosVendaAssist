<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';

include_once '../helpdesk/mlg_funciones.php';
include_once './funcoes.php';

$title       = "Relatório de Solicitações de Postagem";
$layout_menu = "gerencia";

$form = [
    'data_inicial' => [
        'span'     => 4,
        'label'    => 'Data Inicial',
        'type'     => 'input/text',
        'width'    => 5,
        'required' => true,
    ],
    'data_final' => [
        'span'     => 4,
        'label'    => 'Data Final',
        'type'     => 'input/text',
        'width'    => 5,
        'required' => true,
    ],
];

if (!in_array($login_fabrica, array(1))) {

    $form['gerado'] = [
            'span'    => 4,
            'label'   => 'Gerado Por',
            'type'    => 'select',
            'width'   => 5,
            'options' => [
                'sac'   => 'SAC',
                'posto' => 'Postos'
            ],
    ];
}

$form['csv'] = [
        'span'   => 4,
        'label'  => 'Gerar Arquivo?',
        'type'   => 'checkbox',
        "checks" => ['t' => 'CSV']
    ];

try {
    if (count($_POST)) {
        $gerarCSV = $_REQUEST['csv'][0] == 't';
        $data_ini = is_date(getPost('data_inicial'));
        $data_fim = is_date(getPost('data_final'));
        $gerado   = getPost('gerado');

        // pre_echo($_POST, 'POST data');
        // pre_echo(compact('data_ini', 'data_fim', 'gerarCSV', 'gerado'), 'PARSED data');

        if (!$data_ini) throw new Exception('Digite uma data inicial válida!');
        if (!$data_fim) throw new Exception('Digite uma data final válida!');

        validaData($data_ini, $data_fim, 6);

        if (in_array($login_fabrica, array(1))) {
            $sql = "
                SET dateStyle TO 'SQL,DMY';
                SELECT tbl_faturamento_correio.numero_postagem
                     , tbl_faturamento_correio.data
                     , tbl_faturamento_correio.conhecimento
                     , tbl_faturamento_correio.faturamento
                     , tbl_faturamento_correio.situacao
                  FROM tbl_faturamento_correio
                 WHERE tbl_faturamento_correio.fabrica = $login_fabrica
                   AND tbl_faturamento_correio.data    BETWEEN '$data_ini' AND '$data_fim 23:59:59'
            ";
        } else {
            $sql = "
                SET dateStyle TO 'SQL,DMY';
                SELECT tbl_hd_chamado_postagem.hd_chamado
		     , CASE
			    WHEN tbl_hd_chamado_postagem.os IS NULL THEN
				tbl_hd_chamado_extra.os
			    ELSE tbl_hd_chamado_postagem.os
			END AS os
		     , CASE
			    WHEN tbl_os.sua_os IS NULL THEN
				osex.sua_os
			    ELSE tbl_os.sua_os
			END AS sua_os
                     , tbl_hd_chamado_postagem.numero_postagem
                     , case when tbl_faturamento_correio.data notnull then tbl_faturamento_correio.data else tbl_hd_chamado_postagem.data end as data
                     , tbl_hd_chamado_postagem.admin
                     , COALESCE(tbl_admin.nome_completo, 'POSTO') AS atendente
                     , tbl_faturamento_correio.conhecimento
                     , tbl_faturamento_correio.faturamento
                     , tbl_faturamento_correio.situacao
                  FROM tbl_hd_chamado_postagem
             LEFT JOIN tbl_faturamento_correio
                    ON tbl_faturamento_correio.numero_postagem = tbl_hd_chamado_postagem.numero_postagem
                   AND tbl_faturamento_correio.fabrica         = $login_fabrica
             LEFT JOIN tbl_admin
                    ON tbl_admin.admin = tbl_hd_chamado_postagem.admin
             LEFT JOIN tbl_os
		    ON tbl_os.os = tbl_hd_chamado_postagem.os
	     LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_postagem.hd_chamado = tbl_hd_chamado_extra.hd_chamado
	     LEFT JOIN tbl_os osex ON osex.os = tbl_hd_chamado_extra.os
                 WHERE tbl_hd_chamado_postagem.fabrica = $login_fabrica
		   AND tbl_hd_chamado_postagem.data    BETWEEN '$data_ini' AND '$data_fim 23:59:59'
            ";

            if ($gerado == 'posto')
            $sql .= " AND tbl_hd_chamado_postagem.admin IS NULL";
            if ($gerado == 'sac')
		    $sql .= " AND tbl_hd_chamado_postagem.admin IS NOT NULL";

	    $sql .= " GROUP BY tbl_hd_chamado_postagem.hd_chamado,
		    		tbl_hd_chamado_postagem.os,
				tbl_hd_chamado_extra.os,
				tbl_os.sua_os,
				osex.sua_os,
				tbl_hd_chamado_postagem.numero_postagem,
				tbl_hd_chamado_postagem.data,
				tbl_faturamento_correio.data,
				tbl_hd_chamado_postagem.admin,
				atendente,
				tbl_faturamento_correio.conhecimento,
				tbl_faturamento_correio.faturamento,
				tbl_faturamento_correio.situacao
			ORDER BY tbl_hd_chamado_postagem.numero_postagem";
        }

        $res = pg_query($con, $sql);

        //pre_echo($sql);

        if(!is_resource($res)) {
            throw new Exception("Erro ao acessar o banco. ".pg_last_error($con) . "<pre>$sql</pre>");
        }

        if (pg_num_rows($res)) {
            $tabela = [];
            while ($row = pg_fetch_assoc($res)) {
                $sqlRastreio = "SELECT conhecimento AS conhecimento
                                  FROM tbl_faturamento_correio
                                 WHERE fabrica     = $login_fabrica
                                   AND numero_postagem = '{$row['numero_postagem']}'";
                $resRastreio = pg_query($con, $sqlRastreio);

                if (in_array($login_fabrica, array(1))) {
                    $sqlOs = "SELECT tbl_os_campo_extra.os, 
                                tbl_posto_fabrica.codigo_posto || tbl_os.sua_os as os_black
                              FROM tbl_os_campo_extra
                              JOIN tbl_os USING (os)
                              JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto
                              AND tbl_posto_fabrica.fabrica = $login_fabrica
                              WHERE JSON_FIELD('numero_coleta', campos_adicionais) = '".$row['numero_postagem']."'
                              AND tbl_os.fabrica = $login_fabrica LIMIT 1";
                    $resOs = pg_query($con, $sqlOs);

                    $sua_os = pg_fetch_result($resOs, 0, 'os_black');
                    $os     = pg_fetch_result($resOs, 0, 'os');

                    $row['os']     = $os;
                    $row['sua_os'] = $sua_os;
                }


		// $conhecimento = 'SW338533166BR';
                $cod_rastreio = $row['conhecimento'];
                if (strpos($cod_rastreio, 'http') !== false) {
                    $conhecimento = "<a href='{$cod_rastreio}' target='_blank'>Ratreio Pedido</a>";
                } else if (pg_num_rows($resRastreio) > 0) {
                    $cod_rastreio = pg_fetch_result($resRastreio, 0, 'conhecimento');
                    $conhecimento = "<a href='./relatorio_faturamento_correios.php?conhecimento={$cod_rastreio}' rel='shadowbox'>{$cod_rastreio}</a>";
                } else {
                    $conhecimento = "<a href='http://www.linkcorreios.com.br/{$cod_rastreio}' target='_blank'>{$cod_rastreio}</A>";
                }
                $consumidor_nome = "";
                if (in_array($login_fabrica, array(186))) {
                    $sqlChamadoExtra = "SELECT nome FROM tbl_hd_chamado_extra WHERE hd_chamado =".$row['hd_chamado'];
                    $resChamadoExtra = pg_query($con, $sqlChamadoExtra);
                    $consumidor_nome = pg_fetch_result($resChamadoExtra, 0, 'nome');
                }
                if ($gerarCSV) {

                     if (in_array($login_fabrica, array(1))) {
                        $tabela[] = [
                             'OS'            => $row['sua_os'],
                             'Num. Postagem' => $row['numero_postagem'],
                             'Data'          => substr($row['data'], 0, 19),
                             'Cod. Rastreio' => strip_tags($conhecimento),
                        ];
                     } else if (in_array($login_fabrica, array(186))) {
                        $tabela[] = [
                         'Atendimento'   => (int) $row['hd_chamado'],
                         'OS'            => $row['sua_os'],
                         'Nome do Consumidor' => $consumidor_nome,
                         'Núm. Postagem' => $row['numero_postagem'],
                         'Data'          => substr($row['data'], 0, 19),
                         'Atendente'     => $row['atendente'],
                         'Cód. Rastreio' => strip_tags($conhecimento),
                         'Situação'      => (trim($row['situacao']) == '01') ? "Solicitação gerada" : trim($row['situacao']),
                        ];
                     } else {
                        $tabela[] = [
                         'Atendimento'   => (int) $row['hd_chamado'],
                         'OS'            => $row['sua_os'],
                         'Núm. Postagem' => $row['numero_postagem'],
                         'Data'          => substr($row['data'], 0, 19),
                         'Atendente'     => $row['atendente'],
                         'Cód. Rastreio' => strip_tags($conhecimento),
                         'Situação'      => (trim($row['situacao']) == '01') ? "Solicitação gerada" : trim($row['situacao']),
                        ];
                        if (in_array($login_fabrica, array(186))) {
                            $tabela[] = ["Nome do Consumidor" => $consumidor_nome];
                        }
                     }
                } else {
                    if (in_array($login_fabrica, array(1))) {

                       $tabela[] = [
                           'OS' => "<a href='os_press.php?os={$row['os']}' target='new'>{$row['sua_os']}</a>",
                           'Núm. Postagem' => $row['numero_postagem'],
                           'Data'          => substr($row['data'], 0, 19),
                           'Cód. Rastreio' => $conhecimento,
                       ];

                    } elseif (in_array($login_fabrica, array(186))) {

                       $tabela[] = [
                           'Atendimento' => $row['hd_chamado']
                               ? "<a href='callcenter_interativo_new.php?callcenter={$row['hd_chamado']}' target='new'>{$row['hd_chamado']}</a>"
                               : '&ndash;',
                           'OS' => $row['os']
                               ? "<a href='os_press.php?os={$row['os']}' target='new'>{$row['sua_os']}</a>"
                               : '&ndash;',
                           'Nome do Consumidor' => $consumidor_nome,
                           'Núm. Postagem' => $row['numero_postagem'],
                           'Data'          => substr($row['data'], 0, 19),
                           'Atendente'     => $row['atendente'],
                           'Cód. Rastreio' => $conhecimento,
                           'Situação'      => (trim($row['situacao']) == '01') ? "Solicitação gerada" : trim($row['situacao']),
                       ];

                    } else {

                        $tabela[] = [
                           'Atendimento' => $row['hd_chamado']
                               ? "<a href='callcenter_interativo_new.php?callcenter={$row['hd_chamado']}' target='new'>{$row['hd_chamado']}</a>"
                               : '&ndash;',
                           'OS' => $row['os']
                               ? "<a href='os_press.php?os={$row['os']}' target='new'>{$row['sua_os']}</a>"
                               : '&ndash;',
                           'Núm. Postagem' => $row['numero_postagem'],
                           'Data'          => substr($row['data'], 0, 19),
                           'Atendente'     => $row['atendente'],
                           'Cód. Rastreio' => $conhecimento,
                           'Situação'      => (trim($row['situacao']) == '01') ? "Solicitação gerada" : trim($row['situacao']),
                       ];

                    }
                }
            }

            if ($gerarCSV and count($tabela)) {
                $csvFname = 'solicitacoes_postagem_'.is_date('agora', '', 'Y-m-d-H-i').'.csv';
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="'. $csvFname . '"');

                echo strip_tags(array2csv($tabela, ';', true, true));
                die;
            }

            $tableAttrs = array(
                'tableAttrs' => 'data-toggle="table" width="800" class="table table-fixed table-large table-bordered table-hover table-striped "',
                'headerAttrs' => 'class="titulo_coluna"',
            );

        }
        else {
            $msg_erro['msg'][] = 'Sem registros';
        }
    }
} catch (Exception $e) {
    $msg_erro['msg'][] = $e->getMessage();
}

include 'cabecalho_new.php';

$plugins = ['dataTable', 'datepicker', 'mask', 'shadowbox'];
include_once 'plugin_loader.php';

?>
    <div class="container">
<?php if (count($msg_erro["msg"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?php echo implode("<br />", $msg_erro["msg"])?></h4>
        </div>
<?php } ?>
        <div class="container">
            <strong class="obrigatorio pull-right"> * Campos obrigatórios </strong>
        </div>
        <form name='frm_relatorio' method='POST' align='center' class='form-search form-inline tc_formulario'>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <div class='titulo_tabela'>Parâmetros de Pesquisa</div> <br/>
            <? echo montaForm($form, []);?>
            <p>
            <br/>
            <button class='btn' id="btn_acao" type="button"
                  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
            </p>
            <br/>
        </form>
<?php
if (isset($tabela) and count($tabela)) {
    echo array2table($tabela);
}
?>
    </div>
<script>
$(function(){
    $("form[name=frm_relatorio]").find('input,select').change(function() {
        $("#btn_click").val('');
    });

    Shadowbox.init();
    $('#data_inicial,#data_final').mask("99/99/9999");
    $.datepickerLoad(["data_final", "data_inicial"]);
<?php if (count($tabela) > 60): ?>
    $.dataTableLoad('.table');
<?php endif; ?>
});
</script>
<br />
<br />
<?php
include 'rodape.php';

