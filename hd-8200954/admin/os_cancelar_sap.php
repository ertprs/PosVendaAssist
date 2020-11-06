<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin.php";

if ($login_fabrica==3) {
  $id_servico_realizado = 20;
  $id_servico_realizado_ajuste = 96;
}

// Britania Cancelar SAP
if($_GET['ajax_cancelar_ocultar'] == "ok"){ 
  $os = $_GET['os'];
  $justificativa = $_GET['justificativa'];    
  $itens = json_decode($_GET['itens'], true);
  if(!empty($os)){

    $sql = "SELECT posto,sua_os,finalizada FROM tbl_os where os={$os}";
    $res = pg_query($con,$sql);

    $posto = trim(pg_fetch_result($res,0,posto));
    $sua_os = trim(pg_fetch_result($res,0,sua_os));
    $finalizada = trim(pg_fetch_result($res,0,finalizada));

    pg_query($con, "BEGIN TRANSACTION");

    if(strlen($finalizada) > 0 ){
      $sql = "UPDATE tbl_os SET finalizada = NULL WHERE os = {$os}";
      $res = pg_query($con,$sql);
      $msg_erro = pg_errormessage($con);
    }

    if(strlen($msg_erro) == 0){

      for ($j = 0; $j < count($itens); $j++) {
        $sql_peca = "SELECT tbl_peca.referencia AS referencia ,
                            tbl_peca.descricao AS descricao
                       FROM tbl_peca
                       JOIN tbl_os_item USING(peca)
                      WHERE tbl_os_item.os_item = {$itens[$j]}"; 
        $res_peca = pg_query($con,$sql_peca);
        $peca_referencia = trim(pg_result($res_peca,0,referencia));
        $peca_descricao = trim(pg_result($res_peca,0,descricao));

        $sql = "UPDATE tbl_os_item
                   SET servico_realizado = {$id_servico_realizado_ajuste} ,
                       admin = {$login_admin} ,
                       liberacao_pedido = FALSE,
                       liberacao_pedido_analisado = FALSE,
                       data_liberacao_pedido = null
                 WHERE os_item = {$itens[$j]}";
        $res = pg_query($con,$sql);

        $msg_erro = pg_errormessage($con);

        if(strlen($msg_erro) == 0) {

          $sql = "INSERT INTO tbl_comunicado (
                                                  descricao ,
                                                  mensagem ,
                                                  tipo ,
                                                  fabrica ,
                                                  obrigatorio_os_produto ,
                                                  obrigatorio_site ,
                                                  posto ,
                                                  ativo
                                            ) VALUES (
                                                  'Pedido de Peças CANCELADO' ,
                                                  'Seu pedido da peça $peca_referencia - $peca_descricao referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>Justificativa da Fábrica: $justificativa',
                                                  'Pedido de Peças',
                                                  $login_fabrica,
                                                  'f' ,
                                                  't',
                                                  $posto,
                                                  't'
                                            );";
          $res = pg_query($con,$sql);

          $msg_erro .= pg_errormessage($con);
        }
      }
      if(strlen($msg_erro) == 0){

        $msg_posto= "Pedido de Peças Cancelado Pela Fábrica. Justificativa: ".utf8_decode($justificativa);

        $sql = "INSERT INTO tbl_os_status 
        (os,status_os,data,observacao,admin)
        VALUES ({$os},72,current_timestamp,'{$msg_posto}',{$login_admin})";
        $res = pg_query($con,$sql);

        $msg_erro = pg_errormessage($con);

      }
    }

    if (strlen($msg_erro) == 0 && $login_fabrica == 3) {

        $itens_a_cancelar = $itens;
        $itens_a_liberar = [];

        $SqlOs = "SELECT tbl_os_item.os_item
                    FROM tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                   WHERE tbl_os.os = {$os}
                     AND tbl_os.fabrica = {$login_fabrica}"; 
        $resOs = pg_query($con,$SqlOs);
        $itens_da_os = pg_fetch_all($resOs);

        foreach ($itens_da_os as $key => $os_item) {
            
            if (in_array($os_item["os_item"], $itens_a_cancelar)) {
                continue;
            }
            $itens_a_liberar[] = $os_item["os_item"];

        }


        if (count($itens_a_liberar) > 0) {

            for ($j = 0; $j < count($itens_a_liberar); $j++) {

              $sql = "UPDATE tbl_os_item
                       SET servico_realizado = {$id_servico_realizado} ,
                           admin = {$login_admin} ,
                           liberacao_pedido = TRUE,
                           liberacao_pedido_analisado = TRUE,
                           data_liberacao_pedido = current_timestamp
                     WHERE os_item = {$itens_a_liberar[$j]}";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);

            }
            if (strlen($msg_erro) == 0 ) {

                $msg_posto = "Pedido de Peças Autorizado Pela Fábrica.";

                $sql = "INSERT INTO tbl_os_status 
                (os,status_os,data,observacao,admin)
                VALUES ({$os},73,current_timestamp,'{$msg_posto}',{$login_admin})";
                $res = pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
            }


        }
    }



    if (strlen($msg_erro) == 0 AND strlen($finalizada) > 0){

      $sql = "UPDATE tbl_os SET finalizada = '$finalizada' WHERE os = {$os}";
      $res = pg_exec($con,$sql);
      $msg_erro = pg_errormessage($con);
    }

    if(strlen($msg_erro)>0){
      $res = pg_exec ($con,"ROLLBACK TRANSACTION");
      echo "erro|$msg_erro";
    }else{
      $res = pg_exec ($con,"COMMIT TRANSACTION");
      $msg = "Os itens da OS foram cancelados"; 
      echo "ok|$msg";
    }
  }
  exit;
}

$styleTag_iframe = <<<STYLE_FOR_IFRAME
  <style>
    body {
      padding: 2px;
      margin: 0;
      margin-bottom: 1em;
      font-size: 12px;
      font-family: Verdana,Tahoma,Arial;
    }
    table, th, td {
      font-size: 12px !important;
      width: 100%;
      border: 1px solid #ccc;
    }
    .frm {
        border: #888888 1px solid;
        font-weight: bold;
        font-size: 8pt;
        background-color: #f0f0f0;
    }
    #frm_form {
      width: 95%;
      margin: 0 auto;
    }

    button,input[type=button],input[type=submit] {
      color: #fff;
      border-radius: 5px;
      background-color: #596D9B;
      border: 0px;
      padding: 5px;
      width: 150px;
      text-align: center;
      cursor: pointer;
    }
    .box-success {
      border: 1px solid green;
      padding: 5px;
      margin: 0 auto;
      color: green;
      background-color: #BEF781;
      margin-top: 15px;
      text-align: center;
    }
    .subtitle_secao {
        background-color:#e1e1e1;
        font-size: 12px;
        color: black;
        text-align: left;
        padding: 2px 1ex;
        margin: 0;
    }
    .subtitle_acao {
        background-color:#e1e1e1;
        font-size: 12px;
        color: white;
        text-align: center;
        padding: 3px;
        margin: 0;
    }
    .box-error {
      border: 1px solid red;
      padding: 5px;
      margin: 0 auto;
      color: red;
      background-color: #F6CECE;
      margin-top: 15px;
      text-align: center;
    }    
    span.label { display: inline-block; width: 18em;}
  </style>
STYLE_FOR_IFRAME;
$titulo = "INTERVENÇÃO TÉCNICA"; 

?>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" />
<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<?=$styleTag_iframe?>
<script type="text/javascript">

  function enviar_cancelar_ocultar(os,id_servico_realizado, posicao){    
    var justificativa = $('.justificativa').val();

        var json = {};
        var array = new Array();        

        $(".pecasCancelar").each(function(){
            if( $(this).is(":checked") ){
              array.push($(this).val());        
            }

        });
        json = array;        

        var itens = JSON.stringify(json);

    $.ajax({
    url: "os_cancelar_sap.php?os=" + os + "&id_servico_realizado=" + id_servico_realizado,
    type: "get", 
    data:{
            ajax_cancelar_ocultar:'ok'      ,
            os:os                           ,
            justificativa:justificativa     ,
            id_servico_realizado:id_servico_realizado ,
            itens: itens
        },
        complete: function(data){
          var retorno = data.responseText.split("|");          
          if(retorno[0] == "ok"){            
            alert(retorno[1]);
            window.parent.alterarCorLinha(os,"cancelar",posicao);            
            window.parent.Shadowbox.close();                   
            window.parent.tb_remove();                   
          } else {
            alert(retorno[1]);                                    
            window.parent.Shadowbox.close();
            window.parent.tb_remove();
          }
        }
    })
  }

</script>

<?php
//$os = $_REQUEST['os'];
//$id_servico_realizado = $_REQUEST['id_servico_realizado']; 
$tipo = $_REQUEST['tipo'];

  // OS não excluída
  $sql =  "SELECT tbl_os.os                                               ,
        tbl_os.sua_os                                                     ,
        LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
        TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
        current_date - tbl_os.data_abertura          AS dias_aberto       ,
        tbl_os.data_abertura                         AS abertura_os       ,
        tbl_os.serie                                                      ,
        tbl_os.consumidor_nome                                            ,
        tbl_os.consumidor_endereco                                        ,
        tbl_os.consumidor_numero                                          ,
        tbl_os.consumidor_bairro                                          ,
        tbl_os.consumidor_cidade                                          ,
        tbl_os.consumidor_estado                                          ,
        tbl_posto_fabrica.codigo_posto                                    ,
        tbl_posto.posto                             AS posto              ,
        tbl_posto.nome                              AS posto_nome         ,
        tbl_posto_fabrica.contato_fone_comercial    AS posto_fone         ,
        tbl_produto.referencia                      AS produto_referencia ,
        tbl_produto.descricao                       AS produto_descricao  ,
        tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
         (
            SELECT status_os
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (67, 68, 70, 199)
                 ORDER BY data DESC LIMIT 1)        AS reincindente, (
            SELECT observacao
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_descricao, (
            SELECT status_os
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_os, (
            SELECT data
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_pedido, (
            SELECT TO_CHAR(data,'DD/MM/YYYY') AS data
              FROM tbl_os_status
             WHERE tbl_os_status.fabrica_status = $login_fabrica
               AND tbl_os.os                    = tbl_os_status.os
               AND status_os IN (62, 64, 65, 127, 147, 167, 199)
                 ORDER BY data DESC LIMIT 1)        AS status_data2
      FROM tbl_os
        JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
        LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
      WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.os=$os";

  $res   = pg_query($con,$sql);
  $total = pg_num_rows($res);

    $os                  = trim(pg_fetch_result($res,0,'os'));
    $sua_os              = trim(pg_fetch_result($res,0,'sua_os'));
    $data_nf             = trim(pg_fetch_result($res,0,'data_nf'));
    $digitacao           = trim(pg_fetch_result($res,0,'digitacao'));
    $abertura            = trim(pg_fetch_result($res,0,'abertura'));
    $serie               = trim(pg_fetch_result($res,0,'serie'));
    $consumidor_nome     = trim(pg_fetch_result($res,0,'consumidor_nome'));
    $consumidor_endereco = trim(pg_fetch_result($res,0,'consumidor_endereco'));
    $consumidor_numero   = trim(pg_fetch_result($res,0,'consumidor_numero'));
    $consumidor_bairro   = trim(pg_fetch_result($res,0,'consumidor_bairro'));
    $consumidor_cidade   = trim(pg_fetch_result($res,0,'consumidor_cidade'));
    $consumidor_estado   = trim(pg_fetch_result($res,0,'consumidor_estado'));
    $codigo_posto        = trim(pg_fetch_result($res,0,'codigo_posto'));
    $posto_nome          = trim(pg_fetch_result($res,0,'posto_nome'));
    $posto_fone          = trim(pg_fetch_result($res,0,'posto_fone'));
    $produto_referencia  = trim(pg_fetch_result($res,0,'produto_referencia'));
    $produto_descricao   = trim(pg_fetch_result($res,0,'produto_descricao'));
    $posto_fone          = substr(trim(pg_fetch_result($res,0,'posto_fone')),0,17);
    $status_os           = trim(pg_fetch_result($res,0,'status_os'));
    $status_descricao    = trim(pg_fetch_result($res,0,'status_descricao'));
    $dias_abertura       = trim(pg_fetch_result($res,0,'dias_aberto'));


if ($login_fabrica == 3 && $tipo == "cancelar_laudo_tecnico") { ?>
       <form enctype="multipart/form-data" name='frm_form' id="frm_form" method="post" action="" class="thickbox">
          <input name='os' id='os_cancelar_ocultar_pecas_sap' class='os_cancelar_ocultar_pecas_sap' value='<?=$os?>' type='hidden'>
          <input name='os' id='os_cancelar_ocultar_pecas_sap' class='id_servico_realizado' value='<?=$id_servico_realizado?>' type='hidden'>
          <input type='hidden' name='btn_tipo' value='<?=$tipo?>'>
          <div>
            <div class='subtitle_acao' style='background-color:#596D9B;padding: 5px;'><?=$titulo?></div>
            <?
            if ($tipo == 'cancelar') {
              echo "<div class='subtitle_acao' style='background-color:#EF4B4B;'>CANCELAR PEDIDO</div>\n";
            } else if ($tipo == 'reparar' && in_array($login_fabrica, array(11,172))) {
                echo "<div class='subtitle_acao' style='background-color:#F7D909;'>REPARAR</div>\n";
            } else if ($tipo == 'autorizar') {
                echo "<div class='subtitle_acao' style='background-color:#34BC3F;'>AUTORIZAR PEDIDO</div>\n";
            }
} ?>
              <div class='subtitle_secao'>Posto</div>
              <br><span class="label">Código:</span> <?=$codigo_posto?> - <?=$posto_nome?>
              <br><span class="label">Telefone:</span> <?=$posto_fone?>
              <br>
              <br><div class="subtitle_secao">Ordem de Serviço</div>
              <br><span class="label">Número OS:</span> <b><?=$sua_os?></b>
              <br><span class="label">Data Abertura:</span> <b><?=$abertura?></b>
              <br><span class="label">Data da Nota Fiscal:</span> <b><?=$data_nf?></b>
              <br>
              <br><div class="subtitle_secao">Produto</div>
              <br>
              Referência: <?=$produto_referencia?> - <?=$produto_descricao?>
<?
              $sql_peca = "SELECT tbl_os_item.os_item,
                        tbl_peca.troca_obrigatoria AS troca_obrigatoria,
                        tbl_peca.retorna_conserto AS retorna_conserto,
                        tbl_peca.bloqueada_garantia AS bloqueada_garantia,
                        tbl_peca.referencia AS referencia,
                        tbl_peca.descricao AS descricao,
                        tbl_peca.peca AS peca,
                        tbl_os_item.servico_realizado AS servico_realizado
                      FROM tbl_os_produto
                      JOIN tbl_os_item USING(os_produto)
                      JOIN tbl_peca    USING(peca)
                       WHERE tbl_os_produto.os=$os";
              $res_peca = pg_query($con,$sql_peca);
              $resultado = pg_num_rows($res_peca);
              if ($resultado>0){
                echo "<br>";
                echo "<br><div  class='subtitle_secao'>Peças</div>";
                for($j=0;$j<$resultado;$j++){
                  $os_item            = trim(pg_fetch_result($res_peca, $j, 'os_item'));
                  $peca_id            = trim(pg_fetch_result($res_peca, $j, 'peca'));
                  $peca_referencia    = trim(pg_fetch_result($res_peca, $j, 'referencia'));
                  $peca_descricao     = trim(pg_fetch_result($res_peca, $j, 'descricao'));
                  $bloqueada_garantia = trim(pg_fetch_result($res_peca, $j, 'bloqueada_garantia'));
                  $retorna_conserto   = trim(pg_fetch_result($res_peca, $j, 'retorna_conserto'));
                  $servico_realizado  = trim(pg_fetch_result($res_peca, $j, 'servico_realizado'));
                  if ($bloqueada_garantia=='t')
                    $bloqueada_garantia="(bloqueada p/ garantia)";
                  else
                    $bloqueada_garantia="";
                  if ($retorna_conserto=='t')
                    $retorna_conserto=" <b>*</b> ";
                  else
                    $retorna_conserto="";
                  if ($servico_realizado==$id_servico_realizado){
                      $servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(Troca de Peça)</b>";
                  }else{
                    $servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(não gera pedido)</b>";
                  }

                  echo "<br>";

                  if($login_fabrica == 3){
                    echo '<input type="checkbox" name="pecas_selecionadas[]" class="pecasCancelar" id="peca_'.$peca_id.'" checked="checked" value="'.$os_item.'" />';
                  }
                  echo "<label for='peca_$peca_id'>$retorna_conserto $peca_referencia - $peca_descricao $servico_realizado $bloqueada_garantia</label> \n";
                }
                echo "<br><b style='color:gray;font-size:9px;font-weight:normal'>* Peças com intervenção da fábrica.";

                if($login_fabrica == 3){
                  echo "<br>** As peças marcadas terão seus pedidos cancelados</b>";

                  #Script de validação para selecionar ao menos uma peça
                  $script_validacao = '
                  pecaSelecionada = false;
                  $(\'.pecasCancelar\').each(function(){
                    if($(this).is(\':checked\')){
                      pecaSelecionada = true;
                    }
                  });';
                }
              }
              $sql = "SELECT status_os, TO_CHAR(data,'DD/MM/YYYY') AS data,
                       observacao,
                       admin
                    FROM tbl_os_status
                     WHERE os             = $os
                     AND status_os     IN (62, 64, 65)
                     AND fabrica_status = $login_fabrica
                     ORDER BY data DESC LIMIT 1";
              $res = pg_query($con,$sql);
              $total=pg_num_rows($res);

              if ($total>0){
                $st_os    = trim(pg_fetch_result($res, 0, 'status_os'));
                $st_data  = trim(pg_fetch_result($res, 0, 'data'));
                $st_obs   = trim(pg_fetch_result($res, 0, 'observacao'));
                $st_admin = trim(pg_fetch_result($res, 0, 'admin'));
              }
             
              if ($tipo=="cancelar"){
                $msg_titulo="Justificativa do Cancelamento";
              }
              if ($tipo=="reparar" && in_array($login_fabrica, array(11,172)) ){
                $msg_titulo="Será feito o reparo deste produto na fabrica, informe abaixo a justificativa:";
              }elseif ($tipo == "autorizar"){
                $msg_titulo="Justificativa da Autorização";
              }
              ?>
                <br>
                <p class="subtitle_acao" style='font-weight:bold;background-color:#A9C1E0;color:black'><?=$msg_titulo?></p>
                <br>
                <textarea name='justificativa' class='justificativa' style='width:100%' rows='5' class='frm'></textarea>                
                <input type='hidden' name='btn_acao' value=''><br><br>
                <center>
                  <img src='imagens/btn_gravar.gif' class='gravar' alt='gravar' border='0' style='cursor:pointer;'
                   onclick="                   
                      if (document.frm_form.justificativa.value != '' ){
                        if (document.frm_form.btn_acao.value == '' ) {
                          if (confirm('Deseja continuar?')) {
                            var pecaSelecionada = true;
                            <?=$script_validacao?>
                            if(pecaSelecionada == false){
                              alert('É necessário selecionar ao menos uma peça');
                            }else{
                                var os = $('.os_cancelar_ocultar_pecas_sap').val();
                                          var id_servico_realizado = $('.id_servico_realizado').val();                  
                              enviar_cancelar_ocultar(os,id_servico_realizado, <?php echo $_GET["posicao"];?>);
                              document.frm_form.btn_acao.value='gravar';
                            }
                          }
                        }else {
                          alert ('Aguarde submissão');
                        }
                      }
                      else{
                        alert('Digite a justificativa!');
                      }">
                </center>
            
<br><br><br>

