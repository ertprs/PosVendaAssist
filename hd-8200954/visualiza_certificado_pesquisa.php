<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

if(array_key_exists("ajax", $_GET) ){
  header('Content-Type: application/json');

  $sql = "SELECT pesquisa FROM tbl_pesquisa WHERE pesquisa = $1 AND fabrica = $2";
  $res_pesquisa = pg_query_params($con, $sql, array($_POST['pesquisa'],$login_fabrica));
  if(pg_num_rows($res_pesquisa) == 0){
    header('Content-Type: application/json');
    echo json_encode(array("exception" => "Pesquisa não encontrada, por favor tente novamente"));
  }
  $res_pesquisa = pg_fetch_array($res_pesquisa);

  $sql = "SELECT resposta FROM tbl_resposta WHERE tecnico = $1 AND pesquisa = $2";
  $res_resposta = pg_query_params($con,$sql,array($_POST['tecnico'], $res_pesquisa['pesquisa']));
  if(pg_num_rows($res_resposta) > 0 ){
    $res_resposta = pg_fetch_array($res_resposta);
    $sql = "UPDATE tbl_resposta SET resposta = $resposta WHERE resposta = $1";

    $res_update = pg_query_params($con,$sql,array($res_resposta['resposta']));
    if(!pg_last_error($con)){
      echo json_encode(array("success" => "Resposta gravada com sucesso!"));exit;
    }
  }else{
    $resposta = $_POST['resposta'];
    $resposta = json_encode($resposta);

    $sql = "INSERT INTO tbl_resposta (pesquisa,posto,tecnico,txt_resposta) VALUES($1, $2, $3, $4)";
    $res_insert = pg_query_params($con,$sql,array($res_pesquisa['pesquisa'], $login_posto, $_POST['tecnico'], $resposta));
    if(!pg_last_error($con)){
      echo json_encode(array("success" => "Resposta gravada com sucesso!"));exit;
    }
  }
  exit;
}




$plugins = array(
);

include __DIR__.'/admin/plugin_loader.php';



$treinamento = $_GET['treinamento'];
$tecnico = $_GET['tecnico'];

$sql = "SELECT t.treinamento, t.titulo, to_char(t.data_inicio,'DD/MM/YYYY') as data_inicio, to_char(t.data_fim,'DD/MM/YYYY') as data_fim, t.descricao, to_char(t.data_finalizado,'DD/MM/YYYY') as data_finalizado
    FROM tbl_treinamento t
    WHERE t.treinamento = $1 AND t.fabrica = $2";
$res_treinamento = pg_query_params($con,$sql,array($treinamento,$login_fabrica));
$res_treinamento = pg_fetch_array($res_treinamento);


$sql = "SELECT tp.treinamento_posto, te.tecnico, te.nome, tp.aprovado, tp.nota_tecnico, tp.participou, r.resposta
        FROM tbl_treinamento_posto tp
        JOIN tbl_tecnico te USING(tecnico)
        JOIN tbl_pesquisa tps ON tps.treinamento = tp.treinamento
        LEFT JOIN tbl_resposta r ON r.pesquisa = tps.pesquisa AND r.tecnico = te.tecnico
        WHERE tp.treinamento = $1 AND tp.tecnico = $2 AND te.fabrica = $3";
$res_treinamento_posto = pg_query_params($con,$sql,array($res_treinamento['treinamento'],$tecnico, $login_fabrica));
$res_treinamento_posto = pg_fetch_array($res_treinamento_posto);

// echo $res_treinamento['treinamento'].'--'.$tecnico.'--'.$login_fabrica."<br/><br/>";
// echo $sql;exit;

$res_pesquisa = "";
if($res_treinamento_posto['resposta'] == NULL){
    $sql = "SELECT pesquisa, descricao, categoria, texto_ajuda FROM tbl_pesquisa WHERE treinamento = $1";

    $res_pesquisa = pg_query_params($con, $sql, array($res_treinamento['treinamento']));
    $res_pesquisa = pg_fetch_array($res_pesquisa);
}

if (in_array($login_fabrica, [169,170])){
    $sql_verifica = "SELECT 
                          tbl_treinamento_posto.participou
                    FROM  tbl_treinamento_posto
                    WHERE tbl_treinamento_posto.treinamento = {$treinamento}
                          AND tbl_treinamento_posto.tecnico = {$tecnico}";
    $res_verifica = pg_query($con,$sql_verifica);
    if (pg_num_rows($res_verifica) > 0){
        $participou = pg_fetch_result($res_verifica,0,'participou');
        if ($participou == 't' || $participou === true){
          $cond = true;
        }else{
          $cond = false;
        }
    }                          
}else if (in_array($login_fabrica, [193])) {
  $cond = false;
}else{
  $cond = true;
}

?>
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
<link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />

<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">


<body>
  <input type="hidden" name="treinamento" id="treinamento" value="<?=$res_treinamento['treinamento']?>">
  <input type="hidden" name="tecnico" id="tecnico" value="<?=$res_treinamento_posto['tecnico']?>">
  <input type="hidden" name="pesquisa" id="pesquisa" value="<?=$res_pesquisa['pesquisa']?>">

  <div class="container-fluid">
      <?php
      if($res_pesquisa != "" && $cond == true){

        ?>
        <div class="row env-notice-tecnico" style="margin-top: 20px">
          <div class="span12">
            <p><b><?=$res_treinamento_posto['nome']?></b>, Por favor, responda a pesquisa para liberar os certificados</p>
          </div>
        </div>
        <?php



        $pesquisa = json_decode($res_pesquisa['texto_ajuda'],1);
        array_walk_recursive($pesquisa, function (&$value) {
                $value = utf8_decode($value);
            }
        );


        foreach ($pesquisa as $question) {

          ?>
          <div class="row-fluid env-item">
            <div class="span12">
              <h4 class="main_title"><?=$question['main_title']?></h4>

              <div class="itens">
                  <div class="item-header">
                    <?php
                    if($question['itens'][0] != "open_text_area"){
                      ?>
                      <div class="span6">
                        <?php
                        if($question['question'] != ""){
                          ?>
                          <h5 class="question"><?=$question['question']?></h5>
                          <?php
                        }
                        ?>

                      </div>
                      <div class="span6 tac">
                        <div class="btn-group table-legenda">
                            <button class="btn-peso-1 btn " disabled=""><i class="fa fa-frown"></i><br>1</button>
                            <button class="btn-peso-2 btn no-icon" disabled="">2</button>
                            <button class="btn-peso-3 btn " disabled=""><i class="fa fa-meh"></i><br>3</button>
                            <button class="btn-peso-4 btn no-icon" disabled="">4</button>
                            <button class="btn-peso-5 btn " disabled=""><i class="fa fa-smile"></i><br>5</button>
                         </div>
                      </div>
                      <?php
                    }
                    ?>
                  </div>

                  <?php
                  foreach ($question['itens'] as $item) {
                    if($item == "open_text_area"){
                      ?>
                      <div class="item">
                        <div class="span12">
                          <textarea  class="text-area" placeholder="Seu texto" style="width: 100%" rows="3"></textarea>
                        </div>
                      </div>
                      <?php
                    }else{
                      ?>
                      <div class="item">
                        <div class="span6">
                          <p class="item_question"><?=$item?></p>
                        </div>
                        <div class="span6">
                          <div class="answer tac">
                            <div class="btn-group">
                              <button class="btn btn-note" data-peso="1">&nbsp</button>
                              <button class="btn btn-note" data-peso="2">&nbsp</button>
                              <button class="btn btn-note" data-peso="3">&nbsp</button>
                              <button class="btn btn-note" data-peso="4">&nbsp</button>
                              <button class="btn btn-note" data-peso="5">&nbsp</button>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php
                    }
                  }
                  ?>
              </div>
            </div>
          </div>
          <?php
        }


        ?>
        <div class="row-fluid env-btn-send">
          <div class="span12">
              <button type="button" id="send-answer" class="btn btn-large" style="float: right;" type="button">Enviar Avaliação</button>
          </div>
        </div>
        <?php
      }
      ?>


      <div class="row env-certificate env-tdocs-uploads">
          <h2 style="text-align: center;">Certificados e Arquivos</h1>
          <h5 id="download-info" style="text-align: center; color: #0088cc;">Carregando <i class="fas fa-circle-notch fa-spin"></i></h5>
          <?php if (in_array($login_fabrica, array(169,170,193)) && strlen($_GET["gerado"]) == 0) { ?>
            <h5 id="download-info" id="lbl_atualizando_certificado" style="text-align: center; color: #0088cc;">Atualizando Certificado(s)</h5>
          <?php } ?>
      </div>
  </div>
</div>

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<script type="text/javascript">

    $(function(){
      var stop_controler = false;

      //Preparando itens para enviar na resposta
      $(".main_title").each(function(idx,elem){
        $(elem).data("text",$(elem).html());
      });

      $(".question").each(function(idx,elem){
        $(elem).data("text",$(elem).html());
      });

      $(".item_question").each(function(idx,elem){
        $(elem).data("text",$(elem).html());
      });

      //Eventos UI
      $(".btn-note").hover(function(){
        $($(this).parents(".env-item")[0]).find(".table-legenda").find(".btn-peso-"+$(this).data("peso")).addClass('btn-peso-hover');
      });

      $($(".btn-note")).mouseout(function(){
        $(".btn-peso-hover").removeClass("btn-peso-hover");
      });

      $(".btn-note").click(function(){
        $($(this).parents(".btn-group")[0]).find(".btn-note-choose").removeClass("btn-note-choose");
        $(this).addClass("btn-note-choose");
        $(this).parents(".item-error").removeClass("item-error");
      });

      //Envio da pesquisa
      $("#send-answer").click(function(){

        var responses = [];
        var stop = false;


        $(".main_title").each(function(idx,elem){
            console.log(elem);
            var envItem = $(elem).parents(".env-item")[0];
            console.log(envItem);
            var item = {};
            item.main_title = $(elem).data("text");
            item.question = $(envItem).find(".question").data("text");

            item.itens = [];
            $(envItem).find(".itens > .item").each(function(idx1,elem1){
              var answer = {};

              if($(elem1).find(".item_question").length > 0){
                answer.ask = $(elem1).find(".item_question").data("text");
                answer.val = $(elem1).find(".btn-note-choose").data("peso");

                if(answer.val == null){
                    $(elem1).addClass("item-error");
                    alert("Por favor, responda todas as perguntas");
                    $('html, body').animate({
                        scrollTop: $(elem1).offset().top - 50
                    }, 250);
                    stop = true;
                    return false;
                }
                item.itens.push(answer);
              }else{
                answer.ask = "open_text_area";
                answer.val = $(elem1).find(".text-area").val();
                item.itens.push(answer);
              }
            });
            if(stop == true){
              return false;
            }

            responses.push(item);
        });

        if(stop == true){
          stop_controler = true;
          return false;
        }

        var treinamento = $("#treinamento").val();
        var tecnico = $("#tecnico").val();
        var pesquisa = $("#pesquisa").val();


        $.ajax("visualiza_certificado_pesquisa.php?ajax=pesquisa_resposta",{
          method: "POST",
          data: {
            treinamento: treinamento,
            tecnico: tecnico,
            pesquisa: pesquisa,
            resposta: responses
          }
        }).done(function(response){
          if(response.exception != undefined){
            alert(response.exception);
            
          }else{
            if(response.success != undefined){

              <?php if (in_array($login_fabrica, array(169,170,193))) { ?>
                      geraCertificado(treinamento,tecnico);
              <?php } ?>

              
              alert(response.success);
              $(".env-item").fadeOut(1000);
              $(".env-btn-send").fadeOut(1000);
              $(".env-notice-tecnico").fadeOut(1000,function(){
                window.setTimeout('location.reload()', 500); 
                $(".env-certificate").fadeIn(1000);
              });
            }
          }
        });

      });

      <?php if (in_array($login_fabrica, array(169,170,193)) && $cond == false){ ?>
        $(".env-certificate").fadeIn(1000);
        
        <?php if (!in_array($login_fabrica, [193])) { ?> 
        $("#download-info").html("Certificado não disponível, O técnico não participou do(a) treinamento/palestra.");   
        <?php } ?>
      <?php } ?>

      <?php if (in_array($login_fabrica, array(169,170,193)) && strlen($_GET["gerado"]) == 0) { ?>
        if (stop_controler == false) {
          var treinamento = $("#treinamento").val();
          var tecnico     = $("#tecnico").val();
          $.ajax("gera_certificado.php",{
            method: "POST",
            data: {
              treinamento: treinamento,
              tecnico: tecnico
            }
          }).done(function(response){
            response = JSON.parse(response);
            if (response.ok != "Certificado Gerado com Sucesso") {
              alert("Erro ao buscar o certificado");
            } else {
              window.location.href = "visualiza_certificado_pesquisa.php?treinamento=" + treinamento + "&tecnico=" + tecnico +"&gerado=sim";
            }
          });
        }
      <?php } ?>
    });

  /* Função para gerar certificado */
  function geraCertificado(treinamento,tecnico){
      $.ajax("gera_certificado.php",{
          method: "POST",
          data: {
            treinamento: treinamento,
            tecnico: tecnico
          }
        }).done(function(response){
          response = JSON.parse(response);
        
          if (response.ok !== undefined) {
              window.setTimeout('location.reload()', 500);
          }
        });
  }

  //ARQUIVOS
  <?php if (in_array($login_fabrica, array(169,170,193))){ 
          // verificando se o técnico participou
          $tecnico = $res_treinamento_posto['tecnico'];
          $sql_tecnico = "SELECT
                          tbl_treinamento_posto.participou,
                          tbl_treinamento_posto.aprovado
                      FROM tbl_treinamento_posto
                      WHERE tbl_treinamento_posto.treinamento = {$treinamento}
                      AND tbl_treinamento_posto.tecnico       = {$tecnico}";
          $res_tecnico = pg_query($con,$sql_tecnico);
          $msg_erro    = pg_last_error($con);
          if (!strlen($msg_erro) > 0){
              $participou = pg_fetch_result($res_tecnico, 0, participou);
          }
  ?>
          var tdocs_uploader_url = "plugins/fileuploader/fileuploader-iframe.php?context=gera_certificado&reference_id=<?=$res_treinamento_posto['tecnico']?>&treinamento=<?=$treinamento?>&no_hash=true&verificaParticipou=t";
          var aux_each = false;
  <?php }else { ?>
        var tdocs_uploader_url = "plugins/fileuploader/fileuploader-iframe.php?context=treinamento_posto&reference_id=<?=$res_treinamento_posto['treinamento_posto']?>&no_hash=true";
  <?php } ?>

  
  var updateEnvTdocs = function(){
  $.ajax(tdocs_uploader_url+"&ajax=get_tdocs").done(function(response){

      if (response.length == 0){
          $("#download-info").html("Nenhum arquivo disponível ainda");
      }else{
          $(response).each(function(idx,elem){
              if($("#"+elem.tdocs_id).length == 0){
                  var div = $("<div class='env-img' style='display: none'>");
                  $(".env-tdocs-uploads").append(div);
                  elem.obs = JSON.parse(elem.obs);
                  loadImage(elem.tdocs_id,function(responseTdocs){
                      $(div).html("");
                      $(div).attr("id",elem.tdocs_id);
                      var img = $("<img class=''>");
                      if(responseTdocs.fileType == 'image'){
                        $(img).attr("src",responseTdocs.link);
                        var span = $("<span>"+responseTdocs.file_name+" - "+elem.obs[0].typeName+"</span>")
                        var a = $("<a target='_BLANK'>");
                        $(a).attr("href",responseTdocs.link);
                        $(a).append(span);
                        $(div).append(a);
                      }else{
                        $(img).attr("src","plugins/fileuploader/file-placeholder.png");
                        if (responseTdocs.file_name == null || responseTdocs.file_name == "null") { /*HD - 6261912*/
                          responseTdocs.file_name = "CERTIFICADO";
                        }
                        var span = $("<span>"+responseTdocs.file_name+"</span>")
                        var a = $("<a target='_BLANK'>");
                        $(a).attr("href",responseTdocs.link);
                        $(a).append(span);

                        $(div).append(a);
                      }

                      $(div).prepend(img);

                      setTimeout(function(){
                          $(div).fadeIn(1500);
                      },1000);
                  });
              }

              setTimeout(function(){
                  if(response.length == 0){
                      window.location.href=window.location.href; 
                      $("#download-info").html("Nenhum arquivo disponível ainda");
                  }else{
                      $("#download-info").html("Clique no link em azul para download");
                  }
              },3000);

              <?php if (in_array($login_fabrica, [169,170,193])) { ?>
                        if (aux_each == false) {
                          aux_each = true;
                          return false;
                        }
              <?php } ?>
          });
      }
  });
}

  function loadImage(uniqueId, callback){
    $.ajax("plugins/fileuploader/fileuploader-iframe.php?loadTDocs="+uniqueId).done(callback);
  }

  <?php if (in_array($login_fabrica, array(169,170,193))){ 
            if ($participou == 't'){ ?>
                updateEnvTdocs();
  <?php     }else{ ?> 
                $("#download-info").html("Certificado não disponível, O técnico não participou do(a) treinamento/palestra.");   
  <?php     }
      }else{ ?>
      updateEnvTdocs();
<?php } ?>
  
  setTimeout(function(){
    if($("#pesquisa").length == 0 || $("#pesquisa").val() === ""){
      $(".env-certificate").fadeIn(1000);
    }
  },1000);

</script>

<style type="text/css">
  .env-certificate{
    display: none;
  }

  .env-img > img{
    display: block;
    margin: 0 auto;
    max-height: 235px;
  }

  .env-img{
    width: 50%;
    display: block;
    float: left;
    text-align: center;
  }

  .item-error{
        background: #f1d8d8 !important;
  }

  .main_title{
    background: #e2e2e2;
    padding: 8px

  }

  .btn-note:hover{
    background: #e2e2e2
  }

  .btn-note-choose{
    background: #b4daf3 !important;
  }

  .question{
    border-bottom: 1px solid #e2e2e2;
  }

  .itens{
    width: 100%;
    float: left;
  }

  .item{
    float: left;
    width: 100%;
    padding: 5px 0 5px 0;
    border-bottom: 1px solid #f5f3f3;
  }

  .item-header{
    float: left;
    width: 100%;
    padding: 5px 0 5px 0;
    border-bottom: 1px solid #f5f3f3;
  }

  .item > .span6:first-child > p{
    margin-top: 10px
  }

  .pagination {
     margin: 0px;
     text-align: center;
  }

  .table-legenda > button{
    height: 42px;
  }

  .table-legenda > .no-icon{
    padding-top: 19px
  }

  .answer > .btn-group > button{
    width: 38px;
    height: 38px;
  }

  .btn-peso-hover{
      background: #333 !important;
      color: #fff !important;
  }
</style>
