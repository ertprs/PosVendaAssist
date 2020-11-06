<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, auditoria";

include "autentica_admin.php";
include "funcoes.php";

$programa_insert = $_SERVER['PHP_SELF'];

require('../class/email/mailer/class.phpmailer.php');

## INTERAGIR NA OS ##
if ($_POST["interagir"] == true) {

  $interacao = utf8_decode(trim($_POST["interacao"]));
  $os        = $_POST["os"];

  if (!strlen($interagir)) {
    $retorno = array("erro" => utf8_encode("Digite a interação"));
  } else if (empty($os)) {
    $retorno = array("erro" => utf8_encode("OS não informada"));
  } else {

    $select = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $result = pg_query($con, $select);

    if (!pg_num_rows($result)) {
      $retorno = array("erro" => utf8_encode("OS não encontrada"));
    } else {
      $insert = "INSERT INTO tbl_os_interacao
             (programa,os, admin, fabrica, comentario)
             VALUES
             ('$programa_insert',{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
      $result = pg_query($con, $insert);

      if (strlen(pg_last_error()) > 0) {
        $retorno = array("erro" => utf8_encode("Erro ao interagir na OS"));
      } else {
        $retorno = array("ok" => true);
      }
    }
  }
  exit(json_encode($retorno));
}
## FIM INTERAGIR NA OS ##

## CANCELAR OS ##
if($_POST["cancelar"] == true){
  $motivo         = trim($_POST["motivo"]);
  $os             = $_POST["os"];
  $tipo_auditoria = $_POST["tipo_auditoria"];
  $imagem1        = $_FILES["foto1"];

  if(!strlen($motivo)){
    $retorno = array("erro" => utf8_encode("Informe o motivo"));
  }else if(empty($os)){
    $retorno = array("erro" => utf8_encode("OS não informada"));
  }else if(empty($tipo_auditoria)){
    $retorno = array("erro" => utf8_encode("Tipo de auditoria não informado"));
  }else{

    $motivo_valor = "OS cancelada pelo fabricante. Motivo: $motivo ";

    if ($login_fabrica == 131) {
      $motivo_valor = "Auditoria de pedido de peça/produto, motivo: $motivo ";      
    }

    pg_query($con, "BEGIN");

    $insert = "INSERT INTO tbl_os_status(
            os,
            status_os,
            observacao,
            fabrica_status,
            admin
          )VALUES(
            {$os},
            {$tipo_auditoria},
            '{$motivo_valor}',
            {$login_fabrica},
            {$login_admin}
          )";
    $result = pg_query($con, $insert);

    if(!strlen(pg_last_error())){

      $sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin)";
      $res = pg_query($con, $sql);

      if(strlen(pg_last_error()) > 0){
        pg_query($con, "ROLLBACK");
        $retorno = array("erro" => utf8_encode("Erro ao cancelar OS"));
      } else {
        $sql ="UPDATE tbl_os_excluida SET motivo_exclusao = '$motivo' WHERE os = $os ";
        $res = pg_query($con,$sql);
        pg_query($con, "COMMIT");
        $retorno = array("ok" => true);
      }
    } else {
      pg_query($con, "ROLLBACK");
      $retorno = array("erro" => utf8_encode("Erro ao cancelar OS"));
    }
  }

  exit(json_encode($retorno));
}
## FIM CANCELAR OS ##

## RECUSAR OS ##
if ($_POST["recusar"] == true) {
  $motivo         = trim($_POST["motivo"]);
  $os             = $_POST["os"];
  $tipo_auditoria = $_POST["tipo_auditoria"];
  $imagem1        = $_FILES["foto1"];

  if(!strlen($motivo)){
    $retorno = array("erro" => utf8_encode("Informe o motivo"));
  }else if(empty($os)){
    $retorno = array("erro" => utf8_encode("OS não informada"));
  }else if(empty($tipo_auditoria)){
    $retorno = array("erro" => utf8_encode("Tipo de auditoria não informado"));
  }else{
    pg_query($con, "BEGIN");

    $insert = "INSERT INTO tbl_os_status(
            os,
            status_os,
            observacao,
            fabrica_status,
            admin
          )VALUES(
            {$os},
            {$tipo_auditoria},
            'Pedido de peças reprovado pelo fabicante. Motivo: $motivo',
            {$login_fabrica},
            {$login_admin}
          )";
    $result = pg_query($con, $insert);

    if(!strlen(pg_last_error())){

      $upate = "UPDATE tbl_os SET status_checkpoint = 13 WHERE os = $os";
      $resUpdate = pg_query($con, $upate);

      if(strlen(pg_last_error()) > 0){
        pg_query($con, "ROLLBACK");
        $retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
      } else {
        $select = "SELECT posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $result = pg_query($con, $select);

        $os_posto = pg_fetch_result($result, 0, "posto");

        $insert = "INSERT INTO tbl_comunicado (
                fabrica,
                posto,
                obrigatorio_site,
                tipo,
                ativo,
                descricao,
                mensagem
              ) VALUES (
                {$login_fabrica},
                {$os_posto},
                true,
                'Com. Unico Posto',
                true,
                'OS recusada',
                'A OS $os foi <b>recusada</b> pela fábrica na auditoria de Pedido de Peça / Produto. <br />'
              )";
        $result = pg_query($con, $insert);

        if (strlen(pg_last_error()) > 0) {
          pg_query($con, "ROLLBACK");
          $retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
        } else {

          $sql = "SELECT contato_email
                FROM tbl_posto_fabrica
                WHERE posto = $os_posto
                AND fabrica = $login_fabrica";
          $res = pg_query($con,$sql);

          $destinatario = (pg_num_rows($res)>0) ? pg_result($res,0,0) : "";

          if(!empty($destinatario)){
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From     = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $assunto   = "OS RECUSADA PELO FABRICANTE.";

            $mensagem  = "<center>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</center><br><br>";
            $mensagem .= "At. Responsável,<br><br>A OS $os foi recusada pelo seguinte motivo: <br> $motivo. <br>";
            $mensagem .= "Qualquer duvida entrar em contato.<br>";
            $mensagem .= "<b><font color='red'>PRESSURE</font></b>";

            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Additional headers
            $headers .= "To: $destinatario" . "\r\n";
            $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n";


            mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
          }


          if($imagem1['size'] > 0){
            $s3 = new AmazonTC('inspecao', $login_fabrica);
            $laudo_tecnico = $imagem1;

            $types = array("png", "jpg", "jpeg", "bmp", "pdf");
            $type  = strtolower(preg_replace("/.+\//", "", $laudo_tecnico["type"]));

            if(!in_array($type, $types)) {
              throw new Exception("Anexo 1 com formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf");
            }else{
              $name1 = $os."-laudo-tecnico-1";
              $file1 = $laudo_tecnico;
            }
          }

          if($imagem1['size'] > 0){
            $s3->upload ($name1, $file1);
          }

          pg_query($con, "COMMIT");

          $retorno = array("ok" => true);
        }
      }
    } else {
      pg_query($con, "ROLLBACK");
      $retorno = array("erro" => utf8_encode("Erro ao recusar OS"));
    }
  }

  exit(json_encode($retorno));
}
## FIM RECUSAR OS ##

## APROVAR OS ##
if ($_POST["aprovar"] == true) {

  $os             = $_POST["os"];
  $tipo_auditoria = $_POST["tipo_auditoria"];


  $sqlAprova = "INSERT INTO tbl_os_status(
              os,status_os,observacao,fabrica_status, admin
            )VALUES(
              $os,204,'Pedido de peças aprovado',$login_fabrica, $login_admin
            )";
  $resAprova = pg_query($con, $sqlAprova);

  if(strlen(pg_last_error()) > 0){
    $retorno = array("erro" => utf8_encode("Erro ao aprovar OS"));
  }else{
    $sql = "UPDATE tbl_os SET status_checkpoint = 3 WHERE os = {$os}";
    $res = pg_query($con, $sql);

    if(strlen(pg_last_error()) > 0){
      $retorno = array("erro" => utf8_encode("Erro ao aprovar OS"));
    }else{
      $retorno = array("ok" => true);
    }
  }
  exit(json_encode($retorno));
}
## FIM APROVAR OS ##

## PESQUISAR / LISTAR TODOS ##
if ($_POST["btn_acao"] == "submit" || $_POST['btn_acao'] == "listar_todas") {
  $data_inicial    = $_POST['data_inicial'];
  $data_final      = $_POST['data_final'];
  $codigo_posto    = $_POST['codigo_posto'];
  $descricao_posto = $_POST['descricao_posto'];
  //$tipo_auditoria  = $_POST['tipo_auditoria'];
  $status          = $_POST["status"];
  $listar_todas    = ($_POST['btn_acao'] == "listar_todas") ? true: false;

  if (strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0){
    $sql = "SELECT tbl_posto_fabrica.posto
        FROM tbl_posto
        JOIN tbl_posto_fabrica USING(posto)
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND (
          (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
          OR
          (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
        )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Posto não encontrado";
      $msg_erro["campos"][] = "posto";
    } else {
      $posto = pg_fetch_result($res, 0, "posto");
    }
  }

  if ((!strlen($data_inicial) || !strlen($data_final)) && $listar_todas === false) {
    $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
    $msg_erro["campos"][] = "data";
  } else if($listar_todas === false){
    list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);

    if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
      $msg_erro["msg"][]    = "Data Inválida";
      $msg_erro["campos"][] = "data";
    } else {
      if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
        $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
        $msg_erro["campos"][] = "data";
      } else if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final)) {
        $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 1 mês";
        $msg_erro["campos"][] = "data";
      } else {
        $aux_data_inicial = "{$yi}-{$mi}-{$di} 00:00:00";
        $aux_data_final   = "{$yf}-{$mf}-{$df} 23:59:59";
        $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
      }
    }
  }

  /*
  if (empty($tipo_auditoria)) {
    $msg_erro["msg"][] = "Selecione um tipo de auditoria";
  }
  */

  if (empty($status)) {
    $msg_erro["msg"][] = "Selecione um status";
  }

  if (!count($msg_erro["msg"])) {
    if (!empty($posto)) {
      $cond_posto = " AND tbl_os.posto = {$posto} ";
    }
    // else{
    //   $cond_posto = " AND tbl_os.posto <> 6359 ";
    // }

    switch ($status) {
      case 'aprovadas':
        $status = 204;
        break;

      case 'reprovadas':
        $status = 203;
        break;

      default:
        $status = 205;
        break;
    }

    /*
    switch ($tipo_auditoria) {
      case 'troca_produto':
        $status_os = $tipo_auditoria;
        break;

      default:
        $status_os = $$tipo_auditoria;
        break;
    }
    */
    $sql = "SELECT auditoria.os
        INTO TEMP tmp_auditoria_pedido_peca_produto
        FROM (
          SELECT ultimo_status.os, (
              SELECT status_os
                FROM tbl_os_status
               WHERE tbl_os_status.os             = ultimo_status.os
                 AND tbl_os_status.fabrica_status = $login_fabrica
                 AND status_os IN (203,204,205)
           ORDER BY os_status DESC LIMIT 1) AS ultima_os_status
            FROM (
              SELECT DISTINCT os
                FROM tbl_os_status
               WHERE tbl_os_status.fabrica_status = $login_fabrica
                 AND status_os IN (203,204,205)
            ) ultimo_status) auditoria
       WHERE auditoria.ultima_os_status IN (".$status.");";
    $res = pg_query($con, $sql);

    $sql = "SELECT
          tbl_os.os AS id_os,
          TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS abertura,
          tbl_os.posto AS id_posto,
          tbl_posto.nome AS nome_posto,
          tbl_posto.fone AS fone_posto,
          tbl_produto.referencia AS produto_referencia,
          tbl_produto.descricao AS produto_descricao,
          tbl_os.hd_chamado AS hd_chamado,
          tbl_posto_fabrica.codigo_posto AS codigo_posto
        FROM tbl_os
        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 131
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
        WHERE tbl_os.fabrica = $login_fabrica
        $cond_posto
        $cond_data
        AND tbl_os.os IN ( SELECT os FROM tmp_auditoria_pedido_peca_produto );";
        //echo nl2br($sql);exit;
    $resSubmit = pg_query($con, $sql);

  }
}
## FIM PESQUISAR / LISTAR TODOS ##

$layout_menu = "auditoria";
$title = "AUDITORIA DE PEDIDO DE PEÇA/PRODUTO";
include 'cabecalho_new.php';

$plugins = array(
  "autocomplete",
  "datepicker",
  "shadowbox",
  "mask",
  "dataTable"
);

include("plugin_loader.php");

/*
function ultima_interacao($os) {
  global $con, $login_fabrica;

  $select = "SELECT admin, posto FROM tbl_os_interacao WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY data DESC LIMIT 1";
  $result = pg_query($con, $select);

  if (pg_num_rows($result) > 0) {
    $admin = pg_fetch_result($result, 0, "admin");
    $posto = pg_fetch_result($result, 0, "posto");

    if (!empty($admin)) {
      $ultima_interacao = "fabrica";
    } else {
      $ultima_interacao = "posto";
    }
  }

  return $ultima_interacao;
}
*/
?>

<style>

.legenda {
  display: inline-block;
  width: 36px;
  height: 18px;
  border-radius: 3px;
}

</style>

<script>

$(function() {
  $.datepickerLoad(Array("data_final", "data_inicial"));
  $.autocompleteLoad(Array("posto"));
  Shadowbox.init();

  $("span[rel=lupa]").click(function () {
    $.lupa($(this));
  });

  /* INTERAGIR NA OS */
  $("button[name=interagir]").click(function() {
    var os = $(this).attr("rel");

    if (os != undefined && os.length > 0) {
      Shadowbox.open({
        content: $("#DivInteragir").html().replace(/__NumeroOs__/, os),
        player: "html",
        height: 175,
        width: 400,
        options: {
          enableKeys: false
        }
      });
    }
  });

  $(document).on("click", "button[name=button_interagir]", function() {
    var os        = $(this).attr("rel");
    var interacao = $.trim($("#sb-container").find("textarea[name=text_interacao]").val());

    if (interacao.length == 0) {
      alert("Digite a interação");
    } else if (os != undefined && os.length > 0) {
      $.ajax({
        url: "auditoria_pedido_peca_produto.php",
        type: "post",
        data: { interagir: true, interacao: interacao, os: os },
        beforeSend: function() {
          $("#sb-container").find("div.conteudo").hide();
          $("#sb-container").find("div.loading").show();
        }
      }).always(function(data) {
        data = $.parseJSON(data);

        if (data.erro) {
          alert(data.erro);
        } else {
          $("button[name=interagir][rel="+os+"]").parents("tr").find("td").css({ "background-color": "#FFDC4C" });
          Shadowbox.close();
        }

        $("#sb-container").find("div.loading").hide();
        $("#sb-container").find("div.conteudo").show();
      });
    } else {
      alert("Erro ao interagir na OS");
    }
  });
  /* FIM INTERAGIR NA OS */

  /* RECUSAR OS */
  $("button[name=recusar]").click(function() {
    var os             = $(this).attr("rel");
    var tipo_auditoria = $(this).attr("tipo-auditoria");

    if ((os != undefined && os.length > 0) && (tipo_auditoria != undefined && tipo_auditoria.length > 0)) {
      Shadowbox.open({
        content: $("#DivRecusar").html().replace(/__NumeroOs__/, os).replace(/__TipoAuditoria__/, tipo_auditoria),
        player: "html",
        height: 235,
        width: 500,
        options: {
          enableKeys: false
        }
      });
    }
  });

  $(document).on("click", "button[name=button_recusar]", function() {
    var os             = $(this).attr("rel");
    var motivo         = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());
    var tipo_auditoria = $(this).attr("tipo-auditoria");

    if (motivo.length == 0) {
      alert("Informe o motivo");
    } else if ((os != undefined && os.length > 0) && (tipo_auditoria != undefined && tipo_auditoria.length > 0)) {
      $.ajax({
        url: "auditoria_pedido_peca_produto.php",
        type: "post",
        data: { recusar: true, motivo: motivo, os: os, tipo_auditoria: tipo_auditoria },
        beforeSend: function() {
          $("#sb-container").find("div.conteudo").hide();
          $("#sb-container").find("div.loading").show();
        }
      }).always(function(data) {
        data = $.parseJSON(data);

        if (data.erro) {
          alert(data.erro);
        } else {
          $("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-danger tac' style='margin-bottom: 0px;' >Recusado</div>");
          Shadowbox.close();
        }

        $("#sb-container").find("div.loading").hide();
        $("#sb-container").find("div.conteudo").show();
      });
    } else {
      alert("Erro ao recusar OS");
    }
  });
  /* FIM RECUSAR OS */

  /* CANCELAR OS */
  $("button[name=cancelar]").click(function() {
    var os             = $(this).attr("rel");
    var tipo_auditoria = $(this).attr("tipo-auditoria");

    if ((os != undefined && os.length > 0) && (tipo_auditoria != undefined && tipo_auditoria.length > 0)) {
      Shadowbox.open({
        content: $("#DivCancelar").html().replace(/__NumeroOs__/, os).replace(/__TipoAuditoria__/, tipo_auditoria),
        player: "html",
        height: 235,
        width: 500,
        options: {
          enableKeys: false
        }
      });
    }
  });

  $(document).on("click", "button[name=button_cancelar]", function() {
    var os             = $(this).attr("rel");
    var motivo         = $.trim($("#sb-container").find("textarea[name=text_motivo]").val());
    var tipo_auditoria = $(this).attr("tipo-auditoria");

    if (motivo.length == 0) {
      alert("Informe o motivo");
    } else if ((os != undefined && os.length > 0) && (tipo_auditoria != undefined && tipo_auditoria.length > 0)) {
      $.ajax({
        url: "auditoria_pedido_peca_produto.php",
        type: "post",
        data: { cancelar: true, motivo: motivo, os: os, tipo_auditoria: tipo_auditoria },
        beforeSend: function() {
          $("#sb-container").find("div.conteudo").hide();
          $("#sb-container").find("div.loading").show();
        }
      }).always(function(data) {
        data = $.parseJSON(data);
        if (data.erro) {
        } else {
          $("button[name=cancelar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-danger tac' style='margin-bottom: 0px;' >Cancelado</div>");
          Shadowbox.close();
        }

        $("#sb-container").find("div.loading").hide();
        $("#sb-container").find("div.conteudo").show();
      });
    } else {
      alert("Erro ao cancelar OS");
    }
  });
  /* FIM CANCELAR OS */

  /* APROVAR OS */
  $("button[name=aprovar]").click(function() {
    var os             = $(this).attr("rel");
    var tipo_auditoria = $(this).attr("tipo-auditoria");

    if ((os != undefined && os.length > 0) && (tipo_auditoria != undefined && tipo_auditoria.length > 0)) {
      var loading = "<div class='loading tac' ><img src='imagens/loading_img.gif' style='width: 18px; height: 18px;' /></div>";
      var td      = $(this).parent("td");

      $.ajax({
        url: "auditoria_pedido_peca_produto.php",
        type: "post",
        data: { aprovar: true, os: os, tipo_auditoria: tipo_auditoria },
        beforeSend: function() {
          $(td).find("button").hide();
          $(td).append(loading);
        }
      }).always(function(data) {
        data = $.parseJSON(data);

        if (data.erro) {
          alert(data.erro);

          $(td).find("button").show();
          $(td).find("div.loading").remove();
        } else {
          $("button[name=recusar][rel="+os+"]").parents("tr").find("td").last().html("<div class='alert alert-success tac' style='margin-bottom: 0px;' >Aprovado</div>");
        }
      });
    }
  });
  /* FIM APROVAR OS */
});

function retorna_posto(retorno){
  $("#codigo_posto").val(retorno.codigo);
  $("#descricao_posto").val(retorno.nome);
}

</script>



<div id="DivInteragir" style="display: none;" >
  <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
  <div class="conteudo" >
    <div class="titulo_tabela" >Interagir na OS</div>

    <div class="row-fluid">
      <div class="span12">
        <div class="controls controls-row">
          <textarea name="text_interacao" rows="5" class="span12"></textarea>
        </div>
      </div>
    </div>

    <p><br/>
      <button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" >Interagir</button>
    </p><br/>
  </div>
</div>

<div id="DivRecusar" style="display: none;" >
  <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
  <div class="conteudo" >
    <div class="titulo_tabela" >Informe o Motivo</div>

    <div class="row-fluid">
      <div class="span12">
        <div class="controls controls-row">
          <textarea name="text_motivo" rows='5' class="span12" ></textarea>
        </div>
      </div>
    </div>

    <div class="row-fluid">
      <div class="span12">
        <label><strong>Anexar Laudo</strong></label>
        <div class="controls controls-row">
          <input type="file" name="foto1" />
        </div>
      </div>
    </div>
    <p>
      <button type="button" name="button_recusar" class="btn btn-block btn-danger" rel="__NumeroOs__" tipo-auditoria="__TipoAuditoria__" >Recusar</button>
    </p><br/>
  </div>
</div>

<div id="DivCancelar" style="display: none;" >
  <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
  <div class="conteudo" >
    <div class="titulo_tabela" >Informe o Motivo</div>

    <div class="row-fluid">
      <div class="span12">
        <div class="controls controls-row">
          <textarea name="text_motivo" rows='5' class="span12" ></textarea>
        </div>
      </div>
    </div>

    <div class="row-fluid">
      <div class="span12">
        <label><strong>Anexar Laudo</strong></label>
        <div class="controls controls-row">
          <input type="file" name="foto1" />
        </div>
      </div>
    </div>
    <p>
      <button type="button" name="button_cancelar" class="btn btn-block btn-danger" rel="__NumeroOs__" tipo-auditoria="__TipoAuditoria__" >Cancelar</button>
    </p><br/>
  </div>
</div>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<div class="row">
  <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_auditoria_os_troca_produto" method="post" action="<?=$_SERVER['PHP_SELF']?>" class="form-search form-inline tc_formulario" style="margin: 0 auto;" >
  <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
  <br />

  <!-- DATA INICIAL / DATA FINAL -->
  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='data_inicial'>Data Inicial</label>
        <div class='controls controls-row'>
          <div class='span4'>
            <h5 class='asteristico'>*</h5>
              <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
          </div>
        </div>
      </div>
    </div>
    <div class='span4'>
      <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='data_final'>Data Final</label>
        <div class='controls controls-row'>
          <div class='span4'>
            <h5 class='asteristico'>*</h5>
              <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
          </div>
        </div>
      </div>
    </div>
    <div class='span2'></div>
  </div>
  <!-- FIM DATA INICIAL / DATA FINAL -->

  <!-- CODIGO POSTO / NOME POSTO -->
  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span4'>
      <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='codigo_posto'>Código Posto</label>
        <div class='controls controls-row'>
          <div class='span7 input-append'>
            <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
          </div>
        </div>
      </div>
    </div>
    <div class='span4'>
      <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
        <label class='control-label' for='descricao_posto'>Nome Posto</label>
        <div class='controls controls-row'>
          <div class='span12 input-append'>
            <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
          </div>
        </div>
      </div>
    </div>
    <div class='span2'></div>
  </div>
  <!-- FIM CODIGO POSTO / NOME POSTO -->

  <!-- STATUS OS -->
  <div class='row-fluid'>
    <div class='span2'></div>
    <div class='span8'>
      <label class='control-label' for='status'>Status</label>
      <div class='controls controls-row'>
        <div class='span4'>
           <label class="radio">
                <input type="radio" name="status" value="pendente" <?php if($status == 205) echo "checked"; ?> />
                Aguardando auditoria
            </label>
        </div>
        <div class='span4'>
          <label class="radio">
              <input type="radio" name="status" value="aprovadas" <?php if($status == 204) echo "checked"; ?> />
              Aprovadas
          </label>
        </div>
        <div class='span4'>
          <label class="radio">
              <input type="radio" name="status" value="reprovadas" <?php if($status == 203) echo "checked"; ?> />
              Recusadas
          </label>
        </div>
      </div>
    </div>
    <div class='span2'></div>
  </div>
  <!-- FIM STATUS OS -->
  <p><br/>
    <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
    <button class='btn btn-info' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'listar_todas');">Listar Todas</button>
    <input type='hidden' id="btn_click" name='btn_acao' value='' />
  </p><br/>
</form>

</div>

<?php
if (isset($resSubmit)) {
  if (pg_num_rows($resSubmit) > 0) {
    $count = pg_num_rows($resSubmit);
    ?>
    <br />

    <table id="resultado" class='table table-striped table-bordered table-hover table-fixed' style="margin: 0 auto;" >
      <thead>
        <tr class='titulo_coluna' >
          <th>OS</th>
          <th>Chamado</th>
          <th>Abertura</th>
          <th>Posto</th>
          <th style="width: 80px;">Fone Posto</th>
          <th>Produto</th>
          <?php
          if ($status == "205") {
          ?>
            <th>Ações</th>
          <?php
          }
          ?>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $count; $i++) {
          $os                 = pg_fetch_result($resSubmit, $i, "id_os");
          $data_abertura      = pg_fetch_result($resSubmit, $i, "abertura");
          $nome_posto         = pg_fetch_result($resSubmit, $i, "nome_posto");
          $fone_posto         = pg_fetch_result($resSubmit, $i, "fone_posto");
          $produto_referencia = pg_fetch_result($resSubmit, $i, "produto_referencia");
          $produto_descricao  = pg_fetch_result($resSubmit, $i, 'produto_descricao');
          $hd_chamado         = pg_fetch_result($resSubmit, $i, 'hd_chamado');
          $codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
          //$ultima_interacao = ultima_interacao($os);

          // switch ($ultima_interacao) {
          //   case "fabrica":
          //     $cor = "#FFDC4C";
          //     break;

          //   case "posto":
          //     $cor = "#A6D941";
          //     break;

          //   default:
          //     $cor = "#FFFFFF";
          //     break;
          // }
          ?>

          <tr>
            <td class="tac"><a href="os_press.php?os=<?=$os?>" target="_blank" ><?=$os?></a></td>
            <td class="tac"><?=$hd_chamado?></td>
            <td class="tal"><?=$data_abertura?></td>
            <td class="tal"><?=$codigo_posto."-".$nome_posto?></td>
            <td class=""><?=$fone_posto?></td>
            <td class="tal"><?=$produto_referencia."-".$produto_descricao?></td>
            <?php
            if ($status == "205") {
            ?>
              <td class="tac" nowrap >
                <button type="button" rel="<?=$os?>" tipo-auditoria="" name="interagir" class="btn btn-small btn-primary" >Interagir</button>
                <button type="button" rel="<?=$os?>" tipo-auditoria="204" name="aprovar" class="btn btn-small btn-success" >Aprovar</button>
                <button type="button" rel="<?=$os?>" tipo-auditoria="203" name="recusar" class="btn btn-small btn-warning" >Recusar</button>
                <button type="button" rel="<?=$os?>" tipo-auditoria="15" name="cancelar" class="btn btn-small btn-danger" >Cancelar</button>
              </td>
            <?php
            }
            ?>
          </tr>
        <?php
        }
        ?>
      </tbody>
    </table>
  <?php
  } else {
  ?>
    <div class="container">
      <div class="alert">
        <h4>Nenhum resultado encontrado</h4>
      </div>
    </div>
  <?php
  }
}

include "rodape.php";
?>
