<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios='auditoria';
include 'autentica_admin.php';
include 'funcoes.php';
$programa_insert = $_SERVER['PHP_SELF'];


if($_POST['aprovar_reprovar'] == true){

  $checks     = $_POST['checks'];
  $qtde_os    = trim($_POST["qtde_os"]);
  $observacao = trim(utf8_encode($_POST["observacao"]));
  $acoes      = $_POST['acoes'];

  if(strlen($observacao) > 0){
    $observacao = $observacao;
  }else{
    $observacao = "";
  }

  $count = count($checks);

  if($count == 0){
    $msg = "erro|OS Selecione uma OS";
  }

  if(strlen($msg) == ""){
    foreach ($checks as $key => $value) {
      $xxos = $value;

      if (strlen($xxos) > 0) {

        $sql_posto = "SELECT tbl_posto_fabrica.contato_email as email, tbl_posto_fabrica.posto FROM tbl_os JOIN tbl_posto_fabrica USING(posto, fabrica) WHERE os = $xxos;";
        $res_posto = @pg_exec($con, $sql_posto);
        if (pg_num_rows($res_posto) > 0) {
          $posto           = trim(pg_result($res_posto, 0, 'posto'));
          $remetente_email = trim(pg_result($res_posto, 0, 'email'));
        } else {
          $msg_erro = 'Erro ao buscar dados do posto!';
        }

        $res_update = pg_exec($con, "BEGIN TRANSACTION");

        $sqlAud = "SELECT tbl_auditoria_os.auditoria_os
                     FROM tbl_auditoria_os
                    WHERE tbl_auditoria_os.os = $xxos
                      AND auditoria_status = 4 
                      AND liberada ISNULL 
                      AND cancelada ISNULL 
                      AND reprovada ISNULL 
                    ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
        $resAud = pg_query($con, $sqlAud);
        $aud_os = pg_fetch_result($resAud, 0, 'auditoria_os');

        if($acoes == ""){
          $msg = "erro|Selecione uma opção Aprovar ou Reprovar";
        }

        if($acoes == "aprovar"){
          $sql_update = "UPDATE tbl_auditoria_os SET liberada = current_timestamp, bloqueio_pedido = false, justificativa = '$observacao', admin = $login_admin WHERE os = $xxos AND auditoria_os = $aud_os";
          $res_update = pg_query($con, $sql_update);
          $msg_erro = pg_last_error($con);

          if(strlen($msg_erro) == ''){
            $msg = "ok|Aprovadas";
          }
        }

        if($acoes == "reprovar"){
          $sql_update = "UPDATE tbl_auditoria_os SET reprovada = current_timestamp, justificativa = '$observacao', admin = $login_admin WHERE os = $xxos AND auditoria_os = $aud_os";
          $res_update = pg_query($con, $sql_update);

          $sql_servico = "SELECT tbl_os_item.servico_realizado, tbl_os.os, tbl_os_item.os_item
                    FROM tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                    JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                  WHERE tbl_os.os = $xxos";
          $res_servico = pg_query($con, $sql_servico);
          $msg_erro = pg_last_error($con);
          if(pg_num_rows($res_servico) > 0){
            $os_item = pg_fetch_result($res_servico, 0, 'os_item');

            $sql_up = "UPDATE tbl_os_item SET servico_realizado = 10773 WHERE os_item = $os_item";
            $res_up = pg_query($con, $sql_up);
            $msg_erro .= pg_last_error($con);

            if(strlen($msg_erro) == ''){
              $msg_observacao = "OS $xxos Recusada da Auditoria <br><br> $observacao ";
                $sql = "INSERT INTO tbl_comunicado (
                          descricao              ,
                          mensagem               ,
                          tipo                   ,
                          fabrica                ,
                          obrigatorio_os_produto ,
                          obrigatorio_site       ,
                          posto                  ,
                          ativo
                        ) VALUES (
                          'OS Recusada da Auditoria',
                          '$msg_observacao',
                          'Pedido de Peças',
                          $login_fabrica,
                          'f' ,
                          't',
                          $posto,
                          't'
                    );";
                $res       = pg_query($con,$sql);
                $msg_erro .= pg_last_error($con);

              $msg = "ok|Reprovadas";
            }
          }else{
            $msg_erro .= "OS $xxos não tem Serviço Realizado";
            $msg = "erro|OS $xxos não tem Serviço Realizado";
          }
        }
      }

      if (strlen($msg_erro) > 0){
        $res = @pg_query ($con,'ROLLBACK TRANSACTION');
        echo $msg;
      }else {
        $res = @pg_query ($con,'COMMIT TRANSACTION');
        echo $msg;
      }

    }
  }
  exit;
}

// INTERAGIR //
if ($_POST["interagir"] == true) {
  $os = $_POST['os'];
  $posto = $_POST['posto'];
  $interacao = utf8_decode(trim($_POST["interacao"]));

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
             ({'$programa_insert'},{$os}, {$login_admin}, {$login_fabrica}, '{$interacao}')";
      $result = pg_query($con, $insert);

      if (strlen(pg_last_error()) > 0) {
        $retorno = array("erro" => utf8_encode("Erro ao interagir na OS"));
      } else {
        $retorno = array("ok" => true);

        $sql_email_posto = "SELECT tbl_posto_fabrica.contato_email, tbl_posto_fabrica.nome_fantasia
                            FROM tbl_posto_fabrica
                            WHERE tbl_posto_fabrica.posto = $posto
                            AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $res_email_posto = pg_query($con, $sql_email_posto);
        $email_posto = pg_fetch_result($res_email_posto, 0, 'contato_email');
        $nome_posto = pg_fetch_result($res_email_posto, 0, 'nome_fantasia');

        /* Email */
        include_once '../class/email/mailer/class.phpmailer.php';
        $mailer = new PHPMailer();
        $email_responsavel = $email_posto;
        $headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
        $headers .= "From: Suporte <helpdesk@telecontrol.com.br> \r\n";

        $assunto = "Existe uma interação na OS $os, por favor verificar.";
        $mensagem = "Olá {$nome_posto},";
        $mensagem .="<br>Existe uma interação feita pela Fábrica na OS $os, por favor verificar";
        if (!mail($email_responsavel, $assunto, $mensagem, $headers)) {
            $msg_erro .= "Erro ao enviar email para $email_responsavel";
        }
      }
    }
  }
  exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
  $data_inicial       = $_POST['data_inicial'];
  $data_final         = $_POST['data_final'];
  $produto_referencia = $_POST['produto_referencia'];
  $produto_descricao  = $_POST['produto_descricao'];
  $peca_referencia    = $_POST['peca_referencia'];
  $peca_descricao     = $_POST['peca_descricao'];
  $codigo_posto       = $_POST['codigo_posto'];
  $descricao_posto    = $_POST['descricao_posto'];
  $linha              = $_POST['linha'];
  $familia            = $_POST['familia'];
  $os                 = $_POST['os_pesquisa'];
  $status_os          = ($_POST['status_os']);


  if(empty($os)){
    //DATAS
    if (!strlen($data_inicial) or !strlen($data_final)) {
      $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
      $msg_erro["campos"][] = "data";
    } else {
      list($di, $mi, $yi) = explode("/", $data_inicial);
      list($df, $mf, $yf) = explode("/", $data_final);

      if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
        $msg_erro["msg"][]    = "Data Inválida";
        $msg_erro["campos"][] = "data";
      } else {
        $aux_data_inicial = "{$yi}-{$mi}-{$di}";
        $aux_data_final   = "{$yf}-{$mf}-{$df}";

        if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
          $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
          $msg_erro["campos"][] = "data";
        }

        $sqlX = "SELECT '$aux_data_inicial'::date + interval '3 months' > '$aux_data_final'";
        $resX = pg_query($con,$sqlX);
        $periodo_3meses = pg_fetch_result($resX,0,0);
        if($periodo_3meses == 'f'){
          $msg_erro["msg"][]    = "AS Datas devem ser no máximo 3 meses";
          $msg_erro["campos"][] = "data";
        }
      }
    }
  }

  //PRODUTO
  if (strlen($produto_referencia) > 0 or strlen($produto_descricao) > 0){
    $sql = "SELECT produto
        FROM tbl_produto
        WHERE fabrica_i = {$login_fabrica}
        AND (
              (UPPER(referencia) = UPPER('{$produto_referencia}'))
                OR
              (UPPER(descricao) = UPPER('{$produto_descricao}'))
            )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Produto não encontrado";
      $msg_erro["campos"][] = "produto";
    } else {
      $produto = pg_fetch_result($res, 0, "produto");
    }
  }

  //PEÇA
  if (strlen($peca_referencia) > 0 or strlen($peca_descricao) > 0){
    $sql = "SELECT peca
        FROM tbl_peca
        WHERE fabrica = {$login_fabrica}
        AND ((UPPER(referencia) = UPPER('{$peca_referencia}'))
              OR
              (UPPER(descricao) = UPPER('{$peca_descricao}'))
            )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Peça não encontrada";
      $msg_erro["campos"][] = "peca";
    } else {
      $peca = pg_fetch_result($res, 0, "peca");
    }
  }

  //POSTO
  if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
    $sql = "SELECT tbl_posto_fabrica.posto
        FROM tbl_posto
        JOIN tbl_posto_fabrica USING(posto)
        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
        AND (
          (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
        )";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Posto não encontrado";
      $msg_erro["campos"][] = "posto";
    } else {
      $posto = pg_fetch_result($res, 0, "posto");
    }
  }

  //LINHA
  if (strlen($linha) > 0) {
    $sql = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND linha = {$linha}";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Linha não encontrada";
      $msg_erro["campos"][] = "linha";
    }
  }

  //FAMILIA
  if (strlen($familia)) {
    $sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
    $res = pg_query($con ,$sql);

    if (!pg_num_rows($res)) {
      $msg_erro["msg"][]    = "Familia não encontrada";
      $msg_erro["campos"][] = "familia";
    }
  }

  if (!count($msg_erro["msg"])) {

    //CONDIÇÕES

      if(strlen($data_inicial) > 0 AND strlen($os) == ""){
        $cond_data = " AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";
      }
      if(strlen($produto_referencia) > 0 OR strlen($produto_descricao) > 0){
        $cond_produto = "AND tbl_produto.produto = $produto";
      }
      if(strlen($peca_referencia) > 0 OR strlen($peca_descricao) > 0){
        $cond_peca = "AND tbl_os_item.peca = $peca";
      }
      if(strlen($codigo_posto) > 0){
        $cond_posto = " AND tbl_os.posto = {$posto} ";
      }
      if(strlen($linha) > 0){
        $cond_linha = " AND tbl_produto.linha = {$linha} ";
      }
      if(strlen($familia) > 0) {
        $cond_familia = " AND tbl_produto.familia = {$familia} ";
      }
      if(strlen($os) > 0) {
        $cond_os = "AND tbl_os.os = $os";
      }
    //
    switch ($status_os) {
      case 'aprovacao':
          $cond_auditado = " AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.cancelada IS NULL AND tbl_auditoria_os.reprovada IS NULL";
          $cond_excluida   = 'AND tbl_os.excluida IS NOT TRUE';
          $cond_finalizada = 'AND tbl_os.finalizada IS NULL';
          $cond_extrato    = 'AND tbl_os_status.extrato IS NULL';
          $cond_fechamento = "AND tbl_os.data_fechamento IS NULL AND tbl_os.excluida IS NOT TRUE";
          #$cond_garantia   = 'AND tbl_os.troca_garantia IS NOT TRUE';

          $sqlDrop = "DROP TABLE IF EXISTS tmp_os_intervencao_hydra;";
          $resDrop = pg_query($con,$sqlDrop);

          $sql_intervencao = "SELECT tbl_auditoria_os.os
                                INTO TEMP tmp_os_intervencao_hydra
                                FROM tbl_auditoria_os
                                JOIN tbl_os ON tbl_auditoria_os.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
                               WHERE tbl_auditoria_os.auditoria_status = 4
                                 $cond_auditado
                                 $cond_excluida
                                 $cond_finalizada
                                 $cond_fechamento
                               ORDER BY tbl_auditoria_os.auditoria_os DESC";
          $res_intervencao = pg_query($con, $sql_intervencao);
        break;
      case 'aprovadas':

          $sqlDrop = "DROP TABLE IF EXISTS tmp_os_intervencao_hydra;";
          $resDrop = pg_query($con,$sqlDrop);

          $cond_auditado = " AND tbl_auditoria_os.liberada IS NOT NULL";
          $sql_intervencao = "SELECT tbl_auditoria_os.os
                                INTO TEMP tmp_os_intervencao_hydra
                                FROM tbl_auditoria_os
                               WHERE tbl_auditoria_os.auditoria_status = 4
                                 $cond_auditado
                               ORDER BY tbl_auditoria_os.auditoria_os DESC";
          $res_intervencao = pg_query($con, $sql_intervencao);
        break;

      case 'reprovadas':

            $sqlDrop = "DROP TABLE IF EXISTS reprovadas;";
            $resDrop = pg_query($con,$sqlDrop);

            $sql_drop = "DROP TABLE IF EXISTS tmp_os_intervencao_hydra;";
            $res_drop = pg_query($con,$sql_drop);

            $sql_reprovadas = "SELECT os,
                                (
                                  SELECT reprovada
                                    FROM tbl_auditoria_os
                                    WHERE tbl_auditoria_os.os = tbl_os.os
                                    ORDER BY auditoria_os
                                    DESC LIMIT 1
                                ) AS reprovada
                              INTO TEMP reprovadas
                              FROM tbl_os
                              JOIN tbl_auditoria_os using(os)
                              WHERE fabrica = $login_fabrica
                              AND reprovada notnull";
            $res_reprovadas = pg_query($con, $sql_reprovadas);

            $sql_audi = "SELECT os
                          INTO TEMP tmp_os_intervencao_hydra
                          FROM reprovadas
                          WHERE reprovadas IS NOT NULL";
            $res_audi = pg_query($con, $sql_audi);
        break;
    }

    $sql = "SELECT  tbl_os.os ,
                      TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
                      /*TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,*/
                      tbl_os.posto ,
                      tbl_posto.nome AS posto_nome ,
                      tbl_posto_fabrica.codigo_posto AS codigo_posto,
                      tbl_posto_fabrica.contato_email AS posto_email ,
                      tbl_produto.referencia AS produto_referencia ,
                      tbl_produto.descricao AS produto_descricao
                      INTO TEMP tmp_todos_dados
            FROM tbl_os
            JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
            JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
            JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_auditoria_os ON tbl_os.os = tbl_auditoria_os.os
            LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            LEFT JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            LEFT JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
            WHERE tbl_os.os IN (select os from tmp_os_intervencao_hydra)
            AND tbl_os.fabrica = $login_fabrica
            $cond_data
            $cond_produto
            $cond_peca
            $cond_posto
            $cond_linha
            $cond_familia
            $cond_os
            GROUP BY tbl_os.os,
            tbl_os.data_abertura,
            tbl_os.data_fechamento,
            tbl_os.posto,
            tbl_posto.nome,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto_fabrica.contato_email,
            tbl_produto.referencia,
            tbl_produto.descricao
            ORDER BY tbl_os.os
            ";
    $res = pg_query($con, $sql);

    $sql = "SELECT codigo_posto,
              posto_nome,
              count(os) AS qtde_os,
              posto
            FROM tmp_todos_dados
            GROUP BY codigo_posto, posto_nome, posto
          ";
    $resSubmit = pg_query($con, $sql);

  }
}
$layout_menu = 'auditoria';
$title = 'Auditoria de OS finalizadas';
include 'cabecalho_new.php';


$plugins = array(
  'autocomplete',
  'datepicker',
  'shadowbox',
  'mask',
  'dataTable',
  'price_format'
);

include('plugin_loader.php');

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

?>

<style>
.legenda {
  display: inline-block;
  width: 36px;
  height: 18px;
  vertical-align: middle;
  margin-right: 5px;
  border-radius: 3px;
}
</style>


<script type="text/javascript">
  var hora = new Date();
  var engana = hora.getTime();

  $(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("peca", "posto"));
    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
      $.lupa($(this));
    });
  });

  function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
  }

  function retorna_peca(retorno){
    $("#peca_referencia").val(retorno.referencia);
    $("#peca_descricao").val(retorno.descricao);
  }

  function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
  }

  var ok   = false;
  var cont = 0;
  function checkaTodos() {
    f = document.frm_pesquisa2;
    if (!ok) {
      for (i = 0; i < f.length; i++) {
        if (f.elements[i].type == "checkbox") {
          f.elements[i].checked = true;
          ok = true;
          if (document.getElementById('linha_'+cont)) {
            document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
          }
          cont++;
        }
      }
    } else {
      for (i = 0; i < f.length; i++) {
        if (f.elements[i].type == "checkbox") {
          f.elements[i].checked = false;
          ok = false;
          if (document.getElementById('linha_'+cont)) {
            document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
          }
          cont++;
        }
      }
    }
  }

  function check_os(posto) {
    $("#tab_"+posto).find("input[type='checkbox']").each(function(){
      if($(this).is(":checked")){
        $(this).prop("checked",false);
      }else{
        $(this).prop("checked",true);
      }
    });

  }

  function acaoOS(){
    var form = $("form[name=frm_pesquisa2]");
    var checks = [];
    var valor = '';
    var acoes = $("#acoes option:selected").val();
    var qtde_os = $(form).find("input[name=qtde_os]").val();
    var observacao = $(form).find("input[name=motivo]").val();

    $('input[name^=check_os_][type=checkbox]:checked').each(function(){
      valor = $(this).val();
      // var name = $(this).attr("name");
      // checks[name] = $(this).val();
      checks.push(valor);
    });
    var dataAjax = {
      "aprovar_reprovar" : true,
      "checks" : checks,
      "acoes" : acoes,
      "qtde_os" : qtde_os,
      "observacao" : observacao
    };
    if(acoes == "reprovar" && observacao == ''){
      alert("Preencha o motivo da Recusa");
    }else{
      if(valor == '' || valor == undefined){
        alert("Selecione uma OS");
      }else{
        $.ajax({
          url: "<?=$PHP_SELF?>",
          type:"POST",
          data: dataAjax,
          complete: function(retorno){
            var data = retorno.responseText;
            result = data.split("|");

            if(result[0] == "ok"){
              $('input[name^=check_os_][type=checkbox]:checked').each(function(){
                var trChecked = $(this).parent("td").parent("tr");
                $(trChecked).nextUntil("tr[id^=tr_]").remove();
                $(trChecked).remove();

                var linha_os = $('input[name^=check_os_]').length;

                if(linha_os == 0){
                  $('input[name^=tr_check_]').each(function(){
                    var tr_checked = $(this).parent("td").parent("tr");
                    $(tr_checked).nextUntil("tr[id^=linha_]").remove();
                    $(tr_checked).remove();
                  });
                }
              });
              var resultado = result[1];

              alert("Ação executada com Sucesso");

              $("input[name=motivo]").val('');
            }else{
              alert(result[1]);
            }
          }
        });
      }
    }
  }

  function expande(ordem) {
    var elemento = document.getElementById('completo_' + ordem);
    var display = elemento.style.display;
    if (display == "none") {
      elemento.style.display = "";
      $('#icone_expande_' + ordem ).removeClass('icon-plus').addClass('icon-minus');
    } else {
      elemento.style.display = "none";
      $('#icone_expande_' + ordem ).removeClass('icon-minus').addClass('icon-plus');
    }
  }


  //INTERAGIR
  $(document).on("click", "button[name=interagir]", function () {
    var os = $(this).attr("rel");
    if (os != undefined && os.length > 0) {
      Shadowbox.open({
        content: $("#DivInteragir").html().replace(/__NumeroOs__/, os),
        player: "html",
        height: 145,
        width: 400,
        options: {
          enableKeys: false
        }
      });
    }
  });
  $(document).on("click", "button[name=button_interagir]", function() {
    var obj = $(this).attr("rel");
    var dados = obj.split("|");
    var os = dados[0];
    var posto = dados[1];
    var interacao = $.trim($("#sb-container").find("textarea[name=text_interacao]").val());
    if (interacao.length == 0) {
      alert("Digite a interação");
    } else if (os != undefined && os.length > 0) {
      $.ajax({
        url: "relatorio_auditoria_status_os.php",
        type: "post",
        data: { interagir: true, interacao: interacao, os: os, posto: posto },
        beforeSend: function() {
          $("#sb-container").find("div.conteudo").hide();
          $("#sb-container").find("div.loading").show();
        },complete: function (data) {
          data = data.responseText;

          if (data.erro) {
            alert(data.erro);
          } else {

            $("#linha_"+os).find("td").css({ "background-color": "#FFDC4C" });
            //$("button[name=interagir][rel="+os+"]").parents("tr").find("td").css({ "background-color": "#FFDC4C" });
            Shadowbox.close();
          }
          $("#sb-container").find("div.loading").hide();
          $("#sb-container").find("div.conteudo").show();
        }
      });
    } else {
      alert("Erro ao interagir na OS");
    }
  });

  //
  $(document).on("click", "div[name=mostrar_pecas]", function() {
      var linha = $(this);
      linha.next("#m_peca").css({"width":"360px"});
      linha.next("#m_peca").show();
      linha.attr('name', 'esconder_pecas');
      linha.html('<span class="label label-info">Esconder peças</span>');
      $(".acoes").css({"width":"380px"});
  });

  $(document).on("click", "div[name=esconder_pecas]", function() {
      var linha = $(this);
      linha.next("#m_peca").hide();
      linha.attr('name', 'mostrar_pecas');
      linha.html('<span class="label label-info">Mostrar peças</span>');
      $(".acoes").css({"width":"280px"});
  });
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
    <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
  <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <!-- DATAS -->
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
    <!-- #### -->

    <!-- PRODUTOS -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span4'>
        <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='produto_referencia'>Ref. Produto</label>
          <div class='controls controls-row'>
            <div class='span7 input-append'>
              <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
              <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
              <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
            </div>
          </div>
        </div>
      </div>
      <div class='span4'>
        <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='produto_descricao'>Descrição Produto</label>
          <div class='controls controls-row'>
            <div class='span12 input-append'>
              <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
              <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
              <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
            </div>
          </div>
        </div>
      </div>
      <div class='span2'></div>
    </div>
    <!-- #### -->

    <!-- PEÇAS -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span4'>
        <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='peca_referencia'>Ref. Peças</label>
          <div class='controls controls-row'>
            <div class='span7 input-append'>
              <input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
              <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
              <input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
            </div>
          </div>
        </div>
      </div>
      <div class='span4'>
        <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='peca_descricao'>Descrição Peça</label>
          <div class='controls controls-row'>
            <div class='span12 input-append'>
              <input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
              <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
              <input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
            </div>
          </div>
        </div>
      </div>
      <div class='span2'></div>
    </div>
    <!-- #### -->

    <!-- POSTO -->
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
    <!-- #### -->

    <!-- LINHA / FAMILIA -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span4'>
        <div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='linha'>Linha</label>
          <div class='controls controls-row'>
            <div class='span4'>
              <select name="linha" id="linha">
                <option value=""></option>
                <?php
                $sql = "SELECT linha, nome
                    FROM tbl_linha
                    WHERE fabrica = $login_fabrica
                    AND ativo";
                $res = pg_query($con,$sql);

                foreach (pg_fetch_all($res) as $key) {
                  $selected_linha = ( isset($linha) and ($linha == $key['linha']) ) ? "SELECTED" : '' ;

                ?>
                  <option value="<?php echo $key['linha']?>" <?php echo $selected_linha ?> >

                    <?php echo $key['nome']?>

                  </option>
                <?php
                }
                ?>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class='span4'>
        <div class='control-group <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
          <label class='control-label' for='familia'>Familia</label>
          <div class='controls controls-row'>
            <div class='span4'>
              <select name="familia" id="familia">
                <option value=""></option>
                <?php

                  $sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica and ativo order by descricao";
                  $res = pg_query($con,$sql);
                  foreach (pg_fetch_all($res) as $key) {

                    $selected_familia = ( isset($familia) and ($familia == $key['familia']) ) ? "SELECTED" : '' ;

                  ?>
                    <option value="<?php echo $key['familia']?>" <?php echo $selected_familia ?> >
                      <?php echo $key['descricao']?>
                    </option>


                  <?php
                  }

                ?>
              </select>
            </div>
            <div class='span2'></div>
          </div>
        </div>
      </div>
    </div>
    <!-- #### -->

    <!-- OS -->
    <div class='row-fluid'>
      <div class="span2"></div>
      <div class="span4">
        <div class='control-group'>
          <label class="control-label" for="os_pesquisa">Número da OS</label>
          <div class="controls controls-row">
            <div class="span8">
              <input id="os_pesquisa" name="os_pesquisa" class='span12' type="text" value="<?=$os_pesquisa?>" />
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- #### -->

    <!-- FILTRO AUDITORIA -->
    <div class='row-fluid'>
      <div class='span2'></div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="aprovacao" <? if($status_os == 'aprovacao' OR $filtro_auditoria == ''){ ?> checked <?}?> >
            Em aprovação
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="aprovadas" <? if($status_os == 'aprovadas'){ ?> checked <?}?> >
            Aprovadas
          </label>
        </div>
      </div>
      <div class='span3'>
        <div class='control-group'>
          <label class='radio'>
            <input type="radio" name="status_os" value="reprovadas" <? if($status_os == 'reprovadas'){ ?> checked <?}?> >
            Reprovadas
          </label>
        </div>
      </div>
      <div class='span1'></div>
    </div>
    <!-- FIM FILTRO AUDITORIA -->
    <p><br/>
      <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
      <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>

<?php

if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
      echo "<br />";
      $count = pg_num_rows($resSubmit);
    ?>
  <FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>
    <table id="res_auditoria_status_os" class='table table-striped table-bordered table-fixed' >
      <thead>
        <tr class='titulo_coluna' >
          <?php if($status_os == "aprovacao"){ ?>
            <th><img border='0' src='imagens_admin/selecione_todas.gif' onclick='checkaTodos()' alt='Selecionar todos' style='cursor:pointer;' align='center'></th>
          <?php } ?>
          <th>Código</th>
          <th>Nome Posto</th>
          <th>Qtde OS</th>
        </tr>
      </thead>
      <tbody>
        <?php
        for ($i = 0; $i < $count; $i++) {
          $id_posto           = pg_fetch_result($resSubmit, $i, 'posto');
          $nome_posto         = pg_fetch_result($resSubmit, $i, 'posto_nome');
          $codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
          $qtde_os            = pg_fetch_result($resSubmit, $i, 'qtde_os');
          $cor = "#FFFFFF";

          $sql_dados = "SELECT os,
                              data_abertura,
                              produto_referencia,
                              produto_descricao
                            FROM tmp_todos_dados
                            WHERE posto = $id_posto
                      ";
          $res_dados = pg_query($con, $sql_dados);
          $body = "
            <tr id='linha_$id_posto'>";
            if($status_os == "aprovacao"){
              $body .="<td class='tac'><input type='checkbox' name='tr_check_$i' id='check_$i' value='' onClick='check_os($id_posto)'></td>";
            }
              $body .="
                  <td class='tal' style='background-color: $cor'>{$codigo_posto}</td>
                  <td class='tal' style='background-color: $cor'>{$nome_posto}</td>
                  <td class='tac' style='background-color: $cor'>{$qtde_os} &nbsp;&nbsp;&nbsp;<i style='cursor: pointer;' onClick='expande($i)' class='icon-plus' id='icone_expande_$i'></i></td>
                </tr>
              ";

              $body.= "<tr id='completo_$i' style='display: none;'>
                          <td colspan='4'>
                            <div class='row-fluid'>
                              <table id='tab_$id_posto' class='table table-striped table-bordered table-fixed'>
                                <thead>";
                                if($status_os == "aprovacao"){
                                  $body .="
                                    <tr>
                                      <td colspan='6'>
                                        <span class='legenda' style='background: #FFDC4C;' ></span>Fábrica interagiu<br />
                                        <span class='legenda' style='background: #A6D941;' ></span>Posto interagiu<br />
                                      </td>
                                    </tr>
                                  ";
                                }
                                $body .="
                                  <tr class='titulo_coluna'>";

                                  if($status_os == "aprovacao"){
                                    $body .="<th>Selecionar</th>";
                                  }

                                  $body .="<th>OS</th>
                                    <th>Abertura</th>
                                    <th>Produto</th>
                                    <th>Peças</th>
                                    <th>Ações</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  ";
                                    if(pg_num_rows($res_dados) > 0){
                                      $count2 = pg_num_rows($res_dados);
                                      for ($x=0; $x < $count2; $x++) {
                                        $os = pg_fetch_result($res_dados, $x, 'os');
                                        $data_abertura = pg_fetch_result($res_dados, $x, 'data_abertura');
                                        $produto_referencia = pg_fetch_result($res_dados, $x, 'produto_referencia');
                                        $produto_descricao = pg_fetch_result($res_dados, $x, 'produto_descricao');

                                        if($status_os == "aprovacao"){
                                          $ultima_interacao = ultima_interacao($os);
                                          switch ($ultima_interacao) {
                                            case "fabrica":
                                              $cor = "#FFDC4C";
                                              break;

                                            case "posto":
                                              $cor = '#A6D941';
                                              break;

                                            default:
                                              $cor = "#FFFFFF";
                                              break;
                                          }
                                        }else{
                                          $cor = "#FFFFFF";
                                        }

                                        $body .="<tr id='tr_$os' class='linha_os'>";
                                        if($status_os == "aprovacao"){
                                          $body .="<td class='tac' style='background-color: $cor' ><input type='checkbox' name='check_os_$x' id='check_os_$x' value='$os'></td>";
                                        }
                                        $body .="
                                          <td style='background-color: $cor' ><a href='os_press.php?os=$os' target='_blank' >{$os}</a></td>
                                          <td style='background-color: $cor' >{$data_abertura}</td>
                                          <td style='background-color: $cor' >{$produto_referencia} - {$produto_descricao}</td>";

                                          $sql_peca = "SELECT  tbl_os_item.os_item,
                                                        tbl_peca.referencia AS referencia,
                                                        tbl_peca.descricao AS descricao,
                                                        tbl_peca.peca AS peca
                                                      FROM tbl_os_produto
                                                        JOIN tbl_os_item USING (os_produto)
                                                        JOIN tbl_peca    USING (peca)
                                                      WHERE tbl_os_produto.os=$os ";
                                          $res_peca = pg_query($con, $sql_peca);
                                          $resultado = pg_num_rows($res_peca);
                                          $quantas_pecas = $resultado;
                                          if ($resultado > 0 ){
                                            $peca = trim(pg_fetch_result($res_peca, 0, 'peca'));
                                            $pecas = '';
                                            for($j=0;$j<$resultado;$j++){

                                              $peca_referencia = trim(pg_fetch_result($res_peca, $j, 'referencia'));
                                              $peca_descricao  = trim(pg_fetch_result($res_peca, $j, 'descricao'));

                                              $pecas[$peca_referencia]->ref = $peca_referencia;
                                              $pecas[$peca_referencia]->desc = $peca_descricao;
                                              $pecas[$peca_referencia]->id   = trim(pg_fetch_result($res_peca, $j, 'peca'));
                                              $pecas[$peca_referencia]->cont++;
                                            }
                                          }
                                          $body .="<td style='background-color: $cor'>";
                                          if(!empty($pecas)){
                                            $body .= "
                                              <div name='mostrar_pecas' rel='$os' style='width: 100%; text-align: center; cursor: pointer;'><span class='label label-info'> Mostrar peças</span></div>
                                              <table style='display:none;' style='width:385px;' id='m_peca' class='table table-bordered'>
                                                <thead>
                                                  <tr class='titulo_coluna'>
                                                    <th>Nome</th>
                                                    <th>Qtde</th>
                                                  </tr>
                                                </thead>
                                                <tbody>";

                                                foreach ($pecas as $peca_id => $peca) {
                                                  $body .="<tr>
                                                    <td class='peca'><a href='peca_cadastro.php?peca=$peca->id' target='_blank'>$peca->ref - $peca->desc</a></td>
                                                    <td class='peca'> $peca->cont</td>
                                                  </tr>";
                                                }
                                            $body .= "</table>";
                                          }
                                          $body .="
                                          </td>
                                          <td style='background-color: $cor' >
                                            <button type='button'  rel='$os|$id_posto' name='interagir' class='btn btn-small btn-primary'>Interagir</button>
                                          </td>
                                          </tr>
                                        ";
                                      }
                                    }
                                  $body .="
                                </tbody>
                              </table>
                            </div>
                          </td>
                        </tr>
                ";

          echo $body;
        }
        ?>
      </tbody>
        <?php if($status_os == "aprovacao"){ ?>

          <tfoot>
            <input id='qtde_os' type='hidden' name='qtde_os' value='<?=$i?>'>
            <tr class='titulo_coluna' style="padding: 0px;">
              <td colspan="7" style="line-height: 30px;">
                &nbsp; &nbsp;
                <img border='0' src='imagens/seta_checkbox.gif' style="margin-bottom: 10px; margin-left: 14px;"> &nbsp; Com Marcados: &nbsp;
                <select name='select_acao' id='acoes'size='1' class='frm' style='width: 95px; margin-bottom: 0px;'>
                  <option value=''></option>
                  <option value='aprovar'>Aprovar</option>
                  <option value='reprovar'>Reprovar</option>
                </select>
                &nbsp;&nbsp;
                Motivo:&nbsp; <input type='text' name='motivo' style="width:400px; margin-bottom: 0px;">
                &nbsp; &nbsp; &nbsp; &nbsp;
                <input type='button' class='btn' value='Gravar' style='cursor:pointer' onclick='acaoOS()'style='cursor:pointer;' border='0'>
              </td>
            </tr>
          </tfoot>
        <?php } ?>

      </table>

      <div id="DivInteragir" style="display: none;" >
        <div class="loading tac" style="display: none;" ><img src="imagens/loading_img.gif" /></div>
        <div class="conteudo" >
          <div class="titulo_tabela" >Interagir na OS</div>

          <div class="row-fluid">
            <div class="span12">
              <div class="controls controls-row">
                <textarea name="text_interacao" class="span12"></textarea>
              </div>
            </div>
          </div>

          <p><br/>
            <button type="button" name="button_interagir" class="btn btn-primary btn-block" rel="__NumeroOs__" >Interagir</button>
          </p><br/>
        </div>
      </div>

      </FORM>
    <?php
    }else{
      echo '
      <div class="container">
      <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
      </div>
      </div>';
    }
  }



include 'rodape.php';?>
