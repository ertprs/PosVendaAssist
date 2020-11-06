<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once "../plugins/fileuploader/TdocsMirror.php";
include_once "../classes/mpdf61/mpdf.php";

define('BS3', true);

$TITULO    = 'Clientes Telecontrol';

$TdocsMirror = new TdocsMirror();
$pdf         = new mPDF;

if (isset($_POST['gerar_pdf'])) {

  $sqlMarcas = "SELECT tdocs_id, situacao, tbl_fabrica.nome, tbl_fabrica.fabrica FROM tbl_tdocs JOIN tbl_fabrica USING(fabrica) WHERE referencia = 'marcatc' and contexto = 'marcatc' AND situacao = 'ativo' ORDER BY tbl_fabrica.nome";
  $resMarcas = pg_query($con, $sqlMarcas);

  $body = "<table style='width: 90%;'>";

  $x = 0;
  while ($dadosMarcas = pg_fetch_object($resMarcas)) {

    $dadosArquivo = $TdocsMirror->get($dadosMarcas->tdocs_id);

    if ($x % 6 == 0) {
      $body .= "<tr>";
    }

      $body .= '<td style="text-align: center;">
                  <img style="vertical-align: middle;max-width: 70px;max-height: 35px;width: auto;margin-bottom: 10px;" src="'.$dadosArquivo['link'].'" />
                </td>';

    if ($x % 6 == 0) {
      $body .= "</tr>";
      $x     = 0;
    }

    $x++;
  }

  $body .= "</table>";

  $arquivo = utf8_encode($body);
  $caminho = "../admin/xls/logomarcas_".date("Ymdhis").".pdf";

  $pdf->allow_html_optional_endtags = true;
  $pdf->setAutoTopMargin = 'stretch';
  $pdf->SetTitle("Marcas Telecontrol ");
  $pdf->SetDisplayMode('fullpage');
  $pdf->AddPage('L');
  $pdf->WriteHTML($arquivo);
  $pdf->Output($caminho,'F');

  exit($caminho);
}

if (isset($_POST['ativo_inativo'])) {

  $ativo_inativo = $_POST['ativo_inativo'];
  $tdocs_id = $_POST['tdocs_id'];

  if (!empty($tdocs_id)) {

    $sqlUpd = "UPDATE tbl_tdocs SET situacao = '{$ativo_inativo}' WHERE tdocs_id = '{$tdocs_id}'";
    pg_query($con, $sqlUpd);

  }

  exit;
}

if (isset($_POST['deletar_logo'])) {

  $tdocs_id = $_POST['tdocs_id'];

  if (!empty($tdocs_id)) {

    $sqlDel = "DELETE FROM tbl_tdocs WHERE tdocs_id = '{$tdocs_id}'";
    pg_query($con, $sqlDel);

  }

  exit;
}

if (isset($_POST['submit_dados'])) {

    $anexo              = $_FILES['anexo'];
    $fabric             = $_POST['fabricas'];
    $ativo_logo         = ($_POST['ativo_fabrica']) ? 'ativo' : 'inativo';
    $tdocs_id_anterior  = $_POST['tdocs_id_alterar'];

    foreach ($anexo as $key => $value) {
        if (empty($value)) {
            unset($anexo[$key]);
        }
    }

    if (!empty(($anexo['tmp_name']))) {

          $retorno = $TdocsMirror->post($anexo['tmp_name']);

          foreach ($retorno[0] as $key => $val) {

            $obs = json_encode(array(
                "acao"     => "anexar",
                "filename" => $anexo['name'],
                "filesize" => $anexo['size'],
                "data"     => date("Y-m-d\TH:i:s"),
                "fabrica"  => $fabric,
                "page"     => "helpdesk/adm_clientes_tc.php",
                "typeId"   => "marcatc"
            ));

            if (!empty($tdocs_id_anterior)) {
              $sql = "UPDATE tbl_tdocs 
                      SET tdocs_id = '".$val['unique_id']."', 
                      obs = '[{$obs}]', 
                      situacao = '{$ativo_logo}'
                      WHERE tdocs_id = '$tdocs_id_anterior'";
            } else {
              $sql = "INSERT INTO tbl_tdocs (obs, tdocs_id, fabrica, situacao, referencia, referencia_id, contexto) VALUES ('[{$obs}]', '".$val['unique_id']."', {$fabric}, '{$ativo_logo}','marcatc',{$fabric}, 'marcatc')";
            }

            pg_query($con, $sql);

          }

    } else if (!empty($tdocs_id_anterior)) {

      $sql = "UPDATE tbl_tdocs
              SET situacao = '{$ativo_logo}'
              WHERE tdocs_id = '$tdocs_id_anterior'";
      pg_query($con, $sql);

    }
}

include "menu.php";

?>
<script src='plugins/jquery.form.js'></script>
<script>
  $(function(){

      $(".altera_fabrica").click(function(){

        let fabrica      = $(this).data("fabrica");
        let fabrica_nome = $(this).text();
        let img_src      = $(this).data("src");

        $("#gravar").show();
        $("#nome_fabrica").val(fabrica_nome);
        $("#fabrica_id").val(fabrica);
        $(window).scrollTop();

      });

      $("#exibir_inativos").click(function(){
        if ($(this).is(":checked")) {
          $(".inativo").show();
        } else {
          $(".inativo").hide();
        }
      });

      $(".imagens").contextmenu(function(e){

        e.preventDefault();

        $(".opcoes").hide();
        $(this).find(".opcoes").show();

      });

      $(".imagens").click(function(){

        let fabrica = $(this).data("fabrica");
        let nome    = $(this).data("nome");
        let ativo   = ($(this).data('ativo') == 'ativo') ? true : false;
        let tdocsid = $(this).data("tdocsid");

        $("select[name=fabricas] > option").prop("selected", false).hide();
        $("select[name=fabricas] > option[value="+fabrica+"]").prop({
          "selected" : true,
          "readonly" : true
        });

        $("#ativo_fabrica").prop("checked", ativo);

        $("#tdocs_id_alterar").val(tdocsid);
        $("#gravar").text("Alterar");

        $(window).scrollTop(0);

      });

      $(document).click(function(){
        $(".opcoes").hide();
      });

      $(".inativar_ativar_logo").click(function(){

        var opcoes = $(this).closest(".imagens");

        if ($(this).text() == "Inativar") {
          var ativo_inativo = "inativo"
        } else {
          var ativo_inativo = "ativo";
        }

        var tdocs_id = $(this).data("tdocsid");

        $.ajax({
              async: false,
              url : window.location,
              type: "POST",
              data: {
                  ativo_inativo : ativo_inativo,
                  tdocs_id : tdocs_id
              },
              complete: function(data){

                if (ativo_inativo == 'inativo') {
                  $(opcoes).hide("slow");
                } else {
                  $(opcoes).show("slow");
                }

              }
          });

      });

      $(".deletar_logo").click(function(){

        var tdocs_id = $(this).data("tdocsid");

        var opcoes = $(this).closest(".imagens");

        $.ajax({
              async: false,
              url : window.location,
              type: "POST",
              data: {
                  deletar_logo : true,
                  tdocs_id : tdocs_id
              },
              complete: function(data){
                  $(opcoes).hide("slow");
              }
          });

      });

      document.querySelector("html").classList.add('js');

      var fileInput  = document.querySelector( ".input-file" ),  
          button     = document.querySelector( ".input-file-trigger" ),
          the_return = document.querySelector(".file-return");
            
      button.addEventListener( "keydown", function( event ) {
          if ( event.keyCode == 13 || event.keyCode == 32 ) {  
              fileInput.focus();  
          }  
      });
      button.addEventListener( "click", function( event ) {
         fileInput.focus();
         return false;
      });  
      fileInput.addEventListener( "change", function( event ) {  
          the_return.innerHTML = this.value;  
      });  

      $("#gerar_pdf").click(function(){

        $.ajax({
            async: true,
            url : window.location,
            type: "POST",
            data: {
                gerar_pdf : true
            },
            beforeSend: function () {
                $("#gerar_pdf").text("..Gerando arquivo").prop("disabled", true);
            },
            complete: function (data) {
              window.open(data.responseText, "_blank");
              $("#gerar_pdf").text("Gerar PDF").prop("disabled", false);
            }
        });

      });

  });

</script>

<style>

.table {
  width: 50%;
  margin-left: 25%;
}

.table > tbody > tr > td,
.table > tbody > tr > th {
  vertical-align: middle;
}

.table > thead > tr > th {
  vertical-align: middle;
  text-align: center;
  background-color: #2b2c50;
  color: white;
}

.table > tbody > tr > td:first-of-type {
  font-weight: bold;
  text-align: left;
}

.imagem {
  max-width: 115px;
  max-height: 75px;
  width: auto;
}

.imagens {
  text-align: center;
  margin-bottom: 0;
  padding: 0px !important;
  height: 90px;
  line-height: 150px;
}

.input-file-container {
  position: relative;
  width: 225px;
} 
.js .input-file-trigger {
  display: block;
  padding: 14px 45px;
  background: #39D2B4;
  color: #fff;
  font-size: 1em;
  transition: all .4s;
  cursor: pointer;
}
.js .input-file {
  position: absolute;
  top: 0; left: 0;
  width: 225px;
  opacity: 0;
  padding: 14px 0;
  cursor: pointer;
}
.js .input-file:hover + .input-file-trigger,
.js .input-file:focus + .input-file-trigger,
.js .input-file-trigger:hover,
.js .input-file-trigger:focus {
  background: #34495E;
  color: #39D2B4;
}

.file-return {
  margin: 0;
}
.file-return:not(:empty) {
  margin: 1em 0;
}
.js .file-return {
  font-style: italic;
  font-size: .9em;
  font-weight: bold;
}
.js .file-return:not(:empty):before {
  content: "Arquivo selecionado: ";
  font-style: normal;
  font-weight: normal;
}

.inativar_ativar_logo, .deletar_logo {
  width: 100%;
  height: 50%;
  text-align: center;
  color: white;
  background-color: #2b2c50;
  border: white 1px solid;
  font-weight: bolder;
  line-height: 25px;
}

.inativar_ativar_logo:hover, .deletar_logo:hover {
  background-color: black;
  cursor: pointer;
}

</style>
    <div class="container">
      <div class="panel panel-default" style="width: 90%;margin-left: 5%;">
        <div class="panel-heading">
        <h3 class="panel-title"><?=$TITULO?></h3>
        </div>
        <div class="panel-body">
          <form name="form_altera" method="POST" enctype="multipart/form-data">
            <input type="hidden" value="submit" name="submit_dados" />
            <input type="hidden" value="" name="tdocs_id_alterar" id="tdocs_id_alterar" />
            <div class="row">
              <div class="col-md-1"></div>
              <div class="col-md-5">
                <?php
                $sqlFab = "SELECT nome, fabrica FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
                $resFab = pg_query($con, $sqlFab);

                ?>Fábrica: <span style="color: red;">*</span><br />
                <select class="form-control col-md-3" name="fabricas">
                  <option value="">Selecione</option>
                  <?php
                  while ($dadosFab = pg_fetch_object($resFab)) { ?>
                    <option value="<?= $dadosFab->fabrica ?>"><?= $dadosFab->nome ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (<?= $dadosFab->fabrica ?>)</option>
                  <?php
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-5">
                <br />
                  <center>
                    <input type="checkbox" name="ativo_fabrica" id="ativo_fabrica" /> Ativo
                  </center>
              </div>
            </div>
            <br />
            <div class="row">
              <center>
                  <div class="input-file-container">  
                    <input class="input-file" id="my-file" type="file" name="anexo" />
                    <label tabindex="0" for="my-file" class="input-file-trigger">Selecionar Arquivo</label>
                  </div>
                <p class="file-return"></p>
              </center>
            </div>
            <br />
            <div class="row">
              <center>
                <button class="btn btn-default" id="gravar">Gravar</button>
              </center>
             </div>
          </form>
        </div>
      </div>
        <center>
            (Clique sobre a imagem com o botão esquerdo para exibir as opções)<br /><br />
            <label>
              <input type="checkbox" id="exibir_inativos" /> Exibir logos inativas
            </label>
          <br /><br />
          <button class="btn btn-danger" id="gerar_pdf">Gerar PDF</button>
        </center>
        <br />
        <div class="row row-fluid" id="exibir_logos">
          <?php
           $sqlMarcas = "SELECT tdocs_id, situacao, tbl_fabrica.nome, tbl_fabrica.fabrica FROM tbl_tdocs JOIN tbl_fabrica USING(fabrica) WHERE referencia = 'marcatc' and contexto = 'marcatc' ORDER BY tbl_fabrica.nome";
           $resMarcas = pg_query($con, $sqlMarcas);

           while ($dadosMarcas = pg_fetch_object($resMarcas)) {

            if (empty($dadosMarcas->tdocs_id)) {
              continue;
            }

            $dadosArquivo = $TdocsMirror->get($dadosMarcas->tdocs_id);

            $displayImg = ($dadosMarcas->situacao == 'inativo') ? 'display: none;' : '';

            ?>
            
            <div style="<?= $displayImg ?>cursor: pointer;" class='col-md-2 imagens <?= $dadosMarcas->situacao ?>' data-tdocsid="<?= $dadosMarcas->tdocs_id ?>" data-nome="<?= $dadosMarcas->nome ?>" data-fabrica="<?= $dadosMarcas->fabrica ?>" data-ativo="<?= $dadosMarcas->situacao ?>">
              <div class="opcoes" style='display: none;width: 100px;height: 50px;position: absolute;top: -10px;'>
                <div class="inativar_ativar_logo" data-tdocsid="<?= $dadosMarcas->tdocs_id ?>" style=""><?= ($dadosMarcas->situacao == 'inativo') ? 'Ativar' : 'Inativar' ?></div>
                <div class="deletar_logo" data-tdocsid="<?= $dadosMarcas->tdocs_id ?>">Deletar</div>
              </div>
              <img style="vertical-align: middle;" src='<?= $dadosArquivo['link'] ?>' class='imagem' />
            </div>

           <?php
           }
           ?>
        </div>
        <br />
    </div>
<?php
include "rodape.php"
?>
