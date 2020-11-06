<?php
#echo "<center><h1>Sistema em Manutenção</h1><center>"; exit;
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($login_fabrica != 1) {
  $admin_privilegios="gerencia,call_center";
}

include 'autentica_admin.php';
include "../helpdesk.inc.php";
include '../helpdesk/mlg_funciones.php';

include_once '../class/tdocs.class.php';

$hd_chamado = ( isset($_GET['hd_chamado']) ) ? $_GET['hd_chamado'] : '' ;
$interno    = (isset($_GET['interno'])) ? $_GET['interno'] : '';

if($login_fabrica == 1){
  $sql_admin_sac = "SELECT fale_conosco FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
  $res_admin_sac = pg_query($con, $sql_admin_sac);

  if(pg_num_rows($res_admin_sac) > 0){
    $admin_sac = pg_fetch_result($res_admin_sac, 0, "fale_conosco");
    $admin_sac = ($admin_sac == "t") ? true : false;
  }
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

    $busca      = $_GET["busca"];
    $tipo_busca = $_GET["tipo_busca"];

    if (strlen($q) > 2) {

        if ($tipo_busca == 'posto') {

            $sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

            $sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND tbl_posto.nome ilike '%$q%' ";

            $res = pg_query($con,$sql);

            if (pg_num_rows ($res) > 0) {

                for ($i = 0; $i < pg_num_rows($res); $i++) {

                    $cnpj         = trim(pg_fetch_result($res, $i, 'cnpj'));
                    $nome         = trim(pg_fetch_result($res, $i, 'nome'));
                    $codigo_posto = trim(pg_fetch_result($res, $i, 'codigo_posto'));

                    echo "$cnpj|$nome|$codigo_posto";
                    echo "\n";

                }

            }

        }

        if ($tipo_busca == "produto") {

            $sql = "SELECT tbl_produto.produto,
                            tbl_produto.referencia,
                            tbl_produto.descricao
                    FROM tbl_produto
                    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                    WHERE tbl_linha.fabrica = $login_fabrica ";

            $sql .=  ($busca == "codigo") ? " AND tbl_produto.referencia like '%$q%' " : " AND UPPER(tbl_produto.descricao) ilike '%$q%' ";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i = 0; $i < pg_num_rows($res); $i++) {
                    $produto    = trim(pg_fetch_result($res,$i,'produto'));
                    $referencia = trim(pg_fetch_result($res,$i,'referencia'));
                    $descricao  = trim(pg_fetch_result($res,$i,'descricao'));
                    echo "$produto|$descricao|$referencia";
                    echo "\n";
                }
            }

        }

        if ($tipo_busca=="consumidor_cidade"){

            $sql = "SELECT      DISTINCT tbl_posto.cidade
                    FROM        tbl_posto_fabrica
                    JOIN tbl_posto using(posto)
                    WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
                    AND         tbl_posto.cidade ILIKE '%$q%'
                    ORDER BY    tbl_posto.cidade";

            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $consumidor_cidade        = trim(pg_fetch_result($res,$i,cidade));
                    echo "$consumidor_cidade";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

if($_POST["finalizar_chamado"] == "true"){

  $hd_chamado = $_POST["hd_chamado"];
  $observacao = $_POST["observacao"];

  $sql = " UPDATE tbl_hd_chamado
           SET
              status = 'Resolvido',
              data_resolvido = current_timestamp
          WHERE
              hd_chamado = $hd_chamado";
  $res = pg_query($con,$sql);

  $sql = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, status_item, interno) VALUES ({$hd_chamado}, '{$observacao}', {$login_admin}, 'Resolvido', 't')";
  $res = pg_query($con,$sql);

  echo "ok";

  exit;

}

if($_POST["busca_resposta_padrao"] == "true" && $login_fabrica == 3){

  $defeito_solucao = $_POST["defeito_solucao"];

  $sql_ds = "SELECT solucao_procedimento FROM tbl_defeito_constatado_solucao WHERE defeito_constatado_solucao = {$defeito_solucao} AND fabrica = {$login_fabrica}";
  $res_ds = pg_query($con, $sql_ds);

  $solucao_procedimento = pg_fetch_result($res_ds, 0, "solucao_procedimento");

  $result["status"] = (strlen($solucao_procedimento) > 0) ? true : false;
  $result["procedimento"] = utf8_encode($solucao_procedimento);

  echo json_encode($result);

  exit;

}

if ( $login_fabrica == 1 ) {

  $atendente_sap['atendente'] = $cook_admin;

    $sqladmin = " SELECT admin_sap FROM tbl_admin WHERE admin = {$atendente_sap['atendente']} " ;
    $resadmin = pg_query($con,$sqladmin);
    $atendente_sap['admin_sap'] = pg_fetch_result($resadmin,0,'admin_sap');
}

// pre_echo($_POST);
// exit;
if(isset($_GET['busca_atendente'])) { //Busca ajax HD 281195 Fá

    $categoria = $_GET['busca_atendente'];

    $dados = hdBuscarChamado($hd_chamado);

    $posto = $dados['posto'];

    $novo_atendente = $categorias[$categoria]['atendente'];

    if(!is_numeric($novo_atendente)){
        $novo_atendente = hdBuscarAtendentePorPosto($posto,$categoria);
    }

    $new_atendente = hdBuscarAdmin(array('admin = '.$novo_atendente));
    $new_atendente[$novo_atendente]['nome_completo'];

    unset($categoria);

    return;
}

//Britania
if ($_POST["busca_info_produto"] == "true" && $login_fabrica == 3) {
  if (strlen($_POST["os"]) > 0) {
    $os = $_POST["os"];

    $sql = "SELECT tbl_produto.produto, tbl_produto.descricao as produto_descricao, tbl_produto.referencia as produto_referencia, tbl_os.serie as produto_serie
        FROM tbl_os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os.sua_os = '{$os}'
        ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
      $retorno["produto_descricao"]  = utf8_encode(pg_fetch_result($res, 0, "produto_descricao"));
      $retorno["produto_referencia"] = utf8_encode(pg_fetch_result($res, 0, "produto_referencia"));
      $retorno["produto_serie"]      = utf8_encode(pg_fetch_result($res, 0, "produto_serie"));
      $retorno["produto"]          = utf8_encode(pg_fetch_result($res, 0, "produto"));
    } else {
      $retorno["erro"] = utf8_encode("OS não encontrada");
    }
  } else {
    $retorno["erro"] = utf8_encode("OS não encontrada");
  }

  echo json_encode($retorno);

  exit;
}


if($_POST["busca_defeito_produto"] == "true" && $login_fabrica == 3){

  $produto = $_POST["produto"];

  $sqlVerificaProdutosDesconsiderar = "
        SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar,
             tbl_defeito_constatado_solucao.defeito_constatado_solucao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
        JOIN tbl_produto ON tbl_produto.produto = {$produto}
        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
        AND tbl_familia.familia = tbl_produto.familia
        AND tbl_familia.fabrica = {$login_fabrica}
        WHERE
        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
        AND tbl_defeito_constatado_solucao.ativo IS TRUE
        AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
        AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL";
    $resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

    $arrDefeitosDesc = [];
    while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
      $arrDesconsiderar = json_decode($dadosDesc->produtos_desconsiderar);

      if (in_array($produto, $arrDesconsiderar)) {
        $arrDefeitosDesc[] = (int) $dadosDesc->defeito_constatado_solucao;
      }

    }

    if (count($arrDefeitosDesc) > 0) {
      $condDefDescImplode = "AND tbl_defeito_constatado_solucao.defeito_constatado_solucao NOT IN (".implode(",",$arrDefeitosDesc).")";
    }
    
    $sql = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao)
          tbl_defeito_constatado.defeito_constatado,
          tbl_defeito_constatado.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.produto = {$produto}
        UNION
        SELECT DISTINCT ON (UPPER(tbl_defeito_constatado.descricao))
          tbl_defeito_constatado.defeito_constatado,
          tbl_defeito_constatado.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
        JOIN tbl_produto ON tbl_produto.produto = {$produto}
        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
        AND tbl_familia.familia = tbl_produto.familia
        AND tbl_familia.fabrica = {$login_fabrica}
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
        {$condDefDescImplode}";
    $res = pg_query($con, $sql);

  if(pg_num_rows($res) > 0){

    $result = "<strong>Dúvidas / Defeitos</strong> <br />";
    $result .= "<select name='defeitos_produtos' class='frm' id='defeitos_produtos' onchange='busca_solucao_produto(this.value)'>";
    $result .= "<option value=''></option>";

    for($i = 0; $i < pg_num_rows($res); $i++){
      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
      $descricao = pg_fetch_result($res, $i, "descricao");
      $result .= "<option value='$defeito_constatado'>$descricao</option>";
    }

    $result .= "</select>";

  }

  echo $result;
  exit;
}

if($_POST["pega_id_produto"] == "true" && $login_fabrica == 3){
    $produto_ref       = $_POST['produto'];

    $sql_ref = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia  = '{$produto_ref}';";
    $res_ref = pg_query($con,$sql_ref);
    // echo $sql_ref;

    if (pg_num_rows($res_ref) > 0) {
        $produto_id = pg_fetch_result($res_ref, 0, produto);
        $retorno["ok"] = $produto_id;
    } else {
        $retorno["erro"] = utf8_encode("Não foi possível encontrar o Produto!");
    }
    echo json_encode($retorno);
    exit;
}

function verifica_defeito_solucao($defeito_resp = null, $solucao_resp = null, $hd_chamado){
    Global $con;
    if($defeito_resp == null && $solucao_resp == null){ return null; }
    $sql_verifica = "SELECT
                        tbl_dc_solucao_hd,
                        tbl_defeito_constatado_solucao.defeito_constatado,
                        tbl_defeito_constatado_solucao.defeito_constatado_solucao
                    FROM tbl_dc_solucao_hd
                    JOIN tbl_defeito_constatado_solucao USING(defeito_constatado_solucao)
                    WHERE hd_chamado = {$hd_chamado}";
    $res_verifica = pg_query($con, $sql_verifica);

    if(pg_num_rows($res_verifica) > 0){
        /* Carrega informações anteriores para inserir na interação */
        $defeito_constatado_antes = pg_fetch_result($res_verifica, 0, defeito_constatado);
        $defeito_constatado_solucao_antes = pg_fetch_result($res_verifica, 0, defeito_constatado_solucao);

        if ($defeito_constatado_antes !== $defeito_resp && $defeito_resp !== null) {
            $sql_defeito_constatado = "SELECT
                                        defeito_constatado,
                                        descricao
                                    FROM tbl_defeito_constatado
                                    WHERE defeito_constatado IN({$defeito_constatado_antes}, {$defeito_resp})";
            $res_defeito_constatado = pg_query($con, $sql_defeito_constatado);
            for ($i=0; $i < pg_num_rows($res_defeito_constatado); $i++) {
                if (pg_fetch_result($res_defeito_constatado, $i, defeito_constatado) == $defeito_resp) {
                    $defeito_constatado = pg_fetch_result($res_defeito_constatado, $i, descricao);
                }else{
                    $defeito_constatado_antes = pg_fetch_result($res_defeito_constatado, $i, descricao);
                }
            }
            $msg_defeito_alterado = "Defeito alterado de <u>{$defeito_constatado_antes}</u> para <u>{$defeito_constatado}</u>";
            return $msg_defeito_alterado;
        }

        if ($defeito_constatado_solucao_antes !== $solucao_resp && $solucao_resp !== null) {
            $sql_defeito_solucao_constatado = "SELECT
                                                tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                                                tbl_solucao.descricao
                                            FROM tbl_defeito_constatado_solucao
                                            JOIN tbl_solucao ON tbl_solucao.solucao =tbl_defeito_constatado_solucao.solucao
                                            WHERE tbl_defeito_constatado_solucao.defeito_constatado_solucao IN({$defeito_constatado_solucao_antes}, {$solucao_resp})";
            $res_defeito_solucao_constatado = pg_query($con, $sql_defeito_solucao_constatado);
            for ($i=0; $i < pg_num_rows($res_defeito_solucao_constatado); $i++) {
                if (pg_fetch_result($res_defeito_solucao_constatado, $i, defeito_constatado_solucao) == $solucao_resp) {
                    $defeito_constatado_solucao = pg_fetch_result($res_defeito_solucao_constatado, $i, descricao);
                }else{
                    $defeito_constatado_solucao_antes = pg_fetch_result($res_defeito_solucao_constatado, $i, descricao);
                }
            }
            $msg_solucao_alterada = "Solução alterada de <u>{$defeito_constatado_solucao_antes}</u> para <u>{$defeito_constatado_solucao}</u>";
            return $msg_solucao_alterada;
        }
    }
}

if($_POST["grava_procedimento_produto"] == "true" && $login_fabrica == 3){

  $procedimento_up  = utf8_decode( $_POST['procedimento'] );
  $defeito_up       = $_POST['defeito'];
  $solucao_up       = $_POST['solucao_id'];
  $produto_up       = $_POST['produto'];
  $hd_chamado_p     = $_POST['hd_chamado_p'];

  $msg_defeito = verifica_defeito_solucao($defeito_up,null, $hd_chamado_p);
  $msg_solucao = verifica_defeito_solucao(null, $solucao_up, $hd_chamado_p);

  $msg_log = (!empty($msg_defeito)) ? $msg_defeito : '';
  $msg_log = (!empty($msg_log)) ? $msg_log."<br />".$msg_solucao : $msg_solucao;

  pg_query($con,"BEGIN");

  $sql_update = "UPDATE tbl_defeito_constatado_solucao
                    SET solucao_procedimento = '{$procedimento_up}'
                    WHERE defeito_constatado_solucao = {$solucao_up}
                      AND fabrica = {$login_fabrica}
                      AND produto = {$produto_up}
                      AND defeito_constatado = {$defeito_up}";

  $res_update = pg_query($con,$sql_update);

  if (!empty($hd_chamado_p) AND $hd_chamado_p != null AND $hd_chamado_p != 'undefined') {
    $procedimento_up = "PROCEDIMENTO ATUALIZADO <br /> ".$procedimento_up;
    $sql_up = "INSERT INTO tbl_hd_chamado_item (
              hd_chamado,
              comentario,
              admin
              ) VALUES (
              {$hd_chamado_p},
              '{$procedimento_up}',
              {$login_admin} )";
    $res_up = pg_query($con,$sql_up);
    //echo $sql_up; pg_query($con,"ROLLBACK");exit;

    // Cadastra o Defeito e Solução no HD
    $tem_defeitos = false;
    if(strlen(trim($defeito_up)) > 0 && strlen(trim($solucao_up)) > 0){
      $tem_defeitos = TRUE;

      $sql_verifica = "SELECT tbl_dc_solucao_hd FROM tbl_dc_solucao_hd WHERE hd_chamado = {$hd_chamado_p}";
      $res_verifica = pg_query($con, $sql_verifica);

      if(pg_num_rows($res_verifica) > 0){
        $update_ds = "UPDATE tbl_dc_solucao_hd SET defeito_constatado_solucao = {$solucao_up} WHERE hd_chamado = {$hd_chamado_p}";
        $update_ds = pg_query($con, $update_ds);
      }else{
        $sql_da = "SELECT data FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado_p}";
        $res_da = pg_query($con, $sql_da);

        $data_abertura = pg_fetch_result($res_da, 0, "data");
        list($data, $hora) = explode(" ", $data_abertura);
        $data_abertura = $data;

        $sql_defeito_solucao = "INSERT INTO tbl_dc_solucao_hd (
                                                  fabrica,
                                                  defeito_constatado_solucao,
                                                  hd_chamado,
                                                  data_abertura
                                                  ) VALUES (
                                                  $login_fabrica,
                                                  $solucao_up,
                                                  $hd_chamado_p,
                                                  '$data_abertura')";
        $res_defeito_solucao = pg_query($con, $sql_defeito_solucao);
      }
    }
  }

  //$erro_up = pg_last_error($con);
  if(strlen(pg_last_error($con)) > 0) {
    pg_query($con,"ROLLBACK");
    $retorno["erro"] = utf8_encode("Não foi possível atualizar o procedimento!");
  }else{
    $sqlStatus = " UPDATE tbl_hd_chamado set status = 'Ag. Posto' WHERE hd_chamado = $hd_chamado_p";
    $resStatus = pg_query($con,$sqlStatus);

    if (!empty($msg_log)) {
        $hd_chamado_item = hdCadastrarResposta($hd_chamado_p, $msg_log, 1, '', $login_admin, null,'');
    }

    pg_query($con,"COMMIT");
    $retorno["ok"] = utf8_encode("Procedimento atualizado com sucesso!");
    $retorno["procedimento"] =  "<strong>Procedimento:</strong> <br /><textarea>{$procedimento_up}</textarea>";

  }

  echo json_encode($retorno);
  exit;
}

if($_POST["busca_procedimento_produto"] == "true" && $login_fabrica == 3){

  $defeito_proced = $_POST['defeito'];
  $solucao_proced = $_POST['solucao_id'];
  $produto_proced = $_POST['produto'];

  $sql_procedimento = "SELECT
                          tbl_produto.referencia AS ref_produto,
                          tbl_produto.descricao AS desc_produto,
                          tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                          tbl_defeito_constatado_solucao.defeito_constatado_solucao As dc_solucao
                        FROM tbl_defeito_constatado_solucao
                          JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                          JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                          JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto
                        WHERE
                          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_proced}
                        AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$solucao_proced}
                        AND tbl_defeito_constatado_solucao.produto = {$produto_proced}; ";

  $res_procedimento = pg_query($con,$sql_procedimento);

  $procedimento_solucao = pg_fetch_result($res_procedimento, 0, procedimento);
  $dc_solucao           = pg_fetch_result($res_procedimento, 0, "dc_solucao");

  if (count($procedimento_solucao) == 0) {

    $procedimento_solucao = "Solução sem procedimento cadastrado.";

  }elseif (!empty($dc_solucao)) {
            include_once S3CLASS;
            $s3 = new AmazonTC("procedimento", $login_fabrica);
            $anexos = $s3->getObjectList("{$login_fabrica}_{$dc_solucao}", false, '2016', '04');

            if (count($anexos) > 0) {
              $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
              if ($ext == "pdf") {
                $anexo_imagem = "imagens/pdf_icone.png";
              } else if (in_array($ext, array("doc", "docx"))) {
                $anexo_imagem = "imagens/docx_icone.png";
              } else {
                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, '2016', '04');
              }

              $anexo_link = $s3->getLink(basename($anexos[0]), false, '2016', '04');
              $anexo = basename($anexos[0]);
              $anexo_inf= "<br />
              <strong>Anexo</strong>
              <br />
              <td class='dados' colspan='2'>
                <div id='div_anexo' class='tac' style='display: inline-block; margin: 0px 5px 0px 5px;'>
                  <a href='$anexo_link' target='_blank'>
                    <img src='$anexo_imagem' class='anexo_thumb' style='width: 100px; height: 90px;' />
                  </a>
                </div>";
            }else{
              $anexo_inf= "";
            }
  }

  $result = "<strong>Procedimentos</strong> <br />";
  $result .= "<textarea id='solucao_procedimento_prod' rows='4' cols='50'>{$procedimento_solucao}</textarea>{$anexo_inf}";

  echo $result;
  exit;
}

if($_POST["busca_solucao_produto"] == "true" && $login_fabrica == 3){

  $produto = $_POST["produto"];
  $defeito = $_POST["defeito"];

  $sql = "SELECT DISTINCT
          tbl_defeito_constatado_solucao.defeito_constatado_solucao,
          tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
          tbl_solucao.solucao,
          tbl_solucao.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.produto = {$produto}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}
        UNION
        SELECT DISTINCT
          tbl_defeito_constatado_solucao.defeito_constatado_solucao,
          tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
          tbl_solucao.solucao,
          tbl_solucao.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
        JOIN tbl_produto ON tbl_produto.produto = {$produto}
        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
        AND tbl_familia.familia = tbl_produto.familia
        AND tbl_familia.fabrica = {$login_fabrica}
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
    $res = pg_query($con, $sql);

  if(pg_num_rows($res) > 0){

    $sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
                  FROM tbl_dc_solucao_hd
                  JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                  JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                  WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                  AND tbl_defeito_constatado_solucao.produto = {$produto}
                  AND tbl_hd_chamado.resolvido is not null
                  AND tbl_defeito_constatado_solucao.ativo IS TRUE
                  AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
    $res_total_solucoes = pg_query($con, $sql_total_solucoes);

    $total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

    $result = "<strong>Soluções - Índices de Soluções</strong> <br />";
    $result .= "<select name='solucoes_produtos' class='frm' id='solucoes_produtos' onchange='busca_resposta_padrao(this.value); busca_procedimento_produto(this.value, $defeito)'>";
    $result .= "<option value=''></option>";

    for($i = 0; $i < pg_num_rows($res); $i++){
      $defeito_constatado_solucao = pg_fetch_result($res, $i, "defeito_constatado_solucao");
      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
      $solucao = pg_fetch_result($res, $i, "solucao");
      $descricao = pg_fetch_result($res, $i, "descricao");

      $sqlVerificaProdutosDesconsiderar = "
          SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar
          FROM tbl_defeito_constatado_solucao
          JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
          JOIN tbl_produto ON tbl_produto.produto = {$produto}
          JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
          AND tbl_familia.familia = tbl_produto.familia
          AND tbl_familia.fabrica = {$login_fabrica}
          WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
          AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL
          AND tbl_defeito_constatado_solucao.solucao = {$solucao}";
      $resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

      $arrProdutosDesc = [];
      while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
        $arrDesconsiderar = json_decode(pg_fetch_result($resVerificaProdutosDesconsiderar, 0, 'produtos_desconsiderar'));
        foreach ($arrDesconsiderar as $produtoId) {
          $arrProdutosDesc[] = (int) $produtoId;
        }
      }

      if (in_array($produto, $arrProdutosDesc)) {
        continue;
      } 

      /* Estatística */
      $sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                FROM tbl_dc_solucao_hd
                JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                WHERE tbl_defeito_constatado_solucao.solucao = {$solucao}
                AND tbl_defeito_constatado_solucao.produto = {$produto}
                AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}
                AND tbl_hd_chamado.resolvido is not null
                AND tbl_defeito_constatado_solucao.ativo IS TRUE
                AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
      $res_estatistica = pg_query($con, $sql_estatistica);
      //echo $sql_estatistica;

      $total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

      if($total_ds > 0){

        $total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);

      }else{
        $total_porc = 0;
      }

      /* Fim - Estatística */

      $descricao = $descricao." - ".$total_porc."%";

      $result .= "<option value='$defeito_constatado_solucao'>$descricao</option>";
    }

    $result .= "</select>";

  }

  echo $result;
  exit;

}

if($_POST["busca_defeito_produto_resp"] == "true" && $login_fabrica == 3){

  $produto = $_POST["produto"];

  $sqlVerificaProdutosDesconsiderar = "
      SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar
      FROM tbl_defeito_constatado_solucao
      JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
      JOIN tbl_produto ON tbl_produto.produto = {$produto}
      JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
      AND tbl_familia.familia = tbl_produto.familia
      AND tbl_familia.fabrica = {$login_fabrica}
      WHERE
      tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
      AND tbl_defeito_constatado_solucao.ativo IS TRUE
      AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
      AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL";
  $resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

  $arrProdutosDesc = [];
  while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
    $arrDesconsiderar = json_decode($dadosDesc->produtos_desconsiderar);
    foreach ($arrDesconsiderar as $produtoId) {
      $arrProdutosDesc[] = (int) $produtoId;
    }
  }

  if (count($arrProdutosDesc) > 0) {
    $condProdDescImplode = "AND tbl_produto.produto NOT IN (".implode(",",$arrProdutosDesc).")";
  }
  
  $sql = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao)
        tbl_defeito_constatado.defeito_constatado,
        tbl_defeito_constatado.descricao
      FROM tbl_defeito_constatado_solucao
      JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
      WHERE
        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
        AND tbl_defeito_constatado_solucao.ativo IS TRUE
        AND tbl_defeito_constatado_solucao.produto = {$produto}
      UNION
      SELECT DISTINCT ON (UPPER(tbl_defeito_constatado.descricao))
        tbl_defeito_constatado.defeito_constatado,
        tbl_defeito_constatado.descricao
      FROM tbl_defeito_constatado_solucao
      JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
      JOIN tbl_produto ON tbl_produto.produto = {$produto}
      JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
      AND tbl_familia.familia = tbl_produto.familia
      AND tbl_familia.fabrica = {$login_fabrica}
      WHERE
        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
        AND tbl_defeito_constatado_solucao.ativo IS TRUE
      {$condProdDescImplode}";
  $res = pg_query($con, $sql);

  if(pg_num_rows($res) > 0){

    $result = "";
    $result .= "<select name='defeito' id='defeito' onchange='busca_solucao_produto_resp(this.value)'>";
    $result .= "<option value=''></option>";

    for($i = 0; $i < pg_num_rows($res); $i++){
      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
      $descricao = pg_fetch_result($res, $i, "descricao");
      $result .= "<option value='$defeito_constatado'>$descricao</option>";
    }

    $result .= "</select>";
    $result .= "<br><a href='javascript:busca_defeitos_produto_resp();' >Atualizar</a>";

  }

  echo $result;
  exit;
}

if($_POST["busca_solucao_produto_resp"] == "true" && $login_fabrica == 3){

  $produto = $_POST["produto"];
  $defeito = $_POST["defeito"];

  $sql = "SELECT DISTINCT
          tbl_defeito_constatado_solucao.defeito_constatado_solucao,
          tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
          tbl_solucao.solucao,
          tbl_solucao.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.produto = {$produto}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}
        UNION
        SELECT DISTINCT
          tbl_defeito_constatado_solucao.defeito_constatado_solucao,
          tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
          tbl_solucao.solucao,
          tbl_solucao.descricao
        FROM tbl_defeito_constatado_solucao
        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
        JOIN tbl_produto ON tbl_produto.produto = {$produto}
        JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
        AND tbl_familia.familia = tbl_produto.familia
        AND tbl_familia.fabrica = {$login_fabrica}
        WHERE
          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
          AND tbl_defeito_constatado_solucao.ativo IS TRUE
          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
  $res = pg_query($con, $sql);

  if(pg_num_rows($res) > 0){

    $defeito_constatado_solucao = pg_fetch_result($res, 0, 'defeito_constatado_solucao');

    $sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
                  FROM tbl_dc_solucao_hd
                  JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                  JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                  WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                  AND tbl_defeito_constatado_solucao.produto = {$produto}
                  AND tbl_hd_chamado.resolvido is not null
                  AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito}";
    $res_total_solucoes = pg_query($con, $sql_total_solucoes);

    $total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

    $result = "";
    $result .= "<select name='solucao' id='solucao' onchange='busca_resposta_padrao_resp(this.value); busca_procedimento_produto_resp(this.value, $defeito_constatado_solucao)'>";
    $result .= "<option value=''></option>";

    for($i = 0; $i < pg_num_rows($res); $i++){
      $defeito_constatado_solucao = pg_fetch_result($res, $i, "defeito_constatado_solucao");
      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
      $solucao = pg_fetch_result($res, $i, "solucao");
      $descricao = pg_fetch_result($res, $i, "descricao");

      /* Estatística */
      $sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                FROM tbl_dc_solucao_hd
                JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                WHERE tbl_defeito_constatado_solucao.solucao = {$solucao}
                AND tbl_defeito_constatado_solucao.produto = {$produto}
                AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}
                AND tbl_hd_chamado.resolvido is not null
                AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
      $res_estatistica = pg_query($con, $sql_estatistica);
      //echo $sql_estatistica;

      $total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

      if($total_ds > 0){

        $total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);

      }else{
        $total_porc = 0;
      }

      /* Fim - Estatística */

      $descricao = $descricao." - ".$total_porc."%";

      $result .= "<option value='$defeito_constatado_solucao'>$descricao</option>";
    }

    $result .= "</select>";
    $result .= "<br><a href='javascript:busca_solucao_produto_resp();' >Atualizar</a>";

  }

  echo $result;
  exit;
}

if($_POST["busca_procedimento_produto_resp"] == "true" && $login_fabrica == 3){

  $defeito_proced = $_POST['defeito_solucao'];

  $sql_procedimento = "SELECT tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento
                        FROM tbl_defeito_constatado_solucao
                          JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                          JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                        WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica} 
                        AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$defeito_proced}";

  $res_procedimento = pg_query($con,$sql_procedimento);

  $procedimento_solucao = pg_fetch_result($res_procedimento, 0, 'procedimento');

  if (empty($procedimento_solucao)) {

    $procedimento_solucao = "Solução sem procedimento cadastrado.";

  }

  $result = "<strong>Procedimentos</strong> <br />";
  $result .= "<textarea id='solucao_procedimento_resp' rows='4' cols='50'>{$procedimento_solucao}</textarea>";

  echo $result;
  exit;
}
//Fim Britania

if ( $login_fabrica == 1 ) {

  $atendente_sap['atendente'] = $cook_admin;

  $sqladmin = " SELECT admin_sap FROM tbl_admin WHERE admin = {$atendente_sap['atendente']} " ;
  $resadmin = pg_query($con,$sqladmin);
  $atendente_sap['admin_sap'] = pg_fetch_result($resadmin,0,'admin_sap');

  if(!empty($hd_chamado)){
   $sql = " SELECT admin_sap FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica}";
   $res = pg_query($con,$sql);
   $admin_sap = pg_fetch_result($res,0,'admin_sap');

   $sql = "SELECT tbl_admin.admin_sap FROM tbl_hd_chamado JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin AND tbl_hd_chamado.hd_chamado = {$hd_chamado}";
   $res = pg_query($con,$sql);
   $admin_hd_sap = pg_fetch_result($res,0,'admin_sap');

   $libera_interacao = false;

    if($admin_sap == "t" || ($admin_hd_sap != "t" && $admin_sap != "t")){
      $libera_interacao = true;
    }
  }else{
    $libera_interacao = true;
  }
}else{
    $libera_interacao = true;
}

// pre_echo($_POST);
// exit;
if(isset($_GET['busca_atendente'])) { //Busca ajax HD 281195 Fá

    $categoria = $_GET['busca_atendente'];

    $dados = hdBuscarChamado($hd_chamado);

    $posto = $dados['posto'];

    $novo_atendente = $categorias[$categoria]['atendente'];

    if(!is_numeric($novo_atendente)){
        $novo_atendente = hdBuscarAtendentePorPosto($posto,$categoria);
    }

    $new_atendente = hdBuscarAdmin(array('admin = '.$novo_atendente));
    $new_atendente[$novo_atendente]['nome_completo'];

    unset($categoria);

    return;
}
if ($login_fabrica == 3) {
    if (!empty($hd_chamado_item)) {
        $tempUniqueId = $hd_chamado_item;
        $anexoNoHash = null;
    } else if (strlen($_POST["anexo_chave"]) > 0) {
        $tempUniqueId = $_POST["anexo_chave"];
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

if (count($_POST) > 0) {
  if($_POST['btnEnviar']=='Enviar' OR $_POST['btnEnviar']=='Enviar Chamado'){
    
    $status_interacao = check_post_field('status_interacao');
    $chamado_interno  = check_post_field('chamado_interno');
    $xchamado_interno = (strlen($chamado_interno)>0) ? true : false;
    $manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';
    $hd_chamado = check_post_field("hd_chamado");
    $posto      = check_post_field('posto');
    $referencia = check_post_field("referencia");
    $os         = (empty(check_post_field("os"))) ? check_post_field("os2") : check_post_field("os");
    $pedido     = check_post_field("pedido");
    $categoria  = check_post_field("categoria");
    $garantia   = check_post_field("garantia");
    $transferir = check_post_field("transferir");
    $transferir_para = check_post_field("transferir_para");
    $tipo_resposta = check_post_field("tipo_resposta");
    $pendente_acompanhamento = check_post_field("pendente_acompanhamento");
    $encerrar_acompanhamento = check_post_field("encerrar_acompanhamento");
    $usuario_sac = check_post_field("usuario_sac");
    
    if($login_fabrica == 3){
      $defeitos_produtos = (isset($_POST["defeitos_produtos"])) ? $_POST["defeitos_produtos"] : $_POST["defeito"];
      $defeitos_produtos = (isset($_POST["defeitos_produtos2"])) ? $_POST["defeitos_produtos2"] : $defeitos_produtos;
      $solucoes_produtos = (isset($_POST["solucoes_produtos"])) ? $_POST["solucoes_produtos"] : $_POST["solucao"];
      $solucoes_produtos = (isset($_POST["solucoes_produtos2"])) ? $_POST["solucoes_produtos2"] : $solucoes_produtos;
      $produto_hidden =  (isset($_POST["produto_hidden2"])) ? $_POST["produto_hidden2"] : $_POST["produto_hidden"];
      $utilizar_resposta = $_POST["utilizar_resposta"];

        if(strlen($produto_hidden) > 0){
          $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto_hidden} AND fabrica_i = {$login_fabrica}";
          $res_produto = pg_query($con, $sql_produto);

          if(pg_num_rows($res_produto) > 0){
            $referencia = pg_fetch_result($res_produto, 0, "referencia");
            $descricao = pg_fetch_result($res_produto, 0, "descricao");
         }
            $os = $_POST["os2"];
        }
    }

    if( in_array($login_fabrica, array(11,42,172)) ) {
      $cancelar_chamado = check_post_field("cancelar_chamado");

      if ($cancelar_chamado == "cancelar") {
          $resposta = check_post_field("resposta");
          $resposta = strip_tags(html_entity_decode($resposta),$manterHtml);

          if (!empty($hd_chamado)) {
              $status  = "'Cancelado'";
              $resposta= "Chamado cancelado pela Fábrica. <br />$resposta";

              if (!is_bool(hdCadastrarResposta($hd_chamado, $resposta, false, $status, null, $login_posto))) {
                  $msg_ok[] = 'Chamado cancelado.';
              }
              $sql = " UPDATE tbl_hd_chamado
                       SET
                          status = $status,
                          data_resolvido = current_timestamp
                      WHERE
                          hd_chamado = $hd_chamado";
              $res = pg_query($con,$sql);
              $cancelado = true;

              echo "<script> window.location = '".$_SERVER["PHP_SELF"]."?hd_chamado=$hd_chamado'; </script>";
              exit;
          }
      }
    }

    if(strlen(trim($_POST["resposta"])) > 0){

      $resposta = $_POST["resposta"];

      if(strstr($resposta, "<img ") == true){

        $msg_erro[] = '<p> Não é permitido a inserção de imagens na respostas! </p>';

      }

    }

    if($encerrar_acompanhamento and $tipo_resposta <> 'Resp.Conclusiva'){
      $tipo_resposta = $encerrar_acompanhamento;
    }

    if($hd_chamado) {

      $sql_valida = "SELECT hd_chamado
                        FROM tbl_hd_chamado
                        WHERE fabrica = {$login_fabrica}
                          AND fabrica_responsavel = {$login_fabrica}
                          AND posto = {$posto}
                          AND hd_chamado = {$hd_chamado}
                          AND titulo = 'Help-Desk Posto'";
      $res_valida = pg_query($con,$sql_valida);

      if (pg_num_rows($res_valida) == 0) {
        $msg_erro[] = "<p>Interação inválida para o Chamado: {$hd_chamado} !</p>";
      }


        if (($login_fabrica == 3 && empty($defeitos_produtos) && empty($solucoes_produtos)) || !in_array($login_fabrica, array(3))) {
          if (is_null($resposta = check_post_field("resposta"))) {
            $msg_erro[] = '<p>Por favor, digite o texto !</p>';
          } else if ( strlen($resposta) <= 15 ) {
            $msg_erro[] = '<p>Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior !</p>';
          } /* else if(strlen($_POST["resposta"]) > 1000){
              $msg_erro[] = "Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser menor que 1000 caracteres!<br>";
          } */
        }

      if(!count($msg_erro)) {
        #$res = @pg_query($con,'BEGIN');
        if(!is_resource($res)){
          $msg_erro[] = "<p>Não foi possível iniciar a transação</p>";
        }
      }

      $sql = "SELECT atendente,login
                      FROM tbl_hd_chamado
                      LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
                      WHERE fabrica_responsavel= $login_fabrica
                        AND hd_chamado = $hd_chamado";
          $res = pg_query($con, $sql);
          if(pg_num_rows($res) > 0) {
            $ultimo_atendente       = pg_fetch_result($res,0,'atendente');
            $ultimo_atendente_login = pg_fetch_result($res,0,'login');
      }else{
        $msg_erro[] = "Atendimento não pertence a fábrica";
      }


      if(!count($msg_erro)) {
        $multi_admin = $_POST['multi_admin'];
        if(count($multi_admin) > 0){
          include_once '../class/email/mailer/class.phpmailer.php';
          if ($login_fabrica == 1) {
            include_once '../class/communicator.class.php';
          }

          $mailer = new PHPMailer(); //Class para envio de email com autenticação no servidor

          $sql = "SELECT login,email FROM tbl_admin WHERE admin = $ultimo_atendente";
          $res = pg_query($con,$sql);
          $nome_ultimo_atendente  = pg_fetch_result($res,0,login);
          $email_ultimo_atendente = pg_fetch_result($res,0,email);

          if(strlen($email_ultimo_atendente) >0){
            for($j =0;$j<count($multi_admin);$j++) {
                $sqlm="SELECT email FROM tbl_admin
                        WHERE admin=".$multi_admin[$j];
                $resm = pg_query($con,$sqlm);
                $email_atendente .=($j == 0) ? pg_fetch_result($resm,0,email) : ",".pg_fetch_result($resm,0,email) ;

                if(pg_num_rows($resm) == 1){
                  $envio_email[] = pg_fetch_result($resm,0,'email');
                  if ($login_fabrica == 1) {
                    $email_admin = pg_fetch_result($resm,0,'email'); 
                  } else {
                    $mailer->AddAddress(pg_fetch_result($resm,0,'email'));
                  }
                }
            }

            if(count($envio_email)){
                $assunto= "Telecontrol - Chamado Help-Desk $hd_chamado Transferido";

                $corpo  = "<P style='text-align:left;font-weight:bold'>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM POR E-MAIL. A RESPOSTA DEVERÁ SER INSERIDA NO LINK ABAIXO, DENTRO DO PRÓPRIO CHAMADO, QUE SERÁ ENVIADO SOMENTE PARA O(S) ENVOLVIDO(S) NO CHAMADO INTERNO ****.</P>
                    <P align=left>Prezado,</P>
                    <P align=justify>
                    O chamado $hd_chamado foi transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para você
                    </P>";

                $corpo .= "<p>Segue abaixo link para acesso e resposta do chamado interno:</p><p align=justify><a href='http://posvenda.telecontrol.com.br/assist/admin/helpdesk_cadastrar.php?hd_chamado=$hd_chamado&interno=sim'>Clique aqui para acessar o chamado</a>";

                if ($login_fabrica == 1) {
                  $mailTc = new TcComm($externalId);
                  $res = $mailTc->sendMail(
                      $email_admin,
                      utf8_encode($assunto),
                      utf8_encode($corpo),
                      'noreply@telecontrol.com.br'
                  );
                   $msg_ok[] = "<br>Foi enviado um email para: ".implode(", ", $envio_email)."<br>";
                } else {
                //$mailer->IsSMTP();
                $mailer->IsHTML();
                $mailer->Subject = $assunto;
                $mailer->Body = $corpo;

                if (count($mailer->getTo()) > 0) {
                    if ($mailer->Send()){
                        $msg_ok[] = "<br>Foi enviado um email para: ".implode(", ", $envio_email)."<br>";
                    }else{
                        $msg_erro[] = "Não foi possível enviar o email.";
                    }
                }
              }
            }
          }
        }
      }

        if(!count($msg_erro)) {

        if( in_array($login_fabrica, array(11,42,172)) ) {
            $sql = "SELECT status FROM tbl_hd_chamado WHERE fabrica = $login_fabrica AND hd_chamado = $hd_chamado";
            $res = pg_query($con, $sql);
            $status = pg_fetch_result($res, 0, "status");

                if($status <> "Interno" && empty($inativar)) {
                    if ($xchamado_interno == false and empty($transferir_para)) {
                        $sql = " UPDATE tbl_hd_chamado set status = 'Ag. Posto'
                                WHERE hd_chamado = $hd_chamado";
                        $res = pg_query($con,$sql);
                    }
                }
            } else {
                if ($xchamado_interno == false and  empty($transferir_para)) {

                    $sql = " UPDATE tbl_hd_chamado set status = 'Ag. Posto'
                            WHERE hd_chamado = $hd_chamado";
                    $res = pg_query($con,$sql);
                }
            }

            if($tipo_resposta == "Resp.Conclusiva"){
                $bDados = hdBuscarChamado($hd_chamado);
                $abertura = explode('.',$bDados['data_abertura']);
                $duracao = strtotime('now') - strtotime($abertura[0]);

                $sql = " UPDATE tbl_hd_chamado set duracao = (case when duracao isnull then $duracao else duracao + $duracao end)
                            WHERE hd_chamado = $hd_chamado";
                $res = pg_query($con,$sql);
            }

            if ($login_fabrica == 1) {

                if($tipo_resposta == "Em Acomp." && $pendente_acompanhamento == "pendente_acomp"){

                    $sql_pendente = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = 't' WHERE hd_chamado = {$hd_chamado}";
                    $res_pendente = pg_query($con, $sql_pendente);

                }else if($tipo_resposta == "Resp.Conclusiva" OR $pendente_acompanhamento != "pendente_acomp"){

                    $sql_pendente = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = 'f' WHERE hd_chamado = {$hd_chamado}";
                    $res_pendente = pg_query($con, $sql_pendente);

                }

                if($tipo_resposta == "encerrar_acomp" && $encerrar_acompanhamento == "encerrar_acomp"){

                    $sql_encerrar = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = 'f' WHERE hd_chamado = {$hd_chamado}";
                    $res_encerrar = pg_query($con, $sql_encerrar);

                }

            }

            if(!is_null($categoria)) { // HD 228309 - Permitir alterar a categoria
                $novo_atendente = $categorias[$categoria]['atendente'];
                $novo_atendente = (is_numeric($novo_atendente)) ? $novo_atendente : hdBuscarAtendentePorPosto($posto,$categoria);
                $cat_anterior   = check_post_field('cat_atual');

                if($ultimo_atendente != $novo_atendente) { // Informar para quem foi transferido o chamado, se mudou de atendente.
                    $a_admin = hdBuscarAdmin(array('admin = '.$novo_atendente));
                    $novo_atendente_nome = $a_admin[$novo_atendente]['nome_completo'];
                    $transfere = "<br>Chamado transferido para <b>$novo_atendente_nome</b>.";
                }

                if($login_fabrica == 1){
                    $sql = "UPDATE tbl_hd_chamado SET categoria = '$categoria',atendente = $novo_atendente WHERE hd_chamado = $hd_chamado";
                }else{
                    $sql = "UPDATE tbl_hd_chamado SET categoria = '$categoria',status='Ag. Fábrica',atendente = $novo_atendente WHERE hd_chamado = $hd_chamado";
                }

                //$sql = "UPDATE tbl_hd_chamado SET categoria = '$categoria',status='Ag. Fábrica',atendente = $novo_atendente WHERE hd_chamado = $hd_chamado";

                $res = pg_query($con, $sql);
                $void = hdCadastrarResposta($hd_chamado, "Tipo de solicitação alterado de <i>$cat_anterior</i> para <i><u>{$categorias[$categoria]['descricao']}</u></i>.$transfere", true, $tipo_resposta, $login_admin, null,$transferir_para);

                $msg_ok[] = "Solicitação alterada para {$categorias[$categoria]['descricao']}.$transfere";
            }
            if ($login_fabrica == 1) {
                if ($tipo_resposta == "encerrar_acomp" && $encerrar_acompanhamento == "encerrar_acomp"){
                    $tipo_resposta = "Em Acomp. Encerra";

                }
                if ($tipo_resposta == "Em Acomp."  && $pendente_acompanhamento == "pendente_acomp"){
                    $tipo_resposta   = "Em Acomp. Pendente";
                }
            }

            if ($login_fabrica == 3) {
                if (strlen(trim($produto_hidden_cd)) > 0){
                    $sql_produto_cd = "SELECT referencia FROM tbl_produto WHERE produto = {$produto_hidden_cd} AND fabrica_i = {$login_fabrica};";
                    $res_produto_cd = pg_query($con,$sql_produto_cd);

                    if (pg_num_rows($res_produto_cd) > 0 ) {
                        $produto_cd_hidden = pg_fetch_result($res_produto_cd, 0, referencia);

                        $sql_produto_cd = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden_cd} WHERE hd_chamado = {$hd_chamado}";
                        $res_produto_cd = pg_query($con, $sql_produto_cd);

                        if (!pg_last_error()) {
                            $msg_produto_alterado = "Produto alterado de <i>{$produto_hidden_ant}</i> para <i><u>{$produto_cd_hidden}</u></i>.$transfere";
                        }
                    }
                }
            }

            if (!empty($_POST['resposta'])) {
                $xresposta = $_POST['resposta'];
                $xresposta = strip_tags(html_entity_decode($xresposta),$manterHtml);
                $hd_chamado_item = hdCadastrarResposta($hd_chamado, $xresposta, $xchamado_interno, $tipo_resposta, $login_admin, null,$transferir_para);
            }else{
                $hd_chamado_item = true;
            }

            if ( ! $hd_chamado_item ) {
                $msg_erro[] = "<p>Erro ao inserir a resposta no chamado.</p>";
                pg_query($con,"ROLLBACK");
                $hd_chamado = "";
            } else {

                $msg_ok[]  = "<p>Chamado respondido</p>";

                /* if ( isset($_FILES) && count($_FILES) > 0 && !empty($_FILES['anexo']['name']) ) {

                    $ok = hdCadastrarUpload('anexo',$hd_chamado_item,$msg_erro);
                    if ( $ok ) {
                    $msg_ok[] = "<p>Arquivo anexado com sucesso</p>";
                    }

                } */

                if($login_fabrica == 3){
                    $tem_defeitos = false;

                    if(strlen($defeitos_produtos) > 0 && strlen($solucoes_produtos) > 0 && strlen($produto_hidden) > 0){
                    $tem_defeitos = TRUE;
                    $sql_verifica = "SELECT tbl_dc_solucao_hd FROM tbl_dc_solucao_hd WHERE hd_chamado = {$hd_chamado}";
                    $res_verifica = pg_query($con, $sql_verifica);

                    $sql_produto = "UPDATE tbl_hd_chamado_extra SET produto = {$produto_hidden} WHERE hd_chamado = {$hd_chamado}";
                    $res_produto = pg_query($con, $sql_produto);

                    if(pg_num_rows($res_verifica) > 0){

                        $update_ds = "UPDATE tbl_dc_solucao_hd SET defeito_constatado_solucao = {$solucoes_produtos} WHERE hd_chamado = {$hd_chamado}";
                        $update_ds = pg_query($con, $update_ds);

                    }else{

                        $sql_da = "SELECT data FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
                        $res_da = pg_query($con, $sql_da);

                        $data_abertura = pg_fetch_result($res_da, 0, "data");
                        list($data, $hora) = explode(" ", $data_abertura);
                        $data_abertura = $data;

                        $sql_defeito_solucao = "INSERT INTO tbl_dc_solucao_hd (fabrica, defeito_constatado_solucao, hd_chamado, data_abertura) VALUES ($login_fabrica, $solucoes_produtos, $hd_chamado, '$data_abertura')";
                        $res_defeito_solucao = pg_query($con, $sql_defeito_solucao);

                    }

                    if(strlen($utilizar_resposta) > 0 && $utilizar_resposta != "nao"){
                        $upd_resposta = "UPDATE tbl_defeito_constatado_solucao SET solucao_procedimento = '{$resposta}' WHERE defeito_constatado_solucao = {$solucoes_produtos}";
                        $res_resposta = pg_query($con, $upd_resposta);
                    }

                    }

                    $produto_id_resp = $_POST['produto_id_resp'];
                    $defeito_resp = $_POST['defeito'];
                    $solucao_resp = $_POST['solucao'];

                    if(strlen(trim($defeito_resp)) > 0 && strlen(trim($solucao_resp)) > 0 && strlen(trim($produto_id_resp)) > 0){
                        $tem_defeitos = TRUE;

                        $sql_verifica = "SELECT
                                        tbl_dc_solucao_hd,
                                        tbl_defeito_constatado_solucao.defeito_constatado,
                                        tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                    FROM tbl_dc_solucao_hd
                                    JOIN tbl_defeito_constatado_solucao USING(defeito_constatado_solucao)
                                    WHERE hd_chamado = {$hd_chamado}";
                        $res_verifica = pg_query($con, $sql_verifica);

                        if(pg_num_rows($res_verifica) == 0 && $login_fabrica == 3){
                            $sql = "SELECT hd_chamado_anterior FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado AND fabrica = $login_fabrica";
                            $res = pg_query($con, $sql);
                            $hd_chamado_anterior = pg_fetch_result($res, 0, 'hd_chamado_anterior');
                            $hd_chamado_aux = hdChamadoAnterior($hd_chamado, $hd_chamado_anterior);
                            list($hd_chamado_aux,$digito) = explode('-',$hd_chamado_aux);
                            $hd_chamado_int = preg_replace("/\D/","",$hd_chamado_aux);

                            $sql = "SELECT
                                        tbl_hd_chamado.hd_chamado
                                    FROM tbl_hd_chamado
                                        JOIN tbl_hd_chamado_posto USING(hd_chamado)
                                    WHERE tbl_hd_chamado_posto.seu_hd = '$hd_chamado_aux'
                                        OR tbl_hd_chamado.hd_chamado = $hd_chamado_int
                                        OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado_int
                                        AND tbl_hd_chamado.fabrica = $login_fabrica
                                    ORDER BY 1 DESC;";

                            $res = pg_query($con, $sql);
                            $hd_chamado_anterior = pg_fetch_result($res, 1, 'hd_chamado');
                            if (!empty($hd_chamado_anterior)) {
                                $sql_verifica = "SELECT
                                                tbl_dc_solucao_hd,
                                                tbl_defeito_constatado_solucao.defeito_constatado,
                                                tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                            FROM tbl_dc_solucao_hd
                                            JOIN tbl_defeito_constatado_solucao USING(defeito_constatado_solucao)
                                            WHERE hd_chamado = {$hd_chamado_anterior}";
                                $res_verifica = pg_query($con, $sql_verifica);
                            }
                        }

                        if(pg_num_rows($res_verifica) > 0){
                            /* Carrega informações anteriores para inserir na interação */
                            $defeito_constatado_antes = pg_fetch_result($res_verifica, 0, defeito_constatado);
                            $defeito_constatado_solucao_antes = pg_fetch_result($res_verifica, 0, defeito_constatado_solucao);

                            if ($defeito_constatado_antes !== $defeito_resp) {
                                $sql_defeito_constatado =
                                            "SELECT
                                                defeito_constatado,
                                                descricao
                                            FROM tbl_defeito_constatado
                                            WHERE defeito_constatado IN({$defeito_constatado_antes}, {$defeito_resp})";
                                $res_defeito_constatado = pg_query($con, $sql_defeito_constatado);
                                for ($i=0; $i < pg_num_rows($res_defeito_constatado); $i++) {
                                    if (pg_fetch_result($res_defeito_constatado, $i, defeito_constatado) == $defeito_resp) {
                                        $defeito_constatado = pg_fetch_result($res_defeito_constatado, $i, descricao);
                                    }else{
                                        $defeito_constatado_antes = pg_fetch_result($res_defeito_constatado, $i, descricao);
                                    }
                                }
                                $msg_defeito_alterado = "Defeito alterado de <u>{$defeito_constatado_antes}</u> para <u>{$defeito_constatado}</u>";
                            }
                            if ($defeito_constatado_solucao_antes !== $solucao_resp) {
                                $sql_defeito_solucao_constatado =
                                            "SELECT
                                                tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                                                tbl_solucao.descricao
                                            FROM tbl_defeito_constatado_solucao
                                                JOIN tbl_solucao ON tbl_solucao.solucao =tbl_defeito_constatado_solucao.solucao
                                            WHERE tbl_defeito_constatado_solucao.defeito_constatado_solucao IN({$defeito_constatado_solucao_antes}, {$solucao_resp})";
                                $res_defeito_solucao_constatado = pg_query($con, $sql_defeito_solucao_constatado);
                                for ($i=0; $i < pg_num_rows($res_defeito_solucao_constatado); $i++) {
                                    if (pg_fetch_result($res_defeito_solucao_constatado, $i, defeito_constatado_solucao) == $solucao_resp) {
                                        $defeito_constatado_solucao = pg_fetch_result($res_defeito_solucao_constatado, $i, descricao);
                                    }else{
                                        $defeito_constatado_solucao_antes = pg_fetch_result($res_defeito_solucao_constatado, $i, descricao);
                                    }
                                }
                                $msg_solucao_alterada = "Solução alterada de <u>{$defeito_constatado_solucao_antes}</u> para <u>{$defeito_constatado_solucao}</u>";
                            }

                            if (empty($hd_chamado_aux)) {
                                $update_ds = "UPDATE tbl_dc_solucao_hd SET defeito_constatado_solucao = {$solucao_resp} WHERE hd_chamado = {$hd_chamado}";
                            } else {
                                $sql_da = "SELECT data FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
                                $res_da = pg_query($con, $sql_da);

                                $data_abertura = pg_fetch_result($res_da, 0, "data");
                                list($data, $hora) = explode(" ", $data_abertura);
                                $data_abertura = $data;

                                $update_ds = "INSERT INTO tbl_dc_solucao_hd (
                                                fabrica,
                                                defeito_constatado_solucao,
                                                hd_chamado,
                                                data_abertura
                                            ) VALUES (
                                                $login_fabrica,
                                                $solucao_resp,
                                                $hd_chamado,
                                                '$data_abertura'
                                            )";

                                $msg = (in_array($login_fabrica, array(3))) ? 'Admin informou um defeito e solução para o atendimento' : 'Admin atualizou o defeito e solução do chamado';
                                $void = hdCadastrarResposta($hd_chamado, $msg, false, $tipo_resposta, $login_admin, null,$transferir_para);
                            }
                            $update_ds = pg_query($con, $update_ds);
                            if (!pg_last_error($con)) {
                                $msg_produto_alterado = (!empty($msg_produto_alterado)) ? $msg_produto_alterado."<br />" : $msg_produto_alterado;

                                if (!empty($msg_defeito_alterado)) {
                                    $msg_solucao_alterada = (!empty($msg_solucao_alterada)) ? "<br />".$msg_solucao_alterada : $msg_solucao_alterada;

                                    $hd_chamado_item = hdCadastrarResposta($hd_chamado, $msg_produto_alterado.$msg_defeito_alterado.$msg_solucao_alterada, 1, $tipo_resposta, $login_admin, null,$transferir_para);
                                }elseif (!empty($msg_solucao_alterada)) {
                                    $hd_chamado_item = hdCadastrarResposta($hd_chamado, $msg_produto_alterado.$msg_solucao_alterada, 1, $tipo_resposta, $login_admin, null,$transferir_para);
                                }elseif (empty($_POST['resposta']) && !empty($_POST['transferir_para'])) {
                                    $hd_chamado_item = hdCadastrarResposta($hd_chamado, null, false, $tipo_resposta, $login_admin, null,$transferir_para);
                                }elseif (empty($_POST['resposta'])) {
                                    if ($_POST['tipo_resposta'] == 'Em Acomp.') {
                                        $msg_automatica = "O admin classificou o chamado como 'Em acompanhamento'";
                                    }elseif ($_POST['tipo_resposta'] == 'Resp.Conclusiva') {
                                        $msg_automatica = "O admin classificou o chamado como 'Resposta Conclusiva'";
                                    }elseif ($_POST['tipo_resposta'] == 'cancelar') {
                                        $msg_automatica = "O admin classificou o chamado como 'Cancelado'";
                                    }
                                    $hd_chamado_item = hdCadastrarResposta($hd_chamado, $msg_automatica, 0, $tipo_resposta, $login_admin, null,$transferir_para);
                                }
                            }else{
                                if (!empty($msg_produto_alterado)) {
                                    $void = hdCadastrarResposta($hd_chamado, $msg_produto_alterado, true, $tipo_resposta, $login_admin, null,$transferir_para);
                                }
                            }
                        } else {
                            $sql_da = "SELECT data FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
                            $res_da = pg_query($con, $sql_da);

                            $data_abertura = pg_fetch_result($res_da, 0, "data");
                            list($data, $hora) = explode(" ", $data_abertura);
                            $data_abertura = $data;

                            $sql_defeito_solucao = "INSERT INTO tbl_dc_solucao_hd (
                                                                    fabrica,
                                                                    defeito_constatado_solucao,
                                                                    hd_chamado,
                                                                    data_abertura
                                                                    ) VALUES (
                                                                    $login_fabrica,
                                                                    $solucao_resp,
                                                                    $hd_chamado,
                                                                    '$data_abertura')";
                            $res_defeito_solucao = pg_query($con, $sql_defeito_solucao);

                            $msg = (in_array($login_fabrica, array(3))) ? 'Admin informou um defeito e solução para o atendimento' : 'Admin atualizou o defeito e solução do chamado';
                            $void = hdCadastrarResposta($hd_chamado, $msg, false, $tipo_resposta, $login_admin, null,$transferir_para);
                        }

                        if(strlen($utilizar_resposta) > 0 && $utilizar_resposta != "nao"){
                            $upd_resposta = "UPDATE tbl_defeito_constatado_solucao SET solucao_procedimento = '{$resposta}' WHERE defeito_constatado_solucao = {$solucoes_produtos}";
                            $res_resposta = pg_query($con, $upd_resposta);
                        }
                    }

                    if (!$tem_defeitos && !in_array($login_fabrica, array(3))) {
                        $sql_valida = "SELECT dc_solucao_hd
                                            FROM tbl_dc_solucao_hd
                                            JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                            JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                            WHERE tbl_dc_solucao_hd.hd_chamado = $hd_chamado ;
                                            ";
                        $res_valida = pg_query($con,$sql_valida);
                        if (pg_num_rows($res_valida) > 0) {
                            $sql_exclui = "DELETE FROM tbl_dc_solucao_hd WHERE dc_solucao_hd = ".pg_fetch_result($res_valida, 0, dc_solucao_hd).";";
                            $res_exclui = pg_query($con,$sql_exclui);
                        }
                    }
                }elseif (!empty($msg_produto_alterado)) {
                    $void = hdCadastrarResposta($hd_chamado, $msg_produto_alterado, true, $tipo_resposta, $login_admin, null,$transferir_para);
                }
                if (isset($_FILES) && count($_FILES) > 0) {

                    $idExcluir = null;

                    if ($_POST['anexo']) {
                        $_POST['anexo'] = $anexo = stripslashes($_POST['anexo']);
                        $fileData = json_decode($anexo, true);
                        $idExcluir =  $fileData['tdocs_id'];
                    }

                    $tDocs   = new TDocs($con, $login_fabrica);

                    for($f = 0; $f < count($_FILES["anexo"]["tmp_name"]); $f++){

                        if (strlen($_FILES['anexo']['tmp_name'][$f]) > 0) {

                            $arquivo_anexo = array(
                                    "name"     => $_FILES['anexo']['name'][$f],
                                    "type"     => $_FILES['anexo']['type'][$f],
                                    "tmp_name" => $_FILES['anexo']['tmp_name'][$f],
                                    "error"    => $_FILES['anexo']['error'][$f],
                                    "size"     => $_FILES['anexo']['size'][$f]
                            );

                            $anexoID = $tDocs->uploadFileS3($arquivo_anexo, $hd_chamado_item, false, 'hdpostoitem');

                            // Exclui o anterior, pois não será usado
                            if ($anexoID) {
                                // Se ocorrer algum erro, o anexo está salvo:
                                $_POST['anexo'] = json_encode($tDocs->sentData);
                                if (!is_null($idExcluir)) {
                                $tDocs->deleteFileById($idExcluir);
                                }
                            } else {
                                $msg_erro[] = 'Erro ao salvar o arquivo!';
                            }
                        }
                    }
                }

                if ($login_fabrica == 3){
                  $anexo_chave = $_POST["anexo_chave"];
                    
                  if ($anexo_chave != $hd_chamado_item) {
                    $sql_tem_anexo = "SELECT *
                                      FROM tbl_tdocs
                                      WHERE fabrica = $login_fabrica
                                      AND hash_temp = '{$anexo_chave}'
                                      AND situacao = 'ativo'";
                    $res_tem_anexo = pg_query($con, $sql_tem_anexo);
                    if (pg_num_rows($res_tem_anexo) > 0) {
                      $sql_update = "UPDATE tbl_tdocs SET
                                    referencia_id = {$hd_chamado_item},
                                    hash_temp = NULL,
                                    referencia = 'hdpostoitem'
                                    WHERE fabrica = $login_fabrica
                                    AND situacao = 'ativo'
                                    AND hash_temp = '{$anexo_chave}'";
                      $res_update = pg_query($con, $sql_update);
                      if (strlen(pg_last_error()) > 0) {
                        $msg_erro["msg"][] = traduz("Erro ao gravar anexos");
                      }
                    }
                  }
                }

                if(count($msg_erro) == 0){

                    pg_query($con,'COMMIT');
                    if ($login_fabrica == 42 && !empty($transferir_para)) {
                        include_once "../class/communicator.class.php";

                        $sqlVerificaEmailAtendente = "
                            SELECT  JSON_FIELD('aviso_email',tbl_admin.parametros_adicionais) AS aviso_email
                            FROM    tbl_admin
                            WHERE   fabrica = $login_fabrica
                            AND     admin   = $transferir_para
                        ";
                        $resVerificaEmailAtendente = pg_query($con,$sqlVerificaEmailAtendente);

                        if (pg_fetch_result($resVerificaEmailAtendente,0,aviso_email) == 't') {
                            $sqlEmailAtendente = "
                                SELECT  email,
                                        nome_completo
                                FROM    tbl_admin
                                WHERE   fabrica = $login_fabrica
                                AND     admin = $transferir_para
                            ";
                            $resEmailAtendente = pg_query($con,$sqlEmailAtendente);

                            $emailAtendente = pg_fetch_result($resEmailAtendente,0,email);
                            $nomeAtendente = pg_fetch_result($resEmailAtendente,0,nome_completo);
//                                     <a href='$ondeAcessar/assist/helpdesk_cadastrar.php?hd_
                            if (!empty($emailAtendente)) {
                                $tituloEmail = utf8_encode("Chamado Telecontrol nº $hd_chamado");
                                $ondeAcessar = $_SERVER['SERVER_NAME'];
                                $textoEmail = "
                                    Chamado $hd_chamado - Tipo de Solicitação: ".$categorias[$categoria]['descricao']."
                                    <br />
                                    <br />
                                    <p>
                                    ATENDENTE $nomeAtendente,
                                    <br />
                                    Clique no link para acessar o seu chamado:
                                    <br />
                                    <a href='$ondeAcessar/assist/admin/helpdesk_cadastrar.php?hd_chamado=$hd_chamado' target='_BLANK'>$hd_chamado</a>
                                    <br />
                                    <br />
                                    Nota: Este e-mail é gerado automaticamente. <strong>Por favor, não responda esta mensagem!</strong>
                                    <br />
                                    <br />
                                    <span style='font-style:italic;'>Telecontrol Networking</span>
                                    </p>
                                ";
                                $mailer = new TcComm('smtp@posvenda');

                                $res = $mailer->sendMail(
                                    $emailAtendente,
                                    $tituloEmail,
                                    $textoEmail,
                                    'noreply@telecontrol.com.br'
                                );
                            }
                        }
                    
                        $updade_leitura = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = TRUE WHERE hd_chamado = $hd_chamado";
                        $res_leitura    = pg_query($con, $updade_leitura);
                    } else if ($login_fabrica == 42) {
                        $sql_leu = " SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado AND leitura_pendente IS TRUE ";
                        $res_leu = pg_query($con, $sql_leu);
                        if (pg_num_rows($res_leu) > 0) {
                          $updade_leitura = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = FALSE WHERE hd_chamado = $hd_chamado";
                          $res_leitura    = pg_query($con, $updade_leitura);
                        }

                    }
                    header("Location: helpdesk_cadastrar.php?hd_chamado=$hd_chamado&ok=1");

                }else{

                    pg_query($con,'ROLLBACK');

                    /* Caso dê algum erro ao subir a imagem, deleta a interação no chamado. */
                    $sql_del_item = "DELETE FROM tbl_hd_chamado_item WHERE hd_chamado = {$hd_chamado} AND hd_chamado_item = {$hd_chamado_item}";
                    $res_del_item = pg_query($con, $sql_del_item);

                }

            }
        } else {
            pg_query($con,'ROLLBACK');
        }
    }else{  //  Abre novo chamado
      $codigo_posto       = check_post_field('codigo_posto');
      $categoria          = check_post_field('categoria');
      $atendente_sac      = check_post_field('atendente');
      $referencia         = check_post_field('referencia');
      $os                 = null;
      $pedido             = null;
      $garantia           = check_post_field('garantia');
      $tipo_atualizacao   = null;
      $fone               = null;
      $email              = null;
      $banco              = null;
      $agencia            = null;
      $conta              = null;
      $nome_cliente       = null;
      $hd_chamado_sac     = null;
      $data_pedido        = null;
      $peca_faltante      = null;
      $peca_faltante2     = null;
      $peca_faltante3     = null;
      $linhas_atendimento = null;
      $produto            = null;
      $tipo_resposta = check_post_field("tipo_resposta");
      $pendente_acompanhamento = check_post_field("pendente_acompanhamento");

      if ($login_fabrica == 3) {
          $os = check_post_field('os');
      }

      if (is_null($codigo_posto)) {
        $msg_erro[] = 'Selecione o Posto Autorizado!';
      }else{
        $sql_pa = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
        $res_pa = @pg_query($con, $sql_pa);
        if (is_resource($res_pa) or @pg_num_rows($res_pa) == 0) {
            $posto = pg_fetch_result($res_pa, 0, 0);
        } else {
            $msg_erro[] = "Posto $codigo_posto não localizado.";
        }
      }

      if(!is_null($categoria)) {
          $campos_obrig = $categorias[$categoria][campos_obrig];
          $atendente    = $categorias[$categoria][atendente];
          $campos_cat   = $categorias[$categoria][campos];

          if((strlen($campo_obrig)>0)){
              foreach (array_unique(array_merge($campos_cat,$campo_obrig)) as $campo) {
                  $$campo = check_post_field($campo);
              }
          }

          foreach($campos_obrig as $required) {   // confere que os campos obrigatórios tenham vindo com valor
              if ($login_fabrica == 1 && $_POST["categoria"] == "manifestacao_sac") {
                if ($required == "usuario_sac" || $required == "nome_cliente") {
                  continue;
                }
              }
              $$required = check_post_field($required);
              if (is_null($$required)) $a_msg_erro[] = "O campo <span stlye='text-transform:uppercase'>{$a_campos[$required]}</span> é obrigatório";
          }
          if (count($a_msg_erro)) $msg_erro[] = implode('<br>', $a_msg_erro);
      }else{
        $msg_erro[] = 'Selecione o tipo de solicitação!';
      }

      if(empty($tipo_resposta) && $admin_sac == false){
        $msg_erro[] = 'Marcar uma das opções: Em acompanhamento ou Resposta conclusiva.';
      }
      if(!count($msg_erro)){
        $os_en = 'null';
        $pedido_en = 'null';

        switch ($categoria) {
          case 'atualiza_cadastro':
            $tipo_atualizacao = check_post_field('tipo_atualizacao');
            switch ($tipo_atualizacao) {
              case 'telefone':
                $fone = check_post_field('fone');
                if (is_null($fone)) {
                    $msg_erro[] = "Por favor, informe o telefone para Atualização";
                }
              break;
              case 'email':
                $email = check_post_field('email');
                if(is_null($email)) {
                    $msg_erro[] = "Por favor, informe o email para Atualização";
                } elseif (!is_email($email)) {
                    $msg_erro[] = 'Por favor, digite um e-mail válido para Atualização';
                }
              break;
              case 'end_cnp_raz_ban':
                $banco  = check_post_field('banco');
                $agencia= check_post_field('agencia');
                $conta  = check_post_field('conta');
              case 'dados_bancarios':
                if(is_null($banco) or is_null($agencia) or is_null($conta)) {
                  $msg_erro[] = "Por favor, informar todos os dados bancários";
                }
              break;
              case 'linha_atendimento':
                $linhas_atendimento = check_post_field('linhas');
                if (!is_null($linhas_atendimento)) {
                    foreach($linhas_atendimento as $linha) {$nomes_linhas[] = $a_linhas[$linha];}
                    $linha_atendimento = implode(', ', $linhas_atendimento);
                    $linhas = implode(', ', $nomes_linhas);
                //  Interpreta os valores gerados e cria uma frase 'natural' para gravar no chamado
                    $txt_linhas = (count($linhas_atendimento)==1) ? "Gostaria atender a linha $linhas.":"Gostaria atender as linhas $linhas.";
                    if (count($nomes_linhas)>1) $txt_linhas = substr_replace($txt_linhas, ' e', strrpos($txt_linhas, ','), 1);
                    if (strlen($_POST['resposta']) > 0) $txt_linhas.= "<br>\n";
                } else {
                    $msg_erro[] = "Por favor, selecione uma ou mais linhas de atendimento";
                }
              break;
            }
          break;
          case 'manifestacao_sac':
                    $nome_cliente       = check_post_field('nome_cliente');
                    $hd_chamado_sac     = check_post_field('hd_chamado_sac');
                    if($login_fabrica <> 1){
                      if ($nome_cliente == null or $atendente_sac == null) {
                          $msg_erro[] = "Por favor, informe o nome do cliente e atendente";
                      }
                      break;
                    }
                    $os                 = check_post_field('os');
                    $referencia         = check_post_field('referencia');
                    $garantia           = check_post_field('garantia');
                    break;
          case 'servico_atendimeto_sac':
                    $nome_cliente       = check_post_field('nome_cliente');
                    $hd_chamado_sac     = check_post_field('hd_chamado_sac');
                    if($login_fabrica <> 1){
                      if ($nome_cliente == null or $atendente_sac == null) {
                          $msg_erro[] = "Por favor, informe o nome do cliente e atendente";
                          break;
                      }
                    }
                    $os                 = check_post_field('os');
                    $referencia         = check_post_field('referencia');
                    $garantia           = check_post_field('garantia');
                    $os_posto           = check_post_field('os_posto');
                    break;
          case 'pendencias_de_pecas':
          case 'pedido_de_pecas':
          case 'pend_pecas_dist':
              $pedido             = check_post_field('pedido');
              $data_pedido        = pg_is_date(check_post_field('data_pedido'));
              $peca_faltante      = check_post_field('peca_faltante');
              if ($data_pedido === false) $data_pedido = null;
              if (!is_null($pedido)) $pedido = strtoupper($pedido);
              //  Não tem "BREAK" porque também tem que conferir OS e produtos... :P
          case 'duvida_produto':
          case 'duvida_troca':
          case 'digitacao_fechamento':
              $os                 = check_post_field('os');
              $referencia         = check_post_field('referencia');
              $garantia           = check_post_field('garantia');
              break;
          case 'solicitacao_coleta':
              $solic_coleta = check_post_field('solic_coleta');
              $tipo_dev_peca = check_post_field('tipo_dev_peca');
              $tipo_dev_prod = check_post_field('tipo_dev_prod');
          break;

        }

            //  Procura a OS se for solicitada pelo tipo de categoria
        if(empty($msg_erro)){
            if (in_array('os',  array_merge($campos_obrig,$campos_cat)) and !is_null($os)) {
                $sql = " SELECT os
                        FROM tbl_os
                        WHERE fabrica = $login_fabrica
                        AND   posto = $posto ";
                if (strlen($os) > 0) {
                    $sua_os = "000000" . trim ($os);
                    if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                        $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                    }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){
                        $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                    }else{
                        $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                    }
                //          $sua_os = strtoupper ($sua_os);
                    $sql .= "   AND (
                                tbl_os.sua_os ~ E'0?$sua_os' OR
                                tbl_os.sua_os = substr('$os',6,length('$os')) OR
                                tbl_os.sua_os = substr('$os',7,length('$os'))
                                OR tbl_os.sua_os ~ E'0*$sua_os\\-[1-4]+[0-9]?') ";
                }
                $sql.= " LIMIT 1 ";
                $res = pg_query($con,$sql);



                if(pg_num_rows($res) > 0){
                    $os_en = pg_fetch_result($res,0,os);
                }else{
                    $msg_erro[] = "Ordem de Serviço $os não encontrada";
                }
            }

            //Valida Dúvidas Técnicas
            $cat_array = array( 'duvida_tecnica_informatica'=> 'duvida_tecnica_informatica',
                      'duvida_tecnica_eletro_pessoal_refri'=> 'duvida_tecnica_eletro_pessoal_refri',
                      'duvida_tecnica_celular'=> 'duvida_tecnica_celular',
                      'duvida_tecnica_audio_video'=> 'duvida_tecnica_audio_video');

            if ($login_fabrica == 3 && in_array($categoria, $cat_array)) {

              $garantia     = check_post_field('garantia');

              if ($garantia == 't') {
                if (!is_null($os2)) {
                  $os2 = trim($os2);
                  $sql = "SELECT os
                        FROM tbl_os
                        WHERE fabrica = {$login_fabrica}
                        AND posto = {$posto}
                        AND sua_os = '{$os2}' LIMIT 1";
                  $res = pg_query($con,$sql);

                  if(pg_num_rows($res) > 0){
                    $os_en = pg_fetch_result($res,0,os);
                  }else{
                    $msg_erro[] = "Ordem de Serviço $os2 não encontrada<br>";
                  }
                }else{
                  $msg_erro[] = "Favor preencher a Ordem de Serviço<br>";
                }
              }
            }

            if ($login_fabrica == 3 and !empty($campos_cat)) {
                if (in_array('os',  $campos_cat) and !is_null($os)) {
                    $sql = " SELECT os
                            FROM tbl_os
                            WHERE fabrica = $login_fabrica
                            AND   posto = $posto ";
                    if (strlen($os) > 0) {
                        $sua_os = "000000" . trim ($os);
                        // $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
                        }elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
                        }else{
                            $sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
                        }
                        //          $sua_os = strtoupper ($sua_os);
                        $sql .= "   AND (
                                    tbl_os.sua_os ~ E'0?$sua_os' OR
                                    tbl_os.sua_os = substr('$os',6,length('$os')) OR
                                    tbl_os.sua_os = substr('$os',7,length('$os'))
                                    OR tbl_os.sua_os ~ E'0*$sua_os\\-[1-4]+[0-9]?') ";
                    }
                    $sql.= " LIMIT 1 ";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $os_en = pg_fetch_result($res,0,os);
                    }else{
                        $msg_erro[] = "Ordem de Serviço $os não encontrada<br>";
                    }
                }
            }

            //  Procura o código do PRODUTO se for solicitada pelo tipo de categoria
            if (in_array('referencia', array_merge($campos_obrig,$campos_cat)) and !is_null($referencia)) {
                $sql = "SELECT  produto
                        FROM    tbl_produto
                        JOIN    tbl_linha USING(linha)
                        WHERE   fabrica = $login_fabrica
                          AND   referencia LIKE UPPER('$referencia%')";
                $res = @pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $produto = pg_fetch_result($res,0,produto);
                }else{
                    $msg_erro[] = "Produto $referencia não encontrado!";
                }
            }


            if (in_array('pedido', array_merge($campos_obrig,$campos_cat)) and !is_null($pedido)) {
                $seu_pedido = (is_numeric($pedido)) ? "~ E'[A-Z]{3}\\d?$pedido\\d?'" : "LIKE '".strtoupper($pedido)."%'";
                $sql = "SELECT pedido
                        FROM tbl_pedido
                        $join_pedido_emb
                        WHERE fabrica   = $login_fabrica
                          AND posto     = $posto
                          AND seu_pedido  $seu_pedido
                        LIMIT 1";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0) {
                    $pedido_en = pg_fetch_result($res,0,pedido);
                    $tem_pedido = "sim";
                } else {
                    $msg_erro[] = "Número de Pedido $pedido não encontrado";
                }
            }
        }

        if ($login_fabrica == 3) {
          if(strlen($produto_hidden) > 0){
            $sql_produto = "SELECT referencia, descricao FROM tbl_produto WHERE produto = {$produto_hidden} AND fabrica_i = {$login_fabrica}";
            $res_produto = pg_query($con, $sql_produto);

            if(pg_num_rows($res_produto) > 0){
              $referencia = pg_fetch_result($res_produto, 0, "referencia");
              $descricao = pg_fetch_result($res_produto, 0, "descricao");
              $produto = $produto_hidden;
            }
            $defeito_constatado = $_POST["defeitos_produtos"];
            $defeito_solucao_id = $_POST["solucoes_produtos"];
          }
        }

        if (!count($msg_erro)) {   // Se não teve erro no bloco anterior...
            if(is_null($hd_chamado)) {
            //$garantia = (!is_null($garantia) or $garantia == 't');

                if(is_null($data_pedido) and ($categoria == 'pendencias_de_pecas' or $categoria == 'pedido_de_pecas')) {    // Reduntdante, já tem conferência de campos obrigatórios
                  // $msg_erro[] = "Por favor, informe a data do pedido"; HD 281195
                }else{
                    if($tem_pedido == 'sim') {
                        $sql = "SELECT pedido
                                FROM tbl_pedido
                                WHERE pedido = $pedido_en
                                AND   data::date = '$data_pedido'";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) == 0){
                            $msg_erro[] = "A data do pedido informado está errada<br>";
                        }
                    }
                }
                if(count($peca_faltante) > 0 AND $categoria != "solicitacao_coleta") {
                    for($i =0;$i<count($peca_faltante);$i++) {
                        $sql = " SELECT tbl_peca.referencia,
                                        tbl_peca.descricao
                                FROM tbl_pedido_item
                                JOIN tbl_peca USING(peca)
                                WHERE pedido = $pedido_en
                                AND   fabrica = $login_fabrica
                                AND   referencia  = '".$peca_faltante[$i]."'";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $pecas .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
                        }else{
                            if($tem_pedido == 'sim' ) {
                                $msg_erro[] = $peca_faltante[$i]." não pertence ao pedido digitado<br>";
                            }else{
                                $sql = " SELECT tbl_peca.referencia,
                                        tbl_peca.descricao
                                FROM tbl_peca
                                WHERE fabrica = $login_fabrica
                                AND   referencia  = '".$peca_faltante[$i]."'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $pecas .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
                                }
                            }
                        }

                    }
                }

                if($categoria == "solicitacao_coleta"){

                    $solic_coleta = check_post_field('solic_coleta');
                    if($solic_coleta == "pecas"){
                        $tipo_dev_peca = check_post_field('tipo_dev_peca');
                        if($tipo_dev_peca == 1){

                            $nf_origem_peca           = check_post_field('nf_origem_peca');
                            $data_nf_peca             = check_post_field('data_nf_peca');
                            $peca_faltante2           = check_post_field('peca_faltante2');
                            $nf_venda_peca            = check_post_field('nf_venda_peca');
                            $data_nf_venda_peca       = check_post_field('data_nf_venda_peca');
                            $defeito_constatado_peca = check_post_field('defeito_constatado_peca');

                            list($d,$m,$y) = explode('/',$data_nf_peca);
                            if(!checkdate($m,$d,$y)){
                                $msg_erro[] = "Data da NF inválida";
                            } else {
                                $data_nf_peca = "$y-$m-$d";
                            }

                            if(empty($nf_origem_peca)){
                                $msg_erro[] = "Informe Nota Fiscal de Origem";
                            }

                            if(empty($data_nf_peca)){
                                $msg_erro[] = "Informe a data Nota Fiscal de Origem";
                            }

                            if(!empty($nf_venda_peca)){
                                if(empty($data_nf_venda_peca)){
                                    $msg_erro[] = "Informe a data Nota Fiscal de Venda";
                                }else{
                                    list($d,$m,$y) = explode('/',$data_nf_venda_peca);
                                    if(!checkdate($m,$d,$y)){
                                        $msg_erro[] = "Data da NF Venda inválida";
                                    } else {
                                        $data_nf_peca_venda = "$y-$m-$d";
                                        if(strtotime($data_nf_peca_venda.'+90 days') < strtotime('today')){
                                            $msg_erro[] = "Prazo para constatação do defeito na peça enviada pela fábrica é de até 90 dias após a venda para o cliente";
                                        }
                                    }
                                }
                            }



                            if(!count($msg_erro)){
                                $sql = "SELECT tbl_pendencia_bd_novo_nf.nota_fiscal
                                        FROM tbl_pendencia_bd_novo_nf
                                        JOIN tbl_pedido ON tbl_pendencia_bd_novo_nf.pedido::integer = tbl_pedido.pedido AND tbl_pedido.tipo_pedido = 86 AND tbl_pedido.fabrica = $login_fabrica
                                        WHERE tbl_pendencia_bd_novo_nf.posto = $posto
                                        AND tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_origem_peca'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    $inf_adicionais = "ARRAY['$solic_coleta','Peça enviada com defeito','$tipo_dev_peca','".$nf_venda_peca.";".$data_nf_peca_venda.";".$defeito_constatado_peca."']";
                                    $campos_hd_chamado_posto = ", inf_adicionais ";
                                    $values_hd_chamado_posto = ", $inf_adicionais";
                                    $campos_hd_chamado_extra = ", nota_fiscal, data_nf";
                                    $values_hd_chamado_extra = ", '$nf_origem_peca', '$data_nf_peca'";
                                } else {
                                    $msg_erro[] = "NF não encontrada no sistema para venda de peças. Gentileza verificar.<br>";
                                }
                            }
                            if(!count($peca_faltante2)){
                                $msg_erro[] = "Informe as peças";
                            }


                            if(!count($msg_erro) AND count($peca_faltante2) > 0){
                                for($i =0;$i<count($peca_faltante2);$i++) {
                                    $sql = "SELECT tbl_peca.referencia,tbl_peca.descricao
                                            FROM tbl_pendencia_bd_novo_nf
                                            JOIN tbl_peca ON tbl_pendencia_bd_novo_nf.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
                                            WHERE tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_origem_peca'
                                            AND tbl_pendencia_bd_novo_nf.posto = $posto
                                            AND referencia = '".$peca_faltante2[$i]."'";
                                    $res = pg_query($con,$sql);
                                    if(pg_num_rows($res) > 0){
                                        $pecas .=pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao)."<br>";
                                    }else {
                                        $msg_erro[] = "Peça $peca_ref não consta na NF de origem informada.<br>";
                                    }

                                }
                            }

                        } else {
                            $resp_devolucao_peca    = check_post_field('resp_devolucao_peca');
                            $motivo_devolucao_peca  = check_post_field('motivo_devolucao_peca');
                            $extratos_peca          = check_post_field('extratos_peca');

                            if(empty($resp_devolucao_peca)){
                                $msg_erro[] = "Informe o responsável pela devolução da peça";
                            }

                            if(empty($motivo_devolucao_peca)){
                                $msg_erro[] = "Informe o motivo da devolução da peça";
                            }

                            $inf_adicionais = "ARRAY['$solic_coleta','Devolução de peça para análise','$tipo_dev_peca','".$resp_devolucao_peca.";".$motivo_devolucao_peca.";".$extratos_peca."']";
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        }

                    } else {
                        $nf_origem_prod             = check_post_field('nf_origem_prod');
                        $data_nf_prod               = check_post_field('data_nf_prod');
                        $os                         = check_post_field('os_coleta');
                        $referencia                 = check_post_field('referencia2');
                        $descricao_produto          = check_post_field('descricao2');
                        $motivo_dev_produto         = check_post_field('motivo_dev_produto');
                        $resp_devolucao_produto     = check_post_field('resp_devolucao_produto');
                        $motivo_devolucao_produto   = check_post_field('motivo_devolucao_produto');

                        list($d,$m,$y) = explode('/',$data_nf_prod);
                        if(!checkdate($m,$d,$y)){
                            $msg_erro[] = "Data da NF inválida";
                        } else {
                            $data_nf_prod = "$y-$m-$d";
                        }

                        if(empty($nf_origem_prod)){
                            $msg_erro[] = "Informe Nota Fiscal de Origem";
                        }

                        if(empty($data_nf_prod)){
                            $msg_erro[] = "Informe a data Nota Fiscal de Origem";
                        }

                        if(!count($msg_erro)){
                            $sql = "SELECT tbl_os_item_nf.nota_fiscal
                                    FROM tbl_os_item_nf
                                    JOIN tbl_os_item ON tbl_os_item_nf.os_item = tbl_os_item.os_item AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $posto
                                    JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica
                                    WHERE  tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
                                    AND   tbl_os_item_nf.data_nf = '$data_nf_prod'
                                    AND tbl_pedido.troca IS TRUE";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) > 0){

                                if($tipo_dev_prod == 1 OR $tipo_dev_prod == 2){
                                    $sql = "SELECT tbl_produto.produto
                                            FROM tbl_os_item_nf
                                            JOIN tbl_os_item ON tbl_os_item_nf.os_item = tbl_os_item.os_item AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $posto
                                            JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica AND tbl_peca.produto_acabado IS TRUE
                                            JOIN tbl_produto ON tbl_peca.referencia = tbl_produto.referencia_fabrica AND tbl_produto.fabrica_i = $login_fabrica
                                            JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica and tbl_pedido.posto = $posto
                                            WHERE tbl_peca.referencia LIKE UPPER('$referencia%')
                                            AND tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
                                            AND tbl_os_item_nf.data_nf = '$data_nf_prod'
                                            AND tbl_pedido.troca IS TRUE LIMIT 1";
                                    $res = pg_query($con,$sql);

                                    if(pg_num_rows($res) > 0){

                                        $produto = pg_result($res,0,produto);
                                        if($tipo_dev_prod == 1){
                                            $inf_adicionais = "ARRAY['$solic_coleta','Produto trocado pela fábrica','$tipo_dev_prod','".$resp_devolucao_produto."']";
                                        }
                                        if($tipo_dev_prod == 2){
                                            $inf_adicionais = "ARRAY['$solic_coleta','Produto para análise da fábrica','$tipo_dev_prod','".$resp_devolucao_produto."']";
                                        }
                                    } else {
                                        $msg_erro[] = "Produto $referencia não consta na NF de origem informada.";
                                    }

                                } else if($tipo_dev_prod == 3) {

                                    $sql = "SELECT tbl_os.os
                                            FROM tbl_os
                                            JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
                                            JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
                                            JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
                                            WHERE tbl_os.sua_os = '$os'
                                            AND tbl_os.fabrica = $login_fabrica
                                            AND tbl_os.posto = $posto
                                            AND (tbl_os.troca_garantia IS TRUE OR tbl_os.troca_faturada IS TRUE)
                                            AND tbl_os_item_nf.nota_fiscal = '$nf_origem_prod'
                                            AND   tbl_os_item_nf.data_nf = '$data_nf_prod'";
                                    $res = pg_query($con,$sql);

                                    if(pg_num_rows($res) > 0){
                                        $os_en = pg_fetch_result($res,0,os);
                                        $inf_adicionais = "ARRAY['$solic_coleta','Produto novo na embalagem','$tipo_dev_prod','".$motivo_dev_produto."']";
                                    }else{
                                        $msg_erro[] = "A OS: $os não consta na NF de origem informada.";
                                    }
                                }
                                $campos_hd_chamado_posto = ", inf_adicionais ";
                                $values_hd_chamado_posto = ", $inf_adicionais";
                                $campos_hd_chamado_extra = ", nota_fiscal, data_nf";
                                $values_hd_chamado_extra = ", '$nf_origem_prod', '$data_nf_prod'";
                            }else{
                                $msg_erro[] = "NF não encontrada no sistema para envio de produto(s) para o seu posto de serviços. Gentileza verificar.";
                            }
                        }

                    }
                }

                if ($categoria == "solicita_informacao_tecnica") {
                    $solicita_informacao_tecnica       = $_POST["solicita_informacao_tecnica"];
                    $campos_hd_chamado_posto           = ", inf_adicionais ";
                    $solicita_informacao_tecnica_outro = $_POST["solicita_informacao_tecnica_outro"];

                    if ($solicita_informacao_tecnica == "outro") {
                        $values_hd_chamado_posto = ", ARRAY['$solicita_informacao_tecnica', '$solicita_informacao_tecnica_outro']";
                    } else {
                        $values_hd_chamado_posto = ", ARRAY['$solicita_informacao_tecnica']";
                    }
                }

                if ($categoria == "sugestao_critica") {
                    $sugestao_critica = $_POST["sugestao_critica"];

                    $campos_hd_chamado_posto = ", inf_adicionais ";
                    $values_hd_chamado_posto = ", ARRAY['$sugestao_critica']";
                }

                if($categoria == "pagamento_garantia"){
                    $duvida = check_post_field('duvida');

                    switch($duvida){
                        case 'aprova':
                            $data_fechamento = check_post_field('data_fechamento');
                            $inf_adicionais = "ARRAY['$duvida','Aprovação de extrato','".$data_fechamento."']";

                            if($data_fechamento){
                                list($d, $m, $y) = explode("/", $data_fechamento);
                                if(!checkdate($m,$d,$y))
                                    $msg_erro[] = "Data fechamento inválida";
                            } else{
                                $msg_erro[] = "Informe a data de fechamento";
                            }
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        break;

                        case 'pendente':
                        case 'bloqueado':
                            $extrato_duvida = check_post_field('num_extrato');
                            if($duvida == 'pendente'){
                                $inf_adicionais = "ARRAY['$duvida','Extrato pendente','".$extrato_duvida."']";
                            } else {
                                $inf_adicionais = "ARRAY['$duvida','Extrato bloqueado','".$extrato_duvida."']";
                            }
                            if($extrato_duvida){
                                $sql = "SELECT protocolo
                                        FROM tbl_extrato
                                        WHERE fabrica = $login_fabrica
                                        AND posto = $posto
                                        AND protocolo = '$extrato_duvida'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){

                                }else{
                                    $msg_erro[] = "Extrato não encontrado";
                                }
                            }else{
                                $msg_erro[] = "Informe número do extrato";
                            }
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        break;

                        case 'documentos':
                            $extrato_duvida = check_post_field('num_extrato');
                            $objeto_duvida = check_post_field('num_objeto');
                            $data_envio = check_post_field('data_envio');

                            list($d,$m,$y) = explode('/',$data_envio);
                            if(!checkdate($m,$d,$y)){
                                $msg_erro[] = "Data de envio inválida";
                            }

                            $inf_adicionais = "ARRAY['$duvida','Documentação enviada para a fábrica','".$extrato_duvida.";".$objeto_duvida.";".$data_envio."']";

                            if($extrato_duvida){
                                $sql = "SELECT protocolo
                                        FROM tbl_extrato
                                        WHERE fabrica = $login_fabrica
                                        AND posto = $posto
                                        AND protocolo = '$extrato_duvida'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){

                                }else{
                                    $msg_erro[] = "Extrato não encontrado";
                                }
                            }else{
                                $msg_erro[] = "Informe número do extrato";
                            }

                            if($data_envio){
                                list($d, $m, $y) = explode("/", $data_envio);
                                if(!checkdate($m,$d,$y))
                                    $msg_erro = "Data de envio inválida";
                            } else{
                                $msg_erro[] = "Informe data de envio";
                            }

                            if($objeto_duvida){
                                if(strlen($objeto_duvida) != 13){
                                    $msg_erro[] = "Número do objeto inválido";
                                }
                            } else {
                                $msg_erro[] = "Informe número do objeto";
                            }
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        break;
                        case 'duvida_extrato':
                            $inf_adicionais = "ARRAY['$duvida','Dúvida no Extrato']";
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        break;
                        case 'pagamento_nf':
                            $inf_adicionais = "ARRAY['$duvida','Pagamento de NFs']";
                            $campos_hd_chamado_posto = ", inf_adicionais ";
                            $values_hd_chamado_posto = ", $inf_adicionais";
                        break;
                    }
                }

                if($categoria == "erro_embarque"){
                    $erro_emb  = check_post_field('erro_emb');
                    $tipo_emb_peca  = check_post_field('tipo_emb_peca');
                    $tipo_emb_prod  = check_post_field('tipo_emb_prod');
                    $referencia3  = check_post_field('referencia3');
                    $descricao_produto  = check_post_field('descricao3');

                    $join_pedido_emb = " JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica AND (tbl_tipo_pedido.descricao = 'GARANTIA' OR tbl_tipo_pedido.descricao = 'FATURADO') ";



                    if($erro_emb == "pecas"){
                        $data_nf_emb  = check_post_field('data_nf_emb');
                        $nf_embarque = check_post_field('nf_embarque');
                        if($data_nf_emb){
                            list($d, $m, $y) = explode("/", $data_nf_emb);
                            if(!checkdate($m,$d,$y)){
                                $msg_erro[] = "Data fechamento inválida";
                            } else {
                                $data_nf_emb_aux = "$y-$m-$d";
                            }
                        } else{
                            $msg_erro[] = "Informe a data de fechamento";
                        }

                        $seu_pedido  = check_post_field('pedido_emb_peca');

                        $sql = "SELECT pedido, tbl_tipo_pedido.descricao
                                FROM tbl_pedido
                                $join_pedido_emb
                                WHERE tbl_pedido.fabrica    = $login_fabrica
                                  AND posto     = $posto
                                  AND seu_pedido  LIKE '%$seu_pedido'
                                LIMIT 1";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0) {
                            $pedido_en = pg_fetch_result($res,0,pedido);
                            $tipo_pedido = pg_fetch_result($res,0,descricao);
                            $tem_pedido = "sim";
                        }else{
                            $msg_erro[] = "Pedido não encontrado";
                        }

                        if(empty($nf_embarque)){
                            $msg_erro[] = "Informe Nota Fiscal de Embarque";
                        }
                        if($tipo_emb_peca == 1){

                            $peca_faltante3 = check_post_field('peca_faltante3');

                            if(!count($peca_faltante3)){
                                $msg_erro[] = "Informe as peças";
                            }
                            $pecas_faltam = implode(';',$peca_faltante3);

                            $inf_adicionais = "ARRAY['$erro_emb','Quantidade incorreta','$tipo_emb_peca','$pecas_faltam','".$tipo_pedido."']";

                        } else if($tipo_emb_peca == 2){
                            $peca_faltante3 = check_post_field('peca_faltante3');

                            if(!count($peca_faltante3)){
                                $msg_erro[] = "Informe as peças";
                            }


                            if(!count($msg_erro) AND count($peca_faltante3) > 0){
                                for($i =0;$i<count($peca_faltante3);$i++) {
                                    $sql = "SELECT tbl_peca.referencia,tbl_peca.descricao
                                                FROM tbl_pedido
                                                JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                                LEFT JOIN tbl_faturamento ON tbl_pedido.pedido = tbl_faturamento.pedido AND tbl_faturamento.fabrica = $login_fabrica
                                                LEFT JOIN tbl_faturamento_item  ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                                LEFT JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
                                                LEFT JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.fabrica_i = $login_fabrica AND tbl_os_item.posto_i = $posto
                                                LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
                                                WHERE tbl_pedido.fabrica = $login_fabrica
                                                AND seu_pedido LIKE '%$seu_pedido'
                                                AND tbl_pedido.posto = $posto
                                                AND tbl_peca.referencia = '".$peca_faltante3[$i]."'
                                                AND (tbl_faturamento.nota_fiscal = '$nf_embarque' OR tbl_os_item_nf.nota_fiscal = '$nf_embarque')
                                                AND (tbl_faturamento.emissao = '$data_nf_emb_aux' OR tbl_os_item_nf.data_nf = '$data_nf_emb_aux')";
                                    $res = pg_query($con,$sql);
                                    if(pg_num_rows($res) > 0){
                                        $pecas .=pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao)."<br>";
                                    }else {
                                        $msg_erro[] = "Peça ".$peca_faltante3[$i]." não consta na NF de origem informada.";
                                    }

                                }
                            }
                            $inf_adicionais = "ARRAY['$erro_emb','Peça incorreta','$tipo_emb_peca','".$tipo_pedido."']";
                        } else if($tipo_emb_peca == 3){
                            $inf_adicionais = "ARRAY['$erro_emb','Extravio de mercadoria','$tipo_emb_peca','".$tipo_pedido."']";
                        }
                    } else {
                        $data_nf_emb_prod = check_post_field('data_nf_emb_prod');
                        $nf_embarque_prod = check_post_field('nf_embarque_prod');
                        if($data_nf_emb_prod){
                            list($d, $m, $y) = explode("/", $data_nf_emb_prod);
                            if(!checkdate($m,$d,$y)){
                                $msg_erro[] = "Data fechamento inválida";
                            } else {
                                $data_nf_emb_aux = "$y-$m-$d";
                            }
                        } else{
                            $msg_erro[] = "Informe a data de fechamento";
                        }

                        $seu_pedido  = check_post_field('pedido_emb_prod');

                        $sql = "SELECT pedido,tbl_tipo_pedido.descricao
                                FROM tbl_pedido
                                $join_pedido_emb
                                WHERE tbl_pedido.fabrica    = $login_fabrica
                                  AND posto     = $posto
                                  AND seu_pedido  LIKE '%$seu_pedido'
                                LIMIT 1";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0) {
                            $pedido_en = pg_fetch_result($res,0,pedido);
                            $tipo_pedido = pg_fetch_result($res,0,descricao);
                            $tem_pedido = "sim";
                        }else{
                            $msg_erro[] = "Pedido não encontrado";
                        }

                        if(empty($nf_embarque_prod)){
                            $msg_erro[] = "Informe Nota Fiscal de Embarque";
                        }
                        if($referencia3){
                            $sql = "SELECT tbl_produto.produto
                                        FROM tbl_pedido
                                        JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                                        JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica AND tbl_peca.produto_acabado IS TRUE
                                        LEFT JOIN tbl_pendencia_bd_novo_nf ON tbl_pedido.pedido = tbl_pendencia_bd_novo_nf.pedido_banco
                                        JOIN tbl_os_item ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_os_item.fabrica_i = $login_fabrica
                                        JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
                                        JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                        JOIN tbl_produto ON tbl_peca.referencia = tbl_produto.referencia_fabrica AND tbl_produto.fabrica_i = $login_fabrica AND tbl_produto.produto = tbl_os_produto.produto
                                        WHERE tbl_pedido.fabrica = $login_fabrica
                                        AND seu_pedido LIKE '%$seu_pedido'
                                        AND tbl_pedido.posto = $posto
                                        AND tbl_pedido.troca IS TRUE
                                        AND UPPER(tbl_produto.referencia) = UPPER('$referencia3')
                                        AND (tbl_os_item_nf.nota_fiscal = '$nf_embarque' OR tbl_pendencia_bd_novo_nf.nota_fiscal = '$nf_embarque_prod' )
                                        AND (tbl_os_item_nf.data_nf = '$data_nf_emb_aux' OR tbl_pendencia_bd_novo_nf.data = '$data_nf_emb_aux')";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) > 0){
                                $produto = pg_fetch_result($res,0,produto);

                            } else {
                                $msg_erro[] = "Produto $referencia3 não consta na NF de origem informada.";
                            }
                        }

                        if($tipo_emb_prod == 1){

                            $modelo_enviado  = check_post_field('referencia4');
                            $modelo_enviado_desc  = check_post_field('descricao4');

                            $inf_adicionais = "ARRAY['$erro_emb','Produto incorreto','$tipo_emb_prod','$modelo_enviado - $modelo_enviado_desc;$tipo_pedido']";

                        } else if($tipo_emb_prod == 2){
                            $inf_adicionais = "ARRAY['$erro_emb','Produto faltando acessório','$tipo_emb_prod','".$acess_faltantes_emb.";".$tipo_pedido."']";
                        }else if($tipo_emb_prod == 3){

                            $acess_faltantes_emb  = check_post_field('acess_faltantes_emb');
                            $inf_adicionais = "ARRAY['$erro_emb','Voltagem incorreta','$tipo_emb_prod','".$tipo_pedido."']";

                        } else if($tipo_emb_prod == 4){

                            $produto_faltante  = check_post_field('produto_faltante');
                            $produtos = implode(';',$produto_faltante);
                            $inf_adicionais = "ARRAY['$erro_emb','Quantidade incorreta','$tipo_emb_prod','".$produtos."','".$tipo_pedido."']";
                        }


                    }
                    $nf_embarque_aux = (empty($nf_embarque)) ? $nf_embarque_prod : $nf_embarque;
                    $campos_hd_chamado_extra = ", nota_fiscal, data_nf";
                    $values_hd_chamado_extra = ", '$nf_embarque', '$data_nf_emb_aux'";
                    $campos_hd_chamado_posto = ", inf_adicionais ";
                    $values_hd_chamado_posto = ", $inf_adicionais";

                }
            }

            $respostaLimpa = str_replace("&nbsp;", "", $_POST['resposta']);

            if ((strlen(trim($respostaLimpa)) == 0)  and $txt_linhas == '') {
                $msg_erro[] = '<p>Por favor, digite o texto a ser enviado para o Posto Autorizado!</p>';
            } else if (strlen($_POST['resposta']) <= 15  and ($txt_linhas == '')) {
                $msg_erro[]= '<p>Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser maior!</p>';
            } /* else if(strlen($_POST["resposta"]) > 1000){
                $msg_erro[] = "Para melhor comunicação o Texto Enviado para a Fábrica ou Posto deve ser menor que 1000 caracteres!<br>";
            } */
            $atendente = $categorias[$categoria]['atendente'];
            $atendente = (is_numeric($atendente)) ? $atendente : hdBuscarAtendentePorPosto($posto,$categoria);
  
        }

        if(!count($msg_erro)) {
            $resposta = check_post_field("resposta");
            $res = @pg_query($con,'BEGIN');
            if ( ! is_resource($res) ) {
                $msg_erro[] = "<p>Não foi possível iniciar a transação</p>";
            }
        }

        if(!count($msg_erro) and is_null($hd_chamado)) { // INSERIR NOVO CHAMADO --------------------------
          $atendente          = pg_quote($atendente, true);
          $produto            = pg_quote($produto, true);
          $os_en              = pg_quote($os_en, true);
          $pedido_en          = pg_quote($pedido_en, true);
          $garantia           = pg_quote($garantia);
          $tipo_atualizacao   = pg_quote($tipo_atualizacao);
          $fone               = pg_quote(substr($fone, 0, 20));
          $email              = pg_quote($email);
          $banco              = pg_quote($banco);
          $agencia            = pg_quote($agencia);
          $conta              = pg_quote($conta);
          $nome_cliente       = pg_quote($nome_cliente);
          $hd_chamado_sac     = pg_quote($hd_chamado_sac, true);
          $data_pedido        = pg_quote($data_pedido);
          $peca               = pg_quote($pecas);
          $linha_atendimento  = pg_quote($linha_atendimento);
          $pecas              = pg_quote($pecas);
          $status = "Ag. Posto";

          if ( in_array($login_fabrica, array(11,42,172)) ) {
            if ($_POST["chamado_interno"] == "interno") {
                $status = "Interno";
            }
          }
          //HD 1151239 - Britania não tinha regra ficou a regra que já existia.

          // if($login_fabrica == 3) { // COMENTADO hd_chamado=2570738
          //   $status = "Ag. Fábrica";
          // }

          // HD-2232637
          if($login_fabrica == 1 AND $categoria == "servico_atendimeto_sac"){
            $atendente = $login_admin;
          }

          if ($login_fabrica == 1) {
            $atendente = $login_admin; 
          }
          
          $sql = "INSERT INTO tbl_hd_chamado (hd_chamado,fabrica, fabrica_responsavel, atendente, admin, posto, categoria,status, titulo)
                  VALUES         (DEFAULT,$login_fabrica, $login_fabrica, $atendente, $atendente, $posto, '$categoria', '$status', 'Help-Desk Posto')
                  RETURNING hd_chamado";
          $res = pg_query($con,$sql);
          if(is_resource($res)){
            $hd_chamado = pg_fetch_result($res,0,0);

            if ($login_fabrica == 3) {
              $sql_cod_posto = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
              $res_cod_posto = pg_query($con, $sql_cod_posto);

              $hd_codigo_posto = pg_fetch_result($res_cod_posto, 0, "codigo_posto");

              $seu_hd = (!strlen($os)) ? $hd_codigo_posto.$hd_chamado : $os.$hd_chamado;
            }

            if($login_fabrica == 1 && $categoria == "servico_atendimeto_sac"){
              $sql_protocolo = "SELECT COUNT(hd_chamado) AS qtde_sac FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND categoria = 'servico_atendimeto_sac'";
              $res_protocolo = pg_query($con, $sql_protocolo);

              $qtde_sac = pg_fetch_result($res_protocolo, 0, "qtde_sac");

              $protocolo_cliente = "SAC".str_pad($qtde_sac, 7, "0", STR_PAD_LEFT);

              $sql_update_protocolo = "UPDATE tbl_hd_chamado SET protocolo_cliente = '$protocolo_cliente' WHERE hd_chamado = {$hd_chamado} AND fabrica = {$login_fabrica}";
              $res_update_protocolo = pg_query($con, $sql_update_protocolo);

            }

            // buscando info do posto
            $sql = "SELECT SUBSTR(p.nome, 1, 40) AS nome, p.cnpj, pf.contato_endereco as endereco, pf.contato_numero as numero, pf.contato_complemento as complemento,
                           pf.contato_cep as cep, pf.contato_cidade as cidade, pf.contato_estado as estado, pf.contato_email as email,
                           SUBSTR(pf.contato_fone_comercial, 1, 20) as fone
                    FROM tbl_posto p
                    INNER JOIN tbl_posto_fabrica pf USING (posto)
                    WHERE p.posto = $posto
                      AND pf.fabrica = $login_fabrica";
            $res = @pg_query($con,$sql);
                if(is_resource($res) || pg_num_rows($res) <= 0) {
                    $p      = array_map(pg_quote,pg_fetch_assoc($res));
                    $cidade = buscarCidadeId($p['estado'],$p['cidade']);
                    $cidade = ($cidade !== false) ? pg_quote($sidade, true) : 'NULL';

                    $campos_adicionais = array("usuario_sac" => utf8_encode($usuario_sac), "os_posto" => $os_posto);

                    /*HD - 6065678*/
                    if ($login_fabrica == 1) {
                      if (strlen($usuario_sac) == 0) {
                        $msg_erro[] = "Por favor informar o responsável pela solicitação";
                      } else if ($categoria == "manifestacao_sac") {
                        $aux_hd_chamado_sac = $_POST["hd_chamado_sac"];

                        $aux_sql = "SELECT posto FROM tbl_hd_chamado WHERE hd_chamado = $aux_hd_chamado_sac";
                        $aux_res = pg_query($con, $aux_sql);
                        $aux_posto = pg_fetch_result($aux_res, 0, 'posto');

                        if (strlen($aux_posto) == 0) {
                          $msg_erro[] = "Número de chamado SAC inválido";
                        } else {
                          $array_adicional = $aux_hd_chamado_sac;
                        }
                      } else if (in_array($categoria, array("nova_duvida_pedido", "nova_duvida_pecas", "nova_duvida_produto", "nova_erro_fecha_os", "atualiza_cadastro"))) {
                        if ($categoria == "nova_duvida_pedido") {
                          $duvida_pedido = $_POST["duvida_pedido"];

                          if (strlen($duvida_pedido) == 0) {
                            $msg_erro[] = "Por favor informar qual o tipo de dúvida em relação ao pedido.";
                          } else {
                            if (in_array($duvida_pedido, array("informacao_recebimento", "divergencia_recebimento", "pendencia_peca_fabrica"))) {
                              $sub1_duvida_pedido_numero_pedido = $_POST["sub1_duvida_pedido_numero_pedido"];
                              $sub1_duvida_pedido_data_pedido   = $_POST["sub1_duvida_pedido_data_pedido"];

                              $aux_cont_numero = count($sub1_duvida_pedido_numero_pedido);
                              $aux_cont_data   = count($sub1_duvida_pedido_data_pedido);

                              if ($aux_cont_numero == 0) {
                                $msg_erro[] = "Por favor informar ao menos um número do pedido";
                              } else {
                                for ($z = 0; $z < $aux_cont_numero; $z++) {
                                  $numero_pedido = $sub1_duvida_pedido_numero_pedido[$z];
                                  $data_pedido   = $sub1_duvida_pedido_data_pedido[$z];

                                  if (strlen($numero_pedido) == 0) {
                                    $msg_erro[] = "Por favor informar o pedido";
                                  } else {
                                    $aux_sql  = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
                                    $aux_res   = pg_query($con, $aux_sql);
                                    $aux_posto = pg_fetch_result($aux_res, 0, 'posto');

                                    $aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE seu_pedido = '$numero_pedido' AND posto = {$aux_posto}";
                                    $aux_res = pg_query($con, $aux_sql);

                                    $pedido_id = pg_fetch_result($aux_res, 0, 'pedido');
                                    $aux_data  = pg_fetch_result($aux_res, 0, 'data');

                                    if (strlen($pedido_id) == 0) {

                                      $aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE seu_pedido LIKE '%$numero_pedido%' AND posto = $aux_posto";
                                      $aux_res = pg_query($con, $aux_sql);

                                      $pedido_id = pg_fetch_result($aux_res, 0, 'pedido');
                                      $aux_data  = pg_fetch_result($aux_res, 0, 'data');

                                      if (strlen($pedido_id) == 0) {
                                        $msg_erro[] = "O pedido \"$numero_pedido\" não é válido";
                                      }
                                    }

                                    if (empty($msg_erro) && strlen($sub1_duvida_pedido_data_pedido[$z]) == 0) {
                                      $sub1_duvida_pedido_data_pedido[$z] = $aux_data;
                                    }
                                  }
                                }

                                if (empty($msg_erro)) {
                                  $array_adicional = array();

                                  for ($z = 0; $z < $aux_cont_numero; $z++) { 
                                    $array_adicional[$z]["numero_pedido"] = $sub1_duvida_pedido_numero_pedido[$z];
                                    $array_adicional[$z]["data_pedido"]   = $sub1_duvida_pedido_data_pedido[$z];
                                    $array_adicional[$z]["duvida_pedido"] = $duvida_pedido;
                                  }
                                }
                              }
                            } else if ($duvida_pedido == "pendencia_peca_distribuidor") {
                              $sub2_duvida_pedido_numero_pedido     = $_POST["sub2_duvida_pedido_numero_pedido"];
                              $sub2_duvida_pedido_data_pedido       = $_POST["sub2_duvida_pedido_data_pedido"];
                              $sub2_duvida_pedido_nome_distribuidor = $_POST["sub2_duvida_pedido_nome_distribuidor"];

                              $aux_cont_numero = count($sub2_duvida_pedido_numero_pedido);
                              $aux_cont_data   = count($sub2_duvida_pedido_data_pedido);
                              $aux_cont_nome   = count($sub2_duvida_pedido_nome_distribuidor);

                              if ($aux_cont_numero == 0 || $aux_cont_data == 0 || $aux_cont_nome == 0) {
                                $msg_erro[] = "Por favor informar o número e a data do pedido e o nome do distribuidor";
                              } else {
                                for ($z = 0; $z < $aux_cont_numero; $z++) { 
                                  $numero_pedido     = $sub2_duvida_pedido_numero_pedido[$z];
                                  $data_pedido       = $sub2_duvida_pedido_data_pedido[$z];
                                  $nome_distribuidor =  $sub2_duvida_pedido_nome_distribuidor[$z];

                                  if (strlen($numero_pedido) == 0 || strlen($data_pedido) == 0 || strlen($nome_distribuidor) == 0) {
                                    $msg_erro[] = "Por favor informar o número e a data do pedido e o nome do distribuidor";
                                  } else {
                                    $aux_sql  = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
                                    $aux_res   = pg_query($con, $aux_sql);
                                    $aux_posto = pg_fetch_result($aux_res, 0, 'posto');

                                    $aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE seu_pedido = '$numero_pedido' AND posto = {$aux_posto}";
                                    $aux_res = pg_query($con, $aux_sql);

                                    $pedido_id = pg_fetch_result($aux_res, 0, 'pedido');
                                    $aux_data  = pg_fetch_result($aux_res, 0, 'data');

                                    if (strlen($pedido_id) == 0) {

                                      $aux_sql = "SELECT pedido, posto, TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_pedido WHERE seu_pedido LIKE '%$numero_pedido%' AND posto = $aux_posto";
                                      $aux_res = pg_query($con, $aux_sql);

                                      $pedido_id = pg_fetch_result($aux_res, 0, 'pedido');
                                      $aux_data  = pg_fetch_result($aux_res, 0, 'data');

                                      if (strlen($pedido_id) == 0) {
                                        $msg_erro[] = "O pedido {$numero_pedido} não é válido";
                                      }
                                    }
                                  }
                                }

                                if (empty($msg_erro)) {
                                  $array_adicional = array();

                                  for ($z = 0; $z < $aux_cont_numero; $z++) { 
                                    $array_adicional[$z]["numero_pedido"] = $sub2_duvida_pedido_numero_pedido[$z];
                                    $array_adicional[$z]["data_pedido"]   = $sub2_duvida_pedido_data_pedido[$z];
                                    $array_adicional[$z]["distribuidor"]  = utf8_encode($sub2_duvida_pedido_nome_distribuidor[$z]);
                                    $array_adicional[$z]["duvida_pedido"] = $duvida_pedido;
                                  }
                                }
                              }
                            }
                          }
                        } else if ($categoria == "nova_duvida_pecas") {
                          $duvida_pecas = $_POST["duvida_pecas"];

                          if (strlen($duvida_pecas) == 0) {
                            $msg_erro[] = "Por favor informar qual o tipo de dúvida em relação a peça.";
                          } else {
                            if (in_array($duvida_pecas, array("obsoleta_indisponivel", "substituta", "tecnica", "devolucao"))) {
                              $sub1_duvida_pecas_codigo_peca = $_POST["sub1_duvida_pecas_codigo_peca"];
                              $sub1_duvida_pecas_descricao_peca = $_POST["sub1_duvida_pecas_descricao_peca"];

                              if (empty($sub1_duvida_pecas_codigo_peca) && empty($sub1_duvida_pecas_descricao_peca)) {
                                $msg_erro[] = "Por favor informar ao menos uma peça";
                              } else {
                                $aux_cont_pecas_codigo    = count($sub1_duvida_pecas_codigo_peca);
                                $aux_cont_pecas_descricao = count($sub1_duvida_pecas_descricao_peca);

                                if ($aux_cont_pecas_codigo != $aux_cont_pecas_descricao) {
                                  $msg_erro[] = "Informar o código e a descrição da peça";
                                } else {
                                  $array_adicional = array(); 

                                  for ($z=0; $z < $aux_cont_pecas_codigo; $z++) { 
                                    $array_adicional[$z]["duvida_pecas"]   = $duvida_pecas;
                                    $array_adicional[$z]["codigo_peca"]    = utf8_encode($sub1_duvida_pecas_codigo_peca[$z]);
                                    $array_adicional[$z]["descricao_peca"] = utf8_encode($sub1_duvida_pecas_descricao_peca[$z]);
                                  }
                                }
                              }
                            } else if ($duvida_pecas == "nao_consta_lb_ve") {
                              $sub2_duvida_pecas_descricao_pecas = $_POST["sub2_duvida_pecas_descricao_pecas"];

                              if (empty($sub2_duvida_pecas_descricao_pecas)) {
                                $msg_erro[] = "Por favor informar ao menos uma peça";
                              } else {
                                $aux_cont_pecas  = count($sub2_duvida_pecas_descricao_pecas);
                                $array_adicional = array(); 

                                for ($z=0; $z < $aux_cont_pecas; $z++) {
                                  $array_adicional[$z]["duvida_pecas"] = $duvida_pecas;
                                  $array_adicional[$z]["descricao_peca"] = utf8_encode($sub2_duvida_pecas_descricao_pecas[$z]);
                                }
                              }
                            }
                          }
                        } else if ($categoria == "nova_duvida_produto") {
                          $duvida_produto = $_POST["duvida_produto"];

                          if (strlen($duvida_produto) == 0) {
                            $msg_erro[] = "Por favor informar qual o tipo de dúvida em relação ao pedido.";
                          } else {
                            if (in_array($duvida_produto, array("tecnica", "troca_produto", "produto_substituido", "troca_faturada", "atendimento_sac"))) {
                              $sub1_duvida_produto_codigo_produto    = $_POST["sub1_duvida_produto_codigo_produto"];
                              $sub1_duvida_produto_descricao_produto = $_POST["sub1_duvida_produto_descricao_produto"];

                              $aux_cont_cod_produto  = count($sub1_duvida_produto_codigo_produto);
                              $aux_cont_desc_produto = count($sub1_duvida_produto_descricao_produto);

                              if ($aux_cont_cod_produto == 0 || $aux_cont_desc_produto == 0) {
                                $msg_erro[] = "Por favor informar o código e a descrição do produto";
                              } else {
                                for ($z = 0; $z < $aux_cont_cod_produto; $z++) { 
                                  $codigo_produto    = $sub1_duvida_produto_codigo_produto[$z];
                                  $descricao_produto = $sub1_duvida_produto_descricao_produto[$z];

                                  if (strlen($codigo_produto) == 0 || strlen($descricao_produto) == 0) {
                                    $msg_erro[] = "Por favor informar o código e a descrição do produto";
                                  } else {
                                    $aux_sql = "SELECT produto FROM tbl_produto WHERE (referencia = '$codigo_produto' OR descricao = '". strtoupper($descricao)."') AND fabrica_i = $login_fabrica";
                                    $aux_res = pg_query($con, $aux_sql);

                                    $produto_id = pg_fetch_result($aux_res, 0, 'produto');

                                    if (strlen($produto_id) > 0) {
                                      $aux_produto = pg_fetch_result($aux_res, 0, 'produto');
                                    } else {
                                      $msg_erro[] = "Erro ao localizar o produto informado";
                                    }
                                  }
                                }

                                if (empty($msg_erro)) {
                                  $array_adicional = array();

                                  for ($z = 0; $z < $aux_cont_cod_produto; $z++) { 
                                    $array_adicional[$z]["codigo_produto"]    = utf8_encode($sub1_duvida_produto_codigo_produto[$z]);
                                    $array_adicional[$z]["descricao_produto"] = utf8_encode($sub1_duvida_produto_descricao_produto[$z]);
                                    $array_adicional[$z]["duvida_produto"]    = $duvida_produto;
                                  }
                                }
                              }
                            } else if ($duvida_produto == "nao_consta_lb_ve") {
                              $sub2_duvida_produto_descricao_produto    = $_POST["sub2_duvida_produto_descricao_produto"];
                              $aux_cont_desc_produto                    = count($sub2_duvida_produto_descricao_produto);

                              if ($aux_cont_desc_produto == 0) {
                                $msg_erro[] = "Por favor informar ao menos um produto";
                              } else {
                                $array_adicional = array();

                                for ($z = 0; $z < $aux_cont_desc_produto; $z++) { 
                                  $array_adicional[$z]["descricao_produto"] = utf8_encode($sub2_duvida_produto_descricao_produto[$z]);
                                  $array_adicional[$z]["duvida_produto"]    = $duvida_produto;
                                }
                              }
                            }
                          }
                        } else if ($categoria == "nova_erro_fecha_os") {
                          $sub1_erro_fecha_os_codigo_os = $_POST["sub1_erro_fecha_os_codigo_os"];

                          if (strlen($sub1_erro_fecha_os_codigo_os[0]) == 0) {
                            $msg_erro[] = "Por favor informar a O.S. que está com problemas para fechar";
                          } else {
                            for ($z=0; $z < count($sub1_erro_fecha_os_codigo_os); $z++) { 
                              $aux_os = $sub1_erro_fecha_os_codigo_os[$z];
                              $aux_so = str_replace($codigo_posto, "", $aux_os);

                              $aux_sql  = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $aux_os";
                              $aux_res  = pg_query($con, $aux_sql);
                              $auxiliar = pg_fetch_result($aux_res, 0, 'os');

                              if (strlen($auxiliar) == 0) {
                                $aux_sql  = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
                                $aux_res  = pg_query($con, $aux_sql);
                                $posto_id = pg_fetch_result($aux_res, 0, 'posto');

                                $aux_sql  = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND sua_os = '$aux_so' AND posto = $posto_id";
                                $aux_res  = pg_query($con, $aux_sql);
                                $auxiliar = pg_fetch_result($aux_res, 0, 'os');

                                if (strlen($auxiliar) == 0) {
                                  $msg_erro[] = "O número de O.S. \"$aux_os\" é inválido";
                                }
                              }
                            }

                            if (empty($msg_erro)) {
                              $array_adicional = array();

                              for ($z=0; $z < count($sub1_erro_fecha_os_codigo_os); $z++) { 
                                $array_adicional[$z]["ordem_servico"] = $sub1_erro_fecha_os_codigo_os[$z];
                              }
                            }
                          }
                        } else if ($categoria == "atualiza_cadastro") {
                          $numero_linhas    = count($_POST["linhas"]);
                          $auxiliar         = $_POST["linhas"];
                          $array_adicional = array();

                          $array_linhas = array(
                          "ferramentas_dewalt"       => "Ferramentas DEWALT",
                          "ferramentas_dewalt_black" =>"Ferramentas Black&Decker",
                          "ferramentas_stanley"      => "Ferramentas Stanley",
                          "ferramentas_pneumaticas"  => "Ferramentas Pneumáticas",
                          "compressores"             => "Compressores",
                          "lavadores"                => "Lavadoras",
                          "motores"                  => "Motores",
                          "eletro_protateis"         => "Eletro-portáteis");

                          for ($wx=0; $wx < $numero_linhas; $wx++) {                
                            $array_adicional[] = utf8_encode($array_linhas[$auxiliar[$wx]]);
                          }
                        }
                      }

                      if (strlen($_POST["duvida_pedido"]) > 0) {
                        $campos_adicionais["pedidos"] = $array_adicional;
                      } else if (strlen($_POST["duvida_pecas"]) > 0) {
                        $campos_adicionais["pecas"] = $array_adicional;
                      } else if (strlen($_POST["duvida_produto"]) > 0) {
                        $campos_adicionais["produtos"] = $array_adicional;
                      } else if (strlen($_POST["sub1_erro_fecha_os_codigo_os"][0]) > 0) {
                        $campos_adicionais["ordem_servico"] = $array_adicional;
                      }  else if (strlen($_POST["hd_chamado_sac"]) > 0) {
                        $campos_adicionais["hd_chamado_sac"] = $array_adicional;
                      } else if (strlen($_POST["linhas"][0]) > 0) {
                        $campos_adicionais["linhas"] = $array_adicional;
                      }
                    }

                    if ($login_fabrica == 1) {
                      $campos_adicionais = json_encode($campos_adicionais);
                    } else {
                      $campos_adicionais = addslashes(json_encode($campos_adicionais));
                    }


                    if($login_fabrica == 1 && $admin_sac == true){
                      $sql_produto = "SELECT produto FROM tbl_produto WHERE referencia = '{$referencia}' AND fabrica_i = {$login_fabrica}";
                      $res_produto = pg_query($con, $sql_produto);

                      if(pg_num_rows($res_produto) > 0){
                        $produto = pg_fetch_result($res_produto, 0, "produto");
                      }

                      $sua_os = str_replace($codigo_posto, "", $os);

                      $sql_os = "SELECT tbl_os.os FROM tbl_os
                      JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}'
                      WHERE tbl_os.sua_os = '$sua_os' AND tbl_os.fabrica = {$login_fabrica}";
                      $res_os = pg_query($con, $sql_os);

                      if(pg_num_rows($res_os) > 0){
                        $os_en = pg_fetch_result($res_os, 0, "os");
                      }

                    }
                    if ($login_fabrica == 1 and $pendente_acompanhamento == "pendente_acomp") {
                      $cpd_acomp_extra = ", leitura_pendente";
                      $vpd_acomp_extra = ", 't'";
                    }else{
                      $cpd_acomp_extra = "";
                      $vpd_acomp_extra = "";
                    }
                    //HD-6786812 - Salvar ID do produto e OS na tbl_hd_chamado_externo
                    // INICIO
          $produtoID = "null";
                    if(isset($referencia)){
                      $sqlIDProduto = "SELECT 
                                          produto,
                                          referencia
                                        FROM tbl_produto
                                        WHERE referencia = '{$referencia}'";
                      $resIDProduto = pg_query($con, $sqlIDProduto);
                      $produtoID = pg_fetch_result($resIDProduto, 0, 'produto'); 
                    }

                    $osID = (!empty($_POST['os'])) ? $_POST['os'] : 'null';   
                    
                    //FIM
                    $sql = "INSERT INTO tbl_hd_chamado_extra (".
                                    "hd_chamado, nome, endereco, numero, complemento, cep, fone,
                                    email, cpf,cidade,produto,os,pedido,garantia, array_campos_adicionais $campos_hd_chamado_extra $cpd_acomp_extra
                                ) VALUES (
                                    $hd_chamado,{$p['nome']},{$p['endereco']},{$p['numero']},
                                    {$p['complemento']},{$p['cep']},{$p['fone']},{$p['email']},
                                    {$p['cnpj']}, $cidade, $produtoID, $osID, $pedido_en, $garantia, '$campos_adicionais' $values_hd_chamado_extra $vpd_acomp_extra)";

                    //echo "Enviando consulta <pre>$sql</pre><br>";flush();
                    $res = pg_query($con, $sql);
                    //pg_query($con, 'ROLLBACK');exit;
                    if (is_resource($res)) {
                        if (in_array($categoria, array('atualiza_cadastro','manifestacao_sac','pendencias_de_pecas','pend_pecas_dist','solicitacao_coleta','pagamento_garantia','erro_embarque','solicita_informacao_tecnica','sugestao_critica'))) {
                            if ($login_fabrica == 3) {
                                $campos_hd_chamado_posto .= ", seu_hd ";
                                $values_hd_chamado_posto .= ", '$seu_hd'";
                            }

                            if ($login_fabrica == 1) $linha_atendimento = "''";



                            $sql = " INSERT INTO tbl_hd_chamado_posto
                                        (hd_chamado,tipo,fone,email,nome_cliente,
                                         atendente,banco,agencia,conta,data_pedido,peca_faltante,linha_atendimento,hd_chamado_sac $campos_hd_chamado_posto)
                                    VALUES
                                        ($hd_chamado,$tipo_atualizacao,$fone,$email,$nome_cliente,
                                         $atendente,$banco,$agencia,$conta,$data_pedido,$pecas,
                                         $linha_atendimento, $hd_chamado_sac $values_hd_chamado_posto) RETURNING hd_chamado_posto";
                            $res = pg_query($con,$sql);

                            if($login_fabrica == 42){
                              $hd_chamado_posto = pg_fetch_result($res,0,0);
                              if(count($peca_faltante) > 0) {
                                for($i =0;$i<count($peca_faltante);$i++) {
                                  $sql_peca = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_faltante[$i]' AND fabrica = $login_fabrica";
                  
                                  $res_peca = pg_query($con, $sql_peca);
                                  if(pg_num_rows($res_peca) > 0){
                                    $peca_id = pg_fetch_result($res_peca, 'peca');
                                    $sql_insert = "INSERT INTO tbl_hd_chamado_posto_peca (hd_chamado_posto, peca, data_input) VALUES ($hd_chamado_posto, $peca_id, current_timestamp);";
                                    pg_query($con, $sql_insert);
                                  }
                                }
                              }
                            }
              if ( ! is_resource($res) ) {
                $erro = pg_last_error($con);
                if(strpos($erro, "bl_hd_chamado_posto_hd_chamado_sac_fkey")) {
                    $msg_erro[] = "Nº do chamado SAC não existe";
                }
                                if ($login_fabrica == 1) {
                                  if (count($msg_erro) == 0) {
                                    $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                                  }
                                } else {
                                  $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                                }
                            }
                           
                        } else if ($login_fabrica == 3) {
                            $sql = "INSERT INTO tbl_hd_chamado_posto
                                        (hd_chamado, seu_hd)
                                    VALUES
                                        ({$hd_chamado}, '{$seu_hd}')";
                            $res = pg_query($con, $sql);

                            if ( ! is_resource($res) ) {
                              if ($login_fabrica == 1) {
                                if (count($msg_erro) == 0) {
                                  $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                                }
                              } else {
                                $msg_erro[] = pg_last_error()."Erro ao inserir informações do posto.<br>";
                              }
                            }
                        }

                        
                        if ( !is_null($hd_chamado) && empty($msg_erro) ) {
                            $xresposta = $_POST['resposta'];
                            $xresposta = strip_tags(html_entity_decode($xresposta),$manterHtml);

                            if ($login_fabrica == 1) $txt_linhas = "";
                            $hd_chamado_item = hdCadastrarResposta($hd_chamado, $txt_linhas . $xresposta,false,$tipo_resposta,$login_admin,null);

                            if ($hd_chamado_item) {

                              /* if ( isset($_FILES) && count($_FILES) > 0 && !empty($_FILES['anexo']['name']) ) {
                                  $ok = hdCadastrarUpload('anexo',$hd_chamado_item,$msg_erro);
                                  if ( $ok ) {
                                      $msg_ok[] = "<p>Arquivo anexado com sucesso</p>";
                                  }
                              } */
                              if ($login_fabrica == 3) {
                                $anexo_chave = $_POST["anexo_chave"];
                                
                                //$anexos = $tdocs->getByHashTemp($anexo_chave);
                                if ($anexo_chave != $hd_chamado_item) {
                                  $sql_tem_anexo = "SELECT *
                                                    FROM tbl_tdocs
                                                    WHERE fabrica = $login_fabrica
                                                    AND hash_temp = '{$anexo_chave}'
                                                    AND situacao = 'ativo'";
                                  $res_tem_anexo = pg_query($con, $sql_tem_anexo);
                                  if (pg_num_rows($res_tem_anexo) > 0) {
                                      $sql_update = "UPDATE tbl_tdocs SET
                                                    referencia_id = {$hd_chamado_item},
                                                    hash_temp = NULL,
                                                    referencia = 'hdpostoitem'
                                                    WHERE fabrica = $login_fabrica
                                                    AND situacao = 'ativo'
                                                    AND hash_temp = '{$anexo_chave}'";
                                      $res_update = pg_query($con, $sql_update);
                                      if (strlen(pg_last_error()) > 0) {
                                        $msg_erro["msg"][] = traduz("Erro ao gravar anexos");
                                      }
                                  }
                                }
                              } elseif (isset($_FILES) && count($_FILES) > 0) {
                                $idExcluir = null;

                                if ($_POST['anexo']) {
                                    $_POST['anexo'] = $anexo = stripslashes($_POST['anexo']);
                                    $fileData = json_decode($anexo, true);
                                    $idExcluir =  $fileData['tdocs_id'];
                                }

                                $tDocs   = new TDocs($con, $login_fabrica);

                                for($f = 0; $f < count($_FILES["anexo"]["tmp_name"]); $f++){

                                    if (strlen($_FILES['anexo']['tmp_name'][$f]) > 0) {

                                        $arquivo_anexo = array(
                                                "name"     => $_FILES['anexo']['name'][$f],
                                                "type"     => $_FILES['anexo']['type'][$f],
                                                "tmp_name" => $_FILES['anexo']['tmp_name'][$f],
                                                "error"    => $_FILES['anexo']['error'][$f],
                                                "size"     => $_FILES['anexo']['size'][$f]
                                        );

                                        $anexoID = $tDocs->uploadFileS3($arquivo_anexo, $hd_chamado_item, false, 'hdpostoitem');

                                        // Exclui o anterior, pois não será usado
                                        if ($anexoID) {
                                            // Se ocorrer algum erro, o anexo está salvo:
                                            $_POST['anexo'] = json_encode($tDocs->sentData);
                                            if (!is_null($idExcluir)) {
                                            $tDocs->deleteFileById($idExcluir);
                                            }
                                        } else {
                                            $msg_erro[] = 'Erro ao salvar o arquivo!';
                                        }
                                    }
                                }
                            }else{
                                    if($categoria == 'atualiza_cadastro') {
                                        if(in_array($tipo_atualizacao,array('endereco','razao_social','cnpj','end_cnp_raz_ban'))) {
                                            $msg_erro[] = ($tipo_atualizacao=='cnpj' or $tipo_atualizacao == 'end_cnp_raz_ban') ? "Para esse tipo de alteração é necessário enviar o Novo contrato social, cartão de CNPJ e Sintegra. Gentileza anexar os documentos" : "Para esse tipo de alteração é necessário enviar a Alteração do contrato social. Gentileza anexar o documento";
                                        }
                                    }
                                    if ($categoria == 'falha_no_site') {
                                        $msg_erro[] = 'Quando há um erro de sistema é necessário anexar um <i>print</i> da tela com erro para que possamos verificar.';
                                    }
                                }

                if($login_fabrica == 3 && strlen($defeito_solucao_id) > 0){


                                  $sql_defeito_solucao = "INSERT INTO tbl_dc_solucao_hd (
                                                                                                      fabrica,
                                                                                                      defeito_constatado_solucao,
                                                                                                      hd_chamado,
                                                                                                      data_abertura
                                                                                                      ) VALUES (
                                                                                                      $login_fabrica,
                                                                                                      $defeito_solucao_id,
                                                                                                      $hd_chamado,
                                                                                                      CURRENT_DATE)";
                                  $res_defeito_solucao = pg_query($con, $sql_defeito_solucao);

                                }

                                if(empty($msg_erro)) {
                                    pg_query($con,'COMMIT');

                                    header("Location: helpdesk_cadastrar.php?hd_chamado=$hd_chamado&ok=1");


                                }else{
                                    pg_query($con,"ROLLBACK");
                                    $hd_chamado = "";
                                }
                            } else {
                                $msg_erro[] = "<p>Erro ao inserir uma interação no chamado.</p>";
                                pg_query($con,"ROLLBACK");
                                $hd_chamado = "";
                            }
                        } else {
                            pg_query($con,"ROLLBACK");
                            $hd_chamado = "";
                            extract($_POST);    // As informações não vão mais pro banco
                        }
                    } else {
                        if ($login_fabrica == 1) {
                          if (count($msg_erro) == 0) {
                            $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                          }
                        } else {
                          $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                        }
                    }
                } else {
                  if ($login_fabrica == 1) {
                    if (count($msg_erro) == 0) {
                      $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                    }
                  } else {
                    $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                  }
                }
          } else {
              if ($login_fabrica == 1) {
                if (count($msg_erro) == 0) {
                  $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
                }
              } else {
                $msg_erro[] = "<p>Erro ao inserir informações do posto.</p>";
              }
          }
        }
      }
    } // fim do SWITCH de insert
  } // (fim de envio do POST)
}
// ! Buscar os dados do chamado
if ($login_fabrica == 1 && strlen($hd_chamado) > 0 && strlen($_GET["hd_chamado"]) == 0) {
  $aux_sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado";
  $aux_sql = pg_query($con, $aux_sql);
  $aux_hdc = pg_fetch_result($aux_res, 0, 'hd_chamado');

  if (strlen($aux_hdc) == 0) {
    $hd_chamado = "";
  }
}
if(strlen($hd_chamado) > 0) {
  $sql_valida = "SELECT hd_chamado
                  FROM tbl_hd_chamado
                  WHERE fabrica = {$login_fabrica}
                    AND fabrica_responsavel = {$login_fabrica}
                    AND titulo = 'Help-Desk Posto'
                    AND hd_chamado = {$hd_chamado}
                    AND posto is not null;";
  $res_valida = pg_query($con,$sql_valida);
  // echo nl2br($sql_valida);
  if (pg_num_rows($res_valida) == 0 ) {
    header("Location: menu_callcenter.php");
  }

  $aDados     = hdBuscarChamado($hd_chamado);
  if(count($aDados) == 0 ) {
    $msg_erro[] = "Atendimento não pertence a fábrica";
  }
  $categoria  = $categorias[$aDados['categoria']]['descricao'];
  $tipo       = $a_tipos[$aDados['tipo']];

  $informacao_adicional = $aDados['inf_adicionais'];
  $procurar = array("{","}",'"');
  $informacao_adicional = str_replace($procurar,'',$informacao_adicional);

  if($_GET["ok"] == 1){

    if($login_fabrica == 1 && $admin_sac == true){
      $sql_p = "SELECT protocolo_cliente FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado} AND fabrica = {$login_fabrica}";
      $res_p = pg_query($con, $sql_p);

      if(pg_num_rows($res_p) > 0){
        $protocolo_cliente = pg_fetch_result($res_p, 0, "protocolo_cliente");
      }else{
        $protocolo_cliente = $hd_chamado;
      }

    }
    if ($login_fabrica == 1) {
      $aChamadoblack = (!empty($aDados['hd_chamado_anterior']))?hdChamadoAnterior($hd_chamado,$aDados['hd_chamado_anterior']):$hd_chamado;
    }
    //$msg_ok[]  = "<p>Seu chamado número ".(($login_fabrica == 3) ? $aDados["seu_hd"] : ($login_fabrica == 1 && $admin_sac == true) ? $protocolo_cliente : $hd_chamado)." foi enviado com sucesso.<br/>Favor aguardar retorno do posto!</p>";
    $msg_ok[]  = "<p>Seu chamado número ".(($login_fabrica == 3) ? $aDados["seu_hd"] : ($login_fabrica == 1 && $admin_sac == true) ? $protocolo_cliente : $aChamadoblack)." foi enviado com sucesso.<br/>Favor aguardar retorno do posto!</p>";
  }

  if($informacao_adicional){
    list($subcategoria,$desc_subcategoria,$tipo_subcategoria,$conteudo_adicional) = explode(',',$informacao_adicional);

    switch($categoria){
        //CASE CATEGORIA EMBARQUE
        case 'Erro de embarque':
            $erro_emb = $subcategoria;
            if($erro_emb == "produtos"){
                $tipo_emb_prod = $tipo_subcategoria;
                switch($tipo_emb_prod){ //CASE TIPO EMBARQUE
                    case 1:
                        list($conteudo2,$tipo_pedido) = explode(';',$conteudo_adicional);
                        $titulo2 = "Modelo Enviado";
                    break;

                    case 2:
                        list($conteudo1,$tipo_pedido) = explode(',',$conteudo_adicional);
                        $titulo1 = "Acessório Faltante";
                    break;

                    case 3:
                        $tipo_pedido=$conteudo_adicional;
                    break;

                    case 4:
                        list($conteudo1,$tipo_pedido) = explode(',',$conteudo_adicional);
                        $titulo1 = "Qtde. Enviada";
                    break;
                }
            } else {
                $tipo_emb_peca = $tipo_subcategoria;
                switch($tipo_emb_peca){ //CASE TIPO EMBARQUE
                    case 1:
                        list($conteudo1,$conteudo2) = explode(',',$conteudo_adicional);
                        $titulo1 = "Qtde. enviada";
                    break;
                    case 2:
                    case 3:
                        $tipo_pedido = $conteudo_adicional;
                    break;
                }
            }
        break;

        case 'Solicitação de Informação Técnica':
            list($desc_subcategoria, $adc_subcategoria) = explode(',',$informacao_adicional);

            switch ($desc_subcategoria) {
                case 'vista_explodida':
                    $desc_subcategoria = "Vistas Explodidas";
                break;
                case 'informativo_tecnico':
                    $desc_subcategoria = "Informativo Técnico";
                break;
                case 'esquema_eletrico':
                    $desc_subcategoria = "Esquema Elétrico";
                break;
                case 'procedimento_manutencao':
                    $desc_subcategoria = "Procedimento de Manutenção";
                break;
                case 'analise_garantia':
                    $desc_subcategoria = "Análise de Garantia";
                break;
                case 'manual_usuario':
                    $desc_subcategoria = "Manual de Usuário";
                break;
                case 'outro':
                    $desc_subcategoria = "Outro";
                break;
            }
        break;

        case 'Sugestao, Críticas, Reclamações ou Elogios':
            list($desc_subcategoria) = explode(',',$informacao_adicional);
            switch ($desc_subcategoria) {
                case 'sugestao':
                    $desc_subcategoria = "Sugestões";
                break;
                case 'critica':
                    $desc_subcategoria = "Críticas";
                break;
                case 'reclamacao':
                    $desc_subcategoria = "Reclamações";
                break;
                case 'elogio':
                    $desc_subcategoria = "Elogios";
                break;
            }
        break;

        case 'Solicitação de coleta':
            $solict_coleta = $subcategoria;
            if($solict_coleta == "pecas"){
                $tipo_solict_peca = $tipo_subcategoria;
                switch($tipo_solict_peca){ //CASE TIPO SOLICITAÇÃO DE COLETA
                    case 1:
                        list($conteudo1,$conteudo2,$conteudo3) = explode(',',$conteudo_adicional);
                        $titulo1 = "NF de Venda";
                        $titulo2 = "Data NF Venda";
                        $titulo3 = "Defeito Constatado";
                    break;

                    case 2:
                        list($conteudo1,$conteudo2,$conteudo3) = explode(',',$conteudo_adicional);
                        $titulo1 = "Responsável";
                        $titulo2 = "Motivo Devolução";
                        $titulo3 = "Extrato";
                    break;
                }
            } else {
                $tipo_solict_prod = $tipo_subcategoria;
                switch($tipo_solict_prod){ //CASE TIPO EMBARQUE
                    case 2:
                        $conteudo1 = $conteudo_adicional;
                        $titulo1 = "Responsável";
                    break;
                    case 3:
                        $conteudo1 = $conteudo_adicional;
                        $titulo1 = "Motivo Devolução";
                    break;
                }
            }
        break;

        case 'Pagamento das garantias':
            list($duvida,$desc_subcategoria,$conteudo_adicional) = explode(',',$informacao_adicional);
            switch($duvida){ //CASE PAGAMENTO DE GARANTIAS
                    case 'aprova':
                        $conteudo1 = $conteudo_adicional;
                        $titulo1 = "Data de Fechamento";
                    break;
                    case 'pendente':
                        $conteudo1 = $conteudo_adicional;
                        $titulo1 = "Extrato pendente";
                    break;
                    case 'bloqueado':
                        $conteudo1 = $conteudo_adicional;
                        $titulo1 = "Extrato bloqueado";
                    break;
                    case 'documentos':
                        list($conteudo1,$conteudo2,$conteudo3) = explode(';',$conteudo_adicional);
                        $titulo1 = "Extrato";
                        $titulo2 = "Nº Objeto";
                        $titulo3 = "Data de Envio";
                    break;

                }
        break;

    }
  }

  if(strlen($aDados['status']) > 0){
      $status = $aDados['status'];
      $status = str_replace('Ag.', 'Aguardando', $status);
      $status = str_replace('Acomp.', 'Acompanhamento', $status);
      $status = str_replace('Resp.', 'Resposta ', $status);
      $status = str_replace('Intera', 'interação', $status);
  }

  list($ultima_interacao,$restante) = explode(' ',$aDados['data_ultima_interacao']);

  if(strtotime($ultima_interacao.'+5 days') < strtotime('today') AND $status == "Em Acompanhamento"){
      $status_aux = "EM ACOMPANHAMENTO5";
  } else {
      $status_aux = $status;
  }

  $aChamado = (!empty($aDados['hd_chamado_anterior']))?hdChamadoAnterior($hd_chamado,$aDados['hd_chamado_anterior']):$hd_chamado;
    $title = "Consulta de Chamado do Posto";
    $layout_menu = "callcenter";
    include 'cabecalho.php';
  ?>

  <script src='plugins/jquery.maskedinput_new.js'></script>

  <link rel="stylesheet" href="js/jquery.autocomplete.css" type="text/css" />
  <script type='text/javascript' src='js/jquery.autocomplete.js'></script>
  <script src="https://code.jquery.com/jquery-3.0.0.js"></script>
  <script src="https://code.jquery.com/jquery-migrate-3.0.1.js"></script>
  
  <script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
  <script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
  <link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
  <!-- <script type="text/javascript" src="js/fckeditor/fckeditor.js"></script> -->
  <script src="../plugins/ckeditor_new/ckeditor.js"></script>
  <script type="text/javascript">
    $(window).load(function(){
       if ($("textarea[name=resposta]").attr("name") == "resposta"){
        var aux_toolbar = [
            { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
            { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
            { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
            { name: 'tools', items: ['Maximize'] },
            '/',
            { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike' , '-', 'RemoveFormat'] },
            { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
            { name: 'styles', items: ['Styles', 'Format'] },
            { name: 'font', items: ['TextColor', 'BGColor'] }
          ]; 
          CKEDITOR.replace("resposta", { enterMode : CKEDITOR.ENTER_BR, toolbar : aux_toolbar, uiColor : '#A0BFE0', disableNativeSpellChecker: false, width: '100%' });
          // var oFCKeditor = new FCKeditor( 'resposta' ) ;
          // console.log(oFCKeditor);
          // oFCKeditor.BasePath = "js/fckeditor/" ;
          // oFCKeditor.ToolbarSet = 'Peca' ;
          // oFCKeditor.ReplaceTextarea() ;
          setTimeout(function(){
              $(".cke_button__image").hide();
              $(".cke_button__table").hide();
          },1000);
      }
    });

  $(function(){

    Shadowbox.init();

    function busca_solucao_produto(defeito){

        var produto = $('#produto_hidden').val();

        if(defeito != ""){

            $(".box-solucoes").html("<em>buscando lista de soluções...</em>");

            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: {
                    busca_solucao_produto: true,
                    produto: produto,
                    defeito: defeito
                },
                complete: function (data) {
                    data = data.responseText;
                    $(".box-solucoes").html(data);
                }
            });
        }
    }

    function busca_solucao_produto2(defeito){

        var produto = $('#produto_hidden2').val();

        if(defeito != ""){

            $(".box-solucoes2").html("<em>buscando lista de soluções...</em>");

            $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: {
                    busca_solucao_produto: true,
                    produto: produto,
                    defeito: defeito
                },
                complete: function (data) {
                    data = data.responseText;
                    $(".box-solucoes2").html(data);
                }
            });
        }
    }

    $("#referencia_os").change(function(){
        if($(this).val() == ""){
            $("#descricao_os").val("");
            $("#produto_hidden").val("");
            $("#defeitos_produtos").val("");
            $("#solucoes_produtos").val("");
            $(".box-defeitos").html("");
            $(".box-solucoes").html("");
            $(".box-link").hide();
            CKEDITOR.instances.resposta.setData("");
        }
    });

    $("#descricao_os").change(function(){
        if($(this).val() == ""){
            $("#referencia_os").val("");
            $("#produto_hidden").val("");
            $("#defeitos_produtos").val("");
            $("#solucoes_produtos").val("");
            $(".box-defeitos").html("");
            $(".box-solucoes").html("");
            $(".box-link").hide();
            CKEDITOR.instances.resposta.setData("");
        }
    });

    $("#referencia_os2").change(function(){
        if($(this).val() == ""){
            $("#descricao_os2").val("");
            $("#produto_hidden2").val("");
            $("#defeitos_produtos2").val("");
            $("#solucoes_produtos2").val("");
            $(".box-defeitos2").html("");
            $(".box-solucoes2").html("");
            $(".box-link2").hide();
            CKEDITOR.instances.resposta.setData("");
        }
    });

    $("#descricao_os2").change(function(){
        if($(this).val() == ""){
            $("#referencia_os2").val("");
            $("#produto_hidden2").val("");
            $("#defeitos_produtos2").val("");
            $("#solucoes_produtos2").val("");
            $(".box-defeitos2").html("");
            $(".box-solucoes2").html("");
            $(".box-link2").hide();
            CKEDITOR.instances.resposta.setData("");
        }
    });

    function formatItem(row) {
        return row[2] + " - " + row[1];
    }

    /* Busca pelo Nome */
    $("#produto_referencia_cdd").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#produto_referencia_cdd").result(function(event, data, formatted) {
        $('#produto_referencia_cdd').val(data[2]);
        $('#produto_id_resp').val(data[0]);
        busca_defeitos_produto_resp();
    });

    function fnc_pendente_encerrar_acompanhemento(tipo){
        if (tipo.value == 'encerrar_acomp') {
            $("input[name=pendente_acompanhamento]").attr("checked", false);
        } else {
            $("input[name=encerrar_acompanhamento]").attr("checked", false);
        }
    }

    $("#produto_referencia_cdd").blur(function(){
        var produto_referencia_cdd = $('#produto_referencia_cdd').val();
        $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
                pega_id_produto: true,
                produto: produto_referencia_cdd
            },
            complete:function(data){
                data = $.parseJSON(data.responseText);
                if (data.erro) {
                    alert(data.erro);
                } else {
                    if (data.ok !== $("#produto_id_resp").val()) {
                        $("#produto_hidden_cd").val(data.ok);
                        $("#produto_id_resp").val(data.ok);
                        busca_defeitos_produto_resp();
                    }
                }
            }
        });
    });

        //
        $(document).delegate("#solucao_procedimento_resp","change",function(){
          var r = confirm("Deseja alterar o procedimento da solução?");
          if(r == true){
            var produto = $('#produto_id_resp').val();
            var solucao_id = $('#solucao').val();
            var defeito = $('#defeito').val();
            var procedimento = $('#solucao_procedimento_resp').val();
            if ($('#hd_chamado').val() != null && $('#hd_chamado').val() != 'undefined') {
              var hd_chamado_p = $('#hd_chamado').val();
            }


            $.ajax({
              url: "<?=$_SERVER['PHP_SELF']?>",
              type: "POST",
              data: {
                grava_procedimento_produto: true,
                produto: produto,
                solucao_id: solucao_id,
                defeito: defeito,
                procedimento: procedimento,
                hd_chamado_p: hd_chamado_p
              },
              complete:function(data){
                data = $.parseJSON(data.responseText);
                if (data.erro) {
                  alert(data.erro);
                } else {
                  alert(data.ok);
                  //window.location.reload();
                  //CKEDITOR.instances.resposta.setData(data.procedimento);
                }
              }
            });
          }
          //$("").text("Novo conteudo");//transferir o valor do campo
        });

      })

      function fnc_tipo_atendimento(tipo) {
        //console.log(tipo.value);
        if (tipo.value == 'Resp.Conclusiva') {
          $("input[name=pendente_acompanhamento]").attr("disabled", true);
          $("input[name=pendente_acompanhamento]").attr("checked", false);
        } else {
          $("input[name=pendente_acompanhamento]").attr("disabled", false);
        }
      }

      function fnc_pendente_encerrar_acompanhemento(tipo){
        if (tipo.value == 'encerrar_acomp') {
          $("input[name=pendente_acompanhamento]").attr("checked", false);
        } else {
          $("input[name=encerrar_acompanhamento]").attr("checked", false);
        }
      }

      function pesquisaPosto(campo,tipo){
          var campo = campo.value;
          if (jQuery.trim(campo).length > 2){
              Shadowbox.open({
                  content:    "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
                  player:     "iframe",
                  title:      "Pesquisa Posto",
                  width:      800,
                  height:     500
              });
          }else
              alert("Informar toda ou parte da informação para realizar a pesquisa!");
      }

      var campo_descricao;
      var campo_referencia;
      var campo_voltagem;

      function fnc_pesquisa_produto2 (xdescricao, xreferencia, div, posicao = '') {
        var descricao  = jQuery.trim(xdescricao);
        var referencia = jQuery.trim(xreferencia);

        <?php if ($login_fabrica == 1) { ?>
          descricao       = $(".sub_duvida_produto_descricao_" + posicao).val();
          referencia      = $(".sub_duvida_produto_referencia_" + posicao).val();
          var url_posicao = "&posicao=" + posicao;

          if (descricao == undefined && referencia == undefined) {
            var referencia  = $("#referencia").val();
            var descricao   = $("#descricao").val();
          }

          var url_posicao = "&posicao=" + posicao;
          Shadowbox.open({
              content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
              player: "iframe",
              title:      "Pesquisa Produto",
              width:  800,
              height: 500
          });
        <?php } else { ?>
          descricao         = $("input[name='"+xdescricao+"']").val();
          referencia        = $("input[name='"+xreferencia+"']").val();
          var url_posicao = "";
          console.log(referencia)
          if (descricao.length > 2 || referencia.length > 2){
            campo_descricao = xdescricao;
            campo_referencia = xreferencia;

            if (div != undefined && div == "div") {
              campo_voltagem = $(campo_descricao).parent("div").find("input[name=voltagem]");
            } else {
              campo_voltagem = $(campo_descricao).parent("td").parent("tr").find("input[name=voltagem]");
            }

            Shadowbox.open({
              content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
              player: "iframe",
              title:      "Pesquisa Produto",
              width:  800,
              height: 500
            });
          }else{
            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
          }
        <?php } ?>
      }
        <?php if($login_fabrica == 42){?>
          function fnc_pesquisa_produto_makita (campo, campo2, tipo) {
            if (tipo == "referencia" ) {
              var xcampo = campo;
            }
          
            if (tipo == "descricao" ) {
              var xcampo = campo2;
            }
          
            if (xcampo.value != "") {
              var url = "";
              url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=/assist/comunicado_mostra_test.php?tipo=Descritivo%20t%E9cnico";
              janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
              janela.referencia   = campo;
              janela.descricao    = campo2;
              
              janela.focus();
            }

            else{
              alert("Preencha toda ou parte da informação para realizar a pesquisa!");
            }
          }
      <?php } ?>

      function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
        gravaDados('codigo_posto',codigo_posto);
        gravaDados('nome_posto',nome);
      }

      function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao) {
        
      <?php if ($login_fabrica == 1) { ?>
        if (posicao == undefined || posicao == "undefined" || posicao == "") {
          $("#referencia").val(referencia);
          $("#descricao").val(descricao);
        } else {
          $(".sub_duvida_produto_referencia_" + posicao).val(referencia);
          $(".sub_duvida_produto_descricao_" + posicao).val(descricao);
        }
      <?php } else { ?>
        $('input[name=produto_hidden]').val(produto);
        $('input[name=descricao_os]').val(descricao);
        $('input[name=referencia_os]').val(referencia);
      <?php } ?>

        busca_defeitos_produto();

        $('.link').html(' <a href="cadastro_defeitos_solucoes.php?produto_referencia='+referencia+'" target="_blank">Cadastrar / Editar Dúvidas e Soluções para Produtos</a>');
      }

      function busca_defeitos_produto(){
        var produto = $('#produto_hidden').val();
        if(produto != ""){
          $(".box-defeitos").html("<em>buscando lista de defeitos...</em>");
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_defeito_produto: true,
              produto: produto
            },
            complete: function (data) {
              data = data.responseText;
              if(data == ""){
                $(".box-defeitos").html("Defeitos não cadastrados para esse produto.");
              }else{
                $(".box-defeitos").html(data);
                var produto_ref = $('#referencia_os').val();
                $("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+produto_ref);
                $('.box-link').show();
              }
            }
          });
        }else{
          $(".box-defeitos").html("");
        }
        //$("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+produto);
        $('.box-link').show();
      }

      function busca_solucao_produto(defeito){
        var produto = $('#produto_hidden').val();
        if(defeito != ""){
          $(".box-solucoes").html("<em>buscando lista de soluções...</em>");
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_solucao_produto: true,
              produto: produto,
              defeito: defeito
            },
            complete: function (data) {
              data = data.responseText;
              $(".box-solucoes").html(data);
            }
          });
        }
      }

      function busca_solucao_produto2(defeito){
        var produto = $('#produto_hidden2').val();
        if(defeito != ""){
          $(".box-solucoes2").html("<em>buscando lista de soluções...</em>");
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_solucao_produto: true,
              produto: produto,
              defeito: defeito
            },
            complete: function (data) {
              data = data.responseText;
              $(".box-solucoes2").html(data);
            }
          });
        }
      }

      function busca_resposta_padrao(defeito_solucao){
        if(defeito_solucao != ""){
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_resposta_padrao: true,
              defeito_solucao: defeito_solucao
            },
            complete: function (data) {
              $("#utilizar_resposta").val("");
              $(".box-utilizar-resposta").hide();
              data = $.parseJSON(data.responseText);
              if(data.status == true){
                var r = confirm("Existe uma respota padrão para essa Solução, deseja inserir como resposta do Chamado?");
                if(r == true){
                  CKEDITOR.instances.resposta.insertText(data.procedimento);
                }else{
                  CKEDITOR.instances.resposta.setData("");
                  $(".box-utilizar-resposta").show();
                }
              }else{
                CKEDITOR.instances.resposta.setData("");
                $(".box-utilizar-resposta").show();
              }
            }
          });
        }
      }

      function busca_defeitos_produto_resp(){
        var produto = $('#produto_id_resp').val();
        if(produto != ""){
          $(".box-defeitos-resp").html("<em>buscando lista de defeitos...</em>");
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_defeito_produto_resp: true,
              produto: produto
            },
            complete: function (data) {
              data = data.responseText;
              if(data == ""){
                $(".box-defeitos-resp").html("Defeito não cadastrado para esse produto.");
                $(".box-solucao-resp").html("");
                $(".box-solucao-resp-titulo").html("");
                $(".box-procedimento-resp").html("");
              }else{
                $(".box-defeitos-resp").html(data);
                $(".box-solucao-resp").html("");
                $(".box-solucao-resp-titulo").html("");
                $(".box-procedimento-resp").html("");
              }
            }
          });
        }else{
          $(".box-defeitos-resp").html("");
          $(".box-solucao-resp").html("");
          $(".box-solucao-resp-titulo").html("");
          $(".box-procedimento-resp").html("");
        }
      }

      function busca_solucao_produto_resp(defeito){
        var produto = $('#produto_id_resp').val();
        if(defeito != ""){
          var defeito = $('#defeito').val();
        }

        if(defeito != ""){

          $(".box-box-solucao-resp").html("<em>buscando lista de soluções...</em>");

          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_solucao_produto_resp: true,
              produto: produto,
              defeito: defeito
            },
            complete: function (data) {
              data = data.responseText;
              if(data == ""){
                $(".box-solucao-resp").html("Solução não cadastrada para esse produto.");
                $(".box-solucao-resp-titulo").html("<strong>Solução</strong>");
                $(".box-procedimento-resp").html('');
              }else{
                $(".box-solucao-resp").html(data);
                $(".box-solucao-resp-titulo").html("<strong>Solução</strong>");
                $(".box-procedimento-resp").html('');
              }
            }
          });
        }
      }

      function busca_procedimento_produto_resp(solucao_id, defeito_solucao){
        var produto = $('#produto_id_resp').val();
        if(solucao_id != ""){
          $(".box-procedimento-resp").html("<em>buscando procedimentos...</em>");
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_procedimento_produto_resp: true,
              defeito_solucao: defeito_solucao
            },
            complete:function(data){
              data = data.responseText;
              $(".box-procedimento-resp").html(data);
              //CKEDITOR.instances.resposta.setData(data);
            }
          });
        }
      }

      function busca_resposta_padrao_resp(defeito_solucao){
        if(defeito_solucao != ""){
          $.ajax({
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            data: {
              busca_resposta_padrao: true,
              defeito_solucao: defeito_solucao
            },
            complete: function (data) {
              $("#utilizar_resposta").val("");
              $(".box-utilizar-resposta").hide();

              data = $.parseJSON(data.responseText);
              if(data.status == true){
                var r = alert("Existe uma resposta padrão para essa Solução:\n\n"+data.procedimento +"\n\n");
                // var r = alert("Existe uma resposta padrão para essa Solução:\n\n"+data.procedimento +"\n\nDeseja inserir como resposta do Chamado?");
                // if(r == true){
                  // CKEDITOR.instances.resposta.insertText(data.procedimento);
                  // }else{
                    // CKEDITOR.instances.resposta.setData("");
                    // $(".box-utilizar-resposta").show();
                    // }
              }
                  // }else{
                    //   CKEDITOR.instances.resposta.setData("");
                    //   $(".box-utilizar-resposta").show();
                    // }
            }
          });

        }
      }
  </script>

  <script type="text/javascript">
    $(document).ready(function(){
      var login_fabrica = "<?php echo $login_fabrica; ?>";
      var atendente_sap = "<?php echo $atendente_sap['admin_sap']; ?>";
      if (login_fabrica == 1 ) {
        if (atendente_sap== 't')  {
          $('#interações').attr('style','background-color: #D9E2EF');
        }
        <?php
        if($admin_sac == false){
          ?>
          if (atendente_sap == 'f'){
            $('#interações').attr('style','display:none');
            $('#btnEnviar').attr('style','display:none');
          }
          <?php
        }
        ?>
      }
    });

    $(function(){
      $('form').submit(function(){
        $('#multi_admin option').attr('selected','selected');
      });
      $('#cat_txt').dblclick(function () {
        $('#cat_txt').hide();
        $('#cat_sel').show().removeAttr('disabled');
        $('#categoria').removeAttr('disabled');
      });
      $('#cat_sel').dblclick(function () {
        $('#cat_txt').show();
        $('#cat_sel').hide().attr('disabled','disabled');
        $('#categoria').attr('disabled','disabled');
      }).change(function () {
       $('#categoria').val($(this).val());
      });
      $('#cat_sel').change(function(){ // HD 281195
        /*<?php if ($login_fabrica == 3) { ?>
                var  cat_array_cat = ['duvida_tecnica_informatica', 'duvida_tecnica_eletro_pessoal_refri', 'duvida_tecnica_celular', 'duvida_tecnica_audio_video'];
                if(jQuery.inArray($("#cat_sel").val(), cat_array_cat) !== -1) {
                  $("#info_produto_os_2").show();
                } else {
                  $("#info_produto_os_2").hide();
                  $(".box-link").hide();
                  $(".esconde").hide();
                }            
        <?php } ?>*/
        var atendente = $('#cat_sel').val();
        //alert(atendente);
        $("#atendente").load("<?php echo $PHP_SELF . '?hd_chamado=' . $aDados['hd_chamado'] . '&busca_atendente=' ?>" + atendente);
      });
    });

    function addAdmin(){
      var valor = $('#transferir option:selected').val();
      var texto = $('#transferir option:selected').text();

      if (valor.length > 0) {
        $('#multi_admin').append("<option value='"+valor+"'>"+ texto+"</option>");
        $('#transferir option:selected').remove();
      }
    }

    function delAdmin(){
      var valor = $('#multi_admin option:selected').val();
      var texto =  $('#multi_admin option:selected').text();

      if (valor.length > 0) {
        $('#transferir').append("<option value='"+valor+"'>"+ texto+"</option>");
        $('#multi_admin option:selected').remove();
      }

    }

    function checaInterno(){
      if (!$('#chamado_interno').attr('checked')){
        $('#chamado_interno').attr('checked',true);
      }
    }
  </script>

  <style type="text/css">
    #container {
      text-align: center;
      width: 750px;
      margin: 0 auto;
    }
    
    #container div, #container p, #container td {
      font-family:normal normal 10px/14px Verdana,Geneva,Arial,Helvetica,sans-serif;
      font-size-adjust:none;
      text-align:center;
    }

    #container table.resposta {
      border:#485989 1px solid;
      background-color: #A0BFE0;
      margin-bottom: 10px;
    }

    .text-left, .text-left * {
      text-align: left !important;
    }

    .box, .border {
      border-width: 1px;
      border-style: solid;
    }

    .box {
      display: block;
      margin: 0 auto;
      width: 100%;
    }

    .azul {
      border-color: #1937D9;
      background-color: #D9E2EF;
    }

    .msg {
      padding: 10px;
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .error {
      border-color: #cd0a0a;
      background-color: #fef1ec;
      color: #cd0a0a;
      width: 700px;
    }

    .label2 {
        width: 20%;
    }

    .label {
      width: 25%;
      text-align:right!important;
      padding-right: 1ex;
    }

    .dados:hover {
      white-space: normal;
    }

    .dados {
      width: 210px;
      border-width: 1px;
      text-align:left!important;
      padding-left: 1ex;
      _zoom:1;
      display:inline-block;
      overflow:hidden;
      white-space:nowrap;
      text-overflow:ellipsis;
      -o-text-overflow:ellipsis;
    }

    #peca_faltante {
      width: 540px;
    }

    #peca_faltante2 {
      width: 340px;
    }

    #peca_faltante3 {
      width: 340px;
    }
  </style>

  <div id="container">

    <?php if (count($msg_erro)) { ?>
      <div class="box msg error"><?php echo implode('<br>',$msg_erro) . pg_last_error($con); ?></div>

    <?php } ?>

    <?php if (count($msg_ok)) { ?>
      <div class="box msg azul"><?php echo implode('<br>',$msg_ok); ?></div>
    <?php } ?>

    <?php if ( ! empty($hd_chamado) ) { ?>
      <p> &nbsp; </p>
      <style>
        .label_new {
          background-color: #D9E2EF;
          border: 1px #1937D9 solid;
          line-height: 28px;
          text-align: right !important;
        }

      .dados_new {
        background-color: #FFF;
        border: 1px #000 solid;
        line-height: 28px;
        text-align: left !important;
      }
    </style>

      <table style="border: 1px #000 solid; margin: auto; width: 750px;">
          <tr>
              <td class="label_new">
                  Posto
              </td>
              <td class="dados_new">
                  <a href="posto_login.php?posto=<?=$aDados['posto']?>" target="_blank" title="Consultar e logar como este posto" >
                      <?=$aDados["codigo_posto"]." - ".$aDados["posto_nome"]?>
                  </a>
              </td>
              <td class="label_new">
                  Recebe Peça em Garantia
              </td>
              <td class="dados_new">
                  <?=(($aDados["reembolso_peca_estoque"] == "t") ? "Sim" : "Não")?>
              </td>
          </tr>
          <tr>
              <td class="label_new">
                  Abertura
              </td>
              <td class="dados_new">
                  <?=$aDados["data"]?>
              </td>
              <td class="label_new">
                  Chamado
              </td>
              <td class="dados_new">
               <?php
                if($login_fabrica == 1){
                  echo (strlen($aDados["protocolo_cliente"]) > 0 && $aDados["categoria"] == "servico_atendimeto_sac") ? $aDados["protocolo_cliente"] : $aChamado;
                }else{
                  echo ($login_fabrica == 3 && !empty($aDados["seu_hd"])) ? $aDados["seu_hd"] : $aChamado;
                }
                ?>
              </td>
          </tr>
          <tr>
              <td class="label_new">
                  Status
              </td>
              <td class="dados_new">
                  <?
                  $img = $status_array[strtoupper($status_aux)];
                  ?>
                  <img src="imagens_admin/<?=$img?>" />
                  <?=$status?>
              </td>
              <td class="label_new">
                  Atendente
              </td>
              <td class="dados_new">
                  <?=$aDados["atendente_ultimo_nome"]?>
              </td>
          </tr>
          <tr>
              <td class="label_new">
                  Tipo de Solicitação
              </td>
              <td class="dados_new">

                <?php

                  if($login_fabrica == 1 && $admin_sac == true){
                    $disabled = "disabled readonly='readonly'";
                  }

                ?>

                  <select class="frm" name="categoria" id="cat_sel" style="width: 200px;" <?=$disabled?>>
                      <?
                        if ($login_fabrica == 1 && strlen($_GET["hd_chamado"]) > 0) {
                          $aux_sql = "SELECT categoria FROM tbl_hd_chamado WHERE hd_chamado = " . $_GET["hd_chamado"];
                          $aux_res = pg_query($con, $aux_sql);
                          $aux_cat = pg_fetch_result($aux_res, 0, 'categoria');

                          $categorias["nova_duvida_pecas"]["descricao"]   = "Dúvida sobre peças";
                          $categorias["nova_duvida_pedido"]["descricao"]  = "Dúvidas sobre pedido";
                          $categorias["nova_duvida_produto"]["descricao"] = "Dúvidas sobre produtos";
                          $categorias["nova_erro_fecha_os"]["descricao"]  = "Problemas no fechamento da O.S.";
                          $categorias["advertencia"]["descricao"]         = "Advertência";
                        }


                        foreach ($categorias as $cat => $cat_info) {
                          if ($login_fabrica == 1 && $cat <> $aux_cat && !in_array($cat, array("atualiza_cadastro", "manifestacao_sac", "nova_duvida_pecas", "nova_duvida_pedido", "nova_duvida_produto", "falha_no_site", "pagamento_antecipado", "pagamento_garantia", "nova_erro_fecha_os", "satisfacao_90_dewalt", "advertencia"))) {
                            continue;
                          }

                          if ($login_fabrica == 1 && $cat == "pagamento_garantia") {
                            $cat_info["descricao"] = "Pagamento das garantias/Financeiro";
                          }

                          $selected = "";

                          if ($cat_info["no_fabrica"]) {
                              if (in_array($login_fabrica, $cat_info["no_fabrica"])) {
                                  continue;
                              }
                          }
                          
                          if ($cat_info["descricao"] == $categoria) { 
                            $selected = "SELECTED";
                          }

                          if ($login_fabrica == 1 && strlen($_GET["hd_chamado"]) > 0 && strlen($selected) == 0) {
                            if ($cat == $aux_cat) {
                              $selected = "SELECTED";
                            }
                          }
                          echo "<option value='$cat' $selected>{$cat_info['descricao']}</option>";
                      }
                      ?>
                  </select>
              </td>
              <?php if ($login_fabrica == 1 && in_array($aDados['categoria'], array("nova_duvida_pedido", "nova_duvida_pecas", "nova_duvida_produto", "nova_erro_fecha_os", "manifestacao_sac", "atualiza_cadastro"))) {
                echo '</tr>';
                  $aux_sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado";
                  $aux_res = pg_query($con, $aux_sql); 
                  $array_campos_adicionais = json_decode(pg_fetch_result($aux_res, 0, 'array_campos_adicionais'), true);

                  if ($aDados['categoria'] == "nova_duvida_pedido") {
                    $hd_pedidos = $array_campos_adicionais["pedidos"]; ?>
                    <tr>
                      <td class="label_new">Pedido(s)</td>
                      <td class="dados_new">

                        <?php foreach ($hd_pedidos as $key => $value) {
                          if (isset($value["distribuidor"]) && !empty($value["distribuidor"])) {
                            $label_distribuidor = "<br>" . utf8_decode($value["distribuidor"]);
                          } else {
                            $label_distribuidor = "";
                          }
                          echo $label_distribuidor . "<br>";

                          $aux_sql   = "SELECT pedido FROM tbl_pedido WHERE seu_pedido LIKE '%" . $value["numero_pedido"] . "%' AND posto = " .$aDados['posto'];
                          $aux_res   = pg_query($con, $aux_sql);
                          $pedido_id = pg_fetch_result($aux_res, 0, 'pedido');

                          ?> <a href='pedido_admin_consulta.php?pedido=<?=$pedido_id;?>' target='_blank'><?=$value["numero_pedido"];?></a> - <?=$value["data_pedido"];?><br> <?
                        } ?>
                      </td>
                    </tr>
                  <?php } else if ($aDados['categoria'] == "nova_duvida_pecas") {
                    $hd_pecas = $array_campos_adicionais["pecas"];?>
                    <tr>
                      <td class="label_new">Peça(s)</td>
                      <td class="dados_new">
                        <?php foreach ($hd_pecas as $key => $value) {
                          if (strlen($value["codigo_peca"]) > 0) {
                            echo utf8_decode($value["codigo_peca"]) . " - " . utf8_decode($value["descricao_peca"]) . "<br><br>";
                          } else if (strlen($value["descricao_peca"]) > 0) {
                            echo utf8_decode($value["descricao_peca"] . "<br>");
                          }
                        } ?>
                      </td>
                    </tr>
                  <?php } else if ($aDados['categoria'] == "nova_duvida_produto") {
                    $hd_produtos = $array_campos_adicionais["produtos"];?>
                    <tr>
                      <td class="label_new">Produto(s)</td>
                      <td class="dados_new">
                        <?php foreach ($hd_produtos as $key => $value) {
                          if (strlen($value["codigo_produto"]) > 0) {
                            echo utf8_decode($value["codigo_produto"]) . " - " . utf8_decode($value["descricao_produto"]) ."<br>";
                          } else {
                            echo utf8_decode($value["descricao_produto"]) ."<br>";
                          }
                        } ?>
                      </td>
                    </tr>
                  <?php } else if ($aDados['categoria'] == "nova_erro_fecha_os") {
                    $hd_osss = $array_campos_adicionais["ordem_servico"];?>
                    <tr>
                      <td class="label_new">O.S.(s)</td>
                      <td class="dados_new">
                        <?php foreach ($hd_osss as $key => $value) {
                          echo $value["ordem_servico"] . "<br>";
                        } ?>
                      </td>
                    </tr>
                  <?php } else if ($aDados['categoria'] == "manifestacao_sac") {
                    $hd_sac = $array_campos_adicionais["hd_chamado_sac"];?>
                    <tr>
                      <td class="label_new">Nº do chamado SAC</td>
                      <td class="dados_new">
                        <a href="helpdesk_cadastrar.php?hd_chamado=<?=$hd_sac;?>&ok=1" target="_blank"><?=$hd_sac;?></a>
                      </td>
                    </tr>
                  <?php } else if ($aDados['categoria'] == "atualiza_cadastro") {
                    $linhas = $array_campos_adicionais["linhas"];?>
                    <tr>
                      <td class="label_new"><?=($login_fabrica == 1) ? "Atender as linhas..."  : "Gostaria atender as linhas...";?></td>
                      <td class="dados_new">
                        <?php foreach ($linhas as $linha) {
                          echo utf8_decode($linha) . "<br>";
                        } ?>
                      </td>
                    </tr>
                  <?php }
                  }
                if ($aDados["categoria"] <> "atualiza_cadastro" && $login_fabrica != 1) { ?>
                  <td class="label_new">
                      Produto em Garantia
                  </td>
                  <td class="dados_new">
                      <?=(($aDados["garantia"] == "t") ? "Sim" : "Não")?>
                  </td>
                </tr>
              <? } ?>
          <?php
          if ($login_fabrica == 3 and $aDados['categoria'] == 'soliticacao_lgr') {
              $array_campos_adicionais = json_decode($aDados['array_campos_adicionais'], true);

              if (array_key_exists('nf_lgr', $array_campos_adicionais)) {
                  $nf_lgr = $array_campos_adicionais['nf_lgr'];
                  $nf_lgr_link = '';

          if (array_key_exists('notas', $nf_lgr)) {
            $tDocs = new TDocs($con, $login_fabrica);
            $nf_lgr_anexo = $tDocs->getDocumentsByRef($hd_chamado, 'hdposto')->attachListInfo;

            if (!empty($nf_lgr_anexo)) {
              $key = key($nf_lgr_anexo);
              $nf_lgr_link = $nf_lgr_anexo[$key]['link'];
            }
          }

                  $csv_name = substr(md5($login_admin . $hd_chamado), 0, 6) . '.xls';
                  flush();

                  $fp = fopen ("xls/".$csv_name,"w");

                  fputs($fp,"<!DOCTYPE html>");
                  fputs($fp,"<html>");
                    fputs($fp,"<head>");                      
                      fputs($fp,"<title>RELATÓRIO - $data </title>");
                    fputs($fp,"</head>");

                    fputs($fp,"<body>");
                      fputs($fp,"<table>");
                      fputs($fp,"<tr>");
                        fputs($fp,"<td colspan='4'  align='center' colspan='4' bgcolor='#ff0000' ><font size='3' color='#FFDEAD'> <b> AUTORIZAÇÃO DE COLETA E DEVOLUÇÃO:".mostra_data(substr($aDados['data_abertura'],0,10))."</b> </font> </td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                      fputs($fp,"<td  align='center' colspan='4' bgcolor='#ff0000' ><font size='3' color='#FFDEAD'> AUT NR: </font></td>");
                      fputs($fp,"<td></td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                      fputs($fp,"<td colspan='4'  align='center' colspan='4' bgcolor='#ff0000'> <font size='3' color='#FFDEAD'> <b> Logística Reversa - Notas Fiscais Para Devolução</b> </font> </td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='4'></td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                      fputs($fp,"<td>NÚMERO DA NOTA</td>");
                      fputs($fp,"<td>DATA DE EMISSÃO</td>");
                      fputs($fp,"<td>POSTO</td>");
                      fputs($fp,"<td>TRANSPORTADORA</td>");
                      fputs($fp,"</tr>");

                      foreach ($nf_lgr['notas'] as $notas) {
                          fputs($fp,"<tr>");
                             fputs($fp,"<td>".$notas['nf']."</td>");
                             fputs($fp,"<td>". $notas['emissao'] ."</td>");
                             fputs($fp,"<td>". $aDados['codigo_posto']."</td>");
                             fputs($fp,"<td></td>");                              
                          fputs($fp,"</tr>");

                          $csv_content_adicional .= "<tr><td>".$aDados['codigo_posto']. "</td><td>". $aDados['hd_chamado']. '</td><td></td><td>'.$notas['nf']. "</td>";
                      }
                      fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='4'></td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Quantidade total de caixas: </td><td>".$nf_lgr['qtde_caixas']."</td>");
                      fputs($fp,"</tr>");
                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Peso aproximado das caixas em KG:  </td><td>".$nf_lgr['peso_caixas']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='4'></td>");
                      fputs($fp,"</tr>");

                       fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='2'>Dados para a coleta</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Razão Social:  </td><td>".$nf_lgr['razao_social']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>CNPJ:  </td><td>".$nf_lgr['cnpj']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Endereço:  </td><td>".utf8_decode($nf_lgr['endereco'])."</td><td>Número:</td><td>".$nf_lgr['endereco_numero']."</td>" );
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Bairro:  </td><td>".$nf_lgr['bairro']."</td><td>CEP:</td><td>".$aDados['contato_cep']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Cidade:  </td><td>".$nf_lgr['cidade']."</td><td>Estado:</td><td>".$nf_lgr['estado']."</td>");
                      fputs($fp,"</tr>");
                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Responsável pela coleta:  </td><td>".$nf_lgr['responsavel_coleta']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Telefone:  </td><td>".$nf_lgr['telefone']."</td><td>E-mail:</td><td>".$nf_lgr['email']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td>Número do Chamado:  </td><td>".$aDados['hd_chamado']."</td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='4'></td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"<tr>");
                      fputs($fp,"<td>COD.PA</td>");
                      fputs($fp,"<td>NºCHAMADO</td>");
                      fputs($fp,"<td>COD.ENTENDIMENTO</td>");
                      fputs($fp,"<td>Nº NOTA DEV</td>");                      
                      fputs($fp,"<td>TRANSPORTADORA</td>");
                      fputs($fp,"</tr>");                      
                      fputs($fp, $csv_content_adicional);


                      fputs($fp,"<tr>");
                         fputs($fp,"<td colspan='4'></td>");
                      fputs($fp,"</tr>");

                      fputs($fp,"</table>");
                    fputs($fp,"</body>");
                  fputs($fp,"</html>");
                  
                  fclose ($fp);

                  echo '<tr>
                            <td class="label_new">
                                Arquivos
                            </td>
                            <td class="dados_new" colspan="3">
                                <a href="xls/' . $csv_name . '" target="_blank" >
                                    <img src="../helpdesk/imagem/clips.gif" alt="Baixar XLS" />
                                    Baixar CSV
                                </a>';

                            if (!empty($nf_lgr_link)) {
                                echo '&nbsp;&nbsp;&nbsp;
                                <a href="' . $nf_lgr_link . '" target="_blank" >
                                    <img src="../helpdesk/imagem/clips.gif" alt="Baixar ZIP" />
                                    Baixar ZIP
                                </a>';
                            }

                            echo '
                        </tr>';
              }

          }
          ?>
          <?
          $array_subcategoria = array("erro_embarque", "solicitacao_coleta", "pagamento_garantia", "solicita_informacao_tecnica", "sugestao_critica");

          if (in_array($aDados["categoria"], $array_subcategoria) && $login_fabrica != 1) {
          ?>
              <tr>
                  <td class="label_new">
                      Subcategoria
                  </td>
                  <td class="dados_new">
                      <?=$desc_subcategoria?>
                  </td>
                  <td class="label_new" colspan="2">
                      &nbsp;
                  </td>
              </tr>
          <?
          }

          if ($login_fabrica == 42 and $aDados["categoria"] == "solicita_informacao_tecnica" and $desc_subcategoria == "Outro") {
          ?>
              <tr>
                  <td class="label_new">
                      Outro
                  </td>
                  <td class="dados_new">
                      <?=$adc_subcategoria?>
                  </td>
                  <td class="label_new" colspan="2">
                      &nbsp;
                  </td>
              </tr>
          <?
          }

        if ($aDados["categoria"] <> "atualiza_cadastro" && $login_fabrica != 1) { ?>
      <?php
      $defeitos = false;

      if ($login_fabrica == 3 && in_array($aDados['categoria'], ['duvida_tecnica_informatica', 'duvida_tecnica_eletro_pessoal_refri', 'duvida_tecnica_celular', 'duvida_tecnica_audio_video'])) {

          $defeitos = true;
          
          $xsql_defeito_solucao_desc = "SELECT
                                    tbl_solucao.descricao AS solucao,
                                    tbl_defeito_constatado.descricao AS defeito_constatado,
                                    tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                                    tbl_defeito_constatado_solucao.defeito_constatado_solucao As dc_solucao
                                FROM tbl_dc_solucao_hd
                                JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                WHERE tbl_dc_solucao_hd.hd_chamado = $1";
          pg_prepare($con, 'defeito_solucao', $xsql_defeito_solucao_desc);
          $xres_defeito_solucao_desc = pg_execute($con, 'defeito_solucao', array($hd_chamado));
          
          if (pg_num_rows($xres_defeito_solucao_desc) > 0) {
            $sl = pg_fetch_result($xres_defeito_solucao_desc, 0, 'solucao');
            $df = pg_fetch_result($xres_defeito_solucao_desc, 0, 'defeito_constatado');
          }

        /*$sql_prod_def_sol = "SELECT tbl_produto.referencia AS produto,
            tbl_defeito_reclamado.descricao AS reclamado,
            tbl_defeito_constatado.descricao AS constatado,
            tbl_solucao.descricao AS solucao
          FROM tbl_os
          JOIN tbl_produto USING(produto)
          JOIN tbl_defeito_reclamado USING(defeito_reclamado)
          JOIN tbl_defeito_constatado USING(defeito_constatado)
          JOIN tbl_solucao ON tbl_os.solucao_os = tbl_solucao.solucao
          WHERE os = {$aDados['os']}";
        $res_prod_def_sol = pg_query($con, $sql_prod_def_sol);

        $aDados["referencia"] = pg_fetch_result($res_prod_def_sol, 0, 'produto');
        $os_defeito_reclamado = pg_fetch_result($res_prod_def_sol, 0, 'reclamado');
        $os_defeito_constatado = pg_fetch_result($res_prod_def_sol, 0, 'constatado');
        $os_solucao = pg_fetch_result($res_prod_def_sol, 0, 'solucao');

        $defeitos = true;*/
      }
      ?>
            <tr>
                <td class="label_new">
                    Produto
                </td>
                <td class="dados_new" id="produtos">
                    <?php
                    if (isset($_POST['produto_hidden_cd']) && !empty($_POST['produto_hidden_cd'])) {
                        $sql_ref = "SELECT referencia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto  = ".$_POST['produto_hidden_cd'];
                        $res_ref = pg_query($con,$sql_ref);
                        $produto_referencia_cdd = pg_fetch_result($res_ref, 0, referencia);
                    }else if (!empty($aDados["referencia"])){
                        $produto_referencia_cdd = $aDados["referencia"] ;
                    } else if (!empty($aDados['os'])) {
                        $sql_ref = "SELECT referencia FROM tbl_os JOIN tbl_produto USING(produto) WHERE fabrica = {$login_fabrica} AND os = {$aDados['os']}";
                        $res_ref = pg_query($con,$sql_ref);
                        $produto_referencia_cdd = pg_fetch_result($res_ref, 0, referencia);
                    }
                    
                    if(($login_fabrica == 42) AND empty($aDados["sua_os"]) OR ($aDados["garantia"] != "t")){?>
                      <form action="<?= $PHP_SELF ?>" name="makita">
                          <input class="frm" type="text" name="produto_makita" id="produto_makita" size="10" maxlength="20" value="<?=$produto_referencia_cdd?>" style="width: 200px;">
                          <img id="btpesquisa" src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_makita (document.makita.produto_makita, document.makita.produto_cd, 'referencia')"alt='Clique para efetuar a pesquisa' style='cursor:pointer; visibility: hidden'>
                          <input type="hidden" name="produto_cd" id="produto_cd" value="<?=$produto_cd?>" >
                          <br>
                          <input type="button" value="Alterar" id="btalterar" onclick="alterarproduto()">
                        <input type="button" onclick="salvarproduto()" value="Gravar" name="btgravar" id="btgravar" style="display: none">
                        <input type="hidden" value="<?= $hd_chamado?>" name="hd_chamado" id="hd_chamado">
                      </form>
                    <script>
                      function salvarproduto(){
                        var produto = $('#produto_makita').val();
                        var hd_chamado = $('#hd_chamado').val();
                        $.ajax({
                          url : "<?= $PHP_SELF ?>",
                          type: "POST",
                          data: { alterarprodutomakita : true, produto_makita: produto, hd_chamado: hd_chamado },
                          success: function(data) {
                            if (data.ok == 'false') {
                              alert('Erro ao atualizar o produto');
                            }else {
                              alert('Produto atualizado com sucesso');
                              window.location.reload();
                            }
                          }
                        })
                      }
                      function alterarproduto(){
                        $('#btgravar').css('display', 'block')
                        $('#btpesquisa').css('visibility', 'visible')
                        $('#btmais').css('visibility', 'visible')
                        $('#btmenos').css('visibility', 'visible')
                        $('#btalterar').css('display', 'none')
                        $('#produto_referencia_cdd').css('margin-left', '-119px')
                      }
                      function produtos(){
                        var clone = document.getElementById('origemproduto').cloneNode(true);  
                        var destino = document.getElementById('destinoproduto');  
                        destino.appendChild (clone);  

                        var camposClonados = clone.getElementsByTagValue('+');  

                        for(i=0; i<camposClonados.length;i++){ 
                          camposCldestinoonados[i].value = '';  
                        }  
                      }
                      function removerCampos(id){  
                        var node1 = document.getElementById('destinoproduto');  
                        node1.removeChild(node1.childNodes[0]);  
                      }
                    </script>
                  <?php } elseif ( empty($aDados["sua_os"]) OR ($aDados["garantia"] != "t") ) {
                      // echo $aDados["referencia"] ;
                      //  if ( 1==1) {
                      ?>
                      <input class="frm" type="text" name="produto_referencia_cdd" id="produto_referencia_cdd" size="15" maxlength="20" value="<?=$produto_referencia_cdd?>" >
                      <input type="hidden" name="produto_cd" id="produto_cd" value="<?=$produto_cd?>" >
                      <?php
                    }else{
                      echo $produto_referencia_cdd;
                    }

                    $dados = hdBuscarChamado($hd_chamado);
                    if ($login_fabrica == 3) {
                      if (empty($produto_referencia_cdd)) {
                        $produto_referencia_cdd = $dados['referencia'];
                      }
                    } else {
                      $produto_referencia_cdd = $dados['referencia'];
                    }

                    ?>
                   
                  </td>
                  <?php if ($login_fabrica != 1 || ($login_fabrica == 1 && !in_array($aDados['categoria'], array("nova_duvida_pedido", "nova_duvida_pecas", "nova_duvida_produto", "nova_erro_fecha_os")))) { ?>
                  <td class="label_new">
                    Ordem de Serviço
                  </td>
                <td class="dados_new">
                    <? if (!empty($aDados["sua_os"])) { ?>
                        <a href="os_press.php?os=<?=$aDados['os']?>" target="_blank">
                            <?
                            echo ($login_fabrica == 1) ? $aDados['codigo_posto'] . $aDados['sua_os'] : $aDados['sua_os'];
                            ?>
                        </a>
                    <? } ?>
                </td>
              <?php } ?>
            </tr>
      <?php if (true === $defeitos): ?>
      <tr>
        <td class="label_new">
          Defeitos
        </td>
              <td class="dados_new">
                <?=$df?>
                 <!-- Reclamado: <?= $os_defeito_reclamado ?><br>
                 Constatado: <?= $os_defeito_constatado ?>  -->
              </td>
        <td class="label_new">
          Solução
        </td>
              <td class="dados_new">
                <?=$sl?>
                 <!-- <?= $os_solucao ?> -->
              </td>
      </tr>
      <?php endif ?>
        <?
        }
        ?>
      <?php if($login_fabrica == 42){ 
        $sqlpeca = "SELECT peca_faltante from tbl_hd_chamado_posto where hd_chamado = $hd_chamado";
        $respeca = pg_query($con, $sqlpeca);

        if(pg_num_rows($respeca) > 0){
          $pecaresult = explode('<br>',pg_fetch_result($respeca, 'peca_faltante'));
        }

        $sqldefeito = "SELECT campos_adicionais from tbl_hd_chamado where hd_chamado = $hd_chamado";
        $resdefeito = pg_query($con, $sqldefeito);

        if(pg_num_rows($resdefeito) > 0){
          $defeitoresult = json_decode(pg_fetch_result($resdefeito, 'campos_adicionais'), true);
        }

        ?>
        <tr>
          <td class="label_new">
            Peça Causadora
          </td>
          <td class="dados_new">
          <?php for($pe = 0; $pe < count($pecaresult); $pe++) {
              if($pecaresult[$pe] !== "") {?>
              <input class="frm" type="text" name="peca" id="peca" style="width: 200px" value="<?=$pecaresult[$pe]?>"><br>
          <?php }
          } ?>
          </td>
          <td class="label_new">
            Defeito
          </td>
          <td class="dados_new">
          <form action="<?= $PHP_SELF ?>" name="makita">
                <input class="frm" type="text" name="defeito_alterar" id="defeito_alterar" size="10" maxlength="20" value="<?=$defeitoresult['defeito']?>" style="width: 200px;">
                <select id="defeito_makita" name="defeito_makita" style="display: none" >
                  <option value=""></option>
                  <option value="Curto">Curto</option>
                  <option value="Quebra">Quebra</option>
                  <option value="Instrução de Montagem">Instrução de Montagem</option>
                  <option value="Falta de Peça">Falta de Peça</option>
                  <option value="Consulta Código">Consulta Código</option>
                  <option value="Manutenção Inadequada">Manutenção Inadequada</option>
                  <option value="Fundido / Travado">Fundido / Travado</option>
                  <option value="Desgastado">Desgastado</option>
                  <option value="Lamina do coletor solta">Lamina do coletor</option>
                  <option value="Verniz derretido">Verniz derretido</option>
                  <option value="Ruído">Ruído</option>
                  <option value="Sem lubrificação">Sem lubrificação</option>
                  <option value="Excesso de lubrificação">Excesso de lubrificação</option>
                  <option value="Fio rompido">Fio rompido</option>
                  <option value="Conector com zinabre">Conector com zinabre</option>
                  <option value="Mau contato">Mau contato</option>
                  <option value="Sem afiação">Sem afiação</option>
                  <option value="Desajustado">Desajustado</option>
                  <option value="Empenado">Empenado</option>
                  <option value="Amassado">Amassado</option>
                  <option value="Desalinhado">Desalinhado</option>
                  <option value="Não Liga">Não Liga</option>
                  <option value="Não Carrega">Não Carrega</option>
                  <option value="Não Identificado">Não Identificado</option>
                  <option value="Deformada">Deformada</option>
                  <option value="Vazamento">Vazamento</option>
                  <option value="Sobreaquecida">Sobreaquecida</option>
                  <option value="Interferência">Interferência</option>
                  <option value="Folga Excessiva">Folga Excessiva</option>
                  <option value="Montagem Incorreta">Montagem Incorreta</option>
                  <option value="Peça Paralela">Peça Paralela</option>
                  <option value="Com Limalha">Com Limalha</option>
                  <option value="Solicitação Vista explodida">Solicitação Vista explodida</option>
                  <option value="Fora de Linha">Fora de Linha</option>
                  <option value="Importada">Importada</option>
                  <option value="Visita Técnica">Visita Técnica</option>
                  <option value="Consulta Preço">Consulta Preço</option>
                  <option value="Rasgado">Rasgado</option>
                  <option value="Arranhado">Arranhado</option>
                  <option value="Riscado">Riscado</option>
                  <option value="Descolado">Descolado</option>
                  <option value="Perdido">Perdido</option>
                  <option value="Cortado">Cortado</option>
                  <option value="Qualidade do Combustível">Qualidade do Combustível</option>
                  <option value="Combustível Inadequado">Combustível Inadequado</option>
                  <option value="Má conservação">Má conservação</option>
                  <option value="Sujo">Sujo</option>
                  <option value="Contaminado">Contaminado</option>
                  <option value="Outros">Outros</option>
                </select>
              <input type="button" value="Alterar" id="btalterardefeito" onclick="alterardefeito()">
            <input type="button" onclick="salvardefeito()" value="Gravar" name="btgravardefeito" id="btgravardefeito" style="display: none">
            <input type="hidden" value="<?= $hd_chamado?>" name="hd_chamado" id="hd_chamado">
          </form>
          </td>
      </tr>
        <script>
          function salvardefeito(){
            var defeito_makita = $('#defeito_makita').val();
            var hd_chamado = $('#hd_chamado').val();
            $.ajax({
              url : "<?= $PHP_SELF ?>",
              type: "POST",
              data: { alterardefeitomakita : true, defeito_makita: defeito_makita, hd_chamado: hd_chamado },
              success: function(data) {
                if (data.ok == 'false') {
                  alert('Erro ao atualizar o defeito');
                }else {
                  alert('Defeito atualizado com sucesso');
                  window.location.reload();
                }
              }
            })
          }
          function alterardefeito(){
            $('#defeito_alterar').css('display', 'none')
            $('#defeito_makita').css('display', 'block')
            $('#btgravardefeito').css('display', 'block')
            $('#btalterardefeito').css('display', 'none')
            $('#produto_referencia_cdd').css('margin-left', '-119px')
          }
          </script>
      <?php } ?>
<tr>
<td class="label_new">
Responsável pela Solicitação
</td>
<td class="dados_new">
<?php
$aDados["array_campos_adicionais"] = json_decode($aDados["array_campos_adicionais"], true);
echo utf8_decode($aDados["array_campos_adicionais"]["usuario_sac"]);
?>
</td>
<?php
if($login_fabrica == 1 && in_array($aDados['categoria'],array('falha_no_site','duvidas_telecontrol'))){
?>
<td class="label_new">
Menu
</td>
<td class="dados_new">
<?
$aux = explode(",",$informacao_adicional);
echo $aux[1];
?>
</td>
</tr>
<tr>
<td class="label_new">
Link
</td>
<td class="dados_new" colspan="3">
<?
echo $aux[0];
?>
</td>
</tr>
<?
}
if ($login_fabrica == 3) {
?>
<td class="label_new border azul">
Técnico Responsável
</td>
<td class="dados_new">
<?php
if (strlen($aDados["array_campos_adicionais"]["tecnico_responsavel"]) > 0) {
$sql_nome_tecnico = "SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND tecnico = {$aDados["array_campos_adicionais"]["tecnico_responsavel"]}";
$res_nome_tecnico = pg_query($con, $sql_nome_tecnico);
echo pg_fetch_result($res_nome_tecnico, 0, "nome");
}
?>
</td>
<?php
}
?>

<?php
if($login_fabrica == 1 && $admin_sac == treu){
?>
<td class="label_new">
Ordem de Serviço Posto
</td>
<td class="dados_new">
<?php echo $aDados["array_campos_adicionais"]["os_posto"]; ?>
</td>
<?php
}
?>

</tr>
<?php
if ($login_fabrica == 3) {
?>
<tr>
<td class="label_new border azul">
Outro Responsável
</td>
<td class="dados_new">
<?php
echo utf8_decode($aDados["array_campos_adicionais"]["outro_responsavel"]);
?>
</td>
<td class="label_new border azul">
Série
</td>
<td class="dados_new">
<?php
echo utf8_decode($aDados["serie"]);
?>
</td>
</tr>
<?php
}
?>
</table>

    <?php

if($login_fabrica == 1 && $admin_sac == true && $aDados['categoria'] == "servico_atendimeto_sac" &&  $status != "Resolvido"){
?>
<script>

function finalizar_chamado(){

if(!$('.box-obs').is(':visible')){
$('.box-obs').show();
}

var obs = $("#obs").val();

if($("#gerar_bo").is(":checked")){
gerar_bo = "sim";
}else{
gerar_bo = "nao";
}

if(obs == ""){
alert("Por favor insira a Observação");
$("#obs").focus();
return;
}

$.ajax({
url : "<?php echo $PHP_SELF; ?>",
type : "POST",
data : {
finalizar_chamado : true,
observacao : obs,
hd_chamado : "<?php echo $hd_chamado; ?>"
},
complete: function(data){
data = data.responseText;
if(data == "ok"){
if(gerar_bo == "sim"){
window.location = 'cadastro_advertencia_bo.php?hd_chamado=<?=$hd_chamado?>';
}else{
location.reload();
}
}else{
alert("Erro ao finalizar o Chamado");
}
}
});

}

</script>
<style>
.box-bo{
width: 730px;
border: 1px solid #ccc;
padding: 10px;
margin: 0 auto;
margin-top: 30px;
}
</style>
<div class="box-bo">
<strong>Caso haja falha do posto é necessário a abertura do boletim de ocorrência</strong> <br />
<input type="checkbox" name="gerar_bo" id="gerar_bo" value="sim" /> Gerar B.O.

<div class="box-obs" style="display: none;">
<br />
Observação <br />
<textarea name="obs" id="obs" cols="60" rows="5"></textarea>
</div>

<br /> <br />
<button type="button" onclick="finalizar_chamado()">Finalizar Chamado</button>
</div>
<?php
}

?>

<?php
if($aDados['categoria'] == 'atualiza_cadastro' && ($login_fabrica != 1 || ($login_fabrica == 1 && $aDados['categoria'] != 'atualiza_cadastro'))) { ?>
<p> &nbsp; </p>

<table class="box">
<tbody>
<tr>
<td class="label border azul"> Tipo Atualização</td>
<td class="dados border"> &nbsp; <?php echo $tipo;?></td>
</tr>
<tr>
<td class="label border azul"> Dados a ser atualizados</td>
<td class="dados border"> &nbsp;
<?php
if($aDados['tipo'] == 'telefone') {
echo $aDados['fone'];
}

if($aDados['tipo'] == 'email') {
echo $aDados['email'];
}

if($aDados['tipo'] == 'dados_bancarios') {
$sql = " SELECT nome FROM tbl_banco
WHERE codigo = '".$aDados['banco']."'";
$res = pg_query($con,$sql);
echo "Nome do Banco: ". pg_fetch_result($res,0,nome);
echo "<br>";
echo "Agência: ".$aDados['agencia'];
echo "<br>";
echo "Conta: ".$aDados['conta'];
echo "<br>";
}
?>

</td>

</tr>
</tbody>
</table>

<?}?>

<? if($aDados['categoria'] == 'manifestacao_sac' && $login_fabrica != 1) { ?>
<p> &nbsp; </p>

<table class="box">
<caption>Manifestação SAC</caption>
<tbody>
<tr>
<td class="label border azul"> Nome do cliente</td>
<td class="dados border"> &nbsp; <?php echo $aDados['nome_cliente'];?></td>
<?php if($login_fabrica <> 1){ ?>
<td class="label border azul"> Atendente</td>
<td class="dados border"> &nbsp; <?php echo $aDados['atendente_sac'];?></td>
<?php } ?>
</tr>
</tbody>
</table>

<?}?>
<?php 
if (in_array($login_fabrica, [42]) && $categoria == 'Solicitação de coleta') {
    $sqlPosto = "SELECT nome, cnpj, endereco, numero, cep, cidade, estado, email, fone FROM tbl_posto WHERE posto = {$aDados['posto']}";
    $resPosto = pg_query($con, $sqlPosto);
    $posto_razao_social = pg_fetch_result($resPosto, 0, 'nome');
    $posto_cnpj = pg_fetch_result($resPosto, 0, 'cnpj');
    $posto_endereco = pg_fetch_result($resPosto, 0, 'endereco');
    $posto_numero = pg_fetch_result($resPosto, 0, 'numero');
    $posto_cep = pg_fetch_result($resPosto, 0, 'cep');
    $posto_cidade = pg_fetch_result($resPosto, 0, 'cidade');
    $posto_estado = pg_fetch_result($resPosto, 0, 'estado');
    $posto_email = pg_fetch_result($resPosto, 0, 'email');
    $posto_telefone = pg_fetch_result($resPosto, 0, 'fone');
    ?>
    <br>
    <table class="box">
        <tr>
            <td class="label border azul">Razão Social</td>
            <td class="dados border"><?php echo $posto_razao_social?></td>
            <td class="label border azul">CNPJ </td>
            <td class="dados border"><?php echo $posto_cnpj?></td>
        </tr>
        <tr>
            <td class="label border azul">Endereço </td>
            <td class="dados border"><?php echo $posto_endereco?></td>
            <td class="label border azul">Número </td>
            <td class="dados border"><?php echo $posto_numero?></td>
        </tr>
        <tr>
            <td class="label border azul"> CEP </td>
            <td class="dados border"><?php echo $posto_cep?></td>
            <td class="label border azul"> Cidade </td>
            <td class="dados border"><?php echo $posto_cidade?> </td>
        </tr>
        <tr>
            <td class="label border azul"> Estado </td>
            <td class="dados border"><?php echo $posto_estado?> </td>
            <td class="label border azul">e-mail </td>
            <td class="dados border"><?php echo $posto_email?> </td>
        </tr>
        <tr>
            <td class="label border azul">Telefone </td>
            <td class="dados border"><?php echo $posto_telefone?></td>
        </tr>
    </table>
<?php } ?>
<? if($aDados['categoria'] == 'pendencias_de_pecas' or $aDados['categoria']== 'pend_pecas_dist'  or $aDados['categoria']== 'solicitacao_coleta' or ($aDados['categoria']== 'pagamento_garantia' and $login_fabrica != 1) or $aDados['categoria']== 'erro_embarque') { ?>
<p> &nbsp; </p>
<table class="box">
<caption><?$categoria?></caption>
<tbody>
<? if( $aDados['categoria']!= 'solicitacao_coleta' and $aDados['categoria']!= 'pagamento_garantia' and $aDados['categoria']!= 'erro_embarque') { ?>
<tr>
<td class="label border azul"> Número de Pedido </td>
<td class="dados border"> <?php echo ($aDados['categoria']== 'pend_pecas_dist') ? $aDados['pedido_ex']:$aDados['pedido']; ?> </td>

<td class="label border azul"> Data do Pedido</td>
<td class="dados border"> &nbsp; <?php echo $aDados['data_pedido'];?></td>
</tr>
<? } else {?>
<? if($desc_subcategoria != "Devolução de peça para análise" and $aDados['categoria']!= 'pagamento_garantia'){ ?>
<tr>
<td class="label border azul"> NF de Origem </td>
<td class="dados border"> <?php echo $aDados['nota_fiscal']; ?> </td>

<td class="label border azul"> Data NF Origem</td>
<td class="dados border"> &nbsp; <?php echo $aDados['data_nf'];?></td>
</tr>
<?php if (in_array($login_fabrica, [42]) && $aDados['categoria'] == 'solicitacao_coleta') { ?>
<tr>
<td class="label border azul"> Qtde Volumes </td>
<td class="dados border"> <?php echo $aDados['array_campos_adicionais']['qtde_volume']; ?> </td>

<td class="label border azul"> Peso Total</td>
<td class="dados border"> &nbsp; <?php echo $aDados['array_campos_adicionais']['peso_total'];?></td>
</tr>
<?php } ?>
<? } ?>
<? if($tipo_solict_prod != 1 and $tipo_emb_prod != 3 and !in_array($tipo_emb_peca,array(1,2,3))){ ?>
<? if($tipo_emb_prod == 4){
$produtos_enviados = explode(';',$conteudo_adicional);
foreach($produtos_enviados AS $conteudo_ad){
list($prod_enviado,$qtde_enviada) = explode('|',$conteudo_ad);
$sqlP = "SELECT descricao FROM tbl_produto WHERE fabrica_i = $login_fabrica AND upper(referencia )= upper('$prod_enviado')";
$resP = pg_query($con,$sqlP);
if(pg_numrows($resP) > 0){
$descricao_prod = " - ".pg_result($resP,0,'descricao');
}
echo"
<tr>
<td class='label border azul'>Produto Enviado</td>
<td class='dados border'>".$prod_enviado.$descricao_prod."</td>

<td class='label border azul'>Qtde. Enviada</td>
<td class='dados border'> &nbsp;$qtde_enviada</td>
</tr>
";
}


} else{ ?>
<tr>
<td class="label border azul"> <?php echo $titulo1; ?> </td>
<td class="dados border"> <?php echo $conteudo1; ?> </td>

<td class="label border azul"> <?php echo $titulo2; ?></td>
<td class="dados border"> &nbsp; <?php echo $conteudo2;?></td>
</tr>
<? } ?>
<? } ?>
<? if(in_array($tipo_emb_peca,array(1,2,3)) or in_array($tipo_emb_prod,array(1,2,3,4))){ ?>
<tr>
<td class="label border azul"> Pedido </td>
<td class="dados border"> <?php echo $aDados['pedido']; ?> </td>

<td class="label border azul"> Tipo Pedido</td>
<td class="dados border"> &nbsp; <?php echo $tipo_pedido;?></td>
</tr>
<? if($tipo_emb_peca == 1){
$pecas_enviadas = explode(';',$conteudo1);
foreach($pecas_enviadas AS $conteudo_ad){
list($peca_enviada,$qtde_enviada) = explode('|',$conteudo_ad);
$sqlP = "SELECT descricao FROM tbl_peca WHERE fabrica = $login_fabrica AND upper(referencia )= upper('$peca_enviada')";
$resP = pg_query($con,$sqlP);
if(pg_numrows($resP) > 0){
$descricao_peca = " - ".pg_result($resP,0,'descricao');
}
echo"
<tr>
<td class='label border azul'>Peça Enviada</td>
<td class='dados border'>".$peca_enviada.$descricao_peca."</td>

<td class='label border azul'>Qtde. Enviada</td>
<td class='dados border'> &nbsp;$qtde_enviada</td>
</tr>
";
}
}
}
} if($tipo_solict_peca == 2 or $duvida == "documentos"){ ?>
<tr>
<td class="label border azul"> <?php echo $titulo3; ?></td>
<td class="dados border" colspan='3'> &nbsp; <?php echo $conteudo3;?></td>
</tr>
<? } else { ?>
<? if(!in_array($tipo_solict_prod,array(1,2,3)) and !in_array($tipo_emb_peca,array(1,3)) and !in_array($tipo_emb_prod,array(1,2,3,4))and $aDados['categoria']!= 'pagamento_garantia'){ ?>
<tr>
<td class="label border azul"> Peças Faltantes</td>
<td class="dados border" colspan='3'> &nbsp; <?php echo $aDados['peca_faltante'];?></td>
<?php if (in_array($login_fabrica, [42]) && $aDados['categoria'] == 'pendencias_de_pecas') {?>
  <td class="label border azul"> Ordem de Serviço</td>
  <td class="dados border" colspan='3'> &nbsp; <?php echo $aDados['os'];?></td>
<?php } ?>
<? if($tipo_solict_peca == 1 ){ ?>
<td class="label border azul"> <?=$titulo3?></td>
<td class="dados border" colspan='3'> &nbsp; <?php echo $conteudo3;?></td>
<? } ?>
</tr>
<? } ?>
<? } ?>
</tbody>
</table>
<?php
}

if($login_fabrica == 3){
    $sql_defeito_solucao_desc = "SELECT
                                    tbl_solucao.descricao AS solucao,
                                    tbl_defeito_constatado.descricao AS defeito_constatado,
                                    tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
                                    tbl_defeito_constatado_solucao.defeito_constatado_solucao As dc_solucao
                                FROM tbl_dc_solucao_hd
                                JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                WHERE tbl_dc_solucao_hd.hd_chamado = $1";
    pg_prepare($con, 'defeito_solucao', $sql_defeito_solucao_desc);
    $res_defeito_solucao_desc = pg_execute($con, 'defeito_solucao', array($hd_chamado));

//echo nl2br($sql_defeito_solucao_desc);

if(pg_num_rows($res_defeito_solucao_desc) == 0){
    $hd_chamado_aux = ($login_fabrica == 3 && !empty($aDados["seu_hd"])) ? $aDados["seu_hd"] : $aChamado;
    list($hd_chamado_aux,$digito) = explode('-',$hd_chamado_aux);
    $hd_chamado_int = preg_replace("/\D/","",$hd_chamado_aux);

    $sql = "SELECT
                tbl_hd_chamado.hd_chamado
            FROM tbl_hd_chamado
                JOIN tbl_hd_chamado_posto USING(hd_chamado)
            WHERE tbl_hd_chamado_posto.seu_hd = '$hd_chamado_aux'
                OR tbl_hd_chamado.hd_chamado = $hd_chamado_int
                OR tbl_hd_chamado.hd_chamado_anterior = $hd_chamado_int
                AND tbl_hd_chamado.fabrica = $login_fabrica
            ORDER BY 1 DESC;";
    $res = pg_query($con, $sql);
    $hd_chamado_anterior = pg_fetch_result($res, 1, 'hd_chamado');
    if (!empty($hd_chamado_anterior)) {
        $res_defeito_solucao_desc = pg_execute($con, 'defeito_solucao', array($hd_chamado_anterior));
    }
}


}

?>

<p> &nbsp; </p>

<?php
if ($login_fabrica == 3) {
    $sql_i = "SELECT max(hd_chamado_item)
                FROM tbl_hd_chamado_item
                WHERE hd_chamado = {$hd_chamado}
                    AND comentario like 'PROCEDIMENTO ATUALIZADO%' ;";
    $res_i = pg_query($con,$sql_i);

    if (pg_num_rows($res_i) > 0) {
        $ultimo_procedimento = pg_fetch_result($res_i, 0, max);
    }
}

$aRespostas = hdBuscarRespostas($hd_chamado); // funcao declarada em 'assist/www/heldesk.inc.php'
$i = 1;
$manterHtml = '<strong><b><em><br /><br><span><u><table><thead><tr><th><tbody><td><ul><li><ol><u><a>';

foreach ($aRespostas as $aResposta){

  $newResposta = $aResposta['comentario'];
  $newResposta = strip_tags(html_entity_decode($newResposta),$manterHtml);

if (strpos($aResposta['comentario'],"As seguintes informações do chamado")) {
    $x = explode('As seguintes informações do chamado',$newResposta);
    $comentario = $x[0];
}else{
    if($aResposta["status_item"] == "Resolvido Posto"){
        $comentario = "<strong>(O posto resolveu o chamado nessa interação)</strong> <br /> ".$newResposta;
    }else{
        $comentario = $newResposta;
    }
}

$comentario = str_replace("\\n","",$comentario);
$comentario = str_replace("\\r","",$comentario);
$comentario = str_replace("\\","",$comentario);
$comentario = str_replace("body","div",$comentario);

if ($login_fabrica == 3) {

    $pos = strpos($comentario, "PROCEDIMENTO ATUALIZADO");
    if ( $pos !== false ){
        if ($aResposta['hd_chamado_item'] == $ultimo_procedimento) {
            $comentario = "<div style='background-color: #f2dede'>".$comentario."<div>";
        }else{
            $comentario = "<div style='background-color: #fcf8e3'>".$comentario."<div>";
        }
    }
}

switch($aResposta['status_item']){
    case 'Em Acomp. Pendente' : $aResposta['status_item'] = "Em Acompanhamento - Pendente";break;
    case 'Em Acomp. Encerra': $aResposta['status_item'] = "Em Acompanhamento - Encerrar";break;
    case 'Em Acomp.': $aResposta['status_item'] = "Em Acompanhamento";break;
    case 'Resp.Conclusiva': $aResposta['status_item'] = "Resposta Conclusiva";break;
    case 'encerrar_acomp': $aResposta['status_item'] = "Encerrar Acompanhamento";break;
}
?>
<table width="100%" border="0" align="center" class="resposta" cellpadding="2" cellspacing="0">
<tr>
<td colspan="2" align="left" valign="top">
<table border="0" width="100%">
<tr>
<td width="70%">
Resposta <strong><?php echo $i++; ?></strong>
Por <strong><?php echo ( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] ; ?></strong>
- <strong><?php echo $aResposta['status_item']; ?></strong>
</td>
<td align="right" nowrap="nowrap"> <?php echo $aResposta['data']; ?> </td>
</tr>
</table>
</td>
</tr>
<?php if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ) { ?>
<tr>
<td colspan="2" align="center" valign="top" bgcolor="#EFEBCF"> <?php echo $aResposta['status_item']; ?> </td>
</tr>
<?php } ?>
<?php if ( $aResposta['interno'] == 't' ){ ?>
<tr>
<td colspan="2" align="center" valign="top" bgcolor="#EFEBCF"> <?php echo "Chamado Interno"?> </td>
</tr>
<?php } ?>
<tr>
<td  valign="top" bgcolor="#FFFFFF">
  <?php if ($login_fabrica == 3) {
      echo html_entity_decode($comentario);
    } else {
      echo MontarLink($comentario);
    }
  ?>
</td>
<td align="center" valign="middle" bgcolor="#FFFFFF" width="50px">

<?php

$file = hdNomeArquivoUpload($aResposta['hd_chamado_item']);

if (empty($file)) {

    $tDocs   = new TDocs($con, $login_fabrica);
    $idAnexo = $tDocs->getDocumentsByRef($aResposta['hd_chamado_item'],'hdpostoitem')->attachListInfo;

    if(is_array($idAnexo) && count($idAnexo) > 0){
        foreach ($idAnexo as $anexo) {

            if (isset($anexo['link']) && !empty($anexo['link'])) {
                echo '
                <p>
                <a href="'.$anexo['link'].'" target="_blank" >
                <img src="../helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
                Baixar Anexo
                </a>
                </p>';
            }
        }
    }
} else {
    echo '
    <a href="'.TC_HD_UPLOAD_URL.$file.'" target="_blank" >
    <img src="../helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
    Baixar Anexo
    </a>';
}
?>

</td>
</tr>
</table>
<?php } //echo '<pre>'.print_r($aRespostas,1).'</pre>';exit();?>
<?php unset($aRespostas,$iResposta,$aResposta,$_hd_chamado); ?>
<?php }
if ($login_fabrica == 153) {
include_once "../class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("helpdesk_pa", $login_fabrica);

?>
<script src='plugins/jquery.form.js'></script>
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
<?
$anexo = $s3->getObjectList("{$hd_chamado}.");
if (count($anexo) > 0) {?>
<table width="100%" border="0" align="center" class="resposta" cellpadding="2" cellspacing="0">
<tr>
<td colspan="2" align="left" valign="top">
<table border="0" width="100%">
<tr>
<td width="70%"><strong>ANEXO</strong></td>
</tr>
</table>
</td>
</tr>
<tr>
<td  valign="top" bgcolor="#FFFFFF">
<?php
$ext = strtolower(preg_replace("/.+\./", "", basename($anexo[0])));
if ($ext == "pdf") {
$anexo_imagem = "imagens/pdf_icone.png";
} else if (in_array($ext, array("doc", "docx"))) {
$anexo_imagem = "imagens/docx_icone.png";
} else {
$anexo_imagem = $s3->getLink("thumb_".basename($anexo[0]));
}
$anexo_link = $s3->getLink(basename($anexo[0]));
?>
<a href="<?=$anexo_link?>" target="_blank" >
<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
</a>
</td>
</tr>
</table>
<?php
} ?>
<br />
<?php
}

if(pg_num_rows($res_defeito_solucao_desc) > 0){

$solucao        = pg_fetch_result($res_defeito_solucao_desc, 0, "solucao");
$id_solucao        = pg_fetch_result($res_defeito_solucao_desc, 0, "id_solucao");
$defeito_constatado   = pg_fetch_result($res_defeito_solucao_desc, 0, "defeito_constatado");
$id_defeito_constatado   = pg_fetch_result($res_defeito_solucao_desc, 0, "id_defeito_constatado");
$id_defeito_constatado_solucao   = pg_fetch_result($res_defeito_solucao_desc, 0, "dc_solucao");
$solucao_procedimento   = pg_fetch_result($res_defeito_solucao_desc, 0, "procedimento");

if (strpos($categoria,'Técnicas') /*AND $status != 'Aguardando Posto' AND $status != 'Aguardando Fábrica'*/ ) {
//echo $status;
?>
<br />
<table style="border: 1px #000 solid; margin: auto; width: 750px;">
<tr>
<td colspan="2" class="label border azul" style="text-align: center !important;">
<strong style="text-align: center;">Defeito / Solução do produto inserido no chamado</strong>
</td>
</tr>
<tr>
<td class="label border azul" style="text-align: center !important;">Defeito</td>
<td class="label border azul" style="text-align: center !important;">Solução</td>
</tr>
<tr>
<td><?php echo $defeito_constatado; ?></td>
<td><?php echo $solucao; ?></td>
</tr>
<?php if(strlen($solucao_procedimento) > 0){ ?>
<tr>
<td class="label border azul" colspan="2" style="text-align: center !important;">Procedimento</td>
</tr>
<tr>
<td colspan="2" style="text-align: left;"><?php echo nl2br(MontarLink($solucao_procedimento)); ?></td>
</tr>
<?php }
if (!empty($id_defeito_constatado_solucao)) {
                        include_once S3CLASS;
                        $s3 = new AmazonTC("procedimento", $login_fabrica);
                        $anexos = $s3->getObjectList("{$login_fabrica}_{$id_defeito_constatado_solucao}", false, '2016', '04');

                        if (count($anexos) > 0) {
                            $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                            if ($ext == "pdf") {
                                $anexo_imagem = "imagens/pdf_icone.png";
                            } else if (in_array($ext, array("doc", "docx"))) {
                                $anexo_imagem = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, '2016', '04');
                            }

                            $anexo_link = $s3->getLink(basename($anexos[0]), false, '2016', '04');
                            $anexo = basename($anexos[0]);
                        ?>
                        <tr>
                            <td class="label border azul" colspan="2" style=" text-align: center !important; width: 500px !important;">Anexo</td>
                        </tr>
                        <tr>
                            <td colspan="2" style=" text-align: center !important; width: 500px !important;">
                                <div id="div_anexo" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                                    <a href="<?=$anexo_link?>" target="_blank" >
                                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                                    </a>
                                </div>

                            </td>
                        </tr>
                        <?php
                        }
                    }
?>
</table>
<?php
}
}

?>
<div id='interações' style='background-color: #D9E2EF'>

<form action="" method="POST" enctype="multipart/form-data">
<?php
if ($status != "Resolvido Posto" AND $status != "Resolvido" AND $libera_interacao) {
?>
<input type="hidden" name="hd_chamado"  id="hd_chamado" value="<?=$hd_chamado?>" />
<input type="hidden" name="produto_hidden_ant"  id="produto_hidden_ant" value="<?=$produto_referencia_cdd?>" />
<input type="hidden" name="produto_hidden_cd"  id="produto_hidden_cd" value="<?=$produto_hidden_cd?>" />
<input type="hidden" name="cat_atual"   id="cat_atual"  value='<?=$categoria?>' />
<input type="hidden" id='categoria' name="categoria" value='' />
<input type="hidden" name="posto" value='<?=$aDados['posto']?>' />

<?php if ($login_fabrica == 3 AND 1==2) { 

        $sql_os = " SELECT tbl_hd_chamado_extra.os, 
                           CASE WHEN tbl_hd_chamado_extra.produto IS NULL 
                              THEN 
                                tbl_os.produto 
                              ELSE 
                                tbl_hd_chamado_extra.produto
                           END AS produto,
                           tbl_hd_chamado.categoria,
                           tbl_produto.descricao,
                           tbl_produto.referencia,
                           tbl_os.defeito_constatado,
                           tbl_dc_solucao_hd.defeito_constatado_solucao
                    FROM tbl_hd_chamado_extra 
                    LEFT JOIN tbl_hd_chamado USING(hd_chamado)
                    LEFT JOIN tbl_os USING(os)
                    LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                    LEFT JOIN tbl_dc_solucao_hd ON tbl_hd_chamado.hd_chamado = tbl_dc_solucao_hd.hd_chamado
                    WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado 
                    AND tbl_hd_chamado.fabrica = $login_fabrica";
        $res_os = pg_query($con, $sql_os);
        if (pg_num_rows($res_os) > 0) {
          $os2            = pg_fetch_result($res_os, 0, 'os');
          $produto_hidden2 = pg_fetch_result($res_os, 0, 'produto');
          $categoria2      = pg_fetch_result($res_os, 0, 'categoria');
          $referencia2     = pg_fetch_result($res_os, 0, 'referencia');
          $descricao2      = pg_fetch_result($res_os, 0, 'descricao');
          $df_prod2        = pg_fetch_result($res_os, 0, 'defeito_constatado');
          $sl2             = pg_fetch_result($res_os, 0, 'defeito_constatado_solucao');
        }

        $cat_array_cat = array( 'duvida_tecnica_informatica',
                                'duvida_tecnica_eletro_pessoal_refri',
                                'duvida_tecnica_celular',
                                'duvida_tecnica_audio_video'
                               );

        ?> <input type="hidden" name="produto_hidden2" id="produto_hidden2" value="<?php echo $produto_hidden2; ?>" /> <?php

        $display_classificacao = "display: none";
        if (in_array($categoria2, $cat_array_cat)) {
            $display_classificacao = "display: block";
        }
          
  ?>
              <div id="info_produto_os_2" style="<?=$display_classificacao?>">
                <label>OS&nbsp;</label>
                    <input type='text' class='frm' name='os2' size='10' value='<?=$os2?>'>
                &nbsp; &nbsp;
                <label>Produto &nbsp; </label>
                    <input type='text' class='frm' name='referencia_os2' id='referencia_os2' size='20' value='<?=$referencia2?>'>
                <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto3 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'> &nbsp; &nbsp;
                <label>Descrição &nbsp; </label>
                      <input type='text' class='frm' name='descricao_os2' id='descricao_os2' size='20' value='<?=$descricao2?>'>
                <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto3 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'>

                <!-- Box Defeitos -->
                <div class="box-defeitos2" style="margin-top: 20px;">
                  <?php
                  if (!empty($produto_hidden2)) {
                    $defeitos_produtos_b2 = (!empty($_POST['defeitos_produtos'])) ? $_POST['defeitos_produtos'] : $df_prod2;

                    $sql = "SELECT DISTINCT
                          tbl_defeito_constatado.defeito_constatado,
                          tbl_defeito_constatado.descricao
                        FROM tbl_defeito_constatado_solucao
                        JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                        WHERE
                          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                          AND tbl_defeito_constatado_solucao.produto = {$produto_hidden2}
                        ORDER BY tbl_defeito_constatado.descricao ASC";
                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) > 0){

                      $result2 = "<strong>Dúvidas / Defeitos</strong> <br />";
                      $result2 .= "<select name='defeitos_produtos2' class='frm' id='defeitos_produtos2' onchange='busca_solucao_produto2(this.value)'>";
                      $result2 .= "<option value=''></option>";

                      for($i = 0; $i < pg_num_rows($res); $i++){
                        $defeito_constatado2 = pg_fetch_result($res, $i, "defeito_constatado");
                        $descricao2 = pg_fetch_result($res, $i, "descricao");
                        if ($defeitos_produtos_b2 == $defeito_constatado2){
                          $checked_b2 = "selected";
                        }else{
                          $checked_b2 = "";
                        }

                        $result2 .= "<option value='$defeito_constatado2' {$checked_b2}>$descricao2</option>";
                      }

                      $result2 .= "</select>";

                      echo $result2;
                    }
                  }
                  ?>
                </div>

                <!-- Box Solucoes -->
                <div class="box-solucoes2" style="margin-top: 20px;">
                   <?php
                  if (!empty($defeitos_produtos_b2)) {
                    $solucoes_produtos_b2 = (!empty($_POST['solucoes_produtos2'])) ? $_POST['solucoes_produtos2'] : (!empty($_POST['solucoes_produtos'])) ? $_POST['solucoes_produtos'] : $sl2;

                    $sql = "SELECT DISTINCT
                          tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                          tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                          tbl_solucao.solucao,
                          tbl_solucao.descricao
                        FROM tbl_defeito_constatado_solucao
                        JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                        WHERE
                          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                          AND tbl_defeito_constatado_solucao.produto = {$produto_hidden2}
                          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeitos_produtos_b2}
                        ORDER BY tbl_solucao.descricao ASC";
                    $res = pg_query($con, $sql);

                    if(pg_num_rows($res) > 0){

                      $sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
                                    FROM tbl_dc_solucao_hd
                                    JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                    JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                    WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                                    AND tbl_defeito_constatado_solucao.produto = {$produto_hidden2}
                                    AND tbl_hd_chamado.resolvido is not null
                                    AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeitos_produtos_b2}";
                      $res_total_solucoes = pg_query($con, $sql_total_solucoes);

                      $total_solucoes2 = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

                      $result2 = "<strong>Soluções - Índices de Soluções</strong> <br />";
                      $result2 .= "<select name='solucoes_produtos2' class='frm' id='solucoes_produtos2' onchange='busca_resposta_padrao(this.value); busca_procedimento_produto2(this.value, $defeito2)'>";
                      $result2 .= "<option value=''></option>";

                      for($i = 0; $i < pg_num_rows($res); $i++){
                        $defeito_constatado_solucao2 = pg_fetch_result($res, $i, "defeito_constatado_solucao");
                        $defeito_constatado2 = pg_fetch_result($res, $i, "defeito_constatado");
                        $solucao2 = pg_fetch_result($res, $i, "solucao");
                        $descricao2 = pg_fetch_result($res, $i, "descricao");

                        /* Estatística */
                        $sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                                  FROM tbl_dc_solucao_hd
                                  JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                  JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                  WHERE tbl_defeito_constatado_solucao.solucao = {$solucao2}
                                  AND tbl_defeito_constatado_solucao.produto = {$produto2}
                                  AND tbl_hd_chamado.resolvido is not null
                                  AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado2}

                                  AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
                        $res_estatistica = pg_query($con, $sql_estatistica);
                        //echo $sql_estatistica;

                        $total_ds2 = pg_fetch_result($res_estatistica, 0, "total_ds");

                        if($total_ds2 > 0){

                          $total_porc2 = number_format(($total_ds2 * 100) / $total_solucoes2, 1);

                        }else{
                          $total_porc2 = 0;
                        }

                        /* Fim - Estatística */

                        $descricao2 = $descricao2." - ".$total_porc2."%";
                        if ($solucoes_produtos_b2 == $defeito_constatado_solucao2){
                          $checked_b2 = "selected";
                        }else{
                          $checked_b2 = "";
                        }

                        $result2 .= "<option value='$defeito_constatado_solucao2' {$checked_b2}>$descricao2</option>";
                      }

                      $result2 .= "</select>";
                      echo $result2;
                    }
                  }
                  ?>
                </div>

                <!-- Box Procedimento-->
                <div class="box-procedimento2" style="margin-top: 20px;">
                  <?php
                  if (!empty($solucoes_produtos_b2)) {
                    $sql_procedimento = "SELECT
                                            tbl_produto.referencia AS ref_produto,
                                            tbl_produto.descricao AS desc_produto,
                                            tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento
                                          FROM tbl_defeito_constatado_solucao
                                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                            JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                            JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto
                                          WHERE
                                            tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                                          AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_proced2}
                                          AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$solucao_proced2}
                                          AND tbl_defeito_constatado_solucao.produto = {$produto_proced2}; ";

                    $res_procedimento = pg_query($con,$sql_procedimento);

                    $procedimento_solucao2 = pg_fetch_result($res_procedimento, 0, procedimento);

                    if (count($procedimento_solucao2) == 0) {

                      $procedimento_solucao2 = "Solução sem procedimento cadastrado.";

                    }

                    $result2 = "<strong>Procedimentos</strong> <br />";
                    $result2 .= "<textarea id='solucao_procedimento_prod2' rows='4' cols='50'>{$procedimento_solucao2}</textarea>";

                    echo $result2;
                  }
                  ?>
                </div>

                <!-- Box Produtos-->
                <div style="clear: both;"></div>
                <div class="box-link2" style="display : <?php echo (strlen($produto_hidden2) > 0) ? 'block' : 'none'; ?>">
                  <p align="center" class='link2'>
                    <a href="cadastro_defeitos_solucoes.php?produto_referencia=<?php echo $referencia2; ?>" target="_blank" id="link_href">Cadastrar / Editar Dúvidas e Soluções para Produtos</a>
                  </p>
                </div>
              </div>              
<?php
        
      }
    } 

 ?>

<label for="transferir">Enviar E-mail p/:</label><br>
<select name="transferir" multiple size='5' style='width:150px; font-size:12px;' class="input" id='transferir'>
<?php
    $sql = "SELECT admin, nome_completo
            FROM tbl_admin
            WHERE fabrica = $login_fabrica
            AND ativo IS TRUE
            AND (privilegios ~ 'call_center' OR privilegios = '*') ORDER BY login";
    $res = pg_query($con, $sql);#D9E2EF
    if(pg_num_rows($res)>0){
        $_admins = pg_fetch_all($res);
        foreach ($_admins as $dados_admin) {
            $transferir_nome    = $dados_admin['nome_completo'];
            $transferir         = $dados_admin['admin'];
            $admins[$transferir] = $transferir_nome;

            echo "<option value='$transferir'>$transferir_nome</option>\n";
        }
    }
?>
</select>&nbsp;&nbsp;
<select name="multi_admin[]" multiple size='5' style='width:150px; font-size:12px' class="input" id='multi_admin'>
</select>
<p style='width:320px;text-align:center;margin:auto'>
<input type="button" value="Adicionar" style='float:left' onclick="addAdmin()">
<input type="button" value="Excluir" style='float:right' onclick="delAdmin()">
<br />
</p>
<p>
<label for='chamado_interno'>Chamado Interno</label><input type="checkbox" name="chamado_interno" id="chamado_interno" class="input" <?php echo (isset($_POST['chamado_interno']) or $interno=='sim') ? 'checked="checked"' : '' ; ?>
<? if($interno=='sim') {
echo "onclick='checaInterno()' ";
}
?>
/>
<span style="color: #e00;">(* interação)</span>
<input type='hidden' name='interno' value='<?=$interno?>'>
</p>

<p>
<label for='transferir_para'>Transferir para:</label>
<select name="transferir_para" id="transferir_para" style='width:120px; font-size:11px' class="input" >
<option value=''></option>
<?php
    $sql = "SELECT  DISTINCT
                    admin,
                    nome_completo
            FROM    tbl_admin
            $sql_marca
            WHERE   tbl_admin.fabrica = $login_fabrica
            AND     tbl_admin.ativo is true
            AND     (
                        privilegios like '%call_center%'
                    OR  privilegios like '*')
            $sql_fale
       ORDER BY      nome_completo
    ";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res)>0) {
        for ($i = 0; pg_num_rows($res) > $i; $i++) {
            $tranferir      = pg_fetch_result($res,$i,admin);
            $tranferir_nome = pg_fetch_result($res,$i,'nome_completo');
            echo "<option value='$tranferir'>$tranferir_nome</option>";
        }
    }
?>
</select>
</p>
<?

// }
} else {    // Cadastra novo chamado
$title = "Cadastra Chamado para o Posto";
$layout_menu = "callcenter";
include 'cabecalho.php';
// pre_echo($msg_erro, 'Erros');exit;

if (count($msg_erro)) { ?>
<div class="box msg error">
  <?php
    echo implode('<br>',$msg_erro);
    if ($login_fabrica != 1) echo pg_last_error($con);
  ?>    
</div>
<?  } ?>
<?  if ( count($msg_ok) ) { ?>
<div class="box msg azul"><? echo implode('<br>',$msg_ok)?></div>
<?  } ?>

<style type="text/css">
#container {
text-align: center;
width: 700px;
margin: 0 auto;
}
#container p, #container td {
font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;
font-size: 10pt;
font-size-adjust:none;
font-style:normal;
font-variant:normal;
font-weight:normal;
line-height:normal;
text-align:center;
}
#container table.resposta {
border:#485989 1px solid;
background-color: #A0BFE0;
margin-bottom: 10px;
}
.text-left, .text-left * {
text-align: left !important;
}
.box, .border {
border-width: 1px;
border-style: solid;
}
.box {
display: block;
margin: 0 auto;
width: 100%;
}
.azul {
border-color: #1937D9;
background-color: #D9E2EF;
}
.msg {
padding: 10px;
margin-top: 20px;
margin-bottom: 20px;
}
.error {
border-color: #cd0a0a;
background-color: #fef1ec;
color: #cd0a0a;
width: 700px;
}
.label {
width: 25%;
text-align:right!important;
padding-right: 1ex;
}
.dados2 {
    width: 30%;
}
.dados:hover {
white-space: normal;
}
.dados {
width: 210px;
border-width: 1px;
text-align:left!important;
padding-left: 1ex;
_zoom:1;
display:inline-block;
overflow:hidden;
white-space:nowrap;
text-overflow:ellipsis;
-o-text-overflow:ellipsis;
}
#peca_faltante {
width: 540px;
}
#peca_faltante2 {
width: 340px;
}
#peca_faltante3 {
width: 340px;
}
</style>
<script src='plugins/jquery.maskedinput_new.js'></script>
<?php if ($login_fabrica == 1) { ?>
  <script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick.js"></script>
  <script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
  <link type="text/css" href="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<?php } ?>

<script>
  $(function(){


    $("input[name=duvida_pedido]").click(function() {
      var opcao = $("input[name=duvida_pedido]:checked").val();

      if (opcao == "informacao_recebimento" || opcao == "divergencia_recebimento" || opcao == "pendencia_peca_fabrica") {
        //$(".sub_duvida_pedido").val("");
        $("#sub1_duvida_pedido").css("display", "block");
        $("#sub2_duvida_pedido").css("display", "none");
      } else if (opcao == "pendencia_peca_distribuidor") {
        //$(".sub_duvida_pedido").val("");
        $("#sub2_duvida_pedido").css("display", "block");
        $("#sub1_duvida_pedido").css("display", "none");
      }
    });

    $("input[name=duvida_pedido]:checked").click();

    $("input[name=duvida_pecas]").click(function() {
      var opcao = $("input[name=duvida_pecas]:checked").val();

      if (opcao == "obsoleta_indisponivel" || opcao == "substituta" || opcao == "tecnica" || opcao == "devolucao") {
        $(".sub_duvida_pecas").val("");
        $("#sub1_duvida_pecas").css("display", "block");
        $("#sub2_duvida_pecas").css("display", "none");
      } else if (opcao == "nao_consta_lb_ve") {
        $(".sub_duvida_pecas").val("");
        $("#sub2_duvida_pecas").css("display", "block");
        $("#sub1_duvida_pecas").css("display", "none");
      }
    });

    $("input[name=duvida_produto]").click(function() {
      var opcao = $("input[name=duvida_produto]:checked").val();

      if (opcao == "tecnica" || opcao == "troca_produto" || opcao == "produto_substituido" || opcao == "troca_faturada" || opcao == "atendimento_sac") {
        $(".sub_duvida_produto").val("");
        $("#sub1_duvida_produto").css("display", "block");
        $("#sub2_duvida_produto").css("display", "none");
      } else if (opcao == "nao_consta_lb_ve") {
        $(".sub_duvida_produto").val("");
        $("#sub2_duvida_produto").css("display", "block");
        $("#sub1_duvida_produto").css("display", "none");
      }
    });

    $("#sub1_duvida_pedido_btn_add").click(function() {
      $("#sub1_duvida_pedido_table_copiar").clone().prependTo("#sub1_duvida_pedido_table_colar").find("input").val("");
      $('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});
      $(".sub_duvida_pedido_data").mask("99/99/9999");
    });

    $("#sub2_duvida_pedido_btn_add").click(function() {
      $("#sub2_duvida_pedido_table_copiar").clone().prependTo("#sub2_duvida_pedido_table_colar").find("input").val("");
      $('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});
      $(".sub_duvida_pedido_data").mask("99/99/9999");
    });

    var contador  = 1;
    var contador2 = 1;

    $("#sub1_duvida_pecas_btn_add").click(function() {
      var campo = '<table id="sub1_duvida_pecas_table_copiar">'+
            '<tr>'+
              '<td>'+
                '<label style="float:left;">Código da Peça</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label style="float:left; padding-left: 30%;">Descrição da Peça</label>&nbsp;'+
              '</td>'+
            '</tr>'+
            '<tr>'+
              '<td>'+
                '<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_codigo_peca_' + contador +'" name="sub1_duvida_pecas_codigo_peca[]">&nbsp;'+
                '<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_codigo_peca]").val(), null, "referencia", '+ contador +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>&nbsp;&nbsp;&nbsp;'+
                '<input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_descricao_peca_' + contador +'" name="sub1_duvida_pecas_descricao_peca[]">&nbsp;'+
                '<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_descricao_peca]").val(), null, "descricao", '+ contador +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>'+
              '</td>'+
            '</tr>'+
          '</table>';

      contador++;
      $("#sub1_duvida_pecas_table_colar").append(campo);
    });

    $("#sub2_duvida_pecas_btn_add").click(function() {
      var campo = '<tr>'+
                    '<td>'+
                      '<label style="float:left;">Descrição da Peça</label>'+
                    '</td>'+
                  '</tr>'+
                  '<tr>'+
                    '<td>'+
                      '<input type="text" class="frm sub_duvida_pecas" name="sub2_duvida_pecas_descricao_pecas[]">&nbsp;'+
                    '</td>'+
                  '</tr>';
      $("#sub2_duvida_pecas_table_colar").append(campo);
    });

    $("#sub1_duvida_produto_btn_add").click(function() {
      var campo = '<table id="sub1_duvida_produto_table_copiar">'+
            '<tr>'+
              '<td>'+
                '<label style="float:left;">Código Produto</label>&nbsp;<label style="float:left; padding-left: 30%;">Descrição</label>&nbsp;'+
              '</td>'+
            '</tr>'+
            '<tr>'+
              '<td>'+
                '<input type="text" class="frm sub_duvida_produto sub_duvida_produto_referencia_' + contador2 +'" name="sub1_duvida_produto_codigo_produto[]">'+
                '<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(),$("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, ' + contador2 +')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>&nbsp;&nbsp;'+
                '<input type="text" class="frm sub_duvida_produto sub_duvida_produto_descricao_' + contador2 + '" name="sub1_duvida_produto_descricao_produto[]">'+
                '<img src=\'imagens/btn_lupa.gif\' border=\'0\' align=\'absmiddle\' onclick=\'fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(), $("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, ' + contador2 + ')\' alt=\'Clique para efetuar a pesquisa\' style=\'cursor:pointer;\'>'+
              '</td>'+
            '</tr>'+
          '</table>';
      contador2++;
      $("#sub1_duvida_produto_table_colar").append(campo);
    });

    $("#sub2_duvida_produto_btn_add").click(function() {
      $("#sub2_duvida_produto_table_copiar").clone().prependTo("#sub2_duvida_produto_table_colar");
    });

    $("#sub1_erro_fecha_os_btn_add").click(function() {
      var campo = '<table id="sub1_erro_fecha_os_table_copiar">'+
                    '<tr>'+
                      '<td>'+
                        '<label style="float:left;">O.S.</label>&nbsp;'+
                        '<input type="text" class="frm sub_erro_fecha_os" name="sub1_erro_fecha_os_codigo_os[]">&nbsp;'+
                      '</td>'+
                    '</tr>'+
                    '<tr>'+
                      '<td>'+
                      '</td>'+
                    '</tr>'+
                  '</table>';
      $("#sub1_erro_fecha_os_table_colar").append(campo);
    });
  });
    function ocultarDivBlack() {
      $("#duvida_pedido").css("display", "none");
      $("#duvida_pecas").css("display", "none");
      $("#duvida_produto").css("display", "none");
      $("#erro_fecha_os").css("display", "none");
      $("#produto_os").css("display", "none");
      $("#tipos_atualizacao").css("display", "none");
    }

    function verMais() {
      var option = '<option value=""><option value="atualiza_cadastro">Atualização de cadastro </option><option value="manifestacao_sac">Chamados SAC </option><option value="nova_duvida_pecas">Dúvida sobre peças</option><option value="nova_duvida_pedido">Dúvidas sobre pedido</option><option value="nova_duvida_produto">Dúvidas sobre produtos</option><option value="falha_no_site">Falha no site Telecontrol </option><option value="pagamento_antecipado">Pagamento Antecipado </option><option value="pagamento_garantia">Pagamento das garantias/Financeiro </option></option><option value="nova_erro_fecha_os">Problemas no fechamento da O.S.</option><option value="satisfacao_90_dewalt">Satisfação 90 dias DEWALT</option><option value="comunicado_posto">Comunicado Posto</option><option value="gestao_carteira">Gestão de Carteira</option><option value="advertencia">Advertência</option>';

      $("#categoria").html(option);
    }
</script>



<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<link rel="stylesheet" href="js/jquery.autocomplete.css" type="text/css" />
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>

<script src="https://code.jquery.com/jquery-3.0.0.js"></script>
<script src="https://code.jquery.com/jquery-migrate-3.0.1.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<!-- <script type="text/javascript" src="js/fckeditor/fckeditor.js"></script> -->
<script src="../plugins/ckeditor_new/ckeditor.js"></script>

<script src='plugins/jquery.maskedinput_new.js'></script>
<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />

<script type="text/javascript">
function pesquisaPosto(campo,tipo){
var campo = campo.value;

if (jQuery.trim(campo).length > 2){
Shadowbox.open({
content:    "posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
player:     "iframe",
title:      "Pesquisa Posto",
width:      800,
height:     500
});
}else
alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

var campo_descricao;
var campo_referencia;
var campo_voltagem;

function fnc_pesquisa_produto2 (xdescricao, xreferencia, div, posicao = '') {
  var descricao  = jQuery.trim(xdescricao);
  var referencia = jQuery.trim(xreferencia);

  <?php if ($login_fabrica == 1) { ?>
    descricao       = $(".sub_duvida_produto_descricao_" + posicao).val();
    referencia      = $(".sub_duvida_produto_referencia_" + posicao).val();
    var url_posicao = "&posicao=" + posicao;

    if (descricao == undefined && referencia == undefined) {
      var referencia  = $("#referencia").val();
      var descricao   = $("#descricao").val();
    }

    var url_posicao = "&posicao=" + posicao;
    Shadowbox.open({
        content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
        player: "iframe",
        title:      "Pesquisa Produto",
        width:  800,
        height: 500
    });
  <?php } else { ?>
    <?php if($login_fabrica == 42) {?>
      var referencia  = $("#referencia").val();
      var descricao   = $("#descricao").val();
    <?php } else { ?>
      descricao         = $("input[name='"+xdescricao+"']").val();
      referencia        = $("input[name='"+xreferencia+"']").val();
    <?php } ?>
    var url_posicao = "";
    console.log(descricao);
    console.log(referencia);
    if (descricao.length > 2 || referencia.length > 2){
      campo_descricao = xdescricao;
      campo_referencia = xreferencia;

      if (div != undefined && div == "div") {
        campo_voltagem = $(campo_descricao).parent("div").find("input[name=voltagem]");
      } else {
        campo_voltagem = $(campo_descricao).parent("td").parent("tr").find("input[name=voltagem]");
      }

      Shadowbox.open({
        content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + url_posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
        player: "iframe",
        title:      "Pesquisa Produto",
        width:  800,
        height: 500
      });
    }else{
      alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
  <?php } ?>
}
function busca_solucao_produto(defeito){

var produto = $('#produto_hidden').val();

if(defeito != ""){

$(".box-solucoes").html("<em>buscando lista de soluções...</em>");

$.ajax({
url: "<?=$_SERVER['PHP_SELF']?>",
type: "POST",
data: {
busca_solucao_produto: true,
produto: produto,
defeito: defeito
},
complete: function (data) {
data = data.responseText;
$(".box-solucoes").html(data);
}
});

}

}

function busca_solucao_produto2(defeito){

var produto = $('#produto_hidden2').val();

if(defeito != ""){

$(".box-solucoes2").html("<em>buscando lista de soluções...</em>");

$.ajax({
url: "<?=$_SERVER['PHP_SELF']?>",
type: "POST",
data: {
busca_solucao_produto: true,
produto: produto,
defeito: defeito
},
complete: function (data) {
data = data.responseText;
$(".box-solucoes2").html(data);
}
});

}

}

function busca_resposta_padrao(defeito_solucao){

if(defeito_solucao != ""){

$.ajax({
url: "<?=$_SERVER['PHP_SELF']?>",
type: "POST",
data: {
busca_resposta_padrao: true,
defeito_solucao: defeito_solucao
},
complete: function (data) {

$("#utilizar_resposta").val("");
$(".box-utilizar-resposta").hide();

data = $.parseJSON(data.responseText);
if(data.status == true){
var r = alert("Existe uma resposta padrão para essa Solução:\n\n"+data.procedimento +"\n\n");
// var r = alert("Existe uma resposta padrão para essa Solução:\n"+data.procedimento +"\n\nDeseja inserir como resposta do Chamado?");
// var r = confirm("Existe uma resposta padrão para essa Solução, deseja inserir como resposta do Chamado?");
// if(r == true){
// CKEDITOR.instances.resposta.insertText(data.procedimento);
// }else{
// CKEDITOR.instances.resposta.setData("");
// $(".box-utilizar-resposta").show();
// }
}
// }else{
//   CKEDITOR.instances.resposta.setData("");
//   $(".box-utilizar-resposta").show();
// }

}
});

}

}

function busca_procedimento_produto(solucao_id, defeito){

var produto = $('#produto_hidden').val();

if(solucao_id != ""){
$(".box-procedimento").html("<em>buscando procedimentos...</em>");

$.ajax({
url: "<?=$_SERVER['PHP_SELF']?>",
type: "POST",
data: {
busca_procedimento_produto: true,
produto: produto,
solucao_id: solucao_id,
defeito: defeito
},
complete:function(data){
data = data.responseText;
$(".box-procedimento").html(data);
//CKEDITOR.instances.resposta.setData(data);
}
});
}
}

function busca_procedimento_produto2(solucao_id, defeito){

var produto = $('#produto_hidden2').val();

if(solucao_id != ""){
$(".box-procedimento2").html("<em>buscando procedimentos...</em>");

$.ajax({
url: "<?=$_SERVER['PHP_SELF']?>",
type: "POST",
data: {
busca_procedimento_produto: true,
produto: produto,
solucao_id: solucao_id,
defeito: defeito
},
complete:function(data){
data = data.responseText;
$(".box-procedimento2").html(data);
//CKEDITOR.instances.resposta.setData(data);
}
});
}
}

function fnc_pesquisa_produto3 (xdescricao, xreferencia, div) {
    var descricao  = $("input[name='"+xdescricao+"']").val();
    var referencia = $("input[name='"+xreferencia+"']").val();

    if (descricao.length > 2 || referencia.length > 2){
        campo_descricao = xdescricao;
        campo_referencia = xreferencia;

        if (div != undefined && div == "div") {
            campo_voltagem = $(campo_descricao).parent("div").find("input[name=voltagem]");
        } else {
            campo_voltagem = $(campo_descricao).parent("td").parent("tr").find("input[name=voltagem]");
        }

        Shadowbox.open({
            content:    "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
            player: "iframe",
            title:      "Pesquisa Produto",
            width:  800,
            height: 500
        });
    }else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento){
    gravaDados('codigo_posto',codigo_posto);
    gravaDados('nome_posto',nome);
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao) {''

  <?php if ($login_fabrica == 1) { ?>
    if (posicao == undefined || posicao == "undefined" || posicao == "") {
      $("#referencia").val(referencia);
      $("#descricao").val(descricao);
    } else {
      $(".sub_duvida_produto_referencia_" + posicao).val(referencia);
      $(".sub_duvida_produto_descricao_" + posicao).val(descricao);
    }
  <?php } else { ?>
    $('input[name=produto_hidden]').val(produto);
    $('input[name=descricao_os]').val(descricao);
    $('input[name=referencia_os]').val(referencia);
    
    campo_descricao.value = descricao;
    campo_referencia.value = referencia;

    if (campo_voltagem != "" && campo_voltagem.length > 0) {
      campo_voltagem.value = voltagem;
    }
  <?php } ?>


    campo_descricao = "";
    campo_referencia = "";
    campo_voltagem = "";
}

function busca_defeitos_produto(){

    var produto = $('#produto_hidden').val();

    if(produto != ""){

      $(".box-defeitos").html("<em>buscando lista de defeitos...</em>");

      $.ajax({
        url: "<?=$_SERVER['PHP_SELF']?>",
        type: "POST",
        data: {
          busca_defeito_produto: true,
          produto: produto
        },
        complete: function (data) {

          data = data.responseText;

          if(data == ""){
            $(".box-defeitos").html("Defeitos não cadastrados para esse produto.");
          }else{
            $(".box-defeitos").html(data);
            //console.log('2');
            var produto_ref = $('#referencia_os').val();
            $("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+produto_ref);
            $('.box-link').show();
          }

        }
      });

    }else{
      $(".box-defeitos").html("");
    }
    //$("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+produto);
    $('.box-link').show();

  }

  function fnc_tipo_atendimento(tipo) {
    //console.log(tipo.value);
    if (tipo.value == 'Resp.Conclusiva') {
      $("input[name=pendente_acompanhamento]").attr("disabled", true);
      $("input[name=pendente_acompanhamento]").attr("checked", false);
    } else {
      $("input[name=pendente_acompanhamento]").attr("disabled", false);
    }
  }

  function fnc_pendente_encerrar_acompanhemento(tipo){
    if (tipo.value == 'encerrar_acomp') {
      $("input[name=pendente_acompanhamento]").attr("checked", false);
    } else {
      $("input[name=encerrar_acompanhamento]").attr("checked", false);
    }
  }

function gravaDados(name, valor){
    try{
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}

var janela = null;

$(function() {
    $('.sub_duvida_pedido_data').datepick({startDate:'01/01/2000'});
    $(".sub_duvida_pedido_data").mask("99/99/9999");
    if($('#categoria').val() != ''){
        semnome($('#categoria'));
    }

    $('#categoria').change(function(){
        semnome($('#categoria'));
        var fabrica = "<?=$login_fabrica?>";

        if (fabrica == 1) {
          var opcao = $(this).val();
          
          if (opcao == "ver_mais") {
            verMais();
          }

          ocultarDivBlack();

          if (opcao == "nova_duvida_pedido") {
            $("#duvida_pedido").css("display", "block");
          } else if (opcao == "nova_duvida_pecas") {
            $("#duvida_pecas").css("display", "block");
          } else if (opcao == "nova_duvida_produto") {
            $("#duvida_produto").css("display", "block");
          } else if (opcao == "nova_erro_fecha_os") {
            $("#erro_fecha_os").css("display", "block");
          } else if (opcao == "satisfacao_90_dewalt" || opcao == "pagamento_garantia" || opcao == "ver_mais" || opcao == "comunicado_posto" || opcao == "gestao_carteira" || opcao == 'advertencia') {
            $("#fs_params").css("display", "none");
          }

          if (opcao == "falha_no_site" || opcao == "pendencias_de_pecas" || opcao == "pagamento_antecipado" || opcao == "duvidas_telecontrol" || opcao == "duvida_troca" || opcao == "utilizacao_do_site" || opcao == "duvida_produto" || opcao == "duvida_revenda") {
            $("#produto_os").css("display", "block");
          } else if (opcao == "atualiza_cadastro") {
            $("#tipos_atualizacao").css("display", "block");
          }
        }
    });

    $('#tipo_atualizacao').change(function () {
      var novo_valor = $(this).val();
      var status;
      status = (novo_valor=='telefone')           ? 'inline'  : 'none';
      $('#telefone').css('display',status);

      status = (novo_valor=='email')              ? 'inline'  : 'none';
      $('#email').css('display',status);

      status = (novo_valor=='linha_atendimento')  ? 'inline'  : 'none';
      $('#linhas_atendimento').css('display',status);

      status = (novo_valor=='dados_bancarios' || novo_valor=='end_cnp_raz_ban') ? 'inline' : 'none';
      $('#dados_bancarios').css('display',status);
    });


    Shadowbox.init();
    $('#nf_venda').numeric();
    $("input[name=nf_origem_peca]").numeric();
    $("input[name=nf_venda_peca]").numeric();
    $("input[name=nf_origem_prod]").numeric();
    $("input[name=nf_embarque]").numeric();
    $("input[name=nf_prod]").numeric();
    $("input[name=qtde_enviada_emb_prod]").numeric();
    $("input[name=qtde_enviada_emb]").numeric();
    $("input[name=num_extrato]").numeric();
    $("input[name=extratos_peca]").numeric();
    $("input[name=resp_devolucao_produto]").alpha();
    $("input[name=motivo_dev_produto]").alpha();

        function formatItem(row) {
            return row[2] + " - " + row[1];
        }

        function formatResult(row) {
            return row[0];
        }
    $("#codigo_posto").autocomplete("comunicado_produto.php?tipo_busca=posto&busca=codigo", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[2];}
    });

    $("#codigo_posto").result(function(event, data, formatted) {
        $("#nome_posto").val(data[1]) ;
    });

    /* Busca pelo Nome */
    $("#nome_posto").autocomplete("comunicado_produto.php?tipo_busca=posto&busca=nome", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        formatItem: formatItem,
        formatResult: function(row) {return row[1];}
    });

    $("#nome_posto").result(function(event, data, formatted) {
        $("#codigo_posto").val(data[2]) ;
    });
        $("input[name=fone]").mask("(99)9999-9999");
        $("input[name^=data]").mask("99/99/9999");
        $('form').submit(function(){
            $('#peca_faltante option').attr('selected','selected');

            $('#peca_faltante2 option').attr('selected','selected');

            $('#peca_faltante3 option').attr('selected','selected');

            $('#produto_faltante option').attr('selected','selected');
        })
        $('input.numerico').keypress(function (ev) {
//          alert(String.fromCharCode(ev.which));
            if ($(this).attr('name')=='agencia' && (String.fromCharCode(ev.which) == '-' || String.fromCharCode(ev.which) == '.')) {
                $('input[name=conta]').focus();
                return false;
            }
            var numcheck=/\d|-/;
            return numcheck.test(String.fromCharCode(ev.which));
        });
        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

        function busca_defeitos_produto(){

          var produto = $('#produto_hidden').val();
          //var os = $('input[name=os2]').val();

          if(produto != "" ){

            $(".box-defeitos").html("<em>buscando lista de defeitos...</em>");
            $(".box-solucoes").html("");

            $.ajax({
              url: "<?=$_SERVER['PHP_SELF']?>",
              type: "POST",
              data: {
                busca_defeito_produto: true,
                produto: produto
              },
              complete: function (data) {

                data = data.responseText;

                if(data == ""){
                  $(".box-defeitos").html("Defeitos não cadastrados para esse produto.");
                  $(".box-solucoes").html("");
                }else{
                  $(".box-defeitos").html(data);
                  //console.log('3');
                  var produto_ref = $('#referencia_os').val();
                  $("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+produto_ref);
                  $('.box-link').show();
                }

              }
            });

          }else{
            $(".box-defeitos").html("");
            $(".box-solucoes").html("");
          }

        }

        function semnome(param) { //HD 281195
            var fabrica = "<?=$login_fabrica?>";
            var novo_valor = $(param).val();
            var status;
            if (fabrica == 11 || fabrica == 42 || fabrica == 172) {
                if (novo_valor != "outros") {
                    if (novo_valor == '') $('#fs_params').hide();
                    if (novo_valor != '') $('#fs_params').slideDown('fast');
                } else {
                    $('#fs_params').hide();
                }
            } else {
                if (novo_valor == '') $('#fs_params').hide();
                if (novo_valor != '') $('#fs_params').slideDown('fast');
            }
            if (novo_valor == 'atualiza_cadastro') {
                $('#tipos_atualizacao').css('display','inline');
                $('#produto_os,#garantia,#div_produto_de').hide();
            } else if (novo_valor == 'duvida_cadastro') {
                $('#fs_params').hide();
            } else if(novo_valor != 'solicitacao_coleta' && novo_valor != 'pagamento_garantia' && novo_valor != 'erro_embarque'){
                if (fabrica != 1 || (fabrica == 1 && (novo_valor == "falha_no_site" || novo_valor == "pendencias_de_pecas" || novo_valor == "pagamento_antecipado" || novo_valor == "duvidas_telecontrol" || novo_valor == "duvida_troca" || novo_valor == "utilizacao_do_site" || novo_valor == "duvida_produto" || novo_valor == "duvida_revenda"))) {
                  $('#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de').hide();
                  $('#produto_os,#garantia').css('display','inline');
                }
            }else{
                $('#produto_os,#garantia,#tipos_atualizacao,#telefone,#email,#dados_bancarios,#linhas_atendimento,#div_produto_de,#produto_os,#garantia').css('display','none');
            }

            status = (novo_valor == 'manifestacao_sac' || novo_valor == 'servico_atendimeto_sac') ?   'inline': 'none';
            $('#sac').css('display',status);

            status = (novo_valor == 'pendencias_de_pecas' || novo_valor == 'pend_pecas_dist')?'inline':'none';
            $('#pedido_pend').css('display',status);

            status = (novo_valor == 'pend_pecas_dist')  ? 'block'   : 'none';
            $('#distrib').css('display',status);

            status = (novo_valor == 'geo_metais')       ? 'block'   : 'none';
            $('#div_produto_de').css('display',status);

            status = (novo_valor == 'solicitacao_coleta')   ? 'block'   : 'none';
            $('#solicitacao_coleta').css('display',status);

            status = (novo_valor == 'pagamento_garantia')   ? 'block'   : 'none';
            $('#pagamento_garantia').css('display',status);

            status = (novo_valor == 'erro_embarque')    ? 'block'   : 'none';
            $('#erro_embarque').css('display',status);

            status = (novo_valor == 'solicita_informacao_tecnica')  ? 'block'   : 'none';
            $('#solicita_informacao_tecnica').css('display',status);

            status = (novo_valor == 'sugestao_critica') ? 'block'   : 'none';
            $('#sugestao_critica').css('display',status);

            var os = "";
            if(fabrica == 3){
              if(novo_valor.indexOf("tecnica") != -1){
                $('#os1').hide();
                $('#info_produto').hide();
                $('#info_produto2').show();
              }else{

                os = $("input[name=os2]").val();

                if(os != ""){
                  $('#os1').hide();
                  $('#fs_params').show();
                  $('#info_produto2').show();
                  busca_defeitos_produto();
                }else{
                  $("input[name=os2]").val("");
                  $("input[name=produto_hidden]").val("");
                  $('#os1').show();
                  $('#info_produto2').hide();
                }

              }
            }

            if(fabrica == 42){
              if(novo_valor == 'duvida_produto'){
                $('#pecas_div').css('display', 'block')
              }else{
                $('#pecas_div').css('display', 'none')
              }
            }
        }

        //evento para pesquisar OS.
        var login_fabrica = "<?=$login_fabrica?>";

        if (login_fabrica == 3) {
          $("input[name=os2]").change(function () {
            
            if ($.trim($(this).val()).length > 0) {
              var os = $.trim($(this).val());

              $('#produto_hidden').val("");
              $('.box-defeitos').html("");
              $('.box-solucoes').html("");
              $('.box-procedimento').html("");

              $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: { busca_info_produto: true, os: os },
                complete: function (data) {
                  data = $.parseJSON(data.responseText);

                  if (data.erro) {
                    alert(data.erro);
                    $("input[name=os2]").focus();
                    $("input[name=descricao_os]").val("");
                    $("input[name=referencia_os]").val("");
                    $("input[name=produto_hidden]").val("");
                    //$("#info_produto2").hide();
                  } else {

                    $("input[name=descricao_os]").val(data.produto_descricao);
                    $("input[name=referencia_os]").val(data.produto_referencia);
                    $("input[name=produto_hidden]").val(data.produto);
                    //console.log('4');
                    $("#link_href").attr("href", "cadastro_defeitos_solucoes.php?produto_referencia="+data.produto);
                    $(".box-link").show();
                    $("#info_produto2").css({ "display": "inline" });
                    busca_defeitos_produto();

                  }
                }
              });

            } else {
              $("input[name=os2]").focus();
              $("input[name=descricao_os]").val("");
              $("input[name=referencia_os]").val("");
              $("input[name=produto_hidden]").val("");

              $('.box-defeitos').html("");
              $('.box-solucoes').html("");
              //$('.box-procedimento').html("");
              alert("Por favor insira a OS");
              //$("#info_produto2").hide();
            }
          });

          // $("#referencia_os").change(function(){
          //   busca_info_os();
          // });

          $(document).delegate("#solucao_procedimento_prod","change",function(){
            var r = confirm("Deseja alterar o Procedimento de Solução?");
            if(r == true){

              var produto = $('#produto_hidden').val();
              var solucao_id = $('#solucoes_produtos').val();
              var defeito = $('#defeitos_produtos').val();
              var procedimento = $('#solucao_procedimento_prod').val();
              if ($('#hd_chamado').val() != null && $('#hd_chamado').val() != 'undefined') {
                var hd_chamado_p = $('#hd_chamado').val();
              }

              $.ajax({
                url: "<?=$_SERVER['PHP_SELF']?>",
                type: "POST",
                data: {
                  grava_procedimento_produto: true,
                  produto: produto,
                  solucao_id: solucao_id,
                  defeito: defeito,
                  procedimento: procedimento,
                  hd_chamado_p: hd_chamado_p
                },
                complete:function(data){
                  data = $.parseJSON(data.responseText);

                  if (data.erro) {
                    alert(data.erro);
                  } else {
                    alert(data.ok);
                    CKEDITOR.instances.resposta.setData(data.procedimento);
                    //window.location.reload();
                  }
                }
              });

            }


            //$("").text("Novo conteudo");//transferir o valor do campo
          });

        }
        //fim evento de pesquisar OS.

<? /* Deixei este código por se algm dia eles pedem para adicionar estes botões. */ ?>
/*  Finalizar ou excluir chamado
        $('#btnFinalizar').click(function () {
            var valor = $(this).val();
            if (valor == 'Finalizar') {
                $('#motivo_exclusao').css('display','inline');
                $('input[name=btnEnviar]').attr('disabled','disabled');
                $('input[name=btnExcluir]').attr('disabled','disabled');
                return false;
            }
            if (valor == 'FinalizarChamado') {
                $('frm_chamado').submit();
            }
        });
        $('#btnExcluir').click(function () {
            var valor = $(this).val();
            if (valor == 'Excluir') {
                $('#motivo_exclusao').css('display','inline');
                $('input[name=btnEnviar]').attr('disabled','disabled');
                $('input[name=btnFinalizar]').attr('disabled','disabled');
                return false;
            }
            if (valor == 'ExcluirChamado') {
                $('frm_chamado').submit();
            }
        });
        $('#motivo_exclusao').blur(function() {
            if ($(this).val().replace(/[^\s|\s$]/,'') != '') {
                $('#btnExcluir').val('Excluir Chamado');
                $('#btnFinalizar').val('Finalizar Chamado');
            } else {
                $('input[name=btnEnviar]').removeAttr('disabled');
                $('input[name=btnExcluir]').removeAttr('disabled').val('Excluir');
                $('input[name=btnFinalizar]').removeAttr('disabled').val('Finalizar');
                $(this).hide();
            }
        });
*/
        <?php if($categoria == "solicitacao_coleta"){ ?>
        if($("input[name=solic_coleta]").is(":checked")){
            var solict_coleta = $("input[name=solic_coleta]:checked").val();
            if(solict_coleta == "pecas"){
                $("#pecas").show();
                if($("input[name=tipo_dev_peca]").is(":checked")){
                    var tipo_solict_coleta = $("input[name=tipo_dev_peca]:checked").val();
                    mostraCamposPecas(tipo_solict_coleta,'coleta');
                }
            }else if(solict_coleta == "produtos"){
                $("#produtos").show();
                if($("input[name=tipo_dev_prod]").is(":checked")){
                    var tipo_solict_coleta = $("input[name=tipo_dev_prod]:checked").val();
                    mostraCamposProduto(tipo_solict_coleta,'coleta');
                }
            }

        }
        <?php } ?>

        <?php if($categoria == "erro_embarque"){ ?>
        if($("input[name=erro_emb]").is(":checked")){

            var erro_emb = $("input[name=erro_emb]:checked").val();
            if(erro_emb == "produtos"){
                $("#prod_emb").show();
                if($("input[name=tipo_emb_prod]").is(":checked")){
                    var tipo_emb_prod = $("input[name=tipo_emb_prod]:checked").val();
                    mostraCamposProduto(tipo_emb_prod,'embarque');
                }
            }else if(erro_emb == "pecas"){
                $("#pecas_emb").show();
                if($("input[name=tipo_emb_peca]").is(":checked")){
                    var tipo_emb_peca = $("input[name=tipo_emb_peca]:checked").val();
                    mostraCamposPecas(tipo_emb_peca,'embarque');
                }
            }

        }
        <?php } ?>

        <?php if($categoria == "pagamento_garantia"){ ?>
        if($("input[name=duvida]").is(":checked")){
            var pag_garantia = $("input[name=duvida]:checked").val();
            mostraCamposDuvida(pag_garantia);
        }
        <?php } ?>

        $("input[name=solicita_informacao_tecnica]").change(function () {
            if ($(this).val() == "outro") {
                $("input[name=solicita_informacao_tecnica_outro]").val("").show();
            } else {
                $("input[name=solicita_informacao_tecnica_outro]").val("").hide();
            }
        });

    });
    $(document).ready(function(){
      var login_fabrica = "<?php echo $login_fabrica; ?>";
      var atendente_sap = "<?php echo $atendente_sap['admin_sap']; ?>";
      if (login_fabrica == 1 ) {
          if (atendente_sap== 't')  {
              $('#interações').attr('style','background-color: #D9E2EF');
          }
          <?php
          if($admin_sac == false){
          ?>
          if (atendente_sap == 'f'){
            $('#interações').attr('style','display:none');
            $('#btnEnviar').attr('style','display:none');
          }
          <?php
          }
          ?>
      }
    });

    function addItPeca() {
        if ($('#peca_referencia_multi').val()=='') return false;
        if ($('#peca_descricao_multi').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi').val();
        var desc_peca = $('#peca_descricao_multi').val();
        $('#peca_faltante').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

        $('#peca_referencia_multi').val("").focus();
        $('#peca_descricao_multi').val("");
    }

    function delItPeca() {
        var value = $('#peca_faltante option:selected').val();
        $('#peca_faltante option:selected').remove();
		    $("input[value='"+value+"']").remove();
        if($('.select').length ==0) {
            $('#peca_faltante').addClass('select');
        }

    }

    function addItPeca2() {
        if ($('#peca_referencia_multi2').val()=='') return false;
        if ($('#peca_descricao_multi2').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi2').val();
        var desc_peca = $('#peca_descricao_multi2').val();
        $('#peca_faltante2').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");

        if($('.select').length ==0) {
            $('#peca_faltante2').addClass('select');
        }

        $('#peca_referencia_multi2').val("").focus();
        $('#peca_descricao_multi2').val("");
    }

    function delItPeca2() {
        var value = $('#peca_faltante2 option:selected').val();
        $('#peca_faltante2 option:selected').remove();

        if($('.select').length ==0) {
            $('#peca_faltante2').addClass('select');
        }

    }

    function addItPeca3() {
        if ($('#peca_referencia_multi3').val()=='') return false;
        if ($('#peca_descricao_multi3').val()=='') return false;
        var ref_peca  = $('#peca_referencia_multi3').val();
        var desc_peca = $('#peca_descricao_multi3').val();
        var qtde_peca = $('input[name=qtde_enviada_emb]').val();

        if(qtde_peca != ""){
            $('#peca_faltante3').append("<option value='"+ref_peca+"|"+qtde_peca+"'>"+ref_peca+ ' - ' + desc_peca +' - ' + qtde_peca +"</option>");

        }else{
            if($('input[name=tipo_emb_peca]:checked').val() == 1){
                alert('Informe a quantidade');
                return false;
            }else{
                $('#peca_faltante3').append("<option value='"+ref_peca+"'>"+ref_peca+ ' - ' + desc_peca +"</option>");
            }
        }

        if($('.select').length ==0) {
            $('#peca_faltante3').addClass('select');
        }

        $('#peca_referencia_multi3').val("").focus();
        $('#peca_descricao_multi3').val("");
        $('input[name=qtde_enviada_emb]').val("");
    }

    function delItPeca3() {
        var value = $('#peca_faltante3 option:selected').val();
        $('#peca_faltante3 option:selected').remove();

        if($('.select').length ==0) {
            $('#peca_faltante3').addClass('select');
        }

    }

    function addItProduto() {
        if ($('#produto_referencia_multi').val()=='') return false;
        if ($('#produto_descricao_multi').val()=='') return false;
        var ref_produto  = $('#produto_referencia_multi').val();
        var desc_produto = $('#produto_descricao_multi').val();
        var qtde_produto = $('#produto_qtde_enviado').val();
        $('#produto_faltante').append("<option value='"+ref_produto+"|"+qtde_produto+"'>"+ref_produto+ ' - ' + desc_produto +' - ' + qtde_produto +"</option>");

        if($('.select').length ==0) {
            $('#produto_faltante').addClass('select');
        }

        $('#produto_referencia_multi').val("").focus();
        $('#produto_descricao_multi').val("");
        $('#produto_qtde_enviado').val("");
    }

    function delItProduto() {
        $('#produto_faltante option:selected').remove();
        if($('.select').length ==0) {
            $('#produto_faltante').addClass('select');
        }

    }

    $(window).load(function(){
      var aux_toolbar = [
        { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
        { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
        { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
        { name: 'tools', items: ['Maximize'] },
        '/',
        { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike' , '-', 'RemoveFormat'] },
        { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote'] },
        { name: 'styles', items: ['Styles', 'Format'] },
        { name: 'font', items: ['TextColor', 'BGColor'] }
      ]; 

        CKEDITOR.replace("resposta", { enterMode : CKEDITOR.ENTER_BR, toolbar : aux_toolbar, uiColor : '#A0BFE0', disableNativeSpellChecker: false, width: '100%' });
        
        setTimeout(function(){
            $(".cke_button__image").hide();
            $(".cke_button__table").hide();
        },1000);
    });

    function mostraCampos(valor,tipo){
        if(tipo == "coleta"){
            if(valor == 'pecas'){
                $('#pecas').attr('style','display:block');
                $('#produtos').attr('style','display:none');
            } else {
                $('#produtos').attr('style','display:block');
                $('#pecas').attr('style','display:none');
            }
        }else if(tipo == "embarque"){
            if(valor == 'pecas'){
                $('#pecas_emb').attr('style','display:block');
                $('#prod_emb').attr('style','display:none');
            } else {
                $('#prod_emb').attr('style','display:block');
                $('#pecas_emb').attr('style','display:none');
            }
        }
    }

  function mostraCamposDuvida(valor){
    if(valor == "aprova"){
      $('#campos_duvida').attr('style','display:block');
      $('#data_fech').attr('style','display:table-cel');
      $('#data_env').attr('style','display:none');
      $('#extrato_num').attr('style','display:none');
      $('#obj_num').attr('style','display:none');
    } else if(valor == "pendente" || valor == "bloqueado"){
      $('#campos_duvida').attr('style','display:block');
      $('#extrato_num').attr('style','display:table-cel');
      $('#data_fech').attr('style','display:none');
      $('#data_env').attr('style','display:none');
      $('#obj_num').attr('style','display:none');
    } else if(valor == "documentos"){
      $('#campos_duvida').attr('style','display:block');
      $('#extrato_num').attr('style','display:table-cel');
      $('#data_fech').attr('style','display:none');
      $('#data_env').attr('style','display:table-cel');
      $('#obj_num').attr('style','display:table-cel');
    }
  }

    function mostraCamposPecas(valor,tipo){
        if(tipo == "coleta"){
            if(valor == 1){
                $('#peca_enviada').attr('style','display:block');
                $('#devolucao_peca').attr('style','display:none');
            } else {
                $('#devolucao_peca').attr('style','display:block');
                $('#peca_enviada').attr('style','display:none');
            }
        }else if(tipo == "embarque"){
            if(valor == 1){
                $('#peca_emb_campos').attr('style','display:block');
                $('#qtde_enviada_emb').attr('style','display:table-cel');
                $('#peca_pend_emb').attr('style','display:table-cel');
            } else if(valor == 2){
                $('#peca_emb_campos').attr('style','display:block');
                $('#qtde_enviada_emb').attr('style','display:none');
                $('#peca_pend_emb').attr('style','display:table-cel');
            } else if(valor == 3){
                $('#peca_emb_campos').attr('style','display:block');
                $('#qtde_enviada_emb').attr('style','display:none');
                $('#peca_pend_emb').attr('style','display:none');
            }
        }
    }

    function mostraCamposProduto(valor,tipo){

        if(tipo == "coleta"){
            if(valor == 1){
                $('#produto_fabrica').attr('style','display:block');
                $('#modelos_produtos').attr('style','display:table-cel');
                $('#produto_fabrica_analise').attr('style','display:none')
                $('#produto_novo_embalagem_motivo').attr('style','display:none');
                $('#produto_novo_embalagem_os').attr('style','display:none');
            } else if(valor == 2){
                $('#produto_fabrica').attr('style','display:block');
                $('#modelos_produtos').attr('style','display:table-cel');
                $('#produto_fabrica_analise').attr('style','display:table-cel');
                $('#produto_novo_embalagem_motivo').attr('style','display:none');
                $('#produto_novo_embalagem_os').attr('style','display:none');
            } else {
                $('#produto_fabrica').attr('style','display:block');
                $('#produto_fabrica_analise').attr('style','display:none');
                $('#produto_novo_embalagem_motivo').attr('style','display:table-cel');
                $('#produto_novo_embalagem_os').attr('style','display:table-cel');

            }
        }else if(tipo == "embarque"){
            if(valor == 1){
                $('#prod_emb_campos').attr('style','display:block');
                $('#modelo_prod_emb').attr('style','display:table-cel');
                $('#modelo_prod_env_emb').attr('style','display:table-cel');
                $('#acess_faltantes_emb').attr('style','display:none');
                $('#qtde_enviada_emb_prod').attr('style','display:none');
            } else if(valor == 2){
                $('#prod_emb_campos').attr('style','display:block');
                $('#modelo_prod_emb').attr('style','display:table-cel');
                $('#acess_faltantes_emb').attr('style','display:table-cel');
                $('#modelo_prod_env_emb').attr('style','display:none');
                $('#qtde_enviada_emb_prod').attr('style','display:none');
            } else if(valor == 3){
                $('#prod_emb_campos').attr('style','display:block');
                $('#modelo_prod_env_emb').attr('style','display:none');
                $('#modelo_prod_emb').attr('style','display:table-cel');
                $('#acess_faltantes_emb').attr('style','display:none');
                $('#qtde_enviada_emb_prod').attr('style','display:none');
            } else if(valor == 4){
                $('#prod_emb_campos').attr('style','display:block');
                $('#modelo_prod_emb').attr('style','display:none');
                $('#qtde_enviada_emb_prod').attr('style','display:table-cel');
                $('#modelo_prod_env_emb').attr('style','display:none');
                $('#acess_faltantes_emb').attr('style','display:none');
            }
        }
    }

</script>

<? include "javascript_pesquisas_novo.php"; ?>

<script>
function fnc_pesquisa_peca (campo, campo2, tipo, posicao = '') {
    var fabrica = '<?=$login_fabrica;?>';

    if (tipo == "referencia" ) {
      if (fabrica == "1") {
        var xcampo = $(".sub_duvida_pecas_codigo_peca_" + posicao).val();
      } else {
        var xcampo = campo;
      }
    }

    if (tipo == "descricao" ) {
      if (fabrica == "1") {
        var xcampo = $(".sub_duvida_pecas_descricao_peca_" + posicao).val();
      } else {
        var xcampo = campo2;  
      }
    }


    if (xcampo.value != "") {
        var url = "";

        if (fabrica == "1") {
          url = "peca_pesquisa.php?campo=" + xcampo + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao;
        } else {
          url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo + "&multipeca=true" + "&posicao=" + posicao;
        }

        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
        janela.referencia = campo;
        janela.descricao  = campo2;
        janela.focus();
    }
    else{
        alert("Informe toda ou parte da informação para realizar a pesquisa");
    }
}

  function retorna_peca(referencia ,descricao, posicao) {
    $(".sub_duvida_pecas_codigo_peca_" + posicao).val(referencia);
    $(".sub_duvida_pecas_descricao_peca_" + posicao).val(descricao);
  }
</script>

<?
        $mostrar_produto    = "display:none";
        $mostrar_produto_de = "display:none";
        $mostrar_fone       = "display:none";
        $mostrar_email      = "display:none";
        $mostrar_banco      = "display:none";
        $mostrar_linhas     = "display:none";
        if($categoria == 'atualiza_cadastro') {
            $mostrar        = "display:inline";
            if($tipo_atualizacao=='telefone') {
                $mostrar_fone= $mostrar;
            }
            if($tipo_atualizacao=='email') {
                $mostrar_email= $mostrar;
            }
            if($tipo_atualizacao=='linha_atendimento') {
                $mostrar_linhas = $mostrar;
            }
            if($tipo_atualizacao=='dados_bancarios' or $tipo_atualizacao == 'end_cnp_raz_ban') {
                $mostrar_banco= $mostrar;
            }
            $mostrar_produto = "display:none";
        }else{
            $mostrar= "display:none";
        }

    if(empty($nome_cliente) and empty($atendente_sac)) {
        $mostrar_sac = "display:none";
    }else{
        if($categoria == 'manifestacao_sac' || $categoria == 'servico_atendimeto_sac') {
            $mostrar_sac = "display:inline";
        }
    }
    $mostrar_coleta = "display:none";
    $mostrar_duvida = "display:none";
    $mostrar_embarque = "display:none";

    if ($categoria == 'geo_metais') $mostrar_produto_de = 'display:inline';

    if($categoria == 'pendencias_de_pecas' or $categoria == 'pend_pecas_dist') {
        $mostrar_pedido = "display:inline";
    }else{
        $mostrar_pedido = "display:none";
    }

?>
<br><br>
<div class='box azul' style='width:700px;margin-left:auto;margin-right:auto;padding: 1ex 1ex'>
    <form action="<?=$PHP_SELF?>" method="POST" name='frm_chamado' id='frm_chamado' enctype="multipart/form-data">
        <fieldset>
            <legend>Informações do Chamado</legend>
            <? if ( in_array($login_fabrica, array(11,42,172)) ) { ?>
                <label><input type="checkbox" name="chamado_interno" value="interno" />Chamado Interno <span style="color: #e00;">(* somente os admins visualizarão este chamado)</span></label><br /><br />
            <? } ?>
            <label for="codigo_posto">Código do Posto <span class='vermelho'>*&nbsp;</span> </label>
            <input width="200" type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_chamado.codigo_posto, 'codigo')">
            <label for="nome">Razão Social <span class='vermelho'>*&nbsp;</span> </label>
            <input type="text" name="nome_posto" id="nome_posto" size="40" value="<?echo $nome_posto ?>" class="frm">
            <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_chamado.nome_posto, 'nome')">
            <BR><BR>
            <label for="categoria">Tipo de Solicitação <span class='vermelho'>*&nbsp;</span> </label>
            <select name="categoria" id="categoria" class='frm' >
                <option value=''></option>  <?

                foreach ($categorias as $categoria => $config) {

                  if($login_fabrica == 1){

                    if($admin_sac == true){

                      if($categoria == "servico_atendimeto_sac"){
                        if ($config['no_fabrica']) {
                          if (in_array($login_fabrica, $config['no_fabrica'])) {
                              continue;
                          }
                        }
                        echo CreateHTMLOption($categoria, $config['descricao'], $_POST['categoria']);
                      }

                    }else{

                      if ($config['no_fabrica']) {
                        if (in_array($login_fabrica, $config['no_fabrica'])) {
                            continue;
                        }
                      }

                      if($login_fabrica == 1){

                        if($admin_sac != true && $categoria == "servico_atendimeto_sac"){
                          continue;
                        }else{
                            if (in_array($categoria, array("duvida_troca", "duvida_produto", "duvida_revenda", "erro_embarque", "pend_pecas_dist", "solicitacao_coleta", "duvidas_telecontrol", "utilizacao_do_site", "manifestacao_sac", "atualiza_cadastro", "falha_no_site", "pagamento_garantia", "pagamento_antecipado", "satisfacao_90_dewalt", "pendencias_de_pecas"))) {
                              continue;
                            } else {
                              if($categoria == "servico_atendimeto_sac"){
                                continue;
                              }else{
                                /*HD - 6065678*/
                                if ($categoria == "pagamento_garantia") {
                                  $config["descricao"] = "Pagamento das garantias/Financeiro";
                                }
                                
                                echo CreateHTMLOption($categoria, $config['descricao'], $_POST['categoria']);
                              }
                            }
                        }
                      }else{

                         echo CreateHTMLOption($categoria, $config['descricao'], $_POST['categoria']);

                      }

                    }

                  }else{

                     if ($config['no_fabrica']) {
                        if (in_array($login_fabrica, $config['no_fabrica'])) {
                            continue;
                        }
                    }

                    $categoria_selecionada = ($categoria == $_POST['categoria']) ? $_POST['categoria'] : '';

                    echo CreateHTMLOption($categoria, $config['descricao'], $categoria_selecionada);

                  }

                }

                if ($login_fabrica == 1) {
                  $tipos_extras = array(
                    "nova_duvida_pecas"   => "Dúvida sobre peças",
                    "nova_duvida_pedido"  => "Dúvidas sobre pedido",
                    "nova_duvida_produto" => "Dúvidas sobre produtos",
                    "nova_erro_fecha_os"  => "Problemas no fechamento da O.S.",
                    "advertencia"         => "Advertência",
                    "ver_mais"            => "Ver Mais",
                  );

                  foreach ($tipos_extras as $categoria => $descricao_categ) {
                    if($categoria == $_POST['categoria']){
                      $selected = " selected ";
                    }else{
                      $selected = "";
                    }
                    
                    echo "<option value='$categoria' $selected >$descricao_categ</option>";
                  }
                } ?>
            </select>
            &nbsp;&nbsp;<br /><br />
            <fieldset style='display:inline'>
                <legend>Produto em Garantia</legend>
                <input type="radio" name="garantia" value="t" <? if($garantia=='t') echo 'checked'; ?>/>Sim
                <input type="radio" name="garantia" value="f" <? if($garantia!='t') echo 'checked'; ?>/>Não
            </fieldset>
            <br><br>
            <label style='text-align: right;left: 79px;display:inline-block;width: 200px;_zoom:1'>
                Responsável pela Solicitação <span class='vermelho'>*&nbsp;</span>
            </label>
            <input type="text" name="usuario_sac" value="<?=$login_login?>" class="frm">
            <br><br>
            <br>
        </fieldset>
        <br>
        <fieldset for='parametros_chamado' id='fs_params' style='text-align:left;display:none'>
            <legend>Informações adicionais</legend>
            <?php if ($login_fabrica == 1) { ?>
            <div id="duvida_pedido" style="display: <?php if ($_POST["categoria"] == "nova_duvida_pedido") echo "block"; else echo "none";?>">
              <fieldset style="width:500px;float:left;text-align:left;">
                <legend>Produtos</legend>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_informacao_recebimento">
                  <input type="radio" name="duvida_pedido" id="duvida_pedido_informacao_recebimento" value="informacao_recebimento"
                  <?= ($_POST['duvida_pedido'] == 'informacao_recebimento') ? "checked" : "" ?>
                  >&nbsp;Informação de Recebimento
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_divergencia_recebimento">
                  <input type="radio" name="duvida_pedido" id="duvida_pedido_divergencia_recebimento" value="divergencia_recebimento"
                  <?= ($_POST['duvida_pedido'] == 'divergencia_recebimento') ? "checked" : "" ?>
                  >&nbsp;Divergências no recebimento
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_pendencia_peca_fabrica">
                  <input type="radio" name="duvida_pedido" id="duvida_pedido_pendencia_peca_fabrica" value="pendencia_peca_fabrica"
                  <?= ($_POST['duvida_pedido'] == 'pendencia_peca_fabrica') ? "checked" : "" ?>
                  >&nbsp;Pendências de peças com a fábrica
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pedido_pendencia_peca_distribuidor">
                  <input type="radio" name="duvida_pedido" id="duvida_pedido_pendencia_peca_distribuidor" value="pendencia_peca_distribuidor"
                  <?= ($_POST['duvida_pedido'] == 'pendencia_peca_distribuidor') ? "checked" : "" ?>
                  >&nbsp;Pendências de peças com o distribuidor
                </label><br>
                <div id="sub1_duvida_pedido" style="display:none;">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub1_duvida_pedido_btn_add" id="sub1_duvida_pedido_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <?php
                  if (isset($_POST['sub1_duvida_pedido_numero_pedido'])) { 

                    foreach ($_POST['sub1_duvida_pedido_numero_pedido'] as $key => $pedido) {

                    ?>
                      <table id="sub1_duvida_pedido_table_copiar">
                        <tr>
                          <td>
                            <label style="float:left;">Número do Pedido</label>
                            <label style="float:left; padding-left: 22%;">Data do Pedido</label>&nbsp;
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <input type="text" class="frm sub_duvida_pedido" name="sub1_duvida_pedido_numero_pedido[]" value="<?= $pedido ?>" />&nbsp;
                            <input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub1_duvida_pedido_data_pedido[]" value="<?= $_POST['sub1_duvida_pedido_data_pedido'][$key] ?>" />
                          </td>
                        </tr>
                      </table>
                  <?php
                    }

                  } else {
                  ?>
                    <table id="sub1_duvida_pedido_table_copiar">
                      <tr>
                        <td>
                          <label style="float:left;">Número do Pedido</label>
                          <label style="float:left; padding-left: 22%;">Data do Pedido</label>&nbsp;
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <input type="text" class="frm sub_duvida_pedido" name="sub1_duvida_pedido_numero_pedido[]" value="">&nbsp;
                          <input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub1_duvida_pedido_data_pedido[]" value="">
                        </td>
                      </tr>
                    </table>
                  <?php
                  }
                  ?>
                  <div id="sub1_duvida_pedido_table_colar"></div>
                </div>
                <div id="sub2_duvida_pedido" style="display:none">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub2_duvida_pedido_btn_add" id="sub2_duvida_pedido_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <?php
                  if (isset($_POST['sub2_duvida_pedido_numero_pedido'])) { 

                    foreach ($_POST['sub2_duvida_pedido_numero_pedido'] as $key => $pedido) {

                    ?>
                    <table id="sub2_duvida_pedido_table_copiar">
                      <tr>
                        <td>
                          <label style="float:left;">Número do Pedido</label>
                          <label style="float: left; padding-left: 15%;">Data do Pedido</label>
                          <label style="float: left; padding-left: 18%;">Nome do Distribuidor</label>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_numero_pedido[]" value="<?= $pedido ?>" />&nbsp;
                          <input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub2_duvida_pedido_data_pedido[]" value="<?= $_POST['sub2_duvida_pedido_data_pedido'][$key] ?>" />&nbsp;
                          <input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_nome_distribuidor[]" value="<?= $_POST['sub2_duvida_pedido_nome_distribuidor'][$key] ?>" />
                        </td>
                      </tr>
                    </table>
                  <?php
                    }

                  } else {
                  ?>
                    <table id="sub2_duvida_pedido_table_copiar">
                      <tr>
                        <td>
                          <label style="float:left;">Número do Pedido</label>
                          <label style="float: left; padding-left: 15%;">Data do Pedido</label>
                          <label style="float: left; padding-left: 18%;">Nome do Distribuidor</label>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_numero_pedido[]">&nbsp;
                          <input type="text" class="frm sub_duvida_pedido sub_duvida_pedido_data" name="sub2_duvida_pedido_data_pedido[]">&nbsp;
                          <input type="text" class="frm sub_duvida_pedido" name="sub2_duvida_pedido_nome_distribuidor[]">
                        </td>
                      </tr>
                    </table>
                  <?php
                  }
                  ?>
                  <div id="sub2_duvida_pedido_table_colar"></div>
                </div>
              </fieldset>
            </div>
            <div id="duvida_pecas" style="display: <?php if ($_POST["categoria"] == "nova_duvida_pecas") echo "block"; else echo "none";?>">
              <fieldset style="width:500px;float:left;text-align:left;">
                <legend>Peças</legend>
                <label style="font-size: 10px; cursor: pointer;" for="duvida_pecas_obsoleta_indisponivel">
                  <input type="radio" name="duvida_pecas" id="duvida_pecas_obsoleta_indisponivel" value="obsoleta_indisponivel">&nbsp;Obsoleta / Indisponível
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_substituta">
                  <input type="radio" name="duvida_pecas" id="duvida_pecas_substituta" value="substituta">&nbsp;Substituta
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_tecnica">
                  <input type="radio" name="duvida_pecas" id="duvida_pecas_tecnica" value="tecnica">&nbsp;Técnica
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_devolucao">
                  <input type="radio" name="duvida_pecas" id="duvida_pecas_devolucao" value="devolucao">&nbsp;Devolução
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_pecas_nao_consta_lb_ve">
                  <input type="radio" name="duvida_pecas" id="duvida_pecas_nao_consta_lb_ve" value="nao_consta_lb_ve">&nbsp;Não consta na lista básica/vista explodida
                </label><br>
                <div id="sub1_duvida_pecas" style="display:none;">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub1_duvida_pecas_btn_add" id="sub1_duvida_pecas_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <table id="sub1_duvida_pecas_table_copiar">
                    <tr>
                      <td>
                        <label style="float:left;">Código da Peça</label>&nbsp;
                        <label style="float:left; padding-left: 30%;">Descrição da Peça</label>&nbsp;
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_codigo_peca_0" name="sub1_duvida_pecas_codigo_peca[]">&nbsp;
                        <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_codigo_peca]").val(), null, "referencia", 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>&nbsp;&nbsp;&nbsp;
                        <input type="text" class="frm sub_duvida_pecas sub_duvida_pecas_descricao_peca_0" name="sub1_duvida_pecas_descricao_peca[]">&nbsp;
                        <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_peca($("input[name^=sub1_duvida_pecas_descricao_peca]").val(), null, "descricao", 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                      </td>
                    </tr>
                  </table>
                  <div id="sub1_duvida_pecas_table_colar"></div>
                </div>
                <div id="sub2_duvida_pecas" style="display:none">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub2_duvida_pecas_btn_add" id="sub2_duvida_pecas_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <table id="sub2_duvida_pecas_table_copiar">
                    <tr>
                      <td>
                        <label style="float:left;">Descrição da Peça</label>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <input type="text" class="frm sub_duvida_pecas" name="sub2_duvida_pecas_descricao_pecas[]">&nbsp;
                      </td>
                    </tr>
                  </table>
                  <div id="sub2_duvida_pecas_table_colar"></div>
                </div>
              </fieldset>
            </div>
            <div id="duvida_produto" style="display: <?php if ($_POST["categoria"] == "nova_duvida_produto") echo "block"; else echo "none";?>">
              <fieldset style="width:500px;float:left;text-align:left;">
                <legend>Produtos</legend>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_tecnica">
                  <input type="radio" name="duvida_produto" id="duvida_produto_tecnica" value="tecnica">&nbsp;Técnica
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_troca_produto">
                  <input type="radio" name="duvida_produto" id="duvida_produto_troca_produto" value="troca_produto">&nbsp;Troca de produto
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_substituido">
                  <input type="radio" name="duvida_produto" id="duvida_produto_produto_substituido" value="produto_substituido">&nbsp;Produto substituto / Kit
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_substituido">
                  <input type="radio" name="duvida_produto" id="duvida_produto_produto_substituido" value="troca_faturada">&nbsp;Troca faturada
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_atendimento_sac">
                  <input type="radio" name="duvida_produto" id="duvida_produto_produto_atendimento_sac" value="atendimento_sac">&nbsp;Atendimento pelo SAC
                </label><br>
                <label style="font-size: 11px; cursor: pointer;" for="duvida_produto_produto_nao_consta_lb_ve">
                  <input type="radio" name="duvida_produto" id="duvida_produto_produto_nao_consta_lb_ve" value="nao_consta_lb_ve">&nbsp;Produto não cadastrado/sem lista básica/vista explodida
                </label><br>
                <div id="sub1_duvida_produto" style="display:none;">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub1_duvida_produto_btn_add" id="sub1_duvida_produto_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <table id="sub1_duvida_produto_table_copiar">
                    <tr>
                      <td>
                        <label style="float:left;">Código Produto</label>&nbsp;
                        <label style="float:left; padding-left: 30%;">Descrição</label>&nbsp;
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <input type="text" class="frm sub_duvida_produto sub_duvida_produto_referencia_0" name="sub1_duvida_produto_codigo_produto[]">
                        <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(),$("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>&nbsp;&nbsp;
                        <input type="text" class="frm sub_duvida_produto sub_duvida_produto_descricao_0" name="sub1_duvida_produto_descricao_produto[]">
                        <img src='imagens/btn_lupa.gif' border='0' align='absmiddle' onclick='fnc_pesquisa_produto2($("input[name^=sub1_duvida_produto_descricao_produto]").val(), $("input[name^=sub1_duvida_produto_codigo_produto]").val(), null, 0)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>
                      </td>
                    </tr>
                  </table>
                  <div id="sub1_duvida_produto_table_colar"></div>
                </div>
                <div id="sub2_duvida_produto" style="display:none">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub2_duvida_produto_btn_add" id="sub2_duvida_produto_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <table id="sub2_duvida_produto_table_copiar">
                    <tr>
                      <td>
                        <label style="float:left;">Descrição do Produto</label>
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <input type="text" class="frm sub_duvida_produto" name="sub2_duvida_produto_descricao_produto[]">&nbsp;
                      </td>
                    </tr>
                  </table>
                  <div id="sub2_duvida_produto_table_colar"></div>
                </div>
              </fieldset>
            </div>
            <div id="erro_fecha_os" style="display: <?php if ($_POST["categoria"] == "nova_erro_fecha_os") echo "block"; else echo "none"?>;">
              <fieldset style="width:500px;float:left;text-align:left;">
                <legend>Ordem de Serviço</legend>
                <div id="sub1_erro_fecha_os">
                  <br>
                  <table>
                    <tr>
                      <td>
                        <input type="button" name="sub1_erro_fecha_os_btn_add" id="sub1_erro_fecha_os_btn_add" value="Adicionar" style="background-color: green; color: white; float: left; cursor: pointer;">
                      </td>
                    </tr>
                  </table>
                  <table id="sub1_erro_fecha_os_table_copiar">
                    <tr>
                      <td>
                        <label style="float:left;">O.S.</label>&nbsp;
                        <input type="text" class="frm sub_erro_fecha_os" name="sub1_erro_fecha_os_codigo_os[]">&nbsp;
                      </td>
                    </tr>
                    <tr>
                      <td>
                      </td>
                    </tr>
                  </table>
                  <div id="sub1_erro_fecha_os_table_colar"></div>
                </div>
              </fieldset>
            </div>
            <?php } ?>
            <div id='produto_os' style='position:relative;margin-top:1em;<?=$mostrar_produto?>; text-align: center;'>
            <? if ($login_fabrica <> 3) { 
            if ($login_fabrica == 1) { ?>
              <table>
                <tr style="float-left: 0px;">
                  <td>
                    <label>Produto</label>
                  </td>
                  <td>
                    <input type='text' class='frm' name='referencia' id='referencia' size='20' value='<?=$referencia?>'>&nbsp;
                    <input type='hidden' name='voltagem' size='20'>
                    <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao, document.frm_chamado.referencia,'div') " height='22px' style='cursor: pointer'>
                  </td>
                  <td>
                    <label>Descrição</label>
                  </td>
                  <td>
                    <input type='text' class='frm' name='descricao' id='descricao' size='20' value='<?=$descricao?>'>&nbsp;
                      <input type='hidden' name='voltagem' size='20'>
                      <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao, document.frm_chamado.referencia,'div') " height='22px' style='cursor: pointer'>
                  </td>
                  <td>
                      <label>OS Fábrica</label>
                    </td>
                    <td>
                      <input type='text' class='frm' name='os' size='15' value='<?=$os?>' class='numerico'>
                  </td>
                </tr>
              </table>
            <?php } else { ?>
            Produto&nbsp;   <input type='text' class='frm' name='referencia' id='referencia' size='20' value='<?=$referencia?>'>
                            <input type='hidden' name='voltagem' size='20'>
                            <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
                             onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao, document.frm_chamado.referencia,'div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;
            Descrição&nbsp; <input type='text' class='frm' name='descricao' id='descricao' size='20' value='<?=$descricao?>'>
                            <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18'
                             onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao, document.frm_chamado.referencia,'div') " height='22px' style='cursor: pointer'>&nbsp;&nbsp;&nbsp;
            <? } 
            } ?>
            <?php
              if($login_fabrica != 1) { ?>
              <span id="os1">
                <label>OS&nbsp;</label>
                    <input type='text' class='frm' name='os' size='15' value='<?=$os?>' class='numerico'>
              </span>
              <?php if($login_fabrica == 42){?>
              <table id="pecas_div" style="display:none">
                <tr>
                  <td>Peça causadora</td>
                  <td>Código:</td>
                  <td>Descrição:</td>
                  <td>&nbsp;</td>
                </tr>
                <tr>
                  <td></td>
                  <td>
                    <input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="" size="10" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'referencia')"  style='cursor:pointer;' align="absmiddle">
                  </td>
                  <td>
                    <input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="15" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'descricao')"  style='cursor:pointer;' align='absmiddle'>
                  </td>
                  <td>
                    <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();'>
                  </td>
                </tr>
                  
                  </td><br>
                </tr>
                <tr><td colspan="4">
                  <span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
                  <br></td>
                </tr>
                  <tr>
                  <td colspan="3" id="pecas">
                    <select SIZE='6' id='peca_faltante' class='select' name="peca_faltante[]" class='frm' style="width: 390px;">
                      
                    </select>
                  </td>
                  <td><input type="button" value="Remover" onClick="delItPeca();" class='frm'>
                </td>
                <tr>
                  <td>Defeito: </td>
                  <td>
                    <select id="defeito" name="defeito" style="width: 266px;" >
                    <option value=""></option>
                    <option value="Curto">Curto</option>
                    <option value="Quebra">Quebra</option>
                    <option value="Instrução de Montagem">Instrução de Montagem</option>
                    <option value="Falta de Peça">Falta de Peça</option>
                    <option value="Consulta Código">Consulta Código</option>
                    <option value="Manutenção Inadequada">Manutenção Inadequada</option>
                    <option value="Fundido / Travado">Fundido / Travado</option>
                    <option value="Desgastado">Desgastado</option>
                    <option value="Lamina do coletor solta">Lamina do coletor</option>
                    <option value="Verniz derretido">Verniz derretido</option>
                    <option value="Ruído">Ruído</option>
                    <option value="Sem lubrificação">Sem lubrificação</option>
                    <option value="Excesso de lubrificação">Excesso de lubrificação</option>
                    <option value="Fio rompido">Fio rompido</option>
                    <option value="Conector com zinabre">Conector com zinabre</option>
                    <option value="Mau contato">Mau contato</option>
                    <option value="Sem afiação">Sem afiação</option>
                    <option value="Desajustado">Desajustado</option>
                    <option value="Empenado">Empenado</option>
                    <option value="Amassado">Amassado</option>
                    <option value="Desalinhado">Desalinhado</option>
                    <option value="Não Liga">Não Liga</option>
                    <option value="Não Carrega">Não Carrega</option>
                    <option value="Não Identificado">Não Identificado</option>
                    <option value="Deformada">Deformada</option>
                    <option value="Vazamento">Vazamento</option>
                    <option value="Sobreaquecida">Sobreaquecida</option>
                    <option value="Interferência">Interferência</option>
                    <option value="Folga Excessiva">Folga Excessiva</option>
                    <option value="Montagem Incorreta">Montagem Incorreta</option>
                    <option value="Peça Paralela">Peça Paralela</option>
                    <option value="Com Limalha">Com Limalha</option>
                    <option value="Solicitação Vista explodida">Solicitação Vista explodida</option>
                    <option value="Fora de Linha">Fora de Linha</option>
                    <option value="Importada">Importada</option>
                    <option value="Visita Técnica">Visita Técnica</option>
                    <option value="Consulta Preço">Consulta Preço</option>
                    <option value="Rasgado">Rasgado</option>
                    <option value="Arranhado">Arranhado</option>
                    <option value="Riscado">Riscado</option>
                    <option value="Descolado">Descolado</option>
                    <option value="Perdido">Perdido</option>
                    <option value="Cortado">Cortado</option>
                    <option value="Qualidade do Combustível">Qualidade do Combustível</option>
                    <option value="Combustível Inadequado">Combustível Inadequado</option>
                    <option value="Má conservação">Má conservação</option>
                    <option value="Sujo">Sujo</option>
                    <option value="Contaminado">Contaminado</option>
                    <option value="Outros">Outros</option>
                    </select>
                  </td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
              </table>
              <?php } ?>
              
              <?php
              if($login_fabrica == 3){?>
                <div id="info_produto2" style='<?=(empty($os2)) ? "display: none;" : "display: inline;"?>'>
                  <label>OS&nbsp;</label>
                      <input type='text' class='frm' name='os2' size='10' value='<?=$os2?>'>
                  &nbsp; &nbsp;
                  <label>Produto &nbsp; </label>
                      <input type='text' class='frm' name='referencia_os' id='referencia_os' size='20' value='<?=$referencia?>'>
                <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto3 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'> &nbsp; &nbsp;
              <label>Descrição &nbsp; </label>
                      <input type='text' class='frm' name='descricao_os' id='descricao_os' size='20' value='<?=$descricao?>'>
                <img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' height='18' onclick="javascript: fnc_pesquisa_produto3 ('descricao_os','referencia_os','div') " height='22px' style='cursor: pointer'>

              <input type="hidden" name="produto_hidden" id="produto_hidden" value="<?php echo $produto_hidden; ?>" />

              <!-- Box Defeitos -->
              <div class="box-defeitos" style="margin-top: 20px;">
                <?php
                if (!empty($produto_hidden)) {
                  $defeitos_produtos_b = $_POST['defeitos_produtos'];
                  $sql = "SELECT DISTINCT
                        tbl_defeito_constatado.defeito_constatado,
                        tbl_defeito_constatado.descricao
                      FROM tbl_defeito_constatado_solucao
                      JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                      WHERE
                        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado_solucao.produto = {$produto_hidden}
                      ORDER BY tbl_defeito_constatado.descricao ASC";
                  $res = pg_query($con, $sql);

                  if(pg_num_rows($res) > 0){

                    $result = "<strong>Dúvidas / Defeitos</strong> <br />";
                    $result .= "<select name='defeitos_produtos' class='frm' id='defeitos_produtos' onchange='busca_solucao_produto(this.value)'>";
                    $result .= "<option value=''></option>";

                    for($i = 0; $i < pg_num_rows($res); $i++){
                      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
                      $descricao = pg_fetch_result($res, $i, "descricao");
                      if ($defeitos_produtos_b == $defeito_constatado){
                        $checked_b = "selected";
                      }else{
                        $checked_b = "";
                      }

                      $result .= "<option value='$defeito_constatado' {$checked_b}>$descricao</option>";
                    }

                    $result .= "</select>";

                    echo $result;
                  }
                }
                ?>
              </div>

              <!-- Box Solucoes -->
              <div class="box-solucoes" style="margin-top: 20px;">
                 <?php
                if (!empty($defeitos_produtos_b)) {
                  $solucoes_produtos_b = $_POST['solucoes_produtos'];

                  $sql = "SELECT DISTINCT
                        tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                        tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                        tbl_solucao.solucao,
                        tbl_solucao.descricao
                      FROM tbl_defeito_constatado_solucao
                      JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                      WHERE
                        tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                        AND tbl_defeito_constatado_solucao.produto = {$produto_hidden}
                        AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeitos_produtos_b}
                      ORDER BY tbl_solucao.descricao ASC";
                  $res = pg_query($con, $sql);

                  if(pg_num_rows($res) > 0){

                    $sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
                                  FROM tbl_dc_solucao_hd
                                  JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                  JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                  WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                                  AND tbl_defeito_constatado_solucao.produto = {$produto_hidden}
                                  AND tbl_hd_chamado.resolvido is not null
                                  AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeitos_produtos_b}";
                    $res_total_solucoes = pg_query($con, $sql_total_solucoes);

                    $total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

                    $result = "<strong>Soluções - Índices de Soluções</strong> <br />";
                    $result .= "<select name='solucoes_produtos' class='frm' id='solucoes_produtos' onchange='busca_resposta_padrao(this.value); busca_procedimento_produto(this.value, $defeito)'>";
                    $result .= "<option value=''></option>";

                    for($i = 0; $i < pg_num_rows($res); $i++){
                      $defeito_constatado_solucao = pg_fetch_result($res, $i, "defeito_constatado_solucao");
                      $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
                      $solucao = pg_fetch_result($res, $i, "solucao");
                      $descricao = pg_fetch_result($res, $i, "descricao");

                      /* Estatística */
                      $sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                                FROM tbl_dc_solucao_hd
                                JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                WHERE tbl_defeito_constatado_solucao.solucao = {$solucao}
                                AND tbl_defeito_constatado_solucao.produto = {$produto}
                                AND tbl_hd_chamado.resolvido is not null
                                AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}

                                AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
                      $res_estatistica = pg_query($con, $sql_estatistica);
                      //echo $sql_estatistica;

                      $total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

                      if($total_ds > 0){

                        $total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);

                      }else{
                        $total_porc = 0;
                      }

                      /* Fim - Estatística */

                      $descricao = $descricao." - ".$total_porc."%";
                      if ($solucoes_produtos_b == $defeito_constatado_solucao){
                        $checked_b = "selected";
                      }else{
                        $checked_b = "";
                      }

                      $result .= "<option value='$defeito_constatado_solucao' {$checked_b}>$descricao</option>";
                    }

                    $result .= "</select>";
                    echo $result;
                  }
                }
                ?>
              </div>

              <!-- Box Procedimento-->
              <div class="box-procedimento" style="margin-top: 20px;">
                <?php
                if (!empty($solucoes_produtos_b)) {
                  $sql_procedimento = "SELECT
                                          tbl_produto.referencia AS ref_produto,
                                          tbl_produto.descricao AS desc_produto,
                                          tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento
                                        FROM tbl_defeito_constatado_solucao
                                          JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                          JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                          JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto
                                        WHERE
                                          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                                        AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_proced}
                                        AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$solucao_proced}
                                        AND tbl_defeito_constatado_solucao.produto = {$produto_proced}; ";

                  $res_procedimento = pg_query($con,$sql_procedimento);

                  $procedimento_solucao = pg_fetch_result($res_procedimento, 0, procedimento);

                  if (count($procedimento_solucao) == 0) {

                    $procedimento_solucao = "Solução sem procedimento cadastrado.";

                  }

                  $result = "<strong>Procedimentos</strong> <br />";
                  $result .= "<textarea id='solucao_procedimento_prod' rows='4' cols='50'>{$procedimento_solucao}</textarea>";

                  echo $result;
                }
                ?>
              </div>

              <!-- Box Produtos-->
              <div style="clear: both;"></div>
              <div class="box-link" style="display : <?php echo (strlen($produto_hidden) > 0) ? 'block' : 'none'; ?>">
                <p align="center" class='link'>
                  <a href="cadastro_defeitos_solucoes.php?produto_referencia=<?php echo $referencia; ?>" target="_blank" id="link_href">Cadastrar / Editar Dúvidas e Soluções para Produtos</a>
                </p>
              </div>
            </div>

              <?php
              }
              }
            ?>

            </div>
            <div id='div_produto_de' style='position:relative;text-align:left;margin-top:1em;padding-left:24px;<?=$mostrar_produto_de?>'>
                <label for="produto_de">Produto de&nbsp;</label>
                <select name="produto_de" id="produto_de" class='frm' style='width: 139px;'>
                    <option value=""></option>
                    <option value="Construtora" <? if($produto_de=='Construtora') echo 'selected'; ?>>Construtora</option>
                    <option value="Revenda" <? if($produto_de=='Revenda') echo 'selected'; ?>>Revenda</option>
                    <option value="Consumidor" <? if($produto_de=='Consumidor') echo 'selected'; ?>>Consumidor Final</option>
                </select>
            </div>
            <?php
            if($login_fabrica == 1 && $admin_sac == true){
              $mostrar_sac = "display: inline";
            }
            ?>
            <div id='sac' style='position:relative;text-align:left;margin-top:1em;padding-left:24px;<?=$mostrar_sac?>'>
                <br><br>
                <?php if($login_fabrica <> 1){ ?>
                  <label for="nome_cliente">Nome do Cliente <span class='vermelho'><?php if($login_fabrica <> 1){ ?>* <?php }?>&nbsp;</span></label>
                    <input type='text' class='frm' name='nome_cliente' value='<?=$nome_cliente?>'>&nbsp;&nbsp;
                  <label for="atendente">Atendente<span class='vermelho'>*&nbsp;</span></label>
                    <input type='text' class='frm' name='atendente' value='<?=$atendente_sac?>'>
                  <br><br>
                <?php } ?>
                <label title='Nº de chamado SAC/Help-Desk' for="hd_chamado_sac">Nº do chamado SAC&nbsp;</label>
                    <input type='text' class='frm' name='hd_chamado_sac' value='<?=$hd_chamado_sac?>' class='numerico'>
            </div>
            <div style='text-align:left;position:relative;<?=$mostrar_pedido?>' id='pedido_pend'>
                <div id='distrib' style='padding-left:20px;text-align:left;margin-top:1em;display:inline-block;'>
                    <label>Distribuidor</label>
                    <input type='text' name='distribuidor' value='<?=$distribuidor?>' class='frm'>
                    <br>
                </div>
                <div style='padding-left:20px;text-align:left;margin-top:1em;display:inline-block;'>
                    <label>Número de Pedido</label>
                    <input type='text' name='pedido' id='pedido' class='frm numerico' value='<?=$pedido?>' size='15'>
                    &nbsp;&nbsp;&nbsp;
                    <label for="">Data do pedido</label>
                    <?php
                        if($login_fabrica == 1){
                            if($data_pedido != ""){
                                $data_pedido = date('d\/m\/Y',strtotime($data_pedido));
                            }
                        }
                    ?>
                    <input type='text' name='data_pedido' value='<?=$data_pedido?>' class='frm'>
                </div>
                <div id='id_peca_multi' style='padding-left:20px;<?echo $display_multi_pecas;?>'>
                    <br><br>
                    <label for="">Ref:</label>
                    <input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;<IMG src='imagens/btn_buscar5.gif' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'referencia')"  style='cursor:pointer;'>
                    &nbsp;&nbsp;&nbsp;
                    <label for="">Descrição:</label>&nbsp;
                    <input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="30" maxlength="50">&nbsp;<IMG src='imagens/btn_buscar5.gif' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi,document.frm_chamado.peca_descricao_multi,'descricao')"  style='cursor:pointer;' align='absmiddle'>
                    <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();'>
                    <br>
                    <span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
                    <br>
                    <select multiple="multiple" SIZE='6' id='peca_faltante' class='select ' name="peca_faltante[]" class='frm'>
                    <?
                        if(count($peca_faltante) > 0) {
                            for($i =0;$i<count($peca_faltante);$i++) {

                                $sql = " SELECT tbl_peca.referencia,
                                                tbl_peca.descricao
                                        FROM tbl_peca
                                        WHERE fabrica = $login_fabrica
                                        AND   referencia  = '".$peca_faltante[$i]."'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                    echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
                                }
                            }
                        }
                    ?>
                    </select>
                    <input type="button" value="Remover" onClick="delItPeca();" class='frm'>
                </div>
            </div>
            <div style='position:relative;margin-top:1em;<?=$mostrar?>' id='tipos_atualizacao'>
                <label>Tipo de Atualização</label>
                <select name='tipo_atualizacao' class='frm' id='tipo_atualizacao'>
                    <option value=''></option>
    <?
                    foreach ($a_tipos as $tipo=>$descricao) {
                        echo CreateHTMLOption($tipo, $descricao, $_POST['tipo_atualizacao']);
                    }
    ?>          </select>
                <br>
            </div>
            <br>
            <div style='position:relative;<?=$mostrar_fone?>' id='telefone'>
                Novo telefone&nbsp; <input type='text' name='fone' value='<?=$fone?>' class='frm numerico'>
            </div>
            <div style='position:relative;<?=$mostrar_email?>' id='email'>
                Novo E-mail&nbsp;   <input type='text' name='email' value='<?=$email?>' class='frm'>
            </div>
            <div style='position:relative;<?=$mostrar_linhas?>' id='linhas_atendimento'>
                <fieldset style='width: 90%;margin-left:auto;margin-right:auto'>
                    <legend><?=($login_fabrica == 1) ? "Atualizar linhas..."  : "Gostaria atender as linhas...";?></legend><?
                        if ($login_fabrica == 1) {
                         $array_linhas = array(
                            "ferramentas_dewalt"       => "Ferramentas DEWALT",
                            "ferramentas_dewalt_black" =>"Ferramentas Black&Decker",
                            "ferramentas_stanley"      => "Ferramentas Stanley",
                            "ferramentas_pneumaticas"  => "Ferramentas Pneumáticas",
                            "compressores"             => "Compressores",
                            "lavadores"                => "Lavadoras",
                            "motores"                  => "Motores",
                            "eletro_protateis"         => "Eletro-portáteis");

                          foreach ($array_linhas as $linha_cod => $linha_desc) {
                            if ($linha_cod == "compressores") echo "<br>";
                            if (is_array($post_linhas)) $sel = (in_array($linha_cod, $post_linhas)) ? ' checked' : '';
                            echo "<input type='checkbox' name='linhas[]' value='$linha_cod'$sel /><label>&nbsp;$linha_desc</label>&nbsp;&nbsp";
                          }
                        } else {
                          foreach($a_linhas as $linha => $linha_desc) {
                              echo "<input type='checkbox' name='linhas[]' value='$linha' /><label>$linha_desc</label>&nbsp;&nbsp;";
                          }
                        }?>
                </fieldset>
            </div>
            <div style='position:relative;<?=$mostrar_banco?>' id='dados_bancarios'>
                <table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
                <caption>Conta deve ser de pessoa jurídica</caption>
                <tr >
                    <td colspan='2' width = '100%'>BANCO</td>
                </tr>
                <tr >
                    <td colspan='2'>
                        <?
                        $sqlB = "SELECT codigo, nome
                                FROM tbl_banco
                                ORDER BY nome";
                        $resB = pg_exec($con,$sqlB);
                        if (pg_numrows($resB) > 0) {
                            echo "<select name='banco' size='1' class='frm'>\n";
                            echo "<option value=''></option>";
                            for ($x = 0 ; $x < pg_numrows($resB) ; $x++) {
                                $aux_banco     = pg_result($resB,$x,codigo);
                                $aux_banconome = pg_result($resB,$x,nome);
                                echo "<option value='" . $aux_banco . "'";
                                if ($banco == $aux_banco) echo " selected";
                                echo ">$aux_banco - $aux_banconome</option>\n";
                            }
                            echo "</select>\n";
                        }
                        ?>
                    </td>
                </tr>
                <tr >
                    <td width = '50%'>AGÊNCIA</td>
                    <td width = '50%'>CONTA</td>
                </tr>
                <tr >
                    <td width = '50%'>
                    <input type="text" class='frm numerico' name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
                    <?php
                    if (strlen($agencia)>0){
                        echo $readonly;
                    }
                    ?>></td>
                    <td width = '50%'>
                    <input type="text" class='frm numerico' name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
                    <?php
                    if (strlen($conta)>0){
                        echo $readonly;
                    }
                    ?>></td>
                </tr>
                </table>
            </div>

            <div style='position:relative;<?=$mostrar_coleta?>' id='solicitacao_coleta'>
                <fieldset style="width:220px;">
                    <?php
                        echo $solic_coleta;
                        $checked_peca = ($solic_coleta == "pecas") ? "checked" : "";
                        $checked_produto = ($solic_coleta == "produtos") ? "checked" : "";
                    ?>
                    <legend>Solicitação de coleta</legend>
                        <input type="radio" name="solic_coleta" value="pecas" onclick="mostraCampos(this.value,'coleta')" <?=$checked_peca?>>Peças&nbsp;
                        <input type="radio" name="solic_coleta" value="produtos" onclick="mostraCampos(this.value,'coleta')" <?=$checked_produto?>>Produtos
                </fieldset>

                <div id="pecas" style="display:none">
                    <fieldset style="width:220px;float:left;">
                        <?php
                            $checked_1 = ($tipo_dev_peca == "1") ? "checked" : "";
                            $checked_2 = ($tipo_dev_peca == "2") ? "checked" : "";
                        ?>
                        <legend>Peças</legend>
                            Tipo de devolução <br />
                            <input type="radio" name="tipo_dev_peca" value="1" onclick="mostraCamposPecas(this.value,'coleta')" <?=$checked_1?>>Peça enviada com defeito<br>
                            <? if ($login_fabrica <> 42) { ?>
                            <input type="radio" name="tipo_dev_peca" value="2" onclick="mostraCamposPecas(this.value,'coleta')" <?=$checked_2?>>Devolução de peça para análise
                            <? } ?>
                    </fieldset>
                    <div id="peca_enviada" style="float:right;margin-right:-5px;display:none;">
                        <table>
                            <tr>
                                <td>
                                    NF de origem <br>
                                    <input type="text" name="nf_origem_peca" value="<?=$nf_origem_peca?>" size="15" class="frm">
                                </td>
                                <td>
                                    Data da NF <br>
                                    <input type="text" name="data_nf_peca" value="<?=$data_nf_peca?>" size="15" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="">Ref:</label> <br>
                                    <input class='frm' type="text" name="peca_referencia_multi2"  id="peca_referencia_multi2" value="" size="12" maxlength="20">&nbsp;<IMG src='../imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi2,document.frm_chamado.peca_descricao_multi2,'referencia')"  style='cursor:pointer;'>
                                </td>
                                <td>
                                    <label for="">Descrição:</label><br>
                                    <input class='frm' type="text" name="peca_descricao_multi2" id="peca_descricao_multi2" value="" size="30" maxlength="50">&nbsp;<IMG src='../imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi2,document.frm_chamado.peca_descricao_multi2,'descricao')"  style='cursor:pointer;' align='absmiddle'>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca2();'>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center">
                                    <span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>

                                </td>
                            </tr>

                            <tr>
                                <td colspan="2">

                                    <select  multiple="multiple" size='6' id='peca_faltante2' class='select' name="peca_faltante2[]" class='frm'>
                                    <?
                                        if(count($peca_faltante2) > 0) {

                                            for($i =0;$i<count($peca_faltante2);$i++) {

                                                $sql = " SELECT tbl_peca.referencia,
                                                                tbl_peca.descricao
                                                        FROM tbl_peca
                                                        WHERE fabrica = $login_fabrica
                                                        AND   referencia  = '".$peca_faltante2[$i]."'";
                                                $res = pg_query($con,$sql);
                                                if(pg_num_rows($res) > 0){
                                                    echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
                                                }
                                            }
                                        }
                                    ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input type="button" value="Remover" onClick="delItPeca2();" class='frm'>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    NF venda <br>
                                    <input type="text" name="nf_venda_peca" value="<?=$nf_venda_peca?>" size="15" class="frm">
                                </td>
                                <td>
                                    Data NF venda <br>
                                    <input type="text" name="data_nf_venda_peca" value="<?=$data_nf_venda_peca?>" size="15" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    Defeito constatado <br>
                                    <input type="text" name="defeito_constatado_peca" value="<?=$defeito_constatado_peca?>" size="35" class="frm">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="devolucao_peca" style="float:right;margin-right:-5px;display:none;">
                        <table>
                            <tr>
                                <td>
                                    Responsável pela solicitação de devolução <br>
                                    <input type="text" name="resp_devolucao_peca" size="52" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Motivo da devolução <br>
                                    <input type="text" name="motivo_devolucao_peca" size="52" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Número do Extrato de serviço <br>
                                    <input type="text" name="extratos_peca" size="15" class="frm">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="produtos" style="display:none">
                    <fieldset style="width:220px;float:left;">
                        <?php
                            $checked_1 = ($tipo_dev_prod == "1") ? "checked" : "";
                            $checked_2 = ($tipo_dev_prod == "2") ? "checked" : "";
                            $checked_3 = ($tipo_dev_prod == "3") ? "checked" : "";
                        ?>
                        <legend>Produtos</legend>
                            Tipo de devolução <br />
                            <? if ($login_fabrica <> 42) { ?>
                                <input type="radio" name="tipo_dev_prod" value="1" onclick="mostraCamposProduto(this.value,'coleta')" <?=$checked_1?> >Produto trocado pela fábrica<br>
                            <? } ?>
                            <input type="radio" name="tipo_dev_prod" value="2" onclick="mostraCamposProduto(this.value,'coleta')"  <?=$checked_2?> >Produto para análise da fábrica<br>
                            <input type="radio" name="tipo_dev_prod" value="3" onclick="mostraCamposProduto(this.value,'coleta')"  <?=$checked_3?> >Produto novo na embalagem
                    </fieldset>
                    <div id="produto_fabrica" style="float:right;margin-right:-5px;display:none;">
                        <table>
                            <tr>
                                <td>
                                    NF de origem <br>
                                    <input type="text" name="nf_origem_prod" size="15" class="frm" value="<?=$nf_origem_prod?>">
                                </td>
                                <td>
                                    Data da NF <br>
                                    <input type="text" name="data_nf_prod" size="15" class="frm" value="<?=$data_nf_prod?>">
                                </td>
                            </tr>
                            <tr id="modelos_produtos" style="display:none;">
                                <td>
                                    Produto <br> <input type='text' class='frm' name='referencia2' id='referencia2' size='20' value='<?=$referencia?>'>
                                    <input type='hidden' name='voltagem' size='20'>
                                    <img src='../imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao2,document.frm_chamado.referencia2) " height='22px' style='cursor: pointer'>
                                </td>
                                <td>
                                    Descrição <br> <input type='text' class='frm' name='descricao2' id='descricao2' size='20' value='<?=$descricao_produto?>'>
                                    <img src='../imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao2,document.frm_chamado.referencia2) " height='22px' style='cursor: pointer'>
                                </td>
                            </tr>
                            <tr id="produto_fabrica_analise" style="display:none;">
                                <td colspan="2">
                                    Responsável pela solicitação de devolução <br>
                                    <input type="text" name="resp_devolucao_produto" size="52" class="frm">
                                </td>
                            </tr>
                            <tr id="produto_novo_embalagem_os" style="display:none;">
                                <td colspan="2">
                                    Orde(ns) de serviço(s) <br>
                                    <input type='text' name='os_coleta' size='15' value='<?=$os?>' class='numerico frm'>
                                </td>
                            </tr>
                            <tr id="produto_novo_embalagem_motivo" style="display:none;">
                                <td colspan="2">
                                    Motivo da devolução <br>
                                    <input type="text" name="motivo_dev_produto" value="<?=$motivo_dev_produto?>" size="52" class="frm">
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            </div>

            <div style='position:relative;margin-top:1em;<?=$mostrar_duvida?>' id='pagamento_garantia'>
                <?php
                    $checked_1 = ($duvida == "aprova") ? "checked" : "";
                    $checked_2 = ($duvida == "pendente") ? "checked" : "";
                    $checked_3 = ($duvida == "bloqueado") ? "checked" : "";
                    $checked_4 = ($duvida == "documentos") ? "checked" : "";
                    $checked_5 = ($duvida == "duvida_extrato") ? "checked" : "";
                    $checked_6 = ($duvida == "pagamento_nf") ? "checked" : "";
                ?>
                <fieldset style="width:300px;float:left;text-align:left;">
                    <legend>Dúvida referente</legend>
                    <? if ($login_fabrica <> 42) { ?>
                    <input type="radio" name="duvida" id="duvida" value="aprova" onclick="mostraCamposDuvida(this.value)" <?=$checked_1?>>&nbsp;Aprovação de extrato <br>
                    <input type="radio" name="duvida" id="duvida" value="pendente" onclick="mostraCamposDuvida(this.value)" <?=$checked_2?>>&nbsp;Extrato pendente <br>
                    <input type="radio" name="duvida" id="duvida" value="bloqueado" onclick="mostraCamposDuvida(this.value)" <?=$checked_3?>>&nbsp;Extrato bloqueado <br>
                    <input type="radio" name="duvida" id="duvida" value="documentos" onclick="mostraCamposDuvida(this.value)" <?=$checked_4?>>&nbsp;Documentação enviada para a fábrica
                    <?  } else { ?>
                    <input type="radio" name="duvida" id="duvida" value="duvida_extrato" <?=$checked_5?>>&nbsp;Dúvida no Extrato <br>
                    <input type="radio" name="duvida" id="duvida" value="pagamento_nf" <?=$checked_6?>>&nbsp;Pagamento de NFs
                    <? } ?>
                </fieldset>

                <table id="campos_duvida" style="display:none;">
                    <tr>
                        <td id="data_fech">
                            Data fechamento <br>
                            <input type="text" name="data_fechamento" size="15" class="frm" value="<?=$data_fechamento?>">
                        </td>
                        <td id="data_env">
                            Data envio <br>
                            <input type="text" name="data_envio" size="15" class="frm" value="<?=$data_envio?>">
                        </td>
                    </tr>

                    <tr>
                        <td id="extrato_num">
                            Número extrato <br>
                            <input type="text" name="num_extrato" size="15" class="frm" value="<?=$extrato_duvida?>">
                        </td>
                        <td id="obj_num">
                            Número do objeto <br>
                            <input type="text" name="num_objeto" size="15" maxlength="13" class="frm" value="<?=$objeto_duvida?>">
                        </td>
                    </tr>
                </table>
            </div>

            <?php
            if ($login_fabrica == 42) {
            ?>
                <div id="solicita_informacao_tecnica" style='position:relative;margin-top:1em;<?=$mostrar_solicita_informacao_tecnica?>' >
                    <fieldset style="width:300px;float:left;text-align:left;">
                    <legend>Solicita Informação Técnica referente</legend>
                        <input type="radio" name="solicita_informacao_tecnica" value="vista_explodida" <?=(($solicita_informacao_tecnica == 'vista_explodida') ? 'CHECKED' : '' )?> />&nbsp; Vistas Explodidas <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="informativo_tecnico" <?=(($solicita_informacao_tecnica == 'informativo_tecnico') ? 'CHECKED' : '' )?> />&nbsp; Informativo Técnico <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="esquema_eletrico" <?=(($solicita_informacao_tecnica == 'esquema_eletrico') ? 'CHECKED' : '' )?> />&nbsp; Esquema Elétrico <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="procedimento_manutencao" <?=(($solicita_informacao_tecnica == 'procedimento_manutencao') ? 'CHECKED' : '' )?> />&nbsp; Procedimento de Manutenção <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="analise_garantia" <?=(($solicita_informacao_tecnica == 'analise_garantia') ? 'CHECKED' : '' )?> />&nbsp; Análise de Garantia <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="manual_usuario" <?=(($solicita_informacao_tecnica == 'manual_usuario') ? 'CHECKED' : '' )?> />&nbsp; Manual de Usuário <br />
                        <input type="radio" name="solicita_informacao_tecnica" value="outro" <?=(($solicita_informacao_tecnica == 'outro') ? 'CHECKED' : '' )?> /> Outro <br />
                        <input type="text" name="solicita_informacao_tecnica_outro" value="<?=$solicita_informacao_tecnica_outro?>" <?=(($solicita_informacao_tecnica == 'outro') ? "style='display: block;'" : "style='display: none;'" )?> />
                    </fieldset>
                </div>

                <div id="sugestao_critica" style='position:relative;margin-top:1em;<?=$mostrar_solicita_informacao_tecnica?>' >
                    <fieldset style="width:300px;float:left;text-align:left;">
                    <legend>Sugestões, críticas, reclamações ou elogios</legend>
                        <input type="radio" name="sugestao_critica" value="sugestao" <?=(($sugestao_critica == 'vista_explodida') ? 'CHECKED' : '' )?> />&nbsp; Sugestões <br />
                        <input type="radio" name="sugestao_critica" value="critica" <?=(($sugestao_critica == 'informativo_tecnico') ? 'CHECKED' : '' )?> />&nbsp; Críticas <br />
                        <input type="radio" name="sugestao_critica" value="reclamacao" <?=(($sugestao_critica == 'esquema_eletrico') ? 'CHECKED' : '' )?> />&nbsp; Reclamações <br />
                        <input type="radio" name="sugestao_critica" value="elogio" <?=(($sugestao_critica == 'procedimento_manutencao') ? 'CHECKED' : '' )?> />&nbsp; Elogios
                    </fieldset>
                </div>
            <?php
            }
            ?>

            <div style='position:relative;<?=$mostrar_embarque?>' id='erro_embarque'>
                <fieldset style="width:220px;">
                    <legend>Erro Embarque</legend>
                        <?php
                            $checked_peca = ($erro_emb == "pecas") ? "checked" : "";
                            $checked_produto = ($erro_emb == "produtos") ? "checked" : "";
                        ?>
                        <input type="radio" name="erro_emb" value="pecas" onclick="mostraCampos(this.value,'embarque')" <?=$checked_peca?>>Peças&nbsp;
                        <input type="radio" name="erro_emb" value="produtos" onclick="mostraCampos(this.value,'embarque')" <?=$checked_produto?>>Produtos
                </fieldset>

                <div id="pecas_emb" style="display:none">
                    <fieldset style="width:220px;float:left;">
                        <?php
                            $checked_1 = ($tipo_emb_peca == "1") ? "checked" : "";
                            $checked_2 = ($tipo_emb_peca == "2") ? "checked" : "";
                            $checked_3 = ($tipo_emb_peca == "3") ? "checked" : "";
                        ?>
                        <legend>Peças</legend>
                            <input type="radio" name="tipo_emb_peca" value="1" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_1?>>Quantidade incorreta<br>
                            <input type="radio" name="tipo_emb_peca" value="2" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_2?>>Peça incorreta<br>
                            <input type="radio" name="tipo_emb_peca" value="3" onclick="mostraCamposPecas(this.value,'embarque')" <?=$checked_3?>>Extravio de mercadoria
                    </fieldset>

                    <div id="peca_emb_campos" style="float:right;margin-right:-5px;display:none;">
                        <table>
                            <tr>
                                <td id="pedido_emb" colspan='3'>
                                    Pedido <br>
                                    <input type="text" name="pedido_emb_peca" size="15" value="<?=$seu_pedido?>" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td id="nf_embarque">
                                    Nota Fiscal <br>
                                    <input type="text" name="nf_embarque" size="15" value="<?=$nf_embarque?>" class="frm">
                                </td>
                                <td id="data_nf_emb">
                                    Data da NF <br>
                                    <input type="text" name="data_nf_emb" size="15" value="<?=$data_nf_emb?>" class="frm">
                                </td>
                            </tr>
                            <tr id="peca_pend_emb" style="display:none;">
                                <td colspan="2">
                                    <table>
                                        <tr >
                                            <td>
                                                <label for="">Ref:</label> <br>
                                                <input class='frm' type="text" name="peca_referencia_multi3"  id="peca_referencia_multi3" value="" size="12" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca (document.frm_chamado.peca_referencia_multi3,document.frm_chamado.peca_descricao_multi3,'referencia')"  style='cursor:pointer;'>
                                            </td>
                                            <td>
                                                <label for="">Descrição:</label><br>
                                                <input class='frm' type="text" name="peca_descricao_multi3" id="peca_descricao_multi3" value="" size="30" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="javascript: fnc_pesquisa_peca(document.frm_chamado.peca_referencia_multi3,document.frm_chamado.peca_descricao_multi3,'descricao')"  style='cursor:pointer;' align='absmiddle'>
                                            </td>
                                            <td id="qtde_enviada_emb" style="display:none;" nowrap>
                                                Qtde enviada <br>
                                                <input type="text" name="qtde_enviada_emb" size="5" value="<?=$qtde_enviada_emb?>" class="frm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca3();'>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" align="center">
                                                <span style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="3">
                                                <select multiple="multiple" SIZE='6' id='peca_faltante3' class='select ' name="peca_faltante3[]" class='frm' style='width:470px;'>
                                                <?

                                                    if(count($peca_faltante3) > 0) {
                                                        for($i =0;$i<count($peca_faltante3);$i++) {
                                                            list($ref,$qtde) = explode('|', $peca_faltante3[$i]);
                                                            $sql = " SELECT tbl_peca.referencia,
                                                                            tbl_peca.descricao
                                                                    FROM tbl_peca
                                                                    WHERE fabrica = $login_fabrica
                                                                    AND   referencia  = '".$ref."'";
                                                            $res = pg_query($con,$sql);
                                                            if(pg_num_rows($res) > 0){
                                                                echo "<option value='".pg_fetch_result($res,0,referencia);
                                                                if($qtde){
                                                                    echo "|".$qtde;
                                                                }
                                                                echo "' >";

                                                                echo pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao);
                                                                if($qtde){
                                                                    echo " - ".$qtde;
                                                                }
                                                                 echo "</option>";
                                                            }
                                                        }
                                                    }
                                                ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <input type="button" value="Remover" onClick="delItPeca3();" class='frm'>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div id="prod_emb" style="display:none">
                    <fieldset style="width:220px;float:left;">
                        <?php
                            $checked_1 = ($tipo_emb_prod == "1") ? "checked" : "";
                            $checked_2 = ($tipo_emb_prod == "2") ? "checked" : "";
                            $checked_3 = ($tipo_emb_prod == "3") ? "checked" : "";
                            $checked_4 = ($tipo_emb_prod == "4") ? "checked" : "";
                        ?>
                        <legend>Produtos</legend>
                            <input type="radio" name="tipo_emb_prod" value="1" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_1?>>Produto incorreto<br>
                            <input type="radio" name="tipo_emb_prod" value="2" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_2?>>Produto faltando acessório<br>
                            <input type="radio" name="tipo_emb_prod" value="3" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_3?>>Voltagem incorreta<br>
                            <input type="radio" name="tipo_emb_prod" value="4" onclick="mostraCamposProduto(this.value,'embarque')" <?=$checked_4?>>Quantidade incorreta
                    </fieldset>

                    <div id="prod_emb_campos" style="float:right;margin-right:-5px;display:none;">
                        <table >
                            <tr>
                                <td id="pedido_emb_prod" colspan='2'>
                                    Pedido <br>
                                    <input type="text" name="pedido_emb_prod" size="15" value="<?=$seu_pedido?>" class="frm">
                                </td>
                            </tr>
                            <tr>
                                <td id="nf_embarque_prod">
                                    Nota Fiscal <br>
                                    <input type="text" name="nf_embarque_prod" size="15" value="<?=$nf_embarque_prod?>" class="frm">
                                </td>
                                <td id="data_nf_emb_prod">
                                    Data da NF <br>
                                    <input type="text" name="data_nf_emb_prod" value="<?=$data_nf_emb_prod?>" size="15" class="frm">
                                </td>
                            </tr>
                            <tr id="modelo_prod_emb" style="display:none">
                                <td>
                                    Modelo <br> <input type='text' class='frm' name='referencia3' id='referencia3' size='20' value='<?=$referencia3?>'>
                                    <input type='hidden' name='voltagem' size='20'>
                                    <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao3,document.frm_chamado.referencia3) " height='22px' style='cursor: pointer'>
                                </td>
                                <td>
                                    Descrição <br> <input type='text' class='frm' name='descricao3' id='descricao3' size='20' value='<?=$descricao_produto?>'>
                                    <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao3,document.frm_chamado.referencia3) " height='22px' style='cursor: pointer'>
                                </td>
                            </tr>

                            <tr id="modelo_prod_env_emb">
                                <td>
                                    Modelo enviado <br> <input type='text' class='frm' name='referencia4' id='referencia4' size='20' value='<?=$modelo_enviado?>'>
                                    <input type='hidden' name='voltagem' size='20'>
                                    <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao4,document.frm_chamado.referencia4) " height='22px' style='cursor: pointer'>
                                </td>
                                <td>
                                    Descrição <br> <input type='text' class='frm' name='descricao4' id='descricao4' size='20' value='<?=$modelo_enviado_desc?>'>
                                    <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                     onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.descricao4,document.frm_chamado.referencia4) " height='22px' style='cursor: pointer'>
                                </td>
                            </tr>

                            <tr id="acess_faltantes_emb">
                                <td colspan="2">
                                    Acessório(s) faltante(s) <br>
                                    <input type="text" name="acess_faltantes_emb" value="<?=$acess_faltantes_emb?>" class="frm">
                                </td>
                            </tr>

                            <tr id="qtde_enviada_emb_prod">
                                <td colspan='2'>
                                    <table width='450'>
                                        <tr>
                                            <td>
                                                Modelo <br> <input type='text' class='frm' name='produto_referencia_multi' id='produto_referencia_multi' size='20' value='<?=$referencia5?>'>
                                                <input type='hidden' name='voltagem' size='20'>
                                                <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                                onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.produto_descricao_multi,document.frm_chamado.produto_referencia_multi) " height='22px' style='cursor: pointer'>
                                            </td>
                                            <td>
                                                Descrição <br> <input type='text' class='frm' name='produto_descricao_multi' id='produto_descricao_multi' size='20' value='<?=$modelo_enviado_desc5?>'>
                                                <img src='imagens/lupa.png' border='0' align='absmiddle' height='18'
                                                 onclick="javascript: fnc_pesquisa_produto2 (document.frm_chamado.produto_descricao_multi,document.frm_chamado.produto_referencia_multi) " height='22px' style='cursor: pointer'>
                                            </td>
                                            <td >
                                                Qtde enviada <br>
                                                <input type="text" name="qtde_enviada_emb_prod" id='produto_qtde_enviado' value="<?=$qtde_enviada_emb_prod?>" size="5" class="frm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3">
                                                <input type='button' name='adicionar_produto' id='adicionar_produto' value='Adicionar' class='frm' onClick='addItProduto();'>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" align="center">
                                                <span style='font-weight:normal;color:gray;font-size:10px'>(Selecione o produto, informe a quantidade e clique em 'Adicionar')</span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td colspan="3">
                                                <select multiple="multiple" SIZE='6' id='produto_faltante' class='select ' name="produto_faltante[]" class='frm' style="width:440px;font-size:10px;font-weight:bold">
                                                <?
                                                    if(count($produto_faltante) > 0) {
                                                        for($i =0;$i<count($produto_faltante);$i++) {

                                                            $sql = " SELECT tbl_produto.referencia,
                                                                            tbl_produto.descricao
                                                                    FROM tbl_produto
                                                                    WHERE fabrica_i = $login_fabrica
                                                                    AND   referencia  = '".$tbl_produto[$i]."'";
                                                            $res = pg_query($con,$sql);
                                                            if(pg_num_rows($res) > 0){
                                                                echo "<option value='".pg_fetch_result($res,0,referencia)."' >".pg_fetch_result($res,0,referencia) . " - " . pg_fetch_result($res,0,descricao) ."</option>";
                                                            }
                                                        }
                                                    }
                                                ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <input type="button" value="Remover" onClick="delItProduto();" class='frm'>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            </p>
        </fieldset>
        <br />
<?
}
    if($status != "Resolvido Posto" AND $status != "Resolvido" AND $libera_interacao){


?>
        <fieldset style='text-align:left;padding-left:auto;'>
            <label>Digite o texto para o posto <span class='vermelho'>*&nbsp;</span> </label>
            <?php
                if($hd_chamado){
                    $sqlR = "SELECT status_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado ORDER BY hd_chamado_item DESC LIMIT 1";
                    $resR = pg_query($con,$sqlR);
                    if(pg_num_rows($resR) > 0){
                      $resposta_tipo = pg_fetch_result($resR,0,status_item);
                    }
                    if ($login_fabrica == 1) {
                      $sqlPe = "SELECT leitura_pendente FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado AND leitura_pendente = 't' LIMIT 1";
                      $resPe = pg_query($con,$sqlPe);
                      if(pg_num_rows($resPe) > 0){
                          $pendente_acompanhamento = pg_fetch_result($resPe,0,leitura_pendente);
                      }
                    }

                    $sqlR = "SELECT count(status_item) AS total_acomp FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado AND status_item = 'Em Acomp.'";
                    $resR = pg_query($con,$sqlR);
                    if(pg_num_rows($resR) > 0){
                        $total_acomp = pg_result($resR,0,0);
                    }
                }
            ?>

            <p>
            <?php
            if($admin_sac == false){
                if ($login_fabrica == 1) {
                    $click_p = "onclick='fnc_tipo_atendimento(this)'";
                    $click_pa = "onclick='fnc_pendente_encerrar_acompanhemento(this)'";
                }else{
                    $click_p = "";
                    $click_pa = "";
                }

                ?>
                <input type="radio" name="tipo_resposta" value="Em Acomp." <?php echo ($resposta_tipo != "Resp.Conclusiva") ? "checked" : "";?> <?=$click_p?>>Em acompanhamento &nbsp;&nbsp;
                <input type="radio" name="tipo_resposta" value="Resp.Conclusiva" <?php echo ($resposta_tipo == "Resp.Conclusiva") ? "checked" : "";?> <?=$click_p?> >Resposta conclusiva
                <?php
                $checked_encerra = '';
                if ($login_fabrica == 1) {
                  if ($resposta_tipo == "Resp.Conclusiva") {
                    $dis_p = "disabled";
                  }else{
                    $dis_p = "";
                  }
                  if ($pendente_acompanhamento == 't') {
                    $check_p = "checked";
                  }
                  if ($resposta_tipo == 'Em Acomp. Encerra') {
                    $checked_encerra = "checked";
                  }

                  echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='pendente_acompanhamento' value='pendente_acomp' $dis_p $check_p $click_pa >Pendente";
                }
            } ?>
            <?php
                if($resposta_tipo != "Resp.Conclusiva" AND $total_acomp > 0){
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='encerrar_acompanhamento' value='encerrar_acomp' $checked_encerra $click_pa>Encerrar Acompanhamento";
                }

            ?>
            <? if($login_fabrica != 1 && !in_array($aDados['status'], array('Cancelado', 'Resolvido', 'Resolvido Posto')) and !empty($hd_chamado)) { ?>
                <input type="radio" name="cancelar_chamado" value="cancelar" />Cancelar Chamado
            <? } ?>
            </p>

            <?php 
            
            if ($login_fabrica == 3 AND strlen(trim($produto_referencia_cdd)) > 0 AND strpos($categoria,'Técnicas') AND ($status == 'Aguardando Posto' OR $status == 'Aguardando Fábrica') ) {

              if(strlen($produto_referencia_cdd) > 0){
                $sql_prod = "SELECT produto FROM tbl_produto WHERE fabrica_i = $login_fabrica AND referencia = '$produto_referencia_cdd' LIMIT 1;";
                $res_prod = pg_query($con,$sql_prod);
                if(pg_num_rows($res_prod) >0 ){
                  $produto_id = pg_fetch_result($res_prod, 0, produto);
                }
              }

              if (strlen($_POST['defeito'])>0) {
                $id_defeito_pesquisa = $_POST['defeito'];
                $id_solucao_pesquisa = $_POST['solucao'];
              }elseif (strlen($id_defeito_constatado) > 0) {
                $id_defeito_pesquisa = $id_defeito_constatado;
                $id_solucao_pesquisa = $id_defeito_constatado_solucao;
              }
              
                  $sqlVerificaProdutosDesconsiderar = "
                    SELECT tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' as produtos_desconsiderar,
                         tbl_defeito_constatado_solucao.defeito_constatado_solucao
                    FROM tbl_defeito_constatado_solucao
                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                    JOIN tbl_produto ON tbl_produto.produto = {$produto_id}
                    JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
                    AND tbl_familia.familia = tbl_produto.familia
                    AND tbl_familia.fabrica = {$login_fabrica}
                    WHERE
                    tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                    AND tbl_defeito_constatado_solucao.ativo IS TRUE
                    AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' != '[]'
                    AND tbl_defeito_constatado_solucao.campos_adicionais->>'produtosDesconsiderar' IS NOT NULL";
                $resVerificaProdutosDesconsiderar = pg_query($con, $sqlVerificaProdutosDesconsiderar);

                $arrDefeitosDesc = [];
                while ($dadosDesc = pg_fetch_object($resVerificaProdutosDesconsiderar)) {
                  $arrDesconsiderar = json_decode($dadosDesc->produtos_desconsiderar);

                  if (in_array($produto_id, $arrDesconsiderar)) {
                    $arrDefeitosDesc[] = (int) $dadosDesc->defeito_constatado_solucao;
                  }

                }

                if (count($arrDefeitosDesc) > 0) {
                  $condDefDescImplode = "AND tbl_defeito_constatado_solucao.defeito_constatado_solucao NOT IN (".implode(",",$arrDefeitosDesc).")";
                }
                
                $sqlx = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao)
                      tbl_defeito_constatado.defeito_constatado,
                      tbl_defeito_constatado.descricao
                    FROM tbl_defeito_constatado_solucao
                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                    WHERE
                      tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                      AND tbl_defeito_constatado_solucao.ativo IS TRUE
                      AND tbl_defeito_constatado_solucao.produto = {$produto_id}
                    UNION
                    SELECT DISTINCT ON (UPPER(tbl_defeito_constatado.descricao))
                      tbl_defeito_constatado.defeito_constatado,
                      tbl_defeito_constatado.descricao
                    FROM tbl_defeito_constatado_solucao
                    JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                    JOIN tbl_produto ON tbl_produto.produto = {$produto_id}
                    JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
                    AND tbl_familia.familia = tbl_produto.familia
                    AND tbl_familia.fabrica = {$login_fabrica}
                    WHERE
                      tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                      AND tbl_defeito_constatado_solucao.ativo IS TRUE
                    {$condDefDescImplode}";
                $resx = pg_query($con, $sqlx);
              ?>
              <input type="hidden" name="produto_id_resp" id="produto_id_resp" value="<?=$produto_id?>" />
              <div class="box-link">
                <p align="center" class='link'>
                  <a href="cadastro_defeitos_solucoes.php?prod=<?=$produto_id?>" target="_blank">Cadastrar / Editar Dúvidas e Soluções para Produtos</a>
                </p>
              </div>
              <!-- Defeito / Solução -->
              <?php
              if (strlen($id_defeito_pesquisa) > 0 OR pg_num_rows($resx) > 0) {?>
              <table class="esconde" style="width:100%">
                <tr>
                  <td><strong>Defeito</strong></td>
                  <td>
                  <div class="box-solucao-resp-titulo">
                    <?php
                    if (strlen($id_solucao_pesquisa) > 0 ){
                      echo "<strong>Solução</strong>";
                    }
                    ?>
                  </div>
                  </td>
                </tr>
                <tr>
                  <td>
                  <div class="box-defeitos-resp">
                    <select name="defeito" id="defeito" onchange='busca_solucao_produto_resp(this.value)'>
                      <option value=""></option>
                      <?php
                        foreach (pg_fetch_all($resx) as $key) {
                          if (strlen($id_defeito_pesquisa)>0) {
                            //echo $key['defeito_constatado']." - ".$defeito_constatado;
                            $selected_defeito = ($key['defeito_constatado'] == $id_defeito_pesquisa) ? "SELECTED" : '' ;
                          }
                          //$selected_defeito = (isset($defeito) and ($key['defeito_constatado'] == $defeito)) ? "SELECTED" : '' ;
                          //$key['codigo'] = (strlen($key['codigo']) > 0) ? $key['codigo']." - " : "";
                          ?>
                          <option value="<?php echo $key['defeito_constatado'];?>" <?php echo $selected_defeito; ?> >
                            <?php echo $key['descricao']; ?>
                          </option>
                          <?php
                        }

                      ?>
                    </select>

                    <br>
                    <!-- <a href='relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=defeito_constatado&grupo=HD' rel='shadowbox; width = 900; height = 450;' style='margin: 0 auto !important;'>Inserir/Alterar</a> -->
                    <a href="javascript:busca_defeitos_produto_resp();" >Atualizar</a>
                    </div>
                  </td>
                  <td>
                    <div class="box-solucao-resp">
                    <?php
                      $sql = "SELECT DISTINCT
                                tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                                tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                                tbl_solucao.solucao,
                                tbl_solucao.descricao
                              FROM tbl_defeito_constatado_solucao
                              JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                              WHERE
                                tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                                AND tbl_defeito_constatado_solucao.produto = {$produto_id}
                                AND tbl_defeito_constatado_solucao.ativo IS TRUE
                                AND tbl_defeito_constatado_solucao.defeito_constatado = {$id_defeito_pesquisa}
                              UNION
                              SELECT DISTINCT
                                tbl_defeito_constatado_solucao.defeito_constatado_solucao,
                                tbl_defeito_constatado_solucao.defeito_constatado AS defeito_constatado,
                                tbl_solucao.solucao,
                                tbl_solucao.descricao
                              FROM tbl_defeito_constatado_solucao
                              JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                              JOIN tbl_produto ON tbl_produto.produto = {$produto_id}
                              JOIN tbl_familia ON tbl_familia.familia::text = tbl_defeito_constatado_solucao.campos_adicionais->>'familia'
                              AND tbl_familia.familia = tbl_produto.familia
                              AND tbl_familia.fabrica = {$login_fabrica}
                              WHERE tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                              AND tbl_defeito_constatado_solucao.ativo IS TRUE
                              AND tbl_defeito_constatado_solucao.defeito_constatado = {$id_defeito_pesquisa}";
                      $res = pg_query($con, $sql);

                      if(pg_num_rows($res) > 0){

                        $defeito_constatado_solucao = pg_fetch_result($res, 0, 'defeito_constatado_solucao');

                        $sql_total_solucoes = "SELECT COUNT(dc_solucao_hd) AS total_solucoes
                                      FROM tbl_dc_solucao_hd
                                      JOIN tbl_defeito_constatado_solucao ON tbl_dc_solucao_hd.defeito_constatado_solucao = tbl_defeito_constatado_solucao.defeito_constatado_solucao
                                      JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                      WHERE tbl_dc_solucao_hd.fabrica = {$login_fabrica}
                                      AND tbl_defeito_constatado_solucao.produto = {$produto_id}
                                      AND tbl_hd_chamado.resolvido is not null
                                      AND tbl_defeito_constatado_solucao.defeito_constatado = {$id_defeito_pesquisa}";
                        $res_total_solucoes = pg_query($con, $sql_total_solucoes);

                        $total_solucoes = pg_fetch_result($res_total_solucoes, 0, "total_solucoes");

                        $result = "";
                        $result .= "<select name='solucao' id='solucao' onchange='busca_resposta_padrao_resp(this.value); busca_procedimento_produto_resp(this.value, $defeito_constatado_solucao)'>";
                        $result .= "<option value=''></option>";

                        for($i = 0; $i < pg_num_rows($res); $i++){
                          $defeito_constatado_solucao = pg_fetch_result($res, $i, "defeito_constatado_solucao");
                          $defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");
                          $solucao = pg_fetch_result($res, $i, "solucao");
                          $descricao = pg_fetch_result($res, $i, "descricao");

                          /* Estatística */
                          $sql_estatistica = "SELECT COUNT(tbl_dc_solucao_hd.dc_solucao_hd) AS total_ds
                                    FROM tbl_dc_solucao_hd
                                    JOIN tbl_defeito_constatado_solucao ON tbl_defeito_constatado_solucao.defeito_constatado_solucao = tbl_dc_solucao_hd.defeito_constatado_solucao
                                    JOIN tbl_hd_chamado ON tbl_dc_solucao_hd.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                                    WHERE tbl_defeito_constatado_solucao.solucao = {$solucao}
                                    AND tbl_defeito_constatado_solucao.produto = {$produto_id}
                                    AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito_constatado}
                                    AND tbl_hd_chamado.resolvido is not null
                                    AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";
                          $res_estatistica = pg_query($con, $sql_estatistica);
                          //echo $sql_estatistica;

                          $total_ds = pg_fetch_result($res_estatistica, 0, "total_ds");

                          if($total_ds > 0){

                            $total_porc = number_format(($total_ds * 100) / $total_solucoes, 1);

                          }else{
                            $total_porc = 0;
                          }

                          /* Fim - Estatística */

                          $descricao = $descricao." - ".$total_porc."%";
                          $selected_solucao = ($defeito_constatado_solucao == $id_solucao_pesquisa) ? "SELECTED" : '' ;

                          $result .= "<option value='$defeito_constatado_solucao' $selected_solucao>$descricao</option>";
                        }

                        $result .= "</select>";
                        $result .= "<br><a href='javascript:busca_solucao_produto_resp();' >Atualizar</a>";

                      }

                      echo $result;

                    ?>
                    <!-- <a href="relacionamento_diagnostico_ajaxx.php?ajax_acerto=true&tipo=solucao&grupo=HD" rel='shadowbox; width = 900; height = 450;' style='margin: 0 auto !important;'>Inserir/Alterar</a> -->
                    </div>
                  </td>
                </tr>
              </table>
              <?php
              }else{?>
              <div class="box-def-n">
                <p align="center" > <span style="color: #e00;">Defeito não cadastrado para esse produto</span> </p>
              </div>
              <?php
              }
              ?>
              <!-- Box Procedimento-->
              <div class="box-procedimento-resp">
                <?php
                if (!empty($id_solucao_pesquisa)) {
                  $sql_procedimento = "SELECT
                                          tbl_produto.referencia AS ref_produto,
                                          tbl_produto.descricao AS desc_produto,
                                          tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento
                                        FROM tbl_defeito_constatado_solucao
                                          JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado
                                          JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao
                                          JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto
                                        WHERE
                                          tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}
                                        AND tbl_defeito_constatado_solucao.defeito_constatado = {$id_defeito_pesquisa}
                                        AND tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$id_solucao_pesquisa}
                                        AND tbl_defeito_constatado_solucao.produto = {$produto_id}; ";

                  $res_procedimento = pg_query($con,$sql_procedimento);

                  $procedimento_resp = pg_fetch_result($res_procedimento, 0, procedimento);

                  if (count($procedimento_resp) == 0) {

                    $procedimento_resp = "Solução sem procedimento cadastrado.";

                  }

                  $result = "<strong>Procedimentos</strong> <br />";
                  $result .= "<textarea id='solucao_procedimento_resp' rows='4' cols='50'>{$procedimento_resp}</textarea>";

                  echo $result;
                }
                ?>
              </div>
            <?php
            }
            ?>
            <!-- <label>Digite o texto para o posto</label> -->
            <div style="width: 100%;" ><textarea name="resposta" id="resposta" rows="6" cols="30"><? echo( !count($msg_ok) || count($msg_erro) > 0 ) ? $resposta : ""; ?></textarea></div>

            <?php

            if($login_fabrica == 3){

              ?>
              <div class="box-utilizar-resposta" style="display: none;">
                <strong>Utilizar a resposta para os próximos chamados?</strong>
                <select name="utilizar_resposta" id="utilizar_resposta">
                  <option value=""></option>
                  <option value="sim">Sim</option>
                  <option value="nao">Não</option>
                </select>
              </div>
            <?php
                $boxUploader = array(
                    "div_id" => "div_anexos",
                    "prepend" => $anexo_prepend,
                    "context" => "help desk",
                    "unique_id" => $tempUniqueId,
                    "hash_temp" => $anexoNoHash
                );
                
                include "../box_uploader.php";
            } else {
?>

            <p>
                <label for="anexo">Anexo 1: </label>
                <input type="file" name="anexo[]" id="anexo" value="<? echo $anexo; ?>" />
            </p>
            <p>
                <label for="anexo">Anexo 2: </label>
                <input type="file" name="anexo[]" id="anexo" value="<? echo $anexo; ?>" />
            </p>
            <p>
                <label for="anexo">Anexo 3: </label>
                <input type="file" name="anexo[]" id="anexo" value="<? echo $anexo; ?>" />
            </p>
          <? } ?>
            <br>
            <div style="display: inline-block; width: 100%;" align='center'>
                <input type="submit" name="btnEnviar" id="btnEnviar" <?=($login_fabrica == 1)? 'value="Enviar Chamado"' : 'value="Enviar"' ?> />
              <? if(count($msg_ok)){ ?>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <button type="button" name="btnNovo" id="btnNovo" onclick='window.location="helpdesk_cadastrar.php";'>Cadastrar Novo</button>
              <? } 
              ?>
            </div>
        </fieldset>
        <br />
    <?php } ?>
</div>

<?php 
if($login_fabrica == 42){
  if(isset($alterarprodutomakita)){
    try{
      $produto_makita = $_POST['produto_makita'];
      $hd_chamado_makita = $_POST['hd_chamado'];
  
      $produto_id_sql = "SELECT produto FROM tbl_produto where referencia = '$produto_makita'"; 
      $res_produto_id = pg_query($con, $produto_id_sql);
  
      if(pg_num_rows($res_produto_id) > 0){
        $produto_id = pg_fetch_result($res_produto_id, produto);
        
        $update_produto_makita = "UPDATE tbl_hd_chamado_extra SET produto = $produto_id WHERE hd_chamado = $hd_chamado_makita";
        $res_update_makita = pg_query($con, $update_produto_makita);
  
        if (strlen(pg_last_error()) > 0) {
          exit(json_encode(array("ok" => 'false')));
        }else{
          exit(json_encode(array("ok" => 'true')));
        }
      }
    }catch(Exception $e) {
      exit(json_encode(array("ok" => 'false')));
    }
    
  }
  if(isset($alterardefeitomakita)){
    try{
      
      $defeito_makita = $_POST['defeito_makita'];
      $hd_chamado_makita = $_POST['hd_chamado'];
  
      $sqldefeito = "SELECT campos_adicionais from tbl_hd_chamado where hd_chamado = $hd_chamado_makita";
      $resdefeito = pg_query($con, $sqldefeito);

      if(pg_num_rows($resdefeito) > 0){
        $defeitoresult = '{"defeito": "'.$defeito_makita.'"}';

        $update_defeito_makita = "UPDATE tbl_hd_chamado SET campos_adicionais = '$defeitoresult' WHERE hd_chamado = $hd_chamado_makita";
        $res_update_makita = pg_query($con, $update_defeito_makita);
  
        if (strlen(pg_last_error()) > 0) {
          exit(json_encode(array("ok" => 'false')));
        }else{
          exit(json_encode(array("ok" => 'true')));
        }
      }
    }catch(Exception $e) {
      exit(json_encode(array("ok" => 'false')));
    }
    
  }
}

?>
<style type="text/css">
   .tac {
      text-align: center !important; 
    }   
</style>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>
<?include("rodape.php");?>
