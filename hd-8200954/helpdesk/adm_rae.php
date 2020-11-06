<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../admin/funcoes.php';
include_once 'mlg_funciones.php';

define('BS3', true); // Esta tela utiliza o novo menu e layout Bootstrap 3

$array_status_hd = array(
    'Análise'         => array("Novo","Aberto","Análise", "Previsao Cadastrada", "Orçamento",),
    'Requisitos'      => array("Requisitos", "Ap.Requisitos"),
    'Teste'           => array("Validação", "ValidaçãoHomologação",),
    'Desenvolvimento' => array("Execução",'Aguard.Execução'),
    'Correção'        => array("Correção",),
    'Implementação'   => array("Documentação", "Efetivação", "EfetivaçãoHomologação",),
    /*
    'Outros' => array(
        "Aberto",
        "Aguard.Admin",
        "Cancelado",
        "Novo"
        "Parado",
        "Resolvido",
        "Suspenso",
    ),
    */
);

$array_fabricas = pg_fetch_pairs(
    $con,
    "SELECT fabrica, nome FROM tbl_fabrica WHERE ativo_fabrica ORDER BY nome"
);

$statusCASE = '';
foreach($array_status_hd as $atividade => $status) {
    foreach ($status as $status_item) {
        $statusCASE .= sprintf("                WHEN '%s' THEN '%s'\n\t\t\t", $status_item, $atividade);
    }
}
$statusCASE = trim($statusCASE); // este é cosmético, serve apenas para que a consulta fique bem alinhada.

$TITULO = 'Relatório Analítico de Atividades';
$MAX_INTERVAL = 90;

if (count($_REQUEST)) {
    $data_ini   = is_date(getPost('data_inicial'));
    $data_fim   = is_date(getPost('data_final'));
    $enviar     = $_POST['send_email'] == 't';
    $separar    = $_POST['separar']    == 't';
    $analitico  = $_POST['analitico']  == 't';
    $totalizar  = getPost('totalizar') == 't';
    $ajax       = (bool)strlen(getPost('modal'));
    $hd_chamado = (int)getPost('hd_chamado');
    $fabrica    = (int)getPost('fabrica');

    if (!$ajax and !$hd_chamado and (!$data_ini or !$data_fim)) {
        if (!$data_ini and !$data_fim) {
            $array_status_hd['Aguardando Cliente'] = array('Aguard.Admin');

            if (date('d') >= 10) {
                $data_ini = $data_ini ? : date('Y-m-01');
                $data_fim = $data_fim ? : date('Y-m-d');
            } else {
                $data_ini = $data_ini ? : date('Y-m-01', strtotime('-1 month'));
                $data_fim = $data_fim ? : date('Y-m-t',  strtotime('-1 month'));
            }
        } else {
            if ($data_ini)
                $data_fim = date('Y-m-t', is_date($data_ini, 'EUR', 'U'));
            else
                $data_ini = date('Y-m-01', is_date($data_fim, 'EUR', 'U'));
        }
        // Já deixa no _POST para recarregar...
        $_POST['data_inicial'] = is_date($data_ini, 'ISO', 'EUR');
        $_POST['data_final']   = is_date($data_fim, 'ISO', 'EUR');

        if (date_interval($data_ini, $data_fim, 'd') > $MAX_INTERVAL)
            throw new Exception("Intervalo entre datas deve ser de no máximo $MAX_INTERVAL dias.");
        //  $data_fim - $data_ini = ".date_interval($data_ini, $data_fim, 'd')

    }

    try {

        $ordem = $ajax ? 'HDi.data' : 'nome_completo, hd_chamado, HDi.data';
        if ($ajax and $hd_chamado and $fabrica) {
            $filtros = array(
                'HD.hd_chamado' => $hd_chamado,
                'HD.fabrica' => $fabrica
            );
            $analitico = true;
            $enviar    = false;
            $separar   = false;
            $totalizar = true;
        // } else {
        //     $filtros['comentario='] = "~* E'(in.cio do|t.rmino de) trabalho( autom.tico)?|chamado transferido'";
        }

        if ($data_ini)
            $filtros['HDi.data'] = "$data_ini::$data_fim 23:59:59";

        $filtros['fabrica_responsavel'] = 10;

        pg_query($con, "SET DateStyle TO SQL, DMY");

        $sql = "SELECT nome_completo, HDi.admin, hd_chamado, titulo,  HDi.data AS hora,
                       CASE fn_status_hd_item(hd_chamado, HDi.data)
                            $statusCASE
                            ELSE 'Outros'
                       END AS status_item,
                       fn_status_hd_item(hd_chamado, CURRENT_DATE) AS status_hd,
                       CASE
                         WHEN comentario ~* 't.rmino de trabalho( autom.tico)?|chamado transferido'
                           OR status_item = 'Resolvido'
                         THEN 'FIM'
                         WHEN comentario ~* 'in.cio do trabalho'
                         THEN 'INICIO'
                         ELSE     NULL
                       END AS tipo,
                       HD.status,
                       (SELECT nome FROM tbl_fabrica AS f WHERE f.fabrica = HD.fabrica) AS fabrica_nome,
                       tbl_backlog_item.backlog,
                       tbl_backlog_item.horas_analisadas
                  FROM tbl_hd_chamado_item AS HDi
                  JOIN tbl_admin USING (admin)
             LEFT JOIN tbl_hd_chamado HD USING (hd_chamado)
             LEFT JOIN tbl_backlog_item  USING (hd_chamado)
                 WHERE ".sql_where($filtros)."
                 ORDER BY $ordem";

        // file_put_contents("/var/www/test/analitic_data.php", PHP_TAG . PHP_EOL . '$sql = '.$sql. PHP_EOL);

        // pre_echo($sql, 'CONSULTA');

        // $resDumpFile = '/home/manuel/test/res_dump.php';
        // if (!file_exists($resDumpFile))
            $res = pg_query($con, $sql);
        // print(array2table($res, 'RESULTADO CONSULTA', true));

        if (!is_resource($res)) {
            throw new Exception('Erro durante a consulta.<br />'.pg_last_error($con));
        }

        if (!pg_num_rows($res)) {
            throw new Exception('Sem resultados para o período solicitado.');
        }

        $tableAttrs = array(
            'tableAttrs' => 'data-toggle="table" class="table table-condensed table-bordered table-hover table-striped "'
        );

        // if (!file_exists($resDumpFile))
        //     save_var(pg_fetch_all($res), $resDumpFile);

        if ($ajax) {
            $dados = file_exists($resDumpFile) ? include_once($resDumpFile) : pg_fetch_all($res);
            foreach ($dados as $i =>$rec) {
                $dados[$i] = array(
                    'Atendente' => $rec['nome_completo'],
                    'Atividade' => "<span class='pull-right label label-" . (($rec['tipo']=='INICIO')?'success':'danger') . "'>{$rec['tipo']}</span> {$rec['status_item']}",
                    'Data' => is_date($rec['hora'], 'EUR', 'EUR')
                );
            }
            die(array2table($dados, 'Resumo de atividade'));
        }

        /**
         * Estrutura
         * ---
         * Login:       usuário admin TC,e ste será o primeiro nível, pois daí virá a quebra em arquivos/abas
         * Chamado:     núm. de HD cujos dados estão sendo mostrados, segundo nível, não tem quebra, repetido no CSV?
         * Fábrica:     Nome do fabricante que abriu o chamado
         * Atividade:   por enquanto, o status do chamado
         * Inicio:      Hora de início de trabalho
         * Final:       Hora de término de trabalho, transferência dos chamado ou resolução
         * Horas:       Estimação de horas de desenvolvimento para aquele chamado (pelo analista, está no BackLog)
         * Trabalhado:  Intervalo de tempo entre o início e o fim de trabalho para aquela atividade
         */
        $dados = array();
        $i = 0;

        // Agrupando por atendente e atividade, descarta os registros que não sejam INICIO ou FIM
        while ($row = pg_fetch_row($res, $i++)) {
            list (
                $new_login, $new_admin,
                $new_hd, $titulo, $new_hora, $new_status, $new_status_hd, $new_tipo, $new_hd_status,
                $new_nome_fabrica, $new_backLogID, $new_horas_analisadas) = $row;

            // if ($new_login == $login and $tipo == 'INICIO' and $new_tipo === $tipo) {
            //     continue;
            // }

            if ($new_tipo == 'INICIO' and is_null($data_ini) or $new_hd !== $hd)
                $data_ini = $new_hora;

            // if (is_null($new_status))
            //  $status = pg_fetch_result(pg_query($con, "SELECT status_hd_item($new_hd, $new_hora)"), 0, 0);

            if ($new_tipo == 'FIM') {
                $data_fim   = $new_hora;
                $intervalo  = date_interval($data_ini, $data_fim);
                $trabalhado = date_interval($data_ini, $data_fim, 'HMS');
                $atividade  = $new_status;
            // }
            //
            // if ($intervalo and $data_ini and $data_fim) {
                $dados[$new_login][] = array(
                    'Chamado'    => $hd,
                    'titulo'     => $titulo,
                    'Fábrica'    => $nome_fabrica,
                    'Status HD'  => $new_status_hd,
                    'Atividade'  => $atividade,
                    'Início'     => is_date($data_ini, 'ISO', 'EUR'),
                    'Final'      => is_date($data_fim, 'ISO', 'EUR'),
                    'Horas'      => $horas_analisadas,
                    'BackLOG'    => $backLogID,
                    'Intervalo'  => $trabalhado,
                    'Trabalhado' => $intervalo,
                );
                $data_ini = $data_fim = $intervalo = null;
            }

            $login            = $new_login;
            $admin            = $new_admin;
            $hd               = $new_hd;
            $hora             = $new_hora;
            $status           = $new_status;
            $tipo             = $new_tipo;
            $hd_status        = $new_hd_status;
            $nome_fabrica     = $new_nome_fabrica;
            $backLogID        = $new_backLogID;
            $horas_analisadas = $new_horas_analisadas;
            $status           = $atividade;
            $atendimento      = null;
        }
        // pre_echo($dados, 'DADOS DO BANCO');

        // Agrupando por HD, acumulando as horas trabalhadas
        if (!$analitico) {
            $recs = array(); // initialize

            foreach ($dados as $at => $rows) {
                // Agrupnado os dados
                foreach ($rows as $atData) {
                    $hd = $atData['Chamado'];
                    unset($atData['Chamado']);
                    $recs[$at][$hd][] = $atData;
                }
                // echo array2table($recs[$at], "Sintético por HDs Atendente $at");
                // pre_echo($recs[$at], 'Dados do atendente '.$at);
            }

            foreach ($recs as $at => $rec) {
                unset($first, $last);
                $total_horas = 0;
                $indice      = 0;
                $horas_total = 0;
                $totalAT     = 0;
                $estAT       = 0;
                foreach ($rec as $hd => $record) {
                    $first       = reset($record);
                    $last        = end($record);
                    $title       = $first['titulo'];
                    $data_ini    = $first['Início'];
                    $fabricaID   = array_search($first['Fábrica'], $array_fabricas);
                    $data_fim    = $last['Final'];
                    $tempo_total = array_sum(array_column($record, 'Trabalhado'));
                    $horas_total = $first['Horas'];
                    $total_horas = $tempo_total/3600;
                    $indice      = $first['Horas'] ?
                            sprintf('%01.2f', $total_horas / $first['Horas']*100) . ' %' :
                            '&mdash;';
                    $horas_trabalhadas = format_interval($tempo_total, 'hms');

                    $row = array(
                        'Chamado' => createHTMLLink(
                            "adm_chamado_detalhe.php?hd_chamado=$hd&consultar=sim", $hd,
                            " title='Abrir HD $hd &ndash; $titulo' target='new'") .
                            "<button class='btn btn-sm details pull-right' title='Detalhes do HD' data-title='$hd' data-target='#aux' data-href='?hd_chamado=$hd&modal=1&fabrica=$fabricaID'><i class='glyphicon glyphicon-tasks'></i></button>",
                        'Fábrica'   => $first['Fábrica'],
                        'Status HD' => $first['Status HD'],
                        'Início'    => $data_ini,
                        'Final'     => $data_fim,
                        'BackLog'   => $first["Horas"],
                        'Horas'     => $first['Horas'] ?
                            createHTMLLink(
                                "../admin/backlog_cadastro.php?backlog={$first['BackLOG']}&acao=item",
                                $first['Horas'], ' target="new" title="Ver BackLog"') :
                            '&mdash;',
                        // 'Segundos/Horas' => array($tempo_total, $total_horas, $first['Horas']),
                        'Trabalhadas' => $horas_trabalhadas,
                        'Índice' => $indice
                    );
                    if ($enviar == 't') {
                        $row['tempo_trabalho'] = $tempo_total;
                    }

                    $dataTable[$at][$hd] = $row;
                    $estAT      += $horas_total;
                    $totalAT    += $tempo_total;
                }

                // pre_echo($dataTable[$at], "Resumo para $at");

                if ($totalizar) {
                    $hdcount = count($dataTable[$at]);

                    if ($hdcount > 1)
                        $dataTable[$at]['Total'] = array(
                            "<b>$hdcount HD" . ($hdcount==1?'':'s').'</b>',
                            '', '', '',
                            "<b>$estAT h</b>",
                            '<b>'.format_interval($totalAT, 'hms').'</b>',
                            '<b>'.sprintf('%01.2f', $totalAT / $estAT / 36) . ' %</b>'
                        );
                }
            }
        } else {
            $dataTable = array();
            // pre_echo($dados, 'Dados agrupados');
            foreach ($dados as $att => $recs) {
                foreach ($recs as $i => $rec) {
                    $hd             = $rec['Chamado'];
                    $backLog        = $rec['BackLOG'];
                    $analisadas     = $rec['Horas'];
                    $total_horas    = $rec['Trabalhado'] / 3600;
                    $fabricaID      = array_search($rec['Fábrica'], $array_fabricas);

                    $rec['Chamado'] = createHTMLLink(
                        "adm_chamado_detalhe.php?hd_chamado=$hd&consultar=sim", $hd,
                        " title='Abrir HD $hd' target='new'") ;

                    $rec['Horas']   = $analisadas ?
                        createHTMLLink(
                            "../admin/backlog_cadastro.php?backlog=$backLog&acao=item",
                            $analisadas, ' target="new" title="Ver BackLog"') :
                        '&mdash;';

                    $rec['Trabalhado'] = format_interval($rec['Trabalhado'], 'hms');
                    $rec['Índice']     = $analisadas ? round($total_horas / $analisadas, 2)*100 . ' %' : '&mdash;';

                    if ($enviar == 't') {
                        $rec['tempo_trabalho'] = $rec['Trabalhado'];
                    }
                    unset($rec['BackLOG']);

                    $dataTable[$att][] = $rec;
                    // pre_echo($rec, 'Registro '.$i);
                }
            }
            // pre_echo($dataTable, 'Analítico', true);
        }

        if (count($dataTable)) {
            $msg_success[] = "Relatório gerado!";
        }

        // Gerando o CSV se foi solicitado o envio de E-mail
        if ($enviar == 't' and count($dataTable)) {
            require_once(APP_DIR . DIRECTORY_SEPARATOR . 'class/communicator.class.php');
            $mail = new TcComm('smtp@posvenda', 'helpdesk@telecontrol.com.br');
            $header = array('Atendente', 'Chamado', 'Fábrica', 'Status', 'Início', 'Final', 'Horas', 'Trabalhadas', 'Índice', 'Segundos');
            $csv    = $separar ? array() : $header;

            foreach ($dataTable as $att => $hds) {
                // if ($separar) $csv[] = $header;
                foreach ($hds as $hd => $rec) {
                    $rec['Horas'] = $rec['BackLog'];

                    if (!$rec['Final'] and $totalizar) continue; // Pula o totalizodor, se tiver

                    $csv[] =array(
                        $att, $hd, $rec['Fábrica'], $rec['Status HD'], $rec['Início'], $rec['Final'], $rec['Horas'],
                        $rec['Trabalhadas'], $rec['Índice'], $rec['tempo_trabalho']
                    );
                    unset($dataTable[$att][$hd]['tempo_trabalho'], $dataTable[$att][$hd]['BackLog']);
                }
                if ($separar) $csv[] = array('');
            }

            $mail->setEmailDest($login_email)
                ->setEmailSubject("Relatório de Horas Trabalhadas por Atendente e Chamado ({$_POST['data_inicial']} - {$_POST['data_final']})")
                ->setEmailBody(
                    "<p>Seguem os dados solicitados às ".is_date('agora', 'ISO', 'EUR') . '</p><pre>' .
                    array2csv($csv, ';') .
                    '</pre><p>&nbsp;</p>')
                ->sendMail();
            // file_put_contents('/var/www/test/atendentes.csv',array2csv($csv));
            $msg_success[] = "Relatório encaminhado para seu endereço de e-mail cadastrado.";
        }
        if (!$analitico) {
            // Limpar as colunas extra que foram inseridas para o CSV
            foreach ($dataTable as $att => $hds)
                foreach ($hds as $hd => $rec)
                    unset($dataTable[$att][$hd]['BackLog']);
        }
        // pre_echo($dataTable, 'Processado');
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
        if ($ajax)
            die("<div class='alert alert-danger'>$msg_erro</div>");
    }

}

$bs_extras = array('datepicker', 'bstable', 'toggle');
include './menu.php';
?>
<style>
.tab-content table.table th+th+th+th {text-align: center}
.tab-content table.table th+th+th+th+th+th {text-align: right}
.tab-content table.table td+td+td+td {text-align: center}
.tab-content table.table td+td+td+td+td+td {text-align: right}
</style>
<script>
var lang = '<?=$cook_idioma?>' || 'pt-BR';
$(function() {
    $('.input-group.date').datepicker({
        format: 'dd/mm/yyyy',
        language: 'pt-BR',
        weekStart: (lang == 'es') ? 1 : 0,
        endDate: '0d'
    });
});
</script>
<div class="container">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><?=$TITULO?></h3>
    </div>
    <div class="panel-body">
      <form method="POST">
        <div class="row">
          <div class="col-md-2 col-md-offset-1 col-lg-offset-1 col-sm-4 col-xs-6">
            <div class="form-group">
              <label for="data_inicial"><?=traduz("Data Inicial")?></label>
              <div class="input-group date">
                <input type="text" class="form-control" name="data_inicial" placeholder="<?=traduz("Data Inicial")?>" value="<?=$_POST['data_inicial']?>">
                <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
              </div>
            </div>
          </div>
          <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="form-group">
              <label for="data_final"><?=traduz('Data Final')?></label>
              <div class="input-group date">
                <input type="text" class="form-control" name="data_final" placeholder="<?=traduz('Data Final')?>" value="<?=$_POST['data_final']?>">
                <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 col-xs-6">
            <label><?=traduz('Arquivo CSV')?></label>
            <div class="form-group form-inline-group">
              <p>
                  <label class="checkbox-inline"
                   data-toggle="popover" data-placement="top" data-html="true"
                  data-trigger="hover" data-content="Enviar e-mail com o CSV para <code><?=$login_email?></code>">
                    <input type="checkbox" data-onstyle="info" data-toggle="toggle" data-on="Sim" data-off="Não" <?=$enviar=='t' ? 'checked="true"':''?> name="send_email" value="t"> Enviar por E-mail
                  </label>
              </p>
              <p>
                  <label class="checkbox-inline"
                   data-toggle="popover" data-placement="left" data-html="true"
                  data-trigger="hover" data-content="Agrupa os registros por atendente, separando os dados de cada um para melhor localização.">
                    <input type="checkbox" data-toggle="toggle" data-onstyle="info" data-on="Sim" data-off="Não" <?=$separar=='t' ? 'checked="true"':''?> name="separar" value="t"> Agrupar
                  </label>
              </p>
            </div>
          </div>
          <div class="col-md-2 col-sm-4 col-xs-6">
            <label><?=traduz('Tipo de Relatório')?></label>
            <div class="form-group form-inline-group">
            <p class="form-group form-inline-group"
           data-toggle="popover" data-html="true" data-placement="right" data-trigger="hover"
          data-content="<dl><dt>Analítico:</dt><dd>Apresenta os dados detalhados, implica muitos registros, e é normalmente
                a origem para o relatório sintético.</dd>
            <dt>
                <dt>Sintético</dt>
                <dd>Mostra dados em resumo, com somatórias, totalizadores e percentuais dos dados do relatório analítico.</dd>
            </dt></dl>">
              <input id="tipo_relatorio" type="checkbox" name="analitico" <?=$analitico ? 'checked="true"':''?> data-toggle="toggle" data-onstyle="info" data-on="Analítico" data-off="Sintético">
              </p>
                <input id="tot" data-toggle="toggle" data-onstyle="info" data-on="Totalizar" data-off="Não totalizar" <?=$totalizar ? 'checked="true"':''?> type="checkbox" name="totalizar" value="t">
            </div>
          </div>
        </div>
        <div class="row-fluid text-center">
          <div>
            <button type="submit" <?=(count($_POST) > 0)?'disabled':''?>
                   class="btn btn-primary"><?=traduz('Gerar Relatório')?></button>
            <?php if (count($_POST)): ?>
            <button type="button" id="clear-form" class="btn btn-warning"><?=traduz('Limpar')?></button>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php if (isset($dados) and count($dados)): ?>
<div id="tbl_result" class="container">
    <ul class="nav nav-tabs" role="tablist">
        <li class="dropdown" role="presentation">
        <a href="#" class="dropdown-toggle" id="myTabDrop1" data-toggle="dropdown" aria-controls="myTabDrop1-contents"
           aria-expanded="false">Atendente HD <span class="caret"></span></a>
            <ul class="dropdown-menu">
<?php
foreach ($dataTable as $user_login => $table) {
    // $grouped_data = array_group_by($table_data, 'chamado');
    $userLogin = str_replace(' ', '_', strtolower(retira_acentos($user_login)));
    $htmlTABs       .= "\t\t\t\t<li class='menu-item' role='presentation'><a href='#$userLogin' role='tab' data-toggle='tab'>$user_login</a></li>\n";
    $htmlTabContent .= "\t\t\t<div id='$userLogin' class='tab-pane' role='tab-panel'>\n".
        array2table($table, $user_login).
        "\t\t\t</div>\n";
}
?>
        <?=$htmlTABs?>
            </ul>
        </li>
    </ul>
  <!-- Tab panes -->
    <div class="tab-content">
        <?=$htmlTabContent?>
    </div>
</div>
<div id="aux" class="modal" role="dialog">
  <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header">
              <h4 class="modal-title">Detalhe das atividades do HD</h4>
          </div>
          <div class="modal-body"></div>
      </div>
  </div>
</div>
<?php endif; ?>
<script>
$(function() {
    $("#clear-form").click(function() {
        // $('input:checkbox,input:radio').removeAttr('checked');
        $(":checkbox").bootstrapToggle('off');
        $('input[type=text]').datepicker('clearDates').val('');
        $('.btn-primary').removeAttr('disabled');
    });

    $("#tipo_relatorio").change(function() {
        var chk = $(this).prop('checked');
        $("#tot").bootstrapToggle(chk?'disable':'enable');
    });

    $('input').change(function() {$('.btn-primary').removeAttr('disabled');});

    $('form').submit(function() {
        $("#tbl_result").fadeOut(400);
        $("button.close").click();
        $('button.btn-primary').attr('disabled', true);
    });

    $(document).on('click', '.details', function (event) {
        var el = $(event.currentTarget); // Button that triggered the modal
        var lnk = $(el).data('href');
        var modal = $(el).data('target');
        $(modal + ' .modal-body').load(lnk);
        $(modal).modal('show');
    });

    if ($('.btn-warning').length > 0 && window.document.visibilityState !== 'visible') {
        NotificationTC.dispatch('Processo finalizado', $(".alert[role=alert]").text());
    }
});
</script>
<?
include 'rodape.php';
// vim: set et ts=4 sw=4 tw=120 cc=120:
