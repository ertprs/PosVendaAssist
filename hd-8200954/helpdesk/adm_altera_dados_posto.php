<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

define('BS3', true);

$tDocs = new TDocs($con, $login_fabrica);

if (isset($_POST["submit_dados"])) {
  $cnpj = $_POST["posto_cnpj"];
  $descricao_posto = $_POST["descricao_posto"];

  if (empty($cnpj)) {
    $msg_erro[] = "Preencha o CNPJ";
  }

  if (count($msg_erro) == 0) {
    $sql_posto = "SELECT tbl_posto.nome,tbl_posto.ie,tbl_posto.posto
            FROM tbl_posto
            WHERE tbl_posto.cnpj = '$cnpj'";
    $res_posto = pg_query($con, $sql_posto);
  }
}

if (isset($_POST["btn_alterar"])) {
  $anexo           = $_FILES["anexo"];
  $razao_depois    = $_POST["razao_depois"];
  $ie_depois       = $_POST["ie_depois"];
  $posto           = $_POST["id_posto"];

  if (empty($_FILES["anexo"]["tmp_name"])) {
    $msg_erro[] = "O anexo é obrigatório";
  }

  if (empty($razao_depois)) {
    $msg_erro[] = "Preencha a Razão Social";
  }

  if (count($msg_erro) == 0) {

    $sql = "UPDATE tbl_posto
            SET nome = '{$razao_depois}',
                ie   = '{$ie_depois}'
            WHERE posto = $posto;
            ";

    $auditor = new AuditorLog();
    $auditor->retornaDadosSelect("SELECT posto,nome,ie
                                    FROM tbl_posto
                                    WHERE posto = {$posto}");

    $res = pg_query($con, $sql);

    $erro_banco =  pg_last_error($res);

    if (empty($erro_banco)) {
      $tDocs->setContext('posto', 'comprovanterf');

      $anexoID = $tDocs->uploadFileS3($anexo, $posto, true);
      $arquivo_nome = $tDocs->sentData;

      if (!$anexoID) {
        $msg_erro[] = "Erro ao enviar anexo! " . $tDocs->error . $tDocs->erro;
      }

      $auditor->retornaDadosSelect()->enviarLog('update', 'tbl_posto', "$login_fabrica*$posto");
      $msg_success[] = "Dados Alterados com sucesso!";
    } else {
      $msg_erro[] = "Houve um erro ao alterar os dados cadastrais.";
    }
  }
}

$TITULO    = 'Alteração de postos';
$bs_extras = array('shadowbox_lupas');
include "menu.php";
?>
<script>
    $(function() {
      Shadowbox.init();

      $("#lupa_cnpj").click(function() {
        var valor =  $("#cnpj_posto").val();

        Shadowbox.open({
                content: "../admin/posto_lupa_new.php?parametro=cnpj&valor="+valor+"&telecontrol=t",
                player: "iframe",
                title:  "Postos",
                width:  800,
                height: 500
          });
      });

      $("#lupa_descricao").click(function() {
        var valor =  $("#descricao_posto").val();

        Shadowbox.open({
                content: "../admin/posto_lupa_new.php?parametro=nome&valor="+valor+"&telecontrol=t",
                player: "iframe",
                title:  "Postos",
                width:  800,
                height: 500
          });
      });

      $("#log").click(function() {

        Shadowbox.open({
                content: "../admin/posto_lupa_new.php?parametro=nome&valor="+valor+"&telecontrol=t",
                player: "iframe",
                title:  "Postos",
                width:  800,
                height: 500
          });
      });
    });

    function retorna_posto(retorno){
        $("#cnpj_posto").val(retorno.cnpj);
        $("#descricao_posto").val(retorno.nome);
        $("#inscricao_estadual").val(retorno.ie);

        $("form[name=form_altera]").submit();
    }
</script>
<style>
.table > tbody > tr > td,
.table > tbody > tr > th {
  vertical-align: middle;
  text-align: center;
}

.table > tbody > tr > td:first-of-type {
  font-weight: bold;
  text-align: left;
}
</style>
    <div class="container">
      <div class="panel panel-default">
        <div class="panel-heading">
        <h3 class="panel-title"><?=$TITULO?> &Rang; Altera cadastro de postos </h3>
        </div>
        <div class="panel-body">
          <form name="form_altera" method="POST">
            <input type="hidden" value="submit" name="submit_dados" />
            <div class="row">
              <div class="col-md-4 col-md-offset-2 col-sm-4">
                <div class="form-group">
                  <label for="CNPJ">CNPJ</label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="cnpj_posto" name="posto_cnpj" placeholder="Ex: 00000000000000" value="<?= $cnpj ?>">
                    <span class="input-group-addon" id="lupa_cnpj"><i class="glyphicon glyphicon-search"></i></span>
					<input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-sm-4">
                <div class="form-group">
                  <label for="nome_posto">Nome do Posto</label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="descricao_posto" name="descricao_posto" placeholder="Ex: Teste" value="<?=$descricao_posto?>">
                    <span class="input-group-addon lupa" id="lupa_descricao"><i class="glyphicon glyphicon-search"></i></span>
					<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
             </div>
          </form>
        </div>
      </div>
        <?php
        if (isset($_POST["submit_dados"]) && pg_num_rows($res_posto) > 0) {

          $posto = pg_fetch_result($res_posto, 0, "posto");
          $nome  = pg_fetch_result($res_posto, 0, "nome");
          $ie    = pg_fetch_result($res_posto, 0, "ie");

          $ie    = $ie ? : 'Sem IE cadastrada';
        ?>
        <form enctype="multipart/form-data" method="POST">
          <input type="hidden" name="id_posto" value="<?= $posto ?>" />
          <table data-toggle="table" data-stripe="true" data-sort-name="Fábrica" data-pagination="true" data-page-size="20" class="table table-condensed table-bordered table-hover">
            <thead>
              <tr style="text-align: center">
                <th>Info. Posto</th>
                <th>Atual</th>
                <th>Alterar</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Razão Social:</td>
                <td><?= $nome ?></td>
                <td>
                  <div class="input-control col-md-12">
                    <input style="text-align: center;" type="text" class="form-control" id="razao_depois" name="razao_depois" value="<?= $nome ?>">
                  </div>
                </td>
              </tr>
              <tr>
                <td>Inscrição Estadual:</td>
                <td><?=$ie?></td>
                <td>
                  <div class="input-control col-md-6">
                    <input style="text-align: center;font-family: monospace" type="text" class="form-control col-md-3" id="ie_depois" name="ie_depois" value="<?= $ie ?>">
                  </div>
                </td>
              </tr>
              <tr>
                <td>Anexo:</td>
                <td>
                   <?php
                      $tDocs->setContext('posto','comprovanterf');
                      $info = $tDocs->getDocumentsByRef($posto)->attachListInfo;

                      $vAnexo = array_shift($info);

                      if (!empty($vAnexo['link'])) {
                   ?>
                   <a style="width: 70%;" class="btn btn-info" target="_blank" href="<?= $vAnexo['link'] ?>" />Visualizar Anexo</a>
                  <?php
                      } else {
                  ?>
                    Sem Anexo
                  <?php
                      }
                  ?>
                </td>
                <td>
                  <input type="file" name="anexo" id="anexo" />
                </td>
              </tr>
            </tbody>
          </table>
          <br />
          <div class="row">
            <div class="col-md-4 col-sm-2"></div>
            <div class="col-md-5 col-sm-5">
              <a rel='shadowbox' href='../admin/relatorio_log_alteracao_new.php?parametro=tbl_posto&id=<?= $posto ?>' name="btnAuditorLog">
                <button id='log' class='btn btn-warning'>Visualizar LOG</button>
              </a>
              <input type="submit" class="btn btn-primary" value="Alterar Dados do Posto" name="btn_alterar" />
            </div>
          </div>
      </form>
        <?
        }
        ?>
      </div>
    </div>
<?php
include "rodape.php"
?>
