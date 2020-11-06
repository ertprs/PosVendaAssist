<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';
require_once 'autentica_usuario.php';
include_once('anexaNF_inc.php');
include_once('funcoes.php');
include_once('funcao_explode_os_consumidor.php');

$ip_devel = $_SERVER['SERVER_ADDR'];
$title              = $titulo = traduz('Cadastro de Ordem de Serviço');
$layout_menu        = 'os';
$n_linhas_pecas     = 1; //Quantidade de linhas para lançamento de peças
$n_linhas_analise   = 1; //Quantidade de linhas para lançamento de defeito constatado e solução
$msg_erro           = array();
$campos_telecontrol = array();
/**
 * Retorna TRUE ou FALSE.
 *
 * Acrescentar aqui as regras para o anexo obrigatório da foto da série do
 * produto. A "função" recebe um array como parâmetro, assim fica totalmente
 * flexível. As variáveis que estão no use() são lidas conforme ao valor que
 * têm nesta linha, e não quando for usar a "função".
 */

$anexaFotoSerie = function(array $filtros)
  use ($con, $foto_serie_produto, $login_fabrica)
{
  if (!$foto_serie_produto)
    return false;

  if ($login_fabrica == 20) {
    if ('999' == $filtros['serie'] and
      !in_array($filtros['tipo_atendimento'], array(11, 12))) {
      return true;
    }
  }
  return false;
};

// Data de abertura preenchida ao carregar a tela...
$fabrica_data_abertura_auto = in_array($login_fabrica, array(20));

if (!empty($reabrir) and !is_null(getPost('reabrir')))
  $reabrir = getPost('reabrir', true); //primeiro o _GET

$os = getPost('os');
$hd_chamado = getPost("hd_chamado");

if ($fabricaFileUploadOS) {
    if (!empty($os)) {
        $tempUniqueId = $os;
        $anexoNoHash = null;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}
//  Exclui a imagem da NF
if ($_POST['ajax'] == 'excluir_nf') {
  $img_nf = anti_injection($_POST['excluir_nf']);
  //$img_nf = basename($img_nf);

  $excluiu = (excluirNF($img_nf));
  $nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

  if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
  if (!$excluiu) $ret = 'ko|'.traduz('nao.foi.possivel.excluir.o.arquivo.solicitado');

  exit($ret);
}// FIM Excluir imagem

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$digita_os = pg_fetch_result ($res,0,0);
if ($digita_os == 'f' and strlen($hd_chamado)==0) {
  echo "<H4>" . traduz('sem.permissao.de.acesso', $con) . "</H4>";
  exit;
}

if (strlen($os) > 0) {
  try {
      $os = intval($os);

      if($login_fabrica == 20){
        $sql_busca_campos = "SELECT tbl_os_campo_extra.campos_adicionais
                              FROM tbl_os_campo_extra
                              WHERE os = $os
                              AND fabrica = $login_fabrica";
        $res_busca_campos = pg_query($con, $sql_busca_campos);
        if(pg_num_rows($res_busca_campos) > 0){
          $adicionais = pg_result($res_busca_campos,0,campos_adicionais);
          $adicionais = json_decode($adicionais, true);

        }
        extract($adicionais);
      }


      $sql = "SELECT tbl_os.os
            FROM tbl_os
           WHERE tbl_os.os      = {$os}
             AND tbl_os.fabrica = {$login_fabrica}
             AND tbl_os.posto   = {$login_posto}
          ";
      @$res = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0)
      throw new Exception("<erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>", 1);

    if (pg_num_rows($res) == 0) {
      unset($_POST["btn_acao"]);
      unset($os);
      throw new Exception(traduz('os.nao.encontrada', $con, $cook_idioma));
    }

    if($login_fabrica == 20){
      $sql  = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
      $res1 = pg_query ($con,$sql);
      $sql = "SELECT obs_reincidencia,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
      $res = pg_query($con,$sql);

      $obs_reincidencia = pg_fetch_result($res,0,'obs_reincidencia');
      $os_reincidente   = pg_fetch_result($res,0,'os_reincidente');

      if ($os_reincidente == 't' AND (strlen($obs_reincidencia) == 0 or strlen($motivo_atraso ) == 27 OR strlen($observacao) == 27)){
        header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
      }
    }

    if (strlen($reabrir) > 0) {
      $sql = "
        SELECT extrato
          FROM tbl_os_extra
         WHERE os = $os
           AND extrato IS NULL;";
      @$res = pg_query($con, $sql);
      if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>", 1);

      if (pg_num_rows($res) > 0) {
        $sql = "
          SELECT os_item
            FROM tbl_os_item
            JOIN tbl_os_produto
           USING (os_produto)
            JOIN tbl_servico_realizado
              ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
           WHERE tbl_os_produto.os             = {$os}
             AND tbl_servico_realizado.fabrica = {$login_fabrica}
             AND tbl_servico_realizado.troca_produto IS TRUE LIMIT 1
          ";
        @$res = pg_query($con,$sql);
        if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>", 1);

        if (pg_num_rows($res) > 0) {
          unset($_POST["btn_acao"]);
          throw new Exception('esta.os.nao.pode.ser.reaberta.pois.a.solucao.foi.a.troca.do.produto');
        }
        else {
          $apaga_data_hora_fechamento = '';

          if ($login_fabrica == 20) {
            $apaga_data_hora_fechamento = ', data_hora_fechamento = NULL';
          }

          $sql = "
            UPDATE tbl_os
               SET data_fechamento = NULL, finalizada = NULL $apaga_data_hora_fechamento
             WHERE tbl_os.os       = {$os}
               AND tbl_os.fabrica  = {$login_fabrica}
               AND tbl_os.posto    = {$login_posto}
            ";
          @$res = pg_query($con, $sql);
          if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>", 1);
        }
      }
      else {

        unset($_POST["btn_acao"]);
        throw new Exception('esta.os.ja.esta.em.extrato.e.nao.pode.ser.reaberta');
      }
    }

    $sql = "
    SELECT tbl_os.os
      FROM tbl_os
     WHERE tbl_os.os      = {$os}
       AND tbl_os.fabrica = {$login_fabrica}
       AND tbl_os.posto   = {$login_posto}
       AND tbl_os.os_fechada IS FALSE
    ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) == 0) {
      unset($_POST["btn_acao"]);
    if($login_fabrica <> 20){
       throw new Exception('os.finalizada.para.modificar.use.a.opcao.reabrir.na.consulta.de.os');
      }
    }
  }
    catch(Exception $e) {
      unset($_POST["btn_acao"]);
      $ttext = $e->getMessage();
      $msg_erro[] = ($e->getCode() == 1 ? traduz(array('falha.ao.localizar.os', $ttext), $con) : traduz($ttext, $con));
    }
}

include_once "cabecalho.php";
require_once("os_cadastro_unico.class.php");
include_once("gMapsKeys.inc");

if (strlen($hd_chamado) > 0 && strlen($os) == 0 && !isset($_POST["btn_acao"])) {
  try {
    $hd_chamado = intval($hd_chamado);

    if($login_fabrica == 85){
      $campo_categoria = ", tbl_hd_chamado.categoria ";
    }

    $sql = "
      SELECT tbl_hd_chamado.hd_chamado $campo_categoria
        FROM tbl_hd_chamado
        JOIN tbl_hd_chamado_extra
          ON tbl_hd_chamado.hd_chamado  = tbl_hd_chamado_extra.hd_chamado
       WHERE tbl_hd_chamado.hd_chamado  = {$hd_chamado}
         AND tbl_hd_chamado.fabrica     = {$login_fabrica}
         AND tbl_hd_chamado_extra.posto = {$login_posto}
      ";
    @$res = pg_query($con, $sql);

    if($login_fabrica == 85 and pg_num_rows($res)>0){
      $categoria = pg_fetch_result($res, 0, 'categoria');
    }

    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.recuperar.dados.da.pre-os', $con) . " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>");
    if (pg_num_rows($res) == 0) throw new Exception(traduz("pre-os.nao.encontrada", $con));
  }
  catch(Exception $e) {
    unset($hd_chamado);
    $msg_erro[] = $e->getMessage();
  }
}
?>
<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/date.js'></script>
<!-- <link rel='stylesheet' type='text/css' href='js/datePicker-2.css' title='default' media='screen' /> -->
<script type='text/javascript' src='admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.min.js'></script>
<script type='text/javascript' src='admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.datepicker.pt-BR.js'></script>
<script type='text/javascript' src='admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.datepicker.es.js'></script>
<script type='text/javascript' src='js/jquery.maskedinput2.js'></script>
<script type='text/javascript' src='js/jquery.numeric.js'></script>

<link href='js/jquery.autocomplete.css' rel='stylesheet' type='text/css'>
<link href='os_cadastro_unico.css' rel='stylesheet' type='text/css'>
<script type='text/javascript' src='js/jquery.bgiframe.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.dimensions.js'></script>

<script type='text/javascript' src='ajax.js'></script>
<script type='text/javascript' src='ajax_cep.js'></script>
<script type='text/javascript' src='js/anexaNF_excluiAnexo.js'></script>

<script src='plugins/shadowbox/shadowbox.js' type='text/javascript'></script>
<script src="js/ExplodeView.js" type='text/javascript'></script>
<link rel='stylesheet' type='text/css' href='plugins/shadowbox/shadowbox.css' media='all'>
<link href="admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.min.css" type="text/css" rel="stylesheet" media="screen">

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script type="text/javascript" src="admin/js/jquery.mask.js"></script>

<script type="text/javascript">
  var f = <?=$login_fabrica?>;
    // Configura jUI DatePicker
    $.datepicker.setDefaults({
      showOn: "both",
      buttonImage: window.location.pathname.replace(/\/[^/]+$/, '/') + "css/images/calendar-old.png",
      buttonImageOnly: true,
      buttonText: "Calendar",
      minDate: new Date(2000, 01, 01)
    });

  $(function(){
    $("#data_nf").mask('99/99/9999');
    $("#data_abertura").mask('99/99/9999');

  $(".numeric").numeric();

    <?php if ($cook_idioma !== 'en'): ?>
    $.datepicker.setDefaults($.datepicker.regional['<?=$cook_idioma?>']);
    <?php endif; ?>

<?php if ($login_fabrica == 20) { ?>

    function EscondeCampos(){
      $("#peca_nao_disponivel").hide();
      $("#nao_existe_pecas").hide();
      $("#procon").hide();
      $("#solicitacao_fabrica").hide();
      $("#pedido_nao_fornecido").hide();
      $("#linha_medicao").hide();
      $("#contato_sac").hide();
      $("#detalhe").hide();
    }

    $("#tipo_atendimento").change(function(){
      
      set_peca_itens_focus();

      let tipo_atendimento = $(this).val();
      let label_produto    = "";
      let label_peca       = "";

      if ($("#sua_os").length > 0) {
        $("#solucao_os > option[value="+$("#id_solucao_os").val()+"]").prop("selected", true);
      }

      if ((tipo_atendimento == 10 || tipo_atendimento == 13) && $("#sua_os").length > 0) {

        $(this).find("option:not(:selected)").filter(function(){
          return $(this).val() != 13 && $(this).val() != 10;
        }).remove();

        $("#data_nf").prop({readonly: true});
        $("#nota_fiscal").prop({readonly: true});

      }

      $("#div_serie label:first-of-type img").remove();
      $("#div_serie label:first-of-type").append("<img src='imagens/help.png' />");
      $("#div_serie label").attr("title", '<?= traduz("Esse campo deve ser preenchido com o número de 9 dígitos presente na etiqueta da ferramenta. Caso não encontre essa informação (etiqueta ausente), preencher com 999") ?>');

      nao_muda_label = false;
      nao_altera_pesquisa = false;
      nao_altera_maxlength = false;

      if (tipo_atendimento == 10) {
        maxlength      = 10; 
        maxlength_peca = 10;
        nao_altera_maxlength = true;
        label_peca    = '<?= traduz("Código da peça (10 dígitos)") ?>';
        nao_muda_label = true;
        nao_altera_pesquisa = true;
        $(".peca_itens").attr("tipo_pesquisa", "");
        $(this).removeClass("bloqueado");
        $("#produto").attr("tipo_pesquisa", "");
      } else {
        label_peca    = '<?= traduz("Peça referência - Descrição") ?>';
        maxlength     = 200;
        $("#produto").attr("tipo_pesquisa", "");
      }

      if (tipo_atendimento == 16) {
        label_peca    = '<?= traduz("Código da peça (10 dígitos)") ?>';
        $(".peca_itens").attr("tipo_pesquisa", "");
        maxlength = 10;
        maxlength_peca = 10;
        nao_altera_maxlength = true;
        nao_muda_label = true;
        nao_altera_pesquisa = true;
      } else if (tipo_atendimento != 10) {
        $("#produto").attr("tipo_pesquisa", "");
        $(".peca_itens").attr("tipo_pesquisa", "");
        maxlength     = 200;
        maxlength     = 200;
        label_peca    = '<?= traduz("Peça referência - Descrição") ?>';
      }

      if (tipo_atendimento == 10) {
        $("#produto").attr("tipo_pesquisa", "");
      }

      label_produto = '<?= traduz("Código da Ferramenta – Descrição") ?>';

      $("#div_produto > label:first-of-type").text(label_produto);
      $("#produto").attr("maxlength", maxlength);

      if (tipo_atendimento == 11) {
        label_peca    = '<?= traduz("Código da Peça (10 digitos)") ?>';
        $(".peca_itens").attr("tipo_pesquisa", "referencia");
        nao_altera_pesquisa = true;
        maxlength_peca = 10;
        nao_altera_maxlength = true;
        $(".qtde_itens").prop("readonly", true).val("1");
        $("input[id^=qtde][class=qtde_itens]:visible:not(:first)").remove();
        $("div[id^=div_peca]:visible:not(:first)").remove();
      } else {
        if (!nao_muda_label) {
          label_peca    = '<?= traduz("Peça referência - descrição") ?>';
        }
        $(".qtde_itens").prop("readonly", true);
        $(".peca_itens").attr("tipo_pesquisa", "");
      }

      if (tipo_atendimento == 12) {
        label_peca = '<?= traduz("Código do acessório (10 digitos)") ?>';
        maxlength_peca  = 10;
        $(".peca_itens").attr("tipo_pesquisa", "acessorio");
        $(".qtde_itens").prop({readonly: false}).filter(function(){
          return $(this).val() == "";
        }).val("1");
        $("input[id^=qtde][class=qtde_itens]:visible:not(:first)").remove();
        $("div[id^=div_peca]:visible:not(:first)").remove();
      } else {
        if (!nao_altera_pesquisa) {
          $(".peca_itens").attr("tipo_pesquisa", "");
        }
        if (!nao_altera_maxlength) {
          maxlength_peca     = 200;
        }
         if (tipo_atendimento != 11) {
          $(".qtde_itens").prop({readonly: false});
        }
      }

      $("#itens_os_header_label_0").text(label_peca);
      $(".peca_itens").attr("maxlength", maxlength_peca);

      if ($("#tipo_atendimento_gravado").val() != "13" && $("#tipo_atendimento_gravado").val() != "16") {
        if (tipo_atendimento == 13 || tipo_atendimento == 66) {
          $("#promotor_treinamento2, #area_motivo_ordem input").prop("readonly", false).removeClass("bloqueado");
          $("#promotor_treinamento2 > option").prop("disabled", false);
          $("#motivo_ordem").prop("readonly", false).removeClass("bloqueado");
          $("#motivo_ordem > option").prop("disabled", false);
        } else {
          $("#promotor_treinamento2, #area_motivo_ordem input").prop("readonly", true).addClass("bloqueado");
          $("#area_motivo_ordem input").val("");

          $("#promotor_treinamento2 > option, #motivo_ordem > option").prop({"disabled": true}).filter(function(){
            return tipo_atendimento != 16;
          }).prop({"selected": false});

          $("#motivo_ordem").prop("readonly", true).addClass("bloqueado");
        }
      } else {
        $("#promotor_treinamento2, #motivo_ordem, #area_motivo_ordem input").prop("readonly", true).addClass("bloqueado");
        $("#promotor_treinamento2 > option:not(:selected), #motivo_ordem > option:not(:selected)").prop({"disabled": true});
      }

      if (tipo_atendimento == 14) {
        label_peca   = '<?= traduz("Código da Peça (10 digitos)") ?>';
        $("#itens_os_header_label_0").text(label_peca);
        $(".peca_itens").attr("maxlength", 10);
        $("#div_data_nf label").html("Data da Reparação Anterior");
      } else {
        $("#div_data_nf label:first").html("<?=traduz('Data Compra')?>");
      }

      if ((tipo_atendimento == 66 || tipo_atendimento == 16) && $("#sua_os").length == 0) {
        $("#promotor_treinamento2").prop("readonly", false).removeClass("bloqueado");
        $("#promotor_treinamento2 > option").prop({"disabled": false});
      } else if ((tipo_atendimento == 66 || tipo_atendimento == 16) && $("#sua_os").length > 0) {
        $("#promotor_treinamento2, #motivo_ordem, #area_motivo_ordem input").prop("readonly", true).addClass("bloqueado");
        $("#promotor_treinamento2 > option:not(:selected), #motivo_ordem > option:not(:selected)").prop({"disabled": true});
      }

    });

    $("#tipo_atendimento").change();

    $("#motivo_ordem").change(function(){

      $("#area_motivo_ordem input").val("");

      motivo_ordem = $("#motivo_ordem").val();
      EscondeCampos();
      $("#area_motivo_ordem").show();

      if(motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
        $("#peca_nao_disponivel").show();
      }
      if(motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
        $("#nao_existe_pecas").show();
      }
      if(motivo_ordem == 'PROCON (XLR)'){
        $("#procon").show();
      }
      if(motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
        $("#solicitacao_fabrica").show();

      }
      if(motivo_ordem == "Linha de Medicao (XSD)"){
        $("#linha_medicao").show();
      }
      if(motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
        $("#pedido_nao_fornecido").show();
      }

      if(motivo_ordem == 'Contato SAC (XLR)'){
        $("#contato_sac").show();
      }

      if(motivo_ordem == 'Bloqueio financeiro (XSS)' || motivo_ordem == 'Ameaca de Procon (XLR)' || motivo_ordem == 'Defeito reincidente (XQR)'){
        $("#detalhe").show();
      }
    });
  });

<?php } ?>

    function retorna_dados_peca(peca, peca_referencia, peca_descricao, voltagem, qtde, posicao) {

      var ja_inserido = false;

      <?php
      if ($login_fabrica == 20) {
      ?>

        let label = peca_referencia+" - "+peca_descricao;

        $(".peca_itens").each(function(){

          if ($.trim(label) == $.trim($(this).val()) && $.trim($(this).val()) != "") {
            ja_inserido = true;
          }

        });

        $(".msg_erro_peca_dupla").remove();
        
        if (ja_inserido) {
          $('#peca' + posicao).val("");
          $("#itens_os_header_labels").after("<div class='msg_erro_peca_dupla' style='width: 680px;background-color: red;font-weight: bolder;color: white;'>Peça já inserida no formulário. Favor, alterar a quantidade da peça caso necessário.</div>");
          return;
        }
      <?php
      }
      ?>

      gravaDados('peca_referencia_descricao'+posicao,peca_referencia+" - "+peca_descricao);
      gravaDados('peca_id'+posicao,peca);
      gravaDados('peca'+posicao+'_id',peca);
      gravaDados('peca'+posicao+'_last',peca);
      gravaDados('peca'+posicao,peca_referencia+" - "+peca_descricao);
      gravaDados('peca_referencia'+posicao,peca_referencia);

      if ($("#tipo_atendimento").val() != 11 && $("#tipo_atendimento").val() != 12) {
        gravaDados('qtde'+posicao,"1");
      }

      $('#item_causador_referencia' + posicao).focus();

      atualiza_causa_defeito(posicao);
  }

  function gravaDados(name, valor){
      try {
        $("input[name="+name+"]").val(valor);
      } catch(err){
        return false;
      }
  }



<?php if($login_fabrica == 85){ ?>

  setTimeout( function(){
    $('.fones').each(function(){
      var valor = $(this).val().length;

      if(valor <= 10){
        $(this).mask('(00) 0000-0000',  $(this).val()); /* Máscara default */
      }else{
        $(this).mask('(00) 00000-0000', $(this).val());  // 9º Dígito
      }
    });
  },1500);

  var phoneMask = function(){
    if($(this).val().match(/^\(0/)){
      $(this).val('(');
      return;
    }
    if($(this).val().match(/^\([1-9][0-9]\) *[0-8]/)){
      $(this).mask('(00) 0000-0000');
      console.debug('telefone');
    }
    else{
      $(this).mask('(00) 00000-0000');
      console.debug('celular');
    }
    $(this).keyup(phoneMask);
  };
  $('.fones').keyup(phoneMask);



  //$("#tipo_atendimento").val(2);

  });
/* fim - 9º Dígito para São Paulo-SP, digitando o DDD 11 + 9 deixará entrar o 9º caracter */

<?php } ?>
</script>
<style>
.ui-datepicker {
  font-size:12px;
}
img.ui-datepicker-trigger {
  position: relative;
  top: 3px;
  margin: auto 2px;
}
#area_motivo_ordem{
  padding: 5px 15px;
  color: #CC0000;

}
.area_motivo_ordem_titulo{
  font:x-small Verdana, Arial, Helvetica, sans-serif;
  /*color:#CC0000;*/
}
.div_campo {
  margin: 7.5px;
}

.div_grupo {
  padding: 0px;
  padding-bottom: 5px;
}

</style>
<?php

if($login_fabrica == 87 AND !empty($os)){
  $sql = "
    SELECT interv_reinc.os
      FROM (
        SELECT ultima_reinc.os, (
            SELECT status_os
              FROM tbl_os_status
             WHERE fabrica_status   = $login_fabrica
               AND tbl_os_status.os = ultima_reinc.os
               AND status_os IN (62, 64, 81)
         ORDER BY data desc LIMIT 1) AS ultimo_reinc_status
          FROM (
            SELECT DISTINCT os
              FROM tbl_os_status
              JOIN tbl_os
             USING (os)
             WHERE fabrica_status   = $login_fabrica
               AND tbl_os.os        = $os
               AND tbl_os.posto     = $login_posto
               AND tbl_os.finalizada IS NULL
       AND status_os IN (62, 64, 81) ) ultima_reinc) interv_reinc
     WHERE interv_reinc.ultimo_reinc_status IN (62) LIMIT 1";
  $res = pg_query($con,$sql);

  if(pg_numrows($res) > 0){
    $os_interv = pg_result($res,0,os);
    echo "<div class='msg_erro'>" .
         traduz(array('ordem.de.servico',$os_interv,'em.analise'), $con) .
       "</div>";
    exit;
  }
}

//echo "<script type='text/javascript' src='os_cadastro_unico.js'></script>";
$js_config_fabrica = "os_cadastro_unico/js/$login_fabrica.js";
$js_config_fabrica = (file_exists($js_config_fabrica)) ? $js_config_fabrica : 'os_cadastro_unico.js';
$js_config_fabrica = $js_config_fabrica."?q=".date("Ymdhis");

echo "<script type='text/javascript' src='$js_config_fabrica'></script>\n";

$file_config_fabrica = "os_cadastro_unico/fabricas/$login_fabrica.php";

if (file_exists($file_config_fabrica)) {
  include $file_config_fabrica;
} else {
  include "os_cadastro_unico/fabricas/default.php";
}


if (isset($_POST['btn_acao'])) {

  $id_solucao_os         = empty($_POST['solucao_os'])         ? 'null' : $_POST['solucao_os'];

  foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $campo => $configuracoes) {
    $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['valor'] = $_POST[$campo];
  }

  $campos_telecontrol[$login_fabrica]['tbl_os']['produto']['valor_id'] = $_POST['produto_id'];
  $campos_telecontrol[$login_fabrica]['tbl_os']['produto']['valor_last'] = $_POST['produto_last'];
  $campos_telecontrol[$login_fabrica]['tbl_os']['produto']['valor_referencia'] = $_POST['produto_referencia'];

  $n_linhas_analise = intval($_POST["n_linhas_analise"]);
  $n_linhas_pecas   = intval($_POST["n_linhas_pecas"]);
  $cod_ibge         = intval($_POST["cod_ibge"]);

    if($login_fabrica == 20){
    if(in_array($tipo_atendimento,array(11,12,13,66))) { //hd_chamado=2843341
      $campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['valor'] = 12845;
    }

    $campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['obrigatorio'] = 0;

    if(in_array($tipo_atendimento,array(11,12))) {
      $campos_telecontrol[$login_fabrica]['tbl_os']['serie']['obrigatorio']          = 0;
      $campos_telecontrol[$login_fabrica]['tbl_os']['produto']['obrigatorio']        = 0;
      $campos_telecontrol[$login_fabrica]['tbl_os']['data_nf']['obrigatorio']        = 1;
      $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['obrigatorio'] = 0;
    }

    if(in_array($tipo_atendimento,array(10,11,12,13))) {
      $campos_telecontrol[$login_fabrica]['tbl_os']['nota_fiscal']['obrigatorio']    = 1;
    }

    $qry_ns_obr = pg_query(
      $con,
      "SELECT *
         FROM tbl_produto
        WHERE produto = $produto_id
          AND numero_serie_obrigatorio IS NOT TRUE"
    );

    if (pg_num_rows($qry_ns_obr)) {
      $campos_telecontrol[$login_fabrica]['tbl_os']['serie']['obrigatorio'] = 0;
    }

    if(in_array($tipo_atendimento,array(66))) {
      $promotor_treinamento2 = intval($_POST["promotor_treinamento2"]);//era promotor_treinamento
      $campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento2']['obrigatorio'] = 1; // era promotor_treinamento
      $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
      $campos_telecontrol[$login_fabrica]['tbl_os']['motivo_ordem']['obrigatorio'] = 1;
    }

    if(in_array($tipo_atendimento,array(13,15,16))) {
      $promotor_treinamento2 = intval($_POST["promotor_treinamento2"]);
      $campos_telecontrol[$login_fabrica]['tbl_os']['promotor_treinamento2']['obrigatorio'] = 1;

      if(in_array($tipo_atendimento,array(13))) {
        $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']['obrigatorio'] = 1;
        $campos_telecontrol[$login_fabrica]['tbl_os']['motivo_ordem']['obrigatorio'] = 1;
      }
    }

    if(in_array($tipo_atendimento, array(13,16,66))){
      $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_email']['obrigatorio'] = 1;
    }

    if(strlen(trim($_POST['consumidor_celular'])) > 0){ //2843341
      $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_fone']['obrigatorio'] = 0;
    }
    if(strlen(trim($_POST['consumidor_fone'])) > 0){ //2843341
      $campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_celular']['obrigatorio'] = 0;
    }
  }
  
    //print_r($campos_telecontrol);
}elseif (strlen($os) > 0) {

  try {
    $sql = "
    SELECT
            tbl_os.sua_os,
            tbl_os.fabrica,
            tbl_os.posto,
            tbl_os.data_abertura,
            tbl_os.consumidor_revenda,
            tbl_os.tipo_atendimento,
            tbl_os.tecnico,
            tbl_os.defeito_constatado,
            tbl_os.data_hora_fechamento,

            tbl_os.produto,
            tbl_os.serie,
            tbl_os.nota_fiscal,
            tbl_os.data_nf,
            tbl_os.prateleira_box,
            tbl_os.defeito_reclamado_descricao,
            tbl_os.defeito_reclamado,
            tbl_os.aparencia_produto,
            tbl_os.acessorios,

            tbl_os.consumidor_cpf,
            tbl_os.consumidor_nome,
            tbl_os.consumidor_fone,
            tbl_os.consumidor_celular,
            tbl_os.consumidor_fone_comercial,
            tbl_os.consumidor_cep,
            tbl_os.consumidor_endereco,
            tbl_os.consumidor_numero,
            tbl_os.consumidor_complemento,
            tbl_os.consumidor_bairro,
            tbl_os.consumidor_cidade,
            tbl_os.consumidor_estado,
            tbl_os.consumidor_email,

            tbl_os.pedagio,
            tbl_os.qtde_km,
            tbl_os.segmento_atuacao,
            tbl_os.causa_defeito,
            tbl_os.solucao_os,
            tbl_os.promotor_treinamento,

            tbl_os.revenda,
            tbl_os.revenda_cnpj,
            tbl_os.revenda_nome,
            tbl_os.revenda_fone,
            tbl_revenda.cep AS revenda_cep,
            tbl_revenda.endereco AS revenda_endereco,
            tbl_revenda.numero AS revenda_numero,
            tbl_revenda.complemento AS revenda_complemento,
            tbl_revenda.bairro AS revenda_bairro,
            tbl_cidade.nome AS revenda_cidade,
            tbl_cidade.estado AS revenda_estado,

            tbl_os.cod_ibge,
            tbl_os.obs
    FROM
    tbl_os
    LEFT JOIN tbl_revenda ON tbl_os.revenda=tbl_revenda.revenda
    LEFT JOIN tbl_cidade ON tbl_revenda.cidade=tbl_cidade.cidade

    WHERE
    tbl_os.os={$os}
    ";

    @$res = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.consultar.os', $con) . " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>");

    $dados_os = pg_fetch_array($res);

    $id_solucao_os = $dados_os['solucao_os'];

        $promotor_treinamento2 =  pg_fetch_result($res, 0, "promotor_treinamento");

        $cod_ibge = $dados_os["cod_ibge"];
        $consumidor_cidade = $dados_os["consumidor_cidade"];
        $consumidor_estado = $dados_os["consumidor_estado"];
        if(!empty($consumidor_cidade) AND !empty($consumidor_estado))
            $dados_os["consumidor_cidade_estado"] = $dados_os["consumidor_cidade"]." - ".$dados_os["consumidor_estado"];

        $revenda_cidade = $dados_os["revenda_cidade"];
        $revenda_estado =  $dados_os["revenda_estado"];
        if(!empty($revenda_cidade) AND !empty($revenda_estado))
            $dados_os["revenda_cidade_estado"] = $dados_os["revenda_cidade"]." - ".$dados_os["revenda_estado"];

    foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $campo => $configuracoes) {
      switch($configuracoes['tipo_dados']) {
        case "date":
          $dados_os[$campo] = implode('/', array_reverse(explode('-', $dados_os[$campo])));
        break;

        case 'datetime':
          $dados_data = is_date($dados_os[$campo], 'ISO', 'EUR');
          // $dados_data = explode(' ', $dados_os[$campo]);
          // $dados_os[$campo] = implode('/', array_reverse(explode('-', $dados_data[0]))) . ' ' . $dados_data[1];
          break;

        case "float":
          $dados_os[$campo] = str_replace(".", ",", $dados_os[$campo]);
        break;
      }

      switch($campo) {
        case "produto":
          $sql = "
          SELECT tbl_produto.referencia,
                 tbl_produto.referencia || ' - ' ||
                 COALESCE(tbl_produto_idioma.descricao, tbl_produto.descricao)
               AS referencia_descricao
            FROM tbl_produto
            LEFT
            JOIN tbl_produto_idioma
              ON tbl_produto_idioma.produto = tbl_produto.produto
             AND idioma = UPPER('$cook_idioma')
           WHERE tbl_produto.produto = {$dados_os[$campo]}
          ";
          $res = pg_query($con, $sql);
          if (strlen(pg_last_error($con)) > 0)
            throw new Exception(
              traduz('falha.ao.consultar.os', $con) .
              " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>"
            );

          $dados_os["{$campo}_id"] = $dados_os[$campo];
          $dados_os[$campo] = pg_fetch_result($res, 0, "referencia_descricao");
          $dados_os["{$campo}_referencia"] = pg_fetch_result($res, 0, "referencia");
          $dados_os["{$campo}_last"] = $dados_os[$campo];

          $produto_id = $dados_os["{$campo}_id"];
          $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['valor_id'] = $dados_os["{$campo}_id"];
          $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['valor_last'] = $dados_os["{$campo}_last"];
          $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['valor_referencia'] = $dados_os["{$campo}_referencia"];
        break;
      }

      $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['valor'] = $dados_os[$campo];
    }

        if(intval($cod_ibge) > 0){
      $sql_ibge = "SELECT cidade, estado
               FROM tbl_ibge
              WHERE cod_ibge = $cod_ibge";
            $res_ibge = @pg_query($con,$sql_ibge);

            if(@pg_num_rows($res_ibge) >0){
                $campos_telecontrol[$login_fabrica]['tbl_os']["consumidor_cidade"]['valor'] = pg_fetch_result($res_ibge, 0, "cidade");
                $campos_telecontrol[$login_fabrica]['tbl_os']["consumidor_estado"]['valor'] = pg_fetch_result($res_ibge, 0, "estado");
            }
        }

    $sql = "
            SELECT tbl_defeito_constatado.defeito_constatado,
             COALESCE(DCI.descricao, tbl_defeito_constatado.descricao) AS descricao,
           tbl_solucao.solucao,
           tbl_solucao.descricao AS solucao_descricao
              FROM tbl_os_defeito_reclamado_constatado
              JOIN tbl_defeito_constatado
                ON tbl_os_defeito_reclamado_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
     LEFT JOIN tbl_defeito_constatado_idioma AS DCI
          ON DCI.defeito_constatado = tbl_defeito_constatado.defeito_constatado
         AND DCI.idioma = '$sistema_lingua'
        JOIN tbl_solucao
          ON tbl_os_defeito_reclamado_constatado.solucao = tbl_solucao.solucao
       WHERE tbl_os_defeito_reclamado_constatado.os      = {$os};";
    $res = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.consultar.os', $con) . " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>");

    for($i = 0; $i < pg_num_rows($res); $i++) {
      extract(pg_fetch_array($res));

      $itens_analise[$i]['defeito_constatado'] = $defeito_constatado_descricao;
            $itens_analise[$i]['defeito_descricao'] = $descricao;
      $itens_analise[$i]['defeito_constatado_id'] = $defeito_constatado;
            if ($login_fabrica == 85) {
                $itens_analise[$i]['defeito_constatado_last'] = $descricao;
            }else{
                $itens_analise[$i]['defeito_constatado_last'] = $defeito_constatado_descricao;
            }

      $itens_analise[$i]['solucao_os'] = $solucao;
      $itens_analise[$i]['solucao_os_descricao'] = $solucao_descricao;
    }
    if($login_fabrica == 87){
      $select_peca_causador   = "tbl_os_item.peca_causadora, peca_causadora.referencia AS referencia_causadora, peca_causadora.descricao AS descricao_causadora,";
      $join_peca_casaudaor  = "LEFT JOIN tbl_peca AS peca_causadora ON tbl_os_item.peca_causadora=peca_causadora.peca";
    }else{
      $select_peca_causador   = "";
      $join_peca_casaudaor  = "";
    }

    $sql = "
    SELECT
    tbl_os_item.os_item,
    tbl_os_item.peca,
    $select_peca_causador
    tbl_peca.referencia AS peca_referencia,
    tbl_peca.descricao AS peca_descricao,
    /*tbl_lista_basica.qtde AS qtde_lb,*/
    tbl_os_item.qtde,
    tbl_os_item.defeito,
    tbl_os_item.servico_realizado,
    tbl_os_item.pedido

    FROM
    tbl_os_item
    JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
    JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
    JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
    $join_peca_casaudaor
    /*JOIN tbl_lista_basica ON tbl_os.produto=tbl_lista_basica.produto
      AND tbl_peca.peca=tbl_lista_basica.peca*/

    WHERE
    tbl_os.os={$os}
    ";

    //echo nl2br($sql);
    $res = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.consultar.os', $con) . " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>");

    for($i = 0; $i < pg_num_rows($res); $i++) {
      extract(pg_fetch_array($res));

      $itens_pecas[$i]['os_item'] = $os_item;
      $itens_pecas[$i]['peca'] = "{$peca_referencia} - {$peca_descricao}";
      $itens_pecas[$i]['peca_id'] = $peca;
      $itens_pecas[$i]['peca_last'] = "{$peca_referencia} - {$peca_descricao}";
      $itens_pecas[$i]['qtde'] = $qtde;
      $itens_pecas[$i]['qtde_lb'] = $qtde_lb;
      $itens_pecas[$i]['defeito'] = $defeito;
      $itens_pecas[$i]['servico'] = $servico_realizado;
      $itens_pecas[$i]['pedido'] = $pedido;

      if($login_fabrica == 87){

        $itens_pecas[$i]['peca_referencia'] = "{$peca_referencia}";
        $itens_pecas[$i]['peca_referencia_descricao'] = "{$peca_referencia} - {$peca_descricao}";

        if(!empty($peca_causadora)){
          $itens_pecas[$i]['item_causador_id'] = "{$peca_causadora}";
          $itens_pecas[$i]['item_causador_referencia'] = "{$referencia_causadora}";
          $itens_pecas[$i]['item_causador_referencia_descricao'] = "{$referencia_causadora} - {$descricao_causadora}";
        }


      }
    }

    $n_linhas_pecas = count($itens_pecas) + 1;
    $n_linhas_analise = count($itens_analise) + 1;

    $sql = "
    SELECT
    tbl_os_extra.hora_tecnica,
    tbl_os_extra.qtde_horas

    FROM
    tbl_os_extra

    WHERE
    tbl_os_extra.os={$os}
    ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0){
      $campos_telecontrol[$login_fabrica]['tbl_os']['horas_trabalhadas']['valor'] = pg_fetch_result($res, 0, "hora_tecnica");
      $campos_telecontrol[$login_fabrica]['tbl_os']['qtde_horas']['valor'] = pg_fetch_result($res, 0, "qtde_horas");
    }
  }
  catch (Exception $e) {
    unset($dados_os);
    unset($itens_pecas);
    unset($itens_analise);
    $msg_erro["analise{$i}|invalido"] = $e->getMessage();
  }
}



if (strlen($login_pais) and $login_pais !== 'BR') {
  unset($campos_telecontrol[$login_fabrica]['tbl_os']['consumidor_cpf']);
}

//Previne de bloquear campos obrigatórios não preenchidos
foreach($campos_telecontrol[$login_fabrica]['tbl_os'] as $campo => $configuracoes) {
  if ($configuracoes['bloqueia_edicao'] == 1 && $configuracoes['obrigatorio'] == 1 && strlen($configuracoes['valor']) == 0) {
    $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['bloqueia_edicao'] = 0;
  }
}

switch ($_POST["btn_acao"])  {
  case "gravar":
  try {

      //hd-2795821
    $sql_posto_fabrica = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
    if(strlen(trim(pg_last_error($con)))== 0 ){
        if(pg_num_rows($res_posto_fabrica)>0){
            $parametros_adicionais = pg_fetch_result($res_posto_fabrica, 0, parametros_adicionais);
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            
            $gera_pedido = (strlen(trim($parametros_adicionais['gera_pedido']))>0 )? $parametros_adicionais['gera_pedido'] : 'f';
        }
    }else{
        $msg_erro .= pg_last_error($con);
    }


    $tipo_atendimento = $_POST["tipo_atendimento"];

    // HD 3032759 - Salvar dados do anexo da foto da série do produto
    if ($anexaFotoSerie(compact('serie', 'tipo_atendimento'))) {
      if ($_FILES['anexo_serie']['tmp_name']) {
        $tDocs = new TDocs($con, $login_fabrica);

        // Exclui o anterior, pois não será usado
        if ($anexoID) {
          $idExcluir = $anexoID;
        }

        $anexoID  = $tDocs->sendFile($_FILES['anexo_serie']);
        $fileData = $tDocs->sentData;

        if (!$anexoID) {
          $msg_erro[] = 'Erro ao salvar o arquivo! '.$tDocs->error;
        } else {
          // Se ocorrer algum erro, o anexo está salvo:
          $_POST['anexo_serie'] = json_encode($tDocs->sentData);

          if (isset($idExcluir))
            $tDocs->deleteFileById($idExcluir);
        }
      } elseif ($_POST['anexo_serie']) {
        $_POST['anexo_serie'] = $anexo_serie = stripslashes($_POST['anexo_serie']);
        $fileData = json_decode($anexo_serie, true);
        $anexoID  = $fileData['tdocs_id'];
      } else if (!empty($os)) {

        $sqlVerificaTdocs = "SELECT tdocs FROM tbl_tdocs 
                   WHERE referencia_id = {$os} 
                   AND referencia = 'osserie'
                   AND situacao = 'ativo'";
        $resVerificaTdocs = pg_query($con, $sqlVerificaTdocs);

        if (pg_num_rows($resVerificaTdocs) == 0) {
          $msg_erro[] = traduz('foto.do.num.de.serie.ausente/ilegivel.e.obrigatoria', $con);
        }

      } else {
        // $msg_erro[] = traduz('numero.de.serie', $con).' '.traduz('anexo.obrigatorio', $con);
        $msg_erro[] = traduz('foto.do.num.de.serie.ausente/ilegivel.e.obrigatoria', $con);
      }
      $fileName = $fileData['name'];
      $anexoSerieURL = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID.'/file/'.$fileName;
    }

    if ($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)) {

      $dados_campos = array();
      $motivo_ordem = utf8_encode(trim($_POST["motivo_ordem"]));

      $dados_campos['motivo_ordem'] = $motivo_ordem;

      if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){

        $mostrar['peca_nao_disponivel'] = 'block';

        $dados_campos['codigo_peca_1']  = utf8_encode($_POST["codigo_peca_1"]);
        $dados_campos['codigo_peca_2']  = utf8_encode($_POST["codigo_peca_2"]);
        $dados_campos['codigo_peca_3']  = utf8_encode($_POST["codigo_peca_3"]);
        $dados_campos['numero_pedido_1']  = utf8_encode($_POST["numero_pedido_1"]);
        $dados_campos['numero_pedido_2']  = utf8_encode($_POST["numero_pedido_2"]);
        $dados_campos['numero_pedido_3']  = utf8_encode($_POST["numero_pedido_3"]);

        if(strlen(trim($dados_campos['codigo_peca_1']))==0 AND strlen(trim($dados_campos['codigo_peca_2']))==0 AND strlen(trim($dados_campos['codigo_peca_3']))==0){
          $msg_erro[] .= traduz('por.favor.informar.o.codigo.da.peca') .'<br />';
        }

        if(strlen(trim($dados_campos['numero_pedido_1']))==0 AND strlen(trim($dados_campos['numero_pedido_2']))==0 AND strlen(trim($dados_campos['numero_pedido_3']))==0){
          $msg_erro[] .= traduz('por.favor.informar.o.numero.do.pedido') . '<br />';
        }
      }

      if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
        $dados_campos['descricao_peca_1'] = utf8_encode($_POST["descricao_peca_1"]);
        $dados_campos['descricao_peca_2'] = utf8_encode($_POST["descricao_peca_2"]);
        $dados_campos['descricao_peca_3'] = utf8_encode($_POST["descricao_peca_3"]);

        if(strlen(trim($dados_campos['descricao_peca_1']))==0 AND strlen(trim($dados_campos['descricao_peca_2']))==0 AND strlen(trim($dados_campos['descricao_peca_3']))==0){
          $msg_erro[] .= traduz('por.favor.informar.a.descricao.da.peca') . '<br />';
        }
      }
      if($motivo_ordem == 'PROCON (XLR)'){
        $dados_campos['protocolo'] = utf8_encode($_POST["protocolo"]);
      }
      if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
        $dados_campos['ci_solicitante'] = utf8_encode($_POST["ci_solicitante"]);
      }
      if($motivo_ordem == "Linha de Medicao (XSD)"){
        $dados_campos['linha_medicao'] = utf8_encode($_POST["linha_medicao"]);
      }
      if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
        $dados_campos['pedido_nao_fornecido'] = utf8_encode($_POST["pedido_nao_fornecido"]);
      }

      if($motivo_ordem == 'Contato SAC (XLR)'){ //HD-3200578
        if(strlen(trim($_POST['contato_sac'])) == 0){
          $msg_erro[] .= "Campo N° do Chamado é obrigatório";
        }else{
          $dados_campos['contato_sac'] = utf8_encode($_POST['contato_sac']);
        }
      }

      if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){//HD-3200578
        if(strlen(trim($_POST['detalhe'])) == 0){
          $msg_erro[] .= traduz('o.campo.%.e.obrigatorio', null, $cook_idioma, array(ucfirst(traduz('detalhe'))));
        }else{
          $dados_campos['detalhe'] = utf8_encode($_POST['detalhe']);
        }
      }
      extract($dados_campos);
    }

    if($login_fabrica == 85){
      include "class/log/log.class.php";
      $log = new Log();
    }

    $itens_analise = array();

    for($i = 0; $i < $n_linhas_analise; $i++) {

      if($login_fabrica == 20 AND in_array($tipo_atendimento,array(11,12,13,66))) { //hd_chamado=2843341
        $defeito_constatado = 12845;
      }else{
        $defeito_constatado = $_POST["defeito_constatado{$i}"];
      }
      $defeito_constatado_id   = intval($_POST["defeito_constatado{$i}_id"]);
      $defeito_constatado_last = $_POST["defeito_constatado{$i}_last"];
      $solucao_os              = intval($_POST["solucao_os{$i}"]);
      $sql                     = "SELECT descricao FROM tbl_solucao WHERE solucao={$solucao_os}";
      $res                     = pg_query($con, $sql);
      $solucao_os_descricao    = pg_num_rows($res) > 0 ? pg_fetch_result($res, 0, "descricao") : "";


      $itens_analise[$i]['defeito_constatado']      = $defeito_constatado;
      $itens_analise[$i]['defeito_constatado_id']   = $defeito_constatado_id;
      $itens_analise[$i]['defeito_constatado_last'] = $defeito_constatado_last;
      $itens_analise[$i]['solucao_os']              = $solucao_os;
      $itens_analise[$i]['solucao_os_descricao']    = $solucao_os_descricao;
    }

    $itens_pecas = array();

    //verifica se o campo existe se não existe vai pesquisar na tabela
    //if($cod_ibge == 0){
    $cod_ibge = null;
    if (($login_pais == 'BR' or empty($login_pais)) and !empty($consumidor_cidade) AND !empty($consumidor_estado)) {

      $sql_ibdge = "
        SELECT cod_ibge
          FROM tbl_ibge
         WHERE UPPER(fn_retira_especiais(cidade)) LIKE UPPER(fn_retira_especiais('$consumidor_cidade'))
           AND estado = '$consumidor_estado'
         ORDER BY length(cidade) ASC;";

      $res_ibdge = pg_query($con, $sql_ibdge);

      if (pg_num_rows($res_ibdge) > 0){
        $cod_ibge = pg_fetch_result($res_ibdge, 0, "cod_ibge");
        //$msg_erro[] = traduz('estado.ou.cidade.nao.cadastrado.no.ibge', $con);
      }
    }
    //}

    for($i = 0; $i < $n_linhas_pecas; $i++) {

      if($login_fabrica == 87){
        $os_item = $_POST["os_item{$i}"];
        $peca_id = $_POST["peca_id{$i}"];
        $peca    = $_POST["peca{$i}"];
        $qtde    = $_POST["qtde{$i}"];
        $qtde_lb = intval($_POST["qtde_lb{$i}"]);
        $defeito = intval($_POST["defeito{$i}"]);
        $servico = intval($_POST["servico{$i}"]);

        $item_causador_id                   = $_POST["item_causador_id{$i}"];
        $peca_referencia                    = $_POST["peca_referencia{$i}"];
        $peca_referencia_descricao          = $_POST["peca_referencia_descricao{$i}"];
        $item_causador_referencia           = $_POST["item_causador_referencia{$i}"];
        $item_causador_referencia_descricao = $_POST["item_causador_referencia_descricao{$i}"];

        if(!empty($peca_referencia_descricao)){
          $itens_pecas[$i]['os_item']                  = $os_item;
          $itens_pecas[$i]['peca_id']                  = $peca_id;
          $itens_pecas[$i]['peca_referencia']          = $peca_referencia;
          $itens_pecas[$i]['peca_descricao']           = $peca_descricao;
          $itens_pecas[$i]['qtde']                     = $qtde;
          $itens_pecas[$i]['qtde_lb']                  = $qtde_lb;
          $itens_pecas[$i]['item_causador_id']         = $item_causador_id;
          $itens_pecas[$i]['item_causador_referencia'] = $item_causador_referencia;
          $itens_pecas[$i]['item_causador_descricao']  = $item_causador_descricao;
          $itens_pecas[$i]['defeito']                  = $defeito;
          $itens_pecas[$i]['servico']                  = $servico;
        }

      }else{
        $os_item   = intval($_POST["os_item{$i}"]);
        $peca      = $_POST["peca{$i}"];
        $peca_id   = intval($_POST["peca{$i}_id"]);
        $peca_last = $_POST["peca{$i}_last"];
        $qtde      = strlen($_POST["qtde{$i}"]) > 0 ? floatval(str_replace(",", ".", $_POST["qtde{$i}"])) : 1;
        $qtde_lb   = intval($_POST["qtde_lb{$i}"]);
        $defeito   = intval($_POST["defeito{$i}"]);
        $servico   = intval($_POST["servico{$i}"]);

        $itens_pecas[$i]['os_item']   = $os_item;
        $itens_pecas[$i]['peca']      = $peca;
        $itens_pecas[$i]['peca_id']   = $peca_id;
        $itens_pecas[$i]['peca_last'] = $peca_last;
        $itens_pecas[$i]['qtde']      = $qtde;
        $itens_pecas[$i]['qtde_lb']   = $qtde_lb;
        $itens_pecas[$i]['defeito']   = $defeito;
        $itens_pecas[$i]['servico']   = $servico;

      }


    }

    foreach ($campos_telecontrol[$login_fabrica]['tbl_os'] as $campo => $configuracoes) {

      $valor = $configuracoes['valor'];
      $valida_cnpj_cpf = false;

      if ($configuracoes['obrigatorio'] == 1 && strlen($valor) == 0) {
        $msg_erro["{$campo}|obrigatorio"] = traduz('o.campo.%.e.obrigatorio', $con, $cook_idioma, array($configuracoes["label"]));
      }

      switch ($campo) {
        case "produto":
          $produto_id = $_POST["produto_id"];

          $valida = valida_campo_autocomplete("produto");
          if ($valida === true) {
            $sql = "
              SELECT tbl_produto.produto
              FROM tbl_produto
              JOIN tbl_linha
              ON tbl_produto.linha   = tbl_linha.linha
              WHERE tbl_produto.produto = {$produto_id}
              AND tbl_linha.fabrica   = {$login_fabrica}
              ";
            $res = @pg_query($con, $sql);

            if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.validar.produto', $con));

            if (@pg_num_rows($res) == 0) {
              $msg_erro[] = traduz('o.produto.selecionado.nao.foi.encontrado', $con);
            }
          }
          else {
            $msg_erro["{$campo}|invalido"] = $valida;
          }
        break;
        case "consumidor_cpf":
          $valida_cnpj_cpf = (!$login_pais or $login_pais == 'BR');
        break;
        case "consumidor_email":
          if (strlen($valor) > 0 && !preg_match(RE_EMAIL, $valor)) {
            $msg_erro["{$campo}|invalido"] = traduz('email.invalido', $con);
          }
        break;

        case "revenda_cnpj":
          $valida_cnpj_cpf = true;
        break;
      }

      if ($valida_cnpj_cpf && strlen($valor) > 0) {
        $valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$valor));

        if(empty($valida_cpf_cnpj)){
          $sql = "SELECT fn_valida_cnpj_cpf('$valor')";
          @$res = pg_query($con, $sql);
          if (strlen(pg_last_error($con)) > 0) {
            $msg_erro["{$campo}|invalido"] = traduz('cpfcnpj.invalido.para.o.campo.%', $con, $cook_idioma, array($configuracoes["label"]));
          }
        }else{
          $msg_erro["{$campo}|invalido"] = "$valida_cpf_cnpj para {$configuracoes["label"]}";
        }
                $campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['bloqueia_edicao'] = 0;
      }

      switch($configuracoes['tipo_dados']) {
      case "int":
        $valor = intval(preg_replace( '/[^0-9]+/', '', $valor));
        break;

      case "float":
        $valor = floatval(str_replace(",", ".", $valor));
        break;

      case "date":
        if (strlen($valor) > 0) {
          $valor = implode('-', array_reverse(explode('/', $valor)));
          $sql = "SELECT '{$valor}'::date";
          @$res = pg_query($con, $sql);

          if (strlen(pg_last_error()) > 0) {
            $msg_erro["{$campo}|invalido"] = traduz('data.invalida.para.o.campo.%', $con, $cook_idioma, array($configuracoes["label"]));
          }
          $valor = "'{$valor}'";
        }
        else {
          $valor = "NULL";
        }
        break;

      default:
        if (strlen($valor) > 0) {
          if ($configuracoes['tamanho'] > 0) $valor = substr($valor, 0, $configuracoes['tamanho']);
        }
        $valor = "'{$valor}'";
      }

      //Declara uma variável com nome do conteúdo da variável $campo e atribui $valor a ele
      //Ex: Sendo $campo igual a "consumidor_nome" a linha abaixo resultará em uma variável
      //    $consumidor_nome contendo o conteúdo de $valor
      $$campo = ($configuracoes['tipo_dados'] == 'text' and strlen($valor) == 0) ? 'NULL' : $valor;
    }

      //valida numero de serie
      if($login_fabrica == 87){
        $produto_id = $_POST["produto_id"];
        if(!empty($serie) && !empty($produto_id)){
          $sql_valida_serie = "
            SELECT numero_serie
              FROM tbl_numero_serie
             WHERE fabrica = $login_fabrica
               AND produto = $produto_id
               AND serie   = $serie";
          $res_valida_serie = pg_query($con, $sql_valida_serie);

          if (pg_num_rows($res_valida_serie) == 0) throw new Exception(traduz('numero.de.serie.invalido.para.o.produto.%', $con, $cook_idioma, (array) $produto_referencia));
        }else{
          $msg_erro[] = traduz('numero.de.serie.ou.produto.invalido', $con);
        }
      }

            $comp_serie = str_replace("'", "", $serie);
//             $comp_serie = (int)$comp_serie;
            if(in_array($login_fabrica, array(20))){


                if(strlen($comp_serie) != 3 && strlen($comp_serie) != 9){
                    $msg_erro[] = traduz('o.numero.de.serie.deve.conter.%.digitos', $con, $cook_idioma, array(9));
                }


            } else if (in_array($login_fabrica,array(85))) {
                if(strlen($comp_serie) != 10 || !is_numeric($comp_serie)) {
                    $msg_erro[] = traduz('o.numero.de.serie.deve.ser.apenas.numeros', $con);
                }
            }
// echo $comp_serie."->>".is_numeric($comp_serie)."__".strlen($comp_serie);
      if ($tipo_atendimento == 1) {
        $qtde_km = 0;
        $pedagio = 0;
      }

      $itens_analise = array();
      unset($defeito_constatado_principal);
      unset($solucao_os_principal);

      for($i = 0; $i < $n_linhas_analise; $i++) {
        try {
          if($login_fabrica == 20 AND in_array($tipo_atendimento,array(11,12,13,66))) { //hd_chamado=2843341
            $defeito_constatado = 12845;
          }else{
            $defeito_constatado = $_POST["defeito_constatado{$i}"];
          }
          $defeito_constatado_id = intval($_POST["defeito_constatado{$i}_id"]);
          $defeito_constatado_last = $_POST["defeito_constatado{$i}_last"];
          $solucao_os = intval($_POST["solucao_os{$i}"]);

          if (strlen($defeito_constatado) == 0) throw new Exception(traduz('defeito.constatado.nao.selecionado', $con), 0);
          if ($login_fabrica != 20) {
            if ($solucao_os == 0) throw new Exception(traduz('selecione.uma.solucao.para.o.defeito', $con, (array) $defeito_constatado), 1);

            $label = $campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['label'];
            $valida = valida_campo_autocomplete("defeito_constatado{$i}", $label);

          }
          if ($valida !== true) throw new Exception($valida, 1);

          $sql = "
            SELECT
            tbl_diagnostico.defeito_constatado

            FROM
            tbl_diagnostico
            JOIN tbl_produto ON tbl_diagnostico.linha=tbl_produto.linha AND tbl_diagnostico.familia=tbl_produto.familia AND tbl_produto.produto={$produto_id}
            JOIN tbl_defeito_constatado ON tbl_diagnostico.defeito_constatado=tbl_defeito_constatado.defeito_constatado
            JOIN tbl_solucao ON tbl_diagnostico.solucao=tbl_solucao.solucao

            WHERE
            tbl_diagnostico.fabrica=$login_fabrica
            AND tbl_diagnostico.ativo = 't'
            AND tbl_diagnostico.defeito_constatado={$defeito_constatado_id}
            AND tbl_diagnostico.solucao={$solucao_os}
            ";
          @$res = pg_query($con, $sql);

          if (pg_num_rows($res) == 0) throw new Exception(traduz('defeito.constatado.x.solucao.invalidos.para.este.produto', $con));

          $sql = "SELECT descricao FROM tbl_solucao WHERE solucao={$solucao_os}";
          $res = pg_query($con, $sql);
          $solucao_os_descricao = pg_fetch_result($res, 0, "descricao");

          $itens_analise[$i]['defeito_constatado']      = $defeito_constatado;
          $itens_analise[$i]['defeito_constatado_id']   = $defeito_constatado_id;
          $itens_analise[$i]['defeito_constatado_last'] = $defeito_constatado_last;
          $itens_analise[$i]['solucao_os']              = $solucao_os;
          $itens_analise[$i]['solucao_os_descricao']    = $solucao_os_descricao;

          if (!isset($defeito_constatado_principal)) {
            $defeito_constatado_principal = $defeito_constatado_id;
          }

          if (!isset($solucao_os_principal)) {
            $solucao_os_principal = $solucao_os;
          }
        }
        catch (Exception $e) {
          if ($e->getCode() == 1) {
            $msg_erro["analise{$i}|invalido"] = $e->getMessage();
          }
        }
      }

      if (!isset($defeito_constatado_principal)) {
        $defeito_constatado_principal = "NULL";
      }

      if (!isset($solucao_os_principal)) {
        $solucao_os_principal = "NULL";
      }

      $itens_pecas = array();//esta aqui já

      if(!in_array($login_fabrica, array(20,85))){
        $n_linhas_pecas = $n_linhas_pecas - 1;
      }

      for ($i = 0; $i < $n_linhas_pecas; $i++) {
        try{
          if($login_fabrica == 87){
            $peca_referencia_descricao       = $_POST["peca_referencia_descricao{$i}"];
            $item_causador_referencia_descricao  = $_POST["item_causador_referencia_descricao{$i}"];
            $qtde    = $_POST["qtde{$i}"];
            $defeito = intval($_POST["defeito{$i}"]);
            $servico = intval($_POST["servico{$i}"]);
            $os_item = intval($_POST["os_item{$i}"]);

            if(!empty($peca_referencia_descricao)){

              $array_peca = explode("-", $peca_referencia_descricao);
              $peca_referencia = trim($array_peca[0]);

              $sql = "SELECT tbl_peca.peca FROM tbl_peca WHERE tbl_peca.fabrica={$login_fabrica} AND tbl_peca.referencia='{$peca_referencia}' AND tbl_peca.produto_acabado IS NOT TRUE";
              $res = pg_query($con, $sql);

              if (pg_num_rows($res) == 0){
                 $msg_erro[] = traduz('peca.%.nao.encontrada', $con, $cook_idioma, (array)$peca_referencia);
                //throw new Exception(traduz("peca.%.nao.encontrada", $con, $cook_idioma,$peca_referencia["label"]), 1);
                            }else
                $peca = pg_fetch_result($res, 0, "peca");
            }

                        //valida a quantidade de peça solicitada somente quando é inserção...
                        if(is_null($os_item) OR $os_item == 0){
                            if(!empty($peca_referencia_descricao) AND $qtde == 0){
                $msg_erro[] = traduz('quantidade.invalida.para.a.peca.%', $con, $cook_idioma, array($peca_referencia));
                            }
                        }

                        if(!empty($peca_referencia_descricao) AND ($defeito == 0 OR is_null($defeito))){
              $msg_erro[] = traduz('selecione.um.defeito.para.a.peca.%', $con, $cook_idioma, array($peca_referencia));
                        }

            if(!empty($item_causador_referencia_descricao)){

              $array_peca = explode("-", $item_causador_referencia_descricao);
              $item_causador_referencia = trim($array_peca[0]);

              $sql = "
                SELECT tbl_peca.peca
                  FROM tbl_peca
                 WHERE tbl_peca.fabrica    = {$login_fabrica}
                   AND tbl_peca.referencia = '{$item_causador_referencia}'
                   AND tbl_peca.produto_acabado IS NOT TRUE";
              $res = pg_query($con, $sql);

              if (pg_num_rows($res) == 0){
                $msg_erro[] =  traduz('item.causador.%.nao.encontrado', $con, $cook_idioma, array($item_causador_referencia_descricao));
                //throw new Exception(traduz("item.causador.%.nao.encontrada", $con, $cook_idioma,$item_causador_referencia_descricao["label"]), 1);
                            }else
                $item_causador = pg_fetch_result($res, 0, "peca");
            }else{
              $item_causador = 'null';
              $item_causador_referencia = null;
            }

                        if(!empty($peca_referencia_descricao) AND ($servico == 0 OR is_null($servico))){
                            $msg_erro[] = traduz('selecione.um.servico.para.a.peca.%', $con, $cook_idioma, (array) $peca_referencia);
                        }


            if(strlen($peca) > 0){

              $sql_lb = "SELECT qtde as qtde_lb FROM tbl_lista_basica WHERE produto={$produto_id} AND peca={$peca}";
              $res_lb = pg_query($con, $sql_lb);
              $qtde_lb = pg_num_rows($res_lb) > 0 ? pg_result($res_lb, 0, "qtde_lb") : 0;

              if ($qtde > $qtde_lb && $login_fabrica != 87){
                $msg_erro[] = traduz('a.quantidade.digitada.%.e.maior.que.a.quantidade.permitida.%.para.a.lista.basica.do.produto.%',
                           $con, $cook_idioma,
                           array($qtde, $qtde_lb, $produto)
                        );
              }


              $itens_pecas[$i]['peca'] = $peca;
              $os_item = intval($_POST["os_item{$i}"]);

              $itens_pecas[$i]['qtde'] = $qtde;
              $itens_pecas[$i]['qtde_lb'] = $qtde_lb;

              $itens_pecas[$i]['os_item'] = $os_item;
              $itens_pecas[$i]['peca_id'] = $peca;
              $itens_pecas[$i]['peca_referencia_descricao'] = $peca_referencia_descricao;
              $itens_pecas[$i]['peca_referencia'] = $peca_referencia;
              $itens_pecas[$i]['peca_descricao'] = $peca_descricao;

                            $itens_pecas[$i]['defeito'] = $defeito;
                            $itens_pecas[$i]['servico'] = $servico;

              $itens_pecas[$i]['peca_causadora'] = $item_causador;
              $itens_pecas[$i]['item_causador_id'] = $item_causador;
              $itens_pecas[$i]['item_causador_referencia_descricao'] = $item_causador_referencia_descricao;
              $itens_pecas[$i]['item_causador_referencia'] = $item_causador_referencia;
              $itens_pecas[$i]['item_causador_descricao'] = $item_causador_descricao;

                            $peca = null;
            }

            $peca = null;
          }else{
            $os_item = intval($_POST["os_item{$i}"]);
            $peca = $_POST["peca{$i}"];
            $peca_id = intval($_POST["peca{$i}_id"]);
                        $peca_last = $_POST["peca{$i}_last"];
            if ( $login_fabrica != 20) {
              $qtde = strlen($_POST["qtde{$i}"]) > 0 ? floatval(str_replace(",", ".", $_POST["qtde{$i}"])) : 1;
            } else {
              $qtde = strlen($_POST["qtde{$i}"]) > 0 ? floatval(str_replace(",", ".", $_POST["qtde{$i}"])) : 0;
            }
            $defeito = intval($_POST["defeito{$i}"]);
            $servico = intval($_POST["servico{$i}"]);

            if (strlen($peca) == 0)
              throw new Exception(traduz('peca.nao.selecionada', $con), 0);
            if ($qtde == 0 && $login_fabrica != 20)
              throw new Exception(traduz(array('quantidade','zerada'), $con), 0);

            $label = $campos_telecontrol[$login_fabrica]['tbl_os']['peca']['label'];
            $valida = valida_campo_autocomplete("peca{$i}", $label);
            if ($valida !== true)
              throw new Exception($valida, 1);

            $sql = "SELECT tbl_peca.peca FROM tbl_peca WHERE tbl_peca.fabrica={$login_fabrica} AND tbl_peca.peca={$peca_id} AND tbl_peca.produto_acabado IS NOT TRUE";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) == 0) throw new Exception(traduz('peca.%.nao.encontrada', $con, $cook_idioma, (array) $peca), 1);
            if ($login_fabrica != 20) {
              $sql = "SELECT tbl_defeito.defeito FROM tbl_defeito WHERE tbl_defeito.fabrica={$login_fabrica} AND tbl_defeito.defeito={$defeito}";
              $res = pg_query($con, $sql);
              if (pg_num_rows($res) == 0) throw new Exception(traduz('defeito.nao.cadastrado.selecionado.para.a.peca.%', $con, $cook_idioma, (array) $peca), 1);

              $sql = "SELECT tbl_servico_realizado.servico_realizado FROM tbl_servico_realizado WHERE tbl_servico_realizado.fabrica={$login_fabrica} AND tbl_servico_realizado.servico_realizado={$servico}";
              $res = pg_query($con, $sql);
              if (pg_num_rows($res) == 0) throw new Exception(traduz('servico.nao.cadastrado.selecionado.para.a.peca.%', $con, $cook_idioma, array($peca)), 1);
            }

            $sql = "SELECT qtde FROM tbl_lista_basica WHERE produto={$produto_id} AND peca={$peca_id}";
            $res = pg_query($con, $sql);
            $qtde_lb = pg_num_rows($res) > 0 ? pg_fetch_result($res, 0, "qtde") : 0;

            if ($qtde > $qtde_lb && $login_fabrica != 20)
              throw new Exception(traduz(
                  'a.quantidade.digitada.%.e.maior.que.a.quantidade.permitida.%.para.a.lista.basica.do.produto.%',
                  $con, $cook_idioma,
                  array($qtde, $qtde_lb, $produto)
                )
              );


            $sql = "SELECT pedido FROM tbl_os_item WHERE os_item={$os_item}";
            $res = pg_query($con, $sql);
            $pedido = pg_num_rows($res) > 0 ? pg_fetch_result($res, 0, "pedido") : 0;

            $itens_pecas[$i]['os_item']   = $os_item;
            $itens_pecas[$i]['peca']      = $peca;
            $itens_pecas[$i]['peca_id']   = $peca_id;
            $itens_pecas[$i]['peca_last'] = $peca_last;
            $itens_pecas[$i]['qtde']      = $qtde;
            $itens_pecas[$i]['qtde_lb']   = $qtde_lb;
            $itens_pecas[$i]['defeito']   = $defeito;
            $itens_pecas[$i]['servico']   = $servico;
            $itens_pecas[$i]['pedido']    = $pedido;

          }
        } catch (Exception $e) {
          if ($e->getCode() == 1) {
            $msg_erro["peca{$i}|invalido"] = $e->getMessage();
          }
        }
      }

      if (count($itens_analise) == 0 && count($itens_pecas) > 0 && $login_fabrica <> 87 && $login_fabrica <> 20)
        $msg_erro[] = traduz('preencher.a.analise.da.os', $con);

        if ((empty($defeito_constatado) OR intval($defeito_constatado) == 0) AND count($itens_pecas) > 0 AND $login_fabrica == 87)
          $msg_erro[] = traduz('informe.um.defeito.constatado', $con);

        if (($tecnico == 0 || empty($tecnico)) AND count($itens_pecas) > 0 AND $login_fabrica == 87)
          $msg_erro[] = traduz('informe.um.tecnico', $con);

        if ($login_fabrica == 20) {
      $data_fechamento_hora = is_date(trim($_POST['data_hora_fechamento']));

      if ($data_fechamento_hora) {
        $data_fechamento = substr($data_fechamento_hora, 0, 10);

        if (!_is_in($data_fechamento_hora, str_replace("'", '', "$data_abertura::agora"))) {
          $msg_erro[] = strtotime($data_fechamento_hora) > strtotime('now') ?
            traduz('data.fechamento.nao.pode.ser.maior.atual') :
            traduz('data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura');
        }
      }
        }

        if(strlen($revenda_cidade) > 2){
          $sql = "
          SELECT cidade
          FROM tbl_cidade
           WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais({$revenda_cidade}))
           AND UPPER(estado)                    = UPPER({$revenda_estado})";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res) > 0) {
        $revenda_cidade_id = pg_fetch_result($res, 0, "cidade");
      } else {
        $sql = "
          SELECT cidade, estado
            FROM tbl_ibge
           WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais({$revenda_cidade}))
             AND UPPER(estado)                      = UPPER({$revenda_estado})";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
          $cidade_ibge        = pg_fetch_result($res, 0, "cidade");
          $cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

          $sql = "INSERT INTO tbl_cidade (
            nome, estado
          ) VALUES (
            '{$cidade_ibge}', '{$cidade_estado_ibge}'
          ) RETURNING cidade";
          $res = pg_query($con, $sql);

          $revenda_cidade_id = pg_fetch_result($res, 0, "cidade");
        } else {
          $msg_erro[] = traduz('a.cidade.da.revenda.nao.foi.encontrada', $con);
        }
      }
        }else{
          $revenda_cidade_id = 'null';
        }

    if (count($msg_erro) > 0) {
      throw new Exception(traduz('falha.na.validacao.dos.dados.da.os', $con));
    }

    $res = pg_query($con, "BEGIN");

    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz("falha.ao.iniciar.transacao", $con, $cook_idioma));
    /*
    if($login_pais == 'BR'){
      $revenda_cidade = "'$revenda_cidade'";
      $revenda_estado = "'$revenda_estado'";
    }
    */

    if ($revenda_cnpj) {
      $sql = "
        SELECT tbl_revenda.revenda
          FROM tbl_revenda
         WHERE tbl_revenda.cnpj = {$revenda_cnpj} LIMIT 1
      ";
      $res = pg_query($con, $sql);

      if (pg_num_rows($res) == 1) {
        $revenda_id = pg_fetch_result($res, 0, "revenda");

        $sql = "
        UPDATE
        tbl_revenda

        SET
        nome={$revenda_nome},
        fone={$revenda_fone},
        cep={$revenda_cep},
        endereco={$revenda_endereco},
        numero={$revenda_numero},
        complemento={$revenda_complemento},
        bairro={$revenda_bairro},
        cidade={$revenda_cidade_id}

        WHERE
        revenda={$revenda_id}
        ";
        $res = pg_query($con, $sql);
      }
      else {
        $sql = "
        INSERT INTO tbl_revenda(
          cnpj,
          nome,
          fone,
          cep,
          endereco,
          numero,
          complemento,
          bairro,
          cidade
        )VALUES(
          {$revenda_cnpj},
          {$revenda_nome},
          {$revenda_fone},
          {$revenda_cep},
          {$revenda_endereco},
          {$revenda_numero},
          {$revenda_complemento},
          {$revenda_bairro},
          {$revenda_cidade_id}
        ) RETURNING revenda;";
        $res = pg_query($con, $sql);

        $revenda_id = pg_fetch_result($res, 0, "revenda");
      }
    }

    $nova_os = false;

      $causa_defeito                = empty($causa_defeito)               ? "null" : $causa_defeito;
      $promotor_treinamento         = empty($promotor_treinamento2)       ? "null" : $promotor_treinamento2;
      $segmento_atuacao             = empty($segmento_atuacao)            ? "null" : $segmento_atuacao;
      $tecnico                      = empty($tecnico)                     ? "null" : $tecnico;
      $defeito_reclamado            = empty($defeito_reclamado)           ? "null" : $defeito_reclamado;
      $causa_defeito                = empty($causa_defeito)               ? "null" : $causa_defeito;
      $defeito_constatado           = empty($defeito_constatado)          ? "null" : $defeito_constatado;
      $solucao_os                   = empty($solucao_os)                  ? "null" : $solucao_os;
      $defeito_reclamado_descricao  = empty($defeito_reclamado_descricao) ? "''"   : $defeito_reclamado_descricao;
      $defeito_constatado_principal = empty($defeito_constatado)          ? "null" : $defeito_constatado;
      $cod_ibge                     = $cod_ibge == 0 ? "null" : $cod_ibge;


      if (!in_array($login_fabrica,array(20,87))) {
        $defeito_constatado_principal = "null";
      }

      if($login_fabrica == 85){
          $defeito_constatado_principal = $_POST["defeito_constatado0_id"];
          $defeito_constatado_principal = empty($defeito_constatado_principal) ? "null" : $defeito_constatado_principal;
      }else{
        $defeito_constatado_principal = (!empty($defeito_constatado)) ? $defeito_constatado : 'null';
      }

    if ($login_fabrica == 20) {
      $id_solucao_os         = empty($_POST['solucao_os'])         ? 'null' : $_POST['solucao_os'];

      if($login_fabrica == 20 AND in_array($tipo_atendimento,array(11,12,13,66))) { //hd_chamado=2843341
        $defeito_constatado_principal = $defeito_constatado;
      }else{
        $defeito_constatado_principal = empty($_POST['defeito_constatado']) ? 'null' : $_POST['defeito_constatado'];

      }

      $causa_defeito        = empty($_POST['causa_defeito'])      ? 'null' : $_POST['causa_defeito'];
      $solucao_os_principal = empty($_POST['solucao_os'])         ? 'null' : $_POST['solucao_os'];
      #$consumidor_revenda  = empty($_POST['consumidor_revenda']) ? 'null' : $_POST['consumidor_revenda'];
      $consumidor_revenda   = "'C'";
      $acessorios           = empty($_POST['acessorios'])         ? 'null' : $_POST['acessorios'];
      $consumidor_cpf       = empty($_POST['consumidor_cpf'])     ? 'null' : $_POST['consumidor_cpf'];

      $aparencia_produto = "'USN'";
      $segmento_atuacao  = 1;
      $defeito_reclamado = 3701;

      //hd_chamado=2806621
      $consumidor_cep         = empty($_POST['consumidor_cep'])         ? 'null' : $_POST['consumidor_cep'];
      $consumidor_endereco    = empty($_POST['consumidor_endereco'])    ? 'null' : $_POST['consumidor_endereco'];
      $consumidor_numero      = empty($_POST['consumidor_numero'])      ? 'null' : $_POST['consumidor_numero'];
      $consumidor_complemento = empty($_POST['consumidor_complemento']) ? 'null' : $_POST['consumidor_complemento'];
      $consumidor_bairro      = empty($_POST['consumidor_bairro'])      ? 'null' : $_POST['consumidor_bairro'];
      $consumidor_cidade      = empty($_POST['consumidor_cidade'])      ? 'null' : $_POST['consumidor_cidade'];
      $consumidor_estado      = empty($_POST['consumidor_estado'])      ? 'null' : $_POST['consumidor_estado'];
      $revenda_id             = empty($_POST['revenda'])                ? 'null' : $_POST['revenda'];
      $revenda_cnpj           = empty($_POST['revenda_cnpj'])           ? 'null' : $_POST['revenda_cnpj'];
      $revenda_nome           = empty($_POST['revenda_nome'])           ? 'null' : $_POST['revenda_nome'];
      $revenda_fone           = empty($_POST['revenda_fone'])           ? 'null' : $_POST['revenda_fone'];

      if(strlen(trim($data_fechamento_hora)) == 0){
        $data_fechamento_hora = 'null';
      }else{
        $data_fechamento_hora = "'$data_fechamento_hora'";
      }
      //hd_chamado=2806621 fim
      if(strlen($consumidor_cpf) >= 11){
        $consumidor_cpf = "'$consumidor_cpf'";
      }
    }

    if(strlen($_GET['hd_chamado']) > 0) {
        /* VERIFICA SE O POSTO LOGADO É O MESMO QUE ESTA CRIANDO A OS */
        $sql = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$_GET['hd_chamado']}";
        $res = pg_query($con,$sql);
        $posto = pg_fetch_result($res,0,'posto');
        if ($posto !== $login_posto) {
        throw new Exception(traduz('erro:.o.posto.utilizado.não.possui.essa.pré.-.OS', $con), 0);
        }
    }
    if (strlen($os) > 0) {

    if(strlen(trim($consumidor_fone2))==0){
      $consumidor_fone2 = "''";
    }

    $promotor_treinamento = ($promotor_treinamento == "''") ? 'null' : $promotor_treinamento;

    $campos_update = array();
    $campos_update["data_abertura"]      = $data_abertura;
    $campos_update["consumidor_revenda"] = $consumidor_revenda;
    $campos_update["tipo_atendimento"]   = $tipo_atendimento;
    $campos_update["promotor_treinamento"] = $promotor_treinamento;
    $campos_update["produto"]           = $produto_id;
    $campos_update["serie"]             = $serie;
    $campos_update["nota_fiscal"]       = $nota_fiscal;
    $campos_update["data_nf"]           = $data_nf;
    $campos_update["prateleira_box"]    = $prateleira_box;
    $campos_update["aparencia_produto"] = $aparencia_produto;
    $campos_update["acessorios"]        = $acessorios;
    $campos_update["tecnico"]           = $tecnico;
    $campos_update["defeito_reclamado_descricao"]=$defeito_reclamado_descricao;

    $campos_update["consumidor_cpf"]            = $consumidor_cpf;
    $campos_update["consumidor_nome"]           = $consumidor_nome;
    $campos_update["consumidor_fone"]           = $consumidor_fone;
    $campos_update["consumidor_celular"]        = $consumidor_celular;
    $campos_update["consumidor_fone_comercial"] = $consumidor_fone2;
    $campos_update["consumidor_cep"]            = $consumidor_cep;
    $campos_update["consumidor_endereco"]       = $consumidor_endereco;
    $campos_update["consumidor_numero"]         = $consumidor_numero;
    $campos_update["consumidor_complemento"]    = $consumidor_complemento;
    $campos_update["consumidor_bairro"]         = $consumidor_bairro;
    $campos_update["consumidor_cidade"]         = $consumidor_cidade;
    $campos_update["consumidor_estado"]         = $consumidor_estado;
    $campos_update["consumidor_email"]          = $consumidor_email;

    if ($login_fabrica == 85) {
      $campos_update["obs"]=$reclamado;
    }

    $campos_update["pedagio"]=$pedagio;

    if ($login_fabrica == 87) {//HD 697843

      $sql_km = "SELECT max(os_status) as total
             FROM tbl_os_status
            WHERE fabrica_status = $login_fabrica
              AND status_os = 98
              AND os = $os;";

      $res_km = pg_query($con, $sql_km);

      if (!pg_num_rows($res_km)) {

        $campos_update["qtde_km"] = $qtde_km;

      }

    } else {

      $campos_update["qtde_km"] = $qtde_km;

    }

    if($login_fabrica == 20 and in_array($tipo_atendimento,array(11,12,13,66))) { //hd_chamado=2843341
      $campos_update["defeito_constatado"]=$defeito_constatado;
      $defeito_constatado_principal = $defeito_constatado;
    }else{
      $campos_update["defeito_constatado"]=$defeito_constatado_principal;
    }

    $campos_update["solucao_os"]   = $solucao_os_principal;
    $campos_update["revenda"]      = $revenda_id;
    $campos_update["revenda_cnpj"] = $revenda_cnpj;
    $campos_update["revenda_nome"] = $revenda_nome;
    $campos_update["revenda_fone"] = $revenda_fone;
    $campos_update["cod_ibge"]     = $cod_ibge;
    $campos_update["obs"]          = pg_escape_literal(preg_replace('/^\'|\'$/','', $obs));

    if($login_fabrica == 20){
      if (!empty($_POST["nota_fiscal"])) {
        $nota_fiscal = $_POST["nota_fiscal"];
        $campos_update["nota_fiscal"] = "'{$nota_fiscal}'";
      }

      if (!empty($_POST["data_nf"])) {
        $data_nf = DateTime::createFromFormat('d/m/Y', $_POST["data_nf"]);
        $campos_update["data_nf"] = "'{$data_nf->format('Y-m-d')}'";
      }

      if (!empty($_POST["consumidor_nome"])) {
        $consumidor_nome = $_POST["consumidor_nome"];
        $campos_update["consumidor_nome"] = "'{$consumidor_nome}'";
      }

      if (!empty($_POST["consumidor_cpf"])) {
        $consumidor_cpf = $_POST["consumidor_cpf"];
        $campos_update["consumidor_cpf"] = "'{$consumidor_cpf}'";
      }

      if (!empty($_POST["consumidor_fone"])) {
        $consumidor_fone = $_POST["consumidor_fone"];
        $campos_update["consumidor_fone"] = "'{$consumidor_fone}'";
      }

      if (!empty($_POST["consumidor_celular"])) {
        $consumidor_celular = $_POST["consumidor_celular"];
        $campos_update["consumidor_celular"] = "'{$consumidor_celular}'";
      }

      if (!empty($_POST["consumidor_email"])) {
        $consumidor_email = $_POST["consumidor_email"];
        $campos_update["consumidor_email"] = "'{$consumidor_email}'";
      }

      $campos_update["aparencia_produto"] = "'USN'";
    }


    foreach($campos_update as $campo => $valor) {
      if (false !== strpos($campo, 'consumidor_') or in_array($campo, array('data_nf', 'nota_fiscal'))) {
        continue;
      }

      if ($campos_telecontrol[$login_fabrica]['tbl_os'][$campo]['bloqueia_edicao'] == 1) {
        unset($campos_update[$campo]);
      }
    }

    foreach($campos_update as $campo => $valor) {
      $campos_update[$campo] = "{$campo}={$valor}";
        }

    if($login_fabrica == 20) {
      $campos_update['causa_defeito'] = "causa_defeito=$causa_defeito";
    }

    if($login_fabrica == 87){

      $sql = "UPDATE tbl_os_extra SET hora_tecnica = {$horas_trabalhadas}, qtde_horas = {$qtde_horas}  WHERE os={$os} ;";
      @$res = pg_query($con, $sql);
    }

    $update_string = implode(",", $campos_update);

    $sql = "UPDATE tbl_os
           SET {$update_string}
		 WHERE tbl_os.os = {$os} ";
        @$res = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
      throw new Exception(traduz('falha.ao.atualizar.dados.da.os', $con));
    }
   
  } else {

        $promotor_treinamento = ( $promotor_treinamento == "''") ? 'null' : $promotor_treinamento;

        $hora = date('H:i:s');
        $data_hora_abertura = str_replace("'", "",$data_abertura);
        $data_hora_abertura = $data_hora_abertura.' '.$hora;

        $sql = "
              INSERT INTO tbl_os(
                  fabrica,
                  posto,
                  data_abertura,
                  consumidor_revenda,
                  tipo_atendimento,
                  tecnico,

                  produto,
                  serie,
                  nota_fiscal,
                  data_nf,
                  prateleira_box,
                  defeito_reclamado_descricao,
                  aparencia_produto,
                  acessorios,

                  consumidor_cpf,
                  consumidor_nome,
                  consumidor_fone,
                  consumidor_celular,
                  consumidor_fone_comercial,
                  consumidor_cep,
                  consumidor_endereco,
                  consumidor_numero,
                  consumidor_complemento,
                  consumidor_bairro,
                  consumidor_cidade,
                  consumidor_estado,
                  consumidor_email,

                  pedagio,
                  qtde_km,
                  data_hora_fechamento,
                  defeito_reclamado,
                  defeito_constatado,
                  solucao_os,
                  causa_defeito,
                  promotor_treinamento,
                  segmento_atuacao,

                  revenda,
                  revenda_cnpj,
                  revenda_nome,
                  revenda_fone,

                  cod_ibge,
                  obs,
                  data_hora_abertura
              )

              VALUES(
                  {$login_fabrica},
                  {$login_posto},
                  {$data_abertura},
                  {$consumidor_revenda},
                  {$tipo_atendimento},
                  {$tecnico},

                  {$produto_id},
                  {$serie},
                  {$nota_fiscal},
                  {$data_nf},
                  {$prateleira_box},
                  {$defeito_reclamado_descricao},
                  {$aparencia_produto},
                  {$acessorios},

                  $consumidor_cpf,
                  {$consumidor_nome},
                  {$consumidor_fone},
                  {$consumidor_celular},
                  {$consumidor_fone2},
                  {$consumidor_cep},
                  {$consumidor_endereco},
                  {$consumidor_numero},
                  {$consumidor_complemento},
                  {$consumidor_bairro},
                  {$consumidor_cidade},
                  {$consumidor_estado},
                  {$consumidor_email},

                  {$pedagio},
                  {$qtde_km},
                  {$data_fechamento_hora},
                  {$defeito_reclamado},
                  {$defeito_constatado_principal},
                  {$solucao_os_principal},
                  {$causa_defeito},
                  {$promotor_treinamento},
                  {$segmento_atuacao},

                  {$revenda_id},
                  {$revenda_cnpj},
                  {$revenda_nome},
                  {$revenda_fone},

                  {$cod_ibge},
                  {$obs},
                  '{$data_hora_abertura}'
              )

              RETURNING os;";
      $sql = preg_replace('/^(\s+),$/m', '$1NULL,', $sql); // os campos que não vieram, preenche com NULL.
      $res = pg_query($con, $sql);

    if (strlen(pg_last_error($con)) > 0) {
      $erro = pg_last_error($con);
      if (strpos($erro, "data_nf_superior_data_abertura") !== false) {
        $msg_erro[] = traduz('a.data.de.compra.nao.pode.ser.superior.a.data.da.abertura.da.os', $con);
      }

      if (strpos($erro, "data_abertura_futura") !== false) {
        $msg_erro[] = traduz('data.da.abertura.deve.ser.inferior.ou.igual.a.data.de.digitacao.da.os.no.sistema.(data.de.hoje)', $con);
      }

      if (strpos($erro, "data_abertura_muito_antiga") !== false) {
        $msg_erro[] = traduz('data.de.abertura.muito.antiga', $con);
      }

      throw new Exception(traduz('falha.ao.cadastrar.os', $con) . " <erro msg='".traducao_erro(pg_last_error($con), $sistema_lingua)."'>");
    }

    $os = pg_fetch_result($res, 0, "os");

    if($login_fabrica == 87) {

      if(strlen($qtde_horas) == 0) $qtde_horas = '0';
      if(strlen($horas_trabalhadas) == 0) $horas_trabalhadas = '0';

      $sql = "INSERT INTO tbl_os_extra (hora_tecnica, qtde_horas, os) VALUES ({$horas_trabalhadas}, {$qtde_horas}, {$os});";
      @$res = pg_query($con, $sql);
    }
    $nova_os = true;
  }

  //  if ( $login_fabrica != 20) {
  //    $sql = "SELECT fn_valida_os_gelopar_serie(os, fabrica) FROM tbl_os WHERE os={$os}";
  // //     echo $sql;exit;
  //    $res = pg_query($con, $sql);
  //         if (strlen(pg_last_error($con)) > 0) $msg_erro[] = pg_last_error($con);
  //
  //
  //  }

  if (isset($hd_chamado)) {

        try {
          $sql = "
          SELECT
          tbl_hd_chamado_extra.os

          FROM
          tbl_hd_chamado_extra

          WHERE
          hd_chamado={$hd_chamado}
          ";

          @$res = pg_query($con, $sql);

          if (strlen(pg_last_error($con)) > 0) throw new Exception(pg_last_error(), 0);

          $os_hd_chamado = pg_fetch_result($res, 0, "os");

          if (strlen($os_hd_chamado) == 0) {

            $sqlUpdt = "
            UPDATE tbl_hd_chamado_extra SET
            os={$os}

            WHERE
            hd_chamado={$hd_chamado}
            AND tbl_hd_chamado_extra.os IS NULL
            ";
            @$res = pg_query($con, $sqlUpdt);

            if (strlen(pg_last_error($con)) > 0) throw new Exception(pg_last_error(), 0);

            //HD 736243 - insere comunicado no chamado
            $sqlinf = "INSERT INTO tbl_hd_chamado_item(
                    hd_chamado   ,
                    data         ,
                    comentario   ,
                    interno      ,
                    admin
                    )values(
                    {$hd_chamado}       ,
                    current_timestamp ,
                    'Foi aberto pelo posto a OS deste chamado com o número <a href=\"os_press.php?os={$os}\">$os</a>' ,
                    't',
                    (SELECT admin FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} limit 1)
                    )";
            $resinf = pg_query($con,$sqlinf);
            if (strlen(pg_last_error($con)) > 0) throw new Exception(pg_last_error(), 0);

          }
          elseif ($os != $os_hd_chamado) {
            $sql = "
            SELECT
            sua_os

            FROM
            tbl_os

            WHERE
            os={$os_hd_chamado}
            ";
            @$res = pg_query($con, $sql);
            if (strlen(pg_last_error($con)) > 0) throw new Exception(pg_last_error(), 0);

            $sua_os_hd_chamado = pg_fetch_result($res, 0, "sua_os");

            throw new Exception(traduz('ja.existe.outra.os.associada.a.esta.pre-os', $con) .
                        ": <a href='os_press.php?os={$os}' target='_blank'>{$sua_os_hd_chamado}</a>",
                        1);
          }

        }
        catch(Exception $e) {
          $erro = $e->getCode() == 1 ? ": " . $e->getMessage() :  "<erro msg='".$e->getMessage()."'>";
          throw new Exception(traduz('falha.ao.vincular.pre-os.%', $con, $cook_idioma, $erro));
        }
  }




  foreach($itens_analise as $seq => $dados) {
    $sql = "
      SELECT defeito_constatado_reclamado
        FROM tbl_os_defeito_reclamado_constatado
       WHERE os                 = {$os}
         AND defeito_constatado = {$dados['defeito_constatado_id']}
         AND solucao            = {$dados['solucao_os']}
      ";
    $res = pg_query($con, $sql);
    if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.cadastrar.analise.da.os', $con));

    if (pg_num_rows($res) == 0) {
      $sql = "
        INSERT INTO tbl_os_defeito_reclamado_constatado (
          os,
          defeito_constatado,
          solucao,
          fabrica
        ) VALUES (
          {$os},
          {$dados['defeito_constatado_id']},
          {$dados['solucao_os']},
          {$login_fabrica}
        );  ";
      $res = pg_query($con, $sql);
      if (strlen(pg_last_error($con)) > 0) throw new Exception(traduz('falha.ao.cadastrar.analise.da.os', $con));
    }        
  }

  $sql = "
    SELECT tbl_os_produto.os_produto
      FROM tbl_os_produto
     WHERE tbl_os_produto.os      = {$os}
       AND tbl_os_produto.produto = {$produto_id}
    ";
  $res = pg_query($con, $sql);
  if (pg_last_error($con)) throw new Exception(traduz('falha.ao.consultar.produto.na.os', $con));

  if (pg_num_rows($res) > 0) {
    $os_produto = pg_fetch_result($res, 0, "os_produto");

    if (count($itens_pecas) == 0) {
      //Remove o os_produto da OS através de um UPDATE para não pesar no banco
      // Este é o produto ZERO da telecontrol
      $sql = "
        UPDATE tbl_os_produto
           SET os                        = 4836000
         WHERE tbl_os_produto.os_produto = {$os_produto}
        ";
    } else {
      $sql = "
        UPDATE tbl_os_produto
           SET serie                     =  {$serie}
         WHERE tbl_os_produto.os_produto =  {$os_produto}
           AND serie                     <> {$serie}
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_last_error($con)) throw new Exception(traduz('falha.ao.cadastrar.o.produto.na.os', $con));

  }

    /*
     * - Para GELOPAR(3420931):
     * Entrará em auditoria toda OS que gravar,
     * no mínimo, 01 peça na Ordem de serviço
     */
    $gravaAuditoriaPecas = 0;

    if (count($itens_pecas) == 0 && in_array($tipo_atendimento, [11,12])) {
      throw new Exception(traduz('Obrigatório o lançamento de peças para este tipo de atendimento', $con));
    }

  foreach ($itens_pecas as $seq => $dados) {

    if ($login_fabrica != 85 ) { // HD 811354

      $dados["qtde"] = intval($dados["qtde"]);

    }

    if (empty($dados["defeito"])) {

      $dados["defeito"] = 'null';

    }

    if (empty($dados["servico"])) {

      $dados["servico"] = 'null';

    }

    if ( $dados["os_item"] > 0) {

      if (intval($dados["pedido"]) == 0) {

        $sql = "
          SELECT
          tbl_os_item.os_produto

          FROM
          tbl_os_item

          WHERE
          tbl_os_item.os_item={$dados['os_item']};";

        $res = pg_query($con, $sql);
        if (pg_last_error($con)) throw new Exception(traduz('falha.ao.consultar.produto.na.os', $con));

        if (pg_num_rows($res) > 0)
          $os_produto = pg_fetch_result($res, 0, "os_produto");

        if ($dados["qtde"] > 0) {

          $dados["peca_causadora"] = (!empty($dados["peca_causadora"])) ? $dados["peca_causadora"] : "null" ;

          $sql = "
            UPDATE tbl_os_item SET
            peca={$dados["peca_id"]},
            qtde={$dados["qtde"]},
            defeito={$dados["defeito"]},
            servico_realizado={$dados["servico"]},
            peca_causadora={$dados["peca_causadora"]}

            WHERE
            tbl_os_item.os_item={$dados["os_item"]}
            AND tbl_os_item.os_produto={$os_produto}
            AND tbl_os_item.pedido IS NULL
            ";
          $res = pg_query($con, $sql);

          if (pg_last_error($con)) throw new Exception(traduz('falha.ao.atualizar.item.da.os.id.do.item.%', $con, $cook_idioma, array($dados["os_item"])));

          $os_item = $dados["os_item"];
        }else {

          $sql = "UPDATE tbl_os_produto SET
            os = 4836000
            WHERE os_produto = $os_produto";
          $res = @pg_query($con, $sql);
          if (pg_last_error($con)) throw new Exception(traduz('falha.ao.excluir.item.da.os.%', $con, $cook_idioma, array($dados["os_item"])));

          unset($itens_pecas[$seq]);
        }
      }
      //echo '<pre>'; print_r($dados); echo '</pre>';
    } else {
    
      if ($dados["peca_id"] > 0 && ( ($login_fabrica == 20 && $dados['qtde'] > 0 || $login_fabrica <> 20))) {
        $sql = "
          INSERT INTO tbl_os_produto(
            os,
            produto,
            serie
          )VALUES(
      {$os},
      {$produto_id},
      {$serie}
    )RETURNING os_produto;";
        $res = pg_query($con, $sql);
        if (pg_last_error($con)) throw new Exception(traduz("falha.ao.cadastrar.o.produto.na.os", $con, $cook_idioma));

        $os_produto = pg_fetch_result($res, 0, "os_produto");
        $dados['peca_causadora'] = empty ($dados['peca_causadora']) ? 'null' : $dados['peca_causadora'];

                if(!in_array($login_fabrica,array(85))) {
                    $campos_gera_pedido_posto = ", liberacao_pedido ";
                    $value_gera_pedido_posto = ", '$gera_pedido' ";
                }

        $sql = "
          INSERT INTO tbl_os_item(
            os_produto,
            peca,
            peca_causadora,
            qtde,
            defeito
                        $campos_gera_pedido_posto , 
            servico_realizado
          )VALUES(
        {$os_produto},
        {$dados["peca_id"]},
        {$dados["peca_causadora"]},
        {$dados["qtde"]},
        {$dados["defeito"]}
                $value_gera_pedido_posto,
        {$dados["servico"]}
      ) RETURNING os_item;";
        $res = pg_query($con, $sql);

        if (pg_last_error($con)) throw new Exception(traduz("falha.ao.inserir.item.da.os.%", $con, $cook_idioma,$dados["peca"]));
        $os_item = pg_fetch_result($res, 0, "os_item");
                $gravaAuditoriaPecas = ($login_fabrica == 85 && !empty($os_item)) ? 1 : 0;
        unset($dados["peca_id"]);
      } else {
        unset ($itens_pecas[$seq]);
      }
    }
    // echo "<br>SQL: ".$sql;
  }

  if ($gravaAuditoriaPecas == 1) {
        $sqlVerAud = "
            SELECT  os
            FROM    tbl_os_status
            WHERE   os = $os
            AND     status_os IN (62,64)
        ";
        $resVerAud = pg_query($con,$sqlVerAud);

        if (pg_num_rows($resVerAud) == 0) {
            $sqlGravaAud = "
                INSERT INTO tbl_os_status (
                    os,
                    status_os,
                    observacao
                ) VALUES (
                    $os,
                    62,
                    'Intervenção de Entrada de Peças na OS'
                )
            ";
            $resGravaAud = pg_query($con,$sqlGravaAud);
        }
  }

  //throw new Exception('debug');
  //@$res = pg_query($con, "ROLLBACK");
  //exit;

  if($login_fabrica == 20) {

    if (empty($msg_erro) && !empty($data_fechamento)) {

      $sql = "
        UPDATE tbl_os
           SET data_fechamento = '$data_fechamento', data_hora_fechamento = $data_fechamento_hora
         WHERE fabrica         = $login_fabrica
           AND os              = $os";
      $res = @pg_query($con,$sql);
      if (pg_last_error($con))
        throw new Exception(traduz('falha.ao.finalizar.os', $con));
      if (strlen ($msg_erro) == 0) {
        $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
        $res = @pg_query($con, $sql);
        if (pg_last_error($con))
          throw new Exception(traduz('falha.ao.validar.os.%', $con, $cook_idioma,$erro_msg));
      }
    }
  }

      // if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      //     throw new Exception('Email Destinatário inválido.');
      //   }

  if ($login_fabrica == 20 AND $tipo_atendimento == 12)  {
    explodirOSConsumidor($os);

    $anexo_nf = $_FILES["foto_nf"]["name"];
    if(strlen(trim($anexo_nf)) > 0){
      anexa_nf_explodida($os);
    }
    if(strlen(trim($data_fechamento)) > 0){
      finalizaExplodeOs($os,$data_fechamento);
    }
  }

  if($login_fabrica == 85){
    $categoria = $_POST["categoria"];
    if($categoria == "garantia_estendida"){
      $sql_update_garantia_estendida = "UPDATE tbl_os_extra SET classificacao_os  = 51 where os = $os";
      $res_update_garantia_estendida = pg_query($con, $sql_update_garantia_estendida);
    }
  }


    $res = pg_query($con, "SELECT fn_valida_os($os, $login_fabrica)");
    #echo pg_last_error($con);exit;
    if (pg_last_error($con)) {
      //echo pg_last_error($con);
      $erro = str_replace(",","",pg_last_error($con));
      $erro = explode("ERROR:",$erro);
      $erro = explode("CONTEXT:",$erro[1]);
      $erro = $erro[0];
      $msg_erro[] = $erro;

      //$msg_erro[] = traduz('falha.ao.validar.os.%', $con, $cook_idioma, $erro);
      throw new Exception(traduz("falha.ao.validar.os.%", $con, $cook_idioma,$erro_msg));
    }

    if (count($itens_pecas) > 0 || count($itens_analise) > 0) {

      if ($tipo_atendimento == 13 && $login_fabrica == 20) {

        $sqlProd = "SELECT os_produto FROM tbl_os_produto WHERE os = $os";
        $resProd = pg_query($con, $sqlProd);

        if (pg_num_rows($resProd) > 0) {

          while ($dadosOs = pg_fetch_object($resProd)) {

            $sqlRemoveItens = "DELETE FROM tbl_os_item WHERE os_produto = {$dadosOs->os_produto}";
            pg_query($sqlRemoveItens);

          }

        }

      }


      $res = @pg_query($con, "SELECT fn_valida_os_item($os, $login_fabrica)");
      if (pg_last_error($con)) {
        $erro = str_replace(","," ",pg_last_error($con));
        $inicio = strpos($erro, "ERROR") !== false ? strpos($erro, "ERROR") : 0;
        $inicio += 6;
        $fim = strpos($erro, "CONTEXT") !== false ? strpos($erro, "CONTEXT") : strlen($erro);
        $erro = trim(substr($erro, $inicio, $fim - $inicio));
        $msg_erro[] = $erro;
        //$msg_erro[] = traduz('falha.ao.validar.itens.da.os.%', $con, $cook_idioma, $erro);
        throw new Exception(traduz('falha.ao.validar.itens.da.os.%', $con) . $erro);
      }
    }

    $res = pg_query($con, "SELECT fn_valida_os_reincidente($os,$login_fabrica)");
    if (pg_last_error($con)) {
      $erro = pg_last_error($con);
      $inicio = strpos($erro, "ERROR") !== false ? strpos($erro, "ERROR") : 0;
      $inicio += 6;
      $fim = strpos($erro, "CONTEXT") !== false ? strpos($erro, "CONTEXT") : strlen($erro);
      $erro = trim(substr($erro, $inicio, $fim - $inicio));
      $msg_erro[] = $erro;
      //$msg_erro[] = traduz('falha.ao.validar.reincidencia.da.os.%', $con, $cook_idioma, $erro);
      throw new Exception(traduz('falha.ao.validar.reincidencia.da.os.%', $con) . $erro);
    }


    $sql = "
      UPDATE tbl_os SET
      status_checkpoint=fn_os_status_checkpoint_os(os)

      WHERE tbl_os.os = {$os}
      AND tbl_os.status_checkpoint<>fn_os_status_checkpoint_os(os) ";

      $res = pg_query($con, $sql);

      if (pg_last_error($con)) {
        $erro = pg_last_error($con);
        //$msg_erro[] = traduz('falha.ao.atualizar.status.da.os', $con, $cook_idioma, $erro);
        throw new Exception(traduz('falha.ao.atualizar.status.da.os', $con) . $erro);
      }

      if ($anexaNotaFiscal) {
        $link_nf = temNF($os, 'count');

        if ($fabricaFileUploadOS) {
              
            $anexo_chave = $_POST["anexo_chave"];
              
            if ($anexo_chave != $os) {
              $sql_tem_anexo = "SELECT *
                                FROM tbl_tdocs
                                WHERE fabrica = $login_fabrica
                                AND hash_temp = '{$anexo_chave}'
                                AND situacao = 'ativo'";
              $res_tem_anexo = pg_query($con, $sql_tem_anexo);
              if (pg_num_rows($res_tem_anexo) > 0) {
                $sql_update = "UPDATE tbl_tdocs SET
                              referencia_id = {$os},
                              hash_temp = NULL,
                              referencia = 'os'
                              WHERE fabrica = $login_fabrica
                              AND situacao = 'ativo'
                              AND hash_temp = '{$anexo_chave}'";
                $res_update = pg_query($con, $sql_update);
                if (strlen(pg_last_error()) > 0) {
                  $msg_erro[] = traduz('Erro ao gravar anexos');
                  throw new Exception(traduz('Erro ao gravar anexos'));
                }
              } else if ($link_nf == 0) {
                $msg_erro[] = traduz('anexo.da.nota.fiscal.obrigatorio');
                throw new Exception(traduz('anexo.da.nota.fiscal.obrigatorio'));
              }
            }
        } else {
          $qt_anexo = 0;
          foreach($_FILES['foto_nf'] as $files){
              if(strlen($_FILES['foto_nf']['name'][$qt_anexo])==0){
                  continue;
              }
              $dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
              $dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
              $dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
              $dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
              $dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];

              $anexou = anexaNF($os, $dados_anexo);

              if ($anexou !== 0) {
                  $erro_nf .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou;
                  $msg_erro[] =  traduz('falha.ao.anexar.imagem.na.nota.fiscal', $con, $cook_idioma, array($erro_nf));
              }

            $qt_anexo++;
          }
        }

        // $anexa_nf = anexaNF($os, $_FILES['foto_nf']);
        // if ($anexa_nf !== 0) {
        //   $erro_nf = (is_numeric($anexa_nf)) ? $msgs_erro[$anexa_nf] : $anexa_nf;
        //   //die(traduz('falha.ao.anexar.imagem.na.nota.fiscal', $con) . $erro_nf);
        //   $msg_erro[] =  traduz('falha.ao.anexar.imagem.na.nota.fiscal', $con, $cook_idioma, array($erro_nf));
        //   throw new Exception(traduz('falha.ao.anexar.imagem.na.nota.fiscal', $con, $cook_idioma, array($erro_nf)));
        // }

      }

      //gravar na tbl_os_campo_extra - Chamado bosch
      if ($login_fabrica == 20) {
        if ($tipo_atendimento == 13 or $tipo_atendimento == 66) {

          $sql = "SELECT tbl_os_campo_extra.campos_adicionais
            FROM tbl_os_campo_extra
            WHERE os = $os
            AND fabrica = $login_fabrica";
          $res = pg_query($con,$sql);
          $msg_erro .= traducao_erro(pg_last_error($con), $sistema_lingua);

          if(pg_num_rows($res) > 0){
            $res_adicionais = pg_result($res,0,campos_adicionais);

            $adicionais = pg_result($res,0,campos_adicionais);
            $adicionais = json_decode($adicionais, true);

            $campos_adicionais = array_merge($adicionais, $dados_campos);

          }else{
            $campos_adicionais = $dados_campos;
          }

          #$campos_adicionais          = json_encode($campos_adicionais);
          $campos_adicionais = str_replace("\\", "\\\\", json_encode($campos_adicionais)); //HD-3200578
          if(pg_num_rows($res) == 0){
            $sql_campo_extra = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) values ($login_fabrica, $os, '$campos_adicionais')";
          }else{
            $sql_campo_extra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais'
              WHERE os = $os
              AND fabrica = $login_fabrica";
          }
          $res_campo_extra = pg_query($con, $sql_campo_extra);
        }

        if (in_array($tipo_atendimento, array(13, 16, 66))) {
          if (strlen($promotor_treinamento)>0 and $promotor_treinamento <> "null" and $promotor_treinamento <> "''"){
            $sql = "SELECT status_os
              FROM tbl_os_status
              WHERE os = $os
              AND status_os IN (92)
              ";
            $res = @pg_query ($con,$sql);
            if(pg_num_rows($res) > 0){
              $status_os  = pg_fetch_result ($res,0,status_os);
            }
            if(pg_num_rows($res) == 0 AND (($login_pais == 'BR' AND $tipo_atendimento == 16) OR in_array($tipo_atendimento, array(13, 66))) ){
              $msg_status = traduz('os.aguardando.aprovacao.do.promotor', $con);
              $sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os, 92, '$msg_status')";
              $res = @pg_query ($con,$sql);
            }
          }
        }
      }

    @$res = pg_query($con, 'COMMIT');

    // HD 3032759 - Anexo de foto de NS produto para auditoria
    // aqui associa o anexo com a OS
    if (is_object($tDocs) and $anexoID) {
      $fileData['name'] = $os.'_foto_serie.' . pathinfo($fileData['name'], PATHINFO_EXTENSION);
      $tDocs->setDocumentReference($fileData, $os, 'anexar', true, 'osserie');
    } elseif (isset($_POST['anexo_serie'])) {
      $tDocs = new TDocs($con, $login_fabrica);

      $fileData = json_decode($_POST['anexo_serie'], true);
      $fileData['name'] = $os.'_foto_serie.' . pathinfo($fileData['name'], PATHINFO_EXTENSION);

      $tDocs->setContext('osserie');
      $tDocs->setDocumentReference($fileData, $os);
    }

    //BOSCH - ENVIAR EMAIL
    if ($login_fabrica == 20) {
      include_once 'class/email/mailer/class.phpmailer.php';
      $mailer = new PHPMailer();
      if($login_pais == 'BR') {
        $sql = "SELECT tbl_posto.nome,
                   tbl_posto_fabrica.codigo_posto,
                   tbl_posto.email,
                   tbl_os.consumidor_nome,
                   tbl_produto.referencia,
                   tbl_produto.descricao
              FROM tbl_os
              JOIN tbl_posto         USING(posto)
              JOIN tbl_produto       USING(produto)
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
             WHERE os = $os and tipo_atendimento IN (15,16,13,66)";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
          $posto_nome      = trim(pg_fetch_result($res,0, 'nome'));
          $codigo_posto    = trim(pg_fetch_result($res,0, 'codigo_posto'));
          $consumidor_nome = trim(pg_fetch_result($res,0, 'consumidor_nome'));
          $produto_ref     = trim(pg_fetch_result($res,0, 'referencia'));
          $produto_nome    = trim(pg_fetch_result($res,0, 'descricao'));
          if( strlen($promotor_treinamento) > 0  and $promotor_treinamento <>'null'){
            $sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento;";
            $res2 = pg_query($con,$sql);
            $promotor_nome  = trim(pg_fetch_result($res2,0, 'nome'));
            $promotor_email = trim(pg_fetch_result($res2,0, 'email'));

            if(strlen($promotor_email) > 0 ){
              $email_origem  = "helpdesk@telecontrol.com.br";
              $new_email[]   = "helpdesk@telecontrol.com.br";
              $email_destino = $promotor_email;
              $new_email[]   = $promotor_email;
              $assunto       = "Nova OS de Cortesia";

              #Liberado: HD 18323
              if ($tipo_atendimento == 13)
              {
                $assunto = "Solicitação de Troca de Produto";
                $corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
                $corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba cadastrar uma troca de produto e necessita de sua autorização.\n\n";
                $corpo.="<br>Troca de produto para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</b>\n";
                $corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é <b>$os</b>\n";
              }
              else if ($tipo_atendimento == 66)
              {
                $assunto = "Solicitação de Troca de Produto Fora da Garantia";
                $corpo   = "<p>
                  Caro promotor $promotor_nome, <br />
                  O posto autorizado $posto_nome, código $codigo_posto, acaba de cadastrar uma Troca de Produto Fora da Garantia e necessita de sua autorização.
                  </p>
                  <p>
                  Troca de Produto Fora da Garantia para o consumidor $consumidor_nome referente ao produto: $produto_ref - $produto_nome
                  </p>
                  <p>
                  Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é $os.
                  </p>";
              }
              else
              {
                if ($login_posto == '6359' OR 1 == 1){

                  #if ($x_promotor_treinamento<>96){
                  # $email_destino = "fabio@telecontrol.com.br";
                  #}

                  $corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
                  $corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba de cadastrar uma cortesia e necessita de sua autorização.\n\n";
                  $corpo.="<br>Cortesia para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
                  $corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação das OS de Cortesia. O número da OS é <b>$os</b>\n";
                }else{
                  $corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
                  $corpo.="<br>Você acaba de autorizar uma cortesia para o posto autorizado <b>$posto_nome</b>, código do posto: $codigo_posto\n\n";
                  $corpo.="<br>Cortesia concedida para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
                  $corpo.="<br>Verificar a OS <b>$os</b>\n";
                }
              }
              $body_top = "--Message-Boundary\n";
              $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
              $body_top .= "Content-transfer-encoding: 7BIT\n";
              $body_top .= "Content-description: Mail message body\n\n";

              // if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
              //  $enviou = 'ok';
              // }
              $mailer->IsHTML();
              $mailer->AddAddress($email_destino);
              $mailer->Subject  = $assunto;
              $mailer->Body     = $corpo;
              $mailer->From     = $email_origem;
              $mailer->FromName = "Bosch";

              if(!$mailer->Send()){
                $cabecalho  = "MIME-Version: 1.0 \r";
                $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
                $cabecalho .= "From: helpdesk@telecontrol.com.br";

                $xemail_destino  = implode(",", $new_email);

                mail($xemail_destino, utf8_encode($assunto), utf8_encode($corpo), $cabecalho);


              }
              else
              {
                $mailer->ClearAddresses();
                $enviou = "ok";
              }
            }
          }
          // if ($enviou == "ok")
          // {
          //   header ("Location: os_cadastro_adicional.php?os=$os");
          //   exit;
          // }
        }
      }else{
        if(!empty($promotor_treinamento)){
          $sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento;";
          $res2 = pg_query($con,$sql);
          $promotor_nome  = trim(pg_fetch_result($res2,0,nome));
          $promotor_email = trim(pg_fetch_result($res2,0,email));
        }
        $sql = "SELECT  tbl_posto.nome,
          tbl_posto_fabrica.codigo_posto,
          tbl_posto.email,
          tbl_os.consumidor_nome,
          tbl_produto.referencia,
          tbl_produto.descricao
          FROM  tbl_os
          JOIN  tbl_posto         USING(posto)
          JOIN  tbl_produto       USING(produto)
          JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
          WHERE os = $os AND tipo_atendimento = 13 ";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {
          $posto_nome      = trim(pg_fetch_result($res,0,nome));
          $codigo_posto    = trim(pg_fetch_result($res,0,codigo_posto));
          $consumidor_nome = trim(pg_fetch_result($res,0,consumidor_nome));
          $produto_ref     = trim(pg_fetch_result($res,0,referencia));
          $produto_nome    = trim(pg_fetch_result($res,0,descricao));
          $email           = trim(pg_fetch_result($res,0,email));
          //ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

          $email_origem  = "pt.garantia@br.bosch.com";
          $new_email[]   = "pt.garantia@br.bosch.com";
          $email_destino = $promotor_email;
          $new_email[]   = $promotor_email;
          $assunto       = "Nueva OS de Cortesia";

          $corpo ="<br>Estimado $promotor_nome,<br>\n\n";
          $corpo.="<br>El servicio autorizado <b>$codigo_posto - $posto_nome</b>, ha registrado una Cortesía Comercial o Cambio en garantia y necesita su autorización.\n\n";
          $corpo.="<br>Cortesía/Cambio para el cliente <b>$consumidor_nome</b> referente a la herramienta: <b>$produto_ref - $produto_nome.</b>\n";
          $corpo.="El número de OS es <b>$os</b>\n";

          $mailer->IsHTML();
          $mailer->AddAddress($email_destino);
          $mailer->Subject  = $assunto;
          $mailer->Body     = $corpo;
          $mailer->From     = $email_origem;
          $mailer->FromName = "Bosch";
          
          if(!$mailer->Send()){
            $cabecalho  = "MIME-Version: 1.0 \r";
            $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
            $cabecalho .= "From: helpdesk@telecontrol.com.br";

            $xemail_destino  = implode(",", $new_email);

            mail($xemail_destino, utf8_encode($assunto), utf8_encode($corpo), $cabecalho);


          }else{
            $mailer->ClearAddresses();
            $enviou = "ok";
          }
        }
      }
    }

    if ($login_fabrica == 85) {
      $log->adicionaLog("A OS $os foi aberta sem um relacionamento com um Atendimento, favor verificar");

      $log->adicionaTituloEmail("Abertura de OS sem relacionamento com um Atendimento");

      if($_serverEnvironment == 'development'){
        $log->adicionaEmail("guilherme.silva@telecontrol.com.br");
      }else{
        $log->adicionaEmail("assistec11@gelopar.com.br");
      }
      $log->enviaEmails();

    }

    $sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
    @$res = pg_query($con, $sql);
    if (pg_last_error($con)) {
      header("Location: {$PHP_SELF}?os={$os}");
      exit;
    }

    $sua_os = pg_fetch_result($res, 0, "sua_os");

    $imprimir_os = $_POST["imprimir_os"];

    if($imprimir_os == 'imprimir'){
      echo '<script type="text/javascript">window.open("os_print.php?os='.$os.'");</script>';
      #header("Location: os_item_new.php?os=$os&imprimir=1&qtde_etiq=$qtde_estiquetas");
    }

    //echo $sua_os;
    //exit;
    if($login_fabrica == 20){
      echo "<script>window.location='os_press.php?os=$os';</script>";
    } else {
      echo "<script>window.location='os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os';</script>";
    }
    #header ("Location: os_consulta_lite.php?btn_acao=ok&sua_os=$sua_os");
    exit;
  } catch (Exception $e) {
    @$res = pg_query($con, "ROLLBACK");
    if ($nova_os) unset($os);
    if (count($msg_erro) == 0) $msg_erro[] = $e->getMessage();
  }

  break;

}

/*
 * Retornar alguns dados do produto quando $produto_id  é valido...
 * estes dados serão usado para SELECT dos campos que são ajax
 * Regastando o produto_id
 *
 * */
  //if(empty($produto_id)){
    //$produto_id = explode("-", $dados_os['produto']);
    //$produto_id = trim($produto_id[0]);

  //}

  if (!empty($produto_id)) {

    $sql_produto = "
        SELECT tbl_produto.produto,
          tbl_produto.familia,
          tbl_produto.linha,
          tbl_produto.referencia,
          tbl_produto.descricao
        FROM tbl_produto
          JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
        WHERE fabrica = $login_fabrica
        AND produto = $produto_id;";

    $res_produto = @pg_query($con, $sql_produto);

    if (pg_num_rows($res_produto) > 0) {
      //$produto_id = pg_fetch_result($res_produto, 0, "produto");
      $familia = pg_fetch_result($res_produto, 0, "familia");
    }

  }
  $estados = array(
    "AC" => "Acre",        "AL" => "Alagoas",            "AM" => "Amazonas",       "AP" => "Amapá",          "BA" => "Bahia",
    "CE" => "Ceará",       "DF" => "Distrito Federal",   "ES" => "Espírito Santo", "GO" => "Goiás",          "MA" => "Maranhão",
    "MT" => "Mato Grosso", "MS" => "Mato Grosso do Sul", "MG" => "Minas Gerais",   "PA" => "Pará",           "PB" => "Paraíba",
    "PR" => "Paraná",      "PE" => "Pernambuco",         "PI" => "Piauí",          "RJ" => "Rio de Janeiro", "RN" => "Rio Grande do Norte",
    "RO" => "Rondônia",    "RS" => "Rio Grande do Sul",  "RR" => "Roraima",        "SC" => "Santa Catarina", "SE" => "Sergipe",
    "SP" => "São Paulo",   "TO" => "Tocantins");

    echo "<br>";
    $grupo_os = new grupo("dados_os", $campos_telecontrol[$login_fabrica], "dados.da.os");

  if (strlen($os) > 0) {

    $grupo_os->add_field("tbl_os", "sua_os");

  } else if (strlen($hd_chamado) > 0) {

    $grupo_os->add_element(new input_hidden("hd_chamado", "", $hd_chamado));

    if (!isset($_POST["btn_acao"])) {
      $grupo_os->add_element(new input_hidden("pre-os", "", "nova"));
    }

  }

  if (!empty($os)) {

    $sqlTp = "SELECT tipo_atendimento FROM tbl_os WHERE os = {$os}";
    $resTp = pg_query($con, $sqlTp);

    $tipo_atendimento_gravado = pg_fetch_result($resTp, 0, 'tipo_atendimento');

  }

  $grupo_os->add_element(new input_hidden("tipo_atendimento_gravado", "", $tipo_atendimento_gravado));

  $grupo_os->add_field("tbl_os", "data_abertura");

  if ($login_fabrica == 20) {
    $grupo_os->campos['data_abertura']->set_label(traduz("Data Entrada do Equipamento"));
    if (empty($_POST['data_abertura']) && empty($os)) {
      $grupo_os->campos['data_abertura']->set_value("");
    }
  }

  if ($login_fabrica <> 20 and $login_fabrica <> 87) {

    $grupo_os->add_field("tbl_os", "consumidor_revenda");
    $grupo_os->campos["consumidor_revenda"]->add_option('C', 'Consumidor');
    $grupo_os->campos["consumidor_revenda"]->add_option('R', 'Revenda');
    $grupo_os->campos["consumidor_revenda"]->set_value('C');

        /*
    $grupo_os->add_field("tbl_os", "tipo_atendimento");
    $grupo_os->campos["tipo_atendimento"]->add_option('1', '01 - Garantia');
    $grupo_os->campos["tipo_atendimento"]->add_option('2', '02 - Garantia com deslocamento');
        */
  }

  // novo grupo
  if ($login_fabrica == 87) {
    //Somente consumidor
    $grupo_os->add_element(new input_hidden("consumidor_revenda", "", "C"));

    $grupo_produto = new grupo("dados_produto", $campos_telecontrol[$login_fabrica], "dados.do.produto");
    $grupo_produto->add_field("tbl_os", "produto");
    //$grupo_produto->add_field("tbl_os", "produto_descricao");

    $grupo_produto->add_element(new input_hidden("produto_referencia", "", $campos_telecontrol[$login_fabrica]["tbl_os"]["produto"]["valor_referencia"]));
    //$grupo_produto->add_element(new input_hidden("produto_last", "",$produto_id));
        //$grupo_produto->add_element(new input_hidden("produto_id", "",$produto_id));

    $grupo_produto->add_field("tbl_os", "serie");
    $grupo_produto->add_field("tbl_os", "nota_fiscal");
    $grupo_produto->add_field("tbl_os", "tipo_atendimento");

        if(empty($produto_id) || empty($familia))
            $grupo_produto->campos["tipo_atendimento"]->add_option('0', traduz('selecione.o.produto'));
        else{
            $sql_tipo_atendimento = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND familia = $familia";
            $res_tipo_atendimento = pg_query($con, $sql_tipo_atendimento);

            if(pg_num_rows($res_tipo_atendimento)){
                $grupo_produto->campos["tipo_atendimento"]->add_option("", "");
                for($i =0; $i < pg_num_rows($res_tipo_atendimento); $i++){
                    extract(pg_fetch_array($res_tipo_atendimento));
                    $grupo_produto->campos["tipo_atendimento"]->add_option($tipo_atendimento, $descricao);
                }
            }
        }

    $grupo_produto->add_field("tbl_os", "data_nf");
    $grupo_produto->add_field("tbl_os", "defeito_reclamado_descricao");
    $grupo_produto->add_field("tbl_os", "horas_trabalhadas");
  }else{
    if($login_fabrica != 20){
      $grupo_os->add_field("tbl_os", "consumidor_revenda");
         $grupo_os->campos["consumidor_revenda"]->add_option('C', ucfirst(strtolower(traduz("consumidor", $con, $cook_idioma))));
         $grupo_os->campos["consumidor_revenda"]->add_option('R', ucfirst(strtolower(traduz("revenda", $con, $cook_idioma))));

         if(empty($os) AND empty($produto_id))
           $grupo_os->campos["consumidor_revenda"]->set_value('C');
    }

    $grupo_os->add_field("tbl_os", "tipo_atendimento");
    if($login_fabrica == 20){
      switch($login_pais){
        /*case "PE" :  $wr = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(11, 12, 14) "; break;
        case "BR" :  $wr = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(66, 11) "; break;
        case "MX" :  $wr = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(66) "; break;
        case "AR" :  $wr = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(66) "; break;
        default : $wr = "";*/
      }

      $sql = "
      SELECT TA.tipo_atendimento, codigo,
           COALESCE(TAI.descricao, TA.descricao) AS descricao
        FROM tbl_tipo_atendimento  TA
        LEFT JOIN tbl_tipo_atendimento_idioma TAI
          ON TAI.tipo_atendimento = TA.tipo_atendimento
         AND TAI.idioma = UPPER('{$sistema_lingua}')
       WHERE ".
       sql_where(
         array(
          'fabrica' => $login_fabrica,
          'ativo'   => true,
        )
      ) // . $wr;
      . ' ORDER BY codigo ASC';

            $res = pg_query($con, $sql);

            if(pg_num_rows($res)){
                if(empty($tipo_atendimento))
                    $grupo_os->campos["tipo_atendimento"]->add_option('', '');
                for($i =0; $i < pg_num_rows($res); $i++){
                    extract(pg_fetch_array($res));
                    $descricao = sprintf("%02d",$codigo)." - ".ucfirst(($descricao));
                    $grupo_os->campos["tipo_atendimento"]->add_option($tipo_atendimento, $descricao);
                }
            }

            if($login_fabrica != 20){
              $grupo_os->add_field("tbl_os", "segmento_atuacao");

        $sql = "SELECT SA.segmento_atuacao, COALESCE(SAI.descricao, SA.descricao) AS descricao
              FROM tbl_segmento_atuacao SA
           LEFT JOIN tbl_segmento_atuacao_idioma SAI
                ON SAI.segmento_atuacao = SA.segmento_atuacao
               AND TAI.idioma = UPPER('{$sistema_lingua}')
             WHERE " .
          sql_where(
            array(
              'fabrica' => $login_fabrica,
              'ativo' => true
            )
          ) .
          ' ORDER BY descricao';

        $res = pg_query($con, $sql);

        if(pg_num_rows($res)){
          if(empty($segmento_atuacao))
            $grupo_os->campos["segmento_atuacao"]->add_option('', '');

          for($i =0; $i < pg_num_rows($res); $i++){
            extract(pg_fetch_assoc($res));
            $grupo_os->campos["segmento_atuacao"]->add_option($segmento_atuacao, $descricao);
          }
        }
      }

      if($login_fabrica == 20){
        // HD 3310735 - Retirar esta mensagem
        // $msg_pessoa_aprova = "<div id='msg_pessoa_aprova' class='msg'>{$msg_bosch}</div>";
        $msg_pessoa_aprova = '';
      /*
      $msg_bosch = ($sistema_lingua) ?
        "En caso de garantía de repuestos o accesorios no es necesario especificar el producto en la OS" :
        "Nos casos de Garantia de Peças ou Acessórios não é necessário lançar o Produto na OS" ;
      */
        $bloquea_campo = (empty($os)) ? "" : " readonly='true' class='bloqueado' ";
        $txt_responsavel = ($login_pais <> 'BR') ?
          "En caso de cambio de producto, cortesía comercial o técnica, es obligatorio informar del nombre de la persona que lo aprobó y la fecha de aprobación." :
          "Nos casos de Troca de Produto, Cortesia comercial ou técnica é obrigatório informar o nome da pessoa para aprovação.";

                $responsavel = ($login_pais<>'BR') ? "Cortesía del Promotor" : "Aprovador Bosch" ;

                $sql = "SELECT
                            tbl_promotor_treinamento.promotor_treinamento,
                            tbl_promotor_treinamento.nome,
                            tbl_promotor_treinamento.email,
                            tbl_promotor_treinamento.ativo,
                            tbl_escritorio_regional.descricao
                        FROM tbl_promotor_treinamento
                            JOIN tbl_escritorio_regional USING(escritorio_regional)
                        WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
                            AND tbl_promotor_treinamento.ativo ='t'
                            AND tbl_promotor_treinamento.aprova_troca ='t'
                            AND tbl_promotor_treinamento.pais = '$login_pais'
                        ORDER BY tbl_promotor_treinamento.nome";
                $res = pg_query ($con,$sql) ;
                $option_promotor = "<option value=''  ></option>";

                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
                    extract(pg_fetch_array($res));

                    $selected = ($promotor_treinamento2 == $promotor_treinamento) ? " selected " : "" ;

                    $option_promotor .= "<option value='{$promotor_treinamento}' $selected>{$nome}</option>";
                }

                $array_motivo_ordem = array(
                    array(
                        'codigo' => 'XLR',
                        'pt-br' => 'Ameaça PROCON',
                        'es'    => 'Amenaza Defensa Consumidor',
                    ),
                    array(
                        'codigo' => 'XSS',
                        'pt-br' => 'Bloqueio financeiro',
                        'es'    => 'Bloqueo financiero',
                    ),
                    array(
                        'codigo' => 'XLR',
                        'pt-br' => 'Contato SAC',
                        'es'    => 'Contacto SAC',
                    ),
                    array(
                        'codigo' => 'XQR',
                        'pt-br' => 'Defeito reincidente',
                        'es'    => 'Defecto reincidente',
                    ),
                    array(
                        'codigo' => 'XSD',
                        'pt-br' => 'Linha de Medição',
                        'es'    => 'Línea de Medición',
                    ),
                    array(
                        'codigo' => 'XSD',
                        'pt-br' => 'Não existem Peças de reposição (não definidas)',
                        'es'    => 'No hay piezas para recambio (no fueron definidas)',
                    ),
                    array(
                        'codigo' => 'XSS',
                        'pt-br' => 'Peças não disponíveis em estoque',
                        'es'    => 'No hay piezas en stock',
            'block' => 'mostrar_peca_nao_disponivel'
                    ),
                    array(
                        'codigo' => 'XSS',
                        'pt-br' => 'Pedido não fornecido - Valor Mínimo',
                        'es'    => 'Pedido no enviado - Valor Mínimo',
                    ),
                    array(
                        'codigo' => 'XLR',
                        'pt-br' => 'PROCON',
                        'es'    => 'Defensa del Consumidor',
            'block' => 'mostrar_procon'
                    ),
                    array(
                        'codigo' => 'XQR',
                        'pt-br' => 'Solicitação de Fábrica',
                        'es'    => 'Solicitado por la Fábrica',
                    ),
                );

                $motivo_ordem = str_replace("'", "", $motivo_ordem); //HD-3200578

                foreach ($array_motivo_ordem as $frase) {
                    $key   = tira_acentos(sprintf("%s (%s)", $frase['pt-br'], $frase['codigo']));
                    $arr_motivos[$key] =  sprintf("%s (%s)", $frase[$cook_idioma], $frase['codigo']);
                }

                $options_motivo_ordem = array2select(
                    'motivo_ordem', 'motivo_ordem',
                    $arr_motivos, $motivo_ordem,
                    ' style="width:300px ; height:25px; "', ' ',
                    true
                );

                $select_motivo_ordem = "<div class='div_motivo_ordem' style='color: #CC0000; width:300px; float:right; padding:5px '>
                          " . traduz('Motivo Ordem') . " <br>$options_motivo_ordem
                        </div>
                        <div style='clear:both'></div>";


                $select_promotor_treinamento = "
                    {$responsavel}<br>
                    <select name='promotor_treinamento' id='promotor_treinamento' $bloquea_campo style='height: 25px'>
                        {$option_promotor}
                    </select>

                ";

                /*$aprovacao_reponsavel = "
                    <div id='aprovacao_reponsavel'>
                        <div class='aprovacao_reponsavel' style='color: #CC0000; width:250px;  float:left'>
                            {$select_promotor_treinamento}
                        </div>
                        $select_motivo_ordem
                        <div class='msg'>{$txt_responsavel}</div>
                    </div>
                ";*/

                $sql = "SELECT
                            tbl_promotor_treinamento.promotor_treinamento,
                            tbl_promotor_treinamento.nome,
                            tbl_promotor_treinamento.email,
                            tbl_promotor_treinamento.ativo,
                            tbl_escritorio_regional.descricao
                        FROM tbl_promotor_treinamento
                            JOIN tbl_escritorio_regional USING(escritorio_regional)
                        WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
                            AND tbl_promotor_treinamento.ativo ='t'
                            AND   tbl_promotor_treinamento.aprova_troca ='t'
                            AND tbl_promotor_treinamento.pais = '$login_pais'
                        ORDER BY tbl_promotor_treinamento.nome";
                $res = pg_query ($con,$sql) ;

                $option_promotor2 = "<option value=''></option>";

                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
                    extract(pg_fetch_array($res));

                    $selected = (str_replace("'", "", $promotor_treinamento2) == $promotor_treinamento) ? " selected " : "" ;

                    if(strlen($os)>0 and strlen(trim($selected))==0){
                        $disabled = " disabled=true ";
                    }else{
                       $disabled = "";
                    }

                    $option_promotor2 .= "<option value='{$promotor_treinamento}' $selected $disabled >{$nome}</option>";
                }

                $select_promotor_treinamento2 = "
                    {$responsavel}<br>
                    <select name='promotor_treinamento2' id='promotor_treinamento2' $bloquea_campo style='height: 25px'>
                        {$option_promotor2}
                    </select>";

                $aprovacao_reponsavel2 = "
                    <div id='aprovacao_reponsavel2'>
                        <div class='aprovacao_reponsavel' style='color: #CC0000; width:250px; float:left'>
                            {$select_promotor_treinamento2}
                        </div>
                          $select_motivo_ordem
                        <div class='msg'>{$txt_responsavel}</div>
                    </div>";


                    $mostrar_peca_nao_disponivel = "none";
                    $mostrar_nao_existe_pecas = 'none';
                    $mostrar_procon = 'none';
                    $mostrar_solicitacao_fabrica = 'none';
                    $mostrar_linha_medicao = 'none';
                    $mostrar_pedido_nao_fornecido = 'none';
                    $mostrar_sac = 'none';
                    $mostrar_detalhes = 'none';

                    if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
                      $mostrar_peca_nao_disponivel = "block";
                    }
                    if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
                      $mostrar_nao_existe_pecas = 'block';
                    }
                    if($motivo_ordem == 'PROCON (XLR)'){
                      $mostrar_procon = 'block';
                    }
                    if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
                      $mostrar_solicitacao_fabrica = 'block';
                    }
                    if($motivo_ordem == "Linha de Medicao (XSD)"){
                      $mostrar_linha_medicao = 'block';
                    }
                    if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
                      $mostrar_pedido_nao_fornecido = 'block';
                    }

                    if($motivo_ordem == 'Contato SAC (XLR)'){
                      $mostrar_sac = 'block';
                    }
                    if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem ==  'Defeito reincidente (XQR)'){
                      $mostrar_detalhes = 'block';
                    }
                $area_motivo_ordem = "<div id='area_motivo_ordem'>";

                if(strlen($os)>0){
                    $readonly_campos_motivo = 'readonly';
                }


                  $area_motivo_ordem .= "<div id='peca_nao_disponivel' style='display:$mostrar_peca_nao_disponivel'>
                    <TABLE  width='710' border='0'>
                        <TR>
                        <TD rowspan='3' style='width:100px; text-align:right' class='area_motivo_ordem_titulo'> Código das Peças </td>
                        <td> <input type='text' maxlength='15' name='codigo_peca_1' value='".utf8_decode($codigo_peca_1)."' class='frm' $readonly_campos_motivo> </td>
                        <TD rowspan='3' style='width:90px; text-align:right' class='area_motivo_ordem_titulo'> Número de Pedido </td>
                        <td><input type='text' maxlength='15' name='numero_pedido_1' value='".utf8_decode($numero_pedido_1)."' class='frm' $readonly_campos_motivo></td>
                        </tr>
                        <tr>
                          <td><input type='text' maxlength='15' name='codigo_peca_2' value='".utf8_decode($codigo_peca_2)."' class='frm' $readonly_campos_motivo></td>
                          <td><input type='text' maxlength='15' name='numero_pedido_2' value='".utf8_decode($numero_pedido_2)."' class='frm' $readonly_campos_motivo></td>
                        </tr>
                        <tr>
                          <td><input type='text' maxlength='15' name='codigo_peca_3' value='".utf8_decode($codigo_peca_3)."' class='frm' $readonly_campos_motivo></td>
                          <td><input type='text' maxlength='15' name='numero_pedido_3' value='".utf8_decode($numero_pedido_3)."' class='frm' $readonly_campos_motivo></td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='nao_existe_pecas' style='display: $mostrar_nao_existe_pecas'>
                  <TABLE  width='710' border='0'>
                        <TR>
                        <TD rowspan='3' style='width:100px; text-align:right' class='area_motivo_ordem_titulo'> Descrição das Peças </td>
                        <td> <input type='text' maxlength='15' name='descricao_peca_1' value='".utf8_decode($descricao_peca_1)."' class='frm' $readonly_campos_motivo> </td>
                        </tr>
                        <tr>
                          <td><input type='text' maxlength='15' name='descricao_peca_2' value='".utf8_decode($descricao_peca_2)."' class='frm' $readonly_campos_motivo></td>
                        </tr>
                        <tr>
                          <td><input type='text' maxlength='15' name='descricao_peca_3' value='".utf8_decode($descricao_peca_3)."' class='frm' $readonly_campos_motivo></td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='procon' style='display: $mostrar_procon'>
                    <TABLE  width='710' border='0'>
                        <TR>
                          <TD rowspan='3' style='width:60px; text-align:right' class='area_motivo_ordem_titulo'> Protocolo </td>
                          <td> <input type='text' maxlength='15' name='protocolo' value='".utf8_decode($protocolo)."' class='frm' $readonly_campos_motivo> </td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='solicitacao_fabrica' style='display: $mostrar_solicitacao_fabrica '>
                    <TABLE  width='710' border='0'>
                       <TR>
                          <TD style='width:120px; text-align:right' class='area_motivo_ordem_titulo'> Informe CI ou Solicitante </td>
                          <td> <input type='text' maxlength='15' name='ci_solicitante' value='".utf8_decode($ci_solicitante)."' class='frm' $readonly_campos_motivo> </td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='linha_medicao' style='display: $mostrar_linha_medicao '>
                    <TABLE  width='650' border='0'>
                        <tr>
                          <TD style='width:120px; text-align:center' class='area_motivo_ordem_titulo'> Linha de Medição(XSD)</td>
                        </tr>
                        <tr>
                          <td style='width:200px; text-align:center'> <input type='text' maxlength='250' name='linha_medicao' value='".utf8_decode($linha_medicao)."' class='frm' $readonly_campos_motivo style='width:650px;'> </td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='pedido_nao_fornecido' style='display: $mostrar_pedido_nao_fornecido'>
                    <TABLE  width='650' border='0'>
                        <tr>
                          <TD style='width:100px; text-align:center' class='area_motivo_ordem_titulo'>Pedido não fornecido - Valor Mínimo(XSS) </td>
                        </tr>
                        <tr>
                          <td style='width:200px; text-align:center'> <input type='text' maxlength='250' name='pedido_nao_fornecido' value='".utf8_decode($pedido_nao_fornecido)."' class='frm' style='width:650px;' $readonly_campos_motivo> </td>
                        </tr>
                    </table>
                  </div>";

                  //HD-3200578
                  $area_motivo_ordem .= "<div id='contato_sac' style='display: $mostrar_sac'>
                    <TABLE  width='710' border='0'>
                        <TR>
                          <TD rowspan='3' style='width:70px; text-align:right' class='area_motivo_ordem_titulo'> N° do Chamado: </td>
                          <td> <input type='text' maxlength='50' style='width:450px;' name='contato_sac' value='".utf8_decode($contato_sac)."' class='frm' $readonly_campos_motivo> </td>
                        </tr>
                    </table>
                  </div>";

                  $area_motivo_ordem .= "<div id='detalhe' style='display: $mostrar_detalhes'>
                    <TABLE  width='710' border='0'>
                        <TR>
                          <TD rowspan='3' style='width:60px; text-align:right' class='area_motivo_ordem_titulo'> Detalhe: </td>
                          <td> <input type='text' maxlength='100' name='detalhe' style='width:450px;' value='".utf8_decode($detalhe)."' class='frm' $readonly_campos_motivo> </td>
                        </tr>
                    </table>
                  </div>";
                  // FIM HD-3200578
                $area_motivo_ordem .= "</div>";

                $grupo_os->set_html_after($msg_pessoa_aprova.$aprovacao_reponsavel.$aprovacao_reponsavel2.$area_motivo_ordem);
            }

        }else{
            $grupo_os->campos["tipo_atendimento"]->add_option('1', '01 - Garantia');
            $grupo_os->campos["tipo_atendimento"]->add_option('2', '02 - Garantia com deslocamento');
        }

        $grupo_produto = new grupo("dados_produto", $campos_telecontrol[$login_fabrica], "dados.do.produto");
          
            $grupo_produto->add_field("tbl_os", "serie");

            $grupo_produto->campos['serie']->maxlength = ($login_fabrica == 85) ? 10 : 9;

            $grupo_produto->add_field("tbl_os", "produto");
            $grupo_produto->add_element(new input_hidden("produto_referencia", "", $campos_telecontrol[$login_fabrica]["tbl_os"]["produto"]["valor_referencia"]));
            //$grupo_produto->add_element(new input_hidden("produto_last", "",$produto_id));
            //$grupo_produto->add_element(new input_hidden("produto_id", "",$produto_id));


        if($login_fabrica == 20){

            if ($tipo_atendimento_gravado != 10 && $tipo_atendimento_gravado != 13) {
              $grupo_produto->dados_campos["tbl_os"]["data_nf"]["bloqueia_edicao"] = 0;
              $grupo_produto->dados_campos["tbl_os"]["nota_fiscal"]["bloqueia_edicao"] = 0;
            }
            $grupo_produto->add_field("tbl_os", "voltagem");
            $grupo_produto->add_field("tbl_os", "data_nf");
            $grupo_produto->add_field("tbl_os", "nota_fiscal");

            /*
            $grupo_produto->add_field("tbl_os", "aparencia_produto");
            $grupo_produto->campos["aparencia_produto"]->add_css_class("aparencia_produto_bosch", "input");
            $array_aparencia =
                array(
                    "NEW" => array(
                                    "pt-br" => "Bom estado",
                                    "ES" => "Buena aparencia"),
                    "USL" => array(
                                    "pt-br" => "Uso intenso",
                                    "ES" => "Uso continuo"),
                    "USN" => array(
                                    "pt-br" => "Uso Normal",
                                    "ES" => "Uso Normal"),
                    "USH" => array(
                                    "pt-br" => "Uso Pesado",
                                    "ES" => "Uso Pesado"),
                    "ABU" => array(
                                    "pt-br" => "Uso Abusivo",
                                    "ES" => "Uso Abusivo"),
                    "ORI" => array(
                                    "pt-br" => "Original, sem uso",
                                    "ES" => "Original, sin uso"),
                    "PCK" => array(
                                    "pt-br" => "Embalagem",
                                "ES" => "Embalaje")
                );

            foreach ($array_aparencia as $valor => $a_desc) {
                $desc = ($sistema_lingua == 'ES') ? $a_desc['ES'] : $a_desc['pt-br'];
                $grupo_produto->campos["aparencia_produto"]->add_option($valor,$desc);
            }

            $grupo_produto->add_field("tbl_os", "acessorios");
            $grupo_produto->campos["acessorios"]->add_css_class("acessorios_bosch", "input");
            */
			if(!empty($os)) {
				$sqlVerificaTdocs = "SELECT tdocs FROM tbl_tdocs 
					WHERE referencia_id = {$os}
					AND referencia = 'osserie'
					AND situacao = 'ativo'";
				$resVerificaTdocs = pg_query($con, $sqlVerificaTdocs);
			}

				if ($foto_serie_produto && pg_num_rows($resVerificaTdocs) == 0) {
					$html_anexoNF = "";


					$labelText  = traduz('label.anexo.serie.999', $con);
					$imgTitle   = traduz('para.cadastrar.outra.imagem.envie.um.novo.arquivo.ao.lado', $con);
					$imgLabel   = traduz('arquivo.anexado', $con);
					$imgAltText = traduz('anexo.de.num.serie', $con);

					// Mostra o arquivo se já subiu e houve algum outro erro no processamennto da OS
					if (isset($_POST['anexo_serie'])):
						$html_anexo_serie = "
				<div style='width:30%;float:left;height:4em;text-align:left'
				   title='$imgTitle'>
				  $imgLabel:
				  <a href='$anexoSerieURL' target='new'>
					<img id='img_anexo' class='anexo' src='$anexoSerieURL' alt='$imgAltText' style='max-height:4em;vertical-align:top' />
				  </a>
				  <input type='hidden' name='anexo_serie' value='{$_POST['anexo_serie']}' />
				</div>";
					endif;

					$visivel = ($anexaFotoSerie(array('tipo_atendimento'=>$_POST['tipo_atendimento'], 'serie'=>$_POST['serie']))) ? 'style="text-align:right;display:block"' : 'style="text-align:right;display:none"';
					$html_anexo_serie = "
							  <div id='div_anexo_serie' $visivel>
								$html_anexo_serie
								<label for='input_anexo_serie' style='margin-right:45px;color:red'>$imgAltText
								<!-- <label for='input_anexo_serie'>$labelText</label> -->
								<input id='input_anexo_serie' type='file' style='color:black;' name='anexo_serie' class='frm' />
								</label>
							  </div>
							  ";
					$html_anexoNF = $html_anexo_serie;
				}
        }elseif ($login_fabrica == 85) {
            $grupo_produto->add_field("tbl_os", "nota_fiscal");
            $grupo_produto->add_field("tbl_os", "data_nf");
            $grupo_produto->add_field("tbl_os", "defeito_reclamado_descricao");
        }else{
            $grupo_produto->add_field("tbl_os", "nota_fiscal");
            $grupo_produto->add_field("tbl_os", "data_nf");
            $grupo_produto->add_field("tbl_os", "prateleira_box");
            $grupo_produto->add_field("tbl_os", "defeito_reclamado_descricao");
            $grupo_produto->add_field("tbl_os", "aparencia_produto");
            $grupo_produto->add_field("tbl_os", "acessorios");
        }
  }

    // $grupo_produto->add_element(new input_hidden("produto_last", "",$produto_id));
    // $grupo_produto->add_element(new input_hidden("produto_id", "",$produto_id));//$produto_id

  if ($anexaNotaFiscal && !$fabricaFileUploadOS) {
    if (strlen($os) > 0)
      $link_nf = temNF($os, 'count');

    $input_anexo = ($link_nf < LIMITE_ANEXOS) ? "<div id='DIVAnexo'>" . $inputNotaFiscal . "</div>\n" : '';

    $tipo_tabelaNF = ($data_finalizada) ? 'link' : 'linkEx';

    $html_anexoNF .= ($link_nf > 0) ? temNF($os, $tipo_tabelaNF) : '';
      $html_anexoNF.= $input_anexo;
  }

  if ($input_anexo or $html_anexo_serie) {
    $html_anexoNF .= $include_imgZoom;
  }

  $grupo_produto->set_html_after($html_anexoNF);

  $grupo_cliente = new grupo("dados_cliente", $campos_telecontrol[$login_fabrica], traduz("dados.do.cliente"));

  $campos_consumidor = array_keys(array_key_filter($campos_telecontrol[$login_fabrica]['tbl_os'], 'consumidor_'));

  foreach ($campos_consumidor as $campo_nome) {
    if ($campo_nome == 'cod_ibge') {
      $grupo_cliente->add_element(new input_hidden('cod_ibge', '', $cod_ibge)); //gravar na tbl_os
      continue;
    }

    if ($campo_nome == 'consumidor_revenda')
      continue;

        if ($login_fabrica == 20) {
            $grupo_cliente->dados_campos["tbl_os"][$campo_nome]["bloqueia_edicao"] = 0;
        }

    $grupo_cliente->add_field('tbl_os', $campo_nome);


    if ($campo_nome == 'consumidor_cidade')
      $grupo_cliente->campos["consumidor_cidade"]->set_attr("rel='consumidor'");

    if ($campo_nome == 'consumidor_estado') {
      if ($login_pais == 'BR' || empty($login_pais)) {
        $grupo_cliente->campos["consumidor_estado"]->add_option(""," ");
        foreach($estados as $sigla => $estado){
          $grupo_cliente->campos["consumidor_estado"]->add_option($sigla,$sigla." - ".$estado);
        }
      }
    }

    if (in_array($campo_nome, array('consumidor_fone','consumidor_fone2','consumidor_celular'))) {
      $css_class = ($login_pais == '' or $login_pais  == 'BR') ? 'fones' : 'numeric';
      $grupo_cliente->campos[$campo_nome]->add_css_class($css_class, "input");
    }
  }

  $grupo_km = new grupo("dados_km", $campos_telecontrol[$login_fabrica], "controle.de.deslocamento");
  $grupo_km->add_field("tbl_os", "pedagio");
    $sql = "
        SELECT
            contato_endereco AS endereco,
            contato_numero AS numero,
            contato_bairro AS bairro,
            contato_cidade AS cidade,
            contato_estado AS estado,
            contato_cep AS cep,
            tbl_posto_fabrica.longitude||','||tbl_posto_fabrica.latitude AS latlng
        FROM tbl_posto_fabrica
            JOIN tbl_posto USING(posto)
        WHERE
            posto = $login_posto
            AND fabrica = $login_fabrica;";
  $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $info_posto     = pg_fetch_assoc($res, 0);
        $endereco_posto = $info_posto['endereco'] . ', ' . $info_posto['numero'] . ' ' . $info_posto['cidade'] . ' ' . $info_posto['estado'];
        if (!is_null($info_posto['latlng'])) $coord_posto = $info_posto['latlng'];

        if (strlen($distancia_km) == 0) $distancia_km = 0;

        $cep_posto = pg_fetch_result($res, 0, "cep");

    }

  $html = "
  <script src='http://maps.google.com/maps?file=api&v=2&key={$gAPI_key}' type='text/javascript'></script>
  <input type='hidden' id='ponto1' name='ponto1' value='{$endereco_posto}' />
  <input type='hidden' id='coordPosto' value='{$coord_posto}' />
  <input type='hidden' id='cep_posto' value='{$cep_posto}' />
  <input type='hidden' id='distancia_km_maps'  value='' />
  <input type='hidden' name='distancia_km_conferencia' id='distancia_km_conferencia' value='{$distancia_km_conferencia}'>
  <input type='hidden' name='categoria' id='categoria' value='$categoria'>
  ";

  $html .= "
  <div class='toolbar'><b>Endereço do posto:</b> <u>{$endereco_posto}</u><br></div>";
  $grupo_km->set_html_before($html);

  $html = "<div class='toolbar'>";

  $grupo_km->add_field("tbl_os", "qtde_km");
    if ($campos_telecontrol[$login_fabrica]['tbl_os']['qtde_km']['bloqueia_edicao'] != 1 AND $login_fabrica != 85) {
      $html .= "<div id='div_mapa_msg'></div><input type='button' id='btn_ver_mapa' name='btn_ver_mapa' onclick='vermapa();' value='Ver mapa' /><input id='btn_calcula_distancia' name='btn_calcula_distancia' type='button' onclick='initialize(\"\")' value='Calcular Distância'></div>";
    }
  $html .= "
  <div id='mapa' style=' width:688px; height:300px;visibility:hidden;position:absolute;border: 1px #999999 solid; '></div></div>\n";

  $grupo_km->set_html_after($html);

    $grupo_revenda = new grupo("dados_revenda", $campos_telecontrol[$login_fabrica], "dados.da.revenda");

  $campos_revenda = array_keys(array_key_filter($campos_telecontrol[$login_fabrica]['tbl_os'], 'revenda_'));

  foreach ($campos_revenda as $campo_nome) {

    $grupo_revenda->add_field('tbl_os', $campo_nome);

    if ($campo_nome == 'revenda_estado') {
      if ($login_pais == 'BR' || empty($login_pais)) {
        $grupo_revenda->campos["revenda_estado"]->add_option(""," ");
        foreach($estados as $sigla => $estado){
          $grupo_revenda->campos["revenda_estado"]->add_option($sigla,$sigla." - ".$estado);
        }
      }
        }

    if ($campo_nome == 'revenda_cidade')
      $grupo_revenda->campos["revenda_cidade"]->set_attr("rel='revenda'");
  }

  $grupo_analise = new grupo("analise_os", $campos_telecontrol[$login_fabrica], "analise.da.os");

  $grupo_analise->set_html_before("<div id='analise_os_corpo'>");

  if ($login_fabrica == 87) {

    $grupo_analise->set_html_after("</div>");

    $grupo_analise->add_field("tbl_os", "tecnico");
      $sql = "SELECT tecnico, nome FROM tbl_tecnico WHERE fabrica = $login_fabrica AND posto = $login_posto ORDER BY nome ASC;";
      $res = pg_query($con, $sql);
      if (pg_num_rows($res) > 0) {
        //if(empty($tecnico))
          $grupo_analise->campos["tecnico"]->add_option("",'');

        for($i=0; $i < pg_num_rows($res); $i++){
          $tecnico  =  pg_fetch_result($res, $i, "tecnico");
          $nome     =  pg_fetch_result($res, $i, "nome");

          $grupo_analise->campos["tecnico"]->add_option($tecnico,$nome);
        }
      }

    $grupo_analise->add_field("tbl_os", "defeito_constatado");
      if(empty($familia))
        $grupo_analise->campos["defeito_constatado"]->add_option(0,traduz('Informe um Produto'));
      else{
        $sql_defeito_constatado = "
            SELECT DISTINCT
              tbl_defeito_constatado.defeito_constatado,
              tbl_defeito_constatado.descricao
            FROM tbl_diagnostico
              JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
            WHERE
              tbl_defeito_constatado.fabrica = $login_fabrica
              AND tbl_diagnostico.familia = $familia ORDER BY tbl_defeito_constatado.descricao ASC;";
        $res_defeito_constatado = pg_query($con, $sql_defeito_constatado);

        if(pg_num_rows($res_defeito_constatado) > 0){
          //if(empty($defeito_constatado))
            $grupo_analise->campos["defeito_constatado"]->add_option('','');

          for($i = 0; $i < pg_num_rows($res_defeito_constatado); $i++) {
            extract(pg_fetch_array($res_defeito_constatado));

            $grupo_analise->campos["defeito_constatado"]->add_option($defeito_constatado,$descricao);
          }
        }else{
          $grupo_analise->campos["defeito_constatado"]->add_option(0,'nenhum defeito constatado encontrado');
        }
      }

  }else{
    if ( $login_fabrica != 20) {
      $grupo_analise->set_html_after("</div><div class='toolbar'><input id='analise_adicionar_linha' name='analise_adicionar_linha' type='button' onclick='analise_adicionar()' value='".traduz('adicionar.nova.linha', $con)."'></div>");
      $grupo_analise->set_header_labels(array("Defeito Constatado", "Solução"));
    } else {

      if ( !empty($os) ) {
        $sql = "SELECT solucao_os, defeito_constatado, defeito_reclamado, causa_defeito
            FROM tbl_os
            WHERE os = $os
            AND fabrica = $login_fabrica;";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res)) {
          $causa_defeito      = pg_result($res,0,'causa_defeito');
          $defeito_constatado = pg_result($res,0,'defeito_constatado');
          $defeito_reclamado  = pg_result($res,0,'defeito_reclamado');
          $servico_realizado  = pg_result($res,0,'solucao_os');
        }
      }

      if ( !empty($produto_id) ) {

        $sql = "SELECT linha FROM tbl_produto WHERE fabrica_i = $login_fabrica AND produto = $produto_id";
        $res2 = pg_query($con,$sql);

        if (pg_num_rows($res2)) {
          $linha =  pg_result($res2,0,0);
          if($login_fabrica <> 20){
            $cond = empty($defeito_reclamado)  ? '' : " AND DR.defeito_reclamado = $defeito_reclamado";
          }
          $sql = "SELECT DR.defeito_reclamado, COALESCE(DRI.descricao, DR.descricao) AS descricao
                FROM tbl_defeito_reclamado DR
             LEFT JOIN tbl_defeito_reclamado_idioma DRI
                ON DRI.defeito_reclamado = DR.defeito_reclamado
               WHERE fabrica = $login_fabrica
                 AND linha = $linha
                 $cond";
          $res2 = pg_query($con,$sql);
          $opt = '';
          for ($z=0; $z < pg_num_rows($res2); $z++) {
            $opt .= '<option value="' . pg_result($res2,$z,'defeito_reclamado') . '">' . pg_result($res2,$z,'descricao') . '</option>';
          }
        }
      }

      if($login_fabrica <> 20){
                $cond = empty($causa_defeito)  ? '' : ' AND causa_defeito = ' . $causa_defeito . ' ';
      }

      $sql = "SELECT * FROM tbl_causa_defeito WHERE fabrica = $login_fabrica $cond ORDER BY codigo, descricao";
            $sql = "SELECT CD.causa_defeito, codigo || ' - ' || COALESCE(CDI.descricao, CD.descricao) AS descricao
              FROM tbl_causa_defeito CD
         LEFT JOIN tbl_causa_defeito_idioma CDI
                ON CDI.causa_defeito = CD.causa_defeito
            AND idioma = '".$sistema_lingua."'
            where   " .
        sql_where( array_filter(
          array(
            'fabrica' => $login_fabrica,
            'CD.causa_defeito' => $causa_defeito
          )), 'strlen'
        );

      $selCausaDefeito = array2select(
        'causa_defeito', 'causa_defeito',
        pg_fetch_pairs($con, $sql), $causa_defeito,
        '', ' ',
        true
      );

      $cond_acessorio = ($login_fabrica==20 and $tipo_atendimento==12) ? 'AND T.garantia_acessorio IS NOT TRUE' : '';
      $sql = "
        SELECT T.servico_realizado, COALESCE(TI.descricao, T.descricao) AS descricao
          FROM tbl_servico_realizado T
       LEFT JOIN tbl_servico_realizado_idioma TI
          ON T.servico_realizado = TI.servico_realizado
           AND idioma  = UPPER('{$cook_idioma}')
         WHERE T.fabrica = $login_fabrica
           AND T.solucao IS NOT TRUE
           AND T.ativo IS TRUE
           $cond_acessorio
         ORDER BY descricao
      ";

            $res = pg_query($con,$sql);
            if (empty( $servico_realizado)) {
                $optIdentificacao .= '<option></option>';
            }

            for($i = 0; $i< pg_num_rows($res); $i++) {
                $selected = ($_POST['solucao_os'] == pg_result($res,$i, 'servico_realizado')) ? 'selected' : '';

//                 if($login_fabrica == 20){
//                     $selected = ($servico_realizado == pg_fetch_result($res, $i, 'servico_realizado')) ? 'selected' : '';
//                 }

                $optIdentificacao .= '<option value="'.pg_result($res,$i,'servico_realizado').'" '.$selected.'>'.pg_result($res,$i,'descricao').'</option>';

            }

            if (!empty($produto_id)) {
                if($login_fabrica <> 20){
                    $cond = empty($defeito_constatado)  ? '' : ' AND tbl_defeito_constatado.defeito_constatado = ' . $defeito_constatado . ' ';
        }else{
          $cond = '';
        }

        $sql = "
          SELECT
            tbl_defeito_constatado.defeito_constatado,
            tbl_defeito_constatado.codigo,
            COALESCE(DCI.descricao, tbl_defeito_constatado.descricao) AS descricao
          FROM tbl_defeito_constatado
            JOIN tbl_produto_defeito_constatado ON tbl_produto_defeito_constatado.defeito_constatado = tbl_defeito_constatado.defeito_constatado
           LEFT JOIN tbl_defeito_constatado_idioma AS DCI
              ON DCI.defeito_constatado = tbl_defeito_constatado.defeito_constatado
             AND DCI.idioma = '$sistema_lingua'
          WHERE
            tbl_defeito_constatado.fabrica = {$login_fabrica}
            AND tbl_defeito_constatado.ativo IS TRUE
            AND tbl_produto_defeito_constatado.produto = {$produto_id}
            $cond
          ORDER BY tbl_defeito_constatado.descricao ASC;";

        $res2 = pg_query($con,$sql);
        if (empty($defeito_constatado)) {
          $optConstatado = '<option></option>';
        }
        for ($z=0; $z < pg_num_rows($res2); $z++) {
          $selected = ($_POST['defeito_constatado'] == pg_result($res2,$z, 'defeito_constatado')) ? 'selected' : '';
          $optConstatado .= '<option value="' . pg_result($res2,$z,'defeito_constatado') . '" '.$selected.'>'.pg_result($res2,$z,'codigo') .' - ' . pg_result($res2,$z,'descricao') . '</option>';

        }
      }

            $html = '
                <div style="width:300px;">
                    <label class="obrigatorio">' . traduz('identificacao', $con) . '</label>
                    <input type="hidden" name="id_solucao_os" id="id_solucao_os" value='.$id_solucao_os.'>
                    <select name="solucao_os" id="solucao_os" style="width:300px;height:25px;">
                        '.$optIdentificacao.'
                    </select>
                </div>
                <p style="clear:both; overflow:hidden;">&nbsp;</p>';
            $grupo_analise->set_html_after($html);
    }
    $grupo_analise->add_element(new input_hidden("n_linhas_analise", '', $n_linhas_analise));

    for ($i = -1; $i < $n_linhas_analise; $i++) {
      $seq = $i == -1 ? "__modelo__" : $i;

      if($i > 0 AND !empty($produto_id)){
        $peca_referencia          = $_POST["peca_referencia{$i}"];
        $peca_descricao           = $_POST["peca_descricao{$i}"];
        $item_causador_referencia = $_POST["item_causador_referencia{$i}"];
        $item_causador_descricao  = $_POST["item_causador_descricao{$i}"];
      }

      if ($login_fabrica != 20) {

        $grupo_analise->add_field("tbl_os", "defeito_constatado", "defeito_constatado{$seq}");
        $grupo_analise->campos["defeito_constatado{$seq}"]->add_css_class("defeito_constatado_analise", "input");
        $grupo_analise->campos["defeito_constatado{$seq}"]->add_css_class("campos_analise", "div");
        $grupo_analise->campos["defeito_constatado{$seq}"]->set_attr("seq='{$seq}'");
        $grupo_analise->campos["defeito_constatado{$seq}"]->set_value($itens_analise[$seq]["defeito_constatado"]);

      } else {
        /*
        $grupo_analise->add_field("tbl_os", "defeito_reclamado");
        $grupo_analise->campos["defeito_reclamado"]->add_css_class("defeito_reclamado_analise", "input");
        $grupo_analise->campos["defeito_reclamado"]->add_css_class("campos_analise", "div");
        $grupo_analise->campos["defeito_reclamado"]->set_attr("seq='{$seq}'");
        $grupo_analise->campos["defeito_reclamado"]->set_value($_POST['defeito_reclamado']);
        */
        // @verifica com ajax e carrega os defeitos conforme a linha
        /*if (strlen($os) == 0)
          $grupo_analise->campos["defeito_reclamado"]->add_option('','');*/

      }

            if($campos_telecontrol[$login_fabrica]['tbl_os']['defeito_constatado']['tipo'] == 'texto')
          $grupo_analise->campos["defeito_constatado{$seq}"]->set_autocomplete_values($itens_analise[$seq]["defeito_constatado_id"], $itens_analise[$seq]["defeito_constatado_last"]);

      if ( $login_fabrica != 20) {
        $grupo_analise->add_field("tbl_os", "solucao_os", "solucao_os{$seq}");
        $grupo_analise->campos["solucao_os{$seq}"]->add_css_class("solucao_os_analise", "input");
        $grupo_analise->campos["solucao_os{$seq}"]->add_css_class("campos_analise", "div");
        if (isset($itens_analise[$seq]["solucao_os"]) && isset($itens_analise[$seq]["solucao_os_descricao"])) {
          $grupo_analise->campos["solucao_os{$seq}"]->add_option($itens_analise[$seq]["solucao_os"], $itens_analise[$seq]["solucao_os_descricao"]);
        }
        $grupo_analise->campos["solucao_os{$seq}"]->set_value($itens_analise[$seq]["solucao_os"]);
        $grupo_analise->campos["solucao_os{$seq}"]->set_label("");
        $grupo_analise->campos["defeito_constatado{$seq}"]->set_label("");
                if ($login_fabrica == 85) {
                    if (isset($_REQUEST["defeito_constatado{$seq}"])) {
                        $grupo_analise->campos["defeito_constatado{$seq}"]->set_value($_REQUEST["defeito_constatado{$seq}"]);
                    }else{
                        $grupo_analise->campos["defeito_constatado{$seq}"]->set_value($itens_analise[$seq]['defeito_descricao']);
                    }
                }
      } else {
        //$grupo_analise->campos["defeito_reclamado"]->set_label("");

      }

      if ($seq == "__modelo__") {

        if ( $login_fabrica != 20) {
          $grupo_analise->campos["defeito_constatado{$seq}"]->add_css_class($seq, "div");
          $grupo_analise->campos["solucao_os{$seq}"]->add_css_class($seq, "div");
        }else {
          /*$grupo_analise->campos["defeito_reclamado"]->add_css_class($seq, "div");
          $grupo_analise->campos["solucao_os"]->add_css_class($seq, "div");
          $grupo_analise->campos["defeito"]->add_css_class($seq, "div");
          $grupo_analise->campos["defeito_constatado"]->add_css_class($seq, "div");*/
        }
      }
    }
  }

  $grupo_itens = new grupo(
    ($login_fabrica == 87 ? 'itens_os_2' : 'itens_os'),
    $campos_telecontrol[$login_fabrica],
    ($login_fabrica == 20) ? traduz("Peças Trocadas") : traduz('Itens da Ordem de Serviço')
  );

  $grupo_itens->set_html_before("<div id='itens_os_corpo'>");

  $botoes = "</div><div class='toolbar'>";
  $baseBtn = '<input id="%1$s" name="%1$s" type="button" value="%2$s" />';

  if ($login_fabrica == 20) {
    $botoes .= sprintf($baseBtn, 'btn_lbm', traduz('lista.basica', $con));
  }
  if($login_fabrica != 87 AND $login_fabrica <> 20){ //hd_chamado=2843341
    $botoes .= sprintf($baseBtn, 'btn_lista_basica', traduz(array('consultar', 'lista.basica'), $con));
  }


  if ($login_fabrica != 20) {
    $botoes .= sprintf($baseBtn, 'btn_vista_explodida', traduz('vista.explodida', $con));
    $botoes .= sprintf($baseBtn, 'itens_os_adicionar_linha', traduz('adicionar.nova.linha', $con));
  }

  $grupo_itens->set_html_after("$botoes</div>");

    switch($login_fabrica) {
        case 87 : $grupo_itens->set_header_labels(array("Peça Referência - Descrição", "Qtd", "Lista", "Causa Falha","Item Causador","Serviço Realizado")); break;
        case 20 : $grupo_itens->set_header_labels(array(traduz("peca.referencia.descricao", $con), traduz("qtde", $con))); break;
        default : $grupo_itens->set_header_labels(array(traduz("Peça"), traduz("Qtde"), traduz("Defeito"), traduz("Serviço")));
    }

  $grupo_itens->add_element(new input_hidden("n_linhas_pecas", '', $n_linhas_pecas));

  //$n_linhas_pecas = (strlen($os) == 0) ? $n_linhas_pecas : $n_linhas_pecas + 1;
  for ($i = -1; $i < $n_linhas_pecas; $i++) {
    $seq = $i == -1 ? "__modelo__" : $i;

    $grupo_itens->add_element(new input_hidden("os_item{$seq}", '', $itens_pecas[$seq]["os_item"]));

    if ($login_fabrica == 87) {
      $grupo_itens->add_field("tbl_os", "peca_referencia_descricao", "peca_referencia_descricao{$seq}");
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->add_css_class("itens_os_2_header_label_0", "input");
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->add_css_class("itens_os_2_header_label_0", "div");
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->set_attr("seq='{$seq}'");
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->set_value($itens_pecas[$seq]["peca_referencia_descricao"]);
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->set_label("");

        $grupo_itens->add_element(new input_hidden("peca_id{$seq}", '', $itens_pecas[$seq]["peca_id"]));
        $grupo_itens->add_element(new input_hidden("peca_referencia{$seq}", '', $itens_pecas[$seq]["peca_referencia"]));

      $grupo_itens->add_element(new input_hidden("qtde_lb{$seq}", "", $itens_pecas[$seq]["qtde_lb"]));
        $grupo_itens->campos["qtde_lb{$seq}"]->add_css_class("qtde_lb_itens", "input");

      $grupo_itens->add_field("tbl_os", "qtde", "qtde{$seq}");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class("qtde_itens", "input");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class("itens_os_2_header_label_1", "div");
        $grupo_itens->campos["qtde{$seq}"]->set_attr("seq='{$seq}'");
        if (strlen($itens_pecas[$seq]["qtde"]) > 0) {
          $grupo_itens->campos["qtde{$seq}"]->set_value(str_replace(".", ",", $itens_pecas[$seq]["qtde"]));
        }
        else {
          $grupo_itens->campos["qtde{$seq}"]->set_value("");
        }
        $grupo_itens->campos["qtde{$seq}"]->set_label("");

      $grupo_itens->add_element(new img("lista_basica{$seq}", '', 'Lista Basica','lista_basica'));
        $grupo_itens->campos["lista_basica{$seq}"]->add_css_class("itens_os_2_header_label_2", "div");
        $grupo_itens->campos["lista_basica{$seq}"]->set_attr("rel='{$seq}'");

      $grupo_itens->add_field("tbl_os", "defeito", "defeito{$seq}");
        $grupo_itens->campos["defeito{$seq}"]->add_css_class("itens_os_2_header_label_3", "input");
        $grupo_itens->campos["defeito{$seq}"]->add_css_class("itens_os_2_header_label_3", "div");
        $grupo_itens->campos["defeito{$seq}"]->set_attr("seq='{$seq}'");
        $grupo_itens->campos["defeito{$seq}"]->set_value($itens_pecas[$seq]["defeito"]);
        $grupo_itens->campos["defeito{$seq}"]->set_label("");

            $peca_id = $itens_pecas[$seq]['peca_id'];

            if(!empty($peca_id)){
        $sql_defeito = "
            SELECT D.defeito,
             COALESCE(D.descricao, D.descricao) AS descricao D.codigo_defeito
          FROM tbl_peca_defeito
          JOIN tbl_defeito
          ON D.defeito       = tbl_peca_defeito.defeito
          LEFT
          JOIN tbl_defeito_idioma DI
          ON DI.defeito     = D.defeito
           AND DI.iodioma     = UPPER('$coook_idioma')
         WHERE tbl_peca_defeito.peca = {$peca_id}
           AND D.ativo IS TRUE
         ORDER BY descricao ASC;";
        $res_defeito = pg_query($con, $sql_defeito);

        if(pg_num_rows($res_defeito) > 0){
          //if(intval($itens_pecas[$seq]['defeito']) == 0)
          $grupo_itens->campos["defeito{$seq}"]->add_option('','');

          for($x = 0; $x < pg_num_rows($res_defeito); $x++) {
            $defeito_id = pg_fetch_result($res_defeito, $x, "defeito");
            $defeito_descricao = pg_fetch_result($res_defeito, $x, "descricao");

            $grupo_itens->campos["defeito{$seq}"]->add_option($defeito_id,$defeito_descricao);
          }
        }else
          $grupo_itens->campos["defeito{$seq}"]->add_option($defeito,$descricao);
      }

      $grupo_itens->add_field("tbl_os", "item_causador_referencia_descricao", "item_causador_referencia_descricao{$seq}");
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->add_css_class("itens_os_2_header_label_4", "input");
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->add_css_class("itens_os_2_header_label_4", "div");
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->set_attr("seq='{$seq}'");
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->set_value($itens_pecas[$seq]["item_causador_referencia_descricao"]);
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->set_label("");

        $grupo_itens->add_element(new input_hidden("item_causador_id{$seq}", '', $itens_pecas[$seq]["item_causador_id"]));
        $grupo_itens->add_element(new input_hidden("item_causador_referencia{$seq}", '', $itens_pecas[$seq]["item_causador_referencia"]));

      $grupo_itens->add_field("tbl_os", "servico", "servico{$seq}");
        $grupo_itens->campos["servico{$seq}"]->add_css_class("itens_os_2_header_label_5", "input");
        $grupo_itens->campos["servico{$seq}"]->add_css_class("itens_os_2_header_label_5", "div");
        $grupo_itens->campos["servico{$seq}"]->set_value($itens_pecas[$seq]["servico"]);
                $grupo_itens->campos["servico{$seq}"]->set_label("");

      $cond_acessorio = ($login_fabrica==20 and $tipo_atendimento==12) ? 'AND T.garantia_acessorio IS NOT TRUE' : '';
      $sql = "
        SELECT T.servico_realizado, COALESCE(TI.descricao, T.descricao) AS descricao
          FROM tbl_servico_realizado        T
          JOIN tbl_servico_realizado_idioma TI
          ON T.servico_realizado = TI.servico_realizado
           AND idioma  = UPPER('{$cook_idioma}')
         WHERE T.fabrica = $login_fabrica
           AND T.solucao IS NOT TRUE
           AND T.ativo IS TRUE
           $cond_acessorio
         ORDER BY descricao
      ";

      $res = pg_query($con, $sql);

            $grupo_itens->campos["servico{$seq}"]->add_option("", "");
      for ($j = 0; $j < pg_num_rows($res); $j++) {
        extract(pg_fetch_array($res));
        $grupo_itens->campos["servico{$seq}"]->add_option($servico_realizado, $descricao);
      }


      if ($seq == "__modelo__") {
        $grupo_itens->campos["peca_referencia_descricao{$seq}"]->add_css_class($seq, "div");
        $grupo_itens->campos["lista_basica{$seq}"]->add_css_class($seq, "div");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class($seq, "div");
                $grupo_itens->campos["defeito{$seq}"]->add_css_class($seq, "div");
        $grupo_itens->campos["item_causador_referencia_descricao{$seq}"]->add_css_class($seq, "div");
                $grupo_itens->campos["servico{$seq}"]->add_css_class($seq, "div");
      }
    } else {

      $grupo_itens->add_field("tbl_os", "peca", "peca{$seq}");
        $grupo_itens->campos["peca{$seq}"]->add_css_class("peca_itens", "input");
        $grupo_itens->campos["peca{$seq}"]->add_css_class("campos_itens", "div");
        $grupo_itens->campos["peca{$seq}"]->set_attr("seq='{$seq}'");
        $grupo_itens->campos["peca{$seq}"]->set_value($itens_pecas[$seq]["peca"]);
        $grupo_itens->campos["peca{$seq}"]->set_autocomplete_values($itens_pecas[$seq]["peca_id"], $itens_pecas[$seq]["peca_last"]);

            if (in_array($login_fabrica, array(85))) {
                $grupo_itens->add_element(new input_hidden("peca_preco{$seq}", "", $itens_pecas[$seq]["peca_preco"]));
                $grupo_itens->add_element(new input_hidden("peca_ref{$seq}", "", $itens_pecas[$seq]["peca_ref"]));
            }

      $grupo_itens->add_element(new input_hidden("qtde_lb{$seq}", "", $itens_pecas[$seq]["qtde_lb"]));
        $grupo_itens->campos["qtde_lb{$seq}"]->add_css_class("qtde_lb_itens", "input");

      $grupo_itens->add_field("tbl_os", "qtde", "qtde{$seq}");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class("qtde_itens", "input");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class("campos_itens", "div");
        $grupo_itens->campos["qtde{$seq}"]->set_attr("seq='{$seq}'");
        if (strlen($itens_pecas[$seq]["qtde"]) > 0) {
          $grupo_itens->campos["qtde{$seq}"]->set_value(str_replace(".", ",", $itens_pecas[$seq]["qtde"]));
        }
        else {
          $grupo_itens->campos["qtde{$seq}"]->set_value("");
        }
      if ($login_fabrica != 20) {
        $grupo_itens->add_field("tbl_os", "defeito", "defeito{$seq}");
        $grupo_itens->campos["defeito{$seq}"]->add_css_class("defeito_itens", "input");
        $grupo_itens->campos["defeito{$seq}"]->add_css_class("campos_itens", "div");
        $grupo_itens->campos["defeito{$seq}"]->set_value($itens_pecas[$seq]["defeito"]);

        $sql = "
        SELECT
        tbl_defeito.defeito,
        tbl_defeito.descricao

        FROM
        tbl_defeito

        WHERE
        tbl_defeito.fabrica = $login_fabrica
        AND tbl_defeito.ativo IS TRUE
        ";
        $res = pg_query($con, $sql);

        for ($j = 0; $j < pg_num_rows($res); $j++) {
          extract(pg_fetch_array($res));
          $grupo_itens->campos["defeito{$seq}"]->add_option($defeito, $descricao);
        }

        $grupo_itens->add_field("tbl_os", "servico", "servico{$seq}");
          $grupo_itens->campos["servico{$seq}"]->add_css_class("servico_itens", "input");
          $grupo_itens->campos["servico{$seq}"]->add_css_class("campos_itens", "div");
          $grupo_itens->campos["servico{$seq}"]->set_value($itens_pecas[$seq]["servico"]);

        $cond_acessorio = ($login_fabrica==20 and $tipo_atendimento==12) ? 'AND T.garantia_acessorio IS NOT TRUE' : '';
        $sql = "
          SELECT T.servico_realizado, COALESCE(TI.descricao, T.descricao) AS descricao
            FROM tbl_servico_realizado        T
            LEFT JOIN tbl_servico_realizado_idioma TI
            ON T.servico_realizado = TI.servico_realizado
             AND idioma  = UPPER('{$cook_idioma}')
           WHERE T.fabrica = $login_fabrica
             AND T.solucao IS NOT TRUE
             AND T.ativo IS TRUE
             $cond_acessorio
           ORDER BY descricao
        ";

        $res = pg_query($con, $sql);

        for ($j = 0; $j < pg_num_rows($res); $j++) {
          extract(pg_fetch_array($res));
          $grupo_itens->campos["servico{$seq}"]->add_option($servico_realizado, $descricao);
        }

        $grupo_itens->campos["defeito{$seq}"]->set_label("");
        $grupo_itens->campos["servico{$seq}"]->set_label("");
      }

      $grupo_itens->campos["peca{$seq}"]->set_label("");
      $grupo_itens->campos["qtde{$seq}"]->set_label("");


      if (strlen($itens_pecas[$seq]["pedido"]) > 0) {

        if ($login_fabrica != 20) {
          $grupo_itens->campos["peca{$seq}"]->set_read_only();
          $grupo_itens->campos["qtde{$seq}"]->set_read_only();
          $grupo_itens->campos["defeito{$seq}"]->set_read_only();
          $grupo_itens->campos["servico{$seq}"]->set_read_only();
        }
      }

      if ($seq == "__modelo__") {
        $grupo_itens->campos["peca{$seq}"]->add_css_class($seq, "div");
        $grupo_itens->campos["qtde{$seq}"]->add_css_class($seq, "div");
        $grupo_itens->add_element(new input_hidden("peca_id{$seq}", '', $itens_pecas[$seq]["peca_id"]));
        if ($login_fabrica != 20) {
          $grupo_itens->campos["defeito{$seq}"]->add_css_class($seq, "div");
          $grupo_itens->campos["servico{$seq}"]->add_css_class($seq, "div");
        }
                if (in_array($login_fabrica, array(85))) {
                    $grupo_itens->campos["peca_preco{$seq}"]->add_css_class($seq, "div");
                    $grupo_itens->campos["peca_ref{$seq}"]->add_css_class($seq, "div");
                }
      }
    }
  }

  #if ($login_fabrica == 20 && !empty($os) ) {
  if ($login_fabrica == 20) { //hd_chamado=2806621
    $grupo_fechamento = new grupo("fechamento_os", $campos_telecontrol[$login_fabrica], "Fechamento da OS");
    $grupo_fechamento->add_field('tbl_os', 'data_hora_fechamento');
    $grupo_fechamento->campos['data_hora_fechamento']->set_label(traduz("Data de Reparação Finalizada"));
  }

  $grupo_outras = new grupo("outras_os", $campos_telecontrol[$login_fabrica], 'Outras Informações');

  if ($login_fabrica == 87) {
    $grupo_outras->add_field("tbl_os", "qtde_horas");
    $grupo_outras->add_field("tbl_os", "qtde_km");
  }

  $grupo_outras->add_field("tbl_os", "obs");

    $grupo_outras->set_html_after("
        <div class='toolbar toolbar_acoes_os'>
          <input type='checkbox' name='imprimir_os' id='imprimir_os' class='imprimir_os' value='imprimir'><font  class='imprimir_os' size='1' face='Geneva, Arial, Helvetica, san-serif'>Imprimir OS</font>
          <input type='hidden' id='btn_acao' name='btn_acao' value=''>
          <input id='btn_gravar_os' name='btn_gravar_os' type='button' class='verifica_servidor' rel='sem_submit' value='".traduz('Gravar')."' onclick='gravar_os();'>
        </div>"
    );
  /*
  $grupo_acoes = new grupo("acoes_os", $campos_telecontrol[$login_fabrica], "Ações");
    $grupo_acoes->set_html_before("<div class='toolbar toolbar_acoes_os'><input type='hidden' id='btn_acao' name='btn_acao' value=''><input id='btn_gravar_os' name='btn_gravar_os' type='button' value='Gravar' onclick='gravar_os();'></div>");
  */

  if(is_array($msg_erro) > 0) {

      if (array_key_exists("tipo_atendimento|obrigatorio", $msg_erro) AND $login_fabrica == 20) { //2843341
      $msg_erro = traduz('o.campo.%.e.obrigatorio', null, null, array('Tipo Atendimento'));
      }else{
        $msg_erro = implode('<br>', $msg_erro);
      }
  }

//HD 753437
if ($login_fabrica == 87){
  $sql = "SELECT COUNT(DISTINCT(tbl_os.os)) AS qtde_os_soaf

      FROM tbl_os

      JOIN tbl_os_produto ON (tbl_os.os = tbl_os_produto.os)
      JOIN tbl_os_item    ON (tbl_os_produto.os_produto = tbl_os_item.os_produto)
      JOIN tbl_soaf       ON (tbl_os_item.soaf = tbl_soaf.soaf)

      WHERE tbl_os.fabrica       =  $login_fabrica
      AND tbl_os.posto           =  $login_posto
      AND tbl_os_item.soaf       IS NOT NULL
      AND tbl_soaf.data_abertura IS NULL
  ";
  $res = pg_query($con,$sql);

  $qtde_os_soaf = pg_result($res,0,'qtde_os_soaf');

  if ($qtde_os_soaf > 0){
  ?>
    <table align="center" width="700px" >

      <tr>
        <td align="center">
          <a href="os_soaf.php" target="_blank" style="font:bold 14px Arial;color: #339933;text-decoration: underline;cursor:pointer">
<?=traduz('ordens.de.servicos.com.soaf.regularize', $con)?>
          </a>
        </td>
      </tr>

      <tr>
        <td>&nbsp;</td>
      </tr>

    </table>
  <?
  }
}
if(strlen($msg_erro) > 0 AND $login_fabrica == 20){
?>
<script type="text/javascript">
  setTimeout(function(){
    oculta_campos();
    verifica_motivo_ordem();
  }, 100);
</script>
<?php
}
echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";

echo "<form id='frm_os' name='frm_os' method='post' enctype='multipart/form-data' >";

if($login_fabrica == 20 AND $_GET['msg'] == "success"){
?>
  <div class='sucesso' style='width:700px;'><?=traduz('os.gravada.com.sucesso')?></div>
  <div class="div_grupo">
    <label class="label_grupo label_dados_os"><?=traduz('Ações')?></label>
    <div style='text-align: center;'>
  <a href="os_cadastro_unico.php"><input type='button' value='<?=traduz(array('abrir', 'nova', 'os'))?>'></a>
  <a href="os_print.php?os=<?=$os?>"><input type='button' value='<?=traduz('imprimir')?>'></a>
      <!-- hd_chamado=2843341
        <a href="os_consulta_lite.php"><input type='button' value='Voltar para Consulta'></a>
        <a href="os_comprovante_servico_print.php?os=<?=$os?>"><input type='button' value='Comprovante'></a>
      -->
    </div>
  </div>
<?php
}

$grupo_os->draw();
$grupo_produto->draw();
$grupo_cliente->draw();
$grupo_km->draw();
$grupo_revenda->draw();
$grupo_analise->draw();
$grupo_itens->draw();
if (is_object($grupo_fechamento))
  $grupo_fechamento->draw();
$grupo_outras->draw();
//$grupo_acoes->draw();

if ($fabricaFileUploadOS && $anexaNotaFiscal) {
      
    $boxUploader = array(
        "div_id" => "div_anexos",
        "prepend" => $anexo_prepend,
        "context" => "os",
        "unique_id" => $tempUniqueId,
        "hash_temp" => $anexoNoHash,
        "reference_id" => $tempUniqueId
    );

    include "box_uploader.php";
}

echo "</form>";
if($login_fabrica == 20 and $foto_serie_produto) {
	$labelText  = traduz('label.anexo.serie.999', $con);
?>

    <div id="msgAnexoSerie" style="display:none;">
    <p style="font-size: 14px; text-align: center;">
      <?=$labelText?>
    </p>
    <p align="center" style='position:relative; left:45%;'>
      <button type='button' onclick="Shadowbox.close()">OK</button>
    </p>
  </div>
  <script type="text/javascript">

  /* HD-2808833
    $(function(){
      $("#DIVAnexo > label").addClass('obrigatorio');
    });
  */
  </script>
<?php
}
include_once "rodape.php";

