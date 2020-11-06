<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($_POST) {
    $data_ini = is_date(getPost('data_inicial'));
    $data_fim = is_date(getPost('data_final'));

    if (!$data_ini or !$data_fim) {
        if (!$data_ini and !$data_fim) {
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
    }

    try {
        if ($data_ini and $data_ini > is_date('hoje'))
            throw new Exception("A data inicial não pode ser maior do que a data atual.");

        if (date_interval($data_ini, $data_fim, 'd') > 365)
            throw new Exception("O intervalo entre datas não pode ser maior que um ano.");

        if ($data_ini > $data_fim) {
            list($data_ini, $data_fim) = array($data_fim, $data_ini);
        }

        // Já deixa no _POST para recarregar...
        $_POST['data_inicial'] = is_date($data_ini, 'ISO', 'EUR');
        $_POST['data_final']   = is_date($data_fim, 'ISO', 'EUR');

        $sql = "SELECT HD.hd_chamado                      AS \"N. Chamado\",
                       HD.titulo                          AS \"Título\",
                       tbl_fabrica.nome                   AS \"Fábrica\",
                       TO_CHAR(HD.data,'DD/MM/YYYY')      AS \"Data Abertura\",
                       TO_CHAR(HD.resolvido,'DD/MM/YYYY') AS \"Resolvido\"
                  FROM tbl_hd_chamado   AS HD
                  JOIN tbl_fabrica             USING(fabrica)
             LEFT JOIN tbl_backlog_item AS BLi USING(hd_chamado)
                 WHERE ";
        $SEM = pg_query(
            $con,$sql. sql_where(
                array(
                    'BLi.chamado_causador' => null,
                    'HD.data' => "$data_ini::$data_fim 23:59:59",
                    'HD.tipo_chamado' => 5
                )
            )
        );
        $COM = pg_query(
            $con, $sql. sql_where(
                array(
                    'BLi.chamado_causador!' => null,
                    'HD.data' => "$data_ini::$data_fim 23:59:59",
                    'HD.tipo_chamado' => 5
                )
            )
        );

        $sem_causador = pg_num_rows($SEM);
        $com_causador = pg_num_rows($COM);

        $tableAttrs = array(
            'tableAttrs' => 'data-toggle="table" data-undefined-text="&mdash;" data-stripe="true" data-sort-name="Fábrica" data-pagination="true" data-page-size="20" class="table table-condensed table-bordered table-hover table-striped "'
        );
        if (!$sem_causador and !$com_causador)
            $msg_erro[] = "Nenhum resultado encontrado nesse período.";
    } catch (Exception $e) {
        $msg_erro[] = $e->getMessage();
        if (pg_last_error($con))
            $msg_erro[] = pg_last_error($con);

    }
}

define('BS3', true);
$TITULO = 'Relatório de Chamados de Erro';
$bs_extras = array('datepicker', 'bstable');
include "menu.php";
?>
    <script>
    var lang = '<?=$cook_idioma?>' || 'pt-BR';
    $(function() {
        $('.input-group.date').datepicker({
            format: 'dd/mm/yyyy',
            language: 'pt-BR',
            weekStart: (lang == 'es') ? 1 : 0,
            endDate: '0d'
        });
        $("button.btn-warning").click(function() {document.location.href=document.location.href;});
        if ($("#result").length == 1) {
            $("#result tr td:first-of-type").each(function(i,el) {
                var hd = $(el).text();
                var link = Menu.getUrl('hd', {hd_chamado: hd, consultar: 'sim'});
                var lnk = "<a target='_new' href='" + link + "'>"+hd+"</a>";
                $(el).html(lnk);
            });
        }
    });
    </script>
    <div class="container">
      <div class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title"><?=$TITULO?> &Rang; <?=traduz('Parâmetros de Pesquisa')?></h3>
        </div>
        <div class="panel-body">
          <form method="POST">
            <div class="row">
              <div class="col-md-2 col-md-offset-2 col-sm-4">
                <div class="form-group">
                  <label for="data_inicial"><?=traduz("Data Inicial")?></label>
                  <div class="input-group date">
                    <input type="text" class="form-control" name="data_inicial" placeholder="<?=traduz("Data Inicial")?>" value="<?=$_POST['data_inicial']?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2 col-sm-4">
                <div class="form-group">
                  <label for="data_final"><?=traduz('Data Final')?></label>
                  <div class="input-group date">
                    <input type="text" class="form-control" name="data_final" placeholder="<?=traduz('Data Final')?>" value="<?=$_POST['data_final']?>">
                    <span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                  </div>
                </div>
              </div>
              <div class="col-md-2 col-sm-2">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <button type="submit" class="input-group btn btn-primary">Gerar Relatório</button>
                </div>
              </div>
            <?php if (count($_POST)): ?>
              <div class="col-md-2 col-sm-2">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <button type="button" class="input-group btn btn-warning">Limpar</button>
                </div>
              </div>
            <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
<?php if (isset($SEM) and $sem_causador+$com_causador > 0) { ?>
    <div id="result" class="container">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation">
            <a role="tab" data-toggle="tab" href="#sem">Chamados SEM causador <span class="badge"><?=$sem_causador?></span></a>
          </li>
          <li role="presentation">
            <a role="tab" data-toggle="tab" href="#com">Chamados COM causador <span class="badge"><?=$com_causador?></span></a>
          </li>
        </ul>
        <div class="tab-content">
            <div id="sem" class="tab-pane" role="tabpanel">
                <?=array2table($SEM)?>
            </div>
            <div id="com" class="tab-pane" role="tabpanel">
                <?=array2table($COM)?>
            </div>
        </div>
    </div>
<?php
}

include "rodape.php";

