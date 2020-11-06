<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once '../admin/funcoes.php';
include_once 'mlg_funciones.php';

define('BS3', true); // Esta tela utiliza o novo menu e layout Bootstrap 3

/**
 * Status 'Ap.Requisitos' data envio requisitos
 * HD.Comentario 'MENSAGEM AUTOMÁTICA - REQUISITOS APROVADOS EM': aprovação de requisitos
 * HD.data_envio_aprovacao envío do orçamento
 * HD.comentario 'MENSAGEM AUTOMÁTICA - HORA DE DESENVOLVIMENTO APROVADO'
 * HD.data_aprovacao Data aprovação do orçamento.
 */
$array_status_hd = array(
    'Análise'         => array('Novo', 'Aberto', 'Análise', 'Previsao Cadastrada', 'Orçamento', 'Ap.Requisitos'),
    'Requisitos'      => array('Requisitos', 'Pré-Análise', 'Previsao Cadastrada'),
    'Teste'           => array('Validação', 'Correção', 'ValidaçãoHomologação', 'EfetivaçãoHomologação',),
    'Desenvolvimento' => array('Aguard.Execução', 'Execução',),
    'Implementação'   => array('Documentação', 'Efetivação', 'Resolvido'),
    'Aguardando Resposta' => array('Aguard.Admin', 'Parado'),
    /*
    'Outros' => array('Cancelado', 'Novo', 'Resolvido', 'Suspenso',),
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

$tableAttrs = array(
    'tableAttrs' => ' nowrap class="table table-condensed table-bordered table-hover table-striped "' . "
           data-toolbar='#toolbar'
           data-search='true'
           data-show-toggle='true'
           data-show-columns='true'
           data-show-export='true'
           data-minimum-count-columns='2'
           data-show-pagination-switch='true'
           data-pagination='true'
           data-id-field='hd'
           data-page-list='[10, 25, 50, 100, ALL]'
           data-show-footer='false'
           id='results'"
);

$TITULO = 'Relatório Sintético de Atendimentos';
$MAX_INTERVAL = 366;

if (count($_POST)) {
    $data_ini       = is_date(getPost('data_inicial'));
    $data_fim       = is_date(getPost('data_final'));
    $enviar         = $_POST['send_email'] == 't';
    $tipo_relatorio = getPost('tipo_relatorio');
    $fabrica        = getPost('fabricante');

    if (!$data_ini or !$data_fim) {
        if (!$data_ini and !$data_fim) {
            if (date('d') >= 10) {
                $data_ini = $data_ini ? : date('Y-m-01', strtotime('-6 month'));
                $data_fim = $data_fim ? : date('Y-m-d');
            } else {
                $data_ini = $data_ini ? : date('Y-m-01', strtotime('-6 month'));
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
    }

    try {

        if (date_interval($data_ini, $data_fim, 'd') > $MAX_INTERVAL)
            throw new Exception("Intervalo entre datas deve ser de no máximo $MAX_INTERVAL dias.");
        //  $data_fim - $data_ini = ".date_interval($data_ini, $data_fim, 'd')

        pg_query($con, "SET DateStyle TO SQL, DMY");

        $where = array(
            'hd.data' => "$data_ini::$data_fim 23:59:59",
            'fabrica_responsavel' => 10,
            'resolvido!' => null
        );
        if ($fabrica)
            $where['fabrica'] = $fabrica;
        else
            throw new Exception("Deve selecionar um fabricante!");

        $sql = "
            SELECT HDs.hd,
                   FullHD.data,
                   FullHD.resolvido,
                   FullHD.titulo,
                   HDtipo.descricao,
                   (SELECT data_requisito_aprova
                      FROM tbl_hd_chamado_requisito
                     WHERE hd_chamado = HDs.hd
                     ORDER BY data_requisito_aprova DESC
                     LIMIT 1
                   ) AS data_aprova_requisito,
                   FullHD.data_envio_aprovacao,
                   FullHD.data_aprovacao,
                   FullHD.hora_faturada,
                   HDs.status_interacao,
                   HDs.hora,
                   HDs.hora_fim,
                   HDs.time_diff
              FROM (
                    SELECT HDi.hd_chamado AS hd,
                           MIN(HDi.data) AS hora,
                           MAX(HDi.data) AS hora_fim,
                           MAX(HDi.data) - MIN(HDi.data) AS time_diff,
                           CASE fn_status_hd_item(hd_chamado, HDi.data)
                                $statusCASE
                                ELSE
                                CASE WHEN SUBSTR(comentario, 23, 23) = 'REQUISITOS APROVADOS EM'
                                     THEN 'Aprova Req.'
                                     ELSE fn_status_hd_item(hd_chamado, HDi.data)
                                END
                           END AS status_interacao
                      FROM tbl_hd_chamado_item AS HDi
                      JOIN tbl_hd_chamado HD USING (hd_chamado)
                 LEFT JOIN tbl_hd_chamado_requisito AS HDr USING(hd_chamado)
                                 WHERE " . sql_where($where) . "
                     GROUP BY HDi.hd_chamado, HDi.data, comentario
                     ORDER BY HDi.hd_chamado, HDi.data
                   ) AS HDs
              JOIN tbl_hd_chamado   FullHD ON HDs.hd = FullHD.hd_chamado
         LEFT JOIN tbl_backlog_item BLogIt USING(hd_chamado)
              JOIN tbl_tipo_chamado HDtipo USING(tipo_chamado)
            WINDOW chamados AS (
                PARTITION BY hd, status_interacao
                    ORDER BY hora)
             ORDER BY hd_chamado, hora";

        // file_put_contents("/var/www/test/analitic_data.php", PHP_TAG . PHP_EOL . '$sql = '.$sql. PHP_EOL);

        $res = pg_query($con, $sql);

        // pre_echo($sql, 'CONSULTA', false);
        // print(array2csv($res, '|', false));

        if (!is_resource($res)) {
            throw new Exception('Erro durante a consulta.<p>'.
                pg_last_error($con).'</p><pre>'.$sql.'</pre>');
        }

        if (!pg_num_rows($res)) {
            throw new Exception('Sem resultados para o período solicitado.');
        }

        // Agrupando por HD
        $dados = array();
        $i     = 0;
        $hd    = null;

        while ($row = pg_fetch_row($res, $i++)) {
            list (
                $new_hd, $data_abertura, $data_resolvido, $titulo, $tipo,
                $data_aprova_requisito, $data_orcamento, $data_aprova_orcamento,
                $hora_faturada, $new_atividade, $new_hora_ini, $new_hora_fim, $tdiff
            ) = $row;
            $proxima_atividade = pg_fetch_result($res, $i, 7); // $i já incrementou...

            if ($new_hd !== $hd) {
                // pre_echo($row, "Registro $i");
                if ($new_atividade == null or $new_atividade == 'Novo') {
                    $new_atividade = 'Análise';
                    $new_hora_fim  = $data_abertura;
                }

                $dados[$new_hd] = array(
                    'HD'                  => $new_hd,
                    'Título'              => $titulo,
                    'Tipo'                => $tipo,
                    'Abertura'            => is_date($data_abertura, 'ISO', 'EUR'),
                    'Resolvido'           => is_date($data_resolvido, 'ISO', 'EUR'),
                    'Análise'             => null,
                    'Requisitos'          => null,
                    'Aprova Req.'         => is_date($data_aprova_requisito, 'ISO', 'EUR'),
                    'Horas Orçamento'     => $hora_faturada ? "$hora_faturada h" : null,
                    'Data Orçamento'      => is_date($data_orcamento, 'ISO', 'EUR'),
                    'Aprova Orçamento'    => is_date($data_aprova_orcamento, 'ISO', 'EUR'),
                    'Desenvolvimento'     => null,
                    'Teste'               => null,
                    'Implementação'       => null,
                    // 'Aguardando Resposta' => array()
                );
                $hd = $new_hd;
            }

            // último registro da atividade
            if ($new_hd == $hd and $new_atividade == 'Aguardando Resposta') {
                continue;
                $dados[$new_hd]['Aguardando Resposta'] = is_date($new_hora_ini, 'ISO', 'EUR') . ' - ' . is_date($new_hora_fim, 'ISO', 'EUR');
            }

            // registros da mesma atividade do mesmo HD sem ser a 1ª nem a última
            if ($new_hd == $hd and $new_atividade == $atividade and $atividade == $proxima_atividade)
                continue;

            if ($new_hd == $hd and $new_atividade !== $proxima_atividade) {
                $dados[$new_hd][$new_atividade] = is_date($new_hora_fim, 'ISO', 'EUR');
            }

            $atividade = $new_atividade;
        }

        if (count($dados)) {
            $msg_success[] = "Relatório gerado!";
            $dataTable = $dados;
            foreach ($dados as $hd => $row)
                $dataTable[$hd]['HD'] = createHTMLLink(
                    "adm_chamado_detalhe.php?hd_chamado=$hd&consultar=sim", $hd,
                    " title='Consultar HD $hd &ndash; $titulo' target='new'") . '<br />' .
                    "<button data-href='adm_rae.php?modal=1&hd_chamado=$hd&fabrica=$fabrica' class='details btn btn-default btn-xs' data-target='#aux'>Detalhes</button>";
        }

        // Gerando o CSV se foi solicitado o envio de E-mail
        if ($enviar == 't' and count($dataTable)) {
            require_once(APP_DIR . DIRECTORY_SEPARATOR . 'class/communicator.class.php');
            $mail = new TcComm('smtp@posvenda', 'helpdesk@telecontrol.com.br');

            $mail->setEmailDest($login_email)
                ->setEmailSubject("Relatório de SLA por Chamado ({$_POST['data_inicial']} - {$_POST['data_final']})")
                ->setEmailBody(
                    "<p>Seguem os dados solicitados às ".is_date('agora', 'ISO', 'EUR') . '</p><pre>' .
                    array2csv($dados, ';', true) .
                    '</pre><p>&nbsp;</p>')
                ->sendMail();
            // file_put_contents('/var/www/test/atendentes.csv',array2csv($csv));
            $msg_success[] = "Relatório encaminhado para seu endereço de e-mail cadastrado.";
        }

        unset($dados);
        // pre_echo($dataTable, 'Processado');
    } catch (Exception $e) {
        $msg_erro = $e->getMessage();
    }
}

$bs_extras = array('datepicker', 'bstable', 'toggle');
include './menu.php';
?>
<div class="container">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title"><?=$TITULO?></h3>
    </div>
    <div class="panel-body">
      <form method="POST">
        <div class="row">
          <div class="col-md-2 col-md-offset-1 col-sm-4 col-xs-6">
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
              <label for="sel-fabricante">Fabricante</label>
              <?php echo array2select('fabricante', 'sel-fabricante', $array_fabricas, $_POST['fabricante'], ' class="form-control"', 'Selecione a Fábrica', true); ?>
          </div>
          <div class="col-md-3 col-sm-6 col-xs-6">
            <label><?=traduz('Arquivo CSV')?></label>
            <div class="form-group form-inline-group">
              <label class="checkbox-inline"
               data-toggle="popover" data-placement="top" data-html="true"
              data-trigger="hover" data-content="Enviar e-mail com o CSV para <code><?=$login_email?></code>">
                <input type="checkbox" data-toggle="toggle"i data-on="Sim" data-off="Não" data-onstyle="info" <?=$enviar=='t' ? 'checked="true"':''?> name="send_email" value="t"> Enviar por E-mail
              </label>
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
        <div class="row"><p>&nbsp;</p></div>
      </form>
    </div>
  </div>
</div>
<?php if (isset($dataTable) and count($dataTable)): ?>
<div id="tbl_result" class="container">
    <!-- <table id="results" class="table table&#45;bordered table&#45;hover"></table> -->
    <?=array2table($dataTable, null, false, true, false)?>
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
        $('input:checkbox,input:radio').removeAttr('checked');
        $('input[type=text]').datepicker('clearDates').val('');
        $('.btn-primary').removeAttr('disabled');
    });
    $("input[name=analitico]").change(function() {
        var chk = $("#s").is(":checked");
        if (chk) {
            $("#grTot").show();
            $("#tot").removeAttr('disabled');
        } else {
            $("#grTot").hide();
            $("#tot").attr('disabled', true);
        }
    });
    $('input,select,:checkbox').change(function() {$('.btn-primary').removeAttr('disabled');});
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
});
</script>
<?
include 'rodape.php';

// vim: set et ts=4 sw=4 tw=120:
